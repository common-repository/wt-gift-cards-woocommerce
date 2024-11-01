(function ( $ ) {
	'use strict';

	$(
		function () {

			$( ".wt-gc-tips" ).tipTip( {'attribute': 'data-wt-gc-tip'} );
			$( '.wt_gc_color_picker_field' ).wpColorPicker( {} );

			wt_gc_popup.Set();
			wt_gc_sidetab.Set();
			wt_gc_field_group.Set();
			wt_gc_settings_form.Set();
			wt_gc_custom_and_preset.Set();

		}
	);

})( jQuery );

/**
 *  Popup creator
 *
 * 	@since 1.0.0
 */
var wt_gc_popup = {

	Set:function () {
		jQuery( 'body' ).prepend( '<div class="wt_gc_cst_overlay"></div>' );
		this.regPopupOpen();
		this.regPopupClose();
	},
	regPopupOpen:function () {
		jQuery( '[data-wt_gc_popup]' ).on(
			'click',
			function () {
				var elm_class = jQuery( this ).attr( 'data-wt_gc_popup' );
				var elm       = jQuery( '.' + elm_class );
				if (elm.length > 0) {
					wt_gc_popup.showPopup( elm );
				}
			}
		);
	},
	showPopup:function (popup_elm) {
		var pw = popup_elm.outerWidth();
		var wh = jQuery( window ).height();
		var ph = wh - 210;
		popup_elm.css( {'display':'block','top':'20px'} ).animate( {'top':'50px'} );
		popup_elm.find( '.wt_gc_popup_body' ).css( {'max-height':ph + 'px','overflow':'auto'} );
		jQuery( '.wt_gc_cst_overlay' ).show();
	},
	hidePopup:function () {
		jQuery( '.wt_gc_popup_close' ).trigger( 'click' );
	},
	regPopupClose:function (popup_elm) {
		jQuery( document ).on(
			'keyup',
			function (e) {
				if ('Escape' === e.key) {
					wt_gc_popup.hidePopup();
				}
			}
		);
		jQuery( '.wt_gc_popup_close, .wt_gc_popup_cancel, .wt_gc_cst_overlay' ).off( 'click' ).on(
			'click',
			function () {
				jQuery( '.wt_gc_cst_overlay, .wt_gc_popup' ).hide();
			}
		);
	}
};

/**
 *  Toast notification
 *
 * 	@since 1.0.0
 */
var wt_gc_notify_msg = {
	error:function (message, auto_close) {
		var auto_close = (auto_close !== undefined ? auto_close : true);
		var er_elm     = jQuery( '<div class="wt_gc_notify_msg wt_gc_notify_msg_error">' + message + '</div>' );
		this.setNotify( er_elm, auto_close );
	},
	success:function (message, auto_close) {
		var auto_close = (auto_close !== undefined ? auto_close : true);
		var suss_elm   = jQuery( '<div class="wt_gc_notify_msg wt_gc_notify_msg_success">' + message + '</div>' );
		this.setNotify( suss_elm, auto_close );
	},
	progress:function ( message ) {
		var prog_elm = jQuery( '<div class="wt_gc_notify_msg wt_gc_notify_msg_progress"><span class="spinner"></span> ' + message + '</div>' );
		this.setNotify( prog_elm, false, true );
		return prog_elm;
	},
	progress_complete:function ( elm, message, auto_close ) {
		var auto_close = (auto_close !== undefined ? auto_close : true);
		elm.removeClass( 'wt_gc_notify_msg_progress' ).addClass( 'wt_gc_notify_msg_success' );
		elm.html( '<span class="dashicons dashicons-yes-alt" style="color:green;"></span> ' + message );
		this.setNotify( elm, auto_close );
	},
	progress_error:function ( elm, message, auto_close ) {
		var auto_close = (auto_close !== undefined ? auto_close : true);
		elm.removeClass( 'wt_gc_notify_msg_progress' ).addClass( 'wt_gc_notify_msg_error' );
		elm.html( '<span class="dashicons dashicons-dismiss" style="color:red;"></span> ' + message );
		this.setNotify( elm, auto_close );
	},
	setNotify:function (elm, auto_close, is_static) {
		jQuery( 'body' ).append( elm );
		elm.stop( true, true ).animate( {'opacity':1, 'top':'50px'}, 1000 );
		if (is_static) {
			return; }

		elm.on(
			'click',
			function () {
				wt_gc_notify_msg.fadeOut( elm );
			}
		);

		if (auto_close) {
			setTimeout(
				function () {
					wt_gc_notify_msg.fadeOut( elm );
				},
				5000
			);
		} else {
			jQuery( 'body' ).on(
				'click',
				function () {
					wt_gc_notify_msg.fadeOut( elm );
				}
			);
		}
	},
	fadeOut:function (elm) {
		elm.animate(
			{'opacity':0,'top':'100px'},
			1000,
			function () {
				elm.remove();
			}
		);
	}
}

