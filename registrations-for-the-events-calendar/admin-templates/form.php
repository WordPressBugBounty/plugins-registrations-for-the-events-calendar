<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
settings_errors();
?>

<div class="rtec-settings-form-wrap">
	<form method="post" action="options.php">
		<?php settings_fields( 'rtec_options' ); ?>
		<div id="rtec-form-fields" class="rtec-settings-section-anchor"><?php do_settings_sections( 'rtec_form_form_fields' ); ?></div>
		<p class="submit">
			<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
		</p>
		<hr />
		<?php do_settings_sections( 'rtec_form_defaults' ); ?>
		<hr />
		<?php do_settings_sections( 'rtec_form_registration_management' ); ?>
		<hr />
		<?php do_settings_sections( 'rtec_form_users_options' ); ?>
		<span id="styling"></span>
		<hr />
		<?php do_settings_sections( 'rtec_form_styles' ); ?>
		<p class="submit">
			<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
		</p>
	</form>
</div>