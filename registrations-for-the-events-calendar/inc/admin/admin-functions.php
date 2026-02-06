<?php
/**
 * New registrations are counted and added as alerts to the menu items
 *
 * @return false|int    false if no new registrations, else the count
 * @since 1.0
 */
function rtec_get_existing_new_reg_count() {

	$existing_new_reg_data = get_transient( 'rtec_new_registrations' );

	if ( $existing_new_reg_data ) {
		$new_registrations_count = $existing_new_reg_data;
	} else {
		$db                      = new RTEC_Db_Admin();
		$new_registrations_count = $db->check_for_new();

		if ( ! $existing_new_reg_data ) {
			set_transient( 'rtec_new_registrations', $new_registrations_count, 60 * 15 );
		}
	}

	return $new_registrations_count;
}

/**
 * Creates the alert next to the menu item
 *
 * @since 1.0
 */
function rtec_registrations_bubble() {

	$new_registrations_count = rtec_get_existing_new_reg_count();

	if ( $new_registrations_count > 0 ) {
		global $menu;

		foreach ( $menu as $key => $value ) {
			if ( $menu[ $key ][2] === RTEC_TRIBE_MENU_PAGE ) {
				$menu[ $key ][0] .= ' <span class="update-plugins rtec-notice-admin-reg-count"><span>' . $new_registrations_count . '</span></span>';
				return;
			}
		}
	}
}



/**
 * Dismiss banner notice listener
 *
 * @since 2.7.7
 */
function rtec_check_notice_dismiss() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$nonce = isset( $_GET['rtec_nonce'] ) ? $_GET['rtec_nonce'] : false;
	if ( ! wp_verify_nonce( $nonce, 'rtec-dismiss' ) ) {
		return;
	}

	$dismiss_type = isset( $_GET['rtec_dismiss'] ) ? sanitize_key( $_GET['rtec_dismiss'] ) : false;

	if ( 'bfcm' === $dismiss_type ) {
		rtec_dismiss_bfcm_notice();
	}
}
add_action( 'admin_init', 'rtec_check_notice_dismiss' );

/**
 * Dismiss banner notice AJAX listener
 *
 * @since 2.7.7
 */
function rtec_dismiss_banner() {
	check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	$time = isset( $_POST['time'] ) ? sanitize_key( $_POST['time'] ) : 'always';
	rtec_dismiss_bfcm_notice( $time );

	wp_send_json_success();
}
add_action( 'wp_ajax_rtec_dismiss_banner', 'rtec_dismiss_banner' );

/**
 * Dismiss banner notice AJAX listener
 *
 * @since 2.10
 */
function rtec_dismiss_dashboard_notice() {
	check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	delete_transient( 'rtec_smtp_message' );

	wp_send_json_success();
}
add_action( 'wp_ajax_rtec_dismiss_dashboard_notice', 'rtec_dismiss_dashboard_notice' );

/**
 * Dismiss banner notice by updating user meta
 *
 * @param string $time
 * @since 2.7.7
 */
function rtec_dismiss_bfcm_notice( $time = 'always' ) {
	if ( 'always' === $time ) {
		update_user_meta( get_current_user_id(), 'rtec_dismiss_bfcm', 'always' );
	} elseif ( gmdate( 'Y', rtec_time() ) === $time ) {
		update_user_meta( get_current_user_id(), 'rtec_dismiss_bfcm', gmdate( 'Y', rtec_time() ) );
	}
}

/**
 * Whether or not the sale is active
 *
 * @return bool
 */
function rtec_is_bfcm_time_range() {
	$thanksgiving_this_year                 = rtec_get_future_date( 11, gmdate( 'Y', rtec_time() ), 4, 4, 1 );
	$one_week_before_black_friday_this_year = $thanksgiving_this_year - 7 * 24 * 60 * 60;
	$one_day_after_cyber_monday_this_year   = $thanksgiving_this_year + 5 * 24 * 60 * 60;

	return ( rtec_time() > $one_week_before_black_friday_this_year && rtec_time() < $one_day_after_cyber_monday_this_year );
}

function rtec_get_future_date( $month, $year, $week, $day, $direction ) {
	if ( $direction > 0 ) {
		$startday = 1;
	} else {
		$startday = gmdate( 't', mktime( 0, 0, 0, $month, 1, $year ) );
	}

	$start   = mktime( 0, 0, 0, $month, $startday, $year );
	$weekday = gmdate( 'N', $start );

	$offset = 0;
	if ( $direction * $day >= $direction * $weekday ) {
		$offset = -$direction * 7;
	}

	$offset += $direction * ( $week * 7 ) + ( $day - $weekday );
	return mktime( 0, 0, 0, $month, $startday + $offset, $year );
}

/**
 * Adds the meta box for the plugins individual event options
 *
 * @since 1.1
 */
function rtec_meta_boxes_init() {
	add_meta_box(
		'rtec-event-details',
		__( 'Registrations for The Events Calendar', 'registrations-for-the-events-calendar' ),
		'rtec_meta_boxes_html',
		'tribe_events',
		'normal',
		'high'
	);
}
add_action( 'admin_init', 'rtec_meta_boxes_init' );

/**
 * Generates the html for the plugin's individual event options meta boxes
 *
 * @since 1.1
 */
