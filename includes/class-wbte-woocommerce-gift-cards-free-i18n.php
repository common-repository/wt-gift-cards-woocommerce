<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.webtoffee.com/
 * @since      1.0.0
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/includes
 * @author     WebToffee <info@webtoffee.com>
 */
class Wbte_Woocommerce_Gift_Cards_Free_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wt-gift-cards-woocommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
