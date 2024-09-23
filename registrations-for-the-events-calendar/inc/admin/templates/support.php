<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( -1 );
}
?>
<h1><?php esc_html_e( 'Need Help?', 'registrations-for-the-events-calendar' ); ?></h1>
<?php
global $wpdb;
$table_name = esc_sql( $wpdb->prefix . RTEC_TABLENAME );

if ( isset( $_GET['rtec_troubleshoot'] ) ) {
	$charset_collate = $wpdb->get_charset_collate();

	if ( $wpdb->get_var( "show tables like '$table_name'" ) !== $table_name ) {
		$sql = 'CREATE TABLE ' . $table_name . " (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
                event_id BIGINT(20) UNSIGNED NOT NULL,
                registration_date DATETIME NOT NULL,
                last_name VARCHAR(1000) NOT NULL,
                first_name VARCHAR(1000) NOT NULL,
                email VARCHAR(1000) NOT NULL,
                venue VARCHAR(1000) NOT NULL,
                phone VARCHAR(40) DEFAULT '' NOT NULL,
                other VARCHAR(1000) DEFAULT '' NOT NULL,
                guests INT(11) UNSIGNED DEFAULT 0 NOT NULL,
                custom LONGTEXT DEFAULT '' NOT NULL,
                status CHAR(1) DEFAULT 'y' NOT NULL,
                action_key VARCHAR(40) DEFAULT '' NOT NULL,
                reminder VARCHAR(40) DEFAULT 'pending' NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;";
		$wpdb->query( $sql );
		if ( $wpdb->last_error !== '' ) {
			$last_db_error = $wpdb->last_error;
			$wpdb->print_error();
		} else {
			?>
			<div class="updated notice">
				<p>Registrations table created successfully.</p>
			</div>
			<?php
		}
	} else {
		?>
		<div class="updated notice">
			<p>Registrations table exists.</p>
		</div>
		<?php
	}

	$db = new RTEC_Db_Admin();
	$db->maybe_add_index( 'event_id', 'event_id' );
	if ( isset( $last_db_error ) && $last_db_error !== '' ) {
		delete_transient( 'rtec_last_db_error' );
		set_transient( 'rtec_last_db_error', $last_db_error, 60 * 60 * 48 );
	}
	$db->maybe_add_index( 'status', 'status' );
	if ( $wpdb->last_error !== '' ) {
		$wpdb->print_error();
	}
}


$reg_table_exists = ( $wpdb->get_var( "show tables like '$table_name'" ) === $table_name );
if ( ! $reg_table_exists && ! isset( $_GET['rtec_troubleshoot'] ) ) {
	?>
	<div class="error notice">
		<p>Registrations table does not exist. <a href="<?php echo esc_url( add_query_arg( 'rtec_troubleshoot', 'true', get_admin_url( null, 'edit.php?post_type=tribe_events&page=registrations-for-the-events-calendar&tab=support' ) ) ); ?>">Click here</a> to attempt to create the table and record debugging info.</p>
	</div>
	<?php

}
$migration_status = get_option(
	'rtec_migration_status',
	array(
		'attempts'           => 0,
		'complete'           => false,
		'one_migration_done' => false,
	)
);

?>
<?php if ( rtec_doing_series() && ! empty( $migration_status['one_migration_done'] ) ) : ?>
	<p>
		<?php esc_html_e( 'Some of your registrations have been migrated to work with The Events Calendar 6.0+.', 'registrations-for-the-events-calendar' ); ?>
		<br><a href="?page=rtec-support&tab=migration" class="button-secondary"><?php esc_html_e( 'Manage Migration', 'registrations-for-the-events-calendar' ); ?></a>
	</p>
