<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;

class Admin {
	/**
	 * @var null
	 */
	protected static $instance = null;

	private $pages = [];

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		add_action( 'admin_init', [ $this, 'init_update' ] );

		// Remove admin notices from plugin pages
		add_action( 'admin_init', [ $this, 'show_review_popup' ] );

		// admin body class
		add_filter( 'admin_body_class', [ $this, 'admin_body_class' ] );

		//Handle custom app authorization
		add_action( 'admin_init', [ $this, 'app_authorization' ] );

		add_action( 'admin_notices', [ $this, 'display_notices' ] );
	}

	public function display_notices() {
		// Only show notices to users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Display migration notice if needed
		$this->display_migration_notice();

		// Display account-related notices
		$this->display_account_notices();

		// Display account reconnection notice
		$this->display_account_reconnection_notice();
	}

	/**
	 * Display migration notice if migration is in progress.
	 */
	private function display_migration_notice() {
		$migration_status = get_option( 'igd_migration_1_5_1_status' );

		if ( 'run' !== $migration_status && 'running' !== $migration_status ) {
			return;
		}

		ob_start();
		include IGD_INCLUDES . '/views/notice/migration-1.5.1.php';
		$notice_html = ob_get_clean();

		igd()->add_notice( 'warning igd-migration-notice warning', $notice_html );
	}

	/**
	 * Display lost authorization notice for accounts.
	 */
	private function display_account_notices() {
		$accounts = Account::instance()->get_accounts();

		if ( empty( $accounts ) ) {
			return;
		}

		// Prepare common variables
		$icon_url       = esc_url( IGD_ASSETS . '/images/drive.png' );
		$refresh_url    = esc_url( admin_url( 'admin.php?page=integrate-google-drive-settings&tab=accounts' ) );
		$plugin_name    = esc_html__( 'Integrate Google Drive', 'integrate-google-drive' );
		$refresh_text   = esc_html__( 'Refresh', 'integrate-google-drive' );
		$alt_text       = esc_attr__( 'Google Drive icon', 'integrate-google-drive' );
		$lost_auth_text = esc_html__( 'lost authorization for account', 'integrate-google-drive' );

		// Display notice for each account with lost authorization
		foreach ( $accounts as $account ) {
			// Skip accounts that don't have lost authorization
			if ( empty( $account['lost'] ) && empty( $account['is_lost'] ) ) {
				continue;
			}

			$email = esc_html( $account['email'] ?? '' );

			$msg = sprintf(
				'<img src="%1$s" width="32" alt="%2$s" /> <strong>%3$s</strong> %4$s <strong>%5$s</strong>. <a class="button" href="%6$s">%7$s</a>',
				$icon_url,
				$alt_text,
				$plugin_name,
				$lost_auth_text,
				$email,
				$refresh_url,
				$refresh_text
			);

			igd()->add_notice( 'error igd-lost-auth-notice', $msg );
		}
	}

	/**
	 * Display account reconnection notice.
	 */
	private function display_account_reconnection_notice() {
		if ( ! get_option( 'igd_account_notice' ) ) {
			return;
		}

		ob_start();
		include IGD_INCLUDES . '/views/notice/account.php';
		$notice_html = ob_get_clean();

		igd()->add_notice( 'info igd-account-notice error', $notice_html );
	}

	public function admin_body_class( $classes ) {
		global $current_screen;

		// Return early if current screen is not available
		if ( ! is_object( $current_screen ) ) {
			return $classes;
		}

		$admin_pages = Admin::instance()->get_pages();

		// Check if current page is one of our admin pages
		if ( in_array( $current_screen->id, $admin_pages, true ) ) {
			$page_key = array_search( $current_screen->id, $admin_pages, true );

			if ( false !== $page_key ) {
				$classes .= ' igd-admin-page igd_' . $page_key . ' ';
			}
		}

		return $classes;
	}

	public function show_review_popup() {
		// Only show popup to users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if rating notice is enabled and not in timeout period
		$rating_notice    = get_option( 'igd_rating_notice' );
		$notice_interval  = get_transient( 'igd_rating_notice_interval' );

		if ( 'off' === $rating_notice || 'off' === $notice_interval ) {
			return;
		}

		// Add flag to localized data to show review popup
		add_filter( 'igd_localize_data', function ( $data ) {
			$data['showReviewPopup'] = true;
			return $data;
		} );
	}

	public function app_authorization() {
		// Check if this is the authorization callback
		if ( ! isset( $_GET['action'] ) || 'integrate-google-drive-authorization' !== sanitize_key( $_GET['action'] ) ) {
			return;
		}

		// Validate and sanitize the state parameter
		if ( ! isset( $_GET['state'] ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		// Decode and validate the redirect URL
		$redirect_url = $this->validate_and_decode_state( sanitize_text_field( wp_unslash( $_GET['state'] ) ) );

		if ( false === $redirect_url ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		// Remove 'action' from query parameters
		unset( $_GET['action'] );

		// Build the final redirect URL with remaining parameters
		$params       = http_build_query( $_GET );
		$final_url    = esc_url_raw( $redirect_url . '&' . $params );

		// Execute the redirect
		wp_redirect( $final_url );
		exit;
	}

	/**
	 * Validate and decode the state parameter from authorization callback.
	 *
	 * @param string $encoded_state The base64-encoded state parameter.
	 *
	 * @return string|false The decoded URL if valid, false otherwise.
	 */
	private function validate_and_decode_state( $encoded_state ) {
		// Decode the state parameter
		$state_url = base64_decode( $encoded_state, true );

		if ( false === $state_url ) {
			return false;
		}

		// Validate the URL
		if ( false === filter_var( $state_url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		// Validate that the URL belongs to the current website domain
		if ( ! $this->is_valid_redirect_domain( $state_url ) ) {
			return false;
		}

		return $state_url;
	}

	/**
	 * Check if the redirect URL belongs to the current website domain.
	 *
	 * @param string $redirect_url The URL to validate.
	 *
	 * @return bool True if the domain matches, false otherwise.
	 */
	private function is_valid_redirect_domain( $redirect_url ) {
		$current_domain  = wp_parse_url( home_url(), PHP_URL_HOST );
		$redirect_domain = wp_parse_url( $redirect_url, PHP_URL_HOST );

		return $current_domain === $redirect_domain;
	}

	public function init_update() {
		// Only run updates if user can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! class_exists( 'IGD\Update' ) ) {
			require_once IGD_INCLUDES . '/class-update.php';
		}

		$updater = Update::instance();

		if ( $updater->needs_update() ) {
			$updater->perform_updates();
		}
	}

	public function admin_menu() {

		$main_menu_added = false;

		$access_rights = [
			'file_browser'      => [
				'view'          => [ 'IGD\App', 'view' ],
				'title'         => __( 'File Browser - Integrate Google Drive', 'integrate-google-drive' ),
				'submenu_title' => __( 'File Browser', 'integrate-google-drive' )
			],
			'shortcode_builder' => [
				'view'          => [ 'IGD\Shortcode', 'view' ],
				'title'         => __( 'Module Builder - Integrate Google Drive', 'integrate-google-drive' ),
				'submenu_title' => __( 'Module Builder', 'integrate-google-drive' )
			],
			'proof_selections'  => [
				'view'          => [ 'IGD\Proof_Selections', 'view' ],
				'title'         => __( 'Proof Selections - Integrate Google Drive', 'integrate-google-drive' ),
				'submenu_title' => '',
			],
			'private_files'     => [
				'view'          => [ 'IGD\Private_Folders', 'view' ],
				'title'         => __( 'Users Private Files - Integrate Google Drive', 'integrate-google-drive' ),
				'submenu_title' => __( 'Users Private Files', 'integrate-google-drive' )
			],
			'getting_started'   => [
				'view'          => [ $this, 'render_getting_started_page' ],
				'title'         => __( 'Getting Started - Integrate Google Drive', 'integrate-google-drive' ),
				'submenu_title' => __( 'Getting Started', 'integrate-google-drive' )
			],
			'settings'          => [
				'view'          => [ $this, 'render_settings_page' ],
				'title'         => __( 'Settings - Integrate Google Drive', 'integrate-google-drive' ),
				'submenu_title' => __( 'Settings', 'integrate-google-drive' )
			]
		];

		foreach ( $access_rights as $access_right => $page_config ) {

			$can_access = igd_user_can_access( $access_right );

			if ( 'proof_selections' === $access_right ) {
				$can_access = igd_user_can_access( 'shortcode_builder' );
			}

			if ( $can_access ) {
				if ( ! $main_menu_added ) {
					$this->pages[ $access_right . '_page' ] = $this->add_main_menu_page( $page_config['title'], $page_config['submenu_title'], $page_config['view'] );
					$main_menu_added                        = true;
				} else {
					$this->pages[ $access_right . '_page' ] = $this->add_submenu_page( $page_config['title'], $page_config['submenu_title'], $page_config['view'], $access_right );
				}
			}

		}

	}

	private function add_main_menu_page( $title, $submenu_title, $view ) {

		$page = add_menu_page(
			__( 'Integrate Google Drive', 'integrate-google-drive' ),
			__( 'Google Drive', 'integrate-google-drive' ),
			'read',
			'integrate-google-drive',
			$view,
			IGD_ASSETS . '/images/drive.png',
			11
		);

		add_submenu_page( 'integrate-google-drive', $title, $submenu_title, 'read', 'integrate-google-drive' );

		return $page;
	}

	private function add_submenu_page( $title, $submenu_title, $view, $slug, $priority = 90 ) {

		$slug = str_replace( '_', '-', $slug );

		return add_submenu_page( 'integrate-google-drive', $title, $submenu_title, 'read', 'integrate-google-drive-' . $slug, $view, $priority );
	}

	public function render_getting_started_page() {
		include_once IGD_INCLUDES . '/views/getting-started/index.php';
	}

	public function render_settings_page() { ?>
        <div id="igd-settings"></div>
	<?php }

	public function get_pages() {
		return array_filter( $this->pages );
	}

	/**
	 * @return Admin|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

Admin::instance();