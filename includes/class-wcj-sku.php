<?php
/**
 * WooCommerce Jetpack SKU
 *
 * The WooCommerce Jetpack SKU class.
 *
 * @class    WCJ_SKU
 * @version  1.0.0
 * @category Class
 * @author   Algoritmika Ltd.
 */
 
if ( ! defined( 'ABSPATH' ) ) exit;
 
if ( ! class_exists( 'WCJ_SKU' ) ) :
 
class WCJ_SKU {
    
    /**
     * Constructor.
     */
    public function __construct() {
 
		$the_priority = 100;
        // Main hooks
        if ( 'yes' === get_option( 'wcj_sku_enabled' ) ) {
			add_filter( 'wcj_tools_tabs', 				array( $this, 'add_sku_tool_tab' ), $the_priority );
			add_action( 'wcj_tools_sku', 	            array( $this, 'create_sku_tool' ), $the_priority );
			add_action( 'wp_insert_post', 	        	array( $this, 'set_product_sku' ), $the_priority, 3 );
        }
		add_action( 'wcj_tools_dashboard', 			    array( $this, 'add_sku_tool_info_to_tools_dashboard' ), $the_priority );
    
        // Settings hooks
        add_filter( 'wcj_settings_sections',            array( $this, 'settings_section' ) );
        add_filter( 'wcj_settings_sku',                 array( $this, 'get_settings' ), $the_priority );
        add_filter( 'wcj_features_status',              array( $this, 'add_enabled_option' ), $the_priority );
    }
	
	/**
	 * set_sku.
	 */
	public function set_sku( $product_id ) {
		$the_sku = sprintf( '%s%0' . get_option( 'wcj_sku_minimum_number_length', 0 ) . 'd%s', get_option( 'wcj_sku_prefix', '' ), $product_id, apply_filters( 'wcj_get_option_filter', '', get_option( 'wcj_sku_suffix', '' ) ) );
		update_post_meta( $product_id, '_' . 'sku', $the_sku );
	}	
	
	/**
	 * set_sku.
	 */
	public function set_all_sku() {
		$args = array(
			'post_type'			=> 'product',
			'post_status' 		=> 'any',
			'posts_per_page' 	=> -1,
		);
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) : $loop->the_post();
			$this->set_sku( $loop->post->ID );
		endwhile;
	}	
		
	/**
	 * set_product_sku.
	 */
	public function set_product_sku( $post_ID, $post, $update ) {
		if ( 'product' !== $post->type )
			return;
		if ( false === $update ) {
			$this->set_sku( $post_ID );
		}
	}	
	
	/**
	 * add_sku_tool_tab.
	 */
	public function add_sku_tool_tab( $tabs ) {
		$tabs[] = array(
			'id'		=> 'sku',
			'title'		=> __( 'Autogenerate SKUs', 'woocommerce-jetpack' ),
		);
		return $tabs;
	}	

    /**
     * create_sku_tool
     */
	public function create_sku_tool() {
		$result_message = '';
		if ( isset( $_POST['set_sku'] ) ) {
			$this->set_all_sku();
			$result_message = '<p><div class="updated"><p><strong>' . __( 'SKUs generated and set successfully!', 'woocommerce-jetpack' ) . '</strong></p></div></p>';
		}
		?><div>
			<h2><?php echo __( 'WooCommerce Jetpack - Autogenerate SKUs', 'woocommerce-jetpack' ); ?></h2>
			<p><?php echo __( 'The tool generates and sets product SKUs.', 'woocommerce-jetpack' ); ?></p>
			<?php echo $result_message; ?>
			<form method="post" action="">
				<input class="button-primary" type="submit" name="set_sku" value="Set SKUs">
			</form>
		</div><?php
	}	
	
	/**
	 * add_sku_tool_info_to_tools_dashboard.
	 */
	public function add_sku_tool_info_to_tools_dashboard() {
		echo '<tr>';
		if ( 'yes' === get_option( 'wcj_sku_enabled') )		
			$is_enabled = '<span style="color:green;font-style:italic;">' . __( 'enabled', 'woocommerce-jetpack' ) . '</span>';
		else
			$is_enabled = '<span style="color:gray;font-style:italic;">' . __( 'disabled', 'woocommerce-jetpack' ) . '</span>';
		echo '<td>' . __( 'Autogenerate SKUs', 'woocommerce-jetpack' ) . '</td>';
		echo '<td>' . $is_enabled . '</td>';
		echo '<td>' . __( 'The tool generates and sets product SKUs.', 'woocommerce-jetpack' ) . '</td>';
		echo '</tr>';	
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
 
            array( 
				'title' => __( 'SKU Options', 'woocommerce-jetpack' ), 
				'type' => 'title', 
				'desc' => __( 'When enabled - all new products will be given (autogenerated) SKU. If you wish to set SKUs for existing products, use Autogenerate SKUs Tool in WooCommerce > Jetpack Tools.', 'woocommerce-jetpack' ), 
				'id' => 'wcj_sku_options' 
			),
            
            array(
                'title'    => __( 'SKU', 'woocommerce-jetpack' ),
                'desc'     => '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
                'desc_tip' => __( 'Generate SKUs automatically.', 'woocommerce-jetpack' ),
                'id'       => 'wcj_sku_enabled',
                'default'  => 'no',
                'type'     => 'checkbox',
            ),
                  
            array(
                'title'    => __( 'Prefix', 'woocommerce-jetpack' ),
                'id'       => 'wcj_sku_prefix',
                'default'  => '',
                'type'     => 'text',				
            ),
                         
            array(
                'title'    => __( 'Minimum Number Length', 'woocommerce-jetpack' ),
                'id'       => 'wcj_sku_minimum_number_length',
                'default'  => 0,
                'type'     => 'number',
            ),
			
            array(
                'title'    => __( 'Suffix', 'woocommerce-jetpack' ),
                'id'       => 'wcj_sku_suffix',
                'default'  => '',
                'type'     => 'text',	
				'desc' 	   => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes'
						   => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),					
            ),			
        
            array( 
				'type'  => 'sectionend', 
				'id' => 'wcj_sku_options' 
			),
        );
        
        return $settings;
    }
 
    /**
     * settings_section.
     */
    function settings_section( $sections ) {    
        $sections['sku'] = __( 'SKU', 'woocommerce-jetpack' );        
        return $sections;
    }    
}
 
endif;
 
return new WCJ_SKU();
