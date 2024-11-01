<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Webtoffee Security Library
 *
 * Includes Data sanitization, Access checking
 *
 * @author     WebToffee <info@webtoffee.com>
 */

if ( ! class_exists( 'Wbte_Gc_Free_Security_Helper' ) ) {

	class Wbte_Gc_Free_Security_Helper {


		/**
		 *   Data sanitization function.
		 *
		 *   @param mixed  $val value to sanitize
		 *   @param string $key array key in the validation rule
		 *   @param array  $validation_rule array of validation rules. Eg: array('field_key' => array('type' => 'textarea'))
		 *   @return mixed sanitized value
		 */
		public static function sanitize_data( $val, $key, $validation_rule = array() ) {
			if ( isset( $validation_rule[ $key ] ) && is_array( $validation_rule[ $key ] ) ) {
				if ( isset( $validation_rule[ $key ]['type'] ) ) {
					$val = self::sanitize_item( $val, $validation_rule[ $key ]['type'] );

					if ( '_arr' === substr( $validation_rule[ $key ]['type'], -4 ) ) {
						$val = (array) $val;
					}
				}
			} else // if no rule is specified then it will be treated as text
			{
				$val = self::sanitize_item( $val, 'text' );
			}
			return $val;
		}


		/**
		 *   Sanitize individual data item
		 *
		 *   @param mixed  $val value to sanitize
		 *   @param string $type value type
		 *   @return mixed sanitized value
		 */
		public static function sanitize_item( $val, $type = '' ) {
			switch ( $type ) {
				case 'text':
					$val = sanitize_text_field( $val );
					break;
				case 'text_arr':
					$val = self::sanitize_arr( $val );
					break;
				case 'url':
					$val = esc_url_raw( $val );
					break;
				case 'url_arr':
					$val = self::sanitize_arr( $val, 'url' );
					break;
				case 'textarea':
					$val = sanitize_textarea_field( $val );
					break;
				case 'int':
					$val = intval( $val );
					break;
				case 'int_arr':
					$val = self::sanitize_arr( $val, 'int' );
					break;
				case 'absint':
					$val = absint( $val );
					break;
				case 'absint_arr':
					$val = self::sanitize_arr( $val, 'absint' );
					break;
				case 'float':
					$val = floatval( $val );
					break;
				case 'post_content':
					$val = wp_kses_post( $val );
					break;
				case 'hex':
					$val = sanitize_hex_color( $val );
					break;
				case 'skip': /* skip the validation */
					$val = $val;
					break;
				case 'file_name':
					$val = sanitize_file_name( $val );
					break;
				case 'email':
					$val = sanitize_email( $val );
					break;
				case 'email_arr':
					$val = self::sanitize_arr( $val, 'email' );
					break;
				default:
					$val = sanitize_text_field( $val );
			}

			return $val;
		}

		/**
		 *   Recursive array sanitization function
		 *
		 *   @param mixed  $arr value to sanitize
		 *   @param string $type value type
		 *   @return mixed sanitized value
		 */
		public static function sanitize_arr( $arr, $type = 'text' ) {
			if ( is_array( $arr ) ) {
				$out = array();
				foreach ( $arr as $k => $arrv ) {
					if ( is_array( $arrv ) ) {
						$out[ $k ] = self::sanitize_arr( $arrv, $type );
					} else {
						$out[ $k ] = self::sanitize_item( $arrv, $type );
					}
				}
				return $out;
			} else {
				return self::sanitize_item( $arr, $type );
			}
		}

		/**
		 *   User accessibility. Function checks user logged in status, nonce and role access.
		 *
		 *   @param string $plugin_id unique plugin id. Note: This id is used as an identifier in filter name so please use characters allowed in filters
		 *   @param string $nonce_id Nonce id. If not specified then uses plugin id
		 *   @return boolean if user allowed or not
		 */
		public static function check_write_access( $plugin_id, $nonce_id = '' ) {
			$er = true;

			if ( ! is_user_logged_in() ) {
				$er = false;
			}

			if ( true === $er ) {
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				$nonce    = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
				$nonce    = ( is_array( $nonce ) ? $nonce[0] : $nonce ); // in some cases multiple nonces are declared
				$nonce_id = ( '' === $nonce_id ? $plugin_id : $nonce_id ); // if nonce id not provided then uses plugin id as nonce id

				if ( ! ( wp_verify_nonce( $nonce, $nonce_id ) ) ) {
					$er = false;
				} elseif ( ! self::check_role_access( $plugin_id ) ) {
						$er = false;
				}
			}
			return $er;
		}


		/**
		 *   Checks if user role has access
		 *
		 *   @param string $plugin_id unique plugin id. Note: This id is used as an identifier in filter name so please use characters allowed in filters
		 *   @return boolean if user allowed or not
		 */
		public static function check_role_access( $plugin_id ) {
			$roles      = array( 'manage_woocommerce' );
			$roles      = apply_filters( 'wt_' . $plugin_id . '_alter_role_access', $roles ); // dynamic filter based on plugin id to alter roles
			$roles      = ( ! is_array( $roles ) ? array() : $roles );
			$is_allowed = false;

			foreach ( $roles as $role ) {
				if ( current_user_can( $role ) ) {
					$is_allowed = true;
					break;
				}
			}
			return $is_allowed;
		}
	}
}
