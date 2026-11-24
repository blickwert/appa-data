<?php
/**
 * Title: Header Mitgliederbereich (Übersicht)
 * Slug: appa/header-dashboard
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * Static preview: name and membership type come from the member plugin later.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

use BW\AppaTheme\Icons;
?>
<!-- wp:group {"tagName":"header","className":"bw-site-header bw-dashboard-header","backgroundColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|4","bottom":"var:preset|spacing|4"}}},"layout":{"type":"constrained","contentSize":"77rem"}} -->
<header class="wp-block-group bw-site-header bw-dashboard-header has-white-background-color has-background" style="padding-top:var(--wp--preset--spacing--4);padding-bottom:var(--wp--preset--spacing--4)">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
	<div class="wp-block-group">
		<!-- wp:html -->
		<div class="bw-dashboard-brand"><a class="bw-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">APPA<span>.</span></a><span class="bw-dashboard-brand__area"><?php esc_html_e( 'Mitgliederbereich', 'appa' ); ?></span></div>
		<!-- /wp:html -->

		<!-- wp:html -->
		<div class="bw-dashboard-user">
			<span class="bw-dashboard-user__bell" title="<?php esc_attr_e( 'Benachrichtigungen', 'appa' ); ?>"><?php echo Icons::svg( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static theme SVG. ?></span>
			<span class="bw-dashboard-user__avatar"><?php echo Icons::svg( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static theme SVG. ?></span>
			<span class="bw-dashboard-user__role"><?php esc_html_e( 'Ordentliches Mitglied', 'appa' ); ?></span>
		</div>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
