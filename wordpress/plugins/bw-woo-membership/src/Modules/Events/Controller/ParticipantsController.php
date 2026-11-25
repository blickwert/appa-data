<?php
/**
 * Participants page of an event (backend).
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Core\Setup;
use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Repository\WaitlistRepository;
use BW\WooMembership\Modules\Events\Service\EventLabels;
use BW\WooMembership\Modules\Events\Service\EventPricing;
use WC_Order;

/**
 * "Veranstaltungen › Teilnehmende": places (maximum, registered, free), the list of registrations and the waiting
 * list, and a form with which the office adds a person (existing account or new one), free or with a payment link.
 */
final class ParticipantsController {

	public const PAGE = 'bw-wm-participants';
	public const ADD  = 'bw_wm_add_participant';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly WaitlistRepository $waitlist,
		private readonly RegistrationController $registrations,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::ADD, array( $this, 'handle_add' ) );
		add_filter( 'bw_appa_event_editor_info', array( $this, 'editor_info' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_action' ), 10, 2 );
	}

	/**
	 * Submenu "Teilnehmende" below "Veranstaltungen".
	 */
	public function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . EventRepository::POST_TYPE,
			__( 'Teilnehmende', 'bw-woo-membership' ),
			__( 'Teilnehmende', 'bw-woo-membership' ),
			Setup::CAPABILITY,
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Address of the page for an event.
	 */
	public static function url( int $event_id ): string {
		return add_query_arg( array( 'post_type' => EventRepository::POST_TYPE, 'page' => self::PAGE, 'event' => $event_id ), admin_url( 'edit.php' ) );
	}

	/**
	 * Link "Teilnehmende" in the event list.
	 *
	 * @param array<string, string> $actions Row actions.
	 * @param \WP_Post              $post    Post.
	 * @return array<string, string>
	 */
	public function row_action( $actions, $post ) {
		if ( $post instanceof \WP_Post && EventRepository::POST_TYPE === $post->post_type && current_user_can( Setup::CAPABILITY ) && $this->events->fields( $post->ID )['registration'] ) {
			$actions['bw_wm_participants'] = '<a href="' . esc_url( self::url( $post->ID ) ) . '">' . esc_html__( 'Teilnehmende', 'bw-woo-membership' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Figures for the editor sidebar of bw-appa.
	 *
	 * @param array<string, mixed> $info    Info.
	 * @param int                  $post_id Event.
	 * @return array<string, mixed>
	 */
	public function editor_info( $info, $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || ! current_user_can( Setup::CAPABILITY ) ) {
			return $info;
		}
		$f = $this->events->fields( $post_id );
		return array(
			'enabled'    => $f['registration'],
			'registered' => $this->events->count_registrations( $post_id ),
			'capacity'   => $f['capacity'],
			'left'       => EventPricing::places_left( $f['capacity'], $this->events->count_registrations( $post_id ) ),
			'waitlist'   => $this->waitlist->count( $post_id ),
			'url'        => self::url( $post_id ),
		);
	}

	/**
	 * Adds a person to an event.
	 */
	public function handle_add(): void {
		$event_id = isset( $_POST['event'] ) ? absint( $_POST['event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked below.
		if ( ! current_user_can( Setup::CAPABILITY ) || ! $event_id || ! check_admin_referer( self::ADD . '_' . $event_id ) ) {
			wp_die( esc_html__( 'Nicht erlaubt.', 'bw-woo-membership' ), '', array( 'response' => 403 ) );
		}
		$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$first   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$amount  = isset( $_POST['amount'] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) : 0.0;
		$mode    = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'free';
		$notify  = ! empty( $_POST['notify'] );
		$amount  = 'pay' === $mode ? max( 0.0, round( $amount, 2 ) ) : 0.0;
		$message = $this->add( $event_id, $email, $first, $last, $amount, $notify );
		wp_safe_redirect( add_query_arg( 'bw_wm_msg', rawurlencode( $message ), self::url( $event_id ) ) );
		exit;
	}

	/**
	 * Adds a person (finds or creates the account) and returns the message for the office.
	 */
	public function add( int $event_id, string $email, string $first, string $last, float $amount, bool $notify ): string {
		if ( ! is_email( $email ) ) {
			return 'Bitte eine gültige E-Mail-Adresse eintragen.';
		}
		if ( ! $this->events->product_id( $event_id ) || ! $this->events->fields( $event_id )['registration'] ) {
			return 'Für diese Veranstaltung ist die Anmeldung nicht eingeschaltet.';
		}
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			if ( '' === $first || '' === $last ) {
				return 'Für eine neue Person bitte Vor- und Nachname eintragen.';
			}
			// New customer account; WooCommerce mails the access (set a password) to the person.
			$user_id = wc_create_new_customer( $email, '', '', array( 'first_name' => $first, 'last_name' => $last ) );
			if ( is_wp_error( $user_id ) ) {
				return 'Das Konto konnte nicht angelegt werden: ' . $user_id->get_error_message();
			}
			update_user_meta( $user_id, 'billing_first_name', $first );
			update_user_meta( $user_id, 'billing_last_name', $last );
			update_user_meta( $user_id, 'billing_email', $email );
			$user = get_userdata( $user_id );
		} elseif ( '' !== $first && '' === $user->first_name ) {
			update_user_meta( $user->ID, 'first_name', $first );
			update_user_meta( $user->ID, 'last_name', $last );
		}
		$existing = $this->events->registration_of( $event_id, $user->ID );
		if ( null !== $existing && EventRepository::holds_place( $existing ) ) {
			return sprintf( '%s ist bereits angemeldet.', $email );
		}
		$order = $this->registrations->create_order( $event_id, $user->ID, $amount, sprintf( 'Von %s im Backend angemeldet.', wp_get_current_user()->display_name ), $notify );
		if ( ! $order ) {
			return 'Die Anmeldung konnte nicht angelegt werden.';
		}
		$this->waitlist->remove( $event_id, $user->ID );
		return $amount > 0
			? sprintf( '%s ist angemeldet (Zahlung offen%s).', $email, $notify ? ', Zahlungslink per E-Mail verschickt' : '' )
			: sprintf( '%s ist angemeldet%s.', $email, $notify ? ', die Bestätigung ist verschickt' : '' );
	}

	/**
	 * The page.
	 */
	public function render(): void {
		if ( ! current_user_can( Setup::CAPABILITY ) ) {
			return;
		}
		$event_id = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view.
		$message  = isset( $_GET['bw_wm_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['bw_wm_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap bwm-participants">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Teilnehmende', 'bw-woo-membership' ) . '</h1>';
		if ( '' !== $message ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
		$this->event_picker( $event_id );
		if ( $event_id && EventRepository::POST_TYPE === get_post_type( $event_id ) ) {
			$this->event_view( $event_id );
		}
		echo '</div>';
	}

	/**
	 * Select box with the events that take registrations.
	 */
	private function event_picker( int $current ): void {
		$ids = get_posts(
			array(
				'post_type'   => EventRepository::POST_TYPE,
				'post_status' => array( 'publish', 'draft', 'future', 'private' ),
				'numberposts' => 100,
				'fields'      => 'ids',
				'meta_key'    => EventRepository::META_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small post type.
				'orderby'     => 'meta_value',
				'order'       => 'DESC',
			)
		);
		echo '<form method="get" style="margin:12px 0 20px"><input type="hidden" name="post_type" value="' . esc_attr( EventRepository::POST_TYPE ) . '"><input type="hidden" name="page" value="' . esc_attr( self::PAGE ) . '">';
		echo '<label for="bwm-event">' . esc_html__( 'Veranstaltung', 'bw-woo-membership' ) . '</label> <select id="bwm-event" name="event" onchange="this.form.submit()"><option value="">– auswählen –</option>';
		foreach ( $ids as $id ) {
			$f = $this->events->fields( (int) $id );
			if ( ! $f['registration'] ) {
				continue;
			}
			printf( '<option value="%d"%s>%s – %s</option>', (int) $id, selected( $current, (int) $id, false ), esc_html( EventLabels::day( $f['date'] ) ), esc_html( get_the_title( (int) $id ) ) );
		}
		echo '</select> <noscript><button class="button">Anzeigen</button></noscript></form>';
	}

	/**
	 * Figures, registrations, waiting list and the add form of one event.
	 */
	private function event_view( int $event_id ): void {
		$f        = $this->events->fields( $event_id );
		$orders   = $this->events->registration_orders( $event_id );
		$count    = $this->events->count_registrations( $event_id );
		$left     = EventPricing::places_left( $f['capacity'], $count );
		$open     = count( array_filter( $orders, static fn( WC_Order $order ): bool => ! $order->is_paid() ) );
		$waiting  = $this->waitlist->all( $event_id );
		$csv      = add_query_arg( array( 'action' => 'bw_wm_participants', 'event' => $event_id, '_wpnonce' => wp_create_nonce( 'bw_wm_participants' ) ), admin_url( 'admin-post.php' ) );
		$price    = EventPricing::price( $f['audience'], true, $f['price_member'], $f['price_public'] );

		echo '<h2>' . esc_html( get_the_title( $event_id ) ) . '</h2>';
		echo '<p>' . esc_html( trim( EventLabels::when( $f ) . ' · ' . EventLabels::place( $f ), ' ·' ) ) . ' · <a href="' . esc_url( (string) get_edit_post_link( $event_id ) ) . '">Bearbeiten</a> · <a href="' . esc_url( (string) get_permalink( $event_id ) ) . '">Ansehen</a></p>';

		echo '<div class="bwm-stats" style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0">';
		foreach ( array(
			array( 'Plätze (maximal)', '' === $f['capacity'] ? 'unbegrenzt' : $f['capacity'] ),
			array( 'Angemeldet', (string) $count ),
			array( 'Frei', null === $left ? '–' : (string) $left ),
			array( 'Davon Zahlung offen', (string) $open ),
			array( 'Warteliste', (string) count( $waiting ) ),
		) as $stat ) {
			printf( '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 16px;min-width:120px"><div style="color:#50575e">%s</div><div style="font-size:24px;font-weight:600;margin-top:4px">%s</div></div>', esc_html( $stat[0] ), esc_html( $stat[1] ) );
		}
		echo '</div>';

		echo '<table class="widefat striped"><thead><tr><th>Name</th><th>E-Mail</th><th>Mitglied</th><th>Betrag</th><th>Status</th><th>Angemeldet am</th><th></th></tr></thead><tbody>';
		if ( ! $orders ) {
			echo '<tr><td colspan="7">Noch keine Anmeldungen.</td></tr>';
		}
		foreach ( $orders as $order ) {
			$item = $this->events->item_for( $order, $event_id );
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			printf(
				'<tr><td>%s</td><td><a href="mailto:%2$s">%2$s</a></td><td>%3$s</td><td>%4$s</td><td>%5$s</td><td>%6$s</td><td><a href="%7$s">Bestellung %8$s</a></td></tr>',
				esc_html( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ),
				esc_html( $order->get_billing_email() ),
				'member' === $item->get_meta( '_bw_wm_price_basis' ) ? 'ja' : 'nein',
				wp_kses_post( wc_price( (float) $item->get_total() ) ),
				esc_html( wc_get_order_status_name( $order->get_status() ) ),
				esc_html( $order->get_date_created() ? $order->get_date_created()->date( 'd.m.Y' ) : '' ),
				esc_url( $order->get_edit_order_url() ),
				esc_html( (string) $order->get_order_number() )
			);
		}
		echo '</tbody></table>';
		if ( $orders ) {
			echo '<p><a class="button" href="' . esc_url( $csv ) . '">Teilnehmerliste (CSV)</a></p>';
		}

		if ( $waiting ) {
			echo '<h3>Warteliste</h3><ol>';
			foreach ( $waiting as $entry ) {
				$user = get_userdata( $entry['user'] );
				if ( $user ) {
					printf( '<li>%s (%s)%s</li>', esc_html( trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name ), esc_html( $user->user_email ), '' !== $entry['notified'] ? ' – über freien Platz informiert' : '' );
				}
			}
			echo '</ol>';
		}

		// Add a person.
		echo '<h3 style="margin-top:28px">Person hinzufügen</h3>';
		if ( 0 === $left ) {
			echo '<p><strong>Die Veranstaltung ist ausgebucht.</strong> Eine weitere Person kannst du trotzdem anmelden (Überbuchung).</p>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::ADD . '_' . $event_id );
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ADD ) . '"><input type="hidden" name="event" value="' . esc_attr( (string) $event_id ) . '">';
		echo '<table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row"><label for="bwm-email">E-Mail</label></th><td><input class="regular-text" type="email" id="bwm-email" name="email" required><p class="description">Hat die Person schon ein Konto, reicht die E-Mail-Adresse. Sonst legen wir ein Konto an; WooCommerce schickt ihr den Zugang.</p></td></tr>';
		echo '<tr><th scope="row"><label for="bwm-first">Vorname</label></th><td><input class="regular-text" type="text" id="bwm-first" name="first_name"></td></tr>';
		echo '<tr><th scope="row"><label for="bwm-last">Nachname</label></th><td><input class="regular-text" type="text" id="bwm-last" name="last_name"></td></tr>';
		echo '<tr><th scope="row">Betrag</th><td><fieldset>';
		echo '<label><input type="radio" name="mode" value="free" checked> kostenlos (z. B. Vortragende, Vorstand, Gäste)</label><br>';
		printf( '<label><input type="radio" name="mode" value="pay"> mit Zahlung: <input type="text" name="amount" value="%s" size="8" inputmode="decimal"> € – die Person bekommt einen Zahlungslink</label>', esc_attr( null !== $price && $price > 0 ? number_format( $price, 2, ',', '' ) : '' ) );
		echo '</fieldset></td></tr>';
		echo '<tr><th scope="row">E-Mail</th><td><label><input type="checkbox" name="notify" value="1" checked> Bestätigung bzw. Zahlungslink an die Person schicken</label></td></tr>';
		echo '</tbody></table>';
		submit_button( 'Person anmelden', 'primary', 'submit', false );
		echo '</form>';
	}
}
