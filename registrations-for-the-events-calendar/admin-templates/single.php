<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// RTEC_ADMIN_URL
$rtec = RTEC();
$form = $rtec->form->instance();
$db   = $rtec->db_frontend->instance();

$admin_registrations = new RTEC_Admin_Registrations();
$tab                 = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'registrations'; // phpcs:ignore

$view_type    = isset( $_GET['v'] ) ? sanitize_key( $_GET['v'] ) : 'grid'; // phpcs:ignore
$query_type   = isset( $_GET['qtype'] ) ? sanitize_key( $_GET['qtype'] ) : 'upcoming'; // phpcs:ignore
$start_date   = isset( $_GET['start'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $_GET['start'] ) ) : gmdate( 'Y-m-d H:i:s' ); // phpcs:ignore
$reg_status   = isset( $_GET['with'] ) ? sanitize_key( $_GET['with'] ) : 'with'; // phpcs:ignore
$query_offset = isset( $_GET['off'] ) ? max( (int) $_GET['off'], 0 ) : 0; // phpcs:ignore
$event_id            = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore
$single_search       = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : ''; // phpcs:ignore
$registration_status = isset( $_GET['registration_status'] ) ? sanitize_key( $_GET['registration_status'] ) : 'active'; // phpcs:ignore

if ( ! $event_id || ! rtec_current_user_can_manage_event_registrations( $event_id ) ) {
	wp_die( esc_html__( 'You do not have permission to view registrations for this event.', 'registrations-for-the-events-calendar' ) );
}

$settings            = array(
	'v'     => $view_type,
	'qtype' => $query_type,
	'with'  => $reg_status,
	'off'   => $query_offset,
	'start' => $start_date,
	'id'    => $event_id,
);

$admin_registrations->build_admin_registrations( $tab, $settings );

$admin_registrations->the_registrations_detailed_view();

$form->build_form( $event_id );
$fields_atts = $form->get_field_attributes();
$event_meta  = $form->get_event_meta();
$event_obj   = new RTEC_Admin_Event();
$event_obj->build_admin_event( $event_id, 'single', '', $form );

// Status counts (from full list, before filters) for filter bar
$status_counts = array(
	'active'    => 0,
	'confirmed' => 0,
	'pending'   => 0,
);
foreach ( $event_obj->registrants_data as $row ) {
	$status_counts['active']++;
	$s = isset( $row['status'] ) ? $row['status'] : 'c';
	// Legacy 'n' is treated as confirmed; only 'p' is pending.
	if ( $s === 'c' || $s === 'n' ) {
		$status_counts['confirmed']++;
	} elseif ( $s === 'p' ) {
		$status_counts['pending']++;
	}
}

// Apply registration status filter
if ( $registration_status === 'confirmed' ) {
	$event_obj->registrants_data = array_values( array_filter( $event_obj->registrants_data, function ( $row ) {
		$s = isset( $row['status'] ) ? $row['status'] : 'c';
		return $s === 'c' || $s === 'n';
	} ) );
} elseif ( $registration_status === 'pending' ) {
	$event_obj->registrants_data = array_values( array_filter( $event_obj->registrants_data, function ( $row ) {
		$s = isset( $row['status'] ) ? $row['status'] : 'c';
		return $s === 'p';
	} ) );
}
// 'active' = no status filter (show all)

