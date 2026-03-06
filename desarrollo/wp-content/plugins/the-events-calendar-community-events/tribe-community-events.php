<?php
/**
 * Plugin Name: The Events Calendar: Community
 * Plugin URI:  https://evnt.is/1acd
 * Description: Community is an add-on providing additional functionality to the open source plugin The Events Calendar. Empower users to submit and manage their events on your website. <a href="https://theeventscalendar.com/products/community-events/?utm_campaign=in-app&utm_source=docblock&utm_medium=plugin-community">Check out the full feature list</a>. Need more features? Peruse our selection of <a href="https://theeventscalendar.com/products/?utm_campaign=in-app&utm_source=docblock&utm_medium=plugin-community" target="_blank">plugins</a>.
 * Version: 5.0.7
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author:      The Events Calendar
 * Author URI:  https://evnt.is/1aor
 * Text Domain: tribe-events-community
 * Domain Path: /lang/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 7.1
 * WC tested up to: 9.8.5
 */

/*
Copyright 2011-2024 by The Events Calendar and the contributors

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

use TEC\Events_Community\Integrations\Events_Community_Tickets_Provider;

define( 'EVENTS_COMMUNITY_DIR', dirname( __FILE__ ) );
define( 'EVENTS_COMMUNITY_FILE', __FILE__ );

// Load the required php min version functions
require_once dirname( EVENTS_COMMUNITY_FILE ) . '/src/functions/php-min-version.php';

require_once dirname( EVENTS_COMMUNITY_FILE ) . '/vendor/autoload.php';

/**
 * Verifies if we need to warn the user about min PHP version and bail to avoid fatals
 */
if ( tribe_is_not_min_php_version() ) {
	tribe_not_php_version_textdomain( 'tribe-events-community', EVENTS_COMMUNITY_FILE );

	/**
	 * Include the plugin name into the correct place
	 *
	 * @since  4.6
	 *
	 * @param  array $names current list of names
	 *
	 * @return array
	 */
	function tribe_events_community_not_php_version_plugin_name( $names ) {
		$names['tribe-events-community'] = esc_html__( 'Community', 'tribe-events-community' );

		return $names;
	}

	add_filter( 'tribe_not_php_version_names', 'tribe_events_community_not_php_version_plugin_name' );
	if ( ! has_filter( 'admin_notices', 'tribe_not_php_version_notice' ) ) {
		add_action( 'admin_notices', 'tribe_not_php_version_notice' );
	}

	return false;
}
/**
 * Registering the Community Plugins.
 *
 * @since 5.0.0
 *
 * @return void
 */
function tribe_register_community() {
	//remove action if we run this hook through common
	remove_action( 'plugins_loaded', 'tribe_register_community', 50 );

	// if we do not have a dependency checker then shut down
	if ( ! class_exists( 'Tribe__Abstract_Plugin_Register' ) ) {
		add_action( 'admin_notices', 'tribe_community_show_fail_message' );
		add_action( 'network_admin_notices', 'tribe_community_show_fail_message' );

		//prevent loading of PRO
		remove_action( 'tribe_common_loaded', 'tribe_community_init' );

		return;
	}

	tribe_community_events_autoloading();

	new Tribe__Events__Community__Plugin_Register();

	// CE is not properly activated, we shouldn't continue our logic.
	if ( ! tribe_check_plugin( 'Tribe__Events__Community__Main' ) ) {
		return;
	}

	// Set up Community Tickets via the Provider.
	tribe_register_provider( Events_Community_Tickets_Provider::class );
}

/**
 * We have some autoloading collisions with Community Tickets child plugin.
 * To avoid this, we will disable CT initialization.
 *
 * @since 5.0.0
 */
add_action( 'tribe_common_loaded', static function() {
	remove_action( 'tribe_common_loaded', 'tribe_register_community_tickets' );
	remove_action( 'plugins_loaded', 'tribe_register_community_tickets', 50 );
}, 5 );

add_action( 'tribe_common_loaded', 'tribe_register_community', 5 );
// add action if Event Tickets or the Events Calendar is not active
add_action( 'plugins_loaded', 'tribe_register_community', 50 );

/**
 * Instantiate class and set up WordPress actions on Common Loaded
 *
 * @since 4.6
 */
add_action( 'tribe_common_loaded', 'tribe_community_init' );
function tribe_community_init() {
	$events_plugin_check = tribe_community_events_init();

	if ( ! $events_plugin_check ) {
		// We remove the notice. We will handle this ourselves.
		Tribe__Admin__Notices::instance()->remove( 'Tribe__Events__Community__Main' );
	}

	// if we have the plugin register the dependency check will handle the messages.
	if ( class_exists( 'Tribe__Abstract_Plugin_Register' ) ) {
		new Tribe__Events__Community__PUE( __FILE__ );
		return;
	}

	add_action( 'admin_notices', 'tribe_community_show_fail_message' );
	add_action( 'network_admin_notices', 'tribe_community_show_fail_message' );
}

/**
 * Instantiate class and set up WordPress actions on Common Loaded
 *
 * @since 4.6
 * @since 5.0.0 changed logic to check for `Tribe__Main`.
 *
 * @return bool
 */
