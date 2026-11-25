<?php
/**
 * Pages of the member area.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

/**
 * Finds the login page and the overview: by default the pages using the theme templates "page-member" and
 * "page-member-dashboard"; any page containing a member block is protected as well.
 */
final class MemberPages {

	/**
	 * Blocks that need a logged-in user.
	 */
	public const PROTECTED_BLOCKS = array( 'bw-woo-membership/overview', 'bw-woo-membership/events', 'bw-woo-membership/documents', 'bw-woo-membership/profile', 'bw-woo-membership/proof' );

	/**
	 * ID of the login page (0 = none).
	 */
	public function login_page_id(): int {
		/**
		 * Filters the ID of the member login page.
		 *
		 * @param int $page_id Found page ID or 0.
		 */
		return (int) apply_filters( 'bw_wm_login_page_id', $this->page_with_template( 'page-member' ) );
	}

	/**
	 * ID of the overview page (0 = none).
	 */
	public function dashboard_page_id(): int {
		/**
		 * Filters the ID of the member overview page.
		 *
		 * @param int $page_id Found page ID or 0.
		 */
		return (int) apply_filters( 'bw_wm_dashboard_page_id', $this->page_with_template( 'page-member-dashboard' ) );
	}

	/**
	 * URL of the overview or empty string.
	 */
	public function dashboard_url(): string {
		$id = $this->dashboard_page_id();
		return $id ? (string) get_permalink( $id ) : '';
	}

	/**
	 * Login URL that returns to `$return_to` afterwards.
	 */
	public function login_url( string $return_to ): string {
		$id = $this->login_page_id();
		if ( ! $id ) {
			return wp_login_url( $return_to );
		}
		$url = (string) get_permalink( $id );
		return '' === $return_to ? $url : add_query_arg( 'redirect_to', rawurlencode( $return_to ), $url );
	}

	/**
	 * Validated return address from the login page's `redirect_to` parameter: only addresses on this site,
	 * never the login page itself; otherwise an empty string.
	 */
	public function safe_return_url( string $requested ): string {
		$url = '' === $requested ? '' : wp_validate_redirect( $requested, '' );
		if ( '' === $url ) {
			return '';
		}
		// `allowed_redirect_hosts` may widen wp_validate_redirect(); the login page only returns to its own host.
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( is_string( $host ) && strtolower( $host ) !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return '';
		}
		$login_id = $this->login_page_id();
		$login    = $login_id ? (string) get_permalink( $login_id ) : '';
		$path     = static fn( string $address ): string => untrailingslashit( (string) wp_parse_url( $address, PHP_URL_PATH ) );
		return '' !== $login && $path( $url ) === $path( $login ) ? '' : $url;
	}

	/**
	 * Address of the member area overview, optionally with an anchor (e.g. "nachweis").
	 */
	public static function area_url( string $anchor = '' ): string {
		return self::page_url( 'area', '/mitgliederbereich-uebersicht/' ) . ( '' !== $anchor ? '#' . $anchor : '' );
	}

	/**
	 * Address of the page that explains the membership and links to joining.
	 */
	public static function membership_url(): string {
		return self::page_url( 'membership', '/mitgliedschaft/' );
	}

	/**
	 * Address of the event list.
	 */
	public static function events_url(): string {
		return self::page_url( 'events', '/veranstaltungen/' );
	}

	/**
	 * Address of a fixed page of the website; the paths can be changed with a filter.
	 */
	private static function page_url( string $page, string $path ): string {
		/**
		 * Filters the address of a fixed page the plugin links to.
		 *
		 * @param string $url  Address (default: home_url( $path )).
		 * @param string $page "area" (member area overview), "membership" or "events".
		 */
		return (string) apply_filters( 'bw_wm_page_url', home_url( $path ), $page );
	}

	/**
	 * Whether a page belongs to the protected member area.
	 */
	public function is_protected( int $page_id ): bool {
		if ( $page_id <= 0 ) {
			return false;
		}
		if ( $page_id === $this->dashboard_page_id() ) {
			return true;
		}
		$content = (string) get_post_field( 'post_content', $page_id );
		foreach ( self::PROTECTED_BLOCKS as $block ) {
			if ( has_block( $block, $content ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * First published page using a page template.
	 */
	private function page_with_template( string $template ): int {
		$ids = get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'meta_key'               => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- few pages, cached.
				'meta_value'             => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'                 => 'ids',
				'numberposts'            => 1,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);
		return (int) ( $ids[0] ?? 0 );
	}
}
