<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$support_url = 'https://roundupwp.com/support/';
?>
<div class="wrap rtec-admin-wrap rtec-wizard-page rtec-migration-wizard-page">
	<div class="rtec-wizard-outer">
		<?php
		$rtec_wizard_logo_url    = $logo_url;
		$rtec_wizard_show_title  = false;
		$rtec_wizard_show_dots   = true;
		$rtec_wizard_total_steps = $total_steps;
		$rtec_wizard_progress    = $progress;
		$rtec_wizard_dots_label  = __( 'Migration progress', 'registrations-for-the-events-calendar' );
		require rtec_plugin_path( 'admin-templates/partials/wizard-header.php' );
		?>

		<div class="rtec-wizard-wrap rtec-welcome-screen">
			<div class="rtec-wizard-content rtec-welcome-text">
				<?php
				if ( 1 === (int) $step ) {
					require rtec_plugin_path( 'admin-templates/migration-wizard/step-compatibility.php' );
				} elseif ( 2 === (int) $step ) {
					require rtec_plugin_path( 'admin-templates/migration-wizard/step-install-eg.php' );
				}
				?>
			</div>
		</div>

		<footer class="rtec-wizard-support-footer">
			<?php esc_html_e( 'Need help?', 'registrations-for-the-events-calendar' ); ?>
			<a href="<?php echo esc_url( $support_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contact support', 'registrations-for-the-events-calendar' ); ?></a>
		</footer>
	</div>
</div>
