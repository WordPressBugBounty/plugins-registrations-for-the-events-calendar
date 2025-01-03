<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
settings_errors(); ?>
<div class="rtec-individual-available-notice">
	<p><strong><span class="rtec-individual-available">&#42;</span><?php esc_html_e( 'Can also be set for each event separately on the Events->Edit page', 'registrations-for-the-events-calendar' ); ?></strong></p>
</div>
<hr>
<form method="post" action="options.php">
	<?php settings_fields( 'rtec_options' ); ?>
	<?php do_settings_sections( 'rtec_form_registration_availability' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr />
	<?php do_settings_sections( 'rtec_form_form_fields' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr />
	<?php do_settings_sections( 'rtec_form_custom_text' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr />
	<?php do_settings_sections( 'rtec_form_visitors_options' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr />
	<?php do_settings_sections( 'rtec_form_users_options' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr />
	<?php do_settings_sections( 'rtec_attendee_data' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<span id="styling"></span>
	<hr />
	<?php do_settings_sections( 'rtec_form_styles' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr />
	<?php do_settings_sections( 'rtec_advanced' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
</form>