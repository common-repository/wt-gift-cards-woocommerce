<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 *  @since 1.1.0
 */

if(current_user_can('install_plugins') && current_user_can('update_plugins'))
{
    $placeholder_arr = array('<a>', '</a>', '<a href="'.esc_url($mpdf_wp_url).'" target="_blank">', '</a>');

    if(!$is_mpdf_active && !$is_mpdf_exists)
    {
        $enable_mpdf_msg = __('Requires mPDF library to add PDF support. %s Click here %s to install the %s mPDF add-on by WebToffee %s(free).', 'wt-woocommerce-gift-cards');
        $placeholder_arr[0] = '<a href="' . esc_url(wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $mpdf_slug), 'install-plugin_' . $mpdf_slug)) . '">';

    }elseif($is_mpdf_active && !$is_required_mpdf_version_installed)
    {
        $enable_mpdf_msg = __('Requires mPDF version %s or greater to add PDF support. %s Click here %s to update the %s mPDF add-on by WebToffee %s(free).', 'wt-woocommerce-gift-cards');
        $placeholder_arr[0] = '<a href="' . esc_url(wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . $mpdf_slug), 'upgrade-plugin_' . $mpdf_slug)) . '">';
        array_unshift($placeholder_arr , $mpdf_required_version);

    }elseif(!$is_mpdf_active && $is_mpdf_exists)
    {
        $enable_mpdf_msg = __('Requires mPDF library to add PDF support. %s Click here %s to activate the %s mPDF add-on by WebToffee %s(free).', 'wt-woocommerce-gift-cards');
        $placeholder_arr[0] = '<a href="' . esc_url(wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . urlencode($mpdf_path) . '&plugin_status=all&paged=1&s'), 'activate-plugin_' . $mpdf_path)) . '">';
    }else
    {
        $enable_mpdf_msg = '';
        $placeholder_arr = array();
    }
}else
{
    $enable_mpdf_msg = __('Requires mPDF library to add PDF support. Please install the %s mPDF add-on by WebToffee %s(free).', 'wt-woocommerce-gift-cards');
    $placeholder_arr = array('<a href="'.esc_url($mpdf_wp_url).'" target="_blank">', '</a>');
}

$attr = (!$is_mpdf_active || !$is_required_mpdf_version_installed ? 'disabled = "disabled"' : '');

?>
<table class="wt-gc-form-table wt-gc-product-page-tab-form-table">
	<?php
		Wbte_Woocommerce_Gift_Cards_Free_Admin::generate_form_field(
			array(
				array(
					'type'          => 'field_group_head', //field type
					'head'          => __('Configure gift card email', 'wt-woocommerce-gift-cards'),
					'group_id'      => 'product_page_titles', //field group id
					'show_on_default' => 1,
				),
				array(
					'label'       => __( 'Configure gift card email', 'wt-gift-cards-woocommerce' ),
					'non_field'   => true,
					'option_name' => 'configure_gift_card_email_link',
					'type'        => 'plaintext',
					'text'        => '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=wbte_woocommerce_gift_cards_free_email' ) ) . '" class="button button-secondary" target="_blank">' . __( 'Configure email', 'wt-gift-cards-woocommerce' ) . '</a>',
					'help_text' => __('Redirects to WooCommerce > Settings > Emails. Can configure the gift cards related email from there', 'wt-woocommerce-gift-cards'),

				),
				array(
					'label'         =>  __("Attach Gift Card as PDF", 'wt-woocommerce-gift-cards'),
					'option_name'   =>  "attach_as_pdf",
					'type'          =>  "checkbox",
					'checkbox_label'  =>  __("Enable", 'wt-woocommerce-gift-cards'),
					'field_vl'      =>  'yes',
					'attr'          =>  $attr,
					'after_form_field' => ($enable_mpdf_msg ? '<div class="wt_gc_msgs wt_gc_msg_wrn" style="margin-top:5px;">'.vsprintf($enable_mpdf_msg, $placeholder_arr).'</div>' : ''),
				),
			)
		, $this->module_id);
	?>
</table>