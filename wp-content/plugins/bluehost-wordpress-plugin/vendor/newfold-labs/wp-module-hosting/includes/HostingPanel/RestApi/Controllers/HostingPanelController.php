<?php

namespace NewfoldLabs\WP\Module\Hosting\HostingPanel\RestApi\Controllers;

use NewfoldLabs\WP\Module\Hosting\Permissions;
use NewfoldLabs\WP\Module\Hosting\HostingPanel\HostingPanel;

/**
 * Class HostingPanelController
 *
 * Handles the Hosting REST API integration.
 */
class HostingPanelController {

	/**
	 * The namespace for the REST API endpoint.
	 *
	 * @var string
	 */
	protected $namespace = 'newfold-hosting/v1';

	/**
	 * The base for the REST API endpoint.
	 *
	 * @var string
	 */
	protected $rest_base = 'panel';

	/**
	 * HostingPanel instance.
	 *
	 * @var HostingPanel
	 */
	protected $hosting_panel;

	/**
	 * HostingPanelController constructor.
	 *
	 * @param HostingPanel $hosting_panel The HostingPanel instance.
	 */
	public function __construct( HostingPanel $hosting_panel ) {
		$this->hosting_panel = $hosting_panel;
	}

	/**
	 * Registers the routes for the Hosting API.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_hosting_info' ),
				'permission_callback' => array( Permissions::class, 'rest_is_authorized_admin' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_hosting_settings' ),
				'permission_callback' => array( Permissions::class, 'rest_is_authorized_admin' ),
				'args'                => array(
					'identifier' => array(
						'required' => true,
						'type'     => 'string',
					),
					'action'     => array(
						'required' => true,
						'type'     => 'string',
					),
					'data'       => array(
						'required' => false,
						'type'     => 'object',
						'default'  => array(),
					),
				),
			)
		);
	}

	/**
	 * Fetches hosting data dynamically.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function get_hosting_info( \WP_REST_Request $request ) {
		$flush = $request->get_header( 'X-NFD-Flush-Cache' );
		if ( $flush && filter_var( $flush, FILTER_VALIDATE_BOOLEAN ) ) {
			$this->hosting_panel->flush_cache();
		}
		$data = $this->hosting_panel->get_data();

		if ( empty( $data ) ) {
			return new \WP_REST_Response(
				array( 'error' => __( 'Failed to retrieve hosting info', 'wp-module-hosting' ) ),
				500
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}


	/**
	 * Updates hosting settings dynamically based on identifier and action.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_hosting_settings( \WP_REST_Request $request ) {
		$identifier = sanitize_text_field( $request->get_param( 'identifier' ) );
		$action     = sanitize_text_field( $request->get_param( 'action' ) );
		$data       = $request->get_param( 'data' );
		$flush      = $request->get_header( 'X-NFD-Flush-Cache' );

		if ( $flush && filter_var( $flush, FILTER_VALIDATE_BOOLEAN ) ) {
			$this->hosting_panel->flush_cache();
		}

		$result = $this->hosting_panel->perform_action( $identifier, $action, $data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response(
			array( 'message' => __( 'Action executed successfully', 'wp-module-hosting' ) ),
			200
		);
	}
}
