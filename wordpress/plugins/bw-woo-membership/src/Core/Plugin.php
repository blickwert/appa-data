<?php
/**
 * Plugin entry point.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Core;

/**
 * Boots the modules.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	/**
	 * Active modules.
	 *
	 * @var array<string, Module>
	 */
	private array $modules = array();

	/**
	 * Returns the single plugin instance.
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Discovers and registers all modules.
	 */
	public function boot(): void {
		Setup::register();
		$this->modules = ( new ModuleManager() )->discover();
		foreach ( $this->modules as $module ) {
			$module->register();
		}
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'bw-woo-membership', false, dirname( plugin_basename( BW_WM_FILE ) ) . '/languages' );
	}

	/**
	 * Activation: let modules prepare data, then flush rewrite rules once.
	 */
	public static function activate(): void {
		foreach ( self::instance()->modules as $module ) {
			$module->activate();
		}
		Setup::run();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation: remove the modules' rewrite rules.
	 */
	public static function deactivate(): void {
		Setup::deactivate();
		flush_rewrite_rules();
	}
}
