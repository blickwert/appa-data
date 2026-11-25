<?php
/**
 * Theme images.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

namespace BW\AppaTheme;

/**
 * Images of the theme (assets/images) are not in the media library, so WordPress adds no width/height. This adds the
 * real size (no layout shift) and gives the large photo at the top of a page priority (Largest Contentful Paint).
 */
final class Images {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_filter( 'render_block_core/image', array( $this, 'image' ), 10, 1 );
		add_filter( 'render_block_core/cover', array( $this, 'cover' ), 10, 1 );
	}

	/**
	 * Content images: width and height.
	 */
	public function image( string $html ): string {
		return $this->size( $html, false );
	}

	/**
	 * Photo bands at the top: width, height and high priority.
	 */
	public function cover( string $html ): string {
		return $this->size( $html, true );
	}

	/**
	 * Adds the attributes to the first <img> of a theme image.
	 */
	private function size( string $html, bool $priority ): string {
		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag( 'img' ) ) {
			return $html;
		}
		$src  = (string) $tags->get_attribute( 'src' );
		$base = get_theme_file_uri( 'assets/images/' );
		if ( ! str_starts_with( $src, $base ) ) {
			return $html;
		}
		$file = get_theme_file_path( 'assets/images/' . basename( (string) wp_parse_url( $src, PHP_URL_PATH ) ) );
		$size = is_readable( $file ) ? wp_getimagesize( $file ) : false;
		if ( is_array( $size ) && null === $tags->get_attribute( 'width' ) ) {
			$tags->set_attribute( 'width', (string) $size[0] );
			$tags->set_attribute( 'height', (string) $size[1] );
		}
		if ( $priority ) {
			$tags->set_attribute( 'fetchpriority', 'high' );
			$tags->remove_attribute( 'loading' );
		} elseif ( null === $tags->get_attribute( 'loading' ) ) {
			$tags->set_attribute( 'decoding', 'async' );
		}
		return $tags->get_updated_html();
	}
}
