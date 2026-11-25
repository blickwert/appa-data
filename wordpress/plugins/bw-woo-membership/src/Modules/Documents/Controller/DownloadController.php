<?php
/**
 * Document downloads.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Documents\Controller;

use BW\WooMembership\Modules\Documents\Repository\DocumentRepository;

/**
 * Streams a document after checking login, membership and visibility (`?bw_wm_doc=<ID>`).
 */
final class DownloadController {

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly DocumentRepository $documents ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'maybe_download' ), 1 );
	}

	/**
	 * Download URL of a document.
	 */
	public static function url( int $post_id ): string {
		return add_query_arg( 'bw_wm_doc', $post_id, home_url( '/' ) );
	}

	/**
	 * Serves the file or refuses.
	 */
	public function maybe_download(): void {
		if ( ! isset( $_GET['bw_wm_doc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- access is checked below.
			return;
		}
		$post_id = absint( $_GET['bw_wm_doc'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			auth_redirect();
		}

		/**
		 * Whether a user may open member content. Answered by the membership module.
		 *
		 * @param bool $has_access Default false.
		 * @param int  $user_id    User ID.
		 */
		$allowed = (bool) apply_filters( 'bw_wm_user_has_access', false, $user_id );
		if ( 'board' === $this->documents->visibility( $post_id ) ) {
			$allowed = user_can( $user_id, 'edit_pages' );
		}
		$file = (string) get_attached_file( $this->documents->file_id( $post_id ) );
		if ( ! $allowed || DocumentRepository::POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) || '' === $file || ! is_readable( $file ) ) {
			wp_die( esc_html__( 'Dieses Dokument ist nur für Mitglieder mit aktiver Mitgliedschaft verfügbar.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}

		$this->documents->count_download( $post_id );
		$type = wp_check_filetype( $file );
		$name = sanitize_file_name( get_the_title( $post_id ) ) . ( '' !== (string) $type['ext'] ? '.' . $type['ext'] : '' );
		nocache_headers();
		header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'Content-Length: ' . (string) filesize( $file ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a protected file.
		exit;
	}
}
