<?php

class Tribe__Events__Community__Tickets__Event_Form {
	/**
	 * @var Tribe__Events__Community__Tickets__Main $main Parent community tickets object
	 */
	public $main;

	/**
	 * Constructor!
	 */
	public function __construct( $main ) {
		$this->main = ! empty( $main ) ? $main : tribe( 'community-tickets.main' );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_resources' ] );
		add_action( 'tec_events_community_form_before_module_spam-control', [ $this, 'add_edit_tickets' ] );
		add_action( 'tribe_events_tickets_new_ticket_warnings', [ $this, 'add_payment_options_button' ] );
		add_action( 'tribe_events_community_display_cost_section', [ $this, 'maybe_hide_event_cost' ], 20 );

		// we must use the update_post_metadata filter because we always want to make sure the payment split
		// is set appropriately even if the _regular_price meta value hasn't changed
		add_filter( 'update_post_metadata', [ $this, 'set_adaptive_payment_data' ], 10, 5 );

		if ( ! Tribe__Main::instance()->doing_ajax() ) {
			add_filter( 'tribe_events_tickets_module_name', [ $this, 'ticket_module_name' ] );
		}

		add_filter( 'tribe_events_tickets_report_url', [ $this, 'ticket_report_url' ], 10, 2 );
		add_filter( 'tribe_events_tickets_attendees_url', [ $this, 'ticket_attendees_url' ], 10, 2 );
		add_filter( 'tribe_events_tickets_woo_display_sku', '__return_false' );

		// make sure the user can only see images he/she has uploaded
		add_filter( 'parse_query', [ $this, 'view_only_users_media' ] );
		add_filter( 'has_cap', [ $this, 'give_subscribers_upload_files_role' ] );

		add_filter( 'tribe_events_tickets_woo_display_ecommerce_links', [ $this, 'display_ecommerce_links' ] );
		add_filter( 'tribe_events_tickets_edd_display_ecommerce_links', [ $this, 'display_ecommerce_links' ] );

		add_filter( 'tribe_ticket_filter_attendee_report_link', [ $this, 'modify_report_link' ], 10, 2 );
		add_filter( 'tribe_filter_attendee_order_link', [ $this, 'modify_order_link' ], 10, 2 );
		add_filter( 'tribe_tickets_attendees_event_action_links', [ $this, 'maybe_remove_edit_action_link' ], 10, 2 );
		add_filter( 'event_community_tickets_event_action_links_edit_url', [ $this, 'maybe_force_front_end_edit_post_link' ], 10, 2 );
		add_filter( 'tribe_tickets_venue_action_links_edit_url', [ $this, 'maybe_force_front_end_edit_venue_link' ], 10, 2 );
		add_filter( 'tribe_tickets_plus_woocommerce_order_link_url', [ $this, 'maybe_force_front_end_edit_order_link' ], 10, 2 );

		// use TEC's "Integrations" class for Twenty Seventeen theme to fix front-end Community Tickets layouts
		add_filter( 'tribe_events_twenty_seventeen_remove_sidebar_class', [ $this, 'maybe_remove_twenty_seventeen_sidebar_class' ] );
	}

	/**
	 * Enqueues needed styles and scripts
	 */
	public function enqueue_resources() {
		if ( ! tribe_is_community_my_events_page() && ! tribe_is_community_edit_event_page() ) {
			return;
		}

		wp_enqueue_script( 'event-tickets' );
		tribe_asset_enqueue( 'events-community-tickets-js' );
		wp_enqueue_style( 'event-tickets' );
		wp_enqueue_style( 'common' );
		tribe_asset_enqueue( 'events-community-tickets-css' );

		$nonces = [
			'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
			'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
			'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
			'ajaxurl'             => admin_url( 'admin-ajax.php' ),
		];

		wp_localize_script( 'event-tickets', 'TribeTickets', $nonces );
		wp_localize_script(
			'event-tickets',
			'tribe_ticket_notices',
			[
				'confirm_alert' => esc_html__(
					'Are you sure you want to delete this ticket? This cannot be undone.',
					'tribe-events-community'
				),
			]
		);

		Tribe__Tickets__Metabox::localize_decimal_character();

		// using the event-tickets localization here because it is a pre-requisite, so this translation should be available
		$upload_header_data = [
			'title'  => __( 'Ticket header image', 'event-tickets' ),
			'button' => __( 'Set as ticket header', 'event-tickets' ),
		];
		wp_localize_script( 'event-tickets', 'HeaderImageData', $upload_header_data );

		wp_enqueue_media();
	}

