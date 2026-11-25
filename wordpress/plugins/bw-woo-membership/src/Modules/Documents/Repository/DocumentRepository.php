<?php
/**
 * Member documents.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Documents\Repository;

/**
 * Loads member documents and their file.
 */
final class DocumentRepository {

	public const POST_TYPE       = 'bw_member_doc';
	public const TAXONOMY        = 'bw_member_doc_cat';
	public const META_FILE       = 'bw_wm_doc_file';
	public const META_VISIBILITY = 'bw_wm_doc_visibility';
	public const META_PINNED     = 'bw_wm_doc_pinned';
	public const META_DOWNLOADS  = 'bw_wm_doc_downloads';

	/**
	 * Who may see a document, with visible labels.
	 *
	 * @return array<string, string>
	 */
	public static function visibilities(): array {
		return array(
			'members' => __( 'Alle Mitglieder', 'bw-woo-membership' ),
			'board'   => __( 'Nur Vorstand und Office', 'bw-woo-membership' ),
		);
	}

	/**
	 * Newest published documents, pinned ones first.
	 *
	 * @return \WP_Post[]
	 */
	public function latest( int $limit, bool $include_board ): array {
		$visibility = $include_board ? array_keys( self::visibilities() ) : array( 'members' );
		$posts      = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small post type.
					'relation' => 'OR',
					array(
						'key'     => self::META_VISIBILITY,
						'value'   => $visibility,
						'compare' => 'IN',
					),
					array(
						'key'     => self::META_VISIBILITY,
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		usort(
			$posts,
			fn( \WP_Post $a, \WP_Post $b ): int => array( $this->pinned( $b->ID ), $b->post_date ) <=> array( $this->pinned( $a->ID ), $a->post_date )
		);
		return array_slice( $posts, 0, $limit );
	}

	/**
	 * Attachment ID of a document (0 = none).
	 */
	public function file_id( int $post_id ): int {
		return (int) get_post_meta( $post_id, self::META_FILE, true );
	}

	/**
	 * Visibility key of a document.
	 */
	public function visibility( int $post_id ): string {
		$value = (string) get_post_meta( $post_id, self::META_VISIBILITY, true );
		return isset( self::visibilities()[ $value ] ) ? $value : 'members';
	}

	/**
	 * Whether the document is pinned to the top.
	 */
	public function pinned( int $post_id ): bool {
		return '1' === (string) get_post_meta( $post_id, self::META_PINNED, true );
	}

	/**
	 * Saves file, visibility and pin.
	 */
	public function save( int $post_id, int $file_id, string $visibility, bool $pinned ): void {
		update_post_meta( $post_id, self::META_FILE, $file_id );
		update_post_meta( $post_id, self::META_VISIBILITY, isset( self::visibilities()[ $visibility ] ) ? $visibility : 'members' );
		update_post_meta( $post_id, self::META_PINNED, $pinned ? '1' : '0' );
	}

	/**
	 * Download counter.
	 */
	public function downloads( int $post_id ): int {
		return (int) get_post_meta( $post_id, self::META_DOWNLOADS, true );
	}

	/**
	 * Counts one download.
	 */
	public function count_download( int $post_id ): void {
		update_post_meta( $post_id, self::META_DOWNLOADS, $this->downloads( $post_id ) + 1 );
	}
}
