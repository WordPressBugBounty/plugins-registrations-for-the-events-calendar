<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$scan_rows = array(
	'events'        => __( '%d events found', 'registrations-for-the-events-calendar' ),
	'registrations' => __( '%d registrations found', 'registrations-for-the-events-calendar' ),
	'venues'        => __( '%d venues found', 'registrations-for-the-events-calendar' ),
	'organizers'    => __( '%d organizers found', 'registrations-for-the-events-calendar' ),
	'categories'    => __( '%d categories found', 'registrations-for-the-events-calendar' ),
	'tags'          => __( '%d tags found', 'registrations-for-the-events-calendar' ),
);
?>
<ul class="rtec-migration-wizard-scan-summary" aria-label="<?php esc_attr_e( 'Data found on your site', 'registrations-for-the-events-calendar' ); ?>">
	<?php foreach ( $scan_rows as $key => $label_format ) : ?>
		<li class="rtec-migration-wizard-scan-summary-item">
			<?php
			printf(
				esc_html( $label_format ),
				(int) ( $scan_counts[ $key ] ?? 0 )
			);
			?>
		</li>
	<?php endforeach; ?>
</ul>
