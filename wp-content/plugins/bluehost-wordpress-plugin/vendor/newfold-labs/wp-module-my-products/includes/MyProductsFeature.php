<?php

namespace NewfoldLabs\WP\Module\MyProducts;

use function NewfoldLabs\WP\ModuleLoader\container as getContainer;

/**
 * Child class for a feature
 *
 * Child classes should define a name property as the feature name for all API calls. This name will be used in the registry.
 * Child class naming convention is {FeatureName}Feature.
 */
class MyProductsFeature extends \NewfoldLabs\WP\Module\Features\Feature {
	/**
	 * The feature name.
	 *
	 * @var string
	 */
	protected $name = 'my-products';

	/**
	 * The feature value. Defaults to on.
	 *
	 * @var bool
	 */
	protected $value = true;

	/**
	 * Initialize my products feature.
	 */
	public function initialize() {

		// Register module
		add_action(
			'plugins_loaded',
			function () {
				new Products( getContainer() );
			}
		);
	}
}