function rtec_meta_boxes_html() {
	global $post;

	$event_meta                    = rtec_get_event_meta( $post->ID );
	$limit_disabled_att            = '';
	$limit_disabled_class          = '';
	$users_only_disabled_att       = '';
	$users_only_disabled_class     = '';
	$max_disabled_att              = '';
	$max_disabled_class            = '';
	$deadline_disabled_att         = '';
	$deadline_disabled_class       = '';
	$deadline_other_disabled_class = '';

	$notification_recipients_for_event = get_post_meta( $event_meta['post_id'], '_RTECnotificationEmailRecipient' );

	if ( ! empty( $notification_recipients_for_event[0] ) ) {
		$notification_recipients = explode( ',', str_replace( ' ', '', $notification_recipients_for_event[0] ) );
	} else {
		$notification_recipients = array();
	}

	$notification_email = implode( ', ', $notification_recipients );
	$confirmation_from  = rtec_get_confirmation_from_address( $post->ID, true );
	$deadline_time      = isset( $event_meta['deadline_other_timestamp'] ) ? intval( $event_meta['deadline_other_timestamp'] ) : strtotime( $event_meta['start_date'] );
	if ( $deadline_time === 0 ) {
		$deadline_time = strtotime( date( 'Y/m/d' ) ) + 28800;
	}

	$open_time = isset( $event_meta['open_other_timestamp'] ) ? $event_meta['open_other_timestamp'] : time() + rtec_get_time_zone_offset();
	if ( (int) $open_time === 0 ) {
		$open_time = time() + rtec_get_time_zone_offset();
	}

	if ( $event_meta['registrations_disabled'] ) {
		$users_only_disabled_att   = ' disabled="true"';
		$users_only_disabled_class = ' rtec-fade';
		$limit_disabled_att        = ' disabled="true"';
		$limit_disabled_class      = ' rtec-fade';
		$deadline_disabled_att     = ' disabled="true"';
		$deadline_disabled_class   = ' rtec-fade';
	}

	if ( $event_meta['deadline_type'] !== 'other' ) {
		$deadline_other_disabled_class = ' rtec-fade';
	}

	if ( $event_meta['open_type'] !== 'other' ) {
		$open_other_disabled_class = ' rtec-fade';
	}

	if ( $event_meta['registrations_disabled'] || ! $event_meta['limit_registrations'] ) {
		$max_disabled_att   = ' disabled="true"';
		$max_disabled_class = ' rtec-fade';
	}

	$date_picker_format_num = rtec_tribe_get_option( 'datepickerFormat' ) ? rtec_tribe_get_option( 'datepickerFormat' ) : 0;
	$date_picker_format     = $date_picker_format_num && class_exists( 'Tribe__Date_Utils' ) ? Tribe__Date_Utils::datepicker_formats( $date_picker_format_num ) : 'Y-m-d';


	global $rtec_options;
	?>
	<?php if ( isset( $rtec_options['template_location'] ) && $rtec_options['template_location'] === 'shortcode' ) : ?>
	<div class="rtec-notice rtec-box-shadow">
		<span><?php _e( sprintf( 'Add the shortcode %s using the editor above to display a registration form on this page.', '<code>[rtec-registration-form]</code>' ), 'registrations-for-the-events-calendar' ); ?></span>
	</div>
		<?php
	else :
		$new_status = get_transient( 'rtec_new_messages' );
		if ( ! $event_meta['registrations_disabled'] && $new_status === 'yes' ) :
			?>
		<div class="rtec-notice rtec-box-shadow">
			<?php _e( 'A registration form will be automatically added to the event content.', 'registrations-for-the-events-calendar' ); ?>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<div id="eventDetails" class="inside eventForm">
		<table cellspacing="0" cellpadding="0" id="EventInfo">
			<tbody>
			<tr>
				<td colspan="2" class="tribe_sectionheader">
					<div class="tribe_sectionheader" style="">
						<h4><?php _e( 'General', 'registrations-for-the-events-calendar' ); ?></h4>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<table class="eventtable rtec-eventtable">
						<tbody>
							<tr class="rtec-hidden-option-wrap">
								<td class="tribe-table-field-label"><?php _e( 'Disable Registrations:', 'registrations-for-the-events-calendar' ); ?></td>
								<td>
									<input type="checkbox" id="rtec-disable-checkbox" name="_RTECregistrationsDisabled" 
									<?php
									if ( $event_meta['registrations_disabled'] ) {
										echo 'checked'; }
									?>
									value="1"/>
								</td>
							</tr>
							<tr class="rtec-hidden-option-wrap<?php echo $users_only_disabled_class; ?>">
								<td class="tribe-table-field-label"><?php _e( 'Logged-in Users Only:', 'registrations-for-the-events-calendar' ); ?></td>
								<td>
									<input type="checkbox" id="rtec-who-checkbox" name="_RTECwhoCanRegister" 
									<?php
									if ( 'any' !== $event_meta['who_can_register'] ) {
										echo 'checked'; }
									?>
									value="users" <?php echo $users_only_disabled_att; ?>/>
								</td>
							</tr>
							<tr class="rtec-hidden-option-wrap<?php echo $limit_disabled_class; ?>">
								<td class="tribe-table-field-label"><?php _e( 'Limit Registrations:', 'registrations-for-the-events-calendar' ); ?></td>
								<td>
									<input type="checkbox" id="rtec-limit-checkbox" class="" name="_RTEClimitRegistrations" 
									<?php
									if ( $event_meta['limit_registrations'] ) {
										echo 'checked'; }
									?>
									value="1"<?php echo $limit_disabled_att; ?>/>
								</td>
							</tr>
							<tr class="rtec-hidden-option-wrap<?php echo $max_disabled_class; ?>">
								<td class="tribe-table-field-label"><?php _e( 'Maximum Registrations:', 'registrations-for-the-events-calendar' ); ?></td>
								<td>
									<input type="text" size="3" id="rtec-max-input" name="_RTECmaxRegistrations" value="<?php echo esc_attr( $event_meta['max_registrations'] ); ?>"<?php echo $max_disabled_att; ?>/>
								</td>
							</tr>


                            <tr class="rtec-hidden-option-wrap<?php echo $deadline_disabled_class; ?>">
                                <td class="tribe-table-field-label rtec-top-label"><?php esc_html_e( 'Open Date:', 'registrations-for-the-events-calendar' ); ?>
                                    <br>
                                    <small class="rtec-light-text"><?php esc_html_e( 'When does registration open?', 'registrations-for-the-events-calendar' ); ?></small>
                                </td>
                                <td>
									<?php

									$deadline_multiplier = isset( $rtec_options['registration_deadline'] ) ? sanitize_text_field( $rtec_options['registration_deadline'] ) : 0;
									$deadline_unit       = isset( $rtec_options['registration_deadline_unit'] ) ? sanitize_text_field( $rtec_options['registration_deadline_unit'] ) : 0;

									$offset_string = '';
									if ( $deadline_multiplier > 0 ) {
										if ( $deadline_unit === '60' ) {
											$offset_string = __( 'Minutes', 'registrations-for-the-events-calendar' );
										} elseif ( $deadline_unit === '3600' ) {
											$offset_string = __( 'Hours', 'registrations-for-the-events-calendar' );
										} else {
											$offset_string = __( 'Days', 'registrations-for-the-events-calendar' );
										}
									}
									?>
                                    <div class="tribe-datetime-block">
                                        <div class="rtec-radio-event-settings-flex">
                                            <div class="rtec-sameline">
                                                <input type="radio" id="rtec-open-start-<?php echo esc_attr( $event_meta['post_id'] ); ?>" name="_RTECopenType"<?php if ( $event_meta['open_type'] === 'immediate' ) { echo ' checked'; } ?> value="immediate"<?php echo $deadline_disabled_att; ?>/>
                                                <label for="rtec-open-start-<?php echo esc_attr( $event_meta['post_id'] ); ?>"><?php esc_html_e( 'Immediately', 'registrations-for-the-events-calendar' ); ?></label>
                                            </div>
                                            <div class="rtec-sameline">
                                                <input type="radio" id="rtec-open-end-<?php echo esc_attr( $event_meta['post_id'] ); ?>" name="_RTECopenType"<?php if ( $event_meta['open_type'] === 'relative' ) { echo ' checked'; } ?> value="relative"<?php echo $deadline_disabled_att; ?>/>
                                                <label for="rtec-open-end-<?php echo esc_attr( $event_meta['post_id'] ); ?>"><?php esc_html_e( 'Relative to event start', 'registrations-for-the-events-calendar' ); ?></label>
                                            </div>
                                            <div id="rtec-open-rel-picker"<?php if ( $event_meta['open_type'] !== 'relative ' ) { echo ' style="display: none;"'; } ?>>
                                                <span><?php esc_html_e( 'Open registration', 'registrations-for-the-events-calendar' ); ?></span>
                                                <input name="_RTECopenOffset" id="rtec_registration_opens_offset" type="number" style="width: 50px;" value="<?php echo esc_attr( $event_meta['open_offset'] ); ?>">
                                                <span><?php esc_html_e( 'days before event start time', 'registrations-for-the-events-calendar' ); ?></span>
                                            </div>
                                        </div>
                                        <div class="rtec-sameline">
                                            <input type="radio" id="rtec-open-other-<?php echo esc_attr( $event_meta['post_id'] ); ?>" name="_RTECopenType"<?php if ( $event_meta['open_type'] === 'specific' ) { echo ' checked'; } ?> value="specific"<?php echo $deadline_disabled_att; ?>/>
                                            <label for="rtec-open-other-<?php echo esc_attr( $event_meta['post_id'] ); ?>"><?php esc_html_e( 'Other:', 'registrations-for-the-events-calendar' ); ?></label>
                                            <input type="text" id="rtec-date-picker-open" name="_RTECopenDate" value="<?php echo esc_attr( gmdate( $date_picker_format, $open_time ) ); ?>" data-format="<?php echo esc_attr( $date_picker_format_num ); ?>" data-rtec-open="<?php echo esc_attr( $open_time ); ?>" class="rtec-date-picker<?php echo $open_other_disabled_class; ?>" style="width: 100px;"/>
                                            <input type="hidden" name="_RTECopenDatet" value="<?php echo esc_attr( $open_time ); ?>" class="rtec-datepicker-selected"/>
                                            <input autocomplete="off" tabindex="2001" type="text" class="rtec-time-picker tribe-timepicker tribe-field-end_time ui-timepicker-input<?php echo $open_other_disabled_class; ?>" name="_RTECopenTime" id="rtec-time-picker-open" data-step="30" data-round="" value="<?php echo esc_attr( gmdate( rtec_get_time_format( true ), $open_time ) ); ?>" style="width: 80px;">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr class="rtec-hidden-option-wrap<?php echo $deadline_disabled_class; ?>">
                                <td class="tribe-table-field-label rtec-top-label"><?php esc_html_e( 'Deadline Date:', 'registrations-for-the-events-calendar' ); ?>
                                    <br>
                                    <small class="rtec-light-text"><?php esc_html_e( 'When does registration close?', 'registrations-for-the-events-calendar' ); ?></small>
                                </td>
                                <td>
									<?php

									$deadline_multiplier = isset( $rtec_options['registration_deadline'] ) ? sanitize_text_field( $rtec_options['registration_deadline'] ) : 0;
									$deadline_unit       = isset( $rtec_options['registration_deadline_unit'] ) ? sanitize_text_field( $rtec_options['registration_deadline_unit'] ) : 0;

									$offset_string = '';
									if ( $deadline_multiplier > 0 ) {
										if ( $deadline_unit === '60' ) {
											$offset_string = __( 'Minutes', 'registrations-for-the-events-calendar' );
										} elseif ( $deadline_unit === '3600' ) {
											$offset_string = __( 'Hours', 'registrations-for-the-events-calendar' );
										} else {
											$offset_string = __( 'Days', 'registrations-for-the-events-calendar' );
										}
									}
									?>
                                    <div class="tribe-datetime-block">
                                        <div class="rtec-radio-event-settings-flex">

                                            <div class="rtec-sameline">
                                                <input type="radio" id="rtec-start-<?php echo esc_attr( $event_meta['post_id'] ); ?>" name="_RTECdeadlineType"<?php if ( $event_meta['deadline_type'] === 'start' ) { echo ' checked'; } ?> value="start"<?php echo $deadline_disabled_att; ?>/>
                                                <label for="rtec-start-<?php echo esc_attr( $event_meta['post_id'] ); ?>"><?php esc_html_e( 'Start time', 'registrations-for-the-events-calendar' ); ?><?php if ( ! empty( $offset_string ) ) { echo esc_html( ' (-' . $deadline_multiplier . ' ' . $offset_string . ')' ); } ?>
                                                </label>
                                            </div>
                                            <div class="rtec-sameline">
                                                <input type="radio" id="rtec-end-<?php echo esc_attr( $event_meta['post_id'] ); ?>" name="_RTECdeadlineType"<?php if ( $event_meta['deadline_type'] === 'end' ) { echo ' checked'; } ?> value="end"<?php echo $deadline_disabled_att; ?>/>
                                                <label for="rtec-end-<?php echo esc_attr( $event_meta['post_id'] ); ?>"><?php esc_html_e( 'End time', 'registrations-for-the-events-calendar' ); ?></label>
                                            </div>
                                            <div class="rtec-sameline">
                                                <input type="radio" id="rtec-none-<?php echo esc_attr( $event_meta['post_id'] ); ?>" name="_RTECdeadlineType"<?php if ( $event_meta['deadline_type'] === 'none' ) {echo ' checked'; } ?> value="none"<?php echo $deadline_disabled_att; ?>/>
                                                <label for="rtec-none-<?php echo esc_attr( $event_meta['post_id'] ); ?>"><?php esc_html_e( 'No deadline', 'registrations-for-the-events-calendar' ); ?></label>
                                            </div>

                                        </div>
                                        <input type="radio" id="rtec-other-<?php echo esc_attr( $event_meta['post_id'] ); ?>" name="_RTECdeadlineType"<?php if ( $event_meta['deadline_type'] === 'other' ) { echo ' checked'; } ?> value="other"<?php echo $deadline_disabled_att; ?>/>

                                        <label for="rtec-other-<?php echo esc_attr( $event_meta['post_id'] ); ?>"><?php esc_html_e( 'Other:', 'registrations-for-the-events-calendar' ); ?></label>
                                        <input type="text" id="rtec-date-picker-deadline" name="_RTECdeadlineDate" value="<?php echo esc_attr( gmdate( $date_picker_format, $deadline_time ) ); ?>" data-format="<?php echo esc_attr( $date_picker_format_num ); ?>" data-rtec-deadline="<?php echo esc_attr( $deadline_time ); ?>" class="rtec-date-picker<?php echo $deadline_other_disabled_class; ?>" style="width: 100px;"/>
                                        <input type="hidden" name="_RTECdeadlineDatet" value="<?php echo $deadline_time; ?>" class="rtec-datepicker-selected"/>
                                        <input autocomplete="off" tabindex="2001" type="text" class="rtec-time-picker tribe-timepicker tribe-field-end_time ui-timepicker-input<?php echo $deadline_other_disabled_class; ?>" name="_RTECdeadlineTime" id="rtec-time-picker" data-step="30" data-round="" value="<?php echo esc_attr( gmdate( rtec_get_time_format( true ), $deadline_time ) ); ?>" style="width: 80px;">
                                    </div>
                                </td>
                            </tr>


						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="tribe_sectionheader">
					<div class="tribe_sectionheader" style="">
						<h4><?php _e( 'Email', 'registrations-for-the-events-calendar' ); ?></h4>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<table class="eventtable rtec-eventtable">
						<tbody>
						<tr class="rtec-hidden-option-wrap">
							<td class="tribe-table-field-label"><?php _e( 'Notification Email Recipients:', 'registrations-for-the-events-calendar' ); ?></td>
							<td>
								<input type="text" style="width: 100%; max-width: 400px;" id="rtec-not-email" name="_RTECnotificationEmailRecipient" value="<?php echo esc_attr( $notification_email ); ?>" placeholder="<?php _e( 'leave blank for default', 'registrations-for-the-events-calendar' ); ?>"/>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<table class="eventtable rtec-eventtable">
						<tbody>
						<tr class="rtec-hidden-option-wrap">
							<td class="tribe-table-field-label"><?php _e( 'Confirmation From Address:', 'registrations-for-the-events-calendar' ); ?></td>
							<td>
								<input type="text" size="30" id="rtec-conf-from" name="_RTECconfirmationEmailFrom" value="<?php echo esc_attr( $confirmation_from ); ?>" placeholder="<?php _e( 'leave blank for default', 'registrations-for-the-events-calendar' ); ?>"/>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="tribe_sectionheader">
					<div class="tribe_sectionheader" style="">
						<h4><?php _e( 'Shortcodes', 'registrations-for-the-events-calendar' ); ?></h4>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php _e( 'Copy and paste these shortcodes to display the registration form and the attendee list on a page outside of the single event view.', 'registrations-for-the-events-calendar' ); ?>
				</td>
			</tr>
			<tr>
				<td>
					<h4><?php _e( 'Registration Form: ', 'registrations-for-the-events-calendar' ); ?></h4><code>[rtec-registration-form event=<?php echo $event_meta['post_id']; ?>]</code><br /><small><?php _e( 'Note that the registration form appears on the single event view automatically.', 'registrations-for-the-events-calendar' ); ?></small>
					<span class="rtec-tooltip-table">
						<strong><?php _e( 'Shortcode Settings:', 'registrations-for-the-events-calendar' ); ?></strong></br>
						<span class="rtec-col-1">event="123"</span><span class="rtec-col-2"><?php _e( 'Show registration form by event ID', 'registrations-for-the-events-calendar' ); ?></span>
						<span class="rtec-col-1">hidden="true"</span><span class="rtec-col-2"><?php _e( 'Use "false" to show the form initially', 'registrations-for-the-events-calendar' ); ?></span>
						<span class="rtec-col-1">showheader="false"</span><span class="rtec-col-2"><?php _e( 'Use "true" to add the event title and start/end time information above the form', 'registrations-for-the-events-calendar' ); ?></span>
						<span class="rtec-col-1">attendeelist="false"</span><span class="rtec-col-2"><?php _e( 'Use "true" to add the attendee list above the form if enabled for this event', 'registrations-for-the-events-calendar' ); ?></span>
					</span>
				</td>
			</tr>
			<tr>
				<td>
					<h4><?php _e( 'Attendee List: ', 'registrations-for-the-events-calendar' ); ?></h4><code>[rtec-attendee-list event=<?php echo absint( $event_meta['post_id'] ); ?>]</code>
					<span class="rtec-tooltip-table">
						<strong><?php _e( 'Shortcode Settings:', 'registrations-for-the-events-calendar' ); ?></strong></br>
						<span class="rtec-col-1">event="123"</span><span class="rtec-col-2"><?php _e( 'Show attendee list by event ID', 'registrations-for-the-events-calendar' ); ?></span>
						<span class="rtec-col-1">showheader="false"</span><span class="rtec-col-2"><?php _e( 'Use "true" to add the event title and start/end time information above the list', 'registrations-for-the-events-calendar' ); ?></span>
					</span>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="tribe_sectionheader">
					<div class="tribe_sectionheader" style="">
						<h4><?php _e( 'More Single Event Options', 'registrations-for-the-events-calendar' ); ?></h4>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<p><?php _e( 'More single event options like custom confirmation email templates, multiple venues/tier registration, settings for logged-in users and others in', 'registrations-for-the-events-calendar' ); ?> <a href="https://roundupwp.com/products/registrations-for-the-events-calendar-pro/" target="_blank"><?php _e( 'Registrations for The Events Calendar', 'registrations-for-the-events-calendar' ); ?> Pro</a></p>
				</td>
			</tr>
			</tbody>
		</table>
	</div>
    <?php
	$series_id = rtec_maybe_series_id_for_event( $event_meta['post_id'] );

    if ( $series_id ) : ?>

    <div class="rtec-series-flag rtec-notice rtec-box-shadow rtec-is-warning">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
        <span><?php esc_html_e( 'Heads up! Making changes to a recurring event may cause your registrations to go missing. Export registrations before making changes.', 'registrations-for-the-events-calendar' ); ?></span>
    </div>
        <?php
    endif;
}

