<?php
/**
 * Student proof workflow.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use BW\WooMembership\Support\Mailer;
use BW\WooMembership\Support\ProtectedFolder;
use WP_Error;

/**
 * Upload, review and reminders for the yearly student proof. The file is deleted right after the decision
 * (data minimisation); only status, dates and reviewer remain.
 */
final class ProofService {

	public const SUBDIR       = '/bw-member-proofs';
	public const STASH_PREFIX = 'beitritt';

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly ProofRepository $proofs,
		private readonly MembershipService $memberships,
		private readonly Mailer $mailer,
	) {
	}

	/**
	 * Stores an uploaded proof for a member and notifies the office.
	 *
	 * @param array{name: string, tmp_name: string, size: int, error: int} $file Entry of $_FILES.
	 * @return true|WP_Error
	 */
	public function upload( int $user_id, array $file ): bool|WP_Error {
		$stored = $this->store( $file, 'nachweis-' . $user_id );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}
		$this->attach( $user_id, $stored['file'], $stored['name'] );
		return true;
	}

	/**
	 * Stores the proof of someone joining without an account yet; it is attached after the order (see attach()).
	 * Files that are never attached are removed by cleanup_stash().
	 *
	 * @param array{name: string, tmp_name: string, size: int, error: int} $file Entry of $_FILES.
	 * @return array{file: string, name: string}|WP_Error
	 */
	public function stash( array $file ): array|WP_Error {
		return $this->store( $file, self::STASH_PREFIX );
	}

	/**
	 * Attaches a stored file to a member as "pending" (renamed out of the stash) and notifies the office.
	 */
	public function attach( int $user_id, string $path, string $name ): void {
		if ( ! is_readable( $path ) ) {
			return;
		}
		if ( str_starts_with( basename( $path ), self::STASH_PREFIX ) ) {
			$target = dirname( $path ) . '/nachweis-' . $user_id . '-' . wp_generate_password( 16, false ) . '.' . pathinfo( $path, PATHINFO_EXTENSION );
			if ( rename( $path, $target ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- same protected folder.
				$path = $target;
			}
		}
		$this->delete_file( $user_id );
		$this->proofs->update(
			$user_id,
			array(
				'status'   => 'pending',
				'file'     => $path,
				'name'     => sanitize_file_name( $name ),
				'uploaded' => current_time( 'Y-m-d' ),
				'reason'   => '',
			)
		);
		$user = get_userdata( $user_id );
		$this->mailer->send(
			$this->mailer->office(),
			'Neuer Studiennachweis zur Prüfung',
			sprintf( "%s hat einen Studiennachweis hochgeladen.\n\nPrüfen: %s", $user ? $user->display_name : '#' . $user_id, admin_url( 'admin.php?page=bw-wm-members&path=/proofs' ) )
		);
	}

	/**
	 * Deletes stashed proofs that were not attached to an order within two days.
	 */
	public function cleanup_stash(): int {
		$dir     = (string) wp_upload_dir( null, false )['basedir'] . self::SUBDIR;
		$removed = 0;
		foreach ( (array) glob( $dir . '/' . self::STASH_PREFIX . '*' ) as $file ) {
			if ( is_string( $file ) && filemtime( $file ) < time() - 2 * DAY_IN_SECONDS ) {
				wp_delete_file( $file );
				++$removed;
			}
		}
		return $removed;
	}

	/**
	 * Validates and moves an uploaded file into the protected folder.
	 *
	 * @param array{name: string, tmp_name: string, size: int, error: int} $file Entry of $_FILES.
	 * @return array{file: string, name: string}|WP_Error
	 */
	private function store( array $file, string $prefix ): array|WP_Error {
		if ( UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'bw_wm_proof', 'Die Datei konnte nicht hochgeladen werden.' );
		}
		if ( (int) $file['size'] > ProofRules::MAX_BYTES ) {
			return new WP_Error( 'bw_wm_proof', 'Die Datei ist größer als 5 MB.' );
		}
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], ProofRules::MIME_TYPES );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return new WP_Error( 'bw_wm_proof', 'Bitte lade eine PDF-, JPG- oder PNG-Datei hoch.' );
		}
		$dir    = ProtectedFolder::ensure( (string) wp_upload_dir( null, false )['basedir'], self::SUBDIR );
		$target = $dir . '/' . $prefix . '-' . wp_generate_password( 16, false ) . '.' . $check['ext'];
		if ( ! move_uploaded_file( $file['tmp_name'], $target ) ) {
			return new WP_Error( 'bw_wm_proof', 'Die Datei konnte nicht gespeichert werden.' );
		}
		return array( 'file' => $target, 'name' => sanitize_file_name( $file['name'] ) );
	}

	/**
	 * Approves or rejects a pending proof, deletes the file and informs the member.
	 *
	 * @return true|WP_Error
	 */
	public function decide( int $user_id, bool $approve, string $valid_until, string $reason, int $reviewer ): bool|WP_Error {
		$proof = $this->proofs->get( $user_id );
		if ( 'pending' !== $proof['status'] ) {
			return new WP_Error( 'bw_wm_proof', 'Für dieses Mitglied wartet kein Nachweis auf Prüfung.', array( 'status' => 409 ) );
		}
		$today = current_time( 'Y-m-d' );
		if ( $approve ) {
			$valid_until = 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $valid_until ) ? $valid_until : ProofRules::default_valid_until( $this->memberships->for_user( $user_id )->valid_until, $today );
		}
		$this->delete_file( $user_id );
		$this->proofs->update(
			$user_id,
			array(
				'status'      => $approve ? 'approved' : 'rejected',
				'file'        => '',
				'valid_until' => $approve ? $valid_until : $proof['valid_until'],
				'reviewed'    => $today,
				'reviewer'    => $reviewer,
				'reason'      => $approve ? '' : sanitize_text_field( $reason ),
			)
		);
		$user = get_userdata( $user_id );
		if ( $user ) {
			$area = home_url( '/mitgliederbereich-uebersicht/' );
			$this->mailer->send(
				$user->user_email,
				$approve ? 'Dein Studiennachweis ist bestätigt' : 'Dein Studiennachweis: bitte neu hochladen',
				$approve
					? sprintf( "Hallo %s,\n\ndanke! Dein Studiennachweis ist bestätigt und gilt bis %s.\nDeine studentische Mitgliedschaft bleibt damit bestehen.", $user->first_name ?: $user->display_name, MembershipPeriod::format( $valid_until ) )
					: sprintf( "Hallo %s,\n\nwir konnten deinen Studiennachweis leider nicht bestätigen%s.\nBitte lade eine aktuelle Inskriptionsbestätigung im Mitgliederbereich hoch: %s", $user->first_name ?: $user->display_name, '' !== $reason ? ' (' . sanitize_text_field( $reason ) . ')' : '', $area )
			);
		}
		return true;
	}

	/**
	 * Sends due reminders (daily cron). Returns the number of mails.
	 */
	public function send_reminders(): int {
		$sent  = 0;
		$today = current_time( 'Y-m-d' );
		foreach ( get_users( array( 'fields' => 'ID', 'number' => -1, 'meta_key' => 'bw_wm_type', 'meta_value' => 'student' ) ) as $id ) { // phpcs:ignore WordPress.DB.SlowDBQuery -- few members.
			$user_id    = (int) $id;
			$membership = $this->memberships->for_user( $user_id );
			$proof      = $this->proofs->get( $user_id );
			if ( ! ProofRules::reminder_due( $membership->type, $membership->valid_until, $proof, $today ) ) {
				continue;
			}
			$user = get_userdata( $user_id );
			if ( $user && $this->mailer->send(
				$user->user_email,
				'Bitte Studiennachweis für das nächste Jahr hochladen',
				sprintf( "Hallo %s,\n\ndeine studentische Mitgliedschaft läuft bis %s. Damit sie zum ermäßigten Beitrag weiterläuft, lade bitte eine aktuelle Inskriptionsbestätigung hoch: %s\n\nOhne gültigen Nachweis verlängerst du als ordentliches Mitglied.", $user->first_name ?: $user->display_name, MembershipPeriod::format( $membership->valid_until ), home_url( '/mitgliederbereich-uebersicht/#nachweis' ) )
			) ) {
				$this->proofs->update( $user_id, array( 'reminded' => (string) $membership->valid_until ) );
				++$sent;
			}
		}
		return $sent;
	}

	/**
	 * Absolute path of the pending proof file, or ''.
	 */
	public function file( int $user_id ): string {
		$file = $this->proofs->get( $user_id )['file'];
		return '' !== $file && is_readable( $file ) ? $file : '';
	}

	/**
	 * Removes a stored proof file.
	 */
	private function delete_file( int $user_id ): void {
		$file = $this->proofs->get( $user_id )['file'];
		if ( '' !== $file && file_exists( $file ) ) {
			wp_delete_file( $file );
		}
	}
}
