<?php
/**
 * Uploads of member documents.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Documents\Controller;

use BW\WooMembership\Modules\Documents\Repository\DocumentRepository;
use BW\WooMembership\Support\ProtectedFolder;

/**
 * Files uploaded on a document's screen go to the protected folder with an unguessable name.
 */
final class UploadController {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'random_name' ) );
		add_filter( 'wp_handle_sideload_prefilter', array( $this, 'random_name' ) );
	}

	/**
	 * Redirects the upload into the protected folder.
	 *
	 * @param array<string, mixed> $dirs Upload directories.
	 * @return array<string, mixed>
	 */
	public function upload_dir( array $dirs ): array {
		if ( ! $this->is_document_upload() ) {
			return $dirs;
		}
		ProtectedFolder::ensure( (string) $dirs['basedir'] );
		$dirs['subdir'] = ProtectedFolder::SUBDIR;
		$dirs['path']   = $dirs['basedir'] . ProtectedFolder::SUBDIR;
		$dirs['url']    = $dirs['baseurl'] . ProtectedFolder::SUBDIR;
		return $dirs;
	}

	/**
	 * Adds a random part to the file name so the direct address cannot be guessed (nginx ignores .htaccess).
	 *
	 * @param array<string, mixed> $file Uploaded file.
	 * @return array<string, mixed>
	 */
	public function random_name( array $file ): array {
		if ( $this->is_document_upload() && isset( $file['name'] ) ) {
			$info         = pathinfo( (string) $file['name'] );
			$file['name'] = sanitize_file_name( ( $info['filename'] ?? 'dokument' ) . '-' . wp_generate_password( 12, false ) . ( isset( $info['extension'] ) ? '.' . $info['extension'] : '' ) );
		}
		return $file;
	}

	/**
	 * Whether the current upload belongs to a member document (media modal sends the post ID).
	 */
	private function is_document_upload(): bool {
		// phpcs:disable WordPress.Security.NonceVerification -- the upload itself is checked by core; only the target folder is chosen here.
		// Media modal sends "post_id", the REST media route "post".
		$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : ( isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0 );
		// phpcs:enable
		return $post_id > 0 && DocumentRepository::POST_TYPE === get_post_type( $post_id );
	}
}
