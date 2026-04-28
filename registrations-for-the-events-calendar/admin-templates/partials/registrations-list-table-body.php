<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
$date_format = 'F jS, ' . rtec_get_time_format();
$unconnected_event_id = isset( $event_obj->event_meta['unconnected_post_id'] ) ? $event_obj->event_meta['unconnected_post_id'] : $event_obj->event_meta['post_id'];
if ( function_exists( 'tribe_get_start_date' ) ) {
	$start_date = tribe_get_start_date( $unconnected_event_id );
	$end_date   = tribe_get_end_date( $unconnected_event_id );
} else {
	$start_date = date_i18n( $date_format, strtotime( $event_obj->event_meta['start_date'] ) );
	$end_date   = date_i18n( $date_format, strtotime( $event_obj->event_meta['end_date'] ) );
}
$canceled_html = ! empty( $event_obj->event_meta['canceled'] ) ? '<br><span class="rtec_canceled_pill rtec-red-bg rtec-red-border">' . esc_html__( 'canceled', 'registrations-for-the-events-calendar' ) . '</span>' : '';

$detail_url = $this->get_detailed_view_url( $event->ID, '' );
$view_url   = get_permalink( $unconnected_event_id );
$edit_url   = get_edit_post_link( $unconnected_event_id );
if ( $edit_url ) {
	$edit_url .= '#rtec-event-details';
}
?>

<tr data-rtec-event-id="<?php echo esc_attr( (string) $event->ID ); ?>">
	<td>
		<strong><a href="<?php echo esc_url( $detail_url ); ?>" class="row-title"><?php echo esc_html( $event_meta['title'] ); ?></a></strong><?php echo $canceled_html; ?>
		<div class="row-actions">
			<?php
			$actions = array();
			if ( $view_url ) {
				$actions[] = '<span class="view"><a href="' . esc_url( $view_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View', 'registrations-for-the-events-calendar' ) . '</a></span>';
			}
			if ( $edit_url ) {
				$actions[] = '<span class="edit"><a href="' . esc_url( $edit_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Edit', 'registrations-for-the-events-calendar' ) . '</a></span>';
			}
			$actions[] = '<span class="manage-registration"><a href="' . esc_url( $detail_url ) . '">' . esc_html__( 'Manage registration', 'registrations-for-the-events-calendar' ) . '</a></span>';
			echo implode( ' | ', $actions );
			?>
		</div>
	</td>
	<td><?php echo esc_html( $start_date ); ?></td>
	<td><?php echo esc_html( $end_date ); ?></td>
	<td><?php echo esc_html( $venue ); ?></td>
	<td class="rtec-list-attendance"><?php echo esc_html( $event_obj->get_registration_text( array(), $event_obj->event_meta['num_registered'] ) ); ?></td>
</tr>
