<?php
/**
 * WooCommerce Jetpack Payment Gateways Fees
 *
 * The WooCommerce Jetpack Payment Gateways Fees class.
 *
 * @version 2.2.2
 * @since   2.2.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Payment_Gateways_Fees' ) ) :

class WCJ_Payment_Gateways_Fees extends WCJ_Module {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id         = 'payment_gateways_fees';
		$this->short_desc = __( 'Payment Gateways Fees', 'woocommerce-jetpack' );
		$this->desc       = __( 'Enable extra fees for WooCommerce payment gateways.', 'woocommerce-jetpack' );
		parent::__construct();

		add_filter( 'init',  array( $this, 'add_hooks' ) );

		if ( $this->is_enabled() ) {
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'gateways_fees' ) );
			add_action( 'wp_enqueue_scripts' , array( $this, 'enqueue_checkout_script' ) );
//			add_filter( 'woocommerce_payment_gateways_settings', array( $this, 'add_fees_settings' ), 100 );
			add_action( 'init', array( $this, 'register_script' ) );
		}
	}

	/**
	 * get_settings.
	 */
	function get_settings() {
		$settings = array();
		//$settings = $this->add_fees_settings( $settings );
		$settings = apply_filters( 'wcj_payment_gateways_fees_settings', $settings );
		return $this->add_enable_module_setting( $settings );
	}

    /**
     * add_hooks.
     */
    function add_hooks() {
		add_filter( 'wcj_payment_gateways_fees_settings',  array( $this, 'add_fees_settings' ) );
	}

    /**
     * register_script.
     */
    public function register_script() {
        wp_register_script( 'wcj-payment-gateways-checkout', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/checkout.js', array( 'jquery' ), false, true );
    }

    /**
     * enqueue_checkout_script.
     */
    public function enqueue_checkout_script() {
        if( ! is_checkout() )
			return;
		wp_enqueue_script( 'wcj-payment-gateways-checkout' );
    }

	/**
	 * gateways_fees.
	 *
	 * @version 2.2.2
	 */
	function gateways_fees() {
		global $woocommerce;
		$current_gateway = $woocommerce->session->chosen_payment_method;
		if ( '' != $current_gateway ) {
			$fee_text  = get_option( 'wcj_gateways_fees_text_' . $current_gateway );
			$min_cart_amount = get_option( 'wcj_gateways_fees_min_cart_amount_' . $current_gateway );
			$max_cart_amount = get_option( 'wcj_gateways_fees_max_cart_amount_' . $current_gateway );
			$total_in_cart = $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total;
			if ( '' != $fee_text && $total_in_cart >= $min_cart_amount  && ( 0 == $max_cart_amount || $total_in_cart <= $max_cart_amount ) ) {
				$fee_value = get_option( 'wcj_gateways_fees_value_' . $current_gateway );
				$fee_type  = apply_filters( 'wcj_get_option_filter', 'fixed', get_option( 'wcj_gateways_fees_type_' . $current_gateway ) );
				$final_fee_to_add = 0;
				switch ( $fee_type ) {
					case 'fixed': 	$final_fee_to_add = $fee_value; break;
					case 'percent':
						$final_fee_to_add = ( $fee_value / 100 ) * $total_in_cart;
						if ( 'yes' === get_option( 'wcj_gateways_fees_round_' . $current_gateway ) ) {
							$final_fee_to_add = round( $final_fee_to_add, get_option( 'wcj_gateways_fees_round_precision_' . $current_gateway ) );
						}
						break;
				}
				if ( '' != $fee_text && 0 != $final_fee_to_add ) {
					$taxable = ( 'yes' === get_option( 'wcj_gateways_fees_is_taxable_' . $current_gateway ) ) ? true : false;
					$tax_class = ( true === $taxable ) ? apply_filters( 'wcj_get_option_filter', 'standard', get_option( 'wcj_gateways_fees_tax_class_' . $current_gateway, 'standard' ) ) : '';
					$woocommerce->cart->add_fee( $fee_text, $final_fee_to_add, $taxable, $tax_class );
				}
			}
		}
	}

    /**
     * add_fees_settings.
     */
	function add_fees_settings( $settings ) {
		// Gateway's Extra Fees
        $settings[] = array(
			'title' => __( 'Payment Gateways Fees Options', 'woocommerce-jetpack' ),
			'type' => 'title',
			'desc' => __( 'This section lets you set extra fees for payment gateways.', 'woocommerce-jetpack' ),
//					  __( 'Fees are applied BEFORE taxes.', 'woocommerce-jetpack' ),
			'id' => 'wcj_payment_gateways_fees_options' );

		//$available_gateways = WC()->payment_gateways->payment_gateways();
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		//$available_gateways = WC()->payment_gateways();
		foreach ( $available_gateways as $key => $gateway ) {
			/*echo '<h5>' . $gateway->title . '</h5>';
			if ( $gateway->is_available() )
				echo '<strong style="color: green;">' . __( 'Available', 'woocommerce-jetpack' ) . '</strong>';
			else
				echo '<strong style="color: red;">' . __( 'Not available', 'woocommerce-jetpack' ) . '</strong>';*/

			$settings = array_merge( $settings, array(

				array(
					'title'    	=> $gateway->title,
					'desc'    	=> __( 'Fee title to show to customer.', 'woocommerce-jetpack' ),
					'desc_tip'	=> __( 'Leave blank to disable.', 'woocommerce-jetpack' ),
					'id'       	=> 'wcj_gateways_fees_text_' . $key,
					'default'  	=> '',
					'type'		=> 'text',
				),

				array(
					'title'    	=> '',
					'desc'    	=> __( 'Fee type.', 'woocommerce-jetpack' ),
					'desc_tip'	=> __( 'Percent or fixed value.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' ),
					'custom_attributes'
								=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
					'id'       	=> 'wcj_gateways_fees_type_' . $key,
					'default'  	=> 'fixed',
					'type'		=> 'select',
					'options'     => array(
						'fixed' 	=> __( 'Fixed', 'woocommerce-jetpack' ),
						'percent'   => __( 'Percent', 'woocommerce-jetpack' ),
					),
				),

				array(
					'title'    	=> '',
					'desc'    	=> __( 'Fee value.', 'woocommerce-jetpack' ),
					'desc_tip'	=> __( 'The value.', 'woocommerce-jetpack' ),
					'id'       	=> 'wcj_gateways_fees_value_' . $key,
					'default'  	=> 0,
					'type'		=> 'number',
					'custom_attributes' => array(
						'step' 	=> '0.01',
						'min'	=> '0',
					),
				),

				array(
					'title'    	=> '',
					'desc'    	=> __( 'Minimum cart amount for adding the fee.', 'woocommerce-jetpack' ),
					'desc_tip'	=> __( 'Set 0 to disable.', 'woocommerce-jetpack' ),
					'id'       	=> 'wcj_gateways_fees_min_cart_amount_' . $key,
					'default'  	=> 0,
					'type'		=> 'number',
					'custom_attributes' => array(
						'step' 	=> '0.01',
						'min'	=> '0',
					),
				),

				array(
					'title'    	=> '',
					'desc'    	=> __( 'Maximum cart amount for adding the fee.', 'woocommerce-jetpack' ),
					'desc_tip'	=> __( 'Set 0 to disable.', 'woocommerce-jetpack' ),
					'id'       	=> 'wcj_gateways_fees_max_cart_amount_' . $key,
					'default'  	=> 0,
					'type'		=> 'number',
					'custom_attributes' => array(
						'step' 	=> '0.01',
						'min'	=> '0',
					),
				),

				array(
					'title'    	=> '',
					'desc'    	=> __( 'Round the fee value before adding to the cart.', 'woocommerce-jetpack' ),
					//'desc_tip'	=> __( 'Set 0 to disable.', 'woocommerce-jetpack' ),
					'id'       	=> 'wcj_gateways_fees_round_' . $key,
					'default'  	=> 'no',
					'type'		=> 'checkbox',
				),

				array(
					'title'    	=> '',
					'desc'    	=> __( 'If rounding is enabled, set precision here.', 'woocommerce-jetpack' ),
					//'desc_tip'	=> __( 'Set 0 to disable.', 'woocommerce-jetpack' ),
					'id'       	=> 'wcj_gateways_fees_round_precision_' . $key,
					'default'  	=> 0,
					'type'		=> 'number',
					'custom_attributes' => array(
						'step' 	=> '1',
						'min'	=> '0',
					),
				),

				array(
					'title'    	=> '',
					'desc'    	=> __( 'Is taxable?', 'woocommerce-jetpack' ),
					/* 'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ), */
					'id'       	=> 'wcj_gateways_fees_is_taxable_' . $key,
					'default'  	=> 'no',
					'type'		=> 'checkbox',
					/* 'custom_attributes'
								=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ), */
				),

				array(
					'title'    	=> '',
					'desc'    	=> __( 'Tax Class (only if Taxable selected).', 'woocommerce-jetpack' ),
					'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' ),
					'id'       	=> 'wcj_gateways_fees_tax_class_' . $key,
					'default'  	=> 'standard',
					'type'		=> 'select',
					'options'   => array_merge( array( 'standard' => __( 'Standard Rate', 'woocommerce-jetpack' ) ), WC_Tax::get_tax_classes() ),
					'custom_attributes'
								=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
				),

			) );
        }

        $settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_payment_gateways_fees_options' );

		return $settings;
	}
}

endif;

return new WCJ_Payment_Gateways_Fees();
