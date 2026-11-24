<?php
/**
 * Page model of the member area.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

use BW\WooMembership\Modules\Membership\Model\Membership;
use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Repository\ProofRepository;

/**
 * Prepares the values the member area templates print (texts, dates, URLs).
 */
final class DashboardModel {

	/**
	 * Event post type and meta keys of the plugin "APPA Funktionen" (bw-appa).
	 */
	private const EVENT_TYPE      = 'appa_event';
	private const EVENT_DATE      = 'bw_appa_event_date';
	private const EVENT_TIME      = 'bw_appa_event_time';
	private const EVENT_LINK      = 'bw_appa_event_zoom_link';
	private const EVENT_AUDIENCE  = 'bw_appa_event_audience';
	private const MONTHS_SHORT    = array( 'JÄN', 'FEB', 'MÄR', 'APR', 'MAI', 'JUN', 'JUL', 'AUG', 'SEP', 'OKT', 'NOV', 'DEZ' );
	private const STATUS_LABELS   = array(
		Membership::STATUS_ACTIVE  => array( 'Aktiv', 'Mitgliedschaft aktiv' ),
		Membership::STATUS_PENDING => array( 'Zahlung offen', 'Beitrag noch offen' ),
		Membership::STATUS_ON_HOLD => array( 'Pausiert', 'Zahlung offen' ),
		Membership::STATUS_EXPIRED => array( 'Abgelaufen', 'Mitgliedschaft abgelaufen' ),
		Membership::STATUS_NONE    => array( 'Keine Mitgliedschaft', 'Noch kein Mitglied' ),
	);

	/**
	 * Builds the model.
	 */
	public function __construct(
		private readonly MembershipService $memberships,
		private readonly MemberRepository $members,
		private readonly MembershipOrderRepository $orders,
		private readonly ProofRepository $proofs,
	) {
	}

	/**
	 * Name and membership type for the header.
	 *
	 * @return array{name: string, type: string, initials: string}
	 */
	public function user( int $user_id ): array {
		$user       = get_userdata( $user_id );
		$first      = $user ? (string) $user->first_name : '';
		$name       = $user ? trim( $first . ' ' . $user->last_name ) : '';
		$name       = '' !== $name ? $name : ( $user ? (string) $user->display_name : '' );
		$membership = $this->memberships->for_user( $user_id );
		$parts      = preg_split( '/\s+/', $name ) ?: array();
		$initials   = strtoupper( implode( '', array_map( static fn( string $p ): string => mb_substr( $p, 0, 1 ), array_slice( $parts, 0, 2 ) ) ) );
		return array(
			'name'     => $name,
			'type'     => '' !== $membership->type ? MembershipTypes::label( $membership->type ) : self::STATUS_LABELS[ Membership::STATUS_NONE ][1],
			'initials' => $initials,
		);
	}

