<?php
/**
 * Theme entry point.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

namespace BW\AppaTheme;

/**
 * Wires the theme's services into WordPress.
 */
final class Theme {

	public const SLUG = 'appa';

	private static ?Theme $instance = null;

	/**
	 * Returns the single theme instance.
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Registers all hooks.
	 */
	public function boot(): void {
		add_action( 'after_setup_theme', array( $this, 'setup' ) );
		( new Assets() )->register();
		( new Patterns() )->register();
		( new Blocks() )->register();
		( new Images() )->register();
	}

	/**
	 * Theme supports and editor styles.
	 */
	public function setup(): void {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/base.css' );
		remove_theme_support( 'core-block-patterns' );
	}

	/**
	 * Version used for cache busting of theme assets.
	 */
	public static function version(): string {
		return (string) wp_get_theme( self::SLUG )->get( 'Version' );
	}
}
