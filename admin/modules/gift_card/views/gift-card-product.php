<?php
/**
 *  @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$product_status_tab_key        = isset( $_GET['product_status_tab_key'] ) ? sanitize_text_field( wp_unslash( $_GET['product_status_tab_key'] ) ) : 'all';
$gift_card_products            = self::get_gift_card_products();
$post_status_arr               = get_post_statuses();
$post_status_arr['trash']      = __( 'Trash', 'wt-gift-cards-woocommerce' );
$post_status_arr['auto-draft'] = __( 'Auto draft', 'wt-gift-cards-woocommerce' );
?>
<style type="text/css">
table.wp-list-table span.wc-image {
	display: block;
	text-indent: -9999px;
	position: relative;
	height: 1em;
	width: 1em;
	margin: 0 auto;
}
table.wp-list-table span.wc-image::before {
	font-family: Dashicons;
	speak: never;
	font-weight: 400;
	font-variant: normal;
	text-transform: none;
	line-height: 1;
	-webkit-font-smoothing: antialiased;
	margin: 0;
	text-indent: 0;
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	text-align: center;
	content: "\f128";
}
.wt_gc_gift_card_product_table_top, .wt_gc_gift_card_product_status_filter_links{ float:left; width:100%; padding:20px 0px;}
.wt_gc_gift_card_product_status_filter_links a { display:inline-block; text-decoration:none; }
.wt_gc_gift_card_product_status_filter_links .status_link_glue{ display:inline-block; padding:0px 6px; }
.wt_gc_gift_card_product_status_filter_links .status_link_active{ display:inline-block; font-weight:bold; }
.wt_gc_gift_card_product_table_top .hd{font-weight:bold; font-size:18px; display:inline-block; margin-top:5px; margin-right:15px; }
.wt_gc_gift_card_product_table .post-state{font-weight:bold;}
.wt_gc_copy_shortcode_btn{ display:inline-block; width:30px; height:30px; background:url('<?php echo esc_url($this->module_url . 'assets/images/copy_icon.svg'); ?>') no-repeat center; opacity:.8; cursor:pointer; }
.wt_gc_copy_shortcode_btn:hover{ opacity:1; box-shadow:0px 0px 1px #ccc; }
.wt_gc_copy_shortcode_btn span{ display:none; }
.wt_gc_copy_shortcode_copied{ font-weight:400; display:none; }
.wt_gc_copy_shortcode_copied .dashicons{ color:green; }
</style>

<div class="wt-gc-tab-content">

	<div class="wt_gc_gift_card_product_table_top">
		<span class="hd"><?php esc_html_e( 'Gift Card Products', 'wt-gift-cards-woocommerce' ); ?></span> 
		<a href="<?php echo esc_url( 'https://www.webtoffee.com/product/woocommerce-gift-cards/?utm_source=free_plugin_add_new_+button&utm_medium=Gift_card_basic&utm_campaign=WooCommerce_Gift_Cards&utm_content=' . WBTE_GC_FREE_VERSION ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Add new', 'wt-gift-cards-woocommerce' ); ?> <img src="<?php echo esc_url( WBTE_GC_FREE_URL . 'admin/images/pro_crown.svg' ); ?>" style="float:right; margin-left:3px; margin-top:6px;" /> </a>
	</div>

	<table class="wp-list-table widefat fixed striped table-view-list wt_gc_gift_card_product_table">
		<thead>
			<tr>
				<th style="width:40px;" class="wt_gc_text_left">#</th>
				<th style="width:150px; text-align:center;"><span class="wc-image">Image</span></th>
				<th><?php esc_html_e( 'Title', 'wt-gift-cards-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Price', 'wt-gift-cards-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wt-gift-cards-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Date', 'wt-gift-cards-woocommerce' ); ?></th>
				<th style="width:100px; text-align:center;"><?php esc_html_e('Shortcode', 'wt-gift-cards-woocommerce');?> <?php echo wp_kses_post( Wbte_Woocommerce_Gift_Cards_Free_Admin::set_tooltip( 'product_page_shortcode', $this->module_id ) );?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$j = 0;

			if ( ! empty( $gift_card_products ) ) {
				foreach ( $gift_card_products as $product_id ) {
					$product = wc_get_product( $product_id );

					if ( ! $product ) {
						continue; }
					if ( ! $this->is_gift_card_product( $product_id ) ) {
						continue; }


					$post_title = ( trim( $product->get_title() ) ? $product->get_title() : __( '(no title)', 'wt-gift-cards-woocommerce' ) );

					$image       = self::get_product_image( $product );
					$metas       = self::get_product_metas( $product_id );
					$post_status = $product->get_status();

					++$j;
					?>
					<tr>
						<td class="wt_gc_text_left"> <?php echo esc_html( $j ); ?> </td>
						<td class="wt_gc_text_center">
							<?php
							if ( $image && is_array( $image ) && isset( $image[0] ) ) {
								?>
								<img src="<?php echo esc_attr( $image[0] ); ?>" data-id="<?php echo esc_attr( $product_id ); ?>" width="50" height="50" />
								<?php
							}
							?>
						</td>
						<td>
							<?php
							$edit_url = '';

							if ( current_user_can( 'edit_post', $product_id ) && 'trash' !== $post_status ) {
								$edit_url = $current_url . '&wt_gc_product_edit_tab=' . $product_id;
								echo wp_kses_post( '<a href="' . esc_url( $edit_url ) . '">' );
							}

							echo wp_kses_post( '<b>' . esc_html( $post_title ) . '</b>' );

							if ( '' !== $edit_url ) {
								echo wp_kses_post( '</a>' );
							}

							?>
							<div class="row-actions wt_gc_product_action_box">                         
								<?php
								$actions = array(
									/* translators: %d: Product ID. */
									'id' => array( 'title' => sprintf( __( 'ID: %d', 'woocommerce' ), $product_id ) ),
								);

								if ( '' !== $edit_url ) {
									$actions['edit'] = array(
										'url'   => $edit_url,
										'title' => __( 'Edit', 'wt-gift-cards-woocommerce' ),
									);
								}

								if ( current_user_can( 'delete_post', $product_id ) ) {
									if ( 'trash' === $post_status ) {
										$actions['untrash'] = array(
											'url'   => wp_nonce_url(
												add_query_arg(
													array(
														'post' => $product_id,
														'action' => 'untrash',
													),
													admin_url( 'post.php' )
												),
												'untrash-post_' . $product_id
											),
											'title' => __( 'Restore', 'wt-gift-cards-woocommerce' ),
										);
									} else {
										$actions['trash'] = array(
											'url'   => get_delete_post_link( $product_id ),
											'title' => __( 'Trash', 'wt-gift-cards-woocommerce' ),
										);
									}
								}

								// Permalink
								if ( 'trash' !== $post_status ) {
									$actions['view'] = array(
										'url'   => get_permalink( $product_id ),
										'title' => __( 'View', 'wt-gift-cards-woocommerce' ),
									);
								}

								$actions = apply_filters( 'wt_gc_gift_product_actions', $actions, $product_id );

								$i = 0;

								foreach ( $actions as $action => $action_data ) {
									++$i;
									?>
									<span class="<?php echo esc_attr( $action ); ?>">
										<?php
										if ( isset( $action_data['url'] ) ) {
											?>
											<a href="<?php echo esc_url( $action_data['url'] ); ?>"><?php echo esc_html( $action_data['title'] ); ?></a>
											<?php
										} else {
											echo esc_html( $action_data['title'] );
										}
										?>
										<?php echo wp_kses_post( count( $actions ) > $i ? '|' : '' ); ?>
									</span>
									<?php
								}

								?>
														  
							</div>                          
						</td>
						<td>
							<?php
							if ( isset( $metas['_wt_gc_amounts']['value'] ) ) {
								echo wp_kses_post( $metas['_wt_gc_amounts']['value'] );
							}
							?>
						</td>
						<td>
							<b><?php echo esc_html( isset( $post_status_arr[ $post_status ] ) ? $post_status_arr[ $post_status ] : $post_status ); ?></b>
						</td>
						<td>
							<?php echo esc_html( ! is_null( $product->get_date_modified() ) ? gmdate( 'Y-m-d h:i:s A', $product->get_date_modified()->getOffsetTimestamp() ) : '' ); ?>
						</td>
						<?php 
                        /**
                         *  Product page shortcode
                         *  
                         *  @since 1.1.0
                         */
                        ?>
                        <td style="text-align:center;">
                            <?php 
                            if ( 'publish' === $product->get_status() 
                                && $product->is_visible()  
                                && self::is_templates_enabled( $product_id ) 
                            ) 
                            {
                            ?> 
                                <span class="wt_gc_copy_shortcode_btn">
                                    <span><?php echo esc_html( '[' . self::$product_page_shortcode_name . ' id="' . $product_id . '"]' ); ?></span>
                                </span>
                                <span class="wt_gc_copy_shortcode_copied"><?php esc_html_e( 'Copied', 'wt-gift-cards-woocommerce' ); ?> <span class="dashicons dashicons-yes-alt"></span></span>
                            <?php 
                            }
                            ?>
                        </td>
					</tr>
					<?php
					break;
				}
			} else {
				?>
				<tr>
					<td colspan="6" style="text-align:center;">
						<?php esc_html_e( 'No products to display.', 'wt-gift-cards-woocommerce' ); ?>
					</td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>

</div>