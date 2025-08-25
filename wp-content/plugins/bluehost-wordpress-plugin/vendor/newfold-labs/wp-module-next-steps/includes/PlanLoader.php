<?php
/**
 * Plan Loader for Next Steps Data.
 *
 * @package WPPluginBluehost
 */

namespace NewfoldLabs\WP\Module\NextSteps;

use function NewfoldLabs\WP\ModuleLoader\container;
use function NewfoldLabs\WP\Context\getContext;

/**
 * NewfoldLabs\WP\Module\NextSteps\PlanLoader
 *
 * Handles plan loading and solution changes for the Next Steps module.
 * All step data is now managed by PlanManager.
 */
class PlanLoader {

	/**
	 * Transient name for Newfold solutions data
	 */
	const SOLUTIONS_TRANSIENT = 'newfold_solutions';

	/**
	 * Option name for onboarding site info
	 */
	const ONBOARDING_SITE_INFO_OPTION = 'nfd_module_onboarding_site_info';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize default steps on init
		\add_action( 'init', array( __CLASS__, 'load_default_steps' ), 1 );

		// Hook into solution option changes for dynamic plan switching
		\add_action( 'update_option_' . self::ONBOARDING_SITE_INFO_OPTION, array( __CLASS__, 'on_sitetype_change' ), 10, 2 );

