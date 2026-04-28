<?php
/**
 * All Registrations (Latest) page: list of all registrations with identity column (manage link),
 * registration date, event (link to single), event date, status.
 * Supports search (registrants), status filter, and pagination. No multiple venues/tiers.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$admin_registrations = new RTEC_Admin_Registrations();
$admin_registrations->build_admin_registrations( 'latest', array() );

$menu_slug           = defined( 'RTEC_MENU_SLUG' ) ? RTEC_MENU_SLUG : 'registrations-for-the-events-calendar';
$per_page            = 20;
$search_param        = 'latest_search';
$paged_param         = 'paged';
$status_param        = 'registration_status';

$search_query        = isset( $_GET[ $search_param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $search_param ] ) ) : '';
$registration_status = isset( $_GET[ $status_param ] ) ? sanitize_key( $_GET[ $status_param ] ) : 'active';
$current_page        = isset( $_GET[ $paged_param ] ) ? max( 1, (int) $_GET[ $paged_param ] ) : 1;
$offset              = ( $current_page - 1 ) * $per_page;

$base_url = add_query_arg( array(
	'page' => $menu_slug,
	'tab'  => 'latest',
), admin_url( 'admin.php' ) );

$db     = new RTEC_Db_Admin();
$result = $db->get_latest_registrations( array(
	'search'             => $search_query,
	'registration_status' => $registration_status,
	'limit'              => $per_page,
	'offset'             => $offset,
) );

$registrations = $result['registrations'];
$total_items   = $result['total'];
$total_pages   = $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 1;
$total_pages   = max( 1, $total_pages );

$tab = 'latest';

require_once rtec_plugin_path( 'admin-templates/partials/latest-filter-bar.php' );
?>
<div class="rtec-table-wrap rtec-latest-table-wrap">
	<table class="widefat striped rtec-registrations-data rtec-latest-registrations-data">
		<thead>
			<tr>
				<th><span class="screen-reader-text"><?php esc_html_e( 'Name', 'registrations-for-the-events-calendar' ); ?></span></th>
				<th><?php esc_html_e( 'Email', 'registrations-for-the-events-calendar' ); ?></th>
				<th><?php esc_html_e( 'Registration Date', 'registrations-for-the-events-calendar' ); ?></th>
				<th><?php esc_html_e( 'Event', 'registrations-for-the-events-calendar' ); ?></th>
				<th><?php esc_html_e( 'Event Date', 'registrations-for-the-events-calendar' ); ?></th>
				<th><?php esc_html_e( 'Status', 'registrations-for-the-events-calendar' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $registrations ) ) : ?>
				<?php foreach ( $registrations as $registration ) : ?>
					<?php
					$event_meta = rtec_get_event_meta( $registration['event_id'] );
					include rtec_plugin_path( 'admin-templates/partials/reg-table/latest-row.php' );
					?>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6" class="rtec-no-results"><?php esc_html_e( 'No Registrations Yet', 'registrations-for-the-events-calendar' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<?php
require_once rtec_plugin_path( 'admin-templates/partials/latest-pagination.php' );
?>
<?php require_once rtec_plugin_path( 'admin-templates/rtec-content-modal/shell.php' ); ?>
