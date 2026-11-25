<?php
/**
 * Overview: welcome, status, membership cards, confirmation.
 *
 * @package BW\WooMembership
 *
 * @var array<string, mixed> $data Prepared by DashboardModel::overview().
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="bwm-overview">
	<div class="bwm-head">
		<div>
			<p class="bwm-eyebrow"><?php esc_html_e( 'Mitgliederbereich', 'bw-woo-membership' ); ?></p>
			<h1 class="bwm-title"><?php
			echo '' !== $data['first_name']
				/* translators: %s: first name */
				? esc_html( sprintf( __( 'Willkommen zurück, %s', 'bw-woo-membership' ), $data['first_name'] ) )
				: esc_html__( 'Willkommen zurück', 'bw-woo-membership' );
			?></h1>
			<p><?php esc_html_e( 'Hier findest du deine wichtigsten Informationen auf einen Blick.', 'bw-woo-membership' ); ?></p>
		</div>
		<p class="bwm-status bwm-status--<?php echo esc_attr( $data['status'] ); ?>"><?php echo esc_html( $data['badge'] ); ?></p>
	</div>

	<?php if ( '' !== $data['notice'] ) : ?>
		<div class="bwm-notice">
			<p><?php echo esc_html( $data['notice'] ); ?></p>
			<?php if ( ! $data['active'] ) : ?>
				<p><a class="bwm-button" href="<?php echo esc_url( $data['join_url'] ); ?>"><?php esc_html_e( 'Zur Mitgliedschaft', 'bw-woo-membership' ); ?></a></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'bw_wm_overview_before_cards', $data ); ?>

	<section class="bwm-section" aria-labelledby="bwm-membership">
		<div class="bwm-section__head">
			<h2 id="bwm-membership"><?php esc_html_e( 'Meine Mitgliedschaft', 'bw-woo-membership' ); ?></h2>
			<?php if ( '' !== $data['account_url'] ) : ?>
				<a class="bwm-link" href="<?php echo esc_url( $data['account_url'] ); ?>"><?php esc_html_e( 'Zahlungen und Rechnungen', 'bw-woo-membership' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="bwm-cards">
			<?php foreach ( $data['cards'] as $card ) : ?>
				<div class="bwm-card">
					<p class="bwm-label"><?php echo esc_html( $card['label'] ); ?></p>
					<p class="bwm-value"><?php if ( $card['dot'] ) :
?><span class="bwm-dot" aria-hidden="true"></span><?php endif; ?><?php echo esc_html( $card['value'] ); ?></p>
					<?php if ( '' !== $card['sub'] ) : ?>
						<p class="bwm-sub"><?php echo esc_html( $card['sub'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( '' !== $data['renew_url'] ) : ?>
			<p><a class="bwm-button" href="<?php echo esc_url( $data['renew_url'] ); ?>"><?php esc_html_e( 'Mitgliedschaft verlängern', 'bw-woo-membership' ); ?></a></p>
		<?php endif; ?>
		<?php if ( '' !== $data['confirmation'] ) : ?>
			<p><a class="bwm-button bwm-button--outline bwm-button--download" href="<?php echo esc_url( $data['confirmation'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Mitgliedsbestätigung', 'bw-woo-membership' ); ?> <small><?php esc_html_e( 'zum Drucken oder als PDF', 'bw-woo-membership' ); ?></small></a></p>
		<?php endif; ?>
	</section>
</div>
