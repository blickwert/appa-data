<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Forms\Service\FormToken;
use PHPUnit\Framework\TestCase;

final class FormTokenTest extends TestCase {

	public function test_round_trip_and_tampering(): void {
		$token = new FormToken( static fn(): string => 'secret' );
		$value = $token->create( 1_800_000_000 );

		self::assertSame( 1_800_000_000, $token->time( $value ) );
		self::assertNull( $token->time( '1700000000.' . explode( '.', $value )[1] ) );
		self::assertNull( ( new FormToken( static fn(): string => 'other' ) )->time( $value ) );
		self::assertNull( $token->time( 'garbage' ) );
	}

	public function test_maximum_age(): void {
		$token = new FormToken( static fn(): string => 'secret' );
		$time  = 1_800_000_000;
		$value = $token->create( $time );

		self::assertSame( $time, $token->time( $value, $time + 30 ) );
		self::assertSame( $time, $token->time( $value, $time + FormToken::MAX_AGE ), 'exactly 24 hours is still valid' );
		self::assertNull( $token->time( $value, $time + FormToken::MAX_AGE + 1 ), 'older than 24 hours' );
		self::assertNull( $token->time( $value, $time - 60 ), 'from the future' );
		self::assertNull( ( new FormToken( static fn(): string => 'secret', 60 ) )->time( $value, $time + 61 ), 'own maximum age' );
	}
}
