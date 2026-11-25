<?php
/**
 * Registration box and member area list.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Core\View;
use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Service\RegistrationService;
use BW\WooMembership\Modules\Membership\Service\MemberPages;
use BW\WooMembership\Support\NoCache;
use WP_Block;

/**
 * Block "Veranstaltung: Anmeldung" on the event page and "Meine Anmeldungen" inside the member area events block.
 */
final class BlockController {

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly RegistrationService $registrations ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ), 20 );
		add_action( 'bw_wm_events_before_list', array( $this, 'render_my_registrations' ) );
		add_action( 'wp_ajax_bw_wm_event_box', array( $this, 'ajax_box' ) );
		add_action( 'wp_ajax_nopriv_bw_wm_event_box', array( $this, 'ajax_box' ) );
	}

	/**
	 * Registers the block.
	 */
	public function register_block(): void {
		register_block_type( BW_WM_DIR . '/blocks/event-registration', array( 'render_callback' => array( $this, 'render_box' ) ) );
	}

	/**
	 * Placeholder on the (cacheable) event page; the personal box (price, registration) is loaded by AJAX.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner content.
	 * @param WP_Block|null        $block      Block instance (context postId).
	 */
	public function render_box( array $attributes, string $content = '', ?WP_Block $block = null ): string {
		$event_id = (int) ( $block->context['postId'] ?? get_the_ID() );
		if ( $event_id <= 0 || EventRepository::POST_TYPE !== get_post_type( $event_id ) || ! $this->registrations->enabled( $event_id ) ) {
			return '';
		}
		wp_enqueue_script( 'bw-wm-event-box', plugins_url( 'assets/event-box.js', BW_WM_FILE ), array(), BW_WM_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_add_inline_script( 'bw-wm-event-box', 'window.bwWmEventBox = ' . wp_json_encode( array( 'url' => admin_url( 'admin-ajax.php' ), 'error' => __( 'Die Anmeldung konnte nicht geladen werden. Bitte lade die Seite neu.', 'bw-woo-membership' ) ) ) . ';', 'before' );
		return sprintf(
			'<div class="bwm-section bwm-register bwm-register--loading" data-bwm-event="%1$d" aria-live="polite"><h2 class="bwm-register__title">%2$s</h2><p class="bwm-sub">%3$s</p><noscript><p>%4$s</p></noscript></div>',
			$event_id,
			esc_html__( 'Anmeldung', 'bw-woo-membership' ),
			esc_html__( 'Preise und freie Plätze werden geladen …', 'bw-woo-membership' ),
			esc_html__( 'Bitte aktiviere JavaScript, um dich anzumelden.', 'bw-woo-membership' )
		);
	}

	/**
	 * The personal registration box as HTML (admin-ajax recognises the login cookie without a nonce, which a cached
	 * page could not carry fresh). Read-only; booking goes through the WooCommerce cart.
	 */
	public function ajax_box(): void {
		NoCache::send();
		$event_id = isset( $_GET['event'] ) ? absint( $_GET['event'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
		$html     = '';
		if ( $event_id > 0 && EventRepository::POST_TYPE === get_post_type( $event_id ) && 'publish' === get_post_status( $event_id ) ) {
			$box  = $this->registrations->box( $event_id, get_current_user_id() );
			$html = $box['enabled'] ? View::render( 'events/registration', $box ) : '';
		}
		header( 'Content-Type: text/html; charset=utf-8' );
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template.
		exit;
	}

	/**
	 * "Meine Anmeldungen" in the member area (hook of the membership events template).
	 */
	public function render_my_registrations(): void {
		$items = $this->registrations->registrations( get_current_user_id() );
		if ( $items ) {
			echo View::render( 'events/my-registrations', array( 'items' => $items, 'back' => MemberPages::area_url( 'veranstaltungen' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template.
		}
	}
}
