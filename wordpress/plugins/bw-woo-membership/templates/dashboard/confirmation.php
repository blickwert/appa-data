<?php
/**
 * Printable membership confirmation (stand-alone page).
 *
 * @package BW\WooMembership
 *
 * @var array<string, string> $data Prepared by DashboardModel::confirmation().
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mitgliedsbestätigung</title>
<link rel="stylesheet" href="<?php echo esc_url( plugins_url( 'assets/confirmation.css', BW_WM_FILE ) . '?ver=' . BW_WM_VERSION ); ?>">
</head>
<body class="bwm-print">
<main class="bwm-print__page">
	<p class="bwm-print__brand">APPA<span>.</span></p>
	<p class="bwm-print__org">Austrian Positive Psychology Association · Plüddemanngasse 104 · 8042 Graz</p>
	<h1>Mitgliedsbestätigung</h1>
	<p>Hiermit bestätigen wir, dass</p>
	<p class="bwm-print__name"><?php echo esc_html( $data['name'] ); ?></p>
	<p>Mitglied der Austrian Positive Psychology Association (APPA) ist.</p>
	<dl>
		<dt>Mitgliedschaftsart</dt><dd><?php echo esc_html( $data['type'] ); ?></dd>
		<?php if ( '' !== $data['number'] ) : ?><dt>Mitgliedsnummer</dt><dd><?php echo esc_html( $data['number'] ); ?></dd><?php endif; ?>
		<?php if ( '' !== $data['since'] ) : ?><dt>Mitglied seit</dt><dd><?php echo esc_html( $data['since'] ); ?></dd><?php endif; ?>
		<?php if ( '' !== $data['until'] ) : ?><dt>Gültig bis</dt><dd><?php echo esc_html( $data['until'] ); ?></dd><?php endif; ?>
	</dl>
	<p class="bwm-print__date">Graz, <?php echo esc_html( $data['today'] ); ?></p>
	<p class="bwm-print__note">Diese Bestätigung wurde elektronisch erstellt und ist ohne Unterschrift gültig.</p>
	<p class="bwm-print__actions"><button type="button" onclick="window.print()">Drucken oder als PDF speichern</button></p>
</main>
</body>
</html>
