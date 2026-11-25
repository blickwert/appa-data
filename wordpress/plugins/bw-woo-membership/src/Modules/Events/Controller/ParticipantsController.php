<?php
/**
 * Participants page of an event (backend).
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Core\Setup;
use BW\WooMembership\Core\View;
use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Repository\NoticeRepository;
use BW\WooMembership\Modules\Events\Repository\WaitlistRepository;
use BW\WooMembership\Modules\Events\Service\EventLabels;
use BW\WooMembership\Modules\Events\Service\EventPricing;
use BW\WooMembership\Modules\Events\Service\ParticipantService;

/**
 * "Veranstaltungen › Teilnehmende": places, registrations, waiting list and the form to add a person (view
 * templates/admin/participants.php, logic in ParticipantService).
 */
final class ParticipantsController {

	public const PAGE = 'bw-wm-participants';
	public const ADD  = 'bw_wm_add_participant';

	private string $hook = '';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly WaitlistRepository $waitlist,
		private readonly ParticipantService $participants,
		private readonly NoticeRepository $notices,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_post_' . self::ADD, array( $this, 'handle_add' ) );
		add_filter( 'bw_appa_event_editor_info', array( $this, 'editor_info' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_action' ), 10, 2 );
	}

	/**
	 * Submenu "Teilnehmende" below "Veranstaltungen" (only when the post type of bw-appa exists).
	 */
	public function menu(): void {
		if ( ! post_type_exists( EventRepository::POST_TYPE ) ) {
			return;
		}
		$this->hook = (string) add_submenu_page(
			'edit.php?post_type=' . EventRepository::POST_TYPE,
			__( 'Teilnehmende', 'bw-woo-membership' ),
			__( 'Teilnehmende', 'bw-woo-membership' ),
			Setup::CAPABILITY,
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Styles of the page.
	 */
	public function enqueue( string $hook ): void {
		if ( '' !== $this->hook && $hook === $this->hook ) {
			wp_enqueue_style( 'bw-wm-admin', plugins_url( 'assets/admin.css', BW_WM_FILE ), array(), BW_WM_VERSION );
		}
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
	 * @param mixed $actions Row actions.
	 * @param mixed $post    Post.
	 * @return mixed
	 */
	public function row_action( mixed $actions, mixed $post ): mixed {
		if ( is_array( $actions ) && $post instanceof \WP_Post && EventRepository::POST_TYPE === $post->post_type && current_user_can( Setup::CAPABILITY ) && $this->events->fields( $post->ID )['registration'] ) {
			$actions['bw_wm_participants'] = '<a href="' . esc_url( self::url( $post->ID ) ) . '">' . esc_html__( 'Teilnehmende', 'bw-woo-membership' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Figures for the editor sidebar of bw-appa.
	 *
	 * @param mixed $info    Info.
	 * @param mixed $post_id Event.
	 * @return mixed
	 */
	public function editor_info( mixed $info, mixed $post_id ): mixed {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || ! current_user_can( Setup::CAPABILITY ) ) {
			return $info;
		}
		$f     = $this->events->fields( $post_id );
		$count = $this->events->count_registrations( $post_id );
		return array(
			'enabled'    => $f['registration'],
			'registered' => $count,
			'capacity'   => $f['capacity'],
			'left'       => EventPricing::places_left( $f['capacity'], $count ),
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
		$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'free';
		$amount = 'pay' === $mode && isset( $_POST['amount'] ) ? max( 0.0, round( (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ), 2 ) ) : 0.0;
		$this->notices->remember(
			get_current_user_id(),
			$this->participants->add(
				$event_id,
				isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
				isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
				isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
				$amount,
				! empty( $_POST['notify'] ),
				wp_get_current_user()->display_name
			)
		);
		wp_safe_redirect( self::url( $event_id ) );
		exit;
	}

	/**
	 * The page.
	 */
	public function render(): void {
		if ( ! current_user_can( Setup::CAPABILITY ) ) {
			return;
		}
		$event_id = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view.
		$valid    = $event_id && EventRepository::POST_TYPE === get_post_type( $event_id );
		$events   = array();
		foreach ( $this->events->with_registration() as $id ) {
			$events[] = array( 'id' => $id, 'label' => EventLabels::day( $this->events->fields( $id )['date'] ) . ' – ' . get_the_title( $id ) );
		}
		$html = View::render(
			'admin/participants',
			array(
				'notice'     => $this->notices->take( get_current_user_id() ),
				'post_type'  => EventRepository::POST_TYPE,
				'page'       => self::PAGE,
				'events'     => $events,
				'event'      => $valid ? $event_id : 0,
				'overview'   => $valid ? $this->participants->overview( $event_id ) : null,
				'edit_url'   => $valid ? (string) get_edit_post_link( $event_id ) : '',
				'view_url'   => $valid ? (string) get_permalink( $event_id ) : '',
				'csv_url'    => add_query_arg( array( 'action' => 'bw_wm_participants', 'event' => $event_id, '_wpnonce' => wp_create_nonce( 'bw_wm_participants' ) ), admin_url( 'admin-post.php' ) ),
				'add_action' => self::ADD,
			)
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the template escapes.
	}
}
