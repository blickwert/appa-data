<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use BW\WooMembership\Support\PrivatePath;
use PHPUnit\Framework\TestCase;

final class PrivatePathTest extends TestCase {

	private const UPLOADS = '/var/www/html/wp-content/uploads';

	public function test_normalize(): void {
		self::assertSame( '/srv/private', PrivatePath::normalize( ' /srv/private/ ' ) );
		self::assertSame( '/', PrivatePath::normalize( '/' ) );
		self::assertSame( 'C:/data/private', PrivatePath::normalize( 'C:\\data\\private\\' ) );
		self::assertSame( '', PrivatePath::normalize( '' ) );
		self::assertSame( '', PrivatePath::normalize( 'relative/dir' ), 'relative paths are not accepted' );
	}

	public function test_folder_without_private_base_stays_below_uploads(): void {
		self::assertSame( self::UPLOADS . '/bw-member-proofs', PrivatePath::folder( PrivatePath::PROOFS, '', self::UPLOADS ) );
		self::assertSame( self::UPLOADS . '/bw-member-docs', PrivatePath::folder( PrivatePath::DOCS, '', self::UPLOADS . '/' ) );
		self::assertSame( self::UPLOADS . '/bw-member-docs', PrivatePath::folder( PrivatePath::DOCS, 'not/absolute', self::UPLOADS ) );
	}

	public function test_folder_with_private_base(): void {
		self::assertSame( '/srv/appa-private/proofs', PrivatePath::folder( PrivatePath::PROOFS, '/srv/appa-private/', self::UPLOADS ) );
		self::assertSame( '/srv/appa-private/docs', PrivatePath::folder( PrivatePath::DOCS, '/srv/appa-private', self::UPLOADS ) );
		self::assertSame( '/srv/appa-private/docs', PrivatePath::folder( 'unknown', '/srv/appa-private', self::UPLOADS ) );
	}

	public function test_resolve_keeps_absolute_and_joins_relative(): void {
		self::assertSame( '/srv/appa-private/proofs/nachweis-1-x.pdf', PrivatePath::resolve( '/srv/appa-private/proofs/nachweis-1-x.pdf', self::UPLOADS ) );
		self::assertSame( self::UPLOADS . '/bw-member-proofs/nachweis-1-x.pdf', PrivatePath::resolve( self::UPLOADS . '/bw-member-proofs/nachweis-1-x.pdf', '/elsewhere' ), 'old absolute paths keep working' );
		self::assertSame( self::UPLOADS . '/bw-member-docs/a.pdf', PrivatePath::resolve( 'bw-member-docs/a.pdf', self::UPLOADS . '/' ) );
		self::assertSame( 'D:\\files\\a.pdf', PrivatePath::resolve( 'D:\\files\\a.pdf', self::UPLOADS ) );
		self::assertSame( '', PrivatePath::resolve( '', self::UPLOADS ) );
	}

	public function test_relative_and_inside(): void {
		self::assertSame( 'bw-member-docs', PrivatePath::relative( self::UPLOADS . '/bw-member-docs', self::UPLOADS . '/' ) );
		self::assertSame( '', PrivatePath::relative( self::UPLOADS, self::UPLOADS ) );
		self::assertSame( 'wp-content/uploads', PrivatePath::relative( self::UPLOADS, '/var/www/html/' ) );
		self::assertNull( PrivatePath::relative( '/var/www/html-private/docs', '/var/www/html' ), 'same prefix, other folder' );
		self::assertNull( PrivatePath::relative( '/var/www/html/../private', '/var/www/html' ), 'no way out with ..' );
		self::assertTrue( PrivatePath::is_inside( '/srv/p/docs/a.pdf', '/srv/p' ) );
		self::assertFalse( PrivatePath::is_inside( '/srv/p/docs/a.pdf', '/var/www/html' ) );
		self::assertFalse( PrivatePath::is_inside( 'docs/a.pdf', '/srv/p' ) );
	}
}
