<?php
/**
 * Waiting list per event.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Repository;

/**
 * Ordered list of people waiting for a place, stored on the event: user, since, notified (date of the e-mail that a
 * place is free; '' when not yet). People leave the list themselves or when they register.
 */
final class WaitlistRepository {

	public const META = '_bw_wm_waitlist';

	/**
	 * Entries in order of joining.
	 *
	 * @return list<array{user: int, since: string, notified: string}>
	 */
	public function all( int $event_id ): array {
		$stored = get_post_meta( $event_id, self::META, true );
		$out    = array();
		foreach ( is_array( $stored ) ? $stored : array() as $entry ) {
			if ( is_array( $entry ) && (int) ( $entry['user'] ?? 0 ) > 0 ) {
				$out[] = array( 'user' => (int) $entry['user'], 'since' => (string) ( $entry['since'] ?? '' ), 'notified' => (string) ( $entry['notified'] ?? '' ) );
			}
		}
		return $out;
	}

	/**
	 * Position of a user (1-based), 0 when not on the list.
	 */
	public function position( int $event_id, int $user_id ): int {
		foreach ( $this->all( $event_id ) as $i => $entry ) {
			if ( $entry['user'] === $user_id ) {
				return $i + 1;
			}
		}
		return 0;
	}

	/**
	 * Adds a user at the end (once).
	 */
	public function add( int $event_id, int $user_id, string $today ): void {
		if ( $user_id <= 0 || $this->position( $event_id, $user_id ) > 0 ) {
			return;
		}
		$list   = $this->all( $event_id );
		$list[] = array( 'user' => $user_id, 'since' => $today, 'notified' => '' );
		update_post_meta( $event_id, self::META, $list );
	}

	/**
	 * Removes a user.
	 */
	public function remove( int $event_id, int $user_id ): void {
		$list = array_values( array_filter( $this->all( $event_id ), static fn( array $e ): bool => $e['user'] !== $user_id ) );
		update_post_meta( $event_id, self::META, $list );
	}

	/**
	 * Marks users as informed about a free place.
	 *
	 * @param int[] $user_ids Users.
	 */
	public function mark_notified( int $event_id, array $user_ids, string $today ): void {
		$list = $this->all( $event_id );
		foreach ( $list as $i => $entry ) {
			if ( in_array( $entry['user'], $user_ids, true ) ) {
				$list[ $i ]['notified'] = $today;
			}
		}
		update_post_meta( $event_id, self::META, $list );
	}

	/**
	 * Forgets earlier notifications (the event is full again, so the next free place is announced anew).
	 */
	public function reset_notified( int $event_id ): void {
		$list = $this->all( $event_id );
		if ( ! array_filter( $list, static fn( array $e ): bool => '' !== $e['notified'] ) ) {
			return;
		}
		foreach ( $list as $i => $entry ) {
			$list[ $i ]['notified'] = '';
		}
		update_post_meta( $event_id, self::META, $list );
	}

	/**
	 * Number of people waiting.
	 */
	public function count( int $event_id ): int {
		return count( $this->all( $event_id ) );
	}
}
