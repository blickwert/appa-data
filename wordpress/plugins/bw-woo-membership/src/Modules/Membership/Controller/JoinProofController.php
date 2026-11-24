<?php
/**
 * Student proof when joining: upload page, checkout check, hand-over to the new account.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Core\View;
use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Service\JoinProof;
use WC_Order;

/**
 * Block "Studiennachweis für den Beitritt" (page /studiennachweis/), handling of its form, refusal of a student
 * membership at the checkout without proof, and moving a session proof to the account after the order.
 */
final class JoinProofController {

	public const NONCE = 'bw_wm_join_proof';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly JoinProof $proof,
		private readonly MembershipOrderRepository $orders,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ), 20 );
		add_action( 'template_redirect', array( $this, 'handle_post' ), 5 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'attach' ), 5 );
	}

	/**
	 * Registers the block.
	 */
	public function register_block(): void {
		register_block_type( BW_WM_DIR . '/blocks/join-proof', array( 'render_callback' => array( $this, 'render' ) ) );
	}

	/**
	 * Upload form, or the received proof with the way on to the checkout.
	 */
	public function render(): string {
		$user_id = get_current_user_id();
		nocache_headers();
		return View::render(
			'join/proof',
			array(
				'has'      => $this->proof->has_proof( $user_id ),
				'name'     => $this->proof->name( $user_id ),
				'error'    => $this->proof->take_error(),
				'nonce'    => wp_nonce_field( self::NONCE, '_bw_wm_join_proof', true, false ),
				'checkout' => add_query_arg( 'bw_wm_join', 'student', home_url( '/' ) ),
				'regular'  => add_query_arg( 'bw_wm_join', 'regular', home_url( '/' ) ),
			)
		);
	}

	/**
	 * Handles the upload form (a front-end request, so the WooCommerce session is available).
	 */
	public function handle_post(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['_bw_wm_join_proof'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_bw_wm_join_proof'] ) ), self::NONCE ) ) {
			$this->proof->remember_error( 'Die Sitzung ist abgelaufen. Bitte lade die Datei noch einmal hoch.' );
			wp_safe_redirect( $this->proof->page_url() );
			exit;
		}
		$file   = isset( $_FILES['bw_wm_proof'] ) && is_array( $_FILES['bw_wm_proof'] ) ? $_FILES['bw_wm_proof'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated in ProofService.
		$result = $this->proof->accept(
			array(
				'name'     => (string) ( $file['name'] ?? '' ),
				'tmp_name' => (string) ( $file['tmp_name'] ?? '' ),
				'size'     => (int) ( $file['size'] ?? 0 ),
				'error'    => (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ),
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			$this->proof->remember_error( $result->get_error_message() );
			wp_safe_redirect( $this->proof->page_url() );
			exit;
		}
		wp_safe_redirect( add_query_arg( 'bw_wm_join', 'student', home_url( '/' ) ) );
		exit;
	}

	/**
	 * The student membership needs a proof at the checkout.
	 */
	public function check_cart(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || $this->proof->has_proof( get_current_user_id() ) ) {
			return;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( 'student' === $this->orders->product_type( (int) ( $item['product_id'] ?? 0 ) ) ) {
				wc_add_notice(
					sprintf(
						/* translators: %s: link to the proof page */
						__( 'Für die studentische Mitgliedschaft brauchen wir deinen Studiennachweis. <a href="%s">Jetzt hochladen</a>', 'bw-woo-membership' ),
						esc_url( $this->proof->page_url() )
					),
					'error'
				);
				return;
			}
		}
	}

	/**
	 * After the order: the proof from the session belongs to the (new) account.
	 */
	public function attach( WC_Order $order ): void {
		if ( 'student' === $this->orders->order_type( $order ) ) {
			$this->proof->attach_to( $order->get_customer_id() );
		}
	}
}
