<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
settings_errors(); ?>
<div class="rtec-individual-available-notice">
	<p><strong><span class="rtec-individual-available">&#42;</span><?php esc_html_e( 'Can also be set for each event separately on the Events->Edit page', 'registrations-for-the-events-calendar' ); ?></strong></p>
</div>
<?php

$new_status = get_transient( 'rtec_smtp_message' );
if ( $new_status === 'yes' ) :
	?>
<div id="rtec-smtp-notice" class="rtec-notice rtec-box-shadow rtec-complex-notice notice notice-info is-dismissible">
	<div class="rtec-msg-wrap">
		<h3><?php esc_html_e( 'Are You Using an SMTP Service?', 'registrations-for-the-events-calendar' ); ?></h3>
		<p><?php esc_html_e( 'Emails sent from the same server your website is hosted on are often rejected by email providers. We recommend using an SMTP service for best results with emails.', 'registrations-for-the-events-calendar' ); ?></p>
		<div class="rtec-button-wrap">
			<a class="button button-primary rtec-cta" href="https://roundupwp.com/faq/send-email-sendinblue-wp-mail-smtp/?utm_campaign=rtec-free&utm_source=dashboard-notice&utm_medium=email-smtp&utm_content=LearnMore" target="_blank" rel="noopener"><?php esc_html_e( 'Learn More', 'registrations-for-the-events-calendar' ); ?></a>
		</div>
	</div>
</div>

<?php endif; ?>
<hr>
<form method="post" action="options.php">
	<?php settings_fields( 'rtec_options' ); ?>
	<?php do_settings_sections( 'rtec_email_all' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr>
	<?php do_settings_sections( 'rtec_email_notification' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr>
	<?php do_settings_sections( 'rtec_email_confirmation' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
	<hr>
	<?php do_settings_sections( 'rtec_unregister_email' ); ?>
	<input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
</form>
