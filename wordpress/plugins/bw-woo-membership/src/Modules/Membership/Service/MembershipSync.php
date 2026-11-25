<?php
/**
 * Membership snapshot.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

use BW\WooMembership\Modules\Membership\Model\Membership;
use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;

/**
 * Writes the resolved membership into user meta, so the member list can filter, sort and export in SQL.
 */
final class MembershipSync {

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly MembershipService $memberships,
		private readonly MemberRepository $members,
		private readonly MembershipOrderRepository $orders,
	) {
	}

	/**
	 * Re-resolves one user and stores the snapshot; returns the fresh membership.
	 */
	public function sync( int $user_id ): Membership {
		$this->memberships->forget( $user_id );
		$m = $this->memberships->for_user( $user_id );
		$this->members->store_snapshot(
			$user_id,
			array(
				'status'      => $m->status,
				'type'        => $m->type,
				'valid_until' => $m->valid_until,
				'next'        => $m->next_renewal,
			)
		);
		return $m;
	}

	/**
	 * Re-resolves all members and customers with an open membership order (daily cron).
	 */
	public function sync_all(): int {
		$ids = array_unique( array_merge( $this->members->member_ids(), $this->orders->customers_with_open_orders(), $this->members->snapshot_ids() ) );
		foreach ( $ids as $user_id ) {
			$this->sync( (int) $user_id );
		}
		return count( $ids );
	}
}
