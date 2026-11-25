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
use BW\WooMembership\Modules\Events\Controller\ParticipantsController;
use BW\WooMembership\Modules\Events\Controller\PrivacyController;
use BW\WooMembership\Modules\Events\Controller\ProductSyncController;
use BW\WooMembership\Modules\Events\Controller\RegistrationController;
use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Repository\NoticeRepository;
use BW\WooMembership\Modules\Events\Repository\WaitlistRepository;
use BW\WooMembership\Modules\Events\Service\ParticipantService;
use BW\WooMembership\Modules\Events\Service\ProductSync;
use BW\WooMembership\Modules\Events\Service\RegistrationFlow;
use BW\WooMembership\Modules\Events\Service\RegistrationMails;
use BW\WooMembership\Modules\Events\Controller\WaitlistController;
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
	public function description(): string {
		return __( 'Anmeldung zu Veranstaltungen über WooCommerce mit Warteliste, Erinnerung und Abmeldung.', 'bw-woo-membership' );
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
		$waitlist      = new WaitlistRepository();
		$notices       = new NoticeRepository();
		$mailer        = new Mailer();
		$registrations = new RegistrationService( $events, $waitlist, $notices );
		$sync          = new ProductSync( $events );
		$flow          = new RegistrationFlow( $events, $registrations, $sync, new RegistrationMails( $mailer ) );
		( new ProductSyncController( $events, $sync ) )->register();
		( new CartController( $events, $registrations ) )->register();
		( new OrderController( $events, $mailer ) )->register();
		( new PrivacyController( $events, $registrations ) )->register();
		( new BlockController( $registrations ) )->register();
		( new AdminController( $events, $waitlist ) )->register();
		( new WaitlistController( $events, $waitlist, $registrations, $mailer ) )->register();
		( new RegistrationController( $events, $flow, $notices ) )->register();
		( new ParticipantsController( $events, $waitlist, new ParticipantService( $events, $waitlist, $flow ), $notices ) )->register();
	}
}
