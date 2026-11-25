<?php
/**
 * Student proof required when joining as a student.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use BW\WooMembership\Modules\Membership\Repository\UploadLimitRepository;
use WP_Error;

/**
 * The proof is required when booking the student membership (answer M2, 24 November 2026). Logged-in people attach
 * it to their account right away; people without an account yet keep it in the WooCommerce session until the order
 * creates their account.
 */
final class JoinProof {

	public const SESSION       = 'bw_wm_join_proof';
	public const SESSION_ERROR = 'bw_wm_join_proof_error';

	/**
	 * Guest uploads per address and hour.
	 */
	public const MAX_GUEST_UPLOADS = 5;

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly ProofRepository $proofs,
		private readonly ProofService $service,
		private readonly UploadLimitRepository $uploads,
	) {
	}

	/**
	 * Page with the upload form (block "Studiennachweis für den Beitritt").
	 */
	public function page_url(): string {
		/**
		 * Filters the address of the page with the join proof form.
		 *
		 * @param string $url Default /studiennachweis/.
		 */
		return (string) apply_filters( 'bw_wm_join_proof_page', home_url( '/studiennachweis/' ) );
	}

	/**
	 * Proof kept in the session, or null.
	 *
	 * @return array{file: string, name: string}|null
	 */
	public function stashed(): ?array {
		$session = function_exists( 'WC' ) ? WC()->session : null;
		$value   = $session ? $session->get( self::SESSION ) : null;
		return is_array( $value ) && is_readable( (string) ( $value['file'] ?? '' ) ) ? array( 'file' => (string) $value['file'], 'name' => (string) ( $value['name'] ?? '' ) ) : null;
	}

	/**
	 * Whether the current person has a proof for joining: in the session, waiting for review or valid today.
	 */
	public function has_proof( int $user_id ): bool {
		if ( null !== $this->stashed() ) {
			return true;
		}
		if ( $user_id <= 0 ) {
			return false;
		}
		$proof = $this->proofs->get( $user_id );
		return 'pending' === $proof['status'] || ProofRules::valid_on( $proof, current_time( 'Y-m-d' ) );
	}

	/**
	 * Name of the proof file shown to the person ('' when none).
	 */
	public function name( int $user_id ): string {
		$stashed = $this->stashed();
		if ( null !== $stashed ) {
			return $stashed['name'];
		}
		return $user_id > 0 ? $this->proofs->get( $user_id )['name'] : '';
	}

	/**
	 * Accepts an uploaded proof: on the account when logged in, otherwise in the session.
	 *
	 * @param array{name: string, tmp_name: string, size: int, error: int} $file   Entry of $_FILES.
	 * @param int                                                          $user_id Logged-in user or 0.
	 * @param string                                                       $client  Address of a guest for the upload limit ('' = no limit).
	 * @return true|WP_Error
	 */
	public function accept( array $file, int $user_id, string $client = '' ): bool|WP_Error {
		if ( $user_id > 0 ) {
			return $this->service->upload( $user_id, $file );
		}
		// Guests can upload without an account, so the number of files per address is limited.
		if ( '' !== $client ) {
			if ( $this->uploads->count( $client ) >= self::MAX_GUEST_UPLOADS ) {
				return new WP_Error( 'bw_wm_proof', 'Zu viele Uploads in kurzer Zeit. Bitte versuche es in einer Stunde noch einmal.' );
			}
			$this->uploads->increment( $client );
		}
		$stored = $this->service->stash( $file );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}
		$session = WC()->session;
		if ( ! $session ) {
			wp_delete_file( $stored['file'] );
			return new WP_Error( 'bw_wm_proof', 'Bitte aktiviere Cookies und versuche es noch einmal.' );
		}
		$old = $this->stashed();
		if ( null !== $old ) {
			wp_delete_file( $old['file'] );
		}
		if ( ! $session->has_session() ) {
			$session->set_customer_session_cookie( true );
		}
		$session->set( self::SESSION, $stored );
		return true;
	}

	/**
	 * Moves a proof from the session to the account created at the checkout.
	 */
	public function attach_to( int $user_id ): void {
		$stashed = $this->stashed();
		if ( null === $stashed || $user_id <= 0 ) {
			return;
		}
		$this->service->attach( $user_id, $stashed['file'], $stashed['name'] );
		WC()->session->set( self::SESSION, null );
	}

	/**
	 * Remembers an error message for the next page view.
	 */
	public function remember_error( string $message ): void {
		if ( function_exists( 'WC' ) && WC()->session ) {
			if ( ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}
			WC()->session->set( self::SESSION_ERROR, $message );
		}
	}

	/**
	 * Takes the remembered error message (once).
	 */
	public function take_error(): string {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return '';
		}
		$message = (string) WC()->session->get( self::SESSION_ERROR );
		if ( '' !== $message ) {
			WC()->session->set( self::SESSION_ERROR, null );
		}
		return $message;
	}
}
