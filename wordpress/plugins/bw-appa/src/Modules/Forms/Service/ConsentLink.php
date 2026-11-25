<?php
/**
 * Link in the consent text.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Service;

/**
 * Turns the word "Datenschutzhinweise" in the consent paragraph of a form into a link to the privacy page.
 *
 * The form markup is stored in CF7 when the form is created, so the link is added at render time and
 * always points to the current privacy page.
 */
final class ConsentLink {

	public const WORD = 'Datenschutzhinweise';

	/**
	 * Returns the form HTML with the first mention in the consent paragraph linked; unchanged if the
	 * paragraph already contains a link, has no mention or the URL is empty.
	 */
	public static function add( string $html, string $url ): string {
		if ( '' === $url ) {
			return $html;
		}
		return (string) preg_replace_callback(
			'#<p\b[^>]*\bbw-form__consent\b[^>]*>.*?</p>#s',
			static function ( array $m ) use ( $url ): string {
				if ( str_contains( $m[0], '<a ' ) ) {
					return $m[0];
				}
				// Opens in a new tab so the half-filled form stays.
				$link = sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' ), self::WORD );
				return (string) preg_replace( '/' . self::WORD . '/', $link, $m[0], 1 );
			},
			$html,
			1
		);
	}
}
