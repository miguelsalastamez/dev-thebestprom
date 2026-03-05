<?php
if ( ! function_exists( 'tribe_is_community_my_events_page' ) ) {
	/**
	 * Tests if the current page is the My Events page
	 *
	 * @since 1.0.1
	 * @since 4.10.0 Updated logic to check route, instead of relying on variable.
	 *
	 * @return bool whether it is the My Events page.
	 */
	function tribe_is_community_my_events_page() {
		$wp_route = get_query_var( 'WP_Route' );

		return ! empty( $wp_route ) && 'ce-list-route' === $wp_route;
	}
}

if ( ! function_exists( 'tribe_is_community_edit_event_page' ) ) {
	/**
	 * Tests if the current page is the Edit Event page
	 *
	 * @since 1.0.1
	 * @since 4.10.0 Updated logic to check route, instead of relying on variable.
	 *
	 * @return bool whether it is the Edit Event page.
	 */
	function tribe_is_community_edit_event_page() {
		$wp_route = get_query_var( 'WP_Route' );

		return ! empty( $wp_route ) && in_array( $wp_route, [ 'ce-add-route', 'ce-edit-route' ], true );
	}
}
if ( ! function_exists( 'tribe_separated_field' ) ) {
	/**
	 * Utility function to compile separated lists.
	 *
	 * @since 3.1
	 *
	 * @param string $body Body.
	 * @param string $separator Separator.
	 * @param string $field Field.
	 *
	 * @return string
	 */
	function tribe_separated_field( $body, $separator, $field ) {
		$body_and_separator = $body ? $body . $separator : $body;

		return $field ? $body_and_separator . $field : $body;
	}
}
/**
 * Echo the Community form title field
 *
 * @since 3.1
 */
function tribe_community_events_form_title() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	$community->formTitle();
}

/**
 * Echo the Community form content editor
 *
 * @since 3.1
 */
function tribe_community_events_form_content() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	$community->formContentEditor();
}

/**
 * Echo the Community form image delete button
 *
 * @since 3.1
 */
function tribe_community_events_form_image_delete() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	echo $community->getDeleteFeaturedImageButton();
}

/**
 * Echo the Community form image preview
 *
 * @since 3.1
 */
function tribe_community_events_form_image_preview() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	echo $community->getDeleteFeaturedImageButton();
}

/**
 * Echo the Community form currency symbol
 *
 * @since 3.1
 */
function tribe_community_events_form_currency_symbol() {
	if ( get_post() ) {
		$EventCurrencySymbol = get_post_meta( get_the_ID(), '_EventCurrencySymbol', true );
	}

	if ( ! isset( $EventCurrencySymbol ) || ! $EventCurrencySymbol ) {
		$EventCurrencySymbol = isset( $_POST['EventCurrencySymbol'] ) ? $_POST['EventCurrencySymbol'] : tribe_get_option( 'defaultCurrencySymbol', '$' );
	}

	echo esc_attr( $EventCurrencySymbol );
}

/**
 * Return URL for adding a new event.
 *
 * @since 3.1
 *
 * @return string The URL for adding a new event.
 */
function tribe_community_events_add_event_link() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	$url = $community->getUrl( 'add' );

	return apply_filters( 'tribe-community-events-add-event-link', $url );
}

/**
 * Return URL for listing events.
 *
 * @since 3.1
 *
 * @return string The URL for listing events.
 */
function tribe_community_events_list_events_link() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	$url = $community->getUrl( 'list' );

	return apply_filters( 'tribe-community-events-list-events-link', $url );
}

/**
 * Return URL for editing an event.
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 *
 * @return string The URL for editing an event.
 */
function tribe_community_events_edit_event_link( $event_id = null ) {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	$url = $community->getUrl( 'edit', $event_id );

	return apply_filters( 'tribe-community-events-edit-event-link', $url, $event_id );
}

/**
 * Return URL for deleting an event.
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 *
 * @return string The URL for deleting an event.
 */
function tribe_community_events_delete_event_link( $event_id = null ) {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	$url = $community->getUrl( 'delete', $event_id );

	return apply_filters( 'tribe-community-events-delete-event-link', $url, $event_id );
}

