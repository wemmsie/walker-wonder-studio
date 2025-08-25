<?php

namespace NewfoldLabs\WP\Module\Hosting\PlanInfo;

use NewfoldLabs\WP\Module\Data\HiiveWorker;

/**
 * Fetches the hosting plan name map via CF worker or static file fallback.
 */
class HostingPlanMap {

	/**
	 * Returns the mapped plan names array for the current locale.
	 *
	 * @return array Plan name map.
	 */
	public static function get() {
		$locale = get_locale();

		$data = self::fetch_from_worker( $locale );
		if ( ! empty( $data ) ) {
			return $data;
		}

		return self::fetch_from_static_file( $locale );
	}

	/**
	 * Fetch plan name map from the worker.
	 *
	 * @param string $locale The locale in kebab-case.
	 * @return array Retrieved plan name mapping or empty array.
	 */
	public static function fetch_from_worker( $locale = 'en_US' ) {
		$worker   = new HiiveWorker( 'hosting-plan-data' );
		$response = $worker->request(
			'GET',
			array(
				'headers' => array( 'Accept' => 'application/json' ),
				'body'    => array( 'locale' => $locale ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Fallback to static plan name map.
	 *
	 * @param string $locale The locale in kebab-case.
	 * @return array Plan mapping from local static file or empty array.
	 */
	public static function fetch_from_static_file( $locale = 'en_US' ) {
		$filename = realpath( __DIR__ . "/Data/Static/hosting-plan-data-{$locale}.json" );

		if ( ! file_exists( $filename ) && 'en_US' !== $locale ) {
			$filename = realpath( __DIR__ . '/Data/Static/hosting-plan-data-en_US.json' );
		}

		if ( ! file_exists( $filename ) ) {
			return array();
		}

		$contents = file_get_contents( $filename );
		$data     = json_decode( $contents, true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}
}
