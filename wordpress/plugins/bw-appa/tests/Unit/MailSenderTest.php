<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Forms\Service\MailSender;
use PHPUnit\Framework\TestCase;

final class MailSenderTest extends TestCase {

	public function test_cleans_display_name(): void {
		self::assertSame( 'dev 2603b-appa <wordpress@example.at>', MailSender::clean( '@dev 2603b-appa <wordpress@example.at>' ) );
		self::assertSame( 'APPA – Austrian Positive Psychology Association <a@b.at>', MailSender::clean( 'APPA – Austrian Positive Psychology Association <a@b.at>' ) );
		self::assertSame( 'Müller & Söhne <a@b.at>', MailSender::clean( '"Müller & Söhne" <a@b.at>' ) );
	}

	public function test_fallback_and_bare_address(): void {
		self::assertSame( 'APPA <a@b.at>', MailSender::clean( '@@ <a@b.at>' ) );
		self::assertSame( 'APPA <a@b.at>', MailSender::clean( '<a@b.at>' ) );
		self::assertSame( 'a@b.at', MailSender::clean( 'a@b.at' ) );
	}
}
