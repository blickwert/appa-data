<?php
/**
 * Documents block.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Documents\Controller;

use BW\WooMembership\Core\View;
use BW\WooMembership\Modules\Documents\Repository\DocumentRepository;
use BW\WooMembership\Modules\Documents\Service\DocumentFormatter;

/**
 * Block "Mitglied: Dokumente" with the newest documents.
 */
final class BlockController {

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly DocumentRepository $documents ) {
	}

	/**
	 * Registers hooks (after the membership module has registered the shared editor script).
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ), 20 );
	}

	/**
	 * Registers the block.
	 */
	public function register_block(): void {
		register_block_type( BW_WM_DIR . '/blocks/documents', array( 'render_callback' => array( $this, 'render' ) ) );
	}

	/**
	 * Renders the list.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render( array $attributes ): string {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return '';
		}
		/** This filter is documented in src/Modules/Documents/Controller/DownloadController.php */
		$access = (bool) apply_filters( 'bw_wm_user_has_access', false, $user_id );
		$items  = array();
		if ( $access ) {
			$limit = max( 1, min( 20, (int) ( $attributes['limit'] ?? 3 ) ) );
			foreach ( $this->documents->latest( $limit, user_can( $user_id, 'edit_pages' ) ) as $post ) {
				$file    = (string) get_attached_file( $this->documents->file_id( $post->ID ) );
				$items[] = array(
					'title' => get_the_title( $post ),
					'meta'  => '' !== $file ? DocumentFormatter::label( $file, (int) @filesize( $file ) ) : '', // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- missing file shows no size.
					'type'  => '' !== $file ? strtolower( DocumentFormatter::type( $file ) ) : '',
					'url'   => DownloadController::url( $post->ID ),
				);
			}
		}
		return View::render( 'dashboard/documents', array( 'access' => $access, 'items' => $items ) );
	}
}
