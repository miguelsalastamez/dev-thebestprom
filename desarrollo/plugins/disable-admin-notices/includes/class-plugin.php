<?php
/**
 * Disable admin notices core class
 *
 * @author        Alexander Kovalev <alex.kovalevv@gmail.com>
 *                Github: https://github.com/alexkovalevv
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
//use WBCR\Factory_Adverts_159\Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WDN_Plugin extends Wbcr_Factory480_Plugin {

	/**
	 * @var Wbcr_Factory480_Plugin
	 */
	private static $app;
	private $plugin_data;


	/**
	 * @param string $plugin_path
	 * @param array $data
	 *
	 * @throws Exception
	 */
	public function __construct( $plugin_path, $data ) {
		parent::__construct( $plugin_path, $data );

		self::$app         = $this;
		$this->plugin_data = $data;

		$this->global_scripts();

		if ( is_admin() ) {
			$this->admin_scripts();
		}

		// Wordpress 6.7 fix
		add_action( 'init', function () {
			if ( is_admin() ) {
				$this->register_pages();
			}
		} );

		add_filter( 'themeisle_sdk_products', [ __CLASS__, 'register_sdk' ] );

		add_filter( 'themeisle_sdk_ran_promos', '__return_true' );
	}
	/**
	 * Register product into SDK.
	 *
	 * @param array $products All products.
	 *
	 * @return array Registered product.
	 */
	public static function register_sdk( $products ) {
		$products[] = WDN_PLUGIN_FILE;

		return $products;
	}
	/**
	 * @return Wbcr_Factory480_Plugin
	 */
	public static function app() {
		return self::$app;
	}

	private function register_pages() {
		//self::app()->registerPage( 'WDN_Log_Page', WDN_PLUGIN_DIR . '/admin/pages/class-pages-log.php' );
		self::app()->registerPage( 'WDN_Settings_Page', WDN_PLUGIN_DIR . '/admin/pages/class-pages-settings.php' );
		self::app()->registerPage( 'WDAN_Notices', WDN_PLUGIN_DIR . '/admin/pages/class-pages-notices.php' );
		self::app()->registerPage( 'WDAN_Block_Ad_Redirects', WDN_PLUGIN_DIR . '/admin/pages/class-pages-edit-redirects.php' );
		self::app()->registerPage( 'WDAN_Edit_Admin_Bar', WDN_PLUGIN_DIR . '/admin/pages/class-pages-edit-admin-bar.php' );
	}

	private function admin_scripts() {
		require( WDN_PLUGIN_DIR . '/admin/options.php' );
		require( WDN_PLUGIN_DIR . '/admin/class-page-basic.php' );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			require_once( WDN_PLUGIN_DIR . '/admin/ajax/hide-notice.php' );
			require_once( WDN_PLUGIN_DIR . '/admin/ajax/restore-notice.php' );
			require_once( WDN_PLUGIN_DIR . '/admin/ajax/disable-adminbar-menus.php' );
		}

		require_once( WDN_PLUGIN_DIR . '/admin/boot.php' );
		require_once( WDN_PLUGIN_DIR . '/admin/pages/class-pages-edit-admin-bar.php' );
		require_once( WDN_PLUGIN_DIR . '/admin/pages/class-pages-edit-redirects.php' );
		require_once( WDN_PLUGIN_DIR . '/admin/pages/class-pages-notices.php' );

		/*add_action( 'plugins_loaded', function () {
			$this->register_pages();
		}, 30 );*/
	}

	private function global_scripts() {
		require_once( WDN_PLUGIN_DIR . '/includes/classes/class-configurate-notices.php' );
		new WDN_ConfigHideNotices( self::$app );
	}
}
