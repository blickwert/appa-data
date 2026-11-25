<?php
/**
 * Profile fields.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

/**
 * Cleans the directory profile a member submits (meeting 2 October 2026: title, website, focus, groups, short profile).
 */
final class ProfileFields {

	/**
	 * Specialist groups of the APPA.
	 *
	 * @return array<string, string>
	 */
	public static function groups(): array {
		return array(
			'coaching'   => __( 'Positive Coaching', 'bw-woo-membership' ),
			'clinical'   => __( 'Positive Clinical Psychology', 'bw-woo-membership' ),
			'health'     => __( 'Positive Health', 'bw-woo-membership' ),
			'education'  => __( 'Positive Education', 'bw-woo-membership' ),
			'leadership' => __( 'Positive Leadership', 'bw-woo-membership' ),
			'inclusion'  => __( 'Positive Inclusion', 'bw-woo-membership' ),
		);
	}

	/**
	 * Sanitises raw input; unknown groups are dropped, texts are limited in length.
	 *
	 * @param array<string, mixed> $input Raw values.
	 * @return array{title: string, website: string, focus: string, bio: string, groups: string[]}
	 */
	public static function sanitize( array $input ): array {
		$text    = static fn( string $key, int $max ): string => mb_substr( sanitize_text_field( (string) ( $input[ $key ] ?? '' ) ), 0, $max );
		$website = esc_url_raw( (string) ( $input['website'] ?? '' ), array( 'http', 'https' ) );
		$groups  = array_values( array_intersect( array_keys( self::groups() ), array_map( 'strval', (array) ( $input['groups'] ?? array() ) ) ) );
		return array(
			'title'   => $text( 'title', 80 ),
			'website' => $website,
			'focus'   => $text( 'focus', 200 ),
			'bio'     => mb_substr( sanitize_textarea_field( (string) ( $input['bio'] ?? '' ) ), 0, 1200 ),
			'groups'  => $groups,
		);
	}
}
