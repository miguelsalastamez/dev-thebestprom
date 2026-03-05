<?php

/**
 * Class Tribe__Events__Community__Tickets__Plugin_Register
 *
 * @since 5.0.0 Migrated to Community from Community Tickets.
 */
class  Tribe__Events__Community__Tickets__Plugin_Register extends Tribe__Abstract_Plugin_Register {

	/**
	 * Community Tickets' main class name.
	 *
	 * @var string $main_class
	 */
	protected $main_class = 'Tribe__Events__Community__Tickets__Main';

	/**
	 * Community Tickets' requirements.
	 *
	 * @see   \tribe_register_community_tickets()
	 *
	 * @var array $dependencies
	 */
	protected $dependencies = [
		'parent-dependencies' => [],
		'co-dependencies'     => [
			'Tribe__Tickets_Plus__Main'      => '5.7.1-dev',
			'Tribe__Events__Community__Main' => '4.10.8-dev',
		],
	];

	/**
	 * Constructor method.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 * @since 5.0.7 switched to using `tec_tickets_fully_loaded` instead of `tribe_tickets_plugin_loaded`.
	 */
	public function __construct() {
		// @todo If we'll need this class, need to evaluate base_dir usage.
		$this->base_dir = EVENTS_COMMUNITY_FILE;
		$this->version  = Tribe__Events__Community__Tickets__Main::VERSION;

		// If ET isn't loaded, then we don't want to register Community Tickets logic.
		if ( ! did_action( 'tec_tickets_fully_loaded' ) ) {
			return;
		}

		add_filter( 'tribe_register_Tribe__Events__Community__Tickets__Main_plugin_dependencies', [ $this, 'setup_ct_dependencies' ] );
		add_filter( 'tribe_register_Tribe__Events__Community__Tickets__Main_plugin_dependencies', [ $this, 'add_woo_as_dependency_if_able_via_common' ] );
		add_filter( 'tribe_register_Tribe__Events__Community__Tickets__Main_plugin_dependencies', [ $this, 'remove_dependency_if_tc_enabled' ] );

		$this->inform_dependency_manager_of_woocommerce();
		$this->register_plugin();
	}

	/**
	 * Set our dependency on Event Tickets using the minimum version found within CE main.
	 *
	 * @param $dependencies array $dependencies An array of dependencies for the plugins. These can include parent, add-on and other dependencies.
	 *
	 * @return array
	 */
	public function setup_ct_dependencies( $dependencies ) {
		// Set the minimum version of ET to that set in Tribe__Events__Community__Main.
		$dependencies['parent-dependencies']['Tribe__Tickets__Main'] = Tribe__Events__Community__Plugin_Register::$minimum_et_version;
		return $dependencies;
	}

	/**
	 * Allow Common's Dependency manager/notices to handle requiring WooCommerce by informing
	 * it of which version is active, if active at all.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 */
	private function inform_dependency_manager_of_woocommerce() {
		$woo = tribe_community_tickets_get_woocommerce_info_array();

		/**
		 * Tell that it's active and what version it is.
		 *
		 * @var Tribe__Dependency $dependency
		 */
		$dependency = tribe( Tribe__Dependency::class );

		$woo_version = null;

		if (
			function_exists( 'WC' )
			&& ! empty( WC()->version )
		) {
			$woo_version = WC()->version;

			$dependency->add_registered_plugin( $woo['class'], $woo_version, $woo['path'] );
			$dependency->add_active_plugin( $woo['class'], $woo_version, $woo['path'] );
		}
	}

	/**
	 * Add WooCommerce as a co-dependency via filter instead of class property to avoid grammar errors in the notice.
	 *
	 * @todo  Add WooCommerce as class property once Common v4.9.17 is far enough in the past.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @see   \tribe_community_tickets_get_woocommerce_info_array()
	 *
	 * @param array $dependencies An array of dependencies for the plugins. These can include parent, add-on and other dependencies.
	 *
	 * @return array
	 */
	public function add_woo_as_dependency_if_able_via_common( $dependencies ) {
		if (
			! empty( $GLOBALS['tribe-common-info']['version'] )
			&& -1 !== version_compare( $GLOBALS['tribe-common-info']['version'], '4.9.17' )
			&& is_array( $dependencies )
			&& is_array( $dependencies['co-dependencies'] )
			&& ! in_array( 'WooCommerce', $dependencies['co-dependencies'], true )
		) {
			$dependencies['co-dependencies']['WooCommerce'] = '3.2.0';
		}

		return $dependencies;
	}

	/**
	 * Remove Co-dependency when CT Tickets Commerce is enabled.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $dependencies An array of dependencies for the plugins. These can include parent, add-on and other dependencies.
	 *
	 * @return array
	 */
	public function remove_dependency_if_tc_enabled( array $dependencies ) : array {
		if (
			tec_ct_tickets_commerce_enabled()
			&& is_array( $dependencies['co-dependencies'] )
		) {
			unset( $dependencies['co-dependencies']['Tribe__Tickets_Plus__Main'] );
			unset( $dependencies['co-dependencies']['WooCommerce'] );
		}

		return $dependencies;
	}
}
