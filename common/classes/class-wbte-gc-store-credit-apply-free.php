<?php

/**
 * Apply store credit on order.
 *
 * @link
 * @since 1.0.0
 *
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wbte_Gc_Store_Credit_Apply_Free extends Wbte_Woocommerce_Gift_Cards_Free_Common {

	private static $instance        = null;
	private $discount_data          = array();
	private $total_discount         = array();
	private $valid_product_subtotal = false;
	private $subtotal_calculated    = array();

	private $coupon_amounts = array();

	private $newly_added_storecredits = array();
	private $discounts                = array();

	public function __construct() {
		/**
		 * Set store credit sort priority
		 */
		add_filter( 'woocommerce_coupon_sort', array( $this, 'set_coupon_priority' ), 10, 2 );

		/**
		 * Allow store credit coupons with `Individual use` coupons.
		 */
		add_filter( 'woocommerce_apply_with_individual_use_coupon', array( $this, 'allow_store_credit_with_all_coupons' ), 10, 3 );

		/**
		 *  Is coupon valid
		 */
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'is_valid' ), 10, 3 );

		/**
		* Make the coupon valid for cart
		*/
		add_filter( 'woocommerce_coupon_is_valid_for_cart', array( $this, 'is_valid_for_cart' ), 10, 2 );

		/**
		 * Checks valid products are in the cart to apply the store credit
		 */
		add_filter( 'wt_smart_coupon_store_credit_validation', array( $this, 'is_valid_products' ), 10, 2 );

		/**
		 *  Keep the `Store credit` coupon applied when an `Individual use` coupon is being applied.
		 */
		add_filter( 'woocommerce_apply_individual_use_coupon', array( $this, 'keep_the_store_credit_coupon_applied' ), 10, 3 );

		/**
		* Move the store credit as last entry when applying coupons.
		*/
		add_action( 'woocommerce_applied_coupon', array( $this, 'apply_coupon_last' ) );

		/**
		 *  Update the credit value of coupon on successful order. Remove the coupon if the credit amount is zero or lesser.
		 */
		add_action( 'woocommerce_new_order', array( $this, 'update_credit_amount' ), 8 ); // Classic checkout
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'update_credit_amount' ), 8 ); // Checkout block

		/**
		* Move the store credits as last entries on taking order coupons. This will be helpful when recalculating coupons on order edit page
		*/
		add_action( 'woocommerce_order_get_items', array( $this, 'move_store_credit_as_last_entry' ), 1000, 3 );

		/**
		 * Calculations ----------------------------
		 */

		/**
		 * Get the store credit discount amount for a cart item
		 */
		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'get_discount_amount' ), 10, 5 );

		$priority = self::get_calculate_totals_hook_priority( 1000, 'woocommerce_order_after_calculate_totals' );
		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'order_calculate_discount_amount' ), $priority, 2 );

		/**
		 * Display functions ----------------------------
		 */

		/**
		 *  Store Credit coupon label in cart and checkout pages.
		 */
		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'store_credit_cart_total_coupon_label' ), 10, 2 );

		/**
		 * Add store credit entry in order detail table. My account->orders page, Order success page etc
		 */
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'add_credit_info_to_order_detail_table' ), 10, 2 );
	}

	/**
	 * Get Instance
	 *
	 *  @since 1.0.0
	 */
	public static function get_instance( $plugin_name, $version ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Gc_Store_Credit_Apply_Free();
		}
		return self::$instance;
	}

	/**
	 * Set the store credit coupon priority
	 */
	public function set_coupon_priority( $priority, $coupon ) {
		return ( self::is_store_credit_coupon( $coupon ) ? 4 : $priority );
	}

	/**
	 * Allow store credit coupons with all coupons
	 */
	public function allow_store_credit_with_all_coupons( $allow_coupon, $coupon, $check_coupon ) {
		if ( ! self::is_store_credit_coupon( $coupon ) ) {
			return $allow_coupon;
		}

		return wc_string_to_bool( self::get_option( 'usage_with_other_coupons' ) );
	}


	/**
	 * Make the coupon valid for cart
	 */
	public function is_valid_for_cart( $valid, $coupon ) {
		return ( self::is_store_credit_coupon( $coupon ) ? true : $valid );
	}

	public function is_valid( $valid, $coupon, $discount ) {
		if ( empty( $coupon ) ) {
			return $valid;
		}

		if ( self::is_store_credit_coupon( $coupon ) ) {
			$coupon_id = $coupon->get_id();

			/* Auto generated but not activated. */
			if ( get_post_meta( $coupon_id, 'wt_auto_generated_store_credit_coupon', true ) && ! get_post_meta( $coupon_id, '_wt_smart_coupon_credit_activated', true ) ) {
				$valid = false;
			}

			$valid = apply_filters( 'wt_gc_store_credit_validation', $valid, $coupon );

			if ( $valid && 0 === $coupon->get_amount() ) {
				$valid = false;
			}

			/**
			 *  Check and disallow gift card purchase using store credit
			 */
			if ( $valid && ! self::allow_to_purchase_gift_cards() ) {
				foreach ( $discount->get_items_to_validate() as $item ) {
					if ( isset( $item->object['wt_credit_amount'] ) || isset( $item->object['wt_credit_coupon_generated'] ) ) {
						$valid = false;
						throw new Exception( __( 'Sorry, you cannot purchase store credit with this coupon.', 'wt-gift-cards-woocommerce' ) );
						break;
					}
				}
			}
		}

		return $valid;
	}


	/**
	 *  Checks valid products are in the cart to apply the store credit
	 */
	public function is_valid_products( $valid, $coupon ) {
		if ( self::is_store_credit_coupon( $coupon ) && $valid ) {
			$cart_items                    = WC()->cart->get_cart();
			$cart_items                    = ( isset( $cart_items ) && is_array( $cart_items ) ) ? $cart_items : array();
			$coupon_allowed_product_id_arr = $coupon->get_product_ids();
			$coupon_allowed_product_id_arr = is_array( $coupon_allowed_product_id_arr ) ? $coupon_allowed_product_id_arr : array();

			/**
			 *  From Smart coupon plugin.
			 */
			$store_credit_excluded = self::get_store_credit_disabled_products();

			$total_products   = 0;
			$exclude_products = 0;

			foreach ( $cart_items as $cart_item_key => $item ) {
				if ( isset( $item['free_product'] ) && 'wt_give_away_product' === $item['free_product'] ) {
					continue; // skip free products
				}

				$product_id   = $item['product_id'];
				$variation_id = $item['variation_id'];

				if ( 0 < $variation_id ) {
					if ( ! empty( $coupon_allowed_product_id_arr ) && ! in_array( $variation_id, $coupon_allowed_product_id_arr ) && ! in_array( $product_id, $coupon_allowed_product_id_arr ) ) {
						++$exclude_products;
					} elseif ( in_array( $variation_id, $store_credit_excluded ) || in_array( $product_id, $store_credit_excluded ) ) {
							++$exclude_products;
					}
				} elseif ( ! empty( $coupon_allowed_product_id_arr ) && ! in_array( $product_id, $coupon_allowed_product_id_arr ) ) {
						++$exclude_products;
				} elseif ( in_array( $product_id, $store_credit_excluded ) ) {
						++$exclude_products;
				}

				++$total_products;
			}

			if ( $total_products === $exclude_products ) {
				$valid = false;
			} else {
				$valid = true;
			}
		}

		return $valid;
	}


	/**
	 * Make the store credit coupon allowed.
	 */
	public function keep_the_store_credit_coupon_applied( $allowed_coupons, $the_coupon, $applied_coupons ) {
		foreach ( $applied_coupons as $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );
			if ( self::is_store_credit_coupon( $coupon ) ) {
				$allowed_coupons[] = $coupon_code;
			}
		}
		return $allowed_coupons;
	}


	/**
	 * Move the store credit coupons as last entries in the applied coupon list.
	 */
	public function apply_coupon_last( $coupon_code ) {
		$applied_coupons = WC()->cart->get_applied_coupons();

		if ( empty( $applied_coupons ) ) {
			return;
		}

		$coupon = new WC_Coupon( $coupon_code );

		if ( self::is_store_credit_coupon( $coupon ) ) {
			return;
		}

		$codes_to_add_back = array();

		foreach ( $applied_coupons as $applied_coupon_index => $applied_coupon_code ) {
			$applied_coupon = new WC_Coupon( $applied_coupon_code );
			if ( self::is_store_credit_coupon( $applied_coupon ) ) {
				WC()->cart->remove_coupon( $applied_coupon_code );
				$codes_to_add_back[] = $applied_coupon_code;
			}
		}

		add_filter( 'woocommerce_coupon_message', '__return_empty_string' );

		foreach ( $codes_to_add_back as $code_to_add_back ) {
			WC()->cart->add_discount( $code_to_add_back );
		}

		remove_filter( 'woocommerce_coupon_message', '__return_empty_string' );
	}


	/**
	 *  Set store credit coupons as last entries when get_coupons from an order
	 */
	public function move_store_credit_as_last_entry( $items, $order, $types ) {
		if ( ( is_array( $types ) && in_array( 'coupon', $types ) ) || ( is_string( $types ) && 'coupon' === $types ) ) {
			$out           = array();
			$store_credits = array();

			foreach ( $items as $key => $coupon ) {
				if ( self::is_store_credit_coupon( $coupon ) ) {
					$store_credits[ $key ] = $coupon;
				} else {
					$out[ $key ] = $coupon;
				}
			}

			$items = $out + $store_credits; // do not use array_merge
		}

		return $items;
	}


	/**
	 *  Get the store credit discount amount for a cart item
	 */
	public function get_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
		if ( ! self::is_store_credit_coupon( $coupon ) ) {
			return $discount;
		}

		$cart_item_key = self::get_item_key_from_item( $cart_item );

		if ( false === $cart_item_key || is_null( $cart_item_key ) ) {
			return $discount;
		}

		$product = self::get_product_from_cart_item( $cart_item );

		if ( is_null( $product ) ) {
			return $discount;
		}

		$is_backend        = self::is_order_edit( $cart_item );
		$store_credit_used = array();

		if ( $is_backend ) {
			$order_id          = $cart_item->get_order_id();
			$store_credit_used = $order_id ? $this->get_credit_used_for_order( $order_id ) : array();
			$store_credit_used = ( is_array( $store_credit_used ) ? $store_credit_used : array() );
		}

		$coupon_code = wc_sanitize_coupon_code( $coupon->get_code() );

		$coupon_amount = (float) ( isset( $this->coupon_amounts[ $coupon_code ] ) ? $this->coupon_amounts[ $coupon_code ] : $coupon->get_amount() );

		if ( $is_backend && isset( $store_credit_used[ $coupon_code ] ) ) {
			$coupon_amount = (float) $store_credit_used[ $coupon_code ];
		}

		$this->coupon_amounts[ $coupon_code ] = $coupon_amount; // just assign it, may be not assigned

		if ( ! is_null( $product ) && $coupon->is_valid_for_product( $product ) ) {
			$discount = $this->get_discount_for_item( $coupon, $cart_item_key );

			if ( false === $discount ) {
				$discount = $discounting_amount * $cart_item['quantity'];
				$discount = min( $discount, $coupon_amount );

				$this->set_discount_for_item( $coupon, $cart_item_key, $discount ); /* save this for future */

				$this->coupon_amounts[ $coupon_code ] -= $discount;
			}
		}

		return $discount / $cart_item['quantity'];
	}

	/**
	 *  Store Credit coupon label in cart and checkout pages.
	 */
	public function store_credit_cart_total_coupon_label( $label, $coupon ) {
		if ( self::is_store_credit_coupon( $coupon ) ) {
			$label = __( 'Store credit', 'wt-gift-cards-woocommerce' );
		}

		return $label;
	}

	/**
	 *  Update the credit value of coupon on successful order. Remove the coupon if the credit amount is zero or lesser.
	 *
	 *  @since  1.0.0
	 *  @param  int|WC_Order $order              Order id/WC_Order
	 */
	public function update_credit_amount( $order ) {

		$order    = ! is_object( $order ) ? wc_get_order( $order ) : $order;
		$order_id = $order->get_id();

		if ( empty( WC()->cart ) ) {
			return;
		}

		$applied_coupons = WC()->cart->get_applied_coupons();

		if ( empty( $applied_coupons ) ) {
			return;
		}

		$store_credit_used      = array();
		$coupon_discount_totals = $this->get_coupon_discount_totals();

		foreach ( $applied_coupons as $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );

			if ( ! $coupon || ! self::is_store_credit_coupon( $coupon ) ) {
				continue;
			}

			$coupon_discount = ( isset( $coupon_discount_totals[ $coupon_code ] ) ? $coupon_discount_totals[ $coupon_code ] : 0 );

			if ( $coupon_discount > 0 ) {
				$store_credit_used[ $coupon_code ] = $coupon_discount;
				$coupon_amount                     = $coupon->get_amount();
				$coupon_id                         = $coupon->get_id();

				$remaining_coupon_amount = max( 0, ( $coupon_amount - $coupon_discount ) );

				$coupon->set_amount( wc_format_decimal( $remaining_coupon_amount, 2 ) );
				$coupon->save();

				$this->add_credit_history( $coupon_id, $order_id, $coupon_amount, $remaining_coupon_amount, $coupon_discount );

				if ( $remaining_coupon_amount <= 0 && apply_filters( 'wt_gc_delete_store_credit_after_use', false, $coupon ) ) {
					wp_trash_post( $coupon_id );
				}
			}
		}

		$this->update_credit_used_for_order( $order_id, $store_credit_used, 'new_order' );
	}

	public function add_credit_info_to_order_detail_table( $total_rows, $order ) {
		$credit = $this->get_total_credit_used_for_an_order( $order );

		if ( 0 < $credit ) {
			$credit = wc_round_discount( $credit, wc_get_price_decimals() );
			$offset = array_search( 'order_total', array_keys( $total_rows ), true );

			if ( false === $offset ) {
				$offset = count( $total_rows );
			}

			$total_rows = array_merge(
				array_slice( $total_rows, 0, $offset ),
				array(
					'store_credit' => array(
						'label' => __( 'Store credit used:', 'wt-gift-cards-woocommerce' ),
						'value' => wc_price( -$credit ),
					),
				),
				array_slice( $total_rows, $offset )
			);

			/**
			 *  Toggle discount row.
			 */
			$total_discount = $order->get_total_discount(); // total discount in the order, Includes all coupons
			$total_discount = wc_round_discount( $total_discount, wc_get_price_decimals() );

			if ( $total_discount === $credit ) {
				unset( $total_rows['discount'] );
			} elseif ( isset( $total_rows['discount'] ) ) {
					$total_discount                  = $total_discount - $credit;
					$total_rows['discount']['value'] = '-' . wc_price( $total_discount );
			}
		}

		return $total_rows;
	}


	/**
	 * To set store credit amount for orders that are manually created and updated from backend
	 *
	 * @param   bool     $and_taxes Calc taxes if true.
	 * @param   WC_Order $order Order object.
	 */
	public function order_calculate_discount_amount( $and_taxes, $order ) {
		/* POST variables */
        $post_action    = ( ! empty( $_POST['action'] ) ) ? wc_clean( wp_unslash( $_POST['action'] ) ) : ''; // phpcs:ignore
        $post_post_type = ( ! empty( $_POST['post_type'] ) ) ? wc_clean( wp_unslash( $_POST['post_type'] ) ) : ''; // phpcs:ignore
        $post_coupon = ( ! empty( $_POST['coupon'] ) ) ? wc_clean( wp_unslash( $_POST['coupon'] ) ) : ''; // phpcs:ignore

		if ( is_object( $order ) && is_a( $order, 'WC_Order' ) ) {
			$order_id = $order->get_id();
			$total    = $order->get_total();
			$coupons  = $order->get_items( 'coupon' );

			$this->order_calculate_discount_before_tax( $post_action, $post_coupon, $coupons, $order );
		}
	}


	/*
	|--------------------------------------------------------------------------
	| Non filter callback functions.
	|--------------------------------------------------------------------------
	*/


	private function order_calculate_discount_before_tax( $post_action, $post_coupon, $coupons, $order ) {
		if ( $this->is_order_new_coupon( $post_action, $post_coupon ) || $this->is_order_remove_coupon( $post_action, $post_coupon ) ) {
			$new_coupon = new WC_Coupon( $post_coupon );

			$order_id          = $order->get_id();
			$total             = $order->get_total();
			$store_credit_used = $this->get_credit_used_for_order( $order_id );

			foreach ( $coupons as $item_id => $item ) {
				$coupon_code = wc_sanitize_coupon_code( $item->get_code() );

				$coupon = new WC_Coupon( $coupon_code );
				if ( ! self::is_store_credit_coupon( $coupon ) ) {
					continue;
				}

				if ( isset( $store_credit_used[ $coupon_code ] ) ) {
					$discount = $store_credit_used[ $coupon_code ];
				} else {
					$discount = ( is_object( $item ) && is_callable( array( $item, 'get_discount' ) ) ) ? $item->get_discount() : wc_get_order_item_meta( $item_id, 'discount_amount', true );
				}

				$store_credit_used[ $coupon_code ] = $discount;

				if ( is_object( $item ) && is_callable( array( $item, 'set_discount' ) ) ) {
					$item->set_discount( $discount );
				} else {
					$item['discount_amount'] = $discount;
				}
			}

			$action = ''; // For balance updation filter

			// update newly added coupon amount
			if ( $this->is_order_new_coupon( $post_action, $post_coupon ) && ! in_array( $post_coupon, $this->newly_added_storecredits ) ) {
				if ( self::is_store_credit_coupon( $new_coupon ) ) {
					$coupon_amount           = $new_coupon->get_amount();
					$coupon_discount         = $store_credit_used[ $post_coupon ];
					$remaining_coupon_amount = max( 0, ( $coupon_amount - $coupon_discount ) );
					$new_coupon->set_amount( wc_format_decimal( $remaining_coupon_amount, 2 ) );
					$new_coupon->save();

					$this->newly_added_storecredits[] = $post_coupon; // to prevent deducting the amount multiple times
					$action                           = 'order_new_coupon_before_tax';
				}
			}

			if ( $this->is_order_remove_coupon( $post_action, $post_coupon ) && isset( $store_credit_used[ $post_coupon ] ) ) {
				self::reimburse_coupon( $new_coupon, $discount, $order );

				unset( $store_credit_used[ $post_coupon ] );
				$action = 'order_remove_coupon_before_tax';
			}

			$this->update_credit_used_for_order( $order_id, $store_credit_used, $action );

		}
	}

	private function is_order_remove_coupon( $post_action, $post_coupon ) {
		return ( 'woocommerce_remove_order_coupon' === $post_action && '' !== $post_coupon );
	}

	private function is_order_new_coupon( $post_action, $post_coupon ) {
		return ( 'woocommerce_add_coupon_discount' === $post_action && '' !== $post_coupon );
	}


	/**
	 * Store the calculated discount values into cache.
	 */
	public function set_discount_for_item( $coupon, $item_key, $discount ) {
		$coupon_code = $this->get_coupon_code( $coupon );

		$discounts              = $this->get_discounts_for_coupon( $coupon_code );
		$discounts[ $item_key ] = $discount;

		$this->discounts[ $coupon_code ] = $discounts;
	}

	/**
	 * Update cart object (Update coupon discount total)
	 */
	public function update_coupon_discount_total( $coupon, $discount ) {
		$coupon_code            = $this->get_coupon_code( $coupon );
		$coupon_discount_totals = $this->get_coupon_discount_totals();

		$coupon_discount_totals[ $coupon_code ] = $discount;

		if ( method_exists( WC()->cart, 'set_coupon_discount_totals' ) ) {
			WC()->cart->set_coupon_discount_totals( $coupon_discount_totals );
		} else {
			WC()->cart->coupon_discount_amounts = $coupon_discount_totals;
		}
	}

	public function add_credit_history( $coupon_id, $order_id, $old_amount, $new_amount, $discount ) {
		$credit_history = $this->get_credit_history( $coupon_id );

		$credit_history[ "'" . time() . "'" ] = array(
			'order'           => $order_id,
			'previous_credit' => $old_amount,
			'updated_credit'  => $new_amount,
			'credit_used'     => $discount,
			'comments'        => __( '-', 'wt-gift-cards-woocommerce' ),
		);

		update_post_meta( $coupon_id, 'wt_credit_history', $credit_history );
	}

	/**
	 *  Get methods ------------------------
	 */

	/**
	 * Get coupon discount if cached.
	 */
	public function get_discounts_for_coupon( $coupon ) {
		$coupon_code = $this->get_coupon_code( $coupon );

		return ( isset( $this->discounts[ $coupon_code ] ) ? $this->discounts[ $coupon_code ] : array() );
	}

	/**
	 *  get discount for specified item
	 */
	public function get_discount_for_item( $the_coupon, $item_key ) {
		$discounts = $this->get_discounts_for_coupon( $the_coupon );

		if ( isset( $discounts[ $item_key ] ) ) {
			return $discounts[ $item_key ];
		}

		return false;
	}

	public function get_coupon_code( $coupon ) {
		if ( is_string( $coupon ) ) {
			return wc_sanitize_coupon_code( $coupon );

		} elseif ( is_a( $coupon, 'WC_Coupon' ) ) {
			return wc_sanitize_coupon_code( $coupon->get_code() );
		}

		return '';
	}

	/**
	 *  Get Coupon Discount total from cart session.
	 */
	public function get_coupon_discount_totals() {
		if ( method_exists( WC()->cart, 'get_coupon_discount_totals' ) ) {
			$coupon_discount_totals = WC()->cart->get_coupon_discount_totals();
		} else {
			$coupon_discount_totals = ( isset( WC()->cart->coupon_discount_amounts ) ? WC()->cart->coupon_discount_amounts : array() );
		}

		return is_array( $coupon_discount_totals ) ? $coupon_discount_totals : array();
	}

	public function get_credit_history( $coupon_id ) {
		$credit_history = array();

		if ( metadata_exists( 'post', $coupon_id, 'wt_credit_history' ) ) {
			$credit_history = get_post_meta( $coupon_id, 'wt_credit_history', true );
		}

		return ( is_array( $credit_history ) ? $credit_history : array() );
	}


	/**
	 *  Get total discount applied by all store credit coupons in a cart item.
	 *  This is used when tax calculation is done before applying store credit.
	 *  This is useful for preventing store credit being applied over non eligible amounts.
	 *
	 *  @since 1.0.0
	 *  @param $cart_item_key   string  Cart item key
	 *  @return float   Discount total
	 */
	public function get_item_discount_total( $cart_item_key ) {
		return array_sum( array_column( $this->discounts, $cart_item_key ) );
	}
}
