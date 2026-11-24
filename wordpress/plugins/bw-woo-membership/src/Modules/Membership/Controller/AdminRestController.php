<?php
/**
 * REST routes of the member administration.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Core\Setup;
use BW\WooMembership\Modules\Membership\Service\MemberAdminService;
use BW\WooMembership\Modules\Membership\Service\ProofService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Routes under `bw-wm/v1` for the admin app; all require the capability `bw_wm_manage_members`.
 */
final class AdminRestController {

	public const NAMESPACE = 'bw-wm/v1';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MemberAdminService $admin,
		private readonly ProofService $proofs,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the routes.
	 */
	public function routes(): void {
		$can = static fn(): bool => current_user_can( Setup::CAPABILITY );
		register_rest_route(
			self::NAMESPACE,
			'/members',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list' ),
					'permission_callback' => $can,
					'args'                => array(
						'status'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
						'type'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
						'search'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
						'page'     => array( 'type' => 'integer', 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'default' => 50 ),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create' ),
					'permission_callback' => $can,
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/members/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get' ),
					'permission_callback' => $can,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update' ),
					'permission_callback' => $can,
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/proofs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'proofs' ),
				'permission_callback' => $can,
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/proofs/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'decide' ),
				'permission_callback' => $can,
				'args'                => array(
					'decision'    => array( 'type' => 'string', 'enum' => array( 'approve', 'reject' ), 'required' => true ),
					'valid_until' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
					'reason'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);
	}

	/**
	 * GET /members.
	 */
	public function list( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( $this->admin->list( $request->get_params() ) );
	}

	/**
	 * POST /members (manual membership with a new account).
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->admin->create( (array) $request->get_json_params() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * GET /members/{id}.
	 */
	public function get( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->admin->get( (int) $request['id'] );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * POST /members/{id}.
	 */
	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->admin->update( (int) $request['id'], (array) $request->get_json_params() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * GET /proofs.
	 */
	public function proofs(): WP_REST_Response {
		return rest_ensure_response( $this->admin->pending_proofs() );
	}

	/**
	 * POST /proofs/{id}.
	 */
	public function decide( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->proofs->decide(
			(int) $request['id'],
			'approve' === $request['decision'],
			(string) $request['valid_until'],
			(string) $request['reason'],
			get_current_user_id()
		);
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'ok' => true ) );
	}
}
