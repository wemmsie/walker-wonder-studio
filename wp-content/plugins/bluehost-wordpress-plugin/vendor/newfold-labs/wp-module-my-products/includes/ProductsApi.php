<?php

namespace NewFoldLabs\WP\Module\MyProducts;

use NewfoldLabs\WP\Module\Data\HiiveConnection;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class ProductsApi
 */
class ProductsApi {

	/**
	 * Transient name where user products data is stored.
	 */
	const TRANSIENT = 'newfold_my_products';

	/**
	 * Hiive API endpoint for fetching products.
	 */
	const HIIVE_API_PRODUCTS_ENDPOINT = 'sites/v1/customer/products';

	/**
	 * Instance of the HiiveConnection class.
	 *
	 * @var HiiveConnection
	 */
	private $hiive;

	/**
	 * ProductsApi constructor.
	 *
	 * @param HiiveConnection $hiive           Instance of the HiiveConnection class.
	 */
	public function __construct( HiiveConnection $hiive ) {
		$this->hiive     = $hiive;
		$this->namespace = 'newfold-my-products/v1';
		$this->rest_base = '/products';
	}

	/**
	 * Register products routes.
	 */
	public function register_routes() {

		// Add route for fetching user products
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'products_callback' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Set the transient where user products is stored (6 Hours).
	 *
	 * @param array     $data array of products.
	 * @param float|int $expiration    Transient expiration.
	 */
	public function setTransient( $data, $expiration = 21600 ) {
		set_transient( self::TRANSIENT, $data, $expiration );
	}

	/**
	 * Get products data
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function products_callback() {

		$products = get_transient( self::TRANSIENT );

		if ( false === $products ) {

			$products       = array();
			$args           = array(
				'method' => 'GET',
			);
			$hiive_response = $this->hiive->hiive_request( self::HIIVE_API_PRODUCTS_ENDPOINT, array(), $args );

			if ( is_wp_error( $hiive_response ) ) {
				return new WP_REST_Response( $hiive_response->get_error_message(), 500 );
			}

			$status_code = wp_remote_retrieve_response_code( $hiive_response );

			if ( 200 !== $status_code ) {
				return new WP_REST_Response( wp_remote_retrieve_response_message( $hiive_response ), $status_code );
			}

			$payload = json_decode( wp_remote_retrieve_body( $hiive_response ), true );
			if ( $payload && is_array( $payload ) ) {
				$products = $payload;

				$this->setTransient( $products );
			}

		}

		return new WP_REST_Response( $products, 200 );
	}
}
