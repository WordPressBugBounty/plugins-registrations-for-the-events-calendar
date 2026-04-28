<?php
/**
 * Filter bar for All Registrations (Latest) page: status filter (left), search (right).
 * Pagination is shown at the bottom of the page via latest-pagination.php.
 *
 * Expects: $search_query, $registration_status, $search_param, $status_param.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$menu_slug = defined( 'RTEC_MENU_SLUG' ) ? RTEC_MENU_SLUG : 'registrations-for-the-events-calendar';
?>
<div class="rtec-filter-bar rtec-latest-filter-bar">
	<div class="rtec-latest-status-filter">
		<form method="get" class="rtec-status-form">
			<input type="hidden" name="page" value="<?php echo esc_attr( $menu_slug ); ?>">
			<input type="hidden" name="tab" value="latest">
			<input type="hidden" name="<?php echo esc_attr( $search_param ); ?>" value="<?php echo esc_attr( $search_query ); ?>">
			<label for="rtec-latest-status" class="screen-reader-text"><?php esc_html_e( 'Filter by status', 'registrations-for-the-events-calendar' ); ?></label>
			<select id="rtec-latest-status" name="<?php echo esc_attr( $status_param ); ?>">
				<option value="active" <?php selected( $registration_status, 'active' ); ?>><?php esc_html_e( 'All statuses', 'registrations-for-the-events-calendar' ); ?></option>
				<option value="confirmed" <?php selected( $registration_status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'registrations-for-the-events-calendar' ); ?></option>
				<option value="pending" <?php selected( $registration_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'registrations-for-the-events-calendar' ); ?></option>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'registrations-for-the-events-calendar' ); ?></button>
		</form>
	</div>

	<div class="rtec-flex-align-center rtec-search-box">
		<form method="get" class="rtec-search-form">
			<input type="hidden" name="page" value="<?php echo esc_attr( $menu_slug ); ?>">
			<input type="hidden" name="tab" value="latest">
			<input type="hidden" name="<?php echo esc_attr( $status_param ); ?>" value="<?php echo esc_attr( $registration_status ); ?>">
			<p class="search-box">
				<span class="rtec-toolbar-icon">
					<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g opacity="0.7">
							<path d="M12.432 7.24711C12.432 10.404 9.87283 12.9631 6.71598 12.9631C3.55913 12.9631 1 10.404 1 7.24711C1 4.09026 3.55913 1.53113 6.71598 1.53113C9.87283 1.53113 12.432 4.09026 12.432 7.24711Z" stroke="currentColor" stroke-width="2"/>
							<line x1="10.2374" y1="11.7251" x2="14.7069" y2="16.1947" stroke="currentColor" stroke-width="2"/>
						</g>
					</svg>
				</span>
				<label class="screen-reader-text" for="rtec-latest-search-input"><?php esc_html_e( 'Search registrants', 'registrations-for-the-events-calendar' ); ?></label>
				<input type="search" id="rtec-latest-search-input" name="<?php echo esc_attr( $search_param ); ?>" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search registrants', 'registrations-for-the-events-calendar' ); ?>">
			</p>
		</form>
	</div>
</div>
