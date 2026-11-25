<?php
/**
 * Module base class.
 *
 * @package BW\WooMembership
 */

declare(strict_types=1);

namespace BW\WooMembership\Core;

/**
 * Sensible defaults for modules.
 */
abstract class AbstractModule implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return '';
	}

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
