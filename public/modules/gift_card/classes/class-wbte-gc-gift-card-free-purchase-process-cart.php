<?php
/**
 * Store credit purchase as gift card or coupon.
 *
 * @link
 * @since 1.0.0
 *
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Wbte_Gc_Gift_Card_Free_Purchase' ) ) {
	return;
}

class Wbte_Gc_Gift_Card_Free_Purchase_Process_Cart extends Wbte_Gc_Gift_Card_Free_Purchase {

	private static $instance = null;

	public function __construct() {
		parent::init_vars();

		/**
		 * Validate the store credit amount on add to cart.
		 * Validate custom image, if enabled.
		 */
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_store_credit_on_add_to_cart' ), 10, 2 );

		/**
		 *  Add store credit details into cart item data.
		 */
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_store_credit_template_details_to_cart_item_data' ), 10, 3 );

		/**
		 * Set credit amount session on cart.
		 */
		add_action( 'woocommerce_add_to_cart', array( $this, 'save_credit_details_in_session' ), 10, 6 );

		/**
		 * Update cart item price for credit purchase.
		 */
		add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_item_price_for_credit_purchase' ), 10, 3 );

		add_filter( 'woocommerce_cart_item_thumbnail', array( $this, 'display_gift_card_image_in_cart_item' ), 10, 3 );

		add_filter( 'woocommerce_get_item_data', array( $this, 'display_credit_details_into_cart_item' ), 10, 2 );

		/**
		 * Create random coupon and update the credit details into order item meta
		 */
		add_action( 'woocommerce_new_order_item', array( $this, 'update_coupon_data_into_order' ), 1, 3 );

		/**
		 * Save created coupon details into order meta data.
		 */
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_credit_details_in_order' ) ); // Classic checkout
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'save_credit_details_in_order' ) ); // Checkout block
	}

	/**
	 * Get Instance
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Gc_Gift_Card_Free_Purchase_Process_Cart();
		}
		return self::$instance;
	}

	/**
	 *  Validate the store credit fields on add to cart.
	 *  Validates Credit amount, Email
	 *
	 *  @since  1.0.0
	 *  @param  bool $passed         Is valid or not
	 *  @param  int  $product_id     Id of the product
	 *  @return bool    $passed         Is valid or not
	 */
	public function validate_store_credit_on_add_to_cart( $passed, $product_id ) {
		if ( ! self::is_gift_card_product( $product_id ) ) {
			return $passed;
		}

		$gift_card_products   = self::get_gift_card_products();
		$gift_card_product_id = ( is_array( $gift_card_products ) && ! empty( $gift_card_products ) && isset( $gift_card_products[0] ) ? absint( $gift_card_products[0] ) : 0 );

		if ( $product_id !== $gift_card_product_id ) { // Not the first product in the array
			return $passed;
		}

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$template = isset( $_REQUEST['wt_gc_gift_card_image'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wt_gc_gift_card_image'] ) ) : '';

		if ( self::is_templates_enabled( $product_id ) && ! $template ) {

			wc_add_notice( __( 'Please select a template', 'wt-gift-cards-woocommerce' ), 'error' );
			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['wt_credit_amount'] ) || ( isset( $_REQUEST['wt_credit_amount'] ) && 0 === floatval( wp_unslash( $_REQUEST['wt_credit_amount'] ) ) ) ) {
			wc_add_notice( __( 'Amount is required!', 'wt-gift-cards-woocommerce' ), 'error' );
			return false;
		}

		/*
		Email validation */
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['wt_gc_gift_card_reciever_email'] ) ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! is_email( sanitize_text_field( wp_unslash( $_REQUEST['wt_gc_gift_card_reciever_email'] ) ) ) ) {
				wc_add_notice( __( 'Please enter valid email address ', 'wt-gift-cards-woocommerce' ), 'error' );
				return false;
			}
		} else {
                        $mandatory_fields = apply_filters( 'wt_gc_alter_gift_card_form_mandatory_fields', array( 'reciever_email' ) );
                        if( in_array( 'reciever_email', $mandatory_fields ) ){
                            wc_add_notice( __( 'Recipient email is required ', 'wt-gift-cards-woocommerce' ), 'error' );
                            return false;
                        }
		}

		return $passed;
	}


	/**
	 *  Save store credit details into cart item
	 *
	 *  @since  1.0.0
	 *  @param  array $cart_item_data     Cart item data array
	 *  @param  int   $product_id         Id of the product
	 *  @param  int   $variation_id       Id of the product variation
	 *  @return array       $cart_item_data     Cart item data array
	 */
	public function add_store_credit_template_details_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( ! self::is_gift_card_product( $product_id ) ) {
			return $cart_item_data;
		}

		/**
		 *  Keeping the meta same as smart coupon plugin to get compatibility.
		 *  Alternate meta key list contains old meta keys that are used in smart coupon plugin
		 */
		$template_items = array();

		foreach ( self::alternate_meta_keys() as $key => $meta_key ) {
			$field_key = 'wt_gc_gift_card_' . $key;

			if ( false !== stripos( $key, 'email' ) ) {
				$template_items[ $meta_key ] = ( isset( $_REQUEST[ $field_key ] ) ? sanitize_email( wp_unslash( $_REQUEST[ $field_key ] ) ) : '' );
			} else {
				$template_items[ $meta_key ] = ( isset( $_REQUEST[ $field_key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $field_key ] ) ) : '' );
			}
		}

		if ( self::is_templates_enabled( $product_id ) ) {
			$template_items['extended'] = true;
			if ( '' === $template_items['wt_smart_coupon_template_image'] ) {
				$template_items['wt_smart_coupon_template_image'] = 'general'; /* default template for gift card */
			}
		}

		/* user action: email, print */
		$template_items['user_action'] = ( isset( $_REQUEST['wt_gc_gift_card_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wt_gc_gift_card_action'] ) ) : '' );

		$cart_item_data['wt_credit_amount']         = ( isset( $_REQUEST['wt_credit_amount'] ) ? $this->sanitize_price( sanitize_text_field( wp_unslash( $_REQUEST['wt_credit_amount'] ) ) ) : 0 );
		$cart_item_data['wt_store_credit_template'] = $template_items;

		return $cart_item_data;
	}

	/**
	 *  Set credit amount session on cart.
	 *
	 *  @since 1.0.0
	 */
	public function save_credit_details_in_session( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( ! empty( $variation_id ) && 0 < $variation_id ) {
			return;
		}

		if ( ! isset( $cart_item_data['wt_credit_amount'] ) || empty( $cart_item_data['wt_credit_amount'] ) ) {
			return;
		}

		if ( self::is_gift_card_product( $product_id ) ) {
			$wt_credit_amount = WC()->session->get( 'wt_credit_amount' );

			if ( empty( $wt_credit_amount ) || ! is_array( $wt_credit_amount ) ) {
				$wt_credit_amount = array();
			}

			$wt_credit_amount[ $cart_item_key ] = $cart_item_data['wt_credit_amount'];
			WC()->session->set( 'wt_credit_amount', $wt_credit_amount );
		}
	}

	/**
	 *  Update cart item price for gift card purchase.
	 *
	 *  @since  1.0.0
	 *  @param  string $product_price  formatted product price
	 *  @param  array  $cart_item      cart item
	 *  @param  string $cart_item_key  cart item key
	 *  @return string   product price
	 */
	public function cart_item_price_for_credit_purchase( $product_price, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item['wt_credit_amount'] ) ) {
			$product_price = (float) $cart_item['wt_credit_amount'];
			return wp_kses_post( wc_price( $product_price ) );
		}

		return $product_price;
	}

	/**
	 *  Display gift card details in cart table
	 *  This function will add gift card data to item data for printing in cart table
	 *
	 *  @since  1.0.0
	 *  @param  array $item_data   Item data
	 *  @param  array $cart_item   Cart item
	 *  @return array       Item data
	 */
	public function display_credit_details_into_cart_item( $item_data, $cart_item ) {
		if ( isset( $cart_item['wt_store_credit_template'] ) ) {
			$store_credit_details = $cart_item['wt_store_credit_template'];
			$alternate_keys       = self::alternate_meta_keys();

			foreach ( self::get_customizable_giftcard_fields() as $field => $field_title ) {
				if ( isset( $alternate_keys[ $field ] ) && '' !== trim( $store_credit_details[ $alternate_keys[ $field ] ] ) ) {
					$item_data[] = array(
						'key'   => $field_title,
						'value' => stripslashes( $store_credit_details[ $alternate_keys[ $field ] ] ),
					);
				}
			}
		}

		return $item_data;
	}

	/**
	 *  Display gift card image in the cart table
	 *  This method will replace actual product image with user selected gift card template
	 *
	 *  @since  1.0.0
	 *  @param  string $product_image      Product image HTML
	 *  @param  array  $cart_item          Cart item
	 *  @param  string $cart_item_key      Cart item key (Optional)
	 *  @return string  Product image HTML
	 */
	public function display_gift_card_image_in_cart_item( $product_image, $cart_item, $cart_item_key = '' ) {
		if ( isset( $cart_item['wt_store_credit_template'] ) && isset( $cart_item['wt_store_credit_template']['wt_smart_coupon_template_image'] ) && '' !== $cart_item['wt_store_credit_template']['wt_smart_coupon_template_image'] ) {
			$template_data = self::get_gift_card_template( $cart_item['wt_store_credit_template']['wt_smart_coupon_template_image'] );

			if ( isset( $template_data['image_url'] ) && '' !== $template_data['image_url'] ) {
				$product_image = preg_replace( '(src="(.*?)")', 'src="' . esc_attr( $template_data['image_url'] ) . '"', $product_image );
				$product_image = preg_replace( '(srcset="(.*?)")', 'srcset="' . esc_attr( $template_data['image_url'] ) . '"', $product_image );
			}
		}
		return $product_image;
	}

	/**
	 *  Generate and update the coupon data/gift card data to order item
	 *
	 *  @since 1.0.0
	 *                  Order id update for custom image upload.
	 *  @param int    $item_id    Order item id
	 *  @param object $item       Order item
	 *  @param int    $order_id   Id of order
	 */
	public function update_coupon_data_into_order( $item_id, $item, $order_id ) {
		$product = ( is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : '' );

		if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		if ( ! property_exists( $item, 'legacy_values' ) ) {
			return;
		}

		$legacy_values = $item->legacy_values;

		if ( ! isset( $legacy_values['wt_credit_amount'] ) || 0 >= floatval( $legacy_values['wt_credit_amount'] ) ) {
			return;
		}

		if ( ! isset( $legacy_values['wt_store_credit_template'] ) ) {
			return;
		} elseif ( empty( $legacy_values['wt_store_credit_template'] ) ) {
				return;
		}

		$product_id   = $product->get_id();
		$credit_value = floatval( $legacy_values['wt_credit_amount'] );
		$settings     = self::get_product_metas( $product_id ); // gift card product settings

		$email_id = isset( $legacy_values['wt_store_credit_template']['wt_credit_coupon_send_to'] ) ? sanitize_email( $legacy_values['wt_store_credit_template']['wt_credit_coupon_send_to'] ) : sanitize_email( wp_unslash( $_REQUEST['billing_email'] ) );
		$message  = isset( $legacy_values['wt_store_credit_template']['wt_credit_coupon_send_to_message'] ) ? sanitize_textarea_field( $legacy_values['wt_store_credit_template']['wt_credit_coupon_send_to_message'] ) : '';

		/**
		 * prepare coupon properties
		 */
		$coupon_expiry      = (int) Wbte_Woocommerce_Gift_Cards_Free_Common::get_option( 'generated_coupon_expiry_days' );
		$coupon_expiry_date = ( 0 < $coupon_expiry ? gmdate( 'd M Y', strtotime( 'Today +' . $coupon_expiry . ' day' ) ) : '' );
		$product_short_desc = wp_strip_all_tags( $product->get_short_description() );

		$coupons_generated = array();
		$store_credit_data = array();

		$qty = ( is_callable( array( $item, 'get_quantity' ) ) ? $item->get_quantity() : 1 );
		$qty = absint( ! empty( $qty ) ? $qty : 1 );

		for ( $i = 0; $i < $qty; $i++ ) {
			$coupon_data = $this->create_gift_card_coupon( $credit_value, $product_short_desc );

			if ( ! empty( $coupon_data ) ) {
				$coupon_id  = $coupon_data['coupon_id'];
				$coupon_obj = $coupon_data['coupon_obj'];

                                $force_email_restriction = apply_filters( 'wt_gc_coupon_set_email_restriction', true );
				/**
				 *  Set coupon properties
				 */
				// email restrictions
				if ( '' !== $email_id && $force_email_restriction ) {
					$coupon_obj->set_email_restrictions( $email_id );
				}

				// expiry
				if ( $coupon_expiry_date ) {
					$coupon_obj->set_date_expires( $coupon_expiry_date );
				}

				$coupon_obj->save();

				/* preparing values for order meta */
				$coupons_generated[]                          = array(
					'coupon_id'       => $coupon_id,
					'credited_amount' => $credit_value,
				);
				$store_credit_data[ $coupon_id ]              = $legacy_values['wt_store_credit_template'];
				$store_credit_data[ $coupon_id ]['coupon_id'] = $coupon_id;

				/**
				 *  After store credit coupon generated
				 *
				 *  @param  $coupon_obj  WC_Coupon object
				 *  @param  action  For which action the coupon was generated
				 */
				do_action( 'wt_gc_after_store_credit_coupon_generated', $coupon_obj, 'purchased_gift_card' );

			}
		}

		wc_add_order_item_meta( $item_id, 'wt_credit_coupon_generated', $coupons_generated );
		wc_add_order_item_meta( $item_id, 'wt_credit_coupon_template_details', $store_credit_data );
	}

	/**
	 *  Save created coupon details into order meta data.
	 *
	 *  @since 1.0.0
	 *  @param int|WC_Order $order              Order id/WC_Order
	 */
	public function save_credit_details_in_order( $order ) {
		$order       = ! is_object( $order ) ? wc_get_order( $order ) : $order;
		$order_id    = $order->get_id();
		$order_items = $order->get_items();

		$coupons = array();
		foreach ( $order_items as $item_id => $order_item ) {
			$coupons_generated = $order_item->get_meta( 'wt_credit_coupon_generated' );
			if ( ! empty( $coupons_generated ) && is_array( $coupons_generated ) ) {
				$coupons = array_merge( $coupons, $coupons_generated );
			}
		}
		if ( ! empty( $coupons ) ) {
			Wbte_Woocommerce_Gift_Cards_Free_Common::update_order_meta( $order_id, 'wt_credit_coupons', maybe_serialize( $coupons ) );
		}
	}
}
