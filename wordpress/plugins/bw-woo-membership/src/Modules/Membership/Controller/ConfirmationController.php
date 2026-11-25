<?php
/**
 * Membership confirmation.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Core\View;
use BW\WooMembership\Modules\Membership\Service\DashboardModel;
use BW\WooMembership\Modules\Membership\Service\MembershipService;

/**
 * Printable membership confirmation for active members (the browser saves it as PDF).
 */
final class ConfirmationController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MembershipService $memberships,
		private readonly DashboardModel $model,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'maybe_render' ), 1 );
	}

	/**
	 * Renders the confirmation for `?bw_wm_confirmation=1`.
	 */
	public function maybe_render(): void {
		if ( ! isset( $_GET['bw_wm_confirmation'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page of the current user.
			return;
		}
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			auth_redirect();
		}
		if ( ! $this->memberships->for_user( $user_id )->grants_access() ) {
			wp_die( esc_html__( 'Eine Mitgliedsbestätigung gibt es nur für aktive Mitglieder.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow' );
		echo View::render( 'dashboard/confirmation', $this->model->confirmation( $user_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template.
		exit;
	}
}
