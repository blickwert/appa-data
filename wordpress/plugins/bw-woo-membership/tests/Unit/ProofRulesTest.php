<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use BW\WooMembership\Modules\Membership\Service\ProofRules;
use PHPUnit\Framework\TestCase;

final class ProofRulesTest extends TestCase {

	private function proof( string $status, string $until = '', string $reminded = '' ): array {
		return array( 'status' => $status, 'valid_until' => $until, 'reminded' => $reminded );
	}

	public function test_validity(): void {
		self::assertTrue( ProofRules::valid_on( $this->proof( 'approved', '2027-12-31' ), '2027-01-01' ) );
		self::assertFalse( ProofRules::valid_on( $this->proof( 'approved', '2026-12-31' ), '2027-01-01' ) );
		self::assertFalse( ProofRules::valid_on( $this->proof( 'pending', '2027-12-31' ), '2027-01-01' ) );
	}

	public function test_student_purchase(): void {
		self::assertTrue( ProofRules::may_buy_student( false, $this->proof( 'none' ), '2027-01-01' ) );
		self::assertFalse( ProofRules::may_buy_student( true, $this->proof( 'approved', '2026-12-31' ), '2027-01-01' ) );
		self::assertTrue( ProofRules::may_buy_student( true, $this->proof( 'approved', '2027-12-31' ), '2027-01-01' ) );
	}

	public function test_reminder(): void {
		$end = '2026-12-31';
		self::assertTrue( ProofRules::reminder_due( 'student', $end, $this->proof( 'approved', $end ), '2026-12-10' ) );
		self::assertFalse( ProofRules::reminder_due( 'student', $end, $this->proof( 'approved', $end ), '2026-11-01' ), 'too early' );
		self::assertFalse( ProofRules::reminder_due( 'student', $end, $this->proof( 'approved', $end, $end ), '2026-12-10' ), 'already reminded' );
		self::assertFalse( ProofRules::reminder_due( 'student', $end, $this->proof( 'approved', '2027-12-31' ), '2026-12-10' ), 'next year covered' );
		self::assertFalse( ProofRules::reminder_due( 'student', $end, $this->proof( 'pending' ), '2026-12-10' ), 'waiting for review' );
		self::assertFalse( ProofRules::reminder_due( 'regular', $end, $this->proof( 'none' ), '2026-12-10' ) );
	}

	public function test_default_valid_until(): void {
		self::assertSame( '2026-12-31', ProofRules::default_valid_until( '2026-12-31', '2026-11-24' ) );
		self::assertSame( '2026-12-31', ProofRules::default_valid_until( null, '2026-11-24' ) );
	}
}
