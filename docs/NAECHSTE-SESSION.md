# Übergabe für die nächste Session

Stand: 24. November 2026. Zuerst `CLAUDE.md` und `docs/WP-UMSETZUNG.md` lesen, dann hier weitermachen. Sprache mit David: Deutsch.

## Was fertig ist (PR „Block-Theme appa + Plugin bw-appa statt Elementor“)

- Theme `wordpress/themes/appa/` und Plugin `wordpress/plugins/bw-appa/` sind gebaut und lokal geprüft (WordPress 7.1 + SQLite): alle Seiten bei 1440/390 px, 0 ungültige Blöcke im Editor, Veranstaltungsfilter, Spamschutz des Kontaktformulars, 13 PHPUnit-Tests grün.
- Inhalte = Stand der Elementor-Seite `2603-appa` (Texte nach Briefing vom 23. 9. und Seite „Positive Psychologie“).
- Getroffene Entscheidungen (nicht neu verhandeln): neue Dev-Installation `2603b-appa`; Formulare mit Contact Form 7 + Turnstile; Veranstaltungen als Beitragstyp im Plugin per Code (kein ACF); Kontakt/Impressum/Datenschutz nur im Footer-Menü.

## Nächste Aufgabe: Übertragung auf `2603b-appa`

Voraussetzung (von David): Installation `https://dev.blickwert.at/wp/2603b-appa` mit BW WP Bridge und Zugangsdaten als Umgebungsvariablen. Erwartete Namen: `WP_APPA2_URL`, `WP_APPA2_USER`, `WP_APPA2_APP_PASSWORD` (ggf. `WP_APPA2_BRIDGE_KEY`). Prüfen mit `env | grep '^WP_' | sed 's/=.*//'` (Werte nie ausgeben). Fehlen sie, David fragen.

1. Bridge-Client holen: Repo `blickwert/bw-wp-bridge` (über `add_repo` einbinden, klonen) → `python3 tools/wp_bridge.py --site appa2 status`.
2. Bestand ansehen (nichts ändern): WordPress-Version, Sprache, Permalinks, installierte Plugins.
3. Contact Form 7 installieren und aktivieren, **deutsche Übersetzung vor `bw-appa`** (sonst entstehen die CF7-Meldungen englisch).
4. ZIPs bauen und installieren:
   ```bash
   cd wordpress/themes && zip -qr /tmp/appa.zip appa
   cd ../plugins && zip -qr /tmp/bw-appa.zip bw-appa -x 'bw-appa/vendor/*' 'bw-appa/tests/*' 'bw-appa/composer.*' 'bw-appa/phpunit.xml.dist'
   python3 tools/wp_bridge.py --site appa2 plugin-install --zip /tmp/bw-appa.zip --activate   # Theme: über WP-Admin oder Bridge, dann aktivieren
   ```
   Theme-Upload kann der Bridge-Client evtl. nicht – dann David bitten, `appa.zip` unter Design → Themes hochzuladen, oder REST/Bridge prüfen.
5. Permalinks „Beitragsname“ setzen, einmal `wp-admin` aufrufen (legt die CF7-Formulare an).
6. Seiten, Startseite, Hauptmenü: `WP_URL=$WP_APPA2_URL WP_USER=$WP_APPA2_USER WP_APP_PASSWORD=$WP_APPA2_APP_PASSWORD python3 scripts/import_pages.py` (erst mit `--dry-run`).
7. Einstellungen → „APPA Formulare“: Empfänger (vorerst `bw-cms@blickwert.at`, später `info@appa.net` – David fragen). CF7 → Integration: Turnstile-Schlüssel (David).
8. 2–3 Veranstaltungen als Beispiel anlegen (Fachtagung 8./9. Oktober 2027 aus dem Briefing), Formulare testen, Screenshots 1440/390 gegen `2603-appa` vergleichen, Ergebnis melden.

## Lokale Prüfumgebung neu aufsetzen (Container ist frisch)

```bash
S=<scratchpad>; mkdir -p $S/wpl && cd $S/wpl
curl -sSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o wp-cli.phar
W="php $S/wpl/wp-cli.phar --allow-root --path=$S/wpl/site"
curl -sSL https://wordpress.org/latest.zip -o wp.zip && unzip -q wp.zip && mv wordpress site
curl -sSL https://downloads.wordpress.org/plugin/sqlite-database-integration.zip -o sq.zip && unzip -q sq.zip -d site/wp-content/plugins/
cp site/wp-content/plugins/sqlite-database-integration/db.copy site/wp-content/db.php
sed -i "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$S/wpl/site/wp-content/plugins/sqlite-database-integration#;s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" site/wp-content/db.php
$W config create --dbname=wp --dbuser=x --dbpass=x --skip-check
$W core install --url=http://127.0.0.1:8899 --title=APPA --admin_user=admin --admin_password=admin --admin_email=dev@example.com --skip-email
$W option update siteurl http://127.0.0.1:8899 && $W option update home http://127.0.0.1:8899
$W language core install de_DE --activate && $W plugin install contact-form-7 --activate && $W language plugin install contact-form-7 de_DE
ln -s <repo>/wordpress/themes/appa site/wp-content/themes/appa && ln -s <repo>/wordpress/plugins/bw-appa site/wp-content/plugins/bw-appa
$W theme activate appa && $W plugin activate bw-appa && $W rewrite structure '/%postname%/'
(cd site && php -S 127.0.0.1:8899 &)   # Screenshots: Playwright mit executablePath /opt/pw-browsers/chromium
```
App-Passwort für `import_pages.py`: `$W user application-password create admin local --porcelain`; bei Python-Anfragen `NO_PROXY=127.0.0.1` setzen.

## Arbeitsregeln (Kurzfassung)

- Texte/Abschnitte nur in `scripts/build_patterns.py` ändern, dann `cd scripts && python3 build_patterns.py`; Designwerte nur in `tokens/tokens.json`, dann `python3 scripts/build_theme_json.py`.
- Schrift-/Abstands-Slugs ohne Ziffer am Anfang + Buchstaben (WordPress macht aus `3xl` → `3-xl`).
- Raster (`layout: grid` mit Mindestbreite) nicht in senkrechte Flex-Gruppen setzen (Höhe explodiert); `stack()` nutzt deshalb normalen Fluss.
- Nach jeder Änderung: Screenshots 1440/390, Editor auf ungültige Blöcke prüfen, `composer test` im Plugin.

## Offene Punkte (von David / APPA)

- Dev-Installation `2603b-appa` + Zugangsdaten.
- Turnstile-Schlüssel, endgültiger Formular-Empfänger.
- Datenschutzerklärung, Statuten-PDF, ZVR-Zahl und Vereinsbehörde (Impressum).
- „Mehr Gelb“ im Design (Meeting 9. 10.): wo genau.
- Danach: Mitgliederbereich und Mitgliedschaft mit Zahlung im eigenen Plugin `bw-woo-membership` (Konzept „APPA – Mitgliedschaft online“, Meeting 9. 10.: WooCommerce + Subscriptions, Stripe, Staffel Juni ½ / Oktober ⅓ / Dezember gratis, Studierendennachweis jährlich, vier Mitgliedsarten).
- Die alte Elementor-Seite `2603-appa` nutzt noch `wp-plugins/appa-loop-query`; nach Umstieg kann der Ordner entfallen.
