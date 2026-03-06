<?php
// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Setup the Community Tickets Shortcodes
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 */
class Tribe__Events__Community__Tickets__Shortcodes extends Tribe__Events__Community__Shortcode__Abstract {

	/**
	 * Add a hook for the tribe_community_tickets shortcode tag and
	 * Enqueue the Community Tickets scripts before rendering the shortcode for CE Submission form
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function hooks() {
		// Enqueues Community Tickets scripts before rendering the CE Submission form via shortcode
		add_action( 'tribe_events_community_before_shortcode', [ $this, 'enqueue_assets' ] );
		// Add the Community Tickets shortcode
		add_shortcode( 'tribe_community_tickets', [ $this, 'do_shortcode' ] );
	}

	/**
	 * Enqueue the scripts and stylesheets for the Community Tickets Shortcodes
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function enqueue_assets() {
		tribe_asset_enqueue_group( 'event-tickets-admin' );
		tribe_asset_enqueue( 'events-community-tickets-js' );
		tribe_asset_enqueue( 'event-tickets-plus-admin-css' );
		tribe_asset_enqueue( 'event-tickets-plus-meta-admin-css' );
		tribe_asset_enqueue( 'events-community-tickets-shortcodes-css' );

		$nonces = [
			'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
			'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
			'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
			'ajaxurl'             => admin_url( 'admin-ajax.php' ),
		];

		wp_localize_script( 'event-tickets', 'TribeTickets', $nonces );
		wp_localize_script(
			'event-tickets',
			'tribe_ticket_notices', [
				'confirm_alert' => esc_html__( 'Are you sure you want to delete this ticket? This cannot be undone.', 'tribe-events-community-events' ),
			]
		 );

		Tribe__Tickets__Metabox::localize_decimal_character();

		// using the event-tickets localization here because it is a pre-requisite, so this translation should be available
		$upload_header_data = [
			'title'  => esc_html__( 'Ticket header image', 'event-tickets' ),
			'button' => esc_html__( 'Set as ticket header', 'event-tickets' ),
		];

		wp_localize_script( 'event-tickets', 'HeaderImageData', $upload_header_data );

		wp_enqueue_media();
	}

	/**
	 * Display the Community Tickets Shortcodes
	 *
	 * To display the Attendees Report use:
	 * [tribe_community_tickets view="attendees_report" id="your_event_id"]
	 *
	 * To display the Sales Report use:
	 * [tribe_community_tickets view="sales_report" id="your_event_id"]
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array  $attributes
	 * @param string $tag the name of the shortcode
	 *
	 * @return mixed
	 */
	public function do_shortcode( $attributes = [], $tag = 'tribe_community_tickets' ) {
		if ( is_admin() ) {
			return;
		}

		// Normalize attribute keys, lowercase
		$attributes = array_change_key_case( (array) $attributes, CASE_LOWER );
		$tribe_id   = $this->check_id( $attributes );
		$tribe_view = array_key_exists( 'view', $attributes ) ? $attributes['view'] : 'submission_form';

		// Override default attributes with user attributes
		$tribe_attributes = shortcode_atts(
			[
				'view' => $tribe_view,
				'id'   => $tribe_id,
			],
			$attributes, $tag );

		// Check if current user is allowed to visualize the reports
		$login = $this->is_logged_in( $tribe_id );
		if ( $login !== true ) {
			return $login;
		}

		$attendees_report = new Tribe__Events__Community__Tickets__Route__Attendees_Report( '', true );
		$sales_report     = new Tribe__Events__Community__Tickets__Route__Sales_Report( '', true );
		$shortcode_notice = esc_html__( 'Community Tickets Shortcode error: The provided Event ID is invalid', 'tribe-events-community' );

		$this->enqueue_assets();

		switch ( $tribe_attributes['view'] ) {
			case 'attendees_report':
				if ( $tribe_id === false ) {
					$view = $shortcode_notice;
				} else {
					add_filter(
						'tribe_events_tickets_attendees_table_nav',
						[
							$this,
							'attendees_buttons',
						],
						1
					);
					$view = $attendees_report->callback( $tribe_attributes['id'], true );
				}
				break;
			case 'sales_report':
				if ( $tribe_id === false ) {
					$view = $shortcode_notice;
				} else {
					$view = $sales_report->callback( $tribe_attributes['id'], true );
				}
				break;
			default:
				$view = esc_html__( 'Community Tickets Shortcode error: The view you specified doesn\'t exist or the Event ID is invalid.', 'tribe-events-community' );
		}

		$display = "<div id='tribe-community-tickets-shortcode' style='visibility:hidden;'>$view</div>";
		$display .= '<script>setTimeout(function(){document.getElementById("tribe-community-tickets-shortcode").style.visibility = "visible";},400);</script>';

		return $display;
	}

	/**
	 * Override the default pagination for Sales Report
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @return int
	 */
	public function orders_per_page() {
		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
		$items    = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table::get_orders( $event_id );

		return count( $items );
	}

	/**
	 * Override the default Print, Email and Export buttons on Attendees table.
	 * Enable the "Export CSV files" feature on attendee reports
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param $event_id
	 *
	 * @return array
	 */
	public function attendees_buttons( $event_id = '' ) {

		if ( ! empty( $event_id ) ) {
			$post_object = get_post( absint( $_GET['event_id'] ) );

			if ( isset( $post_object->ID ) ) {
				$event_id = $post_object->ID;
			}
		}

		/** @var Tribe__Events__Community__Main $main */
		$main = tribe( 'community.main' );

		$community_slug = $main->getCommunityRewriteSlug();
		$attendees_slug = $main->get_rewrite_slug( 'attendees' );
		$event_slug     = $main->get_rewrite_slug( 'event' );

		$url = esc_url( home_url() . '/' . $community_slug . '/' . $attendees_slug . '/' . $event_slug . '/' . $event_id );

		$export_url = add_query_arg(
			[
				'attendees_csv'       => true,
				'attendees_csv_nonce' => wp_create_nonce( 'attendees_csv_nonce' ),
			],
			esc_url( $url )
		);

		$email_link = tribe( 'settings' )->get_url(
			[
				'page'      => 'tickets-attendees',
				'action'    => 'email',
				'event_id'  => $event_id,
				'TB_iframe' => true,
				'width'     => 410,
				'height'    => 300,
				'parent'    => 'admin.php',
			]
		);

		$nav = [
			'left'  => [
				'print'  => sprintf( '<input type="button" name="print" class="print button action" value="%s">', esc_attr__( 'Print', 'event-tickets' ) ),
				'email'  => sprintf( '<a class="email button action thickbox" href="%1$s">%2$s</a>', esc_url( $email_link ), esc_html__( 'Email', 'event-tickets' ) ),
				'export' => sprintf( '<a target="_blank" href="%1$s" class="export button action">%2$s</a>', esc_url( $export_url ), esc_html__( 'Export', 'event-tickets' ) ),
			],
			'right' => [],
		];

		return $nav;
	}
}
