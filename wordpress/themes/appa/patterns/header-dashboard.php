<?php
/**
 * Title: Header Mitgliederbereich (Übersicht)
 * Slug: appa/header-dashboard
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * Name and membership type come from the block of the plugin "APPA Mitgliedschaft" (bw-woo-membership).
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

?>
<!-- wp:group {"tagName":"header","className":"bw-site-header bw-dashboard-header","backgroundColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|4","bottom":"var:preset|spacing|4"}}},"layout":{"type":"constrained","contentSize":"77rem"}} -->
<header class="wp-block-group bw-site-header bw-dashboard-header has-white-background-color has-background" style="padding-top:var(--wp--preset--spacing--4);padding-bottom:var(--wp--preset--spacing--4)">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
	<div class="wp-block-group">
		<!-- wp:html -->
		<div class="bw-dashboard-brand"><a class="bw-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">APPA<span>.</span></a><span class="bw-dashboard-brand__area"><?php esc_html_e( 'Mitgliederbereich', 'appa' ); ?></span></div>
		<!-- /wp:html -->

		<!-- wp:bw-woo-membership/user /-->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
