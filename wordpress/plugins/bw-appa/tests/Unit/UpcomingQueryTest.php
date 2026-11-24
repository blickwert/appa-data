<?php
declare(strict_types=1);

namespace BW\Appa\Tests\Unit;

use BW\Appa\Modules\Events\Service\UpcomingQuery;
use PHPUnit\Framework\TestCase;

final class UpcomingQueryTest extends TestCase {

	public function test_adds_date_constraint_and_sorting(): void {
		$args = ( new UpcomingQuery() )->apply(
			array(
				'post_type' => 'appa_event',
				'order'     => 'DESC',
			),
			'2026-11-24'
		);

		self::assertSame( '2026-11-24', $args['meta_query'][0]['event_date']['value'] );
		self::assertSame( '>=', $args['meta_query'][0]['event_date']['compare'] );
		self::assertSame( 'DATE', $args['meta_query'][0]['event_date']['type'] );
		self::assertSame( array( 'event_date' => 'ASC', 'event_time' => 'ASC', 'title' => 'ASC' ), $args['orderby'] );
		self::assertArrayNotHasKey( 'order', $args );
	}

	public function test_keeps_existing_meta_query(): void {
		$args = ( new UpcomingQuery() )->apply(
			array( 'meta_query' => array( array( 'key' => 'other', 'value' => 1 ) ) ),
			'2026-11-24'
		);
		self::assertSame( 'other', $args['meta_query'][0]['key'] );
		self::assertCount( 3, $args['meta_query'] );
	}
}
