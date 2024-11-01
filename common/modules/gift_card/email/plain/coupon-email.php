<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$coupon_id  = maybe_unserialize( $email_args['coupon_id'] );
$coupon_id  = absint( is_array( $coupon_id ) ? $coupon_id[0] : $coupon_id );
$coupon_obj = new WC_Coupon( $coupon_id );

$coupon_amount = 0;
$expiry_date   = '';

if ( $coupon_obj ) {
	$coupon_title  = wc_sanitize_coupon_code( $coupon_obj->get_code() );
	$coupon_amount = apply_filters( 'wt_gc_alter_giftcard_email_price', $coupon_obj->get_amount(), $coupon_obj );

	$expiry      = Wbte_Woocommerce_Gift_Cards_Free_Common::get_store_credit_expiry( $coupon_obj );
	$expiry_date = ! is_null( $expiry ) ? $expiry->date( 'd M Y' ) : '';
}

$template = isset( $email_args['template'] ) ? $email_args['template'] : 'general';

$coupon_title   = ( ! $coupon_title ? 'XXXX-XXXX-XXXX-XXXX' : $coupon_title ); // for email preview purpose
$coupon_message = ( isset( $email_args['message'] ) ? $email_args['message'] : Wbte_Gc_Gift_Card_Free_Common::get_gift_card_message( $template ) );
$from           = isset( $email_args['from_name'] ) ? $email_args['from_name'] : '';
$reciever_name  = isset( $email_args['reciever_name'] ) ? $email_args['reciever_name'] : '';
$by_admin       = isset( $email_args['by_admin'] ) ? $email_args['by_admin'] : false;

echo esc_html( $email_heading ) . "\n\n";


/* Greetings */
if ( '' !== $reciever_name ) {
	/* translators: %s reciever name */
	echo esc_html( sprintf( __( 'Hi %s,', 'wt-gift-cards-woocommerce' ), $reciever_name ) );
} else {
	esc_html_e( 'Hi there,', 'wt-gift-cards-woocommerce' );
}

echo "\n\n"; // phpcs:ignore

/* from */
if ( '' !== $from ) {
	/* translators: from name */
	echo esc_html( sprintf( __( 'Congratulations! You have received a store credit from %s.', 'wt-gift-cards-woocommerce' ), $from ) );
} else {
	echo esc_html__( 'Congratulations! You have received a store credit.', 'wt-gift-cards-woocommerce' );
}

echo "\n\n"; // phpcs:ignore


echo esc_html($coupon_message). "\n\n"; // phpcs:ignore


echo esc_html__( 'Coupon code:', 'wt-gift-cards-woocommerce' ) . esc_html( strtoupper( $coupon_title ) ) . "\n\n";


echo esc_html__( 'Amount:', 'wt-gift-cards-woocommerce' ) . esc_html( $coupon_amount ) . "\n\n";

/* translators: %s Expiry date */
echo esc_html( '' !== $expiry_date ? sprintf( __( 'Expiry date: %s', 'wt-gift-cards-woocommerce' ), $expiry_date ) . "\n\n" : '' );


$custom_addition_content = __( 'To redeem this store credit, you can enter the coupon code in the dedicated field during the checkout.', 'wt-gift-cards-woocommerce' );
echo esc_html( apply_filters( 'wt_gc_alter_gift_card_email_custom_addition_content', $custom_addition_content, $coupon_obj ) );



echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
