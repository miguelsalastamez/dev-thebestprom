<?php
/**
 * Provides support for anonymous users of Community or any logged in user.
 *
 * @property-read array $venue_org_creation_caps
 */

namespace TEC\Events_Community\Integrations\Plugins\Events\Anonymous_Users;

use Tribe__Events__Community__Main;
use Tribe__Events__Main;

/**
 * Class Anonymous_Users_Logic
 *
 * @since 5.0.0
 */
class Anonymous_Users_Logic {
	/**
	 * Stores the values of any lazily generated properties.
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * Instance of Tribe__Events__Community__Main.
	 *
	 * @var Tribe__Events__Community__Main
	 */
	protected Tribe__Events__Community__Main $ce_main;

	/**
	 * Returns a callable for handling anonymous users.
	 *
	 * @since 5.0.0
	 *
	 * @return callable The callback function for handling anonymous users.
	 */
	public function handler(): callable {
		return tribe_callback( self::class, 'anonymous_users' );
	}

	/**
	 * Handles the logic for anonymous users.
	 *
	 * @since 5.0.0
	 *
	 * @param Tribe__Events__Community__Main $ce_main The main Community instance.
	 *
	 * @return void
	 */
	public function anonymous_users( Tribe__Events__Community__Main $ce_main ): void {
		$this->ce_main = $ce_main;
		if ( is_user_logged_in() || $ce_main->allowAnonymousSubmissions ) {
			$this->allow_venue_organizer_submissions();
			$this->allow_tribe_event_validation();
		}
	}

	/**
	 * Lazily returns certain properties that we may not be able to determine during
	 * instantiation (or may not require at all in many requests).
	 *
	 * @since 5.0.0
	 *
	 * @param string $property The property name.
	 *
	 * @return array The property value.
	 */
	public function __get( string $property ): array {
		if ( isset( $this->data[ $property ] ) ) {
			return $this->data[ $property ];
		}

		if ( 'venue_org_creation_caps' === $property ) {
			$this->data[ $property ] = [
				get_post_type_object( Tribe__Events__Main::VENUE_POST_TYPE )->cap->create_posts     => true,
				get_post_type_object( Tribe__Events__Main::ORGANIZER_POST_TYPE )->cap->create_posts => true,
			];
		}

		return $this->data[ $property ];
	}

	/**
	 * Provide anonymous users with the capabilities to create new venues and
	 * organizers, but only when required.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function allow_venue_organizer_submissions(): void {
		add_action( 'tribe_events_community_form', [ $this, 'add_venue_org_caps' ] );
		add_action( 'tribe_events_community_before_event_submission_page', [ $this, 'add_venue_org_caps' ] );
	}

	/**
	 * Sets up a capabilities filter so that users can submit venues and organizers.
	 * Intended for use with anonymous users.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function add_venue_org_caps(): void {
		add_filter( 'user_has_cap', [ $this, 'filter_venue_org_caps' ], 10, 3 );
	}

	/**
	 * Temporarily adds the venue and organizer create_posts capabilities to the list of
	 * those held by the current user.
	 *
	 * @since 5.0.0
	 *
	 * @param array $current_capabilities The current capabilities of the user.
	 * @param array $requested_capabilities The requested capabilities.
	 * @param array $args Additional arguments.
	 *
	 * @return array The modified capabilities array.
	 */
	public function filter_venue_org_caps( array $current_capabilities, array $requested_capabilities, array $args ): array {
		return $current_capabilities + $this->venue_org_creation_caps;
	}

	/**
	 * Various validation tests (if an organizer already exists, etc) run on a wp_ajax_*
	 * hook meaning they return a -1 result if we try to access them while the user is
	 * logged out.
	 *
	 * This hooks them up to the matching wp_ajax_nopriv_* hook so we get the expected
	 * result in the context of the frontend submission form.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function allow_tribe_event_validation(): void {
		add_action(
			'wp_ajax_nopriv_tribe_event_validation',
			[
				$this->ce_main::instance(),
				'ajax_form_validate',
			]
		);
	}
}