/**
 * Return the event start date on the Community submission form with a default of today.
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 * @return string event date
 */
function tribe_community_events_get_start_date( $event_id = null ) {
	$event_id          = Tribe__Events__Main::postIdHelper( $event_id );
	$event             = ( $event_id ) ? get_post( $event_id ) : null;
	$datepicker_format = Tribe__Date_Utils::datepicker_formats( tribe_get_option( 'datepickerFormat' ) );

	$date = tribe_get_start_date( $event, false, $datepicker_format );
	$date = $date ? $date : date_i18n( $datepicker_format );

	/**
	 * Filter the event start date value on the Community submission form.
	 *
	 * @param string $date The event start date
	 * @param int|null $event_id The ID of this event, or null
	 */
	return apply_filters( 'tribe_community_events_get_start_date', $date, $event_id );
}

/**
 * Return the event end date on the Community submission form with a default of today.
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 * @return string event date
 */
function tribe_community_events_get_end_date( $event_id = null ) {
	$event_id          = Tribe__Events__Main::postIdHelper( $event_id );
	$event             = ( $event_id ) ? get_post( $event_id ) : null;
	$datepicker_format = Tribe__Date_Utils::datepicker_formats( tribe_get_option( 'datepickerFormat' ) );

	$date = tribe_get_end_date( $event, false, $datepicker_format );
	$date = $date ? $date : date_i18n( $datepicker_format );

	/**
	 * Filter the event end date value on the Community submission form.
	 *
	 * @param string $date The event end date
	 * @param int|null $event_id The ID of this event, or null
	 */
	return apply_filters( 'tribe_community_events_get_end_date', $date, $event_id );
}

/**
 * Return true if event is an all day event.
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 * @return bool event date
 */
function tribe_community_events_is_all_day( $event_id = null ) {
	$event_id = Tribe__Events__Main::postIdHelper( $event_id );
	$is_all_day = tribe_event_is_all_day( $event_id );
	$is_all_day = ( $is_all_day == 'Yes' || $is_all_day == true );
	return apply_filters( 'tribe_community_events_is_all_day', $is_all_day, $event_id );
}

/**
 * Return form select fields for event start time.
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 * @return string time select HTML
 */
function tribe_community_events_form_start_time_selector( $event_id = null ) {

	$event_id = Tribe__Events__Main::postIdHelper( $event_id );
	$is_all_day = tribe_event_is_all_day( $event_id );

	$start_date = null;

	if ( $event_id ) {
		$start_date = tribe_get_start_date( $event_id, true, Tribe__Date_Utils::DBDATETIMEFORMAT );
	}

	$start_minutes 	= Tribe__View_Helpers::getMinuteOptions( $start_date, true );
	$start_hours = Tribe__View_Helpers::getHourOptions( $is_all_day == 'yes' ? null : $start_date, true );
	$start_meridian = Tribe__View_Helpers::getMeridianOptions( $start_date, true );

	$output = '';
	$output .= sprintf( '<select name="EventStartHour" class="tribe-dropdown">%s</select>', $start_hours );
	$output .= sprintf( '<select name="EventStartMinute" class="tribe-dropdown">%s</select>', $start_minutes );
	if ( ! tribe_community_events_use_24hr_format() ) {
		$output .= sprintf( '<select name="EventStartMeridian" class="tribe-dropdown">%s</select>', $start_meridian );
	}
	return apply_filters( 'tribe_community_events_form_start_time_selector', $output, $event_id );
}

/**
 * Return form select fields for event end time.
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 * @return string time select HTML
 */
