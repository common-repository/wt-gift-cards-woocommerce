<?php
/**
 * Gift card purchase form HTML.
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
<div class="wt_gc_gift_card_product_page_form_wrapper">
 
	<input type="hidden" name="wt_gc_gift_card_action" value="email">
	<?php
	$denominations        = $this->process_denomination_list( $settings['_wt_gc_amounts']['value'] );
	$is_single_predefined = ( 1 === count( $denominations ) );
	?>
	<div class="wt_gc_gift_card_product_page_form_item" style="<?php echo esc_attr( $is_single_predefined && $is_templates_enabled ? 'display:none;' : '' ); ?>">
		<div class="radio-toolbar wt_gc_credit_denominations">
			<label class="wt_gc_credit_amount_label"><?php esc_html_e( 'Amount', 'wt-gift-cards-woocommerce' ); ?></label>
			<?php
			// Only single predefined and on first page load. So set the predefined amount as default value.
			$wt_credit_amount = ( 0 === $wt_credit_amount && 0 === $form_submit_triggered && $is_single_predefined ? $denominations[0] : $wt_credit_amount );

			if ( ! $is_single_predefined || ! $is_templates_enabled ) { // Only when multiple items are available or templates are disabled
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
				$credit_denominaton = ( isset( $_REQUEST['credit_denominaton'] ) ? floatval( wp_unslash( $_REQUEST['credit_denominaton'] ) ) : 0 );
				$i                  = 0;

				foreach ( $denominations as $denomination ) {
					$show_custom_label = true;
					?>
					<span class="wt_gc_credit_denomination">
						<input type="radio" id="denomination_<?php echo esc_attr( $i ); ?>" name="credit_denominaton" value="<?php echo esc_attr( $denomination ); ?>" <?php checked( $credit_denominaton, $denomination ); ?>> <label class="denominaton_label" for="denomination_<?php echo esc_attr( $i ); ?>"><?php echo wp_kses_post( wc_price( $denomination ) ); ?></label>
					</span>
					<?php
					++$i;
				}
			}
			?>
		</div>                      
	</div> 
	<?php



	/**
	 *  Customizable fields
	 */
	if ( ! in_array( 'reciever_email', $fields_enabled, true ) ) {
		$fields_enabled[] = 'reciever_email'; // mandatory field
	}

	foreach ( $all_fields as $field => $field_title ) {
		if ( ! in_array( $field, $fields_enabled, true ) ) {
			continue; // field not enabled
		}

		$required_attr = ( in_array( $field, $mandatory_fields, true ) ? ' required="required"' : '' );

		$field_name  = 'wt_gc_gift_card_' . $field;
		$field_title = ( isset( $all_fields[ $field ] ) ? $all_fields[ $field ] : '' );

		if ( 'message' === $field ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$field_vl = ( isset( $_REQUEST[ $field_name ] ) ? sanitize_textarea_field( wp_unslash( $_REQUEST[ $field_name ] ) ) : '' );
			?>
			<div class="wt_gc_gift_card_product_page_form_item">
				<label><?php echo esc_html( $field_title ); ?></label>
				<textarea name="<?php echo esc_attr( $field_name ); ?>" class="wt_gc_gift_card_field" id="<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo esc_attr( $field_title ); ?>" <?php echo esc_html( $required_attr ); ?>><?php echo esc_textarea( $field_vl ); ?></textarea>
			</div>
			<?php
		} else {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
			$field_vl = ( isset( $_REQUEST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $field_name ] ) ) : '' );

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( 'sender_email' === $field && ! isset( $_REQUEST[ $field_name ] ) ) {
				$field_vl = $user_email;
			}

			?>
			<div class="wt_gc_gift_card_product_page_form_item <?php echo esc_attr( $field_name . '_wt_gc_form_item' ); ?>">
				<label><?php echo esc_html( $field_title ); ?></label>
				<input type="<?php echo esc_attr( false !== stripos( $field, 'email' ) ? 'email' : 'text' ); ?>" name="<?php echo esc_attr( $field_name ); ?>" class="wt_gc_gift_card_field" id="<?php echo esc_attr( $field_name ); ?>" placeholder="<?php echo esc_attr( $field_title ); ?>" value="<?php echo esc_attr( $field_vl ); ?>" <?php echo esc_html( $required_attr ); ?>/>
			</div>
			<?php
		}
	}

	do_action( 'wt_gc_gift_card_after_gift_to_friend_form' );

	?>
	<input type="hidden" name="wt_gc_gift_card_send_today" value="1">
	<?php

	if ( self::is_templates_enabled( $product_id ) ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$wt_gc_gift_card_image = ( isset( $_REQUEST['wt_gc_gift_card_image'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wt_gc_gift_card_image'] ) ) : '' );
		?>
		<input type="hidden" name="wt_gc_gift_card_image"  id="wt_gc_gift_card_image"  value="<?php echo esc_attr( $wt_gc_gift_card_image ); ?>" />
		<?php
	}

	do_action( 'wt_gc_gift_card_after_send_gift_card_form' );

	?>
	<input type="hidden" name="wt_gift_card_form_submit_triggered" value="<?php echo esc_attr( $form_submit_triggered ); ?>" />
	<input type="hidden" name="wt_credit_amount" id="wt_credit_amount" value="<?php echo esc_attr( $wt_credit_amount ); ?>" />

	<div style="clear:both;"></div>
</div>