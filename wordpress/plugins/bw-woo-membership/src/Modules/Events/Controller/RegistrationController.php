<?php
/**
 * Registering, cancelling, confirmation and reminder mails.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Service\EventLabels;
use BW\WooMembership\Modules\Events\Service\Ics;
use BW\WooMembership\Modules\Events\Service\RegistrationRules;
use BW\WooMembership\Modules\Events\Service\RegistrationService;
use BW\WooMembership\Support\Mailer;
use WC_Order;
use WC_Order_Item_Product;

/**
 * The registration flow (concept 25 November 2026, like the event pages of the WKO):
 * - free events: logged-in people register with one click under their name (a 0 € WooCommerce order);
 *   paid events and people without an account go through the checkout;
 * - confirmation mail with a calendar file, reminder a few days before (with the Zoom data for online events);
 * - cancelling on the website: free until the event day, paid until a deadline with a full refund.
 */
final class RegistrationController {

	public const REGISTER        = 'bw_wm_register';
	public const CANCEL          = 'bw_wm_cancel_registration';
	public const ITEM_CONFIRMED  = '_bw_wm_confirmed';
	public const ITEM_REMINDED   = '_bw_wm_reminded';
	private const NOTICE         = 'bw_wm_event_notice_';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly RegistrationService $registrations,
		private readonly ProductSyncController $sync,
		private readonly Mailer $mailer,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::REGISTER, array( $this, 'handle_register' ) );
		add_action( 'admin_post_' . self::CANCEL, array( $this, 'handle_cancel' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'confirm' ), 20, 2 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'confirm' ), 20, 2 );
		add_action( 'bw_wm_daily_after_sync', array( $this, 'send_reminders' ) );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', array( $this, 'skip_completed_mail' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_new_order', array( $this, 'skip_free_order_mail' ), 10, 2 );
	}

	/**
	 * One-click registration for a free event (logged-in people).
	 */
	public function handle_register(): void {
		$event_id = isset( $_POST['event'] ) ? absint( $_POST['event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below.
		$user_id  = get_current_user_id();
		$back     = $event_id ? (string) get_permalink( $event_id ) . '#anmeldung' : home_url( '/' );
		if ( ! $event_id || $user_id <= 0 || ! check_admin_referer( self::REGISTER . '_' . $event_id ) ) {
			wp_safe_redirect( $back );
			exit;
		}
		$refusal = $this->registrations->refusal( $event_id, $user_id );
		$price   = $this->registrations->price( $event_id, $user_id );
		if ( '' !== $refusal || null === $price || $price > 0 ) {
			$this->notice( $user_id, '' !== $refusal ? $refusal : 'Für diese Veranstaltung ist die Anmeldung über die Kasse nötig.' );
			wp_safe_redirect( $back );
			exit;
		}
		$order = $this->create_free_order( $event_id, $user_id );
		$this->notice( $user_id, $order ? 'Du bist angemeldet. Die Bestätigung haben wir dir per E-Mail geschickt.' : 'Die Anmeldung hat nicht geklappt. Bitte versuche es noch einmal.' );
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Cancels the person's own registration (from the event page or "Meine Anmeldungen").
	 */
	public function handle_cancel(): void {
		$event_id = isset( $_POST['event'] ) ? absint( $_POST['event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below.
		$user_id  = get_current_user_id();
		$back     = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$back     = '' !== $back ? $back : ( $event_id ? (string) get_permalink( $event_id ) . '#anmeldung' : home_url( '/' ) );
		if ( ! $event_id || $user_id <= 0 || ! check_admin_referer( self::CANCEL . '_' . $event_id ) ) {
			wp_safe_redirect( $back );
			exit;
		}
		$this->notice( $user_id, $this->cancel( $event_id, $user_id ) );
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Cancels a registration and returns the message for the person.
	 */
	public function cancel( int $event_id, int $user_id ): string {
		$order = $this->events->registration_of( $event_id, $user_id );
		$item  = $order ? $this->events->item_for( $order, $event_id ) : null;
		if ( ! $order || ! $item ) {
			return 'Wir haben keine Anmeldung gefunden.';
		}
		$f      = $this->events->fields( $event_id );
		$amount = (float) $item->get_total();
		$paid   = $order->is_paid() && $amount > 0;
		if ( ! RegistrationRules::can_cancel( $f['date'], $f['cancel_days'], $paid, current_time( 'Y-m-d' ) ) ) {
			return sprintf( 'Eine Abmeldung über die Website ist nicht mehr möglich. Bitte schreib uns an %s.', $this->mailer->office() );
		}
		$title    = get_the_title( $event_id );
		$refunded = '';
		if ( $paid ) {
			$refunded = $this->refund( $order, $item, $amount );
		}
		if ( 1 === count( $order->get_items() ) ) {
			// The order is only this registration: cancel it (a full refund already set it to "refunded").
			if ( ! $paid && ! $order->has_status( array( 'cancelled', 'refunded' ) ) ) {
				$order->update_status( 'cancelled', 'Von der Person selbst abgemeldet.' );
			}
		} else {
			$item->update_meta_data( EventRepository::ITEM_CANCELLED, current_time( 'mysql' ) );
			$item->save();
			$order->add_order_note( sprintf( 'Abmeldung von „%s“ durch die Person.', $title ) );
		}
		$this->events->forget();
		$this->sync->sync( $event_id );

		$user = get_userdata( $user_id );
		$this->mailer->send(
			$order->get_billing_email(),
			'Abmeldung bestätigt: ' . $title,
			sprintf( "Hallo %s,\n\ndu bist von „%s“ (%s) abgemeldet.%s\n\nSchade, dass du nicht dabei sein kannst – vielleicht beim nächsten Mal: %s", $order->get_billing_first_name() ?: ( $user ? $user->display_name : '' ), $title, EventLabels::when( $f ), '' !== $refunded ? "\n" . $refunded : '', home_url( '/veranstaltungen/' ) )
		);
		return 'Du bist abgemeldet.' . ( '' !== $refunded ? ' ' . $refunded : '' );
	}

	/**
	 * Confirmation mail with calendar file, once per registration, when the order is paid (or free).
	 *
	 * @param int           $order_id Order ID.
	 * @param WC_Order|null $order    Order.
	 */
	public function confirm( int $order_id, $order = null ): void {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product || $item->get_meta( self::ITEM_CONFIRMED ) || $item->get_meta( EventRepository::ITEM_CANCELLED ) ) {
				continue;
			}
			$event_id = $this->events->event_id( (int) $item->get_product_id() );
			if ( ! $event_id ) {
				continue;
			}
			$item->update_meta_data( self::ITEM_CONFIRMED, current_time( 'mysql' ) );
			$item->save();
			$this->send_confirmation( $order, $item, $event_id );
		}
	}

	/**
	 * Daily: reminder mails for upcoming events.
	 */
	public function send_reminders(): int {
		$today = current_time( 'Y-m-d' );
		$sent  = 0;
		foreach ( $this->events->upcoming_with_registration( $today ) as $event_id ) {
			$f = $this->events->fields( $event_id );
			if ( ! RegistrationRules::reminder_due( $f['date'], $f['reminder_days'], $today ) ) {
				continue;
			}
			foreach ( $this->events->registration_orders( $event_id ) as $order ) {
				$item = EventRepository::holds_place( $order ) && $order->is_paid() ? $this->events->item_for( $order, $event_id ) : null;
				if ( ! $item || $item->get_meta( self::ITEM_REMINDED ) ) {
					continue;
				}
				$item->update_meta_data( self::ITEM_REMINDED, current_time( 'mysql' ) );
				$item->save();
				$this->send_reminder( $order, $event_id, $f );
				++$sent;
			}
		}
		return $sent;
	}

	/**
	 * Registrations get our confirmation (with calendar file) instead of the WooCommerce "order completed" mail; the
	 * receipt of a paid order ("order received") stays.
	 *
	 * @param bool          $enabled Whether the mail is sent.
	 * @param WC_Order|null $order   Order.
	 */
	public function skip_completed_mail( $enabled, $order = null ) {
		return $order instanceof WC_Order && $this->only_events( $order ) ? false : $enabled;
	}

	/**
	 * The office gets no "new order" mail for each free registration (the participant list is in the backend).
	 *
	 * @param bool          $enabled Whether the mail is sent.
	 * @param WC_Order|null $order   Order.
	 */
	public function skip_free_order_mail( $enabled, $order = null ) {
		if ( $order instanceof WC_Order && 0.0 === (float) $order->get_total() && $this->events->events_in_order( $order ) ) {
			return false;
		}
		return $enabled;
	}

	/**
	 * Whether every item of an order is an event registration.
	 */
	private function only_events( WC_Order $order ): bool {
		$items = $order->get_items();
		foreach ( $items as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product || ! $this->events->event_id( (int) $item->get_product_id() ) ) {
				return false;
			}
		}
		return (bool) $items;
	}

	/**
	 * Creates a completed 0 € order for the person.
	 */
	private function create_free_order( int $event_id, int $user_id ): ?WC_Order {
		$product = wc_get_product( $this->events->product_id( $event_id ) );
		if ( ! $product ) {
			return null;
		}
		try {
			$order = wc_create_order( array( 'customer_id' => $user_id, 'created_via' => 'bw_wm_register' ) );
			if ( is_wp_error( $order ) ) {
				return null;
			}
			$item_id = $order->add_product( $product, 1, array( 'subtotal' => 0, 'total' => 0 ) );
			$item    = $order->get_item( $item_id );
			if ( $item ) {
				$item->add_meta_data( '_bw_wm_price_basis', $this->registrations->is_member( $user_id ) ? 'member' : 'public', true );
				$item->add_meta_data( '_bw_wm_event_id', $event_id, true );
				$item->save();
			}
			$customer = new \WC_Customer( $user_id );
			$user     = get_userdata( $user_id );
			// The name shown in the box ("Du meldest dich an als …") is the profile name.
			$order->set_billing_first_name( $user && '' !== $user->first_name ? $user->first_name : $customer->get_billing_first_name() );
			$order->set_billing_last_name( $user && '' !== $user->last_name ? $user->last_name : $customer->get_billing_last_name() );
			$order->set_billing_email( $customer->get_billing_email() ?: ( $user ? $user->user_email : '' ) );
			$order->set_billing_country( $customer->get_billing_country() );
			$order->calculate_totals();
			$order->add_order_note( 'Kostenlose Anmeldung über die Veranstaltungsseite.' );
			$order->save();
			$order->payment_complete(); // Completes the order (OrderController) and reduces the stock.
			return $order;
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Refunds a paid registration in full; returns the sentence for the person ('' when nothing was refunded).
	 */
	private function refund( WC_Order $order, WC_Order_Item_Product $item, float $amount ): string {
		$args   = array(
			'amount'     => $amount,
			'reason'     => 'Abmeldung durch die Person',
			'order_id'   => $order->get_id(),
			'line_items' => array( $item->get_id() => array( 'qty' => 1, 'refund_total' => $amount ) ),
		);
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway && $gateway->supports( 'refunds' ) ) {
			$refund = wc_create_refund( $args + array( 'refund_payment' => true ) );
			if ( ! is_wp_error( $refund ) ) {
				return sprintf( 'Den Betrag von %s erstatten wir auf dein Zahlungsmittel zurück.', wp_strip_all_tags( wc_price( $amount ) ) );
			}
		}
		// Bank transfer or a failed automatic refund: recorded, the office pays back.
		$refund = wc_create_refund( $args + array( 'refund_payment' => false ) );
		if ( is_wp_error( $refund ) ) {
			$order->add_order_note( 'Abmeldung: Erstattung konnte nicht angelegt werden – bitte manuell erstatten.' );
		}
		$this->mailer->send(
			$this->mailer->office(),
			'Erstattung nötig: Bestellung ' . $order->get_order_number(),
			sprintf( "%s %s hat sich abgemeldet (Bestellung %s). Bitte %s manuell zurücküberweisen.\n\n%s", $order->get_billing_first_name(), $order->get_billing_last_name(), $order->get_order_number(), wp_strip_all_tags( wc_price( $amount ) ), $order->get_edit_order_url() )
		);
		return sprintf( 'Den Betrag von %s überweisen wir dir in den nächsten Tagen zurück.', wp_strip_all_tags( wc_price( $amount ) ) );
	}

	/**
	 * Sends the confirmation of one registration.
	 */
	private function send_confirmation( WC_Order $order, WC_Order_Item_Product $item, int $event_id ): void {
		$f      = $this->events->fields( $event_id );
		$title  = get_the_title( $event_id );
		$paid   = (float) $item->get_total() > 0;
		$until  = RegistrationRules::cancel_until( $f['date'], $f['cancel_days'], $paid );
		$lines  = array(
			sprintf( 'Hallo %s,', $order->get_billing_first_name() ),
			'',
			sprintf( 'du bist für „%s“ angemeldet.', $title ),
			'',
			'Termin: ' . EventLabels::when( $f ),
		);
		$place = EventLabels::place( $f );
		if ( '' !== $place ) {
			$lines[] = 'Ort: ' . $place;
		}
		if ( $paid ) {
			$lines[] = 'Betrag: ' . wp_strip_all_tags( wc_price( (float) $item->get_total() ) ) . ' (Bestellung ' . $order->get_order_number() . ')';
		}
		$lines[] = '';
		if ( EventLabels::is_online( $f ) ) {
			$lines[] = 'Den Zoom-Link schicken wir dir mit der Erinnerung vor dem Termin. Du findest ihn auch jederzeit unter „Meine Anmeldungen“.';
		}
		$reminder = RegistrationRules::reminder_day( $f['date'], $f['reminder_days'] );
		if ( '' !== $reminder ) {
			$lines[] = sprintf( 'Wir erinnern dich am %s per E-Mail.', EventLabels::day( $reminder ) );
		}
		$lines[] = 'Den Termin für deinen Kalender findest du im Anhang.';
		$lines[] = '';
		if ( '' !== $until ) {
			$lines[] = sprintf( 'Du kannst nicht teilnehmen? Bis %s kannst du dich selbst abmelden%s: %s', EventLabels::day( $until ), $paid ? ' – du bekommst den vollen Betrag zurück' : '', (string) get_permalink( $event_id ) . '#anmeldung' );
		}
		$file = $this->ics_file( $order, $event_id, $f, $title );
		$this->mailer->send( $order->get_billing_email(), 'Anmeldung bestätigt: ' . $title, implode( "\n", $lines ), '' !== $file ? array( $file ) : array() );
		if ( '' !== $file ) {
			wp_delete_file( $file );
			rmdir( dirname( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- own temporary folder.
		}
	}

	/**
	 * Sends the reminder of one registration.
	 *
	 * @param array<string, mixed> $f Event fields.
	 */
	private function send_reminder( WC_Order $order, int $event_id, array $f ): void {
		$title = get_the_title( $event_id );
		$lines = array(
			sprintf( 'Hallo %s,', $order->get_billing_first_name() ),
			'',
			sprintf( 'bald ist es so weit: „%s“', $title ),
			'',
			'Termin: ' . EventLabels::when( $f ),
		);
		$place = EventLabels::place( $f );
		if ( '' !== $place ) {
			$lines[] = 'Ort: ' . $place;
		}
		if ( EventLabels::is_online( $f ) && '' !== $f['zoom_link'] ) {
			$lines[] = '';
			$lines[] = 'Zoom-Link: ' . $f['zoom_link'];
			if ( '' !== $f['zoom_access'] ) {
				$lines[] = 'Zugangsdaten: ' . $f['zoom_access'];
			}
			$lines[] = 'Bitte gib den Link nicht weiter.';
		}
		$lines[] = '';
		$lines[] = 'Alle Infos: ' . (string) get_permalink( $event_id );
		$lines[] = '';
		$lines[] = 'Wir freuen uns auf dich!';
		$this->mailer->send( $order->get_billing_email(), 'Erinnerung: ' . $title, implode( "\n", $lines ) );
	}

	/**
	 * Writes the calendar file to a temporary path ('' when that fails).
	 *
	 * @param array<string, mixed> $f Event fields.
	 */
	private function ics_file( WC_Order $order, int $event_id, array $f, string $title ): string {
		if ( '' === $f['date'] ) {
			return '';
		}
		$ics = Ics::build(
			array(
				'uid'         => sprintf( 'appa-event-%d-order-%d@%s', $event_id, $order->get_id(), (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
				'title'       => $title,
				'date'        => $f['date'],
				'time'        => $f['time'],
				'end_date'    => $f['end_date'],
				'end_time'    => $f['end_time'],
				'location'    => EventLabels::place( $f ),
				'url'         => (string) get_permalink( $event_id ),
				'description' => 'Alle Infos und Abmeldung: ' . (string) get_permalink( $event_id ),
			),
			gmdate( 'Ymd\THis\Z' )
		);
		$dir  = trailingslashit( get_temp_dir() ) . 'bw-wm-' . wp_generate_password( 8, false );
		$file = $dir . '/termin.ics';
		return wp_mkdir_p( $dir ) && false !== file_put_contents( $file, $ics ) ? $file : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- temporary attachment.
	}

	/**
	 * Remembers a message for the person's next view of the registration box.
	 */
	private function notice( int $user_id, string $message ): void {
		set_transient( self::NOTICE . $user_id, $message, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Takes the remembered message (once).
	 */
	public static function take_notice( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}
		$message = (string) get_transient( self::NOTICE . $user_id );
		if ( '' !== $message ) {
			delete_transient( self::NOTICE . $user_id );
		}
		return $message;
	}
}
