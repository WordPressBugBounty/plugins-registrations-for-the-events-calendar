<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Shared wizard chrome: logo + optional title, step dots.
 *
 * Expects optional vars before include:
 * - $rtec_wizard_logo_url (string) — defaults to Roundup logo.
 * - $rtec_wizard_show_title (bool) — show plugin title next to logo.
 * - $rtec_wizard_show_dots (bool) — default false.
 * - $rtec_wizard_total_steps (int) — required when dots shown.
 * - $rtec_wizard_progress (int) — current step (1-based), required when dots shown.
 * - $rtec_wizard_dots_label (string) — aria-label for dots nav.
 */
$rtec_wizard_logo_url    = isset( $rtec_wizard_logo_url ) ? $rtec_wizard_logo_url : rtec_plugin_url( 'assets/images/RU-Logo-150.png' );
$rtec_wizard_show_title  = ! empty( $rtec_wizard_show_title );
$rtec_wizard_show_dots   = ! empty( $rtec_wizard_show_dots );
$rtec_wizard_total_steps = isset( $rtec_wizard_total_steps ) ? (int) $rtec_wizard_total_steps : 0;
$rtec_wizard_progress    = isset( $rtec_wizard_progress ) ? (int) $rtec_wizard_progress : 0;
$rtec_wizard_dots_label  = isset( $rtec_wizard_dots_label ) ? $rtec_wizard_dots_label : __( 'Wizard progress', 'registrations-for-the-events-calendar' );
?>
<header class="rtec-wizard-header">
	<div class="rtec-wizard-header-main">
		<div class="rtec-wizard-header-brand">
			<img src="<?php echo esc_url( $rtec_wizard_logo_url ); ?>" alt="" class="rtec-wizard-logo" width="45" height="45">
			<?php if ( $rtec_wizard_show_title ) : ?>
			<span class="rtec-wizard-header-title">
				<span class="rtec-wizard-header-title-main"><?php esc_html_e( 'Registrations', 'registrations-for-the-events-calendar' ); ?></span>
				<span class="rtec-wizard-header-title-sub"><?php esc_html_e( 'for the Events Calendar', 'registrations-for-the-events-calendar' ); ?></span>
			</span>
			<?php endif; ?>
		</div>
		<?php if ( $rtec_wizard_show_dots && $rtec_wizard_total_steps > 0 ) : ?>
		<nav class="rtec-wizard-dots" role="tablist" aria-label="<?php echo esc_attr( $rtec_wizard_dots_label ); ?>">
			<?php for ( $i = 1; $i <= $rtec_wizard_total_steps; $i++ ) : ?>
				<span
					class="rtec-wizard-dot <?php echo (int) $i === (int) $rtec_wizard_progress ? 'rtec-wizard-dot-current' : ''; ?> <?php echo (int) $i < (int) $rtec_wizard_progress ? 'rtec-wizard-dot-done' : ''; ?>"
					aria-current="<?php echo (int) $i === (int) $rtec_wizard_progress ? 'step' : 'false'; ?>"
				></span>
			<?php endfor; ?>
		</nav>
		<?php endif; ?>
	</div>
</header>
