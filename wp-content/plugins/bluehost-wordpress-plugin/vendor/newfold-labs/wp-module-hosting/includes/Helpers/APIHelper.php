<?php

namespace NewfoldLabs\WP\Module\Hosting\Helpers;

/**
 * A utility class to generate REST API URLs.
 * This ensures consistency and avoids hardcoded API paths across the application.
 */
class APIHelper {

	/**
	 * Generate a REST API URL using the `rest_route` parameter.
	 *
	 * @param string $route The REST API route (e.g., '/newfold-installer/v1/install').
	 * @param array  $query_args Optional query parameters as key-value pairs.
	 *                           Example: array( 'hard_refresh' => 'true', '_locale' => 'user' ).
	 * @return string The fully constructed REST API URL.
	 */
	public static function get_rest_api_url( $route, $query_args = array() ) {
		$url = add_query_arg( 'rest_route', $route, get_rest_url() );

		// Append additional query parameters if provided
		if ( ! empty( $query_args ) ) {
			$url = add_query_arg( $query_args, $url );
		}

		return $url;
	}
}
