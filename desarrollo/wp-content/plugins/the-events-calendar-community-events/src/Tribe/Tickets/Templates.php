<?php
/**
 * Templating functionality for Tribe Events Calendar
 */

// don't load directly
if ( ! defined('ABSPATH') ) {
	die('-1');
}

if ( class_exists( 'Tribe__Events__Community__Tickets__Templates' ) ) {
	return;
}

/**
 * Handle views and template files.
 */
class Tribe__Events__Community__Tickets__Templates {

	function __construct() {
		add_filter( 'tribe_events_template_paths', [ $this, 'add_community_tickets_template_paths' ] );
	}

	/**
	 * Filter template paths to add the community plugin to the queue
	 *
	 * @param array $paths
	 * @return array $paths
	 * @author Peter Chester
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function add_community_tickets_template_paths( $paths ) {
		$paths['community-tickets'] = tribe( 'community-tickets.main' )->plugin_path;
		return $paths;
	}
}
