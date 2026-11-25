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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_woocommerce' ), 99 );
		add_action( 'init', array( $this, 'enqueue_block_styles' ) );
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );
	}

	/**
	 * Base styles (focus, skip link, small resets) that theme.json cannot express, and the small navigation script.
	 */
	public function enqueue_base(): void {
		wp_enqueue_style( 'bw-appa-theme-base', get_theme_file_uri( 'assets/css/base.css' ), array(), Theme::version() );
		wp_enqueue_script(
			'bw-appa-theme-navigation',
			get_theme_file_uri( 'assets/js/navigation.js' ),
			array(),
			Theme::version(),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		if ( is_page_template( array( 'page-member', 'page-member-dashboard' ) ) ) {
			wp_enqueue_style( 'bw-appa-theme-member', get_theme_file_uri( 'assets/css/member.css' ), array( 'bw-appa-theme-base' ), Theme::version() );
		}
	}

	/**
	 * WooCommerce account pages and forms in the look of the member area. Runs after WooCommerce's own styles.
	 */
	public function enqueue_woocommerce(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		if ( ! self::is_shop_page() ) {
			// Pages without shop parts load none of the WooCommerce styles and scripts (render-blocking, jQuery).
			foreach ( array( 'woocommerce', 'wc-add-to-cart', 'wc-cart-fragments', 'sourcebuster-js', 'wc-order-attribution', 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-blocktheme', 'wcs-view-subscription', 'wc-blocks-style' ) as $handle ) {
				wp_dequeue_script( $handle );
				wp_dequeue_style( $handle );
			}
			return;
		}
		wp_enqueue_style( 'bw-appa-theme-woocommerce', get_theme_file_uri( 'assets/css/woocommerce.css' ), array( 'bw-appa-theme-base' ), Theme::version() );
	}

	/**
	 * Cart, checkout, account and the (redirecting) shop pages.
	 */
	private static function is_shop_page(): bool {
		return ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
			|| ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) );
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
					'handle' => 'bw-appa-theme-' . $name,
					'src'    => get_theme_file_uri( "assets/css/blocks/{$name}.css" ),
					'path'   => get_theme_file_path( "assets/css/blocks/{$name}.css" ),
					'ver'    => Theme::version(),
				)
			);
		}
	}
}
