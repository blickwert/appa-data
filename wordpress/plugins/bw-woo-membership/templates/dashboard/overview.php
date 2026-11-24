<?php
/**
 * Overview: welcome, status, membership cards, confirmation.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by DashboardModel::overview().
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="bwm-overview">
	<div class="bwm-head">
		<div>
			<p class="bwm-eyebrow">Mitgliederbereich</p>
			<h1 class="bwm-title"><?php echo '' !== $data['first_name'] ? esc_html( sprintf( 'Willkommen zurück, %s', $data['first_name'] ) ) : 'Willkommen zurück'; ?></h1>
			<p>Hier findest du deine wichtigsten Informationen auf einen Blick.</p>
		</div>
		<p class="bwm-status bwm-status--<?php echo esc_attr( $data['status'] ); ?>"><?php echo esc_html( $data['badge'] ); ?></p>
	</div>

	<?php if ( '' !== $data['notice'] ) : ?>
		<div class="bwm-notice">
			<p><?php echo esc_html( $data['notice'] ); ?></p>
			<?php if ( ! $data['active'] ) : ?>
				<p><a class="bwm-button" href="<?php echo esc_url( $data['join_url'] ); ?>">Zur Mitgliedschaft</a></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'bw_wm_overview_before_cards', $data ); ?>

	<section class="bwm-section" aria-labelledby="bwm-membership">
		<div class="bwm-section__head">
			<h2 id="bwm-membership">Meine Mitgliedschaft</h2>
			<?php if ( '' !== $data['account_url'] ) : ?>
				<a class="bwm-link" href="<?php echo esc_url( $data['account_url'] ); ?>">Zahlungen und Rechnungen</a>
			<?php endif; ?>
		</div>
		<div class="bwm-cards">
			<?php foreach ( $data['cards'] as $card ) : ?>
				<div class="bwm-card">
					<p class="bwm-label"><?php echo esc_html( $card['label'] ); ?></p>
					<p class="bwm-value"><?php if ( $card['dot'] ) : ?><span class="bwm-dot" aria-hidden="true"></span><?php endif; ?><?php echo esc_html( $card['value'] ); ?></p>
					<?php if ( '' !== $card['sub'] ) : ?>
						<p class="bwm-sub"><?php echo esc_html( $card['sub'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( '' !== $data['renew_url'] ) : ?>
			<p><a class="bwm-button" href="<?php echo esc_url( $data['renew_url'] ); ?>">Mitgliedschaft verlängern</a></p>
		<?php endif; ?>
		<?php if ( '' !== $data['confirmation'] ) : ?>
			<p><a class="bwm-button bwm-button--outline bwm-button--download" href="<?php echo esc_url( $data['confirmation'] ); ?>" target="_blank" rel="noopener">Mitgliedsbestätigung <small>zum Drucken oder als PDF</small></a></p>
		<?php endif; ?>
	</section>
</div>
