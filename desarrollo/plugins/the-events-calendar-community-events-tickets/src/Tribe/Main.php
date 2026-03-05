<?php

use Tribe\Community\Tickets\Payouts;
use Tribe\Community\Tickets\Payouts\Service_Provider as Payouts_Provider;
use TEC\Tickets\Admin\Attendees\Page as Attendees_Page;

class Tribe__Events__Community__Tickets__Main {
	/**
	 * Option name to save all plugin options as a serialized array.
	 */
	const OPTIONNAME = 'tribe_community_events_tickets_options';

	/**
	 * The current version of Community Events Tickets.
	 */
	const VERSION = '4.9.6';


	/**
	 * The event form.
	 *
	 * @var Tribe__Events__Community__Tickets__Event_Form
	 */
	private $event_form;

	/**
	 * The FE payment options form.
	 *
	 * @var Tribe__Events__Community__Tickets__Payment_Options_Form
	 */
	private $payment_options_form;

	/**
	 * Plugin templates.
	 *
	 * @var Tribe__Events__Community__Tickets__Templates
	 */
	private $templates;

	/**
	 * Plugin update configuration object.
	 *
	 * @var Tribe__Events__Community__Tickets__PUE
	 */
	private $pue;

	/**
	 * WP_Router routes for the plugin.
	 *
	 * @var array
	 */
	public $routes = [];

	/**
	 * The plugin directory.
	 *
	 * @var string
	 */
	public $plugin_dir;

	/**
	 * The plugin path.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * The plugin slug.
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * The plugin URL.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * The plugin options.
	 *
	 * @var array
	 */
	protected static $options;

	/**
	 * Placeholder for the payouts object.
	 *
	 * @var Tribe\Community\Tickets\Payouts\Payout
	 */
	protected $payouts;

	/**
	 * Accepted payment fee settings
	 *
	 * @var array
	 */
	private $payment_fee_setting_options = [
		'absorb',
		'pass',
		'add',
	];

	/**
	 * Iterable list of required Paypal options.
	 *
	 * @var array
	 */
	public $paypal_required_options = [
		'paypal_invoice_prefix',
	];

	/**
	 * Default options
	 *
	 * @var array
	 */
	public $option_defaults = [
		'edit_event_tickets_cap'   => true,
		'enable_community_tickets' => false,
		'enable_image_uploads'     => true,
		'enable_split_payments'    => false,
		'split_payment_method'     => '',
		'payment_fee_setting'      => 'absorb',
		'paypal_api_password'      => '',
		'paypal_api_signature'     => '',
		'paypal_api_username'      => '',
		'paypal_application_id'    => '',
		'paypal_invoice_prefix'    => '',
		'paypal_receiver_email'    => '',
		'paypal_sandbox'           => false,
		'site_fee_flat'            => 0,
		'site_fee_on_free'         => true,
		'site_fee_percentage'      => 0,
		'site_fee_type'            => 'none',
		'default_provider_handler' => 'TEC_Tickets_Commerce_Module',
	];

	/**
	 * Singleton to instantiate the Community Tickets class
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Constructor!
	 */
	public function __construct() {
		$this->plugin_path = trailingslashit( EVENTS_COMMUNITY_TICKETS_DIR );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = trailingslashit( plugins_url( $this->plugin_dir ) );
		$this->plugin_slug = 'events-community-tickets';

		// Hook to 11 to make sure this gets initialized after events-tickets-woo
		add_action( 'tribe_plugins_loaded', [ $this, 'bootstrap' ] );
		add_action( 'woocommerce_cart_calculate_fees', [ $this, 'calculate_cart_fees' ] );
	}

	/**
	 * Bootstrap the plugin with the plugins_loaded action.
	 *
	 * @since 4.7.0
	 * @since 4.8.4 - Added functionality for Tickets Commerce
	 */
	public function bootstrap() {
		$this->register_resources();

		// No need to bootstrap, we already did.
		if ( has_action( 'init', [ $this, 'init' ] ) ) {
			return;
		}

		$this->pue = new Tribe__Events__Community__Tickets__PUE( "{$this->plugin_path}/events-community-tickets.php" );

		require_once $this->plugin_path . 'src/functions/template-tags.php';

		$this->hooks();
		$this->bind_implementations();
	}

