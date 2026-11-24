<?php
/**
 * Submission counter.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Repository;

/**
 * Counts submissions per client and hour in transients. The client is stored only as a salted hash.
 */
final class RateLimitRepository {

	/**
	 * Submissions of a client in the current hour.
	 */
	public function count( string $client ): int {
		return (int) get_transient( $this->key( $client ) );
	}

	/**
	 * Adds one submission.
	 */
	public function increment( string $client ): void {
		set_transient( $this->key( $client ), $this->count( $client ) + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Transient key from a hash of the client.
	 */
	private function key( string $client ): string {
		return 'bw_appa_rl_' . substr( hash_hmac( 'sha256', $client . '|' . gmdate( 'YmdH' ), wp_salt( 'nonce' ) ), 0, 24 );
	}
}