// Apply server-side search filter for single event view
if ( $single_search !== '' ) {
	$term = strtolower( trim( $single_search ) );
	$event_obj->registrants_data = array_filter( $event_obj->registrants_data, function ( $row ) use ( $term ) {
		$haystack = '';
		foreach ( array( 'first_name', 'last_name', 'first', 'last', 'email', 'phone', 'other' ) as $key ) {
			if ( ! empty( $row[ $key ] ) && is_string( $row[ $key ] ) ) {
				$haystack .= ' ' . $row[ $key ];
			}
		}
		$custom = isset( $row['custom'] ) ? $row['custom'] : null;
		if ( is_string( $custom ) ) {
			$custom = maybe_unserialize( $custom );
		}
		if ( is_array( $custom ) ) {
			$parts = array();
			foreach ( $custom as $v ) {
				if ( is_array( $v ) && isset( $v['value'] ) ) {
					$parts[] = is_scalar( $v['value'] ) ? (string) $v['value'] : '';
				} elseif ( is_scalar( $v ) ) {
					$parts[] = (string) $v;
				}
			}
			$haystack .= ' ' . implode( ' ', $parts );
		}
		return ( strpos( strtolower( $haystack ), $term ) !== false );
	} );
	$event_obj->registrants_data = array_values( $event_obj->registrants_data );
}
$admin_registrations->add_event_id_on_page( $event_id );

$custom_column_keys             = $event_obj->form_obj->get_custom_column_keys();
$custom_fields_label_name_pairs = $event_obj->form_obj->get_custom_fields_label_name_pairs();
$custom_fields_name_label_pairs = array_flip( $custom_fields_label_name_pairs );

$event_permalink = get_the_permalink( $event_id );
$event_edit_link  = get_edit_post_link( $event_id, 'raw' );
$event_edit_link  = $event_edit_link ? $event_edit_link . '#rtec-event-details' : '';

$submissions_base_url = add_query_arg(
	array(
		'page' => 'registrations-for-the-events-calendar',
		'tab'  => $tab,
		'id'   => $event_id,
	),
	admin_url( 'admin.php' )
);
if ( $single_search !== '' ) {
	$submissions_base_url = add_query_arg( 'search', $single_search, $submissions_base_url );
}
?>
<div class="rtec-detailed-view-bump"></div>
<a id="rtec-back-overview" href="<?php $admin_registrations->the_toolbar_href( 'tab', 'overview' ); ?>"><strong><?php esc_html_e( 'Back to Events', 'registrations-for-the-events-calendar' ); ?></strong> ⤴︎</a>

