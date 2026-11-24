<?php
/**
 * Profile form.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Service\ProfileFields;

/**
 * Saves the member's directory profile and visibility (normal form post with nonce).
 */
final class ProfileController {

	public const ACTION = 'bw_wm_profile';

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly MemberRepository $members ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'save' ) );
	}

	/**
	 * Handles the form and returns to the member area.
	 */
	public function save(): void {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 || ! check_admin_referer( self::ACTION ) ) {
			wp_die( esc_html__( 'Nicht erlaubt.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		$input = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised in ProfileFields and below.

		if ( isset( $input['bw_wm_part'] ) && 'directory' === $input['bw_wm_part'] ) {
			$this->members->set_directory( $user_id, ! empty( $input['bw_wm_directory'] ), current_time( 'Y-m-d' ) );
		} else {
			$this->members->save_profile( $user_id, ProfileFields::sanitize( (array) ( $input['bw_wm'] ?? array() ) ) );
		}

		$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'bwm', 'saved', remove_query_arg( 'bwm', $back ) ) . '#profil' );
		exit;
	}
}
