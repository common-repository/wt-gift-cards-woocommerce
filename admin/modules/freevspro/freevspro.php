<?php
/**
 * Free vs Pro Comparison
 *
 * @link
 * @since 1.3.0
 *
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wbte_Gc_Freevspro {

	public $module_id               = '';
	public static $module_id_static = '';
	public $module_base             = 'freevspro';
	private static $instance        = null;

	public function __construct() {
		$this->module_id        = $this->module_base;
		self::$module_id_static = $this->module_id;

		add_filter( 'wt_gc_plugin_settings_tabhead', array( $this, 'settings_tabhead' ), 1 );
		add_filter( 'wt_gc_plugin_out_settings_form', array( $this, 'out_settings_form' ), 1 );
	}


	/**
	 *  Get Instance
	 *
	 *  @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Gc_Freevspro();
		}
		return self::$instance;
	}

	/**
	 *  Coupon banner tab content
	 *
	 *  @since 1.0.0
	 */
	public function out_settings_form( $args = array() ) {
		if ( isset( $args['tab_key'] ) && $this->module_id === $args['tab_key'] ) {
			include 'views/goto-pro.php';
		}
	}

	/**
	 *  Tab head for plugin settings page
	 *
	 *  @since 1.0.0
	 */
	public function settings_tabhead( $arr ) {
		$added   = 0;
		$out_arr = array();
		foreach ( $arr as $k => $v ) {
			$out_arr[ $k ] = $v;
			if ( 'wt-gc-help' === $k && 0 === $added ) { /* After help */
				$out_arr[ $this->module_id ] = __( 'Free vs. Pro', 'wt-gift-cards-woocommerce' );
				$added                       = 1;
			}
		}
		if ( 0 === $added ) {
			$out_arr[ $this->module_id ] = __( 'Free vs. Pro', 'wt-gift-cards-woocommerce' );
		}
		return $out_arr;
	}
}
Wbte_Gc_Freevspro::get_instance();
