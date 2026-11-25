<?php
/**
 * Registration requests and WooCommerce glue of the registration flow.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Repository\NoticeRepository;
use BW\WooMembership\Modules\Events\Service\RegistrationFlow;
use WC_Order;

/**
 * Handles the forms (register with one click, cancel), wires the flow to WooCommerce (confirmation on payment,
 * daily reminders) and switches off WooCommerce mails that the flow replaces. The rules live in RegistrationFlow.
 */
final class RegistrationController {

	public const REGISTER = 'bw_wm_register';
	public const CANCEL   = 'bw_wm_cancel_registration';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly RegistrationFlow $flow,
		private readonly NoticeRepository $notices,
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
		add_filter( 'woocommerce_email_enabled_customer_on_hold_order', array( $this, 'skip_office_on_hold_mail' ), 10, 2 );
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'payable_on_hold' ), 10, 2 );
		add_filter( 'woocommerce_should_send_low_stock_notification', array( $this, 'no_stock_mail' ), 10, 2 );
		add_filter( 'woocommerce_should_send_no_stock_notification', array( $this, 'no_stock_mail' ), 10, 2 );
	}

	/**
	 * One-click registration for a free event (logged-in people).
	 */
	public function handle_register(): void {
		$event_id = isset( $_POST['event'] ) ? absint( $_POST['event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below.
		$user_id  = get_current_user_id();
		$back     = $event_id ? (string) get_permalink( $event_id ) . '#anmeldung' : home_url( '/' );
		if ( $event_id && $user_id > 0 && check_admin_referer( self::REGISTER . '_' . $event_id ) ) {
			$this->notices->remember( $user_id, $this->flow->register_free( $event_id, $user_id ) );
		}
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Cancels the person's own registration (from the event page or "Meine Anmeldungen").
	 */
	public function handle_cancel(): void {
		$event_id = isset( $_POST['event'] ) ? absint( $_POST['event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below.
		$user_id  = get_current_user_id();
		$back     = isset( $_POST['back'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['back'] ) ), '' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below.
		$back     = '' !== $back ? $back : ( $event_id ? (string) get_permalink( $event_id ) . '#anmeldung' : home_url( '/' ) );
		if ( $event_id && $user_id > 0 && check_admin_referer( self::CANCEL . '_' . $event_id ) ) {
			$this->notices->remember( $user_id, $this->flow->cancel( $event_id, $user_id ) );
		}
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Paid (or free) order: confirmation of its registrations.
	 *
	 * @param int           $order_id Order ID.
	 * @param WC_Order|null $order    Order.
	 */
	public function confirm( int $order_id, mixed $order = null ): void {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			$this->flow->confirm( $order );
		}
	}

	/**
	 * Daily reminders.
	 */
	public function send_reminders(): void {
		$this->flow->send_reminders( current_time( 'Y-m-d' ) );
	}

	/**
	 * Registrations get the flow's confirmation (with calendar file) instead of the WooCommerce "order completed"
	 * mail; the receipt of a paid order ("order received") stays.
	 *
	 * @param mixed         $enabled Whether the mail is sent.
	 * @param WC_Order|null $order   Order.
	 */
	public function skip_completed_mail( mixed $enabled, mixed $order = null ): bool {
		return $order instanceof WC_Order && $this->flow->only_events( $order ) ? false : (bool) $enabled;
	}

	/**
	 * The office gets no "new order" mail for each free registration (the participant list is in the backend).
	 *
	 * @param mixed         $enabled Whether the mail is sent.
	 * @param WC_Order|null $order   Order.
	 */
	public function skip_free_order_mail( mixed $enabled, mixed $order = null ): bool {
		return $order instanceof WC_Order && 0.0 === (float) $order->get_total() && $this->events->events_in_order( $order ) ? false : (bool) $enabled;
	}

	/**
	 * Registrations added by the office with an amount get the flow's mail with the payment link instead.
	 *
	 * @param mixed         $enabled Whether the mail is sent.
	 * @param WC_Order|null $order   Order.
	 */
	public function skip_office_on_hold_mail( mixed $enabled, mixed $order = null ): bool {
		return $order instanceof WC_Order && $order->get_meta( RegistrationFlow::ADDED_BY_OFFICE ) ? false : (bool) $enabled;
	}

	/**
	 * Registrations added by the office can be paid through the payment link while "on hold".
	 *
	 * @param mixed         $statuses Statuses that can be paid.
	 * @param WC_Order|null $order    Order.
	 * @return string[]
	 */
	public function payable_on_hold( mixed $statuses, mixed $order = null ): array {
		$statuses = (array) $statuses;
		if ( $order instanceof WC_Order && $order->get_meta( RegistrationFlow::ADDED_BY_OFFICE ) ) {
			$statuses[] = 'on-hold';
		}
		return $statuses;
	}

	/**
	 * No "low stock" / "out of stock" mails for event products (places are shown on the participants page).
	 *
	 * @param mixed $send       Whether to send.
	 * @param mixed $product_id Product.
	 */
	public function no_stock_mail( mixed $send, mixed $product_id = 0 ): bool {
		return $this->events->event_id( (int) $product_id ) ? false : (bool) $send;
	}
}
