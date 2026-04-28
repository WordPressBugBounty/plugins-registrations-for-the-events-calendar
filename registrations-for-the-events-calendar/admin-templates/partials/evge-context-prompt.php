<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Event Genius is active while The Events Calendar is not — prompt to choose stack.
 *
 * Expects $evge_continue_url and $evge_use_rtec_url (admin URLs with nonces).
 */
?>
<div class="rtec-onboarding-evge-context">
	<h2 class="rtec-onboarding-step-title"><?php esc_html_e( 'Event Genius is already active', 'registrations-for-the-events-calendar' ); ?></h2>
	<p class="rtec-onboarding-step-body"><?php esc_html_e( 'Event Genius includes built-in event registration, so you may not need this plugin.', 'registrations-for-the-events-calendar' ); ?></p>
	<div class="rtec-onboarding-cta-footer rtec-onboarding-evge-context-actions">
		<p class="rtec-onboarding-evge-primary-wrap">
			<a href="<?php echo esc_url( $evge_continue_url ); ?>" class="button button-primary rtec-onboarding-cta-with-chevron rtec-onboarding-evge-primary">
				<span class="rtec-button-text"><?php esc_html_e( 'Continue with Event Genius', 'registrations-for-the-events-calendar' ); ?></span>
				<span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
			</a>
		</p>
		<p class="rtec-onboarding-evge-secondary-wrap">
			<a href="<?php echo esc_url( $evge_use_rtec_url ); ?>" class="button button-secondary rtec-onboarding-evge-secondary"><?php esc_html_e( 'Continue with RTEC and The Events Calendar', 'registrations-for-the-events-calendar' ); ?><span class="rtec-button-carat" aria-hidden="true">&#8250;</span>
			</a>
		</p>
	</div>
</div>
