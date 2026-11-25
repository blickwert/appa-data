<?php
/**
 * Upload counter per client.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Repository;

/**
 * Counts guest uploads of the student proof per hashed IP address for one hour (transient; the address
 * itself is not stored).
 */
final class UploadLimitRepository {

	private const PREFIX = 'bw_wm_proof_uploads_';

	/**
	 * Uploads of this client within the current hour.
	 */
	public function count( string $client ): int {
		return (int) get_transient( $this->key( $client ) );
	}

	/**
	 * Counts one more upload; the hour starts again with every upload.
	 */
	public function increment( string $client ): void {
		$key   = $this->key( $client );
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Transient key from a salted hash of the client address.
	 */
	private function key( string $client ): string {
		return self::PREFIX . substr( hash_hmac( 'sha256', $client, wp_salt( 'nonce' ) ), 0, 32 );
	}
}
