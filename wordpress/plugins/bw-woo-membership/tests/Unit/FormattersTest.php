<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use Brain\Monkey;
use BW\WooMembership\Modules\Documents\Service\DocumentFormatter;
use BW\WooMembership\Modules\Membership\Service\MemberNumber;
use BW\WooMembership\Modules\Membership\Service\ProfileFields;
use PHPUnit\Framework\TestCase;

final class FormattersTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_document_label(): void {
		self::assertSame( 'PDF · 4,2 MB', DocumentFormatter::label( 'tagung.pdf', (int) ( 4.2 * 1024 * 1024 ) ) );
		self::assertSame( '320 KB', DocumentFormatter::size( 320 * 1024 ) );
		self::assertSame( 'XLSX', DocumentFormatter::type( '/a/b/budget.xlsx' ) );
		self::assertSame( '', DocumentFormatter::size( 0 ) );
	}

	public function test_member_number(): void {
		self::assertSame( 'A-0001', MemberNumber::format( 1 ) );
		self::assertSame( 'A-0123', MemberNumber::format( 123 ) );
	}

	public function test_profile_is_cleaned(): void {
		Monkey\Functions\stubs(
			array(
				'sanitize_text_field'     => static fn( $v ) => trim( strip_tags( (string) $v ) ),
				'sanitize_textarea_field' => static fn( $v ) => trim( strip_tags( (string) $v ) ),
				'esc_url_raw'             => static fn( $v ) => str_starts_with( (string) $v, 'http' ) ? (string) $v : '',
			)
		);
		$clean = ProfileFields::sanitize(
			array(
				'title'   => '<b>Dr.</b>',
				'website' => 'javascript:alert(1)',
				'groups'  => array( 'education', 'hacker' ),
				'bio'     => str_repeat( 'x', 2000 ),
			)
		);
		self::assertSame( 'Dr.', $clean['title'] );
		self::assertSame( '', $clean['website'] );
		self::assertSame( array( 'education' ), $clean['groups'] );
		self::assertSame( 1200, mb_strlen( $clean['bio'] ) );
	}
}
