<?php
/**
 * Related Registrations table (same person, other events) for Manage Registration modal (free).
 * Requires: $entry_data, $event_id (and $event_meta for consistency; no payment column in free).
 *
 * @package Registrations_For_The_Events_Calendar
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$related = RTEC_Manage_Modal_Context::get_related_registrations( $entry_data, $event_id );
?>
<div class="rtec-dashboard-item">
	<div class="rtec-dashboard-item-header">
		<h3><?php esc_html_e( 'Related Registrations', 'registrations-for-the-events-calendar' ); ?></h3>
	</div>
	<div class="rtec-dashboard-item-content">
		<?php if ( empty( $related ) ) : ?>
			<p class="rtec-no-related"><?php esc_html_e( 'No related registrations found.', 'registrations-for-the-events-calendar' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat striped rtec-related-registrations-table">
				<thead>
					<tr>
						<th scope="col">ID</th>
						<th scope="col"><?php esc_html_e( 'Event', 'registrations-for-the-events-calendar' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'registrations-for-the-events-calendar' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $related as $reg ) : ?>
						<?php
						$reg_id    = isset( $reg['id'] ) ? (int) $reg['id'] : 0;
						$reg_event = isset( $reg['event_id'] ) ? (int) $reg['event_id'] : 0;
						$event_meta_rel = $reg_event > 0 ? rtec_get_event_meta( $reg_event ) : array();
						$event_title    = isset( $event_meta_rel['title'] ) ? $event_meta_rel['title'] : ( $reg_event > 0 ? get_the_title( $reg_event ) : '—' );
						$date_summary   = '';
						if ( $reg_event > 0 && function_exists( 'tribe_get_start_date' ) ) {
							$date_summary = tribe_get_start_date( $reg_event, true );
						} elseif ( ! empty( $event_meta_rel['start_date'] ) ) {
							$date_summary = date_i18n( get_option( 'date_format' ), strtotime( $event_meta_rel['start_date'] ) );
						}
						?>
						<tr>
							<td><a href="#" class="rtec-manage-link" data-rtec-registration-id="<?php echo (int) $reg_id; ?>" data-rtec-event-id="<?php echo (int) $reg_event; ?>" data-rtec-modal-content="manage-registration"><?php echo (int) $reg_id; ?></a></td>
							<td><?php echo esc_html( $event_title ); ?></td>
							<td><?php echo esc_html( $date_summary ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
