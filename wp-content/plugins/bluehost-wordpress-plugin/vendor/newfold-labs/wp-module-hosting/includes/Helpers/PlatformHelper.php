<?php

namespace NewfoldLabs\WP\Module\Hosting\Helpers;

/**
 * Helper class for platform-related utilities.
 */
class PlatformHelper {

	/**
	 * Checks if the platform is Atomic.
	 *
	 * @return bool True if the platform is 'atomic', otherwise false.
	 */
	public static function is_atomic() {
		// Use fully qualified function call
		$platform = \NewfoldLabs\WP\Context\getContext( 'platform' );
		return 'atomic' === $platform;
	}
}