	/**
	 * All your hooks are belong to us!
	 *
	 * @since 4.7.0
	 */
	public function hooks() {
		tribe_register_provider( Tribe__Events__Community__Tickets__Service_Provider::class );
		tribe_register_provider( Payouts_Provider::class );

		add_action( 'init', [ $this, 'init' ], 5 );

		add_action( 'tribe_load_text_domains', [ $this, 'load_text_domain' ] );

		// this allows event owners to fetch/read orders on posts they own
		add_filter( 'user_has_cap', [ $this, 'user_has_read_private_shop_orders' ], 10, 3 );
		add_filter( 'user_has_cap', [ $this, 'user_has_edit_event_tickets_cap' ], 1, 3 );
		add_filter( 'user_has_cap', [ $this, 'user_has_sell_event_tickets_cap' ], 2, 3 );
		add_filter( 'user_has_cap', [ $this, 'user_has_edit_tribe_events_cap' ], 2, 3 );

		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$this->event_form();
			$this->payment_options_form();
			$this->templates();

			add_filter( 'user_has_cap', [ $this, 'give_subscribers_upload_files_cap' ], 10, 3 );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_admin_resources' ], 11 );

		add_action( 'tribe_community_events_enqueue_resources', [ $this, 'maybe_enqueue_frontend' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_frontend' ] );

		add_filter( 'tribe_events_template_paths', [ $this, 'add_template_paths' ] );
		add_action( 'wp_router_generate_routes', [ $this, 'generate_routes' ] );
		add_action( 'tribe_events_community_event_list_table_row_actions', [ $this, 'report_links' ] );

		add_filter( 'tribe_community_settings_tab', [ $this, 'community_tickets_settings' ] );

		add_action( 'woocommerce_order_item_meta_start', [ $this, 'add_order_item_details' ], 10, 3 );

		add_action( 'wp_ajax_tribe-ticket-add-Tribe__Events__Tickets__Woo__Main', [ $this, 'ajax_handler_ticket_save' ], 9 );
		add_action( 'wp_ajax_tribe-ticket-edit-Tribe__Events__Tickets__Woo__Main', [ $this, 'ajax_handler_ticket_save' ], 9 );

		// Determine if the ticket price can be updated.
		add_filter( 'tribe_tickets_can_update_ticket_price', [ $this, 'can_update_ticket_price' ], 10, 2 );
		add_filter( 'tribe_tickets_disallow_update_ticket_price_message', [ $this, 'disallow_update_ticket_price_message' ], 10, 2 );

		add_action( 'tribe_tickets_plus_after_event_details_list', [ $this, 'order_report_organizer_data' ], 10, 2 );

		// Control the user's ability to delete tickets.
		add_filter( 'tribe_tickets_current_user_can_delete_ticket', [ $this, 'can_delete_existing_ticket' ], 10, 2 );

		// Control the user's ability to check in attendees.
		add_filter( 'tribe_tickets_user_can_manage_attendees', [ $this, 'user_can_manage_own_event_attendees' ], 11, 3 );

		// Compatibility with Event Tickets Plus.
		add_action( 'wp_ajax_tribe-ticket-add-Tribe__Tickets__Woo__Main', [ $this, 'ajax_handler_ticket_save' ], 9 );
		add_action( 'wp_ajax_tribe-ticket-edit-Tribe__Tickets__Woo__Main', [ $this, 'ajax_handler_ticket_save' ], 9 );

		// Ticket fee settings.
		add_filter( 'tribe_tickets_pass_fees_to_user', [ $this, 'pass_fees_to_user' ], 10, 2 );
		add_filter( 'tribe_tickets_fee_percent', [ $this, 'get_fee_percent' ] );
		add_filter( 'tribe_tickets_fee_flat', [ $this, 'get_fee_flat' ] );

		// Setup Help Tab texting.
		add_filter( 'tribe_support_registered_template_systems', [ $this, 'add_template_updates_check' ] );

		add_filter( 'tribe_tickets_get_default_module', [ $this, 'filter_prevent_edd_provider' ] );

		add_action( 'admin_init', [ $this, 'run_updates' ], 10, 0 );
	}

	/**
	 * Binds implementations that are specific to CT
	 *
	 * @since 4.8.4
	 *
	 * @return void
	 */
	public function bind_implementations() {

		tribe_register_provider( \TEC\Community_Tickets\Tickets\Commerce\Provider::class );

	}

	/**
	 * Bootstrap of the Plugin on Init
	 *
	 * @since 4.6.2
	 */
	public function init() {
		// Setup Community Tickets payment gateway adapter.
		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		// This has to happen after the service provider above is registered so that 'community-tickets.payouts' is registered.
		if ( $payouts->is_adaptive_payments_enabled() ) {
			add_filter( 'woocommerce_payment_gateways', [ $this, 'add_adapter' ] );
		}
	}

	/**
	 * Load this plugin's text domain.
	 *
	 * @since 4.7.4
	 */
	public function load_text_domain() {
		// Load text domain.
		$mopath = $this->plugin_dir . 'lang/';
		$domain = 'tribe-events-community-tickets';

		// If we don't have Common classes load the old fashioned way.
		if ( ! class_exists( 'Tribe__Main' ) ) {
			load_plugin_textdomain( $domain, false, $mopath );
		} else {
			// This will load `wp-content/languages/plugins` files first.
			Tribe__Main::instance()->load_text_domain( $domain, $mopath );
		}
	}

	/**
	 * Combined checks to ensure that we're on the admin when we think we are.
	 * The Community Plugins pull admin views into the front end and thus often
	 * reports `is_admin()` incorrectly.
	 *
	 * @since 4.7.1
	 *
	 * @return boolean
	 */
	public function is_really_admin() {
		// If we're on a Community event page, we're not in the admin.
		if ( tribe_is_community_my_events_page() ) {
			return false;
		}

		// If we're on a Community event edit page, we're not in the admin.
		if ( tribe_is_community_edit_event_page() ) {
			return false;
		}

		// If we've passed `is_admin` as a requrst var and it's false, we're not in the admin.
		return tribe_is_truthy( tribe_get_request_var( 'is_admin', is_admin() ) );
	}

	/**
	 * On Community Tickets we do not allow EDD
	 *
	 * @since 4.6.2
	 *
	 * @param string $provider Which provider is the default.
	 *
	 * @return string
	 */
	public function filter_prevent_edd_provider( $provider ) {
		$is_admin = $this->is_really_admin();

		if ( $is_admin ) {
			return $provider;
		}

		return 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main';
	}

	/**
	 * Register the community-tickets paths with TEC
	 */
	public function add_template_paths( $paths ) {
		$paths['community-tickets'] = $this->plugin_path;

		return $paths;
	}

	/**
	 * Auto-appends the author ID onto the SKU
	 */
	public function ajax_handler_ticket_save() {
		if ( ! isset( $_POST['formdata'] ) ) {
			return;
		}

		if ( ! isset( $_POST['post_ID'] ) ) {
			return;
		}

		if ( ! $post = get_post( $_POST['post_ID'] ) ) {
			return;
		}

		$form_data = wp_parse_args( $_POST['formdata'] );

		if ( empty( $form_data['ticket_name'] ) ) {
			$form_data['ticket_name'] = 'ticket';
		}

		// make sure the SKU is set to the correct value
		$form_data['ticket_woo_sku'] = "{$post->ID}-{$post->post_author}-" . sanitize_title( $form_data['ticket_name'] );

		$_POST['formdata'] = http_build_query( $form_data );
	}

	/**
	 * Add the adapter.
	 *
	 * @param array $methods WooCommerce payment methods.
	 *
	 * @return array          PayPal Adaptive Payments gateway.
	 */
	public function add_adapter( $methods ) {
		$methods[] = 'Tribe__Events__Community__Tickets__Adapter__WooCommerce_PayPal';

		return $methods;
	}

	/**
	 * Gateway object accessor method.
	 *
	 * @param string $gateway Which static gateway to retrieve.
	 *
	 * @return Tribe__Events__Community__Tickets__Gateway__Abstract|WP_Error Gateway object or error object if class does not exist.
	 */
	public function gateway( $gateway ) {
		static $gateways = [];

		if ( empty( $gateways[ $gateway ] ) ) {
			$gateway_class = "Tribe__Events__Community__Tickets__Gateway__{$gateway}";

			if ( ! class_exists( $gateway_class ) ) {
				return new WP_Error( "{$gateway_class} does not exist" );
			}

			$gateways[ $gateway ] = new $gateway_class;
		}

		return $gateways[ $gateway ];
	}

	/**
	 * Registers scripts and styles.
	 * Includes filters for version numbers
	 *
	 * @since 1.0
	 * @since 4.7.4 Change to use tribe_asset().
	 *
	 * @return void
	 */
	public function register_resources() {
		tribe_asset(
			$this,
			'events-community-tickets-admin-js',
			'events-community-tickets-admin.js',
			[ 'jquery' ]
		);

		tribe_asset(
			$this,
			'events-community-tickets-js',
			'events-community-tickets.js',
			[
				'jquery',
				'tribe-bumpdown',
			]
		);

		tribe_asset(
			$this,
			'events-community-tickets-admin-css',
			'events-community-tickets-admin.css'
		);

		tribe_asset(
			$this,
			'events-community-tickets-css',
			'events-community-tickets.css'
		);

		tribe_asset(
			$this,
			'events-community-tickets-shortcodes-css',
			'events-community-tickets-shortcodes.css',
			[
				'events-community-tickets-css',
			]
		);
	}

	/**
	 * Enqueue the admin resources where needed.
	 *
	 * @since 1.0
	 * @since 4.7.4 Change to tribe_asset_enqueue().
	 * @since 4.7.13 Replaced `tribe_events_page_tribe-common` to `tribe_events_page_tec-events-settings`.
	 *
	 * @param string $screen WP_Screen ID.
	 */
	public function maybe_enqueue_admin_resources( $screen ) {
		$ce_admin_pages = [
			'tribe_events_page_tec-events-settings',
			'tribe_events_page_tickets-orders',
			'tribe_events_page_tickets-attendees',
			'tribe_events_page_events-community-tickets-payouts',
		];

		if (
		in_array( $screen, $ce_admin_pages, true )
		) {
			tribe_asset_enqueue( 'events-community-tickets-admin-css' );
			tribe_asset_enqueue( 'events-community-tickets-admin-js' );
		}
	}

	/**
	 * Enqueue the front end resources, add nonces for forms
	 */
	public function maybe_enqueue_frontend() {
		$nonces = [
			'add_ticket_nonce'    => wp_create_nonce( 'add_ticket_nonce' ),
			'edit_ticket_nonce'   => wp_create_nonce( 'edit_ticket_nonce' ),
			'remove_ticket_nonce' => wp_create_nonce( 'remove_ticket_nonce' ),
			'ajaxurl'             => admin_url( 'admin-ajax.php' ),
		];

		wp_localize_script( 'events-community-tickets-js', 'TribeTickets', $nonces );

		$post = get_post();

		if (
			$post
			&& ! empty( $post->post_content )
			&& has_shortcode( $post->post_content, 'tribe_community_tickets' )
		) {
			tribe_asset_enqueue_group( 'events-admin' );
			tribe_asset_enqueue( 'events-community-tickets-css' );
		}

		if ( ! $this->is_really_admin() ) {
			// enqueue the styles so our page nav looks ok
			tribe_asset_enqueue( 'events-community-tickets-css' );
		}

		if (
			tribe_is_community_my_events_page()
			|| tribe_is_community_edit_event_page()
		) {
			tribe_asset_enqueue_group( 'event-tickets-admin' );
			tribe_asset_enqueue_group( 'event-tickets-plus-admin' );
		}
	}

	/**
	 * Event form object accessor method
	 */
	public function event_form() {
		if ( ! $this->event_form ) {
			$this->event_form = new Tribe__Events__Community__Tickets__Event_Form( $this );
		}

		return $this->event_form;
	}

	/**
	 * Payment options form object accessor method
	 */
	public function payment_options_form() {
		if ( ! $this->payment_options_form ) {
			$this->payment_options_form = new Tribe__Events__Community__Tickets__Payment_Options_Form;
			$this->payment_options_form->set_default( 'payment_fee_setting', $this->get_payment_fee_setting() );
		}

		return $this->payment_options_form;
	}

	/**
	 * Templates object accessor method
	 */
	public function templates() {
		if ( ! $this->templates ) {
			$this->templates = new Tribe__Events__Community__Tickets__Templates;
		}

		return $this->templates;
	}

	/**
	 * Add the upload_files capability to a user when they are uploading files
	 */
	public function give_subscribers_upload_files_cap( $all_caps, $caps, $args ) {
		// Bail if there isn't a cap or user_id
		if ( empty( $caps[0] ) || empty( $args[1] ) ) {
			return $all_caps;
		}

		$cap     = $caps[0];
		$user_id = absint( $args[1] );

		if ( 'upload_files' !== $cap ) {
			return $all_caps;
		}

		// If the user isn't logged in, bail
		if ( ! is_user_logged_in() ) {
			return $all_caps;
		}

		// If the user isn't uploading media, bail
		if ( false === strpos( $_SERVER['REQUEST_URI'], '/wp-admin/async-upload.php' ) ) {
			return $all_caps;
		}

		// If the user is originating the request from the dashboard, bail
		if ( false !== strpos( $_SERVER['HTTP_REFERER'], '/wp-admin/' ) ) {
			return $all_caps;
		}

		$base_url = get_bloginfo( 'url' );

		// If the request did not come from the site, bail
		if ( ! preg_match( "@^{$base_url}@", $_SERVER['HTTP_REFERER'] ) ) {
			return $all_caps;
		}

		$all_caps['upload_files'] = true;

		return $all_caps;
	}

	/**
	 * Returns whether or not split payments are enabled
	 *
	 * @depredated 4.7.0
	 */
	public function is_split_payments_enabled() {
		_deprecated_function( __METHOD__, '4.7.0', 'tribe( "community-tickets.payouts" )->is_split_payments_enabled()' );

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		return $payouts->is_split_payments_enabled();
	}

	/**
	 * Returns whether or not community tickets is enabled in settings
	 */
	public function is_enabled() {
		$options = $this->get_options();

		if ( empty( $options['enable_community_tickets'] ) ) {
			return false;
		}

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		// If we've enabled payouts but it doesn't have the necessary data, fail.
		if (
			$payouts->is_split_payments_enabled()
			&& ! $payouts->is_split_payment_functional()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Returns whether or not tickets are enabled for a given event
	 *
	 * @param mixed $event WP_Post or ID for event
	 *
	 * @return boolean
	 */
	public function is_enabled_for_event( $event ) {
		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		// If split payments are not enabled, we don't need to worry about the creator's paypal email address
		if ( ! $payouts->is_split_payments_enabled() ) {
			return true;
		}

		if ( ! $event ) {
			// If an event isn't passed in, assume we're creating an event and use the currently logged in user
			$user_id = get_current_user_id();
		} else {
			if ( ! $event instanceof WP_Post ) {
				if ( ! is_numeric( $event ) ) {
					return false;
				}

				$event = get_post( $event );
			}

			if ( ! $event ) {
				return false;
			}

			$user_id = $event->post_author;
		}

		$user_meta = $this->payment_options_form()->get_meta( $user_id );

		return filter_var( $user_meta['paypal_account_email'], FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Gets an event's payment fee setting.
	 *
	 * @param null|WP_Post $event Event object (if payment fee setting should be specific to event).
	 *
	 * @return string Payment fee setting.
	 */
	public function get_payment_fee_setting( $event = null ) {
		/** @var Tribe__Events__Community__Tickets__Fees $payout_fees */
		$payout_fees = tribe( 'community-tickets.fees' );

		$payment_fee_setting = $payout_fees->get_current_fee( 'operation' );

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		// If split payments are not enabled or organizer fee display is not enabled, return current fee setting.
		if ( ! $payouts->is_split_payments_enabled() || ! $payouts->is_organizer_fee_display_override_enabled() ) {
			return $payment_fee_setting;
		}

		if ( $event && ! $event instanceof WP_Post ) {
			$event = get_post( $event );
		}

		// If event was not found or author is not set, return current fee setting.
		if ( ! $event || 0 === $event->post_author ) {
			return $payment_fee_setting;
		}

		$creator_options = $this->payment_options_form()->get_meta_options( $event->post_author );

		/*
		 * If site payment fee setting is 'absorb' and the organizer fee display override is set to 'pass',
		 * only support the 'pass' override.
		 */
		if ( 'absorb' === $payment_fee_setting && 'pass' === $creator_options['payment_fee_setting'] ) {
			$payment_fee_setting = 'pass';
		}

		return $payment_fee_setting;
	}

	/**
	 * Add routes
	 */
	public function generate_routes( $router ) {
		$this->routes['attendees-report'] = new Tribe__Events__Community__Tickets__Route__Attendees_Report( $router );
		$this->routes['sales-report']     = new Tribe__Events__Community__Tickets__Route__Sales_Report( $router );

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		if ( $payouts->is_split_payments_enabled() ) {
			$this->routes['payment-options'] = new Tribe__Events__Community__Tickets__Route__Payment_Options( $router );
		}
	}

	/**
	 * Redirects if the user is not logged in
	 *
	 * @param int|null $event_id
	 */
	public function require_login( $event_id = null ) {
		if ( ! is_user_logged_in() ) {
			wp_redirect( tribe_get_events_link() );
			die;
		}

		if ( empty( $event_id ) ) {
			return;
		}

		$event = get_post( $event_id );

		if ( empty( $event ) || get_current_user_id() !== (int) $event->post_author ) {
			wp_redirect( tribe_get_events_link() );
			die;
		}
	}

	/**
	 * Hooked to the tribe_events_community_event_list_table_row_actions action to add navigation for reports.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function report_links( $post ) {
		if ( ! current_user_can( 'edit_event_tickets' ) ) {
			return;
		}

		if ( ! tribe_events_has_tickets( $post ) ) {
			return;
		}

		/**
		 * Allow filtering of attendees button text on the event list.
		 *
		 * @since 3.12
		 *
		 * @param string $attendees_button_text Current translated button text.
		 */
		$attendees_button_text = apply_filters( 'tribe-events-community-tickets-event-list-attendees-button-text', __( 'Attendees', 'tribe-events-community-tickets' ) );

		?>
		<br />
		<strong><?php echo esc_html__( 'Reports:', 'tribe-events-community-tickets' ); ?></strong>
		<a class="tribe-attendee-report" href="<?php echo esc_url( $this->routes['attendees-report']->url( $post->ID ) ); ?>">
			<?php echo esc_html( $attendees_button_text ); ?>
		</a>
		<?php
		// if event currently has at least one ticket, show the sales link.
		$event_tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $post->ID );
		$has_ticket = array_reduce( $event_tickets, function ( $has_ticket, $ticket ) {
			// If $has_ticket is true, no need to check other tickets.
			if ( ! empty( $has_ticket ) ) {
				return $has_ticket;
			}

			// if it's not an RSVP, it's a ticket.
			if ( isset( $ticket->provider_class ) && 'Tribe__Tickets__RSVP' !== $ticket->provider_class ) {
				return true;
			}

			return $has_ticket;
		}, false );
		if ( $has_ticket ) :

			/**
			 * Allow filtering of sales button text on the event list.
			 *
			 * @since 3.12
			 *
			 * @param string $sales_button_text Current translated button text.
			 */
			$sales_button_text = apply_filters( 'tribe-events-community-tickets-event-list-sales-button-text', __( 'Sales', 'tribe-events-community-tickets' ) );

			?>
				|
				<a class="tribe-sales-report" href="<?php echo esc_url( $this->routes['sales-report']->url( $post->ID ) ); ?>">
					<?php echo esc_html( $sales_button_text ); ?>
				</a>
			<?php
		endif;
	}

	/**
	 * Filter the community settings tab to include community tickets settings.
	 *
	 * @param array $settings Field settings for the community settings tab in the dashboard.
	 *
	 * @return array
	 */
	public function community_tickets_settings( $settings ) {
		include $this->plugin_path . 'src/admin-views/payment-options.php';

		return $settings;
	}

	/**
	 * Calculate fees for cart.
	 *
	 * @param WC_Cart $wc_cart
	 */
	public function calculate_cart_fees( $wc_cart ) {
		$cart = new Tribe__Events__Community__Tickets__Cart;
		$cart->calculate_cart_fees( $wc_cart );
	}

	/**
	 * Determines whether the currently logged in user has the correct cap.
	 *
	 * @param array $all_caps User capabilities.
	 * @param array $caps     Caps being checked.
	 * @param array $args     Additional user_cap args.
	 */
	public function user_has_edit_event_tickets_cap( $all_caps, $caps, $args ) {
		static $options;

		// Bail if there isn't a cap or user_id.
		if ( empty( $caps[0] ) || empty( $args[1] ) ) {
			return $all_caps;
		}

		$cap     = $caps[0];
		$user_id = $args[1];

		// Bail if this isn't the cap we care about
		if ( 'edit_event_tickets' !== $cap ) {
			return $all_caps;
		}

		if ( ! $options ) {
			$options = wp_parse_args( $this->get_options(), $this->option_defaults );
		}

		if ( ! isset( $all_caps[ $cap ] ) ) {
			// Assume the user has it - by default all users with accounts have it.
			$all_caps[ $cap ] = (bool) $options['edit_event_tickets_cap'];
		}

		// If split payments is enabled, let users create tickets.
		if ( ! $this->is_enabled() ) {
			$all_caps[ $cap ] = false;

			return $all_caps;
		}

		return $all_caps;
	}

	/**
	 * Determines whether or not the currently logged in user has the correct cap to sell tickets.
	 * (has PayPal info entered if split payments is enabled).
	 *
	 * @param array $all_caps User capabilities.
	 * @param array $caps     Caps being checked.
	 * @param array $args     Additional user_cap args.
	 *
	 * @return array
	 */
	public function user_has_sell_event_tickets_cap( $all_caps, $caps, $args ) {
		static $options;

		// Bail if there isn't a cap or user_id.
		if ( empty( $caps[0] ) || empty( $args[1] ) ) {
			return $all_caps;
		}

		$cap     = $caps[0];
		$user_id = $args[1];

		// Bail if this isn't the cap we care about.
		if ( 'sell_event_tickets' !== $cap ) {
			return $all_caps;
		}

		if ( ! $options ) {
			$options = $this->get_options();
		}

		if ( ! isset( $all_caps[ $cap ] ) ) {
			// Assume the user has it - by default all users with accounts have it.
			$all_caps[ $cap ] = user_can( $user_id, 'edit_event_tickets' );
		}

		/** @var Payouts $payouts */
		$payouts = tribe( 'community-tickets.payouts' );

		// If split payments is enabled, let users create tickets.
		if ( $payouts->is_split_payment_functional() ) {
			// If enabled, make sure the user has their paypal email set.
			$meta = get_user_meta( $user_id, Tribe__Events__Community__Tickets__Payment_Options_Form::$meta_key, true );

			if ( empty( $meta['paypal_account_email'] ) ) {
				$all_caps[ $cap ] = false;

				return $all_caps;
			}
		}

		return $all_caps;
	}

	/**
	 * Alter the read_private_shop_orders cap if the order contains a ticket that is attached to an event
	 * created by the current user.
	 *
	 * @param array $all_caps User capabilities.
	 * @param array $caps     Caps being checked.
	 * @param array $args     Additional user_cap args.
	 *
	 * @return array
	 */
	public function user_has_read_private_shop_orders( $all_caps, $caps, $args ) {
		// Bail if there isn't a cap or user_id.
		if ( empty( $caps[0] ) || empty( $args[1] ) ) {
			return $all_caps;
		}

		$cap     = $caps[0];
		$user_id = absint( $args[1] );

		// Bail if this isn't the cap we care about.
		if ( 'read_private_shop_orders' !== $cap ) {
			return $all_caps;
		}

		// If there isn't a post id for the order, bail.
		if ( empty( $args[2] ) ) {
			return $all_caps;
		}

		$order_id = absint( $args[2] );

		$query_args = [
			'post_type'   => 'tribe_wooticket',
			'post_status' => 'publish',
			'meta_key'    => '_tribe_wooticket_order',
			'meta_value'  => $order_id,
		];

		$posts = get_posts( $query_args );
		foreach ( $posts as $post ) {
			$event_id = (int) get_post_meta( $post->ID, '_tribe_wooticket_event', true );

			if ( empty( $event_id ) ) {
				continue;
			}

			$event = get_post( $event_id );

			if ( empty( $event ) || $user_id !== (int) $event->post_author ) {
				continue;
			}

			$all_caps[ $cap ] = true;

			return $all_caps;
		}

		return $all_caps;
	}

	/**
	 * Injects information about an order's line item "unused" params are passed to the template.
	 *
	 * @since Added check if ET+ is enabled.
	 *
	 * @param int           $item_id Item ID.
	 * @param WC_Order_Item $item    WooCommerce Order Item object.
	 * @param WC_Order      $order   WooCommerce Order object.
	 */
	public function add_order_item_details( $item_id, $item, $order ) {

		// If ET+ is enabled, bail as it causes a duplicate link.
		if ( class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return;
		}

		// If there isn't a product ID, bail.
		if ( empty( $item['product_id'] ) ) {
			return;
		}

		// If there isn't a product post for the product id, bail.
		if ( ! $product = get_post( $item['product_id'] ) ) {
			return;
		}

		// If there isn't an event ID, bail.
		if ( ! $event_id = get_post_meta( $product->ID, '_tribe_wooticket_for_event', true ) ) {
			return;
		}

		// If there isn't an event post for the event ID, bail.
		if ( ! $event = get_post( $event_id ) ) {
			return;
		}

		$title = get_the_title( $event_id );
		include Tribe__Events__Templates::getTemplateHierarchy( 'community-tickets/modules/email-item-event-details' );
	}

	/**
	 * Injects organizer data into the orders report "unused" params are passed to the template.
	 *
	 * @param WP_Post $event     Event post object.
	 * @param WP_User $organizer Community Organizer user object.
	 */
	public function order_report_organizer_data( $event, $organizer ) {
		include Tribe__Events__Templates::getTemplateHierarchy( 'community-tickets/modules/orders-report-after-organizer' );
	}

	/**
	 * Injects a meta note about site fees in the Order Report.
	 *
	 * @return string Meta note text.
	 */
	public function add_site_fee() {
		tribe( 'community-tickets.fees' )->get_fee_message();
	}

	/**
	 * Filters whether or not the ticket price can be updated.
	 *
	 * @param boolean $can_update Can the ticket price be updated?
	 * @param WP_Post $ticket     Ticket object.
	 *
	 * @return boolean
	 */
	public function can_update_ticket_price( $can_update, $ticket ) {
		if ( ! $ticket instanceof WP_Post ) {
			$ticket = get_post( $ticket );
		}

		if ( empty( $ticket->ID ) ) {
			return $can_update;
		}

		if ( $this->is_really_admin() ) {
			return $can_update;
		}

		$total_sales = get_post_meta( $ticket->ID, 'total_sales', true );

		if ( $total_sales ) {
			$can_update = false;
		}

		/**
		 * Allows filtering of the ability to edit ticket prices from the Community Events perspective.
		 *
		 * @since 4.7.1
		 *
		 * @param boolean $can_update Whether the user can update the ticket price.
		 */
		$can_update = apply_filters( 'tribe_community_events_tickets_can_update_ticket_price', $can_update, $ticket );

		return $can_update;
	}

	/**
	 * Modify the price field description if we've disabled price editing.
	 * Hooked to `tribe_tickets_price_description` in `event-tickets/src/admin-views/editor/fieldset/price.php`.
	 *
	 * @param string      $description_string The description string.
	 * @param WP_Post|int $ticket_id          The ticket object or its ID.
	 *
	 * @return string The new description string.
	 */
	public function tickets_price_description( $description_string, $ticket_id ) {
		if ( $this->can_update_ticket_price( true, $ticket_id ) ) {
			return $description_string;
		}

		return esc_html__( 'To update the pricing for tickets that already have sales, please contact the Site Admin.', 'tribe-events-community-tickets' );
	}

	/**
	 * Disable the price field if we've disabled price editing.
	 * Hooked to `tribe_tickets_price_disabled` in `event-tickets/src/admin-views/editor/fieldset/price.php`.
	 *
	 * @param boolean     $disabled  Whether the field should be disabled.
	 * @param WP_Post|int $ticket_id The ticket object or its ID.
	 *
	 * @return boolean Whether the field should be disabled.
	 */
	public function tickets_price_disabled( $disabled, $ticket_id ) {
		// if we can edit ticket price, pass on through.
		if ( $this->can_update_ticket_price( true, $ticket_id ) ) {
			return $disabled;
		}

		return true;
	}

	/**
	 * Filters the user's ability to delete existing tickets.
	 *
	 * The default behavior is to disallow deletion of tickets once sales have
	 * been made. This can be overridden via the following filter hook, which
	 * this callback itself runs on:
	 *
	 *     tribe_tickets_current_user_can_delete_ticket
	 *
	 * @param bool $can_delete Whether user is able to delete existing tickets before applying this function's logic.
	 * @param int  $ticket_id The Ticket's Post ID.
	 *
	 * @return bool Whether user is able to delete existing tickets after applying this function's logic.
	 */
	public function can_delete_existing_ticket( $can_delete, $ticket_id ) {
		$event = tribe_events_get_ticket_event( $ticket_id );
		if ( tribe_community_events_is_community_event( $event ) ) {
			$total_sales = get_post_meta( $ticket_id, 'total_sales', true );

			if ( $total_sales ) {
				return false;
			}
		}

		return $can_delete;
	}

	/**
	 * Filters the no-update message to display when updating tickets is disallowed.
	 *
	 * @param string  $message Message to display to user.
	 * @param WP_Post $ticket  Ticket object.
	 *
	 * @return string
	 */
	public function disallow_update_ticket_price_message( $message, $ticket ) {
		if ( empty( $ticket->ID ) ) {
			return $message;
		}

		$total_sales = get_post_meta( $ticket->ID, 'total_sales', true );

		if ( $total_sales ) {
			$message = esc_html__( 'Editing ticket prices is not allowed once purchases have been made.', 'tribe-events-community-tickets' );
		}

		return $message;
	}

	/**
	 * Filters whether or not fees are being passed to the end user (purchaser).
	 *
	 * @param boolean $pass_fees Whether or not to pass fees to user.
	 * @param int     $event_id  Event post ID.
	 *
	 * @return boolean
	 */
	public function pass_fees_to_user( $pass_fees, $event_id ) {
		$fee_setting = $this->get_payment_fee_setting( $event_id );

		return 'pass' === $fee_setting;
	}

	/**
	 * Filters the fee percentage to apply to a ticket/order.
	 *
	 * @param float $unused_fee_percent Fee percentage.
	 *
	 * @return float
	 */
	public function get_fee_percent( $unused_fee_percent ) {
		return $this->gateway( 'PayPal' )->fee_percentage();
	}

	/**
	 * Filters the flat fee to apply to a ticket/order.
	 *
	 * @param float $unused_fee_flat Flat fee.
	 *
	 * @return float
	 */
	public function get_fee_flat( $unused_fee_flat ) {
		return $this->gateway( 'PayPal' )->fee_flat();
	}

	/**
	 * Get purchase limit.
	 * @deprecated 4.5.6
	 */
	public function get_purchase_limit( $ticket_id = null ) {
		_deprecated_function( __METHOD__, '4.5.6' );

		$options = $this->get_options();

		$purchase_limit = isset( $options['purchase_limit'] ) ? absint( $options['purchase_limit'] ) : 0;

		if ( ! empty( $ticket_id ) ) {
			$ticket_limit = absint( get_post_meta( $ticket_id, '_ticket_purchase_limit', true ) );
			if ( '' !== $ticket_limit ) {
				$purchase_limit = absint( $ticket_limit );
			}
		}

		return $purchase_limit;
	}

	/**
	 * Filters add-to-cart quantity to enforce purchase limit.
	 *
	 * @deprecated 4.5.6
	 *
	 * @param string $cart_item_key WooCommerce cart item index.
	 * @param int    $product_id    WC_Product ID.
	 */
	public function enforce_purchase_limit_on_add( $cart_item_key, $product_id ) {
		_deprecated_function( __METHOD__, '4.5.6' );
		$item = WC()->cart->cart_contents[ $cart_item_key ];

		// If this isn't a product with an event, then let's not mess with the quantity
		if ( ! get_post_meta( $product_id, '_tribe_wooticket_for_event', true ) ) {
			return;
		}

		$purchase_limit = $this->get_purchase_limit( $product_id );

		if ( $item['quantity'] < $purchase_limit || 0 === $purchase_limit ) {
			return;
		}

		WC()->cart->set_quantity( $cart_item_key, $purchase_limit, false );
	}

	/**
	 * Filters update-cart quantity to enforce purchase limit.
	 *
	 * @deprecated 4.5.6
	 *
	 * @param int    $quantity      Amount to add/update.
	 * @param string $cart_item_key WooCommerce cart item index.
	 *
	 * @return int
	 */
	public function enforce_purchase_limit_on_update( $quantity, $cart_item_key ) {
		_deprecated_function( __METHOD__, '4.5.6' );
		$product = WC()->cart->cart_contents[ $cart_item_key ];

		// If this isn't a product with an event, then let's not mess with the quantity
		if ( ! get_post_meta( $product['product_id'], '_tribe_wooticket_for_event', true ) ) {
			return $quantity;
		}

		$purchase_limit = $this->get_purchase_limit( $product['product_id'] );

		if ( $purchase_limit > 0 && $quantity > $purchase_limit ) {
			$quantity = $purchase_limit;
		}

		return $quantity;
	}

	/**
	 * Filters update-cart quantity to enforce purchase limit.
	 *
	 * @deprecated 4.5.6
	 *
	 * @param int $purchase_limit The purchase limit for an item.
	 */
	public function set_default_purchase_limit( $purchase_limit ) {
		_deprecated_function( __METHOD__, '4.5.6' );
		$purchase_limit = $this->get_purchase_limit();

		return $purchase_limit;
	}

	/**
	 * Maybe display a notice about purchase limits.
	 * @deprecated 4.5.6
	 */
	public function maybe_display_purchase_limit( $ticket ) {
		_deprecated_function( __METHOD__, '4.5.6' );
		if ( empty( $ticket->ID ) ) {
			return;
		}

		$purchase_limit = $this->get_purchase_limit( $ticket->ID );

		if ( ! $purchase_limit ) {
			return;
		}
		?>
		<div class="tribe-events-community-tickets-purchase-limit">
			<?php
			printf(
				esc_html__( 'Limited to %1$s per order.', 'tribe-events-community-tickets' ),
				esc_html( $purchase_limit )
			);
			?>
		</div>
		<?php
	}

	/**
	 * Asserts whether the user is editing an own auto-draft event.
	 *
	 * @return bool
	 */
	protected function editing_ticket_on_own_autodraft() {
		if ( empty( $_POST['post_ID'] ) || empty( $_POST['nonce'] ) ) {
			return false;
		}

		$post = get_post( $_POST['post_ID'] );

		if ( empty( $post ) || ! tribe_is_event( $post->ID ) ) {
			return false;
		}

		$legit_actions = [
			'add_ticket_nonce',
			'edit_ticket_nonce',
			'remove_ticket_nonce',
		];

		do {
			$can = wp_verify_nonce( $_POST['nonce'], current( $legit_actions ) );
		} while ( ! $can && next( $legit_actions ) );

		return $can
			&& ( $post->post_author == get_current_user_id() )
			&& ( $post->post_status == 'auto-draft' );
	}

	/**
	 * Alter the `edit_tribe_events` cap if the request comes during an AJAX
	 * ticket save/update/remove operation on an event auto-draft.
	 *
	 * Even if the option that allows users that submitted Community
	 * Events to edit their events later is off users submitting the first
	 * draft of an event should still be able to create, update and remove
	 * tickets from the event draft.
	 *
	 * @param array $all_caps User capabilities.
	 * @param array $caps     Caps being checked.
	 *
	 * @return array
	 */
	public function user_has_edit_tribe_events_cap( $all_caps, $caps ) {
		if ( empty( $caps[0] ) || 'edit_tribe_events' !== $caps[0] ) {
			return $all_caps;
		}

		if ( Tribe__Main::instance()->doing_ajax() && $this->editing_ticket_on_own_autodraft() ) {
			$all_caps['edit_tribe_events'] = true;
		}

		return $all_caps;
	}


	/**
	 * Allows event creator to edit attendees if allowUsersToEditSubmissions is true.
	 *
	 * @since 4.6.1
	 *
	 * @param boolean $user_can user can/can't edit.
	 * @param int     $user_id  ID of user to check, uses current user if empty.
	 * @param int     $event_id Event ID.
	 *
	 * @return boolean
	 */
	public function user_can_manage_own_event_attendees( $user_can, $user_id, $event_id ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Cannot manage attendees without user.
		if ( empty( $user_id ) ) {
			return false;
		}

		// Cannot manage attendees without event, when not on the attendees page.
		if ( ! tribe( Attendees_Page::class )->is_on_page() && empty( $event_id ) ) {
			return false;
		}

		// Can manage attendees from admin area.
		if ( is_admin() ) {
			return true;
		}

		// Cannot determine management if origin is not current origin.
		if ( 'community-events' !== get_post_meta( $event_id, '_EventOrigin', true ) ) {
			return $user_can;
		}

		// Cannot manage attendees that they do not own.
		if ( (int) $user_id !== (int) get_post_field( 'post_author', $event_id ) ) {
			return false;
		}

		/** @var Tribe__Events__Community__Main $ce_main */
		$ce_main = tribe( 'community.main' );

		// Cannot manage attendees if they are not allowed to edit submissions.
		if ( ! $ce_main->getOption( 'allowUsersToEditSubmissions' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get all options for the plugin.
	 *
	 * @since 4.6.2
	 *
	 * @param bool $force
	 *
	 * @return array The current settings for the plugin.
	 */
	public static function get_options( $force = false ) {
		if ( ! isset( self::$options ) || $force ) {
			$options = get_option( self::OPTIONNAME, [] );

			/**
			 * Filter the plugin options after retrieval from the database
			 *
			 * @since 4.6.2
			 *
			 * @param array $options The unserialized values.
			 */
			self::$options = apply_filters( 'tribe_community_events_tickets_get_options', $options );
		}

		return self::$options;
	}

	/**
	 * Get value for a specific option.
	 *
	 * @since 4.6.2
	 *
	 * @param string $option_name Name of option.
	 * @param mixed  $default     Default value.
	 * @param bool   $force
	 *
	 * @return mixed Results of option query.
	 */
	public function get_option( $option_name, $default = '', $force = false ) {
		if ( ! $option_name ) {
			return;
		}

		if ( ! isset( self::$options ) || $force ) {
			self::get_options( $force );
		}

		$option = $default;

		if ( isset( self::$options[ $option_name ] ) ) {
			$option = self::$options[ $option_name ];
		}

		/**
		 * Filter a single option value.
		 *
		 * @since 4.6.2
		 *
		 * @param string $option      Option value.
		 * @param string $default     Default value.
		 * @param string $option_name Name of option.
		 */
		return apply_filters( 'tribe_get_single_option', $option, $default, $option_name );
	}

	/**
	 * Set value for a specific option.
	 *
	 * @since 4.6.2
	 *
	 * @param string $option_name Name of option.
	 * @param string $value       Value to set.
	 */
	public function set_option( $option_name, $value ) {
		if ( ! $option_name ) {
			return;
		}

		if ( ! isset( self::$options ) ) {
			self::get_options();
		}

		self::$options[ $option_name ] = $value;

		update_option( self::OPTIONNAME, self::$options );
	}

	/**
	 * Run Updater on Plugin Updates.
	 *
	 * @since 4.5.4
	 */
	public function run_updates() {
		if ( ! class_exists( 'Tribe__Updater' ) ) {
			return; // Core needs to be updated for compatibility.
		}

		$updater = new Tribe__Events__Community__Tickets__Updater( self::VERSION );

		if ( $updater->update_required() ) {
			$updater->do_updates();
		}
	}

	/**
	 * Sets up class autoloading for this plugin.
	 *
	 * @deprecated 4.6
	 *
	 */
	public function autoloading() {
		_deprecated_function( __METHOD__, '4.6' );

		if ( ! class_exists( 'Tribe__Autoloader' ) ) {
			return;
		}

		$autoloader = Tribe__Autoloader::instance();
		$autoloader->register_prefix( 'Tribe__Events__Community__Tickets__', dirname( __FILE__ ), 'events-community-tickets' );
		$autoloader->register_autoloader();
	}

	/**
	 * Registers this plugin as being active for other tribe plugins and extensions.
	 *
	 * @deprecated 4.6
	 *
	 * @return bool Indicates if Tribe Common wants the plugin to run.
	 */
	public function register_active_plugin() {
		_deprecated_function( __METHOD__, '4.6' );

		if ( ! function_exists( 'tribe_register_plugin' ) ) {
			return true;
		}

		return tribe_register_plugin( EVENTS_COMMUNITY_TICKETS_FILE, __CLASS__, self::VERSION );
	}

	/**
	 * Injects a meta note about site fees in the Order Report.
	 *
	 * @deprecated 4.7.0
	 *
	 * @param WP_Post $unused_event Event post.
	 * @param WP_User $organizer    Community Organizer user object.
	 *
	 * @return string Meta note text.
	 */
	public function order_report_site_fees_note( $unused_event, $organizer ) {
		_deprecated_function( __METHOD__, '4.7.0' );

		$options = $this->get_options();
		$gateway = self::instance()->gateway( 'PayPal' );

		$flat       = $gateway->fee_flat;
		$percentage = $gateway->fee_percentage;

		if ( tribe( 'community-tickets.fees' )->is_flat_fee( $options['site_fee_type'] ) ) {
			$flat += (float) $options['site_fee_flat'];
		}

		if ( tribe( 'community-tickets.fees' )->is_percentage_fee( $options['site_fee_type'] ) ) {
			$percentage += (float) $options['site_fee_percentage'];
		}

		$flat_message = sprintf(
			__( 'Site Fee: %s per order', 'tribe-events-community-tickets' ),
			esc_html( tribe_format_currency( number_format_i18n( $flat, 2 ) ) )
		);

		$percentage_message = sprintf(
			__( 'Site Fee Percentage: %s%%', 'tribe-events-community-tickets' ),
			esc_html( $percentage )
		);

		if ( 'flat' === $options['site_fee_type'] ) {
			return $flat_message;
		} elseif ( 'percentage' === $options['site_fee_type'] ) {
			return $percentage_message;
		} elseif ( 'flat-and-percentage' === $options['site_fee_type'] ) {
			return "{$flat_message}, {$percentage_message}";
		}
	}

	/**
	 * Register Community Tickets with the template update checker.
	 *
	 * @since  4.7.7  Updated Template directory structure.
	 *
	 * @param array $plugins List of plugins with template structure.
	 *
	 * @return array List of plugins with template structure.
	 */
	public function add_template_updates_check( $plugins ) {
		$plugins[ __( 'Community Events Tickets', 'tribe-events-community-tickets' ) ] = [
			self::VERSION,
			$this->plugin_path . 'src/views/community-tickets',
			trailingslashit( get_stylesheet_directory() ) . 'tribe/community-tickets',
		];

		$plugins[ __( 'Community Events Tickets - Legacy', 'tribe-events-community-tickets' ) ] = [
			self::VERSION,
			$this->plugin_path . 'src/views/community-tickets',
			trailingslashit( get_stylesheet_directory() ) . 'tribe-events/community-tickets',
		];

		return $plugins;
	}
}
