<?php
/**
 * Manage Registration modal body (free: Submissions only).
 * Matches Pro structure: wrapper, single-header, event-meta, panels.
 * Requires: $event_meta, $event_obj, $admin_registrations, $form, $fields_atts, $event_id, $entry_data.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$entry_id = ! empty( $entry_data['id'] ) ? (int) $entry_data['id'] : 0;
$contact_event_id = isset( $event_meta['post_id'] ) ? (int) $event_meta['post_id'] : 0;
$contact_entry_id = $entry_id;
$identity = trim( ( isset( $entry_data['first_name'] ) ? $entry_data['first_name'] : '' ) . ' ' . ( isset( $entry_data['last_name'] ) ? $entry_data['last_name'] : '' ) );
if ( $identity === '' && ! empty( $entry_data['email'] ) ) {
	$identity = $entry_data['email'];
}
if ( $identity === '' ) {
	$identity = '—';
}
$contact_identity = $identity . ( ! empty( $entry_data['email'] ) ? ' <' . $entry_data['email'] . '>' : '' );
$has_registration = $entry_id > 0;

$status_labels = array(
	'c' => __( 'Confirmed', 'registrations-for-the-events-calendar' ),
	'p' => __( 'Pending', 'registrations-for-the-events-calendar' ),
	'n' => __( 'New', 'registrations-for-the-events-calendar' ),
);
$status_slug = $has_registration && isset( $entry_data['status'] ) ? $entry_data['status'] : 'c';
if ( ! isset( $status_labels[ $status_slug ] ) ) {
	$status_slug = 'c';
}
$date_format = 'F jS, ' . rtec_get_time_format();
$event_title = isset( $event_meta['title'] ) ? $event_meta['title'] : '';
$event_date_summary = '';
if ( ! empty( $event_meta['start_date'] ) && ! empty( $event_meta['end_date'] ) ) {
	$event_date_summary = sprintf(
		/* translators: %1$s start date, %2$s end date */
		__( '%1$s to %2$s', 'registrations-for-the-events-calendar' ),
		date_i18n( $date_format, strtotime( $event_meta['start_date'] ) ),
		date_i18n( $date_format, strtotime( $event_meta['end_date'] ) )
	);
}
$venue_display = isset( $event_meta['venue_title'] ) ? $event_meta['venue_title'] : '';
?>
<div class="rtec-manage-registration rtec-content-modal-body rtec-single-registration-manager" data-rtec-event-id="<?php echo esc_attr( (string) $contact_event_id ); ?>" data-rtec-entry-id="<?php echo esc_attr( (string) $contact_entry_id ); ?>" data-rtec-contact-identity="<?php echo esc_attr( $contact_identity ); ?>">
	<?php if ( $has_registration ) : ?>
		<div class="rtec-single-header rtec-modal-heading">
			<h2 class="rtec-manage-registration-heading"><?php echo esc_html( $identity ); ?></h2>
			<div class="rtec-registration-column-status rtec-status-<?php echo esc_attr( $status_slug ); ?>"><?php echo esc_html( $status_labels[ $status_slug ] ); ?></div>
			<div class="rtec-quantity-cost">
				<span class="rtec-icon-text"><?php RTEC_Icon::output( 'registration' ); ?> <?php esc_html_e( '1 registration', 'registrations-for-the-events-calendar' ); ?></span>
			</div>
		</div>
		<div class="rtec-single-event-meta">
			<div class="rtec-single-event-meta-title"><?php echo esc_html( $event_title ); ?></div>
			<?php if ( $event_date_summary !== '' ) : ?>
				<div class="rtec-single-event-meta-date-summary"><?php echo esc_html( $event_date_summary ); ?></div>
			<?php endif; ?>
			<?php if ( $venue_display !== '' ) : ?>
				<div class="rtec-single-event-meta-misc">
					<span class="rtec-event-detail-item"><?php RTEC_Icon::output( 'location' ); ?> <?php echo esc_html( $venue_display ); ?></span>
				</div>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="rtec-single-header rtec-modal-heading">
			<h2 class="rtec-manage-registration-heading"><?php esc_html_e( 'Add New', 'registrations-for-the-events-calendar' ); ?></h2>
		</div>
		<div class="rtec-single-event-meta">
			<div class="rtec-single-event-meta-title"><?php echo esc_html( $event_title ); ?></div>
			<?php if ( $event_date_summary !== '' ) : ?>
				<div class="rtec-single-event-meta-date-summary"><?php echo esc_html( $event_date_summary ); ?></div>
			<?php endif; ?>
			<?php if ( $venue_display !== '' ) : ?>
				<div class="rtec-single-event-meta-misc">
					<span class="rtec-event-detail-item"><?php RTEC_Icon::output( 'location' ); ?> <?php echo esc_html( $venue_display ); ?></span>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="rtec-manage-registration-panels">
		<?php include rtec_plugin_path( 'admin-templates/registration-manage-modal/partials/submissions-panel.php' ); ?>
	</div>
</div>
