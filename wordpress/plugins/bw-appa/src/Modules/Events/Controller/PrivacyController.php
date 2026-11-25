<?php
/**
 * Private event data.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Controller;

use BW\Appa\Modules\Events\Repository\EventRepository;
use WP_Post;
use WP_REST_Response;

/**
 * Keeps Zoom link and access data private: on the website they show only when `bw_appa_event_private_visible` allows
 * it, in the REST API only for editors.
 */
final class PrivacyController {

	/**
	 * Meta keys that are private.
	 */
	private const PRIVATE_KEYS = array( EventRepository::META_LINK, EventRepository::META_ACCESS );

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'rest_prepare_' . EventRepository::POST_TYPE, array( $this, 'strip_from_rest' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'no_cache' ) );
	}

	/**
	 * Whether the current visitor may see Zoom link and access data of an event.
	 */
	public static function private_visible( int $post_id ): bool {
		/**
		 * Filters whether the private data of an event (Zoom link, access data) is visible to the current user.
		 * Without a membership plugin everyone sees it (default true); the plugin "APPA Mitgliedschaft" limits it
		 * to members or registered participants.
		 *
		 * @param bool $visible Default true.
		 * @param int  $post_id Event ID.
		 * @param int  $user_id Current user (0 = guest).
		 */
		return (bool) apply_filters( 'bw_appa_event_private_visible', true, $post_id, get_current_user_id() );
	}

	/**
	 * Removes the private fields from REST responses for everyone who cannot edit the event.
	 */
	public function strip_from_rest( WP_REST_Response $response, WP_Post $post ): WP_REST_Response {
		if ( current_user_can( 'edit_post', $post->ID ) ) {
			return $response;
		}
		$data = $response->get_data();
		if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( self::PRIVATE_KEYS as $key ) {
				unset( $data['meta'][ $key ] );
			}
			$response->set_data( $data );
		}
		return $response;
	}

	/**
	 * An event page with access data looks different for registered people, so it must not come from a page cache
	 * (the hosting cache does not skip logged-in users).
	 */
	public function no_cache(): void {
		if ( ! is_singular( EventRepository::POST_TYPE ) ) {
			return;
		}
		$event_id = (int) get_queried_object_id();
		if ( '' === (string) get_post_meta( $event_id, EventRepository::META_LINK, true ) && '' === (string) get_post_meta( $event_id, EventRepository::META_ACCESS, true ) ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- constant read by caching plugins.
		}
		nocache_headers();
		header( 'X-Accel-Expires: 0' );
	}
}
