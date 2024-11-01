<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;}
$arr = array(
	'enable_gift_card_product'                        => __( 'Enable to add gift card products and purchase functionality to the store. When disabled hides all gift cards and disables purchasing option', 'wt-gift-cards-woocommerce' ),
	'order_status_to_generate'                        => __( 'Set to generate Gift cards when the order reaches the selected order status', 'wt-gift-cards-woocommerce' ),
	'wt_gc_choose_gift_card_template'                 => __( 'Image to be used in the gift card template', 'wt-gift-cards-woocommerce' ),
	'wt_gc_choose_gift_card_template_category_preset' => __( 'Adds template to the specified gift card category', 'wt-gift-cards-woocommerce' ),

	/**
	 *  non option tooltips
	 */
	'templates_manage'                                => __( 'Selected templates will be available on gift card product page', 'wt-gift-cards-woocommerce' ),

	// product data meta box
	'_wt_gc_purchase_options'                         => __( 'Allows to choose between  pre-defined gift card value or set custom gift card values', 'wt-gift-cards-woocommerce' ),
	'_wt_gc_amounts'                                  => __( 'Predefined gift card values to display on the product page. Separate multiple amounts with a comma', 'wt-gift-cards-woocommerce' ),
	'_wt_gc_enable_template'                          => __( 'Enable to allow users to choose template from the front end', 'wt-gift-cards-woocommerce' ),



	// admin send gift card
	'wt_gc_send_email_amount'                         => __( 'Value of the gift card', 'wt-gift-cards-woocommerce' ),
	'wt_gc_send_email_description'                    => __( 'The text will be displayed in the gift card email', 'wt-gift-cards-woocommerce' ),
	'wt_gc_send_email_address'                        => __( 'Recipient list to send the gift card', 'wt-gift-cards-woocommerce' ),
	'configure_gift_card_email_link'                  => __( 'Redirects to WooCommerce Email settings > Store credit email section. Can configure the store credit email from there', 'wt-gift-cards-woocommerce' ),
	
	/**
	 * 	Product page shortcode
	 * 	@since 1.1.0
	 */
	'product_page_shortcode' => __('Copy and paste the shortcode into any page within your store to showcase the gift card product on that specific page.', 'wt-gift-cards-woocommerce'),
	
	'product_page_title_text'	=> __( 'The text will serve as the title of the gift card product page', 'wt-woocommerce-gift-cards' ),
	'product_page_templates_title_text'	=> __( 'The text will serve as the title for the template selection section on the gift card product page', 'wt-woocommerce-gift-cards' ),

);
