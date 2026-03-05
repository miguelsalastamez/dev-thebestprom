<?php
/**
 * Event Submission Cleaner
 *
 * @package TEC\Events_Community\Submission
 *
 * @since 5.0.0
 */

namespace TEC\Events_Community\Submission;

/**
 * Class Cleaner
 *
 * The Cleaner class is responsible for sanitizing and filtering content in event submissions.
 * It provides methods to filter strings and arrays of strings, and to customize the allowed HTML tags.
 *
 * This class uses various filters to determine the content that should be stripped or allowed,
 * based on user-specific and event-specific conditions.
 *
 * @package TEC\Events_Community\Submission
 *
 * @since 5.0.0
 */
class Cleaner {

	/**
	 * An array of content filters to apply to strings.
	 *
	 * @since 5.0.0
	 *
	 * @var array|null
	 */
	protected $filters = null;

	/**
	 * Filters a string through the registered content filters.
	 *
	 * @since 5.0.0
	 *
	 * @param string $string The string to be filtered.
	 *
	 * @return string The filtered string.
	 */
	public function filter_string( $string ): string {
		foreach ( $this->get_content_filters() as $callback ) {
			$string = call_user_func( $callback, $string );
		}
		return $string;
	}

	/**
	 * Filters an array of strings through the filter_string() method.
	 *
	 * This method iterates over an array of strings, applying the filter_string()
	 * method to each one to sanitize them according to the registered content filters.
	 *
	 * @since 5.0.0
	 *
	 * @param array $strings The array of strings to be filtered.
	 *
	 * @return array The array of filtered strings.
	 */
	public function filter_string_array( array $strings ): array {
		foreach ( $strings as &$single_string ) {
			$single_string = $this->filter_string( $single_string );
		}

		return $strings;
	}

	/**
	 * Retrieves the content filters to be applied to strings.
	 *
	 * This method initializes the content filters if they haven't been set yet,
	 * applying filters based on various conditions and user settings.
	 *
	 * @since 5.0.0
	 *
	 * @return array|null The array of content filter callbacks.
	 */
	protected function get_content_filters(): ?array {
		if ( ! isset( $this->filters ) ) {
			$this->filters = [];
			$user_id       = is_user_logged_in() ? wp_get_current_user()->ID : false;
			$event_id      = $this->submission['ID'] ?? false;

			/**
			 * Filter to determine whether to strip HTML from the content.
			 *
			 * @since 5.0.0
			 *
			 * @param bool     $strip_html Whether to strip HTML. Default true.
			 * @param int|bool $user_id The current user's ID, or false if not logged in.
			 * @param int|bool $event_id The event ID, or false for new events.
			 */
			$strip_html = apply_filters( 'tribe_events_community_submission_should_strip_html', true, $user_id, $event_id );

			/**
			 * Filter to determine whether to strip shortcodes from the content.
			 *
			 * @since 5.0.0
			 *
			 * @param bool     $strip_shortcodes Whether to strip shortcodes. Default false.
			 * @param int|bool $user_id The current user's ID, or false if not logged in.
			 * @param int|bool $event_id The event ID, or false for new events.
			 */
			$strip_shortcodes = apply_filters( 'tribe_events_community_submission_should_strip_shortcodes', false, $user_id, $event_id );

			// Determine whether to strip HTML.
			if ( $strip_html ) {
				$this->filters[] = 'wp_kses_post';
			}

			// Determine whether to strip shortcodes.
			if ( $strip_shortcodes ) {
				$this->filters[] = 'strip_shortcodes';
			}

			// Always strip slashes.
			$this->filters[] = 'stripslashes_deep';
		}

		return $this->filters;
	}

	/**
	 * Filters the allowed HTML tags for the wp_kses() sanitization of events submitted via the Community
	 * submission form.
	 *
	 * Modifies the allowed HTML tags for the wp_kses() function, removing certain tags such as 'form' and
	 * 'button'. It also provides a filter 'tribe_events_community_allowed_tags' to allow further customization of the
	 * allowed tags.
	 *
	 * @since 5.0.0
	 *
	 * @param array  $tags The array of HTML tags allowed through the wp_kses() filter.
	 * @param string $context The context for which the tags are being filtered.
	 *
	 * @return array The modified array of allowed HTML tags.
	 */
	public function filter_allowed_html_tags( $tags, $context ) {
		unset( $tags['form'] );
		unset( $tags['button'] );

		/**
		 * Allows filtering the allowed tags for the wp_kses() sanitization of events submitted via Community submission form.
		 *
		 * @since 5.0.0
		 *
		 * @param array $tags The array of HTML tags allowed through the wp_kses() filter.
		 */
		return apply_filters( 'tribe_events_community_allowed_tags', $tags, $context );
	}
}
