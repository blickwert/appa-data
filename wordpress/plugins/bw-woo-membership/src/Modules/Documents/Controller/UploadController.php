<?php
/**
 * Uploads of member documents.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Documents\Controller;

use BW\WooMembership\Core\PrivateStorage;
use BW\WooMembership\Modules\Documents\Repository\DocumentRepository;
use BW\WooMembership\Support\PrivatePath;

/**
 * Files uploaded on a document's screen go to the protected folder (private folder when configured, see
 * PrivateStorage) with an unguessable name.
 */
final class UploadController {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'random_name' ) );
		add_filter( 'wp_handle_sideload_prefilter', array( $this, 'random_name' ) );
		add_action( 'delete_attachment', array( $this, 'delete_private_files' ) );
		add_filter( 'wp_get_attachment_url', array( $this, 'private_url' ), 10, 2 );
	}

	/**
	 * Files in the private folder have no public address: link the checked download of the document instead of
	 * a broken uploads URL that would also show the server path.
	 *
	 * @param string|false $url           Attachment URL.
	 * @param int          $attachment_id Attachment ID.
	 * @return string|false
	 */
	public function private_url( $url, $attachment_id ) {
		$base = PrivateStorage::configured();
		$file = (string) get_post_meta( (int) $attachment_id, '_wp_attached_file', true );
		if ( '' === $base || ! PrivatePath::is_inside( $file, $base ) ) {
			return $url;
		}
		$parent = (int) wp_get_post_parent_id( (int) $attachment_id );
		return $parent > 0 && DocumentRepository::POST_TYPE === get_post_type( $parent ) ? DownloadController::url( $parent ) : '';
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
		$subdir = PrivatePath::UPLOAD_SUBDIRS[ PrivatePath::DOCS ];
		// Outside uploads WordPress keeps the absolute path in _wp_attached_file; the URL is never used (download via PHP).
		$dirs['path']   = PrivateStorage::dir( PrivatePath::DOCS, (string) $dirs['basedir'] );
		$dirs['subdir'] = $subdir;
		$dirs['url']    = $dirs['baseurl'] . $subdir;
		return $dirs;
	}

	/**
	 * Deletes the files of an attachment in the private folder; core only deletes files below uploads.
	 */
	public function delete_private_files( int $attachment_id ): void {
		$base = PrivateStorage::base();
		$file = (string) get_attached_file( $attachment_id, true );
		if ( '' === $base || '' === $file || ! PrivatePath::is_inside( $file, $base ) ) {
			return;
		}
		$dir   = dirname( $file );
		$meta  = wp_get_attachment_metadata( $attachment_id );
		$files = array( $file );
		if ( is_array( $meta ) ) {
			foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
				$files[] = $dir . '/' . (string) ( $size['file'] ?? '' );
			}
			if ( ! empty( $meta['original_image'] ) ) {
				$files[] = $dir . '/' . (string) $meta['original_image'];
			}
		}
		foreach ( array_unique( $files ) as $path ) {
			if ( is_file( $path ) && PrivatePath::is_inside( $path, $base ) ) {
				wp_delete_file( $path );
			}
		}
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
