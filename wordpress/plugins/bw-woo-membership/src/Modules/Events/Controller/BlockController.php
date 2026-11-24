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
	}

	/**
	 * Registers the block.
	 */
	public function register_block(): void {
		register_block_type( BW_WM_DIR . '/blocks/event-registration', array( 'render_callback' => array( $this, 'render_box' ) ) );
	}

	/**
	 * Prices, free places and the registration button of the current event.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner content.
	 * @param WP_Block|null        $block      Block instance.
	 */
	public function render_box( array $attributes, string $content = '', ?WP_Block $block = null ): string {
		$event_id = (int) ( $block->context['postId'] ?? get_the_ID() );
		if ( $event_id <= 0 || EventRepository::POST_TYPE !== get_post_type( $event_id ) ) {
			return '';
		}
		$box = $this->registrations->box( $event_id, get_current_user_id() );
		return $box['enabled'] ? View::render( 'events/registration', $box ) : '';
	}

	/**
	 * "Meine Anmeldungen" in the member area (hook of the membership events template).
	 */
	public function render_my_registrations(): void {
		$items = $this->registrations->registrations( get_current_user_id() );
		if ( $items ) {
			echo View::render( 'events/my-registrations', array( 'items' => $items ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template.
		}
	}
}
