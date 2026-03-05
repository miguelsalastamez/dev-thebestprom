<?php
/**
 * Tickets settings tab.
 * Subtab of the Community Tab.
 *
 * @since 5.0.4
 */

$settings = [];

$tickets_tab = new Tribe__Settings_Tab(
	'community-tickets-tab',
	esc_html__( 'Community Tickets', 'tribe-events-community' ),
	[
		'priority' => 36.25,
		'fields'   => apply_filters( 'tec_events_community_settings_tickets_section', $settings ),
	]
);

/**
 * Fires after the tickets settings tab has been created.
 *
 * @since 5.0.4
 *
 * @param Tribe__Settings_Tab $viewing The Viewing settings tab.
 */
do_action( 'tec_events_community_settings_tab_tickets', $tickets_tab );

return $tickets_tab;
