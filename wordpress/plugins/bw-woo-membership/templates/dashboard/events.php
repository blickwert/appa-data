<?php
/**
 * Upcoming events in the member area.
 *
 * @package BW\WooMembership
 *
 * @var array{events: list<array<string, mixed>>, all_url: string} $data
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<section class="bwm-section" id="veranstaltungen" aria-labelledby="bwm-events">
	<div class="bwm-section__head">
		<h2 id="bwm-events"><?php esc_html_e( 'Veranstaltungen für Mitglieder', 'bw-woo-membership' ); ?></h2>
		<?php if ( '' !== $data['all_url'] ) : ?>
			<a class="bwm-link" href="<?php echo esc_url( $data['all_url'] ); ?>"><?php esc_html_e( 'Alle Veranstaltungen', 'bw-woo-membership' ); ?></a>
		<?php endif; ?>
	</div>
	<?php
	/**
	 * Fires before the list of upcoming events (the events module adds "Meine Anmeldungen").
	 */
	do_action( 'bw_wm_events_before_list' );
	?>
	<?php if ( ! $data['events'] ) : ?>
		<p class="bwm-empty"><?php esc_html_e( 'Aktuell sind keine Termine geplant. Sobald es Neuigkeiten gibt, findest du sie hier.', 'bw-woo-membership' ); ?></p>
	<?php endif; ?>
	<?php foreach ( $data['events'] as $event ) : ?>
		<article class="bwm-event">
			<p class="bwm-date"><?php echo esc_html( $event['day'] ); ?><strong><?php echo esc_html( $event['month'] ); ?></strong><?php echo esc_html( $event['year'] ); ?></p>
			<div class="bwm-event__body">
				<p class="bwm-tags">
					<?php if ( $event['exclusive'] ) :
?><span class="bwm-pill bwm-pill--green"><?php esc_html_e( 'Exklusiv für Mitglieder', 'bw-woo-membership' ); ?></span><?php endif; ?>
					<span class="bwm-pill"><?php echo $event['online'] ? esc_html__( 'Online', 'bw-woo-membership' ) : esc_html__( 'Vor Ort', 'bw-woo-membership' ); ?></span>
				</p>
				<h3><a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h3>
				<?php if ( '' !== $event['meta'] ) : ?>
					<p class="bwm-sub"><?php echo esc_html( $event['meta'] ); ?></p>
				<?php endif; ?>
			</div>
			<p class="bwm-event__action">
				<?php if ( '' !== $event['zoom'] ) : ?>
					<a class="bwm-button" href="<?php echo esc_url( $event['zoom'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Per Zoom teilnehmen', 'bw-woo-membership' ); ?></a>
				<?php elseif ( $event['registration'] ) : ?>
					<a class="bwm-button" href="<?php echo esc_url( $event['url'] ); ?>"><?php esc_html_e( 'Zur Anmeldung', 'bw-woo-membership' ); ?></a>
				<?php else : ?>
					<a class="bwm-button bwm-button--outline" href="<?php echo esc_url( $event['url'] ); ?>"><?php esc_html_e( 'Details', 'bw-woo-membership' ); ?></a>
				<?php endif; ?>
			</p>
		</article>
	<?php endforeach; ?>
</section>
