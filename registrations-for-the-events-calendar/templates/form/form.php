<?php
/**
 * Registrations for the Events Calendar Form Template
 * Creates the registration form.
 *
 * Can be overridden in your theme -> rtec/form/form.php
 *
 * @version 2.5 Registrations for the Events Calendar by Roundup WP
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$event_user = null;
?>
<?php do_action( 'rtec_after_reveal_button', $event_meta, $event_user ); ?>

<div class="rtec-form-wrapper rtec-toggle-on-click<?php echo esc_attr( $form_styles['form_class_att'] ); ?>" data-remaining="<?php echo (int) $max_guests; ?>" style="<?php echo esc_attr( $form_styles['form_style_att'] ); ?>">

	<?php echo $attendance_message_html; ?>
	<div class="rtec-field-group-menu rtec-form-spacing-sides"></div>

	<form method="post" action="<?php echo esc_url( get_the_permalink() ); ?>" id="rtec-form" class="rtec-form">

		<input type="hidden" name="rtec_email_submission" value="1" />
		<input type="hidden" name="rtec_event_id" value="<?php echo (int) $event_meta['post_id']; ?>" />
		<?php echo $additional_hidden_fields_html; ?>

		<?php do_action( 'rtec_before_field_groups', $event_meta['post_id'] ); ?>

		<div class="rtec-field-group rtec-field-group-main">
			<div class="rtec-fg-header rtec-form-spacing-sides"><span class="rtec-fg-identifier"></span></div>
			<?php include rtec_plugin_path( 'templates/form/field-group.php' ); ?>
		</div>

		<div class="rtec-form-field rtec-user-comments" style="display: none;">
			<label for="rtec_user_comments" class="rtec_text_label">Comments</label>
			<div class="rtec-input-wrapper">
				<input type="text" name="rtec_user_comments" value="" id="rtec_user_comments" />
				<p><?php esc_html_e( 'If you are a human, do not fill in this field', 'registrations-for-the-events-calendar' ); ?></p>
			</div>
		</div>

		<div class="rtec-form-buttons rtec-form-spacing-sides">
			<input type="submit" class="rtec-submit-button<?php echo esc_attr( $form_styles['button_class_att'] ); ?><?php echo esc_attr( $submit_button_class ); ?>" name="rtec_submit" value="<?php echo esc_attr( $submit_button_text ); ?>" style="<?php echo esc_attr( $form_styles['button_style_att'] ); ?>"/>
		</div>

	</form>

</div>
