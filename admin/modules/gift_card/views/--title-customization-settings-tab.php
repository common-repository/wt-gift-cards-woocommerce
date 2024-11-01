<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 *  @since 1.1.0
 */
?>
<table class="wt-gc-form-table wt-gc-product-page-tab-form-table">
	<?php
		Wbte_Woocommerce_Gift_Cards_Free_Admin::generate_form_field(
			array(
				array(
					'type'          => 'field_group_head', 
					'head'          => __('Customize product page titles', 'wt-woocommerce-gift-cards'),
					'group_id'      => 'product_page_titles', 
					'show_on_default' => 1,
				),
				array(
					'label'         =>  __("Custom page title", 'wt-woocommerce-gift-cards'),
					'option_name'   =>  "product_page_title_text",
					'type'          => 'text',
					'field_group'   => 'product_page_titles',
					'help_text'     => __("By default, the page title will showcase the name of the gift card product.", 'wt-woocommerce-gift-cards'),
					'attr'          =>  'placeholder="'.esc_attr__( 'Gift card product name', 'wt-woocommerce-gift-cards' ).'"',
				),
				array(
					'label'         =>  __("Template selection", 'wt-woocommerce-gift-cards'),
					'option_name'   =>  "product_page_templates_title_text",
					'type'          => 'text',
					'field_group'   => 'product_page_titles',
				),
			),
			$this->module_id
		);
	?>
</table>