<?php

namespace NewfoldLabs\WP\Module\Hosting\CDNInfo;

use NewfoldLabs\WP\Module\Performance\Cache\Types\Cloudflare;

/**
 * Handles CDN information retrieval.
 */
class CDNInfo {

	/**
	 * Dependency container instance.
	 *
	 * @var mixed
	 */
	protected $container;

	/**
	 * Cloudflare object for CDN interaction.
	 *
	 * @var Cloudflare
	 */
	protected $cloudflare;

	/**
	 * CDNInfo constructor.
	 *
	 * @param mixed $container The dependency container instance.
	 */
	public function __construct( $container ) {
		$this->container  = $container;
		$this->cloudflare = new Cloudflare( $this->container );
	}

	/**
	 * Retrieves CDN-related data.
	 *
	 * @return array CDN data.
	 */
	public function get_data() {
		$cdn_enabled = $this->cloudflare->isCoudflareEnabled();

		return array(
			'cdn_enabled' => $cdn_enabled,
		);
	}

	/**
	 * Purges all Cloudflare cache.
	 *
	 * @return bool True if purge was successful, false otherwise.
	 */
	public function purge() {
		return $this->cloudflare->purge_all();
	}
}
