<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Fired during plugin activation
 *
 * @link       https://www.webtoffee.com/
 * @since      1.0.0
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/includes
 * @author     WebToffee <info@webtoffee.com>
 */
class Wbte_Woocommerce_Gift_Cards_Free_Activator {

	/**
	 * Activate the plugin
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Force include module files. Maybe the modules are not enabled during the activation.
		include_once WBTE_GC_FREE_MAIN_PATH . 'common/modules/gift_card/gift_card.php';
		include_once WBTE_GC_FREE_MAIN_PATH . 'admin/modules/gift_card/gift_card.php';

		// Create a new gift card product for users installing the plugin for the first time.
		if ( empty( Wbte_Gc_Gift_Card_Free_Common::get_gift_card_products() ) ) {
			$wt_gc_giftcard_admin = Wbte_Gc_Gift_Card_Free_Admin::get_instance();
			$wt_gc_giftcard_admin->create_dummy_product();
		}
	}
}
