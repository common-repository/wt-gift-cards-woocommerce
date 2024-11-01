<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Wbte_Woocommerce_Gift_Cards_Free_Email extends WC_Email {
	public $data_arr = array();

	public function __construct() {
		$this->id             = 'wt_gc_gift_card'; // checking in inline CSS adding function, PDF attaching function.
		$this->title          = __( 'You have received a Gift card', 'wt-gift-cards-woocommerce' );
		$this->description    = __( 'This email will be sent to customers when they purchase the gift card or when the admin mails a gift card manually.', 'wt-gift-cards-woocommerce' ) . ' [WebToffee WooCommerce Gift Cards]';
		$this->customer_email = true;
		$this->heading        = __( 'You have received a Gift card', 'wt-gift-cards-woocommerce' );
		$this->subject        = sprintf( _x( "You've got a gift!", 'default email subject for active emails sent to the customer', 'wt-gift-cards-woocommerce' ) );
		$this->template_base  = dirname( plugin_dir_path( __FILE__ ) ) . '/';

		$this->wt_gc_set_template();

		// Triggers for this email
		add_action( 'wt_gc_send_gift_card_coupon_to_customer', array( $this, 'trigger' ), 10, 1 );

		// We want all the parent's methods, with none of its properties, so call its parent's constructor
		WC_Email::__construct();
	}

	/**
	 *  Get mail template based on gift card template enabled status
	 */
	public function wt_gc_set_template( $email_args = array() ) {
		$is_extended = ( isset( $email_args['extended'] ) ? $email_args['extended'] : true );

		if ( ! $is_extended ) {
			$this->template_html  = 'email/coupon-email.php';
			$this->template_plain = 'email/plain/coupon-email.php';

		} else // email with gift card template
		{
			$this->template_html  = 'email/gift-card-email.php';
			$this->template_plain = 'email/plain/coupon-email.php'; // for non HTML mail viewers
		}
	}

	public function trigger( $email_args ) {
		$this->recipient = $email_args['send_to'];
		$this->object    = (object) $email_args;
		$this->data_arr  = $email_args;

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->wt_gc_set_template( $email_args ); /* set the template type. */

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}


	public function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'email_args'         => $this->data_arr,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}


	public function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'email_args'         => $this->data_arr,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}
}
