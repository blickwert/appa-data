<?php
/**
 * Event queries.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Controller;

use BW\Appa\Modules\Events\Repository\EventRepository;
use BW\Appa\Modules\Events\Service\UpcomingQuery;
use WP_Block;
use WP_Query;

/**
 * Shows only upcoming events in query loops and the archive; sorts the admin list by date.
 */
final class QueryController {

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly UpcomingQuery $upcoming ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'query_loop_block_query_vars', array( $this, 'filter_query_loop' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'filter_main_query' ) );
	}

	/**
	 * Query loop blocks (front page teaser, archive list) list upcoming events only.
	 *
	 * @param array<string, mixed> $query Query arguments.
	 * @return array<string, mixed>
	 */
	public function filter_query_loop( array $query, WP_Block $block ): array {
		if ( EventRepository::POST_TYPE !== ( $query['post_type'] ?? '' ) ) {
			return $query;
		}
		return $this->upcoming->apply( $query, $this->today() );
	}

	/**
	 * The archive's main query and the admin list.
	 */
	public function filter_main_query( WP_Query $query ): void {
		if ( ! $query->is_main_query() || EventRepository::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( is_admin() ) {
			if ( in_array( $query->get( 'orderby' ), array( '', EventRepository::META_DATE ), true ) ) {
				$query->set( 'meta_key', EventRepository::META_DATE ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query->set( 'orderby', 'meta_value' );
				$query->set( 'order', $query->get( 'order' ) ?: 'DESC' );
			}
			return;
		}
		if ( $query->is_post_type_archive( EventRepository::POST_TYPE ) ) {
			foreach ( $this->upcoming->apply( $query->query_vars, $this->today() ) as $key => $value ) {
				$query->set( $key, $value );
			}
		}
	}

	/**
	 * Today in the site's time zone.
	 */
	private function today(): string {
		return wp_date( 'Y-m-d' );
	}
}
