<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * The common layer of the plugin. This section will be commonly accessible for admin and public
 *
 * @link       https://www.webtoffee.com/
 * @since      1.0.0
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/common
 */

/**
 * The common layer of the plugin.
 *
 * Defines the plugin name, version
 *
 * @package    Wbte_Woocommerce_Gift_Cards_Free
 * @subpackage Wbte_Woocommerce_Gift_Cards_Free/common
 * @author     WebToffee <info@webtoffee.com>
 */
class Wbte_Woocommerce_Gift_Cards_Free_Common {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


	private static $instance     = null;
	private static $hpos_enabled = null;

	private static $stored_options = array();

	public static $no_image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

	public static $store_credit_coupon_type_name = 'store_credit'; /* store credit coupon type name */

	/*
	 * module list, Module folder and main file must be same as that of module name
	 * Please check the `register_modules` method for more details
	 */
	public static $modules = array(
		'gift_card',
	);

	public static $existing_modules = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 *  Get Instance
	 *
	 *  @since 1.0.0
	 */
	public static function get_instance( $plugin_name, $version ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Woocommerce_Gift_Cards_Free_Common( $plugin_name, $version );
		}

		return self::$instance;
	}

	/**
	 *  Registers modules
	 *
	 *  @since 1.0.0
	 */
	public function register_modules() {
		Wbte_Woocommerce_Gift_Cards_Free::register_modules( self::$modules, 'wt_gc_common_modules', plugin_dir_path( __FILE__ ), self::$existing_modules );
	}

	/**
	 *  Check module is enabled
	 *
	 *  @since 1.0.0
	 *  @param string module base
	 *  @return bool is module exists or not
	 */
	public static function module_exists( $module ) {
		return in_array( $module, self::$existing_modules, true );
	}

	/**
	 *  Remove store credit functionality from Smart coupon plugin
	 *
	 *  @since 1.0.0
	 */
	public function remove_smart_coupon_store_credit_functionality() {
		if ( apply_filters( 'wt_gc_force_remove_smart_coupon_store_credit', true ) ) {
			$options = array( 'wt_sc_common_modules', 'wt_sc_admin_modules', 'wt_sc_public_modules' );

			foreach ( $options as $option ) {
				$option_val = get_option( $option );

				if ( $option_val && is_array( $option_val ) && isset( $option_val['store_credit'] ) && 1 === absint( $option_val['store_credit'] ) ) {
					$option_val['store_credit'] = 0;
					update_option( $option, $option_val );
				}
			}
		}
	}

	/**
	 *  Migrate settings from Smart coupon plugin
	 *
	 *  @since 1.0.0
	 */
	public function migrate_settings_from_smart_coupon() {
		if ( ! get_option( 'wt_gc_migrated_from_smart_coupon' ) ) {
			$smartcoupon_options = (array) get_option( 'wt-smart-coupon-for-woo_store_credit' );
			$gift_card_module_id = Wbte_Gc_Gift_Card_Free_Common::$module_id_static;

			// gift card product and related metas
			if ( isset( $smartcoupon_options['store_credit_purchase_product'] ) && absint( $smartcoupon_options['store_credit_purchase_product'] ) > 0 ) {
				$product_id = absint( $smartcoupon_options['store_credit_purchase_product'] );
				$product    = wc_get_product( $product_id );

				if ( $product ) {
					// add gift card post metas
					update_post_meta( $product_id, '_wt_gc_gift_card_product', $product_id );

					/* add to products list */
					$existing_products   = Wbte_Gc_Gift_Card_Free_Common::get_gift_card_products();
					$existing_products[] = $product_id;
					self::update_option( 'gift_card_products', $existing_products, $gift_card_module_id );

					// enable template
					if ( isset( $smartcoupon_options['enabled_extended_store_credit'] ) ) {
						update_post_meta( $product_id, '_wt_gc_enable_template', wc_bool_to_string( wc_string_to_bool( $smartcoupon_options['enabled_extended_store_credit'] ) ) );
					}

					// preset amounts
					if ( isset( $smartcoupon_options['denominations'] ) ) {
						update_post_meta( $product_id, '_wt_gc_amounts', $smartcoupon_options['denominations'] );
					}
				}
			}

			// Order status to generate/activate
			if ( isset( $smartcoupon_options['send_purchased_credit_on_order_status'] ) ) {
				$new_value = ( ! is_array( $smartcoupon_options['send_purchased_credit_on_order_status'] ) ? array( $smartcoupon_options['send_purchased_credit_on_order_status'] ) : $smartcoupon_options['send_purchased_credit_on_order_status'] );
				self::update_option( 'order_status_to_generate', $new_value, $gift_card_module_id );
			}

			// hidden templates
			if ( isset( $smartcoupon_options['gift_card_template_to_hide'] ) ) {
				$existing_hidden = Wbte_Gc_Gift_Card_Free_Common::get_hidden_templates();
				$new_value       = (array) $smartcoupon_options['gift_card_template_to_hide'];

				$new_value = array_unique( array_merge( $existing_hidden, $new_value ) ); // merge
				self::update_option( 'gift_card_template_to_hide', $new_value, $gift_card_module_id );
			}

			update_option( 'wt_gc_migrated_from_smart_coupon', time() );
		}
	}

	/**
	 *  Get default settings
	 *
	 *  @since     1.0.0
	 *  @param     string $base_id  Module id. Optional, If empty then return plugin main settings
	 *  @return    array    Settings array
	 */
	public static function default_settings( $base_id = '' ) {
		$settings = array(
			'usage_with_other_coupons'     => 'yes',
			'allow_to_purchase_gift_cards' => 'yes',
			'generated_coupon_expiry_days' => 365,

		);

		if ( '' !== $base_id ) {
			$settings = apply_filters( 'wt_gc_module_default_settings', $settings, $base_id );
		}
		return $settings;
	}

	/**
	 *  Fields like, multi select, checkbox etc will not return a POST variable when their value is empty.
	 *
	 *  @since 1.0.0
	 *  @param     string $base_id  Module id.
	 *  @return    array    array of fields
	 */
	public static function settings_needed_default_val( $base_id = '' ) {
		return apply_filters( 'wt_gc_intl_alter_fields_needed_default_val', array(), $base_id );
	}

	/**
	 *  Validation rule for settings. If no validation rule for an input then it will validate as text
	 *  Eg: array(
	 *      'filed_name' => array('type' => 'text_arr'),
	 *  );
	 *
	 *  @since 1.0.0
	 *  @param     string $base_id  Module id.
	 *  @return    array    array of validation rule
	 */
	public static function settings_validation_rule( $base_id = '' ) {
		$validation_rule = array(); // this is for plugin settings default. Modules can alter

		return apply_filters( 'wt_gc_intl_alter_validation_rule', $validation_rule, $base_id );
	}

	/**
	 *  Get current settings.
	 *
	 *  @since     1.0.0
	 *  @param     string $base_id  Module id. Optional, If empty then return plugin main settings
	 *  @return    array    Settings array
	 */
	public static function get_settings( $base_id = '' ) {
		$settings         = self::default_settings( $base_id );
		$option_name      = ( '' === $base_id ? WBTE_GC_FREE_SETTINGS_FIELD : $base_id );
		$option_id        = ( '' === $base_id ? 'main' : $base_id ); // to store in the stored option variable
		$current_settings = get_option( $option_name, array() );

		if ( ! empty( $current_settings ) ) {
			foreach ( $settings as $setting_key => $setting ) {
				if ( isset( $current_settings[ $setting_key ] ) ) {
					if ( is_array( $setting ) && self::is_assoc_arr( $setting ) ) {
						$settings[ $setting_key ] = wp_parse_args( $current_settings[ $setting_key ], $settings[ $setting_key ] );
					} else {
						/* assumes not a sub setting */
						$settings[ $setting_key ] = $current_settings[ $setting_key ];
					}
				}
			}
		}

		// stripping escape slashes
		$settings = self::arr_stripslashes( $settings );
		$settings = apply_filters( 'wt_gc_alter_settings', $settings, $base_id );

		return $settings;
	}


	/**
	 *  Update current settings.
	 *
	 *  @since     1.0.0
	 *  @param     array  $the_options  Settings array
	 *  @param     string $base_id  Module id. Optional
	 */
	public static function update_settings( $the_options, $base_id = '' ) {
		if ( '' !== $base_id && 'main' !== $base_id ) {
			self::$stored_options[ $base_id ] = $the_options;
			update_option( $base_id, $the_options );
		}

		if ( '' === $base_id ) {
			self::$stored_options['main'] = $the_options;
			update_option( WBTE_GC_FREE_SETTINGS_FIELD, $the_options );
		}
	}


	/**
	 *  Update option value,
	 *
	 *  @since     1.0.0
	 *  @param     string $option_name  Option name to be updated
	 *  @param     mixed  $value  Option value
	 *  @param     string $base  Module id. Optional
	 */
	public static function update_option( $option_name, $value, $base = '' ) {
		$the_options                 = self::get_settings( $base );
		$the_options[ $option_name ] = $value;
		self::update_settings( $the_options, $base );
	}

	/**
	 *  Get option value
	 *
	 *  @since  1.0.0
	 *  @param     string $option_name  Name of Option
	 *  @param     string $base  Module id. Optional
	 *  @param     array  $the_options  Settings array. Optional
	 *  @return    mixed    Value of option.
	 */
	public static function get_option( $option_name, $base = '', $the_options = null ) {
		if ( is_null( $the_options ) ) {
			$the_options = self::get_settings( $base );
		}

		$vl = isset( $the_options[ $option_name ] ) ? $the_options[ $option_name ] : false;
		$vl = apply_filters( 'wt_gc_alter_option', $vl, $the_options, $option_name, $base );

		return $vl;
	}


	/**
	 *  Get module id from module base
	 *
	 *  @since 1.0.0
	 *  @param     string $module_base  Module base.
	 *  @return    string   Module id.
	 */
	public static function get_module_id( $module_base ) {
		return WBTE_GC_FREE_PLUGIN_NAME . '_' . $module_base;
	}


	/**
	 *   Get module base from module id
	 *
	 *   @since  1.0.0
	 *   @param  string $module_id  Module id.
	 *   @return string  Module base.
	 */
	public static function get_module_base( $module_id ) {
		if ( false !== strpos( $module_id, WBTE_GC_FREE_PLUGIN_NAME . '_' ) ) {
			return str_replace( WBTE_GC_FREE_PLUGIN_NAME . '_', '', $module_id );
		}
		return false;
	}


	/**
	 *  Checks the current array is an associative array
	 *
	 *  @since  1.0.0
	 *  @param  array $arr Array to be checked
	 *  @return bool is associative array or not
	 */
	public static function is_assoc_arr( $arr ) {
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

	/**
	 *  Strip slashes, If array or object given, the function will recursively check
	 *
	 *  @since     1.0.0
	 *  @param mixed $arr The value to be checked
	 *  @return mixed The slash stripped value, If array given the output value will be array
	 */
	protected static function arr_stripslashes( $arr ) {
		if ( is_array( $arr ) || is_object( $arr ) ) {
			foreach ( $arr as &$arrv ) {
				$arrv = self::arr_stripslashes( $arrv );
			}

			return $arr;

		} else {
			return stripslashes( $arr );
		}
	}

	/**
	 *  Register store credit coupon type
	 *
	 *  @since 1.0.0
	 *  @param array $discount_types  Discount types array
	 *  @return array  Discount types array
	 */
	public function add_store_credit_discount_type( $discount_types ) {
		$discount_types[ self::$store_credit_coupon_type_name ] = __( 'Store credit', 'wt-gift-cards-woocommerce' );
		return $discount_types;
	}

	/**
	 *  Current coupon is a store credit coupon
	 *
	 *  @since 1.0.0
	 *  @param WC_Coupon                           $coupon     coupon object
	 *  @param bool         is store credit or not
	 */
	public static function is_store_credit_coupon( $coupon ) {
		return $coupon->is_type( self::$store_credit_coupon_type_name );
	}


	/**
	 *  Checks if the given email address(es) matches the ones specified on the coupon.
	 *
	 *  @since 1.0.0
	 *  @param array $check_emails Array of customer email addresses.
	 *  @param array $restrictions Array of allowed email addresses.
	 *  @return bool
	 */
	public static function is_coupon_emails_allowed( $check_emails, $coupon_obj ) {
		$restrictions = $coupon_obj->get_email_restrictions();

		if ( empty( $restrictions ) ) {
			return true;
		}

		foreach ( $check_emails as $check_email ) {

			$check_email = strtolower( $check_email );

			// With a direct match we return true.
			if ( in_array( $check_email, $restrictions, true ) ) {
				return true;
			}

			// Go through the allowed emails and return true if the email matches a wildcard.
			foreach ( $restrictions as $restriction ) {
				// Convert to PHP-regex syntax.
				$regex = '/^' . str_replace( '*', '(.+)?', $restriction ) . '$/';
				preg_match( $regex, $check_email, $match );
				if ( ! empty( $match ) ) {
					return true;
				}
			}
		}

		// No matches, this one isn't allowed.
		return false;
	}


	/**
	 *  Get WC_DateTime object for a date
	 *
	 *  @since 1.0.0
	 *  @param  string|int $value String date or date timestamp
	 *  @return WC_DateTime  Date and time object
	 */
	public static function prepare_date_object( $value ) {
		if ( is_int( $value ) ) {
			$timestamp = $value;
		} elseif ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
				$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
				$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
		} else {
			$timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $value ) ) ) );
		}
		$datetime = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

		// Set local timezone or offset.
		if ( get_option( 'timezone_string' ) ) {
			$datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
		} else {
			$datetime->set_utc_offset( wc_timezone_offset() );
		}

		return $datetime;
	}


	/**
	 *  Get coupon expiration date.
	 *
	 *  @since  1.0.0
	 *  @param  WC_Coupon $coupon Coupon object
	 *  @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public static function get_store_credit_expiry( $coupon ) {
		/**
		 *  Filter to alter the date.
		 *  Some plugins provide custom coupon expiry options
		 */
		return apply_filters( 'wt_gc_alter_store_credit_expiry', $coupon->get_date_expires(), $coupon );
	}


	/**
	 *  Get calculate totals hook priority
	 *
	 *  @since 1.0.0
	 *  @param int    $priority   Priority number
	 *  @param string $hook       Hook name, In which hook the priority is applying. Eg: `woocommerce_after_calculate_totals`, `woocommerce_order_after_calculate_totals`
	 *  @return int   Priority number
	 */
	public static function get_calculate_totals_hook_priority( $priority, $hook ) {
		return apply_filters( 'wt_gc_calculate_totals_hook_priority', $priority, $hook );
	}

	/**
	 *  Get product from cart/order item,
	 *  This method is usefull when doing operations with cart item/order item inside same function
	 *
	 *  @since 1.0.0
	 *  @param mixed $cart_item  Cart item/Order item
	 *  @return null|Product object
	 */
	public static function get_product_from_cart_item( $cart_item ) {
		if ( is_null( $cart_item['data'] ) && is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
			return $cart_item->get_product();
		}

		return ( isset( $cart_item['data'] ) ? $cart_item['data'] : null );
	}

	/**
	 *  This function will return cart item key if the argument is WC_Cart item, otherwise return order item id
	 *
	 *  @since 1.0.0
	 *  @param mixed $cart_item  Cart item/Order item
	 *  @return null|string/int   Cart item key/Or order item id on success otherwise null
	 */
	public static function get_item_key_from_item( $cart_item ) {
		if ( is_null( $cart_item['key'] ) && is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
			return $cart_item->get_id();
		}

		return ( isset( $cart_item['key'] ) ? $cart_item['key'] : null );
	}

	/**
	 *  Check is order edit, apply coupon etc from backend
	 *
	 *  @since 1.0.0
	 *  @param mixed $cart_item  Cart item/Order item
	 *  @return bool    Is order item or not
	 */
	public static function is_order_edit( $cart_item ) {
		return is_a( $cart_item, 'WC_Order_Item_Product' );
	}

	/**
	 *  Get credit used for an order
	 *
	 *  @since  1.0.0
	 *  @param  WC_Order $order  Order object
	 *  @return bool|array  Associative array with credit used from coupons otherwise false
	 */
	public static function get_credit_used_for_order( $order ) {
		$order_id = self::get_order_id( $order );

		if ( ! $order_id ) {
			return false; }

		$credit_amount = self::get_order_meta( $order_id, 'wt_store_credit_used' );

		if ( is_array( $credit_amount ) && ! empty( $credit_amount ) ) {
			return $credit_amount;
		}

		return false;
	}

	/**
	 *   Get total credit used for an order. Sum of credits from all store credit coupons
	 *
	 *   @since 1.0.0
	 *   @param WC_Order $order      Order object
	 *   @return float       Total credit used for the order
	 */
	public function get_total_credit_used_for_an_order( $order ) {
		$credit_used = $this->get_credit_used_for_order( $order );
		$credit      = 0;

		if ( $credit_used && is_array( $credit_used ) ) {
			$credit = array_sum( $credit_used );
		}

		return $credit;
	}

	/**
	 *  Success order statuses
	 *
	 *  @since 1.0.0
	 *  @return array  Order statuses array
	 */
	public static function success_order_statuses() {
		$order_statuses = array(
			'processing' => __( 'Processing', 'wt-gift-cards-woocommerce' ),
			'completed'  => __( 'Completed', 'wt-gift-cards-woocommerce' ),
		);

		/**
		 *  Filter to alter success order status array.
		 *
		 *  @since 1.0.0
		 *  @param array $order_statuses Order statuses
		 */
		return (array) apply_filters( 'wt_gc_coupon_success_order_statuses', $order_statuses );
	}

	/**
	 *  Failed order statuses
	 *
	 *  @since  1.0.0
	 *  @return array  Order statuses array
	 */
	public function failed_order_statuses() {
		$failed_status = array(
			'refunded'  => __( 'Refunded', 'wt-gift-cards-woocommerce' ),
			'cancelled' => __( 'Cancelled', 'wt-gift-cards-woocommerce' ),
			'failed'    => __( 'Failed', 'wt-gift-cards-woocommerce' ),

		);

		/**
		 *  Filter to alter failed order status array.
		 *
		 *  @since 1.0.0
		 *  @param array $failed_status Order statuses
		 */
		return (array) apply_filters( 'wt_gc_coupon_failed_order_statuses', $failed_status );
	}

	/**
	 * Manage the credit refund on changing order status.
	 *
	 *  @since 1.0.0
	 *  @param int      $order_id   Id of order
	 *  @param string   $old_status     Old status of order
	 *  @param string   $new_status     New status of order
	 *  @param WC_Order $order
	 */
	public function manage_credit_on_order_status_change( $order_id, $old_status, $new_status, $order ) {
		if ( in_array( $new_status, array_keys( $this->failed_order_statuses() ), true ) ) {
			/**
			 *  Remove applied store credit coupons. Default: true
			 *
			 *  @since 1.0.0
			 *  @param bool     is remove
			 *  @param WC_Order     $order
			 *  @param string   $old_status     Old status of order
			 *  @param string   $new_status     New status of order
			 */
			if ( apply_filters( 'wt_gc_remove_store_credit_from_order', false, $order, $old_status, $new_status ) ) {
				$this->reimburse_credit_value( $order );
			}

			do_action( 'wt_gc_on_order_reimburse', $order, $old_status, $new_status );
		}
	}


	/**
	 *  Reimburse Credit amount on for an order (On failed, refund etc.)
	 *
	 *  @since 1.0.0
	 *  @param WC_Order  Order object
	 */
	public function reimburse_credit_value( $order ) {
		if ( ! is_object( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$update      = false;
		$credit_used = $this->get_credit_used_for_order( $order );

		if ( $credit_used ) {
			foreach ( $credit_used as $coupon_code => $amount ) {
				if ( ! self::is_coupon_exists( $coupon_code ) ) {
					continue;
				}

				$coupon = new WC_Coupon( $coupon_code );

				if ( ! self::is_store_credit_coupon( $coupon ) ) {
					continue;
				}

				$this->reimburse_coupon( $coupon, $amount, $order );

				$order->remove_coupon( $coupon_code );

				unset( $credit_used[ $coupon_code ] );
				$update = true;
			}
		}

		if ( $update ) {
			$this->update_credit_used_for_order( $order, $credit_used, 'order_reimburse' );
		}
	}


	/**
	 *  Reimburse actions of a credit coupon
	 *
	 *  @since 1.0.0
	 *  @param WC_Coupon $coupon     Coupon object
	 *  @param float     $amount     Amount to be reimbursed
	 *  @param WC_Order  $order      Order object
	 */
	public static function reimburse_coupon( $coupon, $amount, $order ) {
		$coupon_id      = $coupon->get_id();
		$current_amount = $coupon->get_amount();
		$usage_count    = $coupon->get_usage_count();
		$usage_count    = ( ( $usage_count > 0 ) ? ( $usage_count - 1 ) : 0 );
		$new_amount     = $amount + $current_amount;

		$coupon->set_usage_count( $usage_count );
		$coupon->set_amount( $new_amount );
		$coupon->save();

		$credit_history                       = (array) get_post_meta( $coupon_id, 'wt_credit_history', true );
		$credit_history_this_order            = array(
			'order'           => $order->get_id(),
			'previous_credit' => $current_amount,
			'updated_credit'  => $new_amount,
			'credit_used'     => '-',
			'reimbursed'      => $amount,
			'comments'        => __( 'Reimburse credit value', 'wt-gift-cards-woocommerce' ),
		);
		$credit_history[ "'" . time() . "'" ] = $credit_history_this_order;
		update_post_meta( $coupon_id, 'wt_credit_history', $credit_history );
	}


	/**
	 *  Checks the current coupon exists or not
	 *
	 *  @since  1.0.0
	 *  @param  string $coupon_code  Coupon code
	 *  @return int     0 for non existing coupons otherwise coupon id
	 */
	public static function is_coupon_exists( $coupon_code ) {
		return wc_get_coupon_id_by_code( $coupon_code );
	}


	/**
	 *  Update credit used for an order
	 *
	 *  @since 1.0.0
	 *  @param mixed  $order          Order id or Wc_Order
	 *  @param array  $credit_used    An assoicative array of credit used for each store credit coupon. Eg: array('{coupon_code}' => {used_amount})
	 *  @param string $action         In which action the update request was triggered
	 */
	public function update_credit_used_for_order( $order, $credit_used, $action = 'new_order' ) {
		$order_id = self::get_order_id( $order );

		if ( ! $order_id ) {
			return false; }

		do_action( 'wt_gc_before_order_used_store_credit_updated', $credit_used, $order_id, $action );

		if ( ! is_array( $credit_used ) || empty( $credit_used ) ) {
			self::delete_order_meta( $order, 'wt_store_credit_used' );
		} else {
			self::update_order_meta( $order, 'wt_store_credit_used', $credit_used );
		}
	}


	/**
	 *  Get order id. Checks the current value and return based on it
	 *
	 *  @since 1.0.0
	 *  @param mixed $order  May be WC_Order, Order id, Or ther values
	 *  @return int|bool    Order id on success otherwise bool
	 */
	public static function get_order_id( $order ) {
		if ( is_int( $order ) ) {
			$order_id = $order;
		} else {
			if ( ! is_object( $order ) || ! is_a( $order, 'WC_Order' ) ) {
				return false;
			}

			$order_id = $order->get_id();
		}

		return $order_id;
	}


	/**
	 *  Allow usage of store credit to purchase gift cards
	 *
	 *  @since 1.0.0
	 *  @return bool    Is allowed or not
	 */
	public static function allow_to_purchase_gift_cards() {
		return wc_string_to_bool( self::get_option( 'allow_to_purchase_gift_cards' ) );
	}


	/**
	 *  Get ids of products that are not allowed for store credit.
	 *  This function will only return data when Smart coupon plugin is installed
	 *
	 *  @since 1.0.0
	 *  @return int[] Product Ids
	 */
	public static function get_store_credit_disabled_products() {
		return apply_filters( 'wt_sc_store_credit_disabled_products', array() );
	}


	/**
	 *  Get category ids of a product
	 *
	 * @since  1.0.0
	 * @param  int $product_id     Product ID.
	 * @return int[]
	 */
	public static function get_product_cat_ids( $product_id ) {
		if ( apply_filters( 'wt_gc_product_categories_with_ancestors', false, $product_id ) ) {
			return wc_get_product_cat_ids( $product_id );
		} else {
			return wc_get_product_term_ids( $product_id, 'product_cat' );
		}
	}


	/**
	 * Is WooCommerce HPOS enabled
	 *
	 * @since   1.0.0
	 * @static
	 * @return  bool    True when enabled otherwise false
	 */
	public static function is_wc_hpos_enabled() {
		if ( is_null( self::$hpos_enabled ) ) {
			if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
				self::$hpos_enabled = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			} else {
				self::$hpos_enabled = false;
			}
		}

		return self::$hpos_enabled;
	}


	/**
	 * Get WC_Order object from the given value.
	 *
	 * @since   1.0.0
	 * @static
	 * @param   int|WC_order $order      Order id or order object
	 * @return  WC_order        Order object
	 */
	public static function get_order( $order ) {
		return ( is_int( $order ) || ( is_string( $order ) && 0 < absint( $order ) ) ? wc_get_order( absint( $order ) ) : $order );
	}


	/**
	 * Get order meta value.
	 * HPOS and non-HPOS compatible
	 *
	 * @since   1.0.0
	 * @static
	 * @param   int|WC_order $order             Order id or order object
	 * @param   string       $meta_key          Meta key
	 * @param   mixed        $default_value     Optional, Default value for the meta
	 */
	public static function get_order_meta( $order, $meta_key, $default_value = '' ) {
		if ( self::is_wc_hpos_enabled() ) {
			$order = self::get_order( $order );

			if ( ! $order ) {
				return $default_value;
			}

			$meta_value = $order->get_meta( $meta_key );
			return ( ! $meta_value ? get_post_meta( $order->get_id(), $meta_key, true ) : $meta_value );

		} else {
			$order_id = self::get_order_id( $order );

			$meta_value = get_post_meta( $order_id, $meta_key, true );

			if ( ! $meta_value ) {
				$order = wc_get_order( $order_id );
				return $order ? $order->get_meta( $meta_key ) : $default_value;

			} else {
				return $meta_value;
			}
		}
	}


	/**
	 * Add order meta.
	 *
	 * @since   1.0.0
	 * @static
	 * @param   int|WC_order $order      Order id or order object
	 * @param   string       $meta_key   Meta key
	 * @param   mixed        $value      Value for meta
	 */
	public static function add_order_meta( $order, $meta_key, $value ) {
		$order = self::get_order( $order );
		$order->add_meta_data( $meta_key, $value );
		$order->save();
	}


	/**
	 * Update order meta.
	 *
	 * @since   1.0.0
	 * @static
	 * @param   int|WC_order $order      Order id or order object
	 * @param   string       $meta_key   Meta key
	 * @param   mixed        $value      Value for meta
	 */
	public static function update_order_meta( $order, $meta_key, $value ) {
		$order = self::get_order( $order );
		$order->update_meta_data( $meta_key, $value );
		$order->save();
	}


	/**
	 * Delete order meta.
	 *
	 * @since   1.0.0
	 * @static
	 * @param   int|WC_order $order      Order id or order object
	 * @param   string       $meta_key   Meta key
	 */
	public static function delete_order_meta( $order, $meta_key ) {
		$order = self::get_order( $order );
		$order->delete_meta_data( $meta_key );
		$order->save();
	}


	/**
	 *  Order meta exists
	 *
	 *  @since  1.0.0
	 *  @param  int|WC_order $order      Order id or order object
	 *  @param  string       $meta_key   Meta key
	 *  @return bool            True when order meta exists
	 */
	public static function order_meta_exists( $order, $meta_key ) {
		$order = self::get_order( $order );
		return $order->meta_exists( $meta_key );
	}


	/**
	 *  Add store credit to cart coupon types
	 *
	 *  @since  1.0.0
	 *  @param  array $coupon_types   Cart coupon types array
	 *  @return array   Cart coupon types array
	 */
	public function add_store_credit_to_cart_coupon_type( $coupon_types ) {
		$coupon_types[] = self::$store_credit_coupon_type_name;
		return $coupon_types;
	}


	/**
	 *  Add product validity for store credit coupons.
	 *
	 *  @since  1.0.0
	 *  @param  bool       $valid      Is valid or not
	 *  @param  WC_Product $product    Product object
	 *  @param  WC_Coupon  $coupon     Coupon object
	 *  @param  array      $values     Values array
	 *  @return bool        Is valid or not
	 */
	public function set_coupon_validity_for_excluded_products( $valid, $product, $coupon, $values ) {
		return ( $coupon->is_type( 'store_credit' ) ? true : $valid );
	}


	/**
	 *  Get kses allowd tags
	 *
	 *  @since  1.0.0
	 *  @return array   Array of allowed tags
	 */
	public static function get_allowed_html() {
		$allowed_html = wp_kses_allowed_html( 'post' );

		$new_allowed_html = array(
			'style' => array(
				'type' => true,
			),
		);

		/**
		 *  Filter to alter the allowed HTML
		 *
		 *  @since 1.0.0
		 *  @param array    $new_allowed_html   Allowed HTML
		 */
		$new_allowed_html = apply_filters( 'wbte_gc_kses_allowed_html', $new_allowed_html );

		if ( ! empty( $new_allowed_html ) ) {
			foreach ( $new_allowed_html as $tag => $attributes ) {
				if ( ! empty( $attributes ) && array_key_exists( $tag, $allowed_html ) ) {
					$allowed_html[ $tag ] = array_merge( $allowed_html[ $tag ], $attributes );
				} else {
					$allowed_html[ $tag ] = $attributes;
				}
			}
		}

		return $allowed_html;
	}

	/**
     *  To get price in giftcard by providing currency 
     *  param $args eg:-  array( 'coupon'   => $coupon_obj,
     *                           'currency' => $order_currency,
     *                           'order'    => $order,
     *                           'product'  => $_product );
     * 
     *  @since  1.1.1
     *  @param  array     $args     args
     *  @return string    Price HTML
     */
    public static function get_giftcard_price( $args ){

        $amount = is_null($args['product']) ? $args['coupon']->get_amount() : $args['product']->get_price();

        /**
         *  Hook to alter price on Giftcard 
         * 
         * 
         * @since 1.1.1
         */
        return  apply_filters( 'wbte_gc_alter_prices_on_giftcard', wc_price($amount, array('currency' => $args['currency'])) , $args );
    }
}