/**
 * This saves the meta when the event post is updated
 *
 * @since 1.1
 */
function rtec_save_meta() {
	if ( rtec_doing_duplicate() ) {
		global $post;

		rtec_duplicate_meta( absint( $_GET['post_id'] ), $post->ID );
		return;
	}
	// do not save for non tribe_events post types
	if ( ! isset( $_POST['_RTECdeadlineType'] ) && ! isset( $_POST['_RTECregistrationsDisabled'] ) ) {
		return;
	}

	global $post;

	$registrations_disabled_status = 0;
	$who_can_register              = 'any';
	$use_limit_status              = 0;
	$max_reg                       = 30;
	$registrations_deadline_type   = 'start';

	if ( isset( $_POST['_RTECregistrationsDisabled'] ) ) {
		$registrations_disabled_status = sanitize_text_field( $_POST['_RTECregistrationsDisabled'] );
	}

	if ( isset( $_POST['_RTECdeadlineType'] ) ) {
		$registrations_deadline_type = sanitize_text_field( $_POST['_RTECdeadlineType'] );

		if ( $registrations_deadline_type === 'other' ) {
			$deadline_date = isset( $_POST['_RTECdeadlineDate'] ) ? sanitize_text_field( $_POST['_RTECdeadlineDate'] ) : date( 'm/d/Y' );
			$deadline_time = isset( $_POST['_RTECdeadlineTime'] ) ? sanitize_text_field( $_POST['_RTECdeadlineTime'] ) : '8:00:00';
			$parsed_date   = date_parse( $deadline_time );

			$deadline_time_stamp = ( (int) $parsed_date['hour'] * 60 * 60 ) + ( (int) $parsed_date['minute'] * 60 ) + strtotime( $deadline_date );

			if ( isset( $post->ID ) ) {
				update_post_meta( $post->ID, '_RTECdeadlineTimeStamp', $deadline_time_stamp );
			}
		}
	}

	if ( isset( $_POST['_RTECopenType'] ) ) {
		$open_time = isset( $_POST['_RTECopenTime'] ) ? sanitize_text_field( $_POST['_RTECopenTime'] ) : '8:00:00';

		if ( ! empty( $_POST['_RTECopenDatet'] ) ) {
			$open_time_stamp = strtotime( gmdate( 'Y-m-d', (int) $_POST['_RTECopenDatet'] ) . ' ' . $open_time );
		} else {
			$open_date   = isset( $_POST['_RTECopenDate'] ) ? sanitize_text_field( $_POST['_RTECopenDate'] ) : gmdate( 'm/d/Y' );
			$parsed_date = date_parse( $open_time );

			$open_time_stamp = ( (int) $parsed_date['hour'] * 60 * 60 ) + ( (int) $parsed_date['minute'] * 60 ) + strtotime( $open_date );
		}

		$new_post_meta['_RTECopenType']      = sanitize_text_field( $_POST['_RTECopenType'] );
		$new_post_meta['_RTECopenTimeStamp'] = $open_time_stamp;
	}

	if ( isset( $_POST['_RTECopenOffset'] ) ) {
		$new_post_meta['_RTECopenOffset'] = sanitize_text_field( $_POST['_RTECopenOffset'] );
	}

	foreach ( $new_post_meta as $key => $value ) {
        update_post_meta( $post->ID, $key, $value );
	}

	if ( isset( $_POST['_RTECwhoCanRegister'] ) ) {
		$who_can_register = sanitize_text_field( $_POST['_RTECwhoCanRegister'] );
	}

	if ( isset( $_POST['_RTEClimitRegistrations'] ) ) {
		$use_limit_status = sanitize_text_field( $_POST['_RTEClimitRegistrations'] );
	}

	if ( isset( $_POST['_RTECmaxRegistrations'] ) ) {
		$max_reg = sanitize_text_field( $_POST['_RTECmaxRegistrations'] );
	}

	if ( isset( $_POST['_RTECnotificationEmailRecipient'] ) && ! empty( $_POST['_RTECnotificationEmailRecipient'] ) && isset( $post->ID ) ) {
		$not_email = sanitize_text_field( $_POST['_RTECnotificationEmailRecipient'] );
		update_post_meta( $post->ID, '_RTECnotificationEmailRecipient', $not_email );
	} elseif ( isset( $post->ID ) ) {
		delete_post_meta( $post->ID, '_RTECnotificationEmailRecipient' );
	}

	if ( isset( $_POST['_RTECconfirmationEmailFrom'] ) && ! empty( $_POST['_RTECconfirmationEmailFrom'] ) && isset( $post->ID ) ) {
		$con_email = sanitize_text_field( $_POST['_RTECconfirmationEmailFrom'] );
		update_post_meta( $post->ID, '_RTECconfirmationEmailFrom', $con_email );
	} elseif ( isset( $post->ID ) ) {
		delete_post_meta( $post->ID, '_RTECconfirmationEmailFrom' );
	}

	if ( isset( $post->ID ) ) {
		update_post_meta( $post->ID, '_RTECregistrationsDisabled', $registrations_disabled_status );
		update_post_meta( $post->ID, '_RTECdeadlineType', $registrations_deadline_type );
		update_post_meta( $post->ID, '_RTECwhoCanRegister', $who_can_register );
		update_post_meta( $post->ID, '_RTEClimitRegistrations', $use_limit_status );
		update_post_meta( $post->ID, '_RTECmaxRegistrations', $max_reg );
	}
}
add_action( 'save_post', 'rtec_save_meta' );