function tribe_community_events_form_end_time_selector( $event_id = null ) {

	$event_id = Tribe__Events__Main::postIdHelper( $event_id );
	$is_all_day = tribe_event_is_all_day( $event_id );
	$end_date = null;

	if ( $event_id ) {
		$end_date = tribe_get_end_date( $event_id, true, Tribe__Date_Utils::DBDATETIMEFORMAT );
	}

	$end_minutes = Tribe__View_Helpers::getMinuteOptions( $end_date );
	$end_hours = Tribe__View_Helpers::getHourOptions( $is_all_day == 'yes' ? null : $end_date );
	$end_meridian = Tribe__View_Helpers::getMeridianOptions( $end_date );

	$output = '';
	$output .= sprintf( '<select name="EventEndHour" class="tribe-dropdown">%s</select>', $end_hours );
	$output .= sprintf( '<select name="EventEndMinute" class="tribe-dropdown">%s</select>', $end_minutes );
	if ( ! tribe_community_events_use_24hr_format() ) {
		$output .= sprintf( '<select name="EventEndMeridian" class="tribe-dropdown">%s</select>', $end_meridian );
	}
	return apply_filters( 'tribe_community_events_form_end_time_selector', $output, $event_id );
}

/**
 * Get the error or notice messages for a given form result.
 *
 * @since 3.1
 *
 * @return string error/notice HTML
 */
function tribe_community_events_get_messages() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	return $community->outputMessage( null, false );
}

/********************** ORGANIZER TEMPLATE TAGS **********************/

/**
 * Echo Organizer edit form contents
 *
 * @since 3.1
 *
 * @param int|null $organizer_id The organizer ID.
 */
function tribe_community_events_organizer_edit_form( $organizer_id = null ) {
	if ( $organizer_id ) {
		$post = get_post( $organizer_id );
		$saved = false;

		if ( isset( $post->post_type ) && $post->post_type == Tribe__Events__Main::ORGANIZER_POST_TYPE ) {

			$postId = $post->ID;

			$saved = ( ( is_admin() && isset( $_GET['post'] ) && $_GET['post'] ) || ( ! is_admin() && isset( $postId ) ) );

			// Generate all the inline variables that apply to Organizers
			$organizer_vars = Tribe__Events__Main::instance()->organizerTags;
			foreach ( $organizer_vars as $var ) {
				if ( $postId && $saved ) { //if there is a post AND the post has been saved at least once.
					$$var = get_post_meta( $postId, $var, true );
				}
			}
		}
		$meta_box_template = apply_filters( 'tribe_events_organizer_meta_box_template', '' );
		if ( ! empty( $meta_box_template ) ) {
			include( $meta_box_template );
		}
	}
}

/**
 * Echo Organizer select menu
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 */
function tribe_community_events_organizer_select_menu( $event_id = null ) {
	if ( ! $event_id ) {
		global $post;
		if ( isset( $post->post_type ) && $post->post_type == Tribe__Events__Main::POSTTYPE ) {
			$event_id = $post->ID;
		} elseif ( isset( $post->post_type ) && $post->post_type == Tribe__Events__Main::ORGANIZER_POST_TYPE ) {
			return;
		}
	}
	do_action( 'tribe_organizer_table_top', $event_id );
}

/**
 * Test to see if this is the Organizer edit screen
 *
 * @since 3.1
 *
 * @param int|null $organizer_id The organizer ID.
 * @return bool
 */
function tribe_community_events_is_organizer_edit_screen( $organizer_id = null ) {
	$organizer_id = Tribe__Events__Main::postIdHelper( $organizer_id );
	$is_organizer = ( $organizer_id ) ? Tribe__Events__Main::instance()->isOrganizer( $organizer_id ) : false;
	return apply_filters( 'tribe_is_organizer', $is_organizer, $organizer_id );
}

/**
 * Return Organizer Description
 *
 * @since 3.1
 *
 * @param int|null $organizer_id The organizer ID.
 * @return string
 */
function tribe_community_events_get_organizer_description( $organizer_id = null ) {
	$organizer_id = tribe_get_organizer_id( $organizer_id );
	$description = ( $organizer_id > 0 ) ? get_post( $organizer_id )->post_content : null;
	return apply_filters( 'tribe_get_organizer_description', $description );
}

/********************** VENUE TEMPLATE TAGS **********************/

/**
 * Echo Venue edit form contents
 *
 * @since 3.1
 *
 * @param int|null $venue_id The venue ID.
 */
