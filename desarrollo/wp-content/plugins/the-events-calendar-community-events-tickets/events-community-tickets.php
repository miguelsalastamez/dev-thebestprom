<?php
/*
Plugin Name: The Events Calendar: Community Events Tickets
Plugin URI:  https://evnt.is/1ace
Description: Community Events Tickets is an add-on providing an additional way for community organizers to offer paid tickets for community events.
Version: 4.9.6
Author: The Events Calendar
Author URI: https://evnt.is/1aor
Text Domain: tribe-events-community-tickets
Domain Path: /lang/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
Copyright 2011-2021 by The Events Calendar and the contributors

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

define( 'EVENTS_COMMUNITY_TICKETS_DIR', dirname( __FILE__ ) );
define( 'EVENTS_COMMUNITY_TICKETS_FILE', __FILE__ );

// Load the required php min version functions
require_once dirname( EVENTS_COMMUNITY_TICKETS_FILE ) . '/src/functions/php-min-version.php';
require_once EVENTS_COMMUNITY_TICKETS_DIR . '/vendor/autoload.php';

// Load the check to see if CT/TC functionality is enabled and available.
require_once EVENTS_COMMUNITY_TICKETS_DIR . '/src/functions/tickets/commerce/provider.php';

/**
 * Verifies if we need to warn the user about min PHP version and bail to avoid fatals
 */
if ( tribe_is_not_min_php_version() ) {
	tribe_not_php_version_textdomain( 'tribe-events-community-tickets', EVENTS_COMMUNITY_TICKETS_FILE );

	/**
	 * Include the plugin name into the correct place
	 *
	 * @since  4.6
	 *
	 * @param  array $names current list of names
	 *
	 * @return array
	 */
	function tribe_events_community_tickets_not_php_version_plugin_name( $names ) {
		$names['tribe-events-community-tickets'] = esc_html__( 'Community Events Tickets', 'tribe-events-community-tickets' );
		return $names;
	}

	add_filter( 'tribe_not_php_version_names', 'tribe_events_community_tickets_not_php_version_plugin_name' );
	if ( ! has_filter( 'admin_notices', 'tribe_not_php_version_notice' ) ) {
		add_action( 'admin_notices', 'tribe_not_php_version_notice' );
	}
	return false;
}

/**
 * Attempt to register this plugin.
 *
 * @since 4.6
 */
function tribe_register_community_tickets() {
	//remove action if we run this hook through common
	remove_action( 'plugins_loaded', 'tribe_register_community_tickets', 50 );

	// if we do not have a dependency checker then shut down
	if ( ! class_exists( 'Tribe__Abstract_Plugin_Register' ) ) {

		add_action( 'admin_notices', 'tribe_show_community_tickets_fail_message', 5 );
		add_action( 'network_admin_notices', 'tribe_show_community_tickets_fail_message', 5 );

		//prevent loading of PRO
		remove_action( 'tribe_common_loaded', 'tribe_events_community_tickets_init' );

		return;
	}

	add_filter( 'tribe_plugins_get_list', 'tribe_community_tickets_enable_requiring_woocommerce' );

	tribe_init_community_tickets_autoloading();

	new Tribe__Events__Community__Tickets__Plugin_Register();
}

add_action( 'tribe_common_loaded', 'tribe_register_community_tickets' );
// add action if Event Tickets or the Events Calendar is not active
add_action( 'plugins_loaded', 'tribe_register_community_tickets', 50 );

/**
 * Instantiate class and set up WordPress actions on Common Loaded
 *
 * @since 4.6
 */
