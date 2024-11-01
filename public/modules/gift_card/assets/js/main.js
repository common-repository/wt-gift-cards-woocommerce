/** Design store credit gift card */
var wt_gc_gift_card =
{
	Set:function () {
		if (jQuery( '.wt_gc_email_preview' ).length) {
			this.prepare_preview_content();
			this.populate_template_values();
			this.set_preview();
			this.set_preview_texts();
			this.set_carousal();
		}

		this.set_amount_field();
	},
	prepare_preview_content:function () {
		jQuery( '.wt_gc_email_preview' ).html( jQuery( '.wt_gc_email_preview .wt_gc_email_wrapper' )[0].outerHTML ).show();
	},
	set_amount_field:function () {
		/* Denomination */
		jQuery( '.wt_gc_credit_denominations .denominaton_label' ).on(
			'click',
			function () {

				var radio_input = jQuery( this ).siblings( 'input[name="credit_denominaton"]' );
				radio_input.prop( 'checked', true );

				var credit_value = radio_input.val();
				wt_gc_gift_card.set_amount( credit_value );

				jQuery( '.wt_gc_credit_denominations .denominaton_label' ).removeClass( 'wt_gc_selected_amount' );
				jQuery( this ).addClass( 'wt_gc_selected_amount' );
			}
		);

		if ( jQuery( '.wt_gc_credit_denominations .denominaton_label' ).length ) {
			jQuery( '.wt_gc_credit_denominations .denominaton_label:eq(0)' ).trigger( 'click' );
		} else {
			wt_gc_gift_card.set_preview_price( jQuery( '#wt_credit_amount' ).val() );
		}
	},
	set_amount:function (credit_value) {
		credit_value = parseFloat( '' === credit_value ? 0 : credit_value );

		jQuery( '#wt_credit_amount' ).val( credit_value ); /* hidden input for credit amount */

		this.set_preview_price( credit_value );/* preview price */
	},
	set_preview:function () {
		jQuery( '.wt_gc_gift_card_product_page_templates_inner div img' ).on(
			'click',
			function () {
				var elm       = jQuery( this );
				var parent_dv = jQuery( this ).parents( 'div' );

				var image  = elm.attr( 'src' );
				var design = elm.attr( 'design' );

				wt_gc_gift_card.set_email_preview( parent_dv, image, design );

			}
		);

		/**
		 * On page load or reload after template change
		 */
		var template_img_found = false;
		var template_id        = (jQuery( '[name="wt_gc_gift_card_image"]' ).length ? jQuery( '[name="wt_gc_gift_card_image"]' ).val().trim() : '');

		if ("" !== template_id) {
			var template_img = jQuery( '.wt_gc_gift_card_product_page_templates_inner div img[design="' + template_id + '"]' );

			if (0 < template_img.length) {
				template_img.trigger( 'click' );
				var template_img_found = true;
			}
		}

		if ( ! template_img_found && 0 < jQuery( '.wt_gc_gift_card_product_page_templates_inner' ).children( 'div:eq(0)' ).length) {
			jQuery( '.wt_gc_gift_card_product_page_templates_inner' ).children( 'div:eq(0)' ).find( 'img' ).trigger( 'click' );
		}

	},
	set_preview_price:function (price) {
		jQuery( '.wt_gc_email_preview .wt_gc_email_coupon_price .amount' ).contents().filter(
			function () {
				return this.nodeType === Node.TEXT_NODE;
			}
		).each(
			function () {
				this.textContent = price;
			}
		);
	},
	populate_template_values:function () {
		if ('1' === jQuery( '[name="wt_gift_card_form_submit_triggered"]' ).val()) {
			return; /* only on first page load */
		}

		jQuery( '#wt_gc_gift_card_message' ).val( jQuery( '.wt_gc_email_preview .wt_gc_email_message' ).text().trim() );
		jQuery( '#wt_gc_gift_card_sender_name' ).val( jQuery( '.wt_gc_email_preview .wt_gc_from_name' ).text().trim() );

		var reciever_name = jQuery( '.wt_gc_email_preview .wt_gc_reciever_name' ).text().trim();
		var temp_elm      = jQuery( '<div />' ).html( wt_gc_gift_card_params.msgs.hi_there ); /* for proper multi lang compatibility */

		if (temp_elm.find( '.wt_gc_reciever_name' ).text().trim() !== reciever_name) {
			jQuery( '#wt_gc_gift_card_reciever_name' ).val( reciever_name );
		}

	},
	set_preview_texts:function () {
		jQuery( '.wt_gc_gift_card_field' ).on(
			'keyup paste change input',
			function () {
				wt_gc_gift_card.set_email_preview_values( jQuery( this ) );
			}
		);
	},
	set_email_preview_values:function (elm) {
		var vl   = elm.val();
		var name = elm.attr( 'name' );

		if ('wt_user_credit_amount' === name) {
			vl = parseFloat( vl );
			vl = isNaN( vl ) ? 0 : vl;
			this.set_preview_price( vl );

		} else if ('wt_gc_gift_card_message' === name) {
			if (jQuery( '.wt_gc_email_preview .wt_gc_email_message' ).length) {
				jQuery( '.wt_gc_email_preview .wt_gc_from_name_block' ).after( '<div class="wt_gc_email_message"></div>' );
			}

			if ("" === vl.trim()) {
				jQuery( '.wt_gc_email_preview .wt_gc_email_message' ).hide();
			} else {
				jQuery( '.wt_gc_email_preview .wt_gc_email_message' ).show().html( vl );
			}

		} else if ('wt_gc_gift_card_sender_name' === name) {
			if ("" === vl.trim()) {
				jQuery( '.wt_gc_email_preview .wt_gc_from_name_prefix, .wt_gc_email_preview .wt_gc_from_name' ).html( '' );
			} else {
				jQuery( '.wt_gc_email_preview .wt_gc_from_name_prefix' ).html( ' ' + wt_gc_gift_card_params.msgs.from + ' ' );
				jQuery( '.wt_gc_email_preview .wt_gc_from_name' ).html( vl );
			}
		} else if ('wt_gc_gift_card_reciever_name' === name) {
			if ("" === vl.trim()) {
				jQuery( '.wt_gc_email_preview .wt_gc_reciever_name_block' ).html( wt_gc_gift_card_params.msgs.hi_there );
			} else {

				jQuery( '.wt_gc_email_preview .wt_gc_reciever_name' ).html( vl );
			}
		}
	},
	set_carousal:function () {
		setTimeout(
			function () {
				jQuery( '.wt_gc_carousal_inner' ).each(
					function () {

						let elm = jQuery( this );

						elm.before( '<div class="wt_gc_carousal_nav_arrows wt_gc_carousal_nav_arrows_left wt_gc_disable_text_selection"><span> &#10094; </span></div>' );
						elm.after( '<div class="wt_gc_carousal_nav_arrows wt_gc_carousal_nav_arrows_right wt_gc_disable_text_selection"><span> &#10095; </span></div>' );

						wt_gc_gift_card.reset_carousal( elm );
					}
				);
			},
			200
		);

		jQuery( document ).on(
			'click',
			'.wt_gc_carousal_nav_arrows_left',
			function () {

				let inner_elm       = jQuery( this ).siblings( '.wt_gc_carousal_inner' );
				let inner_elm_js    = inner_elm[0];
				let new_scroll_left = inner_elm_js.scrollLeft;

				if (0 < inner_elm_js.scrollLeft) {
					new_scroll_left = Math.max( (inner_elm_js.scrollLeft - inner_elm_js.clientWidth), 0 );
					inner_elm.animate( {'scrollLeft': new_scroll_left} );
				}

			}
		);

		jQuery( document ).on(
			'click',
			'.wt_gc_carousal_nav_arrows_right',
			function () {

				let inner_elm       = jQuery( this ).siblings( '.wt_gc_carousal_inner' );
				let inner_elm_js    = inner_elm[0];
				let max_scroll_left = (inner_elm_js.scrollWidth - inner_elm_js.clientWidth);
				let new_scroll_left = inner_elm_js.scrollLeft;

				if (max_scroll_left > inner_elm_js.scrollLeft) {
					new_scroll_left = Math.min( (parseInt( inner_elm_js.scrollLeft ) + parseInt( inner_elm_js.clientWidth )), max_scroll_left );
					inner_elm.animate( {'scrollLeft': new_scroll_left} );
				}
			}
		);

		jQuery( '.wt_gc_carousal_inner' ).on(
			'scroll',
			function () {
				wt_gc_gift_card.toggle_carousal_btns( jQuery( this ), this.scrollLeft );
			}
		);

		jQuery( window ).on(
			'resize',
			function () {
				jQuery( '.wt_gc_carousal_inner' ).trigger( 'scroll' );
			}
		);
	},
	reset_carousal:function (elm) {
		elm.animate( {'scrollLeft': 0}, 300 );
		wt_gc_gift_card.toggle_carousal_btns( elm, 0 );
	},
	toggle_carousal_btns:function (inner_elm, scroll_left) {
		let max_scroll_left = Math.max( inner_elm[0].scrollWidth - inner_elm[0].clientWidth, 0 );

		if (max_scroll_left <= scroll_left || (max_scroll_left - 1) <= scroll_left) {
			inner_elm.siblings( '.wt_gc_carousal_nav_arrows_right' ).addClass( 'disabled' );
		}

		if (0 < scroll_left) {
			inner_elm.siblings( '.wt_gc_carousal_nav_arrows_left' ).removeClass( 'disabled' );
		}

		if (0 >= scroll_left) {
			inner_elm.siblings( '.wt_gc_carousal_nav_arrows_left' ).addClass( 'disabled' );
		}

		if (0 < max_scroll_left && (max_scroll_left - 1) > scroll_left) {
			inner_elm.siblings( '.wt_gc_carousal_nav_arrows_right' ).removeClass( 'disabled' );
		}

		if (inner_elm[0].scrollWidth === inner_elm[0].clientWidth) {
			inner_elm.siblings( '.wt_gc_carousal_nav_arrows' ).hide();
			inner_elm.parent( '.wt_gc_carousal' ).css( {'padding': '0px'} );
		} else {
			inner_elm.siblings( '.wt_gc_carousal_nav_arrows' ).show();
			inner_elm.parent( '.wt_gc_carousal' ).css( {'padding': '0px 25px'} );
		}
	},
	set_email_preview:function (parent_dv, image, design) {
		jQuery( '.wt_gc_gift_card_product_page_templates_inner div, .wt_gc_gift_card_product_page_custom_template_box' ).removeClass( 'active' );
		parent_dv.addClass( 'active' );

		jQuery( '.wt_gc_email_preview div.wt_gc_email_img img' ).attr( {'src': image, 'alt': design} );
		jQuery( '[name="wt_gc_gift_card_image"]' ).val( design );
	}
}

jQuery( document ).ready(
	function () {
		wt_gc_gift_card.Set();
	}
);