	/**
	 * Welcome, status badge, the three status cards and the confirmation link.
	 *
	 * @return array<string, mixed>
	 */
	public function overview( int $user_id ): array {
		$m      = $this->memberships->for_user( $user_id );
		$user   = get_userdata( $user_id );
		$labels = self::STATUS_LABELS[ $m->status ] ?? self::STATUS_LABELS[ Membership::STATUS_NONE ];
		$since  = null !== $m->since ? 'Mitglied seit ' . substr( $m->since, 0, 4 ) : '';
		$number = '' !== $m->number ? 'Nr. ' . $m->number : '';

		if ( null !== $m->next_renewal ) {
			$renewal     = MembershipPeriod::format( $m->next_renewal );
			$renewal_sub = $m->auto_renew ? 'Automatische Verlängerung' : 'Verlängerung per Zahlung';
		} elseif ( null !== $m->valid_until ) {
			$renewal     = MembershipPeriod::format( $m->valid_until );
			$renewal_sub = Membership::STATUS_ACTIVE === $m->status ? 'Gültig bis' : 'Abgelaufen am';
		} else {
			$renewal     = '–';
			$renewal_sub = Membership::STATUS_ACTIVE === $m->status ? 'Unbefristet' : '';
		}

		return array(
			'first_name'   => $user ? (string) $user->first_name : '',
			'status'       => $m->status,
			'active'       => $m->grants_access(),
			'badge'        => $labels[1],
			'cards'        => array(
				array( 'label' => 'Status', 'value' => $labels[0], 'sub' => trim( $since . ( '' !== $since && '' !== $number ? ' · ' : '' ) . $number ), 'dot' => $m->grants_access() ),
				array( 'label' => 'Mitgliedschaftsart', 'value' => '' !== $m->type ? MembershipTypes::label( $m->type ) : '–', 'sub' => $this->fee_text( $m ), 'dot' => false ),
				array( 'label' => 'Nächste Verlängerung', 'value' => $renewal, 'sub' => $renewal_sub, 'dot' => false ),
			),
			'notice'       => $this->notice( $m ),
			'join_url'     => home_url( '/mitgliedschaft/' ),
			'account_url'  => function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'myaccount' ) : '',
			'confirmation' => $m->grants_access() ? add_query_arg( 'bw_wm_confirmation', '1', home_url( '/' ) ) : '',
			'renew_url'    => $this->renew_url( $m ),
		);
	}

	/**
	 * Checkout link for renewing (order-based memberships without automatic renewal): within 45 days before the end
	 * of the period or after it. Students renew as students only with a proof for the next period.
	 */
	private function renew_url( Membership $m ): string {
		if ( $m->auto_renew || 'manual' === $m->source || '' === $m->type || ! function_exists( 'wc_get_checkout_url' ) ) {
			return '';
		}
		$today = current_time( 'Y-m-d' );
		if ( Membership::STATUS_ACTIVE === $m->status && null !== $m->valid_until && ( new \DateTimeImmutable( $today ) )->diff( new \DateTimeImmutable( $m->valid_until ) )->days > 45 ) {
			return '';
		}
		if ( ! in_array( $m->status, array( Membership::STATUS_ACTIVE, Membership::STATUS_EXPIRED ), true ) ) {
			return '';
		}
		$next = null !== $m->valid_until ? MembershipPeriod::next_renewal( $m->valid_until ) : $today;
		$type = 'student' === $m->type && ProofRules::valid_on( $this->proofs->get( $m->user_id ), max( $next, $today ) ) ? 'student' : ( 'student' === $m->type ? 'regular' : $m->type );
		$product = $this->orders->product_for_type( $type );
		return $product ? add_query_arg( 'add-to-cart', $product, wc_get_checkout_url() ) : '';
	}

	/**
	 * Upcoming events for the member area (post type of the plugin bw-appa), soonest first.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function events( int $user_id, int $limit ): array {
		if ( ! post_type_exists( self::EVENT_TYPE ) ) {
			return array();
		}
		$posts  = get_posts(
			array(
				'post_type'      => self::EVENT_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'meta_key'       => self::EVENT_DATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small post type.
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => self::EVENT_DATE,
						'value'   => current_time( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
				'no_found_rows'  => true,
				'suppress_filters' => false,
			)
		);
		$events = array();
		foreach ( $posts as $post ) {
			$date = (string) get_post_meta( $post->ID, self::EVENT_DATE, true );
			$day  = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
			$time = (string) get_post_meta( $post->ID, self::EVENT_TIME, true );
			$link = (string) get_post_meta( $post->ID, self::EVENT_LINK, true );
			$events[] = array(
				'title'     => get_the_title( $post ),
				'url'       => (string) get_permalink( $post ),
				'day'       => $day ? $day->format( 'd' ) : '',
				'month'     => $day ? self::MONTHS_SHORT[ (int) $day->format( 'n' ) - 1 ] : '',
				'year'      => $day ? $day->format( 'Y' ) : '',
				'meta'      => trim( ( '' !== $time ? $time . ' Uhr' : '' ) . ( '' !== $link ? ' · Online via Zoom' : '' ), ' ·' ),
				'online'    => '' !== $link,
				'exclusive' => 'members' === get_post_meta( $post->ID, self::EVENT_AUDIENCE, true ),
				/** This filter is documented in the plugin bw-appa (PrivacyController). */
				'zoom'      => '' !== $link && apply_filters( 'bw_appa_event_private_visible', true, $post->ID, $user_id ) ? $link : '',
				'registration' => '1' === get_post_meta( $post->ID, 'bw_appa_event_registration', true ),
			);
		}
		return $events;
	}

	/**
	 * Directory switch and profile fields.
	 *
	 * @return array<string, mixed>
	 */
	public function profile( int $user_id ): array {
		return array(
			'visible' => $this->members->in_directory( $user_id ),
			'fields'  => $this->members->profile( $user_id ),
			'groups'  => ProfileFields::GROUPS,
			'action'  => admin_url( 'admin-post.php' ),
			'saved'   => isset( $_GET['bwm'] ) && 'saved' === $_GET['bwm'], // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
			'active'  => $this->memberships->for_user( $user_id )->grants_access(),
		);
	}

	/**
	 * Values for the printable membership confirmation.
	 *
	 * @return array<string, string>
	 */
	public function confirmation( int $user_id ): array {
		$m    = $this->memberships->for_user( $user_id );
		$user = $this->user( $user_id );
		return array(
			'name'   => $user['name'],
			'type'   => MembershipTypes::label( $m->type ),
			'number' => $m->number,
			'since'  => MembershipPeriod::format( $m->since ),
			'until'  => null !== $m->valid_until ? MembershipPeriod::format( $m->valid_until ) : '',
			'today'  => MembershipPeriod::format( current_time( 'Y-m-d' ) ),
		);
	}

	/**
	 * Text below the membership type.
	 */
	private function fee_text( Membership $m ): string {
		return match ( $m->status ) {
			Membership::STATUS_ACTIVE  => 'manual' === $m->source ? 'Beitragsfrei' : 'Jahresbeitrag entrichtet',
			Membership::STATUS_PENDING, Membership::STATUS_ON_HOLD => 'Jahresbeitrag offen',
			default => '',
		};
	}

	/**
	 * Hint above the cards when something needs the member's attention.
	 */
	private function notice( Membership $m ): string {
		return match ( $m->status ) {
			Membership::STATUS_PENDING => 'Dein Mitgliedsbeitrag ist noch nicht eingegangen. Sobald die Zahlung da ist, stehen dir alle Inhalte des Mitgliederbereichs zur Verfügung.',
			Membership::STATUS_ON_HOLD => 'Die letzte Zahlung ist fehlgeschlagen. Bitte prüfe deine Zahlungsart unter „Mein Konto“; bis dahin ist der Zugang zu Dokumenten und Mitgliederveranstaltungen pausiert.',
			Membership::STATUS_EXPIRED => 'Deine Mitgliedschaft ist abgelaufen. Verlängere sie, um wieder alle Inhalte nutzen zu können.',
			Membership::STATUS_NONE    => 'Du hast noch keine Mitgliedschaft. Werde Mitglied, um Dokumente, Mitgliederveranstaltungen und das Mitgliederverzeichnis zu nutzen.',
			default                    => '',
		};
	}
}
