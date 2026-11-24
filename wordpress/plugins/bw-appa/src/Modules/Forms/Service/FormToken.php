<?php
/**
 * Signed render time.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Service;

/**
 * Creates and checks a signed token with the time the form was rendered, so the minimum fill time cannot be faked.
 */
final class FormToken {

	/**
	 * Builds the token service.
	 *
	 * @param \Closure(): string $secret Returns the site-specific secret; called lazily because wp_salt() is pluggable.
	 */
	public function __construct( private readonly \Closure $secret ) {
	}

	/**
	 * Token for the given time: "<time>.<signature>".
	 */
	public function create( int $time ): string {
		return $time . '.' . $this->sign( $time );
	}

	/**
	 * The time inside a valid token, or null.
	 */
	public function time( string $token ): ?int {
		$parts = explode( '.', $token, 2 );
		if ( 2 !== count( $parts ) || ! ctype_digit( $parts[0] ) ) {
			return null;
		}
		$time = (int) $parts[0];
		return hash_equals( $this->sign( $time ), $parts[1] ) ? $time : null;
	}

	/**
	 * HMAC of the time.
	 */
	private function sign( int $time ): string {
		return substr( hash_hmac( 'sha256', 'bw_appa_form|' . $time, ( $this->secret )() ), 0, 20 );
	}
}
