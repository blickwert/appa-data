<?php
/**
 * Printable membership confirmation (stand-alone page).
 *
 * @package BW\WooMembership
 *
 * @var array<string, string> $data Prepared by DashboardModel::confirmation().
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php esc_html_e( 'Mitgliedsbestätigung', 'bw-woo-membership' ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( plugins_url( 'assets/confirmation.css', BW_WM_FILE ) . '?ver=' . BW_WM_VERSION ); ?>"><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- stand-alone print page without wp_head(). ?>
</head>
<body class="bwm-print">
<main class="bwm-print__page">
	<p class="bwm-print__brand">APPA<span>.</span></p>
	<p class="bwm-print__org">Austrian Positive Psychology Association · Plüddemanngasse 104 · 8042 Graz</p>
	<h1><?php esc_html_e( 'Mitgliedsbestätigung', 'bw-woo-membership' ); ?></h1>
	<p><?php esc_html_e( 'Hiermit bestätigen wir, dass', 'bw-woo-membership' ); ?></p>
	<p class="bwm-print__name"><?php echo esc_html( $data['name'] ); ?></p>
	<p><?php esc_html_e( 'Mitglied der Austrian Positive Psychology Association (APPA) ist.', 'bw-woo-membership' ); ?></p>
	<dl>
		<dt><?php esc_html_e( 'Mitgliedschaftsart', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( $data['type'] ); ?></dd>
		<?php if ( '' !== $data['number'] ) :
?><dt><?php esc_html_e( 'Mitgliedsnummer', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( $data['number'] ); ?></dd><?php endif; ?>
		<?php if ( '' !== $data['since'] ) :
?><dt><?php esc_html_e( 'Mitglied seit', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( $data['since'] ); ?></dd><?php endif; ?>
		<?php if ( '' !== $data['until'] ) :
?><dt><?php esc_html_e( 'Gültig bis', 'bw-woo-membership' ); ?></dt><dd><?php echo esc_html( $data['until'] ); ?></dd><?php endif; ?>
	</dl>
	<p class="bwm-print__date">Graz, <?php echo esc_html( $data['today'] ); ?></p>
	<p class="bwm-print__note"><?php esc_html_e( 'Diese Bestätigung wurde elektronisch erstellt und ist ohne Unterschrift gültig.', 'bw-woo-membership' ); ?></p>
	<p class="bwm-print__actions"><button type="button" onclick="window.print()"><?php esc_html_e( 'Drucken oder als PDF speichern', 'bw-woo-membership' ); ?></button></p>
</main>
</body>
</html>
