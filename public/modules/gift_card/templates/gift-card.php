<?php
/**
 * Gift card product page template
 * Version: 1.0.1
 *
 * @link
 * @since 1.0.0
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
?>
<div class="wt_gc_gift_card_product_page_wrapper <?php echo esc_attr( apply_filters( 'wt_gc_add_gift_card_product_page_css_class', '' ) ); ?>">
	<div class="wt_gc_gift_card_product_page_title">
		<?php 
            $gift_card_product_page_title = apply_filters( 'wt_gc_alter_gift_card_product_page_title', $gift_card_product_page_title, $product_id );
            if( '' === $gift_card_product_page_title ){
                $product = wc_get_product( $product_id );
                ?>
                    <h1><?php echo wp_kses_post( $product->get_name() ); ?></h1>
                <?php
            }else{
                ?>
                    <h1><?php echo wp_kses_post( $gift_card_product_page_title ); ?></h1>                   
                <?php
            }
        ?>
	</div>
	<div class="wt_gc_gift_card_product_page_bottom">
		<div class="wt_gc_gift_card_product_page_preview_wrapper">
			<div class="wt_gc_email_preview">              
				<?php echo wp_kses_post( $preview_html ); ?>       
			</div>
		</div>
		<div class="wt_gc_gift_card_product_page_form">
			<h2 style="<?php echo esc_attr( 1 === count( $templates ) ? 'display:none;' : '' ); ?>"><?php echo wp_kses_post( apply_filters( 'wt_gc_gift_card_product_page_templates_main_title', $templates_main_title, $product_id ) ); ?></h2>
			<div style="<?php echo esc_attr( 1 === count( $templates ) || empty( $templates ) ? 'display:none;' : '' ); ?>" class="wt_gc_gift_card_product_page_templates_container">
				
				<div class="wt_gc_carousal wt_gc_gift_card_product_page_templates">
					<div class="wt_gc_carousal_inner wt_gc_gift_card_product_page_templates_inner">
						<?php
						$i                  = 0;
						$first_template_key = 'general';

						foreach ( $templates as $template_key => $template ) {
							$class = 'wt_gc_carousal_item ';

							if ( 0 === $i ) {
								$class             .= 'active';
								$first_template_key = $template_key;
							}

							echo '<div class="' . esc_attr( $class ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '<img design="' . esc_attr( $template_key ) . '" src="' . esc_url( $template['image_url'] ) . '" alt="' . esc_attr( $template_key ) . '"/>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							++$i;
						}
						?>
					</div>
				</div>
			</div>

			<h2 class="wt_gc_gift_card_product_page_form_hd"><?php echo wp_kses_post( $how_to_send_title_text ); ?></h2>
			<?php 

            /**
             *  Added compatibility while printing the template via shortcode
             * 
             *  @since 1.1.0
             */
            if ( isset( $via_shortcode ) && true === $via_shortcode ) {
                
                global $product;
                $backup_product = $product; // Maybe null value
                $product = wc_get_product( $product_id ); // Create product variable from product ID

                // Change form action to avoid redirect.
                add_filter( 'woocommerce_add_to_cart_form_action', '__return_empty_string' );
            }
                 
            do_action('wt_gc_gift_card_setup_form'); 

            
            if ( isset( $via_shortcode ) && true === $via_shortcode ) {
                
                // Re-assign the existing product value.
                $product = $backup_product; 
                 
                remove_filter( 'woocommerce_add_to_cart_form_action', '__return_empty_string' );
            }
            ?>
		</div>
	</div>    
</div>