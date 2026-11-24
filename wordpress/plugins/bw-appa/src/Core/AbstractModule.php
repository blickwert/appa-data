<?php
/**
 * Module base class.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Core;

/**
 * Sensible defaults for modules.
 */
abstract class AbstractModule implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function requires(): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function activate(): void {
	}
}
