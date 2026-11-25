<?php
/**
 * Contact Form 7 forms.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Repository;

use BW\Appa\Modules\Forms\Service\FormDefinitions;
use WPCF7_ContactForm;

/**
 * Creates the CF7 forms once and remembers their IDs.
 */
final class FormRepository {

	public const OPTION = 'bw_appa_forms_ids';

	/**
	 * Builds the repository.
	 */
	public function __construct( private readonly FormDefinitions $definitions ) {
	}

	/**
	 * CF7 form for a key, or null if it does not exist (yet).
	 */
	public function find( string $key ): ?WPCF7_ContactForm {
		if ( ! class_exists( WPCF7_ContactForm::class ) ) {
			return null;
		}
		$ids = (array) get_option( self::OPTION, array() );
		$id  = (int) ( $ids[ $key ] ?? 0 );
		return $id > 0 ? WPCF7_ContactForm::get_instance( $id ) : null;
	}

	/**
	 * Whether a CF7 form is one of the forms this plugin created.
	 */
	public function is_ours( int $form_id ): bool {
		return $form_id > 0 && in_array( $form_id, array_map( 'intval', (array) get_option( self::OPTION, array() ) ), true );
	}

	/**
	 * Creates missing forms. Existing forms are never overwritten, so edits in CF7 stay.
	 */
	public function ensure_all(): void {
		if ( ! class_exists( WPCF7_ContactForm::class ) ) {
			return;
		}
		$ids = (array) get_option( self::OPTION, array() );
		foreach ( $this->definitions->all() as $key => $definition ) {
			if ( $this->find( $key ) ) {
				continue;
			}
			$form = WPCF7_ContactForm::get_template( array( 'title' => $definition['title'] ) );
			$form->set_properties(
				array(
					'form'     => $definition['form'],
					'mail'     => array(
						'active'             => true,
						'subject'            => $definition['subject'],
						'sender'             => '[_site_title] <wordpress@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
						'recipient'          => '[_site_admin_email]',
						'body'               => $definition['body'],
						'additional_headers' => 'Reply-To: [email]',
						'attachments'        => '',
						'use_html'           => false,
						'exclude_blank'      => true,
					),
					'messages' => array_merge(
						$form->prop( 'messages' ),
						array( 'mail_sent_ok' => $definition['sent'] )
					),
				)
			);
			$ids[ $key ] = $form->save();
		}
		update_option( self::OPTION, $ids, false );
	}
}
