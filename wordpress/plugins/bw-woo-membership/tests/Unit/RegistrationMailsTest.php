<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use BW\WooMembership\Modules\Events\Service\RegistrationMails;
use PHPUnit\Framework\TestCase;

final class RegistrationMailsTest extends TestCase {

	public function test_confirmation_paid_online(): void {
		$text = RegistrationMails::confirmation_text( array( 'name' => 'Anna', 'title' => 'Fachtagung', 'when' => '8. Oktober 2027, 9:00 Uhr', 'place' => 'Online via Zoom', 'amount' => '80,00 € (Bestellung 12)', 'online' => true, 'reminder' => '6. Oktober 2027', 'until' => '24. September 2027', 'refund' => true, 'url' => 'https://example.com/e/#anmeldung' ) );
		self::assertStringStartsWith( "Hallo Anna,\n", $text );
		self::assertStringContainsString( 'Betrag: 80,00 € (Bestellung 12)', $text );
		self::assertStringContainsString( 'Zoom-Link schicken wir dir mit der Erinnerung', $text );
		self::assertStringContainsString( 'Wir erinnern dich am 6. Oktober 2027', $text );
		self::assertStringContainsString( 'Bis 24. September 2027 kannst du dich selbst abmelden – du bekommst den vollen Betrag zurück: https://example.com/e/#anmeldung', $text );
	}

	public function test_confirmation_free_onsite(): void {
		$text = RegistrationMails::confirmation_text( array( 'name' => 'Bo', 'title' => 'Abend', 'when' => '1. Jänner 2027', 'place' => '', 'amount' => '', 'online' => false, 'reminder' => '', 'until' => '1. Jänner 2027', 'refund' => false, 'url' => 'u' ) );
		self::assertStringNotContainsString( 'Betrag', $text );
		self::assertStringNotContainsString( 'Ort:', $text );
		self::assertStringNotContainsString( 'Zoom', $text );
		self::assertStringNotContainsString( 'vollen Betrag', $text );
	}

	public function test_reminder_with_and_without_zoom(): void {
		$base = array( 'name' => 'Anna', 'title' => 'Impuls', 'when' => 'heute', 'place' => 'Online via Zoom', 'zoom' => 'https://zoom.us/j/1', 'access' => 'Kenncode 4711', 'url' => 'https://example.com/e/' );
		$text = RegistrationMails::reminder_text( $base );
		self::assertStringContainsString( "Zoom-Link: https://zoom.us/j/1\nZugangsdaten: Kenncode 4711\nBitte gib den Link nicht weiter.", $text );
		self::assertStringNotContainsString( 'Zoom-Link', RegistrationMails::reminder_text( array( 'zoom' => '', 'access' => '' ) + $base ) );
	}
}