/**
 *  Settings side tab view
 *
 * 	@since 1.0.0
 */
var wt_gc_sidetab = {

	Set:function () {
		jQuery( '.wt_gc_settings_left_menu_item' ).on(
			'click',
			function () {
				wt_gc_sidetab.toggle_tabs( jQuery( this ) );
			}
		);

		/* on page load */
		jQuery( '.wt_gc_settings_container' ).each(
			function () {
				wt_gc_sidetab.toggle_tabs( jQuery( this ).find( '.wt_gc_settings_left_menu_item:eq(0)' ) );
			}
		);

	},
	toggle_tabs:function (elm) {
		var subtab_id     = elm.attr( 'data-subtab-id' );
		var conatiner_elm = elm.parents( '.wt_gc_settings_container' );

		conatiner_elm.find( '.wt_gc_settings_left_menu_item' ).removeClass( 'active' );
		conatiner_elm.find( '.wt_gc_settings_right_content_item' ).hide();

		conatiner_elm.find( '.wt_gc_settings_left_menu_item[data-subtab-id="' + subtab_id + '"]' ).addClass( 'active' );
		conatiner_elm.find( '.wt_gc_settings_right_content_item[data-subtab-id="' + subtab_id + '"]' ).show();
	}
};

/**
 *  Settings field groups
 *
 * 	@since 1.0.0
 */
var wt_gc_field_group =
{
	Set:function () {
		jQuery( '.wt_gc_field_group_hd .wt_gc_field_group_toggle_btn' ).each(
			function () {
				var group_id         = jQuery( this ).attr( 'data-id' );
				var group_content_dv = jQuery( this ).parents( 'tr' ).find( '.wt_gc_field_group_content' );
				var visibility       = jQuery( this ).attr( 'data-visibility' );
				jQuery( '.wt_gc_field_group_children[data-field-group="' + group_id + '"]' ).appendTo( group_content_dv.find( 'table' ) );
				if (1 === parseInt( visibility )) {
					group_content_dv.show();
				}
			}
		);

		jQuery( '.wt_gc_field_group_hd' ).off( 'click' ).on(
			'click',
			function () {
				var toggle_btn       = jQuery( this ).find( '.wt_gc_field_group_toggle_btn' );
				var visibility       = toggle_btn.attr( 'data-visibility' );
				var group_content_dv = toggle_btn.parents( 'tr' ).find( '.wt_gc_field_group_content' );
				if (1 === parseInt( visibility )) {
					toggle_btn.attr( 'data-visibility',0 );
					toggle_btn.find( '.dashicons' ).removeClass( 'dashicons-arrow-down' ).addClass( 'dashicons-arrow-right' );
					group_content_dv.hide();
				} else {
					toggle_btn.attr( 'data-visibility',1 );
					toggle_btn.find( '.dashicons' ).removeClass( 'dashicons-arrow-right' ).addClass( 'dashicons-arrow-down' );
					group_content_dv.show();
				}
			}
		);
	}
}

/**
 *  Settings form saving, ajax functionality
 *
 * 	@since 1.0.0
 */
