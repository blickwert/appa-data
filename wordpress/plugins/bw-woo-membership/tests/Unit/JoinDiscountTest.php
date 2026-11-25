<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use BW\WooMembership\Modules\Membership\Service\JoinDiscount;
use PHPUnit\Framework\TestCase;

final class JoinDiscountTest extends TestCase {

	public function test_share_per_month(): void {
		foreach ( array( 1, 2, 3, 4, 5, 12 ) as $month ) {
			self::assertSame( 1.0, JoinDiscount::share( $month ), "month $month" );
		}
		foreach ( array( 6, 7, 8, 9 ) as $month ) {
			self::assertSame( 0.5, JoinDiscount::share( $month ), "month $month" );
		}
		foreach ( array( 10, 11 ) as $month ) {
			self::assertEqualsWithDelta( 1 / 3, JoinDiscount::share( $month ), 0.0001, "month $month" );
		}
	}

	public function test_discount_amounts(): void {
		self::assertSame( 0.0, JoinDiscount::discount( 96.0, 3 ) );
		self::assertSame( 48.0, JoinDiscount::discount( 96.0, 6 ) );
		self::assertSame( 64.0, JoinDiscount::discount( 96.0, 10 ), '96 € → 32 € to pay' );
		self::assertSame( 17.5, JoinDiscount::discount( 35.0, 9 ) );
		self::assertSame( 23.33, JoinDiscount::discount( 35.0, 11 ), '35 € → 11,67 € to pay' );
		self::assertSame( 0.0, JoinDiscount::discount( 96.0, 12 ), 'December: full fee, valid until end of next year' );
	}

	public function test_labels(): void {
		self::assertSame( '', JoinDiscount::label( 1 ) );
		self::assertStringContainsString( 'halber', JoinDiscount::label( 7 ) );
		self::assertStringContainsString( 'Drittel', JoinDiscount::label( 11 ) );
	}
}
