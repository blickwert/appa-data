<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use BW\WooMembership\Modules\Events\Service\EventLabels;
use BW\WooMembership\Modules\Events\Service\Ics;
use BW\WooMembership\Modules\Events\Service\RegistrationRules;
use PHPUnit\Framework\TestCase;

final class RegistrationRulesTest extends TestCase {

	public function test_cancel_free_until_event_day(): void {
		self::assertSame( '2027-01-14', RegistrationRules::cancel_until( '2027-01-14', '', false ) );
		self::assertTrue( RegistrationRules::can_cancel( '2027-01-14', '30', false, '2027-01-14' ) );
		self::assertFalse( RegistrationRules::can_cancel( '2027-01-14', '', false, '2027-01-15' ) );
	}

	public function test_cancel_paid_until_days_before(): void {
		self::assertSame( '2027-09-24', RegistrationRules::cancel_until( '2027-10-08', '', true ), 'default 14 days' );
		self::assertTrue( RegistrationRules::can_cancel( '2027-10-08', '7', true, '2027-10-01' ) );
		self::assertFalse( RegistrationRules::can_cancel( '2027-10-08', '7', true, '2027-10-02' ) );
		self::assertTrue( RegistrationRules::can_cancel( '2027-10-08', '0', true, '2027-10-08' ) );
		self::assertFalse( RegistrationRules::can_cancel( '', '', true, '2027-10-08' ) );
	}

	public function test_reminder(): void {
		self::assertSame( '2027-01-12', RegistrationRules::reminder_day( '2027-01-14', '' ) );
		self::assertSame( '', RegistrationRules::reminder_day( '2027-01-14', '0' ) );
		self::assertFalse( RegistrationRules::reminder_due( '2027-01-14', '', '2027-01-11' ) );
		self::assertTrue( RegistrationRules::reminder_due( '2027-01-14', '', '2027-01-12' ) );
		self::assertTrue( RegistrationRules::reminder_due( '2027-01-14', '', '2027-01-13' ), 'late registration' );
		self::assertFalse( RegistrationRules::reminder_due( '2027-01-14', '', '2027-01-14' ) );
	}

	public function test_ics(): void {
		$base = array( 'uid' => 'appa-event-5-order-9@example.com', 'title' => 'Fachtagung, Graz', 'date' => '2027-10-08', 'time' => '09:00', 'end_date' => '', 'end_time' => '17:30', 'location' => 'Uni Graz; HS 12', 'url' => 'https://example.com/e', 'description' => "Zeile 1\nZeile 2" );
		$ics  = Ics::build( $base, '20261125T100000Z' );
		self::assertStringContainsString( "DTSTART;TZID=Europe/Vienna:20271008T090000\r\n", $ics );
		self::assertStringContainsString( "DTEND;TZID=Europe/Vienna:20271008T173000\r\n", $ics );
		self::assertStringContainsString( 'SUMMARY:Fachtagung\, Graz', $ics );
		self::assertStringContainsString( 'LOCATION:Uni Graz\; HS 12', $ics );
		self::assertStringContainsString( 'DESCRIPTION:Zeile 1\nZeile 2', $ics );

		$allday = Ics::build( array( 'time' => '', 'end_date' => '2027-10-09', 'end_time' => '' ) + $base, '20261125T100000Z' );
		self::assertStringContainsString( "DTSTART;VALUE=DATE:20271008\r\n", $allday );
		self::assertStringContainsString( "DTEND;VALUE=DATE:20271010\r\n", $allday );

		$nohour = Ics::build( array( 'end_time' => '' ) + $base, '20261125T100000Z' );
		self::assertStringContainsString( 'DTEND;TZID=Europe/Vienna:20271008T100000', $nohour, 'one hour by default' );
		foreach ( explode( "\r\n", Ics::build( array( 'description' => str_repeat( 'Ä', 80 ) . str_repeat( 'a', 90 ) ) + $base, 'x' ) ) as $line ) {
			self::assertLessThanOrEqual( 75, strlen( $line ) );
		}
	}

	public function test_labels(): void {
		$f = array( 'date' => '2027-10-08', 'time' => '09:00', 'end_date' => '', 'end_time' => '17:30', 'format' => 'hybrid', 'location' => 'Uni Graz', 'zoom_link' => '' );
		self::assertSame( '8. Oktober 2027, 9:00–17:30 Uhr', EventLabels::when( $f ) );
		self::assertSame( '8. Oktober 2027 – 9. Oktober 2027, ab 9:00 Uhr', EventLabels::when( array( 'end_date' => '2027-10-09' ) + $f ) );
		self::assertSame( 'Vor Ort und online: Uni Graz', EventLabels::place( $f ) );
		self::assertTrue( EventLabels::is_online( $f ) );
		self::assertFalse( EventLabels::is_online( array( 'format' => 'onsite' ) + $f ) );
		self::assertSame( 'Online via Zoom', EventLabels::place( array( 'format' => '', 'location' => '', 'zoom_link' => 'https://zoom.us/j/1' ) + $f ) );
	}
}
