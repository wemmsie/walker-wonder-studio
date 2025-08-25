<?php

namespace NewfoldLabs\WP\Module\Hosting\Data;

use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * Manages all the constants for the hosting module.
 */
class Constants {
	/**
	 * Constructor for the Constants class.
	 *
	 * @param Container $container The module container.
	 */
	public function __construct( $container ) {
		if ( ! defined( 'NFD_HOSTING_BUILD_DIR' ) ) {
			define( 'NFD_HOSTING_BUILD_DIR', dirname( __DIR__, 2 ) . '/build' );
		}

		if ( ! defined( 'NFD_HOSTING_BUILD_URL' ) ) {
			define( 'NFD_HOSTING_BUILD_URL', $container->plugin()->url . 'vendor/newfold-labs/wp-module-hosting/build' );
		}

		if ( ! defined( 'NFD_HOSTING_LANG_DIR' ) ) {
			define( 'NFD_HOSTING_LANG_DIR', dirname( $container->plugin()->file ) . '/vendor/newfold-labs/wp-module-hosting/languages' );
		}
	}
}
