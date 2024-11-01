<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( is_array( $args ) ) {
	$allowed_html = Wbte_Woocommerce_Gift_Cards_Free_Common::get_allowed_html();

	foreach ( $args as $key => $value ) {
		$tr_id    = ( isset( $value['tr_id'] ) ? ' id="' . esc_attr( $value['tr_id'] ) . '" ' : '' );
		$tr_class = ( isset( $value['tr_class'] ) ? $value['tr_class'] : '' );

		$type             = ( isset( $value['type'] ) ? $value['type'] : 'text' );
		$field_group_attr = ( isset( $value['field_group'] ) ? ' data-field-group="' . esc_attr( $value['field_group'] ) . '" ' : '' );
		$tr_class        .= ( isset( $value['field_group'] ) ? ' wt_gc_field_group_children ' : '' ); // add an extra class to tr when field grouping enabled

		$after_form_field_html = ( isset( $value['after_form_field_html'] ) ? $value['after_form_field_html'] : '' ); /* after form field `td` */
		$after_form_field      = ( isset( $value['after_form_field'] ) ? $value['after_form_field'] : '' ); /* after form field */
		$before_form_field     = ( isset( $value['before_form_field'] ) ? $value['before_form_field'] : '' );


		if ( 'field_group_head' === $type ) {
			$visibility = ( isset( $value['show_on_default'] ) ? $value['show_on_default'] : 0 );
			?>
			<tr <?php echo wp_kses_post( $tr_id . $field_group_attr ); ?> class="<?php echo esc_attr( $tr_class ); ?>">
				<td colspan="3" class="wt_gc_field_group">
					<div class="wt_gc_field_group_hd">
						<?php echo wp_kses_post( isset( $value['head'] ) ? ( $value['head'] ) : '' ); ?>
						<div class="wt_gc_field_group_toggle_btn" data-id="<?php echo esc_attr( isset( $value['group_id'] ) ? $value['group_id'] : '' ); ?>" data-visibility="<?php echo esc_attr( $visibility ); ?>"><span class="dashicons dashicons-arrow-<?php echo esc_attr( 1 === absint( $visibility ) ? 'down' : 'right' ); ?>"></span></div>
					</div>
					<div class="wt_gc_field_group_content">
						<p class="wt_gc_field_group_description"><?php echo wp_kses_post( isset( $value['description'] ) ? ( $value['description'] ) : '' ); ?></p>
						<table><!-- Content will be added by JS --></table>
					</div>
				</td>
			</tr>
			<?php
		} else {

			if ( isset( $value['field_name'] ) ) {
				$field_name = $value['field_name'];
			} elseif ( isset( $value['parent_option'] ) ) {
				$field_name = $value['parent_option'] . '[' . $value['option_name'] . ']';
			} else {
				$field_name = $value['option_name'];
			}

			$field_id = isset( $value['field_id'] ) ? $value['field_id'] : $field_name;

			$fld_attr   = ( isset( $value['attr'] ) ? $value['attr'] : '' );
			$css_class  = ( isset( $value['css_class'] ) ? esc_attr( $value['css_class'] ) : '' );
			$field_only = ( isset( $value['field_only'] ) ? $value['field_only'] : false );
			$non_field  = ( isset( $value['non_field'] ) ? $value['non_field'] : false );
			$mandatory  = (bool) ( isset( $value['mandatory'] ) ? $value['mandatory'] : false );

			if ( $mandatory ) {
				$fld_attr    .= ' required="required"';
				$required_msg = ( isset( $value['required_msg'] ) ? $value['required_msg'] : '' );
				if ( '' !== $required_msg ) {
					$fld_attr .= ' data-required-msg="' . esc_attr( $required_msg ) . '"';
				}
			}

			$field_name = esc_attr( $field_name );
			$field_id   = esc_attr( $field_id );

			if ( false === $field_only ) {
				$tooltip_html = self::set_tooltip( $field_name, $base );
				?>
				<tr valign="top" <?php echo wp_kses_post( $tr_id . $field_group_attr ); ?> class="<?php echo esc_attr( $tr_class ); ?>">
					<th scope="row">
						<label style="margin-left:10px;">
							<?php echo wp_kses_post( isset( $value['label'] ) ? ( $value['label'] ) : '' ); ?><?php echo wp_kses_post( $mandatory ? '<span class="wt_gc_required_field">*</span>' : '' ); ?> <?php echo wp_kses_post( $tooltip_html ); ?>	
						</label>
					</th>
					<td>
				<?php
			}
			if ( true === $non_field ) {
				if ( 'plaintext' === $type ) {
					echo wp_kses_post( isset( $value['text'] ) ? $value['text'] : '' );
				}
			} else {
				echo wp_kses_post( $before_form_field );

				$parent_option = ( isset( $value['parent_option'] ) ? $value['parent_option'] : '' );

				if ( '' !== $parent_option ) {
					$vl = Wbte_Woocommerce_Gift_Cards_Free_Common::get_option( $parent_option, $base );
					$vl = ( isset( $vl[ $value['option_name'] ] ) ? $vl[ $value['option_name'] ] : '' );
				} else {
					$vl = Wbte_Woocommerce_Gift_Cards_Free_Common::get_option( $value['option_name'], $base );
				}

				$vl = is_string( $vl ) ? stripslashes( $vl ) : $vl;
				if ( 'text' === $type || 'number' === $type || 'password' === $type ) {
					?>
					<input type="<?php echo esc_attr( $type ); ?>" <?php echo wp_kses_post( $fld_attr ); ?> class="<?php echo esc_attr( $css_class ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $vl ); ?>" />
					<?php

				} elseif ( 'textarea' === $type ) {
					?>
						<textarea <?php echo wp_kses_post( $fld_attr ); ?> class="<?php echo esc_attr( $css_class ); ?>" name="<?php echo esc_attr( $field_name ); ?>"><?php echo esc_textarea( $vl ); ?></textarea>
					<?php

				} elseif ( 'checkbox' === $type ) {
					$field_vl       = isset( $value['field_vl'] ) ? $value['field_vl'] : '1';
					$checkbox_label = isset( $value['checkbox_label'] ) ? $value['checkbox_label'] : '';
					?>
						<input class="<?php echo esc_attr( $css_class ); ?>" type="checkbox" value="<?php echo esc_attr( $field_vl ); ?>" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" <?php checked( $field_vl, $vl ); ?> <?php echo wp_kses_post( $fld_attr ); ?>>
					<?php
					if ( $checkbox_label ) {
						?>
						<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo wp_kses_post( $checkbox_label ); ?></label>
						<?php
					}
				} elseif ( 'checkbox_list' === $type ) {
					$checkbox_fields = isset( $value['checkbox_fields'] ) ? $value['checkbox_fields'] : array();

					foreach ( $checkbox_fields as $checkbox_vl => $checkbox_label ) {
						?>
						<span class="wt_gc_checkbox_list_item"><input type="checkbox" id="<?php echo esc_attr( $field_id . '_' . $checkbox_vl ); ?>" name="<?php echo esc_attr( $field_name ); ?>[]" class="<?php echo esc_attr( $css_class ); ?>" value="<?php echo esc_attr( $checkbox_vl ); ?>" <?php echo wp_kses_post( in_array( $checkbox_vl, $vl ) ? ' checked="checked"' : '' ); ?> <?php echo wp_kses_post( $fld_attr ); ?> /> <label for="<?php echo esc_attr( $field_id . '_' . $checkbox_vl ); ?>"><?php echo esc_html( $checkbox_label ); ?></label> </span>
						&nbsp;&nbsp;
						<?php
					}
				} elseif ( 'radio' === $type ) {
					$radio_fields = isset( $value['radio_fields'] ) ? $value['radio_fields'] : array();
					foreach ( $radio_fields as $rad_vl => $rad_label ) {
						?>
						<span class="wt_gc_radio_list_item"><input type="radio" id="<?php echo esc_attr( $field_id . '_' . $rad_vl ); ?>" name="<?php echo esc_attr( $field_name ); ?>" class="<?php echo esc_attr( $css_class ); ?>" value="<?php echo esc_attr( $rad_vl ); ?>" <?php checked( $vl, $rad_vl ); ?> <?php echo wp_kses_post( $fld_attr ); ?> /> <label for="<?php echo esc_attr( $field_id . '_' . $rad_vl ); ?>"><?php echo esc_html( $rad_label ); ?></label> </span>
						&nbsp;&nbsp;
						<?php
					}
				}

				if ( 'checkbox' === $type || 'checkbox_list' === $type ) {
					$hidden_filed_name = $field_name . '_hidden';
					if ( isset( $value['parent_option'] ) ) {
						$hidden_filed_name = $value['parent_option'] . '[' . $value['option_name'] . '_hidden]';
					}
					?>
					<input type="hidden" name="<?php echo esc_attr( $hidden_filed_name ); ?>" value="1" />
					<?php
				}

				echo wp_kses( $after_form_field, $allowed_html ); // phpcs:ignore
			}

			if ( isset( $value['help_text'] ) ) {
				?>
				<span class="wt_gc_form_help"><?php echo wp_kses_post( $value['help_text'] ); ?></span>
				<?php
			}

			if ( false === $field_only ) {
				?>
					</td>
					<td>
						<?php echo wp_kses_post( $after_form_field_html ); ?>
					</td>
				</tr>
				<?php
			}
		}
	}
}