/**
 * Due to bug in meta_query, this function manually removes events that have no meta set
 * but events are disabled by default
 *
 * @since 1.4
 * @since 2.0 now works with added filtering
 */
function rtec_should_show( $with, $disabled_status ) {

	if ( $with === 'either' ) {
		return true;
	} else {
		return ( $disabled_status === false );
	}
}

/**
 * Used to edit, add, and delete registrations from the dashboard
 *
 * @since 2.0
 */
function rtec_records_edit() {
	check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}

	$action = sanitize_text_field( $_POST['edit_action'] );

	$event_id   = (int) $_POST['event_id'];

	if ( ! current_user_can( 'edit_post', $event_id ) ) {
		die( 'You cannot add or edit this registration' );
	}

	$entry_id   = isset( $_POST['entry_id'] ) ? (int) $_POST['entry_id'] : false;
	$event_meta = rtec_get_event_meta( $event_id );
	$venue      = isset( $_POST['venue'] ) ? (string) $_POST['venue'] : $event_meta['venue_id'];

	require_once RTEC_PLUGIN_DIR . 'inc/form/class-rtec-form.php';

	$form = new RTEC_Form();

	$form->build_form( $event_id );
	$fields_atts = $form->get_field_attributes();

	require_once RTEC_PLUGIN_DIR . 'inc/class-rtec-db.php';
	require_once RTEC_PLUGIN_DIR . 'inc/admin/class-rtec-db-admin.php';
	$db = new RTEC_Db_Admin();

	switch ( $action ) {
		case 'delete':
			$registrations_to_be_deleted = array();

			foreach ( $_POST['registrations_to_be_deleted'] as $registration ) {
				$registrations_to_be_deleted[] = absint( $registration );
			}

			$db->remove_records( $registrations_to_be_deleted );

			break;
		case 'add':
			$data = array();

			foreach ( $_POST as $key => $value ) {
				if ( $key === 'custom' ) {
					$data['custom'] = json_decode( stripslashes( $_POST['custom'] ), true );
				} elseif ( $key === 'standard' ) {
					$standard = json_decode( stripslashes( $_POST['standard'] ), true );
					foreach ( $standard as $key_2 => $value_2 ) {
						$data[ $key_2 ] = sanitize_text_field( $value_2 );
					}
				}
			}

			$data['status']   = 'c';
			$data['event_id'] = $event_id;
			$data['user_id']  = 0;

			if ( ! isset( $data['venue'] ) ) {
				$data['venue'] = $venue;
			}

			$db->insert_entry( $data, $fields_atts, false );
			break;
		case 'edit':
			$data = array();

			foreach ( $_POST as $key => $value ) {
				if ( $key === 'custom' ) {
					$data['custom'] = json_decode( stripslashes( $_POST['custom'] ), true );
				} elseif ( $key === 'standard' ) {
					$standard = json_decode( stripslashes( $_POST['standard'] ), true );
					foreach ( $standard as $key_2 => $value_2 ) {
						$data[ $key_2 ] = sanitize_text_field( $value_2 );
					}
				}
			}

			$data['event_id'] = $event_id;

			if ( ! isset( $data['venue'] ) ) {
				$data['venue'] = $venue;
			}
			$db->update_entry( $data, $entry_id, $fields_atts );

			break;
		case 'delete-all':
			$email = isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : false;

			if ( ! $email ) {
				die();
			}
			$args                        = array(
				'fields' => array( 'id' ),
				'where'  => array(
					array( 'email', $email, '=', 'string' ),
				),
			);
			$matches                     = $db->retrieve_entries( $args );
			$registrations_to_be_deleted = array();

			foreach ( $matches as $registration ) {
				$registrations_to_be_deleted[] = absint( $registration['id'] );
			}

			$db->remove_records( $registrations_to_be_deleted );

			break;
		default:
			die( 'incorrect action' );
	}

	$db->update_num_registered_meta_for_event( $event_id );

	die();
}
add_action( 'wp_ajax_rtec_records_edit', 'rtec_records_edit' );

