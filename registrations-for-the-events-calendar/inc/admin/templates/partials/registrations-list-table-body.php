<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
$mvt         = '';
$date_format = 'F jS, ' . rtec_get_time_format();
$start_date = function_exists( 'tribe_get_start_date' ) ? tribe_get_start_date( $event->ID ) : '';
$end_date   = function_exists( 'tribe_get_end_date' ) ? tribe_get_end_date( $event->ID ) : '';
?>

<tr <?php echo $row_class; ?>>
	<td><a href="<?php $this->the_detailed_view_href( $event->ID, '' ); ?>"><?php echo esc_html( $event_meta['title'] ); ?></a></td>
	<td><?php echo esc_html( $start_date ); ?></td>
    <td><?php echo esc_html( $end_date ); ?></td>
	<td class="rtec-venue-highlight"><?php echo esc_html( $venue ); ?></td>
	<td class="rtec-list-attendance"><?php echo esc_html( $event_obj->get_registration_text( array(), $num_registered ) ); ?></td>
	<td><a href="<?php $this->the_detailed_view_href( $event->ID, $mvt ); ?>"><i class="fa fa-list" aria-hidden="true"></i> <?php esc_html_e( 'Details', 'registrations-for-the-events-calendar' ); ?></a></td>
</tr>
