<?php
/**
 * Member administration.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Service;

use BW\WooMembership\Modules\Membership\Repository\MemberRepository;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Repository\ProofRepository;
use WP_Error;
use WP_User;

/**
 * List, details and changes of members for the backend (office).
 */
final class MemberAdminService {

	public const STATUS_LABELS = array(
		'active'  => 'Aktiv',
		'pending' => 'Zahlung offen',
		'on-hold' => 'Pausiert',
		'expired' => 'Abgelaufen',
		'none'    => 'Keine Mitgliedschaft',
	);
	public const PROOF_LABELS  = array(
		'none'     => '–',
		'pending'  => 'wartet auf Prüfung',
		'approved' => 'bestätigt',
		'rejected' => 'abgelehnt',
	);

	/**
	 * Builds the service.
	 */
	public function __construct(
		private readonly MembershipService $memberships,
		private readonly MemberRepository $members,
		private readonly MembershipOrderRepository $orders,
		private readonly ProofRepository $proofs,
		private readonly MembershipSync $sync,
	) {
	}

	/**
	 * Members for the list (from the snapshot, so filters and paging run in SQL).
	 *
	 * @param array{status?: string, type?: string, search?: string, page?: int, per_page?: int} $args Filters.
	 * @return array{items: list<array<string, mixed>>, total: int, counts: array<string, int>}
	 */
	public function list( array $args ): array {
		$meta = array( array( 'key' => MemberRepository::META_STATUS, 'compare' => 'EXISTS' ) );
		if ( ! empty( $args['status'] ) && isset( self::STATUS_LABELS[ $args['status'] ] ) ) {
			$meta[] = array( 'key' => MemberRepository::META_STATUS, 'value' => $args['status'] );
		}
		if ( ! empty( $args['type'] ) && MembershipTypes::exists( $args['type'] ) ) {
			$meta[] = array( 'key' => MemberRepository::META_TYPE, 'value' => $args['type'] );
		}
		$per_page = max( 1, min( 200, (int) ( $args['per_page'] ?? 50 ) ) );
		$query    = new \WP_User_Query(
			array(
				'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- few hundred members.
				'search'         => ! empty( $args['search'] ) ? '*' . $args['search'] . '*' : '',
				'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
				'number'         => $per_page,
				'paged'          => max( 1, (int) ( $args['page'] ?? 1 ) ),
				'orderby'        => 'display_name',
				'order'          => 'ASC',
				'count_total'    => true,
			)
		);
		$items = array();
		foreach ( $query->get_results() as $user ) {
			$items[] = $this->row( $user );
		}
		return array( 'items' => $items, 'total' => (int) $query->get_total(), 'counts' => $this->counts() );
	}

