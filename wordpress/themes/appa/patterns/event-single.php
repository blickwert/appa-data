<?php
/**
 * Title: Veranstaltung: Detailansicht
 * Slug: appa/event-single
 * Categories: appa
 * Inserter: no
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);
?>
<!-- wp:group {"tagName":"section","align":"full","className":"is-style-section-sky","style":{"spacing":{"padding":{"top":"var:preset|spacing|16","bottom":"var:preset|spacing|16"},"blockGap":"var:preset|spacing|3"}},"layout":{"type":"constrained","contentSize":"47.5rem"}} -->
<section class="wp-block-group alignfull is-style-section-sky" style="padding-top:var(--wp--preset--spacing--16);padding-bottom:var(--wp--preset--spacing--16)">
	<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"bw-appa/event","args":{"key":"when"}}}},"className":"is-style-eyebrow"} -->
	<p class="is-style-eyebrow"></p>
	<!-- /wp:paragraph -->

	<!-- wp:post-title {"level":1} /-->

	<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"bw-appa/event","args":{"key":"access"}}}}} -->
	<p></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button {"metadata":{"bindings":{"url":{"source":"bw-appa/event","args":{"key":"zoom_link"}}}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Per Zoom teilnehmen</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

	<!-- wp:bw-woo-membership/event-registration /-->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|section","bottom":"var:preset|spacing|section"}}},"layout":{"type":"constrained","contentSize":"47.5rem"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--section);padding-bottom:var(--wp--preset--spacing--section)">
	<!-- wp:post-content {"layout":{"type":"constrained"}} /-->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"is-style-arrow"} -->
		<div class="wp-block-button is-style-arrow"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_post_type_archive_link( 'appa_event' ) ?: home_url( '/veranstaltungen/' ) ); ?>">Alle Veranstaltungen</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
