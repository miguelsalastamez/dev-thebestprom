<?php
/**
 * The URLs settings tab.
 *
 * @since 5.0.4
 */

$tce = tribe( 'community.main' );

// Settings for the Community rewrite slugs.
$community_rewrite_slug_settings = [
	'communityRewriteSlug'     => [
		'type'            => 'text',
		'label'           => __( 'Community rewrite slug', 'tribe-events-community' ),
		'validation_type' => 'slug',
		'size'            => 'medium',
		'default'         => 'community',
		'tooltip'         => __(
			'The slug used for building the Community URL - it is appended to the main events slug.',
			'tribe-events-community'
		),
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'community-add-slug'       => [
		'type'            => 'text',
		'label'           => __( 'Add slug', 'tribe-events-community' ),
		'validation_type' => 'slug',
		'size'            => 'medium',
		'default'         => $tce->get_default_rewrite_slugs( 'add' ),
		'tooltip'         => __(
			'The slug used for building the community "add event" URL - it is appended to the main Community slug.',
			'tribe-events-community'
		),
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'community-list-slug'      => [
		'type'            => 'text',
		'label'           => __( 'List slug', 'tribe-events-community' ),
		'validation_type' => 'slug',
		'size'            => 'medium',
		'default'         => $tce->get_default_rewrite_slugs( 'list' ),
		'tooltip'         => __(
			'The slug used for building the community "events list" URL - it is appended to the main Community slug.',
			'tribe-events-community'
		),
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'community-edit-slug'      => [
		'type'            => 'text',
		'label'           => __( 'Edit slug', 'tribe-events-community' ),
		'validation_type' => 'slug',
		'size'            => 'medium',
		'default'         => $tce->get_default_rewrite_slugs( 'edit' ),
		'tooltip'         => __(
			'The slug used for building the community "edit event" URL - it is appended to the main Community slug.',
			'tribe-events-community'
		),
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'community-venue-slug'     => [
		'type'            => 'text',
		'label'           => __( 'Venue slug', 'tribe-events-community' ),
		'validation_type' => 'slug',
		'size'            => 'medium',
		'default'         => $tce->get_default_rewrite_slugs( 'venue' ),
		'tooltip'         => __(
			'The slug used for building the community "edit venue" URL - it is appended to the main Community slug.',
			'tribe-events-community'
		),
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'community-organizer-slug' => [
		'type'            => 'text',
		'label'           => __( 'Organizer slug', 'tribe-events-community' ),
		'validation_type' => 'slug',
		'size'            => 'medium',
		'default'         => $tce->get_default_rewrite_slugs( 'organizer' ),
		'tooltip'         => __(
			'The slug used for building the community "edit organizer" URL - it is appended to the main Community slug.',
			'tribe-events-community'
		),
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
	'community-event-slug'     => [
		'type'            => 'text',
		'label'           => __( 'Event slug', 'tribe-events-community' ),
		'validation_type' => 'slug',
		'size'            => 'medium',
		'default'         => $tce->get_default_rewrite_slugs( 'event' ),
		'tooltip'         => __(
			'The slug used for building the community "edit event" URL - it is appended to the main Community slug.',
			'tribe-events-community'
		),
		'parent_option'   => Tribe__Events__Community__Main::OPTIONNAME,
	],
];

// Settings will be displayed conditionally upon $base_url empty or not.
$base_url = ( '' === get_option( 'permalink_structure' ) ) ? '' : trailingslashit( trailingslashit( home_url() ) . $tce->getCommunityRewriteSlug() );

// Auto-check the rewrites checkbox upon page load if existing customizations exist.
$checked = '';

foreach ( (array) $tce::getOptions() as $key => $value ) {
	if (
		! empty( $value )
		&& array_key_exists( $key, $community_rewrite_slug_settings )
	) {
		$checked = checked( true, true, false );
		break;
	}
}

// @todo redscar - It would be great to make this dynamic based off of the routes that are created instead of hardcoding them.
$base_urls = [
	[
		'name'  => 'Community base',
		'url'   => $base_url,
		'order' => 1,
	],
	[
		'name'  => 'List events',
		'url'   => $base_url . sanitize_title( $tce->get_rewrite_slug( 'list' ) ),
		'order' => 2,
	],
	[
		'name'  => 'Add event',
		'url'   => $base_url . sanitize_title( $tce->get_rewrite_slug( 'add' ) ),
		'order' => 3,
	],
];

$edit_urls = [
	[
		'name'  => 'Edit event',
		'url'   => trailingslashit( $base_url ) . trailingslashit( sanitize_title( $tce->get_rewrite_slug( 'edit' ) ) ) . sanitize_title( $tce->get_rewrite_slug( 'event' ) ),
		'order' => 1,
	],
];

/**
 * Filters the array of base community URLs for further customization.
 *
 * @since 5.0.0
 *
 * @param array  $base_urls Array of base URLs and their details.
 * @param string $base_url The base URL used by Community.
 *
 * @return array Filtered array of base URLs.
 */
$base_urls = apply_filters( 'tribe_community_settings_base_urls', $base_urls, $base_url );

/**
 * Filters the array of community edit URLs that require an ID appended for further customization.
 *
 * @since 5.0.0
 *
 * @param array  $edit_urls Array of edit URLs and their details.
 * @param string $base_url The base URL used by Community.
 *
 * @return array Filtered array of edit URLs.
 */
$edit_urls = apply_filters( 'tribe_community_settings_edit_urls', $edit_urls, $base_url );

$current_base_url_display = '';
foreach ( $base_urls as $url ) {
	$current_base_url_display .= sprintf(
		'<dl><dt>%s:</dt> <dd><code>%s</code></dd></dl>',
		esc_html( $url['name'] ),
		trailingslashit( $url['url'] )
	);
}

$current_edit_url_display = '';
foreach ( $edit_urls as $url ) {
	$current_edit_url_display .= sprintf(
		'<dl><dt>%s:</dt> <dd><code>%s</code></dd></dl>',
		esc_html( $url['name'] ),
		trailingslashit( $url['url'] )
	);
}

// Generate the rewrite fields with the box checked if applicable.
$rewrite_fields = [
	'tec-events-community-settings-urls-title' => [
		'type' => 'html',
		'html' => '<div class="tec-settings-form__header-block">'
			. '<h3 id="tec-events-community-urls__title" class="tec-settings-form__section-header">'
			. esc_html__( 'Community URLs', 'tribe-events-community' )
			. '</h3>'
			. '<p class="description">'
			. sprintf(
				'<p>%1$s<br><i>%2$s</i></p>',
				esc_html__( 'Edit the default URLs for Community Pages.', 'tribe-events-community' ),
				esc_html__(
					'Note that these slugs are not translatable. Please write them in the correct language for your site.',
					'tribe-events-community'
				)
			)
			. '</p>'
			. '</div>',
	],
	'rewrite-notice-unprettyPermalinks'        => [
		'type'        => 'wrapped_html',
		'label'       => esc_html__( 'Cannot be set!', 'tribe-events-community' ),
		'conditional' => ! $base_url,
		'html'        => '<p>'
			. sprintf(
				/* translators: %1$s is the shortcode %2$s is the url to the Permalinks Settings */
				_x(
					'Community requires non-default (pretty) Permalinks to be enabled or the <code>%1$s</code> shortcode to exist on a post or page.<br><br>You cannot edit Community slugs for your events pages as you do not have pretty Permalinks enabled. In order to edit the slugs here, first <a href="%2$s">enable pretty Permalinks</a>.',
					'Pretty permalinks error for URL slugs',
					'tribe-events-community'
				),
				'[tribe_community_events]',
				esc_url( trailingslashit( get_admin_url() ) . 'options-permalink.php' )
			)
			. '</p>',
	],
	'current_urls'                             => [
		'type'            => 'wrapped_html',
		'size'            => 'medium',
		'validation_type' => 'html',
		'conditional'     => $base_url,
		'html'            => sprintf(
			'
			%1$s
			%2$s
			<p><input type="checkbox" class="tribe-accordion" id="events-community-rewrite-slugs-toggle" %3$s>
			<label for="events-community-rewrite-slugs-toggle">%4$s</label></p>
			',
			$current_base_url_display,
			$current_edit_url_display,
			$checked,
			esc_html__( 'Edit URL Slugs (unchecked clears all customizations)', 'tribe-events-community' )
		),
	],
	'community-rewrite-slug-settings-start'    => [
		'html' => '<div id="tribe-events-community-tickets-rewrite-slug-settings" class="tribe-dependent" data-depends="#events-community-rewrite-slugs-toggle" data-condition-is-checked>',
	],
];

$rewrite_fields += $community_rewrite_slug_settings;

$rewrite_fields['community-rewrite-slug-settings-end'] = [
	'html' => '</div>',
];

foreach ( $rewrite_fields as $name => $setting ) {
	// If it doesn't have a type, assume it is html.
	if ( empty( $setting['type'] ) ) {
		$setting['type'] = 'html';
	}

	// Skip non-field "settings".
	if ( in_array( $setting['type'], [ 'html', 'wrapped_html', 'heading' ], true ) ) {
		continue;
	}

	$setting['class']        = 'light-bordered full-width';
	$setting['can_be_empty'] = true;

	$existing_field_attributes = Tribe__Utils__Array::get( $setting, 'fieldset_attributes', [] );

	$additional_attributes = [
		'data-depends'              => '#events-community-rewrite-slugs-toggle',
		'data-condition-is-checked' => '',
	];

	$setting['fieldset_attributes'] = array_merge( $existing_field_attributes, $additional_attributes );

	$setting['validate_if'] = new Tribe__Field_Conditional( 'events-community-rewrite-slugs-toggle', 'tribe_is_truthy' );

	$rewrite_fields[ $name ] = $setting;
}

$urls_tab = new Tribe__Settings_Tab(
	'community-urls-tab',
	esc_html__( 'Community URLs', 'tribe-events-community' ),
	[
		'priority' => 36.10,
		'fields'   => apply_filters( 'tec_events_community_settings_urls_section', $rewrite_fields ),
	]
);

/**
 * Fires after the URL settings tab has been created.
 *
 * @since 5.0.4
 *
 * @param Tribe__Settings_Tab $viewing The Viewing settings tab.
 */
do_action( 'tec_events_community_settings_tab_urls', $urls_tab );

return $urls_tab;
