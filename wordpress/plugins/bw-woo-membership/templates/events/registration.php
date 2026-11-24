<?php
/**
 * Registration box on the event page.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by RegistrationService::box().
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="bwm-section bwm-register" id="anmeldung">
	<h2 class="bwm-register__title">Anmeldung</h2>
	<dl class="bwm-register__prices">
		<?php if ( '' !== $data['member_price'] ) : ?>
			<dt>Für Mitglieder</dt><dd><?php echo esc_html( $data['member_price'] ); ?></dd>
		<?php endif; ?>
		<?php if ( 'members' === $data['audience'] ) : ?>
			<dt>Für Nichtmitglieder</dt><dd>nur für Mitglieder</dd>
		<?php elseif ( '' !== $data['public_price'] ) : ?>
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
		<p class="bwm-saved" role="status"><?php echo $data['paid'] ? 'Du bist angemeldet.' : 'Du bist angemeldet – die Zahlung ist noch offen.'; ?></p>
		<p><a class="bwm-button bwm-button--outline" href="<?php echo esc_url( $data['area_url'] ); ?>">Zu meinen Anmeldungen</a></p>
	<?php elseif ( '' !== $data['book_url'] ) : ?>
		<p><a class="bwm-button" href="<?php echo esc_url( $data['book_url'] ); ?>">Jetzt anmelden</a></p>
		<?php if ( ! $data['is_member'] && 'members' !== $data['audience'] && '' !== $data['member_price'] ) : ?>
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
	<?php else : ?>
		<p><?php echo esc_html( $data['refusal'] ); ?></p>
		<?php if ( 'members' === $data['audience'] && ! $data['is_member'] ) : ?>
			<p class="bwm-register__links">
				<?php if ( '' !== $data['login_url'] ) : ?><a class="bwm-button bwm-button--outline" href="<?php echo esc_url( $data['login_url'] ); ?>">Anmelden</a><?php endif; ?>
				<a class="bwm-button" href="<?php echo esc_url( $data['join_url'] ); ?>">Mitglied werden</a>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</div>
