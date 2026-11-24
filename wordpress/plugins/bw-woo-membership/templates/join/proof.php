<?php
/**
 * Join as a student: proof upload before the checkout.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by JoinProofController::render().
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="bwm-section bwm-proof bwm-join-proof" id="nachweis" aria-labelledby="bwm-join-proof">
	<h2 id="bwm-join-proof" class="screen-reader-text">Studiennachweis hochladen</h2>
	<?php if ( '' !== $data['error'] ) : ?>
		<p class="bwm-saved bwm-saved--error" role="alert"><?php echo esc_html( $data['error'] ); ?></p>
	<?php endif; ?>
	<?php if ( $data['has'] ) : ?>
		<p class="bwm-saved" role="status">Danke, dein Nachweis ist da<?php echo '' !== $data['name'] ? ': ' . esc_html( $data['name'] ) : ''; ?>. Wir prüfen ihn nach deinem Beitritt.</p>
		<p><a class="bwm-button" href="<?php echo esc_url( $data['checkout'] ); ?>">Weiter zur Kasse</a></p>
		<p class="bwm-sub">Falsche Datei? Lade einfach eine neue hoch.</p>
	<?php endif; ?>
	<form class="bwm-form bwm-proof__form" method="post" enctype="multipart/form-data">
		<?php echo $data['nonce']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nonce_field() output. ?>
		<p><label for="bwm-join-proof-file">Inskriptionsbestätigung des aktuellen Semesters (PDF, JPG oder PNG, höchstens 5 MB)</label><input id="bwm-join-proof-file" type="file" name="bw_wm_proof" accept=".pdf,.jpg,.jpeg,.png" required></p>
		<p><button class="bwm-button<?php echo $data['has'] ? ' bwm-button--outline' : ''; ?>" type="submit"><?php echo $data['has'] ? 'Neue Datei hochladen' : 'Hochladen und weiter zur Kasse'; ?></button></p>
		<p class="bwm-sub">Wir löschen die Datei, sobald wir sie geprüft haben. Gespeichert bleibt nur, bis wann dein Nachweis gilt.</p>
	</form>
	<p class="bwm-sub">Kein Nachweis zur Hand? <a href="<?php echo esc_url( $data['regular'] ); ?>">Ordentliche Mitgliedschaft wählen</a> – du kannst später mit Nachweis zur studentischen wechseln (ab der nächsten Verlängerung).</p>
</section>
