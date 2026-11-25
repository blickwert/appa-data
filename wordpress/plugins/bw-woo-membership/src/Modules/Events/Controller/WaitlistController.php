<?php
/**
 * Waiting list of sold-out events.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Repository\WaitlistRepository;
use BW\WooMembership\Modules\Events\Service\EventPricing;
use BW\WooMembership\Modules\Events\Service\RegistrationService;
use BW\WooMembership\Support\Mailer;
use WC_Order;

/**
 * Joining and leaving the waiting list (logged-in people), an e-mail to the next people when places become free,
 * and removal from the list after registering. There is no reservation: whoever books first gets the place.
 */
final class WaitlistController {

	public const ACTION = 'bw_wm_waitlist';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly WaitlistRepository $waitlist,
		private readonly RegistrationService $registrations,
		private readonly Mailer $mailer,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'bw_wm_event_places_changed', array( $this, 'notify' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_registration' ), 110, 4 );
	}

	/**
	 * Join or leave the waiting list.
	 */
	public function handle(): void {
		$event_id = isset( $_POST['event'] ) ? absint( $_POST['event'] ) : 0;
		$user_id  = get_current_user_id();
		if ( $user_id <= 0 || $event_id <= 0 || ! check_admin_referer( 'bw_wm_waitlist_' . $event_id ) ) {
			wp_die( esc_html__( 'Nicht erlaubt.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		$op = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		if ( 'leave' === $op ) {
			$this->waitlist->remove( $event_id, $user_id );
		} elseif ( 'join' === $op && $this->registrations->box( $event_id, $user_id )['waitlist']['can_join'] ) {
			$this->waitlist->add( $event_id, $user_id, current_time( 'Y-m-d' ) );
		}
		wp_safe_redirect( (string) get_permalink( $event_id ) . '#anmeldung' );
		exit;
	}

	/**
	 * Places became free: e-mail to the next people on the list (as many as places, each once).
	 */
	public function notify( int $event_id, ?int $left ): void {
		if ( 0 === $left ) {
			$this->waitlist->reset_notified( $event_id ); // Full again: the next free place is announced anew.
			return;
		}
		if ( $this->registrations->is_closed( $event_id ) ) {
			return;
		}
		$users = EventPricing::to_notify( $this->waitlist->all( $event_id ), $left );
		if ( ! $users ) {
			return;
		}
		foreach ( $users as $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$this->mailer->send(
					$user->user_email,
					/* translators: %s: event title */
					sprintf( __( 'Ein Platz ist frei: %s', 'bw-woo-membership' ), get_the_title( $event_id ) ),
					sprintf(
						/* translators: 1: first name, 2: event title, 3: event address */
						__( "Hallo %1\$s,\n\nbei „%2\$s“ ist ein Platz frei geworden. Du stehst auf der Warteliste – melde dich jetzt an, solange der Platz frei ist (wer zuerst bucht, bekommt ihn):\n%3\$s", 'bw-woo-membership' ),
						$user->first_name ? $user->first_name : $user->display_name,
						get_the_title( $event_id ),
						(string) get_permalink( $event_id )
					)
				);
			}
		}
		$this->waitlist->mark_notified( $event_id, $users, current_time( 'Y-m-d' ) );
	}

	/**
	 * Whoever registers leaves the waiting list.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from     Old status.
	 * @param string   $to       New status.
	 * @param WC_Order $order    Order.
	 */
	public function on_registration( int $order_id, string $from, string $to, mixed $order ): void {
		if ( ! $order instanceof WC_Order || ! in_array( $to, EventRepository::HOLDING_STATUSES, true ) || $order->get_customer_id() <= 0 ) {
			return;
		}
		foreach ( $this->events->events_in_order( $order ) as $event_id ) {
			$this->waitlist->remove( $event_id, $order->get_customer_id() );
		}
	}
}
