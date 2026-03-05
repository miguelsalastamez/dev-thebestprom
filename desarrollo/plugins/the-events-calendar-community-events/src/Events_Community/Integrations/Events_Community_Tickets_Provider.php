<?php
/**
 * Integration between Community and Community Tickets after the merge of the two plugins.
 *
 * @since   5.0.0
 * @package TEC\Events_Community\Integrations
 */

namespace TEC\Events_Community\Integrations;

use TEC\Common\Integrations\Plugin_Merge_Provider_Abstract;
use Tribe__Autoloader;
use Tribe__Events__Community__Main;

/**
 * Class Events_Community_Tickets_Provider
 *
 * @since 5.0.0
 */
class Events_Community_Tickets_Provider extends Plugin_Merge_Provider_Abstract {
	/**
	 * Initializes the merged plugin.
	 *
	 * @since 5.0.0
	 */
	public function init_merged_plugin(): void {
		require_once EVENTS_COMMUNITY_DIR . '/src/deprecated/ticket-constants.php';
		require_once EVENTS_COMMUNITY_DIR . '/src/functions/tickets/tickets-loading.php';
		require_once EVENTS_COMMUNITY_DIR . '/src/functions/tickets/commerce/provider.php';

		$autoloader = Tribe__Autoloader::instance();
		$autoloader->register_prefix( 'TEC\\Community_Tickets\\', EVENTS_COMMUNITY_DIR . '/src/Community_Tickets' );
		$autoloader->register_prefix( 'Tribe\\Community\\Tickets', EVENTS_COMMUNITY_DIR . '/src/Tribe/Tickets' );

		tribe_register_community_tickets();
		tribe_events_community_tickets_init( true );
	}

	/**
	 * @inheritDoc
	 */
	public function get_merge_notice_slug(): string {
		return 'events-community-events-community-tickets-merge';
	}

	/**
	 * @inheritDoc
	 */
	public function get_merged_version(): string {
		return '5.0.0-dev';
	}

	/**
	 * @inheritDoc
	 */
	public function get_plugin_file_key(): string {
		return 'events-community-tickets/events-community-tickets.php';
	}

	/**
	 * @inheritDoc
	 */
	public function get_last_version_option_key(): string {
		return 'tribe-events-community-schema-version';
	}

	/**
	 * @inheritDoc
	 */
	public function get_child_plugin_text_domain(): string {
		return 'tribe-events-community-tickets';
	}

	/**
	 * @inheritDoc
	 */
	public function get_plugin_updated_name(): string {
		return sprintf(
			/* Translators: %1$s is the new version number. */
			_x(
				'Community Events to %1$s',
				'Plugin name upgraded to version number.',
				'tribe-events-community'
			),
			Tribe__Events__Community__Main::VERSION
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_updated_merge_notice_message(): string {
		return sprintf(
			/* Translators: %1$s is the plugin that was deactivated, %2$s is the plugin name, %3$s is the opening anchor tag, %4$s is the closing anchor tag. */
			_x(
				'%1$s has been deactivated as it\'s now bundled into the %2$s. %3$sLearn More%4$s',
				'Notice message for the forced deactivation of the Community Events Tickets plugin after updating Community Events to the merged version.',
				'tribe-events-community'
			),
			'Community Tickets',
			'Community Events',
			'<a target="_blank" href="https://evnt.is/1bdy">',
			'</a>'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_activating_merge_notice_message(): string {
		return sprintf(
			/* Translators: %1$s is the plugin name, %2$s is the plugin that was deactivated, %3$s is the opening anchor tag, %4$s is the closing anchor tag. */
			_x(
				'%1$s could not be activated. The %1$s functionality has been merged into %2$s. %3$sLearn More%4$s.',
				'Notice message for the forced deactivation of the Community Tickets plugin after attempting to activate, and the plugin was merged to the Community Events.',
				'tribe-events-community'
			),
			'Community Tickets',
			'Community Events',
			'<a target="_blank" href="https://evnt.is/1bdy">',
			'</a>'
		);
	}
}
