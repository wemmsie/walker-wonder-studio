<?php

namespace NewfoldLabs\WP\Module\MyProducts;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Do not allow multiple copies of the module to be active
if ( defined( 'NFD_DATA_MODULE_MY_PRODUCTS' ) ) {
	return;
}

define( 'NFD_DATA_MODULE_MY_PRODUCTS', '1.0.3' );

/**
 * @see Features::initFeatures()
 */
add_filter(
	'newfold/features/filter/register',
	function ( array $features ): array {
		return array_merge( $features, array( MyProductsFeature::class ) );
	}
);
