<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$this->manage_template_css(); // insert css

?>
<style type="text/css">
.button-secondary.wt_gc_email_preview_btn{ float:right; margin-right:10px; }
.wt_gc_email_preview{width:100%; height:auto;}
.wt_gc_email_preview_loading{ width:100%; height:100px; line-height:100px; text-align:center; }

.wt_gc_email_message{ width:100%; height:auto; border-left:solid 5px #DEDEDE; font-style:italic; margin-top:25px; padding:5px 15px; }
</style>

<!-- Email preview popup -->
<div class="wt_gc_email_preview_popup wt_gc_popup" style="width:900px;">
	<div class="wt_gc_popup_hd">
		<div class="wt_gc_popup_title"><?php esc_html_e( 'Email preview', 'wt-gift-cards-woocommerce' ); ?></div>
		<div class="wt_gc_popup_close">X</div>
	</div>
	<div class="wt_gc_popup_body">
		<div class="wt_gc_email_preview" data-loaded="0" data-loaded-type="0"></div> 
	</div>
</div>
<!-- Email preview popup -->

<form method="post" class="wt_gc_gift_card_mail_form">
	<?php
	// Set nonce:
	if ( function_exists( 'wp_nonce_field' ) ) {
		wp_nonce_field( WBTE_GC_FREE_PLUGIN_NAME );
	}
	?>
	   
	<table class="wt-gc-form-table">
		<?php
		Wbte_Woocommerce_Gift_Cards_Free_Admin::generate_form_field(
			array(
				array(
					'label'       => __( 'Credit amount', 'wt-gift-cards-woocommerce' ),
					'option_name' => 'wt_gc_send_email_amount',
					'css_class'   => 'wt_gc_send_email_field wt_gc_number_field',
					'mandatory'   => true,
					'type'        => 'number',
					'attr'        => 'step=".01" min=".01"',
				),
				array(
					'label'       => __( 'Recipient email(s)', 'wt-gift-cards-woocommerce' ),
					'option_name' => 'wt_gc_send_email_address',
					'mandatory'   => true,
					'help_text'   => __( 'Multiple emails can be inputted by separating with commas.', 'wt-gift-cards-woocommerce' ),
				),
				array(
					'label'       => __( 'Sender name', 'wt-gift-cards-woocommerce' ),
					'option_name' => 'wt_gc_send_email_from_name',
					'css_class'   => 'wt_gc_send_email_field',
				),
				array(
					'label'       => __( 'Gift card message', 'wt-gift-cards-woocommerce' ),
					'option_name' => 'wt_gc_send_email_description',
					'css_class'   => 'wt_gc_send_email_field',
					'type'        => 'textarea',
				),
				array(
					'label'       => __( 'Configure gift card email', 'wt-gift-cards-woocommerce' ),
					'non_field'   => true,
					'option_name' => 'configure_gift_card_email_link',
					'type'        => 'plaintext',
					'text'        => '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=wbte_woocommerce_gift_cards_free_email' ) ) . '" class="button button-secondary" target="_blank">' . __( 'Configure email', 'wt-gift-cards-woocommerce' ) . '</a>',
				),
			),
			$this->module_id
		);

		?>
	</table>
	<input type="hidden" name="wt_gc_send_email_template" value="general">
	<?php
	Wbte_Woocommerce_Gift_Cards_Free_Admin::add_settings_footer( __( 'Send email', 'wt-gift-cards-woocommerce' ), '', '<a class="button button-secondary wt_gc_email_preview_btn" data-wt_gc_popup="wt_gc_email_preview_popup">' . __( 'Preview email', 'wt-gift-cards-woocommerce' ) . '</a>' );
	?>
</form>