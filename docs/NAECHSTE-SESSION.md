# Übergabe für die nächste Session

Stand: 24. November 2026. Zuerst `CLAUDE.md` und `docs/WP-UMSETZUNG.md` lesen, dann hier weitermachen. Sprache mit David: Deutsch.

## Was fertig ist (PR „Block-Theme appa + Plugin bw-appa statt Elementor“)

- Theme `wordpress/themes/appa/` und Plugin `wordpress/plugins/bw-appa/` sind gebaut und lokal geprüft (WordPress 7.1 + SQLite): alle Seiten bei 1440/390 px, 0 ungültige Blöcke im Editor, Veranstaltungsfilter, Spamschutz des Kontaktformulars, 13 PHPUnit-Tests grün.
- Inhalte = Stand der Elementor-Seite `2603-appa` (Texte nach Briefing vom 23. 9. und Seite „Positive Psychologie“).
- Getroffene Entscheidungen (nicht neu verhandeln): neue Dev-Installation `2603b-appa`; Formulare mit Contact Form 7 + Turnstile; Veranstaltungen als Beitragstyp im Plugin per Code (kein ACF); Kontakt/Impressum/Datenschutz nur im Footer-Menü.

## Stand `2603b-appa` (übertragen am 24. 11. 2026)

Zugang: Bridge-Client `tools/wp_bridge.py` aus `blickwert/bw-wp-bridge`, `--site dev2603bappa` (Umgebungsvariablen `WP_DEV2603BAPPA_URL`, `_USER`, `_APP_PASSWORD`, `_BRIDGE_KEY`; Werte nie ausgeben). Die Bridge verlangt den Verbindungsschlüssel bei **allen** REST-Anfragen; `import_pages.py` sendet ihn, wenn `WP_BRIDGE_KEY` gesetzt ist.

