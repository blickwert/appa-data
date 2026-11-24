<?php
/**
 * Registration product per event.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Controller;

use BW\WooMembership\Modules\Events\Repository\EventRepository;
use BW\WooMembership\Modules\Events\Service\EventPricing;
use WP_Post;

/**
 * Keeps a hidden, virtual WooCommerce product per event with registration: price, title and free places follow the
 * event. Products are never deleted (orders refer to them); without registration they become private.
 */
final class ProductSyncController {

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly EventRepository $events ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		// wp_after_insert_post: the block editor saves meta after save_post.
		add_action( 'wp_after_insert_post', array( $this, 'on_save' ), 20, 2 );
		add_action( 'wp_trash_post', array( $this, 'on_remove' ) );
		add_action( 'before_delete_post', array( $this, 'on_remove' ) );
	}

	/**
	 * An event was saved.
	 */
	public function on_save( int $post_id, WP_Post $post ): void {
		if ( EventRepository::POST_TYPE !== $post->post_type || wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}
		$this->sync( $post_id );
	}

	/**
	 * An event is trashed or deleted: its product stops selling.
	 */
	public function on_remove( int $post_id ): void {
		if ( EventRepository::POST_TYPE === get_post_type( $post_id ) ) {
			$this->hide( $this->events->product_id( $post_id ) );
		}
	}

	/**
	 * Creates or updates the product of an event.
	 */
	public function sync( int $event_id ): void {
		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			return;
		}
		$fields     = $this->events->fields( $event_id );
		$product_id = $this->events->product_id( $event_id );
		$product    = $product_id ? wc_get_product( $product_id ) : null;
		if ( ! $fields['registration'] ) {
			$this->hide( $product_id );
			return;
		}
		if ( ! $product ) {
			$product = new \WC_Product_Simple();
		}
		// Base price = public price (or member price for members-only); the cart sets the price per person.
		$base = 'members' === $fields['audience'] ? $fields['price_member'] : ( '' !== $fields['price_public'] ? $fields['price_public'] : $fields['price_member'] );
		$product->set_name( 'Anmeldung: ' . get_the_title( $event_id ) );
		$product->set_status( 'publish' === get_post_status( $event_id ) ? 'publish' : 'private' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_virtual( true );
		$product->set_sold_individually( true );
		$product->set_tax_status( 'none' );
		$product->set_regular_price( '' !== $base ? $base : '0' );
		$left = EventPricing::places_left( $fields['capacity'], $this->events->count_registrations( $event_id ) );
		$product->set_manage_stock( null !== $left );
		if ( null !== $left ) {
			$product->set_stock_quantity( $left );
		}
		$product->update_meta_data( EventRepository::META_EVENT, $event_id );
		$new_id = $product->save();
		if ( $new_id !== $product_id ) {
			$this->events->link( $event_id, $new_id );
		}
	}

	/**
	 * Makes a product private (not purchasable, orders stay intact).
	 */
	private function hide( int $product_id ): void {
		$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( $product && 'private' !== $product->get_status() ) {
			$product->set_status( 'private' );
			$product->save();
		}
	}
}
