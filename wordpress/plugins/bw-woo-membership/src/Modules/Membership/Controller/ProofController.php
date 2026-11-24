<?php
/**
 * Student proof in the member area.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Core\View;
use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use BW\WooMembership\Modules\Membership\Service\Mailer;
use BW\WooMembership\Modules\Membership\Service\MembershipPeriod;
use BW\WooMembership\Modules\Membership\Service\MembershipService;
use BW\WooMembership\Modules\Membership\Service\ProofRules;
use BW\WooMembership\Modules\Membership\Service\ProofService;

/**
 * Block "Mitglied: Studiennachweis" with upload form, the upload handler, the welcome mail for new student members
 * and the daily reminders.
 */
final class ProofController {

	public const ACTION = 'bw_wm_proof';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly ProofService $service,
		private readonly ProofRepository $proofs,
		private readonly MembershipService $memberships,
		private readonly Mailer $mailer,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ), 20 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_upload' ) );
		add_action( 'bw_wm_daily_after_sync', array( $this->service, 'send_reminders' ) );
		add_action( 'bw_wm_membership_paid', array( $this, 'ask_new_student' ), 10, 2 );
	}

	/**
	 * Registers the block.
	 */
	public function register_block(): void {
		register_block_type( BW_WM_DIR . '/blocks/proof', array( 'render_callback' => array( $this, 'render' ) ) );
	}

	/**
	 * Status and upload form; only for student members or members with a proof on file.
	 */
	public function render(): string {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return '';
		}
		$membership = $this->memberships->for_user( $user_id );
		$proof      = $this->proofs->get( $user_id );
		if ( 'student' !== $membership->type && 'none' === $proof['status'] ) {
			return '';
		}
		$labels = array(
			'none'     => array( 'Noch kein Nachweis', 'Bitte lade eine aktuelle Inskriptionsbestätigung hoch.' ),
			'pending'  => array( 'In Prüfung', 'Danke! Wir prüfen deinen Nachweis und melden uns per E-Mail.' ),
			'approved' => array( 'Bestätigt', 'Gültig bis ' . MembershipPeriod::format( $proof['valid_until'] ) . '.' ),
			'rejected' => array( 'Nicht bestätigt', 'Bitte lade einen neuen Nachweis hoch.' . ( '' !== $proof['reason'] ? ' Grund: ' . $proof['reason'] : '' ) ),
		);
		$label   = $labels[ $proof['status'] ] ?? $labels['none'];
		$message = isset( $_GET['bwm_proof'] ) ? sanitize_key( wp_unslash( $_GET['bwm_proof'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		return View::render(
			'dashboard/proof',
			array(
				'status'  => $proof['status'],
				'label'   => $label[0],
				'text'    => $label[1],
				'can'     => 'pending' !== $proof['status'] || 'error' === $message,
				'action'  => admin_url( 'admin-post.php' ),
				'message' => 'ok' === $message ? 'Nachweis hochgeladen.' : ( 'error' === $message ? (string) get_transient( 'bw_wm_proof_error_' . $user_id ) : '' ),
				'error'   => 'error' === $message,
			)
		);
	}

	/**
	 * Handles the upload form.
	 */
	public function handle_upload(): void {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 || ! check_admin_referer( self::ACTION ) ) {
			wp_die( esc_html__( 'Nicht erlaubt.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		$file   = isset( $_FILES['bw_wm_proof'] ) && is_array( $_FILES['bw_wm_proof'] ) ? $_FILES['bw_wm_proof'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated in ProofService.
		$result = $this->service->upload(
			$user_id,
			array(
				'name'     => (string) ( $file['name'] ?? '' ),
				'tmp_name' => (string) ( $file['tmp_name'] ?? '' ),
				'size'     => (int) ( $file['size'] ?? 0 ),
				'error'    => (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ),
			)
		);
		if ( is_wp_error( $result ) ) {
			set_transient( 'bw_wm_proof_error_' . $user_id, $result->get_error_message(), 300 );
		}
		$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'bwm_proof', is_wp_error( $result ) ? 'error' : 'ok', remove_query_arg( array( 'bwm_proof', 'bwm' ), $back ) ) . '#nachweis' );
		exit;
	}

	/**
	 * New student members without a valid proof are asked to upload one (within 30 days, see docs/OFFENE-FRAGEN.md M2).
	 */
	public function ask_new_student( int $user_id, string $type ): void {
		if ( 'student' !== $type || ProofRules::valid_on( $this->proofs->get( $user_id ), current_time( 'Y-m-d' ) ) ) {
			return;
		}
		$user = get_userdata( $user_id );
		if ( $user ) {
			$this->mailer->send(
				$user->user_email,
				'Willkommen bei der APPA – bitte Studiennachweis hochladen',
				sprintf( "Hallo %s,\n\nschön, dass du dabei bist! Für die studentische Mitgliedschaft brauchen wir eine aktuelle Inskriptionsbestätigung. Bitte lade sie innerhalb von 30 Tagen im Mitgliederbereich hoch: %s", $user->first_name ?: $user->display_name, home_url( '/mitgliederbereich-uebersicht/#nachweis' ) )
			);
		}
	}
}
