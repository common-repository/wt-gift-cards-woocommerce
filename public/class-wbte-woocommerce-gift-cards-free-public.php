<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.webtoffee.com/
 * @since      1.0.0
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/public
 * @author     WebToffee <info@webtoffee.com>
 */
class Wbte_Woocommerce_Gift_Cards_Free_Public {

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

	/*
	 * module list, Module folder and main file must be same as that of module name
	 * Please check the `register_modules` method for more details
	 */
	public static $modules = array(
		'gift_card',
	);

	public static $existing_modules = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since       1.0.0
	 * @param       string $plugin_name       The name of the plugin.
	 * @param       string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wt-woocommerce-gift-cards-free-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		$params = array(
			'nonce' => wp_create_nonce( WBTE_GC_FREE_PLUGIN_NAME ),
			'msgs'  => array(
				'is_required' => __( 'is required', 'wt-gift-cards-woocommerce' ),
				'copied'      => __( 'Copied!', 'wt-gift-cards-woocommerce' ),
				'error'       => __( 'Error', 'wt-gift-cards-woocommerce' ),
				'loading'     => __( 'Loading...', 'wt-gift-cards-woocommerce' ),
				'please_wait' => __( 'Please wait...', 'wt-gift-cards-woocommerce' ),
			),
		);

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wt-woocommerce-gift-cards-free-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'wt_gc_params', $params );
	}


	/**
	 *  Registers public modules
	 *
	 *  @since 1.0.0
	 */
	public function register_modules() {
		Wbte_Woocommerce_Gift_Cards_Free::register_modules( self::$modules, 'wt_gc_public_modules', plugin_dir_path( __FILE__ ), self::$existing_modules );
	}

	/**
	 *  Check module enabled
	 *
	 *  @since 1.0.0
	 */
	public static function module_exists( $module ) {
		return in_array( $module, self::$existing_modules, true );
	}
}
