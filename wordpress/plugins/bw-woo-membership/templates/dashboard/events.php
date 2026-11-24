<?php
/**
 * Upcoming events in the member area.
 *
 * @package BW\WooMembership
 *
 * @var array{events: list<array<string, mixed>>, all_url: string} $data
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="bwm-section" id="veranstaltungen" aria-labelledby="bwm-events">
	<div class="bwm-section__head">
		<h2 id="bwm-events">Veranstaltungen für Mitglieder</h2>
		<?php if ( '' !== $data['all_url'] ) : ?>
			<a class="bwm-link" href="<?php echo esc_url( $data['all_url'] ); ?>">Alle Veranstaltungen</a>
		<?php endif; ?>
	</div>
	<?php if ( ! $data['events'] ) : ?>
		<p class="bwm-empty">Aktuell sind keine Termine geplant. Sobald es Neuigkeiten gibt, findest du sie hier.</p>
	<?php endif; ?>
	<?php foreach ( $data['events'] as $event ) : ?>
		<article class="bwm-event">
			<p class="bwm-date"><?php echo esc_html( $event['day'] ); ?><strong><?php echo esc_html( $event['month'] ); ?></strong><?php echo esc_html( $event['year'] ); ?></p>
			<div class="bwm-event__body">
				<p class="bwm-tags">
					<?php if ( $event['exclusive'] ) : ?><span class="bwm-pill bwm-pill--green">Exklusiv für Mitglieder</span><?php endif; ?>
					<span class="bwm-pill"><?php echo $event['online'] ? 'Online' : 'Vor Ort'; ?></span>
				</p>
				<h3><a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h3>
				<?php if ( '' !== $event['meta'] ) : ?>
					<p class="bwm-sub"><?php echo esc_html( $event['meta'] ); ?></p>
				<?php endif; ?>
			</div>
			<p class="bwm-event__action">
				<?php if ( '' !== $event['zoom'] ) : ?>
					<a class="bwm-button" href="<?php echo esc_url( $event['zoom'] ); ?>" target="_blank" rel="noopener">Per Zoom teilnehmen</a>
				<?php else : ?>
					<a class="bwm-button bwm-button--outline" href="<?php echo esc_url( $event['url'] ); ?>">Details</a>
				<?php endif; ?>
			</p>
		</article>
	<?php endforeach; ?>
</section>
