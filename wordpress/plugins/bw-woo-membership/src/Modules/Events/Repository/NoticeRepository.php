<?php
/**
 * One-time messages for a person.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Repository;

/**
 * Messages shown once on the next page view (after a form was sent), stored per user for a few minutes.
 */
final class NoticeRepository {

	private const PREFIX = 'bw_wm_event_notice_';

	/**
	 * Remembers a message for a user.
	 */
	public function remember( int $user_id, string $message ): void {
		if ( $user_id > 0 && '' !== $message ) {
			set_transient( self::PREFIX . $user_id, $message, 10 * MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Takes the remembered message (once), '' when none.
	 */
	public function take( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}
		$message = (string) get_transient( self::PREFIX . $user_id );
		if ( '' !== $message ) {
			delete_transient( self::PREFIX . $user_id );
		}
		return $message;
	}
}
