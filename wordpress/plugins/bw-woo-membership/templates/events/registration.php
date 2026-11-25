<?php
/**
 * Registration box on the event page.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by RegistrationService::box().
 */

defined( 'ABSPATH' ) || exit;

$bwm_exclusive = 'members' === $data['audience'];
?>
<div class="bwm-section bwm-register" id="anmeldung">
	<?php if ( $bwm_exclusive ) : ?>
		<p class="bwm-register__badge">Exklusive Veranstaltung für Mitglieder</p>
	<?php endif; ?>
	<h2 class="bwm-register__title">Anmeldung</h2>
	<?php if ( '' !== $data['notice'] ) : ?>
		<p class="bwm-saved" role="status"><?php echo esc_html( $data['notice'] ); ?></p>
	<?php endif; ?>
	<dl class="bwm-register__prices">
		<?php if ( ! $bwm_exclusive && '' !== $data['public_price'] && $data['public_price'] === $data['member_price'] ) : ?>
			<dt>Teilnahme</dt><dd><?php echo esc_html( $data['member_price'] ); ?></dd>
		<?php elseif ( '' !== $data['member_price'] ) : ?>
			<dt>Für Mitglieder</dt><dd><?php echo esc_html( $data['member_price'] ); ?></dd>
		<?php endif; ?>
		<?php if ( ! $bwm_exclusive && '' !== $data['public_price'] && $data['public_price'] !== $data['member_price'] ) : ?>
			<dt>Für Nichtmitglieder</dt><dd><?php echo esc_html( $data['public_price'] ); ?></dd>
		<?php endif; ?>
		<?php if ( null !== $data['places_left'] ) : ?>
			<dt>Freie Plätze</dt><dd><?php echo esc_html( (string) $data['places_left'] ); ?></dd>
		<?php endif; ?>
		<?php if ( '' !== $data['deadline'] ) : ?>
			<dt>Anmeldeschluss</dt><dd><?php echo esc_html( $data['deadline'] ); ?></dd>
		<?php endif; ?>
	</dl>
	<?php if ( $data['registered'] ) : ?>
		<p class="bwm-register__state"><strong>Du bist angemeldet</strong><?php echo '' !== $data['name'] ? ' als ' . esc_html( $data['name'] ) : ''; ?><?php echo $data['paid'] ? '.' : ' – die Zahlung ist noch offen.'; ?></p>
		<p class="bwm-register__links">
			<a class="bwm-button bwm-button--outline" href="<?php echo esc_url( $data['area_url'] ); ?>">Meine Anmeldungen</a>
		</p>
		<?php if ( '' !== $data['cancel'] ) : ?>
			<form method="post" action="<?php echo esc_url( $data['action'] ); ?>" class="bwm-register__cancel" onsubmit="return window.confirm( 'Möchtest du dich wirklich abmelden?' );">
				<input type="hidden" name="action" value="bw_wm_cancel_registration">
				<input type="hidden" name="event" value="<?php echo esc_attr( (string) $data['event'] ); ?>">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $data['cancel'] ); ?>">
				<p class="bwm-sub">Du kannst nicht teilnehmen? Abmelden bis <?php echo esc_html( $data['cancel_until'] ); ?><?php echo $data['refund'] ? ', du bekommst den vollen Betrag zurück' : ''; ?>.
					<button class="bwm-link-button" type="submit">Abmelden</button></p>
			</form>
		<?php else : ?>
			<p class="bwm-sub">Du kannst nicht teilnehmen? Bitte schreib uns an <a href="mailto:<?php echo esc_attr( $data['office'] ); ?>"><?php echo esc_html( $data['office'] ); ?></a>.</p>
		<?php endif; ?>
	<?php elseif ( '' !== $data['one_click'] ) : ?>
		<form method="post" action="<?php echo esc_url( $data['action'] ); ?>">
			<input type="hidden" name="action" value="bw_wm_register">
			<input type="hidden" name="event" value="<?php echo esc_attr( (string) $data['event'] ); ?>">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $data['one_click'] ); ?>">
			<p class="bwm-sub">Du meldest dich an als <strong><?php echo esc_html( $data['name'] ); ?></strong> (<?php echo esc_html( $data['email'] ); ?>). Die Bestätigung kommt per E-Mail, eine Erinnerung vor dem Termin.</p>
			<p><button class="bwm-button" type="submit">Jetzt anmelden</button></p>
		</form>
	<?php elseif ( '' !== $data['book_url'] ) : ?>
		<p><a class="bwm-button" href="<?php echo esc_url( $data['book_url'] ); ?>">Jetzt anmelden</a></p>
		<p class="bwm-sub">Die Bestätigung kommt per E-Mail, eine Erinnerung vor dem Termin.<?php if ( '' !== $data['paid_until'] && ( '' !== $data['member_price'] || '' !== $data['public_price'] ) ) : ?> Kostenlose Abmeldung mit voller Rückerstattung bis <?php echo esc_html( $data['paid_until'] ); ?>.<?php endif; ?></p>
		<?php if ( ! $data['is_member'] && ! $bwm_exclusive && '' !== $data['member_price'] ) : ?>
			<p class="bwm-sub">Mitglied? <?php if ( '' !== $data['login_url'] ) : ?><a href="<?php echo esc_url( $data['login_url'] ); ?>">Melde dich an</a>, um den Mitgliederpreis zu erhalten.<?php else : ?>Der Mitgliederpreis gilt mit aktiver Mitgliedschaft.<?php endif; ?></p>
		<?php endif; ?>
	<?php elseif ( $data['waitlist']['open'] ) : ?>
		<?php $w = $data['waitlist']; ?>
		<p>Die Veranstaltung ist ausgebucht.</p>
		<?php if ( $w['position'] > 0 ) : ?>
			<p class="bwm-saved" role="status">Du stehst auf der Warteliste (Platz <?php echo esc_html( (string) $w['position'] ); ?>). Wird ein Platz frei, schreiben wir dir eine E-Mail.</p>
		<?php elseif ( $w['can_join'] ) : ?>
			<p>Setz dich auf die Warteliste: Wird ein Platz frei, schreiben wir dir eine E-Mail.</p>
		<?php else : ?>
			<p><a href="<?php echo esc_url( $data['login_url'] ); ?>">Melde dich an</a>, um dich auf die Warteliste zu setzen.</p>
		<?php endif; ?>
		<?php if ( $w['position'] > 0 || $w['can_join'] ) : ?>
			<form method="post" action="<?php echo esc_url( $w['action'] ); ?>">
				<input type="hidden" name="action" value="bw_wm_waitlist">
				<input type="hidden" name="event" value="<?php echo esc_attr( (string) $w['event'] ); ?>">
				<input type="hidden" name="op" value="<?php echo $w['position'] > 0 ? 'leave' : 'join'; ?>">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $w['nonce'] ); ?>">
				<p><button class="bwm-button<?php echo $w['position'] > 0 ? ' bwm-button--outline' : ''; ?>" type="submit"><?php echo $w['position'] > 0 ? 'Von der Warteliste nehmen' : 'Auf die Warteliste'; ?></button></p>
			</form>
		<?php endif; ?>
	<?php elseif ( $bwm_exclusive && ! $data['is_member'] && $data['enabled'] ) : ?>
		<p><?php echo '' !== $data['login_url'] ? 'Diese Veranstaltung ist exklusiv für Mitglieder der APPA. Melde dich an, um dich zu registrieren – oder werde Mitglied.' : 'Diese Veranstaltung ist exklusiv für Mitglieder der APPA. Mit einer aktiven Mitgliedschaft kannst du dich hier anmelden.'; ?></p>
		<p class="bwm-register__links">
			<?php if ( '' !== $data['login_url'] ) : ?><a class="bwm-button" href="<?php echo esc_url( $data['login_url'] ); ?>">Jetzt anmelden</a><?php endif; ?>
			<a class="bwm-button<?php echo '' !== $data['login_url'] ? ' bwm-button--outline' : ''; ?>" href="<?php echo esc_url( $data['join_url'] ); ?>">Mitglied werden</a>
		</p>
	<?php else : ?>
		<p><?php echo esc_html( $data['refusal'] ); ?></p>
	<?php endif; ?>
</div>
