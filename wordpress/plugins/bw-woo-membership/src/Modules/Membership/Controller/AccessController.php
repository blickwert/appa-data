<?php
/**
 * Access to the member area.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Service\MemberPages;
use BW\WooMembership\Modules\Membership\Service\MembershipService;
use BW\WooMembership\Support\NoCache;
use WP_User;

/**
 * Protects the member area: guests go to the login page, members land on the overview after logging in.
 */
final class AccessController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly MembershipService $memberships,
		private readonly MemberPages $pages,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'guard' ) );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 20, 3 );
		add_filter( 'woocommerce_login_redirect', array( $this, 'woocommerce_login_redirect' ), 20, 2 );
		add_filter( 'bw_wm_user_has_access', array( $this, 'user_has_access' ), 10, 2 );
		add_filter( 'bw_wm_user_is_member', array( $this, 'user_is_member' ), 10, 2 );
	}

	/**
	 * Guests on the overview are sent to the login page; logged-in users on the login page to the overview.
	 */
	public function guard(): void {
		if ( ! is_singular( 'page' ) ) {
			return;
		}
		$page_id = (int) get_queried_object_id();
		if ( $this->pages->is_protected( $page_id ) || $page_id === $this->pages->login_page_id() ) {
			NoCache::send(); // Personal content or a redirect that depends on the login.
		}
		if ( $this->pages->is_protected( $page_id ) && ! is_user_logged_in() ) {
			wp_safe_redirect( $this->pages->login_url( (string) get_permalink( $page_id ) ) );
			exit;
		}
		if ( $page_id === $this->pages->login_page_id() && is_user_logged_in() && '' !== $this->pages->dashboard_url() ) {
			wp_safe_redirect( $this->pages->dashboard_url() );
			exit;
		}
	}

	/**
	 * Members (not staff) land on the overview after logging in via wp-login.php.
	 *
	 * @param string           $redirect_to Requested target.
	 * @param string           $requested   Raw requested target.
	 * @param WP_User|\WP_Error $user        User or error.
	 */
	public function login_redirect( string $redirect_to, string $requested, $user ): string {
		if ( ! $user instanceof WP_User || user_can( $user, 'edit_posts' ) ) {
			return $redirect_to;
		}
		$wants_admin = '' === $requested || str_contains( $requested, '/wp-admin' );
		return $wants_admin && '' !== $this->pages->dashboard_url() ? $this->pages->dashboard_url() : $redirect_to;
	}

	/**
	 * Same for the WooCommerce login form ("Mein Konto").
	 */
	public function woocommerce_login_redirect( string $redirect, $user ): string {
		if ( ! $user instanceof WP_User || user_can( $user, 'edit_posts' ) ) {
			return $redirect;
		}
		return 'none' !== $this->memberships->for_user( $user->ID )->status && '' !== $this->pages->dashboard_url() ? $this->pages->dashboard_url() : $redirect;
	}

	/**
	 * Answers `bw_wm_user_has_access` for other modules: active members and site staff.
	 */
	public function user_has_access( bool $has_access, int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		return $has_access || user_can( $user_id, 'edit_pages' ) || $this->memberships->for_user( $user_id )->grants_access();
	}

	/**
	 * Answers `bw_wm_user_is_member` for other modules: active membership only (no staff exception).
	 */
	public function user_is_member( bool $is_member, int $user_id ): bool {
		return $is_member || ( $user_id > 0 && $this->memberships->for_user( $user_id )->grants_access() );
	}
}
