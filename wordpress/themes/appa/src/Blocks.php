<?php
/**
 * Theme blocks.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

namespace BW\AppaTheme;

/**
 * Registers the theme's own blocks from /blocks (block.json metadata).
 */
final class Blocks {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Registers each block folder and passes the icon list to the editor.
	 */
	public function register_blocks(): void {
		register_block_type( get_theme_file_path( 'blocks/icon' ) );
		wp_add_inline_script(
			'bw-icon-editor-script',
			'window.bwAppaIcons = ' . wp_json_encode( Icons::names() ) . ';',
			'before'
		);
	}
}
