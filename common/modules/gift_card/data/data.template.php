<?php
/**
 * Gift card HTML for PDF.
 *
 * @link       
 * @since 1.1.0    
 *
 * @package  Wt_Woocommerce_Gift_Cards  
 */
$coupon_id = maybe_unserialize($args['coupon_id']);
$coupon_id = absint(is_array($coupon_id) ? $coupon_id[0] : $coupon_id);
$coupon_obj = new WC_Coupon($coupon_id);

$coupon_amount = 0;
$expiry_date = '';
$coupon_code = '';

if($coupon_obj)
{
    $coupon_code = wc_sanitize_coupon_code($coupon_obj->get_code());
    $coupon_amount = apply_filters('wt_gc_alter_giftcard_pdf_price', $coupon_obj->get_amount(), $coupon_obj);


    $expiry = Wbte_Woocommerce_Gift_Cards_Free_Common::get_store_credit_expiry($coupon_obj);
    $expiry_date = !is_null($expiry) ? $expiry->date('d M Y') : '';

}

$template = isset($args['template']) ?  $args['template'] : 'general';
$coupon_message = (isset($args['message']) ?  $args['message'] : Wbte_Gc_Gift_Card_Free_Common::get_gift_card_message($template));
$from = isset($args['from_name'])? $args['from_name'] : '';
$reciever_name = isset($args['reciever_name'])? $args['reciever_name'] : '';
$by_admin = isset($args['by_admin']) ? $args['by_admin'] : false;
$order = isset($args['order_id']) ? wc_get_order($args['order_id']) : false ;
$order_currency = $order ? $order->get_currency() : get_woocommerce_currency();

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php esc_html_e('Gift card', 'wt-woocommerce-gift-cards'); ?></title>
        <style>
        body, html{margin:0px; padding:0px; font-family:Verdana; }

        .wt_gc_pdf_wrapper{ margin:0 auto; max-width:600px; background:#ffffff; line-height:22px; font-size:14px; margin-top:20px; padding:20px; border:solid 1px #ccc; }  
        .wt_gc_pdf_message{width:100%; height:auto; border-left:solid 5px #DEDEDE; font-style:italic; margin-top:25px; padding:5px 15px; }
        .wt_gc_pdf_img{ width:100%; height:auto; margin-top:25px;}
        .wt_gc_pdf_img img{ width:100%;}
        .wt_gc_pdf_coupon_info{ width:100%; height:auto; text-align:left; margin-top:25px;}
        .wt_gc_pdf_coupon_code_block{ text-align:left; }
        .wt_gc_pdf_coupon_code{ font-size:18px; font-weight:400; margin-top:7px; }
        .wt_gc_pdf_price_expiry_block{ text-align:right;}
        .wt_gc_pdf_coupon_price{ font-size:28px; font-weight:700; margin-top:7px; }
        .wt_gc_pdf_coupon_expiry{ margin-top:7px;  }
        .wt_gc_pdf_sender_info, .wt_gc_pdf_bottom{ width:100%; height:auto; text-align:left; margin-top:25px; }
        .wt_gc_pdf_sender_info table td{ padding:5px; }
        <?php
        do_action('wt_gc_pdf_coupon_css');
        ?>
        </style>
    </head>
<body>
	<div class="wt_gc_pdf_wrapper">
        <div class="wt_gc_pdf_img">
            <?php 
            if(isset($args['extended']) && true === $args['extended'])
            {
                $template = isset($args['template']) ?  $args['template'] : 'general';
                $template_data = Wbte_Gc_Gift_Card_Free_Common::get_gift_card_template($template);
                echo '<img src="'.esc_url($template_data['image_url']).'"/>';  
            }
            ?>
        </div>
        <div class="wt_gc_pdf_coupon_info">
            <table style="width:100%; border-spacing:0px; border-collapse:collapse;" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="left" valign="bottom" class="wt_gc_pdf_coupon_code_block">
                        <b><?php esc_html_e('Gift card code:', 'wt-woocommerce-gift-cards');?></b>
                        <div class="wt_gc_pdf_coupon_code"><?php echo esc_html(strtoupper($coupon_code)); ?></div>
                    </td>
                    <td align="right" valign="bottom" class="wt_gc_pdf_price_expiry_block">                         
                        <b><?php esc_html_e('Amount:', 'wt-woocommerce-gift-cards');?></b>
                        <div class="wt_gc_pdf_coupon_price"> 
                            <?php
                                $args = array( 'coupon'      => $coupon_obj,
                                                'currency'    => $order_currency,
                                                'order'       => $order,
                                                'product'     => null );
                                echo wp_kses_post(Wbte_Woocommerce_Gift_Cards_Free_Common::get_giftcard_price( $args ));
                            ?>
                        </div>
                        <div class="wt_gc_pdf_coupon_expiry">
                            <?php echo ("" !== $expiry_date ? sprintf(__('Expiry date: %s', 'wt-woocommerce-gift-cards'), $expiry_date) : ''); ?>
                        </div>
                    </td>
                </tr>
            </table>  
        </div>
        <div class="wt_gc_pdf_sender_info">
            <table style="border-spacing:0px; border-collapse:collapse;" cellpadding="0" cellspacing="0">
                <?php 
                
                if("" !== trim($from))
                { ?>
                    <tr>
                        <td align="left" valign="bottom"><?php _e('From:', 'wt-woocommerce-gift-cards'); ?></td>
                        <td><?php echo esc_html($from);?></td>
                    </tr>
                <?php 
                }

                if("" !== trim($reciever_name))
                {
                ?>
                <tr>
                    <td align="left" valign="bottom"><?php _e('To:', 'wt-woocommerce-gift-cards'); ?></td>
                    <td><?php echo esc_html($reciever_name);?></td>
                </tr>
                <?php 
                }

                if("" !== trim($coupon_message))
                {
                ?>
                <tr>
                    <td align="left" valign="bottom"><?php _e('Message:', 'wt-woocommerce-gift-cards'); ?></td>
                    <td><?php echo esc_html($coupon_message);?></td>
                </tr>
                <?php 
                }
                ?>
            </table>
        </div>

        <div class="wt_gc_pdf_bottom">
            <div class="wt_gift_coupon_custom_additional_content">
                <?php 
                    $custom_addition_content = __('To redeem this gift card, you can enter the gift card code in the dedicated field during checkout.', 'wt-woocommerce-gift-cards');
                    echo apply_filters('wt_gc_alter_gift_card_pdf_custom_addition_content', $custom_addition_content, $coupon_obj);
                ?>  
            </div>
        </div>
    </div>
    </body>
</html>