	/**
	 * Appends the ticket UI prior to the hidden spam field so that it appears at the end of the form.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function add_edit_tickets() {
		tribe_doing_frontend( true );

		if ( ! is_user_logged_in() ) {
			return;
		}

		$enabled_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$post_type          = tribe( Tribe__Events__Community__Main::class )->get_community_events_post_type();

		if ( in_array( $post_type, $enabled_post_types ) ) {
			tribe( Tribe__Events__Community__Templates::class )->tribe_get_template_part( 'community-tickets/modules/tickets', null, [ 'community_tickets' => $this->main ] );
		}
	}

	/**
	 * Adds a "button" to the ticket form linking to
	 * the payment options page - if split payments are enabled
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return void
	 */
	public function add_payment_options_button() {
		if ( tribe_is_truthy( tribe_get_request_var( 'is_admin' ) ) || ! tribe( 'community-tickets.payouts' )->is_split_payments_enabled() ) {
			return;
		}

		printf(
			esc_html__(
				'%1$sPayment options%2$s',
				'tribe-events-community'
			),
			'<a
				href="'
			. esc_url( $this->main->routes['payment-options']->url() )
			. '" class="button-secondary tribe-button-icon tribe-button-icon-edit">',
			'</a>'
		);
	}

	/**
	 * Hooked to the update_post_meta action
	 */
	public function set_adaptive_payment_data( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( '_regular_price' !== strtolower( $meta_key ) ) {
			return $check;
		}

		if ( ! tribe( 'community-tickets.payouts' )->is_split_payments_enabled() ) {
			return $check;
		}

		$event = get_post( $object_id );
		$user  = get_user_by( 'id', $event->post_author );

		// if the owner of the event cannot create tickets, bail
		if ( ! user_can( $user, 'edit_event_tickets' ) ) {
			return $check;
		}

		$meta = get_user_meta( $event->post_author, Tribe__Events__Community__Tickets__Payment_Options_Form::$meta_key, true );

		if ( empty( $meta['paypal_account_email'] ) ) {
			return $check;
		}

		update_post_meta( $object_id, '_paypal_adaptive_receivers', $meta['paypal_account_email'] . ' | 90' );

		return $check;
	}

	/**
	 * Renames the ticket module to a more generic name for the front end.
	 */
	public function ticket_module_name( $name ) {
		// using the event-tickets translation here, because that is the one that will potentially be changed
		if ( $name != __( 'RSVP', 'event-tickets' ) ) {
			$name = __( 'Tickets', 'tribe-events-community' );
		}

		return $name;
	}

	/**
	 * Replaces the attendees URL on the front-end with a community-friendly URL
	 */
	public function ticket_attendees_url( $url, $event_id ) {
		$url = get_bloginfo( 'url' ) . '/' . $this->main->routes['attendees-report']->path( "/{$event_id}" );

		return $url;
	}

	/**
	 * Replaces the report URL on the front-end with a community-friendly URL
	 */
	public function ticket_report_url( $url, $event_id ) {
		$url = get_bloginfo( 'url' ) . '/' . $this->main->routes['sales-report']->path( "/{$event_id}" );

		return $url;
	}

	/**
	 * Hooked to parse_query to limit media to include ONLY media uploaded by the current user
	 *
	 * @param $wp_query WP_Query WP Query object
	 *
	 * @return WP_Query
	 */
	public function view_only_users_media( $wp_query ) {
		global $current_user;

		// if the user isn't viewing the media library, bail
		if ( false === strpos( $_SERVER['REQUEST_URI'], '/wp-admin/upload.php' ) ) {
			return $wp_query;
		}

		// if a user is a contributor or greater, don't filter the media they can see. bail.
		if ( current_user_can( 'edit_posts' ) ) {
			return $wp_query;
		}

		$user_id = isset( $current_user->id ) ? $current_user->id : 0;
		$wp_query->set( 'author', $user_id );

		return $wp_query;
	}

	/**
	 * Filters whether or not the event cost section should be displayed
	 */
	public function maybe_hide_event_cost() {
		return ! current_user_can( 'edit_event_tickets' );
	}

