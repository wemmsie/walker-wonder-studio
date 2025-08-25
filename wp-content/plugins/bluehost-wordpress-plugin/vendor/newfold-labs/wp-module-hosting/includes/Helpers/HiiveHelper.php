<?php

namespace NewfoldLabs\WP\Module\Hosting\Helpers;

use NewfoldLabs\WP\Module\Data\HiiveConnection;

/**
 * Helper class for interacting with Hiive APIs.
 */
class HiiveHelper {
	/**
	 * Base URL for Hiive APIs.
	 *
	 * @var string
	 */
	private $api_base_url;

	/**
	 * API endpoint.
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * Request body.
	 *
	 * @var array
	 */
	private $body;

	/**
	 * HTTP method (GET, POST, etc).
	 *
	 * @var string
	 */
	private $method;

	/**
	 * Constructor.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $body     Request body.
	 * @param string $method   HTTP method.
	 */
	public function __construct( $endpoint, $body = array(), $method = 'POST' ) {
		if ( ! defined( 'NFD_HIIVE_URL' ) ) {
			define( 'NFD_HIIVE_URL', 'https://hiive.cloud/api' );
		}

		$this->api_base_url = constant( 'NFD_HIIVE_URL' );
		$this->endpoint     = $endpoint;
		$this->body         = $body;
		$this->method       = strtoupper( $method );
	}

	/**
	 * Sends the request to Hiive.
	 *
	 * @return mixed|string|\WP_Error JSON-decoded data or WP_Error.
	 */
	public function send_request() {
		if ( ! HiiveConnection::is_connected() ) {
			return new \WP_Error(
				'nfd_hiive_error',
				__( 'Failed to connect to Hiive API.', 'wp-module-hosting' )
			);
		}

		$url = $this->api_base_url . $this->endpoint;

		$args = array(
			'method'  => $this->method,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . HiiveConnection::get_auth_token(),
			),
			'timeout' => 30,
		);

		if ( in_array( $this->method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $this->body );
		}

		if ( in_array( $this->method, array( 'GET', 'DELETE' ), true ) && ! empty( $this->body ) ) {
			$url = add_query_arg( $this->body, $url );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'nfd_hiive_error', "Hiive API returned HTTP {$code}" );
		}

		return wp_remote_retrieve_body( $response );
	}
}
