<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.webtoffee.com/
 * @since      1.0.0
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/admin
 * @author     WebToffee <info@webtoffee.com>
 */
class Wbte_Woocommerce_Gift_Cards_Free_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public static $tooltip_arr = array();

	/*
	 * module list, Module folder and main file must be same as that of module name
	 * Please check the `register_modules` method for more details
	 */
	public static $modules = array(
		'gift_card',
		'freevspro',
	);

	public static $existing_modules = array();

	private static $instance = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 *  Get Instance
	 *
	 *  @since 1.0.0
	 */
	public static function get_instance( $plugin_name, $version ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Woocommerce_Gift_Cards_Free_Admin( $plugin_name, $version );
		}

		return self::$instance;
	}

	/**
	 *  Registers modules
	 *
	 *  @since 1.0.0
	 */
	public function register_modules() {
		Wbte_Woocommerce_Gift_Cards_Free::register_modules( self::$modules, 'wt_gc_admin_modules', plugin_dir_path( __FILE__ ), self::$existing_modules );
	}

	/**
	 *  Check module enabled
	 *
	 *  @since 1.0.0
	 */
	public static function module_exists( $module ) {
		return in_array( $module, self::$existing_modules, true );
	}

	/**
	 * Registers menu options
	 * Hooked into admin_menu
	 *
	 * @since    1.0.0
	 */
	public function admin_menu() {
		$menus = array(
			array(
				'menu',
				__( 'Gift cards', 'wt-gift-cards-woocommerce' ),
				__( 'Gift cards', 'wt-gift-cards-woocommerce' ),
				'manage_woocommerce',
				WBTE_GC_FREE_PLUGIN_NAME,
				array( $this, 'admin_settings_page' ),
				'dashicons-tickets',
				59,
			),
		);
		$menus = apply_filters( 'wt_gc_admin_menu', $menus );

		if ( is_array( $menus ) ) {
			foreach ( $menus as $menu ) {
				if ( 'submenu' === $menu[0] ) {
					if ( isset( $menu[6] ) ) {
						add_submenu_page( $menu[1], $menu[2], $menu[3], $menu[4], $menu[5], $menu[6] );
					} else {
						add_submenu_page( $menu[1], $menu[2], $menu[3], $menu[4], $menu[5] );
					}
				} else {
					add_menu_page( $menu[1], $menu[2], $menu[3], $menu[4], $menu[5], $menu[6], $menu[7] );
				}
			}
		}

		if ( function_exists( 'remove_submenu_page' ) ) {
			remove_submenu_page( WBTE_GC_FREE_PLUGIN_NAME, WBTE_GC_FREE_PLUGIN_NAME );
		}
	}


	/**
	 * Add menu under Smart coupon plugin if Smart coupon plugin is active
	 *
	 * @since    1.0.0
	 */
	public function admin_menu_under_smart_coupon( $menus ) {
		$menus[] = array(
			'submenu',
			WT_SC_PLUGIN_NAME,
			__( 'Gift cards', 'wt-gift-cards-woocommerce' ),
			__( 'Gift cards', 'wt-gift-cards-woocommerce' ),
			'manage_woocommerce',
			WBTE_GC_FREE_PLUGIN_NAME,
			array( $this, 'admin_settings_page' ),
		);

		return $menus;
	}


	/**
	 *  Admin settings page
	 *
	 *  @since    1.0.0
	 */
	public function admin_settings_page() {
		include WBTE_GC_FREE_MAIN_PATH . 'admin/views/settings-page.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$gc_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( WBTE_GC_FREE_PLUGIN_NAME === $gc_page || apply_filters( 'wt_gc_include_admin_css_file', false ) ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wt-woocommerce-gift-cards-free-admin.css', array( 'wc-admin-layout' ), $this->version, 'all' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$gc_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( WBTE_GC_FREE_PLUGIN_NAME === $gc_page || apply_filters( 'wt_gc_include_admin_js_file', false ) ) {

			$params = array(
				'no_image' => Wbte_Woocommerce_Gift_Cards_Free_Common::$no_image,
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wt_gc_admin_nonce' ),
				'msgs'     => array(
					/* translators: 1: line break, 2: html anchor link open tag, 3: html anchor link close tag */
					'settings_error'         => wp_kses_post( sprintf( __( 'Unable to update settings due to an internal error. %1$s To troubleshoot please click %2$s here. %3$s', 'wt-gift-cards-woocommerce' ), '<br />', '<a href="https://www.webtoffee.com/how-to-fix-the-unable-to-save-settings-issue/" target="_blank">', '</a>' ) ),
					'is_required'            => esc_html__( 'is required', 'wt-gift-cards-woocommerce' ),
					'copied'                 => esc_html__( 'Copied!', 'wt-gift-cards-woocommerce' ),
					'error'                  => esc_html__( 'Error', 'wt-gift-cards-woocommerce' ),
					'loading'                => esc_html__( 'Loading...', 'wt-gift-cards-woocommerce' ),
					'please_wait'            => esc_html__( 'Please wait...', 'wt-gift-cards-woocommerce' ),
					'are_you_sure'           => esc_html__( 'Are you sure?', 'wt-gift-cards-woocommerce' ),
					'are_you_sure_to_delete' => esc_html__( 'Are you sure you want to delete?', 'wt-gift-cards-woocommerce' ),
					'saving'                 => esc_html__( 'Saving...', 'wt-gift-cards-woocommerce' ),
				),
			);

			wp_enqueue_media();
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wt-woocommerce-gift-cards-free-admin.js', array( 'jquery', 'wp-color-picker', 'jquery-tiptip', 'wc-enhanced-select' ), $this->version, false );
			wp_localize_script( $this->plugin_name, 'wt_gc_params', $params );
		}
	}

	/**
	 * Generate tab head for settings page.
	 *
	 * @since     1.0.0
	 */
	public static function generate_settings_tabhead( $title_arr, $type = 'plugin' ) {
		$out_arr = apply_filters( 'wt_gc_' . $type . '_settings_tabhead', $title_arr );
		$tab_key = self::get_tab_key();

		foreach ( $out_arr as $k => $v ) {
			if ( is_array( $v ) ) {
				$v = ( isset( $v[2] ) ? $v[2] : '' ) . $v[0] . ' ' . ( isset( $v[1] ) ? $v[1] : '' );
			}
			?>
				<a class="nav-tab <?php echo esc_attr( $k === $tab_key ? 'nav-tab-active' : '' ); ?>" data-tab-id="<?php echo esc_attr( $k ); ?>" href="<?php echo esc_url( self::get_tab_url( $k ) ); ?>"><?php echo wp_kses_post( $v ); ?></a>
			<?php
		}
	}

	public static function insert_tab_content_file( $tab_files, $tab_key ) {
		if ( isset( $tab_files[ $tab_key ] ) ) {
			$settings_view = WBTE_GC_FREE_MAIN_PATH . 'admin/views/' . $tab_files[ $tab_key ];

			if ( file_exists( $settings_view ) ) {
				include $settings_view;
			}
		}
	}

	/**
	 *   Form field generator
	 *
	 *   @since 1.0.0
	 */
	public static function generate_form_field( $args, $base = '' ) {
		include WBTE_GC_FREE_MAIN_PATH . 'admin/views/-form-field-generator.php';
	}

	private static function get_tab_key() {
		return ( isset( $_GET['wt_gc_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['wt_gc_tab'] ) ) : 'wt-gc-general' );
	}

	public static function get_tab_url( $tab_key ) {

		$get_arr_keys = array( 'page', 'post_type', 'product_status_tab_key', 'action' );
		$get_arr      = array( 'wt_gc_tab' => $tab_key );

		// Prepare the URL parameter array
		foreach ( $get_arr_keys as $get_arr_key ) {
			if ( isset( $_GET[ $get_arr_key ] ) ) {
				$get_arr[ $get_arr_key ] = sanitize_text_field( wp_unslash( $_GET[ $get_arr_key ] ) );
			}
		}

		return '?' . http_build_query( $get_arr );
	}

	/**
	 *  Set tooltip for form fields
	 *
	 *  @since 1.0.0
	 */
	public static function set_tooltip( $key, $base_id = '', $custom_css = '' ) {
		$tooltip_text = self::get_tooltips( $key, $base_id );

		if ( '' !== $tooltip_text ) {
			$tooltip_text = "<span style='display:inline-block; color:#16a7c5; " . ( '' !== $custom_css ? esc_attr( $custom_css ) : esc_attr( 'margin-top:1px; margin-left:2px; position:absolute;' ) ) . "' class='dashicons dashicons-editor-help wt-gc-tips' data-wt-gc-tip='" . esc_attr( $tooltip_text ) . "'></span>";
		}
		return $tooltip_text;
	}

	/**
	 *  Get tooltip config data for non form field items
	 *
	 *  @since 1.0.0
	 *  @return array 'class': class name to enable tooltip, 'text': tooltip text including data attribute if not empty
	 */
	public static function get_tooltip_configs( $key, $base_id = '' ) {
		$out  = array(
			'class' => '',
			'text'  => '',
		);
		$text = self::get_tooltips( $key, $base_id );

		if ( '' !== $text ) {
			$out['text']  = " data-wt-gc-tip='" . esc_attr( $text ) . "'";
			$out['class'] = ' wt-gc-tips';
		}
		return $out;
	}

	/**
	 *  This function will take tooltip data from modules
	 *
	 *  @since 1.0.0
	 */
	public function register_tooltips() {
		include plugin_dir_path( __FILE__ ) . 'data/data-tooltip.php';
		self::$tooltip_arr = array(
			'main' => $arr,
		);
		/* hook for modules to register tooltip */
		self::$tooltip_arr = apply_filters( 'wt_gc_alter_tooltip_data', self::$tooltip_arr );
	}

	/**
	 *  Get tooltips
	 *
	 *  @since  1.0.0
	 *  @param  string $key array key for tooltip item
	 *  @param  string $base module base id
	 *  @return tooltip content, empty string if not found
	 */
	public static function get_tooltips( $key, $base_id = '' ) {
		$arr = ( '' !== $base_id && isset( self::$tooltip_arr[ $base_id ] ) ? self::$tooltip_arr[ $base_id ] : self::$tooltip_arr['main'] );
		return ( isset( $arr[ $key ] ) ? $arr[ $key ] : '' );
	}

	/**
	 * Add setting tab footer
	 *
	 *  @since 1.0.0
	 */
	public static function add_settings_footer( $settings_button_title = '', $settings_footer_left = '', $settings_footer_right = '' ) {
		include WBTE_GC_FREE_MAIN_PATH . 'admin/views/admin-settings-save-button.php';
	}

	/**
	 *  Save admin settings and module settings ajax hook
	 *
	 *  @since 1.0.0
	 */
	public function save_settings() {
		$out = array(
			'status' => false,
			'msg'    => esc_html__( 'Error', 'wt-gift-cards-woocommerce' ),
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

		$base    = ( isset( $_POST['wt_gc_settings_base'] ) ? sanitize_text_field( wp_unslash( $_POST['wt_gc_settings_base'] ) ) : 'main' );
		$base_id = ( 'main' === $base ? '' : Wbte_Woocommerce_Gift_Cards_Free_Common::get_module_id( $base ) );

		$this->save_settings_inner( $base_id );

		$out['status'] = true;
		$out['msg']    = esc_html__( 'Settings Updated', 'wt-gift-cards-woocommerce' );

		/**
		 *  Alter `settings saved` response
		 *
		 *  @since 1.0.0
		 *  @param array    $out        Default response array
		 *  @param string   $base_id    Settings base ID
		 */
		$out = apply_filters( 'wt_gc_intl_alter_setting_saved_response', $out, $base_id );

		echo wp_json_encode( $out ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit();
	}

	public function save_settings_inner( $base_id = '' ) {

		// Nonce verification.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_gc_admin_nonce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$the_options = Wbte_Woocommerce_Gift_Cards_Free_Common::get_settings( $base_id );
		do_action( 'wt_gc_intl_before_setting_update', $the_options, $base_id );

		// Multi select form fields array. (It will not return a POST val if it's value is empty so we need to set default value)
		$default_val_needed_fields = Wbte_Woocommerce_Gift_Cards_Free_Common::settings_needed_default_val( $base_id );

		$validation_rule = Wbte_Woocommerce_Gift_Cards_Free_Common::settings_validation_rule( $base_id );

		foreach ( $the_options as $key => $value ) {
			if ( isset( $_POST[ $key ] ) ) {
				$the_options[ $key ] = Wbte_Gc_Free_Security_Helper::sanitize_data( wc_clean( wp_unslash( $_POST[ $key ] ) ), $key, $validation_rule );
			} elseif ( array_key_exists( $key, $default_val_needed_fields ) ) {
					/* Set a hidden field for every multi-select, checkbox etc fields in the form. This will be used to add a default value when it does not have any POST value. */
				if ( isset( $_POST[ $key . '_hidden' ] ) ) {
					$the_options[ $key ] = $default_val_needed_fields[ $key ];
				}
			}
		}

		Wbte_Woocommerce_Gift_Cards_Free_Common::update_settings( $the_options, $base_id );

		do_action( 'wt_gc_intl_after_setting_update', $the_options, $base_id );
	}

	/**
	 * Add plugin action links in WP plugins page.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_action_links( $links ) {
            $links['settings']        = '<a href="' . esc_url( admin_url( 'admin.php?page=' . WBTE_GC_FREE_PLUGIN_NAME ) ) . '">' . __( 'Settings', 'wt-gift-cards-woocommerce' ) . '</a>';
            $links['premium-upgrade'] = '<a target="_blank" href="https://www.webtoffee.com/product/woocommerce-gift-cards/?utm_source=free_plugins_listing_page&utm_medium=Gift_card_basic&utm_campaign=WooCommerce_Gift_Cards&utm_content=' . esc_attr( WBTE_GC_FREE_VERSION ) . '" style="color: #3db634; font-weight: 500;">' . esc_html__( 'Premium Upgrade', 'wt-gift-cards-woocommerce' ) . '</a>';
            if ( array_key_exists( 'deactivate', $links ) ) {
                $links[ 'deactivate' ] = str_replace( '<a', '<a class="wbtegiftcards-deactivate-link"', $links[ 'deactivate' ] );
            }
            return $links;
	}

	/**
	 *  Links under plugin description section of plugins page
	 *
	 *  @since 1.0.0
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( WBTE_GC_FREE_BASE_NAME !== $file ) {
			return $links;
		}

		$links['documentation'] = '<a target="_blank" href="https://www.webtoffee.com/category/documentation/woocommerce-gift-cards/">' . esc_html__( 'Docs', 'wt-gift-cards-woocommerce' ) . '</a>';
		$links['support']       = '<a target="_blank" href="https://www.webtoffee.com/contact/">' . esc_html__( 'Support', 'wt-gift-cards-woocommerce' ) . '</a>';
		$links['review']        = '<a target="_blank" href="https://wordpress.org/support/plugin/wt-gift-cards-woocommerce/reviews/#new-post">' . esc_html__( 'Review', 'wt-gift-cards-woocommerce' ) . '</a>';

		return $links;
	}


	/**
	 *  Add CSS to highlight Smart coupon menu
	 *
	 *  @since 1.0.0
	 */
	public function add_css_for_smart_coupon_menu_highlight() {
		if ( 1 !== absint( get_option( 'wbte_gc_smart_coupon_menu_higlight_is_removed', 0 ) ) ) {
			$print_css = true;

			/**
			 *  Hide the highlight once the Gift card page was visited
			 */
			if ( isset( $_GET['page'] ) && WBTE_GC_FREE_PLUGIN_NAME === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
				add_option( 'wbte_gc_smart_coupon_menu_higlight_is_removed', 1 );
				$print_css = false;
			}

			if ( $print_css ) {
				$settings_page_url = 'admin.php?page=' . WBTE_GC_FREE_PLUGIN_NAME;
				?>
				<style type="text/css">
					li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]{ position:relative; }
					a.toplevel_page_wt-smart-coupon-for-woo::after, li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]::after{ content: "."; position:absolute; right:10px; color:#d63638; font-size:45px; font-weight:bold; margin-top:-5px; top:0px; line-height:0px; }  
					li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]::after{ right:70px; }
				</style>
				<?php
			}
		}
	}


	/**
	 * Is current admin page is HPOS enabled orders page
	 *
	 * @since   1.0.0
	 * @static
	 * @return  bool    True when current page is HPOS orders page
	 */
	public static function is_hpos_orders_page() {
		$basename = Wbte_Woocommerce_Gift_Cards_Free::get_basename();
		$page     = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		return ( 'admin.php' === $basename && 'wc-orders' === $page && ( '' === $action || '-1' === $action ) );
	}


	/**
	 * Is current admin page is HPOS enabled order edit page
	 *
	 * @since   1.0.0
	 * @static
	 * @return  bool    True when current page is HPOS order edit page
	 */
	public static function is_hpos_order_edit_page() {
		$basename = Wbte_Woocommerce_Gift_Cards_Free::get_basename();
		$page     = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		return ( 'admin.php' === $basename && 'wc-orders' === $page && 'edit' === $action );
	}


	/**
	 *  Column title for `allowed emails` in coupon listing page. Column will be added just after `categories` column
	 *
	 *  @since  1.0.0
	 *  @param  array $columns    Columns array
	 *  @return array    $columns    Columns array
	 */
	public function add_coupon_allowed_email_column( $columns ) {
		$out                = array();
		$email_column_title = __( 'Allowed email(s)', 'wt-gift-cards-woocommerce' );
		$email_column_key   = 'wt_sc_coupon_allowed_emails';

		foreach ( $columns as $column_key => $column_title ) {
			$out[ $column_key ] = $column_title;

			if ( 'usage' === $column_key ) {
				$out[ $email_column_key ] = $email_column_title;
			}
		}

		if ( ! isset( $out[ $email_column_key ] ) ) {
			$out[ $email_column_key ] = $email_column_title;
		}

		return $out;
	}


	/**
	 *  Column content for `allowed emails` in coupon listing page.
	 *
	 *  @since  1.0.0
	 *  @param  string $column_name    Columns name
	 *  @param  int    $post_ID        Post ID
	 */
	public function add_coupon_allowed_email_column_content( $column_name, $post_ID ) {
		if ( 'wt_sc_coupon_allowed_emails' === $column_name ) {
			$coupon_obj   = new WC_Coupon( $post_ID );
			$restrictions = $coupon_obj->get_email_restrictions();
			echo esc_html( is_array( $restrictions ) ? implode( ', ', $restrictions ) : '' );
		}
	}
}