	/**
	 * Disable eCommerce links to tickets in the admin if current user cannot access the admin
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param $display
	 *
	 * @return bool
	 */
	public function display_ecommerce_links() {
		if ( tribe_is_truthy( tribe_get_request_var( 'is_admin' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Modify Attendee Report Link to use front end link
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param $url
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function modify_report_link( $url, $post_id ) {
		if ( tribe_is_community_edit_event_page() || ! tribe_is_truthy( tribe_get_request_var( 'is_admin' ) ) ) {

			$routes = $this->main->routes;

			return ! empty( $routes['attendees-report'] ) ? $routes['attendees-report']->url( $post_id ) : $url;
		}

		return $url;
	}

	/**
	 * Modify Order Link to use front end link
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param $url
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public function modify_order_link( $url, $post_id ) {
		if ( tribe_is_community_edit_event_page() || ! tribe_is_truthy( tribe_get_request_var( 'is_admin' ) ) ) {

		  	$routes = $this->main->routes;

			return $routes['sales-report']->url( $post_id );
		}

		return $url;

	}

	/**
	 * Helps ensure the HTML string of "action links" in front-end reports don't include an "edit" action
	 * link if the user's not allowed to edit submissions.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $action_links The existing HTML string of "action links" for this event.
	 * @param int    $event_id     The post ID of this event.
	 *
	 * @return string
	 */
	public function maybe_remove_edit_action_link( $action_links, $event_id ) {
		/** @var Tribe__Events__Community__Main $ce_main */
		$ce_main = tribe( 'community.main' );

		$allowed_to_edit = $ce_main->getOption( 'allowUsersToEditSubmissions' );

		// $action_links has both "edit" and "view" links by default; if editing not allowed, return just "view" link.
		if ( ! $allowed_to_edit ) {
			$action_links = sprintf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( get_permalink( $event_id ) ),
				esc_attr_x( 'View', 'attendee event actions', 'tribe-events-community' ),
				esc_html_x( 'View Event', 'attendee event actions', 'tribe-events-community' )
			);
		}

		return $action_links;
	}

	/**
	 * Hijack event "edit" links on front-end Attendees and Sales reports and, in some cases, force them to *not* be wp-admin edit URLs.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $link     The existing URL for this edit-event link.
	 * @param int    $event_id The Post ID of this event.
	 *
	 * @return string
	 */
	public function maybe_force_front_end_edit_post_link( $link, $event_id ) {
		// We only want to modify front-end links for events.
		if ( is_admin() || ! tribe_is_event( $event_id ) ) {
			return $link;
		}

		if ( current_user_can( 'edit_post', $event_id ) ) {
			/** @var Tribe__Events__Community__Main $ce_main */
			$ce_main = tribe( 'community.main' );

			return $ce_main->getUrl( 'edit', $event_id, null, Tribe__Events__Main::POSTTYPE );
		}

		return $link;
	}

	/**
	 * Hijack venue "edit" links on front-end Attendees and Sales reports and, in some cases, force them to *not* be wp-admin edit URLs.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $link     The existing URL for this edit-venue link.
	 * @param int    $venue_id The Post ID of this venue.
	 *
	 * @return string
	 */
	public function maybe_force_front_end_edit_venue_link( $link, $venue_id ) {
		// We only want to modify front-end links for events.
		if ( is_admin() || ! tribe_is_venue( $venue_id ) ) {
			return $link;
		}

		if ( current_user_can( 'edit_post', $venue_id ) ) {
			/** @var Tribe__Events__Community__Main $ce_main */
			$ce_main = tribe( 'community.main' );

			return $ce_main->getUrl( 'edit', $venue_id, null, Tribe__Events__Venue::POSTTYPE );
		}

		return $link;
	}

	/**
	 * Hijack order "edit" links on front-end Sales reports and, in some cases, force them to *not* be wp-admin edit URLs.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param string $order_number_link The default "order" link.
	 * @param int    $order_number      The Post ID of the order.
	 *
	 * @return string
	 */
	public function maybe_force_front_end_edit_order_link( $order_number_link, $order_number ) {
		// We only want to modify front-end links for orders
		if ( ! is_admin() ) {
			return $order_number;
		}

		return $order_number_link;
	}

	/**
	 * Removes troublesome 'has-sidebar' body class in Twenty Seventeen theme to fix front-end Sales and Attendees Reports
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param boolean $should_remove Whether the sidebar class should be removed.
	 *
	 * @return boolean
	 */
	public function maybe_remove_twenty_seventeen_sidebar_class( $should_remove ) {
		return $should_remove || tribe_community_tickets_is_frontend_attendees_report() || tribe_community_tickets_is_frontend_sales_report();
	}
}
