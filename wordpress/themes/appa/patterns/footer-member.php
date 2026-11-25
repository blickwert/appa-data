<?php
/**
 * Title: Footer Mitgliederbereich
 * Slug: appa/footer-member
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

$bw_links = array(
	'kontakt/'     => 'Kontakt',
	'datenschutz/' => 'Datenschutz',
	'impressum/'   => 'Impressum',
);
?>
<!-- wp:group {"tagName":"footer","className":"bw-member-footer","backgroundColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8"}}},"fontSize":"sm","layout":{"type":"constrained","contentSize":"69rem"}} -->
<footer class="wp-block-group bw-member-footer has-white-background-color has-background has-sm-font-size" style="padding-top:var(--wp--preset--spacing--8);padding-bottom:var(--wp--preset--spacing--8)">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph -->
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> APPA · Austrian Positive Psychology Association</p>
		<!-- /wp:paragraph -->

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
	<!-- /wp:group -->
</footer>
<!-- /wp:group -->