function tribe_community_events_venue_edit_form( $venue_id = null ) {
	if ( $venue_id ) {
		$post = get_post( $venue_id );
		$saved = false;

		if ( isset( $post->post_type ) && $post->post_type == Tribe__Events__Main::VENUE_POST_TYPE ) {

			$postId = $post->ID;

			$saved = ( ( is_admin() && isset( $_GET['post'] ) && $_GET['post'] ) || ( ! is_admin() && isset( $postId ) ) );

			// Generate all the inline variables that apply to Venues
			$venue_vars = Tribe__Events__Main::instance()->venueTags;
			foreach ( $venue_vars as $var ) {
				if ( $postId && $saved ) { //if there is a post AND the post has been saved at least once.
					$$var = get_post_meta( $postId, $var, true );
				}
			}
		}

		$meta_box_template = apply_filters( 'tribe_events_venue_meta_box_template', '' );
		if ( ! empty( $meta_box_template ) ) {
			include( $meta_box_template );
		}
	}
}

/**
 * Echo Venue select menu
 *
 * @since 3.1
 *
 * @param int|null $event_id The event ID.
 */
function tribe_community_events_venue_select_menu( $event_id = null ) {
	if ( ! $event_id ) {
		global $post;
		if ( isset( $post->post_type ) && $post->post_type == Tribe__Events__Main::POSTTYPE ) {
			$event_id = $post->ID;
		} elseif ( isset( $post->post_type ) && $post->post_type == Tribe__Events__Main::VENUE_POST_TYPE ) {
			return;
		}
	}


	do_action( 'tribe_venue_table_top', $event_id );
}

/**
 * Echo Series select menu
 *
 * @since 4.10.0
 *
 * @param int|null $event_id The event ID.
 */
function tribe_community_events_series_select_menu( $event_id = null ) {

	if ( ! class_exists( '\TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type' ) ) {
		return;
	}

	$series = \TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type::POSTTYPE;

	if ( ! $event_id ) {
		global $post;
		if ( isset( $post->post_type ) && $post->post_type === Tribe__Events__Main::POSTTYPE ) {
			$event_id = $post->ID;
		} elseif ( isset( $post->post_type ) && $post->post_type === $series ) {
			return;
		}
	}

	/**
	 * @since 4.10.0
	 */
	do_action( 'tribe_series_table_top', $event_id );
}

/**
 * Test to see if this is the Venue edit screen
 *
 * @since 3.1
 *
 * @param int|null $venue_id The venue ID.
 * @return bool
 */
function tribe_community_events_is_venue_edit_screen( $venue_id = null ) {
	$venue_id = Tribe__Events__Main::postIdHelper( $venue_id );
	return ( tribe_is_venue( $venue_id ) );
}

/**
 * Return Venue Description
 *
 * @since 3.1
 *
 * @param int|null $venue_id The venue ID.
 * @return string
 */
function tribe_community_events_get_venue_description( $venue_id = null ) {
	$venue_id = tribe_get_venue_id( $venue_id );
	$description = ( $venue_id > 0 ) ? get_post( $venue_id )->post_content : null;
	return apply_filters( 'tribe_get_venue_description', $description );
}

/**
 * Event Website URL
 *
 * @since 3.1
 * @deprecated 4.10.11 Use tribe_get_event_website_url().
 *
 * @return string The event's website URL
 */
function tribe_community_get_event_website_url( $event = null ) {
	_deprecated_function(
		__FUNCTION__,
		'4.10.11',
		'tribe_get_event_website_url'
	);
	return tribe_get_event_website_url();
}

/**
 * Get the logout URL.
 *
 * @since 3.1
 *
 * @return string The logout URL with appropriate redirect for the current user
 */
function tribe_community_events_logout_url() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	return $community->logout_url();
}

/**
 * Check if a field is required
 *
 * @since 3.1
 *
 * @param string $field The field name.
 *
 * @return bool Whether the field is required.
 */
function tribe_community_is_field_required( $field ) {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	return in_array( $field, $community->required_fields_for_submission(), true );
}

/**
 * Check if a field group is required
 *
 * @since 3.1
 *
 * @param string $field The field name.
 *
 * @return bool Whether the field group is required.
 */
