<?php

namespace NewfoldLabs\WP\Module\NextSteps;

use NewfoldLabs\WP\Module\NextSteps\DTOs\Plan;
use NewfoldLabs\WP\Module\NextSteps\DTOs\Track;
use NewfoldLabs\WP\Module\NextSteps\DTOs\Section;
use NewfoldLabs\WP\Module\NextSteps\DTOs\Task;

/**
 * Plan Manager
 *
 * Handles plan loading, switching, and management based on nfd_solution option
 */
class PlanManager {

	/**
	 * Option name where the current plan is stored
	 */
	const OPTION = 'nfd_next_steps';

	/**
	 * Current version of plan data structure
	 * Increment this when plan data changes to trigger merges
	 */
	const PLAN_DATA_VERSION = NFD_NEXTSTEPS_MODULE_VERSION;

	/**
	 * Available plan types, this maps the site_type from onboarding module to internal plan types
	 *
	 * Maps nfd_module_onboarding_site_info['site_type'] values to internal plan types:
	 * - 'personal' (onboarding) -> 'blog' (internal plan)
	 * - 'business' (onboarding) -> 'corporate' (internal plan)
	 * - 'ecommerce' (onboarding) -> 'ecommerce' (internal plan)
	 */
	const PLAN_TYPES = array(
		'personal'  => 'blog',
		'business'  => 'corporate',
		'ecommerce' => 'ecommerce',
	);

	/**
	 * Get the current plan
	 *
	 * @return Plan|null
	 */
	public static function get_current_plan(): ?Plan {
		$plan_data = get_option( self::OPTION, array() );

		if ( empty( $plan_data ) ) {
			// Load default plan based on solution
			return PlanLoader::load_default_plan();
		}

		// Check if we need to merge with new plan data
		$saved_version   = $plan_data['version'] ?? '0.0.0';
		$current_version = self::PLAN_DATA_VERSION;

		if ( version_compare( $saved_version, $current_version, '<' ) ) {
			// Version is outdated, need to merge with latest plan data

			// First determine what plan type this is based on saved data
			$plan_id  = $plan_data['id'] ?? '';
			$new_plan = null;

			// Load the appropriate new plan based on the saved plan ID
			switch ( $plan_id ) {
				case 'blog':
					$new_plan = self::get_blog_plan();
					break;
				case 'corporate':
					$new_plan = self::get_corporate_plan();
					break;
				case 'ecommerce':
					$new_plan = self::get_ecommerce_plan();
					break;
				default:
					// If we can't determine the plan type, fall back to loading default
					return PlanLoader::load_default_plan();
			}

			// Merge the saved data with the new plan
			$merged_plan = self::merge_plan_data( $plan_data, $new_plan );

			// Save the merged plan with updated version
			self::save_plan( $merged_plan );

			return $merged_plan;
		}

		return Plan::from_array( $plan_data );
	}

	/**
	 * Save the current plan
	 *
	 * @param Plan $plan Plan to save
	 * @return bool
	 */
	public static function save_plan( Plan $plan ): bool {
		// Add version information to the saved data
		$plan_data            = $plan->to_array();
		$plan_data['version'] = self::PLAN_DATA_VERSION;
		return update_option( self::OPTION, $plan_data );
	}

