<?php
/**
 * Mail sender header.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Service;

/**
 * Cleans the display name of a "Name <address>" sender.
 *
 * CF7 builds the sender from the site title. Characters such as "@", quotes or angle brackets in that
 * name make some hosts' mail() reject the message ("Could not instantiate mail function"),
 * e.g. for a dev site titled "@dev 2603b-appa". Only letters, digits, spaces and . - – & ' remain.
 */
final class MailSender {

	/**
	 * Returns the sender with a safe display name; the address part is left unchanged.
	 *
	 * @param string $sender   Sender as "Name <address>" or a bare address.
	 * @param string $fallback Name used when nothing remains after cleaning.
	 */
	public static function clean( string $sender, string $fallback = 'APPA' ): string {
		if ( 1 !== preg_match( '/^(.*)<([^<>]+)>\s*$/s', trim( $sender ), $m ) ) {
			return $sender;
		}
		$name = preg_replace( "/[^\p{L}\p{N} .\-–&']/u", '', $m[1] ) ?? '';
		$name = trim( (string) preg_replace( '/\s+/u', ' ', $name ), " .-–&'" );
		if ( '' === $name ) {
			$name = $fallback;
		}
		return $name . ' <' . trim( $m[2] ) . '>';
	}
}
