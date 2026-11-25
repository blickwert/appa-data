<?php
/**
 * Registration box on the event page.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by RegistrationService::box().
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$bwm_exclusive = 'members' === $data['audience'];
?>
<div class="bwm-section bwm-register" id="anmeldung">
	<?php if ( $bwm_exclusive ) : ?>
		<p class="bwm-register__badge"><?php esc_html_e( 'Exklusive Veranstaltung für Mitglieder', 'bw-woo-membership' ); ?></p>
	<?php endif; ?>
	<h2 class="bwm-register__title"><?php esc_html_e( 'Anmeldung', 'bw-woo-membership' ); ?></h2>
	<?php if ( '' !== $data['notice'] ) : ?>
		<p class="bwm-saved" role="status"><?php echo esc_html( $data['notice'] ); ?></p>
	<?php endif; ?>
	<dl class="bwm-register__prices">
		<?php if ( ! $bwm_exclusive && '' !== $data['public_price'] && $data['public_price'] === $data['member_price'] ) : ?>
			<dt><?php esc_html_e( 'Teilnahme', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( $data['member_price'] ); ?></dd>
		<?php elseif ( '' !== $data['member_price'] ) : ?>
			<dt><?php esc_html_e( 'Für Mitglieder', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( $data['member_price'] ); ?></dd>
		<?php endif; ?>
		<?php if ( ! $bwm_exclusive && '' !== $data['public_price'] && $data['public_price'] !== $data['member_price'] ) : ?>
			<dt><?php esc_html_e( 'Für Nichtmitglieder', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( $data['public_price'] ); ?></dd>
		<?php endif; ?>
		<?php if ( null !== $data['places_left'] ) : ?>
			<dt><?php esc_html_e( 'Freie Plätze', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( (string) $data['places_left'] ); ?></dd>
		<?php endif; ?>
		<?php if ( '' !== $data['deadline'] ) : ?>
			<dt><?php esc_html_e( 'Anmeldeschluss', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( $data['deadline'] ); ?></dd>
		<?php endif; ?>
	</dl>
	<?php if ( $data['registered'] ) : ?>
		<p class="bwm-register__state"><strong><?php esc_html_e( 'Du bist angemeldet', 'bw-woo-membership' ); ?></strong><?php
		if ( '' !== $data['name'] ) {
			/* translators: %s: name */
			echo ' ' . esc_html( sprintf( __( 'als %s', 'bw-woo-membership' ), $data['name'] ) );
		}
		echo $data['paid'] ? '.' : ' ' . esc_html__( '– die Zahlung ist noch offen.', 'bw-woo-membership' );
		?></p>
		<p class="bwm-register__links">
			<a class="bwm-button bwm-button--outline" href="<?php echo esc_url( $data['area_url'] ); ?>"><?php esc_html_e( 'Meine Anmeldungen', 'bw-woo-membership' ); ?></a>
		</p>
		<?php if ( '' !== $data['cancel'] ) : ?>
			<form method="post" action="<?php echo esc_url( $data['action'] ); ?>" class="bwm-register__cancel" onsubmit="return window.confirm( '<?php echo esc_js( __( 'Möchtest du dich wirklich abmelden?', 'bw-woo-membership' ) ); ?>' );">
				<input type="hidden" name="action" value="bw_wm_cancel_registration">
				<input type="hidden" name="event" value="<?php echo esc_attr( (string) $data['event'] ); ?>">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $data['cancel'] ); ?>">
				<p class="bwm-sub">
				<?php
				echo esc_html(
					$data['refund']
						/* translators: %s: last day to cancel */
						? sprintf( __( 'Du kannst nicht teilnehmen? Abmelden bis %s, du bekommst den vollen Betrag zurück.', 'bw-woo-membership' ), $data['cancel_until'] )
						/* translators: %s: last day to cancel */
						: sprintf( __( 'Du kannst nicht teilnehmen? Abmelden bis %s.', 'bw-woo-membership' ), $data['cancel_until'] )
				);
				?>
					<button class="bwm-link-button" type="submit"><?php esc_html_e( 'Abmelden', 'bw-woo-membership' ); ?></button></p>
			</form>
		<?php else : ?>
			<p class="bwm-sub"><?php
			printf(
				/* translators: %s: e-mail link of the office */
				esc_html__( 'Du kannst nicht teilnehmen? Bitte schreib uns an %s.', 'bw-woo-membership' ),
				'<a href="mailto:' . esc_attr( $data['office'] ) . '">' . esc_html( $data['office'] ) . '</a>'
			);
			?></p>
		<?php endif; ?>
	<?php elseif ( '' !== $data['one_click'] ) : ?>
		<form method="post" action="<?php echo esc_url( $data['action'] ); ?>">
			<input type="hidden" name="action" value="bw_wm_register">
			<input type="hidden" name="event" value="<?php echo esc_attr( (string) $data['event'] ); ?>">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $data['one_click'] ); ?>">
			<p class="bwm-sub"><?php
			printf(
				/* translators: 1: name, 2: e-mail address */
				esc_html__( 'Du meldest dich an als %1$s (%2$s). Die Bestätigung kommt per E-Mail, eine Erinnerung vor dem Termin.', 'bw-woo-membership' ),
				'<strong>' . esc_html( $data['name'] ) . '</strong>',
				esc_html( $data['email'] )
			);
			?></p>
			<p><button class="bwm-button" type="submit"><?php esc_html_e( 'Jetzt anmelden', 'bw-woo-membership' ); ?></button></p>
		</form>
	<?php elseif ( '' !== $data['book_url'] ) : ?>
		<p><a class="bwm-button" href="<?php echo esc_url( $data['book_url'] ); ?>" rel="nofollow"><?php esc_html_e( 'Jetzt anmelden', 'bw-woo-membership' ); ?></a></p>
		<p class="bwm-sub"><?php esc_html_e( 'Die Bestätigung kommt per E-Mail, eine Erinnerung vor dem Termin.', 'bw-woo-membership' ); ?><?php if ( '' !== $data['paid_until'] && ( '' !== $data['member_price'] || '' !== $data['public_price'] ) ) :
