<?php

namespace NewfoldLabs\WP\Module\Hosting\PlanInfo;

use NewfoldLabs\WP\Module\Hosting\Helpers\HiiveHelper;
use NewfoldLabs\WP\Module\Hosting\Helpers\PlatformHelper;

/**
 * Handles Plan information retrieval from Hiive and maps it to display-friendly plan names.
 */
class PlanInfo {
	/**
	 * Dependency container instance.
	 *
	 * @var mixed
	 */
	protected $container;

	/**
	 * PlanInfo constructor.
	 *
	 * @param mixed $container The dependency container instance.
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}

	/**
	 * Retrieves and enhances the customer's hosting plan info.
	 *
	 * @return array
	 */
	public function get_data() {
		$hiive    = new HiiveHelper( '/sites/v1/customer', array(), 'GET' );
		$response = $hiive->send_request();

		if ( is_wp_error( $response ) ) {
			return array(
				'is_atomic' => PlatformHelper::is_atomic(),
			);
		}

		$data = json_decode( $response, true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return array(
				'is_atomic' => PlatformHelper::is_atomic(),
			);
		}

		$plan_type    = $data['plan_type'] ?? '';
		$plan_subtype = $data['plan_subtype'] ?? '';
		$plan_name    = null;

		if ( ! empty( $plan_type ) && ! empty( $plan_subtype ) ) {
			$map       = HostingPlanMap::get();
			$plan_name = $map[ $plan_subtype ] ?? null;
		}

		return array_merge(
			$data,
			array(
				'plan_name' => $plan_name,
				'is_atomic' => PlatformHelper::is_atomic(),
			)
		);
	}
}