<input type="hidden" value="<?php echo esc_attr( $event_id ); ?>" name="event_id">
<div class="rtec-wrapper rtec-single<?php echo $event_obj->get_single_event_wrapper_classes(); ?>">

	<div class="rtec-single-event" data-rtec-event-id="<?php echo esc_attr( $event_id ); ?>" data-rtec-mvt-id="" data-rtec-field-atts="<?php echo esc_attr( wp_json_encode( $fields_atts ) ); ?>">
		<div class="rtec-event-meta">
			<?php do_action( 'rtec_registrations_tab_event_meta', $event_obj ); ?>
			<h2 class="nav-tab-wrapper rtec-subtabs">
				<a href="#" class="nav-tab nav-tab-active"><?php esc_html_e( 'Submissions', 'registrations-for-the-events-calendar' ); ?></a>
				<a href="#" class="nav-tab rtec-modal-opener" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'payments', 'location' => 'single-event-nav' ) ) ); ?>"><?php esc_html_e( 'Payments', 'registrations-for-the-events-calendar' ); ?> <span class="rtec-pro-pill">Pro</span></a>
				<a href="#" class="nav-tab rtec-modal-opener" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'message-history', 'location' => 'single-event-nav' ) ) ); ?>"><?php esc_html_e( 'Message History', 'registrations-for-the-events-calendar' ); ?> <span class="rtec-pro-pill">Pro</span></a>
				<a href="#" class="nav-tab rtec-modal-opener" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'attendance', 'location' => 'single-event-nav' ) ) ); ?>"><?php esc_html_e( 'Attendance', 'registrations-for-the-events-calendar' ); ?> <span class="rtec-pro-pill">Pro</span></a>
			</h2>
		</div>

		<div class="rtec-filters-actions-row rtec-reg-info">
			<div class="rtec-filters-wrap">
				<div class="rtec-attendance-status-bar rtec-attendance-status-links">
					<?php
					$reg_statuses = array(
						'active'    => __( 'Active', 'registrations-for-the-events-calendar' ),
						'confirmed' => __( 'Confirmed', 'registrations-for-the-events-calendar' ),
						'pending'   => __( 'Pending', 'registrations-for-the-events-calendar' ),
					);
					foreach ( $reg_statuses as $status_key => $status_label ) :
						$count  = isset( $status_counts[ $status_key ] ) ? (int) $status_counts[ $status_key ] : 0;
						$url    = ( $status_key === 'active' ) ? remove_query_arg( 'registration_status', $submissions_base_url ) : add_query_arg( 'registration_status', $status_key, $submissions_base_url );
						$current = ( $status_key === 'active' && ( $registration_status === 'active' || $registration_status === '' ) ) || $registration_status === $status_key;
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="rtec-attendance-status-link<?php echo $current ? ' rtec-attendance-status-current' : ''; ?>"><?php echo esc_html( $status_label ); ?> (<?php echo (int) $count; ?>)</a>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="rtec-event-actions-top">
				<a href="<?php echo esc_url( $event_permalink ); ?>" class="rtec-admin-secondary-button button action rtec-icon-text" target="_blank" rel="noopener noreferrer"><?php echo RTEC_Icon::get( 'eye' ); ?> <?php esc_html_e( 'View Event', 'registrations-for-the-events-calendar' ); ?></a>
				<?php if ( $event_edit_link && current_user_can( 'edit_posts' ) ) : ?>
					<a href="<?php echo esc_url( $event_edit_link ); ?>" class="rtec-admin-secondary-button button action rtec-icon-text" target="_blank" rel="noopener noreferrer"><?php echo RTEC_Icon::get( 'edit' ); ?> <?php esc_html_e( 'Event Options', 'registrations-for-the-events-calendar' ); ?></a>
				<?php endif; ?>
				<input type="hidden" id="rtec-event-id" name="rtec-event-id" value="<?php echo esc_attr( $event_id ); ?>">
				<input type="hidden" id="rtec-tab" name="rtec-tab" value="<?php echo esc_attr( $tab ); ?>">
				<div class="rtec-search-box">
					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="rtec-single-search-form" role="search">
						<input type="hidden" name="page" value="registrations-for-the-events-calendar">
						<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
						<input type="hidden" name="id" value="<?php echo esc_attr( $event_id ); ?>">
						<?php if ( $registration_status !== '' && $registration_status !== 'active' ) : ?>
							<input type="hidden" name="registration_status" value="<?php echo esc_attr( $registration_status ); ?>">
						<?php endif; ?>
						<p class="search-box">
							<span class="rtec-toolbar-icon"><?php echo RTEC_Icon::get( 'search' ); ?></span>
							<label class="screen-reader-text" for="ru-single-search"><?php esc_html_e( 'Search registrations', 'registrations-for-the-events-calendar' ); ?></label>
							<input type="search" id="ru-single-search" name="search" value="<?php echo esc_attr( $single_search ); ?>" placeholder="<?php esc_attr_e( 'Search', 'registrations-for-the-events-calendar' ); ?>">
						</p>
					</form>
				</div>
			</div>
		</div>

		<?php
		$single_view_data_columns = array_values( array_diff( $event_obj->columns, array( 'first_name', 'last_name' ) ) );
		$single_view_col_count    = 3 + count( $single_view_data_columns ); // checkbox + Name + Status + data columns
		$status_labels            = array(
			'c' => __( 'Confirmed', 'registrations-for-the-events-calendar' ),
			'p' => __( 'Pending', 'registrations-for-the-events-calendar' ),
			'n' => __( 'New', 'registrations-for-the-events-calendar' ),
		);
		?>
		<div class="rtec-table-wrap">
		<table class="widefat wp-list-table fixed striped posts rtec-registrations-data">
		<thead>
		<?php include rtec_plugin_path( 'admin-templates/partials/registrations-single-thead.php' ); ?>
		</thead>
		<?php if ( ! empty( $event_obj->registrants_data ) ) : ?>
			<tbody>
			<?php foreach ( $event_obj->registrants_data as $registration ) : ?>
				<?php
				$custom_data = isset( $registration['custom'] ) ? maybe_unserialize( $registration['custom'] ) : array();
				$is_user     = isset( $registration['user_id'] ) && (int) $registration['user_id'] > 0 ? true : false;
				if ( isset( $registration['registration_date'] ) ) {
					$time_format                       = rtec_get_time_format();
					$registration['registration_date'] = date_i18n( 'F jS, ' . $time_format, strtotime( $registration['registration_date'] ) + rtec_get_time_zone_offset() );
				}
				?>
				<tr class="rtec-reg-row<?php echo $admin_registrations->get_registrant_tr_classes( $registration['status'], $is_user ); ?>" data-rtec-id="<?php echo esc_attr( (int) $registration['id'] ); ?>">
					<td scope="row" class="check-column rtec-checkbox" data-colname="<?php esc_attr_e( 'Select', 'registrations-for-the-events-calendar' ); ?>">
						<label class="screen-reader-text" for="rtec-select-<?php echo esc_attr( (int) $registration['id'] ); ?>"><?php esc_html_e( 'Select', 'registrations-for-the-events-calendar' ); ?> <?php
						$screen_reader_name = trim( ( isset( $registration['first_name'] ) ? $registration['first_name'] : '' ) . ' ' . ( isset( $registration['last_name'] ) ? $registration['last_name'] : '' ) );
						if ( $screen_reader_name === '' && ! empty( $registration['email'] ) ) {
							$screen_reader_name = $registration['email'];
						}
						echo esc_html( $screen_reader_name );
						?></label>
						<input type="checkbox" value="<?php echo esc_attr( (int) $registration['id'] ); ?>" id="rtec-select-<?php echo esc_attr( (int) $registration['id'] ); ?>" class="rtec-registration-select check-column" data-rtec-registration="<?php echo str_replace( '\:', '&#92;&#58;', esc_attr( wp_json_encode( $registration ) ) ); ?>">
						<div class="locked-indicator"></div>
					</td>
					<?php include rtec_plugin_path( 'admin-templates/partials/registrations-main-column.php' ); ?>
					<?php
					$reg_status = isset( $registration['status'] ) ? $registration['status'] : 'c';
					// Legacy 'n' displayed as confirmed; only c and p are valid statuses.
					$reg_status = ( $reg_status === 'p' ) ? 'p' : 'c';
					$reg_status_label = isset( $status_labels[ $reg_status ] ) ? $status_labels[ $reg_status ] : $status_labels['c'];
					?>
					<td class="rtec-data-cell rtec-data-group-1 rtec-reg-status" data-colname="<?php esc_attr_e( 'Status', 'registrations-for-the-events-calendar' ); ?>" data-rtec-key="status" data-rtec-value="<?php echo esc_attr( $reg_status ); ?>"><span class="rtec-status-badge rtec-status-reg rtec-status-<?php echo esc_attr( $reg_status ); ?>"><?php echo esc_html( $reg_status_label ); ?></span></td>
					<?php
					$col_ii   = 0;
					$col_group = 0;
					foreach ( $event_obj->columns as $col_index => $column ) : ?>
						<?php if ( in_array( $column, array( 'first_name', 'last_name' ), true ) ) { continue; } ?>
						<?php
						if ( $col_ii % 4 === 0 ) {
							$col_group++;
						}
						$td_group_class = ' rtec-data-group-' . $col_group;
						$col_label = isset( $event_obj->labels[ $col_index ] ) ? $event_obj->labels[ $col_index ] : $column;
						$col_ii++;
						?>
						<?php if ( $column === 'registration_date' ) : ?>
							<td class="rtec-data-cell rtec-reg-registration_date<?php echo esc_attr( $td_group_class ); ?>" data-colname="<?php echo esc_attr( wp_strip_all_tags( $col_label ) ); ?>" data-rtec-value="<?php echo esc_attr( wp_unslash( $registration['registration_date'] ) ); ?>"><?php echo rtec_admin_truncate_with_tooltip( wp_unslash( $registration['registration_date'] ) ); ?></td>
						<?php elseif ( $column === 'venue' ) : ?>
							<td class="rtec-data-cell rtec-reg-<?php echo esc_attr( $column ); ?><?php echo esc_attr( $td_group_class ); ?>" data-colname="<?php echo esc_attr( wp_strip_all_tags( $col_label ) ); ?>" data-rtec-key="<?php echo esc_attr( $column ); ?>" data-rtec-value="
																			<?php
																			if ( isset( $event_meta['mvt_fields'][ $registration[ $column ] ]['label'] ) ) {
																				echo esc_html( $event_meta['mvt_fields'][ $registration[ $column ] ]['label'] );
																			} else {
																				echo esc_html( wp_unslash( $registration[ $column ] ) ); }
																			?>
							">
							<?php
							$venue_display = isset( $event_meta['mvt_fields'][ $registration[ $column ] ]['label'] ) ? $event_meta['mvt_fields'][ $registration[ $column ] ]['label'] : wp_unslash( $registration[ $column ] );
							echo rtec_admin_truncate_with_tooltip( $venue_display );
							?>
