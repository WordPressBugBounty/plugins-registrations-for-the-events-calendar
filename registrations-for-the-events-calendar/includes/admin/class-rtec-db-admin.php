<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Class RTEC_Db_Admin
 *
 * Contains methods that just apply to the admin area
 *
 * @since 1.0
 */
class RTEC_Db_Admin extends RTEC_Db {

	/**
	 * Used to create the registrations table on activation
	 *
	 * @since 1.0
	 * @since 1.4   added indices for event_id and status
	 */
	public static function create_table() {
		global $wpdb;

			$table_name      = $wpdb->prefix . RTEC_TABLENAME;
			$charset_collate = $wpdb->get_charset_collate();

		if ( $wpdb->get_var( "show tables like '$table_name'" ) != $table_name ) {
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
                INDEX event_id (event_id),
                INDEX status (status),
                UNIQUE KEY id (id)
            ) $charset_collate;";
			$wpdb->query( $sql );

			add_option( 'rtec_db_version', RTEC_DBVERSION );

		}

		$db = new RTEC_Db_Admin();
		$db->maybe_add_index( 'event_id', 'event_id' );
		$db->maybe_add_index( 'status', 'status' );
	}

	/**
	 * Used to make changes to existing registrations
	 *
	 * @param $data array           information to be updated
	 * @param $custom_data array    custom data to be updated
	 * @since 1.0
	 */
	public function update_entry( $data, $entry_id = '', $field_atts = array() ) {
		global $wpdb;

		$set_string = '';

		foreach ( $data as $key => $value ) {

			if ( $key !== 'event_id' && $key !== 'id' ) {

				// Map form keys to DB column names (table has first_name, last_name).
				$column = $key;
				if ( $key === 'first' ) {
					$column = 'first_name';
				} elseif ( $key === 'last' ) {
					$column = 'last_name';
				}

				if ( $key !== 'custom' ) {
					$value_str = is_scalar( $value ) ? (string) $value : '';
					$set_string .= esc_sql( $column ) . "='" . esc_sql( str_replace( "'", '`', $value_str ) ) . "', ";
				} else {
					$custom = $this->get_custom_data( $entry_id );

					$custom = $this->update_custom_data_for_db( $custom, $data['custom'], $field_atts );

					$set_string .= "custom='" . esc_sql( $custom ) . "', ";
				}
			}
		}

		$set_string     = substr( $set_string, 0, -2 );
		$esc_table_name = esc_sql( $this->table_name );

		$int_entry_id = (int) $entry_id;

		if ( ! empty( $entry_id ) ) {
			$sql = "UPDATE $esc_table_name
            SET $set_string
            WHERE id=$int_entry_id";
			$wpdb->query(
				"UPDATE $esc_table_name
            SET $set_string
            WHERE id=$int_entry_id"
			);
		}
	}

	public function get_custom_data( $id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT custom FROM $this->table_name
                WHERE id=%d",
				$id
			),
			ARRAY_A
		);

		return maybe_unserialize( $results[0]['custom'] );
	}

	/**
	 * Updates a custom field in the database with serialization
	 *
	 * @param $db_custom
	 * @param $new_custom
	 * @param $field_atts
	 *
	 * @return mixed
	 * @since 2.0
	 */
	public function update_custom_data_for_db( $db_custom, $new_custom, $field_atts ) {
		if ( ! empty( $new_custom ) ) {
			foreach ( $new_custom as $key => $value ) {
				$db_custom[ $key ] = array(
					'value' => $value,
					'label' => $field_atts[ $key ]['label'],
				);
			}
		}

		return maybe_serialize( $db_custom );
	}

	/**
	 * Removes a set of records from the dashboard
	 *
	 * @param $records array    ids or email of records to remove
	 *
	 * @return bool
	 * @since 1.0
	 */
	public function remove_records( $records ) {
		global $wpdb;

		$record_ids = is_array( $records ) ? $records : array( $records );
		$record_ids = array_filter( array_map( 'absint', $record_ids ) );

		if ( empty( $record_ids ) ) {
			return false;
		}

		$table_name   = esc_sql( $this->table_name );
		$placeholders = implode( ', ', array_fill( 0, count( $record_ids ), '%d' ) );
		$sql          = $wpdb->prepare( "DELETE FROM $table_name WHERE id IN ( $placeholders )", $record_ids );

		return false !== $wpdb->query( $sql );
	}

	/**
	 * Keep only registration IDs the current user is allowed to manage.
	 *
	 * @param int[] $record_ids Registration row IDs.
	 * @return int[]
	 */
	public function filter_manageable_registration_ids( $record_ids ) {
		global $wpdb;

		$record_ids = array_values( array_filter( array_map( 'absint', (array) $record_ids ) ) );
		if ( empty( $record_ids ) ) {
			return array();
		}

		$table_name   = esc_sql( $this->table_name );
		$placeholders = implode( ', ', array_fill( 0, count( $record_ids ), '%d' ) );
		$sql          = $wpdb->prepare(
			"SELECT id, event_id FROM $table_name WHERE id IN ( $placeholders )",
			$record_ids
		);
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( empty( $rows ) ) {
			return array();
		}

		$allowed = array();
		foreach ( $rows as $row ) {
			if ( rtec_current_user_can_manage_event_registrations( (int) $row['event_id'] ) ) {
				$allowed[] = (int) $row['id'];
			}
		}

		return $allowed;
	}

	/**
	 * Used to create the alert for new registrations
	 *
	 * @return false|int    false if no records, otherwise number of new registrations
	 * @since 1.0
	 */
	public function check_for_new() {
		global $wpdb;

		$new = 'n';

		return $wpdb->query(
			$wpdb->prepare(
				"SELECT status
        FROM $this->table_name WHERE status=%s",
				$new
			)
		);
	}

	/**
	 * Count registrations created after a given timestamp (for "new since last view").
	 * registration_date is compared in site local time.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return int Number of registrations.
	 * @since 1.0
	 */
	public function count_registrations_after( $timestamp ) {
		global $wpdb;

		if ( empty( $timestamp ) || ! is_numeric( $timestamp ) ) {
			return 0;
		}

		$threshold = gmdate( 'Y-m-d H:i:s', (int) $timestamp );

		$count     = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $this->table_name WHERE registration_date > %s",
				$threshold
			)
		);

		return $count;
	}

	/**
	 * Get a hard count of the number of registrations currently
	 * in the database for the give id
	 *
	 * @param $id int   post ID for the event
	 *
	 * @return int      number registered
	 * @since 1.0
	 */
	public function get_registration_count( $event_id, $form_id = 1 ) {
		global $wpdb;

		$event_ids = rtec_all_event_aliases( $event_id );

		if ( count( $event_ids ) === 1 ) {
			$num_registered = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT event_id, COUNT(*) AS num_registered
            FROM $this->table_name WHERE event_id = %d",
					$event_ids[0]
				),
				ARRAY_A
			);
		} else {
			$ids_string = implode( ', ', array_map( 'absint', $event_ids ) );

			$num_registered = $wpdb->get_results(
				"SELECT event_id, COUNT(*) AS num_registered
            FROM $this->table_name WHERE event_id IN ($ids_string)",
				ARRAY_A
			);
		}

		$count = isset( $num_registered[0] ) ? $num_registered[0]['num_registered'] : 0;

		$count = ! is_null( $count ) ? $count : 0;

		return $count;
	}

	/**
	 * Manually set the number of registrations
	 *
	 * @param $id int   post ID
	 * @param $num int  new number to set the post meta as
	 * @since 1.0
	 */
	public function set_num_registered_meta( $id, $num ) {
		update_post_meta( $id, '_RTECnumRegistered', (int) $num );
	}

	/**
	 * Gets all of the post IDs with the Tribe Events post type
	 *
	 * @return array    the ids
	 * @since 1.0
	 */
	public function get_event_post_ids() {
		global $wpdb;

		$query     = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", RTEC_TRIBE_EVENTS_POST_TYPE );
		$event_ids = $wpdb->get_col( $query );

		return $event_ids;
	}

	/**
	 * Get search results from registrations table
	 *
	 * @param $term     string
	 * @param $columns  string
	 *
	 * @return array|mixed|null|object
	 * @since 2.0
	 */
	public function get_matches( $term, $columns ) {
		global $wpdb;

		$allowed_columns = array( 'first_name', 'last_name', 'email', 'phone' );
		$columns         = is_array( $columns ) ? $columns : array( $columns );
		$columns         = array_values( array_intersect( $columns, $allowed_columns ) );

		if ( empty( $columns ) ) {
			return array();
		}

		$term_like       = '%' . $wpdb->esc_like( $term ) . '%';
		$where_fragments = array();
		$placeholders    = array();

		foreach ( $columns as $column ) {
			$where_fragments[] = $column . ' LIKE %s';
			$placeholders[]    = $term_like;
		}

		$where_clause = implode( ' OR ', $where_fragments );
		$sql          = "SELECT * FROM $this->table_name WHERE $where_clause";
		$query_args   = $placeholders;

		$manageable_event_ids = rtec_get_manageable_event_ids_for_current_user();
		if ( is_array( $manageable_event_ids ) ) {
			if ( empty( $manageable_event_ids ) ) {
				return array();
			}
			$event_placeholders = implode( ', ', array_fill( 0, count( $manageable_event_ids ), '%d' ) );
			$sql               .= " AND event_id IN ( $event_placeholders )";
			$query_args         = array_merge( $query_args, array_map( 'absint', $manageable_event_ids ) );
		}

		$sql     .= ' ORDER BY id DESC LIMIT 200';
		$query    = $wpdb->prepare( $sql, $query_args );
		$matches  = $wpdb->get_results( $query, ARRAY_A );

		return $matches;
	}

	/**
	 * Used to update the database to accommodate new columns added since release
	 *
	 * @param $column string    name of column to add if it doesn't exist
	 * @since 1.1
	 */
	public function maybe_add_column_to_table( $column, $type = 'VARCHAR(40)' ) {
		global $wpdb;

		$table_name  = esc_sql( $this->table_name );
		$column_name = esc_sql( $column );
		$type_name   = esc_sql( $type );

		$results = $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = '$column_name'" );

		if ( $results == 0 ) {
			$wpdb->query( "ALTER TABLE $table_name ADD $column_name $type_name DEFAULT '' NOT NULL" );
		}
	}

	/**
	 * Used to update the database to accommodate new columns added since release
	 *
	 * @param $column string    name of column to add if it doesn't exist
	 * @since 1.1
	 */
	public function maybe_add_column_to_table_no_string( $column, $type = 'INT(11)' ) {
		global $wpdb;

		$table_name  = esc_sql( $this->table_name );
		$column_name = esc_sql( $column );
		$type_name   = esc_sql( $type );

		$results = $wpdb->query( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = '$column_name'" );

		if ( $results == 0 ) {
			$wpdb->query( "ALTER TABLE $table_name ADD $column_name $type_name DEFAULT 0 NOT NULL" );
		}
	}

	/**
	 * Used to add indices to registrations table
	 *
	 * @param $index string    name of index to add if it doesn't exist
	 * @param $column string        name of column to add index to
	 * @since 1.3
	 */
	public function maybe_add_index( $index, $column ) {
		global $wpdb;

		$table_name  = esc_sql( $this->table_name );
		$column_name = esc_sql( $column );
		$index_name  = esc_sql( $index );

		$results = $wpdb->get_results(
			"SELECT COUNT(1) indexExists FROM INFORMATION_SCHEMA.STATISTICS
			WHERE table_schema=DATABASE() AND table_name = '$table_name' AND index_name = '$index_name'"
		);

		if ( $results[0]->indexExists == '0' ) {
			$wpdb->query( "ALTER TABLE $table_name ADD INDEX $index_name ($column_name)" );
		}
	}

	/**
	 * Used to add indices to registrations table
	 *
	 * @param $edit string    name of index to add if it doesn't exist
	 * @param $column string        name of column to add index to
	 * @since 1.3
	 */
	public function maybe_update_column( $edit, $column ) {
		global $wpdb;

		$table_name  = esc_sql( $this->table_name );
		$column_name = esc_sql( $column );
		$edit        = esc_sql( $edit );

		$results = $wpdb->query( "ALTER TABLE $table_name MODIFY $column_name $edit" );
	}

	/**
	 * @since 2.3
	 */
	public function get_event_ids( $args, $arrange = 'DESC' ) {
		global $wpdb;

		$where_clause = $this->build_escaped_where_clause( $args['where'] );
		$results      = $wpdb->get_col( "SELECT event_id FROM $this->table_name WHERE $where_clause;" );

		return $results;
	}

	/**
	 * Get the most recent registrations across all events (All Registrations tab).
	 * Supports search (name, email, phone), status filter, and pagination.
	 *
	 * @param array|int $args Array with 'search', 'registration_status', 'limit', 'offset'; or int limit (back compat).
	 * @return array { 'registrations' => array, 'total' => int }
	 * @since 1.0
	 */
	public function get_latest_registrations( $args = array() ) {
		global $wpdb;

		if ( is_numeric( $args ) ) {
			$args = array( 'limit' => (int) $args );
		}
		$args = wp_parse_args( $args, array(
			'search'             => '',
			'registration_status' => 'active',
			'limit'              => 20,
			'offset'             => 0,
		) );

		$limit  = max( 1, min( 500, (int) $args['limit'] ) );
		$offset = max( 0, (int) $args['offset'] );

		$table_name = $wpdb->prefix . RTEC_TABLENAME;

		$current_user = wp_get_current_user();
		$current_user_is_author = in_array( 'author', (array) $current_user->roles, true );
		$author_sql = '';
		if ( $current_user_is_author && ! empty( $current_user->ID ) ) {
			$author_posts = new WP_Query( array(
				'author'    => $current_user->ID,
				'fields'    => 'ids',
				'post_type' => 'tribe_events',
			) );
			$author_event_ids = ! empty( $author_posts->posts ) ? implode( ',', array_map( 'absint', $author_posts->posts ) ) : '';
			if ( $author_event_ids === '' ) {
				return array( 'registrations' => array(), 'total' => 0 );
			}
			$author_sql = " AND event_id IN($author_event_ids)";
		}

		$status_sql = '';
		if ( $args['registration_status'] !== '' && $args['registration_status'] !== 'active' ) {
			$status_char = 'c';
			switch ( $args['registration_status'] ) {
				case 'confirmed':
					$status_char = 'c';
					break;
				case 'pending':
					$status_char = 'p';
					break;
				case 'waiting':
					$status_char = 'w';
					break;
				case 'unregistered':
					$status_char = 'x';
					break;
				case 'noshow':
					$status_char = 'o';
					break;
				default:
					$status_char = 'c';
			}
			$status_sql = $wpdb->prepare( ' AND status = %s', $status_char );
		} else {
			$status_sql = " AND status != 'x'";
		}

		$search_sql = '';
		if ( $args['search'] !== '' ) {
			$term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$search_sql = $wpdb->prepare(
				" AND ( first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s )",
				$term,
				$term,
				$term,
				$term
			);
		}

		$where = "1=1" . $author_sql . $status_sql . $search_sql;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE $where" );

		$registrations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE $where ORDER BY registration_date DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);

		return array(
			'registrations' => $registrations ? $registrations : array(),
			'total'         => $total,
		);
	}
}
