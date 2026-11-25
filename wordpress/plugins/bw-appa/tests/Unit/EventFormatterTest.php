<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Events\Model\Event;
use BW\Appa\Modules\Events\Service\EventFormatter;
use PHPUnit\Framework\TestCase;

final class EventFormatterTest extends TestCase {

	private EventFormatter $formatter;

	protected function setUp(): void {
		$this->formatter = new EventFormatter();
	}

	public function test_when_with_and_without_time(): void {
		self::assertSame( '8. Oktober 2027 · 18:30 Uhr', $this->formatter->when( new Event( '2027-10-08', '18:30', '', '' ) ) );
		self::assertSame( '1. Jänner 2027', $this->formatter->when( new Event( '2027-01-01', '', '', '' ) ) );
		self::assertSame( '5. März 2027 · 9:05 Uhr', $this->formatter->when( new Event( '2027-03-05', '09:05', '', '' ) ) );
		self::assertSame( '5. März 2027 · 0:15 Uhr', $this->formatter->when( new Event( '2027-03-05', '00:15', '', '' ) ) );
		self::assertSame( '', $this->formatter->when( new Event( '', '18:00', '', '' ) ) );
	}

	public function test_when_with_end(): void {
		self::assertSame( '8. Oktober 2027 · 9:00–17:30 Uhr', $this->formatter->when( new Event( '2027-10-08', '09:00', '', '', '', '17:30' ) ) );
		self::assertSame( '8.–9. Oktober 2027 · ab 9:00 Uhr', $this->formatter->when( new Event( '2027-10-08', '09:00', '', '', '2027-10-09', '17:00' ) ) );
		self::assertSame( '30. September – 1. Oktober 2027', $this->formatter->when( new Event( '2027-09-30', '', '', '', '2027-10-01' ) ) );
		self::assertSame( '31. Dezember 2027 – 1. Jänner 2028', $this->formatter->when( new Event( '2027-12-31', '', '', '', '2028-01-01' ) ) );
		self::assertSame( '8. Oktober 2027', $this->formatter->when( new Event( '2027-10-08', '', '', '', '2027-10-08' ) ), 'same day as end' );
	}

	public function test_place_badge_and_button(): void {
		self::assertSame( 'Online via Zoom', $this->formatter->place( new Event( '', '', '', '', '', '', 'online', 'egal' ) ) );
		self::assertSame( 'Vor Ort · Universität Graz', $this->formatter->place( new Event( '', '', '', '', '', '', 'onsite', 'Universität Graz' ) ) );
		self::assertSame( 'Vor Ort und online · Graz', $this->formatter->place( new Event( '', '', '', '', '', '', 'hybrid', 'Graz' ) ) );
		self::assertSame( 'Online via Zoom', $this->formatter->place( new Event( '', '', 'https://zoom.us/j/1', '' ) ), 'old events: Zoom link only' );
		self::assertSame( '', $this->formatter->place( new Event( '', '', '', '' ) ) );
		self::assertSame( 'Exklusive Veranstaltung für Mitglieder', $this->formatter->badge( new Event( '', '', '', '', '', '', '', '', 'members' ) ) );
		self::assertSame( '', $this->formatter->badge( new Event( '', '', '', '' ) ) );
		self::assertSame( 'Jetzt anmelden', $this->formatter->call_to_action( new Event( '', '', '', '', '', '', '', '', 'open', true ) ) );
		self::assertSame( 'Mehr erfahren', $this->formatter->call_to_action( new Event( '', '', '', '' ) ) );
	}

	public function test_access_label(): void {
		self::assertSame( 'Zugangsdaten: Kenncode 4711', $this->formatter->access( new Event( '', '', '', 'Kenncode 4711' ) ) );
		self::assertSame( '', $this->formatter->access( new Event( '', '', '', '' ) ) );
	}

	public function test_upcoming_includes_today(): void {
		$today = '2026-11-24';
		self::assertTrue( $this->formatter->is_upcoming( new Event( '2026-11-24', '', '', '' ), $today ) );
		self::assertTrue( $this->formatter->is_upcoming( new Event( '2027-01-01', '', '', '' ), $today ) );
		self::assertFalse( $this->formatter->is_upcoming( new Event( '2026-11-23', '', '', '' ), $today ) );
		self::assertFalse( $this->formatter->is_upcoming( new Event( '', '', '', '' ), $today ) );
	}
}
