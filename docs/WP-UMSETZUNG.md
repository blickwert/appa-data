# WordPress-Umsetzung: Block-Theme `appa` + Plugin `bw-appa`

Stand: 24. November 2026. Die APPA-Website wird ohne Elementor als eigenes **Block-Theme** (Darstellung) und **Funktions-Plugin** umgesetzt, nach dem Vorbild von `blickwert/perma-data`.

## Entscheidungen

| Thema | Entscheidung |
|---|---|
| Theme | Block-Theme (FSE), Designsystem in `theme.json` + Blockstil-Varianten, Abschnitte als Patterns |
| Plugin | `bw-appa`: Module `events` und `forms`, kein Pro-Teil |
| Veranstaltungen | Beitragstyp `appa_event` und Felder im Plugin per Code (nicht mehr ACF); Archiv unter `/veranstaltungen/` |
| Formulare | Contact Form 7 + eigener Spamschutz (Honeypot, Mindestausfüllzeit mit signiertem Zeitstempel, Ratenbegrenzung, Link- und Wortfilter); Cloudflare Turnstile über die CF7-Integration |
| Schrift | Inter (variabel, latin + latin-ext) lokal im Theme, Lizenz OFL |
| Zielinstallation | neue Dev-Installation `2603b-appa`; `2603-appa` (Elementor) bleibt zum Vergleich |
| Navigation | Hauptmenü: Über uns · Positive Psychologie · Veranstaltungen · Mitgliedschaft. Kontakt, Impressum, Datenschutz im Footer |

## Designsystem

- Quelle `tokens/tokens.json` → `scripts/build_theme_json.py` → `theme.json`.
- Farben: Rot `#D93629` (Primär), Navy `#12345A` (Überschriften), Schiefer `#52677A` (Text), Gold, Grün, Blau (Fokus), Hellblau, Grau hell, Linie, Weiß. Freie Farben, Verläufe und Größen sind im Editor abgeschaltet.
- Schriftgrößen `xs, sm, base, lg, xl, xx-large, xxx-large, huge, display` (fluid). Slugs ohne Ziffern, weil WordPress `3xl` zu `3-xl` umschreibt.
- Abstände `1…20` (× 4 px) plus fluid `section` und `gutter`; Radien, Linien, Zeilenhöhen und Zeichenabstand als `--wp--custom--*`.

## Theme-Aufbau

| Bereich | Inhalt |
|---|---|
| Vorlagen | `front-page`, `page`, `page-full` (Seite ohne Titel, aus Abschnitten), `single`, `index`, `404`, `archive-appa_event`, `single-appa_event` |
| Vorlagenteile | `header` (Logo, Navigation, Handy-Menü), `footer` (Navy, Logo-Farbstreifen, Links) |
| Blockstile | Gruppe: Abschnitt Hellblau/Grau/Grün/Navy, Karte, Karte flach · Absatz: Eyebrow, Lead, Schlagworte · Button: Sekundär, Link mit Pfeil · Zitat: groß |
| Patterns | 41 Abschnitte (Kategorie „APPA – Abschnitte“) und 7 Seiteninhalte (Startseite, Über uns, Positive Psychologie, Mitgliedschaft, Kontakt, Impressum, Datenschutz; erscheinen beim Anlegen einer neuen Seite) |
| Eigener Block | `bw/icon` (Symbol aus `assets/icons/`, Größe, Farbe; serverseitig) |
| CSS | `base.css` (global, klein) + je Core-Block eine Datei, die nur geladen wird, wenn der Block auf der Seite vorkommt |

## Plugin `bw-appa`

**Modul `events`**
- Beitragstyp `appa_event` mit Feldern Datum, Uhrzeit, Zoom-Link, Zoom-Zugangsdaten (Meta `bw_appa_event_*`, REST-fähig, bereinigt). Eingabe im Editor über das Seitenpanel „Termin“.
- Query-Loops mit Beitragstyp `appa_event` und das Archiv zeigen nur Termine ab heute, sortiert nach Datum und Uhrzeit.
- Blockbindungen `bw-appa/event` (Schlüssel `when`, `access`, `zoom_link`). Gebundene Absätze und Buttons ohne Wert werden ausgeblendet (z. B. kein Zoom-Button ohne Link).

**Modul `forms`**
- Legt beim ersten Admin-Aufruf zwei CF7-Formulare an: Kontakt und Mitgliedsantrag (bestehende werden nie überschrieben).
- Block `bw-appa/form` (Auswahl Kontakt/Mitgliedsantrag) für Patterns und Seiten.
- Einstellungen → „APPA Formulare“: Empfänger, Mindestausfüllzeit, max. Links, max. Einsendungen pro Stunde, Sperrwörter.

## Übertragung auf `2603b-appa`

1. WordPress 7.x mit Sprache Deutsch, Permalinks „Beitragsname“.
2. Plugins: Contact Form 7 (mit deutscher Übersetzung, **vor** `bw-appa`, damit die CF7-Meldungen deutsch angelegt werden), BW WP Bridge.
3. Theme `appa` und Plugin `bw-appa` als ZIP installieren und aktivieren.
4. `WP_URL=… WP_USER=… WP_APP_PASSWORD=… python3 scripts/import_pages.py` → Seiten, Startseite, Hauptmenü.
5. Einstellungen → APPA Formulare: Empfänger eintragen. CF7 → Integration: Turnstile-Schlüssel (liefert die APPA/blickwert).
6. Veranstaltungen anlegen; Test-Einsendungen beider Formulare; Datenschutztext ergänzen (Turnstile, Formulare).

## Lokal prüfen (Stand dieser Umsetzung)

WordPress 7.1 + SQLite lokal, Theme und Plugin verlinkt:
- Alle Seiten bei 1440 px und 390 px angesehen (Screenshots), Block-Editor: 0 ungültige Blöcke auf allen Seiten.
- Veranstaltungen: vergangene Termine ausgeblendet, Sortierung nach Datum, Zoom-Button nur mit Link.
- Kontaktformular: Sofort-Absenden wird als Spam erkannt; normales Absenden besteht den Spamschutz (Mailversand lokal nicht vorhanden).
- Plugin: 13 PHPUnit-Tests grün; `php -l` auf allen PHP-Dateien grün.

## Offen

- Dev-Installation `2603b-appa` und Zugangsdaten für die Bridge (Umgebungsvariablen `WP_APPA2_*`).
- Turnstile-Schlüssel, endgültiger Empfänger (`info@appa.net`?), Datenschutzerklärung, Statuten-PDF, ZVR-Zahl und Vereinsbehörde im Impressum.
- Mitgliederbereich und Mitgliedschaft mit Zahlung: eigenes Plugin `bw-woo-membership` (WooCommerce Subscriptions), siehe Konzept.
- Mehr Gelb im Design (Meeting 9. Oktober) – mit David abstimmen, wo.
