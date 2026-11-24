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
			'contact'    => array(
				'title'   => 'APPA – Kontakt',
				'form'    => $this->contact_form(),
				'subject' => 'Kontaktanfrage: [betreff]',
				'body'    => "Name: [name]\nE-Mail: [email]\nBetreff: [betreff]\n\n[nachricht]\n\n-- \nGesendet über das Kontaktformular von [_site_url]",
				'sent'    => 'Danke für deine Nachricht! Wir melden uns so bald wie möglich bei dir.',
			),
			'membership' => array(
				'title'   => 'APPA – Mitgliedsantrag',
				'form'    => $this->membership_form(),
				'subject' => 'Mitgliedsantrag: [vorname] [nachname]',
				'body'    => "Neuer Mitgliedsantrag\n\nVorname: [vorname]\nNachname: [nachname]\nE-Mail: [email]\nAnschrift:\n[anschrift]\n\nBeruf / Studienrichtung: [beruf]\nBeitrag: [beitrag]\nFachgruppen: [fachgruppen]\n\nIdeen oder Fragen:\n[ideen]\n\n-- \nGesendet über [_site_url]",
				'sent'    => 'Danke für dein Interesse an der APPA! Dein Antrag ist bei uns eingegangen. Wir melden uns bei dir, sobald der Vorstand über deine Aufnahme entschieden hat.',
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

	/**
	 * Membership application template.
	 */
	private function membership_form(): string {
		return implode(
			"\n",
			array(
				'<div class="bwa-form">',
				'<p class="bwa-form__field bwa-form__field--half"><label>Vorname *<br>[text* vorname autocomplete:given-name]</label></p>',
				'<p class="bwa-form__field bwa-form__field--half"><label>Nachname *<br>[text* nachname autocomplete:family-name]</label></p>',
				'<p class="bwa-form__field"><label>E-Mail-Adresse *<br>[email* email autocomplete:email]</label></p>',
				'<p class="bwa-form__field"><label>Anschrift *<br>[textarea* anschrift x3 autocomplete:street-address]</label></p>',
				'<p class="bwa-form__field"><label>Beruf oder Studienrichtung (freiwillig)<br>[text beruf]</label></p>',
				'<p class="bwa-form__field"><label>Beitrag *<br>[select* beitrag "Regulär (96 Euro pro Jahr)" "Studierende (35 Euro pro Jahr)"]</label></p>',
				'<fieldset class="bwa-form__field bwa-form__group"><legend>Interessante Fachgruppen (freiwillig)</legend>[checkbox fachgruppen use_label_element "Positive Coaching" "Positive Clinical Psychology" "Positive Health" "Positive Education" "Positive Leadership" "Positive Inclusion"]</fieldset>',
				'<p class="bwa-form__field"><label>Deine Ideen oder Fragen (freiwillig)<br>[textarea ideen x4]</label></p>',
				'<p class="bwa-form__field bwa-form__consent">[acceptance statuten] Ich habe die Statuten und die Datenschutzhinweise gelesen. [/acceptance]</p>',
				'<p class="bwa-form__actions">[submit class:bwa-form__submit "Mitgliedschaft beantragen"]</p>',
				'</div>',
			)
		);
	}
}
