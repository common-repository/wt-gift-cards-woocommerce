<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Fired during plugin deactivation
 *
 * @link       https://www.webtoffee.com/
 * @since      1.0.0
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/includes
 * @author     WebToffee <info@webtoffee.com>
 */
class Wbte_Woocommerce_Gift_Cards_Free_Deactivator {

	/**
	 * Deactivate the plugin
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		/**
		 *  Re-enable smart coupon store credit
		 */
		if ( apply_filters( 'wt_gc_force_reenable_smart_coupon_store_credit', true ) ) {
			$options = array( 'wt_sc_common_modules', 'wt_sc_admin_modules', 'wt_sc_public_modules' );

			foreach ( $options as $option ) {
				$option_val = get_option( $option );

				if ( $option_val && is_array( $option_val ) && isset( $option_val['store_credit'] ) && 0 === absint( $option_val['store_credit'] ) ) {
					$option_val['store_credit'] = 1;
					update_option( $option, $option_val );
				}
			}
		}
	}
}