/**
 * Export registrations for a single event
 *
 * @since 2.0
 */
function rtec_event_csv() {
	if ( isset( $_POST['rtec_my_events_csv'] ) ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'You did not do this the right way!' );
		}
		$nonce = $_POST['rtec_csv_export_nonce'];

		if ( ! wp_verify_nonce( $nonce, 'rtec_csv_export' ) ) {
			die( 'You did not do this the right way!' );
		}

		rtec_my_events_csv();
	} elseif ( isset( $_POST['rtec_event_csv'] ) ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'You did not do this the right way!' );
		}
		$nonce = $_POST['rtec_csv_export_nonce'];

		if ( ! wp_verify_nonce( $nonce, 'rtec_csv_export' ) ) {
			die( 'You did not do this the right way!' );
		}

		$rtec = RTEC();
		$form = $rtec->form->instance();

		$event_obj = new RTEC_Admin_Event();
		$form->build_form( (int) $_GET['id'] );

		$event_obj->build_admin_event( (int) $_GET['id'], 'csv', '', $form );
		$event_meta  = $event_obj->event_meta;
		$venue_title = $event_meta['venue_title'];

		$event_meta_string = array(
			array( $event_meta['title'] ),

			array( date_i18n( str_replace( ',', ' ', rtec_get_date_time_format() ), strtotime( $event_meta['start_date'] ) ) ),
			array( date_i18n( str_replace( ',', ' ', rtec_get_date_time_format() ), strtotime( $event_meta['end_date'] ) ) ),
			array( $venue_title ),
			array_map( 'stripslashes', $event_obj->labels ),
		);

		$file_name = str_replace( ' ', '-', substr( $event_meta['title'], 0, 10 ) ) . '_' . str_replace( ' ', '-', substr( $event_meta['venue_title'], 0, 10 ) ) . '_' . date_i18n( 'm.d', strtotime( $event_meta['start_date'] ) );

		// output headers so that the file is downloaded rather than displayed
		header( 'Content-Encoding: UTF-8' );
		header( 'Content-type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . str_replace( ',', '', $file_name ) . '.csv"' );
		echo "\xEF\xBB\xBF"; // UTF-8 BOM

		// create a file pointer connected to the output stream
		$output = fopen( 'php://output', 'w' );
		foreach ( $event_meta_string as $meta ) {
			fputcsv( $output, $meta );
		}
		foreach ( $event_obj->registrants_data as $registration ) {

			$time_format            = rtec_get_time_format();
			$formatted_registration = array( 'registration_date' => date_i18n( 'F jS ' . $time_format, strtotime( $registration['registration_date'] ) + rtec_get_time_zone_offset() ) );

			foreach ( $event_obj->column_label as $column => $label ) {

				if ( isset( $registration[ $column ] ) ) {
					$value = stripslashes( $registration[ $column ] );
				} elseif ( isset( $registration[ $column . '_name' ] ) ) {
					$value = stripslashes( $registration[ $column . '_name' ] );
				} elseif ( isset( $registration['custom'][ $column ] ) ) {
					$value = stripslashes( $registration['custom'][ $column ]['value'] );
				} elseif ( isset( $registration['custom'][ $label ] ) ) {
					$value = stripslashes( $registration['custom'][ $label ] );
				}

				$formatted_registration[ $column ] = $value;

			}

			fputcsv( $output, $formatted_registration );

		}

		fclose( $output );

		die();
	}
}
add_action( 'admin_init', 'rtec_event_csv' );

/**
 * Export registrations for a single event
 *
 * @since 2.2
 */
