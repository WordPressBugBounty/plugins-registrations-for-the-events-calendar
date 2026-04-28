<?php
/**
 * Registrations for the Events Calendar RTEC Template
 * Creates the outer wrapping element of all HTML when the registration
 * form is live.
 *
 * @version 2.5 Registrations for the Events Calendar by Roundup WP
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$event_id = isset( $event_meta['post_id'] ) ? (int) $event_meta['post_id'] : 0;
?>
<?php do_action( 'rtec_before_outer_wrap', $event_id ); ?>

<div class="rtec-outer-wrap<?php echo esc_attr( $outer_wrap_classes ); ?>"<?php echo $data_atts; ?>>
	<?php

	do_action( 'rtec_before_display_form', $before_display_args );

	echo $event_header_html;

	echo $attendee_list_html;

	echo $attendance_count_html;

	do_action( 'rtec_before_the_register_button', $event_id ); ?>

	<div id="rtec" class="rtec<?php echo esc_attr( $classes_string ); ?>"<?php echo $data_string; ?>>
		<?php

        $this->registration_alerts( $event_goer );

    if ( ! $event_goer->get_event_status() ) :
		echo $register_button_html;

			do_action( 'rtec_before_the_form_html', $form_styles, $event_id );

			require RTEC_Form::get_template( 'form' );

			do_action( 'rtec_after_the_form_html', $event_id );
		endif;

		echo $already_registered_tools_html;
		?>
	</div>
</div>

<?php do_action( 'rtec_after_outer_wrap', $event_id ); ?>
