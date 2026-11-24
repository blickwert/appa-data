<?php
/**
 * Events module.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events;

use BW\Appa\Core\AbstractModule;
use BW\Appa\Modules\Events\Controller\BindingsController;
use BW\Appa\Modules\Events\Controller\EditorController;
use BW\Appa\Modules\Events\Controller\PostTypeController;
use BW\Appa\Modules\Events\Controller\PrivacyController;
use BW\Appa\Modules\Events\Controller\QueryController;
use BW\Appa\Modules\Events\Repository\EventRepository;
use BW\Appa\Modules\Events\Service\EventFormatter;
use BW\Appa\Modules\Events\Service\UpcomingQuery;

/**
 * Events with date, time and Zoom data; only upcoming events are shown on the website.
 */
final class EventsModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'events';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return __( 'Veranstaltungen', 'bw-appa' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$repository = new EventRepository();
		( new PostTypeController() )->register();
		( new QueryController( new UpcomingQuery() ) )->register();
		( new BindingsController( $repository, new EventFormatter() ) )->register();
		( new EditorController() )->register();
		( new PrivacyController() )->register();
	}

	/**
	 * {@inheritDoc}
	 */
	public function activate(): void {
		( new PostTypeController() )->register_post_type();
	}
}
