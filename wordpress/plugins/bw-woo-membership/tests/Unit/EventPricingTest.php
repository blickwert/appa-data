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
}