- WordPress 7.1.5, Deutsch, Permalinks `/%postname%/`. Aktiv: Theme `appa`, Plugins `bw-appa`, Contact Form 7 (mit deutscher Übersetzung), BW WP Bridge. Elementor & Co. sind installiert, aber inaktiv.
- Seiten, Startseite und Hauptmenü per `import_pages.py`; CF7-Formulare „APPA – Kontakt“ und „APPA – Mitgliedsantrag“ angelegt. Kontaktformular getestet: wird versendet.
- Layout an die Elementor-Seite angeglichen (Typografie des Block-Themes bleibt), Mitgliederbereich (Anmeldung + Übersicht) als eigene Vorlagen; Theme 0.3.0, Plugin 0.1.2.
- Mitgliederbereich Stufe 1 live (Plugin `bw-woo-membership`): Übersicht geschützt, Daten aus WooCommerce, Mitglieder-Dokumente geschützt (direkter Dateiaufruf auf 2603b liefert 403). Produkte 52/53 haben die Mitgliedsart `regular`/`student`. Nächste Stufen siehe `docs/MITGLIEDERBEREICH.md`.
- **Stufe 2 und 3 live (24. 11.):** `bw-woo-membership` 0.3.0, `bw-appa` 0.2.0, Theme 0.4.0. Backend-Menü „Mitglieder“ (Liste, Bearbeiten, Ehren-/institutionelle Mitglieder, CSV, „Nachweise prüfen“), Studiennachweis im Mitgliederbereich, Veranstaltungsanmeldung über WooCommerce (Felder im Editor unter „Anmeldung und Preise“). Bei der Fachtagung (ID 18, Produkt 60) ist die Anmeldung eingeschaltet, mit **Testpreisen** 80/120 € und 150 Plätzen (echte Preise von APPA fehlen); live mit zwei Testkonten durchgespielt, Testdaten wieder gelöscht. WooCommerce-Modus „Shop demnächst verfügbar“ ist auf 2603b aus (sonst keine Kasse für Kund:innen). Lokal Ende-zu-Ende geprüft (Mitgliederpreis, Gratis-Anmeldung, nur Mitglieder, Erstattung, Gastkasse) und von einem zweiten Agenten gegengelesen.
- Theme 0.4.2: Kopf und Ankerleiste bleiben beim Scrollen oben, aktiver Abschnitt blau (`assets/js/navigation.js`); Titelbox im Fotokopf bündig mit der Inhaltsspalte.
- **Abo-Plugin entschieden: WooCommerce Subscriptions** (Milo Subscriptions geprüft und auf 2603b deaktiviert, siehe `docs/OFFENE-FRAGEN.md` M7). Sobald das Plugin installiert ist: Produkte 52/53 auf „Einfaches Abonnement“ jährlich umstellen, Verlängerung synchron zum 1. 1., Staffel M6 als Abzug beim ersten Beitrag, manuelle Verlängerung per Überweisung testen.
- **Offene Fragen mit Arbeitsannahmen:** `docs/OFFENE-FRAGEN.md` – Antworten dort eintragen.
- Plugins aufgeräumt (24. 11.): aktiv sind nur `bw-appa`, `bw-woo-membership` 0.1.0, BW WP Bridge, Contact Form 7, WooCommerce 11.3 und WooCommerce Stripe Gateway 11.2; alle übrigen (Elementor & Co., ACF Pro, UpdraftPlus …) gelöscht.
- WooCommerce: Graz/EUR (1.234,56 €), keine Steuern, kein Versand, keine Gutscheine, Kauf nur mit Konto (Konto wird an der Kasse angelegt). Seiten `warenkorb`, `kasse`, `mein-konto`, `shop`. Banküberweisung aktiv (Bankdaten noch Platzhalter), Stripe im Testmodus, aber noch nicht verbunden. Produkte (virtuell, im Shop ausgeblendet): „Mitgliedschaft (ordentlich)“ 96 € (ID 52), „Mitgliedschaft (Studierende)“ 35 € (ID 53) – werden zu Abo-Produkten, sobald WooCommerce Subscriptions installiert ist.
- Die CF7-Formulare legt `bw-appa` bei `admin_init` an. Ohne Anmeldung auslösen: `…/wp-admin/admin-ajax.php?action=bw_noop` aufrufen (ohne `action` endet admin-ajax vor `admin_init`).
- Empfänger: Standard = Admin-E-Mail `bw-cms@blickwert.at` (Einstellungen › APPA Formulare ist noch leer). Umstellen auf `info@appa.net` im Backend.
- Drei Beispiel-Veranstaltungen (Fachtagung 8. 10. 2027, zwei Online-Termine mit Platzhalter-Zoom-Link `zoom.us/j/0000000000`).
- Sprachpakete: Die Bridge installiert keine Übersetzungen. Fehlen welche, im Backend „Dashboard › Aktualisierungen › Übersetzungen aktualisieren“.
- Eine Mail mit einem Absendernamen wie dem Dev-Titel „@dev 2603b-appa“ lehnt `mail()` dort ab; deshalb bereinigt `bw-appa` ab 0.1.1 den Absendernamen.

Nach Änderungen neu übertragen: ZIP bauen (siehe unten), `theme-install --zip … --overwrite` bzw. `plugin-install --zip … --overwrite`; Theme- bzw. Plugin-Version erhöhen (Cache der CSS-Dateien).

```bash
cd wordpress/themes && zip -qr <scratchpad>/appa.zip appa
cd ../plugins && zip -qr <scratchpad>/bw-appa.zip bw-appa -x 'bw-appa/vendor/*' 'bw-appa/tests/*' 'bw-appa/composer.*' 'bw-appa/phpunit.xml.dist'
(cd bw-woo-membership && npm install && npm run build)   # Backend-App, build/ ist nicht im Git
zip -qr <scratchpad>/bw-woo-membership.zip bw-woo-membership -x 'bw-woo-membership/vendor/*' 'bw-woo-membership/tests/*' 'bw-woo-membership/composer.*' 'bw-woo-membership/phpunit.xml.dist' 'bw-woo-membership/node_modules/*' 'bw-woo-membership/src-js/*' 'bw-woo-membership/package*.json'
```

