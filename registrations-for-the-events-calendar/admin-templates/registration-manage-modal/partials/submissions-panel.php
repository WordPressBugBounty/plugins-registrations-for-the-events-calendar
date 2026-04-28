<?php
/**
 * Submissions panel: Registration Details form (free).
 * Matches Pro structure: panel wrapper, 2-column layout, dashboard-item, form fields.
 * Requires: $event_meta, $form, $fields_atts, $admin_registrations, $entry_data, $event_id.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$no_backend     = rtec_get_no_backend_column_fields();
$standard_keys  = array( 'first_name', 'last_name', 'email', 'phone', 'venue', 'other', 'status' );
$custom_keys    = $form->get_custom_column_keys();
$is_edit        = ! empty( $entry_data );
$entry_id_val   = $is_edit && isset( $entry_data['id'] ) ? (int) $entry_data['id'] : '';
// Only Confirmed and Pending are selectable; legacy 'n' is treated as Confirmed.
$status_val = $is_edit && isset( $entry_data['status'] ) ? $entry_data['status'] : 'c';

$layout_class   = $is_edit ? 'rtec-single-registration-content rtec-2-column-wrapper' : 'rtec-single-registration-content rtec-1-column-wrapper';
$event_post     = ! empty( $event_meta['post_id'] ) ? get_post( (int) $event_meta['post_id'] ) : null;
?>
<div class="rtec-manage-tab-panel rtec-manage-tab-submissions rtec-manage-tab-visible" data-rtec-tab="submissions">
	<div class="<?php echo esc_attr( $layout_class ); ?>">
		<div class="rtec-column-1">
			<div class="rtec-dashboard-item">
				<div class="rtec-dashboard-item-header">
					<h3><?php esc_html_e( 'Registration Details', 'registrations-for-the-events-calendar' ); ?></h3>
				</div>
				<div class="rtec-dashboard-item-content">
					<div class="rtec-add-edit-wrap">
						<form id="rtec-add-edit-form" class="rtec-add-edit-form" method="post" action="">
							<input type="hidden" name="rtecEntryID" value="<?php echo esc_attr( (string) $entry_id_val ); ?>">
							<input type="hidden" name="rtecEventID" value="<?php echo (int) $event_meta['post_id']; ?>">
							<input type="hidden" name="rtecAction" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">

							<div class="rtec-edit-form-field">
								<label for="rtec-status" class="rtec-mvt-label"><?php esc_html_e( 'Status', 'registrations-for-the-events-calendar' ); ?></label>
								<select id="rtec-status" name="status" class="rtec-standard-input">
									<option value="c" <?php selected( $status_val, 'c' ); ?>><?php esc_html_e( 'Confirmed', 'registrations-for-the-events-calendar' ); ?></option>
									<option value="p" <?php selected( $status_val, 'p' ); ?>><?php esc_html_e( 'Pending', 'registrations-for-the-events-calendar' ); ?></option>
								</select>
							</div>

							<?php foreach ( $fields_atts as $field => $atts ) : ?>
								<?php
								if ( in_array( $field, $no_backend, true ) ) {
									continue;
								}
								$value = isset( $entry_data[ $field ] ) ? $entry_data[ $field ] : '';
								if ( is_string( $value ) ) {
									$value = wp_unslash( $value );
								}
								$type = isset( $atts['type'] ) ? $atts['type'] : 'text';
								$label = isset( $atts['label'] ) ? wp_unslash( $atts['label'] ) : $field;
								$is_std = in_array( $field, $standard_keys, true );
								$is_cus = in_array( $field, $custom_keys, true );
								$cls = $is_std ? 'rtec-standard-input' : ( $is_cus ? 'rtec-custom-input' : 'rtec-standard-input' );
								?>
								<?php if ( $type === 'textarea' ) : ?>
									<div class="rtec-edit-form-field">
										<label for="rtec-ea-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label>
										<textarea name="<?php echo esc_attr( $field ); ?>" id="rtec-ea-<?php echo esc_attr( $field ); ?>" rows="6" class="<?php echo esc_attr( $cls ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
									</div>
								<?php else : ?>
									<div class="rtec-edit-form-field">
										<label for="rtec-ea-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label>
										<input type="text" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>" id="rtec-ea-<?php echo esc_attr( $field ); ?>" class="<?php echo esc_attr( $cls ); ?>"/>
									</div>
								<?php endif; ?>
							<?php endforeach; ?>

							<div class="rtec-form-button-wrapper">
								<?php if ( $event_post ) : ?>
									<button type="submit" name="save" class="rtec-button rtec-inline-flex"><?php echo RTEC_Icon::get( 'check' ); ?><span class="rtec-button-text"><?php esc_html_e( 'Save', 'registrations-for-the-events-calendar' ); ?></span></button>
								<?php else : ?>
									<p class="rtec-notice rtec-notice-info">
										<?php esc_html_e( 'This event no longer exists. You can delete this registration but cannot edit it.', 'registrations-for-the-events-calendar' ); ?>
									</p>
								<?php endif; ?>
								<?php if ( $is_edit && $entry_id_val ) : ?>
									<button type="button" class="rtec-button rtec-delete-registration-button rtec-action-delete rtec-inline-flex" data-rtec-entryid="<?php echo esc_attr( (string) $entry_id_val ); ?>" data-rtec-eventid="<?php echo esc_attr( (string) $event_meta['post_id'] ); ?>"><?php echo RTEC_Icon::get( 'trash' ); ?><span class="rtec-button-text"><?php esc_html_e( 'Delete Registration', 'registrations-for-the-events-calendar' ); ?></span></button>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php if ( $is_edit && ! empty( $entry_data ) ) : ?>
		<div class="rtec-column-2">
			<?php include rtec_plugin_path( 'admin-templates/registration-manage-modal/partials/user-info.php' ); ?>
			<?php include rtec_plugin_path( 'admin-templates/registration-manage-modal/partials/related-registrations.php' ); ?>
		</div>
		<?php endif; ?>
	</div>
</div>
