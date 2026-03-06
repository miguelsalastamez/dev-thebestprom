<?php

// Don't load directly.
use TEC\Events_Community\Settings\Default_Settings_Strategy;
use TEC\Events_Community\Submission\Messages;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'Tribe__Events__Community__Main' ) ) {
	/**
	 * Tribe Community main class
	 *
	 * @since   1.0
	 * @author  The Events Calendar
	 * @package Tribe__Events__Community__Main
	 */
	class Tribe__Events__Community__Main {

		/**
		 * The current version of Community
		 */
		const VERSION = '5.0.7';

		/**
		 * Singleton instance variable
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Whether before and after event HTML should be printed on the page.
		 *
		 * @var bool
		 */
		protected $should_print_before_after_html = true;

		/**
		 * Loadscripts or not
		 *
		 * @var bool
		 */
		private $loadScripts = false;

		/**
		 * plugin options
		 *
		 * @var array
		 */
		protected static $options;

		/**
		 * Whether the events calendar plugin is installed and activated.
		 *
		 * @var bool
		 */
		protected static $tec_installed;

		/**
		 * this plugin's directory
		 *
		 * @var string
		 */
		public $pluginDir;

		/**
		 * this plugin's path
		 *
		 * @var string
		 */
		public $pluginPath;

		/**
		 * this plugin's url
		 *
		 * @var string
		 */
		public $pluginUrl;

		/**
		 * this plugin's slug
		 *
		 * @var string
		 */
		public $pluginSlug;

		/**
		 * tribe url (used for calling the mothership)
		 *
		 * @var string
		 */
		public static $tribeUrl = 'http://tri.be/';

		/**
		 * default event status
		 *
		 * @var string
		 */
		public $defaultStatus;

		/**
		 * Setting to allow anonymous submissions.
		 *
		 * @var bool
		 */
		public $allowAnonymousSubmissions;

		/**
		 * Setting to allow editing submissions.
		 *
		 * @var bool
		 */
		public $allowUsersToEditSubmissions;

		/**
		 * Setting to allow deletion of submissions.
		 *
		 * @var bool
		 */
		public $allowUsersToDeleteSubmissions;

		/**
		 * setting to trash items instead of permanent delete
		 *
		 * @var bool
		 */
		public $trashItemsVsDelete;

		/**
		 * setting to use visual editor
		 *
		 * @var bool
		 */
		public $useVisualEditor;

		/**
		 * setting to control # of events per page
		 *
		 * @var int
		 */
		public $eventsPerPage;

		/**
		 * setting for pagination range
		 *
		 * @var string
		 */
		public $paginationRange;

		/**
		 * message to be displayed to the user
		 *
		 * @var array
		 */
		public $messages;

		/**
		 * the type of the message (error, notice, etc.)
		 *
		 * @var string
		 */
		public $messageType;

		/**
		 * the rewrite slug to use
		 *
		 * @var string
		 */
		public $communityRewriteSlug;

		/**
		 * Array of rewrite slugs for different components
		 *
		 * @var array
		 */
		public $rewriteSlugs;

		/**
		 * Attributes of current location.
		 *
		 * @var array
		 */
		public $context;

		/**
		 * is the current page the my events list?
		 *
		 * @var bool
		 */
		public $isMyEvents = false;

		/**
		 * is the current page the event edit page?
		 *
		 * @var bool
		 */
		public $isEditPage = false;

		/**
		 * should the permalinks be flushed upon plugin load?
		 *
		 * @var bool
		 */
		public $maybeFlushRewrite;

		/**
		 * @var Tribe__Events__Community__Anonymous_Users
		 */
		public $anonymous_users;

		/**
		 * @var array
		 */
		public $blockRolesFromAdmin;

		/**
		 * @var array
		 */
		public $blockRolesList;

		/**
		 * @var bool
		 */
		public $emailAlertsEnabled;

		/**
		 * @var array
		 */
		public $users_can_create;

		/**
		 * @var array
		 */
		public $emailAlertsList;


		/**
		 * @var
		 */
		public $eventListDateFormat;

		/**
		 * The login form ID.
		 *
		 * Used for WP login form ID, hidden login submission field name, and query parameter.
		 *
		 * @since 4.6.3
		 *
		 * @var string
		 */
		private $login_form_id = 'tribe_events_community_login';

		/**
		 * @var int The ID of a page with the community shortcode on it
		 */
		private $tcePageId = null;

		/** @var Tribe__Events__Community__Captcha__Abstract_Captcha */
		private $captcha = null;

		/** @var Tribe__Events__Community__Event_Form */
		public $form;

		/**
		 * The default slugs to use for rewrites.
		 *
		 * @since 4.6.3
		 *
		 * @var array
		 */
		public $default_rewrite_slugs = [
				'add'       => 'add',
				'list'      => 'list',
				'edit'      => 'edit',
				'delete'    => 'delete',
				'event'     => 'event',
				'events'    => 'events',
				'community' => 'community',
			];

		/**
		 * A meta field to help us track if an event's "Submitted" email alert has already been sent.
		 *
		 * @since 4.5.11
		 *
		 * @var string
		 */
		private static $submission_email_sent_meta_key = '_tribe_community_submitted_email_sent';

		/**
		 * Holds the multisite default options values for CE.
		 *
		 * @var array
		 */
		public static $tribeCommunityEventsMuDefaults;

		/**
		 * option name to save all plugin options under
		 * as a serialized array
		 */
		const OPTIONNAME = 'tribe_community_events_options';

		/**
		 * Class constructor
		 * Sets all the class vars up and such
		 *
		 * @since 1.0
		 * @since 5.0.0.1 Added compatibility check for WooCommerce HPOS.
		 *
		 * @param bool $tec_installed Whether The Events Calendar plugin is installed or not.
		 */
		public function __construct( bool $tec_installed = true ) {
			self::$tec_installed = $tec_installed;

			// Load multisite defaults
			if ( is_multisite() ) {
				$tribe_community_events_mu_defaults = [];

				if ( file_exists( WP_CONTENT_DIR . '/tribe-events-mu-defaults.php' ) ) {
					include_once( WP_CONTENT_DIR . '/tribe-events-mu-defaults.php' );
				}

				self::$tribeCommunityEventsMuDefaults = apply_filters( 'tribe_community_events_mu_defaults', $tribe_community_events_mu_defaults );
			}

			self::set_woocommerce_compatibility_checks();

			// get options
			$this->defaultStatus                 = $this->getOption( 'defaultStatus' );
			$this->allowAnonymousSubmissions     = $this->getOption( 'allowAnonymousSubmissions' );
			$this->allowUsersToEditSubmissions   = $this->getOption( 'allowUsersToEditSubmissions' );
			$this->allowUsersToDeleteSubmissions = $this->getOption( 'allowUsersToDeleteSubmissions' );
			$this->trashItemsVsDelete            = $this->getOption( 'trashItemsVsDelete' );
			$this->useVisualEditor               = $this->getOption( 'useVisualEditor' );
			$this->eventsPerPage                 = $this->getOption( 'eventsPerPage', 10 );
			$this->eventListDateFormat           = $this->getOption( 'eventListDateFormat' );
			$this->paginationRange               = 3;
			$this->defaultStatus                 = $this->getOption( 'defaultStatus' );
			$this->emailAlertsEnabled            = $this->getOption( 'emailAlertsEnabled' );
			$emailAlertsList                     = $this->getOption( 'emailAlertsList' );

			$this->emailAlertsList = explode( "\n", $emailAlertsList );

			$this->blockRolesFromAdmin = $this->getOption( 'blockRolesFromAdmin' );
			$this->blockRolesList      = $this->getOption( 'blockRolesList' );

			$this->maybeFlushRewrite = $this->getOption( 'maybeFlushRewrite' );

			if ( $this->blockRolesFromAdmin ) {
				add_action( 'init', [ $this, 'blockRolesFromAdmin' ] );
			}

			$this->pluginPath = trailingslashit( dirname( dirname( dirname( __FILE__ ) ) ) );
			$this->pluginDir  = trailingslashit( basename( $this->pluginPath ) );
			$this->pluginUrl  = plugins_url() . '/' . $this->pluginDir;
			$this->pluginSlug = 'events-community';

			$this->register_active_plugin();

			$this->isMyEvents = false;
			$this->isEditPage = false;

			add_shortcode( 'tribe_community_events_title', [ $this, 'doShortCodeTitle' ] );

			//allow shortcodes for dynamic titles
			add_filter( 'the_title', 'do_shortcode' );
			add_filter( 'wp_title', 'do_shortcode' );

			if ( '' == get_option( 'permalink_structure' ) ) {
				add_action( 'template_redirect', [ $this, 'maybeRedirectMyEvents' ] );
			} else {
				add_action( 'template_redirect', [ $this, 'redirectUglyUrls' ] );
			}

			/**
			 * In 3.5 this is causing an error moved self::maybeLoadAssets(); into function init()...
			 * Also is important to remember that using methods with Params we need to make sure the Hook doesn't pass any params.
			 * In the case of `wp` it passes an instance of the class WP which was breaking how maybeLoadAssets works.
			 *
			 * @central #71943
			 */
			add_action( 'wp', [ $this, 'maybeLoadAssets' ], 10, 0 );

			add_action( 'tribe_load_text_domains', [ $this, 'loadTextDomain' ], 1 );

			add_action( 'init', [ $this, 'init' ], 5 );

			add_action( 'init', [ $this, 'load_captcha_plugin' ], 11 );

			add_action( 'wp_before_admin_bar_render', [ $this, 'addCommunityToolbarItems' ], 20 );

			add_filter( 'tribe_tickets_user_can_manage_attendees', [ $this, 'user_can_manage_own_event_attendees' ], 10, 3 );

			// Tribe common resources
			include_once( $this->pluginPath . 'vendor/the-events-calendar/wp-router/wp-router.php' );

			add_filter( 'query_vars', [ $this, 'communityEventQueryVars' ] );

			// Priority set to 11 so some core body_class items can be removed after added.
			add_filter( 'body_class', [ $this, 'setBodyClasses' ], 11 );

			// Hook into templates class and add theme body classes
			add_filter( 'body_class', [ tribe( Tribe__Events__Community__Theme_Compatibility::class ), 'add_body_classes' ], 55 );

			// ensure that we don't include tabindexes in our form fields
			add_filter( 'tribe_events_tab_index', '__return_null' );

			// options page hook
			add_action( 'tribe_settings_do_tabs', [ $this, 'do_settings' ], 12, 2 );

			add_action( 'plugin_action_links_' . trailingslashit( $this->pluginDir ) . 'Main.php', [ $this, 'addLinksToPluginActions' ] );

			add_filter( 'tribe-events-pro-support', [ $this, 'support_info' ] );

			add_action( 'tribe_community_before_event_page', [ $this, 'maybe_delete_featured_image' ], 10, 1 );
			add_filter( 'tribe_help_tab_forums_url', [ $this, 'helpTabForumsLink' ], 100 );

			add_action( 'save_post', [ $this, 'flushPageIdTransient' ], 10, 1 );

			add_filter( 'user_has_cap', [ $this, 'filter_user_caps' ], 10, 3 );

			if ( is_multisite() ) {
				add_action( 'tribe_settings_get_option_value_pre_display', [ $this, 'multisiteDefaultOverride' ], 10, 3 );
			}

			add_filter( 'tribe_events_multiple_organizer_template', [ $this, 'overwrite_multiple_organizers_template' ] );

			add_action( 'plugins_loaded', [ $this, 'register_resources' ] );

			add_action( 'admin_init', [ $this, 'run_updates' ], 10, 0 );

			add_action( 'wp_ajax_tribe_events_community_delete_post', [ $this, 'ajaxDoDelete' ] );

			// Login form.
			add_filter( 'login_form_bottom', [ $this, 'add_hidden_form_fields_to_login_form' ] );
			add_filter( 'authenticate', [ $this, 'login_form_authentication' ], 70, 3 );
			add_action( 'wp_login_failed', [ $this, 'redirect_failed_login_to_front_end' ] );
			add_action( 'tribe_community_before_login_form', [ $this, 'output_login_form_notices' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_admin_assets' ], 20 );

			add_filter( 'tec_events_linked_posts_my_posts_post_status', [ $this, 'linked_posts_post_status' ], 10, 2 );
			add_filter( 'tec_events_linked_posts_all_posts_post_status', [ $this, 'linked_posts_post_status' ], 10, 2 );
			add_filter( 'tribe_events_get_linked_post_info_args', [ $this, 'linked_post_info_args' ], 10, 3 );

			add_filter( 'tribe_events_assets_should_enqueue_frontend' , [ $this, 'should_enqueue_tec_frontend' ] );

			// Binding the Implementations needs to happen to plugins_loaded
			$this->bind_implementations();
		}

		/**
		 * Whether The Events Calendar plugin is installed or not.
		 *
		 * It also accounts for the required minimum version.
		 *
		 * @return bool
		 */
		public function is_tec_installed() {
			return self::$tec_installed;
		}

		/**
		 * Whether Event Tickets is installed or not.
		 *
		 * Accounts for the required minimum version.
		 *
		 * @return bool
		 */
		public function is_et_installed() {
			try {
				tribe( 'community-tickets.main' );
			} catch ( Exception $e ) {
				return false;
			}

			return true;
		}

		/**
		 * Registers this plugin as being active for other tribe plugins and extensions.
		 *
		 * @return bool Indicates if Tribe Common wants the plugin to run
		 */
		public function register_active_plugin() {
			if ( ! function_exists( 'tribe_register_plugin' ) ) {
				return true;
			}

			return tribe_register_plugin( EVENTS_COMMUNITY_FILE, __CLASS__, self::VERSION );
		}

		/**
		 * Method used to overwrite the admin template for multiple organizers
		 *
		 * @param string $template The original template
		 *
		 * @return string
		 */
		public function overwrite_multiple_organizers_template( $template ) {
			if ( is_admin() ) {
				return $template;
			}

			$community_file = Tribe__Events__Community__Templates::getTemplateHierarchy( 'community/modules/organizer-multiple.php' );

			ob_start();
			include $community_file;
			$community_html = trim( ob_get_clean() );

			// Only use this URL if the template is not empty
			if ( empty( $community_html ) ) {
				return $template;
			}

			return $community_file;
		}

		/**
		 * Object accessor method for the Event_Form object
		 *
		 * @return Tribe__Events__Community__Event_Form
		 */
		public function event_form() {
			if ( ! $this->form ) {
				$event = null;

				if ( ! empty( $_GET['event_id'] ) ) {
					$event = get_post( absint( $_GET['event_id'] ) );
				}

				$this->form = new Tribe__Events__Community__Event_Form( $event );
			}

			return $this->form;
		}//end event_form

		/**
		 * Determines what assets to load.
		 *
		 * @param bool $force
		 */
		public function maybeLoadAssets( $force = false ) {
			$force = tribe_is_truthy( $force );

			// We are not forcing if it's not a boolean
			if ( ! is_bool( $force ) ) {
				$force = false;
			}

			// If we are forcing it we just bail
			if ( ! $force && ! tribe_is_community_my_events_page() && ! tribe_is_community_edit_event_page() ) {
				return;
			}

			// Disable comments on this page.
			add_filter( 'comments_template', [ $this, 'disable_comments_on_page' ] );

			// Load EC resources.
			if ( did_action( 'wp_enqueue_scripts' ) ) {
				$this->enqueue_assets();
			} else {
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 20 );
			}
		}

		/**
		 * Trigger FE asset loading from The Events Calendar on the edit and list pages.
		 *
		 * @since 4.10.18
		 *
		 * @param bool $should_enqueue Whether the assets should be enqueued.
		 */
		public function should_enqueue_tec_frontend( $should_enqueue ) : bool {
			return $should_enqueue || tribe_is_community_my_events_page() || tribe_is_community_edit_event_page();
		}

		/**
		 * Registers scripts and styles.
		 */
		public function register_resources() {
			tribe_asset( $this, 'tribe-events-community-list', 'tribe-events-community-list.css', [ 'tec-variables-skeleton', 'tec-variables-full' ] );
			tribe_asset( $this, 'tribe-events-community-shortcodes', 'tribe-events-community-shortcodes.css' );

			// Our stylesheet
			tribe_asset(
				$this,
				$this->get_community_events_post_type() . '-community-styles',
				'tribe-events-community.css',
				[
					'tec-variables-skeleton',
					'tec-variables-full',
					'tribe-datepicker',
					'tribe-select2-css',
					'tribe-common-admin',
					'tribe-dependency-style',
					'tribe-events-community-list',
				]
			);

			// Admin stylesheet
			tribe_asset(
				$this,
				$this->get_community_events_post_type() . '-community-admin-styles',
				'tribe-events-community-admin.css',
				[]
			);

			if ( ! class_exists( 'Tribe__Events__Community__Templates' ) ) {
				require_once EVENTS_COMMUNITY_DIR . '/src/Tribe/Tribe__Events__Templates.php';
			}

			// Custom stylesheet
			$override_sheet = Tribe__Templates::locate_stylesheet( 'tribe-events/community/tribe-events-community.css' );

			if ( ! empty( $override_sheet ) && file_exists( $override_sheet ) ) {
				tribe_asset(
					$this,
					'tribe-events-community-override-style',
					$override_sheet,
					[],
					'wp_enqueue_scripts',
					[
						'groups' => [ 'events-styles' ],
					]
				);
			}

			// Our javascript
			tribe_asset(
				$this,
				$this->get_community_events_post_type() . '-community',
				'tribe-events-community.js',
				[
					'jquery',
					'tribe-dependency',
				]
			);
		}

		/**
		 * Check if the override stylesheet exists.
		 *
		 * @since 5.0.0
		 *
		 * @return bool
		 */
		public function override_style_exists(): bool {
			_deprecated_function( __METHOD__, '5.0.1', 'No replacement.' );

			$file = Tribe__Templates::locate_stylesheet( 'tribe-events/community/tribe-events-community.css' );
			return $file && file_exists( $file );
		}

		/**
		 * Enqueue on Community Events Pages
		 *
		 * @since  4.4
		 *
		 * @return void
		 */
		public function enqueue_assets() {
			/** @var Tribe__Assets $assets */
			$assets = tribe( 'assets' );

			// Remove front-end scripts in case they're enqueued.
			$assets->remove( 'tribe-events-pro' );
			$assets->remove( 'tribe-events-pro-geoloc' );

			tribe_asset_enqueue_group( 'events-admin' );

			tribe_asset_enqueue( 'tribe-events-dynamic' );
			tribe_asset_enqueue( 'tribe-jquery-timepicker-css' );

			tribe_asset_enqueue( $this->get_community_events_post_type() . '-community-styles' );
			tribe_asset_enqueue( $this->get_community_events_post_type() . '-community' );

			$required_fields = $this->required_fields_for_submission();
			$error_messages  = [];
			$handler         = new Tribe__Events__Community__Submission_Handler( [], null );
			$messages        = Messages::get_instance();
			$validator       = tribe( TEC\Events_Community\Submission\Validator::class );

			foreach ( $required_fields as $field => $key ) {
				$label = $validator->get_field_label( $key );

				// Workaround for `post_content` alias.
				$key = 'post_content' === $key ? 'tcepostcontent' : $key;

				/* Translators : %s the form field label for required fields. */
				$message                = __( '%s is required', 'tribe-events-community' );
				$error_messages[ $key ] = sprintf( $message, $label );
			}

			wp_localize_script(
				$this->get_community_events_post_type() . '-community',
				'tribe_submit_form_i18n',
				[
					'errors' => $error_messages,
				]
			);

			/**
			 * Fires on Community Pages, allowing third-parties to enqueue scripts.
			 */
			do_action( 'tribe_community_events_enqueue_resources' );

			// Hook for other plugins.
			do_action( 'tribe_events_enqueue' );
		}

		/**
		 * Enqueue the admin resources where needed.
		 *
		 * @since 4.6.3
		 *
		 * @param string $screen the current admin screen.
		 */
		public function maybe_enqueue_admin_assets( $screen ) {
			$admin_pages          = tribe( 'admin.pages' );
			$current_page         = $admin_pages->get_current_page();
			$tec_settings_page_id = $this->get_settings_strategy()::$settings_page_id;

			if (
				$tec_settings_page_id === $current_page
				&& isset( $_GET['tab'] )
				&& 'community' === $_GET['tab']
			) {
				wp_enqueue_style( $this->get_community_events_post_type() . '-community-admin-styles' );
			}
		}

		/**
		 * Disable comments on community pages.
		 *
		 * @since  1.0.3
		 * @return null
		 * @author imaginesimplicity
		 */
		public function disable_comments_on_page() {
			return Tribe__Events__Community__Templates::getTemplateHierarchy( 'community/blank-comments-template' );
		}

		/**
		 * We need to provide an "inner" template if community views are being displayed using the
		 * default template.
		 *
		 * @param $unused_template
		 *
		 * @return string
		 */
		public function default_template_placeholder( $unused_template ) {
			return Tribe__Events__Community__Templates::getTemplateHierarchy( 'community/default-placeholder.php' );
		}

		/**
		 * Determine whether to redirect a user back to his events.
		 *
		 * @since 1.0
		 * @return void
		 *
		 */
		public function maybeRedirectMyEvents() {

			if ( ! is_admin() ) {
				//redirect my events with no args to todays page
				global $paged;
				if ( empty( $paged ) && isset( $_GET['tribe_action'] ) && $_GET['tribe_action'] == 'list' ) {
					$paged = 1;
					wp_safe_redirect( esc_url_raw( $this->getUrl( 'list', null, $paged ) ) );
					exit;
				}
			}
		}

		/**
		 * Take care of ugly URLs.
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		public function redirectUglyUrls() {

			if ( ! is_admin() ) {
				// redirect ugly link URLs to pretty permalinks
				if ( isset( $_GET['tribe_action'] ) ) {
					if ( isset( $_GET['paged'] ) ) {
						$url = $this->getUrl( $_GET['tribe_action'], null, $_GET['paged'] );
					} elseif ( isset( $_GET['tribe_id'] ) ) {
						$url = $this->getUrl( $_GET['tribe_action'], $_GET['tribe_id'] );
					} else {
						$url = $this->getUrl( $_GET['tribe_action'] );
					}
				}

				if ( isset( $url ) ) {
					wp_safe_redirect( esc_url_raw( $url ) );
					exit;
				}
			}

		}

		/**
		 * Returns a filterable page title for the "Submit" page.
		 *
		 * @since 4.5.11
		 *
		 * @return string
		 */
		public function ugly_urls_events_page_title() {
			/**
			 * Allows for filtering the "Submit" page's title.
			 *
			 * @since 4.5.11
			 *
			 * @param string $title
			 */
			return apply_filters( 'tribe_ce_submit_event_page_title', __( 'Submit an Event', 'tribe-events-community' ) );
		}

		/**
		 * Outputs the notice about pretty permalinks.
		 *
		 * @since 1.0.3
		 * @since 4.6.7 Added link to Permalinks settings page.
		 */
		public function notice_permalinks() {
			?>
			<div class="error"><p>
					<?php _ex(
						sprintf(
							'Community requires non-default (pretty) Permalinks to be enabled or the %1$s shortcode to exist on a post or page. Please <a href="%2$s">enable pretty Permalinks</a>.',
							'[tribe_community_events]',
							esc_url( trailingslashit( get_admin_url() ) . 'options-permalink.php' )
						),
						'Pretty permalinks admin notice',
						'tribe-events-community'
					); ?>
				</p></div>
			<?php
		}

		/**
		 * Get the URL for a specific action within the Community context.
		 *
		 * This method constructs URLs based on the provided action, ID, page, and post type.
		 * Additionally, it includes a filter to allow overriding or modifying the constructed URL.
		 *
		 * @since 1.0
		 * @since 5.0.0 Refactored logic and added new filter.
		 *
		 * @param string $action The action being performed (e.g., 'edit', 'view').
		 * @param int    $id The ID of the event, organizer, or venue, if applicable.
		 * @param string $page The pagination page number, if applicable.
		 * @param string $post_type The post type being used, if applicable.
		 *
		 * @return string The constructed URL based on the provided parameters.
		 */
		public function getUrl( $action, $id = null, $page = null, $post_type = null ) {
			// Check if permalinks are enabled.
			if ( '' == get_option( 'permalink_structure' ) ) {
				add_action( 'admin_notices', [ $this, 'notice_permalinks' ] );
				return '';
			}

			// Handle special case for recurring events.
			if ( ! empty( $id ) && $action == 'edit' && function_exists( 'tribe_is_recurring_event' ) && tribe_is_recurring_event( $id ) ) {
				$id = wp_get_post_parent_id( $id ) ? : $id;
			}

			// Base URL construction.
			$base_url = home_url( trailingslashit( $this->getCommunityRewriteSlug() ) . trailingslashit( $this->get_rewrite_slug( $action ) ) );

			// Construct URL based on the presence of $id and $post_type.
			$final_url = $base_url;
			if ( ! empty( $id ) ) {
				$final_url = trailingslashit( $base_url . $id );
			}

			// Handle pagination if $page is provided.
			if ( $page ) {
				$final_url = trailingslashit( $base_url . 'page/' . $page );
			}

			// Apply a filter to allow overriding of the final URL.
			return apply_filters( 'tec_events_community_get_urls_for_actions', $final_url, $action, $id, $page, $post_type, $base_url );
		}

		/**
		 * Gets the rewrite slug for community pages.
		 *
		 * Applies a filter to allow customization of the events slug.
		 *
		 * @since 1.0.6
		 * @since 5.0.0 Refactored logic.
		 * @since 5.0.4 Added methods for URL slugs.
		 *
		 * @return string The complete rewrite slug for the community.
		 */
		public function getCommunityRewriteSlug() {
			// Get the event and community slugs.
			$event_url_slug     = $this->get_event_url_slug();
			$community_url_slug = $this->get_community_url_slug();

			return "{$event_url_slug}/{$community_url_slug}";
		}

		/**
		 * Retrieves and sanitizes the event URL slug, applying any relevant filters.
		 *
		 * @since 5.0.4
		 *
		 * @return string The sanitized event slug.
		 */
		protected function get_event_url_slug(): string {
			$default_events_slug = $this->get_rewrite_slug( 'events' );

			/**
			 * Filters the events slug used in community rewrite.
			 *
			 * For example, https://websiteurl/{event_slug}/community
			 *
			 * @since 5.0.4
			 *
			 * @param string $default_events_slug The default events slug.
			 */
			$events_slug = apply_filters( 'tec_events_community_event_slug', $default_events_slug );

			// Fallback if slug is empty.
			if ( empty( $events_slug ) ) {
				$events_slug = $default_events_slug;
			}

			return sanitize_title( $events_slug );
		}

		/**
		 * Retrieves and sanitizes the community URL slug.
		 *
		 * @since 5.0.4
		 *
		 * @return string The sanitized community URL slug.
		 */
		protected function get_community_url_slug(): string {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$community_slug = $this->communityRewriteSlug;

			// Fallback if community slug is empty.
			if ( empty( $community_slug ) ) {
				$community_slug = $this->get_rewrite_slug( 'community' );
			}

			return sanitize_title( $community_slug );
		}

		/**
		 * Get delete button for an event.
		 *
		 * @since 1.0
		 *
		 * @param object $event The event to get the button for.
		 *
		 * @return string The button's output.
		 *
		 */
		public function getDeleteButton( $event ) {
			/**
			 * Filter to add custom overrides to allow users to delete their submissions.
			 *
			 * @since 4.10.1
			 *
			 * @param bool Our default is based on the `allowUsersToDeleteSubmissions` option.
			 * @param WP_Post The post event.
			 *
			 * @return bool Whether this individual can delete events.
			 */
			$allow_users_to_delete_submissions = apply_filters(
				'tec_events_community_allow_users_to_delete_event',
				$this->allowUsersToDeleteSubmissions,
				$event
			);

			$event_id = apply_filters( 'tec_events_community_event_form_post_id', $event->ID );

			if ( ! $allow_users_to_delete_submissions ) {
				$output = '';

				return $output;
			}

			$label = __( 'Delete', 'tribe-events-community' );
			$recurring = false;
			if ( class_exists( 'Tribe__Events__Pro__Main', false ) && tribe_is_recurring_event( $event_id ) ) {
				if ( empty( $event->post_parent ) ) {
					$label     = __( 'Delete All', 'tribe-events-community' );
					$recurring = true;
					$message   = __( 'Are you sure you want to permanently delete all instances of this recurring event?', 'tribe-events-community' );
				} else {
					$recurring = false;
					$message   = __( 'Are you sure you want to permanently delete this instance of a recurring event?', 'tribe-events-community' );
				}
			}

			$output = ' <span class="delete wp-admin events-cal">| %1$s</span>';
			$link   = sprintf(
				'<a rel="nofollow" class="submitdelete" href="%1$s" data-event_id="%3$s" data-nonce="%4$s" data-recurring="%5$s">%2$s</a>',
				esc_url( wp_nonce_url( $this->getUrl( 'delete', $event_id ), 'tribe_community_events_delete' ) ),
				$label,
				esc_attr( $event_id ),
				wp_create_nonce( 'tribe_community_events_delete' ),
				esc_attr( $recurring ? 1 : 0 ),
			);
			$output = sprintf( $output, $link );

			return $output;
		}

		/**
		 * Get edit button for an event.
		 *
		 * @since 1.0
		 *
		 * @param string $label  The label for the button.
		 * @param string $before What comes before the button.
		 * @param string $after  What comes after the button.
		 * @param object $event  The event object.
		 *
		 * @return string $output The button's output.
		 *
		 */
		public function getEditButton( $event, $label = 'Edit', $before = '', $after = '' ) {
			if ( ! isset( $event->EventStartDate ) ) {
				// @todo redscar - bring this logic back.
				//$event->EventStartDate = tribe_get_event_meta( $event->ID, '_EventStartDate', true );
			}

			$output = $before . '<a rel="nofollow" href="';
			$output .= esc_url( $this->getUrl( 'edit', $event->ID, null, $this->get_community_events_post_type() ) );
			$output .= '"> ' . $label . '</a>' . $after;

			return $output;

		}

		/**
		 * Get the featured image delete button.
		 *
		 * @since  1.0
		 *
		 * @param object $event The event id.
		 *
		 * @return string The button's output.
		 * @author Paul Hughes
		 */
		public function getDeleteFeaturedImageButton( $event = null ) {
			if ( ! isset( $event ) ) {
				$event = get_post();
			}

			if ( ! has_post_thumbnail( $event->ID ) ) {
				return '';
			}

			$url = add_query_arg( 'action', 'deleteFeaturedImage', wp_nonce_url( $this->getUrl( 'edit', $event->ID, null, $this->get_community_events_post_type() ), 'tribe_community_events_featured_image_delete' ) );

			if ( class_exists( 'Tribe__Events__Pro__Main' ) && tribe_is_recurring_event( $event->ID ) ) {
				$url = add_query_arg( 'eventDate', date( 'Y-m-d', strtotime( $event->EventStartDate ) ), $url );
			}

			$output = '<a rel="nofollow" class="submitdelete" href="' . esc_url( $url ) . '">' . esc_html__( 'Remove image', 'tribe-events-community' ) . '</a>';

			return $output;
		}

		/**
		 * Get title for a page.
		 *
		 * @since 1.0
		 *
		 * @param string $post_type The post type being viewed.
		 * @param string $action    The action being performed.
		 *
		 * @return string The title.
		 */
		public function getTitle( $action, $post_type ) {
			$i18n['delete'] = [
				$this->get_community_events_post_type()            => __( 'Remove an Event', 'tribe-events-community' ),
				Tribe__Events__Main::VENUE_POST_TYPE     => __( 'Remove a Venue', 'tribe-events-community' ),
				Tribe__Events__Main::ORGANIZER_POST_TYPE => __( 'Remove an Organizer', 'tribe-events-community' ),
				'unknown'                                => __( 'Unknown Post Type', 'tribe-events-community' ),
			];

			$i18n['default'] = [
				$this->get_community_events_post_type()            => __( 'Edit an Event', 'tribe-events-community' ),
				Tribe__Events__Main::VENUE_POST_TYPE     => __( 'Edit a Venue', 'tribe-events-community' ),
				Tribe__Events__Main::ORGANIZER_POST_TYPE => __( 'Edit an Organizer', 'tribe-events-community' ),
				'unknown'                                => __( 'Unknown Post Type', 'tribe-events-community' ),
			];

			if ( empty( $action ) || 'delete' !== $action ) {
				$action = 'default';
			}

			/**
			 * Allow users to hook and change the Page Title for all the existing pages.
			 * Don't remove the 'unknown' key from the array
			 */
			$i18n = apply_filters( 'tribe_ce_i18n_page_titles', $i18n, $action, $post_type );

			if ( ! empty( $i18n[ $action ][ $post_type ] ) ) {
				return $i18n[ $action ][ $post_type ];
			} else {
				return $i18n[ $action ]['unknown'];
			}
		}

		/**
		 * Set context for where we are.
		 *
		 * @since 1.0
		 *
		 * @param string $post_type The current post type.
		 * @param int    $id        The current id.
		 * @param string $action    The current action.
		 *
		 * @return void
		 *
		 */
		private function setContext( $action, $post_type, $id ) {

			$this->context = [
				'title'     => $this->getTitle( $action, $post_type ),
				'post_type' => $post_type,
				'action'    => $action,
				'id'        => $id,
			];

		}

		/**
		 * Get context for where we are.
		 *
		 * @since 1.0
		 *
		 * @param string $action   The current action.
		 * @param int    $tribe_id The current post id.
		 *
		 * @return array The current context.
		 */
		public function getContext( $action = null, $tribe_id = null ) {

			// get context from query string
			if ( isset( $_GET['tribe_action'] ) ) {
				$action = $_GET['tribe_action'];
			}

			if ( isset( $_GET['tribe_id'] ) ) {
				$tribe_id = intval( $_GET['tribe_id'] );
			}

			$tribe_id = intval( $tribe_id );

			if ( isset( $this->context ) ) {
				return $this->context;
			}

			switch ( $action ) {
				case 'edit':
					$context = [
						'title'  => 'Test',
						'action' => $action,
					];

					if ( $tribe_id ) {
						$post = get_post( $tribe_id );
						if ( is_object( $post ) ) {
							$context = [
								'title'     => $this->getTitle( $action, $post->post_type ),
								'action'    => $action,
								'post_type' => $post->post_type,
								'id'        => $tribe_id,
							];
						}
					}

					break;

				case 'list':
					$context = [
						'title'  => apply_filters( 'tribe_ce_event_list_page_title', __( 'My Events', 'tribe-events-community' ) ),
						'action' => $action,
						'id'     => null,
					];
					break;

				case 'delete':

					if ( $tribe_id ) {
						$post = get_post( $tribe_id );
					}

					$context = [
						'title'     => $this->getTitle( $action, $post->post_type ),
						'post_type' => $post->post_type,
						'action'    => $action,
						'id'        => $tribe_id,
					];

					break;

				default:
					$title   = __( 'Submit an Event', 'tribe-events-community' );
					$title   = apply_filters( 'tribe_events_community_submit_event_page_title', $title );
					$context = [
						'title'  => $title,
						'action' => 'add',
						'id'     => null,
					];
			}

			$this->context = $context;

			return $context;
		}

		/**
		 * Unhook content filters from the content.
		 *
		 * @since 1.0
		 * @return void
		 *
		 */
		public function removeFilters() {
			remove_filter( 'the_content', 'wpautop' );
			remove_filter( 'the_content', 'wptexturize' );
		}

		/**
		 * Set the body classes.
		 *
		 * @since  1.0.1
		 *
		 * @param array $classes The current array of body classes.
		 *
		 * @return array The body classes to add.
		 * @author Paul Hughes
		 */
		public function setBodyClasses( $classes ) {
			$is_community_page = false;

			if ( tribe_is_community_my_events_page() ) {
				$classes[]         = 'tribe_community_list';
				$is_community_page = true;
			}

			if ( tribe_is_community_edit_event_page() ) {
				$classes[]         = 'tribe_community_edit';
				$is_community_page = true;
			}

			if ( $is_community_page ) {
				$classes = $this->theme_compatibility_body_class_changes( $classes );
			}

			return $classes;
		}

		/**
		 * Alters the body classes specifically for theme compatibility purposes.
		 *
		 * @param array $classes
		 *
		 * @return array
		 */
		protected function theme_compatibility_body_class_changes( $classes ) {
			$child_theme  = get_option( 'stylesheet' );
			$parent_theme = get_option( 'template' );

			if ( 'twentyseventeen' === $child_theme || 'twentyseventeen' === $parent_theme ) {
				$has_sidebar = array_search( 'has-sidebar', $classes );

				if ( $has_sidebar ) {
					unset( $classes[ $has_sidebar ] );
				}
			}

			return $classes;
		}

		/**
		 * Upon page save, flush the transient for the page-id.
		 *
		 * @since  1.0.5
		 *
		 * @param int $post_id The current post id.
		 *
		 * @return void
		 * @author Paul Hughes
		 */
		public function flushPageIdTransient( $post_id ) {
			if ( get_post_type( $post_id ) == 'page' ) {
				delete_transient( 'tribe-community-events-page-id' );
			}
		}

		/**
		 * Adds the event specific query vars to WordPress.
		 *
		 * @link  http://codex.wordpress.org/Custom_Queries#Permalinks_for_Custom_Archives
		 *
		 * @since 1.0
		 *
		 * @param array $qvars Array of query variables.
		 *
		 * @return array Filtered array of query variables.
		 *
		 */
		public function communityEventQueryVars( $qvars ) {
			// @todo redscar - move this logic into correct location.
			$qvars[] = 'tribe_event_id';
			$qvars[] = 'tribe_venue_id';
			$qvars[] = 'tribe_organizer_id';

			return $qvars;
		}

		/**
		 * Sends the email alerts to all configured recipients and marks the email as sent.
		 *
		 * @since 5.0.7
		 *
		 * @param WP_Post $event   The event post object.
		 * @param string  $message The email message HTML.
		 *
		 * @return array{all: bool, count: int} Array containing whether all emails were sent successfully and how many were sent.
		 */
		protected function send_alert_emails( $event, $message ) {
			//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( empty( $event ) || ! is_array( $this->emailAlertsList ) ) {
				return [
					'all'   => false,
					'count' => 0,
				];
			}

			$subject = $this->get_email_subject( $event );
			$headers = [ 'Content-Type: text/html' ];
			$headers = implode( "\r\n", $headers ) . "\r\n";

			$sent_all   = true;
			$sent_count = 0;

			//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			foreach ( $this->emailAlertsList as $email ) {
				$email = trim( $email );

				if ( empty( $email ) || ! is_email( $email ) ) {
					continue;
				}

				//phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
				$sent_one = wp_mail( $email, $subject, $message, $headers );
				if ( ! $sent_one ) {
					$sent_all = false;
				} else {
					++$sent_count;
				}
			}

			// Only mark as sent if at least one email was sent successfully.
			if ( $sent_count > 0 ) {
				$marked = update_post_meta( $event->ID, self::$submission_email_sent_meta_key, 'yes' );
				// If we failed to mark as sent, consider this a failure.
				if ( ! $marked ) {
					$sent_all = false;
				}
			}

			return [
				'all'   => $sent_all,
				'count' => $sent_count,
			];
		}

		/**
		 * Send email alerts for event submissions.
		 *
		 * @since 5.0.7
		 *
		 * @param int $tribe_event_id The event ID.
		 *
		 * @return bool Whether the emails were sent successfully.
		 */
		public function send_email_alerts( $tribe_event_id ) {
			$event = $this->validate_post( $tribe_event_id );

			if ( ! $event ) {
				return false;
			}

			if ( $this->is_alert_email_already_sent( $tribe_event_id ) ) {
				return false;
			}

			$message = $this->build_alert_email_message( $event );
			if ( empty( $message ) ) {
				return false;
			}

			$sent = $this->send_alert_emails( $event, $message );

			return $sent['all'] && $sent['count'] > 0;
		}

		/**
		 * Validates a post ID and returns the event post if valid.
		 *
		 * @since 5.0.7 Introduced.
		 *
		 * @param int $post_id The post ID to validate.
		 *
		 * @return WP_Post|false The post object if valid, false otherwise.
		 */
		protected function validate_post( $post_id ) {
			$post_id = absint( $post_id );
			if ( empty( $post_id ) ) {
				return false;
			}

			// Get post.
			$post = get_post( $post_id );
			if ( ! $post instanceof WP_Post ) {
				return false;
			}

			/**
			 * Filters the validation result for a post.
			 *
			 * @since 5.0.7
			 *
			 * @param WP_Post|false $post    The post object if valid, false otherwise.
			 * @param int          $post_id The post ID being validated.
			 */
			return apply_filters( 'tec_events_community_validate_post', $post, $post_id );
		}

		/**
		 * Checks if an email alert has already been sent for an event.
		 *
		 * @since 5.0.7
		 *
		 * @param int $tribe_event_id The event ID to check.
		 *
		 * @return bool Whether the email has already been sent.
		 */
		protected function is_alert_email_already_sent( $tribe_event_id ) {
			$already_sent = get_post_meta( $tribe_event_id, self::$submission_email_sent_meta_key, true );
			return tribe_is_truthy( $already_sent );
		}

		/**
		 * Builds the email message for an event submission.
		 *
		 * @since 5.0.7
		 *
		 * @param WP_Post $event The event post object.
		 *
		 * @return string The email message HTML.
		 */
		protected function build_alert_email_message( $event ) {
			if ( empty( $event ) ) {
				return '';
			}

			$subject       = $this->get_email_subject( $event );
			$template_path = $this->get_email_alert_template_path();

			if ( empty( $template_path ) ) {
				return '';
			}

			ob_start();
			$tribe_event_id = $event->ID;
			if ( empty( $post ) ) {
				$post = get_post( $tribe_event_id );
			}

			/**
			 * Action hook before loading the email template.
			 *
			 * @since 4.5.14
			 * @since 5.0.7 Moved to build_email_message method.
			 *
			 * @param int|string $tribe_event_id The Event ID.
			 */
			do_action( 'tribe_events_community_before_email_template', $tribe_event_id );

			include $template_path;

			/**
			 * Action hook after loading the email template.
			 *
			 * @since 4.5.14
			 * @since 5.0.7 Moved to build_email_message method.
			 *
			 * @param int|string $tribe_event_id The Event ID.
			 */
			do_action( 'tribe_events_community_after_email_template', $tribe_event_id );
			return ob_get_clean();
		}

		/**
		 * Gets the email subject for an event submission.
		 *
		 * @since 5.0.7
		 *
		 * @param WP_Post $event The event post object.
		 *
		 * @return string The email subject.
		 */
		protected function get_email_subject( $event ) {
			$blog_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

			return sprintf(
				'[%s] %s: "%s"',
				$blog_name,
				__( 'Community Submission', 'tribe-events-community' ),
				get_the_title( $event->ID )
			);
		}

		/**
		 * Gets the path to the email template.
		 *
		 * @since 5.0.7
		 *
		 * @return string|false The path to the template file, or false if not found.
		 */
		protected function get_email_alert_template_path() {
			$template_path = Tribe__Events__Community__Templates::getTemplateHierarchy( 'integrations/the-events-calendar/email-template' );
			/**
			 * Filter the path to the email template to use.
			 *
			 * @since 5.0.7
			 *
			 * @param string $template_path The path to the email template.
			 */
			$template_path = apply_filters(
				'tec_events_community_email_alert_template_path',
				$template_path
			);

			if ( ! empty( $template_path ) && file_exists( $template_path ) ) {
				return $template_path;
			}

			// Fallback to basic template.
			$template_path = Tribe__Events__Community__Templates::getTemplateHierarchy( 'community/email-template' );

			if ( ! empty( $template_path ) && file_exists( $template_path ) ) {
				return $template_path;
			}

			return false;
		}

		/**
		 * Searches current user's events for the event closest to
		 * today but not in the past, and returns the 'page' that event is on.
		 *
		 * @since 1.0
		 * @return object The page object.
		 *
		 */
		public function findTodaysPage() {

			if ( WP_DEBUG ) {
				delete_transient( 'tribe_community_events_today_page' );
			}
			$todaysPage = get_transient( 'tribe_community_events_today_page' );

			$todaysPage = null;

			if ( ! $todaysPage ) {
				$current_user = wp_get_current_user();
				if ( is_object( $current_user ) && ! empty( $current_user->ID ) ) {
					$args = [
						'posts_per_page' => -1,
						'paged'          => 0,
						'nopaging'       => true,
						'author'         => $current_user->ID,
						'post_type'      => $this->get_community_events_post_type(),
						'post_status'    => 'any',
						'order'          => 'ASC',
						'orderby'        => 'meta_value',
						'meta_key'       => '_EventStartDate',
						'meta_query'     => [
							'key'     => '_EventStartDate',
							'value'   => date( 'Y-m-d 00:00:00' ),
							'compare' => '<=',
						],
					];

					$tp = new WP_Query( $args );

					$pc = $tp->post_count;

					unset( $tp );

					$todaysPage = floor( $pc / $this->eventsPerPage );

					//handle bounds
					if ( $todaysPage <= 0 ) {
						$todaysPage = 1;
					}

					set_transient( 'tribe-community-events_today_page', $todaysPage, 60 * 60 * 1 ); //cache for an hour
				}
			}

			return $todaysPage;

		}

		/** */
		public function ajaxDoDelete() {
			$permission = check_ajax_referer( 'tribe_community_events_delete', 'nonce', false );

			// Basic permission check, to make sure you should be on this page.
			if ( false == $permission ) {
				wp_send_json_error( __( 'You do not have permission to delete this event.', 'tribe-events-community' ) );
				wp_die();
			}

			$event_id = absint( $_REQUEST['id'] );
			$event    = get_post( $event_id );

			$message = '';
			$error   = false;

			// Confirm the event ID is valid.
			if ( ! isset ( $event->ID ) ) {
				$error = true;
				wp_send_json_error( __( 'This event does not appear to exist.', 'tribe-events-community' ) );
			}

			// security check.
			if ( ! ( current_user_can( 'delete_post', $event->ID ) || $this->user_can_delete_their_submissions( $event->ID ) ) ) {
				wp_send_json_error( __( 'You do not have permission to delete this event.', 'tribe-events-community' ) );
				wp_die();
			}

			if ( $this->trashItemsVsDelete ) {
				if ( wp_trash_post( $event_id ) ) {
					$message = __( 'Trashed Event: ', 'tribe-events-community' ) . $event->post_title;
				} else {
					$error   = true;
					$message = __( 'There was an error trashing your event: ', 'tribe-events-community' ) . $event->post_title;
				}

			} else {
				if ( wp_delete_post( $event_id, true ) ) {
					$message = __( 'Deleted Event: ', 'tribe-events-community' ) . $event->post_title;
				} else {
					$error   = true;
					$message = __( 'There was an error deleting your event: ', 'tribe-events-community' ) . $event->post_title;
				}
			}

			if ( $error ) {
				wp_send_json_error( $message );
			} else {
				wp_send_json_success( $message );
			}

			wp_die();
		}

		/**
		 * Delete view for an event.
		 *
		 * @param int $tribe_event_id The event's ID.
		 *
		 * @return string The deletion view.
		 *
		 * @since 1.0
		 */
		public function doDelete( $tribe_event_id ) {
			$this->default_template_compatibility();

			if ( wp_verify_nonce( $_GET['_wpnonce'], 'tribe_community_events_delete' ) && current_user_can( 'delete_post', $tribe_event_id ) ) {
				//does this event even exist?
				$event = get_post( $tribe_event_id );

				if ( isset( $event->ID ) ) {
					if ( $this->trashItemsVsDelete ) {
						wp_trash_post( $tribe_event_id );
						$this->enqueueOutputMessage( __( 'Trashed Event #', 'tribe-events-community' ) . $tribe_event_id );
					} else {
						wp_delete_post( $tribe_event_id, true );
						$this->enqueueOutputMessage( __( 'Deleted Event #', 'tribe-events-community' ) . $tribe_event_id );
					}
				} else {
					$this->enqueueOutputMessage( sprintf( __( 'This event (#%s) does not appear to exist.', 'tribe-events-community' ), $tribe_event_id ) );
				}
			} else {
				$this->enqueueOutputMessage( __( 'You do not have permission to delete this event.', 'tribe-events-community' ) );
			}

			$output = '<div id="tribe-community-events" class="delete">';

			ob_start();
			$this->enqueue_assets();
			include Tribe__Events__Community__Templates::getTemplateHierarchy( 'community/modules/delete' );
			$output .= ob_get_clean();

			/**
			 * Sets the URL normally used to take users back to the main Community list view.
			 *
			 * @param string $back_url
			 */
			$back_url = apply_filters( 'tribe_events_community_deleted_event_back_url', tribe( 'community.main' )->getUrl( 'list' ) );
			$output   .= '<a href="' . esc_url( $back_url ) . '">&laquo; ' . _x( 'Back', 'As in "go back to previous page"', 'tribe-events-community' ) . '</a>';

			$output .= '</div>';

			return $output;

		}

		/**
		 * If a request comes in to delete a featured image,
		 * delete it and redirect back to the event page
		 *
		 * @see do_action('before_tribe_community_event_page')
		 * @see Tribe__Events__Community__Main::doEventForm()
		 *
		 * @param int $event_id
		 *
		 * @return void
		 */
		public function maybe_delete_featured_image( $event_id ) {
			// Delete the featured image, if there was a request to do so.
			if ( $event_id && isset( $_GET['action'] ) && $_GET['action'] == 'deleteFeaturedImage' && wp_verify_nonce( $_GET['_wpnonce'], 'tribe_community_events_featured_image_delete' ) && current_user_can( 'edit_post', $event_id ) ) {
				$featured_image_id = get_post_thumbnail_id( $event_id );
				if ( $featured_image_id ) {
					delete_post_meta( $event_id, '_thumbnail_id' );
					$image_parent = wp_get_post_parent_id( $featured_image_id );
					if ( $image_parent == $event_id ) {
						wp_delete_attachment( $featured_image_id, true );
					}
				}
				$redirect = $_SERVER['REQUEST_URI'];
				$redirect = remove_query_arg( '_wpnonce', $redirect );
				$redirect = remove_query_arg( 'action', $redirect );
				wp_safe_redirect( esc_url_raw( $redirect ), 302 );
				exit();
			}
		}

		/**
		 * Get the View/Edit link for the post
		 *
		 * @since 3.7
		 * @since 4.10.13 Changed the check for the edit link to use `user_can_edit_their_submissions` instead. Added additional check to make sure users are able to edit their submissions.
		 * @since 5.0.0 Added additional logic for logged out users.
		 *
		 * @param int $event_id post ID of event.
		 *
		 * @return string HTML link
		 */
		public function get_view_edit_links( $event_id ) {
			$edit_link = '';
			$view_link = '';

			if ( get_post_status( $event_id ) == 'publish' ) {
				$view_link = sprintf(
					'<a href="%s" class="view-event">%s</a>',
					esc_url( get_permalink( $event_id ) ),
					__( 'View', 'tribe-events-community' )
				);
			}

			if ( ! is_user_logged_in() ) {
				// Logged out users shouldn't be able to edit submissions.
				return $view_link;
			}

			if ( $this->user_can_edit_their_submissions( $event_id ) ) {
				$edit_link = sprintf(
					'<a href="%s" class="edit-event">%s</a>',
					esc_url( tribe_community_events_edit_event_link( $event_id ) ),
					__( 'Edit', 'tribe-events-community' )
				);
			}

			// If you do not have `allowUsersToEditSubmissions` enabled, set the edit link to an empty string.
			if ( ! $this->allowUsersToEditSubmissions ) {
				$edit_link = '';
			}

			// If the user isn't allowed to edit and the post wasn't published, return an empty string.
			if ( empty( $edit_link ) && empty( $view_link ) ) {
				return '';
			}

			$separator = '<span class="sep"> | </span>';

			return '(' . tribe_separated_field( $view_link, $separator, $edit_link ) . ')';
		}

		/**
		 * Check for and return submitted event
		 *
		 * @since 3.3
		 *
		 * @return array event array or empty array if not a CE submitted event
		 */
		private function get_submitted_event() {
			// Validate the submitted data via nonce.
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ecp_event_submission' ) ) {
				return [];
			}

			if ( empty( $_POST['community-event'] ) ) {
				return [];
			}

			if ( ! check_admin_referer( 'ecp_event_submission' ) ) {
				return [];
			}
			$submission = $_POST;

			return $submission;
		}

		/**
		 * Returns an array of fields required for submission.
		 *
		 * @since 3.3
		 *
		 * @return array required fields
		 */
		public function required_fields_for_submission() {
			$required_fields = [
				'post_content',
				'post_title',
			];

			$terms_enabled     = $this->getOption( 'termsEnabled' );
			$terms_description = $this->getOption( 'termsDescription' );

			if ( tribe_is_truthy( $terms_enabled ) && ! empty( $terms_description ) ) {
				$required_fields[] = 'terms';
			}

			/**
			 * Required Community Event Fields
			 *
			 * @param array $required_fields An array of required fields (case sensitive) from:
			 *                               post_title, post_content, EventStartDate, EventStartTime, EventEndDate,
			 *                               EventEndTime, EventCurrencySymbol, tax_input (for Event Categories), venue,
			 *                               organizer, EventShowMapLink, EventURL, is_recurring,
			 *                               event_image (for Event Featured Image)
			 */
			return apply_filters( 'tribe_events_community_required_fields', $required_fields );
		}

		/**
		 * Required Community Event field groups.
		 *
		 * Groups are related set of required fields, a group will be marked as "required"
		 * if even one of its fields is marked as required (logic OR).
		 * Groups are not used to validate the submission, like single fields are, but
		 * to mark a whole group as required in the display logic.
		 *
		 * @return array An array of groups required for the submission.
		 */
		public function required_field_groups_for_submission() {
			$groups = [
				'taxonomy'       => [ 'tax_input' ],
				'featured_image' => [ 'event_image' ],
				'date_time'      => [
					'EventStartDate',
					'EventStartTime',
					'EventEndDate',
					'EventEndTime',
				],
			];

			/**
			 * Filter the required groups.
			 *
			 * A group will be marked as "required" if at least one of its fields is required.
			 *
			 * @param array $groups   An associative array of field groups in the format:
			 *                        [ <group> => [ <field1>, <field2>, ... ]
			 */
			$groups = apply_filters( 'tribe_events_community_required_field_groups', $groups );

			$required_fields = $this->required_fields_for_submission();

			foreach ( $groups as $group => $group_required_fields ) {
				$check_required_fields = array_intersect( $group_required_fields, $required_fields );
				if ( empty( $check_required_fields ) ) {
					unset( $groups[ $group ] );
				}
			}

			return array_keys( $groups );
		}

		/**
		 * Outputs login form.
		 *
		 * @since 3.1
		 * @since 4.6.3 Wrapped in div.tribe-community-events
		 *
		 * @param string $caption
		 *
		 * @return string HTML login form
		 */
		public function login_form( $caption = '' ) {
			ob_start();

			echo '<div class="tribe-community-events">';

			/**
			 * Fires immediately before the login form is rendered (where Community
			 * Events requires that the user logs in).
			 */
			do_action( 'tribe_community_before_login_form' );

			echo '<p>' . esc_html( $caption ) . '</p>';

			wp_login_form( [ 'form_id' => $this->login_form_id ] );

			if ( get_option( 'users_can_register' ) ) {
				wp_register( '<div class="tribe-ce-register">', '</div>', true );
				echo ' | ';
			}

			$this->lostpassword_link();

			/**
			 * Fires immediately after the login form is rendered (where Community
			 * Events requires that the user logs in).
			 */
			do_action( 'tribe_community_after_login_form' );

			echo '</div>';

			return ob_get_clean();
		}

		/**
		 * A uniform way of generating a "Lost your password?" link on CE login forms.
		 *
		 * @since 4.5.14
		 */
		public function lostpassword_link() {
			echo sprintf(
				'<a class="tribe-ce-lostpassword" href="%1$s">%2$s</a>',
				wp_lostpassword_url(),
				esc_html__( 'Lost your password?', 'tribe-events-community' )
			);
		}

		/**
		 * Add hidden form fields to our rendering of the WordPress login form so we know when logging in is attempted
		 * within our context and so we can redirect upon successful login.
		 *
		 * @since 4.6.3
		 *
		 * @param string $content
		 *
		 * @return string
		 */
		public function add_hidden_form_fields_to_login_form( $content ) {
			if (
				$this->isEditPage
				|| $this->isMyEvents
			) {
				// Identify an attempt from our login form
				$content .= sprintf( '%1$s<input type="hidden" name="%2$s" value="1" />%1$s', PHP_EOL, $this->login_form_id );

				/**
				 * Where to redirect upon successful login from Community login form.
				 *
				 * Default is just the current URL without the failed query var (if exists).
				 *
				 * @since 4.6.3
				 *
				 * @param string $redirect_upon_success The URL to redirect to.
				 *
				 * @return string
				 */
				$redirect_upon_success = apply_filters( 'tribe_events_community_successful_login_redirect_to', remove_query_arg( $this->login_form_id ) );

				$content .= sprintf( '%1$s<input type="hidden" name="redirect_to" value="%2$s" />%1$s', PHP_EOL, esc_url( $redirect_upon_success ) );
			}

			return $content;
		}

		/**
		 * Filter the WordPress authentication upon login attempt to force the login error redirect to always fire.
		 *
		 * We look for WP_Error of the type that bypasses the redirect hook for certain types of failed logins.
		 * We prefix such error(s) so the redirect hook fires while not losing the error message(s). We do not use them,
		 * but another plugin may care.
		 *
		 * @since 4.6.3
		 *
		 * @see   \wp_authenticate() The array of $ignore_codes to account for.
		 *
		 * @param string                $username Submitted value for username.
		 * @param string                $password Submitted value for password.
		 *
		 * @param WP_Error|WP_User|null $user     WP_User if the user is authenticated. WP_Error or null otherwise.
		 *
		 * @return WP_Error|WP_User|null
		 */
		public function login_form_authentication( $user, $username, $password ) {
			if (
				! $user instanceof WP_Error
				|| ! tribe_is_truthy( tribe_get_request_var( $this->login_form_id ) )
			) {
				return $user;
			}

			$ignore_codes = [
				'empty_username',
				'empty_password',
			];

			if (
				empty( $user->get_error_code() )
				|| ! in_array( $user->get_error_code(), $ignore_codes )
			) {
				return $user;
			}

			foreach ( $ignore_codes as $code ) {
				$new_key = $this->login_form_id . '_' . $code;

				foreach ( $user->errors as $key => $error ) {
					if ( $code !== $key ) {
						continue;
					}

					$user->errors[ $new_key ] = $error;

					unset( $user->errors[ $key ] );
				}
			}

			return $user;
		}


		/**
		 * Keep our login form's failed attempts on the front end, adding a query parameter.
		 *
		 * @since 4.6.3
		 *
		 * @param string $username Submitted value for username.
		 */
		public function redirect_failed_login_to_front_end( $username ) {
			if (
				$this->isEditPage
				|| $this->isMyEvents
				|| tribe_is_truthy( tribe_get_request_var( $this->login_form_id ) )
			) {
				$referrer = wp_get_referer();

				if ( ! empty( $referrer ) ) {
					wp_safe_redirect( add_query_arg( $this->login_form_id, 'failed', $referrer ) );
					tribe_exit();
				}
			}
		}

		/**
		 * Add the login form notices, such as a failed login message.
		 *
		 * @since 4.6.3
		 */
		public function output_login_form_notices() {
			if ( 'failed' === tribe_get_request_var( $this->login_form_id ) ) {
				$output = '<div id="login_error" class="tribe-community-notice tribe-community-notice-error">';

				$output .= sprintf(
					_x( '%1$sERROR%2$s: Invalid username, email address, or incorrect password.', 'failed login message', 'tribe-events-community' ),
					'<strong>',
					'</strong>'
				);

				$output .= '</div>';

				echo $output;
			}
		}

		/**
		 * Indicates whether or not the image size was exceeded
		 *
		 * @return boolean
		 */
		public function max_file_size_exceeded() {
			return (
				isset( $_SERVER['CONTENT_LENGTH'] )
				&& (int) $_SERVER['CONTENT_LENGTH'] > $this->max_file_size_allowed()
			);
		}

		/**
		 * Indicate the max upload size allowed
		 *
		 * @since 4.5.12
		 *
		 * @return int
		 */
		public function max_file_size_allowed() {
			/**
			 * Filter the the max upload size allowed.
			 *
			 * By default, it's using the `wp_max_upload_size()` value
			 *
			 * @since 4.5.12
			 *
			 * @param int `wp_max_upload_size()` The default WordPress max upload size.
			 */
			return apply_filters( 'tribe_community_events_max_file_size_allowed', wp_max_upload_size() );
		}

		/**
		 * If we have a spam submission, just kick the user away
		 *
		 * @return void
		 */
		public function spam_check( $submission ) {
			$timestamp = empty( $submission['render_timestamp'] ) ? 0 : intval( $submission['render_timestamp'] );
			if ( ! empty( $submission['tribe-not-title'] ) || 0 == $timestamp || time() - $timestamp < 3 ) { // you can't possibly fill out this form in 3 seconds
				wp_safe_redirect( home_url(), 303 );
				exit();
			}
		}

		/**
		 * Form event title.
		 *
		 * @since 1.0
		 *
		 * @param object $event The event to display the tile for.
		 *
		 * @return void
		 */
		public function formTitle( $event = null ) {
			$title = get_the_title( $event );
			if ( empty( $title ) && ! empty( $_POST['post_title'] ) ) {
				$title = stripslashes( $_POST['post_title'] );
			}
			?>
			<input
				id="post_title"
				type="text"
				name="post_title"
				value="<?php esc_attr_e( $title ); ?>"
				class="<?php tribe_community_events_field_classes( 'post_title', [] ); ?>"
			/>
			<?php
		}

		/**
		 * Form event content.
		 *
		 * @since 1.0
		 * @since 4.10.17 Added filter `tec_events_community_event_editor_post_content`.
		 * @since 5.0.1 Added an additional check when $event is defined but get_post_field() doesn't return a value.
		 *
		 * @param object $event The event to display the tile for.
		 *
		 * @return void
		 *
		 */
		public function formContentEditor( $event = null ) {
			if ( null == $event ) {
				$event = get_post();
			}
			if ( $event ) {
				// In the off chance we can't fetch get_post_field, use the event object itself.
				$post_content = get_post_field( 'post_content', $event->ID );
				$post_content = '' !== $post_content ? $post_content : ( $event->post_content ?? '' );
			} elseif ( ! empty( $_POST['post_content'] ) ) {
				$post_content = stripslashes( $_POST['post_content'] );
			} else {
				$post_content = '';
			}

			$post_content = apply_filters( 'tec_events_community_event_editor_post_content', $post_content, $event );

			$classes = tribe_community_events_field_classes( 'post_content', [ 'frontend' ], false );

			// if the admin wants the rich editor, and they are using WP 3.3, show the WYSIWYG, otherwise default to just a text box
			if ( $this->useVisualEditor && function_exists( 'wp_editor' ) ) {
				$settings = [
					'wpautop'       => true,
					'media_buttons' => false,
					'editor_class'  => $classes,
					'textarea_rows' => 5,
				];

				wp_editor( $post_content, 'tcepostcontent', $settings );
			} else {
				?><textarea
				id="post_content"
				name="tcepostcontent"
				class="<?php echo $classes; ?>"
				><?php
				echo esc_textarea( $post_content );
				?></textarea><?php
			}
		}

		/**
		 * Display status icon.
		 *
		 * @since 4.8.14 - Refactored method to simplify it.
		 *
		 * @param string $status The post status.
		 *
		 * @return string The status image element markup.
		 *
		 */
		public function getEventStatusIcon( $status ) {
			// TODO remove method and move to CSS
			$icon = str_replace( ' ', '-', $status ) . '.png';

			$post_status_types = [ 'pending', 'draft', 'future', 'publish' ];

			// Confirm the post status is valid, if not, default to pending.
			if ( ! in_array( $status, $post_status_types ) ) {
				$status = 'pending';
			}

			$src = $this->locatePublishStatusIcon( $status );

			return '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $status ) . ' icon" class="icon ' . esc_attr( $status ) . '">';
		}

		/**
		 * Find the location of the icon that is being searched for.
		 *
		 * @since   4.8.14
		 *
		 * @param string $icon_name - File name of the icon ( pending, draft, future, publish ).
		 *
		 * @return string
		 * @version 4.8.14
		 *
		 */
		public function locatePublishStatusIcon( $icon_name ) {
			/**
			 * File extension for the publish status icons.
			 *
			 * @since   4.8.14
			 *
			 * @param string $extension File extension, including the period.
			 *
			 * @version 4.8.14
			 *
			 */

			$file_extension = apply_filters( 'tribe_community_events_event_status_icon_extension', '.svg' );

			$icon = str_replace( ' ', '-', $icon_name ) . $file_extension;

			// Used to overwrite our default icons.
			$fileLocationList = [
				get_stylesheet_directory() . '/events/community/' . esc_attr( $icon ),
				get_template_directory_uri() . '/events/community/' . esc_attr( $icon ),
			];

			foreach ( $fileLocationList as $file ) {
				if ( file_exists( $file ) ) {
					return $file;
				}
			}

			// No icons found, use our default icons.
			return $this->pluginUrl . 'src/resources/images/' . esc_attr( $icon );

		}


		/**
		 * Filter pagination
		 *
		 * @since 1.0
		 *
		 * @param object $query The query to paginate
		 * @param int    $pages The pages
		 * @param int    $range The range
		 * @param bool   $shortcode
		 *
		 * @return string The pagination links
		 */
		public function pagination( $query, $pages = 0, $range = 3, $shortcode = false ) {
			$output = '';

			// Cast as Int for PHP 8 compatibility.
			$range = (int) $range;
			$pages = (int) $pages;

			$showitems = ( $range * 2 ) + 1;

			global $paged;
			$paged = (int) $paged;
			if ( empty( $paged ) ) {
				$paged = 1;
			}

			if ( $pages == 0 ) {
				//global $wp_query;
				$pages = ceil( $query->found_posts / $this->eventsPerPage );

				if ( ! $pages ) {
					$pages = 1;
				}
			}

			if ( $paged > $pages ) {
				$this->enqueueOutputMessage( __( 'The requested page number was not found.', 'tribe-events-community' ) );
			}
			if ( 1 != $pages ) {
				add_filter( 'get_pagenum_link', [ $this, 'fix_pagenum_link' ] );

				// If we are using the Community Shortcode, we should paginate the current post URL
				if ( $shortcode ) {
					// Ensure that the URLs will always end with slash.
					// This is necessary for the Events List to be paginated on posts or pages with ugly permalinks.
					$url = rtrim( get_permalink(), '/' ) . '/';

					$output .= "<div class='tribe-pagination'>";
					if ( $paged > 2 && $paged > $range + 1 && $showitems < $pages ) {
						$output .= "<a href='" . esc_url( $url . $paged ) . "'>&laquo;</a>";
					}
					if ( $paged > 1 && $showitems < $pages ) {
						$output .= "<a href='" . esc_url( $url . ( $paged - 1 ) ) . "'>&lsaquo;</a>";
					}

					for ( $i = 1; $i <= $pages; $i++ ) {
						if ( 1 != $pages && ( ! ( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) || $pages <= $showitems ) ) {
							$output .= ( $paged == $i ) ? '<span class="current">' . $i . '</span>' : '<a href="' . esc_url( $url . $i ) . '" class="inactive">' . $i . '</a>';
						}
					}

					if ( $paged < $pages && $showitems < $pages ) {
						$output .= "<a href='" . esc_url( $url . ( $paged + 1 ) ) . "'>&rsaquo;</a>";
					}
					if ( $paged < $pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages ) {
						$output .= "<a href='" . esc_url( $url . $paged ) . "'>&raquo;</a>";
					}
					$output .= "</div>\n";
				} else {
					$output .= "<div class='tribe-pagination'>";
					if ( $paged > 2 && $paged > $range + 1 && $showitems < $pages ) {
						$output .= "<a href='" . esc_url( $this->fix_pagenum_link_with_query_vars( get_pagenum_link( 1 ) ) ) . "'>&laquo;</a>";
					}
					if ( $paged > 1 && $showitems < $pages ) {
						$output .= "<a href='" . esc_url( $this->fix_pagenum_link_with_query_vars( get_pagenum_link( $paged - 1 ) ) ) . "'>&lsaquo;</a>";
					}

					for ( $i = 1; $i <= $pages; $i++ ) {
						if ( ! ( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) || $pages <= $showitems ) {
							$output .= ( $paged == $i ) ? '<span class="current">' . esc_html( $i ) . '</span>' : '<a href="' . esc_url( $this->fix_pagenum_link_with_query_vars( get_pagenum_link( $i ) ) ) . '" class="inactive">' . esc_html( $i ) . '</a>';
						}
					}

					if ( $paged < $pages && $showitems < $pages ) {
						$output .= "<a href='" . esc_url( $this->fix_pagenum_link_with_query_vars( get_pagenum_link( $paged + 1 ) ) ) . "'>&rsaquo;</a>";
					}
					if ( $paged < $pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages ) {
						$output .= "<a href='" . esc_url( $this->fix_pagenum_link_with_query_vars( get_pagenum_link( $pages ) ) ) . "'>&raquo;</a>";
					}
					$output .= "</div>\n";
				}
			}

			return $output;
		}

		/**
		 * Pass in a URL, append the eventDisplay variable if it doesn't exist already.
		 *
		 * @param $link string
		 *
		 * @return string
		 */
		public function fix_pagenum_link_with_query_vars( $link ) {
			$eventDisplay = isset( $_GET['eventDisplay'] ) ? $_GET['eventDisplay'] : '';

			// Check if eventDisplay already exists in the link
			if ( ! empty( $eventDisplay ) && strpos( $link, 'eventDisplay=' ) === false ) {
				$separator = strpos( $link, '?' ) !== false ? '&' : '?';
				$link      .= $separator . 'eventDisplay=' . urlencode( $eventDisplay );
			}

			return $link;
		}

		/**
		 * Get the template file with an output buffer.
		 *
		 * @since 1.0
		 *
		 * @param string $template_path The path.
		 * @param string $template_file The file.
		 *
		 * @return string The file's output.
		 */
		public function get_template( $template_path, $template_file ) {
			ob_start();
			include $this->getTemplatePath( $template_path, $template_file );

			return ob_get_clean();
		}

		/**
		 * Filter the limit query.
		 *
		 * @since 1.0
		 *
		 * @return string The modified query.
		 */
		public function limitQuery() {
			global $paged;
			if ( $paged - 1 <= 0 ) {
				$page = 0;
			} else {
				$page = $paged - 1;
			}

			$lq = 'LIMIT ' . ( ( $this->eventsPerPage * $page ) ) . ',' . $this->eventsPerPage;

			return $lq;
		}

		/**
		 * Add messages to the error/notice queue
		 *
		 * @todo redscar - Can this be replaced with the messages class?
		 *
		 * @since 3.1
		 *
		 * @param string      $message
		 * @param null|string $type
		 */
		public function enqueueOutputMessage( $message, $type = null ) {
			$this->messages[] = $message;
			if ( $type ) {
				$this->messageType = $type;
			}
		}

		/**
		 * Output a message to the user.
		 *
		 * @since 1.0
		 *
		 * @param string $type The message type.
		 * @param bool   $echo Whether to display or return the message.
		 *
		 * @return string The message.
		 */
		public function outputMessage( $type = null, $echo = true ) {

			if ( ! $type && ! $this->messageType ) {
				$type = 'updated';
			} elseif ( ! $type && $this->messageType ) {
				$type = $this->messageType;
			}

			$errors = [];

			if ( isset( $this->messages ) && ! empty( $this->messages ) ) {
				$errors = [
					[
						'type'    => $type,
						'message' => '<p>' . join( '</p><p>', $this->messages ) . '</p>',
					],
				];
			}

			$errors = apply_filters( 'tribe_community_events_form_errors', $errors );

			if ( ! is_array( $errors ) ) {
				return '';
			}

			// Prevent the undefined property notice $messages on Community shortcodes
			if ( empty( $this->messages ) ) {
				$this->messages = [];
			}

			ob_start();

			$existing_messages = isset( $this->messages ) ? $this->messages : [];

			/**
			 * Allows for adding content before the form's various messages.
			 *
			 * @since 4.5.15
			 *
			 * @param array $existing_messages The current array of messages to display on the form; empty array if none exist.
			 */
			do_action( 'tribe_community_events_before_form_messages', $existing_messages );

			foreach ( $errors as $error ) {
				printf(
					'<div class="tribe-community-notice tribe-community-notice-%1$s">%2$s</div>',
					esc_attr( $error['type'] ),
					wp_kses_post( $error['message'] )
				);
			}

			unset( $this->messages );

			if ( $echo ) {
				echo ob_get_clean();
			} else {
				return ob_get_clean();
			}
		}

		/**
		 * Filter pagination links.
		 *
		 * @since 1.0
		 *
		 * @param string $result The link.
		 *
		 * @return string The filtered link.
		 */
		public function fix_pagenum_link( $result ) {

			// pretty permalinks - fix page one to have args so we don't redirect to todays's page
			if ( '' != get_option( 'permalink_structure' ) && ! strpos( $result, '/page/' ) ) {
				$result = $this->getUrl( 'list', null, 1 );
			}

			// ugly links - fix page one to have args so we don't redirect to todays's page
			if ( '' == get_option( 'permalink_structure' ) && ! strpos( $result, 'paged=' ) ) {
				$result = $this->getUrl( 'list', null, 1 );
			}

			return $result;
		}

		/**
		 * Returns whether the current user can edit their submission.
		 *
		 * @since 4.10.14 Fixed an issue when the post was empty and there was no author.
		 * @since 4.8.11.1
		 *
		 * @param int $post_id The current post id.
		 *
		 * @return boolean
		 */
		public function user_can_edit_their_submissions( $post_id ) {

			// Use get_post_status to check if the post exists.
			if ( get_post_status( $post_id ) ) {
				return $this->allowUsersToEditSubmissions && ( get_current_user_id() === (int) get_post( $post_id )->post_author );
			}

			return false;

		}

		/**
		 * Returns whether the current user can delete their submission.
		 *
		 * @since 4.8.11.1
		 *
		 * @param int $post_id The current post id.
		 *
		 * @return boolean
		 */
		public function user_can_delete_their_submissions( $post_id ) {

			return $this->allowUsersToDeleteSubmissions && ( get_current_user_id() == get_post( $post_id )->post_author );

		}

		/**
		 * @param array $user_caps      The capabilities the user has
		 * @param array $requested_caps The capabilities the user needs
		 * @param array $args           [0] = The specific cap requested, [1] = The user ID
		 *
		 * @return array mixed
		 */
		public function filter_user_caps( $user_caps, $requested_caps, $args ) {
			if ( defined( 'REST_REQUEST' ) && tribe_is_truthy( REST_REQUEST ) ) {
				return $user_caps;
			}

			return $user_caps;
		}

		/**
		 * Determine if the specified user can edit the specified post.
		 **
		 *
		 * @since      4.10.0
		 *
		 * @param int|null $id        The current post ID.
		 * @param string   $post_type The post type.
		 *
		 * @return bool Whether the use has the permissions to edit a given post.
		 *
		 */
		public function user_can_edit( $id = null, $post_type = null ) {
			// if we're talking about a specific post, use standard WP permissions
			if ( $id && empty( $post_type ) ) {
				return current_user_can( 'edit_post', $id );
			}

			if ( empty( $post_type ) || ! is_user_logged_in() ) {
				return false;
			}

			// only supports Tribe Post Types
			if ( ! in_array( $post_type, Tribe__Main::get_post_types() ) ) {
				return false;
			}

			// admin override
			if ( is_super_admin() || current_user_can( 'manage_options' ) ) {
				return true;
			}

			return $this->allowUsersToEditSubmissions;
		}

		/**
		 * Add a settings tab.
		 *
		 * Additionally sets up a filter to append information to the existing events template setting tooltip.
		 *
		 * @since 5.0.4
		 *
		 */
		public function do_settings( $admin_page ) {
			$tec_settings_page_id = $this->get_settings_strategy()::$settings_page_id;

			if ( ! empty( $admin_page ) && $tec_settings_page_id !== $admin_page ) {
				return;
			}

			require_once $this->pluginPath . 'src/admin-views/community-options-template.php';

			add_filter( 'tribe_field_tooltip', [ $this, 'amend_template_tooltip' ], 10, 3 );
		}

		/**
		 * Add a settings tab.
		 *
		 * Additionally sets up a filter to append information to the existing events template setting tooltip.
		 *
		 * @since 1.0
		 * @deprecated 5.0.4
		 *
		 * @param string $admin_page The current admin page.
		 * @param mixed  $deprecated Unused.
		 */
		public function doSettings( $admin_page, $deprecated = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			_deprecated_function( __METHOD__, '5.0.4' );

			return $this->do_settings( $admin_page );
		}

		/**
		 * This method filters the tooltip for the tribeEventsTemplate setting to make it clear that it also
		 * impacts on Community output.
		 *
		 * @param $text
		 * @param $tooltip
		 * @param $field = null (this may not provided when tribe_field_tooltip callbacks take place)
		 *
		 * @return string
		 */
		public function amend_template_tooltip( $text, $tooltip, $field = null ) {
			if ( null === $field || 'tribeEventsTemplate' !== $field->id ) {
				return $text;
			}
			$description = __( 'This template is also used for Community.', 'tribe-events-community' );

			return str_replace( $tooltip, "$tooltip $description ", $text );
		}

		/**
		 * Add a Community origin to the audit system.
		 *
		 * @since 1.0
		 * @return string The Community slug.
		 *
		 */
		public function filterPostOrigin() {
			return 'community-events';
		}

		/**
		 * Get all options for the plugin.
		 *
		 * @since 1.0
		 *
		 * @param bool $force
		 *
		 * @return array The current settings for the plugin.
		 */
		public static function getOptions( $force = false ) {
			if ( ! isset( self::$options ) || $force ) {
				$options       = get_option( self::OPTIONNAME, [] );
				self::$options = apply_filters( 'tribe_community_events_get_options', $options );
			}

			return self::$options;
		}

		/**
		 * Get value for a specific option.
		 *
		 * @since 1.0
		 *
		 * @param mixed  $default    Default value.
		 * @param bool   $force
		 * @param string $optionName Name of option.
		 *
		 * @return mixed Results of option query.
		 *
		 */
		public function getOption( $optionName, $default = '', $force = false ) {
			if ( ! $optionName ) {
				return;
			}

			if ( ! isset( self::$options ) || $force ) {
				self::getOptions( $force );
			}

			$option = $default;
			if ( isset( self::$options[ $optionName ] ) ) {
				$option = self::$options[ $optionName ];
			} elseif ( is_multisite() && isset( self::$tribeCommunityEventsMuDefaults ) && is_array( self::$tribeCommunityEventsMuDefaults ) && in_array( $optionName, array_keys( self::$tribeCommunityEventsMuDefaults ) ) ) {
				$option = self::$tribeCommunityEventsMuDefaults[ $optionName ];
			}

			return apply_filters( 'tribe_get_single_option', $option, $default, $optionName );
		}

		/**
		 * Set value for a specific option.
		 *
		 * @since 1.0
		 *
		 * @param string $value      Value to set.
		 *
		 * @param string $optionName Name of option.
		 */
		public function setOption( $optionName, $value ) {
			if ( ! $optionName ) {
				return;
			}

			if ( ! isset( self::$options ) ) {
				self::getOptions();
			}
			self::$options[ $optionName ] = $value;
			update_option( self::OPTIONNAME, self::$options );
		}

		/**
		 * Get the plugin's path.
		 *
		 * @since 1.0
		 * @return string The path.
		 *
		 */
		public static function getPluginPath() {
			return self::instance()->pluginPath;
		}

		/**
		 * Get the current user's role.
		 *
		 * @since 1.0
		 * @return string The role.
		 *
		 */
		public function getCurrentUserRole() {
			$user_roles = $this->getUserRoles();
			if ( empty( $user_roles ) ) {
				return false;
			}

			return array_shift( $user_roles );
		}

		/**
		 * get roles for a specified user, or current user
		 *
		 * @since 3.1
		 *
		 * @param integer $user_id defaults to get_current_user_id()
		 *
		 * @return array user roles or an empty array if none found
		 */
		public function getUserRoles( $user_id = 0 ) {
			$user_id = $user_id ? $user_id : get_current_user_id();
			if ( empty( $user_id ) ) {
				return [];
			}

			$user = new WP_User( $user_id );
			if ( isset( $user->roles ) ) {
				return $user->roles;
			}

			return [];
		}

		/**
		 * Get the URL to redirect Block Roles from Admin.
		 *
		 * @since 4.6.3
		 *
		 * @see   \Tribe__Events__Community__Main::user_can_access_admin() Check for this before redirecting to this URL.
		 *
		 * @return string
		 */
		private function get_block_roles_redirect_url() {
			$option = $this->getOption( 'blockRolesRedirect' );

			if ( empty( $option ) ) {
				$url = $this->getUrl( 'list' );
			} else {
				$url = $option;
			}

			return esc_url_raw( $url );
		}

		/**
		 * Facilitate blocking specific roles from the admin environment.
		 */
		public function blockRolesFromAdmin() {
			// Get Current User ID
			$user_id = get_current_user_id();

			// Let WordPress worry about admin access for unauthenticated users
			if ( ! is_user_logged_in() ) {
				return;
			}

			// If the user has access privileges then we don't need to interfere, else hide the WP Admin Bar
			if ( $this->user_can_access_admin( $user_id ) ) {
				return;
			} else {
				add_filter( 'show_admin_bar', '__return_false' );
			}

			// If it is not an admin request - or if it is an ajax request - then we don't need to interfere
			if (
				! is_admin()
				|| wp_doing_ajax()
			) {
				return;
			}

			// Make sure the action to send the email comes from the FE
			if (
				'email' === tribe_get_request_var( 'action' )
				&& 'tickets-attendees' === tribe_get_request_var( 'page' )
				&& tribe_get_request_var( 'event_id' )
			) {
				return;
			}

			wp_redirect( $this->get_block_roles_redirect_url() );
			tribe_exit();
		}

		/**
		 * Get determination if the user has a role that allows access to the admin
		 *
		 * @since 4.5.9
		 *
		 * @param int $user_id
		 *
		 * @return bool
		 */
		public function get_user_can_access_admin( $user_id = 0 ) {
			if ( $this->user_can_access_admin( $user_id ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Determine if the user has a role that allows him to access the admin
		 *
		 * @since 3.1
		 *
		 * @param int $user_id
		 *
		 * @return bool Whether the user is allowed to access the admin (by this plugin)
		 */
		protected function user_can_access_admin( $user_id = 0 ) {
			if ( ! is_array( $this->blockRolesList ) || empty( $this->blockRolesList ) ) {
				return true;
			}

			if ( is_super_admin( $user_id ) ) {
				return true;
			}
			$user_roles = $this->getUserRoles( $user_id );

			// if a user has multiple roles, still let him in if he has a non-blocked role
			$diff = array_diff( $user_roles, $this->blockRolesList );
			if ( empty( $diff ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Get the appropriate logout URL for the current user.
		 *
		 * @since 3.1
		 *
		 * @return string The logout URL.
		 */
		public function logout_url() {
			$can_access_admin = $this->user_can_access_admin();

			if ( $can_access_admin ) {
				$redirect_to = '';
			} else {
				$redirect_to = $this->get_block_roles_redirect_url();
			}

			/**
			 * The appropriate logout URL for the current user.
			 *
			 * @since 4.6.3
			 *
			 * @param string $redirect_to      The URL to redirect_to to.
			 * @param bool   $can_access_admin Whether or not the current user can access the WordPress admin area.
			 *
			 * @return string
			 */
			$redirect_to = apply_filters( 'tribe_events_community_logout_url_redirect_to', $redirect_to, $can_access_admin );

			return wp_logout_url( $redirect_to );
		}

		/**
		 * Add the Community toolbar items.
		 *
		 * @since  1.0.1
		 * @return void
		 * @author Paul Hughes
		 */
		public function addCommunityToolbarItems() {
			/** @var WP_Admin_Bar $wp_admin_bar */
			global $wp_admin_bar;

			$wp_admin_bar->add_group( [
				'id'     => 'tribe-community-events-group',
				'parent' => 'tribe-events-add-ons-group',
			] );

			$wp_admin_bar->add_menu( [
				'id'     => 'tribe-community-events-submit',
				'title'  => sprintf( __( 'Community: Submit %s', 'tribe-events-community' ), $this->get_event_label( 'singular' ) ),
				'href'   => esc_url( $this->getUrl( 'add' ) ),
				'parent' => 'tribe-community-events-group',
			] );

			if ( is_user_logged_in() ) {
				$wp_admin_bar->add_menu( [
					'id'     => 'tribe-community-events-my-events',
					'title'  => sprintf( __( 'Community: My %s', 'tribe-events-community' ), $this->get_event_label( 'plural' ) ),
					'href'   => esc_url( $this->getUrl( 'list' ) ),
					'parent' => 'tribe-community-events-group',
				] );
			}

			if ( current_user_can( 'manage_options' ) ) {
				$wp_admin_bar->add_menu( [
					'id'     => 'tribe-community-events-settings-sub',
					'title'  => __( 'Community', 'tribe-events-community' ),
					'href'   => $this->get_settings_strategy()->get_url( [ 'tab' => 'community' ] ),
					'parent' => 'tribe-events-settings',
				] );
			}
		}

		/**
		 * Return additional action for the plugin on the plugins page.
		 *
		 * @since 1.0.2
		 *
		 * @param array $actions
		 *
		 * @return array
		 */
		public function addLinksToPluginActions( $actions ) {
			if ( class_exists( 'Tribe__Events__Main' ) ) {
				$actions['settings'] = '<a href="' . tribe( 'tec.main' )->settings()->get_url( [ 'tab' => 'community' ] ) . '">' . __( 'Settings', 'tribe-events-community' ) . '</a>';
			}

			return $actions;
		}

		/**
		 * Load the plugin's textdomain.
		 *
		 * @since 1.0
		 * @return void
		 */
		public function loadTextDomain() {
			$mopath = $this->pluginDir . 'lang/';
			$domain = 'tribe-events-community';

			// If we don't have Common classes load the old fashioned way
			if ( ! class_exists( 'Tribe__Main' ) ) {
				load_plugin_textdomain( $domain, false, $mopath );
			} else {
				// This will load `wp-content/languages/plugins` files first
				Tribe__Main::instance()->load_text_domain( $domain, $mopath );
			}
		}

		/**
		 * Init the plugin.
		 *
		 * @since 1.0
		 * @return void
		 */
		public function init() {

			// Setup Main Service Provider.
			tribe_register_provider( 'Tribe__Events__Community__Service_Provider' );
			$this->anonymous_users = $this->anonymous_users_handler();

			// Start the integrations manager.
			tribe( 'community.integrations' )->load_integrations();

			$this->set_rewrite_slugs();
		}

		/**
		 * Handles anonymous users' submissions.
		 *
		 * This method applies a filter to allow customization of the handler for anonymous users.
		 * If a valid handler is provided through the filter, it is called. Otherwise, it returns an empty string.
		 *
		 * @since 5.0.0
		 *
		 * @return null|string|callable The result of the handler call, or an empty string if the handler is not callable.
		 */
		protected function anonymous_users_handler() {
			static $handler_set = false;
			static $handler     = null;

			if ( ! $handler_set ) {
				/**
				 * Filters the anonymous users handler.
				 *
				 * This filter allows hooking into anonymous users logic.
				 * Only the last handler that is sent to this filter will run. To overwrite the logic use a higher priority.
				 *
				 * @since 5.0.0
				 *
				 * @param callable $handler The callback handler for saving the event submission.
				 */
				$handler = apply_filters( 'tec_events_community_submission_anonymous_users_handler', '__return_false' );
			}

			if ( ! is_callable( $handler ) ) {
				// By default Community allows anonymous users to access everything (if $this->allowAnonymousSubmissions is true).
				return '';
			}

			return call_user_func( $handler, $this );
		}

		/**
		 * Sets up the rewrite slugs.
		 *
		 * Grabs the slugs from options, allows other plugins to filter them,
		 * then sets a value from get_default_rewrite_slugs($slug) if they are blank.
		 *
		 * Note these slugs are NOT translated, as this can lead to 404s on multi-lingual sites.
		 *
		 * @since 4.6.3
		 * @since 5.0.0 Refactored to use `get_default_rewrite_slugs` and to make it more dynamic.
		 */
		public function set_rewrite_slugs() {
			$this->communityRewriteSlug = sanitize_title( $this->getOption( 'communityRewriteSlug', $this->get_default_rewrite_slugs( 'community' ) ) );

			/**
			 * Allows for filtering the main community rewrite slug.
			 *
			 * @since 4.6.3
			 *
			 * @param string $rewrite_slug The slug value.
			 */
			$this->communityRewriteSlug = apply_filters( 'tribe_community_events_rewrite_slug', $this->communityRewriteSlug );

			// Set default if we end up with an empty string.
			if ( empty( $this->communityRewriteSlug ) ) {
				$this->communityRewriteSlug = 'community';
			}

			// Retrieve all default slugs to ensure we have a complete list.
			$default_slugs = $this->get_default_rewrite_slugs();

			// Initialize rewrite slugs based on the options or defaults.
			foreach ( $default_slugs as $key => $default_slug ) {
				$this->rewriteSlugs[ $key ] = sanitize_title( $this->getOption( "community-{$key}-slug", $default_slug, true ) );

				/**
				 * Allows for filtering the rewrite slugs individually.
				 *
				 * @since 4.6.3
				 *
				 * @param string $value The slug value.
				 * @param string $key The slug key.
				 */
				$this->rewriteSlugs[ $key ] = apply_filters( "tribe_community_events_{$key}_rewrite_slug", $this->rewriteSlugs[ $key ], $key );
			}

			/**
			 * Allows for filtering the community rewrite slugs.
			 *
			 * @since 4.6.3
			 *
			 * @param array The slug array.
			 */
			$this->rewriteSlugs = apply_filters( 'tribe_community_events_rewrite_slugs', $this->rewriteSlugs );

			// Just in case, reset any slugs that were empty or just whitespace with the defaults.
			foreach ( $this->rewriteSlugs as $key => $slug ) {
				if ( empty( trim( $slug ) ) ) {
					$this->rewriteSlugs[ $key ] = $default_slugs[ $key ];
				}
			}
		}

		public function load_captcha_plugin() {
			$this->captcha = apply_filters( 'tribe_community_events_captcha_plugin', new Tribe__Events__Community__Captcha__Recaptcha_V2() );
			if ( empty( $this->captcha ) ) {
				$this->captcha = new Tribe__Events__Community__Captcha__Null_Captcha();
			}
			$this->captcha->init();
		}

		public function captcha() {
			return $this->captcha;
		}

		/**
		 * Singleton instance method.
		 *
		 * @since 1.0
		 * @return Tribe__Events__Community__Main The instance
		 *
		 */
		public static function instance() {
			return tribe( 'community.main' );
		}

		/**
		 * Sets the setting variable that says the rewrite rules should be flushed upon plugin load.
		 *
		 * @since  1.0.1
		 * @return void
		 * @author Paul Hughes
		 */
		public static function activateFlushRewrite() {
			$options                        = self::getOptions();
			$options['maybeFlushRewrite']   = true;
			update_option( self::OPTIONNAME, $options );
		}

		/**
		 * Removes the Edit link from My Events and Edit Event community pages.
		 *
		 * @since  1.0.3
		 *
		 * @param string $content
		 *
		 * @return string An empty string.
		 * @author Paul Hughes
		 */
		public function removeEditPostLink( $content ) {
			$content = '';

			return $content;
		}

		/**
		 * Return the forums link as it should appear in the help tab.
		 *
		 * @since  1.0.3
		 *
		 * @param string $content
		 *
		 * @return string
		 * @author Paul Hughes
		 */
		public function helpTabForumsLink( $content ) {
			$promo_suffix = '?utm_source=helptab&utm_medium=plugin-community&utm_campaign=in-app';

			return ( isset( Tribe__Events__Main::$tecUrl ) ? Tribe__Events__Main::$tecUrl : Tribe__Events__Main::$tribeUrl ) . 'support/forums/' . $promo_suffix;
		}

		/**
		 * Allows multisite installs to override defaults for settings.
		 *
		 * @since  1.0.6
		 *
		 * @param string $key   The option key.
		 * @param array  $field The field.
		 * @param mixed  $value The current default.
		 *
		 * @return mixed The MU default value of the option.
		 * @author Paul Hughes
		 */
		public function multisiteDefaultOverride( $value, $key, $field ) {
			if ( isset( $field['parent_option'] ) && $field['parent_option'] == self::OPTIONNAME ) {
				$current_options = $this->getOptions();
				if ( isset( $current_options[ $key ] ) ) {
					return $value;
				} elseif ( isset( self::$tribeCommunityEventsMuDefaults[ $key ] ) ) {
					$value = self::$tribeCommunityEventsMuDefaults[ $key ];
				}
			}

			return $value;
		}

		/**
		 * Add in Community Event Slugs to the System Info after Settings
		 *
		 * @param $systeminfo
		 *
		 * @return mixed
		 */
		public function support_info( $systeminfo ) {

			if ( '' != get_option( 'permalink_structure' ) ) {
				$community_data = [
					'Community Add'     => esc_url( $this->getUrl( 'add' ) ),
					'Community List'    => esc_url( $this->getUrl( 'list' ) ),
					'Community Options' => get_option( 'tribe_community_events_options', [] ),
				];
				$systeminfo     = Tribe__Main::array_insert_after_key( 'Settings', $systeminfo, $community_data );
			}

			return $systeminfo;
		}

		/**
		 * Registers the implementations in the container.
		 *
		 * Classes that should be built at `plugins_loaded` time are also instantiated.
		 *
		 * @since 4.5.10
		 *
		 * @return void
		 */
		protected function bind_implementations() {
			if ( class_exists( '\\TEC\\Events_Community\\Custom_Tables\\V1\\Provider' ) ) {
				tribe_register_provider( '\\TEC\\Events_Community\\Custom_Tables\\V1\\Provider' );
			}

			tribe_register_provider( '\\TEC\\Events_Community\\Routes\\Provider' );

			tribe_register_provider( \TEC\Events_Community\Integrations\Provider::class );

			tribe_register_provider( \TEC\Events_Community\Block_Conversion\Controller::class );
		}

		/**
		 * Make necessary database updates on admin_init
		 *
		 * @since 4.5.10
		 *
		 */
		public function run_updates() {
			if ( ! class_exists( 'Tribe__Events__Updater' ) ) {
				return; // core needs to be updated for compatibility
			}

			$updater = new Tribe__Events__Community__Updater( self::VERSION );
			if ( $updater->update_required() ) {
				$updater->do_updates();
			}
		}

		/**
		 * Hooked to tribe_tickets_user_can_manage_attendees
		 * Allows event creator to edit attendees if allowUsersToEditSubmissions is true
		 *
		 * @since 4.6.1
		 *
		 * @param boolean $user_can user can/can't edit
		 * @param int     $user_id  ID of user to check, uses current user if empty
		 * @param int     $event_id Event ID.
		 *
		 * @return boolean
		 */
		public function user_can_manage_own_event_attendees( $user_can, $user_id, $event_id ) {
			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			// Cannot manage attendees without user.
			if ( ! $user_id ) {
				return false;
			}

			// If Event Tickets is active.
			if ( class_exists( 'Tribe__Tickets__Main', false ) ) {
				// Cannot manage attendees without event, when not on the attendees page.
				if (
					! tribe( TEC\Tickets\Admin\Attendees\Page::class )->is_on_page()
					&& empty( $event_id )
				) {
					return false;
				}
			} else {
				if ( empty( $event_id ) ) {
					return false;
				}
			}

			// Can manage attendees from admin area.
			if ( is_admin() ) {
				return true;
			}

			// Cannot determine management if origin is not current origin.
			if ( $this->filterPostOrigin() !== get_post_meta( $event_id, '_EventOrigin', true ) ) {
				return $user_can;
			}

			// Cannot manage attendees that they do not own.
			if ( (int) $user_id !== (int) get_post_field( 'post_author', $event_id ) ) {
				return false;
			}

			// Cannot manage attendees if they are not allowed to edit submissions.
			if ( ! tribe( 'community.main' )->getOption( 'allowUsersToEditSubmissions' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Filters the post statuses for linked posts of a given post type.
		 *
		 * @since 4.10.6
		 *
		 * @param array  $post_status Array of post statuses for linked posts. Example: [ 'publish' ]
		 * @param string $post_type   Post type of the linked post.
		 *
		 * @return array Updated array of post statuses for linked posts.
		 */
		public function linked_posts_post_status( $post_status, $post_type ) {

			// Only return publish if we are on the frontend.
			if ( ! is_admin() ) {
				return [ 'publish' ];
			}

			return $post_status;

		}

		/**
		 * Filters the arguments used for getting linked_posts.
		 *
		 * @since 4.10.6
		 *
		 * @param array     $args             The WP_Query arguments.
		 * @param string    $linked_post_type The post type key.
		 * @param int|array $linked_post_ids  A single Linked Post ID or an array of Linked Post IDs.
		 *
		 * @return array
		 */
		public function linked_post_info_args( $args, $linked_post_type, $linked_post_ids ) {
			// Ignore any posts that have a password.
			$args['post_password'] = '';
			return $args;
		}

		/**
		 * Retrieves the MIME types for valid images.
		 *
		 * @since 5.0.0
		 *
		 * @return array An array of MIME types for valid images.
		 */
		public function allowed_image_upload_mime_types(): array {
			/**
			 * Filters the MIME types for valid images.
			 *
			 * Use this filter to modify or extend the list of valid image MIME types.
			 *
			 * @since 5.0.0
			 *
			 * @param array $mime_types An array of MIME types for valid images.
			 */
			return apply_filters(
				'tec_events_community_image_mime_types',
				[
					'image/png',
					'image/jpeg',
					'image/gif',
				]
			);
		}

		/**
		 * Retrieves the URL for the form submission page.
		 *
		 * This method returns the URL of the form submission page where users can create a new event.
		 * It also allows modification of the default submission link via a filter.
		 *
		 * @since 5.0.0
		 *
		 * @return string The URL of the form submission page.
		 */
		public function get_submission_url(): string {
			// The URL of the form submission page if the user wants to create a new event.
			$submit_url = esc_url( $this->getUrl( 'add' ) );

			/**
			 * Allows to modify the default submission link on Community Submission Form.
			 *
			 * @since 5.0.0
			 *
			 * @param string $submit_url The default submission URL.
			 */
			return apply_filters( 'tribe_events_community_submission_url', $submit_url );
		}

		/**
		 * Retrieves the post type for Community.
		 *
		 * This method applies the 'tec_events_community_posttype' filter to allow customization
		 * of the post type used for Community. By default, it returns 'post'.
		 *
		 * @since 5.0.0
		 *
		 * @return string The post type for Community.
		 * @todo redscar - Move to new class?
		 *
		 */
		public function get_community_events_post_type() {
			return apply_filters( 'tec_events_community_posttype', 'post' );
		}

		/**
		 * Handle ajax requests from admin form
		 *
		 * @todo redscar - Move to new class?
		 */
		public function ajax_form_validate() {
			if (
				$_REQUEST['name']
				&& $_REQUEST['nonce']
				&& $_REQUEST['type']
				&& wp_verify_nonce( $_REQUEST['nonce'], 'tribe-validation-nonce' )
			) {
				echo $this->verify_unique_name( $_REQUEST['name'], $_REQUEST['type'] );
				die;
			}
		}

		/**
		 * Retrieves the settings page class for Community.
		 *
		 * This method applies the 'tec_events_community_settings_strategy' filter to allow customization
		 * of the handler used for settings. It checks if the handler is callable, and if not, it returns
		 * an empty string to prevent fatal errors.
		 *
		 * @since 5.0.0
		 *
		 * @return mixed The settings class that should be used for Community, or an empty string if the handler is not callable.
		 */
		public static function get_settings_strategy() {
			static $strategy_set = false;
			static $strategy     = null;

			if ( ! $strategy_set ) {
				/**
				 * Filters the settings handler.
				 *
				 * This filter allows hooking into the settings logic.
				 * Only the last handler that is sent to this filter will run. To overwrite the logic use a higher priority.
				 *
				 * @since 5.0.0
				 *
				 * @param callable $handler The callback handler for settings.
				 */
				$strategy = apply_filters( 'tec_events_community_settings_strategy', '__return_false' );
			}

			if ( ! is_callable( $strategy ) ) {
				// This is a dummy settings handler that offers no additional logic.
				return tribe( Default_Settings_Strategy::class );
			}

			// Instantiate the handler class.
			$strategy_instance = call_user_func( $strategy );

			/*
			Validate that the handler class has the required property and method.
				For the settings handler to work properly we need -
				- $settings_page_id
				- get_url()
			*/
			if ( ! is_object( $strategy_instance ) ||
				 ! property_exists( $strategy_instance, 'settings_page_id' ) ||
				 ! method_exists( $strategy_instance, 'get_url' ) ) {
				// If validation fails, return a default handler.
				return tribe( Default_Settings_Strategy::class );
			}

			return $strategy_instance;
		}

		/**
		 * Retrieves the event label based on the type.
		 *
		 * This method applies a filter to allow customization of the event labels
		 * used for Community. It checks the type and returns the appropriate label.
		 *
		 * @param string $type The type of label to retrieve ('singular', 'plural', 'singular_lowercase').
		 * @return string The event label for the specified type.
		 */
		public static function get_event_label( $type ) {
			switch ( $type ) {
				case 'singular':
					/**
					 * Filter the singular event label.
					 *
					 * Allows customization of the singular event label.
					 *
					 * @since 5.0.0
					 *
					 * @param string $label The singular event label. Default 'Event'.
					 */
					return apply_filters( 'tribe_community_events_event_label_singular', 'Event' );
				case 'plural':
					/**
					 * Filter the plural event label.
					 *
					 * Allows customization of the plural event label.
					 *
					 * @since 5.0.0
					 *
					 * @param string $label The plural event label. Default 'Events'.
					 */
					return apply_filters( 'tribe_community_events_event_label_plural', 'Events' );
				case 'singular_lowercase':
					/**
					 * Filter the singular lowercase event label.
					 *
					 * Allows customization of the singular lowercase event label.
					 *
					 * @since 5.0.0
					 *
					 * @param string $label The singular lowercase event label. Default 'event'.
					 */
					return apply_filters( 'tribe_community_events_event_label_singular_lowercase', 'event' );
				default:
					/**
					 * Filter the default event label.
					 *
					 * Allows customization of the default event label.
					 *
					 * @since 5.0.0
					 *
					 * @param string $label The default event label. Default 'Event'.
					 */
					return apply_filters( 'tribe_community_events_event_label_default', 'Event' );
			}
		}

		/**
		 * Checks if the given post is an event with the origin set to 'community-events'.
		 *
		 * @since 5.0.0
		 *
		 * @param int $post_id The ID of the post to check.
		 *
		 * @return bool True if the post's _EventOrigin meta key is set to 'community-events', false otherwise.
		 */
		public function tribe_is_event( $post_id ) {
			// Get the _EventOrigin meta value for the post.
			$event_origin = get_post_meta( $post_id, '_EventOrigin', true );

			// Check if the meta value is 'community-events'.
			return 'community-events' === $event_origin;
		}

		/**
		 * Wrapper for getting events.
		 *
		 * This method acts as a wrapper for fetching events using the Tribe__Events__Query class if The Events Calendar (TEC) is enabled.
		 * It allows for customization of the event query arguments and handles scenarios where TEC is not active.
		 *
		 * @since 5.0.0
		 *
		 * @param array $args An array of arguments to customize the event query. Defaults to an empty array.
		 * @param bool  $full Whether to fetch the full event objects. Defaults to false.
		 *
		 * @return WP_Query An array of event objects, otherwise an empty array.
		 */
		public function get_events( array $args = [], bool $full = false ): \WP_Query {
			$default_args = [
				'post_type'      => $this->get_community_events_post_type(),
				'posts_per_page' => 10,
				'author'         => wp_get_current_user()->ID,
				'post_status'    => [ 'pending', 'draft', 'future', 'publish' ],
				'meta_query'     => [
					[
						'key'     => '_EventOrigin',
						'value'   => 'community-events',
						'compare' => '=',
					],
				],
			];
			$args         = wp_parse_args( $args, $default_args );
			$data         = new WP_Query( $args );

			return apply_filters( 'tribe_community_events_get_event_query', $data, $args, $full );
		}

		/**
		 * Check if The Events Calendar is enabled.
		 *
		 * @since 5.0.7 switched to using `tec_events_fully_loaded` instead of `tribe_events_bound_implementations`.
		 * @return int|null
		 */
		public function tec_enabled() {
			return did_action( 'tec_events_fully_loaded' );
		}

		/**
		 * Check if Events Pro is enabled.
		 *
		 * @since 5.0.7 switched to using `tec_events_pro_fully_loaded` instead of class_exists().
		 * @return int|null
		 */
		public function ecp_enabled() {
			return did_action( 'tec_events_pro_fully_loaded' );
		}

		/**
		 * Check if Event Tickets is enabled.
		 *
		 * @since 5.0.7 switched to using `tec_tickets_fully_loaded` instead of `tribe_tickets_plugin_loaded`.
		 * @return int|null
		 */
		public function et_enabled() {
			return did_action( 'tec_tickets_fully_loaded' );
		}

		/**
		 * Define the event form layout.
		 *
		 * @todo redscar - Review later to see if this should live in it's own class (I think it should).
		 *
		 * @since 5.0.0
		 *
		 * @return array The modules for the event form layout.
		 */
		public function event_form_layout() {
			$modules = [
				'title' => [
					'template' => 'community/modules/title',
					'data' => [ 'events_label_singular' => $this->get_event_label( 'singular' ) ],
				],
				'description' => [
					'template' => 'community/modules/description',
				],
				'image' => [
					'template' => 'community/modules/image',
				],
				'spam-control' => [
					'template' => 'community/modules/spam-control',
				],
				'terms' => [
					'template' => 'community/modules/terms',
					'data' => [
						'terms_enabled' => $this->getOption( 'termsEnabled' ),
						'terms_description' => $this->getOption( 'termsDescription' ),
					],
				],
			];

			// Apply filter to allow customization of the form layout.
			$modules = apply_filters( 'tec_events_community_form_layout', $modules );

			// Ensure the submit button is always included at the end.
			$modules['submit-button'] = [
				'template' => 'community/modules/submit',
			];

			return $modules;
		}

		/**
		 * Generate the event form layout.
		 *
		 * @todo redscar - Review later to see if this should live in it's own class (I think it should).
		 *
		 * @since 5.0.0
		 */
		public function generate_form_layout( $event_id ) {
			$modules = $this->event_form_layout();
			foreach ( $modules as $module_key => $module ) {
				/**
				 * Action hook before loading a module template part.
				 *
				 * @since 5.0.0
				 *
				 * @param int    $event_id The ID of the event.
				 * @param string $module_key The key of the module.
				 * @param array  $module The module configuration.
				 */
				do_action( "tec_events_community_form_before_module_{$module_key}", $event_id, $module_key, $module );

				// Include the template part for the module.
				tribe( Tribe__Events__Community__Templates::class )->tribe_get_template_part(
					$module['template'],
					null,
					$module['data'] ?? []
				);

				/**
				 * Action hook after loading a module template part.
				 *
				 * @since 5.0.0
				 *
				 * @param string $module_key The key of the module.
				 * @param array  $module     The module configuration.
				 */
				do_action( "tec_events_community_form_after_module_{$module_key}", $module_key, $module );
			}
		}

		/**
		 * Retrieves default rewrite slugs based on the provided slug key.
		 * If no key is provided, returns all default rewrite slugs.
		 *
		 * This method allows for a flexible retrieval of either a specific rewrite slug or all rewrite slugs,
		 * depending on the need. It checks if the provided slug key exists in the array of slugs and returns it;
		 * if no key is provided, it returns the entire array of default rewrite slugs.
		 *
		 * @param string|null $slug The key for the rewrite slug to retrieve. Null by default.
		 *
		 * @return mixed The rewrite slug if a specific key is provided and found, otherwise all rewrite slugs.
		 */
		public function get_default_rewrite_slugs( ?string $slug = null ) {
			/**
			 * Filters the default rewrite slugs array to allow for external modifications.
			 *
			 * @since 5.0.0
			 *
			 * @param array $default_rewrite_slugs The initial default rewrite slugs array.
			 *
			 * @return array The modified default rewrite slugs array.
			 */
			$modified_slugs = apply_filters( 'tec_events_community_modify_default_rewrite_slugs', $this->default_rewrite_slugs );

			// If no specific slug key is provided, return the entire array.
			if ( null === $slug ) {
				return $modified_slugs;
			}

			// Return the rewrite slug associated with the provided key or null.
			return $modified_slugs[ $slug ] ?? null;
		}

		/**
		 * Retrieves a sanitized rewrite slug for a given community setting.
		 *
		 * This function fetches the option for a specified slug from the WordPress options,
		 * prefixed by 'community-'. It checks against the default rewrite slugs to ensure
		 * validity before fetching. If the option does not exist or if the slug is not valid,
		 * a default slug is used. The returned slug is sanitized to ensure it is safe for use in URLs.
		 *
		 * @param string $slug The key part of the option to retrieve, representing the specific setting.
		 *
		 * @return string The sanitized title of the rewrite slug from the options, or a default slug if not set or invalid.
		 */
		public function get_rewrite_slug( string $slug ): string {
			$default_slugs = $this->get_default_rewrite_slugs();

			// Check if the provided slug is valid.
			if ( ! array_key_exists( $slug, $default_slugs ) ) {
				return sanitize_title( 'community' ); // Returning 'community' as a default slug if not found.
			}

			// Get the default slug if not set in options.
			$default_slug = $default_slugs[ $slug ];

			$option_value = $this->getOption( "community-{$slug}-slug", $default_slug, true );

			return sanitize_title( $option_value );
		}

		/**
		 * Sets the compatibility checks for WooCommerce.
		 *
		 * Used by the WooCommerce Commerce Module to configure compatibility with certain features of WooCommerce.
		 *
		 * The action is set to priority 100 to ensure this code runs as late as possible,
		 * guaranteeing that WooCommerce's functionality is fully loaded before proceeding.
		 *
		 * By passing true to class_exists(), we allow the autoloader to attempt loading the class
		 * if it's not already loaded. WooCommerce autoloads most of its classes early in the
		 * initialization process, so there is minimal risk that the class isn't available.
		 * This also ensures that we don't miss the class if it's available but hasn't been explicitly loaded yet.
		 *
		 * The hooks here are intentionally using anonymous methods as we do not want them to be removed.
		 *
		 * @since 5.0.0.1
		 * @since 5.0.4 Updated compatibility logic for WooCommerce HPOS.
		 *
		 * @return void
		 */
		public static function set_woocommerce_compatibility_checks(): void {
			add_action(
				'before_woocommerce_init',
				static function () {
					if ( ! class_exists( FeaturesUtil::class, true ) ) {
						// Debugging message or further handling if necessary.
						return;
					}
					// Declare compatibility with WooCommerce's custom order tables feature.
					FeaturesUtil::declare_compatibility( 'custom_order_tables', EVENTS_COMMUNITY_FILE, true );
				},
				100
			);
		}

		/**
		 * Deprecated methods
		 */

		/**
		 * Get the rewrite slug
		 *
		 * @deprecated 5.0.0
		 *
		 * @return string
		 */
		public function getRewriteSlug() {
			_deprecated_function( __FUNCTION__, '5.0.0', 'get_rewrite_slug' );
			return $this->get_rewrite_slug( 'events' );
		}

		/**
		 * Checks if it should flush rewrite rules (after plugin is loaded).
		 *
		 * @since  1.0.1
		 *
		 * @deprecated 5.0.0
		 *
		 * @return void
		 */
		public function maybeFlushRewriteRules() {
			_deprecated_function( __FUNCTION__, '5.0.0', 'No replacement.' );
		}

		/**
		 * If the anonymous submit setting is changed, flush the rewrite rules.
		 *
		 * @since  1.0.1
		 *
		 * @deprecated 5.0.0.1
		 *
		 * @param string $field The name of the field being saved.
		 *
		 *  @param string $value The new value of the field.
		 *
		 * @return void
		 * @author Paul Hughes
		 */
		public function flushRewriteOnAnonymous( $field, $value ) {
			_deprecated_function( __FUNCTION__, '5.0.0.1', 'No replacement.' );
			if ( $field == 'allowAnonymousSubmissions' && $value != $this->allowAnonymousSubmissions ) {
				// Do nothing.
				return;
			}
		}

		/**
		 * Send email alerts for event submissions.
		 *
		 * @since 1.0
		 * @deprecated 5.0.7 Use send_email_alerts() instead.
		 *
		 * @param int $tribe_event_id The event ID.
		 *
		 * @return bool Whether the emails were sent successfully.
		 */
		public function sendEmailAlerts( $tribe_event_id ) {
			_deprecated_function( __METHOD__, '5.0.7', 'send_email_alerts' );

			return $this->send_email_alerts( $tribe_event_id );
		}
	}
}
