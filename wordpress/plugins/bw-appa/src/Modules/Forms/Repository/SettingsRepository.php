<?php
/**
 * Form settings.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Repository;

/**
 * Reads and sanitises the form settings (recipient, spam thresholds).
 */
final class SettingsRepository {

	public const OPTION = 'bw_appa_forms_settings';

	/**
	 * Default values.
	 *
	 * @return array{recipient: string, min_seconds: int, max_links: int, max_per_hour: int, blocked_words: string}
	 */
	public function defaults(): array {
		return array(
			'recipient'     => (string) get_option( 'admin_email' ),
			'min_seconds'   => 4,
			'max_links'     => 2,
			'max_per_hour'  => 5,
			'blocked_words' => '',
		);
	}

	/**
	 * Current settings merged with defaults.
	 *
	 * @return array{recipient: string, min_seconds: int, max_links: int, max_per_hour: int, blocked_words: string}
	 */
	public function get(): array {
		$stored = get_option( self::OPTION, array() );
		return $this->sanitize( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Sanitises raw input; unknown keys are dropped.
	 *
	 * @param array<string, mixed> $input Raw values.
	 * @return array{recipient: string, min_seconds: int, max_links: int, max_per_hour: int, blocked_words: string}
	 */
	public function sanitize( array $input ): array {
		$defaults  = $this->defaults();
		$recipient = sanitize_email( (string) ( $input['recipient'] ?? '' ) );
		return array(
			'recipient'     => is_email( $recipient ) ? $recipient : $defaults['recipient'],
			'min_seconds'   => max( 0, min( 60, (int) ( $input['min_seconds'] ?? $defaults['min_seconds'] ) ) ),
			'max_links'     => max( 0, min( 20, (int) ( $input['max_links'] ?? $defaults['max_links'] ) ) ),
			'max_per_hour'  => max( 1, min( 100, (int) ( $input['max_per_hour'] ?? $defaults['max_per_hour'] ) ) ),
			'blocked_words' => sanitize_textarea_field( (string) ( $input['blocked_words'] ?? '' ) ),
		);
	}

	/**
	 * Blocked words as a list.
	 *
	 * @return string[]
	 */
	public function blocked_words(): array {
		return array_values( array_filter( array_map( 'trim', explode( "\n", $this->get()['blocked_words'] ) ) ) );
	}
}
