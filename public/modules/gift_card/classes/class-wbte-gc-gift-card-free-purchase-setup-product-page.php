<?php
/**
 * Store credit purchase as gift card or coupon.
 *
 * @link
 * @since 1.0.0
 *
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Wbte_Gc_Gift_Card_Free_Purchase' ) ) {
	return;
}

class Wbte_Gc_Gift_Card_Free_Purchase_Setup_Product_Page extends Wbte_Gc_Gift_Card_Free_Purchase {

	private static $instance         = null;
	private static $to_remove_blocks = array(); // Blocks to be removed while preparing gift card product page on a block theme

	public function __construct() {
		parent::init_vars();

		/**
		 *  Check and disable the product page when the products are disabled by admin
		 */
		add_action( 'wp', array( $this, 'disable_product_page_when_products_disabled' ) );

		/**
		 *  Remove `Add to cart` button from shop page (Product list page) and archive pages for store credit purchase product.
		 */
                add_action( 'woocommerce_blocks_product_grid_add_to_cart_attributes', array( $this, 'wt_woocommerce_loop_add_to_cart_args_product_new' ), 10, 2 );
		add_action( 'woocommerce_loop_add_to_cart_args', array( $this, 'wt_woocommerce_loop_add_to_cart_args' ), 10, 2 );
                add_action( 'wp_print_footer_scripts', array( $this, 'wt_replace_addtocart_button' ) );

		/**
		 * Remove product price HTML for gift card purchase product.
		 */
		add_filter( 'woocommerce_get_price_html', array( $this, 'remove_price_html_for_gift_card' ), 10, 2 );

		$product_page_design_hook = $this->get_product_page_design_hook();

		/**
		 *  Gift card product page content (Only when templates are enabled)
		 */
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) { // Block theme

			self::$to_remove_blocks = $this->get_blocks_to_remove();

			// Remove unwanted blocks
			add_filter( 'pre_render_block', array( $this, 'remove_unwanted_single_product_page_blocks' ), 11, 2 );

			// Add gift card product page design
			add_filter( 'render_block', array( $this, 'block_theme_single_product_page' ), 10, 2 );

			// Add compatibility for block theme with legacy template
			add_action(
				'template_redirect',
				function () use( $product_page_design_hook ) {

					// In some block themes they keep legacy template sections.
					if ( apply_filters( 'woocommerce_disable_compatibility_layer', false ) ) {
						add_action( $product_page_design_hook, array( $this, 'shop_single_page_design' ), 10, 0 );
					}
				},
				11
			); // Priority must be greater than 10 to get value from WC

		} else { // Non block theme
			add_action( $product_page_design_hook, array( $this, 'shop_single_page_design' ), 10, 0 );
		}

		/**
		 *  Store credit purchase form
		 */
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'set_store_credit_purchase_form' ), 10 );

		/**
		 *  Scripts and styles for store credit purchase form
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'check_and_enqueue_scripts' ) );

		/**
		 *  Disable quantity selection for gift card product
		 */
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'disable_quantity_selection' ), 10, 2 );

		/**
		 *  Remove extra add to cart button in some themes
		 *  Themes: Astra, Ocean, Botiga, Blocksy
		 */
		add_filter( 'astra_woo_single_product_structure', array( $this, 'remove_extra_add_to_cart' ), 10, 1 );
		add_filter( 'ocean_woo_summary_elements_positioning', array( $this, 'remove_extra_add_to_cart' ), 10, 1 );
		add_filter( 'botiga_default_single_product_components', array( $this, 'remove_extra_add_to_cart' ), 10, 1 );
		add_filter( 'botiga_single_product_elements', array( $this, 'remove_extra_add_to_cart' ), 10, 1 );
		add_filter( 'blocksy_woo_single_left_options_layers:defaults', array( $this, 'remove_extra_add_to_cart' ), 10, 1 );
		add_filter( 'blocksy_woo_single_right_options_layers:defaults', array( $this, 'remove_extra_add_to_cart' ), 10, 1 );
		add_filter( 'blocksy_woo_single_options_layers:defaults', array( $this, 'remove_extra_add_to_cart' ), 10, 1 );
		add_filter( 'blocksy:woocommerce:product-single:layout', array( $this, 'remove_extra_add_to_cart' ), 10, 1 );

		/**
		 *  Add theme specific CSS class for product page template main `div`
		 */
		add_filter( 'wt_gc_add_gift_card_product_page_css_class', array( $this, 'add_theme_specific_css_class' ) );

		/**
		 *  Add theme specific CSS styles for product page
		 */
		add_filter( 'wp_footer', array( $this, 'add_theme_specific_css_styles' ) );
                
		/**
		 *  Remove ajax_add_to_cart class from add to cart button as we are not handling ajax submission of our form fields
		 * 
		 *  @since 1.0.3
		 */
		add_filter( 'woocommerce_product_supports', array( $this, 'wt_woocommerce_product_supports' ), 10, 3 );

		/**
         *  Add shortcode for gift card product page content
         * 
         *  @since 1.1.0
         */
        add_shortcode( self::$product_page_shortcode_name, array( $this, 'do_product_page_shortcode' ) );
	}

	/**
	 *  Get Instance
	 *
	 *  @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Gc_Gift_Card_Free_Purchase_Setup_Product_Page();
		}
		return self::$instance;
	}	

        /**
	 *  Remove product price HTML for gift card purchase product.
	 *
	 *  @since  1.0.0
	 *  @param  string     $price          price html
	 *  @param  WC_Product $product        Product object
	 *  @return string          $price          price html
	 */
	public function remove_price_html_for_gift_card( $price = null, $product = null ) {
		if ( is_object( $product ) && self::is_gift_card_product( $product->get_id() ) ) {
			return '';
		}

		return $price;
	}

	/**
	 *  Gift card product page content (Only when templates are enabled)
	 *
	 *  @since 1.0.0
	 */
	public function shop_single_page_design() {
		global $product;
		$product_id = $product->get_id();

		if ( ! self::is_gift_card_product( $product_id ) ) {
			return;
		}

		$gift_card_products   = self::get_gift_card_products();
		$gift_card_product_id = ( is_array( $gift_card_products ) && ! empty( $gift_card_products ) && isset( $gift_card_products[0] ) ? absint( $gift_card_products[0] ) : 0 );

		if ( $product_id !== $gift_card_product_id ) { // Not the first product in the array
			return;
		}

		if ( empty( self::get_enabled_gift_card_user_options() ) ) {
			$this->remove_unwanted_product_page_hooks(); // to show a white page

			if ( current_user_can( 'manage_options' ) ) {
				esc_html_e( 'Error. No user actions enabled.', 'wt-gift-cards-woocommerce' );
			}

			return;
		}

		if ( ! self::is_templates_enabled( $product_id ) ) {
			return;
		}

		/* remove product page hooks to cleanup the product page */
		$this->remove_unwanted_product_page_hooks();

		/* Re-add WC `Add to cart` */
		add_action( 'wt_gc_gift_card_setup_form', 'woocommerce_template_single_add_to_cart', 9999 );

		$this->print_gift_card_product_page_templates_section( $product_id );
	}

	/**
	 *  Print store credit purchase form
	 *
	 *  @since 1.0.0
	 */
	public function set_store_credit_purchase_form() {
		global $product;
		$product_id = $product->get_id();

		if ( ! self::is_gift_card_product( $product_id ) ) {
			return;
		}

		$gift_card_products   = self::get_gift_card_products();
		$gift_card_product_id = ( is_array( $gift_card_products ) && ! empty( $gift_card_products ) && isset( $gift_card_products[0] ) ? absint( $gift_card_products[0] ) : 0 );

		if ( $product_id !== $gift_card_product_id ) { // Not the first product in the array
			return;
		}

		if ( empty( self::get_enabled_gift_card_user_options() ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$wt_credit_amount      = ( isset( $_REQUEST['wt_credit_amount'] ) ? floatval( wp_unslash( $_REQUEST['wt_credit_amount'] ) ) : 0 );
		$form_submit_triggered = ( isset( $_REQUEST['wt_gift_card_form_submit_triggered'] ) ? 1 : 0 ); // phpcs:disable WordPress.Security.NonceVerification.Recommended

		$settings             = self::get_product_metas( $product_id );
		$fields_enabled       = self::get_enabled_product_page_fields();
		$all_fields           = self::get_customizable_giftcard_fields();
		$is_templates_enabled = self::is_templates_enabled( $product_id );
		$allowed_options      = self::get_enabled_gift_card_user_options();
		$user_option_labels   = self::get_gift_card_user_option_labels();

		$user       = wp_get_current_user();
		$user_email = ( $user ? $user->user_email : '' );

		$mandatory_fields = apply_filters( 'wt_gc_alter_gift_card_form_mandatory_fields', array( 'reciever_email' ) );

		include_once $this->module_path . 'views/-store-credit-form.php';
	}


	/**
	 *  Add required scripts/styles for gift card purchase page
	 *  And localize values
	 *
	 *  @since 1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_style( $this->module_id, $this->module_url . 'assets/css/main.css', array(), WBTE_GC_FREE_VERSION, 'all' );
		wp_enqueue_script( $this->module_id, $this->module_url . 'assets/js/main.js', array( 'jquery', WBTE_GC_FREE_PLUGIN_NAME ), WBTE_GC_FREE_VERSION, false );

		$dummy_template_data = self::get_dummy_template();

		$params = array(
			'dummy_template_img_url' => ( isset( $dummy_template_data['image_url'] ) && is_string( $dummy_template_data['image_url'] ) ? esc_url( $dummy_template_data['image_url'] ) : Wbte_Woocommerce_Gift_Cards_Free_Common::$no_image ),
			'msgs'                   => array(
				'from'     => __( 'from', 'wt-gift-cards-woocommerce' ),
				/* translators: 1. HTML span tag open, 2. HTML span tag close */
				'hi_there' => sprintf( __( 'Hi %1$sthere%2$s,', 'wt-gift-cards-woocommerce' ), '<span class="wt_gc_reciever_name">', '</span>' ),
			),
		);

		wp_localize_script( $this->module_id, 'wt_gc_gift_card_params', $params );
	}


	/**
	 *  Remove unwanted hooks from product page
	 *
	 *  @since 1.0.0
	 */
	public function remove_unwanted_product_page_hooks() {
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );

		remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

		remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
		remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
		remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );

		remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
		remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );

		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	}

	/**
	 *  Check and disable the product page when the gift card products are disabled by admin
	 *
	 *  @since 1.0.0
	 */
	public function disable_product_page_when_products_disabled() {
		if ( ! self::is_gift_card_products_enabled() && is_product() && self::is_gift_card_product( get_the_ID() ) ) {
			if ( current_user_can( 'manage_options' ) ) {   // Show a warning message for admins
				wp_die( esc_html__( 'Gift card product is not enabled.', 'wt-gift-cards-woocommerce' ) );

			} else { // Show not found page for users
				global $wp_query;
				$wp_query->set_404();
			}
		}
	}


	/**
	 *  Disable quantity selection for gift card product
	 *
	 *  @since  1.0.0
	 *  @param  array      $args       Array of arguments. Including `min_value`, `max_value` etc
	 *  @param  WC_Product $product    Product object
	 *  @return array       Processed arguments array
	 */
	public function disable_quantity_selection( $args, $product ) {
		if ( self::is_gift_card_product( $product->get_id() ) ) {
			$args['min_value'] = 1;
			$args['max_value'] = 1;
		}

		return $args;
	}


	/**
	 *  Remove extra add to cart button in some themes
	 *  Themes: Astra, Ocean, Botiga
	 *
	 *  @since  1.0.0
	 *  @param  string[] $product_sections   Array of single product page sections
	 *  @return string[]        $product_sections   Empty array, if the current product is a gift card product. Otherwise return the same array in the argument.
	 */
	public function remove_extra_add_to_cart( $product_sections ) {
		global $product, $post;

		if ( ! is_object( $product ) ) {
			if ( is_product() ) {
				$product = wc_get_product( $post->ID );
			} else {
				return $product_sections;
			}
		}

		if ( ! method_exists( $product, 'get_id' ) ) {
			return $product_sections;
		}

		$product_id = $product->get_id();

		if ( ! self::is_gift_card_product( $product_id ) || ! self::is_templates_enabled( $product_id ) ) {
			return $product_sections;
		}

		$theme = wp_get_theme();

		if ( ( 'Blocksy' === $theme->name || 'Blocksy' === $theme->parent_theme ) && version_compare( $theme->version, '2.0.3' ) === 1 ) { // Blocksy theme and version greater than 2.0.3
			return array(
				array(
					'id'      => 'product_title',
					'enabled' => false,
				),
			);
		}

		return array();
	}


	/**
	 *  The list of blocks to be removed while preparing the gift card product page
	 *
	 *  @since  1.0.0
	 *  @return string[]    Name of the blocks
	 */
	private function get_blocks_to_remove() {

		// The blocks to be removed
		$to_remove_blocks = array(
			'woocommerce/product-image-gallery',
			'core/post-title',
			'woocommerce/product-rating',
			'woocommerce/product-price',
			'core/post-excerpt',
			'woocommerce/add-to-cart-form',
			'woocommerce/product-meta',
			'woocommerce/product-sku',
			'core/post-terms',
			'woocommerce/related-products',
		);

		/**
		 *  While setting up the product page for gift card. We need to remove some default product page blocks.
		 *  This filter allows to customize which blocks to remove
		 *
		 *  @since  1.0.0
		 *  @param  string[]    $to_remove_blocks   Name of the blocks
		 */
		return apply_filters( 'wt_gc_single_product_page_blocks_to_remove', $to_remove_blocks );
	}


	/**
	 *  Remove unwanted product page hooks while setting up the `Gift card product page` on a block enabled theme.
	 *
	 *  @since  1.0.0
	 *  @param  string|null $pre_render   The pre-rendered content. Default null.
	 *  @param  array       $parsed_block The block being rendered.
	 *  @return string|null   If the block is in the `To remove` list, then return an empty string.
	 */
	public function remove_unwanted_single_product_page_blocks( $pre_render, $parsed_block ) {

		if ( is_product() ) {
			$product = self::get_product_object();
			if ( ! is_null( $product ) // Single product page
				&& self::is_gift_card_product( $product->get_id() ) // Gift card product
				&& self::is_templates_enabled( $product->get_id() ) // Templates enabled
				&& in_array( $parsed_block['blockName'], self::$to_remove_blocks, true )
			) {
				$pre_render = '';
			}
		}

		return $pre_render;
	}


	/**
	 *  Prepare gift card product page on a block enabled theme.
	 *
	 *  @since  1.0.0
	 *  @param  string $block_content The block content.
	 *  @param  array  $block         The full block, including name and attributes.
	 *  @return string   $block_content The block content.
	 */
	public function block_theme_single_product_page( $block_content, $block ) {

		if ( 'woocommerce/product-details' === $block['blockName'] ) { // Single product page product details block

			$product = self::get_product_object();
			if ( is_null( $product ) ) { // Unable to get the product object
				return $block_content;
			}

			$product_id = $product->get_id();

			// Not a gift card product
			if ( ! self::is_gift_card_product( $product_id ) ) {
				return $block_content;
			}

			$gift_card_products   = self::get_gift_card_products();
			$gift_card_product_id = ( is_array( $gift_card_products ) && ! empty( $gift_card_products ) && isset( $gift_card_products[0] ) ? absint( $gift_card_products[0] ) : 0 );

			// Not the first product in the array
			if ( $product_id !== $gift_card_product_id ) {
				return $block_content;
			}

			// No user actions enabled by admin.
			if ( empty( self::get_enabled_gift_card_user_options() ) ) {

				// Show a warning message for admins
				if ( current_user_can( 'manage_options' ) ) {
					return __( 'Error. No user actions enabled.', 'wt-gift-cards-woocommerce' );
				}

				return '';
			}

			// Templates not enabled
			if ( ! self::is_templates_enabled( $product_id ) ) {
				return $block_content;
			}

			// Re-add `Add to cart`
			add_action( 'wt_gc_gift_card_setup_form', 'woocommerce_template_single_add_to_cart', 9999 );

			ob_start();
			$this->print_gift_card_product_page_templates_section( $product_id );
			$block_content = ob_get_clean();
		}

		return $block_content;
	}


	/**
	 *  Add required scripts/styles for gift card purchase page
	 *  And localize values
	 *
	 *  @since 1.0.0
	 */
	public function check_and_enqueue_scripts() {
		if ( is_product() ) { // Is product page

			global $post;

			/**
			 * Checks the current product is a gift card product or `Product as gift` product
			 */
			if ( self::is_gift_card_product( get_the_ID() ) ) {
				$this->enqueue_scripts();
			}
		}
	}

	/**
	 *  Get the hook to inject gift card product page
	 *
	 *  @since  1.0.0
	 *  @return string  Hook name
	 */
	public function get_product_page_design_hook() {

		$hook  = 'woocommerce_before_single_product_summary';
		$theme = wp_get_theme();

		if ( 'Porto' === $theme->name || 'Porto' === $theme->parent_theme ) {
			$hook = 'woocommerce_before_single_product';
		}

		/**
		 *  Alter the hook name to inject product page
		 *
		 *  @since  1.0.0
		 *  @param  string  Hook name
		 */
		return apply_filters( 'wt_gc_gift_product_page_design_hook', $hook );
	}

	/**
	 *  Add theme specific CSS class for product page template main `div`
	 *  Hooked into `wt_gc_add_gift_card_product_page_css_class`
	 *
	 *  @since  1.0.0
	 *  @param  string $css_class    CSS class name
	 *  @return string   $css_class    CSS class name
	 */
	public function add_theme_specific_css_class( $css_class ) {

		$theme                     = wp_get_theme();
		$alignwide_required_themes = array( 'Twenty Twenty-Two', 'Twenty Twenty-Three', 'Twenty Twenty-Four', 'Moog', 'Saryu', 'Blockpress' );

		/**
		 *  Filter to alter alignwide CSS required themes
		 *
		 *  @since 1.0.0
		 *  @param string[]  Theme names
		 */
		$alignwide_required_themes = apply_filters( 'wt_gc_alignwide_css_required_themes', $alignwide_required_themes );

		if ( in_array( $theme->name, $alignwide_required_themes, true ) || in_array( $theme->parent_theme, $alignwide_required_themes, true ) ) {
			$css_class .= 'alignwide';
		}

		return $css_class;
	}


	/**
	 *  Add theme specific CSS styles for product page
	 *  Hooked into `wp_footer`
	 *
	 *  @since  1.0.0
	 */
	public function add_theme_specific_css_styles() {

		if ( is_product() ) {
			global $product, $post;
			$product = ! is_object( $product ) ? wc_get_product( $post->ID ) : $product;

			if ( method_exists( $product, 'get_id' ) ) {

				$product_id = $product->get_id();

				if ( self::is_gift_card_product( $product_id ) || ! self::is_templates_enabled( $product_id ) ) {

					$styles = '';
					$theme  = wp_get_theme();

					if ( 'Blocksy' === $theme->name || 'Blocksy' === $theme->parent_theme ) {
						$styles .= 'body.theme-blocksy .product-entry-wrapper{ display:block; }';
					}

					/**
					 *  Alter theme specific CSS styles for product page
					 *
					 *  @since  1.0.0
					 *  @param  string  $styles     CSS styles
					 */
					$styles = (string) apply_filters( 'wbte_gc_alter_product_page_theme_specific_css_styles', $styles );

					if ( $styles ) {
						?>
						<style type="text/css">
							<?php echo wp_kses_post( $styles ); ?>
						</style>
						<?php
					}
				}
			}
		}
	}
               

	/**
	 *  Add product link and CSS class to help Remove `Add to cart` button from product-new block in empty cart.
	 *
	 *  @since 1.0.3
	 */        
        public function wt_woocommerce_loop_add_to_cart_args_product_new($button_args, $product) {
            if (self::is_gift_card_product($product->get_id())) {
                $button_args['class'] = $button_args['class'] . ' ' . 'wbte-gc-product-addtocart-button';
                $button_args['data-product_link'] = esc_url($product->get_permalink());
                $button_args['data-product_text'] = esc_html__('Select options', 'wt-gift-cards-woocommerce');
            }
            return $button_args;
        }        
	/**
	 *  Add product link and CSS class to help Remove `Add to cart` button from shop page / archive pages.
	 *
	 *  @since 1.0.2
	 */        
        public function wt_woocommerce_loop_add_to_cart_args($button_args, $product) {
            if (self::is_gift_card_product($product->get_id())) {
                $button_args['class'] = $button_args['class'] . ' ' . 'wbte-gc-product-addtocart-button';
                $button_args['attributes']['data-product_link'] = esc_url($product->get_permalink());
                $button_args['attributes']['data-product_text'] = esc_html__('Select options', 'wt-gift-cards-woocommerce');
            }
            return $button_args;
        }
	/**
	 *  Remove `Add to cart` button from shop page for gift card purchase product and add link to product detail page
	 *
	 *  @since 1.0.2
	 */           
        public function wt_replace_addtocart_button() {
            ?>
            <script>
                    setTimeout(function(){
                    // Loop through each div element with the wt class 
                    jQuery(".wbte-gc-product-addtocart-button").each(function () {
                        var product_link = jQuery(this).attr('data-product_link');
                        var product_text = jQuery(this).attr('data-product_text');
                        var product_classes = jQuery(this).attr('class');
                        // Replace the current button with the a tag
                        var wt_button = '<a href="' + product_link + '" class="'+product_classes+'"><span>' + product_text + '</span></a>';
                        jQuery(this).replaceWith(wt_button);

                    });
                    }, 1000);

            </script>
        <?php
        }     
        
        /**
         *  Remove ajax_add_to_cart class from add to cart button as we are not handling ajax submission of our form fields
         *  Hooked into `woocommerce_product_supports`
         * @param boolean $is_enabled The current option.
         * @param string $feature Name of the option.
         * @param object $product The product.
         * @return bool
         * 
         * @since 1.0.3
         */
        public function wt_woocommerce_product_supports($is_enabled, $feature, $product) {
            if ( is_product() && 'ajax_add_to_cart' === $feature ) {

                $product_id = $product->get_id();
                $is_gift_card = ( metadata_exists('post', $product_id, '_wt_gc_gift_card_product') ? (int) get_post_meta($product_id, '_wt_gc_gift_card_product', true) === absint($product_id) : false );

                if ($is_gift_card) {
                    return false;
                }
            }
            return $is_enabled;
        }  
		
		 /**
		 *  Print product page shortcode content
		 * 
		 *  @since  1.1.0
		 *  @param  array   $atts   Attributes array
		 *  @return string  Product page content
		 */
		public function do_product_page_shortcode( $atts ) {

			$product_id = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;
			
			if ( 0 === $product_id 
				|| ! ( $product = wc_get_product( $product_id ) ) 
				|| ! $product->is_visible() 
				|| ! self::is_gift_card_product( $product_id ) 
			) {
				
				// Show a warning message for admins 
				if ( current_user_can( 'manage_options' ) ) {               
					return __( 'Invalid gift card product.', 'wt-gift-cards-woocommerce' );          
				}

				return '';
			}


			// No user actions enabled by admin.
			if ( empty( self::get_enabled_gift_card_user_options() ) ) { 
			
				// Show a warning message for admins 
				if ( current_user_can( 'manage_options' ) ) {               
					return __( 'Error. No user actions enabled.', 'wt-gift-cards-woocommerce' );          
				}

				return '';
			}


			// Templates not enabled
			if ( ! self::is_templates_enabled( $product_id ) ) { 
				return __( 'Error. Select templates to display Gift card.', 'wt-gift-cards-woocommerce' );          

			}

			/**
			 *  Add scripts and styles for store credit purchase page
			 */
			$this->enqueue_scripts();
			
			/** 
			 *  Add `Add to cart` 
			 *  Requires an extra checking for users who are using their own custom template.
			 * 
			 */
			$current_template = wc_locate_template( 'gift-card.php', '', $this->module_path . 'templates/' );
			$template_header_arr = get_file_data( $current_template, array( 'Version' => 'Version' ) );
			
			if ( isset( $template_header_arr['Version'] ) 
				&& "" !== $template_header_arr['Version'] 
				&& version_compare( $template_header_arr['Version'], '1.0.1', '>=' ) 
			) {
				
				add_action( 'wt_gc_gift_card_setup_form', 'woocommerce_template_single_add_to_cart', 9999 );

			} else {
				add_action( 'wt_gc_gift_card_setup_form', function () {

					// Show a warning message for admins 
					if ( current_user_can( 'manage_options' ) ) {               
						_e( 'Please update your template', 'wt-gift-cards-woocommerce' );          
					}         
				}, 9999 );
			}

			

			ob_start();
			$this->print_gift_card_product_page_templates_section( $product_id, array(), true );    
			return ob_get_clean(); // Return the shortcode content
		}
        
}