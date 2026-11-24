<?php
/**
 * Pattern categories.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

namespace BW\AppaTheme;

/**
 * Registers the theme's pattern categories; the patterns themselves are auto-registered from /patterns.
 */
final class Patterns {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_categories' ) );
	}

	/**
	 * Adds the APPA categories to the inserter.
	 */
	public function register_categories(): void {
		$categories = array(
			'appa'         => __( 'APPA – Abschnitte', 'appa' ),
			'appa-pages'   => __( 'APPA – Seiteninhalte', 'appa' ),
			'appa-content' => __( 'APPA – Bausteine', 'appa' ),
		);
		foreach ( $categories as $slug => $label ) {
			register_block_pattern_category( $slug, array( 'label' => $label ) );
		}
	}
}
