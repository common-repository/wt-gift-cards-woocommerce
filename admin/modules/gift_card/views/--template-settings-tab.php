<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 *  @since 1.0.0
 */
$this->manage_template_css(); // insert css
?>
<div class="wt_gc_giftcard_template_manage">
	<h3 class="wt-gc-form-settings-group-heading">
		<?php
		esc_html_e( 'Templates', 'wt-gift-cards-woocommerce' );
		echo wp_kses_post( Wbte_Woocommerce_Gift_Cards_Free_Admin::set_tooltip( 'templates_manage', $this->module_id ) );
		?>
	</h3>
	<div class="wt_gc_giftcard_template_main" data-page-type="manage">
		<!-- Templates will load here by Ajax --> 
	</div>
</div>