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
				'title'   => 'APPA – Kontakt',
				'form'    => $this->contact_form(),
				'subject' => 'Kontaktanfrage: [betreff]',
				'body'    => "Name: [name]\nE-Mail: [email]\nBetreff: [betreff]\n\n[nachricht]\n\n-- \nGesendet über das Kontaktformular von [_site_url]",
				'sent'    => 'Danke für deine Nachricht! Wir melden uns so bald wie möglich bei dir.',
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
				'<div class="bwa-form">',
				'<p class="bwa-form__field bwa-form__field--half"><label>Vor- und Nachname *<br>[text* name autocomplete:name]</label></p>',
				'<p class="bwa-form__field bwa-form__field--half"><label>E-Mail-Adresse *<br>[email* email autocomplete:email]</label></p>',
				'<p class="bwa-form__field"><label>Betreff *<br>[text* betreff]</label></p>',
				'<p class="bwa-form__field"><label>Nachricht *<br>[textarea* nachricht x5]</label></p>',
				'<p class="bwa-form__field bwa-form__consent">[acceptance datenschutz] Ich habe die Datenschutzhinweise gelesen und bin einverstanden, dass meine Angaben zur Beantwortung meiner Anfrage verarbeitet werden. [/acceptance]</p>',
				'<p class="bwa-form__actions">[submit class:bwa-form__submit "Nachricht senden"]</p>',
				'</div>',
			)
		);
	}
}