var wt_gc_settings_form =
{
	Set:function () {
		jQuery( '.wt_gc_settings_form' ).find( '[type="checkbox"]' ).each(
			function () {
				var id = jQuery( this ).attr( 'id' );

				if (jQuery( this ).is( ':disabled' ) && jQuery( 'label[for="' + id + '"]' ).length) {
					jQuery( 'label[for="' + id + '"]' ).css( {'opacity':'.5', 'cursor':'default'} );
				}
			}
		);

		jQuery( '.wt_gc_settings_form' ).find( '[required]' ).each(
			function () {
				jQuery( this ).removeAttr( 'required' ).attr( 'data-settings-required','' );
			}
		);
		jQuery( '.wt_gc_settings_form' ).on(
			'submit',
			function (e) {
				e.preventDefault();
				if ( ! wt_gc_settings_form.validate( jQuery( this ) )) {
					return false;
				}

				var settings_base = jQuery( this ).find( '.wt_gc_settings_base' ).val();
				var data          = jQuery( this ).serialize();

				var submit_btn = jQuery( this ).find( 'input[type="submit"]' );
				var spinner    = submit_btn.siblings( '.spinner' );
				spinner.css( {'visibility':'visible'} );
				submit_btn.css( {'opacity':'.5','cursor':'default'} ).prop( 'disabled',true );
				var prg_elm = wt_gc_notify_msg.progress( wt_gc_params.msgs.saving );

				jQuery.ajax(
					{
						url:wt_gc_params.ajax_url,
						type:'POST',
						dataType:'json',
						data:data + '&wt_gc_settings_base=' + settings_base + '&action=wt_gc_save_settings&_wpnonce=' + wt_gc_params.nonce,
						success:function (data) {
							spinner.css( {'visibility':'hidden'} );
							submit_btn.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled',false );
							if (true === data.status) {
								wt_gc_notify_msg.progress_complete( prg_elm, data.msg );
								jQuery( document ).trigger( 'wt_gc_settings_saved', [data] );

							} else {
								wt_gc_notify_msg.progress_error( prg_elm, data.msg, false );
							}
						},
						error:function () {
							spinner.css( {'visibility':'hidden'} );
							submit_btn.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled',false );
							wt_gc_notify_msg.progress_error( prg_elm, wt_gc_params.msgs.settings_error, false );
						}
					}
				);
			}
		);
	},
	validate:function (form_elm) {
		var is_valid = true;
		form_elm.find( '[data-settings-required]' ).each(
			function () {
				var elm = jQuery( this );
				if ("" === elm.val().trim() && elm.is( ':visible' )) {
					var required_msg = elm.attr( 'data-required-msg' );
					if (typeof required_msg === 'undefined') {
						var prnt     = elm.parents( 'tr' );
						var label    = prnt.find( 'th label' );
						var temp_elm = jQuery( '<div />' ).html( label.html() );
						temp_elm.find( '.wt_gc_required_field' ).remove();
						required_msg = '<b><i>' + temp_elm.text() + '</i></b>' + wt_gc_params.msgs.is_required;
					}

					wt_gc_notify_msg.error( required_msg );
					is_valid = false;
					return false;
				}
			}
		);
		return is_valid;
	}
}


var wt_gc_custom_and_preset =
{
	Set:function () {
		jQuery( '.wt_gc_custom_and_preset' ).each(
			function () {
				wt_gc_custom_and_preset.toggler( jQuery( this ), jQuery( this ).siblings( '.wt_gc_custom_and_preset_text' ), jQuery( this ).attr( 'data-custom-trigger-val' ) );
			}
		);
	},
	toggler:function (preset_elm, custom_elm, custom_val) {
		/* Toggle between custom and preset value */
		this.do_toggle( preset_elm, custom_elm, custom_val );
		preset_elm.off( 'change' ).on(
			'change',
			function () {
				wt_gc_custom_and_preset.do_toggle( preset_elm, custom_elm, custom_val );
			}
		);
	},
	do_toggle:function (preset_elm, custom_elm, custom_val) {
		if (preset_elm.val() === custom_val) {
			custom_elm.prop( 'readonly', false ).css( {'background':'#ffffff'} ).trigger( 'focus' ).val( '' );
		} else {
			custom_elm.prop( 'readonly', true ).css( {'background':'#efefef'} ).val( preset_elm.find( 'option:selected' ).val() );
		}
	}
}

/**
 * function directley used from cart.js by wooocommerce
 *
 * @param { jQuery object } node
 */
var wt_gc_block_node = function ( node ) {
	node.addClass( 'processing' );
	if (typeof jQuery.fn.block !== 'function') {
		return;
	}
	node.block(
		{
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		}
	);
}

/**
 * function directley used from cart.js by wooocommerce
 *
 * @param {jQuery object} $node
 */
var wt_gc_unblock_node = function ( node ) {

	node.removeClass( 'processing' );

	if (typeof jQuery.fn.unblock !== 'function') {
		return;
	}
	node.unblock();
};