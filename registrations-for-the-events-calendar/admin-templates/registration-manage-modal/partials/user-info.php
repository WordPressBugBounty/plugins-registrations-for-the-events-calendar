<?php
/**
 * User Information block for Manage Registration modal (Submissions tab, free).
 * Requires: $entry_data (with user_id and/or email), $event_id.
 *
 * @package Registrations_For_The_Events_Calendar
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$user_id = isset( $entry_data['user_id'] ) ? (int) $entry_data['user_id'] : 0;
$user    = $user_id > 0 ? get_userdata( $user_id ) : false;
?>
<div class="rtec-dashboard-item">
	<div class="rtec-dashboard-item-header">
		<h3><?php esc_html_e( 'User Information', 'registrations-for-the-events-calendar' ); ?></h3>
	</div>
	<div class="rtec-dashboard-item-content rtec-user-info">
		<?php if ( $user instanceof WP_User ) : ?>
			<div class="rtec-user-header">
				<div class="rtec-user-avatar"><?php echo get_avatar( $user->ID, 96 ); ?></div>
				<div class="rtec-user-meta">
					<h4 class="rtec-user-display-name"><?php echo esc_html( $user->display_name ); ?></h4>
					<p class="rtec-username">@<?php echo esc_html( $user->user_login ); ?></p>
				</div>
			</div>
			<div class="rtec-user-details">
				<p><strong><?php esc_html_e( 'Email', 'registrations-for-the-events-calendar' ); ?>:</strong> <?php echo esc_html( $user->user_email ); ?></p>
				<p><strong><?php esc_html_e( 'Member since', 'registrations-for-the-events-calendar' ); ?>:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?></p>
				<?php
				$total_registrations = RTEC_Manage_Modal_Context::get_registration_count_for_user( $user->ID );
				?>
				<p><strong><?php esc_html_e( 'Total registrations', 'registrations-for-the-events-calendar' ); ?>:</strong> <?php echo (int) $total_registrations; ?></p>
			</div>
			<div class="rtec-user-actions">
				<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Edit User Profile', 'registrations-for-the-events-calendar' ); ?></a>
			</div>
		<?php else : ?>
			<p class="rtec-no-user"><?php esc_html_e( 'No WordPress user account found.', 'registrations-for-the-events-calendar' ); ?></p>
			<?php
			$email = isset( $entry_data['email'] ) ? $entry_data['email'] : '';
			if ( $email !== '' ) :
				?>
				<p><?php esc_html_e( 'Registration email:', 'registrations-for-the-events-calendar' ); ?> <?php echo esc_html( $email ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
