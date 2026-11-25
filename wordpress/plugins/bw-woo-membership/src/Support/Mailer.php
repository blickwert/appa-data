<?php
/**
 * Notification mails.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Support;

/**
 * Plain-text mails to members and the office (no attachments: proofs are only reachable in the backend).
 */
final class Mailer {

	/**
	 * Address of the office.
	 */
	public function office(): string {
		/**
		 * Filters the address that receives office notifications (new proofs).
		 *
		 * @param string $email Default: admin e-mail of the site.
		 */
		return (string) apply_filters( 'bw_wm_office_email', (string) get_option( 'admin_email' ) );
	}

	/**
	 * Sends a mail; the signature is appended.
	 *
	 * @param string[] $attachments File paths.
	 */
	public function send( string $to, string $subject, string $body, array $attachments = array() ): bool {
		if ( ! is_email( $to ) ) {
			return false;
		}
		$body .= "\n\n-- \nAPPA – Austrian Positive Psychology Association\n" . home_url( '/' );
		return wp_mail( $to, $subject, $body, '', $attachments );
	}
}
