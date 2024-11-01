(function ( $ ) {
	'use strict';

	/**
	 *  Gift card template manage.
	 *
	 * 	@since 1.0.0
	 */
	var wt_gc_gift_template_manage = {

		onProgress:false,
		selected_cat:false,
		Set:function () {
			this.load_template();
			this.preview_template();
		},
		preview_template:function () {
			jQuery( document ).on(
				'click',
				'.wt_gc_img_preview_btn',
				function () {
					wt_gc_popup.showPopup( jQuery( '.wt_gc_giftcard_template_preview_popup' ) );
					var img_html = '<img src="' + $( this ).parents( '.wt_gc_giftcard_template_box' ).find( '.wt_gc_template_img' ).attr( 'src' ) + '" />';
					jQuery( '.wt_gc_giftcard_template_preview_popup .wt_gc_popup_body' ).html( img_html );
				}
			)
		},

		/**
		 *  Load template list
		 */
		load_template:function () {
			var template_container_elm = jQuery( '.wt_gc_giftcard_template_main' );
			if ( ! template_container_elm.length) {
				return false;
			}

			jQuery( '[name="wt_gc_update_admin_settings_form"]' ).prop( {'disabled':true} );

			template_container_elm.html( wt_gc_params.msgs.loading );
			var submit_btn = jQuery( '.wt-sc-gift-template-container [name="wt_gc_update_admin_settings_form"]' );
			submit_btn.hide();

			var visible_only          = template_container_elm.attr( 'data-visible-only' );
			var visibility_ajax_param = (typeof visible_only !== 'undefined' ? '&visible_only=' + visible_only : '');

			var page_type            = template_container_elm.attr( 'data-page-type' );
			var page_type_ajax_param = (typeof page_type !== 'undefined' ? '&page_type=' + page_type : '');

			jQuery.ajax(
				{
					url:wt_gc_params.ajax_url,
					type:'POST',
					data:'action=wt_gc_show_giftcard_templates&_wpnonce=' + wt_gc_params.nonce + visibility_ajax_param + page_type_ajax_param,
					success:function (data) {
						submit_btn.show();
						template_container_elm.html( data );
						var selected_templates = template_container_elm.attr( 'data-selected-templates' );

						if (typeof selected_templates !== 'undefined') {
							var selected_template_arr = selected_templates.split( "," );
							template_container_elm.find( '.wt_gc_visible_template_checkbox' ).prop( 'checked', false );

							for (var i = 0; i < selected_template_arr.length; i++) {
								template_container_elm.find( '.wt_gc_visible_template_checkbox[value="' + selected_template_arr[i] + '"]' ).prop( 'checked', true );
							}
						}

						var input_name = template_container_elm.attr( 'data-input-name' );

						if (typeof input_name !== 'undefined') {
							jQuery( '.wt_gc_visible_template_checkbox' ).attr( {'name': input_name + '[]'} );
						}

						jQuery( '[name="wt_gc_update_admin_settings_form"]' ).prop( {'disabled':false} );
					},
					error:function () {
						template_container_elm.html( wt_gc_gift_card_params.msgs.unable_to_load_templates );
					}
				}
			);
		}
	};

	var wt_gc_send_gift_card = {

		Set:function () {
			if ( ! jQuery( 'form.wt_gc_gift_card_mail_form' ).length) {
				return false;
			}

			jQuery( '.wt_gc_email_preview_btn' ).on(
				'click',
				function () {
					wt_gc_send_gift_card.set_preview();
				}
			);

			/* load values to preview from fields */
			jQuery( '.wt_gc_send_email_field' ).on(
				'keyup paste change input',
				function () {
					wt_gc_send_gift_card.set_email_preview_values( jQuery( this ) );
				}
			);

			this.set_preview();
			this.send_email();
		},
		set_preview:function () {
			var preview_elm = jQuery( '.wt_gc_email_preview' );
			if ('0' !== preview_elm.attr( 'data-loaded' )) { /* already loaded */
				return false;
			}

			preview_elm.html( '<div class="wt_gc_email_preview_loading">' + wt_gc_params.msgs.loading + '</div>' );

			let ajax_data = {
				'action': 'wt_gc_gift_card_email_preview',
				'_wpnonce': wt_gc_params.nonce,
			};

			jQuery.ajax(
				{
					url:wt_gc_params.ajax_url,
					type:'POST',
					data:ajax_data,
					success:function (data) {
						preview_elm.html( data ).attr( {'data-loaded':1} );
						jQuery( '.wt_gc_send_email_field' ).each(
							function () {
								wt_gc_send_gift_card.set_email_preview_values( jQuery( this ) );
							}
						);
						wt_gc_send_gift_card.set_preview_template();
					},
					error:function () {
						preview_elm.attr( {'data-loaded':0} ).find( '.wt_gc_email_preview_loading' ).html( wt_gc_gift_card_params.msgs.unable_to_load_preview );
					}
				}
			);

		},
		set_preview_template:function () {
			jQuery( '[name="wt_gc_send_email_template"]' ).val( wt_gc_gift_card_params.default_template );
			jQuery( '.wt_gc_email_preview div.wt_gc_email_img img' ).attr( {'src': wt_gc_gift_card_params.default_template_url, 'alt': wt_gc_gift_card_params.default_template} );
		},
		set_email_preview_values:function (elm) {
			var vl   = elm.val();
			var name = elm.attr( 'name' );

			if ('wt_gc_send_email_amount' === name) {
				vl = parseFloat( vl );
				vl = isNaN( vl ) ? 0 : vl;
				this.set_preview_price( vl );

			} else if ('wt_gc_send_email_description' === name) {
				if ( ! jQuery( '.wt_gc_email_preview .wt_gc_email_message' ).length) {
					jQuery( '.wt_gc_email_preview .wt_gc_from_name_block' ).after( '<div class="wt_gc_email_message"></div>' );
				}

				if ("" === vl.trim()) {
					jQuery( '.wt_gc_email_preview .wt_gc_email_message' ).hide();
				} else {
					jQuery( '.wt_gc_email_preview .wt_gc_email_message' ).show().html( vl );
				}

			} else if ('wt_gc_send_email_from_name' === name) {
				if ("" === vl.trim()) {
					jQuery( '.wt_gc_email_preview .wt_gc_from_name_prefix, .wt_gc_email_preview .wt_gc_from_name' ).html( '' );
				} else {
					jQuery( '.wt_gc_email_preview .wt_gc_from_name_prefix' ).html( ' ' + wt_gc_gift_card_params.msgs.from + ' ' );
					jQuery( '.wt_gc_email_preview .wt_gc_from_name' ).html( vl );
				}

			} else if ('wt_gc_send_email_coupon_expiry' === name) {
				vl = parseInt( vl );
				if ( ! isNaN( vl )) {
					var date = new Date();
					date.setDate( date.getDate() + vl );
					jQuery( '.wt_gc_email_coupon_expiry' ).html( wt_gc_gift_card_params.msgs.expiry_date + date.toDateString() );
				} else {
					jQuery( '.wt_gc_email_coupon_expiry' ).html( '' );
				}

			}

		},
		set_preview_price:function (price) {
			$( '.wt_gc_email_preview .wt_gc_email_coupon_price .amount' ).contents().filter(
				function () {
					return this.nodeType == Node.TEXT_NODE;
				}
			).each(
				function () {
					this.textContent = price;
				}
			);
		},
		send_email:function () {
			jQuery( '.wt_gc_gift_card_mail_form' ).find( '[required]' ).each(
				function () {
					jQuery( this ).prop( 'required', false ).attr( 'data-settings-required', '' );
				}
			);

			jQuery( '.wt_gc_gift_card_mail_form' ).on(
				'submit',
				function (e) {
					e.preventDefault();
					if ( ! wt_gc_settings_form.validate( jQuery( this ) )) {
						return false;
					}

					var data = jQuery( this ).serialize();

					var submit_btn = jQuery( this ).find( 'input[type="submit"]' );
					var spinner    = submit_btn.siblings( '.spinner' );
					spinner.css( {'visibility':'visible'} );
					submit_btn.css( {'opacity':'.5','cursor':'default'} ).prop( 'disabled',true );

					jQuery.ajax(
						{
							url:wt_gc_params.ajax_url,
							type:'POST',
							dataType:'json',
							data:data + '&action=wt_gc_gift_card_email&_wpnonce=' + wt_gc_params.nonce,
							success:function (data) {
								spinner.css( {'visibility':'hidden'} );
								submit_btn.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled',false );
								if (data.status === true) {
									wt_gc_notify_msg.success( data.msg, false );
								} else {
									wt_gc_notify_msg.error( data.msg, false );
								}
							},
							error:function () {
								spinner.css( {'visibility':'hidden'} );
								submit_btn.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );
								wt_gc_notify_msg.error( wt_gc_params.msgs.error, false );
							}
						}
					);

				}
			);
		}
	}

	$(
		function () {

			wt_gc_gift_template_manage.Set();
			wt_gc_send_gift_card.Set();

			jQuery( '.wt_gc_checkbox_list_item' ).has( '#fields_to_be_shown_reciever_email' ).css( {'opacity': .8, 'cursor': 'not-allowed'} );
			jQuery( '#fields_to_be_shown_reciever_email' ).prop( {'disabled': true} );

			/**
			 * Hide shortcode field when templates are not enabled.
			 */
			jQuery(document).ready(function(){
				if (! jQuery(".wt_gc_copy_shortcode_btn").length > 0) {
					jQuery('.wt_gc_gift_card_product_table thead th:last-child').hide();
					jQuery('.wt_gc_gift_card_product_table tbody tr td:last-child').hide();
				}
			});

			jQuery(document).on('click', '.wt_gc_copy_shortcode_btn', function(){
				var elm = jQuery(this);
				var target_elm = elm.find('span');
	
				if(target_elm.length){
					navigator.clipboard.writeText(target_elm.text().trim());
					
					elm.fadeOut(200, function(){
						elm.siblings('.wt_gc_copy_shortcode_copied').fadeIn();
					});		
	
					setTimeout(function(){  				
						elm.siblings('.wt_gc_copy_shortcode_copied').fadeOut(200, function(){
							elm.fadeIn();
						});
						
					}, 2000)
				}
			});

		}
	);

})( jQuery );