function tribe_community_is_field_group_required( $field ) {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	return in_array( $field, $community->required_field_groups_for_submission(), true );
}

/**
 * Return the required field marker
 *
 * @since 3.1
 *
 * @param string $field The field name.
 *
 * @return string The required field marker.
 */
function tribe_community_required_field_marker( $field ) {
	if ( tribe_community_is_field_required( $field ) || tribe_community_is_field_group_required( $field ) ) {
		$html = '<span class="req">' . __( '(required)', 'tribe-events-community' ) . '</span>';
		return apply_filters( 'tribe_community_required_field_marker', $html, $field );
	}
	return '';
}

/**
 * Echo the field label
 *
 * @since 3.1
 *
 * @param string $field The field name.
 * @param string $text The field label text.
 */
function tribe_community_events_field_label( $field, $text ) {
	$label_text = apply_filters( 'tribe_community_events_field_label_text', $text, $field );
	$class      = tribe_community_events_field_has_error( $field ) ? 'error' : '';
	$class      = apply_filters( 'tribe_community_events_field_label_class', $class, $field );
	$html       = sprintf(
		'<label for="%s" class="%s">%s %s</label>',
		$field,
		$class,
		$label_text,
		tribe_community_required_field_marker( $field )
	);

	/**
	 * Filter the field label.
	 * `tribe_community_events_field_label`
	 *
	 * @param string $html The label HTML
	 * @param string $field The field name.
	 * @param string $text  The field label.
	 */
	$html = apply_filters( 'tribe_community_events_field_label', $html, $field, $text );

	echo $html;
}

/**
 * Community field classes.
 *
 * @since 4.7.1
 *
 * @param string  $field   The field name.
 * @param string  $classes The field classes.
 * @param boolean $echo    (Optional) if true we print, else we return.
 *
 * @return mixed
 */
function tribe_community_events_field_classes( $field, $classes = [], $echo = true ) {

	// If we're receiving the classes as string, make it array.
	if ( ! is_array( $classes ) ) {
		$classes = explode( '', $classes );
	}

	// If the field is required, add the `required` class.
	if (
		tribe_community_is_field_required( $field )
		|| tribe_community_is_field_group_required( $field )
	) {
		$classes[] = 'required';
	}

	// Sanitize the $classes.
	$classes = array_map( 'sanitize_html_class', $classes );

	/**
	 * Filter the field classes.
	 * `tribe_community_events_field_label`
	 *
	 * @since 4.7.1
	 *
	 * @param string $field   The field name.
	 * @param string $classes The field classes.
	 */
	$classes = apply_filters( 'tribe_community_events_field_classes', $classes, $field );

	$classes = esc_attr( implode( ' ', $classes ) );

	if ( ! empty( $echo ) ) {
		echo $classes;
	} else {
		return $classes;
	}
}

/**
 * Check if a field has an error
 *
 * @since 4.7.1
 *
 * @param string $field The field name.
 *
 * @return bool Whether the field has an error.
 */
function tribe_community_events_field_has_error( $field ) {
	return apply_filters( 'tribe_community_events_field_has_error', false, $field );
}

/**
 * Check if single geo mode is enabled
 *
 * @since 4.7.1
 *
 * @return bool Whether single geo mode is enabled.
 */
function tribe_community_events_single_geo_mode() {
	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );

	return (bool) $community->getOption( 'single_geography_mode' );
}

/**
 * Whether an event is one submitted via the community event submission or not.
 *
 * The check is made on the `_EventOrigin` custom field set when the event is
 * originally submitted; as such later modifications or deletions of that field can
 * cause different return values from this function.
 * Also note that this function will always return `false` for Community submitted
 * before version `4.3`; to have this function return the right value set the
 * `_EventOrigin` custom field to `community-events` on previously created Community.
 * Note that editing a pre `4.3` version community event through the community event
 * edit screen will mark it as a community event.
 *
 * @since 4.3
 *
 * @param WP_Post|int $event Either the `WP_Post` event object or the event post `ID`.
 *
 * @return bool Whether the event is a community event.
 */
