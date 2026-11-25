<?php
/**
 * Membership resolution.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

use BW\WooMembership\Modules\Membership\Model\Membership;
use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;

/**
 * Determines a user's membership: manual memberships first, then a WooCommerce subscription (if WooCommerce
 * Subscriptions is active), otherwise the latest paid order of a membership product (calendar year).
 */
final class MembershipService {

	/**
	 * Memberships resolved in this request.
	 *
	 * @var array<int, Membership>
	 */
	private array $cache = array();

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly MemberRepository $members,
		private readonly MembershipOrderRepository $orders,
	) {
	}

	/**
	 * Membership of a user.
	 */
	public function for_user( int $user_id, ?string $today = null ): Membership {
		$today = $today ?? current_time( 'Y-m-d' );
		if ( isset( $this->cache[ $user_id ] ) ) {
			return $this->cache[ $user_id ];
		}
		$number = $this->members->number( $user_id );
		$since  = $this->members->since( $user_id );

		$membership = self::from_manual( $user_id, $this->members->manual( $user_id ), $number, $since, $today )
			?? self::from_subscription( $user_id, $this->orders->latest_subscription( $user_id ), $number, $since )
			?? self::from_order( $user_id, $this->orders->latest_order( $user_id ), $number, $since, $today );

		/**
		 * Filters the resolved membership.
		 *
		 * @param Membership $membership The membership.
		 * @param int        $user_id    User ID.
		 */
		$membership = apply_filters( 'bw_wm_membership', $membership, $user_id );

		$this->cache[ $user_id ] = $membership;
		return $membership;
	}

	/**
	 * Forgets the cached membership of a user (after a change in this request).
	 */
	public function forget( int $user_id ): void {
		unset( $this->cache[ $user_id ] );
	}

	/**
	 * Honorary and institutional memberships managed by the office (no payment, optional end date).
	 *
	 * @param array{type: string, until: string}|null $manual Manual data.
	 */
	public static function from_manual( int $user_id, ?array $manual, string $number, ?string $since, string $today ): ?Membership {
		if ( null === $manual || ! MembershipTypes::exists( $manual['type'] ) ) {
			return null;
		}
		$until  = '' !== $manual['until'] ? $manual['until'] : null;
		$status = null === $until || MembershipPeriod::covers( $until, $today ) ? Membership::STATUS_ACTIVE : Membership::STATUS_EXPIRED;
		return new Membership( $user_id, $status, $manual['type'], $number, $since, $until, null, false, 'manual' );
	}

	/**
	 * Membership from a WooCommerce subscription.
	 *
	 * @param array{type: string, status: string, start: ?string, end: ?string, next: ?string, manual: bool}|null $sub Subscription data.
	 */
	public static function from_subscription( int $user_id, ?array $sub, string $number, ?string $since ): ?Membership {
		if ( null === $sub ) {
			return null;
		}
		$status = match ( $sub['status'] ) {
			'active', 'pending-cancel' => Membership::STATUS_ACTIVE,
			'on-hold'                  => Membership::STATUS_ON_HOLD,
			'pending'                  => Membership::STATUS_PENDING,
			default                    => Membership::STATUS_EXPIRED,
		};
		$renews = 'active' === $sub['status'];
		$paid   = in_array( $sub['status'], array( 'active', 'pending-cancel', 'on-hold' ), true );
		// Paid up to the day before the next payment (yearly subscription synced to 1 January → 31 December);
		// a subscription that was never paid has no period and no "member since".
		$until = ! $paid ? null : ( $sub['end'] ?? ( null !== $sub['next'] ? ( new \DateTimeImmutable( $sub['next'] ) )->modify( '-1 day' )->format( 'Y-m-d' ) : null ) );
		return new Membership(
			$user_id,
			$status,
			$sub['type'],
			$number,
			$since ?? ( $paid ? $sub['start'] : null ),
			$until,
			$renews ? $sub['next'] : null,
			$renews && ! $sub['manual'],
			'subscription'
		);
	}

	/**
	 * Membership from the latest order of a membership product (one calendar year per payment).
	 *
	 * @param array{type: string, status: string, paid: ?string}|null $order Order data.
	 */
	public static function from_order( int $user_id, ?array $order, string $number, ?string $since, string $today ): Membership {
		if ( null === $order ) {
			return Membership::none( $user_id, $number, $since );
		}
		if ( in_array( $order['status'], array( 'on-hold', 'pending' ), true ) || null === $order['paid'] ) {
			return new Membership( $user_id, Membership::STATUS_PENDING, $order['type'], $number, $since, null, null, false, 'order' );
		}
		$until  = MembershipPeriod::valid_until( $order['paid'] );
		$active = MembershipPeriod::covers( $until, $today );
		return new Membership(
			$user_id,
			$active ? Membership::STATUS_ACTIVE : Membership::STATUS_EXPIRED,
			$order['type'],
			$number,
			$since ?? $order['paid'],
			$until,
			$active ? MembershipPeriod::next_renewal( $until ) : null,
			false,
			'order'
		);
	}
}
