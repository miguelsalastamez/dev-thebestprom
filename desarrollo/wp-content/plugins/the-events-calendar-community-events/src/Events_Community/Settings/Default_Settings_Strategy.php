<?php
/**
 * Default Settings Handler when none is available.
 *
 * @since   5.0.0
 *
 * @package TEC\Events_Community\Settings
 */

namespace TEC\Events_Community\Settings;

/**
 * Default handler for settings operations when no specific settings class is available.
 *
 * This class serves as a fallback handler, ensuring that basic functionalities remain intact
 * and no fatal errors occur due to the absence of a specific settings class. It provides minimal
 * implementations necessary to maintain operational integrity without adding additional logic.
 */
class Default_Settings_Strategy {

	/**
	 * The settings page slug.
	 *
	 * This property stores the slug identifier for the main admin settings page of The Events Calendar,
	 * specifically for the Community settings. It is used primarily to construct URLs to this settings page.
	 *
	 * @var string
	 */
	public static $settings_page_id = 'events_community_settings';

	/**
	 * Retrieves the main admin settings URL.
	 *
	 * This method is intended to return the URL to the settings page for The Events Calendar's Community.
	 * It serves as a placeholder in this default handler, and typically, this method should be overridden
	 * in a specific settings handler class that provides complete functionality.
	 *
	 * @param array $args Optional. Additional query arguments to append to the settings URL.
	 *
	 * @return string The fully qualified URL to the admin settings page. Returns an empty string in this default
	 *     implementation.
	 */
	public function get_url( array $args = [] ) {
		return '';
	}
}
