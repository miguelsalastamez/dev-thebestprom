<?php

/**
 * Class Tribe__Events__Community__Tickets__Plugin_Register
 *
 * @since 4.6
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
		'parent-dependencies' => [
			'Tribe__Events__Main'  => '6.1.2-dev',
			'Tribe__Tickets__Main' => '5.9.1-dev',
		],
		'co-dependencies'     => [
			'Tribe__Tickets_Plus__Main'      => '5.7.1-dev',
			'Tribe__Events__Community__Main' => '4.10.8-dev',
		],
	];

	/**
	 * Constructor method.
	 *
	 * @since 4.8.4 - Added the filter `remove_et_plus_as_dependency_if_tc_enabled`.
	 * @since 4.6
	 */
	public function __construct() {
		$this->base_dir = EVENTS_COMMUNITY_TICKETS_FILE;
		$this->version  = Tribe__Events__Community__Tickets__Main::VERSION;

		add_filter( 'tribe_register_Tribe__Events__Community__Tickets__Main_plugin_dependencies', [ $this, 'add_woo_as_dependency_if_able_via_common' ] );
		add_filter( 'tribe_register_Tribe__Events__Community__Tickets__Main_plugin_dependencies', [ $this, 'remove_dependency_if_tc_enabled' ] );

		$this->inform_dependency_manager_of_woocommerce();
		$this->register_plugin();
	}

	/**
	 * Allow Common's Dependency manager/notices to handle requiring WooCommerce by informing
	 * it of which version is active, if active at all.
	 *
	 * @since 4.7.1
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
	 * @since 4.7.4
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
	 * @since 4.8.4
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
