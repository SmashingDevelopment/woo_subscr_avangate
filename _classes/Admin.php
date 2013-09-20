<?php

class Admin
{

		/**
	 * The WooCommerce settings tab name
	 *
	 * @since 1.0
	 */
	public static $tab_name = 'subscriptions';

	/**
	 * The prefix for subscription settings
	 *
	 * @since 1.0
	 */
	public static $option_prefix = 'woocommerce_subscriptions';
//	function __construct()
//	{
//	}

	public function init() {
		add_filter( 'product_type_selector', array($this, 'add_subscription_products_to_select' ));

		// Add subscription pricing fields on edit product page
		add_action( 'woocommerce_product_options_general_product_data', array($this, 'subscription_pricing_fields' ));

		// And also on the variations section
		add_action( 'woocommerce_product_after_variable_attributes', array($this, 'variable_subscription_pricing_fields', 10, 3 ));
		add_action('admin_menu', array($this, 'addMenuPages'));
	}

	public function addMenuPages()
	{
		$page_hook = add_submenu_page('woocommerce', __('Manage Subscriptions', 'plugin'), __('Subscriptions', 'plugin'), 'manage_woocommerce', self::$tab_name, array($this, 'subscriptions_management_page'));
		// Add the screen options tab
		add_action("load-$page_hook", array($this, 'add_manage_subscriptions_screen_options'));
	}

