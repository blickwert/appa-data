<?php
/**
 * Mails of the registration flow.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

use BW\WooMembership\Modules\Membership\Service\MemberPages;
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
				/* translators: 1: amount, 2: order number */
				'amount'   => $paid ? sprintf( __( '%1$s (Bestellung %2$s)', 'bw-woo-membership' ), wp_strip_all_tags( wc_price( $amount ) ), $order->get_order_number() ) : '',
				'online'   => EventLabels::is_online( $f ),
				'reminder' => EventLabels::day( RegistrationRules::reminder_day( $f['date'], $f['reminder_days'] ) ),
				'until'    => EventLabels::day( RegistrationRules::cancel_until( $f['date'], $f['cancel_days'], $paid ) ),
				'refund'   => $paid,
				'url'      => (string) get_permalink( $event_id ) . '#anmeldung',
			)
		);
		$file = $this->ics_file( $order, $event_id, $f, $title );
		$this->mailer->send( $order->get_billing_email(), /* translators: %s: event title */ sprintf( __( 'Anmeldung bestätigt: %s', 'bw-woo-membership' ), $title ), $text, '' !== $file ? array( $file ) : array() );
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
		$lines = array(
			/* translators: %s: first name */
			sprintf( __( 'Hallo %s,', 'bw-woo-membership' ), $v['name'] ),
			'',
			/* translators: %s: event title */
			sprintf( __( 'du bist für „%s“ angemeldet.', 'bw-woo-membership' ), $v['title'] ),
			'',
			/* translators: %s: date and time */
			sprintf( __( 'Termin: %s', 'bw-woo-membership' ), $v['when'] ),
		);
		if ( '' !== $v['place'] ) {
			/* translators: %s: place */
			$lines[] = sprintf( __( 'Ort: %s', 'bw-woo-membership' ), $v['place'] );
		}
		if ( '' !== $v['amount'] ) {
			/* translators: %s: amount */
			$lines[] = sprintf( __( 'Betrag: %s', 'bw-woo-membership' ), $v['amount'] );
		}
		$lines[] = '';
		if ( $v['online'] ) {
			$lines[] = __( 'Den Zoom-Link schicken wir dir mit der Erinnerung vor dem Termin. Du findest ihn auch jederzeit unter „Meine Anmeldungen“.', 'bw-woo-membership' );
		}
		if ( '' !== $v['reminder'] ) {
			/* translators: %s: day of the reminder */
			$lines[] = sprintf( __( 'Wir erinnern dich am %s per E-Mail.', 'bw-woo-membership' ), $v['reminder'] );
		}
		$lines[] = __( 'Den Termin für deinen Kalender findest du im Anhang.', 'bw-woo-membership' );
		if ( '' !== $v['until'] ) {
			$lines[] = '';
			$lines[] = $v['refund']
				/* translators: 1: last day to cancel, 2: event address */
				? sprintf( __( 'Du kannst nicht teilnehmen? Bis %1$s kannst du dich selbst abmelden – du bekommst den vollen Betrag zurück: %2$s', 'bw-woo-membership' ), $v['until'], $v['url'] )
				/* translators: 1: last day to cancel, 2: event address */
				: sprintf( __( 'Du kannst nicht teilnehmen? Bis %1$s kannst du dich selbst abmelden: %2$s', 'bw-woo-membership' ), $v['until'], $v['url'] );
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
			/* translators: %s: event title */
			sprintf( __( 'Erinnerung: %s', 'bw-woo-membership' ), $title ),
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
		$lines = array(
			/* translators: %s: first name */
			sprintf( __( 'Hallo %s,', 'bw-woo-membership' ), $v['name'] ),
			'',
			/* translators: %s: event title */
			sprintf( __( 'bald ist es so weit: „%s“', 'bw-woo-membership' ), $v['title'] ),
			'',
			/* translators: %s: date and time */
			sprintf( __( 'Termin: %s', 'bw-woo-membership' ), $v['when'] ),
		);
		if ( '' !== $v['place'] ) {
			/* translators: %s: place */
			$lines[] = sprintf( __( 'Ort: %s', 'bw-woo-membership' ), $v['place'] );
		}
		if ( '' !== $v['zoom'] ) {
			$lines[] = '';
			/* translators: %s: Zoom address */
			$lines[] = sprintf( __( 'Zoom-Link: %s', 'bw-woo-membership' ), $v['zoom'] );
			if ( '' !== $v['access'] ) {
				/* translators: %s: Zoom access data */
				$lines[] = sprintf( __( 'Zugangsdaten: %s', 'bw-woo-membership' ), $v['access'] );
			}
			$lines[] = __( 'Bitte gib den Link nicht weiter.', 'bw-woo-membership' );
		}
		/* translators: %s: event address */
		array_push( $lines, '', sprintf( __( 'Alle Infos: %s', 'bw-woo-membership' ), $v['url'] ), '', __( 'Wir freuen uns auf dich!', 'bw-woo-membership' ) );
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
			/* translators: %s: event title */
			sprintf( __( 'Abmeldung bestätigt: %s', 'bw-woo-membership' ), get_the_title( $event_id ) ),
			sprintf(
				/* translators: 1: first name, 2: event title, 3: date, 4: refund note (with line break) or empty, 5: address of the event list */
				__( "Hallo %1\$s,\n\ndu bist von „%2\$s“ (%3\$s) abgemeldet.%4\$s\n\nSchade, dass du nicht dabei sein kannst – vielleicht beim nächsten Mal: %5\$s", 'bw-woo-membership' ),
				$order->get_billing_first_name(),
				get_the_title( $event_id ),
				EventLabels::when( $f ),
				'' !== $refund_note ? "\n" . $refund_note : '',
				MemberPages::events_url()
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
			/* translators: %s: event title */
			sprintf( __( 'Deine Anmeldung: %s', 'bw-woo-membership' ), get_the_title( $event_id ) ),
			sprintf(
				/* translators: 1: first name, 2: event title, 3: date, 4: amount, 5: payment address */
				__( "Hallo %1\$s,\n\nwir haben dich für „%2\$s“ angemeldet.\nTermin: %3\$s\n\nBitte bezahle %4\$s über diesen Link: %5\$s\n\nSobald die Zahlung da ist, bekommst du die Bestätigung mit dem Termin für deinen Kalender.", 'bw-woo-membership' ),
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
			/* translators: %s: order number */
			sprintf( __( 'Erstattung nötig: Bestellung %s', 'bw-woo-membership' ), $order->get_order_number() ),
			/* translators: 1: first name, 2: last name, 3: order number, 4: amount, 5: order address in the admin */
			sprintf( __( "%1\$s %2\$s hat sich abgemeldet (Bestellung %3\$s). Bitte %4\$s manuell zurücküberweisen.\n\n%5\$s", 'bw-woo-membership' ), $order->get_billing_first_name(), $order->get_billing_last_name(), $order->get_order_number(), wp_strip_all_tags( wc_price( $amount ) ), $order->get_edit_order_url() )
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
				/* translators: %s: event address */
				'description' => sprintf( __( 'Alle Infos und Abmeldung: %s', 'bw-woo-membership' ), (string) get_permalink( $event_id ) ),
			),
			gmdate( 'Ymd\THis\Z' )
		);
		$dir  = trailingslashit( get_temp_dir() ) . 'bw-wm-' . wp_generate_password( 8, false );
		$file = $dir . '/termin.ics';
		return wp_mkdir_p( $dir ) && false !== file_put_contents( $file, $ics ) ? $file : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- temporary attachment.
	}
}
