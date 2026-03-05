<?php
/**
 * The Alerts settings tab.
 *
 * @since 5.0.4
 */

$tec_events_community_alerts_fields = [
	'tec-events-community-settings-alerts-title' => [
		'type' => 'html',
		'html' => '<h3 id="tec-events-community-settings-alerts" class="tec-settings-form__section-header">' . esc_html__( 'Alerts', 'tribe-events-community' ) . '</h3>',
	],
	'emailAlertsEnabled'                         => [
		'type'            => 'checkbox_bool',
		'label'           => __( 'Send an email alert when a new event is submitted', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'emailAlertsList'                            => [
		'type'            => 'textarea',
		'label'           => __( 'Email addresses to be notified', 'tribe-events-community' ),
		'default'         => get_option( 'admin_email' ),
		'tooltip'         => __( 'One address per line', 'tribe-events-community' ),
		'validation_type' => 'textarea',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
];

$alerts_tab = new Tribe__Settings_Tab(
	'community-alerts-tab',
	esc_html__( 'Alerts', 'tribe-events-community' ),
	[
		'priority' => 36.15,
		'fields'   => apply_filters( 'tec_events_community_settings_alerts_section', $tec_events_community_alerts_fields ),
	]
);

/**
 * Fires after the Alerts settings tab has been created.
 *
 * @since 5.0.4
 *
 * @param Tribe__Settings_Tab $viewing The Viewing settings tab.
 */
do_action( 'tec_events_community_settings_tab_alerts', $alerts_tab );

return $alerts_tab;
