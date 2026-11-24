<?php
/**
 * Protected upload folder.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Support;

/**
 * Folders below wp-content/uploads that are closed for direct requests (member documents, student proofs).
 */
final class ProtectedFolder {

	public const SUBDIR = '/bw-member-docs';

	/**
	 * Creates the folder with deny rules (Apache) and an empty index. nginx needs its own rule, see readme.
	 */
	public static function ensure( string $basedir, string $subdir = self::SUBDIR ): string {
		$dir = $basedir . $subdir;
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$files = array(
			'.htaccess'  => "# Member documents are only served through the plugin.\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
			'index.html' => '',
		);
		foreach ( $files as $name => $content ) {
			if ( ! file_exists( $dir . '/' . $name ) ) {
				file_put_contents( $dir . '/' . $name, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- small static files at setup.
			}
		}
		return $dir;
	}
}