## Checkliste Live-Betrieb (vor dem Start)

- Stripe verbinden (Live-Schlüssel), Karte und SEPA-Lastschrift aktivieren; **Banküberweisung deaktivieren** (Entscheidung David, 24. 11.).
- WooCommerce Subscriptions: Produkte jährlich, synchron zum 1. 1., Staffel beim ersten Beitrag; Zahlungsbestätigung als PDF.
- „Shop demnächst verfügbar“ aus, Testpreise der Fachtagung durch echte ersetzen, Testdaten löschen.
- AGB/Beitragsordnung und Datenschutzseite in WooCommerce hinterlegen.

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
App-Passwort für `import_pages.py`: `$W user application-password create admin local --porcelain`; bei Python-Anfragen `NO_PROXY=127.0.0.1` setzen. Lokal zusätzlich `$W config set WP_ENVIRONMENT_TYPE local` (Anwendungspasswörter ohne HTTPS) und `$W config set WP_DEVELOPMENT_MODE theme` (sonst cached WordPress die Pattern-Liste). Lädt `wp plugin install` nichts (Proxy), CF7 und Sprachpakete mit `curl` holen und entpacken.

## Arbeitsregeln (Kurzfassung)

- Texte/Abschnitte nur in `scripts/build_patterns.py` ändern, dann `cd scripts && python3 build_patterns.py`; Designwerte nur in `tokens/tokens.json`, dann `python3 scripts/build_theme_json.py`.
- Schrift-/Abstands-Slugs ohne Ziffer am Anfang + Buchstaben (WordPress macht aus `3xl` → `3-xl`).
- Raster (`layout: grid` mit Mindestbreite) nicht in senkrechte Flex-Gruppen setzen (Höhe explodiert); `stack()` nutzt deshalb normalen Fluss.
- Nach jeder Änderung: Screenshots 1440/390, Editor auf ungültige Blöcke prüfen, `composer test` im Plugin.

## Offene Punkte (von David / APPA)

- Beispielinhalte auf `2603b-appa` (Beitrag „Hallo Welt“, „Beispielseite“, Entwurf „Datenschutzerklärung“, CF7 „Contact form 1“): löschen?
- Debug-Log auf `2603b-appa` ist aus (`WP_DEBUG_LOG`); für die Prüfung auf PHP-Warnungen einschalten.
- Bridge-Freigabe „Plugins und Themes installieren“ nach der Übertragung wieder ausschalten.
- Turnstile-Schlüssel, endgültiger Formular-Empfänger.
- Datenschutzerklärung, Statuten-PDF, ZVR-Zahl und Vereinsbehörde (Impressum).
- „Mehr Gelb“ im Design (Meeting 9. 10.): wo genau.
- WooCommerce Subscriptions (kostenpflichtig, Lizenz/ZIP von APPA bzw. blickwert), Stripe verbinden (Backend: WooCommerce › Einstellungen › Zahlungen › Stripe), Bankdaten für die Überweisung, AGB/Beitragsordnung als Seite, Datenschutzseite in WordPress als Datenschutzseite festlegen, Produkt-URL-Basis `/product/` eindeutschen, Shop-Seiten im Theme gestalten.
- Mockup der Verwaltung (Mitglieder, Dokumente, Veranstaltungen mit Kopplung zum Mitgliederbereich): Claude-Artifact „APPA Mitgliederverwaltung WP“.
- Mitgliederbereich: Analyse in `docs/MITGLIEDERBEREICH.md`, offene Fragen in `docs/OFFENE-FRAGEN.md`. Noch offen: WooCommerce Subscriptions und Staffel beim ersten Beitrag, Aufzeichnungen (V5), Anmeldefrist/Warteliste (V6).
- Lokal: Shop-Basis auf AT stellen und Testkonten eine Adresse geben, sonst scheitert die Block-Kasse an Pflichtfeldern.
- Die alte Elementor-Seite `2603-appa` nutzt noch `wp-plugins/appa-loop-query`; nach Umstieg kann der Ordner entfallen.
