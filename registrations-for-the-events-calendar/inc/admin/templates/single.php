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
$event_id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore
$settings     = array(
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
$admin_registrations->add_event_id_on_page( $event_id );

$custom_column_keys             = $event_obj->form_obj->get_custom_column_keys();
$custom_fields_label_name_pairs = $event_obj->form_obj->get_custom_fields_label_name_pairs();
$custom_fields_name_label_pairs = array_flip( $custom_fields_label_name_pairs );

?>
<h1><?php esc_html_e( 'Single Event Details', 'registrations-for-the-events-calendar' ); ?></h1>
<a id="rtec-back-overview" href="<?php $admin_registrations->the_toolbar_href( 'tab', 'registrations' ); ?>"><?php esc_html_e( 'Back to Overview', 'registrations-for-the-events-calendar' ); ?></a>

<input type="hidden" value="<?php echo esc_attr( $event_id ); ?>" name="event_id">
<div class="rtec-wrapper rtec-single<?php echo $event_obj->get_single_event_wrapper_classes(); ?>">

	<div class="rtec-single-event" data-rtec-event-id="<?php echo esc_attr( $event_id ); ?>" data-rtec-field-atts="<?php echo esc_attr( wp_json_encode( $fields_atts ) ); ?>">
		<h2 class="nav-tab-wrapper rtec-subtabs">
			<a href="#" class="nav-tab nav-tab-active">

				<?php esc_html_e( 'Form Submissions', 'registrations-for-the-events-calendar' ); ?></a>
			<a href="#" class="nav-tab rtec-modal-opener" data-content="payments"><?php esc_html_e( 'Payments', 'registrations-for-the-events-calendar' ); ?>
				<div class="rtec-pro-pill">Pro <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>
				</div>
			</a>
			<a href="#" class="nav-tab rtec-modal-opener" data-content="message-history"><?php esc_html_e( 'Message History', 'registrations-for-the-events-calendar' ); ?>
				<div class="rtec-pro-pill">Pro <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>
				</div>
			</a>
		</h2>
		<div class="rtec-event-meta">
			<?php do_action( 'rtec_registrations_tab_event_meta', $event_obj ); ?>
		</div>

		<table class="widefat wp-list-table fixed striped posts rtec-registrations-data"">
		<thead>
		<tr>
			<td scope="col" class="manage-column column-rtec check-column">
				<label class="screen-reader-text" for="rtec-select-all-1"><?php esc_html_e( 'Select All', 'registrations-for-the-events-calendar' ); ?></label>
				<input type="checkbox" id="rtec-select-all-1">
			</td>
			<?php foreach ( $event_obj->labels as $label ) : ?>
				<th><?php echo esc_html( stripslashes( $label ) ); ?></th>
			<?php endforeach; ?>
		</tr>
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
					<td scope="row" class="check-column rtec-checkbox">
						<label class="screen-reader-text" for="rtec-select-<?php echo esc_attr( (int) $registration['id'] ); ?>">Select <?php echo esc_html( $registration['first_name'] ) . ' ' . esc_html( $registration['last_name'] ); ?></label>
						<input type="checkbox" value="<?php echo esc_attr( (int) $registration['id'] ); ?>" id="rtec-select-<?php echo esc_attr( (int) $registration['id'] ); ?>" class="rtec-registration-select check-column" data-rtec-registration="<?php echo str_replace( '\:', '&#92;&#58;', esc_attr( json_encode( $registration ) ) ); ?>">
						<div class="locked-indicator"></div>
						<?php echo $admin_registrations->get_registrant_icons( $registration['status'], $is_user ); ?>
					</td>
					<?php foreach ( $event_obj->columns as $column ) : ?>
						<?php if ( $column === 'registration_date' ) : ?>
							<td class="rtec-data-cell rtec-reg-registration_date" data-rtec-value="<?php echo esc_attr( stripslashes( $registration['registration_date'] ) ); ?>"><?php echo esc_html( stripslashes( $registration['registration_date'] ) ); ?></td>
						<?php elseif ( $column === 'venue' ) : ?>
							<td class="rtec-data-cell rtec-reg-<?php echo $column; ?>" data-rtec-key="<?php echo $column; ?>" data-rtec-value="
																			<?php
																			if ( isset( $event_meta['mvt_fields'][ $registration[ $column ] ]['label'] ) ) {
																				echo esc_html( $event_meta['mvt_fields'][ $registration[ $column ] ]['label'] );
																			} else {
																				echo esc_html( stripslashes( $registration[ $column ] ) ); }
																			?>
							">
							<?php
							if ( isset( $event_meta['mvt_fields'][ $registration[ $column ] ]['label'] ) ) {
								echo esc_html( $event_meta['mvt_fields'][ $registration[ $column ] ]['label'] );
							} else {
								echo esc_html( stripslashes( $registration[ $column ] ) ); }
							?>
</td>
						<?php elseif ( $column === 'phone' ) : ?>
							<td class="rtec-data-cell rtec-reg-<?php echo $column; ?>" data-rtec-key="<?php echo $column; ?>" data-rtec-value="<?php echo esc_attr( rtec_format_phone_number( $registration[ $column ] ) ); ?>">
                                <?php echo '<span>' . esc_html( rtec_format_phone_number( $registration[ $column ] ) ) . '</span>';
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
								echo '<td class="rtec-data-cell rtec-reg-custom" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( wp_unslash( $value ) ) . '">';
								echo '<span>' . esc_html( wp_unslash( $value ) ) . '</span>';
								echo '<input class="rtec-custom-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $value ) . '">';
								echo '</td>';

							elseif ( is_array( $custom_data ) && isset( $custom_data[ $column ] ) && is_array( $custom_data[ $column ] ) ) :
								$value = isset( $custom_data[ $column ]['value'] ) ? $custom_data[ $column ]['value'] : '';
								echo '<td class="rtec-data-cell rtec-reg-custom" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( wp_unslash( $value ) ) . '">';
                                echo '<span>' . esc_html( wp_unslash( $value ) ) . '</span>';
								echo '<input class="rtec-standard-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $value ) . '">';
								echo '</td>';
                            elseif ( isset( $custom_data[ $custom_fields_name_label_pairs[ $column ] ] ) ) :
								$value = $custom_data[ $custom_fields_name_label_pairs[ $column ] ];
								echo '<td class="rtec-data-cell rtec-reg-custom" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( wp_unslash( $value ) ) . '">' . esc_html( stripslashes( $value ) );
	                            echo '<span>' . esc_html( wp_unslash( $value ) ) . '</span>';
	                            echo '<input class="rtec-standard-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $value ) . '">';
	                            echo '</td>';
							endif;
						else :
							$value = isset( $registration[ $column ] ) ? $registration[ $column ] : '';
							?>
							<td class="rtec-data-cell rtec-reg-<?php echo $column; ?>" data-rtec-key="<?php echo $column; ?>" data-rtec-value="<?php echo esc_attr( wp_unslash( $value ) ); ?>">
                            <?php echo '<span>' . esc_html( wp_unslash( $value ) ) . '</span>';
                            echo '<input class="rtec-standard-input rtec-edit-input" type="text" name="' . esc_attr( $column ) . '" value="' . esc_attr( $value ) . '">';
                            echo '</td>'; ?>
						<?php endif; ?>
					<?php endforeach; ?>

				</tr>
			<?php endforeach; ?>
			</tbody>
			<?php if ( count( $event_obj->registrants_data ) > 14 ) : ?>
				<tfoot>
				<tr>
					<td scope="col" class="manage-column column-rtec check-column">
						<label class="screen-reader-text" for="rtec-select-all-1"><?php esc_html_e( 'Select All', 'registrations-for-the-events-calendar' ); ?></label>
						<input type="checkbox" id="rtec-select-all-1">
					</td>
					<?php foreach ( $event_obj->labels as $label ) : ?>
						<?php if ( ! empty( $label ) ) : ?>
							<th><?php echo esc_html( stripslashes( $label ) ); ?></th>
						<?php endif; ?>
					<?php endforeach; ?>
				</tr>
				</tfoot>
			<?php endif; ?>
		<?php else : ?>
			<tbody>
			<tr class="rtec-reg-row" data-rtec-id="" style="display: none;">
				<td scope="row" class="check-column rtec-checkbox">
					<label class="screen-reader-text" for="rtec-select-">Select </label>
					<input type="checkbox" value="" id="rtec-select-" class="rtec-registration-select check-column" data-rtec-registration="{}">
					<div class="locked-indicator"></div>
				</td>
				<?php foreach ( $event_obj->columns as $column ) : ?>
					<?php if ( $column === 'registration_date' ) : ?>
						<td class="rtec-data-cell rtec-reg-registration_date"></td>
					<?php elseif ( $column === 'venue' ) : ?>
						<td class="rtec-data-cell rtec-reg-<?php echo $column; ?>" data-rtec-key="<?php echo $column; ?>"></td>
						<?php
					elseif ( in_array( $column, $custom_column_keys, true ) ) :
						// check what data structure is being used
						$dep_data_structure = false;
						if ( isset( $custom_fields_name_label_pairs[ $column ] ) && isset( $custom_data[ $custom_fields_name_label_pairs[ $column ] ] ) ) {
							$dep_data_structure = true;
						}
						if ( $dep_data_structure === false ) :
							$value = isset( $custom_data[ $column ]['value'] ) ? $custom_data[ $column ]['value'] : '';
							echo '<td class="rtec-data-cell rtec-reg-custom" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( stripslashes( $value ) ) . '">' . esc_html( stripslashes( $value ) ) . '</td>';
						elseif ( is_array( $custom_data ) && is_array( $custom_data[ $column ] ) ) :
							$value = isset( $custom_data[ $column ]['value'] ) ? $custom_data[ $column ]['value'] : '';
							echo '<td class="rtec-data-cell rtec-reg-custom" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( stripslashes( $value ) ) . '">' . esc_html( stripslashes( $value ) ) . '</td>';
						elseif ( isset( $custom_data[ $custom_fields_name_label_pairs[ $column ] ] ) ) :
							$value = $custom_data[ $custom_fields_name_label_pairs[ $column ] ];
							echo '<td class="rtec-data-cell rtec-reg-custom" data-rtec-custom-key="' . esc_attr( $column ) . '" data-rtec-value="' . esc_attr( stripslashes( $value ) ) . '">' . esc_html( stripslashes( $value ) ) . '</td>';
						endif;
					else :
						?>
						<td class="rtec-data-cell rtec-reg-<?php echo esc_attr( $column ); ?>" data-rtec-key="<?php echo esc_attr( $column ); ?>"></td>
					<?php endif; ?>
				<?php endforeach; ?>

			</tr>
			<tr>
				<td colspan="6" align="center"><?php esc_html_e( 'No Registrations Yet', 'registrations-for-the-events-calendar' ); ?></td>
			</tr>
			</tbody>
		<?php endif; // registrations not empty ?>
		</table>
		<?php
		$cap = apply_filters( 'rtec_registration_actions_capability', 'edit_posts' );
		if ( current_user_can( $cap ) ) :
			?>
		<div class="rtec-event-actions rtec-clear">
			<div class="tablenav">
				<button class="button action rtec-action rtec-admin-secondary-button" data-rtec-action="delete"><i class="fa fa-minus" aria-hidden="true"></i> <?php esc_html_e( 'Delete Selected', 'registrations-for-the-events-calendar' ); ?></button>
				<button class="button action rtec-action rtec-admin-secondary-button" data-rtec-action="edit"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> <?php esc_html_e( 'Edit Selected', 'registrations-for-the-events-calendar' ); ?></button>
				<button class="button action rtec-action rtec-admin-secondary-button" data-rtec-action="add"><i class="fa fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Add New', 'registrations-for-the-events-calendar' ); ?></button>

				<form method="post" id="rtec_csv_export_form" action="">
					<?php wp_nonce_field( 'rtec_csv_export', 'rtec_csv_export_nonce' ); ?>
					<input type="hidden" name="rtec_id" value="<?php echo esc_attr( $event_id ); ?>" />
					<button type="submit" name="rtec_event_csv" class="button action rtec-admin-secondary-button"><i class="fa fa-download" aria-hidden="true"></i> <?php esc_html_e( 'Export (.csv)', 'registrations-for-the-events-calendar' ); ?></button>
				</form>
				<?php do_action( 'rtec_registrations_tab_event_actions', $event_id ); ?>
				<div class="rtec-pro-action-button-wrap">
					<div class="rtec-pro-pill">Pro <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>
					<button class="button action rtec-action-confirm rtec-admin-secondary-button rtec-modal-opener" data-content="confirm-selected"><i class="fa fa-flag" aria-hidden="true"></i> <?php esc_html_e( 'Confirm Selected', 'registrations-for-the-events-calendar' ); ?></button>
				</div>
				<div class="rtec-pro-action-button-wrap">
					<div class="rtec-pro-pill">Pro <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>
					<button class="button action rtec-action-process-waiting rtec-admin-secondary-button rtec-modal-opener" data-content="process-waiting-selected"><i class="fa fa-clock-o" aria-hidden="true"></i> <?php esc_html_e( 'Process Waiting List', 'registrations-for-the-events-calendar' ); ?></button>
				</div>
				<div class="rtec-pro-action-button-wrap">
					<div class="rtec-pro-pill">Pro <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>
					<button class="button action rtec-action-bulk-email rtec-admin-secondary-button rtec-modal-opener" data-content="email-selected"><i class="fa fa-envelope-o" aria-hidden="true"></i> <?php esc_html_e( 'Email Selected', 'registrations-for-the-events-calendar' ); ?></button>
				</div>
				<div class="rtec-pro-action-button-wrap">
					<div class="rtec-pro-pill">Pro <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2v82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9V380.8c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg></div>
					<button class="button action rtec-action rtec-admin-secondary-button rtec-modal-opener" data-content="transfer-selected"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="exchange-alt" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-exchange-alt fa-w-16"><path fill="currentColor" d="M0 168v-16c0-13.255 10.745-24 24-24h360V80c0-21.367 25.899-32.042 40.971-16.971l80 80c9.372 9.373 9.372 24.569 0 33.941l-80 80C409.956 271.982 384 261.456 384 240v-48H24c-13.255 0-24-10.745-24-24zm488 152H128v-48c0-21.314-25.862-32.08-40.971-16.971l-80 80c-9.372 9.373-9.372 24.569 0 33.941l80 80C102.057 463.997 128 453.437 128 432v-48h360c13.255 0 24-10.745 24-24v-16c0-13.255-10.745-24-24-24z" class=""></path></svg> <?php esc_html_e( 'Transfer', 'registrations-for-the-events-calendar' ); ?></button>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div> <!-- rtec-single-event -->

</div> <!-- rtec-single-wrapper -->

<?php do_action( 'rtec_registrations_tab_after_single', $event_obj ); ?>
