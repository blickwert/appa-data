<?php
/**
 * Spam checks.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Service;

/**
 * Decides whether a submission looks like spam. Pure logic: all inputs are passed in.
 */
final class SpamGuard {

	/**
	 * Builds the guard with its thresholds.
	 *
	 * @param int      $min_seconds Minimum time between page load and submit.
	 * @param int      $max_links   Maximum number of links in all text fields.
	 * @param int      $max_per_hour Maximum submissions per client and hour.
	 * @param string[] $blocked_words Words that mark a submission as spam (case-insensitive).
	 */
	public function __construct(
		private readonly int $min_seconds = 4,
		private readonly int $max_links = 2,
		private readonly int $max_per_hour = 5,
		private readonly array $blocked_words = array(),
	) {
	}

	/**
	 * Returns the reason a submission is spam, or null when it passes.
	 *
	 * @param string   $honeypot       Value of the hidden honeypot field (must be empty).
	 * @param int|null $started_at     Unix time when the form was rendered (null = missing/invalid token).
	 * @param int      $now            Current Unix time.
	 * @param string[] $texts          All free-text values of the submission.
	 * @param int      $recent_submits Submissions of this client in the last hour.
	 */
	public function reason( string $honeypot, ?int $started_at, int $now, array $texts, int $recent_submits ): ?string {
		if ( '' !== trim( $honeypot ) ) {
			return 'honeypot';
		}
		if ( null === $started_at || $now - $started_at < $this->min_seconds ) {
			return 'too_fast';
		}
		if ( $recent_submits >= $this->max_per_hour ) {
			return 'rate_limit';
		}
		$text = mb_strtolower( implode( "\n", $texts ) );
		if ( preg_match_all( '#https?://|www\.#i', $text ) > $this->max_links ) {
			return 'links';
		}
		foreach ( $this->blocked_words as $word ) {
			$word = mb_strtolower( trim( $word ) );
			if ( '' !== $word && str_contains( $text, $word ) ) {
				return 'blocked_word';
			}
		}
		return null;
	}
}
