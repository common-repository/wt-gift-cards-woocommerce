<?php
/**
 * @link              https://www.webtoffee.com/
 * @since             1.0.0
 * @package           Wbte_Woocommerce_Gift_Cards_Free
 *
 * @wordpress-plugin
 * Plugin Name:       WebToffee Gift Cards for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/wt-gift-cards-woocommerce/
 * Description:       Create and manage beautiful gift cards for your WooCommerce store.
 * Version:           1.1.1
 * Author:            WebToffee
 * Author URI:        https://www.webtoffee.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wt-gift-cards-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 *   Check pro version is there
 */
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'wt-woocommerce-gift-cards/wt-woocommerce-gift-cards.php' ) ) {
	return;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'WBTE_GC_FREE_VERSION', '1.1.1' );

define( 'WBTE_GC_FREE_FILE_NAME', __FILE__ );
define( 'WBTE_GC_FREE_BASE_NAME', plugin_basename( WBTE_GC_FREE_FILE_NAME ) );
define( 'WBTE_GC_FREE_MAIN_PATH', plugin_dir_path( WBTE_GC_FREE_FILE_NAME ) );
define( 'WBTE_GC_FREE_URL', plugin_dir_url( WBTE_GC_FREE_FILE_NAME ) );

define( 'WBTE_GC_FREE_PLUGIN_NAME', 'wt-woocommerce-gift-cards' ); // Keep it same as pro
define( 'WBTE_GC_FREE_PLUGIN_ID', 'wt_woocommerce_gift_cards_free' );
define( 'WBTE_GC_FREE_SETTINGS_FIELD', WBTE_GC_FREE_PLUGIN_NAME ); // Option name to store settings.

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wbte-woocommerce-gift-cards-free-activator.php
 */
function wbte_activate_woocommerce_gift_cards_free() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wbte-woocommerce-gift-cards-free-activator.php';
	Wbte_Woocommerce_Gift_Cards_Free_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wbte-woocommerce-gift-cards-free-deactivator.php
 */
function wbte_deactivate_woocommerce_gift_cards_free() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wbte-woocommerce-gift-cards-free-deactivator.php';
	Wbte_Woocommerce_Gift_Cards_Free_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'wbte_activate_woocommerce_gift_cards_free' );
register_deactivation_hook( __FILE__, 'wbte_deactivate_woocommerce_gift_cards_free' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, public-facing site hooks, common hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wbte-woocommerce-gift-cards-free.php';

if ( ! class_exists( 'WooCommerce' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	deactivate_plugins( WBTE_GC_FREE_BASE_NAME );
	wp_die( __( 'Oops! Woocommerce not activated, It should required for `Gift cards for woocommerce`.', 'wt-gift-cards-woocommerce' ), '', array( 'back_link' => 1 ) );
}

/**
 * Uninstall feedback
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wbte-uninstall-feedback.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function wbte_run_woocommerce_gift_cards_free() {

	$plugin = new Wbte_Woocommerce_Gift_Cards_Free();
	$plugin->run();
}


wbte_run_woocommerce_gift_cards_free();
