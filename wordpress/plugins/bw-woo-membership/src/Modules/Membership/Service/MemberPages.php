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
	public const PROTECTED_BLOCKS = array( 'bw-woo-membership/overview', 'bw-woo-membership/events', 'bw-woo-membership/documents', 'bw-woo-membership/profile' );

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
		return $id ? (string) get_permalink( $id ) : wp_login_url( $return_to );
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