?> <?php /* translators: %s: last day to cancel with a full refund */ echo esc_html( sprintf( __( 'Kostenlose Abmeldung mit voller Rückerstattung bis %s.', 'bw-woo-membership' ), $data['paid_until'] ) ); ?><?php endif; ?></p>
		<?php if ( ! $data['is_member'] && ! $bwm_exclusive && '' !== $data['member_price'] ) : ?>
			<p class="bwm-sub">
			<?php
			if ( '' !== $data['login_url'] ) {
				printf(
					/* translators: 1: opening link tag, 2: closing link tag */
					esc_html__( 'Mitglied? %1$sMelde dich an%2$s, um den Mitgliederpreis zu erhalten.', 'bw-woo-membership' ),
					'<a href="' . esc_url( $data['login_url'] ) . '">',
					'</a>'
				);
			} else {
				esc_html_e( 'Mitglied? Der Mitgliederpreis gilt mit aktiver Mitgliedschaft.', 'bw-woo-membership' );
			}
			?>
			</p>
		<?php endif; ?>
	<?php elseif ( $data['waitlist']['open'] ) : ?>
		<?php $w = $data['waitlist']; ?>
		<p><?php esc_html_e( 'Die Veranstaltung ist ausgebucht.', 'bw-woo-membership' ); ?></p>
		<?php if ( $w['position'] > 0 ) : ?>
			<p class="bwm-saved" role="status"><?php /* translators: %d: position on the waiting list */ echo esc_html( sprintf( __( 'Du stehst auf der Warteliste (Platz %d). Wird ein Platz frei, schreiben wir dir eine E-Mail.', 'bw-woo-membership' ), $w['position'] ) ); ?></p>
		<?php elseif ( $w['can_join'] ) : ?>
			<p><?php esc_html_e( 'Setz dich auf die Warteliste: Wird ein Platz frei, schreiben wir dir eine E-Mail.', 'bw-woo-membership' ); ?></p>
		<?php else : ?>
			<p>
			<?php
			printf(
				/* translators: 1: opening link tag, 2: closing link tag */
				esc_html__( '%1$sMelde dich an%2$s, um dich auf die Warteliste zu setzen.', 'bw-woo-membership' ),
				'<a href="' . esc_url( $data['login_url'] ) . '">',
				'</a>'
			);
			?>
			</p>
		<?php endif; ?>
		<?php if ( $w['position'] > 0 || $w['can_join'] ) : ?>
			<form method="post" action="<?php echo esc_url( $w['action'] ); ?>">
				<input type="hidden" name="action" value="bw_wm_waitlist">
				<input type="hidden" name="event" value="<?php echo esc_attr( (string) $w['event'] ); ?>">
				<input type="hidden" name="op" value="<?php echo $w['position'] > 0 ? 'leave' : 'join'; ?>">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $w['nonce'] ); ?>">
				<p><button class="bwm-button<?php echo $w['position'] > 0 ? ' bwm-button--outline' : ''; ?>" type="submit"><?php echo $w['position'] > 0 ? esc_html__( 'Von der Warteliste nehmen', 'bw-woo-membership' ) : esc_html__( 'Auf die Warteliste', 'bw-woo-membership' ); ?></button></p>
			</form>
		<?php endif; ?>
	<?php elseif ( $bwm_exclusive && ! $data['is_member'] && $data['enabled'] ) : ?>
		<p><?php echo '' !== $data['login_url'] ? esc_html__( 'Diese Veranstaltung ist exklusiv für Mitglieder der APPA. Melde dich an, um dich zu registrieren – oder werde Mitglied.', 'bw-woo-membership' ) : esc_html__( 'Diese Veranstaltung ist exklusiv für Mitglieder der APPA. Mit einer aktiven Mitgliedschaft kannst du dich hier anmelden.', 'bw-woo-membership' ); ?></p>
		<p class="bwm-register__links">
			<?php if ( '' !== $data['login_url'] ) :
?><a class="bwm-button" href="<?php echo esc_url( $data['login_url'] ); ?>"><?php esc_html_e( 'Jetzt anmelden', 'bw-woo-membership' ); ?></a><?php endif; ?>
			<a class="bwm-button<?php echo '' !== $data['login_url'] ? ' bwm-button--outline' : ''; ?>" href="<?php echo esc_url( $data['join_url'] ); ?>"><?php esc_html_e( 'Mitglied werden', 'bw-woo-membership' ); ?></a>
		</p>
	<?php else : ?>
		<p><?php echo esc_html( $data['refusal'] ); ?></p>
	<?php endif; ?>
</div>
