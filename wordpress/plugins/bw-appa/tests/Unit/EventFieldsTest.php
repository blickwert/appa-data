<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Events\Service\EventFields;
use PHPUnit\Framework\TestCase;

final class EventFieldsTest extends TestCase {

	public function test_date_accepts_valid_and_rejects_invalid_values(): void {
		self::assertSame( '2027-10-08', EventFields::date( ' 2027-10-08 ' ) );
		self::assertSame( '2028-02-29', EventFields::date( '2028-02-29' ) );
		self::assertSame( '', EventFields::date( '2027-02-29' ) );
		self::assertSame( '', EventFields::date( '20271008' ) );
		self::assertSame( '', EventFields::date( '' ) );
	}

	public function test_time_is_normalised_to_hh_mm(): void {
		self::assertSame( '08:30', EventFields::time( '8:30' ) );
		self::assertSame( '18:00', EventFields::time( '18:00:00' ) );
		self::assertSame( '', EventFields::time( '24:00' ) );
		self::assertSame( '', EventFields::time( 'abends' ) );
	}

	public function test_url_allows_http_only(): void {
		self::assertSame( 'https://zoom.us/j/123?pwd=a', EventFields::url( 'https://zoom.us/j/123?pwd=a' ) );
		self::assertSame( '', EventFields::url( 'javascript:alert(1)' ) );
		self::assertSame( '', EventFields::url( 'zoom.us/j/1' ) );
	}

	public function test_text_is_single_line_plain_text(): void {
		self::assertSame( 'ID 123 Kenncode 4711', EventFields::text( "<b>ID 123</b>\n Kenncode   4711" ) );
	}
}
