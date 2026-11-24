<?php
/**
 * Module contract.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Core;

/**
 * A self-contained feature that registers its own hooks.
 */
interface Module {

	/**
	 * Stable identifier, stored in options – never rename after a release.
	 */
	public function id(): string;

	/**
	 * Human-readable name.
	 */
	public function name(): string;

	/**
	 * IDs of modules this module depends on.
	 *
	 * @return string[]
	 */
	public function requires(): array;

	/**
	 * Registers the module's hooks. Only called for active modules.
	 */
	public function register(): void;

	/**
	 * Runs on plugin activation (e.g. rewrite rules, seed data).
	 */
	public function activate(): void;
}
