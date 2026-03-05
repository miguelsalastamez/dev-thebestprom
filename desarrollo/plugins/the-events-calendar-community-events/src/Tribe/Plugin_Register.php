<?php
/**
 * Class Tribe__Events__Community__Plugin_Register
 *
 * @since 4.6
 */
class  Tribe__Events__Community__Plugin_Register extends Tribe__Abstract_Plugin_Register {

	/**
	 * The main class for the Community Events plugin.
	 *
	 * This property holds the name of the main class that handles
	 * the primary functionality of the Community Events plugin.
	 *
	 * @var string
	 */
	protected $main_class = 'Tribe__Events__Community__Main';

	/**
	 * The dependencies for the Community Events plugin.
	 *
	 * This property holds an array of dependencies that the Community Events plugin
	 * relies on. The 'parent-dependencies' key contains an array of plugin names
	 * and their respective versions that are required.
	 *
	 * @var array
	 */
	protected $dependencies = [
		'parent-dependencies' => [],
	];

	/**
	 * Minimum version of The Events Calendar that Community requires.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	public static string $minimum_tec_version = '6.7.0-dev';

	/**
	 * Minimum version of Event Tickets that Community requires.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	public static string $minimum_et_version = '5.23.0-dev';

	/**
	 * Constructor method.
	 *
	 * @since 4.6
	 */
	public function __construct() {
		$this->base_dir = EVENTS_COMMUNITY_FILE;
		$this->version  = Tribe__Events__Community__Main::VERSION;

		add_filter( 'tribe_register_Tribe__Events__Community__Main_plugin_dependencies', [ $this, 'find_dependent_plugins' ] );

		$this->register_plugin();
	}

	/**
	 * Finds dependent plugins based on specific actions.
	 *
	 * Since Community Events doesn't have a strict dependency requirement,
	 * this method checks if Event Tickets (ET) or The Events Calendar (TEC)
	 * have been installed by verifying if specific actions have been fired.
	 *
	 * If the `tribe_events_bound_implementations` action has been fired,
	 * it indicates a dependency on TEC. Community Events requires at least
	 * a minimum of Common version 6.0.
	 *
	 * If the `tec_tickets_fully_loaded` action has been fired,
	 * it indicates a dependency on ET. Community Events requires at least
	 * a minimum of Common version 6.0.
	 *
	 * @since 5.0.0
	 * @since 5.0.7 switched to using `tec_tickets_fully_loaded` instead of `tribe_tickets_plugin_loaded`.
	 *
	 * @param $dependencies array $dependencies An array of dependencies for the plugins. These can include parent, add-on and other dependencies.
	 *
	 * @return array
	 */
	public function find_dependent_plugins( $dependencies ): array {
		// Check if Event Tickets (ET) dependency exists.
		if ( did_action( 'tec_tickets_fully_loaded' ) ) {
			// ET version 5.13.0 uses a minimum of Common version 6.0.
			$dependencies['parent-dependencies']['Tribe__Tickets__Main'] = self::$minimum_et_version;
		}

		// Check if The Events Calendar (TEC) dependency exists, or if ET isn't required as a dependency.
		if (
			empty( $dependencies['parent-dependencies'] )
			|| did_action( 'tribe_events_bound_implementations' )
		) {
			$dependencies['parent-dependencies']['Tribe__Events__Main'] = self::$minimum_tec_version;
		}

		return $dependencies;
	}

	/**
	 * Add Event Tickets dependency if it's active.
	 *
	 * @since 4.10.17
	 *
	 * @deprecated  5.0.0
	 *
	 * @param array $dependencies An array of dependencies for the plugins. These can include parent, add-on and other dependencies.
	 *
	 * @return array
	 */
	public function add_tec_tickets_as_dependency_if_active( $dependencies ) {
		_deprecated_function( __FUNCTION__, '5.0.0', 'No Replacement' );
		if ( class_exists( 'Tribe__Tickets__Main', false ) ) {
			$dependencies['parent-dependencies']['Tribe__Tickets__Main'] = '5.9.1-dev';
		}

		return $dependencies;
	}
}
