<?php
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('WT_GiftCards_Uninstall_Feedback')) :

    /**
     * Class for catch Feedback on uninstall
     */
    class WT_GiftCards_Uninstall_Feedback {

        public function __construct() {
            add_action('admin_footer', array($this, 'deactivate_scripts'));
            add_action('wp_ajax_wbtegiftcards_submit_uninstall_reason', array($this, 'send_uninstall_reason'));
        }

        private function get_uninstall_reasons() {

            $reasons = array(
                array(
                    'id' => 'used-it',
                    'text' => __('Used it successfully. Don\'t need anymore.', 'wt-gift-cards-woocommerce'),
                    'type' => 'reviewhtml',
                    'placeholder' => __('Have used it successfully and aint in need of it anymore', 'wt-gift-cards-woocommerce')
                ),
                array(
                    'id' => 'could-not-understand',
                    'text' => __('I couldn\'t understand how to make it work', 'wt-gift-cards-woocommerce'),
                    'type' => 'textarea',
                    'placeholder' => __('Would you like us to assist you?', 'wt-gift-cards-woocommerce')
                ),
                array(
                    'id' => 'found-better-plugin',
                    'text' => __('I found a better plugin', 'wt-gift-cards-woocommerce'),
                    'type' => 'text',
                    'placeholder' => __('Which plugin?', 'wt-gift-cards-woocommerce')
                ),
                array(
                    'id' => 'not-have-that-feature',
                    'text' => __('The plugin is great, but I need specific feature that you don\'t support', 'wt-gift-cards-woocommerce'),
                    'type' => 'textarea',
                    'placeholder' => __('Could you tell us more about that feature?', 'wt-gift-cards-woocommerce')
                ),
                array(
                    'id' => 'is-not-working',
                    'text' => __('The plugin is not working', 'wt-gift-cards-woocommerce'),
                    'type' => 'supportlink',
                    'placeholder' => __('Could you tell us a bit more whats not working?', 'wt-gift-cards-woocommerce')
                ),
                array(
                    'id' => 'looking-for-other',
                    'text' => __('It\'s not what I was looking for', 'wt-gift-cards-woocommerce'),
                    'type' => 'textarea',
                    'placeholder' => 'Could you tell us a bit more?'
                ),
                array(
                    'id' => 'did-not-work-as-expected',
                    'text' => __('The plugin didn\'t work as expected', 'wt-gift-cards-woocommerce'),
                    'type' => 'textarea',
                    'placeholder' => __('What did you expect?', 'wt-gift-cards-woocommerce')
                ),
                array(
                    'id' => 'other',
                    'text' => __('Other', 'wt-gift-cards-woocommerce'),
                    'type' => 'textarea',
                    'placeholder' => __('Could you tell us a bit more?', 'wt-gift-cards-woocommerce')
                ),
            );

            return $reasons;
        }

        public function deactivate_scripts() {

            global $pagenow;
            if ('plugins.php' !== $pagenow) {
                return;
            }
            $reasons = $this->get_uninstall_reasons();
            ?>
            <div class="wbtegiftcards-modal" id="wbtegiftcards-wbtegiftcards-modal">
                <div class="wbtegiftcards-modal-wrap">
                    <div class="wbtegiftcards-modal-header">
                        <h3><?php esc_html_e('If you have a moment, please let us know why you are deactivating:', 'wt-gift-cards-woocommerce'); ?></h3>
                    </div>
                    <div class="wbtegiftcards-modal-body">
                        <ul class="reasons">
                            <?php foreach ($reasons as $reason) { ?>
                                <li data-type="<?php echo esc_attr($reason['type']); ?>" data-placeholder="<?php echo esc_attr($reason['placeholder']); ?>">
                                    <label><input type="radio" name="selected-reason" value="<?php esc_html_e( $reason['id'] ); ?>"> <?php esc_html_e( $reason['text'] ); ?></label>
                                </li>
                            <?php } ?>
                        </ul>
                        <div class="wt_sc_policy_infobox">
                            <?php esc_html_e("We do not collect any personal data when you submit this form. It's your feedback that we value.", 'wt-gift-cards-woocommerce'); ?>
                            <a href="https://www.webtoffee.com/privacy-policy/" target="_blank"><?php esc_html_e('Privacy Policy', 'wt-gift-cards-woocommerce'); ?></a>        
                        </div>
                    </div>
                    <div class="wbtegiftcards-modal-footer">
                        <a href="#" class="dont-bother-me"><?php esc_html_e('I rather wouldn\'t say', 'wt-gift-cards-woocommerce'); ?></a>                      
                        <a href="https://wordpress.org/support/plugin/wt-gift-cards-woocommerce/#new-topic-0" target="_blank" class="button-primary wbtegiftcards-model-submit"><span class="dashicons dashicons-external" style="margin-top:3px;"></span> <?php _e('Contact Support', 'wt-gift-cards-woocommerce'); ?></a>
                        <button class="button-primary wbtegiftcards-model-submit"><?php esc_html_e('Submit & Deactivate', 'wt-gift-cards-woocommerce'); ?></button>
                        <button class="button-secondary wbtegiftcards-model-cancel"><?php esc_html_e('Cancel', 'wt-gift-cards-woocommerce'); ?></button>
                    </div>
                </div>
            </div>

            <style type="text/css">
                .wbtegiftcards-modal {
                    position: fixed;
                    z-index: 99999;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0;
                    background: rgba(0,0,0,0.5);
                    display: none;
                }
                .wbtegiftcards-modal.modal-active {
                    display: block;
                }
                .wbtegiftcards-modal-wrap {
                    width: 50%;
                    position: relative;
                    margin: 10% auto;
                    background: #fff;
                }
                .wbtegiftcards-modal-header {
                    border-bottom: 1px solid #eee;
                    padding: 8px 20px;
                }
                .wbtegiftcards-modal-header h3 {
                    line-height: 150%;
                    margin: 0;
                }
                .wbtegiftcards-modal-body {
                    padding: 5px 20px 20px 20px;
                }
                .wbtegiftcards-modal-body .input-text,.wbtegiftcards-modal-body textarea {
                    width:75%;
                }
                .wbtegiftcards-modal-body .reason-input {
                    margin-top: 5px;
                    margin-left: 20px;
                }
                .wbtegiftcards-modal-footer {
                    border-top: 1px solid #eee;
                    padding: 12px 20px;
                    text-align: right;
                }
                .reviewlink, .support_link{
                    padding:10px 0px 0px 35px !important;
                    font-size: 15px;
                }
                .review-and-deactivate, .reach-via-support{
                    padding:5px;
                }
                .wt_sc_policy_infobox{
                    font-style:italic;
                    text-align:left;
                    font-size:12px;
                    color:#aaa;
                    line-height:14px;
                    margin-top:35px;
                }
                .wt_sc_policy_infobox a{
                    font-size:11px;
                    color:#4b9cc3;
                    text-decoration-color: #99c3d7;
                }
            </style>
            <script type="text/javascript">
                (function ($) {
                    $(function () {
                        var modal = $('#wbtegiftcards-wbtegiftcards-modal');
                        var deactivateLink = '';
                        $('#the-list').on('click', 'a.wbtegiftcards-deactivate-link', function (e) {
                            e.preventDefault();
                            modal.addClass('modal-active');
                            deactivateLink = $(this).attr('href');
                            modal.find('input[type="radio"]:checked').prop('checked', false);
                            modal.find('a.dont-bother-me').attr('href', deactivateLink).css('float', 'left');
                        });

                        $('#wbtegiftcards-wbtegiftcards-modal').on('click', 'a.review-and-deactivate', function (e) {
                            e.preventDefault();
                            window.open("https://wordpress.org/support/plugin/wt-gift-cards-woocommerce/reviews/#new-post");
                            window.location.href = deactivateLink;
                        });

                        modal.on('click', 'button.wbtegiftcards-model-cancel', function (e) {
                            e.preventDefault();
                            modal.removeClass('modal-active');
                        });
                        modal.on('click', 'input[type="radio"]', function () {
                            var parent = $(this).parents('li:first');
                            modal.find('.reason-block').remove();
                            var inputType = parent.data('type'),
                                    inputPlaceholder = parent.data('placeholder');

                            if ('reviewhtml' === inputType) {
                                var reasonInputHtml = '<div class="reviewlink reason-block"><a href="#" target="_blank" class="review-and-deactivate"><?php _e('Deactivate and leave a review', 'wt-gift-cards-woocommerce'); ?> <span class="wt-wbtegiftcards-rating-link"> &#9733;&#9733;&#9733;&#9733;&#9733; </span></a></div>';
                            } else if ('supportlink' === inputType) {
                                var reasonInputHtml = '<div class="support_link reason-block"> <a href="https://www.webtoffee.com/contact" target="_blank" class="reach-via-support"><?php _e('Let our support team help you', 'wt-gift-cards-woocommerce'); ?> </a>' + '</div>';
                            } else {
                                var reasonInputHtml = '<div class="reason-input reason-block">' + (('text' === inputType) ? '<input type="text" class="input-text" size="40" />' : '<textarea rows="5" cols="45"></textarea>') + '</div>';
                            }
                            if (inputType !== '') {
                                parent.append($(reasonInputHtml));
                                parent.find('input, textarea').attr('placeholder', inputPlaceholder).focus();
                            }
                        });

                        modal.on('click', 'button.wbtegiftcards-model-submit', function (e) {
                            e.preventDefault();
                            var button = $(this);
                            if (button.hasClass('disabled')) {
                                return;
                            }
                            var $radio = $('input[type="radio"]:checked', modal);
                            var $selected_reason = $radio.parents('li:first'),
                                    $input = $selected_reason.find('textarea, input[type="text"]');
                            $reason_info = (0 !== $input.length) ? $input.val().trim() : '';
                            $reason_id = (0 === $radio.length) ? 'none' : $radio.val()


                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'wbtegiftcards_submit_uninstall_reason',
                                    reason_id: $reason_id,
                                    reason_info: $reason_info
                                },
                                beforeSend: function () {
                                    button.addClass('disabled');
                                    button.text('Processing...');
                                },
                                complete: function () {
                                    window.location.href = deactivateLink;
                                }
                            });
                        });
                    });
                }(jQuery));
            </script>
            <?php
        }

        public function send_uninstall_reason() {

            global $wpdb;

            if (!isset($_POST['reason_id'])) {
                wp_send_json_error();
            }

            $data = array(
                'reason_id' => sanitize_text_field($_POST['reason_id']),
                'plugin' => "wbtegiftcards",
                'auth' => 'wbtegiftcards_uninstall_1234#',
                'date' => gmdate("M d, Y h:i:s A"),
                'url' => '',
                'user_email' => '',
                'reason_info' => isset($_REQUEST['reason_info']) ? trim(stripslashes($_REQUEST['reason_info'])) : '',
                'software' => $_SERVER['SERVER_SOFTWARE'],
                'php_version' => phpversion(),
                'mysql_version' => $wpdb->db_version(),
                'wp_version' => get_bloginfo('version'),
                'wc_version' => (!defined('WC_VERSION')) ? '' : WC_VERSION,
                'locale' => get_locale(),
                'languages' => implode(",", get_available_languages()),
                'theme' => wp_get_theme()->get('Name'),
                'multisite' => is_multisite() ? 'Yes' : 'No',
                'wbtegiftcards_version' => WBTE_GC_FREE_VERSION
            );
            // Write an action/hook here in webtoffe to recieve the data
            $resp = wp_remote_post('https://feedback.webtoffee.com/wp-json/wbtegiftcards/v1/uninstall', array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => false,
                'body' => $data,
                'cookies' => array()
                    )
            );

            wp_send_json_success();
        }

    }

    new WT_GiftCards_Uninstall_Feedback();

endif;