<?php endif; ?>
<p>
	<span class="rtec-support-title"><i class="fa fa-life-ring" aria-hidden="true"></i>&nbsp; <a href="https://roundupwp.com/products/registrations-for-the-events-calendar/setup/?utm_campaign=rtec-free&utm_source=support&utm_medium=faq-setup&utm_content=SetupDirections" target="_blank"><?php esc_html_e( 'Setup Directions', 'registrations-for-the-events-calendar' ); ?></a></span>
	<br /><?php esc_html_e( 'A step-by-step guide on how to setup and use the plugin.', 'registrations-for-the-events-calendar' ); ?>
</p>
<p>
	<span class="rtec-support-title"><i class="fa fa-question-circle" aria-hidden="true"></i>&nbsp; <a href="https://roundupwp.com/docs/faqs/?utm_campaign=rtec-free&utm_source=support&utm_medium=faq-faqspage&utm_content=FAQsAndDocs" target="_blank"><?php esc_html_e( 'FAQs and Documentation', 'registrations-for-the-events-calendar' ); ?></a></span>
	<br /><?php esc_html_e( 'You might find some help with our FAQs and troubleshooting guides.', 'registrations-for-the-events-calendar' ); ?>
</p>
<p>
	<span class="rtec-support-title"><i class="fa fa-envelope-o" aria-hidden="true"></i>&nbsp; <a href="https://wordpress.org/support/plugin/registrations-for-the-events-calendar/" target="_blank"><?php esc_html_e( 'Request Support', 'registrations-for-the-events-calendar' ); ?></a></span>
	<br /><?php esc_html_e( 'Have a problem? Post in the WordPress.org support forum and someone from our team will reply you as soon as they are able.', 'registrations-for-the-events-calendar' ); ?>
</p>

<br />
<h2><?php esc_html_e( 'System Info', 'registrations-for-the-events-calendar' ); ?> </h2>
<p><?php esc_html_e( 'You may be asked to follow up with a support request in the forum by submitting a ticket on our website. Include your system info only with the privacy of our ticketing system.', 'registrations-for-the-events-calendar' ); ?></p>

<p><?php esc_html_e( 'Click the text below to select all', 'registrations-for-the-events-calendar' ); ?></p>

<textarea readonly="readonly" onclick="this.focus();this.select()" title="To copy, click the field then press Ctrl + C (PC) or Cmd + C (Mac)." style="width: 70%; height: 500px; white-space: pre;">
## SITE/SERVER INFO: ##
Plugin Version:           <?php echo esc_html( RTEC_TITLE . ' v' . RTEC_VERSION ) . "\n"; ?>
Site URL:                 <?php echo esc_html( site_url() ) . "\n"; ?>
Home URL:                 <?php echo esc_html( home_url() ) . "\n"; ?>
WordPress Version:        <?php echo esc_html( get_bloginfo( 'version' ) ) . "\n"; ?>
PHP Version:              <?php echo esc_html( PHP_VERSION ) . "\n"; ?>
Web Server Info:          
<?php
if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
	echo esc_html( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) ) . "\n"; }
?>

## ACTIVE PLUGINS: ##
<?php
$plugins        = get_plugins();
$active_plugins = get_option( 'active_plugins', array() );

foreach ( $plugins as $plugin_path => $plugin ) {
	// If the plugin isn't active, don't show it.
	if ( in_array( $plugin_path, $active_plugins, true ) ) {
		echo esc_html( $plugin['Name'] ) . ': ' . esc_html( $plugin['Version'] ) . "\n";
	}
}
?>

## OPTIONS: ##
<?php
$db_options = get_option( 'rtec_db_version', '' );
echo esc_html( str_pad( 'database version:', 28 ) . $db_options ) . "\n";
$options = get_option( 'rtec_options' );

foreach ( $options as $key => $val ) {
	$label = esc_html( $key ) . ':';
	if ( is_array( $val ) ) {
		foreach ( $val as $key2 => $val2 ) {
			echo esc_html( $key . ' - ' . str_pad( $key2, 28 ) ) . esc_textarea( (string) $val2 ) . "\n";
		}
	} else {
		$value = isset( $val ) ? esc_html( $val ) : 'unset';
		echo esc_html( str_pad( $label, 28 ) ) . esc_textarea( $value ) . "\n";
	}
}

