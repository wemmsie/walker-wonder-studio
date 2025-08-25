<?php

namespace NewfoldLabs\WP\Module\Hosting\SSHInfo;

use NewfoldLabs\WP\Module\Hosting\Helpers\PlatformHelper;

/**
 * Handles SSH login information retrieval.
 */
class SSHInfo {

	/**
	 * Dependency container instance.
	 *
	 * @var mixed
	 */
	protected $container;

	/**
	 * SSHInfo constructor.
	 *
	 * @param mixed $container The dependency container instance.
	 */
	public function __construct( $container ) {
		$this->container = $container;
	}

	/**
	 * Retrieves SSH login info using the real server hostname and filesystem username.
	 *
	 * @return array SSH login data.
	 */
	public function get_data() {
		// Use the helper to check if the platform is 'atomic'
		if ( PlatformHelper::is_atomic() ) {
			return array(
				'ssh_info' => '',
			);
		}

		$ip       = $this->get_host_ip_from_hostname();
		$username = $this->get_server_username();
		$ssh_info = $username && $ip ? "{$username}@{$ip}" : '';

		return array(
			'ssh_info' => $ssh_info,
		);
	}

	/**
	 * Retrieves the IP address based on the server's hostname.
	 *
	 * @return string|null IP address or null if not found.
	 */
	private function get_host_ip_from_hostname() {
		$hostname = gethostname();
		$ip       = gethostbyname( $hostname );

		// Fallback in case resolution fails or returns the hostname itself
		if ( empty( $ip ) || $ip === $hostname ) {
			return null;
		}

		return $ip;
	}

	/**
	 * Retrieves the server username from the WordPress installation path.
	 *
	 * @return string|null Server username or null if not found.
	 */
	private function get_server_username() {
		$absolute_path = ABSPATH;
		$parts         = explode( '/', trim( $absolute_path, '/' ) );

		return isset( $parts[1] ) ? $parts[1] : null;
	}
}
