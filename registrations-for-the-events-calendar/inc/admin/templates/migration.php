<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
$migration = new RTEC_Migration();

if ( ! empty( $_POST['rtec_migrate_reverse'] ) ) {
	$migration->reset();
	$migration->pause();
	?>
	<div class="updated">
		<p><?php esc_html_e( 'Migration of registrations has been reversed. Use the button below to restart migration.', 'registrations-for-the-events-calendar' ); ?></p>
	</div>
	<?php
}
if ( ! empty( $_POST['rtec_migrate_start'] ) ) {
	$migration->unpause();
	?>
	<div class="updated">
		<p><?php esc_html_e( 'Registrations now being migrated in the background.', 'registrations-for-the-events-calendar' ); ?></p>
	</div>
	<?php
}

$migration_status = get_option( 'rtec_migration_status', array( 'attempts' => 0, 'complete' => false, 'one_migration_done' => false ) );
settings_errors();
?>
<h1><?php esc_html_e( 'Manage Migration', 'registrations-for-the-events-calendar' ); ?></h1>
<p>
	<?php esc_html_e( 'To work properly with events that were migrated with The Events Calendar 6.0, registrations that were made prior to the migration also need to be updated.', 'registrations-for-the-events-calendar' ); ?>
</p>
<p>
	<?php esc_html_e( 'Use this page to manage the migration process.', 'registrations-for-the-events-calendar' ); ?>
</p>
<div id="rtec-migration-report">
	<?php
	$status = $migration->is_complete() ? __( 'complete', 'registrations-for-the-events-calendar' ) : __( 'processing', 'registrations-for-the-events-calendar' );
	if ( ! empty( $migration_status['paused'] ) ) {
		$status = __( 'paused', 'registrations-for-the-events-calendar' );
	}
	$attempts = ! empty($migration_status['attempts'] ) ? $migration_status['attempts'] : 0;
	global $wpdb;
	$table_name  = $wpdb->postmeta;
	$result      = $wpdb->get_col( "
			SELECT meta_value
			FROM $table_name
			WHERE meta_key = '_RTEC_Migration_migrated_to';
			" );
	$num_migrated = count( $result );
	?>
	<h2><?php esc_html_e( 'Report', 'registrations-for-the-events-calendar' ); ?></h2>
	<ul>
		<li><?php esc_html_e( 'Status', 'registrations-for-the-events-calendar' ); ?>: <?php echo esc_html( $status ); ?></li>
		<?php if ( $status !== 'paused' ) : ?>
			<li><?php esc_html_e( 'Attempts', 'registrations-for-the-events-calendar' ); ?>: <?php echo esc_html( $attempts ); ?></li>
		<?php endif; ?>
		<?php if ( $num_migrated !== 0 ) : ?>
			<li><?php esc_html_e( 'Events Migrated', 'registrations-for-the-events-calendar' ); ?>: <?php echo esc_html( $num_migrated ); ?></li>
		<?php endif; ?>
	</ul>
</div>
<?php if ( $migration->is_complete() ) : ?>
	<form method="post" action="">
		<button type="submit" name="rtec_migrate_reverse" class="rtec-button rtec-danger rtec-inline-flex rtec-no-margin" value="yes">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#fff"><path d="M256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C201.7 512 151.2 495 109.7 466.1C95.2 455.1 91.64 436 101.8 421.5C111.9 407 131.8 403.5 146.3 413.6C177.4 435.3 215.2 448 256 448C362 448 448 362 448 256C448 149.1 362 64 256 64C202.1 64 155 85.46 120.2 120.2L151 151C166.1 166.1 155.4 192 134.1 192H24C10.75 192 0 181.3 0 168V57.94C0 36.56 25.85 25.85 40.97 40.97L74.98 74.98C121.3 28.69 185.3 0 255.1 0L256 0zM256 128C269.3 128 280 138.7 280 152V246.1L344.1 311C354.3 320.4 354.3 335.6 344.1 344.1C335.6 354.3 320.4 354.3 311 344.1L239 272.1C234.5 268.5 232 262.4 232 256V152C232 138.7 242.7 128 256 128V128z"/></svg>
			<span class="rtec-button-text"><?php esc_html_e( 'Reverse Migration', 'registrations-for-the-events-calendar' ); ?></span>
		</button>
	</form>
<?php else : ?>
	<form method="post" action="">
		<button type="submit" name="rtec_migrate_start" class="rtec-button rtec-primary rtec-inline-flex rtec-no-margin" value="yes">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#fff"><!--! Font Awesome Pro 6.1.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M449.9 39.96l-48.5 48.53C362.5 53.19 311.4 32 256 32C161.5 32 78.59 92.34 49.58 182.2c-5.438 16.81 3.797 34.88 20.61 40.28c16.97 5.5 34.86-3.812 40.3-20.59C130.9 138.5 189.4 96 256 96c37.96 0 73 14.18 100.2 37.8L311.1 178C295.1 194.8 306.8 223.4 330.4 224h146.9C487.7 223.7 496 215.3 496 204.9V59.04C496 34.99 466.9 22.95 449.9 39.96zM441.8 289.6c-16.94-5.438-34.88 3.812-40.3 20.59C381.1 373.5 322.6 416 256 416c-37.96 0-73-14.18-100.2-37.8L200 334C216.9 317.2 205.2 288.6 181.6 288H34.66C24.32 288.3 16 296.7 16 307.1v145.9c0 24.04 29.07 36.08 46.07 19.07l48.5-48.53C149.5 458.8 200.6 480 255.1 480c94.45 0 177.4-60.34 206.4-150.2C467.9 313 458.6 294.1 441.8 289.6z"/></svg>
			<span class="rtec-button-text"><?php esc_html_e( 'Start Migration', 'registrations-for-the-events-calendar' ); ?></span>
		</button>
	</form>
<?php endif; ?>
