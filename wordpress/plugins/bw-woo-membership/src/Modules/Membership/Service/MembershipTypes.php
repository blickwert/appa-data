<?php
/**
 * Membership types.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

/**
 * The membership types of the APPA (meeting 9 October 2026).
 */
final class MembershipTypes {

	/**
	 * Labels keyed by type.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		$types = array(
			'regular'       => __( 'Ordentliche Mitgliedschaft', 'bw-woo-membership' ),
			'student'       => __( 'Studentische Mitgliedschaft', 'bw-woo-membership' ),
			'honorary'      => __( 'Ehrenmitgliedschaft', 'bw-woo-membership' ),
			'institutional' => __( 'Institutionelle Mitgliedschaft', 'bw-woo-membership' ),
		);

		/**
		 * Filters the membership types.
		 *
		 * @param array<string, string> $types Labels keyed by type.
		 */
		return (array) apply_filters( 'bw_wm_membership_types', $types );
	}

	/**
	 * Label of a type, empty for unknown types.
	 */
	public static function label( string $type ): string {
		return self::all()[ $type ] ?? '';
	}

	/**
	 * Whether the type exists.
	 */
	public static function exists( string $type ): bool {
		return isset( self::all()[ $type ] );
	}
}
