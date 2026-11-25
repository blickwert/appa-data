<?php
/**
 * Form block.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Controller;

use BW\Appa\Modules\Forms\Repository\FormRepository;

/**
 * Registers the block "bw-appa/form" that places one of the APPA forms in a page or pattern.
 */
final class BlockController {

	/**
	 * Builds the controller.
	 */
	public function __construct( private readonly FormRepository $forms ) {
	}

	/**
	 * Registers hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Registers the block from its block.json.
	 */
	public function register_block(): void {
		register_block_type(
			BW_APPA_DIR . '/blocks/form',
			array( 'render_callback' => array( $this, 'render' ) )
		);
		wp_set_script_translations( generate_block_asset_handle( 'bw-appa/form', 'editorScript' ), 'bw-appa', BW_APPA_DIR . '/languages' );
	}

	/**
	 * Renders the CF7 form for the chosen key.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render( array $attributes ): string {
		$key  = is_string( $attributes['form'] ?? null ) ? $attributes['form'] : 'contact';
		$form = $this->forms->find( $key );
		if ( null === $form ) {
			return current_user_can( 'edit_pages' )
				? '<p class="bw-form-missing">' . esc_html__( 'Formular nicht verfügbar: Bitte Contact Form 7 aktivieren.', 'bw-appa' ) . '</p>'
				: '';
		}
		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( array( 'class' => 'bw-form-block' ) ),
			do_shortcode( sprintf( '[contact-form-7 id="%s" html_class="bw-cf7"]', esc_attr( $form->hash() ) ) )
		);
	}
}
