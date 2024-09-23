<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class RTEC_Migration {
	private $migration_status;

	private $migrations_attempts;

	private $missed_ids;

	const MAX_BATCH = 50;

	const MAX_ATTEMPTS = 100;

	public function __construct() {
		$migration_status       = get_option(
			'rtec_migration_status',
			array(
				'attempts'           => 0,
				'complete'           => false,
				'one_migration_done' => false,
				'secondary_check'    => 'pending',
			)
		);
		$this->migration_status = $migration_status;
		if ( ! is_array( $migration_status ) ) {
			$migration_status = array(
				'attempts'           => 0,
				'complete'           => false,
				'one_migration_done' => false,
			);
		}
		if ( empty( $migration_status['attempts'] ) ) {
			$this->migration_status['attempts']           = 0;
			$this->migration_status['complete']           = false;
			$this->migration_status['one_migration_done'] = false;
			$this->migration_status['secondary_check']    = 'pending';
		}
		$this->migrations_attempts = $this->migration_status['attempts'];
		$this->missed_ids          = get_option( 'rtec_migration_missed', array() );
	}

	/**
	 * Start the handler
	 */
	public function init() {
		$this->hooks();
	}

	/**
	 * Actions
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'maybe_migrate' ) );
		add_action( 'wp_footer', array( $this, 'maybe_migrate' ) );
		add_action( 'rtec_admin_before_template_main', array( $this, 'migration_needed_alert' ) );
		add_action( 'wp_ajax_tec_events_custom_tables_v1_migration_undo', array( $this, 'ajax_undo_migration' ), 0, 2 );
	}

	public function is_complete() {
		if ( $this->migration_status['attempts'] > self::MAX_ATTEMPTS ) {
			return true;
		}
		if ( ! empty( $this->migration_status['complete'] ) ) {
			return $this->migration_status['complete'] === true;
		}
		return false;
	}

	public function is_paused() {
		return ! empty( $this->migration_status['paused'] );
	}

	public function get_migrations_attempts() {
		return $this->migrations_attempts;
	}

	public function update_migrations_attempts( $to_add ) {
		$this->migrations_attempts += $to_add;

		$this->migration_status['attempts'] = $this->migrations_attempts;

		update_option( 'rtec_migration_status', $this->migration_status );
	}

	public function update_migrations_complete( $complete ) {
		$this->migration_status['complete'] = $complete;

		update_option( 'rtec_migration_status', $this->migration_status );
	}

	public function update_one_migration_done( $one_migration_done ) {
		$this->migration_status['one_migration_done'] = $one_migration_done;

		update_option( 'rtec_migration_status', $this->migration_status );
	}

	public function update_missed_ids( $ids ) {
		if ( empty( $ids ) ) {
			delete_option( 'rtec_migration_missed' );
		} else {
			$this->missed_ids = $ids;
			update_option( 'rtec_migration_missed', $ids );
		}
	}

	public function get_missed_ids() {
		return $this->missed_ids;
	}

	public function unpause() {
		if ( isset( $this->migration_status['paused'] ) ) {
			unset( $this->migration_status['paused'] );
			update_option( 'rtec_migration_status', $this->migration_status );
		}
	}

	public function pause() {
		$this->migration_status = array(
			'paused'             => true,
			'attempts'           => 0,
			'complete'           => false,
			'one_migration_done' => false,
			'secondary_check'    => 'pending',
		);

		update_option( 'rtec_migration_status', $this->migration_status );
	}

	public function migration_needed() {
		if ( ! rtec_doing_series() ) {
			return false;
		}

		if ( $this->is_complete() ) {
			return false;
		}

		return true;
	}

	public function maybe_migrate() {
		if ( ! $this->migration_needed() ) {
			return;
		}

		if ( $this->is_complete() || $this->is_paused() ) {
			return;
		}

		if ( ! empty( $_GET['rtec_safe'] ) ) {
			return;
		}

		$this->do_migration_batch();
	}

	public function migration_needed_alert() {
		if ( ! $this->migration_needed() && ! $this->is_paused() ) {
			return;
		}

		if ( ! empty( $_GET['tab'] ) && $_GET['tab'] === 'migration' ) {
			return;
		}
		?>
		<div class="rtec-notice rtec-top-notice rtec-box-shadow">
			<?php printf( __( 'Migration needed for some registrations! No action needed as this is being done automatically. Visit %1$sthis page%2$s if you would like to review progress.', 'registrations-for-the-events-calendar' ), '<a href="?page=rtec-support&tab=migration">', '</a>' ); ?>
		</div>
		<?php
	}

	public function get_migration_needed_events() {
		$post_type = defined( 'Tribe__Events__Main::POSTTYPE' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
		$args      = array(
			'post_type'      => $post_type,
			'posts_per_page' => 50,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_tec_ct1_current_migration_phase',
					'value'   => 'MIGRATION_SUCCESS',
					'compare' => '=',
				),
				array(
					'key'     => '_RTEC_Migration_Complete',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$current = get_posts( $args );

		wp_reset_postdata();

		return $current;
	}

	public function get_abandoned_recurrences( $parent_post ) {
		$post_type = defined( 'Tribe__Events__Main::POSTTYPE' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
		$args      = array(
			'post_type'      => $post_type,
			'posts_per_page' => 50,
			'post_parent'    => $parent_post,
			'meta_query'     => array(
				array(
					'key'     => '_RTEC_Migration_Complete',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$current = get_posts( $args );

		wp_reset_postdata();

		return $current;
	}

	public function get_missed_event_ids() {
		$migration_start_date = get_option( 'rtec_migration_date', false );

		if ( empty( $migration_start_date ) ) {
			return array();
		}
		$migration_start_date = date( 'Y-m-d H:i:s', strtotime( $migration_start_date ) );
		global $wpdb;

		$results        = array();
		$results_failed = array();
		if ( defined( 'RTEC_TABLENAME_ENTRIES' ) ) {
			$entries_table_name = $wpdb->prefix . RTEC_TABLENAME_ENTRIES;

			$results = $wpdb->get_col(
				"
				SELECT entries.event_id FROM $entries_table_name as entries
				LEFT JOIN $wpdb->postmeta as meta on entries.event_id = meta.post_id
				WHERE NOT EXISTS (
				  SELECT * FROM $wpdb->postmeta as meta2
				   WHERE meta2.meta_key = '_RTEC_Migration_Complete'
					AND meta2.post_id = entries.event_id
				)
				AND entries.event_id < 10000000
				AND entries.registration_date < '$migration_start_date'
				GROUP BY entries.event_id
			"
			);

			$results_failed = $wpdb->get_col(
				"
				SELECT entries.event_id FROM $entries_table_name as entries
				LEFT JOIN $wpdb->postmeta as meta on entries.event_id = meta.post_id
				WHERE meta.meta_key = '_RTEC_Migration_Failed'
				AND meta.post_id = entries.event_id
				AND entries.event_id < 10000000
				AND entries.registration_date < '$migration_start_date'
				GROUP BY entries.event_id
			"
			);
		}

		$results_backwards = array();
		if ( defined( 'RTEC_TABLENAME' ) ) {
			$entries_table_name = $wpdb->prefix . RTEC_TABLENAME;

			$results_backwards = $wpdb->get_col(
				"
				SELECT entries.event_id FROM $entries_table_name as entries
				LEFT JOIN $wpdb->postmeta as meta on entries.event_id = meta.post_id
				WHERE NOT EXISTS (
				  SELECT * FROM $wpdb->postmeta as meta2
				   WHERE meta2.meta_key = '_RTEC_Migration_Complete'
					AND meta2.post_id = entries.event_id
				)
				AND entries.event_id < 10000000
				AND entries.registration_date < '$migration_start_date'
				GROUP BY entries.event_id
			"
			);
			$results_failed    = $wpdb->get_col(
				"
				SELECT entries.event_id FROM $entries_table_name as entries
				LEFT JOIN $wpdb->postmeta as meta on entries.event_id = meta.post_id
				WHERE meta.meta_key = '_RTEC_Migration_Failed'
				AND entries.event_id < 10000000
				AND entries.registration_date < '$migration_start_date'
				GROUP BY entries.event_id
			"
			);
		}

		return array_merge( $results, $results_backwards, $results_failed );
	}

	public function get_migration_data( $abandoned_recurrence_event_id, $series_id = 0, $bypass_series = false ) {
		$migration_data = array(
			'from' => $abandoned_recurrence_event_id,
			'to'   => false,
		);

		global $wpdb;

		$occurrences_table              = $wpdb->prefix . 'tec_occurrences';
		$tec_series_relationships_table = $wpdb->prefix . 'tec_series_relationships';
		$abandoned_event_start_date     = get_post_meta( $abandoned_recurrence_event_id, '_EventStartDateUTC', true );

		if ( $bypass_series ) {
			$found_event_migration_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $occurrences_table as o
					LEFT JOIN $tec_series_relationships_table as s ON s.event_post_id = o.post_id
					WHERE o.start_date_utc = %s",
					$abandoned_event_start_date
				),
				ARRAY_A
			);

			if ( ! empty( $found_event_migration_data ) && (int) $found_event_migration_data[0]['has_recurrence'] === 1 ) {
				$found_event_migration_data = $this->best_found( $abandoned_recurrence_event_id, $found_event_migration_data );
				$migration_data['to']       = 10000000 + (int) $found_event_migration_data[0]['occurrence_id'];
			} elseif ( ! empty( $found_event_migration_data ) ) {
				$found_event_migration_data = $this->best_found( $abandoned_recurrence_event_id, $found_event_migration_data );
				$migration_data['to']       = (int) $found_event_migration_data[0]['post_id'];
			}
		} elseif ( empty( $series_id ) ) {
			$found_event_migration_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $occurrences_table as o
					LEFT JOIN $tec_series_relationships_table as s ON s.event_post_id = o.post_id
					WHERE o.start_date_utc = %s
					AND o.has_recurrence = 0",
					$abandoned_event_start_date
				),
				ARRAY_A
			);

			if ( ! empty( $found_event_migration_data ) ) {
				$found_event_migration_data = $this->best_found( $abandoned_recurrence_event_id, $found_event_migration_data );
				$migration_data['to']       = (int) $found_event_migration_data[0]['post_id'];
			}
		} else {
			$found_event_migration_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $occurrences_table as o
					LEFT JOIN $tec_series_relationships_table as s ON s.event_post_id = o.post_id
					WHERE o.start_date_utc = %s
					AND s.series_post_id = %d",
					$abandoned_event_start_date,
					$series_id
				),
				ARRAY_A
			);

			if ( ! empty( $found_event_migration_data ) ) {
				if ( ! empty( $found_event_migration_data ) && (int) $found_event_migration_data[0]['has_recurrence'] === 1 ) {
					$found_event_migration_data = $this->best_found( $abandoned_recurrence_event_id, $found_event_migration_data );
					$migration_data['to']       = 10000000 + (int) $found_event_migration_data[0]['occurrence_id'];
				} elseif ( ! empty( $found_event_migration_data ) ) {
					$found_event_migration_data = $this->best_found( $abandoned_recurrence_event_id, $found_event_migration_data );
					$migration_data['to']       = (int) $found_event_migration_data[0]['post_id'];
				}
			}
		}

		return $migration_data;
	}

	public function best_found( $original, $found ) {
		if ( empty( $found ) || count( $found ) === 1 ) {
			return $found;
		}

		foreach ( $found as $single_found ) {
			if ( (int) $single_found['post_id'] === (int) $original
				&& (int) $single_found['has_recurrence'] !== 1 ) {
				return array( $single_found );
			}
		}

		foreach ( $found as $single_found ) {
			if ( ! $this->event_migration_complete( $single_found['post_id'] ) ) {
				return array( $single_found );
			}
		}

		foreach ( $found as $single_found ) {
			if ( (int) $single_found['has_recurrence'] === 1 ) {
				return array( $single_found );
			}
		}

		return $found[0];
	}

	public function do_migration_batch() {
		$this->update_migrations_attempts( 1 );
		$num_migrations         = 0;
		$still_doing_migrations = true;
		$migration_start_date   = get_option( 'rtec_migration_date', false );
		if ( empty( $migration_start_date ) ) {
			update_option( 'rtec_migration_date', date( 'Y-m-d H:i:s' ), false );
		}
		while ( $still_doing_migrations && $num_migrations < self::MAX_BATCH ) {
			$migrate_needed_events = $this->get_migration_needed_events();

			if ( empty( $migrate_needed_events ) ) {
				$still_doing_migrations = false;

				if ( $this->migration_status['secondary_check'] === 'pending' ) {
					$missing_ids      = $this->get_missed_event_ids();
					$missing_ids_live = $this->filter_existing_posts( $missing_ids );
					if ( ! empty( $missing_ids_live ) ) {
						$this->update_missed_ids( $missing_ids_live );
						$this->migration_status['secondary_check'] = 'processing';
						$this->update_migrations_complete( false );
					} else {
						$this->migration_status['secondary_check'] = 'complete';
						$this->update_migrations_complete( false );
					}
				} elseif ( $this->migration_status['secondary_check'] !== 'complete' ) {
					$this->process_missed_ids();
					if ( empty( $this->missed_ids ) ) {
						$this->migration_status['secondary_check'] = 'complete';
						$this->update_migrations_complete( true );
					}
				} else {
					$this->update_migrations_complete( true );
				}
			}
			foreach ( $migrate_needed_events as $migrate_needed_event ) {
				if ( $num_migrations >= self::MAX_BATCH ) {
					break;
				}
				$migrate_needed_event_id = $migrate_needed_event->ID;
				$now_in_series           = rtec_maybe_series_id_for_event( $migrate_needed_event_id );
				$post_meta               = get_post_meta( $migrate_needed_event_id );
				$need_to_connect_series  = false;
				if ( ! empty( $now_in_series ) &&
					! empty( $post_meta['_RTECconnectedEvent'] )
					&& is_array( $post_meta['_RTECconnectedEvent'] )
					&& in_array( 'recurrences', $post_meta['_RTECconnectedEvent'] ) ) {
					$need_to_connect_series = true;
				}
				if ( $need_to_connect_series ) {
					update_post_meta( $migrate_needed_event_id, '_RTECSeriesRegistrationType', 'once' );
					update_post_meta( $now_in_series, '_RTECSeriesRegistrationType', 'once' );
				}

				$migrate_needed_event_to_migrate = $this->get_migration_data( $migrate_needed_event_id, $now_in_series );

				$this->do_migration( $migrate_needed_event_to_migrate['from'], $migrate_needed_event_to_migrate['to'] );
				$this->update_one_migration_done( true );

				$abandoned_recurrences = $this->get_abandoned_recurrences( $migrate_needed_event_id );
				if ( ! empty( $abandoned_recurrences ) ) {
					foreach ( $abandoned_recurrences as $abandoned_recurrence ) {
						if ( $num_migrations >= self::MAX_BATCH ) {
							break;
						}
						$recurrence_id = $abandoned_recurrence->ID;

						$to_migrate = $this->get_migration_data( $recurrence_id, $now_in_series );

						$this->do_migration( $to_migrate['from'], $to_migrate['to'] );
						$this->update_one_migration_done( true );
						++$num_migrations;
					}
				} else {
					++$num_migrations;
					update_post_meta( $migrate_needed_event_id, '_RTEC_Migration_Complete', 'yes' );
				}
			}
		}
	}

	public function filter_existing_posts( $posts ) {
		$post_type = defined( 'Tribe__Events__Main::POSTTYPE' ) ? Tribe__Events__Main::POSTTYPE : 'tribe_events';
		$args      = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 150,
			'post__in'       => $posts,
		);

		$current = get_posts( $args );

		$existing = array();
		foreach ( $current as $post ) {
			$existing[] = $post->ID;
		}

		wp_reset_postdata();

		return $existing;
	}

	public function process_missed_ids() {
		$num_migrations = 0;
		$missed_ids     = $this->get_missed_ids();
		foreach ( $missed_ids as $migrate_needed_event_id ) {
			if ( $num_migrations >= self::MAX_BATCH ) {
				break;
			}
			++$num_migrations;
			$now_in_series                   = rtec_maybe_series_id_for_event( $migrate_needed_event_id );
			$migrate_needed_event_to_migrate = $this->get_migration_data( $migrate_needed_event_id, $now_in_series, true );

			$this->do_migration( $migrate_needed_event_to_migrate['from'], $migrate_needed_event_to_migrate['to'] );
			$this->update_one_migration_done( true );

			if ( ( $key = array_search( $migrate_needed_event_id, $missed_ids ) ) !== false ) {
				unset( $missed_ids[ $key ] );
			}
		}

		$this->update_missed_ids( $missed_ids );
	}

	public function do_migration( $from, $to ) {
		if ( $this->event_migration_complete( $from ) ) {
			return;
		}
		if ( ! empty( $to ) ) {
			update_post_meta( $from, '_RTEC_Migration_Complete', 'yes' );
			if ( (int) $from !== (int) $to ) {
				update_post_meta( $from, '_RTEC_Migration_migrated_to', $to );
				update_post_meta( $to, '_RTEC_Migration_backwards_compat_event', $from );
			}
		} else {
			update_post_meta( $from, '_RTEC_Migration_Failed', 'yes' );
		}

		$post_meta = get_post_meta( $from );

		if ( empty( $post_meta['_RTECregistrationsDisabled'] ) ) {
			$default_disabled = isset( $rtec_options['disable_by_default'] ) ? $rtec_options['disable_by_default'] : false;
			$value            = 0;
			if ( $default_disabled ) {
				$value = 1;
			}
			update_post_meta( $to, '_RTECregistrationsDisabled', $value );
			$context_id = rtec_maybe_wp_post_id_for_event( $to );
			if ( $context_id ) {
				update_post_meta( $context_id, '_RTECregistrationsDisabled', $value );
			}
		}
	}

	public function event_migration_complete( $event_id ) {
		$cache_key         = 'rtec_event_migration_complete_' . $event_id;
		$maybe_alias_cache = wp_cache_get( $cache_key );
		if ( false === $maybe_alias_cache ) {

			global $wpdb;
			$table_name  = $wpdb->postmeta;
			$result      = $wpdb->get_results(
				$wpdb->prepare(
					"
			SELECT meta_value
			FROM $table_name
			WHERE meta_key = '_RTEC_Migration_Complete'
			AND post_id = %d
			LIMIT 1;
			",
					$event_id
				),
				ARRAY_A
			);
			$maybe_alias = ! empty( $result[0] ) ? $result[0]['meta_value'] : 'none';

			wp_cache_set( $cache_key, $maybe_alias );

			if ( $maybe_alias !== 'none' ) {
				return $maybe_alias;
			}

			return false;
		}

		if ( $maybe_alias_cache !== 'none' ) {
			return $maybe_alias_cache;
		}

		return false;
	}

	public function reset() {
		global $wpdb;

		$table_name = $wpdb->postmeta;
		$result     = $wpdb->query(
			"
			DELETE
			FROM $table_name
			WHERE `meta_key` LIKE ('%_RTEC_Migration%')
			"
		);

		delete_option( 'rtec_migration_status' );

		$this->migration_status = array(
			'attempts'           => 0,
			'complete'           => false,
			'one_migration_done' => false,
			'secondary_check'    => 'pending',
		);
	}

	public function ajax_undo_migration() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->reset();

		delete_option( 'rtec_migration_date' );
	}
}
