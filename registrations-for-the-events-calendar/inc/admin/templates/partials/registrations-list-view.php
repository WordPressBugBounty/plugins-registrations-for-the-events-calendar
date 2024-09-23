<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
?>

<table class="widefat rtec-registrations-data">
	<thead>
	<tr>
		<th><?php esc_html_e( 'Title', 'registrations-for-the-events-calendar' ); ?></th>
		<th><?php esc_html_e( 'Start Date', 'registrations-for-the-events-calendar' ); ?></th>
		<th><?php esc_html_e( 'Start Date', 'registrations-for-the-events-calendar' ); ?></th>
		<th><?php esc_html_e( 'Venue', 'registrations-for-the-events-calendar' ); ?></th>
		<th><?php esc_html_e( 'Attendance', 'registrations-for-the-events-calendar' ); ?></th>
		<th><?php esc_html_e( 'Link', 'registrations-for-the-events-calendar' ); ?></th>
	</tr>
	</thead>
	<tbody>
		<?php do_action( 'rtec_registrations_tab_list_table_body' ); ?>
	</tbody>
</table>
