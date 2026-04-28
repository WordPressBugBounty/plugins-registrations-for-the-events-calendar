<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Shared onboarding chrome: logo + plugin title, optional step dots.
 *
 * Expects optional vars before include:
 * - $rtec_onboarding_logo_url (string) — defaults to Roundup logo.
 * - $rtec_onboarding_show_dots (bool) — default false.
 * - $rtec_onboarding_total_steps (int) — required when dots shown.
 * - $rtec_onboarding_progress (int) — current step (1-based), required when dots shown.
 */
$rtec_onboarding_logo_url   = isset( $rtec_onboarding_logo_url ) ? $rtec_onboarding_logo_url : rtec_plugin_url( 'assets/images/RU-Logo-150.png' );
$rtec_onboarding_show_dots  = ! empty( $rtec_onboarding_show_dots );
$rtec_onboarding_total_steps = isset( $rtec_onboarding_total_steps ) ? (int) $rtec_onboarding_total_steps : 0;
$rtec_onboarding_progress    = isset( $rtec_onboarding_progress ) ? (int) $rtec_onboarding_progress : 0;
?>
<header class="rtec-onboarding-header">
	<div class="rtec-onboarding-header-main">
		<div class="rtec-onboarding-header-brand">
			<img src="<?php echo esc_url( $rtec_onboarding_logo_url ); ?>" alt="" class="rtec-onboarding-logo" width="45" height="45">
			<span class="rtec-onboarding-header-title">
				<span class="rtec-onboarding-header-title-main"><?php esc_html_e( 'Registrations', 'registrations-for-the-events-calendar' ); ?></span>
				<span class="rtec-onboarding-header-title-sub"><?php esc_html_e( 'for the Events Calendar', 'registrations-for-the-events-calendar' ); ?></span>
			</span>
		</div>
		<?php if ( $rtec_onboarding_show_dots && $rtec_onboarding_total_steps > 0 ) : ?>
		<nav class="rtec-onboarding-dots" role="tablist" aria-label="<?php esc_attr_e( 'Onboarding progress', 'registrations-for-the-events-calendar' ); ?>">
			<?php for ( $i = 1; $i <= $rtec_onboarding_total_steps; $i++ ) : ?>
				<span class="rtec-onboarding-dot <?php echo (int) $i === (int) $rtec_onboarding_progress ? 'rtec-onboarding-dot-current' : ''; ?> <?php echo (int) $i < (int) $rtec_onboarding_progress ? 'rtec-onboarding-dot-done' : ''; ?>" aria-current="<?php echo (int) $i === (int) $rtec_onboarding_progress ? 'step' : 'false'; ?>"></span>
			<?php endfor; ?>
		</nav>
		<?php endif; ?>
	</div>
</header>
