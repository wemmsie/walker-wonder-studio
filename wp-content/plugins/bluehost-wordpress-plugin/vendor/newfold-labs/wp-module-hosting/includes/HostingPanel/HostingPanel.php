<?php

namespace NewfoldLabs\WP\Module\Hosting\HostingPanel;

use NewfoldLabs\WP\Module\Hosting\CDNInfo\CDNInfo;
use WP_Error;
use NewfoldLabs\WP\Module\Hosting\Data\Constants;
use NewfoldLabs\WP\Module\Hosting\HostingPanel\RestApi\RestApi;
use NewfoldLabs\WP\Module\Hosting\MalwareCheck\MalwareCheck;
use NewfoldLabs\WP\Module\Hosting\ObjectCache\ObjectCache;
use NewfoldLabs\WP\Module\Hosting\PHPVersion\PHPVersion;
use NewfoldLabs\WP\Module\Hosting\Nameservers\Nameservers;
use NewfoldLabs\WP\Module\Hosting\Permissions;
use NewfoldLabs\WP\Module\Hosting\PlanInfo\PlanInfo;
use NewfoldLabs\WP\Module\Hosting\SSHInfo\SSHInfo;
use NewfoldLabs\WP\Module\Hosting\Services\I18nService;

/**
 * Class HostingPanel
 *
 * Manages hosting panel features dynamically.
 */
class HostingPanel {

	/**
	 * Dependency container instance.
	 *
	 * @var mixed
	 */
	protected $container;

	/**
	 * Transient name to cache the full hosting panel response.
	 *
	 * @var string
	 */
	public static $transient_key = 'nfd_hosting_panel_data';

	/**
	 * Transient key used to flag that the hosting panel cache needs to be refreshed.
	 *
	 * @var string
	 */
	public static $refresh_flag_key = 'nfd_hosting_panel_needs_refresh';

	/**
	* Slug used for the hosting module's admin page.
	*
	* @var string
	*/
	const PAGE_SLUG = 'nfd-hosting';

	/**
	 * List of feature class names.
	 *
	 * @var array
	 */
	protected $features = array(
		'object-cache'  => ObjectCache::class,
		'php-version'   => PHPVersion::class,
		'malware-check' => MalwareCheck::class,
		'nameservers'   => Nameservers::class,
		'ssh-info'      => SSHInfo::class,
		'cdn-info'      => CDNInfo::class,
		'plan-info'     => PlanInfo::class,
	);

	/**
	 * Holds instantiated feature objects.
	 *
	 * @var array
	 */
	protected $instances = array();

	/**
	 * HostingPanel constructor.
	 *
	 * @param mixed $container The dependency container instance.
	 */
	public function __construct( $container ) {
		$this->container = $container;

		new Constants( $container );

		$this->initialize_hooks();

		if ( Permissions::is_authorized_admin() || Permissions::rest_is_authorized_admin() ) {
			$this->initialize_features();
			$this->initialize_rest_api();
		}

		new I18nService( $container );
	}

