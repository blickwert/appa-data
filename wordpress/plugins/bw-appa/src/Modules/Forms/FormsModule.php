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
use BW\Appa\Modules\Forms\Service\SpamGuard;

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
	public function description(): string {
		return __( 'Formulare auf Basis von Contact Form 7 mit Honeypot, Mindest-Ausfüllzeit und Rate-Limit.', 'bw-appa' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$forms    = new FormRepository( new FormDefinitions() );
		$settings = new SettingsRepository();
		$guard    = static function () use ( $settings ): SpamGuard {
			$config = $settings->get();
			return new SpamGuard( $config['min_seconds'], $config['max_links'], $config['max_per_hour'], $settings->blocked_words() );
		};
		( new Cf7Controller( $forms, $settings, new RateLimitRepository(), new FormToken( static fn(): string => wp_salt( 'nonce' ) ), $guard ) )->register();
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
