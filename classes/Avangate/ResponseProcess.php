<?php

/**
 * @see https://secure.avangate.com/cpanel/help.php?view=topic&topic=345
 */
class Avangate_ResponseProcess
{

	private $post;

	public function __construct($post)
	{
		$this->setPost($post);
	}

	public function process()
	{
		if ($this->isValidNotification())
		{

			if (!$this->isUserExist())
			{
				$userId = $this->createUserAndSendEmail();
			}

			$userId = $this->getUserId();


			$this->createOrder($userId);
			$this->sendResponse();
		}
	}

	private function isValidNotification()
	{

		return $this->IPN_PID && $this->IPN_PNAME;
//		$IPN_PID ;
//		$IPN_PNAME;
//		$IPN_DATE;
//		$DATE;
//		return true;
	}

	//<EPAYMENT>DATE|HASH</EPAYMENT>
	private function sendResponse()
	{
		$IPN_PID = (string) array_shift($this->IPN_PID);
		$IPN_PNAME = (string) array_shift($this->IPN_PNAME);
		$IPN_DATE = (string) $this->IPN_DATE;
		$DATE = (string) $this->DATE;

		$settings = getAvangateSettings();

		$md5 = hash_hmac('md5', strlen($IPN_PID) . $IPN_PID
				. strlen($IPN_PNAME) . $IPN_PNAME
				. strlen($IPN_DATE) . $IPN_DATE
				. strlen($DATE) . $DATE, $settings[Gateway_Avangate::SECRET_KEY]);

		echo "<EPAYMENT>{$DATE}|{$md5}</EPAYMENT>";
	}

	private function createOrder($userId)
	{
		/**
		 * @todo add to order correct information....
		 *  - customer
		 *  - amount
		 *  - and other
		 */
		global $woocommerce, $wp_taxonomies, $wp;
		$id = wp_insert_post(array(
			'post_type' => 'shop_order',
			'post_status' => 'publish',
			'post_type' => 'shop_order',
			'post_title' => sprintf(__('Order &ndash; %s', 'woocommerce'), strftime(_x('%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce'))),
			'post_status' => 'publish',
			'ping_status' => 'closed',
			'post_excerpt' => '',
			'post_author' => $userId,
			'post_password' => uniqid('order_') // Protects the post just in case
				//		'tax_input' => array('shop_order_status')
		));
		if ($id && is_int($id))
		{
			$o = new WC_Order($id);
			$this->orderProcess($id);
//		
////			woocommerce_add_order_item_meta($id, );
//			$order_data = array(
//				'order_key' => $this->REFNOEXT,
//				'billing_first_name' => $this->FIRSTNAME,
//				'billing_last_name' => $this->LASTNAME,
//				'billing_company' => $this->COMPANY,
//				'billing_address_1' => $this->ADDRESS1,
//				'billing_address_2' => $this->ADDRESS2,
//				'billing_city' => $this->CITY,
//				'billing_postcode' => $this->ZIPCODE,
//				'billing_country' => $this->COUNTRY,
//				'billing_state' => $this->STATE,
//				'billing_email' => $this->CUSTOMEREMAIL,
//				'billing_phone' => $this->PHONE,
//				'shipping_first_name' => $this->FIRSTNAME_D,
//				'shipping_last_name' => $this->LASTNAME_D,
//				'shipping_company' => $this->COMPANY_D,
//				'shipping_address_1' => $this->ADDRESS1_D,
//				'shipping_address_2' => $this->ADDRESS2_D,
//				'shipping_city' => $this->CITY_D,
//				'shipping_postcode' => $this->ZIPCODE_D,
//				'shipping_country' => $this->COUNTRY_D,
//				'shipping_state' => $this->STATE_D,
//				'shipping_method' => '',
//				'shipping_method_title' => '',
//				'payment_method' => $this->PAYMETHOD_CODE,
//				'payment_method_title' => $this->PAYMETHOD,
//				'order_discount' => array_shift($this->IPN_DISCOUNT),
//				//			'cart_discount'			=> $this->'',
//				//			'order_tax'				=> $this->'',
//				//			'order_shipping'			=> $this->'',
//				//			'order_shipping_tax'		=> $this->'',
//				//			'order_total'				=> $this->'',
//				'customer_user' => $this->$userId,
//				'completed_date' => date(),
//			);
//
//			foreach ($order_data as $key => $value)
//			{
//
//				woocommerce_add_order_item_meta($id, $key, $value);
//			}

			$o->update_status('completed');
			$o->payment_complete();
			wp_update_post($o);
		}
	}

