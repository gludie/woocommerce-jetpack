<?php
/**
 * WooCommerce Jetpack Cart
 *
 * The WooCommerce Jetpack Cart class.
 *
 * @class        WCJ_Cart
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
if ( ! class_exists( 'WCJ_Cart' ) ) :
 
class WCJ_Cart {
    
    /**
     * Constructor.
     */
    public function __construct() {
 
        // Main hooks
        if ( get_option( 'wcj_cart_enabled' ) == 'yes' ) {
		
			if ( get_option( 'wcj_empty_cart_enabled' ) == 'yes' ) {
			
				add_action( 'init', array( $this, 'empty_cart' ) );
				add_action( 'woocommerce_after_cart', array( $this, 'add_empty_cart_link' ) );
				//add_filter( 'wcj_empty_cart_button_filter', array( $this, 'empty_cart_button_filter_function' ), 100, 2 );
			}
			
			if ( get_option( 'wcj_add_to_cart_on_visit_enabled' ) == 'yes' )
				add_action( 'woocommerce_before_single_product', array( $this, 'add_to_cart_on_visit' ), 100 );
		}
		
		// Settings hooks
        add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );
        add_filter( 'wcj_settings_cart', array( $this, 'get_settings' ), 100 );
        add_filter( 'wcj_features_status', array( $this, 'add_enabled_option' ), 100 );
    }
	
	/*
	 * empty_cart_button_filter_function.
	 *
	public function empty_cart_button_filter_function ( $value, $type ) {
	
		if ( 'text' == $type ) return 'Empty Cart';
		if ( 'div-style' == $type ) return 'float: right';
	}	
	
	/*
	 * Add item to cart on visit.
	 */
	public function add_to_cart_on_visit() {
	
		if ( is_product() ) {
		
			global $woocommerce;
			$product_id = get_the_ID();
			$found = false;
			//check if product already in cart
			if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
				foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( $_product->id == $product_id )
						$found = true;
				}
				// if product not found, add it
				if ( ! $found )
					$woocommerce->cart->add_to_cart( $product_id );
			} else {
				// if no products in cart, add it
				$woocommerce->cart->add_to_cart( $product_id );
			}
		}
	}
	
    /**
     * add_empty_cart_link.
     */
    public function add_empty_cart_link() {
	
		echo '<div style="' . apply_filters( 'wcj_get_option_filter', 'float: right;', get_option( 'wcj_empty_cart_div_style' ) ) . '"><form action="" method="post"><input type="submit" class="button" name="empty_cart" value="' . apply_filters( 'wcj_get_option_filter', 'Empty Cart', get_option( 'wcj_empty_cart_text' ) ) . '"></form></div>';
	}	
	
    /**
     * empty_cart.
     */
    public function empty_cart() {
    
        if ( isset( $_POST['empty_cart'] ) ) {
			
			global $woocommerce;
			$woocommerce->cart->empty_cart();
		}
    }	
    
    /**
     * add_enabled_option.
     */
    public function add_enabled_option( $settings ) {
    
        $all_settings = $this->get_settings();
        $settings[] = $all_settings[1];
        
        return $settings;
    }
    
    /**
     * get_settings.
     */    
    function get_settings() {
 
        $settings = array(
 
            array( 'title' => __( 'Cart Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_cart_options' ),
            
			array(
				'title'    => __( 'Cart', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable the Cart feature', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Add empty cart button, automatically add to cart on product visit.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_cart_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
        
            array( 'type'  => 'sectionend', 'id' => 'wcj_cart_options' ),
			
            array( 'title' => __( 'Empty Cart Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you add and customize "Empty Cart" button to cart page.', 'woocommerce-jetpack' ), 'id' => 'wcj_empty_cart_options' ),
            
			array(
				'title'    => __( 'Empty Cart', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_empty_cart_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			
			array(
				'title'    => __( 'Empty Cart Button Text', 'woocommerce-jetpack' ),				
				'id'       => 'wcj_empty_cart_text',
				'default'  => 'Empty Cart',
				'type'     => 'text',
				'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'	
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
			),
			
			array(
				'title'    => __( 'Wrapping DIV style', 'woocommerce-jetpack' ),				
				'desc_tip' => __( 'Style for the button\'s div. Default is "float: right;"', 'woocommerce-jetpack' ),
				'id'       => 'wcj_empty_cart_div_style',
				'default'  => 'float: right;',
				'type'     => 'text',
				'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'	
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),						   
			),
        
            array( 'type'  => 'sectionend', 'id' => 'wcj_empty_cart_options' ),		

            array( 'title' => __( 'Add to Cart on Visit', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This section lets you enable automatically adding product to cart on visiting the product page. Product is only added once, so if it is already in cart - duplicate product is not added. ', 'woocommerce-jetpack' ), 'id' => 'wcj_add_to_cart_on_visit_options' ),
            
			array(
				'title'    => __( 'Add to Cart on Visit', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_on_visit_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
        
            array( 'type'  => 'sectionend', 'id' => 'wcj_add_to_cart_on_visit_options' ),			

        );
        
        return $settings;
    }
 
    /**
     * settings_section.
     */
    function settings_section( $sections ) {
    
        $sections['cart'] = __( 'Cart', 'woocommerce-jetpack' );
        
        return $sections;
    }    
}
 
endif;
 
return new WCJ_Cart();
