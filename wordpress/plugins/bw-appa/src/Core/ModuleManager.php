<?php
/**
 * Module discovery.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Core;

use BW\Appa\Modules\Events\EventsModule;
use BW\Appa\Modules\Forms\FormsModule;

/**
 * Collects the modules and registers those whose dependencies are met.
 */
final class ModuleManager {

	/**
	 * Active modules keyed by ID.
	 *
	 * @var array<string, Module>
	 */
	private array $modules = array();

	/**
	 * Builds the module list; other plugins may add modules via `bw_appa_register_modules`.
	 *
	 * @return array<string, Module>
	 */
	public function discover(): array {
		$modules = array( new EventsModule(), new FormsModule() );

		/**
		 * Filters the list of modules.
		 *
		 * @param Module[] $modules Module instances.
		 */
		$modules = (array) apply_filters( 'bw_appa_register_modules', $modules );

		foreach ( $modules as $module ) {
			if ( $module instanceof Module ) {
				$this->modules[ $module->id() ] = $module;
			}
		}

		foreach ( $this->modules as $id => $module ) {
			foreach ( $module->requires() as $required ) {
				if ( ! isset( $this->modules[ $required ] ) ) {
					unset( $this->modules[ $id ] );
				}
			}
		}

		return $this->modules;
	}
}
