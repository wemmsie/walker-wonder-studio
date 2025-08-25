<?php

namespace NewfoldLabs\WP\Module\Hosting\Nameservers;

/**
 * Handles DNS name server retrieval.
 */
class Nameservers {

	/**
	 * Dependency container instance.
	 *
	 * @var mixed
	 */
	protected $container;

	/**
	 * Nameservers constructor.
	 *
	 * @param mixed $container The dependency container instance.
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}

	/**
	 * Retrieves site URL, runs a DNS check, and fetches name servers.
	 *
	 * @return array Name server data.
	 */
	public function get_data() {
		$site_url     = wp_parse_url( get_site_url(), PHP_URL_HOST );
		$name_servers = $this->get_name_servers( $site_url );

		return array(
			'records' => $name_servers,
		);
	}

	/**
	 * Retrieves the name servers for a given domain.
	 *
	 * @param string $domain The domain to check.
	 * @return array List of name servers.
	 */
	private function get_name_servers( $domain ) {
		$records      = dns_get_record( $domain, DNS_NS );
		$name_servers = array();

		if ( ! empty( $records ) ) {
			foreach ( $records as $record ) {
				$name_servers[] = $record['target'];
			}
		}

		return $name_servers;
	}
}