	/**
	 * Merge existing saved plan data with new plan data from code
	 * Updates titles, descriptions, hrefs, priorities while preserving IDs and status
	 *
	 * @param array $saved_data Existing saved plan data
	 * @param Plan  $new_plan   New plan data from code
	 * @return Plan Merged plan
	 */
	public static function merge_plan_data( array $saved_data, Plan $new_plan ): Plan {
		// Create a map of existing tasks by ID for quick lookup
		$existing_tasks = array();
		if ( isset( $saved_data['tracks'] ) && is_array( $saved_data['tracks'] ) ) {
			foreach ( $saved_data['tracks'] as $track ) {
				if ( isset( $track['sections'] ) && is_array( $track['sections'] ) ) {
					foreach ( $track['sections'] as $section ) {
						if ( isset( $section['tasks'] ) && is_array( $section['tasks'] ) ) {
							foreach ( $section['tasks'] as $task ) {
								if ( isset( $task['id'] ) ) {
									$existing_tasks[ $task['id'] ] = $task;
								}
							}
						}
					}
				}
			}
		}

		// Create the merged plan by updating new plan with preserved status
		$merged_tracks = array();
		foreach ( $new_plan->get_tracks() as $track ) {
			$merged_sections = array();
			foreach ( $track->get_sections() as $section ) {
				$merged_tasks = array();
				foreach ( $section->get_tasks() as $task ) {
					$task_data = $task->to_array();

					// If this task exists in saved data, preserve its status and any custom data
					if ( isset( $existing_tasks[ $task->get_id() ] ) ) {
						$existing_task = $existing_tasks[ $task->get_id() ];

						// Preserve status (this is the key user state we want to keep)
						if ( isset( $existing_task['status'] ) ) {
							$task_data['status'] = $existing_task['status'];
						}

						// Preserve any custom completion date if it exists
						if ( isset( $existing_task['completed_at'] ) ) {
							$task_data['completed_at'] = $existing_task['completed_at'];
						}

						// Preserve any custom dismissal date if it exists
						if ( isset( $existing_task['dismissed_at'] ) ) {
							$task_data['dismissed_at'] = $existing_task['dismissed_at'];
						}

						// Preserve any other custom metadata that might have been added
						foreach ( $existing_task as $key => $value ) {
							if ( ! in_array( $key, array( 'id', 'title', 'description', 'href', 'priority', 'source' ), true ) ) {
								$task_data[ $key ] = $value;
							}
						}
					}
					$merged_tasks[] = new Task(
						$task_data['id'],
						$task_data['title'],
						$task_data['description'] ?? '',
						$task_data['href'] ?? '',
						$task_data['status'] ?? 'new',
						$task_data['priority'] ?? 1,
						$task_data['source'] ?? 'wp-module-next-steps',
						$task_data
					);
				}
				$merged_sections[] = new Section(
					$section->get_id(),
					$section->get_label(),
					$section->get_description(),
					$merged_tasks
				);
			}
			$merged_tracks[] = new Track(
				$track->get_id(),
				$track->get_label(),
				$track->get_description(),
				$merged_sections
			);
		}
		return new Plan(
			$new_plan->get_id(),
			$new_plan->get_label(),
			$new_plan->get_description(),
			$merged_tracks
		);
	}

	/**
	 * Switch to a different plan type
	 *
	 * @param string $plan_type Plan type to switch to
	 * @return Plan|false
	 */
	public static function switch_plan( string $plan_type ) {
		if ( ! in_array( $plan_type, array_values( self::PLAN_TYPES ), true ) && ! in_array( $plan_type, array_keys( self::PLAN_TYPES ), true ) ) {
			return false;
		}

		// If we received an onboarding site_type, convert it to internal plan type
		if ( array_key_exists( $plan_type, self::PLAN_TYPES ) ) {
			$plan_type = self::PLAN_TYPES[ $plan_type ];
		}

		// Clear current plan to force reload
		// delete_option( self::OPTION );

		// Load the appropriate plan directly
		switch ( $plan_type ) {
			case 'blog':
				$plan = self::get_blog_plan();
				break;
			case 'corporate':
				$plan = self::get_corporate_plan();
				break;
			case 'ecommerce':
			default:
				$plan = self::get_ecommerce_plan();
				break;
		}

		// Save the loaded plan
		self::save_plan( $plan );

		return $plan;
	}

