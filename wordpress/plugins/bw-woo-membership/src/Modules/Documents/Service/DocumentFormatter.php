<?php
/**
 * Document labels.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Documents\Service;

/**
 * Formats file type and size for the document list ("PDF · 4,2 MB").
 */
final class DocumentFormatter {

	/**
	 * German size label.
	 */
	public static function size( int $bytes ): string {
		if ( $bytes <= 0 ) {
			return '';
		}
		if ( $bytes < 1024 * 1024 ) {
			return max( 1, (int) round( $bytes / 1024 ) ) . ' KB';
		}
		return str_replace( '.', ',', (string) round( $bytes / ( 1024 * 1024 ), 1 ) ) . ' MB';
	}

	/**
	 * Upper-case extension of a file name, e.g. "PDF".
	 */
	public static function type( string $filename ): string {
		return strtoupper( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );
	}

	/**
	 * Combined label.
	 */
	public static function label( string $filename, int $bytes ): string {
		return implode( ' · ', array_filter( array( self::type( $filename ), self::size( $bytes ) ) ) );
	}
}
