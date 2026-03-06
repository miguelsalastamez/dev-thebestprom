<?php

class Tribe__Events__Community__Tickets__Route__Attendees_Report extends Tribe__Events__Community__Tickets__Route__Abstract_Route {
	/**
	 * Route slug
	 * @var string
	 */
	public $route_slug = 'view-attendees-report-route';

	/**
	 * Route suffix
	 * @var string
	 */
	public $route_suffix = '/(\d+/?)$';

	/**
	 * Route query vars
	 * @var array
	 */
	public $route_query_vars = [
		'tribe_community_event_id' => 1,
	];

	/**
	 * Page arguments
	 * @var array
	 */
	public $page_args = [
		'tribe_community_event_id',
	];

	/**
	 * Constructor
	 *
	 * @param $router
	 * @param bool $shortcode
	 */
	public function __construct( $router, $shortcode = false ) {

		if ( true === $shortcode ) {
			return;
		}

		$title = __( 'Attendees Report', 'tribe-events-community' );
		$title = apply_filters( 'tribe_community_tickets_attendees_report_title', $title );
		$title = apply_filters_deprecated(
			'tribe_ct_attendees_report_title',
			[ $title ],
			'4.6.3',
			'tribe_community_tickets_attendees_report_title',
			'The filter "tribe_ct_attendees_report_title" has been renamed to "tribe_community_tickets_attendees_report_title" to match plugin namespacing.'
		);

		$this->title = $title;

		parent::__construct( $router );
	}

	/**
	 * Handles the rendering of the route
	 *
	 * @param mixed $event_id
	 * @param bool  $shortcode
	 *
	 * @return string $output
	 */
	public function callback( $event_id = null, $shortcode = false ) {
		$community_events  = Tribe__Events__Community__Main::instance();
		$community_tickets = tribe( 'community-tickets.main' );
		if ( ! $shortcode ) {
			$community_tickets->require_login( $event_id );
		}
		$community_tickets->register_resources();

		add_thickbox();

		add_filter( 'tribe_events_current_view_template', [ $community_events, 'default_template_placeholder' ] );
		tribe_asset_enqueue_group( 'events-styles' );
		tribe_asset_enqueue_group( 'event-tickets-admin' );
		tribe_asset_enqueue_group( 'event-tickets-admin-attendees' );
		tribe_asset_enqueue_group( 'event-tickets-plus-admin' );
		tribe_asset_enqueue( 'events-community-tickets-shortcodes-css' );

		// the attendees report requires that the event ID be placed in $_GET['event_id']
		$_GET['event_id'] = $event_id;

		$GLOBALS['hook_suffix'] = null;

		include_once ABSPATH . '/wp-admin/includes/screen.php';
		include_once ABSPATH . '/wp-admin/includes/template.php';

		// starting with WordPress 4.4, these two classes were split out to their own files
		if ( ! class_exists( 'WP_Screen' ) ) {
			include_once ABSPATH . '/wp-admin/includes/class-wp-screen.php';
		}

		if ( ! class_exists( 'WP_List_Table' ) ) {
			include_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
		}

		add_filter( 'body_class', [ $this, 'remove_body_classes' ], 10, 2 );

		$community_events->removeFilters();

		add_filter( 'tribe_tickets_attendees_show_title', '__return_false', 12, 2 );
		add_filter( 'tribe_tickets_attendees_show_view_title', '__return_false', 12, 2 );

		ob_start();
		if ( empty( $_GET['attendees_csv'] ) ) {
			?>
			<div class="table-menu-wrapper">
				<a href="<?php echo esc_url( tribe_community_events_list_events_link() ); ?>" class="button">
					<?php echo sprintf( __( 'My %s', 'tribe-events-community' ), $community_events->get_event_label( 'plural' ) ); ?>
				</a>
				<?php do_action( 'tribe_community_tickets_attendees_nav' ); ?>
			</div>
			<?php
		}

		$attendees = tribe( 'tickets.attendees' );
		$attendees->enqueue_assets( '' );
		$attendees->load_pointers( '' );
		$attendees->screen_setup();

		if ( $shortcode ) {
			// Make sure the Attendees report will be rendered only if user is in a page or post and not on the edit area.
			// Necessary for the report to work properly with shortcodes.
			global $pagenow;
			if ( ( 'post.php' !== $pagenow ) || ( 'post' !== get_post_type() ) ) {
				tribe_get_template_part( 'community-tickets/modules/shortcode-attendees', null );
			}
		} else {
			$attendees->render();
		}

		wp_enqueue_style( 'list-tables' );
		tribe_asset_enqueue_group( 'events-styles' );
		tribe_asset_enqueue_group( 'event-tickets-admin' );
		tribe_asset_enqueue_group( 'event-tickets-plus-admin' );
		tribe_asset_enqueue( 'events-community-tickets-css' );
		tribe_asset_enqueue( 'events-community-tickets-js' );
		wp_enqueue_script( 'list-table' );
		wp_enqueue_script( 'common' );
		tribe_asset_enqueue( 'events-community-tickets-shortcodes-css' );

		$output = ob_get_clean();

		$output = '<div id="tribe-events-report">' . $output . '</div>';

		return $output;
	}

	/**
	 * Returns paths for routes
	 *
	 * @param $suffix string Value gets appended to the end of the path upon return
	 *
	 * @return string
	 */
	public function path( $suffix = null ) {
		$community_events = Tribe__Events__Community__Main::instance();

		$path = $community_events->getCommunityRewriteSlug() . '/' . $community_events->get_rewrite_slug( 'attendees' ) . '/' . $community_events->get_rewrite_slug( 'event' );

		$path .= $suffix;

		return $path;
	}//end path

	/**
	 * return the attendees report link
	 */
	public function url( $post_id ) {
		$ce_main = tribe( Tribe__Events__Community__Main::class );
		$url     = $ce_main->getUrl( 'attendees', $post_id, null, $ce_main->get_community_events_post_type() );
		$url = apply_filters( 'tribe_community_tickets_attendees_report_url', $url );
		$url = apply_filters_deprecated(
			'tribe_ct_attendees_report_url',
			[ $url ],
			'4.6.3',
			'tribe_community_tickets_attendees_report_url',
			'The filter "tribe_ct_attendees_report_url" has been renamed to "tribe_community_tickets_attendees_report_url" to match plugin namespacing.'
		);

		return $url;
	}//end url
}
