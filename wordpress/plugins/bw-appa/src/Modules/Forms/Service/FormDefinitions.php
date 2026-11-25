<?php
/**
 * Form definitions.
 *
 * @package BW\Appa
 */

declare(strict_types=1);

namespace BW\Appa\Modules\Forms\Service;

/**
 * The forms of the website as Contact Form 7 templates, mails and messages.
 */
final class FormDefinitions {

	/**
	 * All forms keyed by their stable key.
	 *
	 * @return array<string, array{title: string, form: string, subject: string, body: string, sent: string}>
	 */
	public function all(): array {
		return array(
			'contact' => array(
				'title'   => __( 'APPA – Kontakt', 'bw-appa' ),
				'form'    => $this->contact_form(),
				'subject' => __( 'Kontaktanfrage: [betreff]', 'bw-appa' ),
				'body'    => __( "Name: [name]\nE-Mail: [email]\nBetreff: [betreff]\n\n[nachricht]\n\n-- \nGesendet über das Kontaktformular von [_site_url]", 'bw-appa' ),
				'sent'    => __( 'Danke für deine Nachricht! Wir melden uns so bald wie möglich bei dir.', 'bw-appa' ),
			),
		);
	}

	/**
	 * One definition or null.
	 *
	 * @return array{title: string, form: string, subject: string, body: string, sent: string}|null
	 */
	public function get( string $key ): ?array {
		return $this->all()[ $key ] ?? null;
	}

	/**
	 * Contact form template.
	 */
	private function contact_form(): string {
		return implode(
			"\n",
			array(
				'<div class="bw-form">',
				'<p class="bw-form__field bw-form__field--half"><label>' . esc_html__( 'Vor- und Nachname *', 'bw-appa' ) . '<br>[text* name autocomplete:name]</label></p>',
				'<p class="bw-form__field bw-form__field--half"><label>' . esc_html__( 'E-Mail-Adresse *', 'bw-appa' ) . '<br>[email* email autocomplete:email]</label></p>',
				'<p class="bw-form__field"><label>' . esc_html__( 'Betreff *', 'bw-appa' ) . '<br>[text* betreff]</label></p>',
				'<p class="bw-form__field"><label>' . esc_html__( 'Nachricht *', 'bw-appa' ) . '<br>[textarea* nachricht x5]</label></p>',
				'<p class="bw-form__field bw-form__consent">[acceptance datenschutz] ' . esc_html__( 'Ich habe die Datenschutzhinweise gelesen und bin einverstanden, dass meine Angaben zur Beantwortung meiner Anfrage verarbeitet werden.', 'bw-appa' ) . ' [/acceptance]</p>',
				'<p class="bw-form__actions">[submit class:bw-form__submit "' . esc_attr__( 'Nachricht senden', 'bw-appa' ) . '"]</p>',
				'</div>',
			)
		);
	}
}