function rtec_my_events_csv() {
	$rtec = RTEC();
	$db   = $rtec->db_frontend->instance();

	$admin_registrations = new RTEC_Admin_Registrations();
	$settings            = array(
		'v'     => '',
		'qtype' => 'all',
		'with'  => 'with',
		'off'   => 0,
		'start' => 0,
		'id'    => 0,
		'mvt'   => '',
		'rpag'  => 0,
	);
	$admin_registrations->build_admin_registrations( 'my-registrations', $settings );
	$events = $admin_registrations->get_events( true );

	$event_meta_string = array(
		array( __( 'My Events', 'registrations-for-the-events-calendar' ) ),
	);

	$file_name = str_replace( ' ', '_', __( 'My Events', 'registrations-for-the-events-calendar' ) );

	if ( isset( $_POST['rtec_email'] ) ) {
		$file_name .= '_' . sanitize_text_field( $_POST['rtec_email'] );
	}

	// output headers so that the file is downloaded rather than displayed
	header( 'Content-Encoding: UTF-8' );
	header( 'Content-type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . str_replace( ',', '', $file_name ) . '.csv"' );
	echo "\xEF\xBB\xBF"; // UTF-8 BOM

	// create a file pointer connected to the output stream
	$output = fopen( 'php://output', 'w' );
	foreach ( $event_meta_string as $meta ) {
		fputcsv( $output, $meta );
	}

	fputcsv( $output, array( '' ) );

	foreach ( $events as $event ) {
		$event_meta = rtec_get_event_meta( $event->ID );
		fputcsv( $output, array( get_the_title( $event->ID ) ) );
		// Translators: %1$s is the start date, %2$s is the end date, %3$s is the venue title
		$event_string = sprintf( __( '%1$s to %2$s at %3$s', 'registrations-for-the-events-calendar' ), date_i18n( 'F jS, ' . rtec_get_time_format(), strtotime( $event_meta['start_date'] ) ), date_i18n( 'F jS, ' . rtec_get_time_format(), strtotime( $event_meta['end_date'] ) ), $event_meta['venue_title'] );
		fputcsv( $output, array( $event_string ) );

		$args          = array(
			'fields'   => array( 'registration_date', 'id', 'status', 'user_id', 'first', 'last', 'email', 'other', 'phone', 'custom' ),
			'where'    => array(
				array( 'event_id', $event_meta['post_id'], '=', 'int' ),
				array( 'email', sanitize_text_field( $_POST['rtec_email'] ), '=', 'string' ),
			),
			'order_by' => 'registration_date',
		);
		$registrations = $rtec->db_frontend->retrieve_entries( $args, true );

		foreach ( $registrations as $registration ) {

			$custom_data = isset( $registration['custom'] ) ? maybe_unserialize( $registration['custom'] ) : array();

			$status = $registration['status'];

			$first_name = empty( $first_name ) && isset( $registration['first_name'] ) ? $registration['first_name'] : $first_name;
			$last_name  = empty( $last_name ) && isset( $registration['last_name'] ) ? $registration['last_name'] : $last_name;

			if ( isset( $registration['registration_date'] ) ) {
				$registration_date      = date_i18n( str_replace( ',', ' ', 'm/d/Y ' . rtec_get_time_format() ), strtotime( $registration['registration_date'] ) + rtec_get_utc_offset() );
				$formatted_registration = array( __( 'Registration Date', 'registrations-for-the-events-calendar' ), $registration_date );
				fputcsv( $output, $formatted_registration );
			}

			$formatted_registration = array( __( 'First', 'registrations-for-the-events-calendar' ), $first_name );
			fputcsv( $output, $formatted_registration );
			$formatted_registration = array( __( 'Last', 'registrations-for-the-events-calendar' ), $last_name );
			fputcsv( $output, $formatted_registration );
			$formatted_registration = array( __( 'Status', 'registrations-for-the-events-calendar' ), $status );
			fputcsv( $output, $formatted_registration );

			if ( ! empty( $registration['email'] ) ) {
				$formatted_registration = array( __( 'Email', 'registrations-for-the-events-calendar' ), $registration['email'] );
				fputcsv( $output, $formatted_registration );
			}

			if ( ! empty( $registration['phone'] ) ) {
				$formatted_registration = array( __( 'Phone', 'registrations-for-the-events-calendar' ), $registration['phone'] );
				fputcsv( $output, $formatted_registration );
			}

			if ( ! empty( $registration['other'] ) ) {
				$formatted_registration = array( __( 'Other', 'registrations-for-the-events-calendar' ), $registration['other'] );
				fputcsv( $output, $formatted_registration );
			}

			if ( ! empty( $custom_data ) ) {
				foreach ( $custom_data as $entry_data_key ) {
					if ( isset( $entry_data_key['label'] ) ) {
						$formatted_registration = array( str_replace( '&#42;', '', $entry_data_key['label'] ), $entry_data_key['value'] );
						fputcsv( $output, $formatted_registration );
					} else {
						$formatted_registration = $entry_data_key;
						fputcsv( $output, array( $formatted_registration ) );
					}
				}
			}

			fputcsv( $output, array( '' ) );
		}
	}

	fclose( $output );

	die();
}

/**
 * Accessed with AJAX from admin pages to show search results for matching
 * first, last, email, and phone fields
 *
 * @since 2.0
 */
function rtec_get_search_results() {
	check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}
	global $rtec_options;

	$db   = new RTEC_Db_Admin();
	$term = sanitize_text_field( $_POST['term'] );

	$search_array = array( 'last_name', 'first_name' );
	if ( preg_match( '/\.|@/', $term ) === 1 ) {
		$search_array = array( 'email' );
	}
	if ( preg_match( '/\d/', $term ) === 1 ) {
		$search_array[] = 'phone';
	}

	$matches = $db->get_matches( $term, $search_array );

	$table_columns = array( 'first_name', 'last_name', 'email', 'phone' ); // , 'event_id', 'venue'
	$labels        = array();
	foreach ( $table_columns as $table_column ) {
		$the_label = isset( $rtec_options[ str_replace( '_name', '', $table_column ) . '_label' ] ) ? $rtec_options[ str_replace( '_name', '', $table_column ) . '_label' ] : $table_column;
		if ( $table_column === 'email' ) {
			$the_label .= ' (' . __( 'click to manage', 'registrations-for-the-events-calendar' ) . ')';
		}
		$labels[] = $the_label;
	}

	$WP_offset = get_option( 'gmt_offset' );

	if ( ! empty( $WP_offset ) ) {
		$tz_offset = $WP_offset * HOUR_IN_SECONDS;
	} else {
		$timezone = isset( $options['timezone'] ) ? $rtec_options['timezone'] : 'America/New_York';
		// use php DateTimeZone class to handle the date formatting and offsets
		$date_obj = new DateTime( date( 'm/d g:i a' ), new DateTimeZone( 'UTC' ) );
		$date_obj->setTimeZone( new DateTimeZone( $timezone ) );
		$utc_offset = $date_obj->getOffset();
		$tz_offset  = $utc_offset;
	}

	?>
	<table class="widefat rtec-registrations-data">
		<thead>
		<tr>
			<th><?php _e( 'Registration Date', 'registrations-for-the-events-calendar' ); ?></th>
			<?php foreach ( $labels as $label ) : ?>
				<th><?php echo esc_html( $label ); ?></th>
			<?php endforeach; ?>
			<th><?php _e( 'Event', 'registrations-for-the-events-calendar' ); ?></th>
			<th><?php _e( 'Start Date', 'registrations-for-the-events-calendar' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php

		if ( ! empty( $matches ) ) :
			foreach ( $matches as $registration ) :
				$event_meta = rtec_get_event_meta( $registration['event_id'] );
				?>

			<tr data-email="
				<?php
				if ( isset( $registration['email'] ) ) {
					esc_attr_e( stripslashes( $registration['email'] ) );}
				?>
			">
				<td class="rtec-first-data">
					<?php
					if ( $registration['status'] == 'n' ) {
						echo '<span class="rtec-notice-new">' . _( 'new' ) . '</span>';
					}
					echo esc_html( date_i18n( 'm/d ' . rtec_get_time_format(), strtotime( $registration['registration_date'] ) + $tz_offset ) );
					?>
				</td>
							<?php foreach ( $table_columns as $column ) : ?>
					<td>
								<?php
								if ( isset( $registration[ $column ] ) ) {

									if ( $column === 'phone' ) {
										echo esc_html( rtec_format_phone_number( stripslashes( $registration[ $column ] ) ) );
									} elseif ( $column === 'email' ) {
										echo '<a class="rtec-manage-match" href="javascript:void(0);">' . esc_html( stripslashes( $registration[ $column ] ) ) . '</a>';
										?>
								<div class="rtec-manage-match-actions" data-entry-id="<?php esc_attr_e( $registration['id'] ); ?>" data-email="<?php esc_attr_e( stripslashes( $registration[ $column ] ) ); ?>">
									<button class="button action rtec-match-action" data-rtec-action="delete-single"><i class="fa fa-minus" aria-hidden="true"></i> <?php _e( 'Delete Single', 'registrations-for-the-events-calendar' ); ?></button>
									<button class="button action rtec-match-action" data-rtec-action="delete-all"><i class="fa fa-minus" aria-hidden="true"></i> <?php _e( 'Delete All', 'registrations-for-the-events-calendar' ); ?></button>
									<form method="post" id="rtec_csv_export_form" action="">
										<?php wp_nonce_field( 'rtec_csv_export', 'rtec_csv_export_nonce' ); ?>
										<input type="hidden" name="rtec_email" value="<?php esc_attr_e( stripslashes( $registration[ $column ] ) ); ?>" />
										<button type="submit" name="rtec_my_events_csv" class="button action rtec-match-action"><i class="fa fa-download" aria-hidden="true"></i> <?php _e( 'Export (.csv)', 'registrations-for-the-events-calendar' ); ?></button>
									</form>
								</div>
										<?php
									} else {
										echo esc_html( stripslashes( $registration[ $column ] ) );
									}
								}
								?>
						</td>
				<?php endforeach; ?>
				<td><a href="<?php echo RTEC_ADMIN_URL; ?>&tab=single&id=<?php echo esc_attr( $event_meta['post_id'] ); ?>&record=<?php echo esc_attr( $registration['id'] ); ?>mvt=<?php echo esc_attr( '' ); ?>"><?php echo esc_html( $event_meta['title'] ); ?></a></td>
				<td><?php echo date_i18n( rtec_get_date_time_format(), strtotime( $event_meta['start_date'] ) ); ?></td>
			</tr>
					<?php endforeach; // registration ?>

		<?php else : ?>

			<tr>
				<td colspan="4"><?php _e( 'No Registrations Found', 'registrations-for-the-events-calendar' ); ?></td>
			</tr>

		<?php endif; // registrations not empty ?>
		</tbody>
	</table>
	<?php
	die();
}
add_action( 'wp_ajax_rtec_get_search_results', 'rtec_get_search_results' );

function rtec_dismiss_new() {
	check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}

	$rtec = RTEC();
	$rtec->db_frontend->dismiss_new();

	die();
}
add_action( 'wp_ajax_rtec_dismiss_new', 'rtec_dismiss_new' );

