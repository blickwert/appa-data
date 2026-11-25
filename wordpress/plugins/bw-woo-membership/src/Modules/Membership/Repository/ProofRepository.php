<?php
/**
 * Student proofs.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Repository;

/**
 * Stores the state of a member's student proof (user meta). The file itself is deleted after the decision.
 */
final class ProofRepository {

	public const META = 'bw_wm_proof';

	/**
	 * Proof state.
	 *
	 * @return array{status: string, file: string, name: string, uploaded: string, valid_until: string, reviewed: string, reviewer: int, reason: string, reminded: string}
	 */
	public function get( int $user_id ): array {
		$stored = get_user_meta( $user_id, self::META, true );
		$stored = is_array( $stored ) ? $stored : array();
		return array(
			'status'      => (string) ( $stored['status'] ?? 'none' ),
			'file'        => (string) ( $stored['file'] ?? '' ),
			'name'        => (string) ( $stored['name'] ?? '' ),
			'uploaded'    => (string) ( $stored['uploaded'] ?? '' ),
			'valid_until' => (string) ( $stored['valid_until'] ?? '' ),
			'reviewed'    => (string) ( $stored['reviewed'] ?? '' ),
			'reviewer'    => (int) ( $stored['reviewer'] ?? 0 ),
			'reason'      => (string) ( $stored['reason'] ?? '' ),
			'reminded'    => (string) ( $stored['reminded'] ?? '' ),
		);
	}

	/**
	 * Saves changed values (merged into the stored state).
	 *
	 * @param array<string, string|int> $changes Values to change.
	 */
	public function update( int $user_id, array $changes ): void {
		update_user_meta( $user_id, self::META, array_merge( $this->get( $user_id ), $changes ) );
	}

	/**
	 * Users with a proof waiting for review.
	 *
	 * @return int[]
	 */
	public function pending_user_ids(): array {
		$ids = array();
		foreach ( get_users( array( 'fields' => 'ID', 'number' => -1, 'meta_key' => self::META ) ) as $id ) { // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- few users.
			if ( 'pending' === $this->get( (int) $id )['status'] ) {
				$ids[] = (int) $id;
			}
		}
		return $ids;
	}
}
