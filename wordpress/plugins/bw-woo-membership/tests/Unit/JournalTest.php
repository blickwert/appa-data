<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use BW\WooMembership\Modules\Membership\Service\Journal;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class JournalTest extends TestCase {

	public function test_period_of_a_month(): void {
		$zone          = new DateTimeZone( 'Europe/Vienna' );
		[ $from, $to ] = Journal::period( '2027-02', $zone );
		self::assertSame( '2027-02-01 00:00:00', $from->format( 'Y-m-d H:i:s' ) );
		self::assertSame( '2027-02-28 23:59:59', $to->format( 'Y-m-d H:i:s' ) );
		self::assertSame( 'Europe/Vienna', $from->getTimezone()->getName() );
	}

	public function test_invalid_month_means_previous_month(): void {
		$zone = new DateTimeZone( 'Europe/Vienna' );
		$now  = new DateTimeImmutable( '2027-01-15 10:00', $zone );
		foreach ( array( '', '2027-13', 'x', '2027-1' ) as $month ) {
			[ $from, $to ] = Journal::period( $month, $zone, $now );
			self::assertSame( '2026-12-01', $from->format( 'Y-m-d' ), $month );
			self::assertSame( '2026-12-31', $to->format( 'Y-m-d' ), $month );
		}
	}

	public function test_money_and_net(): void {
		self::assertSame( '96,00', Journal::money( 96 ) );
		self::assertSame( '1234,50', Journal::money( 1234.5 ) );
		self::assertSame( 94.31, Journal::net( 96.0, 0.0, 1.69, 94.31 ) );
		self::assertSame( 0.0, Journal::net( 35.0, 35.0, 0.0, null ), 'refunded bank transfer' );
		self::assertSame( 94.31, Journal::net( 96.0, 0.0, 1.69, null ) );
	}

	public function test_totals(): void {
		$sum = Journal::totals(
			array(
				array( 'total' => 96.0, 'refunded' => 0.0, 'fee' => 1.69, 'net' => 94.31 ),
				array( 'total' => 35.0, 'refunded' => 35.0, 'fee' => 0.0, 'net' => 0.0 ),
				array( 'total' => 0.1, 'refunded' => 0.0, 'fee' => 0.0, 'net' => 0.2 ),
			)
		);
		self::assertSame( array( 'total' => 131.1, 'refunded' => 35.0, 'fee' => 1.69, 'net' => 94.51 ), $sum );
	}
}
