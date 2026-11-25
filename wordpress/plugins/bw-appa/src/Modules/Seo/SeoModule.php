<?php
/**
 * SEO module.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Seo;

use BW\Appa\Core\AbstractModule;
use BW\Appa\Modules\Events\Repository\EventRepository;
use BW\Appa\Modules\Events\Service\EventFormatter;
use BW\Appa\Modules\Seo\Controller\SeoController;
use BW\Appa\Modules\Seo\Service\EventSchema;

/**
 * Site-wide SEO settings for Yoast SEO (organisation, separator, templates), defaults for events and the Event
 * schema. Page titles and descriptions themselves are content (scripts/seo.json → scripts/apply_seo.py).
 */
final class SeoModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'seo';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return __( 'SEO (Yoast)', 'bw-appa' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function requires(): array {
		return array( 'events' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		( new SeoController( new EventRepository(), new EventFormatter(), new EventSchema() ) )->register();
	}
}
