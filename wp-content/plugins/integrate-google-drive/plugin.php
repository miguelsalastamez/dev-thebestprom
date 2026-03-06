<?php

/**
 * Plugin Name: Integrate Google Drive
 * Plugin URI:  https://softlabbd.com/integrate-google-drive
 * Description: Seamless Google Drive integration for WordPress, allowing you to embed, share, play, and download documents and media files directly from Google Drive to your WordPress site.
 * Version:     1.5.5
 * Author:      SoftLab
 * Author URI:  https://softlabbd.com/
 * Text Domain: integrate-google-drive
 * Domain Path: /languages/
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'You can\'t access this page directly.' );
}

/** define constants */
define( 'IGD_VERSION', '1.5.5' );
define( 'IGD_DB_VERSION', '1.4.4' );
define( 'IGD_FILE', __FILE__ );
define( 'IGD_PATH', dirname( IGD_FILE ) );
define( 'IGD_INCLUDES', IGD_PATH . '/includes' );
define( 'IGD_URL', plugins_url( '', IGD_FILE ) );
define( 'IGD_ASSETS', IGD_URL . '/assets' );

define( 'IGD_CACHE_DIR', wp_upload_dir()['basedir'] . '/integrate-google-drive-thumbnails' );
define( 'IGD_CACHE_URL', wp_upload_dir()['baseurl'] . '/integrate-google-drive-thumbnails' );

//Include the base plugin file.
include_once IGD_INCLUDES . '/class-main.php';