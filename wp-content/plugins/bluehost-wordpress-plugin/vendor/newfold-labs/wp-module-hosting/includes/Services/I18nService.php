<?php

namespace NewfoldLabs\WP\Module\Hosting\Services;

/**
 * Class for handling internationalization.
 */
class I18nService {

	/**
	* Slug used for the hosting module's admin page.
	*
	* @var string
	*/
	const PAGE_SLUG = 'nfd-hosting';

	/**
	 * Init the i18n service
	 *
	 * @param Container $container the container
	 */
	public function __construct( $container ) {
		add_action( 'init', array( $this, 'add_php_i18n' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js_i18n' ), 100 );
		add_filter(
			'load_script_translation_file',
			array( $this, 'load_script_translation_file' ),
			10,
			3
		);
	}

	/**
	 * Load module text domain
	 *
	 * @return void
	 */
	public function add_php_i18n() {
		load_plugin_textdomain(
			'wp-module-hosting',
			false,
			NFD_HOSTING_LANG_DIR
		);
		// Load the PHP translations from .l10n.php files in the languages dir.
		load_textdomain(
			'wp-module-hosting',
			NFD_HOSTING_LANG_DIR . '/wp-module-hosting-' . get_locale() . '.l10n.php'
		);
	}

	/**
	 * Enqueue js/script for translations of the hosting app
	 */
	public function add_js_i18n() {
		wp_set_script_translations(
			self::PAGE_SLUG,
			'wp-module-hosting',
			NFD_HOSTING_LANG_DIR
		);
		load_script_textdomain(
			self::PAGE_SLUG,
			'wp-module-hosting',
			NFD_HOSTING_LANG_DIR
		);
	}

	/**
	 * Filters the file path for the JS translation JSON.
	 *
	 * If the script handle matches the module's handle, builds a custom path using
	 * the languages directory, current locale, text domain, and a hash of the script.
	 *
	 * @param string $file   Default translation file path.
	 * @param string $handle Script handle.
	 * @param string $domain Text domain.
	 * @return string Modified file path for the translation JSON.
	 */
	public function load_script_translation_file( $file, $handle, $domain ) {
		if ( self::PAGE_SLUG === $handle ) {
			$file_base = $domain . '-' . determine_locale();
			// Build the file path using the languages directory and the hash of the script.
			$file = NFD_HOSTING_LANG_DIR . '/' . $file_base . '-' . md5( 'build/hosting/hosting.js' ) . '.json';
		}
		return $file;
	}
}
