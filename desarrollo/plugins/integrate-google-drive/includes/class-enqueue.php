<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();

class Enqueue {
	/**
	 * @var Enqueue|null
	 */
	protected static $instance = null;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
	}

	/**
	 * Register and enqueue vendor styles and scripts.
	 */
	private function register_vendor_assets() {
		// SweetAlert2
		if ( ! wp_style_is( 'igd-sweetalert2', 'registered' ) ) {
			wp_register_style( 'igd-sweetalert2', IGD_ASSETS . '/vendor/sweetalert2/sweetalert2.min.css', [], '11.4.8' );
			wp_register_script( 'igd-sweetalert2', IGD_ASSETS . '/vendor/sweetalert2/sweetalert2.min.js', [], '11.4.8', true );
		}

	}

	public function frontend_scripts() {
		$load_on_all_pages = igd_get_settings( 'loadScriptsOnAllPages' );

		$this->register_vendor_assets();

		$css_deps = [
			'wp-components',
			'igd-sweetalert2',
			'dashicons',
		];

		wp_register_style( 'igd-frontend', IGD_ASSETS . '/css/frontend.css', $css_deps, IGD_VERSION );
		wp_style_add_data( 'igd-frontend', 'rtl', 'replace' );
		wp_add_inline_style( 'igd-frontend', $this->get_custom_css() );

		$js_deps = [
			'react',
			'react-dom',
			'wp-i18n',
			'wp-plupload',
			'wp-util',
			'jquery',
			'igd-sweetalert2',
		];

		wp_register_script( 'igd-frontend', IGD_ASSETS . '/js/frontend.js', $js_deps, IGD_VERSION, true );

		// Localize data
		wp_localize_script( 'igd-frontend', 'igd', $this->get_localize_data( false, 'frontend' ) );
		wp_set_script_translations( 'igd-frontend', 'integrate-google-drive', plugin_dir_path( IGD_FILE ) . 'languages' );

		// Enqueue all scripts and styles on all pages if the setting is enabled
		if ( $load_on_all_pages ) {
			wp_enqueue_style( 'igd-frontend' );
			wp_enqueue_script( 'igd-frontend' );
		}
	}

	public function admin_scripts( $hook = '', $should_check = true ) {
		$should_enqueue = ! $should_check || $this->should_enqueue_admin_scripts( $hook );

		$this->register_vendor_assets();

		// Register admin stylesheet
		wp_register_style( 'igd-admin', IGD_ASSETS . '/css/admin.css', [
			'wp-components',
			'igd-sweetalert2',
		], IGD_VERSION );

		wp_enqueue_style( 'igd-admin' );
		wp_style_add_data( 'igd-admin', 'rtl', 'replace' );
		wp_add_inline_style( 'igd-admin', $this->get_custom_css() );

		// Enqueue core dashicons (standard WordPress icon library)
		wp_enqueue_style( 'dashicons' );

		if ( ! $should_enqueue ) {
			return;
		}

		if ( ! class_exists( 'IGD\Admin' ) ) {
			require_once IGD_PATH . '/includes/class-admin.php';
		}

		$admin_pages = Admin::instance()->get_pages();

		// Core admin script
		wp_enqueue_script( 'igd-admin', IGD_ASSETS . '/js/admin.js', [
			'jquery',
			'wp-element',
			'wp-components',
			'wp-i18n',
			'wp-util',
			'wp-plupload',
			'igd-sweetalert2',
		], IGD_VERSION, true );

		wp_set_script_translations( 'igd-admin', 'integrate-google-drive', plugin_dir_path( IGD_FILE ) . 'languages' );

		// Private Folders page scripts
		if ( ! empty( $admin_pages['private_files_page'] ) && $admin_pages['private_files_page'] === $hook ) {
			wp_enqueue_script( 'igd-private-folders', IGD_ASSETS . '/js/private-folders.js', [ 'igd-admin' ], IGD_VERSION, true );
		}

		// Settings Page Scripts
		if ( isset( $admin_pages['settings_page'] ) && $admin_pages['settings_page'] === $hook ) {
			$this->enqueue_settings_page_assets();
		}

		// TinyMCE Editor
		if ( ! empty( $hook ) ) {
			wp_enqueue_editor();
		}

		wp_localize_script( 'igd-admin', 'igd', $this->get_localize_data( $hook ) );
	}

	/**
	 * Enqueue settings page specific assets (CodeMirror, media uploader, etc.)
	 */
	private function enqueue_settings_page_assets() {
		// Uploader scripts
		wp_enqueue_media();

		// Code Editor
		wp_enqueue_script( 'wp-theme-plugin-editor' );
		wp_enqueue_style( 'wp-codemirror' );

		$cm_settings = [
			'codeEditor' => wp_enqueue_code_editor( [
				'type'  => 'text/css',
				'theme' => 'dracula',
			] ),
		];

		wp_localize_script( 'igd-admin', 'cm_settings', $cm_settings );

		// Enqueue settings page scripts
		wp_enqueue_script( 'igd-settings', IGD_ASSETS . '/js/settings.js', [ 'igd-admin' ], IGD_VERSION, true );
	}

	/**
	 * @return array
	 */
	public function get_localize_data( $hook = false, $script = 'admin' ) {

		$localize_data = [
			'isAdmin'   => is_admin(),
			'pluginUrl' => IGD_URL,
			'siteUrl'   => site_url(),
			'homeUrl'   => home_url(),
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'igd' ),
			'isPro'     => false,
			'settings'  => igd_get_settings(),
		];

		if ( is_admin() || $this->is_elementor_editor() || $this->is_divi_builder() ) {

			$localize_data['adminUrl']      = admin_url();
			$localize_data['accounts']      = Account::instance()->get_accounts();
			$localize_data['activeAccount'] = Account::instance()->get_active_account();

			if ( ! class_exists( 'IGD\Admin' ) ) {
				require_once IGD_PATH . '/includes/class-admin.php';
			}

			$admin_pages = Admin::instance()->get_pages();

			// Determine if we are on the settings page
			$is_settings_page = ! empty( $admin_pages['settings_page'] ) && $admin_pages['settings_page'] === $hook;

			// Check page parameter from GET request
			if ( ! $is_settings_page && isset( $_GET['page'] ) ) {
				$page_param = sanitize_key( wp_unslash( $_GET['page'] ) );
				if ( 'integrate-google-drive-settings' === $page_param ) {
					$is_settings_page = true;
				}
			}

			// Determine if we are on the file browser page
			$is_file_browser_page = ! empty( $admin_pages['file_browser_page'] ) && $admin_pages['file_browser_page'] === $hook;

			// Localize the Google Drive API auth URL only on settings or file browser pages
			if ( $is_settings_page || $is_file_browser_page ) {
				$localize_data['authUrl'] = Client::instance()->get_auth_url();
			}

			// User access folder data
			$localize_data['userAccessData'] = igd_get_user_access_data();

			// Shortcodes data
			$localize_data['shortcodes'] = Shortcode::instance()->get_shortcodes();

			// if user-private-folders page
			$is_private_folders_page = ! empty( $admin_pages['private_files_page'] ) && $admin_pages['private_files_page'] === $hook;

			if ( $is_private_folders_page ) {
				$localize_data['userData'] = Private_Folders::instance()->get_user_data( [ 'number' => 10 ] );
			}

		}

		return apply_filters( 'igd_localize_data', $localize_data, $script );
	}

	public function get_custom_css() {
		$css        = '';
		$custom_css = igd_get_settings( 'customCss' );

		if ( ! empty( $custom_css ) ) {
			$css .= $custom_css;
		}

		$primary_color = igd_get_settings( 'primaryColor', '#3C82F6' );

		ob_start();
		?>
		:root {
			--color-primary: <?php echo esc_attr( $primary_color ); ?>;
			--color-primary-dark: <?php echo esc_attr( igd_color_brightness( $primary_color, -30 ) ); ?>;
			--color-primary-light: <?php echo esc_attr( igd_hex2rgba( $primary_color, '.5' ) ); ?>;
			--color-primary-light-alt: <?php echo esc_attr( igd_color_brightness( $primary_color, 30 ) ); ?>;
			--color-primary-lighter: <?php echo esc_attr( igd_hex2rgba( $primary_color, '.1' ) ); ?>;
			--color-primary-lighter-alt: <?php echo esc_attr( igd_color_brightness( $primary_color, 50 ) ); ?>;
		}
		<?php
		$css .= ob_get_clean();

		return $css;
	}

	public function is_block_editor() {
		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
			return ! empty( $current_screen->is_block_editor );
		}

		return false;
	}

	public function is_divi_builder() {
		return function_exists( 'et_fb_is_enabled' ) && et_fb_is_enabled();
	}

	public function is_elementor_editor() {
		return isset( $_GET['elementor-preview'] );
	}

	public function should_enqueue_admin_scripts( $hook ) {

		if ( ! class_exists( 'IGD\Admin' ) ) {
			require_once IGD_PATH . '/includes/class-admin.php';
		}

		$admin_pages = Admin::instance()->get_pages();

		$integration = Integration::instance();

		if ( $integration->is_active( 'gutenberg-editor' ) ) {
			if ( $this->is_block_editor() ) {
				return true;
			}
		}

		if ( $integration->is_active( 'elementor' ) ) {
			if ( $this->is_elementor_editor() ) {
				return true;
			}
		}

		if ( $integration->is_active( 'divi' ) ) {
			if ( $this->is_divi_builder() ) {
				return true;
			}
		}

		if ( $integration->is_active( 'cf7' ) ) {
			$admin_pages[] = 'toplevel_page_wpcf7';
			$admin_pages[] = 'contact_page_wpcf7-new';
		}

		return in_array( $hook, $admin_pages, true );
	}

	/**
	 * Ensure frontend assets are registered in admin context before enqueuing.
	 */
	private function ensure_frontend_assets_registered() : void {
		if ( ! wp_style_is( 'igd-frontend', 'registered' ) ) {
			$this->register_vendor_assets();
			wp_register_style( 'igd-frontend', IGD_ASSETS . '/css/frontend.css', [
				'wp-components',
				'igd-sweetalert2',
				'dashicons',
			], IGD_VERSION );
			wp_style_add_data( 'igd-frontend', 'rtl', 'replace' );
		}

		if ( ! wp_script_is( 'igd-frontend', 'registered' ) ) {
			$this->register_vendor_assets();
			wp_register_script( 'igd-frontend', IGD_ASSETS . '/js/frontend.js', [
				'react',
				'react-dom',
				'wp-i18n',
				'wp-plupload',
				'wp-util',
				'jquery',
				'igd-sweetalert2',
			], IGD_VERSION, true );
		}
	}

	/**
	 * @return Enqueue|null
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

Enqueue::instance();
