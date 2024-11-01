<?php
/**
 *  @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

?>
<style>
/* hide default sidebar */
.wt-gc-tab-container, .wt-gc-tab-head{ width:100%; }
.wt-gc-tab-right-container{ display:none; }
</style>
<div class="wt-gc-tab-content" id="wt_gc_gift_card_product_edit_tab_content"></div>

<script type="text/javascript">
	document.getElementById('wt_gc_gift_card_product_edit_tab_content').innerHTML = '<iframe src="<?php echo esc_attr( $product_edit_page_url ); ?>" style="width:100%; height:600px;"></iframe>';  
</script>