function tribe_community_events_is_community_event( $event ) {
	$event_id = Tribe__Main::post_id_helper( $event );

	return get_post_meta( $event_id, '_EventOrigin', true ) === 'community-events';
}


/**
 * Events Lists Menu Items
 *
 * @since 4.5
 *
 * @return array
 */
function tribe_community_events_list_columns() {
	$columns = [
		'status' => esc_html__( 'Publish status', 'tribe-events-community' ),
		'title'  => esc_html__( 'Title', 'tribe-events-community' ),
	];


	// @todo redscar - move this to an ECP integration.
	if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
		$columns['recurring'] = esc_html__( 'Recurring?', 'tribe-events-community' );
	}



	/**
	 * Filter the columns on the My Events List page.
	 *
	 * This filter allows developers to add, remove, or modify the columns
	 * displayed on the My Events List page.
	 *
	 * @since 5.0.0
	 *
	 * @param array $columns An associative array of columns, where the key is the column slug and the value is the column label.
	 */
	$columns = apply_filters( 'tribe_community_events_list_columns', $columns );

	return $columns;
}

/**
 * Echo the previous/next navigation
 *
 * @since 4.5
 * @since 5.0.0 Refactored method to allow for `tribe_community_events_show_prev_next_nav` which allows for hiding the buttons.
 */
function tribe_community_events_prev_next_nav() {
	/**
	 * Filters whether to show the previous/next navigation buttons.
	 *
	 * This filter allows developers to disable the navigation buttons for Community.
	 *
	 * @since 5.0.0
	 *
	 * @param bool $show_nav Whether to show the navigation buttons. Default true.
	 */
	if ( apply_filters( 'tec_events_community_events_listing_show_prev_next_nav', true ) === false ) {
		return;
	}

	/** @var Tribe__Events__Community__Main $community */
	$community = tribe( 'community.main' );
	add_filter( 'get_pagenum_link', [ $community, 'fix_pagenum_link' ] );

	/**
	 * Filters the pagination link used in the Community shortcode navigation.
	 *
	 * This filter allows customization of the pagination link in the Community shortcode navigation.
	 * By default, it uses the link for the first page generated by `get_pagenum_link( 1 )`.
	 *
	 * @since 5.0.0
	 *
	 * @param string $link The default pagination link for the first page.
	 */
	$link = apply_filters( 'tribe_events_community_shortcode_nav_link', get_pagenum_link( 1 ) );
	$link = remove_query_arg( 'eventDisplay', $link );

	// Determine button classes based on current display mode.
	$is_past_display       = isset( $_GET['eventDisplay'] ) && $_GET['eventDisplay'] === 'past';
	$upcoming_button_class = $is_past_display ? 'tribe-button-tertiary' : 'tribe-button-secondary';
	$past_button_class     = $is_past_display ? 'tribe-button-secondary' : 'tribe-button-tertiary';

	// Render the navigation buttons.
	echo sprintf(
		'<a href="%1s?eventDisplay=list" class="tribe-button tribe-button-small tribe-upcoming %2s">%3s</a>',
		esc_url( $link ),
		esc_attr( $upcoming_button_class ),
		esc_html__( 'Upcoming events', 'tribe-events-community' )
	);
	echo sprintf(
		'<a href="%1s?eventDisplay=past" class="tribe-button tribe-button-small tribe-past %2s">%3s</a>',
		esc_url( $link ),
		esc_attr( $past_button_class ),
		esc_html__( 'Past events', 'tribe-events-community' )
	);
}

if ( ! function_exists( 'tribe_community_tickets_is_frontend_attendees_report' ) ) {
	/**
	 * Check if we're on a front-end Attendees Report
	 *
	 * @since 4.10.17
	 *
	 * @return bool Whether we're on a front-end Attendees Report.
	 */
	function tribe_community_tickets_is_frontend_attendees_report() {
		$wp_route = get_query_var( 'WP_Route' );

		return ! empty( $wp_route ) && 'view-attendees-report-route' === $wp_route;
	}
}

