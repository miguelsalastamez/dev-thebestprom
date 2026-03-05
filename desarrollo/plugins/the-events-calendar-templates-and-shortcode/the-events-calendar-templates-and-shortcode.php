<?php
/*
 Plugin Name:Events Shortcodes Pro
 Plugin URI:https://eventscalendaraddons.com/plugin/events-shortcodes-pro/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugin_uri
 Description:<a href="http://wordpress.org/plugins/the-events-calendar/">📅 The Events Calendar Addon</a> - Use shortcodes to display a list of events from The Events Calendar plugin in premium layouts, including grid, masonry, carousel, and slider, on any page or post.
 Version:3.0
 Requires at least: 5.0
 Tested up to:6.4.2
 Requires PHP:5.6
 Stable tag:trunk
 License:GPL2
 Author:Cool Plugins
 Author URI:https://coolplugins.net/
 License URI:https://www.gnu.org/licenses/gpl-2.0.html
 Domain Path:/languages
 Text Domain:ect
*/
if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
if ( ! defined( 'ECT_PRO_VERSION' ) ) {
	define( 'ECT_PRO_VERSION', '3.0' );
}
/*** Defined constent for later use */
if ( ! defined( 'ECT_PRO_FILE' ) ) {
	define( 'ECT_PRO_FILE', __FILE__ );
}
if ( ! defined( 'ECT_PRO_PLUGIN_URL' ) ) {
	define( 'ECT_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'ECT_PRO_PLUGIN_DIR' ) ) {
	define( 'ECT_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
/*** EventsCalendarTemplates main class by CoolPlugins.net */
if ( ! class_exists( 'EventsCalendarTemplatesPro' ) ) {
	final class EventsCalendarTemplatesPro {

		/**
		 * The unique instance of the plugin.
		 */
		private static $instance;
		/**
		 * Gets an instance of our plugin.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		/**
		 * Constructor.
		 */
		private function __construct() {        }
		// register all hooks
		public function registers() {
			$thisPlugin = self::$instance;

			/*** Installation and uninstallation hooks */
			register_activation_hook( __FILE__, array( 'EventsCalendarTemplatesPro', 'activate' ) );
			if ( get_option( 'ect-v' ) ) {
				update_option( 'ect-pro-v', ECT_PRO_VERSION );
			}
			register_deactivation_hook( __FILE__, array( 'EventsCalendarTemplatesPro', 'deactivate' ) );
			/*** Load required files */
			add_action( 'plugins_loaded', array( $thisPlugin, 'ect_load_files' ) );
			add_action( 'plugins_loaded', array( $thisPlugin, 'ect_check_event_calender_installed' ) );
			add_action( 'plugins_loaded', array( $thisPlugin, 'onLoad' ) );
			add_action( 'admin_enqueue_scripts', array( $thisPlugin, 'ect_tc_css' ) );
			if ( is_admin() ) {
				$this->register_admin();
			}
			require_once ECT_PRO_PLUGIN_DIR . 'includes/events-shortcode-pro.php';
			$events_shortcode = new EventsShortcodePro();
			/*** Include Gutenberg Block */
			require_once ECT_PRO_PLUGIN_DIR . 'admin/gutenberg-block/ect-block.php';
			add_action( 'plugin_row_meta', array( $thisPlugin, 'ect_pro_addMeta_Links' ), 10, 2 );
		}
		/**
		 * Add meta links to the Plugins list page.
		 *
		 * @param array  $links The current action links.
		 * @param string $file  The plugin to see if we are on Event Single Page.
		 *
		 * @return array The modified action links array.
		 */
		public function ect_pro_addMeta_Links( $links, $file ) {
			if ( strpos( $file, basename( __FILE__ ) ) ) {
				$ectanchor   = esc_html__( 'Video Tutorials', 'ect' );
				$ectvideourl = 'https://eventscalendaraddons.com/docs/events-shortcodes-pro/video-tutorials/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=video_tutorial&utm_content=plugins_list';
				$links[]     = '<a href="' . esc_url( $ectvideourl ) . '" target="_blank">' . $ectanchor . '</a>';
			}

			return $links;
		}
		// register admin all hooks
		public function register_admin() {
			$thisPlugin = new self();
			add_action( 'admin_init', array( $thisPlugin, 'ect_settings_migration' ) );
			add_action( 'admin_init', array( $thisPlugin, 'onInit' ) );
			add_action( 'admin_enqueue_scripts', array( $thisPlugin, 'ect_remove_wpcalpha' ), 99 );
			add_action( 'admin_init', array( $thisPlugin, 'ect_plugin_redirect' ) );
			/*** Template Setting Page Link */
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( self::$instance, 'ect_template_settings_page' ) );
			foreach ( array( 'post.php', 'post-new.php' ) as $hook ) {
				add_action( "admin_head-$hook", array( self::$instance, 'ect_rest_url' ) );
			}
		}
		function onLoad() {
			// language translation
			load_plugin_textdomain( 'ect', false, basename( dirname( __FILE__ ) ) . '/languages/' );
			if ( file_exists( plugin_dir_path( __DIR__ ) . 'template-events-calendar/events-calendar-templates.php' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				if ( is_plugin_active( 'template-events-calendar/events-calendar-templates.php' ) ) {
					deactivate_plugins( 'template-events-calendar/events-calendar-templates.php' );
				}
			}
			if ( ! class_exists( 'Tribe\Extensions\EventsControl\Main' ) ) {
				add_action( 'admin_notices', array( $this, 'Install_Ext_Events_Notice' ) );
			}
		}
		/*** Load required files */
		public function ect_load_files() {
			if ( class_exists( 'Tribe__Events__Main' ) or defined( 'Tribe__Events__Main::VERSION' ) ) {
				if ( file_exists( plugin_dir_path( __DIR__ ) . 'elementor/elementor.php' ) ) {
					include_once ABSPATH . 'wp-admin/includes/plugin.php';
					if ( is_plugin_active( 'elementor/elementor.php' ) ) {
						require_once ECT_PRO_PLUGIN_DIR . 'admin/elementor/ect-elementor.php';
					}
				}
				if ( defined( 'WPB_VC_VERSION' ) ) {
					require_once ECT_PRO_PLUGIN_DIR . 'admin/visual-composer/ect-class-vc.php';
				}
			}
			if ( is_admin() ) {
				/*** Plugin review notice file */
				require_once ECT_PRO_PLUGIN_DIR . 'admin/notices/admin-notices.php';
				require_once ECT_PRO_PLUGIN_DIR . 'admin/events-addon-page/events-addon-page.php';
				cool_plugins_events_addon_settings_page( 'the-events-calendar', 'cool-plugins-events-addon', '📅 Events Addons For The Events Calendar' );
				require_once ECT_PRO_PLUGIN_DIR . 'admin/registration-settings.php';
				require_once ECT_PRO_PLUGIN_DIR . 'admin/init-api.php';

				require_once ECT_PRO_PLUGIN_DIR . 'admin/ectcsf-framework/ectcsf-framework.php';
				require_once ECT_PRO_PLUGIN_DIR . 'admin/ect-codestar-settings.php';
				$settings_panel = new ECTProSettings();
				require_once ECT_PRO_PLUGIN_DIR . 'admin/ect-event-shortcode.php';
			}
		}
		// add notice on plugin init
		public function onInit() {
			if ( get_option( 'ect-v' ) !== false ) {
				if ( version_compare( get_option( 'ect-v' ), '3.0', '<' ) ) {
					ect_pro_create_admin_notice(
						array(
							'id'              => 'ect-pro-setting-change',
							'message'         => wp_kses_post( __( '<strong>Major design update</strong> for <strong>Events Shortcodes (Pro)</strong> plugin in version 3.0! Update or reset <a href=' . admin_url( 'admin.php?page=tribe-events-shortcode-template-settings' ) . '>style settings</a> if you face any design issues.', 'ect' ) ),
							'review_interval' => 0,
						)
					);
				}
			}

			if ( version_compare( get_option( 'ect-pro-v' ), '2.7', '<' ) ) {
					ect_pro_create_admin_notice(
						array(
							'id'              => 'ect-pro-setting-migration',
							'message'         => wp_kses_post( __( '<strong>Important Update</strong>:- <strong>Events Shortcodes (Pro) - The Events Calendar Addon</strong> plugin has integrated new settings panel. Please save your settings and check events views.', 'ect' ) ),
							'review_interval' => 0,
						)
					);
			}
			if ( ( did_action( 'elementor/loaded' ) && ! class_exists( 'Events_Calendar_Addon' ) ) || ( did_action( 'elementor/loaded' ) && ! class_exists( 'Events_Calendar_Addon_Pro' ) ) ) {
				ect_pro_create_admin_notice(
					array(
						'id'              => 'ect-elementor-addon-notice',
						'message'         => wp_kses_post(
							__(
								'Hi! We checked that you are using <strong>Elementor Page Builder</strong>.
							<br/>Please try latest <a target="_blank" href="https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro_ectbe&utm_content=elementor_notice"><strong>The Events Calendar Widgets For Elementor</strong></a> plugin developed by <a href="https://coolplugins.net">Cool Plugins</a>
							   & <br/> represent The Events Calendar events in the Elementor page builder pages.',
								'ect'
							)
						),
						'review_interval' => 3,
						'logo'            => ECT_PRO_PLUGIN_URL . 'assets/images/events-widgets-elementor-logo.svg',
					)
				);
			}
				/*** Plugin review notice file */
				ect_pro_create_admin_notice(
					array(
						'id'              => 'ect_pro_review_box',  // required and must be unique
						'slug'            => 'ect',      // required in case of review box
						'review'          => true,     // required and set to be true for review box
						'review_url'      => esc_url( 'https://codecanyon.net/item/the-events-calendar-templates-and-shortcode-wordpress-plugin/reviews/20143286/#new-post' ), // required
						'plugin_name'     => 'Events Shortcodes Pro  Addon',    // required
						'logo'            => ECT_PRO_PLUGIN_URL . 'assets/images/ect-icon.svg',    // optional: it will display logo
						'review_interval' => 3,                    // optional: this will display review notice
																// after 5 days from the installation_time
																// default is 3
					)
				);
		}
		/*** Admin side shortcode generator style CSS */
		public function ect_tc_css() {

			wp_enqueue_style( 'sg-btn-css', plugins_url( 'assets/css/shortcode-generator.css', __FILE__ ) );
		}

		/*** Add links in plugin install list */
		public function ect_template_settings_page( $links ) {
			$links[] = '<a style="font-weight:bold" href="' . esc_url( get_admin_url( null, 'admin.php?page=tribe-events-shortcode-template-settings' ) ) . '">Shortcodes Settings</a>';
			// $links[] = '<a  style="font-weight:bold" href="https://eventscalendaraddons.com/" target="_blank">View Demos</a>';
			return $links;
		}
		// set rest url object for geneator data
		public function ect_rest_url() {
			?>
			<!-- TinyMCE Shortcode Plugin -->
			<script type='text/javascript'>
			var ectRestUrl='<?php echo esc_url( get_rest_url( null, '/tribe/events/v1/' ) ); ?>'
			</script>
			<!-- TinyMCE Shortcode Plugin -->
			<?php
		}
		/*** Check The Events calender is installled or not. If user has not installed yet then show notice */
		public function ect_check_event_calender_installed() {
			if ( ! class_exists( 'Tribe__Events__Main' ) or ! defined( 'Tribe__Events__Main::VERSION' ) ) {
				add_action( 'admin_notices', array( $this, 'Install_ECT_Notice' ) );
			}
		}
		public function Install_ECT_Notice() {
			if ( current_user_can( 'activate_plugins' ) ) {
				printf(
					'<div class="error CTEC_Msz"><p>' .
					esc_html( __( '%1$s %2$s', 'ebec' ) ),
					esc_html( __( 'In order to use this addon, Please first install the latest version of', 'ebec' ) ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( 'plugin-install.php?tab=plugin-information&plugin=the-events-calendar&TB_iframe=true' ),
						esc_html( __( 'The Events Calendar', 'ect' ) ),
					) . '</p></div>'
				);
			}
		}
		// notice for installation TEC parent plugin installation
		public function Install_Ext_Events_Notice() {
			if ( current_user_can( 'activate_plugins' ) ) {
				if ( get_transient( 'ect-status-timing' ) ) {
					$url              = 'https://github.com/mt-support/tribe-ext-events-control/';
					$title            = __( 'The Events Calendar Extension: Events Control', 'ect2' );
					$dont_disturb_url = esc_url( get_admin_url() . '?ect_status_disable_notice=true' );
					?>
					<div class="updated notice is-dismissible">
					<p><?php echo sprintf( __( 'In order to set the event status to Online event,Canceled or Postponed, Please first install the latest version of <a href="%1$s" target="_blank" title="%2$s">%3$s</a>.', 'ect' ), esc_url( $url ), esc_attr( $title ), esc_attr( $title ) ); ?> || <a href="<?php echo $dont_disturb_url; ?> "class="ect-review-done ">Not Interested!</a>
					</p>
					</div>
					<?php
					delete_transient( 'ect-status-timing' );
				}
			}
		}
		/*
		Old settings migration
		*/
		// old titan settings panel fields data
		public function get_titan_settings() {
			$new_settings = array();
			if ( get_option( 'ect_options' ) != false ) {
				$titan_raw_data = get_option( 'ect_options' );
				if ( is_serialized( $titan_raw_data ) ) {
					$titan_settings = array_filter( maybe_unserialize( $titan_raw_data ) );
					if ( is_array( $titan_settings ) ) {
						foreach ( $titan_settings as $key => $val ) {
							$new_settings[ $key ] = maybe_unserialize( $val );
						}
					}
				}
				return $new_settings;
			} else {
				return false;
			}
		}
		/*
			On activation save some settings for later use
		*/
		public static function activate() {
			if ( file_exists( plugin_dir_path( __DIR__ ) . 'template-events-calendar/events-calendar-templates.php' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				if ( is_plugin_active( 'template-events-calendar/events-calendar-templates.php' ) ) {
					deactivate_plugins( 'template-events-calendar/events-calendar-templates.php' );
				}
			}
			update_option( 'ect-pro-v', ECT_PRO_VERSION );
			update_option( 'ect-type', 'PRO' );
			update_option( 'ect-installDate', date( 'Y-m-d h:i:s' ) );
			update_option( 'ect-ratingDiv', 'no' );
			update_option( 'ect_do_activation_redirect', true );
		}
		public static function deactivate() {
			delete_option( 'settings_migration_status' );
			delete_option( 'ect-pro-v' );
			delete_option( 'ect-type' );
			delete_option( 'ect-installDate' );
			delete_option( 'ect-ratingDiv' );
		}
		function ect_settings_migration() {
			if ( version_compare( get_option( 'ect-pro-v' ), '2.6', '>' ) ) {
				return;
			}
			if ( get_option( 'settings_migration_status' ) ) {
				return;
			}
			$old_settings = $this->get_titan_settings();
			if ( $old_settings == false ) {
				return;
			}
			if ( is_array( $old_settings ) ) {
				$req_settings = array(
					'font-family',
					'font-size',
					'font-weight',
					'font-style',
					'line-height',
					'letter-spacing',
					'text-transform',
					'color',
					'font-type',
				);
				$webSafeFonts = array(
					'Arial, Helvetica, sans-serif'         => 'Arial',
					'"Arial Black", Gadget, sans-serif'    => 'Arial Black',
					'"Comic Sans MS", cursive, sans-serif' => 'Comic Sans MS',
					'"Courier New", Courier, monospace'    => 'Courier New',
					'Georgia, serif'                       => 'Geogia',
					'Impact, Charcoal, sans-serif'         => 'Impact',
					'"Lucida Console", Monaco, monospace'  => 'Lucida Console',
					'"Lucida Sans Unicode", "Lucida Grande", sans-serif' => 'Lucida Sans Unicode',
					'"Palatino Linotype", "Book Antiqua", Palatino, serif' => 'Palatino Linotype',
					'Tahoma, Geneva, sans-serif'           => 'Tahoma',
					'"Times New Roman", Times, serif'      => 'Times New Roman',
					'"Trebuchet MS", Helvetica, sans-serif' => 'Trebuchet MS',
					'Verdana, Geneva, sans-serif'          => 'Verdana',
				);
				$old_font_arr = array_flip( $webSafeFonts );
				$new_settings = array();
				foreach ( $old_settings as $key => $field_val ) {
					if ( is_array( $field_val ) ) {
						foreach ( $field_val as $index => $val ) {
							if ( in_array( $index, $req_settings ) ) {
								if ( $index == 'font-type' ) {
									$index = 'type';
								} elseif ( $index == 'font-size' ) {
									$val = str_replace( 'px', '', $val );
								} elseif ( $index == 'line-height' ) {
									$val = str_replace( 'em', '', $val );
								} elseif ( $index == 'letter-spacing' ) {
									$val = str_replace( 'em', '', $val );
								} elseif ( $index == 'font-family' ) {
									$found = array_search( $val, $old_font_arr );
									$val   = $found ? $found : $val;
								}
									$new_settings[ $key ][ $index ] = $val;
							}
						}
							$new_settings[ $key ]['line_height_unit'] = 'em';
							$new_settings[ $key ]['unit']             = 'px';
							$new_settings[ $key ]['subset']           = '';
							$new_settings[ $key ]['text-align']       = '';
							$new_settings[ $key ]['font-variant']     = '';
					} else {
						$new_settings[ $key ] = $field_val;
					}
				}
				update_option( 'ects_options', $new_settings );
				update_option( 'settings_migration_status', 'done' );
				delete_option( 'ect_options' );
			}
		} //end
		// remove notice if users has already saved settings
		function ect_remove_wpcalpha() {
			$current_screen = get_current_screen();
			if ( $current_screen->id === 'tribe_events_page_edit?post_type=tribe-events-shortcode-template-settings' ) {
				wp_dequeue_script( 'wp-color-picker-alpha' );
			}
		}
		// on plugin activation redirect to the setting page
		function ect_plugin_redirect() {
			if ( get_option( 'ect_do_activation_redirect', false ) ) {
				delete_option( 'ect_do_activation_redirect' );
				exit( wp_redirect( admin_url( 'admin.php?page=tribe-events-shortcode-template-settings' ) ) );
			}
		}
	} //class end here
}
/*** THANKS - CoolPlugins.net ) */
$ect = EventsCalendarTemplatesPro::get_instance();
$ect->registers();


