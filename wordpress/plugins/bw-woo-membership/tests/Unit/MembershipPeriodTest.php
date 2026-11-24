<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use BW\WooMembership\Modules\Membership\Service\MembershipPeriod;
use PHPUnit\Framework\TestCase;

final class MembershipPeriodTest extends TestCase {

	public function test_payment_covers_the_calendar_year(): void {
		self::assertSame( '2026-12-31', MembershipPeriod::valid_until( '2026-03-15' ) );
		self::assertSame( '2026-12-31', MembershipPeriod::valid_until( '2026-11-30' ) );
	}

	public function test_december_payment_covers_the_next_year(): void {
		self::assertSame( '2027-12-31', MembershipPeriod::valid_until( '2026-12-01' ) );
	}

	public function test_renewal_and_coverage(): void {
		self::assertSame( '2027-01-01', MembershipPeriod::next_renewal( '2026-12-31' ) );
		self::assertTrue( MembershipPeriod::covers( '2026-12-31', '2026-12-31' ) );
		self::assertFalse( MembershipPeriod::covers( '2026-12-31', '2027-01-01' ) );
	}

	public function test_german_date(): void {
		self::assertSame( '01. Jänner 2027', MembershipPeriod::format( '2027-01-01' ) );
		self::assertSame( '', MembershipPeriod::format( null ) );
	}
}
