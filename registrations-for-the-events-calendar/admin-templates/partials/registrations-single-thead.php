<?php
/**
 * Single-event registrations table header row (reused in thead and tfoot).
 *
 * @var RTEC_Admin_Event $event_obj In scope from parent template.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>
<tr>
	<td class="manage-column column-rtec check-column">
		<label class="screen-reader-text" for="rtec-select-all-1"><?php esc_html_e( 'Select All', 'registrations-for-the-events-calendar' ); ?></label>
		<input type="checkbox" id="rtec-select-all-1">
	</td>
	<th scope="col" class="column-primary"><?php esc_html_e( 'Name', 'registrations-for-the-events-calendar' ); ?></th>
	<th scope="col" class="rtec-reg-status-th rtec-data-cell rtec-data-group-1"><?php esc_html_e( 'Status', 'registrations-for-the-events-calendar' ); ?></th>
	<?php
	$name_columns = array( 'first_name', 'last_name' );
	$num_data_cols = 0;
	foreach ( $event_obj->columns as $col_index => $column ) {
		if ( in_array( $column, $name_columns, true ) ) {
			continue;
		}
		$num_data_cols++;
	}
	$data_col_index = 0;
	$group          = 1;
	foreach ( $event_obj->columns as $col_index => $column ) :
		if ( in_array( $column, $name_columns, true ) ) {
			continue;
		}
		$label = isset( $event_obj->labels[ $col_index ] ) ? $event_obj->labels[ $col_index ] : $column;
		if ( $data_col_index % 4 === 0 ) {
			$group      = (int) ( $data_col_index / 4 ) + 1;
			$arrow_left = ( $data_col_index > 0 )
				? '<div class="rtec-data-nav-wrap rtec-left"><div class="rtec-data-nav rtec-arrow-left" data-next-index="' . (int) ( $group - 1 ) . '">' . RTEC_Icon::get( 'chevron-left' ) . '</div></div>'
				: '';
		} else {
			$arrow_left = '';
		}
		$arrow_right = ( ( $data_col_index + 1 ) % 4 === 0 && ( $data_col_index + 1 ) < $num_data_cols )
			? '<div class="rtec-data-nav-wrap rtec-right"><div class="rtec-data-nav rtec-arrow-right" data-next-index="' . (int) ( $group + 1 ) . '">' . RTEC_Icon::get( 'chevron-right' ) . '</div></div>'
			: '';

		$nav_class = '';
		if ( $arrow_left && $arrow_right ) {
			$nav_class = ' rtec-has-data-nav rtec-has-data-nav-both';
		} elseif ( $arrow_left ) {
			$nav_class = ' rtec-has-data-nav rtec-has-data-nav-left';
		} elseif ( $arrow_right ) {
			$nav_class = ' rtec-has-data-nav rtec-has-data-nav-right';
		}

		$data_col_index++;
		if ( $data_col_index === $num_data_cols ) {
			$arrow_right = '';
		}
		?>
		<th scope="col" class="rtec-data-cell rtec-data-group-<?php echo (int) $group; ?><?php echo esc_attr( $nav_class ); ?>"><?php echo $arrow_left . esc_html( wp_unslash( $label ) ) . $arrow_right; ?></th>
	<?php endforeach; ?>
</tr>
