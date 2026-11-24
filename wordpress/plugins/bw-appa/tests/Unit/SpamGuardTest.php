<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Forms\Service\SpamGuard;
use PHPUnit\Framework\TestCase;

final class SpamGuardTest extends TestCase {

	private const NOW = 1_800_000_000;

	public function test_clean_submission_passes(): void {
		$guard = new SpamGuard();
		self::assertNull( $guard->reason( '', self::NOW - 30, self::NOW, array( 'Hallo, ich habe eine Frage.' ), 0 ) );
	}

	public function test_detects_each_rule(): void {
		$guard = new SpamGuard( 4, 2, 5, array( 'casino' ) );
		self::assertSame( 'honeypot', $guard->reason( 'http://spam', self::NOW - 30, self::NOW, array(), 0 ) );
		self::assertSame( 'too_fast', $guard->reason( '', self::NOW - 2, self::NOW, array(), 0 ) );
		self::assertSame( 'too_fast', $guard->reason( '', null, self::NOW, array(), 0 ) );
		self::assertSame( 'rate_limit', $guard->reason( '', self::NOW - 30, self::NOW, array(), 5 ) );
		self::assertSame( 'links', $guard->reason( '', self::NOW - 30, self::NOW, array( 'https://a https://b www.c' ), 0 ) );
		self::assertSame( 'blocked_word', $guard->reason( '', self::NOW - 30, self::NOW, array( 'Online CASINO' ), 0 ) );
	}

	public function test_link_limit_is_inclusive(): void {
		$guard = new SpamGuard( 4, 2 );
		self::assertNull( $guard->reason( '', self::NOW - 30, self::NOW, array( 'https://a.at und https://b.at' ), 0 ) );
	}
}
