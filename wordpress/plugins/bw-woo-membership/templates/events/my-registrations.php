<?php
/**
 * "Meine Anmeldungen" in the member area.
 *
 * @package BW\WooMembership
 *
 * @var array{items: list<array<string, mixed>>} $data
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="bwm-myreg">
	<h3>Meine Anmeldungen</h3>
	<ul class="bwm-docs">
		<?php foreach ( $data['items'] as $item ) : ?>
			<li class="bwm-doc bwm-myreg__item<?php echo $item['past'] ? ' is-past' : ''; ?>">
				<span class="bwm-doc__text">
					<a href="<?php echo esc_url( $item['url'] ); ?>"><strong><?php echo esc_html( $item['title'] ); ?></strong></a>
					<span class="bwm-sub"><?php echo esc_html( $item['when'] ); ?> · <?php echo esc_html( $item['status'] ); ?></span>
					<?php if ( '' !== $item['access'] && ! $item['past'] ) : ?>
						<span class="bwm-sub">Zugangsdaten: <?php echo esc_html( $item['access'] ); ?></span>
					<?php endif; ?>
				</span>
				<?php if ( '' !== $item['zoom'] && ! $item['past'] ) : ?>
					<a class="bwm-button" href="<?php echo esc_url( $item['zoom'] ); ?>" target="_blank" rel="noopener">Per Zoom teilnehmen</a>
				<?php elseif ( '' !== $item['pay_url'] ) : ?>
					<a class="bwm-button" href="<?php echo esc_url( $item['pay_url'] ); ?>">Jetzt bezahlen</a>
				<?php else : ?>
					<a class="bwm-doc__download" href="<?php echo esc_url( $item['invoice'] ); ?>">Bestellung</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
