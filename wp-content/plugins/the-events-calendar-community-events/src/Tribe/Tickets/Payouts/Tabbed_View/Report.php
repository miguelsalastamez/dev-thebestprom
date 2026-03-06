<?php

/**
 * Payouts report object.
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 *
 * @package Tribe\Community\Tickets\Payouts
 */

namespace Tribe\Community\Tickets\Payouts\Tabbed_View;

use Tribe\Community\Tickets\Payouts;
use Tribe\Community\Tickets\Payouts\Tooltips;
use Tribe__Events__Community__Tickets__Main as CT_Main;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Utils__Array as Utils_Array;

class Report {

	/**
	 * Slug of the admin page for payouts.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	public static $payouts_slug = 'events-community-tickets-payouts';

	/**
	 * The tab slug of the payouts page.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	public static $tab_slug = 'events-community-tickets-payouts-report';

	/**
	 * The menu slug of the payouts page.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var string
	 */
	public $payouts_page;

	/**
	 * The table/tab content.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @var Tribe\Community\Tickets\Payouts\Table
	 */
	public $payouts_table;

	/**
	 * Returns the link to the "payouts" report for this post.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param WP_Post $post the post we want the link for.
	 *
	 * @return string The absolute URL.
	 */
	public static function get_payouts_report_link( $post ) {
		$url = add_query_arg(
			[
				'post_type' => $post->post_type,
				'page'      => self::$payouts_slug,
				'post_id'   => $post->ID,
			],
			admin_url( 'edit.php' )
		);

		return $url;
	}
	/**
	 * Returns the button to the "payouts" report for this post.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param WP_Post $post the post we want the link for.
	 * @param boolean $echo echo or return the html
	 *
	 * @return string The absolute URL.
	 */
	public function get_payouts_report_button( $post, $echo = true ) {
		if ( empty( $post ) ) {
			return;
		}

		$post_id = \Tribe__Main::post_id_helper( $post );
		$post    = get_post( $post_id );

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		if ( ! $payouts->should_show_payouts_actions( $post ) ) {
			return;
		}

		$payouts_url = add_query_arg(
			[
				'post_type' => $post->post_type,
				'page'      => self::$payouts_slug,
				'post_id'   => $post->ID,
			],
			admin_url( 'edit.php' )
		);

		ob_start();
		?>
		<a
				class="button-secondary"
				href="<?php echo esc_url( $payouts_url ); ?>"
			>
				<?php esc_html_e( 'View Payouts', 'tribe-events-community' ); ?>
			</a>
		<?php
		$button = ob_get_clean();

		if ( $echo ) {
			echo $button;
		}

		return $button;
	}

	/**
	 * Adds order related actions to the available row actions for the post.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $actions Collection of row actions as HTML links.
	 * @param int|WP_Post $post The post/event or its ID.
	 *
	 * @return array $actions
	 */
	public function add_payouts_row_action( array $actions, $post ) {
		$post        = get_post( $post );
		$url         = self::get_payouts_report_link( $post );
		$post_labels = get_post_type_labels( get_post_type_object( $post->post_type ) );
		$post_type   = strtolower( $post_labels->singular_name );

		$actions['tickets_payouts'] = sprintf(
			'<a title="%s" href="%s">%s</a>',
			esc_attr( sprintf( __( 'See payouts for this %s', 'tribe-events-community' ), $post_type ) ),
			esc_url( $url ),
			esc_html__( 'Payouts', 'tribe-events-community' )
		);

		return $actions;
	}

