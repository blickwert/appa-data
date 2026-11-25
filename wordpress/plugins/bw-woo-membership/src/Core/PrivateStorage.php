<?php
/**
 * Storage location of protected files.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Core;

use BW\WooMembership\Support\PrivatePath;
use BW\WooMembership\Support\ProtectedFolder;

/**
 * Where student proofs and member documents are stored. With `BW_WM_PRIVATE_DIR` (wp-config.php) or the filter
 * `bw_wm_private_dir` new files go to `<dir>/proofs` and `<dir>/docs`, ideally outside the web root; otherwise to
 * `uploads/bw-member-proofs` and `uploads/bw-member-docs`. Stored paths are absolute, so older files keep working
 * wherever they are.
 */
final class PrivateStorage {

	/**
	 * Configured private base (normalised), whether or not it is usable.
	 */
	public static function configured(): string {
		$dir = defined( 'BW_WM_PRIVATE_DIR' ) ? (string) constant( 'BW_WM_PRIVATE_DIR' ) : '';

		/**
		 * Filters the private folder for proofs and documents (absolute path, best outside the web root).
		 * Empty: files stay below uploads, protected by .htaccess (Apache) or a server rule (nginx).
		 *
		 * @param string $dir Default: the constant BW_WM_PRIVATE_DIR or ''.
		 */
		return PrivatePath::normalize( (string) apply_filters( 'bw_wm_private_dir', $dir ) );
	}

	/**
	 * Usable private base: configured, existing (created if possible) and writable; '' otherwise.
	 */
	public static function base(): string {
		$dir = self::configured();
		if ( '' === $dir ) {
			return '';
		}
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return is_dir( $dir ) && wp_is_writable( $dir ) ? $dir : '';
	}

	/**
	 * Base folder of uploads (unfiltered by this plugin's document redirect, which only changes the subfolder).
	 */
	public static function uploads_basedir(): string {
		return (string) wp_upload_dir( null, false )['basedir'];
	}

	/**
	 * Folder for new files of a kind (PrivatePath::PROOFS or ::DOCS), created with its deny rules.
	 *
	 * @param string      $kind            Kind of file.
	 * @param string|null $uploads_basedir Uploads base when already known (inside the upload_dir filter).
	 */
	public static function dir( string $kind, ?string $uploads_basedir = null ): string {
		$folder = PrivatePath::folder( $kind, self::base(), $uploads_basedir ?? self::uploads_basedir() );
		return ProtectedFolder::ensure( dirname( $folder ), '/' . basename( $folder ) );
	}

	/**
	 * All existing folders that may hold files of a kind: the current one and the old one below uploads.
	 *
	 * @return string[]
	 */
	public static function folders( string $kind ): array {
		$uploads = self::uploads_basedir();
		$folders = array(
			PrivatePath::folder( $kind, self::base(), $uploads ),
			PrivatePath::folder( $kind, '', $uploads ),
		);
		return array_values( array_filter( array_unique( $folders ), 'is_dir' ) );
	}
}
