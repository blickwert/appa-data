<?php
/**
 * Title: Header
 * Slug: appa/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);
?>
<!-- wp:group {"tagName":"header","className":"bw-site-header","backgroundColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|4","bottom":"var:preset|spacing|4"}}},"layout":{"type":"constrained","contentSize":"69rem"}} -->
<header class="wp-block-group bw-site-header has-white-background-color has-background" style="padding-top:var(--wp--preset--spacing--4);padding-bottom:var(--wp--preset--spacing--4)">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
	<div class="wp-block-group">
		<!-- wp:html -->
		<a class="bw-site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php esc_attr_e( 'APPA – zur Startseite', 'appa' ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/appa-logo.webp' ) ); ?>" width="312" height="144" alt="APPA"></a>
		<!-- /wp:html -->

		<!-- wp:group {"className":"bw-site-header__end","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
		<div class="wp-block-group bw-site-header__end">
			<!-- wp:navigation {"ariaLabel":"Hauptmenü","overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right"},"style":{"spacing":{"blockGap":"var:preset|spacing|7"}}} /-->

			<!-- wp:bw/cart-link /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
