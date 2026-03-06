<?php

/**
 * Adds screen options for the Payouts report view.
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts\Tabbed_View;

class Screen_Options {

	/**
	 * @var string The user option that will be used to store the number of orders per page to show.
	 */
	public static $per_page_user_option = 'events_community_tickets_payouts_per_page';

	/**
	 * Filters the save operations of screen options to save the ones the class manages.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param bool   $status Whether the option should be saved or not.
	 * @param string $option The user option slug.
	 * @param mixed  $value  The user option value.
	 *
	 * @return bool|mixed Either `false` if the user option is not one managed by the class or the user
	 *                    option value to save.
	 */
	public function filter_set_screen_options( $status, $option, $value ) {
		if ( $option === self::$per_page_user_option ) {
			return $value;
		}

		return $status;
	}
}
