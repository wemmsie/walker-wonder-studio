<?php
/**
 * Register Admin page and features.
 *
 * @package WPPluginBluehost
 */

namespace Bluehost;

use NewfoldLabs\WP\Module\Data\SiteCapabilities;
use function NewfoldLabs\WP\Context\getContext;
use function NewfoldLabs\WP\Module\Features\isEnabled;
use function NewfoldLabs\WP\Module\LinkTracker\Functions\build_link as buildLink;

/**
 * \Bluehost\Admin
 */
final class Admin {

	/**
	 * Register functionality using WordPress Actions.
	 */
	public function __construct() {
		/* Add Page to WordPress Admin Menu. */
		\add_action( 'admin_menu', array( __CLASS__, 'page' ) );
		/* Load Page Scripts & Styles. */
		\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		/* Load i18 files */
		\add_action( 'init', array( __CLASS__, 'load_text_domain' ), 100 );
		/* Add Links to WordPress Plugins list item. */
		$plugin_basename = defined( 'BLUEHOST_PLUGIN_FILE' )
			? plugin_basename( constant( 'BLUEHOST_PLUGIN_FILE' ) )
			: 'bluehost-wordpress-plugin/bluehost-wordpress-plugin.php';
		\add_filter( "plugin_action_links_{$plugin_basename}", array( __CLASS__, 'actions' ) );
		/* Add inline style to hide subnav link */
		\add_action( 'admin_head', array( __CLASS__, 'admin_nav_style' ) );

		\add_filter( 'newfold-runtime', array( __CLASS__, 'add_to_runtime' ) );
		\add_filter( 'newfold_runtime', array( __CLASS__, 'add_to_runtime' ) );

		if ( isset( $_GET['page'] ) && strpos( filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW ), 'bluehost' ) >= 0 ) { // phpcs:ignore
			\add_action( 'admin_footer_text', array( __CLASS__, 'add_brand_to_admin_footer' ) );
			/* Disable admin notices on App pages */
			\add_action( 'admin_init', array( __CLASS__, 'disable_admin_notices' ) );
		}

