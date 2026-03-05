<?php
/**
 * The Access Control settings tab.
 *
 * @since 5.0.4
 *
 * Note to editors: Due to the foreach at the end,
 * you do not have to set type on html entries
 * and you do not have to add a parent option to inputs - it's all done for you!
 */

// Set up roles.
$block_roles_list = tribe( 'community.main' )->getOption( 'blockRolesList' );

if ( empty( $block_roles_list ) ) {
	$block_roles_list = [];
}

$redirect_roles = [];

foreach ( get_editable_roles() as $role_name => $atts ) {
	// Don't let them lock admins out.
	if ( 'administrator' === $role_name ) {
		continue;
	}

	$redirect_roles[ $role_name ] = $atts['name'];
}

$tec_events_community_access_control_fields = [
	'tec-events-community-settings-access-control-title' => [
		'type' => 'html',
		'html' => '<h3 id="tec-events-community-settings-access-control" class="tec-settings-form__section-header">' . esc_html__( 'Access Control', 'tribe-events-community' ) . '</h3>',
	],
	'blockRolesFromAdmin'                                => [
		'type'            => 'checkbox_bool',
		'label'           => __( 'Block access to WordPress dashboard', 'tribe-events-community' ),
		'tooltip'         => __( 'Also disables the admin bar', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'blockRolesList'                                     => [
		'type'            => 'checkbox_list',
		'label'           => __( 'Roles to block', 'tribe-events-community' ),
		'default'         => [],
		'options'         => $redirect_roles,
		'validation_type' => 'options_multi',
		'tooltip'         => __( 'Check any roles listed to block access to the dashboard.', 'tribe-events-community' ),
		'can_be_empty'    => true,
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'blockRolesRedirect'                                 => [
		'type'            => 'text',
		'label'           => __( 'Redirect URL', 'tribe-events-community' ),
		'tooltip'         => sprintf(
			/* Translators: %1$s and %2$s are placeholders for the start and end of a link */
			__( 'Redirect for users attempting to access the admin without permissions<br>Enter an absolute or relative URL<br>Leave blank for the %1$sCommunity List View%2$s', 'tribe-events-community' ),
			'<a href="' . esc_url( tribe( 'community.main' )->getUrl( 'list' ) ) . '">',
			'</a>'
		),
		'default'         => '',
		'placeholder'     => tribe( 'community.main' )->getUrl( 'list' ),
		'validation_type' => 'url',
		'can_be_empty'    => true,
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'single_geography_mode'                              => [
		'type'            => 'checkbox_bool',
		'label'           => __( 'Single geography mode', 'tribe-events-community' ),
		'tooltip'         => __( 'Removes the country, state/province and timezone selectors from the submission form', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
];

$access_control_tab = new Tribe__Settings_Tab(
	'community-access-control-tab',
	esc_html__( 'Access Control', 'tribe-events-community' ),
	[
		'priority' => 36.30,
		'fields'   => apply_filters( 'tec_events_community_settings_access_control_section', $tec_events_community_access_control_fields ),
	]
);

/**
 * Fires after the Access Control settings tab has been created.
 *
 * @since 5.0.4
 *
 * @param Tribe__Settings_Tab $viewing The Viewing settings tab.
 */
do_action( 'tec_events_community_settings_tab_alerts', $access_control_tab );

return $access_control_tab;