	/**
	 * Number of members per status.
	 *
	 * @return array<string, int>
	 */
	public function counts(): array {
		$counts = array( 'all' => 0 );
		foreach ( array_keys( self::STATUS_LABELS ) as $status ) {
			$counts[ $status ] = count( get_users( array( 'fields' => 'ID', 'number' => -1, 'meta_key' => MemberRepository::META_STATUS, 'meta_value' => $status ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery -- few members.
			$counts['all']    += $counts[ $status ];
		}
		$counts['proofs'] = count( $this->proofs->pending_user_ids() );
		return $counts;
	}

	/**
	 * One list row.
	 *
	 * @return array<string, mixed>
	 */
	public function row( WP_User $user ): array {
		$m     = $this->memberships->for_user( $user->ID );
		$proof = $this->proofs->get( $user->ID );
		return array(
			'id'        => $user->ID,
			'number'    => $m->number,
			'name'      => trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name,
			'email'     => $user->user_email,
			'type'      => MembershipTypes::label( $m->type ),
			'type_key'  => $m->type,
			'status'    => $m->status,
			'status_l'  => self::STATUS_LABELS[ $m->status ] ?? $m->status,
			'since'     => MembershipPeriod::format( $m->since ),
			'renewal'   => MembershipPeriod::format( $m->next_renewal ?? $m->valid_until ),
			'proof'     => 'student' === $m->type || 'none' !== $proof['status'] ? ( self::PROOF_LABELS[ $proof['status'] ] ?? '' ) : '–',
			'directory' => $this->members->in_directory( $user->ID ),
			'source'    => $m->source,
		);
	}

	/**
	 * Details for the edit screen.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function get( int $user_id ): array|WP_Error {
		$user = $this->editable_user( $user_id );
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		$m      = $this->memberships->for_user( $user_id );
		$manual = $this->members->manual( $user_id );
		$proof  = $this->proofs->get( $user_id );
		$orders = array();
		if ( function_exists( 'wc_get_orders' ) ) {
			foreach ( wc_get_orders( array( 'customer_id' => $user_id, 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order' ) ) as $order ) {
				if ( $order instanceof \WC_Order ) {
					$orders[] = array(
						'id'     => $order->get_id(),
						'date'   => $order->get_date_created() ? $order->get_date_created()->date( 'd.m.Y' ) : '',
						'status' => wc_get_order_status_name( $order->get_status() ),
						'total'  => html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ) ),
						'items'  => implode( ', ', array_map( static fn( $item ) => $item->get_name(), array_values( $order->get_items() ) ) ),
						'url'    => $order->get_edit_order_url(),
					);
				}
			}
		}
		return array(
			'row'     => $this->row( $user ),
			'first'   => $user->first_name,
			'last'    => $user->last_name,
			'email'   => $user->user_email,
			'manual'  => array( 'type' => $manual['type'] ?? '', 'until' => $manual['until'] ?? '' ),
			'profile' => $this->members->profile( $user_id ),
			'application' => array(
				'occupation' => (string) get_user_meta( $user_id, \BW\WooMembership\Modules\Membership\Controller\JoinController::META_JOB, true ),
				'statutes'   => (string) get_user_meta( $user_id, \BW\WooMembership\Modules\Membership\Controller\JoinController::META_STATUTES, true ),
			),
			'valid'   => MembershipPeriod::format( $m->valid_until ),
			'proof'   => array(
				'status'      => $proof['status'],
				'label'       => self::PROOF_LABELS[ $proof['status'] ] ?? '',
				'uploaded'    => MembershipPeriod::format( $proof['uploaded'] ),
				'valid_until' => MembershipPeriod::format( $proof['valid_until'] ),
				'reviewed'    => MembershipPeriod::format( $proof['reviewed'] ),
				'reason'      => $proof['reason'],
				'has_file'    => '' !== $proof['file'],
				'suggested'   => ProofRules::default_valid_until( $m->valid_until, current_time( 'Y-m-d' ) ),
			),
			'orders'  => $orders,
			'types'   => MembershipTypes::all(),
			'groups'  => ProfileFields::GROUPS,
		);
	}

	/**
	 * Saves contact data, manual membership, profile and directory choice.
	 *
	 * @param array<string, mixed> $data Raw request data.
	 * @return array<string, mixed>|WP_Error Fresh details.
	 */
	public function update( int $user_id, array $data ): array|WP_Error {
		$user = $this->editable_user( $user_id );
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		$changes = array( 'ID' => $user_id );
		if ( isset( $data['first'] ) ) {
			$changes['first_name'] = sanitize_text_field( (string) $data['first'] );
		}
		if ( isset( $data['last'] ) ) {
			$changes['last_name'] = sanitize_text_field( (string) $data['last'] );
		}
		if ( isset( $data['email'] ) ) {
			$email = sanitize_email( (string) $data['email'] );
			if ( ! is_email( $email ) ) {
				return new WP_Error( 'bw_wm_email', 'Die E-Mail-Adresse ist ungültig.', array( 'status' => 400 ) );
			}
			$owner = email_exists( $email );
			if ( $owner && (int) $owner !== $user_id ) {
				return new WP_Error( 'bw_wm_email', 'Diese E-Mail-Adresse gehört schon zu einem anderen Konto.', array( 'status' => 400 ) );
			}
			$changes['user_email'] = $email;
		}
		$result = wp_update_user( $changes );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( isset( $data['manual'] ) && is_array( $data['manual'] ) ) {
			$type  = sanitize_key( (string) ( $data['manual']['type'] ?? '' ) );
			$until = (string) ( $data['manual']['until'] ?? '' );
			$until = 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $until ) ? $until : '';
			$this->members->set_manual( $user_id, MembershipTypes::exists( $type ) ? $type : '', $until );
			if ( MembershipTypes::exists( $type ) ) {
				$this->members->ensure_number( $user_id, array( MemberNumber::class, 'format' ), current_time( 'Y-m-d' ) );
			}
		}
		if ( isset( $data['profile'] ) && is_array( $data['profile'] ) ) {
			$this->members->save_profile( $user_id, ProfileFields::sanitize( $data['profile'] ) );
		}
		if ( isset( $data['directory'] ) ) {
			$this->members->set_directory( $user_id, (bool) $data['directory'], current_time( 'Y-m-d' ) );
		}
		$this->sync->sync( $user_id );
		return $this->get( $user_id );
	}

	/**
	 * Creates a member account for a manual membership (honorary, institutional) and sends the
	 * "set your password" mail.
	 *
	 * @param array<string, mixed> $data Raw request data: first, last, email, type, until.
	 * @return array<string, mixed>|WP_Error Details of the new member.
	 */
	public function create( array $data ): array|WP_Error {
		$email = sanitize_email( (string) ( $data['email'] ?? '' ) );
		$type  = sanitize_key( (string) ( $data['type'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'bw_wm_email', 'Die E-Mail-Adresse ist ungültig.', array( 'status' => 400 ) );
		}
		if ( email_exists( $email ) ) {
			return new WP_Error( 'bw_wm_email', 'Zu dieser E-Mail-Adresse gibt es schon ein Konto – bitte dort die Mitgliedschaft eintragen.', array( 'status' => 400 ) );
		}
		if ( ! MembershipTypes::exists( $type ) ) {
			return new WP_Error( 'bw_wm_type', 'Bitte eine Mitgliedsart wählen.', array( 'status' => 400 ) );
		}
		$user_id = wp_insert_user(
			array(
				'user_login' => $email,
				'user_email' => $email,
				'user_pass'  => wp_generate_password( 24 ),
				'first_name' => sanitize_text_field( (string) ( $data['first'] ?? '' ) ),
				'last_name'  => sanitize_text_field( (string) ( $data['last'] ?? '' ) ),
				'role'       => get_role( 'customer' ) ? 'customer' : 'subscriber',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
		wp_new_user_notification( $user_id, null, 'user' );
		return $this->update( $user_id, array( 'manual' => array( 'type' => $type, 'until' => (string) ( $data['until'] ?? '' ) ) ) );
	}

	/**
	 * Pending proofs for the review page.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function pending_proofs(): array {
		$items = array();
		foreach ( $this->proofs->pending_user_ids() as $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$row          = $this->row( $user );
				$proof        = $this->proofs->get( $user_id );
				$row['proof'] = array(
					'uploaded'  => MembershipPeriod::format( $proof['uploaded'] ),
					'name'      => $proof['name'],
					'suggested' => ProofRules::default_valid_until( $this->memberships->for_user( $user_id )->valid_until, current_time( 'Y-m-d' ) ),
				);
				$items[]      = $row;
			}
		}
		return $items;
	}

	/**
	 * CSV rows of all members.
	 *
	 * @return list<list<string>>
	 */
	public function csv(): array {
		$rows = array( array( 'Nr.', 'Vorname', 'Nachname', 'E-Mail', 'Mitgliedsart', 'Status', 'Mitglied seit', 'Gültig bis / Verlängerung', 'Studiennachweis', 'Verzeichnis' ) );
		foreach ( get_users( array( 'meta_key' => MemberRepository::META_STATUS, 'number' => -1, 'orderby' => 'display_name' ) ) as $user ) { // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- export.
			$row    = $this->row( $user );
			$rows[] = array( $row['number'], $user->first_name, $user->last_name, $row['email'], $row['type'], $row['status_l'], $row['since'], $row['renewal'], $row['proof'], $row['directory'] ? 'ja' : 'nein' );
		}
		return $rows;
	}

	/**
	 * Members may be edited here, staff accounts never (roles and rights stay in "Benutzer").
	 */
	private function editable_user( int $user_id ): WP_User|WP_Error {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_Error( 'bw_wm_not_found', 'Mitglied nicht gefunden.', array( 'status' => 404 ) );
		}
		if ( user_can( $user, 'edit_posts' ) ) {
			return new WP_Error( 'bw_wm_staff', 'Konten mit Redaktionsrechten werden unter „Benutzer“ bearbeitet.', array( 'status' => 403 ) );
		}
		return $user;
	}
}
