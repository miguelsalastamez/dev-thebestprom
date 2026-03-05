<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();


class Files {

	public static function get_table() {
		global $wpdb;

		return $wpdb->prefix . 'integrate_google_drive_files';
	}

	public static function get( $parent_id, $account_id, $start_index, $limit = '', $filters = [], $sort = [ 'sortBy' => 'name', 'sortDirection' => 'asc' ] ) {
		global $wpdb;

		$table = self::get_table();
		$where = [];

		// --- Parent folder condition ---
		switch ( $parent_id ) {
			case 'computers':
				$where['is_computers'] = 1;
				break;
			case 'shared-drives':
				$where['is_shared_drive'] = 1;
				break;
			case 'shared':
				$where['is_shared_with_me'] = 1;
				break;
			case 'starred':
				$where['is_starred'] = 1;
				break;
			default:
				$where['parent_id'] = $parent_id;
				break;
		}

		$where['account_id'] = $account_id;

		// --- WHERE clause ---
		$where_placeholders = '';
		$where_values       = [];

		foreach ( $where as $key => $value ) {
			$where_placeholders .= " AND $key = %s";
			$where_values[]     = $value;
		}

		// --- Filters ---
		if ( igd_should_filter_files( $filters ) ) {

			// Show/Hide files and folders
			if ( isset( $filters['showFiles'] ) && empty( $filters['showFiles'] ) ) {
				$where_placeholders .= " AND type = 'application/vnd.google-apps.folder'";
			}
			if ( isset( $filters['showFolders'] ) && empty( $filters['showFolders'] ) ) {
				$where_placeholders .= " AND type <> 'application/vnd.google-apps.folder'";
			}

			// Handle extension filters
			if ( ! empty( $filters['allowAllExtensions'] ) ) {
				if ( ! empty( $filters['allowExceptExtensions'] ) ) {
					$extensions         = array_map( 'trim', explode( ',', $filters['allowExceptExtensions'] ) );
					$where_placeholders .= ' AND (extension NOT IN (' . implode( ', ', array_fill( 0, count( $extensions ), '%s' ) ) . ") OR type = 'application/vnd.google-apps.folder')";
					$where_values       = array_merge( $where_values, $extensions );
				}
			} elseif ( ! empty( $filters['allowExtensions'] ) ) {
				$extensions         = array_map( 'trim', explode( ',', $filters['allowExtensions'] ) );
				$where_placeholders .= ' AND (extension IN (' . implode( ', ', array_fill( 0, count( $extensions ), '%s' ) ) . ") OR type = 'application/vnd.google-apps.folder')";
				$where_values       = array_merge( $where_values, $extensions );
			}

			// Handle name filters
			$nameFilterOptions = $filters['nameFilterOptions'] ?? [];
			if ( in_array( 'files', $nameFilterOptions, true ) || in_array( 'folders', $nameFilterOptions, true ) ) {
				$name_field = 'name';
				$like_op    = ! empty( $filters['allowAllNames'] ) ? 'NOT LIKE' : 'LIKE';
				$list_key   = ! empty( $filters['allowAllNames'] ) ? 'allowExceptNames' : 'allowNames';
				$names      = ! empty( $filters[ $list_key ] ) ? array_map( 'trim', explode( ',', $filters[ $list_key ] ) ) : [];

				foreach ( $names as $name ) {
					$name = str_replace( '*', '%', $name );
					$name = str_replace( '?', '_', $name );

					if ( in_array( 'files', $nameFilterOptions, true ) && ! in_array( 'folders', $nameFilterOptions, true ) ) {
						$where_placeholders .= " AND (type = 'application/vnd.google-apps.folder' OR $name_field $like_op %s)";
					} elseif ( in_array( 'folders', $nameFilterOptions, true ) && ! in_array( 'files', $nameFilterOptions, true ) ) {
						$where_placeholders .= " AND (type <> 'application/vnd.google-apps.folder' OR $name_field $like_op %s)";
					} else {
						$where_placeholders .= " AND $name_field $like_op %s";
					}

					$where_values[] = $name;
				}
			}

			// Handle gallery/media/video/folder filters
			if ( ! empty( $filters['isGallery'] ) ) {
				$where_placeholders .= " AND (type LIKE 'image/%' OR type LIKE 'video/%' OR type = 'application/vnd.google-apps.folder')";
			}
			if ( ! empty( $filters['isMedia'] ) ) {
				$where_placeholders .= " AND (type LIKE 'audio/%' OR type LIKE 'video/%' OR type = 'application/vnd.google-apps.folder')";
			}
			if ( ! empty( $filters['onlyVideo'] ) ) {
				$where_placeholders .= " AND (type LIKE 'video/%' OR type = 'application/vnd.google-apps.folder')";
			}
			if ( ! empty( $filters['onlyFolders'] ) ) {
				$where_placeholders .= " AND type = 'application/vnd.google-apps.folder'";
			}

			// Handle Tables
			if ( ! empty( $filters['onlyTables'] ) ) {
				$table_types = [
					'application/vnd.google-apps.spreadsheet',
					'application/vnd.oasis.opendocument.spreadsheet',
					'application/vnd.ms-excel',
					'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
				];

				$where_placeholders .= " AND (type IN (" . implode( ', ', array_fill( 0, count( $table_types ), '%s' ) ) . ") OR type = 'application/vnd.google-apps.folder' )";
				$where_values       = array_merge( $where_values, $table_types );
			}

		}

		// --- Get total count ---
		$count_sql = "SELECT COUNT(*) FROM `$table` WHERE 1 $where_placeholders";
		$count     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $where_values ) );

		// --- Fetch all filtered rows (folders first) ---
		$sql = $wpdb->prepare(
			"SELECT data FROM `$table` WHERE 1 $where_placeholders ORDER BY (type = 'application/vnd.google-apps.folder') DESC",
			$where_values
		);

		$items = $wpdb->get_results( $sql, ARRAY_A );

		// --- Build file array ---
		$files = [];
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$data = maybe_unserialize( $item['data'] );
				if ( ! empty( $data ) && is_array( $data ) ) {
					$files[] = $data;
				}
			}
		}

		// No files found
		if ( empty( $files ) ) {
			return [ [], 0 ];
		}

		// --- Sort using PHP function for identical logic ---
		$files = igd_sort_files( $files, $sort );

		// --- Apply pagination AFTER sorting ---
		$start_index = (int) $start_index;
		if ( ! empty( $limit ) ) {
			$limit = (int) $limit;
			$files = array_slice( $files, $start_index, $limit );
		} else {
			$files = array_slice( $files, $start_index );
		}

		return [ array_values( $files ), $count ];
	}

	/**
	 * Set files
	 *
	 * @param $files
	 * @param $folder_id
	 *
	 * @return void
	 */
	public static function set( $files, $folder_id = '' ) {

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				self::add_file( $file, $folder_id );
			}
		}
	}

	/**
	 * Get cached file by ID
	 *
	 * @param $id
	 *
	 * @return false|mixed
	 */
	public static function get_file_by_id( $id ) {
		global $wpdb;

		$table = self::get_table();

		$sql  = $wpdb->prepare( "SELECT data FROM `$table` WHERE id = %s", $id );
		$item = $wpdb->get_row( $sql, ARRAY_A );

		return ! empty( $item['data'] ) ? unserialize( $item['data'] ) : false;
	}

	public static function get_file_by_name( $name, $folder_id = '' ) {
		global $wpdb;

		$table = self::get_table();

		$sql = $wpdb->prepare( "SELECT data FROM `$table` WHERE name = %s AND parent_id = %s", $name, $folder_id );

		$item = $wpdb->get_row( $sql, ARRAY_A );

		return ! empty( $item['data'] ) ? unserialize( $item['data'] ) : false;
	}

	/**
	 * @param $file
	 * @param $folder
	 *
	 * @return void
	 */
	public static function add_file( $file, $folder_id = '' ) {

		if ( $folder_id && ! empty( $file['parents'] ) && $folder_id != $file['parents'][0] ) {
			$folder_id = $file['parents'][0];
		} elseif ( ! $folder_id && ! empty( $file['parents'] ) ) {
			$folder_id = $file['parents'][0];
		}

		global $wpdb;

		$table = self::get_table();

		$sql = "REPLACE INTO `$table` (id, name, size, parent_id, account_id, type, extension, data, created, updated, is_computers, is_shared_with_me, is_starred, is_shared_drive) 
		VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d,%d,%d,%d )";

		$is_computers      = 'computers' == $folder_id;
		$is_shared_with_me = 'shared' == $folder_id || ! empty( $file['sharedWithMeTime'] );
		$is_starred        = 'starred' == $folder_id || ! empty( $file['starred'] );
		$is_shared_drive   = ! empty( $file['shared-drives'] );
		$parent_id         = ! empty( $file['parents'] ) ? $file['parents'][0] : '';
		$extension         = ! empty( $file['extension'] ) ? $file['extension'] : '';

		$type = $file['type'];
		if ( ! empty( $file['shortcutDetails'] ) ) {
			$type = $file['shortcutDetails']['targetMimeType'];
		}

		$size    = ! empty( $file['size'] ) ? $file['size'] : '';
		$updated = ! empty( $file['updated'] ) ? $file['updated'] : '';

		$values = [
			$file['id'],
			$file['name'],
			$size,
			$parent_id,
			$file['accountId'],
			$type,
			$extension,
			serialize( $file ),
			$file['created'],
			$updated,
			$is_computers,
			$is_shared_with_me,
			$is_starred,
			$is_shared_drive,
		];

		$wpdb->query( $wpdb->prepare( $sql, $values ) );

	}

	/**
	 * @return void
	 */
	public static function delete_account_files( $account_id = false ) {
		global $wpdb;

		$table = self::get_table();

		if ( ! empty( $account_id ) ) {
			$wpdb->delete( $table, [ 'account_id' => $account_id, ] );
		} else {
			$wpdb->query( "TRUNCATE TABLE $table" );
		}

	}

	public static function delete_folder_files( $folder_ids ) {
		global $wpdb;

		// If it's a single ID, put it in an array
		if ( ! is_array( $folder_ids ) ) {
			$folder_ids = array( $folder_ids );
		}

		// Create a string for the placeholders
		$placeholders = implode( ', ', array_fill( 0, count( $folder_ids ), '%s' ) );

		// Prepare the query
		$query = $wpdb->prepare( "DELETE FROM " . self::get_table() . " WHERE parent_id IN ($placeholders)", $folder_ids );

		// Execute the query
		$wpdb->query( $query );
	}

	/**
	 * @param $data
	 * @param $where
	 * @param $format
	 * @param $where_format
	 *
	 * @return void
	 */
	public static function update_file( $data, $where, $format = [], $where_format = [] ) {
		global $wpdb;

		$table = self::get_table();

		$wpdb->update( $table, $data, $where, $format, $where_format );

	}

	/**
	 * @param $where
	 * @param $where_format
	 *
	 * @return void
	 */
	public static function delete( $where, $where_format = null ) {
		global $wpdb;

		$table = self::get_table();

		$wpdb->delete( $table, $where, $where_format );
	}

}