	private function isUserExist()
	{

		if ($this->AVANGATE_CUSTOMER_REFERENCE)
		{
			return get_users(
					array(
						'meta_key' => 'AvangateCustomerReference',
						'meta_value' => $this->AVANGATE_CUSTOMER_REFERENCE)
//								)
			);
		}
		return false;
	}

	private function createUserAndSendEmail()
	{
		$pass = wp_generate_password(8); // 8 - The length of the password to generate.

		$user = array(
			'user_login' => str_replace(' ', '_', $this->FIRSTNAME . '_' . $this->LASTNAME),
			'user_pass' => $pass,
			'user_email' => $this->CUSTOMEREMAIL,
			'first_name' => $this->FIRSTNAME,
			'last_name' => $this->LASTNAME,
		);

		$userId = wp_insert_user($user);
		update_user_meta($userId, 'AvangateCustomerReference', $this->AVANGATE_CUSTOMER_REFERENCE);
		$this->sendEmail($userId, $pass);
	}

	private function getUserId()
	{
		$user = get_users(array(
			'meta_key' => 'AvangateCustomerReference',
			'meta_value' => $this->AVANGATE_CUSTOMER_REFERENCE));

		if ($user && isset($user->ID))
		{
			return $user->ID;
		}
		return false;
	}

	private function sendEmail($userId, $pass)
	{
		wp_new_user_notification($userId, $pass);
	}

	private function getPost()
	{
		return $this->post;
	}

