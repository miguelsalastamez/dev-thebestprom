<?php
/**
 * Templating functionality for Tribe Events Calendar
 */

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'Tribe__Events__Community__Templates' ) ) {

	/**
	 * Handle views and template files.
	 */
	class Tribe__Events__Community__Templates {

		public function __construct() {
			add_filter( 'tribe_events_template_paths', [ $this, 'add_community_template_paths' ] );
			add_filter( 'tribe_support_registered_template_systems', [ $this, 'add_template_updates_check' ] );
		}

		/**
		 * Filter template paths to add the community plugin to the queue
		 *
		 * @param array $paths
		 * @return array $paths
		 * @author Peter Chester
		 * @since 3.1
		 */
		public function add_community_template_paths( $paths ) {
			$paths['community'] = tribe( 'community.main' )->pluginPath;
			return $paths;
		}


		/**
		 * Loads theme files in appropriate hierarchy: 1) child theme,
		 * 2) parent template, 3) plugin resources. will look in the events/
		 * directory in a theme and the views/ directory in the plugin
		 *
		 * @param string $template template file to search for.
		 * @param array  $args additional arguments to affect the template path.
		 *                         - namespace
		 *                         - plugin_path
		 *                         - disable_view_check - bypass the check to see if the view is enabled
		 *
		 * @return string Template path.
		 **/
		public static function getTemplateHierarchy( $template, $args = [] ) {
			if ( ! is_array( $args ) ) {
				$passed        = func_get_args();
				$args          = [];
				$backwards_map = [ 'namespace', 'plugin_path' ];
				$count         = count( $passed );

				if ( $count > 1 ) {
					for ( $i = 1; $i < $count; $i++ ) {
						$args[ $backwards_map[ $i - 1 ] ] = $passed[ $i ];
					}
				}
			}

			$args = wp_parse_args(
				$args,
				[
					'namespace'          => '/',
					'plugin_path'        => '',
					'disable_view_check' => false,
				]
			);
			/**
			 * @var string $namespace
			 * @var string $plugin_path
			 * @var bool   $disable_view_check
			 */
			extract( $args );

			$ce = Tribe__Events__Community__Main::instance();

			// append .php to file name.
			if ( substr( $template, -4 ) != '.php' ) {
				$template .= '.php';
			}

			// Allow base path for templates to be filtered.
			$template_base_paths = apply_filters( 'tribe_events_template_paths', (array) $ce->pluginPath );

			// backwards compatibility if $plugin_path arg is used.
			if ( $plugin_path && ! in_array( $plugin_path, $template_base_paths ) ) {
				array_unshift( $template_base_paths, $plugin_path );
			}

			// ensure that addon plugins look in the right override folder in theme.
			$namespace = ! empty( $namespace ) ? trailingslashit( $namespace ) : $namespace;

			$file = false;

			/*
			Potential scenarios:

			- the user has no template overrides
				-> we can just look in our plugin dirs, for the specific path requested, don't need to worry about the namespace
			- the user created template overrides without the namespace, which reference non-overrides without the namespace and, their own other overrides without the namespace
				-> we need to look in their theme for the specific path requested
				-> if not found, we need to look in our plugin views for the file by adding the namespace
			- the user has template overrides using the namespace
				-> we should look in the theme dir, then the plugin dir for the specific path requested, don't need to worry about the namespace

			*/

			// check if there are overrides at all.
			if ( locate_template( [ 'tribe-events/' ] ) ) {
				$overrides_exist = true;
			} else {
				$overrides_exist = false;
			}

			if ( $overrides_exist ) {
				// check the theme for specific file requested.
				$file = locate_template( [ 'tribe-events/' . $template ], false, false );
				if ( ! $file ) {
					// if not found, it could be our plugin requesting the file with the namespace,
					// so check the theme for the path without the namespace.
					$files = [];
					foreach ( array_keys( $template_base_paths ) as $namespace ) {
						if ( ! empty( $namespace ) && ! is_numeric( $namespace ) ) {
							$files[] = 'tribe-events' . str_replace( $namespace, '', $template );
						}
					}
					$file = locate_template( $files, false, false );
					if ( $file ) {
						_deprecated_function( sprintf( esc_html__( 'Template overrides should be moved to the correct subdirectory: %s', 'tribe-events-community' ), str_replace( get_stylesheet_directory() . '/tribe-events/', '', $file ) ), '3.2', $template );
					}
				} else {
					$file = apply_filters( 'tribe_events_template', $file, $template );
				}
			}

			// if the theme file wasn't found, check our plugins views dirs.
			if ( ! $file ) {

				foreach ( $template_base_paths as $template_base_path ) {

					// make sure directories are trailingslashed.
					$template_base_path = ! empty( $template_base_path ) ? trailingslashit( $template_base_path ) : $template_base_path;

					$file = $template_base_path . 'src/views/' . $template;

					$file = apply_filters( 'tribe_events_template', $file, $template );

					// return the first one found.
					if ( file_exists( $file ) ) {
						break;
					} else {
						$file = false;
					}
				}
			}

			// file wasn't found anywhere in the theme or in our plugin at the specifically requested path,
			// and there are overrides, so look in our plugin for the file with the namespace added
			// since it might be an old override requesting the file without the namespace.
			if ( ! $file && $overrides_exist ) {
				foreach ( $template_base_paths as $_namespace => $template_base_path ) {

					// make sure directories are trailingslashed.
					$template_base_path = ! empty( $template_base_path ) ? trailingslashit( $template_base_path ) : $template_base_path;
					$_namespace         = ! empty( $_namespace ) ? trailingslashit( $_namespace ) : $_namespace;

					$file = $template_base_path . 'src/views/' . $_namespace . $template;

					$file = apply_filters( 'tribe_events_template', $file, $template );

					// return the first one found.
					if ( file_exists( $file ) ) {
						_deprecated_function( sprintf( esc_html__( 'Template overrides should be moved to the correct subdirectory: tribe_get_template_part(\'%s\')', 'tribe-events-community' ), $template ), '3.2', 'tribe_get_template_part(\'' . $_namespace . $template . '\')' );
						break;
					}
				}
			}

			return apply_filters( 'tribe_events_template_' . $template, $file );
		}

		/**
		 * Includes a template part, similar to the WP get template part, but looks
		 * in the correct directories for Tribe Events templates
		 *
		 * @param null|string $name
		 *
		 * @param array       $data optional array of vars to inject into the template part
		 *
		 * @param string      $slug
		 *
		 * @uses Tribe__Templates::getTemplateHierarchy
		 *
		 */
		function tribe_get_template_part( $slug, $name = null, array $data = null ) {

			// Execute code for this part.
			do_action( 'tribe_pre_get_template_part_' . $slug, $slug, $name, $data );
			// Setup possible parts.
			$templates = [];
			if ( isset( $name ) ) {
				$templates[] = $slug . '-' . $name . '.php';
			}
			$templates[] = $slug . '.php';

			// Allow template parts to be filtered.
			$templates = apply_filters( 'tribe_get_template_part_templates', $templates, $slug, $name );

			// Make any provided variables available in the template's symbol table.
			if ( is_array( $data ) ) {
				extract( $data );
			}

			// loop through templates, return first one found.
			foreach ( $templates as $template ) {
				$file = $this->getTemplateHierarchy( $template, [ 'disable_view_check' => true ] );
				$file = apply_filters( 'tribe_get_template_part_path', $file, $template, $slug, $name );
				$file = apply_filters( 'tribe_get_template_part_path_' . $template, $file, $slug, $name );
				if ( file_exists( $file ) ) {
					ob_start();
					do_action( 'tribe_before_get_template_part', $template, $file, $template, $slug, $name );
					include( $file );
					do_action( 'tribe_after_get_template_part', $template, $file, $slug, $name );
					$html = ob_get_clean();
					echo apply_filters( 'tribe_get_template_part_content', $html, $template, $file, $slug, $name );
					break; // We found our template, no need to continue the loop.
				}
			}
			do_action( 'tribe_post_get_template_part_' . $slug, $slug, $name, $data );
		}

		/**
		 * Register Community with the template updates checker.
		 *
		 * @param array $plugins
		 *
		 * @return array
		 */
		public function add_template_updates_check( $plugins ) {
			// ET+ views can be in one of a range of different subdirectories (eddtickets, shopptickets
			// etc) so we will tell the template checker to simply look in views/tribe-events and work
			// things out from there
			$plugins[ __( 'Community', 'tribe-events-community' ) ] = [
				Tribe__Events__Community__Main::VERSION,
				tribe( 'community.main' )->pluginPath . 'src/views/community',
				trailingslashit( get_stylesheet_directory() ) . 'tribe/community',
			];

			$plugins[ __( 'Community - Legacy', 'tribe-events-community' ) ] = [
				Tribe__Events__Community__Main::VERSION,
				tribe( 'community.main' )->pluginPath . 'src/views/community',
				trailingslashit( get_stylesheet_directory() ) . 'tribe-events/community',
			];

			return $plugins;
		}

		/********** Singleton **********/

		/**
		 * @var Tribe__Events__Community__Templates $instance
		 */
		protected static $instance;

		/**
		 * Static Singleton Factory Method
		 *
		 * @return Tribe__Events__Community__Templates
		 */
		public static function instance() {
			return tribe( 'community.templates' );
		}


		/**
		 * Hook into 'tribe_community_events_title' to avoid PHP 7.2 deprecated notices with `create_function`
		 *
		 * @since 4.5.10
		 *
		 * @return mixed|void
		 */
		public function tribe_community_events_title() {
			/**
			 * Replace the CE submit event page title.
			 *
			 * @since 4.5.10
			 *
			 * @return string
			 */
			$title = __( 'Submit an Event', 'tribe-events-community' );
			$title = apply_filters( 'tribe_events_community_submit_event_page_title', $title );

			return $title;
		}

	}
}
