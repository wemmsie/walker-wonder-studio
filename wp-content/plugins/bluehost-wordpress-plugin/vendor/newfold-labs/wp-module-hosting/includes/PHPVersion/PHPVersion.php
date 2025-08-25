<?php

namespace NewfoldLabs\WP\Module\Hosting\PHPVersion;

/**
 * Handles PHP version-related functionality.
 */
class PHPVersion {

	/**
	 * Retrieves PHP version data.
	 *
	 * @return array PHP version details.
	 */
	public function get_data() {
		$current_version     = phpversion();
		$recommended_version = '8.3';

		$data = array(
			'current_version' => $current_version,
		);

		if ( version_compare( $current_version, $recommended_version, '<' ) ) {
			$data['recommended_version'] = $recommended_version;
		}

		return $data;
	}
}
