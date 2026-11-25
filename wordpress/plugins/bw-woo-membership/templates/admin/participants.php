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
	<h1 class="wp-heading-inline">Teilnehmende</h1>
	<?php if ( '' !== $data['notice'] ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $data['notice'] ); ?></p></div>
	<?php endif; ?>

	<form method="get" class="bwm-participants__picker">
		<input type="hidden" name="post_type" value="<?php echo esc_attr( $data['post_type'] ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( $data['page'] ); ?>">
		<label for="bwm-event">Veranstaltung</label>
		<select id="bwm-event" name="event">
			<option value="">– auswählen –</option>
			<?php foreach ( $data['events'] as $bwm_event ) : ?>
				<option value="<?php echo esc_attr( (string) $bwm_event['id'] ); ?>" <?php selected( $data['event'], $bwm_event['id'] ); ?>><?php echo esc_html( $bwm_event['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button" type="submit">Anzeigen</button>
	</form>

	<?php if ( null !== $data['overview'] ) : ?>
		<?php $o = $data['overview']; ?>
		<h2><?php echo esc_html( $o['title'] ); ?></h2>
		<p><?php echo esc_html( $o['meta'] ); ?> · <a href="<?php echo esc_url( $data['edit_url'] ); ?>">Bearbeiten</a> · <a href="<?php echo esc_url( $data['view_url'] ); ?>">Ansehen</a></p>

		<ul class="bwm-participants__stats">
			<li><span>Plätze (maximal)</span><strong><?php echo esc_html( '' === $o['capacity'] ? 'unbegrenzt' : $o['capacity'] ); ?></strong></li>
			<li><span>Angemeldet</span><strong><?php echo esc_html( (string) $o['count'] ); ?></strong></li>
			<li><span>Frei</span><strong><?php echo esc_html( null === $o['left'] ? '–' : (string) $o['left'] ); ?></strong></li>
			<li><span>Davon Zahlung offen</span><strong><?php echo esc_html( (string) $o['open'] ); ?></strong></li>
			<li><span>Warteliste</span><strong><?php echo esc_html( (string) count( $o['waiting'] ) ); ?></strong></li>
		</ul>

		<table class="widefat striped">
			<thead><tr><th scope="col">Name</th><th scope="col">E-Mail</th><th scope="col">Mitglied</th><th scope="col">Betrag</th><th scope="col">Status</th><th scope="col">Angemeldet am</th><th scope="col"><span class="screen-reader-text">Bestellung</span></th></tr></thead>
			<tbody>
				<?php if ( ! $o['rows'] ) : ?>
					<tr><td colspan="7">Noch keine Anmeldungen.</td></tr>
				<?php endif; ?>
				<?php foreach ( $o['rows'] as $bwm_row ) : ?>
					<tr>
						<td><?php echo esc_html( $bwm_row['name'] ); ?></td>
						<td><a href="<?php echo esc_url( 'mailto:' . $bwm_row['email'] ); ?>"><?php echo esc_html( $bwm_row['email'] ); ?></a></td>
						<td><?php echo $bwm_row['member'] ? 'ja' : 'nein'; ?></td>
						<td><?php echo wp_kses_post( wc_price( $bwm_row['amount'] ) ); ?></td>
						<td><?php echo esc_html( $bwm_row['status'] ); ?></td>
						<td><?php echo esc_html( $bwm_row['date'] ); ?></td>
						<td><a href="<?php echo esc_url( $bwm_row['url'] ); ?>">Bestellung <?php echo esc_html( $bwm_row['number'] ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $o['rows'] ) : ?>
			<p><a class="button" href="<?php echo esc_url( $data['csv_url'] ); ?>">Teilnehmerliste (CSV)</a></p>
		<?php endif; ?>

		<?php if ( $o['waiting'] ) : ?>
			<h3>Warteliste</h3>
			<ol>
				<?php foreach ( $o['waiting'] as $bwm_wait ) : ?>
					<li><?php echo esc_html( $bwm_wait['name'] . ' (' . $bwm_wait['email'] . ')' ); ?><?php echo $bwm_wait['notified'] ? ' – über freien Platz informiert' : ''; ?></li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<h3 class="bwm-participants__add">Person hinzufügen</h3>
		<?php if ( 0 === $o['left'] ) : ?>
			<p><strong>Die Veranstaltung ist ausgebucht.</strong> Eine weitere Person kannst du trotzdem anmelden (Überbuchung).</p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( $data['add_action'] . '_' . $data['event'] ); ?>
			<input type="hidden" name="action" value="<?php echo esc_attr( $data['add_action'] ); ?>">
			<input type="hidden" name="event" value="<?php echo esc_attr( (string) $data['event'] ); ?>">
			<table class="form-table" role="presentation"><tbody>
				<tr><th scope="row"><label for="bwm-email">E-Mail</label></th><td><input class="regular-text" type="email" id="bwm-email" name="email" required><p class="description">Hat die Person schon ein Konto, reicht die E-Mail-Adresse. Sonst legen wir ein Konto an; WooCommerce schickt ihr den Zugang.</p></td></tr>
				<tr><th scope="row"><label for="bwm-first">Vorname</label></th><td><input class="regular-text" type="text" id="bwm-first" name="first_name" autocomplete="given-name"></td></tr>
				<tr><th scope="row"><label for="bwm-last">Nachname</label></th><td><input class="regular-text" type="text" id="bwm-last" name="last_name" autocomplete="family-name"></td></tr>
				<tr><th scope="row">Betrag</th><td><fieldset><legend class="screen-reader-text">Betrag</legend>
					<label><input type="radio" name="mode" value="free" checked> kostenlos (z. B. Vortragende, Vorstand, Gäste)</label><br>
					<label><input type="radio" name="mode" value="pay"> mit Zahlung:</label> <input type="text" name="amount" value="<?php echo esc_attr( $o['price'] ); ?>" size="8" inputmode="decimal" aria-label="Betrag in Euro"> € – die Person bekommt einen Zahlungslink
				</fieldset></td></tr>
				<tr><th scope="row">E-Mail</th><td><label><input type="checkbox" name="notify" value="1" checked> Bestätigung bzw. Zahlungslink an die Person schicken</label></td></tr>
			</tbody></table>
			<?php submit_button( 'Person anmelden', 'primary', 'submit', false ); ?>
		</form>
	<?php endif; ?>
</div>