	public static function subscriptions_management_page()
	{

		$subscriptions_table = new Subscription_ListTable();
		$subscriptions_table->prepare_items();
		?>
		<div class="wrap">
			<div id="icon-woocommerce" class="icon32-woocommerce-users icon32"><br/></div>
			<h2><?php _e('Manage Subscriptions', 'plugin'); ?></h2>
			<?php $subscriptions_table->messages(); ?>
			<?php $subscriptions_table->views(); ?>
			<form id="subscriptions-search" action="" method="get"><?php // Don't send all the subscription meta across   ?>
				<?php $subscriptions_table->search_box(__('Search Subscriptions', 'plugin'), 'subscription'); ?>
				<input type="hidden" name="page" value="subscriptions" />
				<?php if (isset($_REQUEST['status']))
				{
					?>
					<input type="hidden" name="status" value="<?php echo esc_attr($_REQUEST['status']); ?>" />
		<?php } ?>
			</form>
			<form id="subscriptions-filter" action="" method="get">
		<?php $subscriptions_table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Outputs the screen options on the Subscription Management admin page.
	 *
	 * @since 1.3.1
	 */
	public  function add_manage_subscriptions_screen_options() {
		add_screen_option( 'per_page', array(
			'label'   => __( 'Subscriptions', 'plugin' ),
			'default' => 10,
			'option'  => self::$option_prefix . '_admin_per_page',
			)
		);
	}

		/**
	 * Add the 'subscriptions' product type to the WooCommerce product type select box.
	 *
	 * @param array Array of Product types & their labels, excluding the Subscription product type.
	 * @return array Array of Product types & their labels, including the Subscription product type.
	 * @since 1.0
	 */
	public  function add_subscription_products_to_select( $product_types ){

		$product_types[WooAvangateExpansion::NAME] = __( 'Simple subscription', 'plugin' );

		if ( class_exists( 'WC_Product_Variable_Subscription' ) )
			$product_types['variable-subscription'] = __( 'Variable subscription', 'plugin' );

		return $product_types;
	}

	/**
	 * Output the subscription specific pricing fields on the "Edit Product" admin page.
	 *
	 * @since 1.0
	 */
	public  function subscription_pricing_fields() {
		global $post;

		// Set month as the default billing period
		if ( ! $subscription_period = get_post_meta( $post->ID, '_subscription_period', true ) )
		 	$subscription_period = 'month';

		echo '<div class="options_group subscription_pricing show_if_subscription">';

		// Subscription Price
		woocommerce_wp_text_input( array(
			'id'          => '_subscription_price',
			'class'       => 'wc_input_subscription_price',
			'label'       => sprintf( __( 'Subscription Price (%s)', 'plugin' ), get_woocommerce_currency_symbol() ),
			'placeholder' => __( 'e.g. 5.90', 'plugin' ),
			)
		);

		// Subscription Period Interval
		woocommerce_wp_select( array(
			'id'          => '_subscription_period_interval',
			'class'       => 'wc_input_subscription_period_interval',
			'label'       => __( 'Subscription Periods', 'plugin' ),
			'options'     => Subscription_Manager::get_subscription_period_interval_strings(),
			)
		);

		// Billing Period
		woocommerce_wp_select( array(
			'id'          => '_subscription_period',
			'class'       => 'wc_input_subscription_period',
			'label'       => __( 'Billing Period', 'plugin' ),
			'value'       => $subscription_period,
			'description' => __( 'for', 'plugin' ),
			'options'     => Subscription_Manager::get_subscription_period_strings(),
			)
		);

		// Subscription Length
		woocommerce_wp_select( array(
			'id'          => '_subscription_length',
			'class'       => 'wc_input_subscription_length',
			'label'       => __( 'Subscription Length', 'plugin' ),
			'options'     => Subscription_Manager::get_subscription_ranges( $subscription_period ),
			'description' => sprintf( __( 'with a %s', 'plugin' ), get_woocommerce_currency_symbol() ),
			)
		);

		// Sign-up Fee
		woocommerce_wp_text_input( array(
			'id'          => '_subscription_sign_up_fee',
			'class'       => 'wc_input_subscription_intial_price',
			'label'       => sprintf( __( 'Sign-up Fee (%s)', 'plugin' ), get_woocommerce_currency_symbol() ),
			'placeholder' => __( 'e.g. 9.90', 'plugin' ),
			'description' => __( 'sign-up fee', 'plugin' ),
			)
		);

		// Trial Length
		woocommerce_wp_text_input( array(
			'id'          => '_subscription_trial_length',
			'class'       => 'wc_input_subscription_trial_length',
			'label'       => __( 'Free Trial', 'plugin' ),
			)
		);

		// Trial Period
		woocommerce_wp_select( array(
			'id'          => '_subscription_trial_period',
			'class'       => 'wc_input_subscription_trial_period',
			'label'       => __( 'Subscription Trial Period', 'plugin' ),
			'options'     => Subscription_Manager::get_available_time_periods(),
			'description' => sprintf( __( 'Include an optional period of time to wait before charging the first recurring payment. Any sign up fee will still be charged at the outset of the subscription. %s', 'plugin' ), self::get_trial_period_validation_message() ),
			'desc_tip'    => true,
			'value'       => Subscription_Product::get_trial_period( $post->ID ), // Explicity set value in to ensure backward compatibility
			)
		);

		do_action( 'woocommerce_subscriptions_product_options_pricing' );

		echo '</div>';
		echo '<div class="show_if_subscription clear"></div>';
	}

	/**
	 * Output the subscription specific pricing fields on the "Edit Product" admin page.
	 *
	 * @since 1.3
	 */
	public static function variable_subscription_pricing_fields( $loop, $variation_data, $variation ) {
		global $woocommerce, $thepostid;

		// Set month as the default billing period
		if ( ! $subscription_period = get_post_meta( $variation->ID, '_subscription_period', true ) )
			$subscription_period = 'month';

		// When called via Ajax
		if ( ! function_exists( 'woocommerce_wp_text_input' ) )
			require_once( $woocommerce->plugin_path() . '/admin/post-types/writepanels/writepanels-init.php' );

		if ( ! isset( $thepostid ) )
			$thepostid = $variation->post_parent;

?>
<tr class="variable_subscription_pricing show_if_variable-subscription">
	<td colspan="1">
		<label><?php printf( __( 'Subscription Price (%s)', 'plugin' ), get_woocommerce_currency_symbol() ) ?></label>
		<?php
		// Subscription Price
		woocommerce_wp_text_input( array(
			'id'            => '_subscription_price[' . $loop . ']',
			'class'         => 'wc_input_subscription_price',
			'wrapper_class' => '_subscription_price_field',
			'label'         => sprintf( __( 'Subscription Price (%s)', 'plugin' ), get_woocommerce_currency_symbol() ),
			'placeholder'   => __( 'e.g. 5.90', 'plugin' ),
			'value'         => get_post_meta( $variation->ID, '_subscription_price', true ),
			)
		);

		// Subscription Period Interval
		woocommerce_wp_select( array(
			'id'            => '_subscription_period_interval[' . $loop . ']',
			'class'         => 'wc_input_subscription_period_interval',
			'wrapper_class' => '_subscription_period_interval_field',
			'label'         => __( 'Subscription Periods', 'plugin' ),
			'options'       => Subscription_Manager::get_subscription_period_interval_strings(),
			'value'         => get_post_meta( $variation->ID, '_subscription_period_interval', true ),
			)
		);

		// Billing Period
		woocommerce_wp_select( array(
			'id'            => '_subscription_period[' . $loop . ']',
			'class'         => 'wc_input_subscription_period',
			'wrapper_class' => '_subscription_period_field',
			'label'         => __( 'Billing Period', 'plugin' ),
			'value'         => $subscription_period,
			'description'   => __( 'for', 'plugin' ),
			'options'       => Subscription_Manager::get_subscription_period_strings(),
			)
		);

		// Subscription Length
		woocommerce_wp_select( array(
			'id'            => '_subscription_length[' . $loop . ']',
			'class'         => 'wc_input_subscription_length',
			'wrapper_class' => '_subscription_length_field',
			'label'         => __( 'Subscription Length', 'plugin' ),
			'options'       => Subscription_Manager::get_subscription_ranges( $subscription_period ),
			'value'         => get_post_meta( $variation->ID, '_subscription_length', true ),
			)
		);
?>
	</td>
	<td>
		<label><?php printf( __( 'Sign-up Fee (%s)', 'plugin' ), get_woocommerce_currency_symbol() ) ?></label>
<?php
		// Sign-up Fee
		woocommerce_wp_text_input( array(
			'id'            => '_subscription_sign_up_fee[' . $loop . ']',
			'class'         => 'wc_input_subscription_intial_price',
			'wrapper_class' => '_subscription_sign_up_fee_field',
			'label'         => sprintf( __( 'Sign-up Fee (%s)', 'plugin' ), get_woocommerce_currency_symbol() ),
			'placeholder'   => __( 'e.g. 9.90', 'plugin' ),
			'value'         => get_post_meta( $variation->ID, '_subscription_sign_up_fee', true ),
			)
		);
?>	</td>
</tr>
<tr class="variable_subscription_trial show_if_variable-subscription">
	<td colspan="1" class="show_if_variable-subscription">
		<label><?php _e( 'Free Trial', 'plugin' ); ?></label>
<?php
		// Trial Length
		woocommerce_wp_text_input( array(
			'id'            => '_subscription_trial_length[' . $loop . ']',
			'class'         => 'wc_input_subscription_trial_length',
			'wrapper_class' => '_subscription_trial_length_field',
			'label'         => __( 'Free Trial', 'plugin' ),
			'placeholder'   => __( 'e.g. 1', 'plugin' ),
			'value'         => get_post_meta( $variation->ID, '_subscription_trial_length', true ),
			)
		);

		// Trial Period
		woocommerce_wp_select( array(
			'id'            => '_subscription_trial_period[' . $loop . ']',
			'class'         => 'wc_input_subscription_trial_period',
			'wrapper_class' => '_subscription_trial_period_field',
			'label'         => __( 'Subscription Trial Period', 'plugin' ),
			'options'       => Subscription_Manager::get_available_time_periods(),
			'description'   => sprintf( __( 'An optional period of time to wait before charging the first recurring payment. Any sign up fee will still be charged at the outset of the subscription. %s', 'plugin' ), self::get_trial_period_validation_message() ),
			'desc_tip'      => true,
			'value'         => Subscription_Product::get_trial_period( $variation->ID ), // Explicity set value in to ensure backward compatibility
			)
		);

		do_action( 'woocommerce_variable_subscription_pricing' ); ?>
	</td>
</tr>
<?php
	}

}