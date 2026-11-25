<?php
/**
 * Path rules for protected files.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Support;

/**
 * Pure path helpers: where proofs and documents live (private base outside the web root or the old folders below
 * uploads) and how stored paths are resolved. No WordPress calls, so it can be tested directly.
 */
final class PrivatePath {

	public const PROOFS = 'proofs';
	public const DOCS   = 'docs';

	/**
	 * Folders below uploads used before a private base existed (and still used without one).
	 */
	public const UPLOAD_SUBDIRS = array(
		self::PROOFS => '/bw-member-proofs',
		self::DOCS   => '/bw-member-docs',
	);

	/**
	 * Absolute path without trailing slash and with forward slashes, or '' when the value is not absolute.
	 */
	public static function normalize( string $dir ): string {
		$dir = str_replace( '\\', '/', trim( $dir ) );
		if ( ! self::is_absolute( $dir ) ) {
			return '';
		}
		$trimmed = rtrim( $dir, '/' );
		return '' === $trimmed || 1 === preg_match( '#^[A-Za-z]:$#', $trimmed ) ? $trimmed . '/' : $trimmed;
	}

	/**
	 * Whether a path is absolute (Unix, Windows drive or UNC).
	 */
	public static function is_absolute( string $path ): bool {
		return str_starts_with( $path, '/' ) || str_starts_with( $path, '\\' ) || 1 === preg_match( '#^[A-Za-z]:[/\\\\]#', $path );
	}

	/**
	 * Folder for new files of a kind: below the private base when one is set, else the old folder below uploads.
	 */
	public static function folder( string $kind, string $private_base, string $uploads_basedir ): string {
		$base = self::normalize( $private_base );
		if ( '' !== $base ) {
			return rtrim( $base, '/' ) . '/' . self::kind( $kind );
		}
		return rtrim( str_replace( '\\', '/', $uploads_basedir ), '/' ) . self::UPLOAD_SUBDIRS[ self::kind( $kind ) ];
	}

	/**
	 * Absolute path of a stored file: absolute paths stay, relative ones belong below uploads (as WordPress stores
	 * attachments). Empty stays empty.
	 */
	public static function resolve( string $stored, string $uploads_basedir ): string {
		if ( '' === $stored || self::is_absolute( $stored ) ) {
			return $stored;
		}
		return rtrim( $uploads_basedir, '/\\' ) . '/' . ltrim( $stored, '/\\' );
	}

	/**
	 * Part of a path below a root ('' for the root itself), or null when the path is not inside it.
	 */
	public static function relative( string $path, string $root ): ?string {
		$path = self::normalize( $path );
		$root = self::normalize( $root );
		if ( '' === $path || '' === $root || str_contains( $path, '/../' ) || str_ends_with( $path, '/..' ) ) {
			return null;
		}
		if ( $path === $root ) {
			return '';
		}
		$prefix = rtrim( $root, '/' ) . '/';
		return str_starts_with( $path, $prefix ) ? substr( $path, strlen( $prefix ) ) : null;
	}

	/**
	 * Whether a path lies inside (or is) a root folder.
	 */
	public static function is_inside( string $path, string $root ): bool {
		return null !== self::relative( $path, $root );
	}

	/**
	 * Known kind, defaults to documents.
	 */
	private static function kind( string $kind ): string {
		return self::PROOFS === $kind ? self::PROOFS : self::DOCS;
	}
}
