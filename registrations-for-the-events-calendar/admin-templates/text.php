<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
settings_errors();
?>
<div class="rtec-settings-form-wrap">
	<form method="post" action="options.php">
		<?php settings_fields( 'rtec_options' ); ?>
		<?php do_settings_sections( 'rtec_text_source' ); ?>
		<hr>
		<?php do_settings_sections( 'rtec_form_custom_text' ); ?>
		<p class="submit">
			<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
		</p>
		<hr>
		<?php do_settings_sections( 'rtec_form_visitors_messages' ); ?>
		<p class="submit">
			<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes', 'registrations-for-the-events-calendar' ); ?>" />
		</p>
	</form>
</div>