function rtec_doing_duplicate() {
	if ( ! rtec_tec_pro_is_active() ) {
		return false;
	}
	if ( empty( $_GET['action'] ) || $_GET['action'] !== 'tec_events_pro_duplicate_event' ) {
		return false;
	}
	if ( empty( $_GET['post_id'] ) ) {
		return false;
	}

	return tribe_is_event( $_GET['post_id'] );
}

function rtec_duplicate_meta( $original, $new ) {
	$original_meta = get_post_meta( $original );

	foreach ( $original_meta as $key => $value ) {
		if ( strpos( $key, '_RTEC' ) === 0 ) {
			add_post_meta( $new, $key, $value[0] );
		}
	}
}

/**
 * Some CSS and JS needed in the admin area as well
 *
 * @since 1.0
 */
function rtec_admin_scripts_and_styles() {
	wp_enqueue_style( 'rtec_admin_styles', trailingslashit( RTEC_PLUGIN_URL ) . 'css/rtec-admin-styles.css', array(), RTEC_VERSION );

	if ( ! class_exists( 'Tribe__Main' ) ) {
		wp_enqueue_script( 'rtec_installer_script', trailingslashit( RTEC_PLUGIN_URL ) . 'js/rtec-installer.js', array( 'jquery' ), RTEC_VERSION, true );
		$data    = array(
			'addon_activate'                  => esc_html__( 'Activate', 'registrations-for-the-events-calendar' ),
			'addon_activated'                 => esc_html__( 'Activated', 'registrations-for-the-events-calendar' ),
			'addon_active'                    => esc_html__( 'Active', 'registrations-for-the-events-calendar' ),
			'addon_deactivate'                => esc_html__( 'Deactivate', 'registrations-for-the-events-calendar' ),
			'addon_inactive'                  => esc_html__( 'Inactive', 'registrations-for-the-events-calendar' ),
			'addon_install'                   => esc_html__( 'Install Addon', 'registrations-for-the-events-calendar' ),
			'addon_error'                     => esc_html__( 'Could not install addon. Please download from roundupwp.com and install manually.', 'registrations-for-the-events-calendar' ),
			'plugin_error'                    => esc_html__( 'Could not install a plugin. Please visit the Plugins page and search for The Events Calendar to install.', 'registrations-for-the-events-calendar' ),
			'addon_search'                    => esc_html__( 'Searching Addons', 'registrations-for-the-events-calendar' ),
			'ajax_url'                        => admin_url( 'admin-ajax.php' ),
			'cancel'                          => esc_html__( 'Cancel', 'registrations-for-the-events-calendar' ),
			'close'                           => esc_html__( 'Close', 'registrations-for-the-events-calendar' ),
			'rtec_nonce'                      => wp_create_nonce( 'rtec_nonce' ),
			'almost_done'                     => esc_html__( 'Almost Done', 'registrations-for-the-events-calendar' ),
			'oops'                            => esc_html__( 'Oops!', 'registrations-for-the-events-calendar' ),
			'ok'                              => esc_html__( 'OK', 'registrations-for-the-events-calendar' ),
			'plugin_install_activate_btn'     => esc_html__( 'Install and Activate', 'registrations-for-the-events-calendar' ),
			'plugin_install_activate_confirm' => esc_html__( 'needs to be installed and activated to import its forms. Would you like us to install and activate it for you?', 'registrations-for-the-events-calendar' ),
			'plugin_activate_btn'             => esc_html__( 'Activate', 'registrations-for-the-events-calendar' ),
			'success_reloading'               => esc_html__( 'Success! Reloading page.', 'registrations-for-the-events-calendar' ),
			'thanks_patience'                 => esc_html__( 'This may take a minute or two. Thanks for your patience.', 'registrations-for-the-events-calendar' ),
		);
		$strings = apply_filters( 'rtec_admin_strings', $data );

		wp_localize_script(
			'rtec_installer_script',
			'rtecAdminAddOns',
			$strings
		);
	} elseif ( isset( $_GET['page'] )
			&& ( strpos( $_GET['page'], RTEC_MENU_SLUG ) !== false || strpos( $_GET['page'], 'rtec' ) !== false ) ) {

			wp_enqueue_script( 'rtec_admin_scripts', trailingslashit( RTEC_PLUGIN_URL ) . 'js/rtec-admin-scripts.js', array( 'jquery', 'jquery-ui-datepicker', 'tribe-jquery-timepicker' ), RTEC_VERSION, false );
			wp_localize_script(
				'rtec_admin_scripts',
				'rtecAdminScript',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'rtec_nonce' => wp_create_nonce( 'rtec_nonce' ),
				)
			);
			wp_enqueue_style( 'rtec_font_awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', array(), '4.6.3' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'jquery-ui-core ' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-smoothness', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css', array(), '1.12.1' );
			wp_enqueue_script( 'tribe-jquery-timepicker' );
			wp_enqueue_style( 'tribe-jquery-timepicker-css' );

	} elseif ( ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'tribe_events' ) || 
	           ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) === 'tribe_events' ) ) {
		wp_enqueue_script( 'rtec_admin_edit_event_scripts', trailingslashit( RTEC_PLUGIN_URL ) . 'js/rtec-admin-edit-event-scripts.js', array( 'jquery' ), RTEC_VERSION, false );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-smoothness', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css', array(), '1.12.1' );
		wp_enqueue_script( 'tribe-jquery-timepicker' );
		wp_enqueue_style( 'tribe-jquery-timepicker-css' );
	}
}
add_action( 'admin_enqueue_scripts', 'rtec_admin_scripts_and_styles' );

/**
 * Add links to the plugin action links
 *
 * @since 1.0
 */
