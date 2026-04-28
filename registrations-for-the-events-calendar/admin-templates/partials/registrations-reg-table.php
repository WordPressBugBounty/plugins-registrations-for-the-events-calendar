<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
// Overview tab: do not show the "Manage" row action (only show it on single event view).
$rtec_show_manage_link = false;
if ( ! isset( $show_venue_label ) ) {
	$show_venue_label = true;
}
$event_id = ( isset( $event ) && isset( $event->ID ) ) ? (int) $event->ID : ( isset( $event_obj->event_meta['post_id'] ) ? (int) $event_obj->event_meta['post_id'] : 0 );
$name_columns = array( 'first', 'last', 'first_name', 'last_name' );
$overview_data_columns = array_diff( array_keys( $event_obj->column_label ), $name_columns );
$status_labels = array(
	'c' => __( 'Confirmed', 'registrations-for-the-events-calendar' ),
	'p' => __( 'Pending', 'registrations-for-the-events-calendar' ),
);
$overview_col_count = 3 + count( $overview_data_columns ); // Name + Registration Date + Status + data columns
?>
<table class="widefat striped rtec-registrations-data">
	<thead>
	<tr>
		<th><span class="screen-reader-text"><?php esc_html_e( 'Name', 'registrations-for-the-events-calendar' ); ?></span></th>
		<th><?php esc_html_e( 'Registration Date', 'registrations-for-the-events-calendar' ); ?></th>
		<th><?php esc_html_e( 'Status', 'registrations-for-the-events-calendar' ); ?></th>
		<?php foreach ( $overview_data_columns as $column ) :
			$label = isset( $event_obj->column_label[ $column ] ) ? $event_obj->column_label[ $column ] : $column;
			if ( ! $show_venue_label && $label === __( 'Registration Type', 'registrations-for-the-events-calendar' ) ) {
				continue;
			}
			?>
			<th><?php echo esc_html( wp_unslash( $label ) ); ?></th>
		<?php endforeach; ?>
	</tr>
	</thead>
	<tbody>
	<?php if ( ! empty( $event_obj->registrants_data ) ) : ?>
		<?php foreach ( $event_obj->registrants_data as $registration ) : ?>
			<?php
			$custom_data = isset( $registration['custom'] ) ? maybe_unserialize( $registration['custom'] ) : array();
			$is_user     = isset( $registration['user_id'] ) && (int) $registration['user_id'] > 0;
			$reg_status  = isset( $registration['status'] ) ? $registration['status'] : 'c';
			$reg_status  = ( $reg_status === 'p' ) ? 'p' : 'c';
			$reg_status_label = isset( $status_labels[ $reg_status ] ) ? $status_labels[ $reg_status ] : $status_labels['c'];
			$registration_date = isset( $registration['registration_date'] ) ? date_i18n( 'm/d ' . rtec_get_time_format(), strtotime( $registration['registration_date'] ) + rtec_get_time_zone_offset() ) : '';
			?>
			<tr class="rtec-reg-row<?php echo esc_attr( $this->get_registrant_tr_classes( $registration['status'], $is_user ) ); ?>">
				<?php include rtec_plugin_path( 'admin-templates/partials/registrations-main-column.php' ); ?>
				<td class="rtec-reg-registration_date"><?php echo esc_html( wp_unslash( $registration_date ) ); ?></td>
				<td class="rtec-reg-status"><span class="rtec-status-badge rtec-status-reg rtec-status-<?php echo esc_attr( $reg_status ); ?>"><?php echo esc_html( $reg_status_label ); ?></span></td>
				<?php foreach ( $overview_data_columns as $column ) :
					$label = isset( $event_obj->column_label[ $column ] ) ? $event_obj->column_label[ $column ] : $column;
					$value = '';
					if ( isset( $registration[ $column ] ) && is_scalar( $registration[ $column ] ) ) {
						$value = $registration[ $column ];
					} elseif ( isset( $registration[ $column . '_name' ] ) ) {
						$value = $registration[ $column . '_name' ];
					} elseif ( isset( $custom_data[ $label ] ) && is_scalar( $custom_data[ $label ] ) ) {
						$value = $custom_data[ $label ];
					} elseif ( isset( $custom_data[ $column ] ) && is_array( $custom_data[ $column ] ) && isset( $custom_data[ $column ]['value'] ) ) {
						$value = $custom_data[ $column ]['value'];
					}
					?>
					<td class="rtec-reg-<?php echo esc_attr( $column ); ?>"><?php echo rtec_admin_truncate_with_tooltip( wp_unslash( (string) $value ) ); ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	<?php else : ?>
		<tr>
			<td colspan="<?php echo (int) $overview_col_count; ?>" align="center"><?php esc_html_e( 'No Registrations Yet', 'registrations-for-the-events-calendar' ); ?></td>
		</tr>
	<?php endif; ?>
	<?php if ( $event_obj->pagination_needed ) : ?>
		<tr>
			<td colspan="<?php echo (int) $overview_col_count; ?>">
				<a href="<?php $this->the_detailed_view_href( $event_id, '' ); ?>" class="button rtec-wide rtec-view-all rtec-icon-text"><?php echo RTEC_Icon::get( 'plus' ); ?> <?php esc_html_e( 'Manage Registrations', 'registrations-for-the-events-calendar' ); ?></a>
			</td>
		</tr>
	<?php endif; ?>
	</tbody>
</table>
<?php
$show_venue_label = true;