function tribe_community_events_init() {
	$classes_exist = class_exists( 'Tribe__Main', false ) && class_exists( 'Tribe__Events__Community__Main', false );
	$plugins_check = function_exists( 'tribe_check_plugin' ) ?
		tribe_check_plugin( 'Tribe__Events__Community__Main' )
		: false;
	$version_ok    = $classes_exist && $plugins_check;

	if ( class_exists( 'Tribe__Main' ) && ! is_admin() && ! file_exists( __DIR__ . '/src/Tribe/PUE/Helper.php' ) ) {
		tribe_main_pue_helper();
	}

	if ( ! $version_ok ) {
		return false;
	}

	tribe_community_events_load();

	return true;
}

/**
 * Load the Community plugin.
 *
 * @since 5.0.0
 *
 * @param bool $no_tec_mode Whether to load avoiding The Events Calendar Requirements.
 * @return void
 */
function tribe_community_events_load( $no_tec_mode = false ) {
	static $loaded = false;
	if ( $loaded ) {
		// Ensure it loads once no matter where called.
		return;
	}

	$loaded = true;

	require_once( EVENTS_COMMUNITY_DIR . '/src/functions/template-tags.php' );

	new Tribe__Events__Community__PUE( EVENTS_COMMUNITY_FILE );

	tribe_singleton( 'community.main', new Tribe__Events__Community__Main( ! $no_tec_mode ) );
	tribe_singleton( 'community.templates', new Tribe__Events__Community__Templates() );

	add_action( 'admin_init', [ 'Tribe__Events__Community__Schema', 'init' ] );
}

/**
 * Autoloading of Tribe Events Community
 *
 * @since 3.10
 */
function tribe_community_events_autoloading() {
	if ( ! class_exists( 'Tribe__Autoloader' ) ) {
		return;
	}

	$autoloader = Tribe__Autoloader::instance();

	$autoloader->register_prefix( 'Tribe__Events__Community__', EVENTS_COMMUNITY_DIR . '/src/Tribe', 'events-community' );
	$autoloader->register_prefix( 'Tribe\\Events\\Community\\', EVENTS_COMMUNITY_DIR . '/src/Tribe' );
	$autoloader->register_prefix( 'TEC\\Events_Community\\', EVENTS_COMMUNITY_DIR . '/src/Events_Community' );

	// deprecated classes are registered in a class to path fashion
	foreach ( glob( EVENTS_COMMUNITY_DIR . '/src/deprecated/*.php' ) as $file ) {
		$class_name = str_replace( '.php', '', basename( $file ) );
		$autoloader->register_class( $class_name, $file );
	}

	$autoloader->register_autoloader();
}

/**
 * Shows notice of missing requirements if Common is unavailable due to TEC not being active.
 *
 * @since 4.6.3
 * @since 4.6.5 Added messaging for the other plugins besides just TEC.
 *
 * @deprecated 5.0.0
 */
function tribe_events_community_show_fail_message() {
	_deprecated_function(
		__FUNCTION__,
		'4.6.3',
		'tribe_community_show_fail_message'
	);
	tribe_community_show_fail_message();
}

/**
 * Shows notice of missing requirements if Common is unavailable due to TEC not being active.
 *
 * @since 4.6.3
 * @since 4.6.5 Added messaging for the other plugins besides just TEC.
 */
function tribe_community_show_fail_message() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$mopath = trailingslashit( basename( dirname( __FILE__ ) ) ) . 'lang/';
	$text_domain = 'tribe-events-community';

	// If we don't have Common classes load the old fashioned way
	if ( ! class_exists( 'Tribe__Main' ) ) {
		load_plugin_textdomain( $text_domain, false, $mopath );
	} else {
		// This will load `wp-content/languages/plugins` files first
		Tribe__Main::instance()->load_text_domain( $text_domain, $mopath );
	}

	// Make sure Thickbox is available and consistent appearance regardless of which admin page we're on
	wp_enqueue_style( 'plugin-install' );
	wp_enqueue_script( 'plugin-install' );
	add_thickbox();

	$link_placeholder = '<a href="%1s" class="thickbox" title="%2s">%3s</a>';
	$tec_link         = sprintf(
		$link_placeholder,
		esc_url( 'plugin-install.php?tab=plugin-information&plugin=the-events-calendar&TB_iframe=true' ),
		esc_attr__( 'The Events Calendar', 'tribe-events-community' ),
		esc_html__( 'The Events Calendar', 'tribe-events-community' ),
	);
	$et_link          = sprintf(
		$link_placeholder,
		esc_url( 'plugin-install.php?tab=plugin-information&plugin=event-tickets&TB_iframe=true' ),
		esc_attr__( 'Event Tickets', 'tribe-events-community' ),
		esc_html__( 'Event Tickets', 'tribe-events-community' ),
	);

	echo '<div class="error"><p>'
		 . sprintf(
			 esc_html__( 'To begin using The Events Calendar: Community, please install the latest version of either %1s or %2s.', 'tribe-events-community' ),
			 $tec_link,
			 $et_link
		 ) .
		 '</p></div>';
}

register_activation_hook( EVENTS_COMMUNITY_FILE, 'tribe_ce_activate' );

function tribe_ce_activate() {
	tribe_community_events_autoloading();
	if ( ! class_exists( 'Tribe__Events__Community__Main' ) ) {
		return;
	}
	Tribe__Events__Community__Main::activateFlushRewrite();
}

/**
 * Instantiate class and get the party started!
 *
 * @deprecated 4.6
 *
 * @since 1.0
 */
function Tribe_CE_Load() {
	_deprecated_function( __FUNCTION__, '4.6', '' );

	return;
}
