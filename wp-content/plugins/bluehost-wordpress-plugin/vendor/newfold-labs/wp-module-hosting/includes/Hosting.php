<?php

namespace NewfoldLabs\WP\Module\Hosting;

use NewfoldLabs\WP\Module\Hosting\HostingPanel\HostingPanel;
use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * Manages all the functionalities for the module.
 */
class Hosting {
	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Constructor for the Hosting class.
	 *
	 * @param Container $container The module container.
	 */
	public function __construct( Container $container ) {
		// We're trying to avoid adding more stuff to this.
		$this->container = $container;

		new HostingPanel( $container );
	}
}
