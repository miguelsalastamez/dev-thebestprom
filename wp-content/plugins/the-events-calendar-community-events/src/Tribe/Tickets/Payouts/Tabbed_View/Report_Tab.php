<?php

/**
 * Payouts report tab object.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts\Tabbed_View;

class Report_Tab extends \Tribe__Tabbed_View__Tab {

	/**
	 * @var bool
	 */
	protected $visible = true;

	/**
	 * Gets the Tabbed View slug
	 * @return void
	 */
	public function get_slug() {
		return Report::$tab_slug;
	}

	/**
	 * Gets the Tabbed View label
	 */
	public function get_label() {
		return __( 'Payouts', 'tribe-events-community' );
	}
}