	/**
	 * Get ecommerce plan
	 *
	 * @return Plan
	 */
	public static function get_ecommerce_plan(): Plan {
		return new Plan(
			array(
				'id'          => 'store_setup',
				'label'       => __( 'Store Setup', 'wp-module-next-steps' ),
				'description' => __( 'Complete your ecommerce store setup with these essential steps:', 'wp-module-next-steps' ),
				'tracks'      => array(
					array(
						'id'       => 'store_build_track',
						'label'    => __( 'Build', 'wp-module-next-steps' ),
						'open'     => true,
						'sections' => array(
							array(
								'id'    => 'basic_store_setup',
								'label' => __( 'Basic Store Setup', 'wp-module-next-steps' ),
								'open'  => true,
								'tasks' => array(
									array(
										'id'              => 'store_quick_setup',
										'title'           => __( 'Quick Setup', 'wp-module-next-steps' ),
										'href'            => '{siteUrl}/wp-admin/admin.php?page=wc-settings&tab=general',
										'status'          => 'new',
										'priority'        => 1,
										'source'          => 'wp-module-next-steps',
										'data_attributes' => array(
											'data-test-id' => 'store_quick_setup',
											'data-nfd-id'  => 'store_quick_start',
										),
									),
								),
							),
							array(
								'id'    => 'customize_store',
								'label' => __( 'Customize Your Store', 'wp-module-next-steps' ),
								'open'  => true,
								'tasks' => array(
									array(
										'id'       => 'store_upload_logo',
										'title'    => __( 'Upload Logo', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=all-parts',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_choose_colors_fonts',
										'title'    => __( 'Choose Colors and Fonts', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fstyles',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_customize_header',
										'title'    => __( 'Customize Header', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=header',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_customize_footer',
										'title'    => __( 'Customize Footer', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=footer',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_customize_homepage',
										'title'    => __( 'Customize Homepage', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Ftemplate',
										'status'   => 'new',
										'priority' => 5,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'setup_products',
								'label' => __( 'Set Up Shopping Experience', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_add_product',
										'title'    => __( 'Add a Product', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?post_type=product',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_customize_shop_page',
										'title'    => __( 'Customize the Shop Page', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post.php?post=',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_customize_cart_page',
										'title'    => __( 'Customize the Cart Page', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post.php?post=',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_customize_checkout_flow',
										'title'    => __( 'Customize the Checkout Flow', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post.php?post=',
										'status'   => 'new',
										'priority' => 5,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'setup_payments_shipping',
								'label' => __( 'Set Up Payments and Shipping', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_setup_payments',
										'title'    => __( 'Set Up Payments', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wc-settings&tab=checkout',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_setup_shipping',
										'title'    => __( 'Set Up Shipping', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wc-settings&tab=shipping',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_setup_taxes',
										'title'    => __( 'Set Up Taxes', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wc-settings&tab=tax',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'setup_legal_pages',
								'label' => __( 'Set Up Legal Pages', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_privacy_policy',
										'title'    => __( 'Privacy Policy', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/options-privacy.php',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_terms_conditions',
										'title'    => __( 'Terms & Conditions', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?wb-library=patterns&wb-category=text',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_return_refund_policy',
										'title'    => __( 'Return and Refund Policy', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?wb-library=patterns&wb-category=text',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
						),
					),
					array(
						'id'       => 'store_brand_track',
						'label'    => __( 'Brand', 'wp-module-next-steps' ),
						'sections' => array(
							array(
								'id'    => 'first_marketing_steps',
								'label' => __( 'First Marketing Steps', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_configure_welcome_popup',
										'title'    => __( 'Configure Welcome Discount Popup', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/improve-conversion-rate-website-pop-ups/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_create_gift_card',
										'title'    => __( 'Create a Gift Card to Sell in Your Shop', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=solutions&category=all',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),

									/*
									Hide Email Templates for now
									array(
										'id'       => 'store_enable_abandoned_cart',
										'title'    => __( 'Enable Abandoned Cart Emails', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/edit.php?post_type=bh-email-template',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_customize_emails',
										'title'    => __( 'Customize Your Store Emails', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/edit.php?post_type=bh-email-template',
										'status'   => 'new',
										'priority' => 5,
										'source'   => 'wp-module-next-steps',
									),
									*/
								),
							),
							array(
								'id'    => 'social_media_engagement',
								'label' => __( 'Launch and Promote - Social Media Setup & Engagement', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_connect_facebook',
										'title'    => __( 'Connect Facebook Store', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wc-admin&tab=extensions&path=%2Fextensions&term=facebook',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_connect_instagram',
										'title'    => __( 'Connect Instagram Shopping', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wc-admin&tab=extensions&path=%2Fextensions&term=facebook',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_connect_tiktok',
										'title'    => __( 'Connect TikTok Shop', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wc-admin&tab=extensions&path=%2Fextensions&term=tiktok',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),

									/*
									Hide Jetpack Social Sharing Settings for now
									array(
										'id'       => 'store_add_social_sharing',
										'title'    => __( 'Add Social Sharing Buttons', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/site-editor.php?p=%2Fstyles&section=%2Fblocks%2Fjetpack%252Fsharing-buttons',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'store_add_social_feed',
										'title'    => __( 'Add Social Media Feed to Homepage', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-incorporate-a-social-media-marketing-strategy-with-your-wordpress-website/ ',
										'status'   => 'new',
										'priority' => 5,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'seo_visibility',
								'label' => __( 'Launch and Promote - SEO & Store Visibility', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_optimize_seo',
										'title'    => __( 'Optimize Your Store SEO', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wpseo_dashboard',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_submit_search_console',
										'title'    => __( 'Submit Site to Google Search Console', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-submit-your-website-to-search-engines/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_create_sitemap',
										'title'    => __( 'Create a Custom Sitemap', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/what-is-a-sitemap-how-it-helps-seo-and-navigation/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
						),
					),
					array(
						'id'       => 'store_grow_track',
						'label'    => __( 'Grow', 'wp-module-next-steps' ),
						'sections' => array(
							array(
								'id'    => 'improve_customer_experience',
								'label' => __( 'Improve Your Customer Experience to Sell More', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_customize_thankyou',
										'title'    => __( 'Customize the Thank You Page', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2F&canvas=edit',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_customize_account',
										'title'    => __( 'Customize Your Customer\'s Account Page', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2F&canvas=edit',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),

									/*
									Hide Advanced Reviews for now
									array(
										'id'       => 'store_collect_reviews',
										'title'    => __( 'Collect and Show Reviews for Your Products', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=bh_advanced_reviews_panel',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									*/
								),
							),
							array(
								'id'    => 'advanced_social_marketing',
								'label' => __( 'Advanced Social & Influencer Marketing', 'wp-module-next-steps' ),
								'tasks' => array(

									/*
									Hide Affiliate Program and Yith WooCommerce Points and Rewards for now
									array(
										'id'       => 'store_launch_affiliate',
										'title'    => __( 'Launch an Affiliate Program', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=bh_affiliates_panel',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_create_rewards',
										'title'    => __( 'Create a Points & Rewards Program for Your Customers', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=yith_woocommerce_points_and_rewards',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_run_first_ad',
										'title'    => __( 'Run First Facebook or Instagram Ad', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wc-admin&tab=extensions&path=%2Fextensions&term=facebook',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'store_launch_giveaway',
										'title'    => __( 'Launch Product Giveaway Campaign', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-create-a-giveaway-contest-that-drives-traffic-to-your-site/',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_create_influencer_list',
										'title'    => __( 'Create Influencer Outreach List', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/influencer-marketing/',
										'status'   => 'new',
										'priority' => 5,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_track_utm_campaigns',
										'title'    => __( 'Track UTM Campaign Links', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/affiliate-marketing-tools/',
										'status'   => 'new',
										'priority' => 6,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'next_marketing_steps',
								'label' => __( 'Next Marketing Steps', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_write_blog_post',
										'title'    => __( 'Write a Blog Post', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?wb-library=patterns&wb-category=text',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_create_sale_campaign',
										'title'    => __( 'Create a Sale & Promo Campaign for Your Products', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.in/blog/how-to-create-promotions-on-wordpress-with-woocommerce/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_create_upsell',
										'title'    => __( 'Create an Upsell Campaign', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/what-is-upselling/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_setup_yoast_premium',
										'title'    => __( 'Setup Yoast Premium to Drive Traffic to Your Store', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wpseo_tools',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'store_performance_security',
								'label' => __( 'Performance & Security', 'wp-module-next-steps' ),
								'tasks' => array(

									/*
									Hide Jetpack Boost and Automatic Backups for now
									array(
										'id'       => 'store_improve_performance',
										'title'    => __( 'Improve Performance and Speed with Jetpack Boost', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=my-jetpack#/add-boost',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_enable_auto_backup',
										'title'    => __( 'Enable Auto-Backup & Update Alerts', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=jetpack-backup',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'store_create_staging',
										'title'    => __( 'Create a Staging Website', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=nfd-staging',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'store_analysis',
								'label' => __( 'Store Analysis', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'store_monitor_traffic',
										'title'    => __( 'Monitor Traffic and Conversion Rates', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'store_run_ab_test',
										'title'    => __( 'Run A/B Test on Homepage Banner', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/split-testing/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Get blog plan
	 *
	 * @return Plan
	 */
	public static function get_blog_plan(): Plan {
		return new Plan(
			array(
				'id'          => 'blog_setup',
				'label'       => __( 'Blog Setup', 'wp-module-next-steps' ),
				'description' => __( 'Get your blog up and running with these essential steps:', 'wp-module-next-steps' ),
				'tracks'      => array(
					array(
						'id'       => 'blog_build_track',
						'label'    => __( 'Build', 'wp-module-next-steps' ),
						'open'     => false,
						'sections' => array(
							array(
								'id'    => 'basic_blog_setup',
								'label' => __( 'Basic Blog Setup', 'wp-module-next-steps' ),
								'open'  => true,
								'tasks' => array(
									array(
										'id'              => 'blog_quick_setup',
										'title'           => __( 'Quick Setup', 'wp-module-next-steps' ),
										'href'            => '{siteUrl}/wp-admin/options-general.php',
										'status'          => 'new',
										'priority'        => 1,
										'source'          => 'wp-module-next-steps',
										'data_attributes' => array(
											'data-test-id' => 'blog_quick_setup',
											'data-nfd-id'  => 'blog_quick_start',
										),
									),
								),
							),
							array(
								'id'    => 'customize_blog',
								'label' => __( 'Customize Your Blog', 'wp-module-next-steps' ),
								'open'  => true,
								'tasks' => array(
									array(
										'id'       => 'blog_upload_logo',
										'title'    => __( 'Upload Logo', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=all-parts',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_choose_colors_fonts',
										'title'    => __( 'Choose Colors and Fonts', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fstyles',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_customize_header',
										'title'    => __( 'Customize Header', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=header',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_customize_footer',
										'title'    => __( 'Customize Footer', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=footer',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'create_content',
								'label' => __( 'Create Content', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'blog_first_post',
										'title'    => __( 'Add Your First Blog Post', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_about_page',
										'title'    => __( 'Create an "About" Page', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?post_type=page&wb-library=patterns&wb-category=features',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_set_featured_image',
										'title'    => __( 'Set a Featured Image for One Post', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post.php?post=1&action=edit',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'setup_navigation',
								'label' => __( 'Set Up Navigation', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'blog_add_pages',
										'title'    => __( 'Add Pages for Home, Blog, About, Contact', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/edit.php?post_type=page',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_create_primary_menu',
										'title'    => __( 'Create a Primary Menu', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=/navigation',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_create_footer_menu',
										'title'    => __( 'Create a Footer Menu', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=footer',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'setup_essential_pages',
								'label' => __( 'Set Up Essential Pages', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'blog_privacy_policy',
										'title'    => __( 'Add a Privacy Policy', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/options-privacy.php',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_terms_conditions',
										'title'    => __( 'Add Terms & Conditions', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?wb-library=patterns&wb-category=text',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_accessibility_statement',
										'title'    => __( 'Add an Accessibility Statement', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?wb-library=patterns&wb-category=text',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
						),
					),
					array(
						'id'       => 'blog_brand_track',
						'label'    => __( 'Brand', 'wp-module-next-steps' ),
						'sections' => array(
							array(
								'id'    => 'first_audience_building',
								'label' => __( 'First Audience-Building Steps', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'          => 'blog_welcome_subscribe_popup',
										'title'       => __( 'Add a Welcome-Subscribe Popup', 'wp-module-next-steps' ),
										'description' => __( 'Convert visitors to email subscribers.', 'wp-module-next-steps' ),
										'href'        => 'https://www.bluehost.com/blog/improve-conversion-rate-website-pop-ups/',
										'status'      => 'new',
										'priority'    => 1,
										'source'      => 'wp-module-next-steps',
									),

									/*
									Hide Email Templates and Jetpack Stats for now
									array(
										'id'       => 'blog_customize_notification_emails',
										'title'    => __( 'Customize Notification Emails', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=bh_email_templates_panel',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_connect_jetpack_stats',
										'title'    => __( 'Connect Jetpack Stats (or Google Analytics 4)', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=stats',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									*/
								),
							),
							array(
								'id'    => 'blog_promote_social',
								'label' => __( 'Social Presence', 'wp-module-next-steps' ),
								'tasks' => array(

									/*
									Hide Jetpack Social Sharing Settings for now
									array(
										'id'       => 'blog_connect_facebook',
										'title'    => __( 'Connect Facebook Page Auto-Sharing', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=jetpack-social',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_add_social_sharing',
										'title'    => __( 'Add Social-Sharing Buttons', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/site-editor.php?p=%2Fstyles&section=%2Fblocks%2Fjetpack%252Fsharing-buttons',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'blog_embed_social_feed',
										'title'    => __( 'Embed a Social Media Feed on Homepage', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-incorporate-a-social-media-marketing-strategy-with-your-wordpress-website/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'blog_promote_seo',
								'label' => __( 'SEO & Visibility', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'blog_optimize_seo',
										'title'    => __( 'Optimize On-Page SEO', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wpseo_dashboard',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_submit_search_console',
										'title'    => __( 'Submit Site to Google Search Console', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-submit-your-website-to-search-engines/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_generate_sitemap',
										'title'    => __( 'Generate & Submit XML Sitemap', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/what-is-a-sitemap-how-it-helps-seo-and-navigation/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
						),
					),
					array(
						'id'       => 'blog_grow_track',
						'label'    => __( 'Grow', 'wp-module-next-steps' ),
						'sections' => array(
							array(
								'id'    => 'enhance_reader_experience',
								'label' => __( 'Enhance Reader Experience', 'wp-module-next-steps' ),
								'tasks' => array(

									/*
									Hide Akismet for now
									array(
										'id'       => 'blog_enable_comments',
										'title'    => __( 'Enable & Style Comments Section', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=akismet-key-config',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'blog_customize_author_boxes',
										'title'    => __( 'Customize Author/Profile Boxes', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2F&canvas=edit',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_display_testimonials',
										'title'    => __( 'Display Testimonials or Highlighted Comments', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/customer-testimonials/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_create_favicon',
										'title'    => __( 'Create a Favicon', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/customize.php?autofocus[section]=title_tagline',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'advanced_promotion_partnerships',
								'label' => __( 'Advanced Social & Influencer Marketing', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'blog_build_newsletter',
										'title'    => __( 'Build an Email Newsletter', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-create-an-email-newsletter/',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_draft_outreach_list',
										'title'    => __( 'Draft an Influencer/Guest-Post Outreach List', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/guest-blogging/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_run_first_ad',
										'title'    => __( 'Run pillar article promotion on social ad', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/social-media-advertising-tips/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_track_utm_campaigns',
										'title'    => __( 'Track Campaigns with UTM Links', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-create-a-content-calendar/',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'content_traffic_strategy',
								'label' => __( 'Content & Traffic Strategy', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'blog_plan_content_series',
										'title'    => __( 'Plan a Content Series or Editorial Calendar', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-create-a-content-calendar/',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_implement_internal_linking',
										'title'    => __( 'Implement Internal-Linking Strategy', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/internal-linking-guide/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_install_yoast_premium',
										'title'    => __( 'Install Yoast Premium for Advanced Schemas', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wpseo_dashboard',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'blog_performance_security',
								'label' => __( 'Performance & Security', 'wp-module-next-steps' ),
								'tasks' => array(

									/*
									Hide Jetpack Boost and Automatic Backups for now
									array(
										'id'       => 'blog_speed_up_site',
										'title'    => __( 'Speed-up Site with Jetpack Boost', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=my-jetpack#/add-boost',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'blog_enable_auto_backups',
										'title'    => __( 'Enable Automatic Backups & Update Alerts', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=jetpack-backup',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'blog_create_staging_site',
										'title'    => __( 'Create a Staging Site', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/what-is-a-staging-site-and-how-to-create-a-bluehost-staging-site-for-your-wordpress-website/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),

							/*
							Hide Jetpack Analytics for now
							array(
								'id'    => 'blog_analytics',
								'label' => __( 'Blog Analytics', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'blog_monitor_traffic',
										'title'    => __( 'Monitor Traffic & Engagement', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=stats',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							*/
						),
					),
				),
			)
		);
	}

	/**
	 * Get corporate plan
	 *
	 * @return Plan
	 */
	public static function get_corporate_plan(): Plan {
		return new Plan(
			array(
				'id'          => 'corporate_setup',
				'label'       => __( 'Corporate Setup', 'wp-module-next-steps' ),
				'description' => __( 'Set up your corporate website with these essential steps:', 'wp-module-next-steps' ),
				'tracks'      => array(
					array(
						'id'       => 'corporate_build_track',
						'label'    => __( 'Build', 'wp-module-next-steps' ),
						'open'     => false,
						'sections' => array(
							array(
								'id'    => 'basic_site_setup',
								'label' => __( 'Basic Site Setup', 'wp-module-next-steps' ),
								'open'  => true,
								'tasks' => array(
									array(
										'id'              => 'corporate_quick_setup',
										'title'           => __( 'Quick Setup', 'wp-module-next-steps' ),
										'href'            => '{siteUrl}/wp-admin/options-general.php',
										'status'          => 'new',
										'priority'        => 1,
										'source'          => 'wp-module-next-steps',
										'data_attributes' => array(
											'data-test-id' => 'corporate_quick_setup',
											'data-nfd-id'  => 'corporate_quick_start',
										),
									),
								),
							),
							array(
								'id'    => 'customize_website',
								'label' => __( 'Customize Your Website', 'wp-module-next-steps' ),
								'open'  => true,
								'tasks' => array(
									array(
										'id'       => 'corporate_upload_logo',
										'title'    => __( 'Upload Company Logo', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=all-parts',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_choose_brand_colors',
										'title'    => __( 'Choose Brand Colors and Fonts', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fstyles',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_customize_header',
										'title'    => __( 'Customize Header', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=header',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_customize_footer',
										'title'    => __( 'Customize Footer', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=footer',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_customize_homepage',
										'title'    => __( 'Customize Homepage Layout', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Ftemplate',
										'status'   => 'new',
										'priority' => 5,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'configure_navigation',
								'label' => __( 'Configure Navigation', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'corporate_add_navigation_pages',
										'title'    => __( 'Add Pages for Home, Blog, About, Contact', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/edit.php?post_type=page',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_create_primary_menu',
										'title'    => __( 'Create a Primary Menu', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=/navigation',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_add_footer_menu',
										'title'    => __( 'Add a Footer Menu', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=footer',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'add_legal_trust_content',
								'label' => __( 'Add Legal & Trust Content', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'corporate_privacy_policy',
										'title'    => __( 'Add a Privacy Policy', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/options-privacy.php',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_terms_conditions',
										'title'    => __( 'Add Terms & Conditions', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?wb-library=patterns&wb-category=text',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_accessibility_statement',
										'title'    => __( 'Add an Accessibility Statement', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?wb-library=patterns&wb-category=text',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
						),
					),
					array(
						'id'       => 'corporate_brand_track',
						'label'    => __( 'Brand', 'wp-module-next-steps' ),
						'sections' => array(
							array(
								'id'    => 'establish_brand_online',
								'label' => __( 'Establish Your Brand Online', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'corporate_setup_custom_domain',
										'title'    => __( 'Set Up a Custom Domain', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/my-account/domain-center-update/list',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_create_favicon',
										'title'    => __( 'Create a Favicon', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/customize.php?autofocus[section]=title_tagline',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_connect_google_business',
										'title'    => __( 'Connect Your Google Business Profile', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/transfer-google-business-profile-free-website-bluehost/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_create_branded_email',
										'title'    => __( 'Create a Branded Email Address', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-create-a-business-email-for-free/',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'launch_marketing_tools',
								'label' => __( 'Launch Essential Marketing Tools', 'wp-module-next-steps' ),
								'tasks' => array(

									/*
									Hide Jetpack Stats for now
									array(
										'id'       => 'corporate_setup_jetpack_stats',
										'title'    => __( 'Set Up Jetpack Stats', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=stats',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'corporate_connect_search_console',
										'title'    => __( 'Connect Google Search Console', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-submit-your-website-to-search-engines/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_install_seo_plugin',
										'title'    => __( 'Explore SEO Plugin', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=wpseo_dashboard',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),

									/*
									Hide Jetpack Social Sharing Settings for now
									array(
										'id'       => 'corporate_add_social_sharing',
										'title'    => __( 'Add Social Sharing Settings', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/site-editor.php?p=%2Fstyles&section=%2Fblocks%2Fjetpack%252Fsharing-buttons',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
									*/
								),
							),
							array(
								'id'    => 'setup_contact_engagement',
								'label' => __( 'Set Up Contact & Engagement', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'corporate_add_contact_form',
										'title'    => __( 'Add a Contact Form with email routing', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/create-contact-form-wordpress-guide/',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_embed_map',
										'title'    => __( 'Embed a Map or Location', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/top-wordpress-store-locator-plugins/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_link_social_profiles',
										'title'    => __( 'Link to Social Media Profiles', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/site-editor.php',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
						),
					),
					array(
						'id'       => 'corporate_grow_track',
						'label'    => __( 'Grow', 'wp-module-next-steps' ),
						'sections' => array(
							array(
								'id'    => 'strengthen_online_presence',
								'label' => __( 'Strengthen Online Presence', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'corporate_add_client_testimonials',
										'title'    => __( 'Add Client Logos or Testimonials or Reviews', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?post_type=page',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_add_certifications',
										'title'    => __( 'Add Certifications, Memberships, or Awards', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?post_type=page',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'build_content_seo_trust',
								'label' => __( 'Build Content for SEO & Trust', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'corporate_publish_first_blog_post',
										'title'    => __( 'Publish Your First Company Blog Post', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?wb-library=patterns&wb-category=text',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_create_faq_page',
										'title'    => __( 'Create a FAQ Page', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/post-new.php?post_type=page&wb-library=patterns&wb-category=features',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_optimize_key_pages',
										'title'    => __( 'Optimize Your Key Pages for Keywords', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/content-optimization-guide/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_generate_submit_sitemap',
										'title'    => __( 'Generate and Submit XML Sitemap', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/what-is-a-sitemap-how-it-helps-seo-and-navigation/',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'marketing_lead_generation',
								'label' => __( 'Marketing & Lead Generation', 'wp-module-next-steps' ),
								'tasks' => array(
									array(
										'id'       => 'corporate_setup_email_capture',
										'title'    => __( 'Set Up an Email Capture Form', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-add-an-email-opt-in-form-to-your-website/',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_connect_crm',
										'title'    => __( 'Connect to CRM or Email Tool', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/marketing-automation-tools/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_add_cta_section',
										'title'    => __( 'Add a Call-to-Action Section to Homepage', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/call-to-action-tips/',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'site_performance_security',
								'label' => __( 'Site Performance & Security', 'wp-module-next-steps' ),
								'tasks' => array(

									/*
									Hide Jetpack Boost and Automatic Backups for now
									array(
										'id'       => 'corporate_install_jetpack_boost',
										'title'    => __( 'Install Jetpack Boost or Caching Plugin', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=my-jetpack#/add-boost',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_enable_auto_backups',
										'title'    => __( 'Enable Automatic Backups & Update Alerts', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=jetpack-backup',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'corporate_install_security_plugin',
										'title'    => __( 'Install a Security Plugin', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=bluehost#/marketplace/security',
										'status'   => 'new',
										'priority' => 3,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_setup_staging_site',
										'title'    => __( 'Set Up a Staging Site', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=nfd-staging',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
							array(
								'id'    => 'monitor_improve',
								'label' => __( 'Monitor & Improve', 'wp-module-next-steps' ),
								'tasks' => array(

									/*
									Hide Jetpack Traffic for now
									array(
										'id'       => 'corporate_review_traffic_engagement',
										'title'    => __( 'Review Traffic & Engagement', 'wp-module-next-steps' ),
										'href'     => '{siteUrl}/wp-admin/admin.php?page=stats',
										'status'   => 'new',
										'priority' => 1,
										'source'   => 'wp-module-next-steps',
									),
									*/
									array(
										'id'       => 'corporate_run_speed_test',
										'title'    => __( 'Run a Speed Test', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/what-is-my-page-speed/',
										'status'   => 'new',
										'priority' => 2,
										'source'   => 'wp-module-next-steps',
									),
									array(
										'id'       => 'corporate_plan_next_content',
										'title'    => __( 'Plan Your Next Content or Campaign Update', 'wp-module-next-steps' ),
										'href'     => 'https://www.bluehost.com/blog/how-to-create-a-content-calendar/',
										'status'   => 'new',
										'priority' => 4,
										'source'   => 'wp-module-next-steps',
									),
								),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Update task status
	 *
	 * @param string $track_id Track ID
	 * @param string $section_id Section ID
	 * @param string $task_id Task ID
	 * @param string $status New status
	 * @return bool
	 */
	public static function update_task_status( string $track_id, string $section_id, string $task_id, string $status ): bool {
		$plan = self::get_current_plan();
		if ( ! $plan ) {
			return false;
		}

		$success = $plan->update_task_status( $track_id, $section_id, $task_id, $status );
		if ( $success ) {
			self::save_plan( $plan );
		}

		return $success;
	}

	/**
	 * Get task by IDs
	 *
	 * @param string $track_id Track ID
	 * @param string $section_id Section ID
	 * @param string $task_id Task ID
	 * @return Task|null
	 */
	public static function get_task( string $track_id, string $section_id, string $task_id ): ?Task {
		$plan = self::get_current_plan();
		if ( ! $plan ) {
			return null;
		}

		return $plan->get_task( $track_id, $section_id, $task_id );
	}

	/**
	 * Add task to a section
	 *
	 * @param string $track_id Track ID
	 * @param string $section_id Section ID
	 * @param Task   $task Task to add
	 * @return bool
	 */
	public static function add_task( string $track_id, string $section_id, Task $task ): bool {
		$plan = self::get_current_plan();
		if ( ! $plan ) {
			return false;
		}

		$section = $plan->get_section( $track_id, $section_id );
		if ( ! $section ) {
			return false;
		}

		$success = $section->add_task( $task );
		if ( $success ) {
			self::save_plan( $plan );
		}

		return $success;
	}

	/**
	 * Reset plan to defaults
	 *
	 * @return Plan
	 */
	public static function reset_plan(): Plan {
		delete_option( self::OPTION );
		return PlanLoader::load_default_plan();
	}

	/**
	 * Get plan statistics
	 *
	 * @return array
	 */
	public static function get_plan_stats(): array {
		$plan = self::get_current_plan();
		if ( ! $plan ) {
			return array();
		}

		return array(
			'completion_percentage' => $plan->get_completion_percentage(),
			'total_tasks'           => $plan->get_total_tasks_count(),
			'completed_tasks'       => $plan->get_completed_tasks_count(),
			'total_sections'        => $plan->get_total_sections_count(),
			'completed_sections'    => $plan->get_completed_sections_count(),
			'total_tracks'          => $plan->get_total_tracks_count(),
			'completed_tracks'      => $plan->get_completed_tracks_count(),
			'is_completed'          => $plan->is_completed(),
		);
	}

	/**
	 * Update section open state
	 *
	 * @param string $track_id Track ID
	 * @param string $section_id Section ID
	 * @param bool   $open Open state
	 * @return bool
	 */
	public static function update_section_status( string $track_id, string $section_id, bool $open ): bool {
		$plan = self::get_current_plan();
		if ( ! $plan ) {
			return false;
		}

		$success = $plan->update_section_open_state( $track_id, $section_id, $open );
		if ( $success ) {
			self::save_plan( $plan );
		}

		return $success;
	}

	/**
	 * Update track open state
	 *
	 * @param string $track_id Track ID
	 * @param bool   $open Open state
	 * @return bool
	 */
	public static function update_track_status( string $track_id, bool $open ): bool {
		$plan = self::get_current_plan();
		if ( ! $plan ) {
			return false;
		}

		$success = $plan->update_track_open_state( $track_id, $open );
		if ( $success ) {
			self::save_plan( $plan );
		}

		return $success;
	}
}
