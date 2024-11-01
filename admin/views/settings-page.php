<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://www.webtoffee.com
 * @since      1.0.0
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

?>
<div class="wrap">
	<h2 class="wp-heading-inline"> <?php esc_html_e( 'WooCommerce Gift Cards', 'wt-gift-cards-woocommerce' ); ?> </h2>
	
	<?php
	do_action( 'wt_gc_plugin_before_settings_tab' );
	?>
	<div class="nav-tab-wrapper wp-clearfix wt-gc-tab-head">
		<?php
		$tab_head_arr = array(
			'wt-gc-general' => __( 'General settings', 'wt-gift-cards-woocommerce' ),
		);
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['debug'] ) ) {
			$tab_head_arr['wt-gc-debug'] = __( 'Debug', 'wt-gift-cards-woocommerce' );
		}

		self::generate_settings_tabhead( $tab_head_arr );

		?>
	</div>

	<div class="wt-gc-tab-container">       
		<?php
		$setting_views_a = array(
			'wt-gc-general' => '-admin-settings-general.php',
		);

		$setting_views_b = array(
			'wt-gc-help' => 'admin-settings-help.php',
		);
		?>

		<form method="post" class="wt_gc_settings_form">
			<input type="hidden" value="main" class="wt_gc_settings_base" />
			<?php

			// Set nonce:
			if ( function_exists( 'wp_nonce_field' ) ) {
				wp_nonce_field( WBTE_GC_FREE_PLUGIN_NAME );
			}

			?>

			<?php
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['debug'] ) ) {
				$setting_views_b['wt-gc-debug'] = 'admin-settings-debug.php';
			}

			$tab_key = self::get_tab_key();

			self::insert_tab_content_file( $setting_views_a, $tab_key );

			// settings tabs
			do_action( 'wt_gc_plugin_settings_form', array( 'tab_key' => $tab_key ) );
			?>
		</form>

		<?php
		self::insert_tab_content_file( $setting_views_b, $tab_key );
		// modules to hook outside settings form
		do_action( 'wt_gc_plugin_out_settings_form', array( 'tab_key' => $tab_key ) );
		?>
	</div>

	<div class="wt-gc-tab-right-container">
		<div class="wt_gc_upgrade_pro">
			<div class="wt_gc_upgrade_pro_main">
				<!-- <span style="font-size:41px; padding-top:20px;">🎉</span> -->
				<span style="font-size:41px; padding-top:20px;"><img src="<?php echo esc_url( WBTE_GC_FREE_URL . 'admin/images/coupon-image.svg' ); ?>" style="width: 46px;"></span>
				<div class="wt_gc_upgrade_pro_main_hd"><?php esc_html_e( 'Boost Your Sales and Customer Loyalty With Premium Gift Card Features!', 'wt-gift-cards-woocommerce' ); ?></div>
			</div>
			<div class="wt_gc_upgrade_pro_content">
				<ul class="pro_feature_list">
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Create an unlimited number of gift cards', 'wt-gift-cards-woocommerce' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Create physical gift cards', 'wt-gift-cards-woocommerce' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( "'Gift this product' option", 'wt-gift-cards-woocommerce' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Let users schedule gift card delivery', 'wt-gift-cards-woocommerce' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Print gift cards', 'wt-gift-cards-woocommerce' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Allow users to upload custom images for gift cards', 'wt-gift-cards-woocommerce' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Recommend products on gift certificates', 'wt-gift-cards-woocommerce' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Accept gift cards as payment method', 'wt-gift-cards-woocommerce' ); ?></li>
					<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Apply store credit on shipping, tax, and other fees', 'wt-gift-cards-woocommerce' ); ?></li>
				</ul>
			</div> 
			<div class="wt_gc_upgrade_pro_lower_green">
				<div class="wt_gc_extras">
					<h3> <?php esc_html_e( 'Try with confidence', 'wt-gift-cards-woocommerce' ); ?> </h3>
					<div class="wt_gc_extras_content" style="border-bottom: none; border-radius: 5px 5px 0px 0px;">
						<img src="<?php echo esc_url(WBTE_GC_FREE_URL . 'admin/images/30day-money-back.svg')?>">
						<h3  style="color: #606060;"><?php _e('100% No Risk Money Back Guarantee', 'wt-woocommerce-related-products'); ?></h3>
					</div>
					<div class="wt_gc_extras_content" style="border-radius: 0px 0px 5px 5px;">
						<img src="<?php echo esc_url(WBTE_GC_FREE_URL . 'admin/images/satisfaction-rating.svg')?>">
						<h3  style="color: #606060;"><?php _e('Excellent Support with 99% Satisfaction Rating', 'wt-woocommerce-related-products'); ?></h3>
					</div>
				</div>
				<div class="wt_gc_upgrade_pro_button">
					<a class="button button-secondary" href="<?php echo esc_url( 'https://www.webtoffee.com/product/woocommerce-gift-cards/?utm_source=free_plugin_sidebar&utm_medium=Gift_card_basic&utm_campaign=WooCommerce_Gift_Cards&utm_content=' . WBTE_GC_FREE_VERSION ); ?>" target="_blank"><?php esc_html_e( 'Check Out Premium', 'wt-gift-cards-woocommerce' ); ?> <span class="dashicons dashicons-arrow-right-alt" style="line-height:58px;font-size:14px;"></span> </a>
				</div>
			</div>
		</div> 
	</div>
	<?php
	do_action( 'wt_gc_plugin_after_settings_tab' );
	?>
</div>