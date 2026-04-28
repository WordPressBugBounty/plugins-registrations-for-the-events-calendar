<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
// Expects: $registration, $admin_registrations, $event_id (and optionally $event_meta)
$identifier = trim( ( isset( $registration['first_name'] ) ? $registration['first_name'] : '' ) . ' ' . ( isset( $registration['last_name'] ) ? $registration['last_name'] : '' ) );
if ( $identifier === '' && ! empty( $registration['email'] ) ) {
	$identifier = $registration['email'];
}
if ( $identifier === '' ) {
	$identifier = '—';
}
$is_user   = isset( $registration['user_id'] ) && (int) $registration['user_id'] > 0;
$reg_id    = isset( $registration['id'] ) ? (int) $registration['id'] : 0;
$parent_id = isset( $registration['parent'] ) ? (int) $registration['parent'] : 0;
$user_class = $is_user ? ' rtec-is-user' : '';

// "New" = registration created after current user last viewed Registrations area (All Registrations or single event tab).
$is_new = class_exists( 'RTEC_New_Registration_Alerts_Service' ) && RTEC_New_Registration_Alerts_Service::instance()->is_registration_new( $registration );

// Pro-style icon row: new (tag), connected (link), user (user)
$connected_icon = '';
if ( $parent_id !== 0 ) {
	$connected_icon = '<span class="rtec-tooltip-wrap"><span class="rtec-is-connected rtec-icon">' . RTEC_Icon::get( 'link' ) . '</span><span class="rtec-status-tooltip rtec-tooltip-shadow"><span class="rtec-status-tooltip-text">' . esc_html__( 'Connected guest (part of a group registration)', 'registrations-for-the-events-calendar' ) . '</span></span></span>';
}
$user_icon = '';
if ( $is_user ) {
	$user_icon_inner = '<span class="rtec-icon-user rtec-icon">' . RTEC_Icon::get( 'user' ) . '</span>';
	$user_icon = '<span class="rtec-tooltip-wrap">' . $user_icon_inner . '<span class="rtec-status-tooltip rtec-tooltip-shadow"><span class="rtec-status-tooltip-text">' . esc_html__( 'Registered user', 'registrations-for-the-events-calendar' ) . '</span></span></span>';
}
$new_icon = '';
if ( $is_new ) {
	$new_icon = '<span class="rtec-new-registration-tag rtec-tooltip-wrap"><span class="rtec-icon-tag rtec-icon">' . RTEC_Icon::get( 'tag' ) . '</span><span class="rtec-status-tooltip rtec-tooltip-shadow"><span class="rtec-status-tooltip-text">' . esc_html__( 'New registration', 'registrations-for-the-events-calendar' ) . '</span></span></span>';
}
?>
<td class="rtec-identifier-column column-primary" data-colname="<?php esc_attr_e( 'Name', 'registrations-for-the-events-calendar' ); ?>" data-rtec-value="<?php echo esc_attr( $identifier ); ?>">
	<div class="rtec-identifier-wrap">
		<div class="rtec-identifier-top">
			<div class="rtec-identifier<?php echo esc_attr( $user_class ); ?>">
				<?php echo rtec_admin_truncate_with_tooltip( wp_unslash( $identifier ) ); ?>
				<div class="rtec-icon-row"><?php echo $new_icon . $connected_icon . $user_icon; ?></div>
			</div>
			<?php if ( ( ! isset( $rtec_show_manage_link ) || $rtec_show_manage_link ) && isset( $event_id ) && (int) $event_id > 0 ) : ?>
				<div class="row-actions">
					<a href="#" class="rtec-manage-link" data-rtec-modal-content="manage-registration" data-rtec-registration-id="<?php echo esc_attr( $reg_id ); ?>" data-rtec-event-id="<?php echo esc_attr( (int) $event_id ); ?>"><?php esc_html_e( 'Manage', 'registrations-for-the-events-calendar' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</td>
