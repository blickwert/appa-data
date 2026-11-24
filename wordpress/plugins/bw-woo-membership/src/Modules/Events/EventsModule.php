<?php
/**
 * Events module.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events;

use BW\WooMembership\Core\AbstractModule;
use BW\WooMembership\Modules\Events\Controller\AdminController;
use BW\WooMembership\Modules\Events\Controller\BlockController;
use BW\WooMembership\Modules\Events\Controller\CartController;
use BW\WooMembership\Modules\Events\Controller\OrderController;
use BW\WooMembership\Modules\Events\Controller\PrivacyController;
use BW\WooMembership\Modules\Events\Controller\ProductSyncController;
use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Service\RegistrationService;
use BW\WooMembership\Support\Mailer;

/**
 * Event registrations through WooCommerce with member prices (events come from the plugin bw-appa).
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
		return __( 'Veranstaltungsanmeldung', 'bw-woo-membership' );
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
		$events        = new EventRepository();
		$registrations = new RegistrationService( $events );
		( new ProductSyncController( $events ) )->register();
		( new CartController( $events, $registrations ) )->register();
		( new OrderController( $events, new Mailer() ) )->register();
		( new PrivacyController( $events, $registrations ) )->register();
		( new BlockController( $registrations ) )->register();
		( new AdminController( $events ) )->register();
	}
}