function rtec_plugin_action_links( $links ) {
	$pro_link      = '<a href="https://roundupwp.com/products/registrations-for-the-events-calendar-pro/?utm_campaign=rtec-free&utm_source=plugins-page&utm_medium=plugin-action-links&utm_content=buy-pro" target="_blank" style="font-weight: bold; color: #269447;">' . __( 'Buy Pro', 'registrations-for-the-events-calendar' ) . '</a>';
	$settings_link = '<a href="' . esc_url( admin_url( RTEC_ADMIN_URL . '&tab=form' ) ) . '">' . __( 'Settings', 'registrations-for-the-events-calendar' ) . '</a>';
	array_unshift( $links, $pro_link, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . RTEC_PLUGIN_BASENAME, 'rtec_plugin_action_links' );

/**
 * Add links to setup and pro versions
 *
 * @since 1.0
 */
function rtec_plugin_meta_links( $links, $file ) {
	$plugin = RTEC_PLUGIN_BASENAME;
	// create link
	if ( $file == $plugin ) {
		return array_merge(
			$links,
			array( '<a href="https://www.roundupwp.com/products/registrations-for-the-events-calendar/setup/?utm_campaign=rtec-free&utm_source=plugins-page&utm_medium=plugin-meta-links&utm_content=setup-instructions" target="_blank">' . __( 'Setup Instructions', 'registrations-for-the-events-calendar' ) . '</a>' )
		);
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'rtec_plugin_meta_links', 10, 2 );

/**
 * Returns the columns for the particular event
 *
 * @param bool $full    type of set to return
 *
 * @since 1.3
 * @return array    columns for registrations view
 */
function rtec_get_event_columns( $full = false ) {
	global $rtec_options;

	$first_label = isset( $rtec_options['first_label'] ) && ! empty( $rtec_options['first_label'] ) ? esc_html( $rtec_options['first_label'] ) : __( 'First', 'registrations-for-the-events-calendar' );
	$last_label  = isset( $rtec_options['last_label'] ) && ! empty( $rtec_options['last_label'] ) ? esc_html( $rtec_options['last_label'] ) : __( 'Last', 'registrations-for-the-events-calendar' );
	$email_label = isset( $rtec_options['email_label'] ) && ! empty( $rtec_options['email_label'] ) ? esc_html( $rtec_options['email_label'] ) : __( 'Email', 'registrations-for-the-events-calendar' );
	$phone_label = isset( $rtec_options['phone_label'] ) && ! empty( $rtec_options['phone_label'] ) ? esc_html( $rtec_options['phone_label'] ) : __( 'Phone', 'registrations-for-the-events-calendar' );
	$other_label = isset( $rtec_options['other_label'] ) && ! empty( $rtec_options['other_label'] ) ? esc_html( $rtec_options['other_label'] ) : __( 'Other', 'registrations-for-the-events-calendar' );

	$labels = array( $last_label, $first_label, $email_label, $phone_label, $other_label );

	if ( ! $full ) {
		// add custom labels
		if ( isset( $rtec_options['custom_field_names'] ) ) {
			$custom_field_names = explode( ',', $rtec_options['custom_field_names'] );
		} else {
			$custom_field_names = array();
		}

		foreach ( $custom_field_names as $field ) {
			if ( isset( $rtec_options[ $field . '_label' ] ) ) {
				$labels[] = $rtec_options[ $field . '_label' ];
			}
		}
	} else {
		$labels[] = 'custom';
	}

	return $labels;
}

/**
 * Check db version and update if necessary
 *
 * @since 1.1   added check and add for "phone" column
 * @since 1.3   added check and add for index on event_id and add "custom" column,
 *              raise character limit for "other" column
 * @since 1.3.2 raise character limit for most fields to match "post" table
 * @since 1.4   added check and add for indices
 */
function rtec_db_update_check() {
	$db_ver = get_option( 'rtec_db_version', 0 );

	// adds "phone" column to database
	if ( $db_ver < 1.1 ) {
		update_option( 'rtec_db_version', RTEC_DBVERSION );

		$db = new RTEC_Db_Admin();
		$db->maybe_add_column_to_table( 'phone', 'VARCHAR(40)' );
	}

	// adds "custom" column
	if ( $db_ver < 1.2 ) {
		update_option( 'rtec_db_version', RTEC_DBVERSION );

		$db = new RTEC_Db_Admin();
		$db->maybe_add_index( 'event_id', 'event_id' );
		$db->maybe_add_column_to_table( 'custom', 'longtext' );
		$db->maybe_update_column( 'VARCHAR(1000) NOT NULL', 'other' );
	}

	if ( $db_ver < 1.3 ) {
		update_option( 'rtec_db_version', RTEC_DBVERSION );

		$db = new RTEC_Db_Admin();
		$db->maybe_update_column( 'BIGINT(20) UNSIGNED NOT NULL', 'event_id' );
		$db->maybe_update_column( 'VARCHAR(1000) NOT NULL', 'first_name' );
		$db->maybe_update_column( 'VARCHAR(1000) NOT NULL', 'last_name' );
		$db->maybe_update_column( 'VARCHAR(1000) NOT NULL', 'email' );
		$db->maybe_update_column( 'VARCHAR(1000) NOT NULL', 'venue' );
	}

	if ( $db_ver < 1.4 ) {
		update_option( 'rtec_db_version', RTEC_DBVERSION );

		$db = new RTEC_Db_Admin();
		$db->maybe_add_index( 'event_id', 'event_id' );
		$db->maybe_add_index( 'status', 'status' );
	}

	if ( $db_ver < 1.5 ) {
		update_option( 'rtec_db_version', RTEC_DBVERSION );

		$db = new RTEC_Db_Admin();
		$db->maybe_add_column_to_table_no_string( 'guests', 'INT(11) UNSIGNED' );
		$db->maybe_add_column_to_table( 'reminder', 'VARCHAR(40)', 'pending', true );
		$db->maybe_add_index( 'reminder', 'reminder' );
		$db->maybe_add_column_to_table( 'action_key', 'VARCHAR(40)', '', true );
		$db->maybe_add_column_to_table_no_string( 'user_id', 'BIGINT(20) UNSIGNED' );
	}
}
add_action( 'plugins_loaded', 'rtec_db_update_check' );

function rtec_lite_dismiss() {
	check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}

	set_transient( 'registrations_tec_dismiss_lite', 'dismiss', 2 * WEEK_IN_SECONDS );

	die();
}
add_action( 'wp_ajax_rtec_lite_dismiss', 'rtec_lite_dismiss' );

function rtec_help_notice_dismiss() {
	check_ajax_referer( 'rtec_nonce', 'rtec_nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}

	set_transient( 'registrations_help_notice_dismiss', 'dismiss', 4 * WEEK_IN_SECONDS );

	die();
}
add_action( 'wp_ajax_rtec_help_notice_dismiss', 'rtec_help_notice_dismiss' );

function rtec_is_admin_page() {
	if ( ! isset( $_GET['page'] ) ) {
		return false;
	} elseif ( $_GET['page'] === 'registrations-for-the-events-calendar' ) {
		return true;
	} elseif ( strpos( $_GET['page'], 'rtec-' ) === 0 ) {
		return true;
	}
	return false;
}

function rtec_admin_hide_unrelated_notices() {

	// Bail if we're not on an RTEC screen or page.
	if ( ! rtec_is_admin_page() ) {
		return;
	}

	// Extra banned classes and callbacks from third-party plugins.
	$blacklist = array(
		'classes'   => array(),
		'callbacks' => array(),
	);

	global $wp_filter;
	foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $notices_type ) {
		if ( empty( $wp_filter[ $notices_type ]->callbacks ) || ! is_array( $wp_filter[ $notices_type ]->callbacks ) ) {
			continue;
		}
		foreach ( $wp_filter[ $notices_type ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {
				if ( is_object( $arr['function'] ) && $arr['function'] instanceof Closure ) {
					unset( $wp_filter[ $notices_type ]->callbacks[ $priority ][ $name ] );
					continue;
				}
				$class = ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) ? strtolower( get_class( $arr['function'][0] ) ) : '';
				if (
					! empty( $class ) &&
					strpos( $class, 'rtec' ) !== false &&
					! in_array( $class, $blacklist['classes'], true )
				) {
					continue;
				}
				if (
					! empty( $name ) && (
						strpos( $name, 'rtec' ) === false ||
						in_array( $class, $blacklist['classes'], true ) ||
						in_array( $name, $blacklist['callbacks'], true )
					)
				) {
					unset( $wp_filter[ $notices_type ]->callbacks[ $priority ][ $name ] );
				}
			}
		}
	}
}
add_action( 'admin_print_scripts', 'rtec_admin_hide_unrelated_notices' );
