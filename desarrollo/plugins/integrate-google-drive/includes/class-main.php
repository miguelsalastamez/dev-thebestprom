<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();

final class Main {
	protected static $instance = null;

	public function __construct() {
		$this->register_hooks();
		$this->init_auto_loader();
		$this->includes();
	}

	public function includes() {
		// Core includes
		include_once IGD_INCLUDES . "/functions.php";
		include_once IGD_INCLUDES . "/class-enqueue.php";
		include_once IGD_INCLUDES . "/class-hooks.php";
		include_once IGD_INCLUDES . "/class-shortcode.php";
		include_once IGD_INCLUDES . "/class-shortcode-locations.php";
		include_once IGD_INCLUDES . "/class-ajax.php";

		// Integration
		include_once IGD_INCLUDES . "/class-integration.php";

		// Admin includes
		if ( is_admin() ) {
			include_once IGD_INCLUDES . "/class-admin.php";
		}

	}

	public function init_auto_loader() {
		spl_autoload_register( function ( $class_name ) {
			if ( strpos( $class_name, 'IGD' ) !== false ) {
				$classes_dir = IGD_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
				$file_name   = strtolower( str_replace( [ 'IGD\\', '_' ], [ '', '-' ], $class_name ) );
				$file_name   = "class-$file_name.php";

				$file = $classes_dir . $file_name;

				if ( file_exists( $file ) ) {
					include_once $file;
				}
			}
		} );
	}

	private function register_hooks() {

		register_activation_hook( IGD_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( IGD_FILE, [ $this, 'deactivate' ] );
		register_uninstall_hook( IGD_FILE, [ 'IGD\Main', 'uninstall' ] );

		// Check environment on plugins_loaded hook when WordPress is fully initialized
		add_action( 'plugins_loaded', [ $this, 'check_environment' ], 1 );

		do_action( 'igd_loaded' );

		add_action( 'admin_notices', [ $this, 'print_notices' ], 15 );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
	}

	public function check_environment() {
		$errors = [];

		// Check PHP version
		$php_version     = PHP_VERSION;
		$min_php_version = '7.4';
		if ( version_compare( $php_version, $min_php_version, '<' ) ) {
			$errors[] = sprintf(
				wp_kses_post( 'Integrate Google Drive requires PHP version <strong>%1$s</strong> or greater. You are currently running PHP <strong>%2$s</strong>. Please contact your hosting provider to update PHP.' ),
				esc_html( $min_php_version ),
				esc_html( $php_version )
			);
		}

		// Check WordPress version
		$wp_version     = get_bloginfo( 'version' );
		$min_wp_version = '5.0';
		if ( version_compare( $wp_version, $min_wp_version, '<' ) ) {
			$errors[] = sprintf(
				wp_kses_post( 'Integrate Google Drive requires WordPress version <strong>%1$s</strong> or greater. You are currently running WordPress <strong>%2$s</strong>. Please update WordPress to the latest version.' ),
				esc_html( $min_wp_version ),
				esc_html( $wp_version )
			);
		}

		// Check if required extensions are available
		if ( ! function_exists( 'curl_init' ) ) {
			$errors[] = wp_kses_post( 'Integrate Google Drive requires the <strong>cURL</strong> PHP extension. Please contact your hosting provider to enable it.' );
		}

		if ( ! extension_loaded( 'json' ) ) {
			$errors[] = wp_kses_post( 'Integrate Google Drive requires the <strong>JSON</strong> PHP extension. Please contact your hosting provider to enable it.' );
		}

		// If there are errors, deactivate and display them
		if ( ! empty( $errors ) ) {
			deactivate_plugins( plugin_basename( IGD_FILE ) );

			$error_message = '<div style="margin-top: 10px; padding: 12px; background: #fef5e7; border-left: 4px solid #f39c12; border-radius: 3px;">';
			$error_message .= '<p style="margin: 0 0 10px 0; font-weight: 600; color: #d68910;">Integrate Google Drive Plugin Requirements Not Met</p>';
			foreach ( $errors as $error ) {
				$error_message .= '<p style="margin: 5px 0; color: #7d6608;">' . wp_kses_post( $error ) . '</p>';
			}
			$error_message .= '</div>';

			wp_die( wp_kses_post( $error_message ), esc_html__( 'Plugin Requirements', 'integrate-google-drive' ) );
		}

	}

	public function activate_deactivate( $method ) {

		if ( ! class_exists( 'IGD\Install' ) ) {
			include_once IGD_INCLUDES . "/class-install.php";
		}

		Install::$method();
	}

	public function activate() {
		$this->activate_deactivate( 'activate' );
	}

	public function deactivate() {
		$this->activate_deactivate( 'deactivate' );
	}

	public static function uninstall() {

		if ( ! class_exists( 'IGD\Install' ) ) {
			include_once IGD_INCLUDES . "/class-install.php";
		}


		// Remove crons
		Install::deactivate();

		// Delete data
		if ( igd_get_settings( 'deleteData' ) ) {
			delete_option( 'igd_tokens' );
			delete_option( 'igd_accounts' );
			delete_option( 'igd_settings' );
			delete_option( 'igd_cached_folders' );

			igd_delete_cache();
		}

	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $plugin_file == plugin_basename( IGD_FILE ) ) {
			$plugin_meta[] = sprintf( '<a target="_blank" href="https://softlabbd.com/docs-category/integrate-google-drive-docs/" style="color:#2FB44B; font-weight: 600;">%s</a>', esc_html__( 'Docs', 'integrate-google-drive' ) );
			$plugin_meta[] = sprintf( '<a target="_blank" href="https://softlabbd.com/support/" style="color:#2FB44B; font-weight: 600;">%s</a>', esc_html__( 'Support', 'integrate-google-drive' ) );
		}

		return $plugin_meta;
	}

	public function add_notice( $class, $message ) {
		$notices = get_option( sanitize_key( 'igd_notices' ), [] );

		if ( is_string( $message ) && is_string( $class ) && ! wp_list_filter( $notices, [ 'message' => $message ] ) ) {
			$notices[] = [
				'message' => $message,
				'class'   => $class,
			];

			update_option( sanitize_key( 'igd_notices' ), $notices );
		}
	}

	public function print_notices() {
		$notices = get_option( sanitize_key( 'igd_notices' ), [] );

		foreach ( $notices as $notice ) {
			$class   = ! empty( $notice['class'] ) ?  $notice['class']  : 'info';
			$message = ! empty( $notice['message'] ) ? $notice['message'] : '';

			printf( '<div class="notice notice-large is-dismissible igd-admin-notice notice-%1$s">%2$s</div>',
				esc_attr($class),
				wp_kses($message, [
					'a'      => [
						'href'  => [],
						'title' => [],
						'target'=> [],
						'rel'   => [],
					],
					'strong' => [],
					'em'     => [],
					'br'     => [],
					'p'      => [
						'class' => [],
					],
					'span'      => [
						'class' => [],
					],
					'div'      => [
						'class' => [],
					],
					'style'      => [],
					'script'      => [],
				]),
			);
		}

		update_option( sanitize_key( 'igd_notices' ), [] );
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

if ( ! function_exists( 'igd' ) ) {
	function igd() {
		return Main::instance();
	}
}

igd();
