<?php
/**
 * Renaming of old CSS classes in form markup.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Service;

/**
 * Up to 0.5.x the form classes used the prefix `bwa-` (e.g. `bwa-form__field`); since 0.6.0 they use `bw-`
 * (`bw-form__field`). CF7 stores the form markup when the form is created, so forms created earlier still
 * carry the old names. This maps them to the new names: once in the stored markup and, as a fallback, when
 * a form is rendered.
 */
final class LegacyClasses {

	private const PATTERN = '/(?<![\w-])bwa-(?=form|cf7)/';

	/**
	 * Whether the markup still contains an old class name.
	 */
	public static function found( string $markup ): bool {
		return 1 === preg_match( self::PATTERN, $markup );
	}

	/**
	 * Replaces `bwa-form…` and `bwa-cf7` by `bw-form…` and `bw-cf7`; everything else stays unchanged.
	 */
	public static function rename( string $markup ): string {
		return (string) preg_replace( self::PATTERN, 'bw-', $markup );
	}
}