</td>
						<?php elseif ( $column === 'phone' ) : ?>
							<td class="rtec-data-cell rtec-reg-<?php echo esc_attr( $column ); ?><?php echo esc_attr( $td_group_class ); ?>" data-colname="<?php echo esc_attr( wp_strip_all_tags( $col_label ) ); ?>" data-rtec-key="<?php echo esc_attr( $column ); ?>" data-rtec-value="<?php echo esc_attr( rtec_format_phone_number( $registration[ $column ] ) ); ?>">
                                <?php echo rtec_admin_truncate_with_tooltip( rtec_format_phone_number( $registration[ $column ] ) );
                                echo '<input class="rtec-custom-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $registration[ $column ] ) . '">';
                                echo '</td>';
                                ?>
							<?php
						elseif ( in_array( $column, $custom_column_keys, true ) ) :
							// check what data structure is being used
							$dep_data_structure = false;
							if ( isset( $custom_fields_name_label_pairs[ $column ] ) && isset( $custom_data[ $custom_fields_name_label_pairs[ $column ] ] ) ) {
								$dep_data_structure = true;
							}
							if ( $dep_data_structure === false ) :
								$value = isset( $custom_data[ $column ]['value'] ) ? $custom_data[ $column ]['value'] : '';
								echo '<td class="rtec-data-cell rtec-reg-custom' . esc_attr( $td_group_class ) . '" data-colname="' . esc_attr( wp_strip_all_tags( $col_label ) ) . '" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( wp_unslash( $value ) ) . '">';
								echo rtec_admin_truncate_with_tooltip( wp_unslash( $value ) );
								echo '<input class="rtec-custom-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $value ) . '">';
								echo '</td>';

							elseif ( is_array( $custom_data ) && isset( $custom_data[ $column ] ) && is_array( $custom_data[ $column ] ) ) :
								$value = isset( $custom_data[ $column ]['value'] ) ? $custom_data[ $column ]['value'] : '';
								echo '<td class="rtec-data-cell rtec-reg-custom' . esc_attr( $td_group_class ) . '" data-colname="' . esc_attr( wp_strip_all_tags( $col_label ) ) . '" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( wp_unslash( $value ) ) . '">';
								echo rtec_admin_truncate_with_tooltip( wp_unslash( $value ) );
								echo '<input class="rtec-standard-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $value ) . '">';
								echo '</td>';
                            elseif ( isset( $custom_data[ $custom_fields_name_label_pairs[ $column ] ] ) ) :
								$value = $custom_data[ $custom_fields_name_label_pairs[ $column ] ];
								echo '<td class="rtec-data-cell rtec-reg-custom' . esc_attr( $td_group_class ) . '" data-colname="' . esc_attr( wp_strip_all_tags( $col_label ) ) . '" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( wp_unslash( $value ) ) . '">';
								echo rtec_admin_truncate_with_tooltip( wp_unslash( $value ) );
								echo '<input class="rtec-standard-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $value ) . '">';
								echo '</td>';
							endif;
						else :
							$value = isset( $registration[ $column ] ) ? $registration[ $column ] : '';
							?>
							<td class="rtec-data-cell rtec-reg-<?php echo esc_attr( $column ); ?><?php echo esc_attr( $td_group_class ); ?>" data-colname="<?php echo esc_attr( wp_strip_all_tags( $col_label ) ); ?>" data-rtec-key="<?php echo esc_attr( $column ); ?>" data-rtec-value="<?php echo esc_attr( wp_unslash( $value ) ); ?>">
                            <?php echo rtec_admin_truncate_with_tooltip( wp_unslash( $value ) );
                            echo '<input class="rtec-standard-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $value ) . '">';
                            echo '</td>'; ?>
						<?php endif; ?>
					<?php endforeach; ?>

				</tr>
			<?php endforeach; ?>
			</tbody>
			<?php if ( count( $event_obj->registrants_data ) > 14 ) : ?>
				<tfoot>
				<?php include rtec_plugin_path( 'admin-templates/partials/registrations-single-thead.php' ); ?>
				</tfoot>
			<?php endif; ?>
		<?php else : ?>
			<tbody>
			<tr class="rtec-reg-row" data-rtec-id="" style="display: none;">
				<td scope="row" class="check-column rtec-checkbox">
					<label class="screen-reader-text" for="rtec-select-"><?php esc_html_e( 'Select', 'registrations-for-the-events-calendar' ); ?></label>
					<input type="checkbox" value="" id="rtec-select-" class="rtec-registration-select check-column" data-rtec-registration="{}">
					<div class="locked-indicator"></div>
				</td>
				<td class="rtec-identifier-column"></td>
				<?php
				$empty_col_ii = 0;
				$empty_col_group = 0;
				foreach ( $event_obj->columns as $column ) : ?>
					<?php if ( in_array( $column, array( 'first_name', 'last_name' ), true ) ) { continue; } ?>
					<?php
					if ( $empty_col_ii % 4 === 0 ) {
						$empty_col_group++;
					}
					$empty_td_group = ' rtec-data-group-' . $empty_col_group;
					$empty_col_ii++;
					?>
					<?php if ( $column === 'registration_date' ) : ?>
						<td class="rtec-data-cell rtec-reg-registration_date<?php echo esc_attr( $empty_td_group ); ?>"></td>
					<?php elseif ( $column === 'venue' ) : ?>
						<td class="rtec-data-cell rtec-reg-<?php echo esc_attr( $column ); ?><?php echo esc_attr( $empty_td_group ); ?>" data-rtec-key="<?php echo esc_attr( $column ); ?>"></td>
					<?php elseif ( in_array( $column, $custom_column_keys, true ) ) : ?>
						<td class="rtec-data-cell rtec-reg-custom<?php echo esc_attr( $empty_td_group ); ?>" data-rtec-custom-key="<?php echo esc_attr( $column ); ?>"></td>
					<?php else : ?>
						<td class="rtec-data-cell rtec-reg-<?php echo esc_attr( $column ); ?><?php echo esc_attr( $empty_td_group ); ?>" data-rtec-key="<?php echo esc_attr( $column ); ?>"></td>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>
			<tr>
				<td colspan="4" align="center"><?php esc_html_e( 'No Registrations Yet', 'registrations-for-the-events-calendar' ); ?></td>
			</tr>
			</tbody>
		<?php endif; // registrations not empty ?>
		</table>
		</div><!-- .rtec-table-wrap -->
		<?php
		$cap       = apply_filters( 'rtec_registration_actions_capability', 'edit_posts' );
		$event_post = get_post( $event_id );
		if ( current_user_can( $cap ) ) :
			?>
		<div class="rtec-event-actions rtec-clear">
			<div class="tablenav">
				<?php if ( $event_post ) : ?>
					<button type="button" class="button action rtec-admin-secondary-button rtec-icon-text" data-rtec-modal-content="manage-registration" data-rtec-event-id="<?php echo esc_attr( (string) $event_id ); ?>" data-rtec-registration-id="0"><?php echo RTEC_Icon::get( 'plus' ); ?> <?php esc_html_e( 'Add New', 'registrations-for-the-events-calendar' ); ?></button>
				<?php endif; ?>
				<button class="button action rtec-action rtec-admin-secondary-button rtec-icon-text" data-rtec-action="delete"><?php echo RTEC_Icon::get( 'minus' ); ?> <?php esc_html_e( 'Delete Selected', 'registrations-for-the-events-calendar' ); ?></button>

				<form method="post" id="rtec_csv_export_form" action="">
					<?php wp_nonce_field( 'rtec_csv_export', 'rtec_csv_export_nonce' ); ?>
					<input type="hidden" name="rtec_id" value="<?php echo esc_attr( $event_id ); ?>" />
					<button type="submit" name="rtec_event_csv" class="button action rtec-admin-secondary-button rtec-icon-text"><?php echo RTEC_Icon::get( 'export' ); ?> <?php esc_html_e( 'Export (.csv)', 'registrations-for-the-events-calendar' ); ?></button>
				</form>
				<?php do_action( 'rtec_registrations_tab_event_actions', $event_id ); ?>
				<button type="button" class="button action rtec-action-confirm rtec-admin-secondary-button rtec-modal-opener rtec-pro-action-button-with-pill" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'confirm-selected', 'location' => 'single-event-actions' ) ) ); ?>"><span class="rtec-pro-action-button-label rtec-icon-text"><?php echo RTEC_Icon::get( 'check' ); ?> <?php esc_html_e( 'Confirm Selected', 'registrations-for-the-events-calendar' ); ?></span><span class="rtec-pro-pill">Pro</span></button>
				<button type="button" class="button action rtec-action-process-waiting rtec-admin-secondary-button rtec-modal-opener rtec-pro-action-button-with-pill" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'process-waiting-selected', 'location' => 'single-event-actions' ) ) ); ?>"><span class="rtec-pro-action-button-label rtec-icon-text"><?php echo RTEC_Icon::get( 'clock' ); ?> <?php esc_html_e( 'Process Waiting List', 'registrations-for-the-events-calendar' ); ?></span><span class="rtec-pro-pill">Pro</span></button>
				<button type="button" class="button action rtec-action-bulk-email rtec-admin-secondary-button rtec-modal-opener rtec-pro-action-button-with-pill" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'email-selected', 'location' => 'single-event-actions' ) ) ); ?>"><span class="rtec-pro-action-button-label rtec-icon-text"><?php echo RTEC_Icon::get( 'email' ); ?> <?php esc_html_e( 'Email Selected', 'registrations-for-the-events-calendar' ); ?></span><span class="rtec-pro-pill">Pro</span></button>
				<button type="button" class="button action rtec-action rtec-admin-secondary-button rtec-modal-opener rtec-pro-action-button-with-pill" data-content="ajax" data-rtec-ajax="<?php echo esc_attr( wp_json_encode( array( 'action' => 'rtec_get_upsell_modal', 'type' => 'transfer-selected', 'location' => 'single-event-actions' ) ) ); ?>"><span class="rtec-pro-action-button-label rtec-icon-text"><?php echo RTEC_Icon::get( 'exchange' ); ?> <?php esc_html_e( 'Transfer', 'registrations-for-the-events-calendar' ); ?></span><span class="rtec-pro-pill">Pro</span></button>
			</div>
		</div>
		<?php endif; ?>
	</div> <!-- rtec-single-event -->

</div> <!-- rtec-single-wrapper -->

<?php
require_once rtec_plugin_path( 'admin-templates/rtec-content-modal/shell.php' );
do_action( 'rtec_registrations_tab_after_single', $event_obj );
?>