		\add_action( 'update_option_WPLANG', array( __CLASS__, 'clear_transient_on_language_change' ), 10, 2 );
	}

	/**
	 * Add to runtime
	 *
	 * @param Array $sdk - The runtime array
	 *
	 * @return array
	 */
	public static function add_to_runtime( $sdk ) {
		include_once BLUEHOST_PLUGIN_DIR . '/inc/Data.php';

		return array_merge( $sdk, Data::runtime() );
	}

	/**
	 * Subpages to register with add_submenu_page().
	 *
	 * Order or array items determines menu order.
	 *
	 * @return array
	 */
	public static function plugin_subpages() {

		$home     = array(
			'route'    => 'bluehost#/home',
			'title'    => __( 'Home', 'wp-plugin-bluehost' ),
			'priority' => 1,
		);
		$settings = array(
			'route'    => 'bluehost#/settings',
			'title'    => __( 'Settings', 'wp-plugin-bluehost' ),
			'priority' => 60,
		);
		$help     = array(
			'route'    => 'bluehost#/help',
			'title'    => __( 'Help Resources', 'wp-plugin-bluehost' ),
			'priority' => 70,
		);

		// apply filter to add module subnav items
		$subnav = apply_filters(
			'nfd_plugin_subnav', // modules can filter this to add their own subnav items
			array(
				$settings,
				$home,
				$help,
			)
		);

		// sort subnav items by priority
		usort(
			$subnav,
			function ( $a, $b ) {
				if ( $a['priority'] === $b['priority'] ) {
					return 0;
				}
				return ( $a['priority'] < $b['priority'] ? -1 : 1 );
			}
		);

		// return subnav items sorted by priority
		return $subnav;
	}

	/**
	 * Add inline script to admin screens
	 *  - hide extra link in subnav
	 */
	public static function admin_nav_style() {
		echo '<style>';
		echo 'ul#adminmenu a.toplevel_page_bluehost.wp-has-current-submenu:after, ul#adminmenu>li#toplevel_page_bluehost.current>a.current:after { border-right-color: #fff !important; }';
		echo 'li#toplevel_page_bluehost > ul > li.wp-first-item { display: none !important; }';
		echo '#wp-toolbar #wp-admin-bar-bluehost-coming_soon .ab-item { padding: 0; }';
		echo 'body.folded #adminmenu .toplevel_page_bluehost div.wp-menu-image { width: 36px; height: 34px; }';
		echo '</style>';
	}

	/**
	 * Add WordPress Page to Appearance submenu.
	 *
	 * @return void
	 */
	public static function page() {
		$bluehost_icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9Im5vbmUiIHZpZXdCb3g9IjAgMCA0MCA0MCI+CiAgICA8cGF0aCBmaWxsPSIjYTdhYWFkIiBkPSJNNCA0aDguOTMzdjguOTIzSDRWNFptMTEuNTMgMGg4Ljk0djguOTIzaC04Ljk0VjRabTExLjUzMSAwSDM2djguOTIzaC04LjkzOVY0Wk00IDE1LjUzOGg4LjkzM3Y4LjkyNEg0di04LjkyNFptMTEuNTMgMGg4Ljk0djguOTI0aC04Ljk0di04LjkyNFptMTEuNTMxIDBIMzZ2OC45MjRoLTguOTM5di04LjkyNFpNNCAyNy4wNzdoOC45MzNWMzZINHYtOC45MjNabTExLjUzIDBoOC45NFYzNmgtOC45NHYtOC45MjNabTExLjUzMSAwSDM2VjM2aC04LjkzOXYtOC45MjNaIi8+Cjwvc3ZnPg==';

		\add_menu_page(
			__( 'Bluehost', 'wp-plugin-bluehost' ),
			__( 'Bluehost', 'wp-plugin-bluehost' ),
			'manage_options',
			'bluehost',
			array( __CLASS__, 'render' ),
			$bluehost_icon,
			0
		);

		// Add subpages to menu
		foreach ( self::plugin_subpages() as $subpage ) {
			\add_submenu_page(
				'bluehost',
				$subpage['title'],
				$subpage['title'],
				'manage_options',
				$subpage['route'],
				array_key_exists( 'callback', $subpage ) ? $subpage['callback'] : array( __CLASS__, 'render' )
			);
		}
	}

	/**
	 * Render DOM element for React to load onto.
	 *
	 * @return void
	 */
	public static function render() {
		global $wp_version;
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugin_data( BLUEHOST_PLUGIN_FILE );

		echo '<!-- Bluehost -->' . PHP_EOL;

		if ( version_compare( $wp_version, $plugin_data['RequiresWP'], '>=' ) ) {
			echo '<div id="wppbh-app" class="wppbh wppbh_app"></div>' . PHP_EOL;
			echo '<div id="nfd-portal-apps"><div id="nfd-next-steps-portal" /><div id="nfd-coming-soon-portal" /></div>' . PHP_EOL;
		} else {
			// fallback messaging for outdated WordPress
			$appWhenOutdated = BLUEHOST_PLUGIN_DIR . '/inc/AppWhenOutdated.php';
			if ( file_exists( $appWhenOutdated ) ) {
				include_once $appWhenOutdated;
			}
		}

		echo '<!-- /Bluehost -->' . PHP_EOL;
	}

	/**
	 * Load Page Scripts & Styles.
	 *
	 * @return void
	 */
	public static function assets() {
		$asset_file = BLUEHOST_BUILD_DIR . '/index.asset.php';

		if ( is_readable( $asset_file ) ) {
			$asset = include_once $asset_file;
		} else {
			return;
		}

		\wp_register_script(
			'nfd-portal-registry',
			BLUEHOST_BUILD_URL . '/portal-registry.js',
			array( 'wp-components', 'wp-element' ),
			$asset['version'],
			true
		);

		\wp_register_script(
			'bluehost-script',
			BLUEHOST_BUILD_URL . '/index.js',
			array_merge(
				$asset['dependencies'],
				array(
					'newfold-features',
					'nfd-runtime',
					'nfd-installer',
					'nfd-portal-registry',
				)
			),
			$asset['version'],
			true
		);

		\wp_set_script_translations(
			'bluehost-script',
			'wp-plugin-bluehost',
			BLUEHOST_PLUGIN_DIR . '/languages'
		);

		\wp_register_style(
			'bluehost-style',
			BLUEHOST_BUILD_URL . '/index.css',
			array( 'wp-components', 'nfd-installer' ),
			$asset['version']
		);

		$screen = get_current_screen();

		// Ensure we're on the Bluehost admin page before enqueuing scripts
		if ( isset( $screen->id ) && false !== strpos( $screen->id, 'bluehost' ) ) {
			// Enqueue the necessary Bluehost scripts and styles
			wp_enqueue_script( 'bluehost-script' );
			wp_enqueue_style( 'bluehost-style' );
		}

		// These assets are loaded in all wp-admin
		\wp_register_script( 'newfold-plugin', false, array(), BLUEHOST_PLUGIN_VERSION, true );
		\wp_localize_script(
			'newfold-plugin',
			'nfdplugin',
			array(
				'restApiUrl'   => \esc_url_raw( \get_home_url() . '/index.php?rest_route=' ),
				'restApiNonce' => \wp_create_nonce( 'wp_rest' ),
			)
		);
		\wp_enqueue_script( 'newfold-plugin' );
	}

	/**
	 * Load text domain for plugin
	 *
	 * @return void
	 */
	public static function load_text_domain() {

		\load_plugin_textdomain(
			'wp-plugin-bluehost',
			false,
			BLUEHOST_PLUGIN_DIR . '/languages'
		);

		\load_script_textdomain(
			'bluehost-script',
			'wp-plugin-bluehost',
			BLUEHOST_PLUGIN_DIR . '/languages'
		);
	}

	/**
	 * Add Links to WordPress Plugins list item for Bluehost.
	 *
	 * @param array $actions - array of action links for Plugin row item.
	 *
	 * @return array
	 */
	public static function actions( $actions ) {
		return array_merge(
			array(
				'overview' => '<a href="' . buildLink( admin_url( 'admin.php?page=bluehost#/home' ) ) . '">' . __( 'Home', 'wp-plugin-bluehost' ) . '</a>',
				'settings' => '<a href="' . buildLink( admin_url( 'admin.php?page=bluehost#/settings' ) ) . '">' . __( 'Settings', 'wp-plugin-bluehost' ) . '</a>',
			),
			$actions
		);
	}

	/**
	 * Disable admin notices on App pages
	 *
	 * @return void
	 */
	public static function disable_admin_notices() {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Filter WordPress Admin Footer Text "Thank you for creating with..."
	 *
	 * @param string $footer_text footer text
	 *
	 * @return string
	 */
	public static function add_brand_to_admin_footer( $footer_text ) {
		$wordpress_url = '<a href="' . buildLink( 'https://wordpress.org/', array( 'source' => 'bluehost_admin_footer' ) ) . '">WordPress</a>';
		$bluehost_url  = '<a href="' . buildLink( 'https://bluehost.com/about', array( 'source' => 'bluehost_admin_footer' ) ) . '">Bluehost</a>';

		return \sprintf( \__( 'Thank you for creating with %1$s and %2$s', 'wp-plugin-bluehost' ), $wordpress_url, $bluehost_url );
	}

	/**
	 * Clears a specific transient when the WordPress admin language setting is changed.
	 *
	 * This function hooks into the `update_option_WPLANG` action to detect when
	 * the site language is updated in the WordPress settings. If a change is detected,
	 * it deletes the specified transient to ensure fresh data is retrieved.
	 *
	 * @param string $old_value The previous language setting (e.g., 'en_US').
	 * @param string $new_value The new language setting (e.g., 'fr_FR').
	 */
	public static function clear_transient_on_language_change( $old_value, $new_value ) {
		// Check if the language has actually changed
		if ( $old_value !== $new_value ) {
			// Delete the transients to refresh cached data
			delete_transient( 'newfold_marketplace' );
			delete_transient( 'newfold_notifications' );
			delete_transient( 'newfold_solutions' );
		}
	}
} // END \Bluehost\Admin
