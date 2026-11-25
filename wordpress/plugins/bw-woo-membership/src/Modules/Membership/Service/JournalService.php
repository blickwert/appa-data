<?php
/**
 * Fee journal: paid orders of a month as rows for the bookkeeping.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use WC_Order;

/**
 * One row per order paid in the month (membership fees, renewals, event registrations, anything else), with the
 * Stripe fee and net amount when the WooCommerce Stripe gateway recorded them, and a sum row at the end.
 */
final class JournalService {

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly MembershipOrderRepository $orders,
		private readonly MemberRepository $members,
	) {
	}

	/**
	 * CSV rows (header, entries, sum) of a period ("Y-m-d" to "Y-m-d") and its label for the file name.
	 *
	 * @return array{label: string, rows: list<list<string>>}
	 */
	public function export( string $from_day, string $to_day ): array {
		[ $from, $to ] = Journal::range( $from_day, $to_day, wp_timezone() );
		$entries       = array();
		foreach ( $this->paid_orders( $from->getTimestamp(), $to->getTimestamp() ) as $order ) {
			$entries[] = $this->entry( $order );
		}
		usort( $entries, static fn( array $a, array $b ): int => array( $a['date'], $a['number'] ) <=> array( $b['date'], $b['number'] ) );

		$rows = array( Journal::header() );
		foreach ( $entries as $e ) {
			$rows[] = array( $e['date_label'], $e['number'], $e['kind'], $e['member'], $e['first'], $e['last'], $e['email'], Journal::money( $e['total'] ), $e['refunded'] > 0 ? Journal::money( $e['refunded'] ) : '', $e['method'], $e['has_fee'] ? Journal::money( $e['fee'] ) : '', Journal::money( $e['net'] ), $e['transaction'] );
		}
		$sum    = Journal::totals( $entries );
		$rows[] = array(
			/* translators: 1: first day, 2: last day */
			sprintf( __( 'Summe %1$s–%2$s', 'bw-woo-membership' ), $from->format( 'd.m.Y' ), $to->format( 'd.m.Y' ) ),
			/* translators: %d: number of payments */
			sprintf( __( '%d Zahlungen', 'bw-woo-membership' ), count( $entries ) ),
			'',
			'',
			'',
			'',
			'',
			Journal::money( $sum['total'] ),
			Journal::money( $sum['refunded'] ),
			'',
			Journal::money( $sum['fee'] ),
			Journal::money( $sum['net'] ),
			'',
		);
		return array( 'label' => $from->format( 'Y-m-d' ) . '_' . $to->format( 'Y-m-d' ), 'rows' => $rows );
	}

	/**
	 * Orders with a payment date in the period (refunded ones included: the refund shows in its own column).
	 *
	 * @return WC_Order[]
	 */
	private function paid_orders( int $from, int $to ): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}
		return array_values(
			array_filter(
				wc_get_orders(
					array(
						'type'      => 'shop_order',
						'status'    => array( 'wc-processing', 'wc-completed', 'wc-refunded' ),
						'date_paid' => $from . '...' . $to,
						'limit'     => -1,
					)
				),
				static fn( $order ): bool => $order instanceof WC_Order
			)
		);
	}

	/**
	 * Journal entry of one order.
	 *
	 * @return array<string, mixed>
	 */
	private function entry( WC_Order $order ): array {
		$paid     = $order->get_date_paid();
		$total    = (float) $order->get_total();
		$refunded = (float) $order->get_total_refunded();
		$fee_raw  = $order->get_meta( '_stripe_fee' );
		$net_raw  = $order->get_meta( '_stripe_net' );
		$fee      = '' !== (string) $fee_raw ? (float) $fee_raw : 0.0;
		$user_id  = $order->get_customer_id();
		return array(
			'date'        => $paid ? $paid->getTimestamp() : 0,
			'date_label'  => $paid ? $paid->date_i18n( 'd.m.Y' ) : '',
			'number'      => (string) $order->get_order_number(),
			'kind'        => $this->kind( $order ),
			'member'      => $user_id > 0 ? $this->members->number( $user_id ) : '',
			'first'       => $order->get_billing_first_name(),
			'last'        => $order->get_billing_last_name(),
			'email'       => $order->get_billing_email(),
			'total'       => $total,
			'refunded'    => $refunded,
			'method'      => $order->get_payment_method_title(),
			'has_fee'     => '' !== (string) $fee_raw,
			'fee'         => $fee,
			'net'         => Journal::net( $total, $refunded, $fee, '' !== (string) $net_raw ? (float) $net_raw : null ),
			'transaction' => $order->get_transaction_id(),
		);
	}

	/**
	 * "Mitgliedsbeitrag (Art)", with "Verlängerung" for renewals of WooCommerce Subscriptions; otherwise the items.
	 */
	private function kind( WC_Order $order ): string {
		$type = $this->orders->order_type( $order );
		if ( '' !== $type ) {
			$renewal = function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order );
			return $renewal
				/* translators: %s: membership type */
				? sprintf( __( 'Mitgliedsbeitrag %s – Verlängerung', 'bw-woo-membership' ), MembershipTypes::label( $type ) )
				/* translators: %s: membership type */
				: sprintf( __( 'Mitgliedsbeitrag %s', 'bw-woo-membership' ), MembershipTypes::label( $type ) );
		}
		return implode( ', ', array_map( static fn( $item ): string => $item->get_name(), array_values( $order->get_items() ) ) );
	}
}
