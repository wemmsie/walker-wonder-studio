<?php

namespace NewfoldLabs\WP\Module\NextSteps;

use NewfoldLabs\WP\Module\NextSteps\DTOs\Task;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class StepsApi
 */
class StepsApi {

	/**
	 * Transient name where data is stored.
	 */
	const OPTION = 'nfd_next_steps';


	/**
	 * REST namespace
	 *
	 * @var string
	 */
	private $namespace;

	/**
	 * REST base
	 *
	 * @var string
	 */
	private $rest_base;

	/**
	 * EntitilementsApi constructor.
	 */
	public function __construct() {
		$this->namespace = 'newfold-next-steps/v1';
		$this->rest_base = '/steps';
	}

	/**
	 * Register Entitlement routes.
	 */
	public function register_routes() {

		// Add route for fetching steps
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_steps' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Add route for adding steps
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/add',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_steps' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Add route for updating a step status
		// newfold-next-steps/v1/steps/status
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/status',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_task_status' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'plan_id'    => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'track_id'   => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'section_id' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'task_id'    => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'status'    => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
				),
			)
		);

		// Add route for plan statistics
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/stats',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_plan_stats' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Add route for switching plans
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/switch',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'switch_plan' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'plan_type' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value ) && in_array( $value, array( 'ecommerce', 'blog', 'corporate' ), true );
						},
					),
				),
			)
		);

		// Add route for resetting plan
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/reset',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'reset_plan' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Add route for adding tasks to specific sections
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/tasks',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_task_to_section' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'track_id'   => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'section_id' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'task'       => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_array( $value ) && isset( $value['id'], $value['title'] );
						},
					),
				),
			)
		);

		// Add route for updating section open state
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/section/open',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_section_status' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'plan_id'    => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'track_id'   => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'section_id' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'open'    => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_bool( $value );
						},
					),
				),
			)
		);

		// Add route for updating track open state
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/track/open',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_track_status' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'plan_id'  => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'track_id' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					),
					'open'  => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return is_bool( $value );
						},
					),
				),
			)
		);
	}

	/**
	 * Set the option where steps are stored.
	 *
	 * @param array $steps           Data to be stored
	 */
	public static function set_data( $steps ) {
		update_option( self::OPTION, $steps );
	}

	/**
	 * Get current plan and steps.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public static function get_steps() {
		$plan = PlanManager::get_current_plan();

		if ( ! $plan ) {
			return new \WP_Error( 'no_plan', __( 'No plan found.', 'wp-module-next-steps' ), array( 'status' => 404 ) );
		}

		// TODO
		// check each steps callback to determine if completed - smart next steps autocomplete
		// each step can define a callback that will be called to determine if the step is completed
		// for example add post can check if a post exists in the site or add media can check if media has been uploaded

		return new WP_REST_Response( $plan->to_array(), 200 );
	}

	/**
	 * Add tasks to the current plan.
	 *
	 * @param array $new_tasks Array of new tasks to add or update.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public static function add_steps( $new_tasks ) {
		// Get the current plan
		$plan = PlanManager::get_current_plan();

		if ( ! $plan ) {
			return new \WP_Error( 'no_plan', __( 'No plan found.', 'wp-module-next-steps' ), array( 'status' => 404 ) );
		}

		// Add tasks to the first available section
		$tracks = $plan->get_tracks();
		if ( empty( $tracks ) ) {
			return new \WP_Error( 'no_tracks', __( 'No tracks found in plan.', 'wp-module-next-steps' ), array( 'status' => 404 ) );
		}

		$first_track = $tracks[0];
		$sections    = $first_track->get_sections();
		if ( empty( $sections ) ) {
			return new \WP_Error( 'no_sections', __( 'No sections found in track.', 'wp-module-next-steps' ), array( 'status' => 404 ) );
		}

		$first_section = $sections[0];

		// Add each task to the first section
		foreach ( $new_tasks as $task_data ) {
			if ( ! isset( $task_data['id'] ) ) {
				continue;
			}

			$task = Task::from_array( $task_data );

			// Check if task already exists and update it
			$existing_task = $first_section->get_task( $task->id );
			if ( $existing_task ) {
				// Update allowed fields
				$sync_fields = array( 'title', 'description', 'href', 'priority' );
				foreach ( $sync_fields as $field ) {
					if ( isset( $task_data[ $field ] ) && $existing_task->$field !== $task_data[ $field ] ) {
						$existing_task->$field = $task_data[ $field ];
					}
				}
			} else {
				// Add new task
				$first_section->add_task( $task );
			}
		}

		// Save the updated plan
		PlanManager::save_plan( $plan );

		return new \WP_REST_Response( $plan->to_array(), 200 );
	}

	/**
	 * Update a task status.
	 *
	 * @param \WP_REST_Request $request  The REST request object.
	 * @return WP_REST_Response|WP_Error The response object on success, or WP_Error on failure.
	 */
	public static function update_task_status( \WP_REST_Request $request ) {
		$plan_id    = $request->get_param( 'plan_id' );
		$track_id   = $request->get_param( 'track_id' );
		$section_id = $request->get_param( 'section_id' );
		$task_id    = $request->get_param( 'task_id' );
		$status     = $request->get_param( 'status' );

		// validate parameters
		if ( empty( $track_id ) || empty( $section_id ) || empty( $task_id ) || empty( $status ) ) {
			return new WP_Error( 'invalid_params', __( 'Invalid parameters provided.', 'wp-module-next-steps' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $status, array( 'new', 'done', 'dismissed' ), true ) ) {
			return new WP_Error( 'invalid_status', __( 'Invalid status provided.', 'wp-module-next-steps' ), array( 'status' => 400 ) );
		}

		// Use PlanManager to update the task status
		$success = PlanManager::update_task_status( $track_id, $section_id, $task_id, $status );

		if ( ! $success ) {
			return new WP_Error( 'step_not_found', __( 'Step not found.', 'wp-module-next-steps' ), array( 'status' => 404 ) );
		}

		// Get the updated plan
		// $plan = PlanManager::get_current_plan();

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Update a section status.
	 *
	 * @param \WP_REST_Request $request  The REST request object.
	 * @return WP_REST_Response|WP_Error The response object on success, or WP_Error on failure.
	 */
	public static function update_section_status( \WP_REST_Request $request ) {
		$plan_id    = $request->get_param( 'plan_id' );
		$track_id   = $request->get_param( 'track_id' );
		$section_id = $request->get_param( 'section_id' );
		$open       = $request->get_param( 'open' ) ?? false;

		// validate parameters
		if ( empty( $track_id ) || empty( $section_id ) ) {
			return new WP_Error( 'invalid_params', __( 'Invalid parameters provided.', 'wp-module-next-steps' ), array( 'status' => 400 ) );
		}

		// Use PlanManager to update the section status
		$success = PlanManager::update_section_status( $track_id, $section_id, $open );

		if ( ! $success ) {
			return new WP_Error( 'section_not_found', __( 'Section not found.', 'wp-module-next-steps' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Update a track status.
	 *
	 * @param \WP_REST_Request $request  The REST request object.
	 * @return WP_REST_Response|WP_Error The response object on success, or WP_Error on failure.
	 */
	public static function update_track_status( \WP_REST_Request $request ) {
		$plan_id  = $request->get_param( 'plan_id' );
		$track_id = $request->get_param( 'track_id' );
		$open     = $request->get_param( 'open' ) ?? false;

		// validate parameters
		if ( empty( $track_id ) ) {
			return new WP_Error( 'invalid_params', __( 'Invalid parameters provided.', 'wp-module-next-steps' ), array( 'status' => 400 ) );
		}

		// Use PlanManager to update the track status
		$success = PlanManager::update_track_status( $track_id, $open );

		if ( ! $success ) {
			return new WP_Error( 'track_not_found', __( 'Track not found.', 'wp-module-next-steps' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Get plan statistics
	 *
	 * @return WP_REST_Response
	 */
	public static function get_plan_stats() {
		$stats = PlanManager::get_plan_stats();
		return new WP_REST_Response( $stats, 200 );
	}

	/**
	 * Switch to a different plan
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function switch_plan( \WP_REST_Request $request ) {
		$plan_type = $request->get_param( 'plan_type' );

		$plan = PlanManager::switch_plan( $plan_type );

		if ( ! $plan ) {
			return new WP_Error( 'invalid_plan_type', __( 'Invalid plan type provided.', 'wp-module-next-steps' ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response( $plan->to_array(), 200 );
	}

	/**
	 * Reset plan to defaults
	 *
	 * @return WP_REST_Response
	 */
	public static function reset_plan() {
		$plan = PlanManager::reset_plan();
		return new WP_REST_Response( $plan->to_array(), 200 );
	}

	/**
	 * Add task to a specific section
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function add_task_to_section( \WP_REST_Request $request ) {
		$track_id   = $request->get_param( 'track_id' );
		$section_id = $request->get_param( 'section_id' );
		$task_data  = $request->get_param( 'task' );

		$task = Task::from_array( $task_data );

		$success = PlanManager::add_task( $track_id, $section_id, $task );

		if ( ! $success ) {
			return new WP_Error( 'add_task_failed', __( 'Failed to add task to section.', 'wp-module-next-steps' ), array( 'status' => 400 ) );
		}

		$plan = PlanManager::get_current_plan();
		return new WP_REST_Response( $plan->to_array(), 200 );
	}
}
