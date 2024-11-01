<?php
/**
 * My account store credit coupons section
 *
 * @link
 * @since 1.0.0
 *
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style type="text/css">
.wt_gc_myaccount_coupons_block{ width:100%; float:left; color:#000; font-size:14px; margin-top:40px;}
.wt_gc_myaccount_coupons_block_hd{ font-weight:600; margin-bottom:15px; }

.wt_gc_coupon_wrapper{ display:flex; clear:both; width:100%; position:relative; flex-direction:row; flex-wrap:wrap; display:flex; justify-content:flex-start; margin-bottom:15px; gap:30px; padding-top:15px; }

.wt_gc_coupon_wrapper .wt-single-coupon{ width:30%; min-width:240px; max-width:300px; margin:0px; padding:15px 10px; flex:1 0 30%;}
.wt_gc_coupon_wrapper .wt-single-coupon .coupon-history{ display:none; }
</style>

<div class="wt_gc_myaccount_coupons_block">
	<div class="wt_gc_myaccount_coupons_block_hd"><?php esc_html_e( 'Available coupons', 'wt-gift-cards-woocommerce' ); ?></div>
	<div class="wt_gc_coupon_wrapper">
		<?php
		$allowed_html = Wbte_Woocommerce_Gift_Cards_Free_Common::get_allowed_html();

		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$coupon_id = get_the_ID();
			$coupon    = new WC_Coupon( $coupon_id );

			/** Display store coupons using Smart coupon plugin functions. Plugin exists check done in the class file */
			$coupon_data                    = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon );
			$coupon_data['display_on_page'] = 'my_account';
            echo wp_kses( Wt_Smart_Coupon_Public::get_coupon_html($coupon, $coupon_data), $allowed_html ); // phpcs:ignore
		}
		?>
	</div>
</div>