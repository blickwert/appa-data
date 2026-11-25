<?php
/**
 * Registration product per event.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Service\ProductSync;
use WP_Post;

/**
 * Keeps a hidden, virtual WooCommerce product per event with registration: price, title and free places follow the
 * event. Products are never deleted (orders refer to them); without registration they become private.
 */
final class ProductSyncController {

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly ProductSync $sync,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		// wp_after_insert_post: the block editor saves meta after save_post.
		add_action( 'wp_after_insert_post', array( $this, 'on_save' ), 20, 2 );
		add_action( 'wp_trash_post', array( $this, 'on_remove' ) );
		add_action( 'before_delete_post', array( $this, 'on_remove' ) );
		// After WooCommerce reduced or restored the stock: set it again from the registrations (one source).
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status' ), 99, 4 );
	}

	/**
	 * An order changed its status: re-sync the stock of its events (covers refunds and cancellations).
	 *
	 * @param int       $order_id Order ID.
	 * @param string    $from     Old status.
	 * @param string    $to       New status.
	 * @param \WC_Order $order    Order.
	 */
	public function on_order_status( int $order_id, string $from, string $to, mixed $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$this->events->forget();
		foreach ( $this->events->events_in_order( $order ) as $event_id ) {
			$this->sync->sync( $event_id );
		}
	}

	/**
	 * An event was saved.
	 */
	public function on_save( int $post_id, WP_Post $post ): void {
		if ( EventRepository::POST_TYPE !== $post->post_type || wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}
		$this->sync->sync( $post_id );
	}

	/**
	 * An event is trashed or deleted: its product stops selling.
	 */
	public function on_remove( int $post_id ): void {
		if ( EventRepository::POST_TYPE === get_post_type( $post_id ) ) {
			$this->sync->hide( $this->events->product_id( $post_id ) );
		}
	}
}
