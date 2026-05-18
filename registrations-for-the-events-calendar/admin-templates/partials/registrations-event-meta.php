<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$event_id    = $event_obj->event_meta['post_id'];
$date_format = 'F jS, ' . rtec_get_time_format();
$event_post  = $event_id ? get_post( $event_id ) : null;

// Attendance: person icon + count text (aligned with Pro card UI).
$attendance_icon = '<svg class="rtec-attendance-meta-icon" width="12" height="16" viewBox="0 0 12 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 0C4.69375 0 3.58125 0.834375 3.17188 2H2C0.896875 2 0 2.89687 0 4V14C0 15.1031 0.896875 16 2 16H10C11.1031 16 12 15.1031 12 14V4C12 2.89687 11.1031 2 10 2H8.82812C8.41875 0.834375 7.30625 0 6 0ZM6 2C6.26522 2 6.51957 2.10536 6.70711 2.29289C6.89464 2.48043 7 2.73478 7 3C7 3.26522 6.89464 3.51957 6.70711 3.70711C6.51957 3.89464 6.26522 4 6 4C5.73478 4 5.48043 3.89464 5.29289 3.70711C5.10536 3.51957 5 3.26522 5 3C5 2.73478 5.10536 2.48043 5.29289 2.29289C5.48043 2.10536 5.73478 2 6 2ZM4 8C4 7.46957 4.21071 6.96086 4.58579 6.58579C4.96086 6.21071 5.46957 6 6 6C6.53043 6 7.03914 6.21071 7.41421 6.58579C7.78929 6.96086 8 7.46957 8 8C8 8.53043 7.78929 9.03914 7.41421 9.41421C7.03914 9.78929 6.53043 10 6 10C5.46957 10 4.96086 9.78929 4.58579 9.41421C4.21071 9.03914 4 8.53043 4 8ZM2.5 13.5C2.5 12.1187 3.61875 11 5 11H7C8.38125 11 9.5 12.1187 9.5 13.5C9.5 13.775 9.275 14 9 14H3C2.725 14 2.5 13.775 2.5 13.5Z"></path></svg>';
$attendance_text = $event_obj->get_registration_text( array(), $event_obj->event_meta['num_registered'] );
$attendance_html = '<span class="rtec-event-meta-attendance rtec-event-meta-item rtec-flex-align-center">' . $attendance_icon . ' ' . esc_html( $attendance_text ) . '</span>';

// Location + venue (and organizers if TEC provides them)
$location_icon      = '<span class="rtec-event-meta-location rtec-icon" aria-hidden="true">' . RTEC_Icon::get( 'location' ) . '</span>';
$event_details_array = array( '<span class="rtec-flex-align-center">' . $location_icon . ' ' . esc_html( $event_obj->event_meta['venue_title'] ) . '</span>' );
if ( $event_post && function_exists( 'tribe_get_organizer_ids' ) && function_exists( 'tribe_get_organizer' ) ) {
	$organizer_ids = tribe_get_organizer_ids( $event_id );
	if ( ! empty( $organizer_ids ) && is_array( $organizer_ids ) ) {
		foreach ( $organizer_ids as $organizer_id ) {
			$event_details_array[] = esc_html( tribe_get_organizer( $organizer_id ) );
		}
	}
}

// Schedule: use TEC schedule details if available, else plain date range.
$schedule_details = '';
if ( $event_post && function_exists( 'tribe_events_event_schedule_details' ) ) {
	$schedule_details = tribe_events_event_schedule_details( $event_id, '<p class="rtec-event-date">', '</p>' );
}
if ( $schedule_details === '' ) {
	if ( ! $event_post ) {
		$schedule_details = '<p class="rtec-event-date">' . esc_html__( 'Event no longer exists.', 'registrations-for-the-events-calendar' ) . '</p>';
	} else {
		$schedule_details  = '<p class="rtec-event-date">';
		$schedule_details .= date_i18n( $date_format, strtotime( $event_obj->event_meta['start_date'] ) );
		$schedule_details .= ' ' . __( 'to', 'registrations-for-the-events-calendar' ) . ' ';
		$schedule_details .= '<span class="rtec-end-time">' . date_i18n( $date_format, strtotime( $event_obj->event_meta['end_date'] ) ) . '</span>';
		$schedule_details .= '</p>';
	}
}
?>

<?php if ( $event_obj->view_type !== 'single' ) : ?>
	<a href="<?php $this->the_detailed_view_href( $event_id, '' ); ?>"><h3><?php echo esc_html( $event_obj->event_meta['title'] ); ?></h3></a>
<?php else : ?>
	<h3><?php echo esc_html( $event_obj->event_meta['title'] ); ?></h3>
<?php endif; ?>
<?php echo $schedule_details; ?>
<div class="rtec-event-meta-row-wrap">
	<div class="rtec-event-meta-details-row rtec-event-meta-item-wrap rtec-flex-align-center">
		<?php echo $attendance_html; ?>
		<span class="rtec-event-meta-item rtec-flex-align-center"><?php echo implode( ' | ', $event_details_array ); ?></span>
	</div>
</div>

<?php if ( $event_obj->view_type !== 'single' && $event_post ) : ?>
	<div class="rtec-event-actions rtec-clear">
		<a href="<?php echo esc_url( get_the_permalink( $event_id ) ); ?>" class="rtec-admin-secondary-button button action rtec-icon-text" target="_blank" rel="noopener noreferrer"><?php echo RTEC_Icon::get( 'eye' ); ?> <?php esc_html_e( 'View Event', 'registrations-for-the-events-calendar' ); ?></a>
		<?php if ( current_user_can( 'edit_posts' ) ) : ?>
			<a href="<?php echo esc_url( get_edit_post_link( $event_id ) . '#rtec-event-details' ); ?>" class="rtec-admin-secondary-button button action rtec-icon-text" target="_blank" rel="noopener noreferrer"><?php echo RTEC_Icon::get( 'edit' ); ?> <?php esc_html_e( 'Event Options', 'registrations-for-the-events-calendar' ); ?></a>
		<?php endif; ?>
		<a href="<?php $this->the_detailed_view_href( $event_id, '' ); ?>" class="rtec-admin-secondary-button button action rtec-icon-text"><?php echo RTEC_Icon::get( 'plus' ); ?> <?php esc_html_e( 'Manage Registrations', 'registrations-for-the-events-calendar' ); ?></a>
	</div>
<?php endif; ?>
