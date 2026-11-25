<?php
/**
 * Registration product of an event.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Events\Service;

use BW\WooMembership\Modules\Events\Repository\EventRepository;

/**
 * Keeps the hidden WooCommerce product of an event in line with the event: name, base price, visibility and stock
 * (= free places).
 */
final class ProductSync {

	/**
	 * Builds the service.
	 */
	public function __construct( private readonly EventRepository $events ) {
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
		/* translators: %s: event title */
		$product->set_name( sprintf( __( 'Anmeldung: %s', 'bw-woo-membership' ), get_the_title( $event_id ) ) );
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
		/**
		 * Free places of an event were recalculated (event saved or a registration changed).
		 *
		 * @param int      $event_id Event.
		 * @param int|null $left     Free places, null = unlimited.
		 */
		do_action( 'bw_wm_event_places_changed', $event_id, $left );
	}

	/**
	 * Makes a product private (not purchasable, orders stay intact).
	 */
	public function hide( int $product_id ): void {
		$product = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( $product && 'private' !== $product->get_status() ) {
			$product->set_status( 'private' );
			$product->save();
		}
	}
}
