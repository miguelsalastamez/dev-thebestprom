<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;

/**
 * Class Install
 *
 * Handles plugin installation, activation, deactivation, and database setup.
 * Manages table creation, default settings, and scheduled cron events.
 *
 * @package IGD
 */
class Install {

	/**
	 * Plugin activation logic.
	 *
	 * Performs initial setup, creates tables, adds default settings,
	 * and handles updates if needed.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		self::load_dependencies();

		$updater = Update::instance();

		if ( $updater->needs_update() ) {
			self::safe_update( $updater );
		} else {
			self::create_default_data();
			self::add_default_settings();
			self::create_tables();
			self::create_thumbnails_folder();
			self::flush_rewrite_rules();
		}

	}

	/**
	 * Flush rewrite rules and register tags.
	 *
	 * Registers custom rewrite tags and rules for modules and tables,
	 * then flushes the rewrite rules to activate them.
	 */
	public static function flush_rewrite_rules() {
		self::register_rewrite_tags();
		self::add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Register custom rewrite tags.
	 */
	private static function register_rewrite_tags() {
		add_rewrite_tag( '%igd-modules%', '([0-9]+)' );
		add_rewrite_tag( '%igd-tables%', '([0-9]+)' );
	}

	/**
	 * Add custom rewrite rules for modules and tables.
	 */
	private static function add_rewrite_rules() {
		add_rewrite_rule(
			'^igd-modules/([0-9]+)/?$',
			'index.php?igd-modules=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^igd-tables/([0-9]+)/?$',
			'index.php?igd-tables=$matches[1]',
			'top'
		);
	}

	/**
	 * Create thumbnails cache folder if it doesn't exist.
	 *
	 * Creates the directory structure needed for storing cached thumbnails.
	 */
	private static function create_thumbnails_folder() {
		if ( file_exists( IGD_CACHE_DIR ) ) {
			return; // Directory already exists
		}

		if ( ! wp_mkdir_p( IGD_CACHE_DIR ) ) {
			error_log( 'IGD: Failed to create cache directory at ' . IGD_CACHE_DIR );
		}
	}

	/**
	 * Load required dependencies for the plugin activation and update process.
	 */
	private static function load_dependencies() {
		if ( ! class_exists( 'IGD\Update' ) ) {
			require_once IGD_INCLUDES . '/class-update.php';
		}
	}

	/**
	 * Safely perform updates, with error handling and logging.
	 *
	 * @param Update $updater Instance of the Update class
	 */
	private static function safe_update( $updater ) {
		try {
			$updater->perform_updates();
		} catch ( \Exception $e ) {
			error_log( '[IGD Update Error] ' . $e->getMessage() );
		}
	}

	/**
	 * Add default settings during plugin activation.
	 *
	 * Configures default integrations and settings for fresh installations.
	 */
	public static function add_default_settings() {
		// Only add settings if this is a fresh install
		if ( get_option( 'igd_version' ) ) {
			return;
		}

		$settings = igd_get_settings();

		// Get default integrations list
		$integrations = self::get_default_integrations();

		$settings['integrations'] = $integrations;
		update_option( 'igd_settings', $settings );
	}

	/**
	 * Get list of default integrations based on plugin license.
	 *
	 * @return array List of integrations to enable by default.
	 */
	private static function get_default_integrations() {
		$integrations = [
			'classic-editor',
			'gutenberg-editor',
			'elementor',
		];


		return $integrations;
	}

	/**
	 * Deactivate the plugin and clean up scheduled events.
	 */
	public static function deactivate() {
		self::remove_scheduled_events();
	}

	/**
	 * Remove scheduled cron events during deactivation.
	 *
	 * Clears all registered cron events to prevent them from running
	 * after the plugin is deactivated.
	 */
	private static function remove_scheduled_events() {
		$hooks = [
			'igd_sync_interval',
			'igd_restore_sharing_interval',
			'igd_statistics_daily_report',
			'igd_statistics_weekly_report',
			'igd_statistics_monthly_report',
		];

		foreach ( $hooks as $hook ) {
			self::unschedule_event( $hook );
		}
	}

	/**
	 * Unschedule a single cron event.
	 *
	 * @param string $hook The cron hook name.
	 */
	private static function unschedule_event( $hook ) {
		$timestamp = wp_next_scheduled( $hook );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $hook );
		}
	}

	/**
	 * Create required database tables during plugin activation.
	 */
	public static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tables = self::get_table_definitions();

		foreach ( $tables as $table ) {
			dbDelta( $table );
		}
	}

	/**
	 * Get the table creation SQL for the plugin.
	 *
	 * @return array List of SQL table creation statements
	 */
	private static function get_table_definitions() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		return [
			self::get_files_table_sql( $wpdb, $charset_collate ),
			self::get_shortcodes_table_sql( $wpdb, $charset_collate ),
			self::get_tables_table_sql( $wpdb, $charset_collate ),
			self::get_logs_table_sql( $wpdb, $charset_collate ),
			self::get_selections_table_sql( $wpdb, $charset_collate ),
		];
	}

	/**
	 * Get SQL for files table creation.
	 *
	 * @param object $wpdb              WordPress database object.
	 * @param string $charset_collate   Charset and collation.
	 *
	 * @return string SQL statement.
	 */
	private static function get_files_table_sql( $wpdb, $charset_collate ) {
		return "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}integrate_google_drive_files (
			id VARCHAR(60) NOT NULL,
			name TEXT NULL,
			size BIGINT NULL,
			parent_id TEXT,
			account_id TEXT NOT NULL,
			type VARCHAR(255) NOT NULL,
			extension VARCHAR(10) NOT NULL,
			data LONGTEXT,
			is_computers TINYINT(1) DEFAULT 0,
			is_shared_with_me TINYINT(1) DEFAULT 0,
			is_starred TINYINT(1) DEFAULT 0,
			is_shared_drive TINYINT(1) DEFAULT 0,
			created TEXT NULL,
			updated TEXT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";
	}

	/**
	 * Get SQL for shortcodes table creation.
	 *
	 * @param object $wpdb              WordPress database object.
	 * @param string $charset_collate   Charset and collation.
	 *
	 * @return string SQL statement.
	 */
	private static function get_shortcodes_table_sql( $wpdb, $charset_collate ) {
		return "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}integrate_google_drive_shortcodes (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NULL,
			status VARCHAR(6) NULL DEFAULT 'on',
			type VARCHAR(255) NULL,
			user_id BIGINT(20) NULL,
			config LONGTEXT NULL,
			locations LONGTEXT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NULL,
			PRIMARY KEY (id)
		) $charset_collate;";
	}

	/**
	 * Get SQL for tables table creation.
	 *
	 * @param object $wpdb              WordPress database object.
	 * @param string $charset_collate   Charset and collation.
	 *
	 * @return string SQL statement.
	 */
	private static function get_tables_table_sql( $wpdb, $charset_collate ) {
		return "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}integrate_google_drive_tables (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NULL,
			status VARCHAR(6) NULL DEFAULT 'on',
			user_id BIGINT(20) NULL,
			config LONGTEXT NULL,
			locations LONGTEXT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NULL,
			PRIMARY KEY (id)
		) $charset_collate;";
	}

	/**
	 * Get SQL for logs table creation.
	 *
	 * @param object $wpdb              WordPress database object.
	 * @param string $charset_collate   Charset and collation.
	 *
	 * @return string SQL statement.
	 */
	private static function get_logs_table_sql( $wpdb, $charset_collate ) {
		return "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}integrate_google_drive_logs (
			id INT NOT NULL AUTO_INCREMENT,
			shortcode_id INT UNSIGNED DEFAULT NULL,
			type VARCHAR(255) NULL,
			user_id INT NULL,
			file_id TEXT NOT NULL,
			file_type TEXT NULL,
			file_name TEXT NULL,
			page TEXT NULL,
			account_id TEXT NOT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
	}

	/**
	 * Get SQL for selections table creation.
	 *
	 * @param object $wpdb              WordPress database object.
	 * @param string $charset_collate   Charset and collation.
	 *
	 * @return string SQL statement.
	 */
	private static function get_selections_table_sql( $wpdb, $charset_collate ) {
		return "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}integrate_google_drive_selections (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			shortcode_id INT UNSIGNED DEFAULT NULL,
			user_id INT UNSIGNED DEFAULT NULL,
			email VARCHAR(255) NOT NULL,
			files TEXT DEFAULT NULL,
			message MEDIUMTEXT DEFAULT NULL,
			page TEXT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";
	}

	/**
	 * Create default data for the plugin (version, installation time, etc.).
	 */
	private static function create_default_data() {
		self::initialize_version_options();
		self::initialize_installation_options();
	}

	/**
	 * Initialize plugin version options.
	 *
	 * Sets up version and database version on first installation.
	 */
	private static function initialize_version_options() {
		if ( ! get_option( 'igd_version' ) ) {
			update_option( 'igd_version', IGD_VERSION );
		}

		if ( ! get_option( 'igd_db_version' ) ) {
			update_option( 'igd_db_version', IGD_DB_VERSION );
		}
	}

	/**
	 * Initialize installation-related options.
	 *
	 * Sets up installation time and setup wizard flag.
	 */
	private static function initialize_installation_options() {
		if ( ! get_option( 'igd_install_time' ) ) {
			update_option( 'igd_install_time', current_time( 'mysql' ) );
		}

		if ( ! get_option( 'igd_show_setup' ) ) {
			update_option( 'igd_show_setup', 1 );
		}

		// Set transient for rating notice
		set_transient( 'igd_rating_notice_interval', 'off', 10 * DAY_IN_SECONDS );
	}

}
