<?php
/**
 * Single row for the All Registrations (Latest) table.
 * Uses same identity column as overview (with Manage link). No Registration Type (no MVT).
 * Expects: $registration, $event_meta, $admin_registrations, $tab = 'latest'.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$is_user   = isset( $registration['user_id'] ) && (int) $registration['user_id'] > 0;
$status    = isset( $registration['status'] ) ? $registration['status'] : 'c';
$status    = ( $status === 'p' ) ? 'p' : 'c';
$status_labels = array(
	'c' => __( 'Confirmed', 'registrations-for-the-events-calendar' ),
	'p' => __( 'Pending', 'registrations-for-the-events-calendar' ),
);
$status_label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status_labels['c'];

$event_id   = isset( $event_meta['post_id'] ) ? (int) $event_meta['post_id'] : 0;
$manage_url = $event_id > 0 ? admin_url( 'admin.php?page=' . RTEC_MENU_SLUG . '&tab=single&id=' . $event_id ) : '';
$tz_offset  = rtec_get_time_zone_offset();
$reg_date   = ! empty( $registration['registration_date'] ) ? date_i18n( rtec_get_date_time_format(), strtotime( $registration['registration_date'] ) + $tz_offset ) : '';
$start_date = ! empty( $event_meta['start_date'] ) ? date_i18n( rtec_get_date_time_format(), strtotime( $event_meta['start_date'] ) ) : '';
$end_date   = ! empty( $event_meta['end_date'] ) ? date_i18n( rtec_get_date_time_format(), strtotime( $event_meta['end_date'] ) ) : '';
$event_date = $start_date;
if ( $end_date !== '' && $end_date !== $start_date ) {
	$event_date .= ' – ' . $end_date;
}
?>
<tr class="rtec-reg-row<?php echo esc_attr( $admin_registrations->get_registrant_tr_classes( $status, $is_user ) ); ?>">
	<?php
	$event_id = $event_meta['post_id'];
	include rtec_plugin_path( 'admin-templates/partials/registrations-main-column.php' );
	$email = isset( $registration['email'] ) ? $registration['email'] : '';
	?>
	<td class="rtec-reg-email"><?php if ( $email ) : ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><?php else : ?>—<?php endif; ?></td>
	<td class="rtec-reg-date"><?php echo esc_html( $reg_date ); ?></td>
	<td class="rtec-event-cell"><?php if ( $manage_url ) : ?><a href="<?php echo esc_url( $manage_url ); ?>"><?php endif; ?><?php echo esc_html( $event_meta['title'] ); ?><?php if ( $manage_url ) : ?></a><?php endif; ?></td>
	<td class="rtec-event-date-cell"><?php echo esc_html( $event_date ); ?></td>
	<td class="rtec-reg-status"><span class="rtec-status-badge rtec-status-reg rtec-status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
</tr>