add_action( 'tribe_common_loaded', 'tribe_events_community_tickets_init' );
function tribe_events_community_tickets_init() {
	$classes_exist = class_exists( 'Tribe__Events__Main' ) && class_exists( 'Tribe__Events__Community__Tickets__Main' );
	$plugins_check = function_exists( 'tribe_check_plugin' ) ?
		tribe_check_plugin( 'Tribe__Events__Community__Tickets__Main' )
		: false;
	$version_ok    = $classes_exist && $plugins_check;

	if ( class_exists( 'Tribe__Main' ) && ! is_admin() && ! file_exists( __DIR__ . '/src/Tribe/PUE/Helper.php' ) ) {
		tribe_main_pue_helper();
	}

	if ( ! $version_ok ) {
		if ( class_exists( 'Tribe__Abstract_Plugin_Register' ) ) {
			// if we have the plugin register, the dependency check will handle the notice
			new Tribe__Events__Community__Tickets__PUE( __FILE__ );
		} else {
			// have to do our own notice without Common's help
			add_action( 'admin_notices', 'tribe_show_community_tickets_fail_message' );
			add_action( 'network_admin_notices', 'tribe_show_community_tickets_fail_message' );
		}

		return;
	}

	tribe_singleton( 'community-tickets.main', new Tribe__Events__Community__Tickets__Main() );

	// Setup initial instance.
	//Tribe__Events__Community__Tickets__Main::instance();
}

/**
 * Filter the list of Tribe Common's list of plugins to check for dependency.
 *
 * @since 4.7.1
 *
 * @see   \Tribe__Plugins::get_list()
 * @see   \Tribe__Events__Community__Tickets__Plugin_Register::$dependencies
 *
 * @param array $common_plugins Contains a list of all plugins able to be checked for dependencies by Common.
 *
 * @return array
 */
function tribe_community_tickets_enable_requiring_woocommerce( $common_plugins ) {
	$common_plugins = (array) $common_plugins;

	$common_plugins[] = tribe_community_tickets_get_woocommerce_info_array();

	return $common_plugins;
}

/**
 * Get the array of plugin information used by Common's Dependency and elsewhere in this plugin.
 *
 * @since 4.7.1
 *
 * @return array
 */
function tribe_community_tickets_get_woocommerce_info_array() {
	/**
	 * Setting the path helps Tribe__Admin__Notice__Plugin_Download
	 *
	 * First check if already defined (i.e. if Woo is active).
	 * If not active, check if plugin file exists. If it does, we've confirmed it's there.
	 * If Woo files don't exist where expected, just send it anyway since Woo isn't active (based on define not being set).
	 */
	if ( defined( 'WC_PLUGIN_FILE' ) ) {
		$path = WC_PLUGIN_FILE;
	} else {
		$path = trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'woocommerce/woocommerce.php';
	}

	return [
		'short_name'   => 'WooCommerce',
		'class'        => 'WooCommerce',
		'path'         => $path,
		'thickbox_url' => 'plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true',
	];
}

/**
 * Requires the autoloader class from the main plugin class and sets up
 * autoloading.
 *
 * @since 4.6
 */
function tribe_init_community_tickets_autoloading() {
	if ( ! class_exists( 'Tribe__Autoloader' ) ) {
		return;
	}

	$autoloader = Tribe__Autoloader::instance();

	$autoloader->register_prefix( 'Tribe__Events__Community__Tickets__', dirname( __FILE__ ) . '/src/Tribe', 'events-community-tickets' );

	// deprecated classes are registered in a class to path fashion
	foreach ( glob( dirname( __FILE__ ) . '/src/deprecated/*.php' ) as $file ) {
		$class_name = str_replace( '.php', '', basename( $file ) );
		$autoloader->register_class( $class_name, $file );
	}

	$autoloader->register_autoloader();
}

/**
 * Loads language files and displays notice of missing requirements if Common is unavailable due to both
 * The Events Calendar (TEC) and Event Tickets (ET) not being active.
 *
 * Bonus, if you check against a function that is hooked to 'admin_notices' action, it will be unhooked to avoid notice
 * overkill, which is why this notice fires on priority 5 when lacking Common. This makes sense for Community Tickets
 * because it has the most requirements; we do not want both CT and CE to display a notice that says TEC is required.
 *
 * @since 4.6
 * @since 4.7.1 Added messaging for all the required plugins, not just TEC.
 * @see   \tribe_community_tickets_get_required_plugins_array()
 *
 */
