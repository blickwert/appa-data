<?php
/**
 * Settings page view.
 *
 * @package BW\Appa
 *
 * @var array{recipient: string, min_seconds: int, max_links: int, max_per_hour: int, blocked_words: string} $values
 * @var callable(string): string $name
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'APPA Formulare', 'bw-appa' ); ?></h1>
	<p><?php esc_html_e( 'Empfänger und Spamschutz für Kontakt- und Mitgliedsformular. Cloudflare Turnstile wird in Contact Form 7 unter „Kontakt → Integration“ eingerichtet.', 'bw-appa' ); ?></p>
	<form method="post" action="options.php">
		<?php settings_fields( 'bw-appa-forms' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="bw-appa-recipient"><?php esc_html_e( 'Empfänger', 'bw-appa' ); ?></label></th>
				<td><input type="email" id="bw-appa-recipient" class="regular-text" name="<?php echo esc_attr( $name( 'recipient' ) ); ?>" value="<?php echo esc_attr( $values['recipient'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="bw-appa-min-seconds"><?php esc_html_e( 'Mindestausfüllzeit (Sekunden)', 'bw-appa' ); ?></label></th>
				<td><input type="number" min="0" max="60" id="bw-appa-min-seconds" name="<?php echo esc_attr( $name( 'min_seconds' ) ); ?>" value="<?php echo esc_attr( (string) $values['min_seconds'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="bw-appa-max-links"><?php esc_html_e( 'Höchstens Links pro Nachricht', 'bw-appa' ); ?></label></th>
				<td><input type="number" min="0" max="20" id="bw-appa-max-links" name="<?php echo esc_attr( $name( 'max_links' ) ); ?>" value="<?php echo esc_attr( (string) $values['max_links'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="bw-appa-max-per-hour"><?php esc_html_e( 'Höchstens Einsendungen pro Stunde und Absender', 'bw-appa' ); ?></label></th>
				<td><input type="number" min="1" max="100" id="bw-appa-max-per-hour" name="<?php echo esc_attr( $name( 'max_per_hour' ) ); ?>" value="<?php echo esc_attr( (string) $values['max_per_hour'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="bw-appa-blocked"><?php esc_html_e( 'Sperrwörter (eines pro Zeile)', 'bw-appa' ); ?></label></th>
				<td><textarea id="bw-appa-blocked" class="large-text" rows="5" name="<?php echo esc_attr( $name( 'blocked_words' ) ); ?>"><?php echo esc_textarea( $values['blocked_words'] ); ?></textarea></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
