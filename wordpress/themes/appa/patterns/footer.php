<?php
/**
 * Title: Footer
 * Slug: appa/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

$bw_links = array(
	'ueber-uns/'             => 'Über uns',
	'positive-psychologie/'  => 'Positive Psychologie',
	'ueber-uns/#fachgruppen' => 'Fachgruppen',
	'veranstaltungen/'       => 'Veranstaltungen',
	'mitgliedschaft/'        => 'Mitgliedschaft',
	'kontakt/'               => 'Kontakt',
	'ueber-uns/#statuten'    => 'Statuten',
	'impressum/'             => 'Impressum',
	'datenschutz/'           => 'Datenschutz',
);
?>
<!-- wp:group {"tagName":"footer","className":"bw-site-footer is-style-section-navy","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"},"blockGap":"var:preset|spacing|5"}},"fontSize":"sm","layout":{"type":"constrained","contentSize":"69rem"}} -->
<footer class="wp-block-group bw-site-footer is-style-section-navy has-sm-font-size" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|2"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"bw-site-footer__brand","fontSize":"lg"} -->
		<p class="bw-site-footer__brand has-lg-font-size">APPA.</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p>APPA · Austrian Positive Psychology Association<br>Positive Psychologie in Österreich gemeinsam weiterentwickeln.<br>Plüddemanngasse 104 · 8042 Graz · <a class="bw-site-footer__mail" href="mailto:info@appa.net">info@appa.net</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:html -->
	<nav aria-label="<?php esc_attr_e( 'Footer', 'appa' ); ?>">
		<ul class="bw-footer-links">
			<?php foreach ( $bw_links as $bw_path => $bw_label ) : ?>
				<li><a href="<?php echo esc_url( home_url( '/' . $bw_path ) ); ?>"><?php echo esc_html( $bw_label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<!-- /wp:html -->

	<!-- wp:paragraph -->
	<p>© APPA Austrian Positive Psychology Association</p>
	<!-- /wp:paragraph -->
</footer>
<!-- /wp:group -->
