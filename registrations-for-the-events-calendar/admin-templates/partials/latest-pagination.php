<?php
/**
 * Pagination for All Registrations (Latest) page, shown at the bottom.
 *
 * Expects: $total_items, $total_pages, $current_page, $search_query, $registration_status,
 *          $base_url, $paged_param, $search_param, $status_param.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( $total_pages <= 1 ) {
	return;
}
?>
<div class="tablenav bottom rtec-latest-pagination">
	<div class="tablenav-pages">
		<span class="pagination-links">
			<?php
			$first_url = add_query_arg( array( $paged_param => 1, $search_param => $search_query, $status_param => $registration_status ), remove_query_arg( $paged_param, $base_url ) );
			$prev_url  = $current_page > 1 ? add_query_arg( array( $paged_param => $current_page - 1, $search_param => $search_query, $status_param => $registration_status ), remove_query_arg( $paged_param, $base_url ) ) : '';
			$next_url  = $current_page < $total_pages ? add_query_arg( array( $paged_param => $current_page + 1, $search_param => $search_query, $status_param => $registration_status ), remove_query_arg( $paged_param, $base_url ) ) : '';
			$last_url  = add_query_arg( array( $paged_param => $total_pages, $search_param => $search_query, $status_param => $registration_status ), remove_query_arg( $paged_param, $base_url ) );
			?>
			<a class="first-page button" href="<?php echo esc_url( $first_url ); ?>"<?php echo $current_page <= 1 ? ' aria-disabled="true"' : ''; ?>><span class="screen-reader-text"><?php esc_html_e( 'First page', 'registrations-for-the-events-calendar' ); ?></span><span aria-hidden="true">«</span></a>
			<a class="prev-page button" href="<?php echo esc_url( $prev_url ); ?>"<?php echo $current_page <= 1 ? ' aria-disabled="true"' : ''; ?>><span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'registrations-for-the-events-calendar' ); ?></span><span aria-hidden="true">‹</span></a>
			<span class="paging-input">
				<label for="rtec-latest-current-page-bottom" class="screen-reader-text"><?php esc_html_e( 'Current page', 'registrations-for-the-events-calendar' ); ?></label>
				<input class="current-page" id="rtec-latest-current-page-bottom" type="text" name="paged" value="<?php echo esc_attr( $current_page ); ?>" size="1" aria-describedby="table-paging">
				<span class="tablenav-paging-text"> <?php echo esc_html( __( 'of', 'registrations-for-the-events-calendar' ) ); ?> <span class="total-pages"><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></span></span>
			</span>
			<a class="next-page button" href="<?php echo esc_url( $next_url ); ?>"<?php echo $current_page >= $total_pages ? ' aria-disabled="true"' : ''; ?>><span class="screen-reader-text"><?php esc_html_e( 'Next page', 'registrations-for-the-events-calendar' ); ?></span><span aria-hidden="true">›</span></a>
			<a class="last-page button" href="<?php echo esc_url( $last_url ); ?>"<?php echo $current_page >= $total_pages ? ' aria-disabled="true"' : ''; ?>><span class="screen-reader-text"><?php esc_html_e( 'Last page', 'registrations-for-the-events-calendar' ); ?></span><span aria-hidden="true">»</span></a>
		</span>
		<span class="displaying-num"><?php echo esc_html( sprintf( _n( '%s item', '%s items', $total_items, 'registrations-for-the-events-calendar' ), number_format_i18n( $total_items ) ) ); ?></span>
	</div>
	<br class="clear">
</div>
