<?php
/**
 * No shop front: products are booked through the membership and event pages.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Modules\Membership\Controller;

use BW\WooMembership\Modules\Membership\Repository\MembershipOrderRepository;
use BW\WooMembership\Modules\Membership\Service\MemberPages;

/**
 * All products are hidden (memberships, event registrations), so the WooCommerce shop page stays empty (David,
 * 25 November 2026: leave it out). The shop page, product archives and product pages redirect to where the product is
 * booked, and every "back to the shop" link leads to the events.
 */
final class ShopController {

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly MembershipOrderRepository $orders ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_get_shop_page_permalink', array( $this, 'shop_url' ) );
		add_filter( 'woocommerce_return_to_shop_redirect', array( $this, 'shop_url' ) );
		add_filter( 'woocommerce_return_to_shop_text', array( $this, 'shop_text' ) );
		add_filter( 'gettext_woocommerce', array( $this, 'texts' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'redirect' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'sitemap' ), 10, 2 );
	}

	/**
	 * Where "the shop" is: the events page.
	 */
	public function shop_url(): string {
		/**
		 * Filters the page that replaces the WooCommerce shop page.
		 *
		 * @param string $url Default: the event list (MemberPages::events_url()).
		 */
		return (string) apply_filters( 'bw_wm_shop_replacement_url', MemberPages::events_url() );
	}

	/**
	 * Button text instead of "Zurück zum Shop".
	 */
	public function shop_text(): string {
		return __( 'Zu den Veranstaltungen', 'bw-woo-membership' );
	}

	/**
	 * Same text in the (empty) mini cart.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 */
	public function texts( mixed $translation, mixed $text ): mixed {
		return 'Return to shop' === $text ? $this->shop_text() : $translation;
	}

	/**
	 * Shop page, product archives and product pages lead to where the product is booked.
	 */
	public function redirect(): void {
		if ( ! function_exists( 'is_shop' ) || is_admin() ) {
			return;
		}
		$target = '';
		if ( is_shop() || is_product_taxonomy() ) {
			$target = $this->shop_url();
		} elseif ( is_product() ) {
			$product_id = (int) get_queried_object_id();
			$event_id   = (int) get_post_meta( $product_id, '_bw_wm_event_id', true );
			$target     = match ( true ) {
				$event_id > 0                                   => (string) get_permalink( $event_id ),
				'' !== $this->orders->product_type( $product_id ) => MemberPages::membership_url(),
				default                                         => $this->shop_url(),
			};
		}
		if ( '' !== $target ) {
			wp_safe_redirect( $target, 301, 'APPA' ); // Permanent: the shop is gone for good.
			exit;
		}
	}

	/**
	 * The shop page is not listed in the sitemap.
	 *
	 * @param array<string, mixed> $args      Query arguments.
	 * @param string               $post_type Post type.
	 * @return array<string, mixed>
	 */
	public function sitemap( mixed $args, mixed $post_type ): mixed {
		$shop = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
		if ( 'page' === $post_type && $shop > 0 ) {
			$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), array( $shop ) );
		}
		return $args;
	}
}