if ( ! function_exists( 'tribe_community_tickets_is_frontend_sales_report' ) ) {
	/**
	 * Check if we're on a front-end Sales Report
	 *
	 * @since 4.10.17
	 *
	 * @return bool Whether we're on a front-end Sales Report.
	 */
	function tribe_community_tickets_is_frontend_sales_report() {
		$wp_route = get_query_var( 'WP_Route' );

		return ! empty( $wp_route ) && 'view-sales-report-route' === $wp_route;
	}
}

/**
 * Builds and returns the correct payout repository.
 *
 * @since 4.10.17
 *
 * @param string $repository The repository name.
 *
 * @return \Tribe\Community\Tickets\Repositories\Payout The payouts repository.
 */
function tribe_payouts( $repository = 'default' ) {
	$map = [
		'default' => 'community-tickets.repositories.payout',
	];

	/**
	 * Filters the map relating payout repository slugs to service container bindings.
	 *
	 * @since 4.10.17
	 *
	 * @param array  $map        A map in the shape [ <repository_slug> => <service_name> ]
	 * @param string $repository The currently requested implementation.
	 */
	$map = apply_filters( 'tribe_community_tickets_payout_repository_map', $map, $repository );

	return tribe( Tribe__Utils__Array::get( $map, $repository, $map['default'] ) );
}

/**
 * Return the base event label
 *
 * @since 5.0.7
 *
 * @return string The base event label.
 */
function tec_events_community_event_label() {
	/**
	 * Filter the base event label.
	 *
	 * @since 5.0.7
	 *
	 * @param string $label The base event label.
	 */
	return apply_filters( 'tec_events_community_event_label', __( 'Event', 'tribe-events-community' ) );
}

/**
 * Return the singular event label
 *
 * @since 5.0.7
 *
 * @return string The singular event label.
 */
function tec_events_community_event_label_singular() {
	/**
	 * Filter the singular event label.
	 *
	 * @since 5.0.7
	 *
	 * @param string $label The singular event label.
	 */
	return apply_filters( 'tec_events_community_event_label_singular', tec_events_community_event_label() );
}

/**
 * Return the singular lowercase event label
 *
 * @since 5.0.7
 *
 * @return string The singular lowercase event label.
 */
function tec_events_community_event_label_singular_lowercase() {
	/**
	 * Filter the singular lowercase event label.
	 *
	 * @since 5.0.7
	 *
	 * @param string $label The singular lowercase event label.
	 */
	return apply_filters( 'tec_events_community_event_label_singular_lowercase', __( 'event', 'tribe-events-community' ) );
}

/**
 * Return the plural event label
 *
 * @since 5.0.7
 *
 * @return string The plural event label.
 */
function tec_events_community_event_label_plural() {
	/**
	 * Filter the plural event label.
	 *
	 * @since 5.0.7
	 *
	 * @param string $label The plural event label.
	 */
	return apply_filters( 'tec_events_community_event_label_plural', __( 'Events', 'tribe-events-community' ) );
}

/**
 * Return the plural lowercase event label
 *
 * @since 5.0.7
 *
 * @return string The plural lowercase event label.
 */
function tec_events_community_event_label_plural_lowercase() {
	/**
	 * Filter the plural lowercase event label.
	 *
	 * @since 5.0.7
	 *
	 * @param string $label The plural lowercase event label.
	 */
	return apply_filters( 'tec_events_community_event_label_plural_lowercase', __( 'events', 'tribe-events-community' ) );
}

/**
 * Return the event label in the specified case
 *
 * @since 5.0.7
 *
 * @param string $variant The case to return the label in.
 *
 * @return string The event label in the specified case.
 */
function tec_events_community_get_event_label( $variant = 'singular' ) {
	switch ( $variant ) {
		case 'singular':
			return tec_events_community_event_label_singular();
		case 'singular_lowercase':
			return tec_events_community_event_label_singular_lowercase();
		case 'plural':
			return tec_events_community_event_label_plural();
		case 'plural_lowercase':
			return tec_events_community_event_label_plural_lowercase();
		default:
			return tec_events_community_event_label_singular();
	}
}
