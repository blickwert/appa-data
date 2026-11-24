<?php
/**
 * Field normalisation.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Service;

/**
 * Normalises raw meta values; used when saving and when reading.
 */
final class EventFields {

	/**
	 * A valid Y-m-d date or ''.
	 */
	public static function date( string $value ): string {
		$value = trim( $value );
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
			return '';
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $value : '';
	}

	/**
	 * A valid H:i time or ''.
	 */
	public static function time( string $value ): string {
		$value = trim( $value );
		if ( 1 !== preg_match( '/^(\d{1,2}):(\d{2})/', $value, $m ) || (int) $m[1] > 23 || (int) $m[2] > 59 ) {
			return '';
		}
		return sprintf( '%02d:%02d', (int) $m[1], (int) $m[2] );
	}

	/**
	 * An http(s) URL or ''.
	 */
	public static function url( string $value ): string {
		$value = trim( $value );
		return 1 === preg_match( '#^https?://[^\s]+$#i', $value ) ? $value : '';
	}

	/**
	 * Single-line plain text.
	 */
	public static function text( string $value ): string {
		return trim( (string) preg_replace( '/\s+/', ' ', strip_tags( $value ) ) );
	}

	/**
	 * Kind of event: one of EventRepository::AUDIENCES, default "open".
	 */
	public static function audience( string $value ): string {
		$value = trim( $value );
		return in_array( $value, array( 'open', 'members', 'free_members' ), true ) ? $value : 'open';
	}

	/**
	 * Price as decimal string with dot ("35.00"), accepts a comma; '' when empty or invalid.
	 */
	public static function price( string $value ): string {
		$value = str_replace( array( ' ', '€' ), '', trim( $value ) );
		$value = str_replace( ',', '.', $value );
		if ( '' === $value || 1 !== preg_match( '/^\d{1,5}(\.\d{1,2})?$/', $value ) ) {
			return '';
		}
		return number_format( (float) $value, 2, '.', '' );
	}

	/**
	 * Number of places as string ('' = unlimited).
	 */
	public static function capacity( string $value ): string {
		$value = trim( $value );
		return 1 === preg_match( '/^\d{1,5}$/', $value ) && (int) $value > 0 ? (string) (int) $value : '';
	}

	/**
	 * On/off flag as '1' or ''.
	 */
	public static function flag( string $value ): string {
		return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'on' ), true ) ? '1' : '';
	}
}
