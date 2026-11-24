<?php
/**
 * Title: Mitgliederbereich: Navigation
 * Slug: appa/member-sidebar
 * Categories: appa
 * Inserter: no
 *
 * Sidebar on large screens, bar at the bottom on small screens.
 *
 * @package BW\AppaTheme
 */

declare(strict_types=1);

use BW\AppaTheme\Icons;

$bw_items = array(
	array( 'layout-grid', 'Übersicht', '#top', true ),
	array( 'calendar', 'Veranstaltungen', '#veranstaltungen', false ),
	array( 'folder', 'Dokumente', '#dokumente', false ),
	array( 'user', 'Profil', '#profil', false ),
);
?>
<!-- wp:html -->
<nav class="bw-member-nav" aria-label="<?php esc_attr_e( 'Mitgliederbereich', 'appa' ); ?>">
	<ul>
		<?php foreach ( $bw_items as list( $bw_icon, $bw_label, $bw_href, $bw_current ) ) : ?>
			<li><a href="<?php echo esc_url( $bw_href ); ?>"<?php echo $bw_current ? ' aria-current="page"' : ''; ?>><?php echo Icons::svg( $bw_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static theme SVG. ?><span><?php echo esc_html( $bw_label ); ?></span></a></li>
		<?php endforeach; ?>
	</ul>
</nav>
<!-- /wp:html -->

<!-- wp:html -->
<div class="bw-member-help">
	<span class="bw-member-help__icon"><?php echo Icons::svg( 'help-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static theme SVG. ?></span>
	<p><strong><?php esc_html_e( 'Brauchen Sie Hilfe?', 'appa' ); ?></strong><br><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt aufnehmen', 'appa' ); ?></a></p>
</div>
<a class="bw-member-exit" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo Icons::svg( 'log-out' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static theme SVG. ?><span><?php esc_html_e( 'Zur öffentlichen Website', 'appa' ); ?></span></a>
<!-- /wp:html -->