	/**
	 * Registers the Payouts page as a plugin options page.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function register_payouts_page() {
		$candidate_post_id = Utils_Array::get( $_GET, 'post_id', Utils_Array::get( $_GET, 'event_id', 0 ) );

		$post_id = absint( $candidate_post_id );

		// $candidate_post_id is a string, don't use !==
		if ( $post_id != $candidate_post_id ) {
			return;
		}

		$cap = 'edit_event_tickets';
		if ( $post_id && ! current_user_can( $cap ) ) {
			$post = get_post( $post_id );

			if ( $post instanceof \WP_Post && get_current_user_id() === (int) $post->post_author ) {
				$cap = 'read';
			}
		}

		$page_title         = __( 'Payouts', 'tribe-events-community' );
		$this->payouts_page = add_submenu_page(
			null,
			$page_title,
			$page_title,
			$cap,
			self::$payouts_slug,
			[ $this, 'payouts_page_inside' ]
		);

		add_action( 'load-' . $this->payouts_page, [ $this, 'payouts_page_screen_setup' ] );
	}

	/**
	 * Enqueue the report assets.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function enqueue_reports_assets() {
		$et_resources_url = plugins_url( 'event-tickets/src/resources' );

		wp_enqueue_style( \Tickets_Attendees::instance()->slug(), $et_resources_url . '/css/tickets-report.css', [], Tickets_Main::instance()->css_version() );
		wp_enqueue_style( \Tickets_Attendees::instance()->slug() . '-print', $et_resources_url . '/css/tickets-report-print.css', [], Tickets_Main::instance()->css_version(), 'print' );
	}

	/**
	 * Filter the page slugs that the payouts resources will load to add the order page.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $slugs List of existing slugs that are converted to tabs.
	 *
	 * @return array $slugs
	 */
	public function add_payouts_resources_page_slug( $slugs ) {
		$slugs[] = $this->payouts_page;

		return $slugs;
	}

	/**
	 * Sets up the payouts page screen.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function payouts_page_screen_setup() {
		$this->payouts_table = new Table();

		wp_enqueue_script( 'jquery-ui-dialog' );

		add_filter( 'admin_title', [ $this, 'payouts_admin_title' ] );
	}

	/**
	 * Sets the browser title for the payouts admin page.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 * @param string $admin_title the browser wondow/tab title to filter.
	 *
	 * @return string
	 */
	public function payouts_admin_title( $admin_title ) {
		if ( ! empty( $_GET['post_id'] ) ) {
			$event       = get_post( absint( $_GET['post_id'] ) );
			$admin_title = sprintf(
				esc_html_x( '%s - Payouts', 'Browser title', 'tribe-events-community' ),
				$event->post_title
			);
		}

		return $admin_title;
	}

	/**
	 * Renders the payouts page.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	public function payouts_page_inside() {
		$this->payouts_table->prepare_items();

		$event_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$event    = get_post( $event_id );

		ob_start();
		$this->payouts_table->display();
		$table = ob_get_clean();

		// Build and render the tabbed view and set this as the active tab
		$tabbed_view = new Tabbed_View();
		$tabbed_view->set_active( self::$tab_slug );
		$tabbed_view->render();

		include CT_Main::instance()->plugin_path . 'src/admin-views/tickets/payouts-tab.php';
	}

	/**
	 * Injects the event details list.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $event_id The Post ID of the event.
	 */
	public function payout_details_column_first( $event_id ) {
		$pto = get_post_type_object( get_post_type( $event_id ) );
		include CT_Main::instance()->plugin_path . 'src/admin-views/tickets/payouts-report-header-first.php';
	}

	/**
	 * Injects the event details list.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $event_id The Post ID of the event.
	 */
	public function payout_details_column_middle( $event_id ) {
		$tickets  = \Tribe__Tickets__Tickets::get_event_tickets( $event_id );
		$ticket_counts = [];

		// Loop through tickets and get total found and total quantity.
		foreach ( $tickets as $ticket ) {
			$repository = tribe_payouts();
			$repository->by( 'ticket', $ticket->ID );
			$repository->set_found_rows( true );

			$ticket_count = $repository->found();
			$total = $repository->get_total_ticket_quantity();

			$ticket_counts[ $ticket->ID ] = [
				'name'           => $ticket->name,
				'total_found'    => $ticket_count,
				'total_quantity' => $total,
			];
		}

		include CT_Main::instance()->plugin_path . 'src/admin-views/tickets/payouts-report-header-middle.php';
	}

