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
