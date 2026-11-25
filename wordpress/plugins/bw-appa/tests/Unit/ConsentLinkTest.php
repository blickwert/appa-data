<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Forms\Service\ConsentLink;
use PHPUnit\Framework\TestCase;

final class ConsentLinkTest extends TestCase {

	private const FORM = '<p class="bwa-form__field">Datenschutzhinweise</p><p class="bwa-form__field bwa-form__consent"><label><input type="checkbox"> Ich habe die Datenschutzhinweise gelesen.</label></p>';

	public function test_links_word_in_consent_paragraph_only(): void {
		$html = ConsentLink::add( self::FORM, 'https://appa.test/datenschutz/?a=1&b=2' );
		self::assertStringContainsString( 'Ich habe die <a href="https://appa.test/datenschutz/?a=1&amp;b=2" target="_blank" rel="noopener">Datenschutzhinweise</a> gelesen.', $html );
		self::assertStringStartsWith( '<p class="bwa-form__field">Datenschutzhinweise</p>', $html );
		self::assertSame( 1, substr_count( $html, '<a ' ) );
	}

	public function test_keeps_existing_link_and_empty_url(): void {
		$linked = ConsentLink::add( self::FORM, 'https://appa.test/datenschutz/' );
		self::assertSame( $linked, ConsentLink::add( $linked, 'https://appa.test/datenschutz/' ) );
		self::assertSame( self::FORM, ConsentLink::add( self::FORM, '' ) );
	}
}
