<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
settings_errors(); ?>
<div class="rtec-settings-form-wrap">
	<div class="rtec-individual-available-notice">
		<p><span class="rtec-individual-available">&#42;</span><?php esc_html_e( 'Can also be set for each event separately on the Events->Edit page', 'registrations-for-the-events-calendar' ); ?></p>
	</div>
	<form method="post" action="options.php">
		<?php settings_fields( 'rtec_options' ); ?>
		<?php do_settings_sections( 'rtec_email_all' ); ?>
		<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
		<hr>
		<?php do_settings_sections( 'rtec_email_notification' ); ?>
		<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
		<hr>
		<div id="rtec-email-confirmation" class="rtec-settings-section-anchor"><?php do_settings_sections( 'rtec_email_confirmation' ); ?></div>
		<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
		<hr>
		<?php do_settings_sections( 'rtec_unregister_email' ); ?>
		<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
		<hr>
		<?php do_settings_sections( 'rtec_email_advanced' ); ?>
		<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
	</form>
</div>
