<?php
/**
 * Member data.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Repository;

/**
 * Reads and writes the member's own data (user meta) and the member number sequence.
 */
final class MemberRepository {

	public const META_NUMBER       = 'bw_wm_number';
	public const META_SINCE        = 'bw_wm_since';
	public const META_MANUAL_TYPE  = 'bw_wm_manual_type';
	public const META_MANUAL_UNTIL = 'bw_wm_manual_until';
	public const META_DIRECTORY    = 'bw_wm_directory';
	public const META_CONSENT      = 'bw_wm_directory_consent';
	public const META_PROFILE      = 'bw_wm_profile';
	public const OPTION_SEQUENCE   = 'bw_wm_number_sequence';

	/**
	 * Member number or empty string.
	 */
	public function number( int $user_id ): string {
		return (string) get_user_meta( $user_id, self::META_NUMBER, true );
	}

	/**
	 * Day (Y-m-d) the user first became a member, or null.
	 */
	public function since( int $user_id ): ?string {
		$since = (string) get_user_meta( $user_id, self::META_SINCE, true );
		return '' !== $since ? $since : null;
	}

	/**
	 * Assigns number and start date once; both stay when a member leaves and rejoins.
	 */
	public function ensure_number( int $user_id, string $formatted_next, string $today ): void {
		if ( '' === $this->number( $user_id ) ) {
			update_user_meta( $user_id, self::META_NUMBER, $formatted_next );
			update_option( self::OPTION_SEQUENCE, $this->sequence() + 1, false );
		}
		if ( null === $this->since( $user_id ) ) {
			update_user_meta( $user_id, self::META_SINCE, $today );
		}
	}

	/**
	 * Last assigned sequence value.
	 */
	public function sequence(): int {
		return (int) get_option( self::OPTION_SEQUENCE, 0 );
	}

	/**
	 * Manually managed membership (honorary, institutional): type and last day, or null.
	 *
	 * @return array{type: string, until: string}|null
	 */
	public function manual( int $user_id ): ?array {
		$type = (string) get_user_meta( $user_id, self::META_MANUAL_TYPE, true );
		if ( '' === $type ) {
			return null;
		}
		return array(
			'type'  => $type,
			'until' => (string) get_user_meta( $user_id, self::META_MANUAL_UNTIL, true ),
		);
	}

	/**
	 * Whether the member agreed to be listed in the public directory.
	 */
	public function in_directory( int $user_id ): bool {
		return 'yes' === get_user_meta( $user_id, self::META_DIRECTORY, true );
	}

	/**
	 * Stores the directory choice; the consent date documents the opt-in (GDPR).
	 */
	public function set_directory( int $user_id, bool $visible, string $today ): void {
		update_user_meta( $user_id, self::META_DIRECTORY, $visible ? 'yes' : 'no' );
		if ( $visible ) {
			update_user_meta( $user_id, self::META_CONSENT, $today );
		} else {
			delete_user_meta( $user_id, self::META_CONSENT );
		}
	}

	/**
	 * Directory profile fields.
	 *
	 * @return array{title: string, website: string, focus: string, bio: string, groups: string[]}
	 */
	public function profile( int $user_id ): array {
		$stored = get_user_meta( $user_id, self::META_PROFILE, true );
		$stored = is_array( $stored ) ? $stored : array();
		return array(
			'title'   => (string) ( $stored['title'] ?? '' ),
			'website' => (string) ( $stored['website'] ?? '' ),
			'focus'   => (string) ( $stored['focus'] ?? '' ),
			'bio'     => (string) ( $stored['bio'] ?? '' ),
			'groups'  => array_values( array_map( 'strval', (array) ( $stored['groups'] ?? array() ) ) ),
		);
	}

	/**
	 * Saves sanitised profile fields.
	 *
	 * @param array{title: string, website: string, focus: string, bio: string, groups: string[]} $profile Clean values.
	 */
	public function save_profile( int $user_id, array $profile ): void {
		update_user_meta( $user_id, self::META_PROFILE, $profile );
	}
}
