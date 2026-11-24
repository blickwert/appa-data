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
<!-- wp:group {"tagName":"footer","className":"bw-site-footer is-style-section-navy","style":{"spacing":{"padding":{"top":"var:preset|spacing|12","bottom":"var:preset|spacing|10"},"blockGap":"var:preset|spacing|8"}},"fontSize":"sm","layout":{"type":"constrained","contentSize":"72rem"}} -->
<footer class="wp-block-group bw-site-footer is-style-section-navy has-sm-font-size" style="padding-top:var(--wp--preset--spacing--12);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|10"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"40%"} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}},"fontSize":"xl"} -->
			<p class="has-xl-font-size" style="font-weight:700">APPA</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>Austrian Positive Psychology Association<br>Positive Psychologie in Österreich gemeinsam weiterentwickeln.</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p>Plüddemanngasse 104 · 8042 Graz<br><a href="mailto:info@appa.net">info@appa.net</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:html -->
			<nav aria-label="<?php esc_attr_e( 'Footer', 'appa' ); ?>">
				<ul class="bw-footer-links">
					<?php foreach ( $bw_links as $bw_path => $bw_label ) : ?>
						<li><a href="<?php echo esc_url( home_url( '/' . $bw_path ) ); ?>"><?php echo esc_html( $bw_label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:paragraph {"fontSize":"xs"} -->
	<p class="has-xs-font-size">© APPA Austrian Positive Psychology Association</p>
	<!-- /wp:paragraph -->
</footer>
<!-- /wp:group -->
