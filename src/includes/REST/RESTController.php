<?php
/**
 * REST Controller for ConsentPilot.
 *
 * @package ConsentPilot
 */

namespace Frogrammer\ConsentPilot\REST;

use Frogrammer\ConsentPilot\Admin\ConsentController;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Registers REST routes and callbacks.
 */
class RESTController {

	/**
	 * Hook registrations.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'consent-pilot/v1',
			'consent',
			array(
				'methods'             => WP_REST_Server::CREATABLE, // POST.
				'callback'            => array( $this, 'handle_consent' ),
				'permission_callback' => '__return_true',
				'args'                => array(),
			)
		);
	}

	/**
	 * Handle consent submission.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function handle_consent( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();

		// Fallback to form-encoded body or query params if JSON not provided.
		if ( empty( $params ) ) {
			$params = $request->get_body_params();
		}
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$result = ConsentController::add_consent_records( $params );

		return rest_ensure_response( $result );
	}
}
