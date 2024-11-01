<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$cat_slug = sanitize_title_with_dashes( $template['category'] );
?>
<div class="wt_gc_giftcard_template_box" data-category="<?php echo esc_attr( $cat_slug ); ?>">
		<label class="wt_gc_checkbox_container">
			<?php
			if ( 'product_page' === $section || 'manage' === $section ) {
				?>
				<input type="checkbox" name="wt_gc_visible_gift_template[]" class="wt_gc_visible_template_checkbox" value="<?php echo esc_attr( $template_k ); ?>" <?php checked( $is_hidden, false ); ?>>
				<?php
			} else {
				?>
				<input type="radio" name="wt_gc_visible_gift_template" class="wt_gc_visible_template_checkbox" value="<?php echo esc_attr( $template_k ); ?>">
				<?php
			}
			?>
			<span class="wt_gc_checkbox_checkmark"></span>
		</label> 
	<div class="wt_gc_img_overlay">
		<div class="wt_gc_img_overlay_content <?php echo esc_attr( $is_custom ? 'wt_gc_custom_template_overlay' : '' ); ?>">
			
			<?php
			if ( 'manage' === $section ) {
				?>
				<span class="dashicons dashicons-editor-expand wt_gc_img_preview_btn" title="<?php esc_attr_e( 'View larger', 'wt-woocommerce-gift-cards' ); ?>"></span>
				<?php

				if ( $is_custom ) {
					?>
					<span class="dashicons dashicons-trash wt_gc_img_delete_btn" title="<?php echo esc_attr( $delete_btn_tooltip ); ?>"></span>
					<?php
				}
			}
			?>
			<span class="wt_gc_template_category"><?php echo esc_html( $category ); ?></span>
		</div>
	</div>
	<img src="<?php echo esc_attr( $img_url ); ?>" class="wt_gc_template_img">
</div> 