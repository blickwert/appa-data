<?php
/**
 * Query arguments for upcoming events.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Service;

use BW\Appa\Modules\Events\Repository\EventRepository;

/**
 * Restricts a query to events from today on, sorted by date and time.
 */
final class UpcomingQuery {

	/**
	 * Adds the upcoming-events constraint to WP_Query arguments.
	 *
	 * @param array<string, mixed> $args  Query arguments.
	 * @param string               $today Y-m-d in the site's time zone.
	 * @return array<string, mixed>
	 */
	public function apply( array $args, string $today ): array {
		$meta_query   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$meta_query[] = array(
			'relation'   => 'AND',
			'event_date' => array(
				'key'     => EventRepository::META_DATE,
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		);
		$meta_query[] = array(
			'relation'   => 'OR',
			'event_time' => array(
				'key'     => EventRepository::META_TIME,
				'compare' => 'EXISTS',
			),
			array(
				'key'     => EventRepository::META_TIME,
				'compare' => 'NOT EXISTS',
			),
		);

		$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args['orderby']    = array(
			'event_date' => 'ASC',
			'event_time' => 'ASC',
			'title'      => 'ASC',
		);
		unset( $args['order'] );

		return $args;
	}
}
