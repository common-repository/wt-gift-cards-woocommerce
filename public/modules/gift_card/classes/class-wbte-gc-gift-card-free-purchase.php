<?php
/**
 * Gift Card purchase.
 *
 * @link
 * @since 1.0.0
 *
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Wbte_Gc_Gift_Card_Free_Public' ) ) {
	return;
}

class Wbte_Gc_Gift_Card_Free_Purchase extends Wbte_Gc_Gift_Card_Free_Public {

	private static $instance = null;
	public function __construct() {
		parent::init_vars();

		/**
		 *  Setup the product
		 */
		include_once $this->module_path . 'classes/class-wbte-gc-gift-card-free-purchase-setup-product.php';
		Wbte_Gc_Gift_Card_Free_Purchase_Setup_Product::get_instance();

		/**
		 *  Setup the product page
		 */
		include_once $this->module_path . 'classes/class-wbte-gc-gift-card-free-purchase-setup-product-page.php';
		Wbte_Gc_Gift_Card_Free_Purchase_Setup_Product_Page::get_instance();

		/**
		 *  Process the cart
		 */
		include_once $this->module_path . 'classes/class-wbte-gc-gift-card-free-purchase-process-cart.php';
		Wbte_Gc_Gift_Card_Free_Purchase_Process_Cart::get_instance();

		/**
		 *  Disable some payment methods on gift card purchase
		 */
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_some_payment_methods_on_gift_card_purchase' ) );
	}

	/**
	 * Get Instance
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Gc_Gift_Card_Free_Purchase();
		}
		return self::$instance;
	}

	/**
	 * Sanitize price field
	 *
	 * @since 1.0.0
	 * @access public
	 * @return float
	 */
	public function sanitize_price( $price ) {
		return filter_var( sanitize_text_field( $price ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
	}

	/**
	 * Process denomination list
	 *
	 * @since 1.0.0
	 * @access protected
	 * @param  string $denominations
	 * @return array  $denominations
	 */
	protected function process_denomination_list( $denominations ) {
		$denominations = array_map( 'floatval', explode( ',', $denominations ) );
		return apply_filters( 'wt_gc_alter_giftcard_predifined_amounts', array_unique( array_filter( $denominations ) ) );
	}


	/**
	 *  Disable some payment gateways on Gift card purchase
	 *  Hooked into: `woocommerce_available_payment_gateways`
	 *
	 *  @since  1.0.0
	 *  @param  array $_available_gateways    Available payment gateways
	 *  @return array    $_available_gateways    Available payment gateways
	 */
	public function disable_some_payment_methods_on_gift_card_purchase( $_available_gateways ) {

		$cart = ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;

		if ( is_object( $cart ) && is_callable( array( $cart, 'get_cart' ) ) ) {

			/**
			 *  Alter payment gateways list to remove on gift card purchase
			 *
			 *  @since  1.0.0
			 *  @param  string[]   Payment gateways name
			 */
			$payment_gateway_to_disable = (array) apply_filters( 'wt_gc_payment_gateways_to_disable_on_giftcard_purchase', array( 'cod' ) );

			if ( ! empty( $payment_gateway_to_disable ) ) {

				$need_to_disable_payment_method = false;

				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

					// Check the cart item is a gift card product.
					if ( isset( $cart_item['wt_store_credit_template'] ) ) {
						$need_to_disable_payment_method = true;
						break;
					}
				}

				// Need to disable the payment method
				if ( $need_to_disable_payment_method ) {

					// Loop through the payment methods
					foreach ( $payment_gateway_to_disable as $payment_gateway ) {

						if ( isset( $_available_gateways[ $payment_gateway ] ) ) {
							unset( $_available_gateways[ $payment_gateway ] ); // Remove the payment method
						}
					}
				}
			}
		}

		return $_available_gateways;
	}
}