	/**
	 * Initializes all registered features.
	 */
	protected function initialize_features() {
		foreach ( $this->features as $identifier => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->instances[ $identifier ] = new $class_name( $this->container );
			}
		}
	}

	/**
	 * Initializes the REST API routes.
	 */
	protected function initialize_rest_api() {
		new RestApi( $this );
	}

	/**
	 * Registers WordPress hooks used by the Hosting Panel module.
	 *
	 * @return void
	 */
	protected function initialize_hooks() {
		add_filter( 'nfd_plugin_subnav', array( $this, 'add_nfd_subnav' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'initialize_hosting_app' ) );
		add_action( 'wp_login', array( $this, 'handle_wp_login' ), 10, 2 );
		add_action( 'newfold_sso_success', array( $this, 'handle_sso_login' ), 10, 2 );
		add_action( 'update_option_permalink_structure', array( $this, 'flush_cache' ), 10, 2 );
	}

	/**
	 * Registers a new feature dynamically.
	 *
	 * @param string $identifier The unique slug identifier for the feature.
	 * @param string $class_name The class name responsible for managing the feature.
	 */
	public function register_feature( $identifier, $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			return new WP_Error(
				'invalid_class',
				__( 'Feature class does not exist.', 'wp-module-hosting' )
			);
		}

		$this->features[ $identifier ]  = $class_name;
		$this->instances[ $identifier ] = new $class_name( $this->container );
	}

	/**
	 * Retrieves hosting panel data dynamically from registered features.
	 *
	 * @return array Hosting panel data.
	 */
	public function get_data() {
		$needs_refresh = get_transient( self::$refresh_flag_key );
		$cached        = get_transient( self::$transient_key );

		// If refresh flag is set, ignore cache and rebuild
		if ( $needs_refresh || false === $cached ) {
			$data = array();

			foreach ( $this->instances as $identifier => $instance ) {
				if ( method_exists( $instance, 'get_data' ) ) {
					$data[ $identifier ] = $instance->get_data();
				}
			}

			$generated           = time();
			$data['__generated'] = $generated;
			$data['__meta']      = array(
				'generated'  => $generated,
				'from_cache' => false,
			);

			set_transient( self::$transient_key, $data, DAY_IN_SECONDS );
			delete_transient( self::$refresh_flag_key );

			return $data;
		}

		// Cache is valid and no refresh needed
		$cached['__meta'] = array(
			'generated'  => $cached['__generated'] ?? time(),
			'from_cache' => true,
		);

		return $cached;
	}

	/**
	 * Flushes the cached hosting panel transient.
	 *
	 * @return bool True if the transient was deleted, false otherwise.
	 */
	public static function flush_cache() {
		return delete_transient( self::$transient_key );
	}

	/**
	 * Performs an action on a registered feature dynamically.
	 *
	 * @param string $identifier The feature identifier.
	 * @param string $action     The public function to call.
	 * @param array  $data       Optional data to pass to the method.
	 *
	 * @return mixed|WP_Error The function result or WP_Error.
	 */
	public function perform_action( $identifier, $action, $data = array() ) {
		if ( ! isset( $this->instances[ $identifier ] ) ) {
			return new WP_Error(
				'invalid_identifier',
				__( 'Invalid feature identifier.', 'wp-module-hosting' )
			);
		}

		$instance = $this->instances[ $identifier ];

		// Check if the requested action is a valid public method in the class
		if ( method_exists( $instance, $action ) && is_callable( array( $instance, $action ) ) ) {
			if ( ! empty( $data ) ) {
				return call_user_func( array( $instance, $action ), $data );
			}

			return call_user_func( array( $instance, $action ) );
		}

		return new WP_Error(
			'invalid_action',
			__( 'Action not supported.', 'wp-module-hosting' )
		);
	}

	/**
	 * Adds the Hosting panel entry to the Newfold Brand Plugin sub-navigation.
	 *
	 * @param array $subnav The existing sub-navigation array.
	 * @return array Modified sub-navigation array with Hosting panel entry appended.
	 */
	public function add_nfd_subnav( $subnav ) {
		$hosting = array(
			'route'    => self::PAGE_SLUG,
			'title'    => __( 'Hosting', 'wp-module-hosting' ),
			'priority' => 20,
			'callback' => array( __CLASS__, 'render_hosting_app' ),
		);
		array_push( $subnav, $hosting );

		return $subnav;
	}

	/**
	 * Callback for the 'wp_login' action hook.
	 *
	 * @param string  $user_login The username of the user logging in.
	 * @param WP_User $user       The logged-in user object.
	 *
	 * @return void
	 */
	public function handle_wp_login( $user_login, $user ) {
		if ( $user instanceof \WP_User && user_can( $user, 'manage_options' ) ) {
			self::mark_cache_for_refresh();
		}
	}

	/**
	 * Callback for the 'newfold_sso_success' action hook.
	 *
	 * Marks the hosting panel cache for refresh if the SSO-authenticated user is an admin.
	 *
	 * @param WP_User $user     The logged-in user object.
	 *
	 * @return void
	 */
	public function handle_sso_login( $user ) {
		if ( $user instanceof \WP_User && user_can( $user, 'manage_options' ) ) {
			self::mark_cache_for_refresh();
		}
	}

	/**
	 * Marks the hosting panel cache to be refreshed on next get_data() call.
	 *
	 * @return void
	 */
	public static function mark_cache_for_refresh() {
		set_transient( self::$refresh_flag_key, true, DAY_IN_SECONDS );
	}

	/**
	 * Outputs the HTML container for the Hosting module's React application.
	 *
	 * @return void
	 */
	public static function render_hosting_app() {
		echo PHP_EOL;
		echo '<!-- NFD:HOSTING -->';
		echo PHP_EOL;
		echo '<div id="' . esc_attr( self::PAGE_SLUG ) . '" class="' . esc_attr( self::PAGE_SLUG ) . '-container"></div>';
		echo PHP_EOL;
		echo '<!-- /NFD:HOSTING -->';
		echo PHP_EOL;
	}

	/**
	 * Initializes the Hosting module by registering and enqueuing its assets.
	 *
	 * @return void
	 */
	public static function initialize_hosting_app() {
		self::register_hosting_assets();
	}

	/**
	 * Registers and enqueues the JavaScript and CSS assets for the Hosting module.
	 *
	 * @return void
	 */
	public static function register_hosting_assets() {
		$build_dir  = NFD_HOSTING_BUILD_DIR;
		$build_url  = NFD_HOSTING_BUILD_URL;
		$asset_file = $build_dir . '/hosting/hosting.min.asset.php';

		if ( is_readable( $asset_file ) ) {
			$asset = include_once $asset_file;

			wp_register_script(
				self::PAGE_SLUG,
				$build_url . '/hosting/hosting.min.js',
				$asset['dependencies'],
				$asset['version'],
				true
			);

			wp_register_style(
				self::PAGE_SLUG,
				$build_url . '/hosting/hosting.css',
				array(),
				$asset['version']
			);
			// Only enqueue on hosting page
			$screen = get_current_screen();
			if ( isset( $screen->id ) && false !== strpos( $screen->id, self::PAGE_SLUG ) ) {
				wp_enqueue_script( self::PAGE_SLUG );
				wp_enqueue_style( self::PAGE_SLUG );
			}
		}
	}
}
