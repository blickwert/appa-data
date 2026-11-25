<?php
/**
 * Join as a student: proof upload before the checkout.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by JoinProofController::render().
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<section class="bwm-section bwm-proof bwm-join-proof" id="nachweis" aria-labelledby="bwm-join-proof">
	<h2 id="bwm-join-proof" class="screen-reader-text"><?php esc_html_e( 'Datei auswählen und hochladen', 'bw-woo-membership' ); ?></h2>
	<?php if ( '' !== $data['error'] ) : ?>
		<p class="bwm-saved bwm-saved--error" role="alert"><?php echo esc_html( $data['error'] ); ?></p>
	<?php endif; ?>
	<?php if ( $data['has'] ) : ?>
		<p class="bwm-saved" role="status">
		<?php
		echo esc_html(
			'' !== $data['name']
				/* translators: %s: file name */
				? sprintf( __( 'Danke, dein Nachweis ist da: %s. Wir prüfen ihn nach deinem Beitritt.', 'bw-woo-membership' ), $data['name'] )
				: __( 'Danke, dein Nachweis ist da. Wir prüfen ihn nach deinem Beitritt.', 'bw-woo-membership' )
		);
		?>
		</p>
		<p><a class="bwm-button" href="<?php echo esc_url( $data['checkout'] ); ?>"><?php esc_html_e( 'Weiter zur Kasse', 'bw-woo-membership' ); ?></a></p>
		<p class="bwm-sub"><?php esc_html_e( 'Falsche Datei? Lade einfach eine neue hoch.', 'bw-woo-membership' ); ?></p>
	<?php endif; ?>
	<form class="bwm-form bwm-proof__form" method="post" enctype="multipart/form-data">
		<?php echo $data['nonce']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nonce_field() output. ?>
		<p><label for="bwm-join-proof-file"><?php esc_html_e( 'Inskriptionsbestätigung des aktuellen Semesters (PDF, JPG oder PNG, höchstens 5 MB)', 'bw-woo-membership' ); ?></label><input id="bwm-join-proof-file" type="file" name="bw_wm_proof" accept=".pdf,.jpg,.jpeg,.png" required></p>
		<p><button class="bwm-button<?php echo $data['has'] ? ' bwm-button--outline' : ''; ?>" type="submit"><?php echo $data['has'] ? esc_html__( 'Neue Datei hochladen', 'bw-woo-membership' ) : esc_html__( 'Hochladen und weiter zur Kasse', 'bw-woo-membership' ); ?></button></p>
		<p class="bwm-sub"><?php esc_html_e( 'Wir löschen die Datei, sobald wir sie geprüft haben. Gespeichert bleibt nur, bis wann dein Nachweis gilt.', 'bw-woo-membership' ); ?></p>
	</form>
	<p class="bwm-sub">
	<?php
	printf(
		/* translators: 1: opening link tag, 2: closing link tag */
		esc_html__( 'Kein Nachweis zur Hand? %1$sOrdentliche Mitgliedschaft wählen%2$s – du kannst später mit Nachweis zur studentischen wechseln (ab der nächsten Verlängerung).', 'bw-woo-membership' ),
		'<a href="' . esc_url( $data['regular'] ) . '">',
		'</a>'
	);
	?>
	</p>
</section>
