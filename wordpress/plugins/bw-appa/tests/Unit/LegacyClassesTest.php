<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Forms\Service\LegacyClasses;
use PHPUnit\Framework\TestCase;

final class LegacyClassesTest extends TestCase {

	public function test_renames_old_form_classes(): void {
		$old = '<div class="bwa-form"><p class="bwa-form__field bwa-form__field--half">[text* name]</p><p class="bwa-form__actions">[submit class:bwa-form__submit "Senden"]</p></div>';
		$new = LegacyClasses::rename( $old );
		self::assertTrue( LegacyClasses::found( $old ) );
		self::assertFalse( LegacyClasses::found( $new ) );
		self::assertSame( '<div class="bw-form"><p class="bw-form__field bw-form__field--half">[text* name]</p><p class="bw-form__actions">[submit class:bw-form__submit "Senden"]</p></div>', $new );
	}

	public function test_keeps_other_text(): void {
		$html = '<p class="my-bwa-form other">bwa-team und xbwa-form bleiben</p>';
		self::assertFalse( LegacyClasses::found( $html ) );
		self::assertSame( $html, LegacyClasses::rename( $html ) );
	}
}
