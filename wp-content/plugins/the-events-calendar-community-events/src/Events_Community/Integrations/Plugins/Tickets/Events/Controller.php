<?php

namespace TEC\Events_Community\Integrations\Plugins\Tickets\Events;

use TEC\Events_Community\Integrations\Plugin_Integration_Abstract;
use TEC\Common\Integrations\Traits\Module_Integration;
use Tribe__Events__Community__Templates;

/**
 * Class Provider
 *
 * @since 5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\Tickets\Events
 */
class Controller extends Plugin_Integration_Abstract {
	use Module_Integration;

	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'event-tickets-event-logic';
	}

	/**
	 * @inheritDoc
	 */
	public function load_conditionals(): bool {
		// If ET is enabled, we always want to load this logic.
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function load(): void {
		// Register the Service Provider for Hooks.
		$this->register_hooks();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider.
	 *
	 * @since 5.0.0
	 */
	protected function register_hooks(): void {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required for the Events Page.
	 *
	 * @since 5.0.0
	 */
	protected function add_actions(): void {
	}

	/**
	 * Adds the filters required for the Events Page.
	 *
	 * @since 5.0.0
	 */
	protected function add_filters(): void {
		add_filter( 'tec_events_community_submission_save_handler', [ $this, 'custom_save_handler' ] );
		add_filter( 'tribe_community_events_list_columns', [ $this, 'remove_date_columns' ] );
		add_filter( 'tec_events_community_events_listing_show_prev_next_nav', '__return_false' );
		add_filter( 'tec_events_community_events_listing_display_options_dropdown', '__return_false' );
		add_filter( 'tec_events_community_email_alert_template_path', [ $this, 'override_email_alert_template_path' ] );
	}

	/**
	 * Custom save handler for the event.
	 *
	 * This method retrieves the event handler from the container and returns its handler.
	 *
	 * @since 5.0.0
	 *
	 * @return callable The callback function for saving the event.
	 */
	public function custom_save_handler(): callable {
		return $this->container->make( Event_Handler::class )->handler();
	}

	/**
	 * Removes the Start and End date from the columns array.
	 *
	 * @since 5.0.0
	 *
	 * @param array $columns The original columns array.
	 *
	 * @return array The modified columns array with the Start and End Date columns removed.
	 */
	public function remove_date_columns( $columns ) {
		unset( $columns['start_date'], $columns['end_date'] );
		return $columns;
	}

	/**
	 * Override the email template path to use ET's template.
	 *
	 * @since 5.0.7
	 *
	 * @param string $template_path The default template path.
	 *
	 * @return string The ET template path.
	 */
	public function override_email_alert_template_path( string $template_path ): string {
		$et_template = Tribe__Events__Community__Templates::getTemplateHierarchy( 'integrations/event-tickets/email-template' );

		return ! empty( $et_template ) && file_exists( $et_template ) ? $et_template : $template_path;
	}
}
