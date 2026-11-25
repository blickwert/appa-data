<?php
/**
 * Keeps personal responses out of page caches.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Support;

/**
 * The hosting page cache does not skip logged-in users (answer T2, 24 November 2026). Responses with personal
 * content therefore tell every common cache not to store them: WordPress cache plugins (DONOTCACHEPAGE), browsers
 * and proxies (Cache-Control) and nginx FastCGI cache (X-Accel-Expires).
 */
final class NoCache {

	/**
	 * Marks the current response as not cacheable.
	 */
	public static function send(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- constant read by caching plugins.
		}
		if ( headers_sent() ) {
			return;
		}
		nocache_headers();
		header( 'X-Accel-Expires: 0' );
	}
}
