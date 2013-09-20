<?php

class Gateway_Avangate extends WC_Payment_Gateway {

	function __construct()
	{
		global $woocommerce;

        $this->id           = 'avangate';
        $this->icon         = '';//apply_filters( 'woocommerce_paypal_icon', $woocommerce->plugin_url() . '/assets/images/icons/paypal.png' );
        $this->has_fields   = false;
        $this->liveurl      = //'https://www.paypal.com/cgi-bin/webscr';
		$this->testurl      = //'https://www.sandbox.paypal.com/cgi-bin/webscr';
        $this->method_title = __( 'Avangate', 'plugin' );
        $this->title = __( 'Avangate', 'plugin' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled = true;
		$this->title 			= $this->get_option( 'title' );
		$this->description 		= $this->get_option( 'description' );
		$this->email 			= $this->get_option( 'email' );
		$this->debug 			= $this->get_option( 'debug' );

		// Logs
		if ( 'yes' == $this->debug )
			$this->log = $woocommerce->logger();

		// Actions
//		add_action( 'valid-paypal-standard-ipn-request', array( $this, 'successful_request' ) );
//		add_action( 'woocommerce_receipt_paypal', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
//
//		// Payment listener/API hook
//		add_action( 'woocommerce_api_wc_gateway_paypal', array( $this, 'check_ipn_response' ) );

	}

	/**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Avangate standard', 'woocommerce' ),
							'default' => ''
						),
			'title' => array(
							'title' => __( 'Title', 'woocommerce' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default' => __( 'Avangate', 'woocommerce' ),
							'desc_tip'      => true,
						),
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'default' => __( '', 'woocommerce' )
						),
			'debug' => array(
							'title' => __( 'Debug Log', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'woocommerce' ),
							'default' => 'no',
							'description' => sprintf( __( 'Log Avangate events, such as IPN requests, inside <code>woocommerce/logs/avangate-%s.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'avangate' ) ) ),
						)
			);
    }
}