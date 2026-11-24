# APPA – Projektkontext für Claude

Sprache mit dem Auftraggeber: Deutsch. Auftraggeber: David (blickwert, Graz). Kunde: APPA – Austrian Positive Psychology Association.

## Aktuelle Aufgabe

**Neue Session? Zuerst [`docs/NAECHSTE-SESSION.md`](docs/NAECHSTE-SESSION.md) lesen** – dort stehen der Stand, die nächsten Schritte (Übertragung auf `2603b-appa`) und wie die lokale Prüfumgebung aufgebaut wird.

**WordPress-Umsetzung als Block-Theme + Funktions-Plugin, ohne Elementor** (Vorbild: Repo `blickwert/perma-data`). Arbeitsstand, Entscheidungen und Ablauf: [`docs/WP-UMSETZUNG.md`](docs/WP-UMSETZUNG.md).

- Theme `wordpress/themes/appa/` (Slug `appa`, Namespace `BW\AppaTheme`, Hooks `bw_appa_theme_`)
- Plugin `wordpress/plugins/bw-appa/` (Slug `bw-appa`, Namespace `BW\Appa`, Hooks/Optionen `bw_appa_`, Konstanten `BW_APPA_*`)
- Neue Dev-Installation: `2603b-appa` (Block-Theme). Die Elementor-Seite `2603-appa` bleibt zum Vergleich.

## Regeln

- Skills in `.claude/skills/`: `wp-theme-architecture`, `wp-plugin-architecture`, `html-css-richtlinien`. Immer anwenden.
- Präfix `bw-` (CSS), `bw_` (PHP-Hooks), PHP 8.3, WordPress 7.x. Keine Page-Builder.
- Designwerte nur aus `tokens/tokens.json`; `theme.json` wird erzeugt (`python3 scripts/build_theme_json.py`), nie von Hand ändern.
- Texte und Abschnitte der Seiten stehen in `scripts/build_patterns.py`; die Pattern-Dateien sind erzeugt (`cd scripts && python3 build_patterns.py`).
- Keine Schriften oder Skripte von fremden Servern (Ausnahme: Turnstile im Formular).
- Keine Schlüssel oder Passwörter ins Repo.

## Arbeitsweise im Repo

- Pro Änderung: Branch, Commit, Pull Request.
- Sichtbare Änderungen lokal prüfen (WordPress + SQLite, Chromium/Playwright, 1440 px und 390 px) und im Block-Editor auf ungültige Blöcke prüfen.
- Plugin: `composer install && composer test` (PHPUnit), `composer lint`.

## Wichtige Dateien

| Pfad | Zweck |
|---|---|
| `tokens/tokens.json` | Designsystem (Farben, Schrift, Größen, Abstände, Radien, Schatten) |
| `scripts/build_theme_json.py` | Tokens → `wordpress/themes/appa/theme.json` |
| `scripts/blocks.py`, `scripts/build_patterns.py` | Block-Markup-Helfer und alle Seitentexte → `patterns/*.php` |
| `scripts/import_pages.py` | Seiten, Startseite und Hauptmenü per REST anlegen/aktualisieren |
| `wordpress/themes/appa/styles/blocks/` | Blockstil-Varianten (Abschnitte, Karten, Buttons, Eyebrow …) |
| `wordpress/plugins/bw-appa/src/Modules/` | Module `events` (Veranstaltungen) und `forms` (CF7-Formulare, Spamschutz) |
| `elementor/`, `APPA-Mockup/` | Alte Elementor-Umsetzung und Mockup (nur Referenz) |
| `meeting/` | Besprechungsprotokolle |
