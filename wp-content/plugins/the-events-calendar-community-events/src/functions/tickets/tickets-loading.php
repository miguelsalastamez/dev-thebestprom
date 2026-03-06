<?php
/**
 * Migrated to Community Tickets from Community Tickets.
 */

/**
 * Attempt to register this plugin.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 */
function tribe_register_community_tickets() {
	//remove action if we run this hook through common
	remove_action( 'plugins_loaded', 'tribe_register_community_tickets', 50 );

	// if we do not have a dependency checker then shut down.
	if ( ! class_exists( 'Tribe__Abstract_Plugin_Register' ) ) {
		return;
	}

	add_filter( 'tribe_plugins_get_list', 'tribe_community_tickets_enable_requiring_woocommerce' );

	new Tribe__Events__Community__Tickets__Plugin_Register();
}

/**
 * Instantiate class and set up WordPress actions on Common Loaded
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @param bool $load Whether to load the class or not.
 * @return bool
 */
function tribe_events_community_tickets_init( $load = false ) {
	$classes_exist = class_exists( 'Tribe__Events__Community__Tickets__Main', false );
	$plugins_check = function_exists( 'tribe_check_plugin' ) && tribe_check_plugin( 'Tribe__Events__Community__Tickets__Main' );
	$version_ok    = $classes_exist && $plugins_check;

	if ( ! $version_ok ) {
		return false;
	}

	tribe_events_community_tickets_load( $load );

	return true;
}

/**
 * Load the Community Tickets plugin.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @param bool $load Whether to load the class or not.
 * @return void
 */
function tribe_events_community_tickets_load( $load = true ) {
	if ( ! $load ) {
		return;
	}

	static $loaded = false;

	if ( $loaded ) {
		// Ensure it loads once no matter where called.
		return;
	}

	$loaded = true;
	tribe_singleton( 'community-tickets.main', Tribe__Events__Community__Tickets__Main::instance() );
}

/**
 * Filter the list of Tribe Common's list of plugins to check for dependency.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
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
 * @since 5.0.0 Migrated to Community from Community Tickets.
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
		$path = trailingslashit( dirname( plugin_dir_path( EVENTS_COMMUNITY_FILE ) ) ) . 'woocommerce/woocommerce.php';
	}

	return [
		'short_name'   => 'WooCommerce',
		'class'        => 'WooCommerce',
		'path'         => $path,
		'thickbox_url' => 'plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true',
	];
}
