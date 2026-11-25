<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use BW\WooMembership\Modules\Events\Service\EventPricing;
use PHPUnit\Framework\TestCase;

final class EventPricingTest extends TestCase {

	public function test_open_event(): void {
		self::assertSame( 80.0, EventPricing::price( 'open', true, '80.00', '120.00' ) );
		self::assertSame( 120.0, EventPricing::price( 'open', false, '80.00', '120.00' ) );
		self::assertSame( 120.0, EventPricing::price( 'open', true, '', '120.00' ), 'no member price: public price' );
		self::assertNull( EventPricing::price( 'open', false, '80.00', '' ), 'no public price: not bookable' );
	}

	public function test_members_only(): void {
		self::assertSame( 0.0, EventPricing::price( 'members', true, '', '50.00' ) );
		self::assertSame( 20.0, EventPricing::price( 'members', true, '20.00', '' ) );
		self::assertNull( EventPricing::price( 'members', false, '20.00', '50.00' ) );
	}

	public function test_free_for_members(): void {
		self::assertSame( 0.0, EventPricing::price( 'free_members', true, '10.00', '25.00' ) );
		self::assertSame( 25.0, EventPricing::price( 'free_members', false, '', '25.00' ) );
	}

	public function test_places_and_labels(): void {
		self::assertSame( 8, EventPricing::places_left( '10', 2 ) );
		self::assertSame( 0, EventPricing::places_left( '10', 12 ) );
		self::assertNull( EventPricing::places_left( '', 5 ) );
		self::assertSame( 'kostenlos', EventPricing::label( 0.0 ) );
		self::assertSame( '1.200,50 €', EventPricing::label( 1200.5 ) );
	}

	public function test_registration_open(): void {
		self::assertTrue( EventPricing::open( '2027-10-08', '', '2027-10-08' ), 'on the day' );
		self::assertFalse( EventPricing::open( '2027-10-08', '', '2027-10-09' ), 'after the event' );
		self::assertTrue( EventPricing::open( '2027-10-08', '2027-09-15', '2027-09-15' ), 'on the deadline' );
		self::assertFalse( EventPricing::open( '2027-10-08', '2027-09-15', '2027-09-16' ), 'after the deadline' );
		self::assertTrue( EventPricing::open( '', '', '2030-01-01' ), 'no dates' );
	}

	public function test_waitlist_to_notify(): void {
		$list = array(
			array( 'user' => 1, 'since' => '2027-01-01', 'notified' => '2027-01-05' ),
			array( 'user' => 2, 'since' => '2027-01-02', 'notified' => '' ),
			array( 'user' => 3, 'since' => '2027-01-03', 'notified' => '' ),
		);
		self::assertSame( array(), EventPricing::to_notify( $list, 1 ), 'the one free place was already announced to user 1' );
		self::assertSame( array( 2 ), EventPricing::to_notify( $list, 2 ), 'second place: next one' );
		self::assertSame( array( 2, 3 ), EventPricing::to_notify( $list, 5 ) );
		self::assertSame( array( 2, 3 ), EventPricing::to_notify( $list, null ), 'unlimited' );
		self::assertSame( array(), EventPricing::to_notify( $list, 0 ) );
	}
}
