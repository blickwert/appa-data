<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use Brain\Monkey;
use BW\WooMembership\Modules\Membership\Model\Membership;
use BW\WooMembership\Modules\Membership\Service\MembershipService;
use PHPUnit\Framework\TestCase;

final class MembershipServiceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_no_order_means_no_membership(): void {
		$m = MembershipService::from_order( 7, null, 'A-0001', '2025-02-01', '2026-11-24' );
		self::assertSame( Membership::STATUS_NONE, $m->status );
		self::assertSame( 'A-0001', $m->number );
		self::assertFalse( $m->grants_access() );
	}

	public function test_paid_order_is_active_until_year_end(): void {
		$m = MembershipService::from_order( 7, array( 'type' => 'regular', 'status' => 'processing', 'paid' => '2026-11-20' ), 'A-0002', null, '2026-11-24' );
		self::assertSame( Membership::STATUS_ACTIVE, $m->status );
		self::assertSame( '2026-12-31', $m->valid_until );
		self::assertSame( '2027-01-01', $m->next_renewal );
		self::assertSame( '2026-11-20', $m->since );
		self::assertTrue( $m->grants_access() );
	}

	public function test_old_payment_expires(): void {
		$m = MembershipService::from_order( 7, array( 'type' => 'student', 'status' => 'completed', 'paid' => '2025-05-01' ), '', '2025-05-01', '2026-11-24' );
		self::assertSame( Membership::STATUS_EXPIRED, $m->status );
		self::assertNull( $m->next_renewal );
	}

	public function test_unpaid_order_is_pending(): void {
		$m = MembershipService::from_order( 7, array( 'type' => 'regular', 'status' => 'on-hold', 'paid' => null ), '', null, '2026-11-24' );
		self::assertSame( Membership::STATUS_PENDING, $m->status );
		self::assertFalse( $m->grants_access() );
	}

	public function test_subscription_states(): void {
		$sub = array( 'type' => 'regular', 'status' => 'active', 'start' => '2026-10-01', 'end' => null, 'next' => '2027-01-01', 'manual' => false );
		$m   = MembershipService::from_subscription( 7, $sub, 'A-0003', null );
		self::assertSame( Membership::STATUS_ACTIVE, $m->status );
		self::assertTrue( $m->auto_renew );
		self::assertSame( '2026-10-01', $m->since );
		self::assertSame( '2026-12-31', $m->valid_until, 'paid up to the day before the next payment' );

		$m = MembershipService::from_subscription( 7, array( 'status' => 'pending-cancel', 'end' => '2027-03-31' ) + $sub, '', null );
		self::assertSame( '2027-03-31', $m->valid_until );

		$m = MembershipService::from_subscription( 7, array( 'status' => 'on-hold' ) + $sub, '', null );
		self::assertSame( Membership::STATUS_ON_HOLD, $m->status );
		self::assertNull( $m->next_renewal );

		$m = MembershipService::from_subscription( 7, array( 'status' => 'pending' ) + $sub, '', null );
		self::assertSame( Membership::STATUS_PENDING, $m->status );
		self::assertNull( $m->valid_until, 'not paid yet: no period' );
		self::assertNull( $m->since );

		self::assertNull( MembershipService::from_subscription( 7, null, '', null ) );
	}

	public function test_manual_membership(): void {
		Monkey\Filters\expectApplied( 'bw_wm_membership_types' )->andReturnFirstArg();
		$m = MembershipService::from_manual( 7, array( 'type' => 'honorary', 'until' => '' ), 'A-0009', '2026-10-01', '2026-11-24' );
		self::assertSame( Membership::STATUS_ACTIVE, $m->status );
		self::assertNull( MembershipService::from_manual( 7, array( 'type' => 'unknown', 'until' => '' ), '', null, '2026-11-24' ) );
	}
}
