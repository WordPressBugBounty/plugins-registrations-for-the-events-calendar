<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
settings_errors();
?>
<div class="rtec-settings-form-wrap">
	<form method="post" action="options.php">
		<?php settings_fields( 'rtec_options' ); ?>
		<?php do_settings_sections( 'rtec_advanced' ); ?>
		<p class="submit">
			<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
		</p>
	</form>
</div>
