<?php
/**
 * "Meine Anmeldungen" in the member area.
 *
 * @package BW\WooMembership
 *
 * @var array{items: list<array<string, mixed>>, back: string} $data
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="bwm-myreg">
	<h3><?php esc_html_e( 'Meine Anmeldungen', 'bw-woo-membership' ); ?></h3>
	<ul class="bwm-docs">
		<?php foreach ( $data['items'] as $item ) : ?>
			<li class="bwm-doc bwm-myreg__item<?php echo $item['past'] ? ' is-past' : ''; ?>">
				<span class="bwm-doc__text">
					<a href="<?php echo esc_url( $item['url'] ); ?>"><strong><?php echo esc_html( $item['title'] ); ?></strong></a>
					<span class="bwm-sub"><?php echo esc_html( $item['when'] ); ?> · <?php echo esc_html( $item['status'] ); ?></span>
					<?php if ( '' !== $item['access'] && ! $item['past'] ) : ?>
						<span class="bwm-sub"><?php esc_html_e( 'Zugangsdaten:', 'bw-woo-membership' ); ?> <?php echo esc_html( $item['access'] ); ?></span>
					<?php endif; ?>
				</span>
				<?php if ( '' !== $item['cancel'] ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return window.confirm( '<?php echo esc_js( __( 'Möchtest du dich wirklich abmelden?', 'bw-woo-membership' ) ); ?>' );">
						<input type="hidden" name="action" value="bw_wm_cancel_registration">
						<input type="hidden" name="event" value="<?php echo esc_attr( (string) $item['event'] ); ?>">
						<input type="hidden" name="back" value="<?php echo esc_url( $data['back'] ); ?>">
						<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $item['cancel'] ); ?>">
						<button class="bwm-link-button" type="submit"><?php esc_html_e( 'Abmelden', 'bw-woo-membership' ); ?></button>
					</form>
				<?php endif; ?>
				<?php if ( '' !== $item['zoom'] && ! $item['past'] ) : ?>
					<a class="bwm-button" href="<?php echo esc_url( $item['zoom'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Per Zoom teilnehmen', 'bw-woo-membership' ); ?></a>
				<?php elseif ( '' !== $item['pay_url'] ) : ?>
					<a class="bwm-button" href="<?php echo esc_url( $item['pay_url'] ); ?>"><?php esc_html_e( 'Jetzt bezahlen', 'bw-woo-membership' ); ?></a>
				<?php else : ?>
					<a class="bwm-doc__download" href="<?php echo esc_url( $item['invoice'] ); ?>"><?php esc_html_e( 'Bestellung', 'bw-woo-membership' ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
