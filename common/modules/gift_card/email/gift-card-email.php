<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_header', $email_heading, $email );


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
$order          = isset($email_args['order_id']) ? wc_get_order($email_args['order_id']) : false ;
$order_currency = $order ? $order->get_currency() : get_woocommerce_currency();
?>
<div class="wt_gc_email_wrapper">
	<div class="wt_gc_email_wrapper_inner">
		<div class="wt_gc_email_top">
			
			<div class="wt_gc_reciever_name_block">
				<?php

				/* Greetings */
				if ( '' !== $reciever_name ) {
					/* translators: %s reciever name wrapped by HTML span. */
					echo wp_kses_post( sprintf( __( 'Hi %s,', 'wt-gift-cards-woocommerce' ), '<span class="wt_gc_reciever_name">' . $reciever_name . '</span>' ) );
				} else {
					/* translators: 1.HTML `span` tag open, 2. HTML `span` tag closing */
					echo wp_kses_post( sprintf( __( 'Hi %1$sthere%2$s,', 'wt-gift-cards-woocommerce' ), '<span class="wt_gc_reciever_name">', '</span>' ) );
				}

				?>
			</div>

			<div class="wt_gc_from_name_block">
				<?php

				/* from */
				if ( '' !== $from ) {
					/* translators: 1.HTML tag open, 2. HTML tag closing */
					echo wp_kses_post( sprintf( __( 'Congratulations! You have received a gift card %1$sfrom %2$s.', 'wt-gift-cards-woocommerce' ), '<span class="wt_gc_from_name_box"><span class="wt_gc_from_name_prefix">', '</span><span class="wt_gc_from_name"> ' . $from . '</span></span>' ) );
				} else {
					/* translators: HTML code */
					echo wp_kses_post( sprintf( __( 'Congratulations! You have received a gift card %s.', 'wt-gift-cards-woocommerce' ), '<span class="wt_gc_from_name_box"><span class="wt_gc_from_name_prefix"></span><span class="wt_gc_from_name"></span></span>' ) );
				}

				?>
			</div>

			<div class="wt_gc_email_message" style="<?php echo esc_attr( '' === $coupon_message ? 'display: none;' : '' ); ?>">
				<?php echo wp_kses_post( $coupon_message ); ?>
			</div>
		</div>
		<div class="wt_gc_email_img">
			<?php
			if ( isset( $email_args['extended'] ) && true === $email_args['extended'] ) {
				$template      = isset( $email_args['template'] ) ? $email_args['template'] : '';
				$template_data = Wbte_Gc_Gift_Card_Free_Common::get_gift_card_template( $template );
				echo wp_kses_post( '<img src="' . esc_attr( $template_data['image_url'] ) . '" />' );
			}
			?>
		</div>
		<?php do_action( 'wt_gc_gift_card_email_content', $coupon_obj ); ?>
		<div class="wt_gc_email_bottom">
			<table style="width:100%; border-spacing:0px; border-collapse:collapse;" cellpadding="0" cellspacing="0">
				<tr>
					<td align="left" valign="bottom" class="wt_gc_email_coupon_code_block">
						<b><?php esc_html_e( 'Gift card code:', 'wt-gift-cards-woocommerce' ); ?></b>
						<div class="wt_gc_email_coupon_code"><?php echo esc_html( strtoupper( $coupon_title ) ); ?></div>
						<?php do_action( 'wt_gc_gift_card_email_after_coupon_code', $coupon_obj ); ?>
					</td>
					<td align="right" valign="bottom" class="wt_gc_email_price_expiry_block">
						<b><?php esc_html_e( 'Amount:', 'wt-gift-cards-woocommerce' ); ?></b>
						<div class="wt_gc_email_coupon_price"> 
							<?php
                            $args = array( 'coupon'      => $coupon_obj,
                                           'currency'    => $order_currency,
                                           'order'       => $order,
                                           'product'     => null );
                            echo wp_kses_post(Wbte_Woocommerce_Gift_Cards_Free_Common::get_giftcard_price( $args ));
                            ?>
						</div>
						<div class="wt_gc_email_coupon_expiry">
							<?php
							/* translators: %s Expiry date */
							echo wp_kses_post( '' !== $expiry_date ? sprintf( __( 'Expiry date: %s', 'wt-gift-cards-woocommerce' ), $expiry_date ) : '' );
							?>
						</div>
					</td>
				</tr>
			</table>  
		</div>
	</div>

	<div class="wt_gc_email_wrapper_inner">
		<div class="wt_gift_coupon_additional_content">     
			<div class="wt_gift_coupon_custom_additional_content">
				<?php
					$custom_addition_content = __( 'To redeem this gift card, you can enter the gift card code in the dedicated field during the checkout.', 'wt-gift-cards-woocommerce' );
                    echo wp_kses_post( apply_filters( 'wt_gc_alter_gift_card_email_custom_addition_content', $custom_addition_content, $coupon_obj ) ); // phpcs:ignore
				?>
				  
			</div>

			<?php
			/**
			 * Show user-defined additional content - Alter this content on WC email's settings.
			 */
			if ( $additional_content ) {
				echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
			}
			?>
		</div>
	</div>
</div>


<?php
do_action( 'woocommerce_email_footer', $email );
?>