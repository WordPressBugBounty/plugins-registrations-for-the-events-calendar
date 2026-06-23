<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Backward-compatible shim — delegates to wizard-header.php.
 */
$rtec_wizard_logo_url    = isset( $rtec_onboarding_logo_url ) ? $rtec_onboarding_logo_url : rtec_plugin_url( 'assets/images/RU-Logo-150.png' );
$rtec_wizard_show_title  = true;
$rtec_wizard_show_dots   = ! empty( $rtec_onboarding_show_dots );
$rtec_wizard_total_steps = isset( $rtec_onboarding_total_steps ) ? (int) $rtec_onboarding_total_steps : 0;
$rtec_wizard_progress    = isset( $rtec_onboarding_progress ) ? (int) $rtec_onboarding_progress : 0;
$rtec_wizard_dots_label  = __( 'Onboarding progress', 'registrations-for-the-events-calendar' );
require rtec_plugin_path( 'admin-templates/partials/wizard-header.php' );