	/**
	 * Injects the event details list.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $event_id The Post ID of the event.
	 */
	public function payout_details_column_last( $event_id ) {
		$summary_counts = [];

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		$stati = $payouts->get_supported_stati();

		// Get total count.
		$repository = tribe_payouts();
		$repository->by( 'event', $event_id );
		$repository->set_found_rows( true );

		$summary_counts['any'] = [
			'count_label'        => __( 'Total Payouts', 'tribe-events-community' ),
			'count'              => $repository->found(),
			'total_amount_label' => __( 'Total Payout Amount', 'tribe-events-community' ),
			'total_amount'       => $repository->get_total_amount(),
			'tooltip'            => '',
		];

		foreach ( $stati as $status => $args ) {
			$repository = tribe_payouts();
			$repository->by( 'event', $event_id );
			$repository->by( 'status', $status );
			$repository->set_found_rows( true );

			$summary_counts[ $status ] = [
				'count_label'        => $args['label'],
				'count'              => $repository->found(),
				'total_amount_label' => $args['label'],
				'total_amount'       => $repository->get_total_amount(),
				'tooltip'            => '',
			];
		}

		/** @var Tooltips $tooltips */
		$tooltips = tribe( 'community-tickets.payouts.tooltips' );

		$summary_counts[ Payouts::STATUS_PENDING_ORDER ]['tooltip'] = $tooltips->get_pending_order_payouts_status_tooltip();
		$summary_counts[ Payouts::STATUS_PENDING ]['tooltip']       = $tooltips->get_pending_payouts_status_tooltip();
		$summary_counts[ Payouts::STATUS_FAILED ]['tooltip']        = $tooltips->get_failed_payouts_status_tooltip();
		$summary_counts[ Payouts::STATUS_PAID ]['tooltip']          = $tooltips->get_paid_payouts_status_tooltip();

		include CT_Main::instance()->plugin_path . 'src/admin-views/tickets/payouts-report-header-last.php';
	}

	/**
	 * Injects action links into the payouts screen.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param int $event_id The Post ID of the event.
	 */
	public function event_action_links( $event_id ) {

		/**
		 * Allows for control of the specific "edit post" URLs used for event Sales, Payouts, and Attendees Reports.
		 *
		 * @since 5.0.0 Migrated to Community from Community Tickets.
		 *
		 * @param string $link The deafult "edit post" URL.
		 * @param int $event_id The Post ID of the event.
		 */
		$edit_post_link = apply_filters( 'event_community_tickets_event_action_links_edit_url', get_edit_post_link( $event_id ), $event_id );

		$post     = get_post( $event_id );
		$pto      = get_post_type_object( $post->post_type );
		$singular = $pto->labels->singular_name;

		$edit = sprintf( _x( 'Edit %s', 'event payout actions', 'tribe-events-community' ), $singular );
		$view = sprintf( _x( 'View %s', 'event payout actions', 'tribe-events-community' ), $singular );

		$action_links = [
			'<a href="' . esc_url( $edit_post_link ) . '" title="' . esc_attr_x( 'Edit', 'event payout actions', 'tribe-events-community' ) . '">' . esc_html( $edit ) . '</a>',
			'<a href="' . esc_url( get_permalink( $event_id ) ) . '" title="' . esc_attr_x( 'View', 'event payout actions', 'tribe-events-community' ) . '">' . esc_html( $view ) . '</a>',
		];

		/**
		 * Provides an opportunity to add and remove action links from the payouts screen summary box.
		 *
		 * @param array $action_links the array of links to filter.
		 * @param int $event_id The Post ID of the event.
		 */
		$action_links = (array) apply_filters( 'tribe_tickets_attendees_event_action_links', $action_links, $event_id );

		if ( empty( $action_links ) ) {
			return;
		}

		echo wp_kses_post( '<li class="event-actions">' . implode( ' | ', $action_links ) . '</li>' );
	}
}
