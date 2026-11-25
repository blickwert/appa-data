<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Events\Model\Event;
use BW\Appa\Modules\Seo\Service\EventSchema;
use PHPUnit\Framework\TestCase;

final class EventSchemaTest extends TestCase {

	private function page( array $changes = array() ): array {
		return $changes + array( 'title' => 'Fachtagung', 'url' => 'https://example.com/e/', 'description' => 'Zwei Tage', 'image' => '', 'price_member' => '80.00', 'price_public' => '120.00', 'organizer' => 'APPA', 'organizer_url' => 'https://example.com/' );
	}

	public function test_onsite_event_with_prices(): void {
		$s = ( new EventSchema() )->build( new Event( '2027-10-08', '09:00', '', '', '2027-10-09', '17:00', 'onsite', 'Universität Graz', 'open', true ), $this->page() );
		self::assertSame( 'Event', $s['@type'] );
		self::assertSame( '2027-10-08T09:00:00+02:00', $s['startDate'] );
		self::assertSame( '2027-10-09T17:00:00+02:00', $s['endDate'] );
		self::assertSame( 'https://schema.org/OfflineEventAttendanceMode', $s['eventAttendanceMode'] );
		self::assertSame( 'Universität Graz', $s['location']['name'] );
		self::assertCount( 2, $s['offers'] );
		self::assertSame( 'EUR', $s['offers'][0]['priceCurrency'] );
	}

	public function test_online_members_only_hides_zoom(): void {
		$s = ( new EventSchema() )->build( new Event( '2027-01-14', '18:00', 'https://zoom.us/j/1', 'Kenncode', '', '19:30', 'online', '', 'members', true ), $this->page( array( 'price_member' => '0.00' ) ) );
		self::assertSame( 'https://schema.org/OnlineEventAttendanceMode', $s['eventAttendanceMode'] );
		self::assertSame( 'VirtualLocation', $s['location']['@type'] );
		self::assertSame( 'https://example.com/e/', $s['location']['url'] );
		self::assertStringNotContainsString( 'zoom.us', (string) json_encode( $s ) );
		self::assertCount( 1, $s['offers'], 'members only: no public offer' );
		self::assertSame( '2027-01-14T19:30:00+01:00', $s['endDate'] );
	}

	public function test_hybrid_and_without_time(): void {
		$s = ( new EventSchema() )->build( new Event( '2027-04-22', '', '', '', '', '', 'hybrid', 'Graz', 'open', false ), $this->page() );
		self::assertSame( '2027-04-22', $s['startDate'] );
		self::assertArrayNotHasKey( 'endDate', $s );
		self::assertCount( 2, $s['location'] );
		self::assertArrayNotHasKey( 'offers', $s, 'no registration on the website' );
	}
}
