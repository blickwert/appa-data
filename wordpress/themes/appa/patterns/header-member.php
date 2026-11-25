<?php
/**
 * Title: Header Mitgliederbereich (Login)
 * Slug: appa/header-member
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

use BW\AppaTheme\Icons;
?>
<!-- wp:group {"tagName":"header","className":"bw-site-header","backgroundColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|5","bottom":"var:preset|spacing|5"}}},"layout":{"type":"constrained","contentSize":"69rem"}} -->
<header class="wp-block-group bw-site-header has-white-background-color has-background" style="padding-top:var(--wp--preset--spacing--5);padding-bottom:var(--wp--preset--spacing--5)">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
	<div class="wp-block-group">
		<!-- wp:html -->
		<a class="bw-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">APPA<span>.</span></a>
		<!-- /wp:html -->

		<!-- wp:html -->
		<a class="bw-back-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo Icons::svg( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static theme SVG. ?><?php esc_html_e( 'Zur Website', 'appa' ); ?></a>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
