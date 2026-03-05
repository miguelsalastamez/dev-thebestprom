<?php

namespace TEC\Events_Community\Integrations\Plugins\Events;

use TEC\Common\Integrations\Traits\Plugin_Integration;
use TEC\Events_Community\Integrations\Plugin_Integration_Abstract;
use Tribe__Events__Main;

/**
 * Class Provider
 *
 * @since   5.0.0
 *
 * @package TEC\Events_Community\Integrations\Plugins\The_Events_Calendar
 */
class Controller extends Plugin_Integration_Abstract {
	use Plugin_Integration;

	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'the-events-calendar';
	}

	/**
	 * @inheritDoc
	 */
	public function load_conditionals(): bool {
		return did_action( 'tribe_events_bound_implementations' );
	}

	/**
	 * @inheritDoc
	 */
	protected function load(): void {
		$this->container->register( Settings\Controller::class );
		$this->container->register( Events\Controller::class );
		$this->container->register( Venues\Controller::class );
		$this->container->register( Organizers\Controller::class );
		$this->container->register( Anonymous_Users\Controller::class );
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
	 * Adds the actions required for the Settings Page.
	 *
	 * @since 5.0.0
	 */
	protected function add_actions(): void {
	}

	/**
	 * Adds the filters required for the Settings Page.
	 *
	 * @since 5.0.0
	 */
	protected function add_filters(): void {
		add_filter( 'tec_events_community_settings_content_creation_section', [ $this, 'add_defaults_header' ], 10 );

		// Override event label functions with tribe_get_event_label_* functions.
		add_filter( 'tec_events_community_event_label_singular', 'tribe_get_event_label_singular' );
		add_filter( 'tec_events_community_event_label_plural', 'tribe_get_event_label_plural' );
		add_filter( 'tec_events_community_event_label_singular_lowercase', 'tribe_get_event_label_singular_lowercase' );
		add_filter( 'tec_events_community_event_label_plural_lowercase', 'tribe_get_event_label_plural_lowercase' );
	}

	/**
	 * Add header section for defaults.
	 *
	 * @since 5.0.4
	 *
	 * @param  array $fields The fields for the settings page.
	 *
	 * @return array
	 */
	public function add_defaults_header( array $fields ): array {
		$fields['tec-events-community-settings-defaults-heading'] = [
			'type'        => 'html',
			'html'        => '<h3 id="tec-events-community-settings-defaults" class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . esc_html__( 'Form Defaults', 'tribe-events-community' ) . '</h3>',
			'conditional' => Tribe__Events__Main::instance()->get_venue_info() || Tribe__Events__Main::instance()->get_organizer_info(),
		];

		return $fields;
	}
}
