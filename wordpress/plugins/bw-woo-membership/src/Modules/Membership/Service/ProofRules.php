<?php
/**
 * Student proof rules.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

/**
 * Rules for the yearly student proof (meeting 9 October 2026): a valid proof is required to renew as a student;
 * new members may buy the student membership and upload the proof afterwards (docs/OFFENE-FRAGEN.md, M2).
 */
final class ProofRules {

	public const REMIND_DAYS = 28;

	/**
	 * Allowed file types and maximum size of an upload.
	 */
	public const MIME_TYPES = array(
		'pdf'      => 'application/pdf',
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
	);
	public const MAX_BYTES  = 5242880;

	/**
	 * Whether an approved proof covers `$day` (Y-m-d).
	 *
	 * @param array{status: string, valid_until: string} $proof Proof state.
	 */
	public static function valid_on( array $proof, string $day ): bool {
		return 'approved' === $proof['status'] && '' !== $proof['valid_until'] && $day <= $proof['valid_until'];
	}

	/**
	 * Whether the user may buy the student membership: new members always, existing members only with a proof that
	 * covers the start of the next period.
	 *
	 * @param array{status: string, valid_until: string} $proof Proof state.
	 */
	public static function may_buy_student( bool $is_existing_member, array $proof, string $next_period_start ): bool {
		return ! $is_existing_member || self::valid_on( $proof, $next_period_start );
	}

	/**
	 * Whether a reminder is due: student member, period ends within REMIND_DAYS, no proof for the next period,
	 * not yet reminded for this period end.
	 *
	 * @param array{status: string, valid_until: string, reminded: string} $proof Proof state.
	 */
	public static function reminder_due( string $type, ?string $period_end, array $proof, string $today ): bool {
		if ( 'student' !== $type || null === $period_end || $today > $period_end || $proof['reminded'] === $period_end ) {
			return false;
		}
		$next_start = ( new \DateTimeImmutable( $period_end ) )->modify( '+1 day' )->format( 'Y-m-d' );
		if ( self::valid_on( $proof, $next_start ) || 'pending' === $proof['status'] ) {
			return false;
		}
		$days_left = (int) ( new \DateTimeImmutable( $today ) )->diff( new \DateTimeImmutable( $period_end ) )->format( '%a' );
		return $days_left <= self::REMIND_DAYS;
	}

	/**
	 * Membership type a subscription renews as on `$day` (answer M4): students without a proof for that day renew
	 * as regular members; a proof still under review keeps the current type; regular members with an approved proof
	 * renew as students. Other types stay as they are.
	 *
	 * @param array{status: string, valid_until: string} $proof Proof state.
	 */
	public static function renewal_type( string $type, array $proof, string $day ): string {
		if ( ! in_array( $type, array( 'student', 'regular' ), true ) || 'pending' === $proof['status'] ) {
			return $type;
		}
		return self::valid_on( $proof, $day ) ? 'student' : 'regular';
	}

	/**
	 * Default "valid until" for an approval: end of the member's current period, else end of this calendar year.
	 */
	public static function default_valid_until( ?string $period_end, string $today ): string {
		return null !== $period_end && $period_end >= $today ? $period_end : substr( $today, 0, 4 ) . '-12-31';
	}
}
