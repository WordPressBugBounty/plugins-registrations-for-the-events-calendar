<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$rtec = RTEC();
$form = $rtec->form->instance();
$db   = $rtec->db_frontend->instance();

$admin_registrations = new RTEC_Admin_Registrations();
$tab                 = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'registrations'; // phpcs:ignore

if ( isset( $_GET['v'] ) ) { // phpcs:ignore
	$view_type = sanitize_key( $_GET['v'] ); // phpcs:ignore
	$admin_registrations->update_view_type_for_user( $view_type );
} else {
	$view_type = $admin_registrations->get_view_type_for_user();
}
$query_type   = isset( $_GET['qtype'] ) ? sanitize_key( $_GET['qtype'] ) : 'upcoming'; // phpcs:ignore
$reg_status   = isset( $_GET['with'] ) ? sanitize_key( $_GET['with'] ) : 'with'; // phpcs:ignore
$query_offset = isset( $_GET['off'] ) ? max( (int) $_GET['off'], 0 ) : 0; // phpcs:ignore
$start_date   = isset( $_GET['start'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $_GET['start'] ) ) : gmdate( 'Y-m-d H:i:s' ); // phpcs:ignore
$settings     = array(
	'v'     => $view_type,
	'qtype' => $query_type,
	'with'  => $reg_status,
	'off'   => $query_offset,
	'start' => $start_date,
);

$admin_registrations->build_admin_registrations( $tab, $settings );
$admin_registrations->the_registrations_overview();

?>
	<h1><?php esc_html_e( 'Overview', 'registrations-for-the-events-calendar' ); ?></h1>

<?php do_action( 'rtec_registrations_tab_after_the_title' ); ?>

	<div class="rtec-wrapper rtec-overview rtec-overview-<?php echo esc_attr( $view_type ); ?>">

		<?php do_action( 'rtec_registrations_tab_before_events' ); ?>

		<?php do_action( 'rtec_registrations_tab_events' ); ?>

		<div class="rtec-clear"></div>

		<?php do_action( 'rtec_registrations_tab_pagination' ); ?>

	</div> <!-- rtec-wrapper -->

<?php
do_action( 'rtec_registrations_tab_events_loaded', $admin_registrations->get_ids_on_page() );
