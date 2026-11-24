<?php
/**
 * Settings page.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Controller;

use BW\Appa\Modules\Forms\Repository\SettingsRepository;

/**
 * "Einstellungen → APPA Formulare": recipient and spam thresholds.
 */
final class SettingsController {

	private const PAGE = 'bw-appa-forms';

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly SettingsRepository $settings ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Registers the option with its sanitiser.
	 */
	public function register_setting(): void {
		register_setting(
			self::PAGE,
			SettingsRepository::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => fn( mixed $input ): array => $this->settings->sanitize( is_array( $input ) ? $input : array() ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Adds the page under Settings.
	 */
	public function add_page(): void {
		add_options_page(
			__( 'APPA Formulare', 'bw-appa' ),
			__( 'APPA Formulare', 'bw-appa' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the settings form.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$values = $this->settings->get();
		$name   = static fn( string $key ): string => SettingsRepository::OPTION . '[' . $key . ']';
		require BW_APPA_DIR . '/templates/forms/settings.php';
	}
}
