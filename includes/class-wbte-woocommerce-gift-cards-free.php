<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.webtoffee.com/
 * @since      1.0.0
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/includes
 * @author     WebToffee <info@webtoffee.com>
 */
class Wbte_Woocommerce_Gift_Cards_Free {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wbte_Woocommerce_Gift_Cards_Free_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public $plugin_admin  = null;
	public $plugin_common = null;
	public $plugin_public = null;

	private static $instance = null;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WBTE_GC_FREE_VERSION' ) ) {
			$this->version = WBTE_GC_FREE_VERSION;
		} else {
			$this->version = '1.1.1';
		}
		$this->plugin_name = WBTE_GC_FREE_PLUGIN_NAME;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_common_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 *  Get Instance
	 *
	 *  @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Woocommerce_Gift_Cards_Free();
		}

		return self::$instance;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wbte_Woocommerce_Gift_Cards_Free_Loader. Orchestrates the hooks of the plugin.
	 * - Wbte_Woocommerce_Gift_Cards_Free_I18n. Defines internationalization functionality.
	 * - Wbte_Woocommerce_Gift_Cards_Free_Admin. Defines all hooks for the admin area.
	 * - Wbte_Woocommerce_Gift_Cards_Free_Public. Defines all hooks for the public side of the site.
	 * - Wbte_Woocommerce_Gift_Cards_Free_Common. Defines all hooks/methods that are using both admin and public sections.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wbte-woocommerce-gift-cards-free-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wbte-woocommerce-gift-cards-free-i18n.php';

		/**
		 * Webtoffee Security Library
		 * Includes Data sanitization, Access checking
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wbte-gc-free-security-helper.php';

		/**
		 * The class responsible for defining all actions that commonly useful for admin/public.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'common/class-wbte-woocommerce-gift-cards-free-common.php';
		require_once plugin_dir_path( __DIR__ ) . 'common/classes/class-wbte-gc-store-credit-apply-free.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-wbte-woocommerce-gift-cards-free-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-wbte-woocommerce-gift-cards-free-public.php';

		$this->loader = new Wbte_Woocommerce_Gift_Cards_Free_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wbte_Woocommerce_Gift_Cards_Free_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wbte_Woocommerce_Gift_Cards_Free_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks that are common to the admin/public area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_common_hooks() {

		$this->plugin_common = Wbte_Woocommerce_Gift_Cards_Free_Common::get_instance( $this->get_plugin_name(), $this->get_version() );

		/**
		*   Initiate common modules
		*/
		$this->plugin_common->register_modules();

		$this->loader->add_action( 'init', $this->plugin_common, 'remove_smart_coupon_store_credit_functionality' );

		/**
		 *  Migrate settings from Smart coupon
		 */
		$this->loader->add_action( 'init', $this->plugin_common, 'migrate_settings_from_smart_coupon' );

		/**
		 *  Register store credit coupon type
		 */
		$this->loader->add_filter( 'woocommerce_coupon_discount_types', $this->plugin_common, 'add_store_credit_discount_type' );

		/**
		 *  This function will remove and reimburse the store credit on order status change to refunded, failed, cancelled etc
		 */
		$this->loader->add_action( 'woocommerce_order_status_changed', $this->plugin_common, 'manage_credit_on_order_status_change', 10, 4 );

		/**
		 *  Add store credit to cart coupon types
		 */
		$this->loader->add_filter( 'wc_get_cart_coupon_types', $this->plugin_common, 'add_store_credit_to_cart_coupon_type' );

		/**
		 *  Add product validity for store credit coupons.
		 */
		$this->loader->add_filter( 'woocommerce_coupon_is_valid_for_product', $this->plugin_common, 'set_coupon_validity_for_excluded_products', 12, 4 );

		/**
		 *  Apply store credit on cart
		 */
		Wbte_Gc_Store_Credit_Apply_Free::get_instance( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->plugin_admin = Wbte_Woocommerce_Gift_Cards_Free_Admin::get_instance( $this->get_plugin_name(), $this->get_version() );

		/**
		*   Initiate admin modules
		*/
		$this->plugin_admin->register_modules();

		if ( self::is_smart_coupon_active() ) {
			$this->loader->add_filter( 'wt_sc_admin_menu', $this->plugin_admin, 'admin_menu_under_smart_coupon', 11 ); /* Adding admin menu under smart coupon */
		} else {
			$this->loader->add_action( 'admin_menu', $this->plugin_admin, 'admin_menu', 11 ); /* Adding admin menu */
		}

		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'plugins_loaded', $this->plugin_admin, 'register_tooltips', 11 );

		/**
		*   Ajax hook for saving settings, Includes plugin main settings and settings from module
		 *
		*   @since 1.0.0
		*/
		$this->loader->add_action( 'wp_ajax_wt_gc_save_settings', $this->plugin_admin, 'save_settings' );

		$this->loader->add_filter( 'plugin_action_links_' . WBTE_GC_FREE_BASE_NAME, $this->plugin_admin, 'add_plugin_action_links' );

		/**
		 *  Links under plugin description section of plugins page
		 *
		 *  @since 1.0.0
		 */
		$this->loader->add_filter( 'plugin_row_meta', $this->plugin_admin, 'plugin_row_meta', 10, 2 );

		/**
		 *  Add a highlight on Smart coupon menu.
		 *  This is to inform a new menu is added under Smart coupon
		 */
		if ( self::is_smart_coupon_active() ) {
			$this->loader->add_action( 'admin_head', $this->plugin_admin, 'add_css_for_smart_coupon_menu_highlight' );

		} else {

			/**
			 *  Column for allowed emails in coupon listing page
			 */
			$this->loader->add_filter( 'manage_edit-shop_coupon_columns', $this->plugin_admin, 'add_coupon_allowed_email_column', 10, 1 );
			$this->loader->add_action( 'manage_shop_coupon_posts_custom_column', $this->plugin_admin, 'add_coupon_allowed_email_column_content', 10, 2 );

		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$this->plugin_public = new Wbte_Woocommerce_Gift_Cards_Free_Public( $this->get_plugin_name(), $this->get_version() );

		/**
		*   Initiate public modules
		*/
		$this->plugin_public->register_modules();

		$this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 *  Register modules
	 *
	 *  @since 1.0.0
	 */
	public static function register_modules( $modules, $module_option_name, $module_path, &$existing_modules ) {
		$wt_gc_modules = get_option( $module_option_name );
		if ( false === $wt_gc_modules ) {
			$wt_gc_modules = array();
		}

		$is_not_common_module = false;

		if ( false !== strpos( $module_option_name, '_public_' ) || false !== strpos( $module_option_name, '_admin_' ) ) {
			$is_not_common_module = true;
		}

		foreach ( $modules as $module ) {
			$is_active = 1;

			if ( isset( $wt_gc_modules[ $module ] ) ) {
				$is_active = $wt_gc_modules[ $module ]; // checking module status
			} else {
				$wt_gc_modules[ $module ] = 1; // add it to module list, default status is active
			}

			$module_file = $module_path . "modules/$module/$module.php";

			if ( file_exists( $module_file ) && 1 === (int) $is_active ) {
				$include_module_file = true;

				if ( $is_not_common_module ) {
					/* Common modules: module entry exists and module not active. So do not include the current public/admin module files */
					if ( in_array( $module, Wbte_Woocommerce_Gift_Cards_Free_Common::$modules, true ) && ! Wbte_Woocommerce_Gift_Cards_Free_Common::module_exists( $module ) ) {
						$include_module_file = false;
					}
				}

				if ( $include_module_file ) {
					$existing_modules[] = $module; // this is for module_exits checking
					require_once $module_file;
				}
			}
		}

		$out = array();

		foreach ( $wt_gc_modules as $k => $m ) {
			if ( in_array( $k, $modules, true ) ) {
				$out[ $k ] = $m;
			}
		}

		update_option( $module_option_name, $out );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wbte_Woocommerce_Gift_Cards_Free_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 *  Checks WebToffee Smart coupon plugin is active
	 *
	 *  @since 1.0.0
	 *  @param bool is active or not
	 */
	public static function is_smart_coupon_active() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		return is_plugin_active( 'wt-smart-coupon-pro/wt-smart-coupon-pro.php' );
	}


	/**
	 *  Get current filename
	 *
	 *  @since  1.0.0
	 *  @return string      File name
	 */
	public static function get_basename() {
		return sanitize_text_field( basename( wp_parse_url( ( isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '' ), PHP_URL_PATH ) ) );
	}
}
