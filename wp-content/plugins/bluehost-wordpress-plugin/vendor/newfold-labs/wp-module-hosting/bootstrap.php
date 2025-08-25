<?php

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\Module\Hosting\Hosting;

use function NewfoldLabs\WP\ModuleLoader\register;

if ( function_exists( 'add_action' ) ) {
	add_action(
		'plugins_loaded',
		function () {
			register(
				array(
					'name'     => 'wp-module-hosting',
					'label'    => __( 'Hosting', 'wp-module-hosting' ),
					'callback' => function ( Container $container ) {
						if ( ! defined( 'NFD_HOSTING_DIR' ) ) {
							define( 'NFD_HOSTING_DIR', __DIR__ );
						}
						new Hosting( $container );
					},
					'isActive' => true,
					'isHidden' => true,
				)
			);
		}
	);

}
