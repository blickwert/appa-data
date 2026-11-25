<?php
/**
 * Backend page "Veranstaltungen › Teilnehmende".
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by ParticipantsController::render().
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bwm-participants">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Teilnehmende', 'bw-woo-membership' ); ?></h1>
	<?php if ( '' !== $data['notice'] ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $data['notice'] ); ?></p></div>
	<?php endif; ?>

	<form method="get" class="bwm-participants__picker">
		<input type="hidden" name="post_type" value="<?php echo esc_attr( $data['post_type'] ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( $data['page'] ); ?>">
		<label for="bwm-event"><?php esc_html_e( 'Veranstaltung', 'bw-woo-membership' ); ?></label>
		<select id="bwm-event" name="event">
			<option value=""><?php esc_html_e( '– auswählen –', 'bw-woo-membership' ); ?></option>
			<?php foreach ( $data['events'] as $bwm_event ) : ?>
				<option value="<?php echo esc_attr( (string) $bwm_event['id'] ); ?>" <?php selected( $data['event'], $bwm_event['id'] ); ?>><?php echo esc_html( $bwm_event['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button" type="submit"><?php esc_html_e( 'Anzeigen', 'bw-woo-membership' ); ?></button>
	</form>

	<?php if ( null !== $data['overview'] ) : ?>
		<?php $o = $data['overview']; ?>
		<h2><?php echo esc_html( $o['title'] ); ?></h2>
		<p><?php echo esc_html( $o['meta'] ); ?> · <a href="<?php echo esc_url( $data['edit_url'] ); ?>"><?php esc_html_e( 'Bearbeiten', 'bw-woo-membership' ); ?></a> · <a href="<?php echo esc_url( $data['view_url'] ); ?>"><?php esc_html_e( 'Ansehen', 'bw-woo-membership' ); ?></a></p>

		<ul class="bwm-participants__stats">
			<li><span><?php esc_html_e( 'Plätze (maximal)', 'bw-woo-membership' ); ?></span><strong><?php echo esc_html( '' === $o['capacity'] ? __( 'unbegrenzt', 'bw-woo-membership' ) : $o['capacity'] ); ?></strong></li>
			<li><span><?php esc_html_e( 'Angemeldet', 'bw-woo-membership' ); ?></span><strong><?php echo esc_html( (string) $o['count'] ); ?></strong></li>
			<li><span><?php esc_html_e( 'Frei', 'bw-woo-membership' ); ?></span><strong><?php echo esc_html( null === $o['left'] ? '–' : (string) $o['left'] ); ?></strong></li>
			<li><span><?php esc_html_e( 'Davon Zahlung offen', 'bw-woo-membership' ); ?></span><strong><?php echo esc_html( (string) $o['open'] ); ?></strong></li>
			<li><span><?php esc_html_e( 'Warteliste', 'bw-woo-membership' ); ?></span><strong><?php echo esc_html( (string) count( $o['waiting'] ) ); ?></strong></li>
		</ul>

		<table class="widefat striped">
			<thead><tr><th scope="col"><?php esc_html_e( 'Name', 'bw-woo-membership' ); ?></th><th scope="col"><?php esc_html_e( 'E-Mail', 'bw-woo-membership' ); ?></th><th scope="col"><?php esc_html_e( 'Mitglied', 'bw-woo-membership' ); ?></th><th scope="col"><?php esc_html_e( 'Betrag', 'bw-woo-membership' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'bw-woo-membership' ); ?></th><th scope="col"><?php esc_html_e( 'Angemeldet am', 'bw-woo-membership' ); ?></th><th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Bestellung', 'bw-woo-membership' ); ?></span></th></tr></thead>
			<tbody>
				<?php if ( ! $o['rows'] ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'Noch keine Anmeldungen.', 'bw-woo-membership' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $o['rows'] as $bwm_row ) : ?>
					<tr>
						<td><?php echo esc_html( $bwm_row['name'] ); ?></td>
						<td><a href="<?php echo esc_url( 'mailto:' . $bwm_row['email'] ); ?>"><?php echo esc_html( $bwm_row['email'] ); ?></a></td>
						<td><?php echo $bwm_row['member'] ? esc_html__( 'ja', 'bw-woo-membership' ) : esc_html__( 'nein', 'bw-woo-membership' ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $bwm_row['amount'] ) ); ?></td>
						<td><?php echo esc_html( $bwm_row['status'] ); ?></td>
						<td><?php echo esc_html( $bwm_row['date'] ); ?></td>
						<td><a href="<?php echo esc_url( $bwm_row['url'] ); ?>"><?php /* translators: %s: order number */ echo esc_html( sprintf( __( 'Bestellung %s', 'bw-woo-membership' ), $bwm_row['number'] ) ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $o['rows'] ) : ?>
			<p><a class="button" href="<?php echo esc_url( $data['csv_url'] ); ?>"><?php esc_html_e( 'Teilnehmerliste (CSV)', 'bw-woo-membership' ); ?></a></p>
		<?php endif; ?>

		<?php if ( $o['waiting'] ) : ?>
			<h3><?php esc_html_e( 'Warteliste', 'bw-woo-membership' ); ?></h3>
			<ol>
				<?php foreach ( $o['waiting'] as $bwm_wait ) : ?>
					<li><?php echo esc_html( $bwm_wait['name'] . ' (' . $bwm_wait['email'] . ')' ); ?><?php echo $bwm_wait['notified'] ? esc_html__( ' – über freien Platz informiert', 'bw-woo-membership' ) : ''; ?></li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<h3 class="bwm-participants__add"><?php esc_html_e( 'Person hinzufügen', 'bw-woo-membership' ); ?></h3>
		<?php if ( 0 === $o['left'] ) : ?>
			<p><strong><?php esc_html_e( 'Die Veranstaltung ist ausgebucht.', 'bw-woo-membership' ); ?></strong> <?php esc_html_e( 'Eine weitere Person kannst du trotzdem anmelden (Überbuchung).', 'bw-woo-membership' ); ?></p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( $data['add_action'] . '_' . $data['event'] ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( $data['add_action'] ); ?>">
			<input type="hidden" name="event" value="<?php echo esc_attr( (string) $data['event'] ); ?>">
			<table class="form-table" role="presentation"><tbody>
				<tr><th scope="row"><label for="bwm-email"><?php esc_html_e( 'E-Mail', 'bw-woo-membership' ); ?></label></th><td><input class="regular-text" type="email" id="bwm-email" name="email" required><p class="description"><?php esc_html_e( 'Hat die Person schon ein Konto, reicht die E-Mail-Adresse. Sonst legen wir ein Konto an; WooCommerce schickt ihr den Zugang.', 'bw-woo-membership' ); ?></p></td></tr>
				<tr><th scope="row"><label for="bwm-first"><?php esc_html_e( 'Vorname', 'bw-woo-membership' ); ?></label></th><td><input class="regular-text" type="text" id="bwm-first" name="first_name" autocomplete="given-name"></td></tr>
				<tr><th scope="row"><label for="bwm-last"><?php esc_html_e( 'Nachname', 'bw-woo-membership' ); ?></label></th><td><input class="regular-text" type="text" id="bwm-last" name="last_name" autocomplete="family-name"></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Betrag', 'bw-woo-membership' ); ?></th><td><fieldset><legend class="screen-reader-text"><?php esc_html_e( 'Betrag', 'bw-woo-membership' ); ?></legend>
					<label><input type="radio" name="mode" value="free" checked> <?php esc_html_e( 'kostenlos (z. B. Vortragende, Vorstand, Gäste)', 'bw-woo-membership' ); ?></label><br>
					<label><input type="radio" name="mode" value="pay"> <?php esc_html_e( 'mit Zahlung:', 'bw-woo-membership' ); ?></label> <input type="text" name="amount" value="<?php echo esc_attr( $o['price'] ); ?>" size="8" inputmode="decimal" aria-label="<?php esc_attr_e( 'Betrag in Euro', 'bw-woo-membership' ); ?>"> <?php esc_html_e( '€ – die Person bekommt einen Zahlungslink', 'bw-woo-membership' ); ?>
				</fieldset></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'E-Mail', 'bw-woo-membership' ); ?></th><td><label><input type="checkbox" name="notify" value="1" checked> <?php esc_html_e( 'Bestätigung bzw. Zahlungslink an die Person schicken', 'bw-woo-membership' ); ?></label></td></tr>
			</tbody></table>
			<?php submit_button( __( 'Person anmelden', 'bw-woo-membership' ), 'primary', 'submit', false ); ?>
		</form>
	<?php endif; ?>
</div>
