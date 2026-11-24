<?php
/**
 * Zoom data of events.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Service\RegistrationService;

/**
 * Answers `bw_appa_event_private_visible` (plugin bw-appa): with registration only paid participants see Zoom link and
 * access data; without registration members-only events show them to active members, other events to everyone.
 */
final class PrivacyController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly RegistrationService $registrations,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'bw_appa_event_private_visible', array( $this, 'visible' ), 10, 3 );
	}

	/**
	 * Visibility of the private event data for a user.
	 */
	public function visible( bool $visible, int $event_id, int $user_id ): bool {
		if ( $user_id > 0 && user_can( $user_id, 'edit_post', $event_id ) ) {
			return true;
		}
		$fields = $this->events->fields( $event_id );
		if ( $fields['registration'] ) {
			$order = $this->events->registration_of( $event_id, $user_id );
			return null !== $order && $order->is_paid();
		}
		if ( 'members' === $fields['audience'] ) {
			return $this->registrations->is_member( $user_id );
		}
		return $visible;
	}
}
