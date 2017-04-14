<?php
/*
Plugin Name: Connected Products
Plugin URI:  http://wtyczka.pandzia.pl
Description: Plugin which connects Products and allow to swich them by buttons below title
Version:     1.0
Author:      Mateusz Wojcik
Author URI:  http://portfolio.pandzia.pl
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wporg
Domain Path: /languages
*/

/*
 * License
 */

/*
 * No uninstaller (clean your databse manually ;)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_ConnectedProducts {
	
	private $connect;
	
	public function __construct(){
		$this->chasil_registerHooks();
	}

	public function chasil_registerHooks(){
            add_filter('woocommerce_product_data_tabs', array($this, 'chasil_add_connected_products_data_tab'));
            add_action('woocommerce_product_data_panels', array($this, 'chasil_add_connected_products_product_data_fields'));
            add_action('woocommerce_process_product_meta', array($this, 'chasil_save_connected_products_data_fields'), 9, 2);
            add_action('woocommerce_single_product_summary', array($this, 'chasil_get_connected_products'),7);
            
            add_action('wp_enqueue_scripts', array($this, 'chasil_addStyle'));
	}

        /*
         * Create new tab in product data panel
         */
        public function chasil_add_connected_products_data_tab( $product_data_tabs ) {
            $product_data_tabs['my-custom-tab'] = array(
                'label' => __( 'Connect Products', 'my_text_domain' ),
                'target' => 'connected_products_product_data',
            );
            return $product_data_tabs;
        }
	/*
         * Create options in data panel (search)
         */
        public function chasil_add_connected_products_product_data_fields() {
            global $woocommerce, $post, $wpdb, $product;

            $product_ids = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_conn_prod_ids', true ) ) );

            ?>
            <div id="connected_products_product_data" class="panel woocommerce_options_panel">

                <div class="options_group">
                    <p class="form-field">
			<label for="conn_prod_ids"><?php _e( 'Powiąż produkty', 'woocommerce' ); ?></label>
			<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="conn_prod_ids" name="conn_prod_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-exclude="<?php echo intval( $post->ID ); ?>">
				<?php
					foreach ( $product_ids as $product_id ) {
						$product = wc_get_product( $product_id );
						if ( is_object( $product ) ) {
							echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
						}
					}?>
			</select>
                    </p>
                </div>
                <?php $btnName = $this->get_conn_button($post); ?>
                <div class="option_group">
                    <p class="form-field">
                        <label for="conn_prod_button">Nazwa przycisku</label>
                        <input type="text" name="chProductButton" id="conn_prod_button" value="<?php echo $btnName[0]; ?>">
                    </p>
                </div>
            </div>

            <?php
        }
        
        /**
	 * Get connected products IDs.
	 *
	 * @since 3.0.0
	 * @param  string $context
	 * @return array
	 */
	public function get_conn_prod_ids( $context = 'view' ) {
            return $this->get_prop( 'conn_prod_ids', $context );
	}
        
        
        /*
         * Save connected products to database + button name
         */
        public function chasil_save_connected_products_data_fields($post_id, $post){
            $connProd = isset( $_POST['conn_prod_ids'] ) ? array_filter( array_map( 'intval', $_POST['conn_prod_ids'] ) ) : array();
            update_post_meta( $post_id, '_conn_prod_ids', $connProd ); // save products
            $conProdButton = array($_POST['chProductButton']);
            update_post_meta( $post_id, '_conn_prod_button', $conProdButton);
                
        }
        
        
        /**
	 * Returns the connected products ids.
	 *
	 * @return array
        */
	public function get_conn_prod($product) {
            $connectedProductIds = get_post_meta( $product->id, '_conn_prod_ids', true );
            return apply_filters( 'woocommerce_product_prod_conn_ids', (array) maybe_unserialize( $connectedProductIds ), $product );
	}
        
        /*
         * Return button name
         * 
         * @return array
         */
        public function get_conn_button($post) {
            $conn_buttons = get_post_meta( $post->ID, '_conn_prod_button', true);
            return apply_filters( 'woocommercs_product_prod_conn_buttons', (array) maybe_unserialize ( $conn_buttons ), $product );
        }
        
        /*
         * Return IDs of connected products buttons names
         * 
         * @return array
         */
        public function get_conn_buttons($post) {
            global $product, $post;
            
            $conn_prods = $this->get_conn_prod($product);
            $conn_prods[] = $post->ID;
            sort($conn_prods);
            
            
            $results = array();
            
            foreach($conn_prods as $id) {
                $productMeta = get_post_meta($id, '_conn_prod_button', true);
                $results[$id] = $productMeta[0];
            }
            return $results;
        }
        
        /*
         * Get connected products from database and display below the product's title
         */
        public function chasil_get_connected_products(){

            global $product, $post;
            
            if ( ! $conn_prod = $this->get_conn_prod($product) ) {
                return; 
            }
            
            $buttonsNames = $this->get_conn_buttons($post);
            
            // documentation https://codex.wordpress.org/Class_Reference/WP_Query
            $args = array(
                'post_type'           => 'product',
                'ignore_sticky_posts' => 1,
                'no_found_rows'       => 1,
                'orderby'             => 'name',
                'post__in'            => $conn_prod,
                'post__not_in'        => array( $product->id ),
                'meta_query'          => WC()->query->get_meta_query()
            );

            $products                    = new WP_Query( $args );

            if ( $products->have_posts() ) : ?>
                <div class="up-sells upsells products">
                    
                    <?php foreach($buttonsNames as $buttonId => $buttonName) : ?>
                        <a class="connProdLink" href="<?php echo get_site_url(); ?>/?p=<?php echo $buttonId; ?>">
                            <input name="submit" type="submit" id="submit" class="submit connProdButton" value="<?php echo $buttonName; ?>">
                        </a>
                    <?php endforeach ; ?>
                </div>
            <?php endif;
        }
        
        /*
         * Add style
         */
        public function chasil_addStyle(){
            wp_register_style('connected-products-stylesheet', plugins_url('/css/connectedProducts.css', __FILE__));
            wp_enqueue_style('connected-products-stylesheet');
        }

}

// Launch
new WC_ConnectedProducts();