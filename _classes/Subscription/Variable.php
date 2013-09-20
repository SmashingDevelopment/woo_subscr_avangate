<?php
/**
 * Variable Subscription Product Class
 *
 * This class extends the WC Variable product class to create variable products with recurring payments.
 *
 * @class 		WC_Product_Variable_Subscription
 * @package		WooCommerce Subscriptions
 * @category	Class
 * @since		1.3
 * 
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WC_Product_Simple' ) ) : // WC 1.x compatibility

class Subscription_Variable extends Subscription_Product {

	var $subscription_price;

	var $subscription_period;

	var $product_type;

	/**
	 * Create a simple subscription product object.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product ) {

		parent::__construct( $product );

		$this->product_type = 'variable-subscription';

		// Load all meta fields
		$this->product_custom_fields = get_post_meta ( $this->id );

		// Convert selected subscription meta fields for easy access
		if ( ! empty( $this->product_custom_fields['_subscription_price'][0] ) )
			$this->subscription_price = $this->product_custom_fields['_subscription_price'][0];

		if ( ! empty( $this->product_custom_fields['_subscription_period'][0] ) )
			$this->subscription_period = $this->product_custom_fields['_subscription_period'][0];

		add_filter( 'woocommerce_add_to_cart_handler', array( &$this, 'add_to_cart_handler' ), 10, 2 );
	}


	/**
	 * Sync variable product prices with the childs lowest/highest prices.
	 *
	 * @access public
	 * @return void
	 */
	public function variable_product_sync() {
		global $woocommerce;

		parent::variable_product_sync();

		$children = get_posts( array(
			'post_parent' 	=> $this->id,
			'posts_per_page'=> -1,
			'post_type' 	=> 'product_variation',
			'fields' 		=> 'ids',
			'post_status'	=> 'publish'
		));

		if ( $children ) {
			foreach ( $children as $child ) {

				$child_price          = get_post_meta( $child, '_price', true );
				$child_billing_period = get_post_meta( $child, '_subscription_period', true );

				// We only care about the lowest price
				if ( $child_price !== $this->min_variation_price )
					continue;

				// Set to the shortest possible billing period
				$this->subscription_period = WC_Subscriptions::get_longest_period( $this->subscription_period, $child_billing_period );
			}

			$woocommerce->clear_product_transients( $this->id );
		}

	}

	/**
	 * Returns the price in html format.
	 *
	 * @access public
	 * @param string $price (default: '')
	 * @return string
	 */
	public function get_price_html( $price = '' ) {

		$price = parent::get_price_html( $price );

		if ( ! isset( $this->subscription_period ) )
			$this->variable_product_sync();

		$price = Subscription_Product::get_price_html( $price, $this );

		return apply_filters( 'woocommerce_variable_subscription_price_html', $price, $this );
	}

	/**
	 * get_child function.
	 *
	 * @access public
	 * @param mixed $child_id
	 * @return object WC_Product_Subscription or WC_Product_Subscription_Variation
	 */
	public function get_child( $child_id ) {
		return get_product( $child_id, array(
			'product_type' => 'Subscription_Variation',
			'parent_id'    => $this->id,
			'parent'       => $this,
			) );
	}

	/**
	 *
	 * @param $product_type string A string representation of a product type
	 * @return $product object Any WC_Product_* object
	 */
	public function add_to_cart_handler( $handler, $product ) {

		if ( 'variable-subscription' === $handler )
			$handler = 'variable';

		return $handler;
	}
}

endif;