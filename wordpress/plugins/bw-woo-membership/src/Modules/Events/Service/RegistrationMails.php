<?php
/**
 * Mails of the registration flow.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

use BW\WooMembership\Support\Mailer;
use WC_Order;

/**
 * Confirmation (with calendar file), reminder, cancellation, payment link and the office note about a refund. The
 * texts are built by pure static methods (tested), the sending needs WordPress.
 */
final class RegistrationMails {

	/**
	 * Builds the service.
	 */
	public function __construct( private readonly Mailer $mailer ) {
	}

	/**
	 * Office address.
	 */
	public function office(): string {
		return $this->mailer->office();
	}

	/**
	 * Confirmation of one registration with the calendar file attached.
	 *
	 * @param array<string, mixed> $f Event fields.
	 */
	public function confirmation( WC_Order $order, int $event_id, array $f, float $amount ): void {
		$title = get_the_title( $event_id );
		$paid  = $amount > 0;
		$text  = self::confirmation_text(
			array(
				'name'     => $order->get_billing_first_name(),
				'title'    => $title,
				'when'     => EventLabels::when( $f ),
				'place'    => EventLabels::place( $f ),
				'amount'   => $paid ? wp_strip_all_tags( wc_price( $amount ) ) . ' (Bestellung ' . $order->get_order_number() . ')' : '',
				'online'   => EventLabels::is_online( $f ),
				'reminder' => EventLabels::day( RegistrationRules::reminder_day( $f['date'], $f['reminder_days'] ) ),
				'until'    => EventLabels::day( RegistrationRules::cancel_until( $f['date'], $f['cancel_days'], $paid ) ),
				'refund'   => $paid,
				'url'      => (string) get_permalink( $event_id ) . '#anmeldung',
			)
		);
		$file = $this->ics_file( $order, $event_id, $f, $title );
		$this->mailer->send( $order->get_billing_email(), 'Anmeldung bestätigt: ' . $title, $text, '' !== $file ? array( $file ) : array() );
		if ( '' !== $file ) {
			wp_delete_file( $file );
			rmdir( dirname( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- own temporary folder.
		}
	}

	/**
	 * Text of the confirmation.
	 *
	 * @param array{name: string, title: string, when: string, place: string, amount: string, online: bool, reminder: string, until: string, refund: bool, url: string} $v Values.
	 */
	public static function confirmation_text( array $v ): string {
		$lines = array( sprintf( 'Hallo %s,', $v['name'] ), '', sprintf( 'du bist für „%s“ angemeldet.', $v['title'] ), '', 'Termin: ' . $v['when'] );
		if ( '' !== $v['place'] ) {
			$lines[] = 'Ort: ' . $v['place'];
		}
		if ( '' !== $v['amount'] ) {
			$lines[] = 'Betrag: ' . $v['amount'];
		}
		$lines[] = '';
		if ( $v['online'] ) {
			$lines[] = 'Den Zoom-Link schicken wir dir mit der Erinnerung vor dem Termin. Du findest ihn auch jederzeit unter „Meine Anmeldungen“.';
		}
		if ( '' !== $v['reminder'] ) {
			$lines[] = sprintf( 'Wir erinnern dich am %s per E-Mail.', $v['reminder'] );
		}
		$lines[] = 'Den Termin für deinen Kalender findest du im Anhang.';
		if ( '' !== $v['until'] ) {
			$lines[] = '';
			$lines[] = sprintf( 'Du kannst nicht teilnehmen? Bis %s kannst du dich selbst abmelden%s: %s', $v['until'], $v['refund'] ? ' – du bekommst den vollen Betrag zurück' : '', $v['url'] );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Reminder with the Zoom data for online events.
	 *
	 * @param array<string, mixed> $f Event fields.
	 */
	public function reminder( WC_Order $order, int $event_id, array $f ): void {
		$title = get_the_title( $event_id );
		$this->mailer->send(
			$order->get_billing_email(),
			'Erinnerung: ' . $title,
			self::reminder_text(
				array(
					'name'   => $order->get_billing_first_name(),
					'title'  => $title,
					'when'   => EventLabels::when( $f ),
					'place'  => EventLabels::place( $f ),
					'zoom'   => EventLabels::is_online( $f ) ? (string) $f['zoom_link'] : '',
					'access' => EventLabels::is_online( $f ) ? (string) $f['zoom_access'] : '',
					'url'    => (string) get_permalink( $event_id ),
				)
			)
		);
	}

	/**
	 * Text of the reminder.
	 *
	 * @param array{name: string, title: string, when: string, place: string, zoom: string, access: string, url: string} $v Values.
	 */
	public static function reminder_text( array $v ): string {
		$lines = array( sprintf( 'Hallo %s,', $v['name'] ), '', sprintf( 'bald ist es so weit: „%s“', $v['title'] ), '', 'Termin: ' . $v['when'] );
		if ( '' !== $v['place'] ) {
			$lines[] = 'Ort: ' . $v['place'];
		}
		if ( '' !== $v['zoom'] ) {
			$lines[] = '';
			$lines[] = 'Zoom-Link: ' . $v['zoom'];
			if ( '' !== $v['access'] ) {
				$lines[] = 'Zugangsdaten: ' . $v['access'];
			}
			$lines[] = 'Bitte gib den Link nicht weiter.';
		}
		array_push( $lines, '', 'Alle Infos: ' . $v['url'], '', 'Wir freuen uns auf dich!' );
		return implode( "\n", $lines );
	}

	/**
	 * Confirmation of a cancellation.
	 *
	 * @param array<string, mixed> $f Event fields.
	 */
	public function cancellation( WC_Order $order, int $event_id, array $f, string $refund_note ): void {
		$this->mailer->send(
			$order->get_billing_email(),
			'Abmeldung bestätigt: ' . get_the_title( $event_id ),
			sprintf(
				"Hallo %s,\n\ndu bist von „%s“ (%s) abgemeldet.%s\n\nSchade, dass du nicht dabei sein kannst – vielleicht beim nächsten Mal: %s",
				$order->get_billing_first_name(),
				get_the_title( $event_id ),
				EventLabels::when( $f ),
				'' !== $refund_note ? "\n" . $refund_note : '',
				home_url( '/veranstaltungen/' )
			)
		);
	}

	/**
	 * Payment link of a registration added by the office.
	 *
	 * @param array<string, mixed> $f Event fields.
	 */
	public function payment_link( WC_Order $order, int $event_id, array $f ): void {
		$this->mailer->send(
			$order->get_billing_email(),
			'Deine Anmeldung: ' . get_the_title( $event_id ),
			sprintf(
				"Hallo %s,\n\nwir haben dich für „%s“ angemeldet.\nTermin: %s\n\nBitte bezahle %s über diesen Link: %s\n\nSobald die Zahlung da ist, bekommst du die Bestätigung mit dem Termin für deinen Kalender.",
				$order->get_billing_first_name(),
				get_the_title( $event_id ),
				EventLabels::when( $f ),
				wp_strip_all_tags( wc_price( (float) $order->get_total() ) ),
				$order->get_checkout_payment_url()
			)
		);
	}

	/**
	 * Note to the office: pay back by hand (bank transfer or failed automatic refund).
	 */
	public function manual_refund( WC_Order $order, float $amount ): void {
		$this->mailer->send(
			$this->mailer->office(),
			'Erstattung nötig: Bestellung ' . $order->get_order_number(),
			sprintf( "%s %s hat sich abgemeldet (Bestellung %s). Bitte %s manuell zurücküberweisen.\n\n%s", $order->get_billing_first_name(), $order->get_billing_last_name(), $order->get_order_number(), wp_strip_all_tags( wc_price( $amount ) ), $order->get_edit_order_url() )
		);
	}

	/**
	 * Writes the calendar file to a temporary folder ('' when that fails).
	 *
	 * @param array<string, mixed> $f Event fields.
	 */
	private function ics_file( WC_Order $order, int $event_id, array $f, string $title ): string {
		if ( '' === $f['date'] ) {
			return '';
		}
		$ics  = Ics::build(
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
}
