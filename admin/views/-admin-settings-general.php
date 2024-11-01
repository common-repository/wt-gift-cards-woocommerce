<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 *  @since 1.0.0
 */
?>
<style type="text/css">
.wt_gc_settings_container{ width:100%; float:left; }
.wt-gc-product-page-tab-form-table tr th:first-child{ width:35%; }
.wt-gc-product-page-tab-form-table tr td:nth-child(2){ width:60%; }
.wt-gc-product-page-tab-form-table tr td:nth-child(3){ width:0%; }
.wt-gc-tab-content{ padding-bottom:0px; }
</style>

<div class="wt-gc-tab-content">
	<div class="wt_gc_settings_container">       
		<h3 class="wt-gc-form-settings-group-heading"><?php esc_html_e( 'General', 'wt-gift-cards-woocommerce' ); ?></h3>
		<?php
		require WBTE_GC_FREE_MAIN_PATH . 'admin/views/--general-tab.php';

		do_action( 'wt_gs_intl_general_settings_tab_content' );
		?>
	</div>
	<?php
	Wbte_Woocommerce_Gift_Cards_Free_Admin::add_settings_footer( __( 'Save', 'wt-gift-cards-woocommerce' ) );
	?>
</div>