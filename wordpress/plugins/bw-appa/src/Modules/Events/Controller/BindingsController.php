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
		add_filter( 'render_block_core/button', array( $this, 'label_call_to_action' ), 10, 3 );
	}

	/**
	 * Gives event buttons bound to the call to action ("Jetzt anmelden", "Mehr erfahren") a unique accessible
	 * name with the event title, because a list shows the same visible text many times.
	 *
	 * @param string               $content Rendered block.
	 * @param array<string, mixed> $parsed  Parsed block.
	 */
	public function label_call_to_action( string $content, array $parsed, WP_Block $block ): string {
		$binding = $parsed['attrs']['metadata']['bindings']['text'] ?? array();
		if ( '' === $content || self::SOURCE !== ( $binding['source'] ?? '' ) || 'cta' !== ( $binding['args']['key'] ?? '' ) ) {
			return $content;
		}
		$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
		$label   = (string) $this->value( array( 'key' => 'cta' ), $block );
		// set_attribute() escapes itself, so the already escaped texts are decoded first.
		$title = $post_id > 0 ? html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' ) : '';
		if ( '' === $label || '' === $title ) {
			return $content;
		}
		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( array( 'class_name' => 'wp-block-button__link' ) ) && null === $tags->get_attribute( 'aria-label' ) ) {
			/* translators: 1: button text, 2: event title */
			$tags->set_attribute( 'aria-label', sprintf( __( '%1$s: %2$s', 'bw-appa' ), html_entity_decode( $label, ENT_QUOTES, 'UTF-8' ), $title ) );
		}
		return $tags->get_updated_html();
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
