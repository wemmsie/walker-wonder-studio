<?php

namespace NewfoldLabs\WP\Module\Hosting\HostingPanel\RestApi;

use NewfoldLabs\WP\Module\Hosting\HostingPanel\HostingPanel;

/**
 * Class RestApi
 *
 * Handles the registration of custom REST API routes.
 */
final class RestApi {

	/**
	 * HostingPanel instance.
	 *
	 * @var HostingPanel
	 */
	protected $hosting_panel;

	/**
	 * An array of controller class names that manage REST API routes.
	 *
	 * @var array $controllers
	 */
	protected $controllers = array(
		'NewfoldLabs\\WP\\Module\\Hosting\\HostingPanel\\RestApi\\Controllers\\HostingPanelController',
	);

	/**
	 * Constructor to initialize the custom REST API.
	 *
	 * @param HostingPanel $hosting_panel The HostingPanel instance.
	 */
	public function __construct( HostingPanel $hosting_panel ) {
		$this->hosting_panel = $hosting_panel;
		// Hook the 'rest_api_init' action to register custom routes
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the custom REST API routes.
	 */
	public function register_routes() {
		foreach ( $this->controllers as $controller ) {
			if ( class_exists( $controller ) ) {
				$instance = new $controller( $this->hosting_panel );
				$instance->register_routes();
			}
		}
	}
}
