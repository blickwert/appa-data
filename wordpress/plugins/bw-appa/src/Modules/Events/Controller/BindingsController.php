<?php
/**
 * Block bindings.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Events\Controller;

use BW\Appa\Modules\Events\Repository\EventRepository;
use BW\Appa\Modules\Events\Service\EventFormatter;
use WP_Block;

/**
 * Provides the block bindings source "bw-appa/event" (keys: when, access, zoom_link) and hides blocks whose
 * bound value is empty, e.g. the Zoom button of an event without a link.
 */
final class BindingsController {

	public const SOURCE = 'bw-appa/event';

	/**
	 * Builds the controller.
	 */
	public function __construct(
		private readonly EventRepository $events,
		private readonly EventFormatter $formatter,
	) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_source' ) );
		add_filter( 'render_block', array( $this, 'hide_empty_bound_blocks' ), 10, 3 );
	}

	/**
	 * Registers the bindings source.
	 */
	public function register_source(): void {
		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'Veranstaltung', 'bw-appa' ),
				'get_value_callback' => array( $this, 'value' ),
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	}

	/**
	 * Value of a bound attribute.
	 *
	 * @param array<string, mixed> $args Source arguments, `key` selects the field.
	 */
	public function value( array $args, WP_Block $block ): ?string {
		$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
		if ( $post_id <= 0 || EventRepository::POST_TYPE !== get_post_type( $post_id ) ) {
			return null;
		}
		$event = $this->events->find( $post_id );
		$key   = (string) ( $args['key'] ?? '' );
		if ( in_array( $key, array( 'access', 'zoom_link' ), true ) && ! PrivacyController::private_visible( $post_id ) ) {
			return '';
		}

		return match ( $key ) {
			'when'      => esc_html( $this->formatter->when( $event ) ),
			'place'     => esc_html( $this->formatter->place( $event ) ),
			'badge'     => esc_html( $this->formatter->badge( $event ) ),
			'cta'       => esc_html( $this->formatter->call_to_action( $event ) ),
			'url'       => esc_url_raw( (string) get_permalink( $post_id ) . ( $event->registration ? '#anmeldung' : '' ) ),
			'access'    => esc_html( $this->formatter->access( $event ) ),
			'zoom_link' => esc_url_raw( $event->zoom_link ),
			default     => null,
		};
	}

	/**
	 * Removes paragraphs and buttons bound to this source when the value is empty.
	 *
	 * @param string               $content Rendered block.
	 * @param array<string, mixed> $parsed  Parsed block.
	 */
	public function hide_empty_bound_blocks( string $content, array $parsed, WP_Block $block ): string {
		$bindings = $parsed['attrs']['metadata']['bindings'] ?? array();
		foreach ( $bindings as $attribute => $binding ) {
			if ( self::SOURCE !== ( $binding['source'] ?? '' ) ) {
				continue;
			}
			$value = $this->value( (array) ( $binding['args'] ?? array() ), $block );
			if ( null !== $value && '' === $value ) {
				return '';
			}
		}
		return $content;
	}
}
