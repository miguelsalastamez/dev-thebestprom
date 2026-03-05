<?php

abstract class Tribe__Events__Community__Tickets__Route__Abstract_Route {
	/**
	 * Router
	 * @var string
	 */
	public $router;

	/**
	 * Route slug
	 * @var string
	 */
	public $route_slug;

	/**
	 * Route suffix
	 * @var string
	 */
	public $route_suffix;

	/**
	 * Route query vars
	 * @var array
	 */
	public $route_query_vars = [];

	/**
	 * Page arguments
	 * @var array
	 */
	public $page_args = [];

	/**
	 * Page title
	 * @var string
	 */
	public $title;

	/**
	 * Page template name
	 * @var string
	 */
	public $template;

	/**
	 * Handles the rendering of the route
	 * @abstract
	 */
	abstract public function callback( $arg = null );

	/**
	 * constructor
	 */
	public function __construct( $router ) {
		$this->router = $router;
		$this->template = $this->get_template();

		$this->add();
	}

	/**
	 * Adds the route to the router
	 */
	public function add() {
		$this->router->add_route(
			$this->route_slug,
			[
				'path' => '^' . $this->path( $this->route_suffix ),
				'query_vars' => $this->route_query_vars,
				'page_callback' => [ $this, 'callback' ],
				'page_arguments' => $this->page_args,
				'access_callback' => true,
				'title' => $this->title,
				'template' => $this->template,
			]
		);
	}

	/**
	 * this code snippet taken from Tribe__Events__Community__Main::addRoutes
	 */
	protected function get_template() {
		$tec_template = tribe_get_option( 'tribeEventsTemplate' );

		switch ( $tec_template ) {
			case '' :
				$template_name = Tribe__Events__Community__Templates::getTemplateHierarchy( 'community/default-template' );
				break;
			case 'default' :
				$template_name = 'page.php';
				break;
			default :
				$template_name = $tec_template;
		}

		$template = apply_filters( 'tribe_community_tickets_template', $template_name );
		$template = apply_filters_deprecated(
			'tribe_ct_template',
			[ $template ],
			'4.6.3',
			'tribe_community_tickets_template',
			'The filter "tribe_ct_template" has been renamed to "tribe_community_tickets_template" to match plugin namespacing.'
		);

		return $template;
	}//end get_template

	/**
	 * Remove search related body classes from body_class() for report screens on FE.
	 *
	 * @since 5.0.0 Migrated to Community from Community Tickets.
	 *
	 * @param array $wp_classes List of body classes to show.
	 *
	 * @return array List of body classes to show.
	 */
	public function remove_body_classes( $wp_classes ) {
		// The classes we want to remove.
		$blacklist = [
			'search',
			'search-results',
			'search-no-results',
		];

		// Remove classes from array.
		$wp_classes = array_diff( $wp_classes, $blacklist );

		// Return modified body class array.
		return $wp_classes;
	}
}
