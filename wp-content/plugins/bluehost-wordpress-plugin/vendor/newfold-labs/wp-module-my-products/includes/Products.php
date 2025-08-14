<?php

namespace NewfoldLabs\WP\Module\MyProducts;

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\Module\Data\HiiveConnection;

/**
 * Class for handling the initialization of the My Products module.
 */
class Products {

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container The plugin container.
	 */
	public function __construct( Container $container ) {

		$this->container = $container;

		// Module functionality goes here
		add_action( 'rest_api_init', array( $this, 'init_product_apis' ) );
	}

	/**
	 * Initialize the Product API Controller.
	 */
	public function init_product_apis(): void {
		// Create a HiiveConnection
		$hiive = new HiiveConnection();

		$products_api = new ProductsApi( $hiive );
		$products_api->register_routes();
	}
}
