<?php

class Tribe__Events__Community__Tickets__Route__Payment_Options extends Tribe__Events__Community__Tickets__Route__Abstract_Route {
	/**
	 * Route slug
	 * @var string
	 */
	public $route_slug = 'edit-payment-options-route';

	/**
	 * Route suffix
	 * @var string
	 */
	public $route_suffix = '(/page/(\d+))?/?$';

	/**
	 * constructor
	 */
	public function __construct( $router ) {
		$title = __( 'Payment Options', 'tribe-events-community' );
		$title = apply_filters( 'tribe_community_tickets_payment_options_title', $title );
		$title = apply_filters_deprecated(
			'tribe_ct_payment-options-title',
			[ $title ],
			'4.6.3',
			'tribe_community_tickets_payment_options_title',
			'The filter "tribe_ct_payment-options-title" has been renamed to "tribe_community_tickets_payment_options_title" to match plugin namespacing.'
		);

		$this->title = $title;

		add_action( 'tribe_community_events_after_event_list_top_buttons', [ $this, 'link' ] );
		add_action( 'tribe_community_tickets_payment_options_nav', [ $this, 'link' ] );
		add_action( 'tribe_community_tickets_attendees_report_nav', [ $this, 'link' ] );
		add_action( 'tribe_community_tickets_sales_report_nav', [ $this, 'link' ] );

		parent::__construct( $router );
	}

	/**
	 * Handles the rendering of the route
	 */
	public function callback( $arg = null ) {
		$community_events = Tribe__Events__Community__Main::instance();
		$community_tickets = tribe( 'community-tickets.main' );
		$community_tickets->require_login();

		add_filter( 'tribe_events_current_view_template', [ $community_events, 'default_template_placeholder' ] );
		tribe_asset_enqueue_group( 'events-styles' );

		$community_events->removeFilters();

		if (
			isset( $_POST['payment_options_nonce'] )
			&& wp_verify_nonce( $_POST['payment_options_nonce'], 'tribe_community_tickets_save_payment_options' )
		) {
			$community_tickets->payment_options_form()->save( get_current_user_id(), $_POST );
		}

		ob_start();

		$community_tickets->payment_options_form()->render();

		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Adds the route to the router
	 */
	public function add() {
		$payouts = tribe( 'community-tickets.payouts' );

		if ( ! current_user_can( 'edit_event_tickets' ) && ! $payouts->is_split_payments_enabled() ) {
			return;
		}

		parent::add();
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
		$path             = $community_events->getCommunityRewriteSlug() . '/' . $community_events->get_rewrite_slug( 'payment-options' );
		$path             .= $suffix;

		return $path;
	}//end path

	/**
	 * Hooked to the tribe_community_events_after_event_list_top_buttons action to add navigation.
	 */
	public function link() {
		$community_tickets = tribe( 'community-tickets.main' );
		$payouts = tribe( 'community-tickets.payouts' );

		if ( ! $community_tickets->is_enabled() ) {
			return;
		}//end if

		if ( ! $payouts->is_split_payments_enabled() && ! current_user_can( 'edit_event_tickets' ) ) {
			return;
		}//end if

		?>
		<a href="<?php echo esc_url( $this->url() ); ?>" class="tribe-community-tickets-payment-options-link button">
			<?php
			$text = __( 'Payment options', 'tribe-events-community' );
			$text = apply_filters( 'tribe_community_tickets_event_list_payment_options_button_text', $text );
			$text = apply_filters_deprecated(
				'tribe_ct_event_list_payment_options_button_text',
				[ $text ],
				'tribe_community_tickets_event_list_payment_options_button_text',
				'The filter "tribe_ct_event_list_payment_options_button_text" has been renamed to "tribe_community_tickets_event_list_payment_options_button_text" to match plugin namespacing.'
			);

			echo esc_html( $text );
			?>
		</a>
		<?php
	}//end link

	/**
	 * return the payment options link
	 *
	 * @return string
	 */
	public function url() {
		$ce_main = tribe( Tribe__Events__Community__Main::class );
		$url     = $ce_main->getUrl( 'payment-options', null, null, $ce_main->get_community_events_post_type() );
		$url     = apply_filters( 'tribe_community_tickets_payment_options_url', $url );
		$url     = apply_filters_deprecated(
			'tribe_ct_payment_options_url',
			[ $url ],
			'4.6.3',
			'tribe_community_tickets_payment_options_url',
			'The filter "tribe_ct_payment_options_url" has been renamed to "tribe_community_tickets_payment_options_url" to match plugin namespacing.'
		);

		return $url;
	}//end url
}
