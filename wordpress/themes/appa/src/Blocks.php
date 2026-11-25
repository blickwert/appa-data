<?php
/**
 * Theme blocks.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

namespace BW\AppaTheme;

/**
 * Registers the theme's own blocks from /blocks (block.json metadata) and adjusts third-party header blocks.
 */
final class Blocks {

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'pre_get_avatar', array( $this, 'account_icon' ), 10, 3 );
	}

	/**
	 * Header account link (WooCommerce block "customer-account"): always the line icon, never a Gravatar image –
	 * it looks the same for everyone and loads nothing from a foreign server.
	 *
	 * @param string|null          $avatar      Avatar markup, null to continue.
	 * @param mixed                $id_or_email User.
	 * @param array<string, mixed> $args        Avatar arguments.
	 */
	public function account_icon( $avatar, $id_or_email, $args ) {
		$class = (array) ( $args['class'] ?? array() );
		return in_array( 'wc-block-customer-account__avatar', $class, true ) || str_contains( implode( ' ', $class ), 'wc-block-customer-account__avatar' ) ? '' : $avatar;
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
