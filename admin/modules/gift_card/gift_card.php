<?php
/**
 * Gift card admin area
 *
 * @link
 * @since 1.0.0
 *
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wbte_Gc_Gift_Card_Free_Common' ) ) {
	return;
}

class Wbte_Gc_Gift_Card_Free_Admin extends Wbte_Gc_Gift_Card_Free_Common {

	public $module_base               = 'gift_card';
	public $module_id                 = '';
	public static $module_id_static   = '';
	private static $instance          = null;
	private $gift_card_product_tab_id = '';
	private $email_gift_card_tab_id   = '';

	private $add_template_form_msgs = array();
	private $module_url             = '';

	public function __construct() {
		$this->module_id        = Wbte_Woocommerce_Gift_Cards_Free_Common::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;
		$this->module_url       = plugin_dir_url( __FILE__ );

		$this->gift_card_product_tab_id = $this->module_id . '-product_tab';
		$this->email_gift_card_tab_id   = $this->module_id . '-email_gift_card_tab';

		$this->add_template_form_msgs = array(
			'unable_to_load_templates' => __( 'Unable to load template list.', 'wt-gift-cards-woocommerce' ),
		);

		add_action( 'wt_gc_intl_after_setting_update', array( $this, 'save_settings' ), 11, 2 );

		/**
		 *  Add module tooltip array to main tooltip array
		 */
		add_filter( 'wt_gc_alter_tooltip_data', array( $this, 'register_tooltips' ), 1 );

		/**
		 *  Manage templates
		 */
		/* Show gift card templates (Ajax hook) */
		add_action( 'wp_ajax_wt_gc_show_giftcard_templates', array( $this, 'show_giftcard_templates' ) );

		/**
		 *  Settings tab
		 */
		add_action( 'wt_gc_general_settings', array( $this, 'add_general_settings_tab_fields' ), 9 );

		add_action( 'wt_gc_plugin_before_settings_tab', array( $this, 'template_preview_popup' ) );

		add_action( 'wt_gs_intl_general_settings_tab_content', array( $this, 'general_settings_tab_content' ), 9 );
		
		add_action( 'wt_gs_intl_general_settings_tab_content', array( $this, 'add_general_settings_tab_email_config_fields' ), 10 );

		add_action( 'wt_gs_intl_general_settings_tab_content', array( $this, 'add_general_settings_tab_text_customization_fields' ), 11 );

		/**
		 *
		 * Gift card product tab
		 */
		add_filter( 'wt_gc_plugin_settings_tabhead', array( $this, 'settings_tabhead' ), 1 );

		add_filter( 'wt_gc_plugin_out_settings_form', array( $this, 'out_settings_form' ), 1 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		/**
		 *
		 * Gift card product page
		 */

		// CSS and JS
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_css_js' ), 1 );
		add_action( 'admin_print_styles-post.php', array( $this, 'add_css_to_product_edit_page' ) );
		add_action( 'admin_print_styles-post-new.php', array( $this, 'add_css_to_product_edit_page' ) );
		add_action( 'admin_print_scripts', array( $this, 'add_js_to_product_edit_page' ), 100 );

		// to preserve the custom URL parameter after saving the product
		add_filter( 'admin_url', array( $this, 'keep_gc_product_edit_url_param' ) );
		add_action( 'submitpost_box', array( $this, 'add_hidden_gc_product_edit_input' ) ); // add a hidden input

		// add custom product data meta box
		add_action( 'add_meta_boxes', array( $this, 'add_custom_product_data_meta_box' ) );

		// saving meta data for gift card product
		add_action( 'save_post_product', array( $this, 'save_product_meta_data' ), 10, 1 );

		add_filter( 'post_row_actions', array( $this, 'alter_product_action_links' ), 20, 2 );

		// Add a post display state for gift card products.
		add_filter( 'display_post_states', array( $this, 'add_gift_card_post_states' ), 10, 2 );

		/**
		 *
		 *  Email gift card
		 */
		/* get gift card email preview (Ajax hook) */
		add_action( 'wp_ajax_wt_gc_gift_card_email_preview', array( $this, 'print_gift_card_email_preview' ) );

		/* send gift card as email (Ajax hook) */
		add_action( 'wp_ajax_wt_gc_gift_card_email', array( $this, 'send_gift_card_email' ) );

		/**
		 *  Purchased gift card details in order edit page
		 */
		/* show gift card info in  order item row */
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'display_credit_info_in_order_item_row' ), 10, 3 );

		/* show gift card image as thumbnail in  order item row */
		add_action( 'woocommerce_admin_order_item_thumbnail', array( $this, 'display_template_image_in_order_item_row' ), 10, 3 );

		/* Meta box for gift card details. */
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box_for_purchased_gift_cards' ) );

		/* Resend gift card email. Purchased store credit (Ajax hook) */
		add_action( 'wp_ajax_wt_resend_store_credit_coupon', array( $this, 'resend_store_credit_coupon' ) );

		/**
		 *  Save store credit related coupon meta when saving the coupon.
		 */
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ), 10, 2 );

		/**
		 *  Remove the product from Gift card product list when deleting it permanently
		 */
		add_action( 'before_delete_post', array( $this, 'check_and_remove_from_gift_product_list' ), 10, 2 );
	}


	/**
	 * Get Instance
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Gc_Gift_Card_Free_Admin();
		}
		return self::$instance;
	}

	/**
	 *  Save settings
	 *
	 *  @since 1.0.0
	 */
	public function save_settings( $the_options, $base_id = '' ) {
		if ( '' === $base_id ) {

			// Nonce verification.
			$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
			$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_gc_admin_nonce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			Wbte_Woocommerce_Gift_Cards_Free::get_instance()->plugin_admin->save_settings_inner( $this->module_id );
			$this->hide_giftcard_templates();
			$this->toggle_gift_product_visibility();

			// Update values in product meta too
			$gift_card_products = self::get_gift_card_products();

			if ( ! empty( $gift_card_products ) ) {

				$product_id = $gift_card_products[0];
				$templates  = isset( $_POST['wt_gc_visible_gift_template'] ) ? wc_clean( wp_unslash( $_POST['wt_gc_visible_gift_template'] ) ) : array(); // phpcs:disable WordPress.Security.NonceVerification.Missing

				update_post_meta( $product_id, '_wt_gc_templates', $templates );
				update_post_meta( $product_id, '_wt_gc_enable_template', ( empty( $templates ) ? 'no' : 'yes' ) ); // Disable template if list is empty
			}
		}
	}

	/**
	 *  Hook the tooltip data to main tooltip array
	 *
	 *  @since 1.0.0
	 *  @param  array $tooltip_arr    Tooltip array
	 *  @return array    $tooltip_arr    Tooltip array
	 */
	public function register_tooltips( $tooltip_arr ) {
		include plugin_dir_path( __FILE__ ) . 'data/data-tooltip.php';
		$tooltip_arr[ $this->module_id ] = $arr;
		return $tooltip_arr;
	}

	/**
	 *  Update the gift card product visibility based on settings
	 *
	 *  @since 1.0.0
	 */
	private function toggle_gift_product_visibility() {
		if ( self::is_gift_card_products_enabled() ) {
			$old_states = (array) get_option( 'wt_gc_products_old_visibility_state' );

			if ( ! empty( $old_states ) ) {
				foreach ( $old_states as $product_id => $state ) {
					$product = wc_get_product( $product_id );

					if ( $product ) {
						$product->set_catalog_visibility( $state );
						$product->save();
					}
				}

				delete_option( 'wt_gc_products_old_visibility_state' ); // remove it, otherwise the above code will run in every setting saving action
			}
		} else // hide the products and store their existing visibility state for future use
		{
			$existing_states    = array();
			$gift_card_products = self::get_gift_card_products();

			foreach ( $gift_card_products as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( ! $product ) {
					continue; }

				/* store the existing state */
				$existing_states[ $product_id ] = $product->get_catalog_visibility();

				/* update the new state to hidden */
				$product->set_catalog_visibility( 'hidden' );
				$product->save();
			}

			update_option( 'wt_gc_products_old_visibility_state', $existing_states ); // save it for future use
		}
	}

	/**
	 *  Function to update which templates to be hidden in the frontend page
	 *
	 *  @since 1.0.0
	 */
	public function hide_giftcard_templates() {

		// Nonce verification.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_gc_admin_nonce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$visible_gift_templates = ( isset( $_POST['wt_gc_visible_gift_template'] ) ? wc_clean( wp_unslash( $_POST['wt_gc_visible_gift_template'] ) ) : '' ); // phpcs:disable WordPress.Security.NonceVerification.Missing
		$visible_gift_templates = ( ! is_array( $visible_gift_templates ) ? array() : $visible_gift_templates );

		$hidden_templates = array();
		$templates        = array_keys( self::get_gift_card_templates() );

		$hidden_templates = array_diff( $templates, $visible_gift_templates );

		$hidden_templates = array_unique( $hidden_templates );
		Wbte_Woocommerce_Gift_Cards_Free_Common::update_option( 'gift_card_template_to_hide', $hidden_templates, $this->module_id );
	}

	/**
	 *  Show store credit gift card templates (Ajax callback)
	 *
	 *  @since 1.0.0
	 */
	public function show_giftcard_templates() {

		// Nonce verification.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_gc_admin_nonce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			exit();
		}

		$templates          = self::get_gift_card_templates();
		$template_url       = self::get_template_location();
		$delete_btn_tooltip = esc_attr( __( 'Delete template', 'wt-woocommerce-gift-cards' ) );
		$visible_only       = ( isset( $_POST['visible_only'] ) ? absint( wp_unslash( $_POST['visible_only'] ) ) : 0 ); // phpcs:disable WordPress.Security.NonceVerification.Missing
		$page_type          = ( isset( $_POST['page_type'] ) ? sanitize_text_field( wp_unslash( $_POST['page_type'] ) ) : 'view' ); // phpcs:disable WordPress.Security.NonceVerification.Missing
		?>
		<div class="wt_gc_hide_giftcard_template_section">
			<?php
			$shown_templates = 0;

			foreach ( $templates as $template_k => $template ) {
				$render    = true;
				$is_hidden = ( isset( $template['is_hidden'] ) ? $template['is_hidden'] : false );

				if ( 1 === $visible_only && $is_hidden ) {
					$render = false;
				}

				if ( $render ) {
					++$shown_templates;
					$this->get_gift_card_template_html( $template_k, $template, $delete_btn_tooltip, $page_type );
				}
			}

			if ( 0 === $shown_templates ) {
				echo wp_kses_post( '<div><div class="wt_gc_msgs wt_gc_msg_wrn">' ); // need an extra wrapping div to avoid flex related height issue  // phpcs:ignore
				echo wp_kses_post( sprintf(__("No templates have been chosen. Navigate to %s General Settings > Template Settings %s and select the desired templates.", 'wt-gift-cards-woocommerce'), '<b>', '</b>') ); // phpcs:ignore
				echo wp_kses_post( '</div></div>' ); // phpcs:ignore
			}
			?>
		</div>
		
		<?php
		exit();
	}


	/**
	 *  Inject tab content for general settings tabs
	 *
	 *  @since  1.0.0
	 *  @param  string $tab_key    Tab key
	 */
	public function general_settings_tab_content() {
		include 'views/--template-settings-tab.php';
	}

	/**
	 *  Fields for General settings -> General
	 *
	 *  @since 1.0.0
	 */
	public function add_general_settings_tab_fields() {
		Wbte_Woocommerce_Gift_Cards_Free_Admin::generate_form_field(
			array(
				array(
					'label'          => __( 'Enable gift card product', 'wt-gift-cards-woocommerce' ),
					'option_name'    => 'enable_gift_card_product',
					'type'           => 'checkbox',
					'checkbox_label' => __( 'Enable', 'wt-gift-cards-woocommerce' ),
					'field_vl'       => 'yes',
				),
				array(
					'label'           => __( 'Order status to generate gift cards', 'wt-gift-cards-woocommerce' ),
					'option_name'     => 'order_status_to_generate',
					'type'            => 'checkbox_list',
					'help_text'       => __( 'Gift cards will be generated upon reaching the selected order status.', 'wt-gift-cards-woocommerce' ),
					'checkbox_fields' => Wbte_Woocommerce_Gift_Cards_Free_Common::success_order_statuses(),
				),
			),
			$this->module_id
		);
	}

	/**
     *  Fields for General settings -> Email configuration
     *
     *  @since 1.1.0
     */
    public function add_general_settings_tab_email_config_fields() {

		$mpdf_slug = self::get_mpdf_plugin_slug();
		$mpdf_path = self::get_mpdf_plugin_path();
		$mpdf_required_version = self::get_required_min_mpdf_version();
		$is_mpdf_active = self::is_mpdf_plugin_active();
		$is_mpdf_exists = self::is_mpdf_plugin_exists();
		$mpdf_wp_url = self::get_mpdf_plugin_url();
		$is_required_mpdf_version_installed = self::is_required_mpdf_version_installed();

		include 'views/--email-configuration-settings-tab.php';

    }

	/**
	 *  Fields for General settings -> Title customization
	 *
	 *  @since 1.1.0
	 */
	public function add_general_settings_tab_text_customization_fields() {

		include 'views/--title-customization-settings-tab.php';

	}

	/**
	 *  Template preview popup HTML
	 *
	 *  @since 1.0.0
	 */
	public function template_preview_popup() {
		include 'views/-template-preview-popup.php';
	}

	/**
	 *  Get HTML for gift card template in admin section
	 *
	 *  @since 1.0.0
	 *  @param $template_k          string|int  template id
	 *  @param $template            array       template data
	 *  @param $delete_btn_tooltip  text       tooltip text for delete button.
	 *  @param $section             text       in which area the template HTML want to print.
	 */
	private function get_gift_card_template_html( $template_k, $template, $delete_btn_tooltip, $section = 'manage' ) {
		$img_url   = ( isset( $template['image_url'] ) ? $template['image_url'] : '' );
		$category  = ( isset( $template['category'] ) ? $template['category'] : '' );
		$is_custom = ( isset( $template['is_custom'] ) ? $template['is_custom'] : false );
		$is_hidden = ( isset( $template['is_hidden'] ) ? $template['is_hidden'] : false );
		include 'views/---gift-card-template.php';
	}

	/**
	 *  Tab heads for plugin settings page
	 *
	 *  @since 1.0.0
	 */
	public function settings_tabhead( $arr ) {
		$out                       = array();
		$product_tab_title         = __( 'Gift card products', 'wt-gift-cards-woocommerce' );
		$email_gift_card_tab_title = __( 'Send gift card', 'wt-gift-cards-woocommerce' );

		foreach ( $arr as $tab_key => $tab_title ) {
			$out[ $tab_key ] = $tab_title;

			if ( 'wt-gc-general' === $tab_key ) {
				$out[ $this->gift_card_product_tab_id ] = $product_tab_title;
				$out[ $this->email_gift_card_tab_id ]   = $email_gift_card_tab_title;
			}
		}

		return $out;
	}

	/**
	 *  Tab contents for plugin settings page
	 *
	 *  @since 1.0.0
	 */
	public function out_settings_form( $args = array() ) {
		if ( isset( $args['tab_key'] ) ) {
			if ( $this->gift_card_product_tab_id === $args['tab_key'] ) {
				$current_url = Wbte_Woocommerce_Gift_Cards_Free_Admin::get_tab_url( $args['tab_key'] );

				/**
				 *  Don't get confused with these 2 GET params.
				 *  wt_gc_product_edit_tab : for settings tab
				 *  wt_gc_product_edit : for product edit page
				 */
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['wt_gc_product_edit_tab'] ) ) {
					$product_edit_id = absint( wp_unslash( $_GET['wt_gc_product_edit_tab'] ) ); // phpcs:disable WordPress.Security.NonceVerification.Recommended
					$url_params      = array(
						'wt_gc_product_edit' => $product_edit_id,
					);

					if ( $product_edit_id > 0 ) {
						$url_params['post']    = $product_edit_id;
						$url_params['action']  = 'edit';
						$product_edit_page_url = admin_url( 'post.php?' . http_build_query( $url_params ) );
						include 'views/gift-card-product-edit.php';
					}
				} else {
					include 'views/gift-card-product.php';
				}
			} elseif ( $this->email_gift_card_tab_id === $args['tab_key'] ) {
				include 'views/email-gift-card.php';
			}
		}
	}

	/**
	 *  Enqueue JS and localize values
	 *
	 *  @since 1.0.0
	 */
	public function enqueue_scripts() {
		$gc_tab  = isset( $_GET['wt_gc_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['wt_gc_tab'] ) ) : ''; // phpcs:disable WordPress.Security.NonceVerification.Missing
		$gc_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:disable WordPress.Security.NonceVerification.Missing

		if (
			( 'wt-gc-general' === $gc_tab || $this->gift_card_product_tab_id === $gc_tab || $this->email_gift_card_tab_id === $gc_tab )
			|| $this->is_product_edit_page()
			|| WBTE_GC_FREE_PLUGIN_NAME === $gc_page
		) {
			wp_enqueue_script( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery', WBTE_GC_FREE_PLUGIN_NAME ), WBTE_GC_FREE_VERSION, false );
			$msgs = array(
				'hide_preview'           => __( 'Hide preview', 'wt-gift-cards-woocommerce' ),
				'show_preview'           => __( 'Show preview', 'wt-gift-cards-woocommerce' ),
				'unable_to_load_preview' => __( 'Unable to load the preview.', 'wt-gift-cards-woocommerce' ),
				'from'                   => __( 'from', 'wt-gift-cards-woocommerce' ),
				'expiry_date'            => __( 'Expiry date: ', 'wt-gift-cards-woocommerce' ),
			);
			$msgs = array_merge( $msgs, $this->add_template_form_msgs );

			$default_templates = self::get_default_gift_card_templates();

			wp_localize_script(
				$this->module_id,
				'wt_gc_gift_card_params',
				array(
					'msgs'                 => $msgs,
					'default_template'     => 'general',
					'default_template_url' => $default_templates['general']['image_url'],
				)
			);
		}
	}

	/**
	 *  Add plugin main admin CSS and JS on product edit page and order edit page
	 *
	 *  @since 1.0.0
	 */
	public function add_admin_css_js() {
		if ( $this->is_product_edit_page() || $this->is_order_edit_page() || Wbte_Woocommerce_Gift_Cards_Free_Admin::is_hpos_order_edit_page() ) {
			add_filter( 'wt_gc_include_admin_js_file', '__return_true', 1 ); // include admin JS file
			add_filter( 'wt_gc_include_admin_css_file', '__return_true', 1 ); // include admin CSS file
		}
	}


	/**
	 *  Add CSS to gift card product edit/add page
	 *
	 *  @since 1.0.0
	 */
	public function add_css_to_product_edit_page() {
		if ( $this->is_product_edit_page() ) {
			$this->manage_template_css();
			?>
			<style type="text/css">
				#wpcontent{ margin-left:0px !important; }
				#wpadminbar, .woocommerce-layout, #screen-meta-links, #adminmenumain, #postbox-container-2 > .postbox, #postbox-container-2 > #normal-sortables .postbox, #postbox-container-2 > #advanced-sortables .postbox, #postbox-container-1 > .postbox, #postbox-container-1 > #side-sortables > .postbox, #duplicate-action{ display:none; }
				.woocommerce_options_panel{ float:left; width:80%; }
				#poststuff #wt-gc-custom-product-data-meta-box .inside{ margin:0px; padding:0px; }
				#wt-gc-custom-product-data-meta-box .woocommerce_options_panel .checkbox_container{ float:left; width:40%; }
				#wt-gc-custom-product-data-meta-box .woocommerce_options_panel .description{ padding:0; margin:0 0 0 24px; display:block; clear:none; }
				#wt-gc-custom-product-data-meta-box .woocommerce_options_panel label{ width:200px; }
				#wt-gc-custom-product-data-meta-box .woocommerce_options_panel .wt_gc_sub_form_field label{ box-sizing:border-box; }              
				.woocommerce ul.wc-tabs li.template_settings_options a::before{ content: "\f232"; }
				.woocommerce_options_panel .wt_gc_giftcard_template_box label.wt_gc_checkbox_container{display:block;position:absolute;z-index:11;margin-left:2px;margin-top:2px;padding-left:35px;margin-bottom:12px;cursor:pointer;font-size:22px;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}
				.wt_gc_giftcard_add_new_template_btnbox{ visibility:hidden; }
				.wp-heading-inline{ display:none; } /* temp hide */
				.wt_gc_giftcard_edit_page_view_all_link{ display:block; width:100%; margin-bottom:10px; }
				.wt_gc_giftcard_edit_page_view_all_link a.button span.dashicons{ margin-top:4px; }
				#_wt_gc_amounts::placeholder{ color:#ccc; }
				#wt_gc_general_product_data .wt_gc_form_help{ width:calc(100% - 50px); float:right; }
				#delete-action .submitdelete.deletion{ display:none; }
				.wrap a.page-title-action, a.page-title-action{ display:none; }
			</style>
			<?php
		}
	}


	/**
	 *  Add CSS for manage template sections
	 *
	 *  @since 1.0.0
	 */
	public function manage_template_css() {
		?>
		<style type="text/css">
		.wt_gc_hide_giftcard_template_section{ display:flex; align-items:flex-start; flex-wrap:wrap; align-items:stretch; }
		.wt_gc_giftcard_template_manage{ padding:10px; box-sizing:border-box; }
		.wt_gc_giftcard_template_main{ width:100%; padding:15px; box-sizing:border-box; float:left; clear:both; margin-bottom:10px; }
		.wt_gc_giftcard_template_box{ width:23%; margin-right:2%; margin-bottom:2%; float:left; position:relative; padding:0px; box-shadow:0px 0px 5px #ddd; border:solid .5px #ccc; text-align:center; box-sizing:border-box; }
		.wt_gc_giftcard_template_box img{ max-width:100%; float:left; }
		.wt_gc_giftcard_template_box .wt_gc_img_overlay{ position:absolute; width:100%; height:100%; top:0; left:0; z-index:10; background:rgba(0, 0, 0, .5); display:none;}
		.wt_gc_giftcard_template_box:hover .wt_gc_img_overlay{ display:block;}
		.wt_gc_img_overlay_content{ position:relative; top:50%; margin-top:-10px; }
		.wt_gc_custom_template_overlay.wt_gc_img_overlay_content{margin-top:-25px;}
		.wt_gc_img_delete_btn, .wt_gc_img_preview_btn{width:30px; height:20px; font-size:20px; color:#fff; cursor:pointer; box-shadow:2px 2px 5px #333; border-radius:5px; padding:5px 0px; }
		.wt_gc_img_delete_btn:hover{ color:#f00; background:#fff;}
		.wt_gc_img_preview_btn:hover{ color:#00f; background:#fff;}
		
		.wt_gc_giftcard_template_preview_popup img{ max-width:100%; }
		.wt_gc_template_category{ color:#fff; height:20px; float:left; width:100%; font-size:1em; font-weight:bold; display:none;}
		#tiptip_holder{ z-index:1118675334; }

		.wt_gc_img_add_btn{ width:50px; height:40px; font-size:40px; color:#000; position:absolute; z-index:10; top:50%; left:50%; margin-left:-25px; margin-top:-20px; cursor:pointer; }
		@media (max-width:768px) {
			.wt_gc_giftcard_template_box{ width:48%; }
		}
		.media-modal{ z-index:100000010; }
		.wt_gc_giftcard_add_new_template_popup .wt-gc-form-table tr td:nth-child(3){ width:25%; }
		.wt_gc_giftcard_add_new_template_popup .wt-gc-form-table tr td:nth-child(2){ width:50%; }
		.wt_gc_giftcard_add_new_template_popup .wt_gc_popup_footer{ height:50px; padding:0px 20px; background-color:#f3f3f3; box-sizing:border-box; padding-top:11px; border-top:1px solid #ddd;}

		/* Custom checkbox */
		.wt_gc_checkbox_container{display:block;position:absolute;z-index:11;margin-left:2px;margin-top:2px;padding-left:35px;margin-bottom:12px;cursor:pointer;font-size:22px;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}
		.wt_gc_checkbox_container input{position:absolute;opacity:0;cursor:pointer;height:0;width:0}
		.wt_gc_checkbox_checkmark{position:absolute;top:0;left:0;height:25px;width:25px;background-color:#eee;border-radius:20px; box-shadow:inset 0px 0px 2px 1px #aaa;}
		.wt_gc_checkbox_container:hover input~.wt_gc_checkbox_checkmark{background-color:#ccc}
		.wt_gc_checkbox_container input:checked~.wt_gc_checkbox_checkmark{background-color:#2196f3; box-shadow:inset 0px 0px 2px 1px #1172bf;}
		.wt_gc_checkbox_checkmark:after{content:"";position:absolute;display:none}
		.wt_gc_checkbox_container input:checked~.wt_gc_checkbox_checkmark:after{display:block}
		.wt_gc_checkbox_container .wt_gc_checkbox_checkmark:after{left:9px;top:5px;width:5px;height:10px;border:solid #fff;border-width:0 3px 3px 0;-webkit-transform:rotate(45deg);-ms-transform:rotate(45deg);transform:rotate(45deg)}
		
		</style>
		<?php
	}

	/**
	 *  Add JS to gift card product edit/add page
	 *
	 *  @since 1.0.0
	 */
	public function add_js_to_product_edit_page() {
		if ( $this->is_product_edit_page() ) {
			add_filter( 'wt_gc_include_admin_js_file', '__return_true', 1 ); // include admin JS file

			$not_hide_meta_boxes  = apply_filters( 'wt_gc_product_page_non_hidden_metaboxes', array( '#submitdiv', '#postimagediv', '#product_catdiv', '#tagsdiv-product_tag', '#wt-gc-custom-product-data-meta-box' ) );
			$gift_product_tab_url = admin_url( 'admin.php?page=' . WBTE_GC_FREE_PLUGIN_NAME . '&wt_gc_tab=' . $this->gift_card_product_tab_id );
			?>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					var not_hide_meta_boxes = '<?php echo esc_js( implode( ',', $not_hide_meta_boxes ) ); ?>';
					jQuery(not_hide_meta_boxes).show();

					
					var product_page_id = <?php echo absint( $this->get_product_edit_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>; 
					var page_title = '<?php esc_html_e( 'Edit gift card product', 'wt-gift-cards-woocommerce' ); ?>';
					
					jQuery('.wp-heading-inline').html(page_title).show();
					jQuery('.wp-heading-inline').before('<div class="wt_gc_giftcard_edit_page_view_all_link"><a class="button button-secondary" href="<?php echo esc_url( $gift_product_tab_url ); ?>" target="_parent"><span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'View gift card products', 'wt-gift-cards-woocommerce' ); ?></a></div>');               
					jQuery('#sample-permalink a, a#sample-permalink').attr({'target':'_blank'});
					jQuery('.options_group').has('p._tax_status_field').appendTo('#wt_gc_general_product_data');
					jQuery('select#_tax_status option[value="shipping"]').remove();

				});  
			</script>
			<?php
		}
	}

	/**
	 *  Keep the URL parameter after saving the product.
	 *
	 *  @since 1.0.0
	 *  @param string $url URL
	 *  @param string $url URL with Gift card product param added
	 */
	public function keep_gc_product_edit_url_param( $url ) {
		if ( $this->is_product_edit_page() ) {
			$url = sanitize_text_field( $url );

			$basename = sanitize_text_field( basename( wp_parse_url( $url, PHP_URL_PATH ) ) );

			if ( 'post.php' === $basename ) {
				$url = add_query_arg( array( 'wt_gc_product_edit' => $this->get_product_edit_id() ), $url );
			}
		}

		return $url;
	}

	/**
	 *  Add a hidden input in product edit page to identify its a gift card product
	 *
	 *  @since 1.0.0
	 */
	public function add_hidden_gc_product_edit_input( $post ) {
		if ( $this->is_product_edit_page() ) {
			?>
			<input type="hidden" name="wt_gc_product_edit" value="<?php echo esc_attr( $this->get_product_edit_id() ); ?>" />
			<?php
		}
	}

	/**
	 *  Adding custom product data meta box
	 *
	 *  @since 1.0.0
	 */
	public function add_custom_product_data_meta_box() {
		global $post;

		if ( ! $post || 'product' !== $post->post_type || ! $this->is_product_edit_page() ) {
			return;
		}

		add_meta_box( 'wt-gc-custom-product-data-meta-box', __( 'Product data', 'wt-gift-cards-woocommerce' ), array( $this, 'product_data_meta_box' ), 'product', 'normal' );
	}

	/**
	 *  Content for custom product data meta box
	 *
	 *  @since 1.0.0
	 */
	public function product_data_meta_box() {
		$product_id    = $this->get_product_edit_id();
		$meta_data_arr = self::get_product_metas( $product_id );

		include 'views/-product-data-meta-box.php';
	}

	/**
	 *  Checks current page is order edit page.
	 *  Non HPOS type
	 *
	 *  @since 1.0.0
	 *  @return bool is order edit page or not
	 */
	private function is_order_edit_page() {
		$basename = Wbte_Woocommerce_Gift_Cards_Free::get_basename();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ( 'post.php' === $basename && isset( $_GET['post'] ) && 'shop_order' === get_post_type( absint( wp_unslash( $_GET['post'] ) ) ) )
		|| ( 'post-new.php' === $basename && isset( $_GET['post_type'] ) && 'shop_order' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 *  Checks current page is gift product edit page
	 *
	 *  @since 1.0.0
	 *  @return bool is gift product edit page or not
	 */
	private function is_product_edit_page() {
		if ( defined( 'WBTE_GC_FORCE_PRODUCT_EDIT_PAGE' ) ) {
			return true;
		}

		$basename = Wbte_Woocommerce_Gift_Cards_Free::get_basename();

		if ( isset( $_REQUEST['wt_gc_product_edit'] ) ) {
			$edit_id = absint( wp_unslash( $_REQUEST['wt_gc_product_edit'] ) );

			if ( 'post.php' === $basename && 0 < $edit_id ) {
				return self::is_gift_card_product( $this->get_product_edit_id() );
			}
		} elseif ( 'post.php' === $basename ) {
			return self::is_gift_card_product( $this->get_product_edit_id() );
		}

		return false;
	}


	/**
	 *  Get gift product edit id
	 *
	 *  @since 1.0.0
	 *  @return int product edit id
	 */
	private function get_product_edit_id() {
		$post_id = ( isset( $_REQUEST['post'] ) ? absint( wp_unslash( $_REQUEST['post'] ) ) : 0 );
		return ( 0 === $post_id && isset( $_REQUEST['wt_gc_product_edit'] ) ? absint( wp_unslash( $_REQUEST['wt_gc_product_edit'] ) ) : $post_id );
	}

	/**
	 *  Print gift card email preview. Ajax callback
	 *
	 *  @since 1.0.0
	 */
	public function print_gift_card_email_preview() {
		if ( Wbte_Gc_Free_Security_Helper::check_write_access( 'gift_cards', 'wt_gc_admin_nonce' ) ) {
			$allowed_html = Wbte_Woocommerce_Gift_Cards_Free_Common::get_allowed_html();
			echo wp_kses( self::get_gift_card_email_preview(), $allowed_html ); // phpcs:ignore
		}
		exit();
	}

	/**
	 *  @since 1.0.0
	 *
	 *  Send gift card email. Ajax callback. (Sending via backend.)
	 */
	public function send_gift_card_email() {
		$out = array(
			'status' => false,
			'msg'    => __( 'Error', 'wt-gift-cards-woocommerce' ),
		);

		if ( ! is_user_logged_in() ) {
			echo wp_json_encode( $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit();
		}

		// Nonce verification.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_gc_admin_nonce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			echo wp_json_encode( $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit();
		}

		$out['status'] = true;
		$email         = ( isset( $_POST['wt_gc_send_email_address'] ) ? array_filter( explode( ',', sanitize_text_field( wp_unslash( $_POST['wt_gc_send_email_address'] ) ) ) ) : array() );
		if ( ! empty( $email ) ) {
			$email = array_filter( Wbte_Gc_Free_Security_Helper::sanitize_item( $email, 'email_arr' ) );
		}

		if ( empty( $email ) ) {
			$out['status'] = false;
			$out['msg']    = __( 'Please enter valid email address.', 'wt-gift-cards-woocommerce' );
		}

		if ( $out['status'] ) {
			$credit_amount = ( isset( $_POST['wt_gc_send_email_amount'] ) ? floatval( wp_unslash( $_POST['wt_gc_send_email_amount'] ) ) : 0 );
			if ( 0 >= $credit_amount ) {
				$out['status'] = false;
				$out['msg']    = __( 'Please enter valid amount.', 'wt-gift-cards-woocommerce' );
			}
		}

		if ( $out['status'] ) {
			$message   = ( isset( $_POST['wt_gc_send_email_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wt_gc_send_email_description'] ) ) : '' );
			$template  = ( isset( $_POST['wt_gc_send_email_template'] ) ? sanitize_text_field( wp_unslash( $_POST['wt_gc_send_email_template'] ) ) : 'general' );
			$from_name = ( isset( $_POST['wt_gc_send_email_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wt_gc_send_email_from_name'] ) ) : '' );

			$coupons_created = 0;

			foreach ( $email as $email_id ) {
				$coupon_data = $this->create_gift_card_coupon( $credit_amount, $message );

				if ( ! empty( $coupon_data ) ) {
					$coupon_id  = $coupon_data['coupon_id'];
					$coupon_obj = $coupon_data['coupon_obj'];

                                        $force_email_restriction = apply_filters( 'wt_gc_admin_coupon_set_email_restriction', true );
					// email restrictions
                                        if( $force_email_restriction ){
                                            $coupon_obj->set_email_restrictions( $email_id );
                                        }
					$coupon_obj->save();

					/**
					 *  After store credit coupon generated
					 *
					 *  @param  $coupon_obj  WC_Coupon object
					 *  @param  action  For which action the coupon was generated
					 */
					do_action( 'wt_gc_after_store_credit_coupon_generated', $coupon_obj, 'admin_send_gift_card' );

					++$coupons_created;

					/* email arguments */
					$credit_email_args = array(
						'send_to'     => $email_id,
						'coupon_id'   => $coupon_id,
						'coupon_code' => $coupon_data['coupon_code'],
						'message'     => $message,
						'template'    => $template,
						'from_name'   => $from_name,
						'extended'    => true,
						'by_admin'    => true,
					);

					/**
					 *  Alter email args before sending gift card
					 *
					 *  @since 1.0.0
					 *  @param  $credit_email_args  Email arguments array
					 *  @param  $coupon_obj  WC_Coupon object
					 */
					$credit_email_args = apply_filters( 'wt_gc_alter_admin_gift_card_email_args', $credit_email_args, $coupon_obj );

					/* trigger the mail to send */
					WC()->mailer();
					do_action( 'wt_gc_send_gift_card_coupon_to_customer', $credit_email_args );

					update_post_meta( $coupon_id, '_wt_smart_coupon_credit_activated', true );
					update_post_meta( $coupon_id, '_wt_smart_coupon_initial_credit', $credit_amount );
				}
			}

			/* translators: %s: Coupon count. */
			$out['msg'] = sprintf( __( 'Success. Total %s coupon(s) created and mailed.', 'wt-gift-cards-woocommerce' ), $coupons_created );
		}
		echo wp_json_encode( $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit();
	}


	/**
	 *  Save product meta data
	 *
	 *  @since 1.0.0
	 *  @param int $post_id  post id
	 */
	public function save_product_meta_data( $post_id ) {

		// Nonce verification.
		$nonce = ( isset( $_REQUEST['wt_gc_admin_nonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['wt_gc_admin_nonce'] ) ) : '' );
		$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_gc_admin_nonce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$gift_card_products = self::get_gift_card_products();

		if ( $this->is_product_edit_page()
			&& ( in_array( $post_id, $gift_card_products ) || empty( $gift_card_products ) ) // Already a gift card product or no gift card products
		) {
			foreach ( self::get_product_metas( $post_id ) as $meta_key => $meta_data ) {
				if ( isset( $_POST[ $meta_key ] ) ) {
					$meta_value = Wbte_Gc_Free_Security_Helper::sanitize_item( wc_clean( wp_unslash( $_POST[ $meta_key ] ) ), $meta_data['type'] );
					update_post_meta( $post_id, $meta_key, $meta_value );
				}
			}

			// Add to gift card products
			if ( empty( $gift_card_products ) ) {
				Wbte_Woocommerce_Gift_Cards_Free_Common::update_option( 'gift_card_products', array( $post_id ), $this->module_id );
			}

			/**
			 *  Update default image for newly created products that have no featured image
			 */
			if ( ! has_post_thumbnail( $post_id ) ) { // no deafult image
				$this->add_product_default_image( $post_id );
			}

			update_post_meta( $post_id, '_wt_gc_gift_card_product', $post_id ); // set product ID as meta value
		}
	}


	/**
	 *  Show gift card info in  order item row
	 *
	 *  @since 1.0.0
	 *  @param int        $item_id    order item id
	 *  @param object     $item       order item object
	 *  @param WC_Product $product       Product
	 */
	public function display_credit_info_in_order_item_row( $item_id, $item, $product ) {
		$template_data = $item->get_meta( 'wt_credit_coupon_template_details' );

		if ( $template_data && is_array( $template_data ) ) {
			$order = $item->get_order();
			$order_currency = $order->get_currency();

			foreach ( $template_data as $coupon_id => $data ) {
				$coupon_obj = new WC_Coupon( $coupon_id );
				?>
				<p style="margin-bottom:0px;">
					<?php esc_html_e( 'Coupon', 'wt-gift-cards-woocommerce' ); ?>: <?php echo esc_html( strtoupper( $coupon_obj->get_code() ) ); ?> <br />
					<?php esc_html_e( 'Coupon amount', 'wt-gift-cards-woocommerce' ); ?>:
					<?php 
						$args = array( 'coupon'     => $coupon_obj,
										'currency'   => $order_currency,
										'order'      => $order,
										'product'    => null );
						echo wp_kses_post(Wbte_Woocommerce_Gift_Cards_Free_Common::get_giftcard_price( $args ));
                    ?> 
                    <br />
					<?php esc_html_e( 'Recipient email', 'wt-gift-cards-woocommerce' ); ?>: <?php echo isset( $data['wt_credit_coupon_send_to'] ) ? esc_html( $data['wt_credit_coupon_send_to'] ) : ''; ?> <br />
				</p>
				<?php
			}
		}
	}

	/**
	 *  Show gift card image as thumbnail in  order item row
	 *
	 *  @since 1.0.0
	 *  @param string $product_image    Product image HTML
	 *  @param int    $item_id    order item id
	 *  @param object $item       order item object
	 */
	public function display_template_image_in_order_item_row( $product_image, $item_id, $item ) {
		$template_data = $item->get_meta( 'wt_credit_coupon_template_details' );

		if ( $template_data && is_array( $template_data ) ) {
			foreach ( $template_data as $coupon_id => $data ) {
				if ( isset( $data['wt_smart_coupon_template_image'] ) && '' !== $data['wt_smart_coupon_template_image'] ) {
					$template_data = self::get_gift_card_template( $data['wt_smart_coupon_template_image'] );

					if ( isset( $template_data['image_url'] ) && '' !== $template_data['image_url'] ) {
						if ( $product_image ) {
							$product_image = preg_replace( '(src="(.*?)")', 'src="' . esc_attr( $template_data['image_url'] ) . '"', $product_image );
							$product_image = preg_replace( '(srcset="(.*?)")', 'srcset="' . esc_attr( $template_data['image_url'] ) . '"', $product_image );
						} else {
							$product_image = '<img src="' . esc_attr( $template_data['image_url'] ) . '" srcset="' . esc_attr( $template_data['image_url'] ) . '" class="attachment-thumbnail size-thumbnail" alt="" loading="lazy" title="" width="150" height="150">';
						}
						break;
					}
				}
			}
		}

		return $product_image;
	}

	/**
	 * Meta box for store credit details. In store credit order detail page
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box_for_purchased_gift_cards() {
		global $post, $theorder;

		$screen = '';

		/* Non HPOS */
		if (
			is_object( $post ) && property_exists( $post, 'post_type' ) && property_exists( $post, 'ID' )
			&& 'shop_order' === $post->post_type && ! empty( get_post_meta( $post->ID, 'wt_credit_coupons', true ) )
		) {
			$screen = 'shop_order'; // valid NON HPOS order page with order coupons available
		}

		/* HPOS */
		if (
			is_object( $theorder ) && method_exists( $theorder, 'get_id' )
			&& ! empty( Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $theorder->get_id(), 'wt_credit_coupons' ) )
		) {
			$screen = wc_get_page_screen_id( 'shop-order' );
		}

		if ( '' !== $screen ) {
			add_meta_box( 'wt-gc-coupons-in-order', __( 'Gift cards purchased', 'wt-gift-cards-woocommerce' ), array( $this, 'gift_cards_metabox' ), $screen, 'normal' );
		}
	}

	/**
	 * Purchased gift cards metabox HTML
	 *
	 * @since 1.0.0
	 */
	public function gift_cards_metabox() {
		global $post, $theorder;

		$order_id        = ( is_object( $post ) && property_exists( $post, 'ID' ) ? $post->ID : $theorder->get_id() );
		$coupon_attached = Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $order_id, 'wt_credit_coupons' );
		$coupons         = maybe_unserialize( $coupon_attached );

		if ( ! empty( $coupons ) ) {
			$order       = wc_get_order( $order_id );
			$order_items = $order->get_items();

			$wt_store_credit_send_from = Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $order_id, 'wt_credit_coupon_send_from' );
			$wt_store_credit_send_to   = Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $order_id, 'wt_credit_coupon_send_to' );
			$wt_store_credit_message   = Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $order_id, 'wt_credit_coupon_send_to_message' );
			$wt_store_credit_template  = '';

			include plugin_dir_path( __FILE__ ) . 'views/-gift-cards-metabox-html.php';
		}
	}

	/**
	 *  Resend/send store credit purchased. (Order edit page) Ajax callback
	 *
	 *  @since 1.0.0
	 */
	public function resend_store_credit_coupon() {
		$out = array(
			'status' => false,
			'msg'    => __( 'Error', 'wt-gift-cards-woocommerce' ),
		);

		if ( ! is_user_logged_in() ) {
			echo wp_json_encode( $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit();
		}

		// Nonce verification.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_gc_admin_nonce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			echo wp_json_encode( $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit();
		}

		$order_id  = ( isset( $_POST['_wt_order_id'] ) ? absint( wp_unslash( $_POST['_wt_order_id'] ) ) : 0 );
		$coupon_id = ( isset( $_POST['_wt_coupon_id'] ) ? absint( wp_unslash( $_POST['_wt_coupon_id'] ) ) : 0 );

		if ( 0 === $order_id || 0 === $coupon_id ) {
			$out = array(
				'status' => false,
				'msg'    => __( 'Something went wrong', 'wt-gift-cards-woocommerce' ),
			);
		} else {
			$this->gift_card_email_trigger_type = ( isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : 'send' ); /** For customized order notes */
			$is_success                         = $this->do_send_mail( $order_id, $coupon_id, true );
		}

		if ( $is_success ) {
			$out = array(
				'status' => true,
				'msg'    => __( 'Success', 'wt-gift-cards-woocommerce' ),
			);
		}

		echo wp_json_encode( $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit();
	}

	/**
	 *  Save store credit related coupon meta when saving the coupon.
	 *
	 *  @since 1.0.0
	 */
	public function process_shop_coupon_meta( $post_id, $post ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have sufficient permission to perform this operation', 'wt-gift-cards-woocommerce' ) );
		}

		$coupon = new WC_Coupon( $post_id );

		if ( $coupon
		&& Wbte_Woocommerce_Gift_Cards_Free_Common::is_store_credit_coupon( $coupon )
		&& ! get_post_meta( $post_id, 'wt_auto_generated_store_credit_coupon', true )
		&& ! get_post_meta( $post_id, '_wt_gc_auto_generated_store_credit_coupon', true )
		) {
			update_post_meta( $post_id, '_wt_smart_coupon_credit_activated', true );
			update_post_meta( $post_id, '_wt_gc_store_credit_coupon', true ); // to exclude coupons created via gift card plugin in my account listing
		}
	}

	/**
	 *  Alter product action links
	 *
	 *  @since 1.0.0
	 *  @param array  $actions array of actions
	 *  @param object $post post object
	 *  @return array array of actions
	 */
	public function alter_product_action_links( $actions, $post ) {
		if ( 'product' !== $post->post_type ) {
			return $actions;
		}

		if ( ! self::is_gift_card_product( $post->ID ) ) {
			return $actions;
		}

		unset( $actions['inline hide-if-no-js'], $actions['inline'], $actions['duplicate'] );

		$actions['edit'] = '<a href="' . esc_url(
			add_query_arg(
				array(
					'page'                   => WBTE_GC_FREE_PLUGIN_NAME,
					'wt_gc_tab'              => $this->gift_card_product_tab_id,
					'wt_gc_product_edit_tab' => $post->ID,
				),
				admin_url( 'admin.php' )
			)
		) . '"">' . esc_html__( 'Edit', 'wt-gift-cards-woocommerce' ) . '</a>';

		return $actions;
	}

	public function add_gift_card_post_states( $post_states, $post ) {
		if ( 'product' !== $post->post_type ) {
			return $post_states;
		}

		if ( ! self::is_gift_card_product( $post->ID ) ) {
			return $post_states;
		}

		$gift_card_products = self::get_gift_card_products();

		// Only first item
		if ( is_array( $gift_card_products ) && ! empty( $gift_card_products ) && isset( $gift_card_products[0] ) && absint( $gift_card_products[0] ) === $post->ID ) {
			$post_states['wt_gc_gift_card_product'] = __( 'Gift card product', 'wt-gift-cards-woocommerce' );
		}

		return $post_states;
	}


	/**
	 *  Add default featured image for gift card products
	 *
	 *  @since 1.0.0
	 *  @param  int $product_id     ID of the product
	 */
	private function add_product_default_image( $product_id ) {
		$option_name   = 'wt_gc_default_featured_image'; // option name to save $attachment_id
		$attachment_id = absint( get_option( $option_name, 0 ) );

		if ( 0 === $attachment_id ) {
			// upload
			$attachment_id = $this->do_image_upload( $option_name );
		} elseif ( ! $this->is_attachment_exists( $attachment_id ) ) {
			// re-upload
			$attachment_id = $this->do_image_upload( $option_name );
		}

		if ( false !== $attachment_id ) {
			set_post_thumbnail( $product_id, $attachment_id ); // set as featured image
		}
	}



	/**
	 *  Upload the image to WP media library and save the attachment id to option table
	 *
	 *  @since  1.0.0
	 *  @param  string $option_name    Name of the option to save the attachment ID
	 *  @return int|bool    Attachment ID on success otherwise false
	 */
	private function do_image_upload( $option_name ) {
		$file_name = 'default_featured.png';
		$file_path = plugin_dir_path( __FILE__ ) . 'assets/images/' . $file_name;

		$file_array = array( 'name' => $file_name );

		$temp_file_name = wp_tempnam( wp_basename( $file_path ) );

		if ( $temp_file_name && copy( $file_path, $temp_file_name ) ) {
			$file_array['tmp_name'] = $temp_file_name;
		}

		if ( empty( $file_array['tmp_name'] ) ) {
			return false;
		}

		$attachment_id = media_handle_sideload( $file_array, 0, __( 'Gift card', 'wt-gift-cards-woocommerce' ) );

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		update_option( $option_name, $attachment_id ); // save the attachment id to option

		return $attachment_id;
	}


	/**
	 *  Check existence of attachment by ID
	 *
	 *  @param  int $attachment_id      ID of attachment
	 *  @return bool
	 */
	private function is_attachment_exists( $attachment_id ) {
		$attachment = get_post( $attachment_id );
		return ( $attachment && 'attachment' === $attachment->post_type );
	}


	/**
	 *  Prepare gift card product edit page URL
	 *
	 *  @since  1.0.0
	 *  @param  int   $product_id            Product ID
	 *  @param  array $custom_url_params     URL parameters
	 *  @return string   Edit page URL
	 */
	public function get_gift_card_product_edit_page_url( $product_id, $custom_url_params = array() ) {
		$current_url = Wbte_Woocommerce_Gift_Cards_Free_Admin::get_tab_url( $this->gift_card_product_tab_id );

		$url_params = array(
			'wt_gc_product_edit_tab' => $product_id,
			'page'                   => WBTE_GC_FREE_PLUGIN_NAME,
		);

		$url_params = array_merge( $url_params, $custom_url_params );

		return admin_url( 'admin.php' . $current_url . '&' . http_build_query( $url_params ) );
	}



	/**
	 *  Add a dummy gift card product as draft for newly installed users for reference
	 *
	 *  @since 1.0.0
	 */
	public function create_dummy_product() {
		$product = new WC_Product();
		$product->set_name( __( 'Gift Card', 'wt-gift-cards-woocommerce' ) );
		$product->set_regular_price( '0' );
		$product->set_description( __( 'This is an automatically created product for Gift card.', 'wt-gift-cards-woocommerce' ) );
		$product_id = $product->save();

		update_post_meta( $product_id, '_wt_gc_purchase_options', array( 'predefined' ) );
		update_post_meta( $product_id, '_wt_gc_amounts', '100,200,300' );
		update_post_meta( $product_id, '_wt_gc_enable_template', 'yes' );
		update_post_meta( $product_id, '_wt_gc_gift_card_product', $product_id ); // set product ID as meta value
		Wbte_Woocommerce_Gift_Cards_Free_Common::update_option( 'gift_card_products', array( $product_id ), $this->module_id );

		/**
		 *  Update default image for newly created products that have no featured image
		 */
		if ( ! has_post_thumbnail( $product_id ) ) { // no deafult image
			$this->add_product_default_image( $product_id );
		}

		wp_update_post(
			array(
				'ID'          => $product_id,
				'post_status' => 'draft',
			)
		); // change product status to `draft`
	}


	/**
	 *  Remove the product from Gift card product list when deleting it permanently
	 *
	 *  @since 1.0.0
	 */
	public function check_and_remove_from_gift_product_list( $postid, $post ) {

		$gift_card_products = self::get_gift_card_products();

		if ( in_array( $postid, $gift_card_products ) ) {

			// Remove from gift card products
			Wbte_Woocommerce_Gift_Cards_Free_Common::update_option( 'gift_card_products', array(), $this->module_id );
		}
	}
}

Wbte_Gc_Gift_Card_Free_Admin::get_instance();