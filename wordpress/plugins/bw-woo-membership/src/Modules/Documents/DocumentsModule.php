<?php
/**
 * Documents module.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Documents;

use BW\WooMembership\Core\AbstractModule;
use BW\WooMembership\Core\PrivateStorage;
use BW\WooMembership\Modules\Documents\Controller\BlockController;
use BW\WooMembership\Modules\Documents\Controller\DownloadController;
use BW\WooMembership\Modules\Documents\Controller\PostTypeController;
use BW\WooMembership\Modules\Documents\Controller\UploadController;
use BW\WooMembership\Modules\Documents\Repository\DocumentRepository;
use BW\WooMembership\Support\PrivatePath;

/**
 * Member documents: post type, protected files, checked downloads, list block.
 */
final class DocumentsModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'documents';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return __( 'Mitglieder-Dokumente', 'bw-woo-membership' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return __( 'Geschützte Dokumente zum Herunterladen für Mitglieder.', 'bw-woo-membership' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function requires(): array {
		return array( 'membership' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$documents = new DocumentRepository();
		( new PostTypeController( $documents ) )->register();
		( new UploadController() )->register();
		( new DownloadController( $documents ) )->register();
		( new BlockController( $documents ) )->register();
	}

	/**
	 * {@inheritDoc}
	 */
	public function activate(): void {
		( new PostTypeController( new DocumentRepository() ) )->register_post_type();
		PrivateStorage::dir( PrivatePath::DOCS );
	}
}
