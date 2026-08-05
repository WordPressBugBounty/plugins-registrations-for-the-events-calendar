<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>
<h2 class="rtec-wizard-step-title"><?php esc_html_e( 'Step 1 of 4: Compatibility Check', 'registrations-for-the-events-calendar' ); ?></h2>
<p class="rtec-wizard-step-body"><?php esc_html_e( 'This event calendar is eligible to move to Event Genius.', 'registrations-for-the-events-calendar' ); ?></p>

<?php if ( RTEC_Migration_Eligibility::is_event_tickets_plus_active() ) : ?>
	<div class="notice notice-warning inline rtec-migration-wizard-advisory">
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: support URL */
					__( 'Event Tickets Plus is active. Event Tickets Plus data is not imported automatically. Contact support for migration help: %s', 'registrations-for-the-events-calendar' ),
					'<a href="' . esc_url( RTEC_Migration_Eligibility::SUPPORT_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( RTEC_Migration_Eligibility::SUPPORT_URL ) . '</a>'
				),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				)
			);
			?>
		</p>
	</div>
<?php endif; ?>

<?php require rtec_plugin_path( 'admin-templates/migration-wizard/partials/scan-summary.php' ); ?>
<?php require rtec_plugin_path( 'admin-templates/migration-wizard/partials/reassurance.php' ); ?>

<div class="rtec-wizard-cta-footer rtec-wizard-cta-footer--left">
	<a href="<?php echo esc_url( add_query_arg( array( 'page' => RTEC_Migration_Wizard::PAGE_SLUG, 'step' => 2 ), admin_url( 'admin.php' ) ) ); ?>" class="button button-primary rtec-wizard-cta-with-chevron">
		<span class="rtec-button-text"><?php esc_html_e( 'Continue', 'registrations-for-the-events-calendar' ); ?></span>
		<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
	</a>
</div>
