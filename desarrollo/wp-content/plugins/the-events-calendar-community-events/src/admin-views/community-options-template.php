<?php

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$info_fields = [
	'info-start'           => [
		'html' => '<div id="modern-tribe-info">',
	],
	'info-box-title'       => [
		'html' => '<h3>' . __( 'Community Settings', 'tribe-events-community' ) . '</h3>',
	],
	'info-box-description' => [
		'html' =>
			sprintf(
				__( '<p>Community enables users to submit events through a form on your site. Whether soliciting contributions from anonymous users or registered members, you have complete editorial control over what makes it onto the calendar.</p><p>Check out our <a href="%s">Community New User Primer</a> for information on configuring and using the plugin.', 'tribe-events-community' ),
				  'https://evnt.is/cestart/?utm_campaign=in-app&utm_medium=plugin-community&utm_source=communitytab')
	],
	'info-end'             => [
		'html' => '</div>',
	],
];

/**
 * Allow for customization of the array of out-of-the-box Community settings.
 * To add additional fields, append them to the 'fields' array.
 *
 * @since 1.0
 * @deprecated 5.0.4
 *
 * @param array $community_tab
 */
apply_filters_deprecated(
	'tribe_community_settings_tab',
	[ $info_fields ],
	'5.0.4',
	'no direct replacement',
	'The Community settings are separated into sections now. The fields for each section can be filtered separately.'
);

$community_tab = new Tribe__Settings_Tab(
	'community',
	esc_html_x( 'Community', 'Label for the Community tab.', 'tribe-events-community' ),
	[
		'priority'      => 36,
		'fields'        => [],
		'network_admin' => is_network_admin(),
	]
);


// Add each of the sub-tabs.
$content_creation_tab = require_once __DIR__ . '/settings/tabs/content-creation.php';
$community_tab->add_child( $content_creation_tab );

$urls_tab = require_once __DIR__ . '/settings/tabs/urls.php';
$community_tab->add_child( $urls_tab );

$alerts_tab = require_once __DIR__ . '/settings/tabs/alerts.php';
$community_tab->add_child( $alerts_tab );

$members_tab = require_once __DIR__ . '/settings/tabs/members.php';
$community_tab->add_child( $members_tab );

if ( class_exists( Tribe__Tickets__Main::class, false ) ) {
	$tickets_tab = require_once __DIR__ . '/settings/tabs/tickets.php';
	$community_tab->add_child( $tickets_tab );
}

$access_control_tab = require_once __DIR__ . '/settings/tabs/access-control.php';
$community_tab->add_child( $access_control_tab );

do_action( 'tec_events_settings_tab_general', $community_tab );

return $community_tab;