$column_descriptions = $wpdb->get_results( "DESCRIBE $table_name" );

echo "\n";

foreach ( $column_descriptions as $column ) {
	echo esc_textarea( 'Field: ' . $column->Field . ', Type: ' . $column->Type . ', Key: ' . $column->Key . ', Extra: ' . $column->Extra ) . "\n";
}

if ( $reg_table_exists ) {

	$last_result = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC LIMIT 1;" );

	echo "\n";

	if ( is_array( $last_result ) ) {

		foreach ( $last_result as $column ) {

			foreach ( $column as $key => $value ) {

				if ( $key !== 'first_name' && $key !== 'last_name' && $key !== 'custom' && $key !== 'phone' && $key !== 'email' ) {
					echo esc_html( $key ) . ': ' . esc_html( $value );
				} else {
					echo esc_html( $key ) . ': ' . esc_html( substr( $value, 0, 3 ) );
				}

				echo "\n";
			}
		}
	} else {
		echo 'no submissions currently';
	}
}
?>

# Upcoming Event: #
<?php
if ( function_exists( 'tribe_get_events' ) ) {
	$event = rtec_get_events(
		array(
			'posts_per_page' => 1,
			'start_date'     => gmdate( 'Y-m-d H:i:s' ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( ! empty( $event[0] ) ) {

		echo esc_url( get_the_permalink( $event[0]->ID ) ) . "\n";

	} else {
		echo 'no upcoming found';
	}
} else {
	echo 'tribe_get_events does not exist';
}

?>

# Last Submission Error: #
<?php
$last_sub_error = get_transient( 'rtecSubmissionError' );
if ( $last_sub_error ) {
	foreach ( $last_sub_error as $key => $val ) {
		if ( is_array( $val ) ) {
			foreach ( $val as $key2 => $val2 ) {
				$value = isset( $val2 ) ? esc_html( $val2 ) : 'unset';
				echo esc_html( $key ) . ' - ' . esc_html( str_pad( $key2, 28 ) ) . esc_html( (string) $value ) . "\n";
			}
		} else {
			$value = isset( $val ) ? esc_html( $val ) : 'unset';
			echo esc_html( $key . ' - ' . $value ) . "\n";
		}
	}
} else {
	echo 'no recent submission errors';
	echo "\n";
}
?>

# Last Email Error: #
<?php
$last_email_error = get_transient( 'rtec_last_email_error' );
if ( $last_email_error ) {
	foreach ( $last_email_error as $key => $val ) {
		if ( is_array( $val ) ) {
			foreach ( $val as $key2 => $val2 ) {
				$value = isset( $val2 ) ? esc_html( $val2 ) : 'unset';
				echo esc_html( $key ) . ' - ' . esc_html( str_pad( $key2, 28 ) ) . esc_html( (string) $value ) . "\n";
			}
		} else {
			$value = isset( $val ) ? esc_html( $val ) : 'unset';
			echo esc_html( $key ) . ' - ' . esc_html( $value ) . "\n";
		}
	}
} else {
	echo 'no recent email errors';
	echo "\n";
}
if ( get_transient( 'rtec_last_db_error' ) ) {
	if ( is_array( get_transient( 'rtec_last_db_error' ) ) ) {
		foreach ( get_transient( 'rtec_last_db_error' ) as $key => $val ) {
			if ( is_array( $val ) ) {
				foreach ( $val as $key2 => $val2 ) {
					$value = isset( $val2 ) ? esc_html( $val2 ) : 'unset';
					echo esc_html( $key ) . ' - ' . esc_html( str_pad( $key2, 28 ) ) . esc_html( $value ) . "\n";
				}
			} else {
				$value = isset( $val ) ? esc_html( $val ) : 'unset';
				echo esc_html( $key ) . ' - ' . esc_html( $value ) . "\n";
			}
		}
	} else {
		echo esc_html( get_transient( 'rtec_last_db_error' ) );
	}
}
?>
</textarea>
