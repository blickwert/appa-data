<?php
/**
 * Stylesheets.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

namespace BW\AppaTheme;

/**
 * Loads the global base stylesheet and one stylesheet per core block, only when that block is on the page.
 */
final class Assets {

	/**
	 * Core blocks with their own component stylesheet in assets/css/blocks/<name>.css.
	 */
	private const BLOCK_STYLES = array(
		'core/button',
		'core/cover',
		'core/details',
		'core/group',
		'core/list',
		'core/navigation',
		'core/post-template',
		'core/quote',
	);

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_base' ) );
		add_action( 'init', array( $this, 'enqueue_block_styles' ) );
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );
	}

	/**
	 * Base styles (focus, skip link, small resets) that theme.json cannot express.
	 */
	public function enqueue_base(): void {
		wp_enqueue_style( 'bw-appa-base', get_theme_file_uri( 'assets/css/base.css' ), array(), Theme::version() );
		if ( is_page_template( array( 'page-member', 'page-member-dashboard' ) ) ) {
			wp_enqueue_style( 'bw-appa-member', get_theme_file_uri( 'assets/css/member.css' ), array( 'bw-appa-base' ), Theme::version() );
		}
	}

	/**
	 * Component CSS per block; WordPress inlines small files and loads them only where the block is used.
	 */
	public function enqueue_block_styles(): void {
		foreach ( self::BLOCK_STYLES as $block ) {
			$name = str_replace( 'core/', '', $block );
			wp_enqueue_block_style(
				$block,
				array(
					'handle' => 'bw-appa-' . $name,
					'src'    => get_theme_file_uri( "assets/css/blocks/{$name}.css" ),
					'path'   => get_theme_file_path( "assets/css/blocks/{$name}.css" ),
					'ver'    => Theme::version(),
				)
			);
		}
	}
}
