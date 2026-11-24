<?php
/**
 * Newest member documents.
 *
 * @package BW\WooMembership
 *
 * @var array{access: bool, items: list<array{title: string, meta: string, type: string, url: string}>} $data
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="bwm-section" id="dokumente" aria-labelledby="bwm-documents">
	<div class="bwm-section__head">
		<h2 id="bwm-documents">Dokumente &amp; Downloads</h2>
	</div>
	<?php if ( ! $data['access'] ) : ?>
		<p class="bwm-empty">Dokumente stehen dir mit aktiver Mitgliedschaft zur Verfügung.</p>
	<?php elseif ( ! $data['items'] ) : ?>
		<p class="bwm-empty">Noch keine Dokumente vorhanden.</p>
	<?php else : ?>
		<ul class="bwm-docs">
			<?php foreach ( $data['items'] as $item ) : ?>
				<li class="bwm-doc">
					<span class="bwm-doc__icon" aria-hidden="true"><?php echo esc_html( '' !== $item['type'] ? $item['type'] : 'doc' ); ?></span>
					<span class="bwm-doc__text"><strong><?php echo esc_html( $item['title'] ); ?></strong><span class="bwm-sub"><?php echo esc_html( $item['meta'] ); ?></span></span>
					<a class="bwm-doc__download" href="<?php echo esc_url( $item['url'] ); ?>"><span class="screen-reader-text"><?php echo esc_html( $item['title'] ); ?> </span>Herunterladen</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