		// Hook into WooCommerce activation to potentially switch to store plan
		\add_action( 'activated_plugin', array( __CLASS__, 'on_woocommerce_activation' ), 10, 2 );
	}

	/**
	 * Load default steps using local site type determination.
	 * This method is called on init to ensure default steps are loaded.
	 */
	public static function load_default_steps() {
		$steps = get_option( StepsApi::OPTION, false );
		if ( false === $steps ) {
			// Use our local site type determination logic
			$plan_type = self::determine_site_type();
			$plan      = PlanManager::switch_plan( $plan_type );
			if ( $plan ) {
				StepsApi::set_data( $plan->to_array() );
			}
		}
	}

	/**
	 * Handle solution option changes to switch plans dynamically.
	 *
	 * @param mixed $old_value Old solution value
	 * @param array $new_value New solution value
	 */
	public static function on_sitetype_change( $old_value, $new_value ) {
		// Validate new value structure
		if ( ! is_array( $new_value ) || ! isset( $new_value['site_type'] ) ) {
			return;
		}

		// Get old site type safely
		$old_site_type = ( is_array( $old_value ) && isset( $old_value['site_type'] ) ) ? $old_value['site_type'] : '';
		$new_site_type = $new_value['site_type'];

		// Only switch plan if the solution actually changed
		if ( $old_site_type !== $new_site_type ) {
			// Check if the new site type is a valid plan type
			if ( array_key_exists( $new_site_type, PlanManager::PLAN_TYPES ) ) {
				$plan_type = PlanManager::PLAN_TYPES[ $new_site_type ];
				$plan      = PlanManager::switch_plan( $plan_type );
				if ( $plan ) {
					StepsApi::set_data( $plan->to_array() );
				}
			}
		}
	}

	/**
	 * Handle WooCommerce activation to potentially switch to store plan.
	 *
	 * @param string $plugin The plugin being activated.
	 * @param bool   $network_wide Whether the plugin is being activated network-wide.
	 */
	public static function on_woocommerce_activation( $plugin, $network_wide ) {
		// Only for WooCommerce activation
		if ( 'woocommerce/woocommerce.php' === $plugin ) {
			// Switch to store plan when WooCommerce is activated
			$plan = PlanManager::switch_plan( 'ecommerce' );
			if ( $plan ) {
				StepsApi::set_data( $plan->to_array() );
			}
		}
	}

	/**
	 * Intelligently detect the site type for existing sites without onboarding data
	 *
	 * @return string The detected plan type ('ecommerce', 'corporate', or 'blog')
	 */
	public static function detect_site_type() {
		// 1. Check for ecommerce indicators first (highest priority)
		if ( self::is_ecommerce_site() ) {
			return 'ecommerce';
		}

		// 2. Check for business/corporate indicators
		if ( self::is_corporate_site() ) {
			return 'corporate';
		}

		// 3. Default to blog/personal
		return 'blog';
	}

	/**
	 * Check if this appears to be an ecommerce site
	 *
	 * @return bool
	 */
	private static function is_ecommerce_site() {
		// Check for WooCommerce plugin
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if this appears to be a corporate/business site
	 *
	 * @return bool
	 */
	private static function is_corporate_site() {
		$business_indicators = 0;
		$business_threshold  = 2;

		// Check for multiple users (businesses often have multiple content creators)
		$user_count = count_users();
		if ( $user_count['total_users'] > 2 ) {
			++$business_indicators;
		}

		// Check for business-oriented pages
		$business_pages = array( 'about', 'about-us', 'services', 'contact', 'team', 'portfolio', 'testimonials' );
		foreach ( $business_pages as $page_slug ) {
			if ( get_page_by_path( $page_slug ) ) {
				++$business_indicators;
				break; // Only count this once
			}
		}

		// Check for business-oriented custom post types
		$business_post_types = array( 'portfolio', 'team', 'service', 'testimonial', 'project', 'case-study' );
		foreach ( $business_post_types as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				++$business_indicators;
				break; // Only count this once
			}
		}

		// Check for business-oriented plugins
		$business_plugins = array(
			'wordpress-seo-premium/wordpress-seo-premium.php',
			'wpforms-lite/wpforms.php',
		);

		$active_business_plugins = 0;
		foreach ( $business_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				++$active_business_plugins;
			}
		}

		if ( $active_business_plugins >= 2 ) {
			++$business_indicators;
		}

		// Check site title/description for business keywords
		$site_title        = get_bloginfo( 'name' );
		$site_description  = get_bloginfo( 'description' );
		$business_keywords = array( 'LLC', 'Inc', 'Corp', 'Company', 'Business', 'Agency', 'Studio', 'Solutions', 'Services', 'Consulting' );

		foreach ( $business_keywords as $keyword ) {
			if ( stripos( $site_title, $keyword ) !== false || stripos( $site_description, $keyword ) !== false ) {
				++$business_indicators;
				break; // Only count this once
			}
		}

		// Threshold: if we have 2 or more business indicators, consider it corporate
		return $business_indicators >= $business_threshold;
	}

	/**
	 * Determine the appropriate site type/plan based on multiple data sources
	 *
	 * Priority order:
	 * 1. nfd_module_onboarding_site_info option (from onboarding)
	 * 2. newfold_solutions transient (from solutions API)
	 * 3. Intelligent site detection (fallback)
	 *
	 * @return string The determined plan type (blog, corporate, ecommerce)
	 */
	public static function determine_site_type(): string {
		// 1. Check onboarding site info first (highest priority)
		$onboarding_info = get_option( self::ONBOARDING_SITE_INFO_OPTION, false );
		if ( is_array( $onboarding_info ) && isset( $onboarding_info['site_type'] ) ) {
			$site_type = $onboarding_info['site_type'];
			if ( array_key_exists( $site_type, PlanManager::PLAN_TYPES ) ) {
				return PlanManager::PLAN_TYPES[ $site_type ];
			}
		}

		// 2. Check solutions transient (second priority)
		$solutions_data = get_transient( self::SOLUTIONS_TRANSIENT );
		if ( is_array( $solutions_data ) && isset( $solutions_data['solution'] ) ) {
			$solution = $solutions_data['solution'];
			switch ( $solution ) {
				case 'WP_SOLUTION_COMMERCE':
					return 'ecommerce';
				case 'WP_SOLUTION_CREATOR':
					return 'blog';
				case 'WP_SOLUTION_SERVICE':
					return 'corporate';
			}
		}

		// 3. Fall back to intelligent detection
		return self::detect_site_type();
	}

	/**
	 * Load default plan based on site type detection
	 *
	 * @return \NewfoldLabs\WP\Module\NextSteps\DTOs\Plan
	 */
	public static function load_default_plan(): \NewfoldLabs\WP\Module\NextSteps\DTOs\Plan {
		$plan_type = self::determine_site_type();

		switch ( $plan_type ) {
			case 'ecommerce':
				$plan = PlanManager::get_ecommerce_plan();
				break;
			case 'corporate':
				$plan = PlanManager::get_corporate_plan();
				break;
			case 'blog':
			default:
				$plan = PlanManager::get_blog_plan();
				break;
		}

		// Save the loaded plan
		PlanManager::save_plan( $plan );

		return $plan;
	}
}
