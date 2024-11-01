<?php
/**
 * Gift card common(admin/public) area
 *
 * @link
 * @since 1.0.0
 *
 * @package  Wbte_Woocommerce_Gift_Cards_Free
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wbte_Gc_Gift_Card_Free_Common {

	public $module_base                     = 'gift_card';
	public $module_id                       = '';
	public static $module_id_static         = '';
	private static $instance                = null;
	private static $templates               = null;
	private static $visible_templates       = null;
	protected $gift_card_email_trigger_type = '';
	protected static $product_page_shortcode_name = 'wt_gc_free_gift_card_product_page'; /** @since 1.1.0 Shortcode name for gift card product page content */


	public function __construct() {
		$this->module_id        = Wbte_Woocommerce_Gift_Cards_Free_Common::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		add_filter( 'wt_gc_module_default_settings', array( $this, 'default_settings' ), 10, 2 );
		add_filter( 'wt_gc_intl_alter_fields_needed_default_val', array( $this, 'settings_needed_default_val' ), 10, 2 );
		add_filter( 'wt_gc_intl_alter_validation_rule', array( $this, 'settings_validation_rule' ), 10, 2 );

		add_filter( 'woocommerce_email_classes', array( $this, 'add_store_credit_emails' ), 11, 1 );

		add_filter( 'woocommerce_email_styles', array( $this, 'add_email_css_styles' ), 10, 2 );

		/* Send store credit email on order status change. This is applicable for store credit purchase */
		add_action( 'woocommerce_order_status_changed', array( $this, 'send_coupon_email_on_status_change' ), 10, 4 );

		/* Remove purchased gift card on order fail/cancel/refund */
		add_action( 'wt_gc_on_order_reimburse', array( $this, 'remove_purchased_store_credits_on_order_reimburse' ) );

		/**
         *  Attach PDF gift card to gift card email
         * 
         *  @since 1.1.0
         */
        add_filter('woocommerce_email_attachments', array($this, 'attach_gift_card_pdf'), 10, 4);
	}

	/**
	 *  Get Instance
	 *
	 *  @since 1.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wbte_Gc_Gift_Card_Free_Common();
		}
		return self::$instance;
	}

	/**
	 *  Default settings
	 *
	 *  @since 1.0.0
	 *  @param array                                                                         $settings   Settings array
	 *  @param string                                                                        $base_id    Module id
	 *  @param array        Return default settings of current module when module id matches
	 */
	public function default_settings( $settings, $base_id ) {
		if ( $base_id !== $this->module_id ) {
			return $settings;
		}

		$default_settings = array(
			/* general */
			'enable_gift_card_product'            => 'yes',
			'gift_card_user_options'              => array( 'email' ),
			'order_status_to_generate'            => array( 'completed' ),

			'fields_to_be_shown'                  => array_keys( self::get_customizable_giftcard_fields() ),
			'gift_card_products'                  => array(),

			/** 
             *  Attach PDF gift card
             *  @since 1.1.0   
             */
            'attach_as_pdf' => 'no',

			/* gift card template */
			'gift_card_template_to_hide'          => array(), /* templates not to show in front end */

			'product_page_title_text'             => __( 'Customise your gift card', 'wt-gift-cards-woocommerce' ),
			'product_page_templates_title_text'   => __( 'Select template', 'wt-gift-cards-woocommerce' ),
			'product_page_how_to_send_title_text' => __( 'How do you want to send it', 'wt-gift-cards-woocommerce' ),

		);

		return $default_settings;
	}


	/**
	 *  Fields like, multi select, checkbox etc will not return a POST variable when their value is empty.
	 *
	 *  @since 1.0.0
	 *  @param  array  $default_val_needed_fields   Fields list
	 *  @param string $base_id    Module id
	 *  @return array   Fields list
	 */
	public function settings_needed_default_val( $default_val_needed_fields, $base_id = '' ) {
		if ( $base_id !== $this->module_id ) {
			return $default_val_needed_fields;
		}

		return array( // value when the POST variable is not present
			'enable_gift_card_product' => 'no',
			'fields_to_be_shown'       => array(),
			'attach_as_pdf'            => 'no', /** @since 1.1.0 */
		);
	}



	/**
	 *  Validation rules for settings. If no validation specified it will validate as text input
	 *
	 *  @since  1.0.0
	 *  @param  array  $validation_rule   Validation rule list. Associative array
	 *  @param  string $base_id    Module id
	 *  @return array   Validation rule list
	 */
	public function settings_validation_rule( $validation_rule, $base_id = '' ) {
		if ( $base_id !== $this->module_id ) {
			return $validation_rule;
		}

		return array(
			'order_status_to_generate'   => array( 'type' => 'text_arr' ),
			'fields_to_be_shown'         => array( 'type' => 'text_arr' ),
			'gift_card_products'         => array( 'type' => 'int_arr' ),
			'gift_card_template_to_hide' => array( 'type' => 'text_arr' ),
		); // this is for plugin settings default. Modules can alter
	}



	/**
	 *  Get fields that are customizable on Gift card product page
	 *
	 *  @since  1.0.0
	 *  @return array   Fields list
	 */
	public static function get_customizable_giftcard_fields() {
		/* these values are using in frontend/backend */
		return array(
			'reciever_email' => __( 'Recipient email', 'wt-gift-cards-woocommerce' ),
			'reciever_name'  => __( 'Recipient name', 'wt-gift-cards-woocommerce' ),
			'sender_email'   => __( 'Sender email', 'wt-gift-cards-woocommerce' ),
			'sender_name'    => __( 'Sender name', 'wt-gift-cards-woocommerce' ),
			'message'        => __( 'Message', 'wt-gift-cards-woocommerce' ),
		);
	}



	/**
	 *  This function will provide alternate meta keys. This is to get compatibility with Webtoffee Smart Coupon plugin
	 *  Format: array(
	 *      'field_key' => 'meta_key' //old meta key for existing, otherwise new key
	 *  )
	 *
	 *  @since 1.0.0
	 *  @return array   Meta key list
	 */
	public static function alternate_meta_keys() {
		return array(
			'reciever_email' => 'wt_credit_coupon_send_to',
			'reciever_name'  => 'reciever_name',
			'sender_email'   => 'sender_email',
			'sender_name'    => 'wt_credit_coupon_from',
			'message'        => 'wt_credit_coupon_send_to_message',
			'image'          => 'wt_smart_coupon_template_image',
		);
	}


	/**
	 *  Get default template location
	 *
	 *  @since 1.0.0
	 *  @return string  Folder location of template images
	 */
	public static function get_template_location() {
		return plugin_dir_url( __FILE__ ) . 'assets/images/';
	}


	/**
	 *  Get default templates
	 *
	 *  @since 1.0.0
	 *  @return array   Default template list
	 */
	public static function get_default_gift_card_templates() {
		$base_url = self::get_template_location();

		$design_categories = array(
			'general' => __( 'General', 'wt-gift-cards-woocommerce' ),
		);

		$design_images = array(
			'general'   => array(
				'image_url'       => esc_url( $base_url . 'general-gift.jpg' ),
				'top_bg_color'    => '#f5a640',
				'bottom_bg_color' => '#f5a640',
				'category'        => $design_categories['general'],
			),
			'general_2' => array(
				'image_url'       => esc_url( $base_url . 'gift-card-2.png' ),
				'top_bg_color'    => '#a6080b',
				'bottom_bg_color' => '#a6080b',
				'category'        => $design_categories['general'],
			),
			'general_3' => array(
				'image_url'       => esc_url( $base_url . 'gift-card-3.png' ),
				'top_bg_color'    => '#2e2621',
				'bottom_bg_color' => '#2e2621',
				'category'        => $design_categories['general'],
			),
			'general_4' => array(
				'image_url'       => esc_url( $base_url . 'gift-card-4.png' ),
				'top_bg_color'    => '#c7bba4',
				'bottom_bg_color' => '#c7bba4',
				'category'        => $design_categories['general'],
			),
		);

		return apply_filters( 'wt_gc_gift_card_template_images', $design_images );
	}


	/**
	 *  Get all available gift card templates. Including default and user added
	 *
	 *  @since      1.0.0
	 *  @param      boolean $visible_only    return only visible templates
	 *  @return     array       $design_images   list of templates
	 */
	public static function get_gift_card_templates( $visible_only = false ) {
		if ( $visible_only && ! is_null( self::$visible_templates ) ) {
			return self::$visible_templates;
		}

		if ( ! $visible_only && ! is_null( self::$templates ) ) {
			return self::$templates;
		}

		$design_images        = self::get_default_gift_card_templates();
		$hidden_template_list = self::get_hidden_templates();

		foreach ( $design_images as $template_k => $template_v ) {
			$template_v['is_custom']      = false;
			$template_v['is_hidden']      = in_array( $template_k, $hidden_template_list );
			$design_images[ $template_k ] = $template_v; /* update the main list */

			if ( $visible_only && $template_v['is_hidden'] ) {
				unset( $design_images[ $template_k ] );
			}
		}

		$design_images = apply_filters( 'wt_gc_gift_card_templates', $design_images );

		if ( $visible_only ) {
			self::$visible_templates = $design_images;
		}

		if ( ! $visible_only ) {
			self::$templates = $design_images;
		}

		return $design_images;
	}


	/**
	 *  Get template by slug
	 *
	 *  @since 1.0.0
	 *  @param      string $template_slug   Template slug
	 *  @return     array      templates data array. Empty array if template not found
	 */
	public static function get_gift_card_template( $template_slug ) {
		$templates = self::get_gift_card_templates();
		$templates = ( is_array( $templates ) ? $templates : array() );

		return ( isset( $templates[ $template_slug ] ) ? $templates[ $template_slug ] : self::get_dummy_template() );
	}


	/**
	 *  Get hidden templates list
	 *
	 *  @since 1.0.0
	 *  @return     array       template slug array
	 */
	public static function get_hidden_templates() {
		return Wbte_Woocommerce_Gift_Cards_Free_Common::get_option( 'gift_card_template_to_hide', self::$module_id_static );
	}


	/**
	 *  Is gift card products enabled
	 *
	 *  @since 1.0.0
	 *  @return     bool    Is gift card products enabled
	 */
	public static function is_gift_card_products_enabled() {
		return wc_string_to_bool( Wbte_Woocommerce_Gift_Cards_Free_Common::get_option( 'enable_gift_card_product', self::$module_id_static ) );
	}


	/**
	 *  Get message for gift card
	 *
	 *  @since 1.0.0
	 *  @param      string $template   Template slug
	 *  @return      string     Gift card message. Default: Empty string
	 */
	public static function get_gift_card_message( $template ) {
		return apply_filters( 'wt_gc_gift_card_message', '', $template );
	}


	/**
	 *  Register Store credit email class to WC email
	 *
	 *  @since 1.0.0
	 *  @param     array       email classes
	 *  @return    array       email classes
	 */
	public function add_store_credit_emails( $email_classes ) {
		include_once plugin_dir_path( __FILE__ ) . 'classes/class-wbte-woocommerce-gift-cards-free-email.php';
		$email_classes['Wbte_Woocommerce_Gift_Cards_Free_Email'] = new Wbte_Woocommerce_Gift_Cards_Free_Email();

		return $email_classes;
	}


	/**
	 *  Inject email CSS
	 *
	 *  @since 1.0.0
	 *  @param     string $style   Style string
	 *  @param     Object $email   Email object
	 *  @return    string      Style string
	 */
	public function add_email_css_styles( $style, $email ) {
		if ( 'wt_gc_gift_card' === $email->id ) {
			$style .= ' .wt_gc_email_wrapper{ margin:0 auto; max-width:600px; background:#ffffff; line-height:22px; font-size:14px; }  
                        .wt_gc_email_top{width:100%; height:auto; text-align:left;}
                        .wt_gc_reciever_name_block, .wt_gc_from_name_block{ margin-top:25px; }                       
                        .wt_gc_email_img{ width:100%; height:auto; margin-top:25px;}
                        .wt_gc_email_img img{ width:100%;}
                        .wt_gc_email_bottom{ width:100%; height:auto; text-align:left; margin-top:25px; padding-bottom:15px;}
                        .wt_gc_email_bottom table, .wt_gc_email_bottom td, .wt_gc_email_bottom tr{ border:none; }
                        .wt_gc_email_coupon_code_block{ text-align:left; }
                        .wt_gc_email_coupon_code{ font-size:18px; font-weight:400; margin-top:7px; }                       
                        .wt_gift_coupon_additional_content{ width:100%; height:auto; text-align:left; margin-top:70px; }
                        .wt_gc_email_message{width:100%; height:auto; border-left:solid 5px #DEDEDE; font-style:italic; margin-top:25px; padding:5px 15px; }
                        .wt_gc_email_price_expiry_block{ text-align:right;}
                        .wt_gc_email_coupon_price{ font-size:28px; font-weight:700; margin-top:7px; }
                        .wt_gc_email_coupon_expiry{ margin-top:7px;  }
                        ';
		}

		return $style;
	}


	/**
	 *  Generate gift card email preview.
	 *
	 *  @since 1.0.0
	 *
	 *  @return string  Email content HTML
	 */
	public static function get_gift_card_email_preview( $product_suggests = array() ) {
		$wc_emails = WC_Emails::instance();
		$emails    = $wc_emails->get_emails();

		$current_email = $emails['Wbte_Woocommerce_Gift_Cards_Free_Email'];

		/*The Woo Way to Do Things Need Exception Handling Edge Cases*/
		add_filter( 'woocommerce_email_recipient_' . $current_email->id, '__return_empty_string' );

		$user         = wp_get_current_user();
		$display_name = ( $user ? $user->display_name : '' );

		$credit_email_args = array(
			'send_to'     => '',
			'coupon_id'   => 0,
			'coupon_code' => 'XXXX-XXXX-XXXX-XXXX',
			'message'     => '',
			'from_name'   => $display_name,
			'extended'    => true,
			'by_admin'    => is_admin(),
		);
		$current_email->trigger( $credit_email_args );

		$content = $current_email->get_content_html();
		return apply_filters( 'woocommerce_mail_content', $current_email->style_inline( $content ) );
	}


	/**
	 *  Get product meta list
	 *  Format: array(
	 *  'meta_key' => array(
	 *          'default'   => 'Default value',
	 *          'type'      => 'Value type',
	 *      )
	 *  )
	 *
	 *  @since 1.0.0
	 *  @return array  Meta info data list
	 */
	public static function get_product_meta_list() {
		return apply_filters(
			'wt_gc_alter_gift_card_product_meta_list',
			array(
				'_wt_gc_purchase_options' => array(
					'default' => array( 'predefined' ), /* default value */
					'type'    => 'text_arr', /* value type */
				),
				'_wt_gc_amounts'          => array(
					'default' => '',
					'type'    => 'text',
				),
				'_wt_gc_enable_template'  => array(
					'default' => 'yes',
					'type'    => 'text',
				),
				'_wt_gc_templates'        => array(
					'default' => array_keys( self::get_gift_card_templates( true ) ),
					'type'    => 'text_arr',
				),

			)
		);
	}


	/**
	 *  Get all metas of a gift card product
	 *
	 *  @since 1.0.0
	 *  @param  int $product_id  Id of product
	 *  @return array   array of product metas and value, If meta not exists, default values will return
	 */
	public static function get_product_metas( $product_id ) {
		$out = array();

		foreach ( self::get_product_meta_list() as $meta_key => $meta_data ) {
			$out[ $meta_key ] = array(
				'value'        => self::get_product_meta( $product_id, $meta_key, $meta_data['default'] ),
				'type'         => $meta_data['type'],
				'default'      => $meta_data['default'],
				'multi_select' => isset( $meta_data['multi_select'] ),
			);
		}

		return $out;
	}



	/**
	 *  Get a specific meta of gift card product
	 *
	 *  @since 1.0.0
	 *  @param  int    $product_id             Id of product
	 *  @param  string $meta_key               Meta key
	 *  @param  mixed  $default_value          Default value (Optional).
	 *  @return  mixed      Value of the meta. If meta not exists default value will return
	 */
	public static function get_product_meta( $product_id, $meta_key, $default_value = null ) {
		if ( is_null( $default_value ) ) {
			$meta_list     = self::get_product_meta_list();
			$default_value = ( isset( $meta_list[ $meta_key ] ) && isset( $meta_list[ $meta_key ]['default'] ) ? $meta_list[ $meta_key ]['default'] : '' );
		}

		if ( 0 === $product_id ) {
			return $default_value;
		}

		return ( metadata_exists( 'post', $product_id, $meta_key ) ? get_post_meta( $product_id, $meta_key, true ) : $default_value );
	}


	/**
	 *  Get gift card product ids
	 *
	 *  @since 1.0.0
	 *  @return  int[]      Gift card product ids
	 */
	public static function get_gift_card_products() {
		$product_ids = (array) Wbte_Woocommerce_Gift_Cards_Free_Common::get_option( 'gift_card_products', self::$module_id_static );
		return array_filter( array_unique( $product_ids ) );
	}


	/**
	 *  Is templates enabled for the given gift card product
	 *
	 *  @since 1.0.0
	 *  @param      int $product_id             Id of product
	 *  @return     bool       Is templates enabled for the given gift card product
	 */
	public static function is_templates_enabled( $product_id ) {
		return wc_string_to_bool( self::get_product_meta( $product_id, '_wt_gc_enable_template' ) );
	}


	/**
	 *  Get templates of a product
	 *
	 *  @since 1.0.0
	 *  @param int      $product_id     Id of product
	 *  @param string[] $templates     slug of templates
	 */
	public static function get_product_templates( $product_id ) {
		return (array) self::get_product_meta( $product_id, '_wt_gc_templates' );
	}


	/**
	 *  Checks the give product is a gift card product
	 *
	 *  @since 1.0.0
	 *  @param      int $product_id             Id of product
	 *  @return     bool        Is a gift card product
	 */
	public static function is_gift_card_product( $product_id ) {
        return ( metadata_exists( 'post', $product_id, '_wt_gc_gift_card_product' ) && get_post_meta( $product_id, '_wt_gc_gift_card_product', true ) );
	}


	/**
	 *  Get featured image of a gift card product
	 *
	 *  @since 1.0.0
	 *  @param      WC_Product $product    Product object
	 *  @return     array|false     Array of image data, or boolean false if no image is available.
	 */
	public static function get_product_image( $product ) {
		$image = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );

		if ( ! $image ) {
			$dimensions = wc_get_image_size( 'woocommerce_thumbnail' );
			$image      = array( wc_placeholder_img_src( 'woocommerce_thumbnail' ), $dimensions['width'], $dimensions['height'], false );
		}

		return $image;
	}


	/**
	 *  Order status to sent/generate gift card
	 *
	 *  @since 1.0.0
	 *  @param      WC_Order $order    Order object
	 *  @return     array       Array of statuses
	 */
	public static function get_order_status_for_gift_card_email( $order ) {
		$status     = Wbte_Woocommerce_Gift_Cards_Free_Common::get_option( 'order_status_to_generate', self::$module_id_static );
		$status_arr = is_array( $status ) ? $status : array( $status );

		return apply_filters( 'wt_gc_alter_purchased_credit_sending_status', $status_arr, $order );
	}


	/**
	 *  Available Gift card options
	 *
	 *  @since 1.0.0
	 *  @return     array       Array of user options
	 */
	public static function get_gift_card_user_options() {
		return array(
			'email' => __( 'Email gift card', 'wt-gift-cards-woocommerce' ),
		);
	}



	/**
	 *  Gift card option labels for users
	 *
	 *  @since 1.0.0
	 *  @return     array       Array of user option labels
	 */
	public static function get_gift_card_user_option_labels() {
		return apply_filters(
			'wt_gc_alter_gift_card_user_option_labels',
			array(
				'email' => __( 'Email to recipient', 'wt-gift-cards-woocommerce' ),
			)
		);
	}


	/**
	 *  Enabled gift card options for users
	 *
	 *  @since 1.0.0
	 *  @return     array       Array of user options
	 */
	public static function get_enabled_gift_card_user_options() {
		return (array) Wbte_Woocommerce_Gift_Cards_Free_Common::get_option( 'gift_card_user_options', self::$module_id_static );
	}


	/**
	 *  Create gift card coupon
	 *
	 *  @since 1.0.0
	 *  @param     float  $credit_value       Credit amount
	 *  @param     string $description        Description for coupon
	 *  @return    array        Created coupon data array
	 */
	public function create_gift_card_coupon( $credit_value, $description = '' ) {
		/* generate random coupon code */
		$coupon_code = self::generate_random_coupon_code();

		/* create a coupon */
		$coupon_args = array(
			'post_title'   => $coupon_code,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_type'    => 'shop_coupon',
		);

		$coupon_id = wp_insert_post( $coupon_args );
		add_post_meta( $coupon_id, '_wt_gc_auto_generated_store_credit_coupon', true );

		$coupon_obj = new WC_Coupon( $coupon_id );
		$coupon_obj->set_amount( $credit_value );
		$coupon_obj->set_discount_type( 'store_credit' );
		$coupon_obj->set_description( $description );
		$coupon_obj->save();

		return array(
			'coupon_id'   => $coupon_id,
			'coupon_code' => $coupon_code,
			'coupon_obj'  => $coupon_obj,
		);
	}

	/**
	 *  Check the generated coupon is send to customer/activated
	 *
	 *  @since 1.0.0
	 *  @param     in $coupon_id       Coupon id
	 *  @return    bool      Is send to customer/activated
	 */
	public function is_generated_coupon_activated( $coupon_id ) {
		/**
		 *  For smart coupon compatibility
		 */
		if ( get_post_meta( $coupon_id, '_wt_sc_send_the_generated_credit', true ) ) {
			return true;
		}

		if ( get_post_meta( $coupon_id, '_wt_smart_coupon_credit_activated', true ) ) {
			return true;
		}

		return false;
	}


	/**
	 *  Is user choosed email option while purchasing gift card
	 *
	 *  @since 1.0.0
	 *  @param  array $coupon_template_data    Gift card purchase form data
	 *  @return  bool    Is user choosed email option
	 */
	public static function is_email_action_choosed( $coupon_template_data ) {
		// adding an OR condition to get compatibility for already scheduled emails via smart coupon plugin
		return ( isset( $coupon_template_data['user_action'] ) && 'email' === $coupon_template_data['user_action'] )
							|| ! isset( $coupon_template_data['user_action'] );
	}


	/**
	 *  Send credit coupon email to the customer on changing order status. This is applicable for store credit purchase
	 *
	 *  @since  1.0.0
	 *  @param  int      $order_id       Id of order
	 *  @param  string   $old_status     Old status
	 *  @param  string   $new_status     New status
	 *  @param  WC_Order $order          Order object
	 */
	public function send_coupon_email_on_status_change( $order_id, $old_status, $new_status, $order ) {
		$coupon_attached = Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $order_id, 'wt_credit_coupons' ); // coupons attached to the order

		if ( ! empty( $coupon_attached ) ) {
			$status_arr = self::get_order_status_for_gift_card_email( $order );

			if ( in_array( $new_status, $status_arr ) ) {
				$order       = new WC_Order( $order_id );
				$order_items = $order->get_items();

				foreach ( $order_items as $order_item_id => $order_item ) {
					$coupons_generated = $order_item->get_meta( 'wt_credit_coupon_generated' );

					if ( empty( $coupons_generated ) || ! is_array( $coupons_generated ) ) {
						continue;
					}

					$coupon_template_details = $order_item->get_meta( 'wt_credit_coupon_template_details' );
					$coupon_template_details = ( ! empty( $coupon_template_details ) && is_array( $coupon_template_details ) ? $coupon_template_details : array() );

					foreach ( $coupons_generated as $generated_coupon ) {
						$coupon_id  = $generated_coupon['coupon_id'];
						$coupon_obj = new WC_Coupon( $coupon_id );

						if ( ! $coupon_obj ) {
							continue;
						}

						if ( $this->is_generated_coupon_activated( $coupon_id ) ) {
							continue;
						}

						$coupon_template_data = isset( $coupon_template_details[ $coupon_id ] ) && is_array( $coupon_template_details[ $coupon_id ] ) ? $coupon_template_details[ $coupon_id ] : array();

						if ( self::is_email_action_choosed( $coupon_template_data ) ) {
							// send it now
							$this->gift_card_email_trigger_type = 'status_reached';
							$this->do_send_mail( $order_id, $coupon_id );
						} else {
							// status reached so activate the coupon
							update_post_meta( $coupon_id, '_wt_smart_coupon_credit_activated', true );
						}
					}
				}
			}
		}
	}


	/**
	 *  Check and send store credit purchase email
	 *
	 *  @since  1.0.0
	 *  @param  int  $order_id     Id of order
	 *  @param  int  $coupon_id    Id of coupon
	 *  @param  bool $force_send   Force send
	 *  @return bool    Email send or not
	 */
	public function do_send_mail( $order_id, $coupon_id = 0, $force_send = false ) {
		$coupons = Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $order_id, 'wt_credit_coupons' );

		if ( empty( $coupons ) ) {
			return false;
		}

		return $this->send_store_credit_email_for_order( $order_id, $coupon_id, $force_send );
	}


	/**
	 *  Send store credit email for gift card store credit orders.
	 *
	 *  @since 1.0.0
	 *  @access public
	 *  @param int  $order_id  Id of order
	 *  @param int  $coupon_id  Id of coupon
	 *  @param bool $force_send  Force send
	 *  @return bool Email send or not
	 */
	public function send_store_credit_email_for_order( $order_id, $coupon_id = 0, $force_send = false ) {
		$email_send  = false;
		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();

		foreach ( $order_items as $order_item ) {
			$coupons_generated = $order_item->get_meta( 'wt_credit_coupon_generated' );

			if ( empty( $coupons_generated ) || ! is_array( $coupons_generated ) ) {
				continue;
			}

			$coupon_template_details = $order_item->get_meta( 'wt_credit_coupon_template_details' );
			$coupon_template_details = ( ! empty( $coupon_template_details ) && is_array( $coupon_template_details ) ? $coupon_template_details : array() );

			foreach ( $coupons_generated as $generated_coupon ) {
				$generated_coupon_id = $generated_coupon['coupon_id'];

				if ( $coupon_id > 0 && $coupon_id !== $generated_coupon_id ) {
					continue;
				}

				if ( ! isset( $coupon_template_details[ $generated_coupon_id ] ) ) {
					continue;
				}

				$coupon_obj = new WC_Coupon( $generated_coupon_id );

				if ( ! $coupon_obj ) {
					continue;
				}

				$coupon_template_data = $coupon_template_details[ $generated_coupon_id ];

				if ( ! self::is_email_action_choosed( $coupon_template_data ) ) {
					continue;
				}

				$coupon_id = isset( $coupon_template_data['coupon_id'] ) ? $coupon_template_data['coupon_id'] : '';
				$template  = isset( $coupon_template_data['wt_smart_coupon_template_image'] ) ? $coupon_template_data['wt_smart_coupon_template_image'] : '';
				$extended  = isset( $coupon_template_data['extended'] ) ? $coupon_template_data['extended'] : false;

				if ( ! $extended && '' !== $template ) {
					$extended = true;
				}

				$send_now = apply_filters( 'wt_send_credit_coupon_on_order_success_status', true, $order_id, $coupon_template_data );

				if ( $send_now && ! empty( $coupon_id ) ) {
					$credit_email_args = array(
						'send_to'       => ( isset( $coupon_template_data['wt_credit_coupon_send_to'] ) ? $coupon_template_data['wt_credit_coupon_send_to'] : '' ),
						'coupon_id'     => $coupon_id,
						'message'       => ( isset( $coupon_template_data['wt_credit_coupon_send_to_message'] ) ? $coupon_template_data['wt_credit_coupon_send_to_message'] : '' ),
						'order_id'      => $order_id,
						'template'      => $template,
						'from_name'     => ( isset( $coupon_template_data['wt_credit_coupon_from'] ) ? $coupon_template_data['wt_credit_coupon_from'] : $order->get_billing_email() ),
						'reciever_name' => ( isset( $coupon_template_data['reciever_name'] ) ? $coupon_template_data['reciever_name'] : '' ),
						'extended'      => $extended,
					);

					WC()->mailer();
					do_action( 'wt_gc_send_gift_card_coupon_to_customer', $credit_email_args );

					$email_send = true;

					update_post_meta( $coupon_id, '_wt_smart_coupon_credit_activated', true );
					update_post_meta( $coupon_id, '_wt_sc_send_the_generated_credit', true );
					update_post_meta( $coupon_id, '_wt_sc_send_date_gmt', current_time( 'mysql', true ) ); /** Update the last sent time in GMT */

					/**
					 *  Add customized order notes
					 */
					$coupon_code    = '<b>' . wc_sanitize_coupon_code( $coupon_obj->get_code() ) . '</b>';
					$order_note_msg = '';

					switch ( $this->gift_card_email_trigger_type ) {
						case 'resend':
							/* translators: %s coupon code. */
							$order_note_msg = sprintf( __( 'Gift card %s resent to recipient using the Resend button.', 'wt-gift-cards-woocommerce' ), $coupon_code );
							break;

						case 'force_send':
							/* translators: %s coupon code. */
							$order_note_msg = sprintf( __( 'Gift card %s emailed to recipient manually using the force sent button.', 'wt-gift-cards-woocommerce' ), $coupon_code );
							break;

						default: // applicable for `status_reached` and `send`
							/* translators: %s coupon code. */
							$order_note_msg = sprintf( __( 'Gift card %s emailed to recipient.', 'wt-gift-cards-woocommerce' ), $coupon_code );
							break;
					}

					$order->add_order_note( $order_note_msg ); // add note

				}
			}
		}

		/**
		 *  Check and change order status to completed when all coupons are mailed.
		 */
		if ( 'completed' !== $order->get_status() // Current order status is not `completed`
			&& apply_filters( 'wt_gc_change_order_status_to_completed_on_giftcard_email', true ) // An extra filter hook to control the automatic completion
		) {

			// Current order is valid for automatic completion
			$is_valid_for_automatic_completion = true;

			foreach ( $order_items as $order_item ) {

				$coupons_generated = $order_item->get_meta( 'wt_credit_coupon_generated' );

				// Not a gift cart order item
				if ( empty( $coupons_generated ) || ! is_array( $coupons_generated ) ) {
					// A non gift card product found so skip
					$is_valid_for_automatic_completion = false;
					break;
				}

				foreach ( $coupons_generated as $generated_coupon ) {

					$coupon_id = $generated_coupon['coupon_id'];

					// Email is not triggered for the current coupon
					if ( ! get_post_meta( $coupon_id, '_wt_sc_send_the_generated_credit', true ) ) {
						$is_valid_for_automatic_completion = false;
						break 2; // Break both loops
					}
				}
			}

			// Order is valid for automatic completion
			if ( $is_valid_for_automatic_completion ) {
				$order->update_status( 'completed', '' );
			}
		}

		return $email_send;
	}


	/**
	 *  Remove purchased gift card on order fail/cancel/refund
	 *
	 *  @since 1.0.0
	 *  @access public
	 *  @param WC_Order Order object
	 *  @param string                $old_status     Order old status
	 *  @param string                $new_status     Order new status
	 *  @return bool    Email send or not
	 */
	public function remove_purchased_store_credits_on_order_reimburse( $order ) {
		if ( ! is_object( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$order_id        = $order->get_id();
		$coupon_attached = Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $order_id, 'wt_credit_coupon_template_details' );

		if ( empty( $coupon_attached ) ) {
			$coupon_attached = Wbte_Woocommerce_Gift_Cards_Free_Common::get_order_meta( $order_id, 'wt_credit_coupons' );
		}

		$coupons = maybe_unserialize( $coupon_attached );

		if ( ! empty( $coupons ) && is_array( $coupons ) ) {
			foreach ( $coupons as $coupon_item ) {
				$coupon_id = ( isset( $coupon_item['coupon_id'] ) ? $coupon_item['coupon_id'] : '' );
				if ( $coupon_id ) {
					wp_delete_post( $coupon_id );
				}
			}

			Wbte_Woocommerce_Gift_Cards_Free_Common::delete_order_meta( $order_id, 'wt_credit_coupon_template_details' );
			Wbte_Woocommerce_Gift_Cards_Free_Common::delete_order_meta( $order_id, 'wt_credit_coupons' );
		}
	}


	/**
	 *  Generate unique random coupon code
	 *  Format: XXXX-XXXX-XXXX-XXXX
	 *
	 *  @since 1.0.0
	 *  @return string Coupon code
	 */
	private static function generate_random_coupon_code() {
		global $wpdb;

		$random_coupon = '';
		$length        = 16;
		$charset       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$count         = strlen( $charset );

		while ( $length-- ) {
			$random_coupon .= $charset[ wp_rand( 0, $count - 1 ) ];

			if ( $length > 0 && 0 === ( $length % 4 ) ) {
				$random_coupon .= '-';
			}
		}

		while ( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_coupon' AND post_status ='publish' AND post_title = %s ", $random_coupon ) ) ) {
			return self::generate_random_coupon_code();
		}

		return $random_coupon;
	}


	/**
	 *  Get product thumbnail URL.
	 *  If the product has no thumbnail and a variation product, then check for parent product's thumbnail
	 *
	 *  @since  1.0.0
	 *  @param  int $product_id     Product id.
	 *  @return string      Thumbnail URL if found otherwise empty string
	 */
	public static function get_product_image_url( $product_id ) {
		$_product = wc_get_product( $product_id );

		$image = wp_get_attachment_image_src( $_product->get_image_id(), 'woocommerce_thumbnail' );

		if ( ! $image ) {
			$parent_product = wc_get_product( $_product->get_parent_id() );

			if ( $parent_product ) {
				$image = wp_get_attachment_image_src( $parent_product->get_image_id(), 'woocommerce_thumbnail' );
			}
		}

		if ( ! $image ) {
			$dimensions = wc_get_image_size( 'woocommerce_thumbnail' );
			$image      = array( wc_placeholder_img_src( 'woocommerce_thumbnail' ), $dimensions['width'], $dimensions['height'], false );
		}

		return ( $image && is_array( $image ) && isset( $image[0] ) ? $image[0] : '' );
	}


	/**
	 *  Get product by id
	 *
	 *  @since  1.0.0
	 *  @param  int $product_id  Id of product
	 *  @return WC_Product|null
	 */
	public function get_product_by_id( $product_id ) {
		$_product = wc_get_product( absint( $product_id ) );

		if ( ! is_null( $_product ) && false !== $_product ) {
			return $_product;
		}

		return null;
	}


	/**
	 *  Dummy template data.
	 *
	 *  @since  1.0.0
	 *  @return array    Template data
	 */
	public static function get_dummy_template() {
		/**
		 *  Filter to alter the dummy template data
		 *
		 *  @since 1.0.0
		 *  @param array    Template data
		 */
		return (array) apply_filters(
			'wt_gc_alter_dummy_template',
			array(
				'image_url'       => esc_url( self::get_template_location() . 'no-image.png' ),
				'top_bg_color'    => '#fff',
				'bottom_bg_color' => '#fff',
				'category'        => 'dummy_template',
			)
		);
	}


	/**
	 *  Get available templates for the current product. Removes hidden templates
	 *
	 *  @since  1.0.0
	 *  @param  int $product_id     ID of the product
	 *  @return array   An associative array of templates data
	 */
	public function get_visible_templates( $product_id ) {
		$templates         = self::get_gift_card_templates( true ); // visible templates
		$product_templates = self::get_product_templates( $product_id ); // product template slugs

		foreach ( $templates as $template_k => $template_v ) {
			if ( ! in_array( $template_k, $product_templates ) ) {
				unset( $templates[ $template_k ] );
			}
		}

		return apply_filters( 'wt_gc_alter_gift_card_product_visible_templates', $templates, $product_id );
	}

	/**
     *  Get mPDF addon plugin file name with path
     * 
     *  @since 1.1.0
     *  @return string    plugin file path
     */
    public static function get_mpdf_plugin_path()
    {
        return 'mpdf-addon-for-pdf-invoices/wt-woocommerce-packing-list-mpdf.php';
    }

	/**
     *  Get mPDF addon plugin slug
     * 
     *  @since 1.1.0
     *  @return string    plugin slug
     */
    public static function get_mpdf_plugin_slug()
    {
        return dirname(self::get_mpdf_plugin_path());
    }

	/**
     *  Get mPDF addon plugin page URL in wordpress.org
     * 
     *  @since 1.1.0
     *  @return string    plugin page URL
     */
    public static function get_mpdf_plugin_url()
    {
        return 'https://wordpress.org/plugins/' . self::get_mpdf_plugin_slug() . '/';
    }


	/**
     *  is mPDF addon plugin folder exists in the plugin directory.
     * 
     *  @since 1.1.0
     *  @return bool    true when plugin files are available otherwise false
     */
    public static function is_mpdf_plugin_exists()
    {
        return file_exists(WP_PLUGIN_DIR . '/' . self::get_mpdf_plugin_path());
    }


    /**
     *  mPDF addon plugin is active.
     * 
     *  @since 1.1.0
     *  @return bool    true when plugin is active otherwise false
     */
    public static function is_mpdf_plugin_active()
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        return is_plugin_active(self::get_mpdf_plugin_path());
    }


	/**
	 *  Get the minimum required version number of mPDF addon plugin.
	 * 
	 *  @since 1.1.0
	 *  @return string    Returns the minimum required version number
	 */
    public static function get_required_min_mpdf_version()
    {
        return '1.0.7';
    }

	/**
     *  Get installed version of mPDF addon plugin. The plugin not required to be activated
     * 
     *  @since 1.1.0
     *  @return string|bool    Returns the version number if the plugin is installed. Otherwise false
     */
    public static function get_installed_mpdf_version()
    {
        if(self::is_mpdf_plugin_exists())
        {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . self::get_mpdf_plugin_path());

            return $plugin_data['Version'];
        }

        return false;
    }

	/**
     *  Is required version of mPDF addon plugin was installed
     * 
     *  @since  1.1.0
     *  @access public
     *  @static 
     *  @return bool    true when minimum required version is installed(may or may not activated)
     */
    public static function is_required_mpdf_version_installed()
    {
        $installed_version = self::get_installed_mpdf_version();

        if($installed_version && version_compare($installed_version, self::get_required_min_mpdf_version(), ">="))
        {
            return true;
        }

        return false;
    }

	 /**
     *  Get directory to store PDF attachments. 
     *  Creates the directory if not exists
     * 
     *  @since  1.1.0
     *  @param  string              $out    Optional, the data to be returned. Eg: path, url etc. If not specified, both values are returned.
     *  @return array|string|bool   array with directory URL and path, If output specified string will return, False when `not exists`/`unable to create` the directory
     */
    public static function get_temp_dir($out = '')
    {
        $upload = wp_upload_dir();
        $upload_dir = $upload['basedir'];
        $upload_url = $upload['baseurl'];
        //plugin subfolder
        $upload_dir = $upload_dir.'/wt-gift-cards-woocommerce';
        $upload_url = $upload_url.'/wt-gift-cards-woocommerce';

        if(!is_dir($upload_dir))
        {
            @mkdir($upload_dir, 0700); //create it if not exists
        }

        if(is_dir($upload_dir)) //secure the directory
        {
            $files_to_create = array('.htaccess' => 'deny from all', 'index.php'=>'<?php // Silence is golden');
            
            foreach($files_to_create as $file => $file_content)
            {
                if(!file_exists($upload_dir.'/'.$file))
                {
                    $fh = @fopen($upload_dir.'/'.$file, "w");
                    
                    if(is_resource($fh))
                    {
                        fwrite($fh, $file_content);
                        fclose($fh);
                    }
                }
            }
        }else
        {
            return false;
        }
        
        if('path' === $out)
        {
            return $upload_dir;

        }elseif('url' === $out)
        {
            return $upload_url; 
        }else
        {
            return array(
                'path' => $upload_dir,
                'url'  => $upload_url,
            );
        }
    }


    /**
     *  Is PDF attachment enabled
     *  
     *  @since  1.1.0
     *  @return bool       true when enabled otherwise false
     */
    public static function is_pdf_attachment_enabled()
    {
        return wc_string_to_bool(Wbte_Woocommerce_Gift_Cards_Free_Common::get_option('attach_as_pdf', self::$module_id_static));
    }


    /**
     *  Generate and attach `PDF gift card` to gift card email
     * 
     *  @since  1.1.0
     *  @param  array       $attachments        Array of attachments
     *  @param  string      $mail_class_id      Email class id
     *  @param  object      $object_in_mail     Object in email
     *  @param  WC_Email    $wc_email_object    Email class
     *  @return array       Array of attachments
     */
    public function attach_gift_card_pdf($attachments, $mail_class_id, $object_in_mail, $wc_email_object)
    {
        if('wt_gc_gift_card' === $mail_class_id  
            && self::is_pdf_attachment_enabled() 
            && self::is_mpdf_plugin_active() 
            && self::is_required_mpdf_version_installed() 
            && ($temp_dir = self::get_temp_dir('path')) 
        ) //PDF attachment enabled, mPDF addon is active, latest version of mPDF addon, temporary directory exists
        {
            $args = $wc_email_object->data_arr; //data array from Email object. This contains all data releated to this mail
            $coupon_id = maybe_unserialize($args['coupon_id']);
            $coupon_obj = new WC_Coupon(is_array($coupon_id) ? $coupon_id[0] : $coupon_id);
            $coupon_code = $coupon_obj->get_code();
            
            $mpdf_info = apply_filters('wt_pklist_mpdf_get_lib_info', array()); //get mPDF library info

            if($coupon_code && is_array($mpdf_info) && isset($mpdf_info['file']) && file_exists($mpdf_info['file'])) //coupon exists, mPDF class file exists
            {
                include_once $mpdf_info['file']; //include the mPDF class file

                if(class_exists($mpdf_info['class'])) //mPDF class exists
                {
                    ob_start();
                    include 'data/data.template.php';
                    $html = ob_get_clean();

                    /**
                     *  Alter the HTML before generating PDF
                     *  
                     *  @since 1.1.0
                     *  @param string       $html           HTML
                     *  @param WC_Coupon    $coupon_obj     Coupon object
                     *  @param array        $args           Array of arguments. Including coupon id, reciever email id etc
                     */
                    $html = apply_filters('wt_gc_alter_giftcard_pdf_html', $html, $coupon_obj, $args);

                    $class_name = $mpdf_info['class'];
                    $pdf_name = $temp_dir.'/'.$coupon_code.'.pdf'; //prepare PDF name based on coupon code
                    $mdf = new $class_name();

                    if($mdf->generate_pdf(array('html'=>$html, 'file_path' => $pdf_name, 'action' => 'save'))) /* PDF was successfully generated */
                    {
                        $attachments[] = $pdf_name; //add the PDF path to attachments array
                    }               
                }
            }    
        }
		
        return $attachments;
    }
}

Wbte_Gc_Gift_Card_Free_Common::get_instance();