function tribe_show_community_tickets_fail_message() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$mopath = trailingslashit( basename( dirname( __FILE__ ) ) ) . 'lang/';
	$domain = 'tribe-events-community-tickets';

	// If we don't have Common classes load the old fashioned way
	if ( ! class_exists( 'Tribe__Main' ) ) {
		load_plugin_textdomain( $domain, false, $mopath );
	} else {
		// This will load `wp-content/languages/plugins` files first
		Tribe__Main::instance()->load_text_domain( $domain, $mopath );
	}

	$missing_reqs = [];

	foreach ( tribe_community_tickets_get_required_plugins_array() as $requirement ) {
		$missing = false;

		// First check for Class, then Function
		if (
			! empty( $requirement['class'] )
			&& ! class_exists( $requirement['class'] )
		) {
			$missing = true;
		} elseif (
			! empty( $requirement['notice_function'] )
			&& ! function_exists( $requirement['notice_function'] )
		) {
			$missing = true;
		}

		if ( $missing ) {
			$css_class = $url = $target = '';

			if ( ! empty( $requirement['thickbox_url'] ) ) {
				$css_class = 'class="thickbox"';
				$url       = $requirement['thickbox_url'];
			} elseif ( ! empty( $requirement['external_url'] ) ) {
				$css_class = 'class="external-link"';
				$url       = $requirement['external_url'];
				$target    = 'target="_blank"';
			}

			$missing_reqs[] = sprintf(
				'<a href="%1$s" %2$s %3$s title="%4$s">%4$s</a>',
				esc_url( $url ),
				$css_class,
				$target,
				esc_html_x( $requirement['short_name'], 'list of lacking required plugins', 'tribe-events-community-tickets' )
			);
		}
	}

	/**
	 * @see \Tribe__Admin__Notice__Plugin_Download::implode_with_grammar() Logic came from here.
	 */
	$separator = _x( ', ', 'separator used in a list of items', 'tribe-events-community' );

	$conjunction = _x( ', and ', 'the final separator in a list of two or more items', 'tribe-events-community' );

	$missing_message = $last_item = array_pop( $missing_reqs );

	if ( $missing_reqs ) {
		$missing_message = implode( $separator, $missing_reqs ) . $conjunction . $last_item;
	}

	// Make sure Thickbox is available and consistent appearance regardless of which admin page we're on
	wp_enqueue_style( 'plugin-install' );
	wp_enqueue_script( 'plugin-install' );
	add_thickbox();

	printf(
		'<div class="error"><p>%1$s %2$s.</p></div>',
		esc_html_x( 'To begin using The Events Calendar: Community Tickets, please install and activate', 'list of lacking required plugins', 'tribe-events-community' ),
		$missing_message
	);
}

/**
 * Get the list of plugins required by Community Events Tickets.
 *
 * If used for displaying notice, plugins display in the order listed in the array.
 * Allows for either Class Exists or Function Exists to handle plugins that load their own class a bit later than
 * expected (e.g. Event Tickets Plus, Community Events).
 *
 * @since 4.7.1
 *
 * @see   \Tribe__Plugins::$tribe_plugins Inspired by but different from.
 *
 * @return array Valid keys: short_name, class, func, thickbox_url, external_url.
 */
function tribe_community_tickets_get_required_plugins_array() {
	$array = [
		[
			'short_name'   => 'The Events Calendar',
			'class'        => 'Tribe__Events__Main',
			'thickbox_url' => 'plugin-install.php?tab=plugin-information&plugin=the-events-calendar&TB_iframe=true',
		],
		[
			'short_name'   => 'Event Tickets',
			'class'        => 'Tribe__Tickets__Main',
			'thickbox_url' => 'plugin-install.php?tab=plugin-information&plugin=event-tickets&TB_iframe=true',
		],
		[
			'short_name'      => 'Event Tickets Plus',
			'notice_function' => 'event_tickets_plus_show_fail_message',
			'external_url'    => 'https://theeventscalendar.com/product/wordpress-event-tickets-plus/',
		],
		[
			'short_name'      => 'Community Events',
			'notice_function' => 'tribe_events_community_show_fail_message',
			'external_url'    => 'https://theeventscalendar.com/product/wordpress-community-events/',
		],
	];

	$array[] = tribe_community_tickets_get_woocommerce_info_array();

	return $array;
}
