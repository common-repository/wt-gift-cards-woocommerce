<?php
/**
 * Product data meta box
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
<div class="woocommerce">
	<div class="panel-wrap product_data">
		<div id="wt_gc_general_product_data" class="panel woocommerce_options_panel">
			<div class="options_group show_if_simple" style="border-bottom:0px;">
				<p class="form-field _wt_gc_amounts_field">
					<label for="_wt_gc_amounts">
						<?php
						esc_html_e( 'Set predefined amounts', 'wt-gift-cards-woocommerce' );
						echo wp_kses_post( Wbte_Woocommerce_Gift_Cards_Free_Admin::set_tooltip( '_wt_gc_amounts', $this->module_id ) );
						?>
										 
					</label>
					<input type="hidden" name="wt_gc_admin_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wt_gc_admin_nonce' ) ); ?>">
					<input type="text" class="short" name="_wt_gc_amounts" id="_wt_gc_amounts" value="<?php echo esc_attr( $meta_data_arr['_wt_gc_amounts']['value'] ); ?>" placeholder="100,200,300">
					<span class="wt_gc_form_help"><?php esc_html_e( 'Multiple values need to be separated by comma.', 'wt-gift-cards-woocommerce' ); ?></span>
				</p>
			</div>
		</div>
		<div class="clear"></div>
	</div>
</div>