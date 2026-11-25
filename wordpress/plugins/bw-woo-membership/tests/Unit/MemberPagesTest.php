<?php
declare(strict_types=1);

namespace BW\WooMembership\Tests\Unit;

use Brain\Monkey;
use BW\WooMembership\Modules\Membership\Service\MemberPages;
use PHPUnit\Framework\TestCase;

final class MemberPagesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Monkey\Functions\stubs(
			array(
				'get_posts'          => array( 7 ),
				'get_permalink'      => 'https://appa.test/mitgliederbereich/',
				'home_url'           => 'https://appa.test',
				'wp_parse_url'       => static fn( $url, $part = -1 ) => parse_url( $url, $part ),
				'untrailingslashit'  => static fn( $s ) => rtrim( (string) $s, '/\\' ),
				'add_query_arg'      => static fn( $key, $value, $url ) => $url . '?' . $key . '=' . $value,
				// Simplified wp_validate_redirect(): same host or relative path.
				'wp_validate_redirect' => static function ( $url, $fallback ) {
					$host = parse_url( $url, PHP_URL_HOST );
					return null === $host || 'appa.test' === $host ? $url : $fallback;
				},
			)
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_login_url_carries_return_address(): void {
		self::assertSame(
			'https://appa.test/mitgliederbereich/?redirect_to=https%3A%2F%2Fappa.test%2Fevent%2F%23anmeldung',
			( new MemberPages() )->login_url( 'https://appa.test/event/#anmeldung' )
		);
		self::assertSame( 'https://appa.test/mitgliederbereich/', ( new MemberPages() )->login_url( '' ) );
	}

	public function test_return_address_is_validated(): void {
		$pages = new MemberPages();
		self::assertSame( 'https://appa.test/event/#anmeldung', $pages->safe_return_url( 'https://appa.test/event/#anmeldung' ) );
		self::assertSame( '', $pages->safe_return_url( 'https://evil.test/' ) );
		self::assertSame( '', $pages->safe_return_url( '' ) );
		self::assertSame( '', $pages->safe_return_url( 'https://appa.test/mitgliederbereich/?x=1' ), 'no loop to the login page' );
	}
}
