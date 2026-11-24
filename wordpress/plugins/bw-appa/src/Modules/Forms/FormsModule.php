<?php
/**
 * Forms module.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms;

use BW\Appa\Core\AbstractModule;
use BW\Appa\Modules\Forms\Controller\BlockController;
use BW\Appa\Modules\Forms\Controller\Cf7Controller;
use BW\Appa\Modules\Forms\Controller\SettingsController;
use BW\Appa\Modules\Forms\Repository\FormRepository;
use BW\Appa\Modules\Forms\Repository\RateLimitRepository;
use BW\Appa\Modules\Forms\Repository\SettingsRepository;
use BW\Appa\Modules\Forms\Service\FormDefinitions;
use BW\Appa\Modules\Forms\Service\FormToken;

/**
 * Contact form on top of Contact Form 7 (membership: WooCommerce, plugin bw-woo-membership), with honeypot, fill time and rate limit.
 */
final class FormsModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'forms';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return __( 'Formulare', 'bw-appa' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$forms    = new FormRepository( new FormDefinitions() );
		$settings = new SettingsRepository();
		( new Cf7Controller( $forms, $settings, new RateLimitRepository(), new FormToken( static fn(): string => wp_salt( 'nonce' ) ) ) )->register();
		( new SettingsController( $settings ) )->register();
		( new BlockController( $forms ) )->register();
	}

	/**
	 * {@inheritDoc}
	 */
	public function activate(): void {
		( new FormRepository( new FormDefinitions() ) )->ensure_all();
	}
}
