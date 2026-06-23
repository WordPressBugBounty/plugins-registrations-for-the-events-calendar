<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$eg_data        = RTEC_Admin::get_plugin_data( 'event-genius' );
$eg_is_active   = ! empty( $eg_data['is_active'] );
$eg_is_installed = ! empty( $eg_data['is_installed'] );
$install_label = $eg_is_active
	? __( 'Continue to Event Genius', 'registrations-for-the-events-calendar' )
	: ( $eg_is_installed
		? __( 'Activate Event Genius', 'registrations-for-the-events-calendar' )
		: __( 'Install Event Genius', 'registrations-for-the-events-calendar' ) );
?>
<h2 class="rtec-wizard-step-title">
	<?php
	if ( $eg_is_active ) {
		esc_html_e( 'Step 2 of 4: Continue to Event Genius', 'registrations-for-the-events-calendar' );
	} else {
		esc_html_e( 'Step 2 of 4: Install Event Genius', 'registrations-for-the-events-calendar' );
	}
	?>
</h2>
<p class="rtec-wizard-step-body">
	<?php
	if ( $eg_is_active ) {
		esc_html_e( 'Event Genius is installed and active. Continue to the migration wizard to import your events and registrations.', 'registrations-for-the-events-calendar' );
	} else {
		esc_html_e( 'Event Genius is required before migration can begin. We\'ll install and activate it for you, then continue the migration automatically.', 'registrations-for-the-events-calendar' );
	}
	?>
</p>

<div class="rtec-migration-wizard-install-start">
	<button type="button" class="button button-primary rtec-wizard-cta-with-chevron" id="rtec-migration-wizard-install-eg">
		<span class="rtec-button-text"><?php echo esc_html( $install_label ); ?></span>
		<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
	</button>
</div>
<p class="rtec-wizard-ajax-message" id="rtec-migration-wizard-status" aria-live="polite"></p>
