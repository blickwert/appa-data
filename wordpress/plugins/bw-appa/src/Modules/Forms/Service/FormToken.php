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
 * A token is only valid for a limited time, so a token copied once cannot be reused by a bot for ever.
 */
final class FormToken {

	/**
	 * Default maximum age of a token in seconds (24 hours).
	 */
	public const MAX_AGE = 86400;

	/**
	 * Builds the token service.
	 *
	 * @param \Closure(): string $secret  Returns the site-specific secret; called lazily because wp_salt() is pluggable.
	 * @param int                $max_age Maximum age of a token in seconds.
	 */
	public function __construct(
		private readonly \Closure $secret,
		private readonly int $max_age = self::MAX_AGE,
	) {
	}

	/**
	 * Token for the given time: "<time>.<signature>".
	 */
	public function create( int $time ): string {
		return $time . '.' . $this->sign( $time );
	}

	/**
	 * The time inside a valid token, or null (wrong signature, or – when `$now` is given – older than the maximum
	 * age or from the future).
	 */
	public function time( string $token, ?int $now = null ): ?int {
		$parts = explode( '.', $token, 2 );
		if ( 2 !== count( $parts ) || ! ctype_digit( $parts[0] ) ) {
			return null;
		}
		$time = (int) $parts[0];
		if ( ! hash_equals( $this->sign( $time ), $parts[1] ) ) {
			return null;
		}
		if ( null !== $now && ( $time > $now || $now - $time > $this->max_age ) ) {
			return null;
		}
		return $time;
	}

	/**
	 * HMAC of the time.
	 */
	private function sign( int $time ): string {
		return substr( hash_hmac( 'sha256', 'bw_appa_form|' . $time, ( $this->secret )() ), 0, 20 );
	}
}
