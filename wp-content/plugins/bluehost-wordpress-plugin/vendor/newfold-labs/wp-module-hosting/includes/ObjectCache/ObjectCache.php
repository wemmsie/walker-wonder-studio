<?php

namespace NewfoldLabs\WP\Module\Hosting\ObjectCache;

use WP_Error;

/**
 * Handles object caching functionality.
 */
class ObjectCache {

	/**
	 * Retrieves the status of the object cache.
	 *
	 * @return array Cache status details.
	 */
	public function get_status() {
		if ( ! file_exists( WP_CONTENT_DIR . '/object-cache.php' ) ) {
			return array(
				'status'  => 'not_setup',
				'message' => __( 'Object cache is not set up.', 'wp-module-hosting' ),
			);
		}

		$enabled = $this->is_enabled();

		if ( ! $enabled ) {
			return array(
				'status'  => 'disabled',
				'message' => __( 'Object cache is installed but disabled.', 'wp-module-hosting' ),
			);
		}

		return array(
			'status'  => 'enabled',
			'message' => __( 'Object cache is enabled and functioning.', 'wp-module-hosting' ),
		);
	}

	/**
	 * Checks if object caching is enabled.
	 *
	 * @return bool True if object cache is enabled, false otherwise.
	 */
	public function is_enabled() {
		return array_key_exists( 'object-cache.php', get_dropins() ) && wp_using_ext_object_cache();
	}


	/**
	 * Clears the object cache.
	 *
	 * @return bool|WP_Error True if cache was cleared, WP_Error on failure.
	 */
	public function clear() {
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
			return true;
		}

		return new WP_Error(
			'cache_clear_failed',
			__( 'Failed to clear object cache.', 'wp-module-hosting' )
		);
	}

	/**
	 * Disables object caching by renaming the drop-in file instead of deleting it.
	 *
	 * @return bool|WP_Error True if object caching was disabled, WP_Error on failure.
	 */
	public function disable() {
		global $wp_filesystem;
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Initialize the filesystem API
		if ( ! WP_Filesystem() ) {
			return new WP_Error(
				'filesystem_init_failed',
				__( 'Failed to initialize filesystem.', 'wp-module-hosting' )
			);
		}

		$dropin_path = WP_CONTENT_DIR . '/object-cache.php';
		$backup_path = WP_CONTENT_DIR . '/object-cache-disabled.php';

		// If object caching is enabled, move it instead of deleting
		if ( $wp_filesystem->exists( $dropin_path ) ) {
			if ( $wp_filesystem->move( $dropin_path, $backup_path, true ) ) {
				return true;
			}
			return new WP_Error(
				'cache_disable_failed',
				__( 'Failed to disable object cache.', 'wp-module-hosting' )
			);
		}

		return new WP_Error(
			'cache_already_disabled',
			__( 'Object cache is already disabled.', 'wp-module-hosting' )
		);
	}

	/**
	 * Enables object caching by restoring a backed-up drop-in file or using a default.
	 *
	 * @return bool|WP_Error True if object caching was enabled successfully, WP_Error otherwise.
	 */
	public function enable() {
		global $wp_filesystem;
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Initialize the filesystem API
		if ( ! WP_Filesystem() ) {
			return new WP_Error(
				'filesystem_init_failed',
				__( 'Failed to initialize filesystem.', 'wp-module-hosting' )
			);
		}

		$dropin_path   = WP_CONTENT_DIR . '/object-cache.php';
		$backup_path   = WP_CONTENT_DIR . '/object-cache-disabled.php';
		$default_cache = ABSPATH . 'wp-includes/object-cache.php';

		// If object cache is already enabled, return true
		if ( $wp_filesystem->exists( $dropin_path ) ) {
			return true;
		}

		// Restore from backup if available
		if ( $wp_filesystem->exists( $backup_path ) ) {
			if ( $wp_filesystem->move( $backup_path, $dropin_path, true ) ) {
				return true;
			}
			return new WP_Error(
				'cache_restore_failed',
				__( 'Failed to restore object cache.', 'wp-module-hosting' )
			);
		}

		// Copy the default WP object cache file as a fallback
		if ( $wp_filesystem->exists( $default_cache ) ) {
			if ( $wp_filesystem->copy( $default_cache, $dropin_path, true ) ) {
				return true;
			}
			return new WP_Error(
				'cache_install_failed',
				__( 'Failed to install object cache.', 'wp-module-hosting' )
			);
		}

		return new WP_Error(
			'cache_enable_failed',
			__( 'Could not enable object caching.', 'wp-module-hosting' )
		);
	}

	/**
	 * Returns object cache data.
	 *
	 * @return array Object cache data.
	 */
	public function get_data() {
		return $this->get_status();
	}
}
