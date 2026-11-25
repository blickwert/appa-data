<?php
/**
 * Registrations in the event admin list.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Core\Setup;
use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Repository\WaitlistRepository;
use BW\WooMembership\Support\Csv;

/**
 * Column "Anmeldungen" (registered of maximum, free places, waiting list) with a link to the participants page, and
 * the participant list as CSV.
 */
final class AdminController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly WaitlistRepository $waitlist,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'manage_' . EventRepository::POST_TYPE . '_posts_columns', array( $this, 'columns' ), 20 );
		add_action( 'manage_' . EventRepository::POST_TYPE . '_posts_custom_column', array( $this, 'column' ), 10, 2 );
		add_action( 'admin_post_bw_wm_participants', array( $this, 'export' ) );
	}

	/**
	 * Adds the column.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$columns['bw_wm_registrations'] = __( 'Anmeldungen', 'bw-woo-membership' );
		return $columns;
	}

	/**
	 * Column value: count, capacity and CSV link.
	 */
	public function column( string $column, int $event_id ): void {
		if ( 'bw_wm_registrations' !== $column ) {
			return;
		}
		$fields = $this->events->fields( $event_id );
		if ( ! $fields['registration'] ) {
			echo '–';
			return;
		}
		$count = $this->events->count_registrations( $event_id );
		echo '<strong>' . esc_html( '' !== $fields['capacity'] ? sprintf( '%d von %s', $count, $fields['capacity'] ) : sprintf( '%d angemeldet', $count ) ) . '</strong>';
		if ( '' !== $fields['capacity'] ) {
			echo '<br>' . esc_html( sprintf( 'frei: %d', max( 0, (int) $fields['capacity'] - $count ) ) );
		}
		$waiting = $this->waitlist->count( $event_id );
		if ( $waiting > 0 ) {
			/* translators: %d: people on the waiting list */
			echo '<br>' . esc_html( sprintf( __( 'Warteliste: %d', 'bw-woo-membership' ), $waiting ) );
		}
		if ( current_user_can( Setup::CAPABILITY ) ) {
			echo '<br><a href="' . esc_url( ParticipantsController::url( $event_id ) ) . '">' . esc_html__( 'Teilnehmende', 'bw-woo-membership' ) . '</a>';
		}
	}

	/**
	 * CSV of the participants of one event.
	 */
	public function export(): void {
		if ( ! current_user_can( Setup::CAPABILITY ) || ! check_admin_referer( 'bw_wm_participants' ) ) {
			wp_die( esc_html__( 'Nicht erlaubt.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		$event_id = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0;
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="teilnehmer-' . sanitize_title( get_the_title( $event_id ) ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- streaming the export.
		fputcsv( $out, array( 'Vorname', 'Nachname', 'E-Mail', 'Mitglied', 'Betrag (EUR)', 'Status', 'Bestellung', 'Datum' ), ';' );
		foreach ( $this->events->registration_orders( $event_id ) as $order ) {
			$basis = '';
			foreach ( $order->get_items() as $item ) {
				$basis = $basis ? $basis : (string) $item->get_meta( '_bw_wm_price_basis' );
			}
			fputcsv(
				$out,
				array_map(
					array( Csv::class, 'safe' ),
					array(
						$order->get_billing_first_name(),
						$order->get_billing_last_name(),
						$order->get_billing_email(),
						'member' === $basis ? 'ja' : 'nein',
						number_format( (float) $order->get_total(), 2, ',', '' ),
						wc_get_order_status_name( $order->get_status() ),
						(string) $order->get_order_number(),
						$order->get_date_created() ? $order->get_date_created()->date( 'd.m.Y' ) : '',
					)
				),
				';'
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