	private function setPost($post)
	{
		$this->post = $post;
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->post))
		{
			return $this->post[$name];
		};
		/**
		 * @todo remove test string
		 */
		return $this->generateRandomString();
	}

	private function generateRandomString()
	{
		return mt_rand(1000000, 10000000);
	}

	private function orderProcess($post_id)
	{
		global $wpdb, $woocommerce, $woocommerce_errors;

		// Add key
		add_post_meta($post_id, '_order_key', uniqid('order_'), true);

		// Update post data
		update_post_meta($post_id, '_billing_first_name', woocommerce_clean($this->_billing_first_name));
		update_post_meta($post_id, '_billing_last_name', woocommerce_clean($this->_billing_last_name));
		update_post_meta($post_id, '_billing_company', woocommerce_clean($this->_billing_company));
		update_post_meta($post_id, '_billing_address_1', woocommerce_clean($this->_billing_address_1));
		update_post_meta($post_id, '_billing_address_2', woocommerce_clean($this->_billing_address_2));
		update_post_meta($post_id, '_billing_city', woocommerce_clean($this->_billing_city));
		update_post_meta($post_id, '_billing_postcode', woocommerce_clean($this->_billing_postcode));
		update_post_meta($post_id, '_billing_country', woocommerce_clean($this->_billing_country));
		update_post_meta($post_id, '_billing_state', woocommerce_clean($this->_billing_state));
		update_post_meta($post_id, '_billing_email', woocommerce_clean($this->_billing_email));
		update_post_meta($post_id, '_billing_phone', woocommerce_clean($this->_billing_phone));
		update_post_meta($post_id, '_shipping_first_name', woocommerce_clean($this->_shipping_first_name));
		update_post_meta($post_id, '_shipping_last_name', woocommerce_clean($this->_shipping_last_name));
		update_post_meta($post_id, '_shipping_company', woocommerce_clean($this->_shipping_company));
		update_post_meta($post_id, '_shipping_address_1', woocommerce_clean($this->_shipping_address_1));
		update_post_meta($post_id, '_shipping_address_2', woocommerce_clean($this->_shipping_address_2));
		update_post_meta($post_id, '_shipping_city', woocommerce_clean($this->_shipping_city));
		update_post_meta($post_id, '_shipping_postcode', woocommerce_clean($this->_shipping_postcode));
		update_post_meta($post_id, '_shipping_country', woocommerce_clean($this->_shipping_country));
		update_post_meta($post_id, '_shipping_state', woocommerce_clean($this->_shipping_state));
		update_post_meta($post_id, '_order_shipping', woocommerce_clean($this->_order_shipping));
		update_post_meta($post_id, '_cart_discount', woocommerce_clean($this->_cart_discount));
		update_post_meta($post_id, '_order_discount', woocommerce_clean($this->_order_discount));
		update_post_meta($post_id, '_order_total', woocommerce_clean($this->_order_total));
		update_post_meta($post_id, '_customer_user', absint($this->customer_user));

		if (isset($this->_order_tax))
			update_post_meta($post_id, '_order_tax', woocommerce_clean($this->_order_tax));

		if (isset($this->_order_shipping_tax))
			update_post_meta($post_id, '_order_shipping_tax', woocommerce_clean($this->_order_shipping_tax));

		// Shipping method handling
		if (get_post_meta($post_id, '_shipping_method', true) !== stripslashes($this->_shipping_method))
		{

			$shipping_method = woocommerce_clean($this->_shipping_method);

			update_post_meta($post_id, '_shipping_method', $shipping_method);
		}

		if (get_post_meta($post_id, '_shipping_method_title', true) !== stripslashes($this->_shipping_method_title))
		{

			$shipping_method_title = woocommerce_clean($this->_shipping_method_title);

			if (!$shipping_method_title)
			{

				$shipping_method = esc_attr($this->_shipping_method);
				$methods = $woocommerce->shipping->load_shipping_methods();

				if (isset($methods) && isset($methods[$shipping_method]))
					$shipping_method_title = $methods[$shipping_method]->get_title();
			}

			update_post_meta($post_id, '_shipping_method_title', $shipping_method_title);
		}

		// Payment method handling
		if (get_post_meta($post_id, '_payment_method', true) !== stripslashes($this->_payment_method))
		{

			$methods = $woocommerce->payment_gateways->payment_gateways();
			$payment_method = woocommerce_clean($this->_payment_method);
			$payment_method_title = $payment_method;

			if (isset($methods) && isset($methods[$payment_method]))
				$payment_method_title = $methods[$payment_method]->get_title();

			update_post_meta($post_id, '_payment_method', $payment_method);
			update_post_meta($post_id, '_payment_method_title', $payment_method_title);
		}

		// Update date
		if (empty($this->order_date))
		{
			$date = current_time('timestamp');
		}
		else
		{
			$date = strtotime($this->order_date . ' ' . (int) $this->order_date_hour . ':' . (int) $this->order_date_minute . ':00');
		}

		$date = date_i18n('Y-m-d H:i:s', $date);

		$wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_date = %s, post_date_gmt = %s WHERE ID = %s", $date, get_gmt_from_date($date), $post_id));


		// Tax rows
		if (isset($this->order_taxes_id))
		{

			$get_values = array('order_taxes_id', 'order_taxes_rate_id', 'order_taxes_amount', 'order_taxes_shipping_amount');

			foreach ($get_values as $value)
				$$value = isset($_POST[$value]) ? $_POST[$value] : array();

			foreach ($order_taxes_id as $item_id)
			{

				$item_id = absint($item_id);
				$rate_id = absint($order_taxes_rate_id[$item_id]);

				if ($rate_id)
				{
					$rate = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $rate_id));
					$label = $rate->tax_rate_name ? $rate->tax_rate_name : $woocommerce->countries->tax_or_vat();
					$compound = $rate->tax_rate_compound ? 1 : 0;

					$code = array();

					$code[] = $rate->tax_rate_country;
					$code[] = $rate->tax_rate_state;
					$code[] = $rate->tax_rate_name ? $rate->tax_rate_name : 'TAX';
					$code[] = absint($rate->tax_rate_priority);
					$code = strtoupper(implode('-', array_filter($code)));
				}
				else
				{
					$code = '';
					$label = $woocommerce->countries->tax_or_vat();
				}

				$wpdb->update(
						$wpdb->prefix . "woocommerce_order_items", array('order_item_name' => woocommerce_clean($code)), array('order_item_id' => $item_id), array('%s'), array('%d')
				);

				woocommerce_update_order_item_meta($item_id, 'rate_id', $rate_id);
				woocommerce_update_order_item_meta($item_id, 'label', $label);
				woocommerce_update_order_item_meta($item_id, 'compound', $compound);

				if (isset($order_taxes_amount[$item_id]))
					woocommerce_update_order_item_meta($item_id, 'tax_amount', woocommerce_clean($order_taxes_amount[$item_id]));

				if (isset($order_taxes_shipping_amount[$item_id]))
					woocommerce_update_order_item_meta($item_id, 'shipping_tax_amount', woocommerce_clean($order_taxes_shipping_amount[$item_id]));
			}
		}


		// Order items + fees
		if (isset($this->order_item_id))
		{

			$get_values = array('order_item_id', 'order_item_name', 'order_item_qty', 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax', 'order_item_tax_class');

			foreach ($get_values as $value)
				$$value = isset($_POST[$value]) ? $_POST[$value] : array();

			foreach ($order_item_id as $item_id)
			{

				$item_id = absint($item_id);

				if (isset($order_item_name[$item_id]))
					$wpdb->update(
							$wpdb->prefix . "woocommerce_order_items", array('order_item_name' => woocommerce_clean($order_item_name[$item_id])), array('order_item_id' => $item_id), array('%s'), array('%d')
					);

				if (isset($order_item_qty[$item_id]))
					woocommerce_update_order_item_meta($item_id, '_qty', apply_filters('woocommerce_stock_amount', $order_item_qty[$item_id]));

				if (isset($item_tax_class[$item_id]))
					woocommerce_update_order_item_meta($item_id, '_tax_class', woocommerce_clean($item_tax_class[$item_id]));

				if (isset($line_subtotal[$item_id]))
					woocommerce_update_order_item_meta($item_id, '_line_subtotal', woocommerce_clean($line_subtotal[$item_id]));

				if (isset($line_subtotal_tax[$item_id]))
					woocommerce_update_order_item_meta($item_id, '_line_subtotal_tax', woocommerce_clean($line_subtotal_tax[$item_id]));

				if (isset($line_total[$item_id]))
					woocommerce_update_order_item_meta($item_id, '_line_total', woocommerce_clean($line_total[$item_id]));

				if (isset($line_tax[$item_id]))
					woocommerce_update_order_item_meta($item_id, '_line_tax', woocommerce_clean($line_tax[$item_id]));

				// Clear meta cache
				wp_cache_delete($item_id, 'order_item_meta');
			}
		}

		// Save meta
		$meta_keys = isset($this->meta_key) ? $this->meta_key : array();
		$meta_values = isset($this->meta_value) ? $this->meta_value : array();

		foreach ($meta_keys as $id => $meta_key)
		{
			$meta_value = ( empty($meta_values[$id]) && !is_numeric($meta_values[$id]) ) ? '' : $meta_values[$id];
			$wpdb->update(
					$wpdb->prefix . "woocommerce_order_itemmeta", array(
				'meta_key' => $meta_key,
				'meta_value' => $meta_value
					), array('meta_id' => $id), array('%s', '%s'), array('%d')
			);
		}

		// Order data saved, now get it so we can manipulate status
		$order = new WC_Order($post_id);

		// Order status
		$order->update_status($this->order_status);

		// Handle button actions
		if (!empty($this->wc_order_action))
		{

			$action = woocommerce_clean($this->wc_order_action);

			if (strstr($action, 'send_email_'))
			{

				do_action('woocommerce_before_resend_order_emails', $order);

				$mailer = $woocommerce->mailer();

				$email_to_send = str_replace('send_email_', '', $action);

				$mails = $mailer->get_emails();

				if (!empty($mails))
				{
					foreach ($mails as $mail)
					{
						if ($mail->id == $email_to_send)
						{
							$mail->trigger($order->id);
						}
					}
				}

				do_action('woocommerce_after_resend_order_email', $order, $email_to_send);
			}
			else
			{

				do_action('woocommerce_order_action_' . sanitize_title($action), $order);
			}
		}

		delete_transient('woocommerce_processing_order_count');
	}

}