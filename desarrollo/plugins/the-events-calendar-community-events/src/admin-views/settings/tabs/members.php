<?php
/**
 * The Members settings tab.
 *
 * @since 5.0.4
 */

$trash_vs_delete_options = [
	'1' => __( 'Placed in the Trash', 'tribe-events-community' ),
	'0' => __( 'Permanently Deleted', 'tribe-events-community' ),
];

$tec_events_community_members_fields = [
	'tec-events-community-settings-members-title'   => [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__header-block">'
			. '<h3 id="tec-events-community-members__title" class="tec-settings-form__section-header">'
			. esc_html__( 'Members', 'tribe-events-community' )
			. '</h3>'
			. '<p class="description">'
			. esc_html__( 'Control the permissions for your logged in users.', 'tribe-events-community' )
			. '</p>'
			. '</div>',
	],
	'allowUsersToEditSubmissions'                   => [
		'type'            => 'checkbox_bool',
		'label'           => __( 'Edit their submissions', 'tribe-events-community' ),
		'tooltip'         => __( 'Users can edit their events, venues, and organizers', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'allowUsersToDeleteSubmissions'                 => [
		'type'            => 'checkbox_bool',
		'label'           => __( 'Remove their submissions', 'tribe-events-community' ),
		'tooltip'         => __( 'Users can delete their events', 'tribe-events-community' ),
		'default'         => false,
		'validation_type' => 'boolean',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'trashItemsVsDelete'                            => [
		'type'            => 'radio',
		'label'           => __( 'Deleted events should be:', 'tribe-events-community' ),
		'options'         => $trash_vs_delete_options,
		'default'         => '1',
		'validation_type' => 'options',
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'tec-events-community-settings-my-events-title' => [
		'type' => 'html',
		'html' => '<h3 id="tec-events-community-settings-my-events" class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . esc_html__( 'My Events', 'tribe-events-community' ) . '</h3>',
	],
	'eventsPerPage'                                 => [
		'type'            => 'text',
		'label'           => __( 'Events per page', 'tribe-events-community' ),
		'tooltip'         => __( 'This is the number of events displayed per page', 'tribe-events-community' ),
		'size'            => 'small',
		'default'         => 10,
		'validation_type' => 'positive_int',
	],
];

$members_tab = new Tribe__Settings_Tab(
	'community-members-tab',
	esc_html__( 'Members', 'tribe-events-community' ),
	[
		'priority' => 36.20,
		'fields'   => apply_filters( 'tec_events_community_settings_members_section', $tec_events_community_members_fields ),
	]
);

/**
 * Fires after the Members settings tab has been created.
 *
 * @since 5.0.4
 *
 * @param Tribe__Settings_Tab $viewing The Viewing settings tab.
 */
do_action( 'tec_events_community_settings_tab_members', $members_tab );

return $members_tab;
