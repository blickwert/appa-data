---
name: wp-theme-architecture
description: Blickwert-Architektur für WordPress-Block-Themes (theme.json als Designsystem, Patterns, Vorlagen, eigene Blöcke, PHP 8.3). Verwenden beim Anlegen oder Ändern eines BW-Block-Themes, beim Übertragen eines Designs aus einer HTML-Vorschau in ein Theme und bei jeder Änderung an Vorlagen, Patterns, Blockstilen oder theme.json.
---

# WordPress-Block-Theme-Architektur (Blickwert)

Gegenstück zum Skill `wp-plugin-architecture`. Grundsätze daraus gelten auch hier (PHP 8.3, strict types, Präfixe, Sicherheit, keine Logik in Vorlagen, keine fremden Schriften). Für CSS gilt zusätzlich der Skill `html-css-richtlinien`. Projektspezifisches steht in der `CLAUDE.md` des Repos – bei Widerspruch gilt die `CLAUDE.md`.

## Ziel

Das Designsystem steckt vollständig im Theme. Wer im Block-Editor Inhalte pflegt, wählt nur noch Blöcke, Patterns und benannte Stile – Farben, Schriften, Abstände und Komponenten-Aussehen werden nicht pro Block eingestellt.

## Abgrenzung Theme / Plugin

- **Theme = Darstellung:** Vorlagen, Vorlagenteile, Patterns, Blockstile, theme.json, Komponenten-CSS, Icons, Schriften.
- **Plugin = Funktion:** Formulare, Speichern, Mails, Schnittstellen (Newsletter, CRM), Spamschutz, eigene Beitragstypen. Siehe `wp-plugin-architecture`.
- Das Theme funktioniert ohne das Plugin (Formular-Abschnitte bleiben leer bzw. zeigen nichts). Das Plugin übernimmt das Aussehen über die CSS-Variablen des Themes (`--wp--preset--*`, `--bw-*`), nicht über eigene Farbwerte.

## Grundregeln

- PHP 8.3, `declare(strict_types=1);`, Typen überall. Namespace `BW\<Theme>\`, PSR-4 (Composer oder einfacher eigener Autoloader).
- Präfix `bw_<theme>_` für Hooks, Optionen, Handles; CSS-Klassen und -Variablen `bw-`. Blöcke im Namespace `bw/` (z. B. `bw/icon`). Pattern-Slugs `<theme-slug>/<name>`, Pattern-Kategorie `<theme-slug>`.
- Text Domain = Theme-Slug. Code und Kommentare englisch; Inhalte der Website in der Sprache der Website.
- Ziel-Version laut `CLAUDE.md` (hier: WordPress 7.x). Nur Funktionen nutzen, die diese Version sicher hat; im Zweifel in der Installation prüfen.

## Ordnerstruktur

```
<theme-slug>/
├─ style.css            nur Theme-Header (Name, Version, Requires at least, Requires PHP, Text Domain)
├─ theme.json           Designsystem (aus Tokens erzeugt, nicht von Hand ändern – Generator anpassen)
├─ functions.php        nur Autoloader + Theme::instance()->boot()
├─ src/                 Theme, Assets, Blocks, Patterns, Icons (je eine Klasse, eine Aufgabe)
├─ templates/           front-page.html, page.html, page-<variante>.html, index.html, 404.html
├─ parts/               header.html, header-<variante>.html, footer.html
├─ patterns/            ein Abschnitt pro Datei (PHP mit Header-Kommentar)
├─ styles/              Block-Stil-Varianten als JSON-Partials (z. B. Abschnitts-Hintergründe)
├─ blocks/<name>/       eigene Blöcke: block.json, render.php, style.css (nur wo Core-Blöcke nicht reichen)
├─ assets/css/          Komponenten-CSS, pro Block registriert (wp_enqueue_block_style)
├─ assets/fonts/        Schriften als woff2 + Lizenzdateien
├─ assets/icons/        SVG-Icons (ein Symbol pro Datei, stroke="currentColor")
└─ languages/
```

## theme.json = Designsystem

- **Einzige Quelle bleibt die Token-Datei** (`tokens/*.json`). Ein Skript erzeugt `theme.json`; Werte werden nie direkt in `theme.json` oder CSS geändert.
- `settings`:
  - `color.palette` (alle Markenfarben mit Slugs), `color.gradients` (Markenverläufe), `custom`, `defaultPalette`, `defaultGradients` und `defaultDuotone` auf `false` – im Editor erscheinen nur Markenfarben.
  - `typography.fontFamilies` mit `fontFace` auf lokale Dateien (`file:./assets/fonts/...`), `typography.fontSizes` aus der Größenskala (bei Bedarf `fluid`), `customFontSize: false`.
  - `spacing.spacingSizes` aus der Abstandsskala, `spacing.units` begrenzen; `layout.contentSize` / `wideSize`.
  - `shadow.presets`, `custom` für Radien, Linienstärken, Transitions.
  - Wo möglich Freiheiten abschalten, die das Design brechen (`color.custom`, `typography.customFontSize`, freie Ränder), nur das Nötige erlauben.
- `styles`: Elemente (`h1`–`h6`, `link`, `button`, `caption`) und Core-Blöcke (`core/button`, `core/details`, `core/quote`, `core/navigation`, `core/list`, `core/separator` …) so gestalten, dass sie ohne Einstellungen richtig aussehen.
- Im CSS die Presets nutzen: `var(--wp--preset--color--navy)`, `var(--wp--preset--spacing--40)`. Die eigenen Kurz-Variablen (`--bw-*`) zeigen auf die Presets, damit Komponenten-CSS aus der Vorschau weiterläuft:
  ```css
  :root { --bw-color-text: var(--wp--preset--color--navy); }
  ```

## Varianten ohne freie Klassen

- **Blockstil-Varianten** (`styles/*.json` bzw. `register_block_style`) für alles, was mehrfach vorkommt: Abschnitts-Hintergründe, Karten, Button-Arten, Listen-Arten. Sie erscheinen im Editor als „Stile“ und erzeugen `is-style-<name>`.
- Freie CSS-Klassen („Zusätzliche CSS-Klassen“) nur in Patterns und Vorlagen des Themes, nicht als Arbeitsweise im Editor.
- Akzentfarben (z. B. je Angebot) als Stil-Variante, die eine CSS-Variable setzt (`--bw-accent`), statt Farben pro Block zu wählen.

## Vorlagen, Vorlagenteile, Patterns

- Vorlagen enthalten nur Struktur (Header-Teil, `core/post-content`, Footer-Teil). Inhalte stehen in Seiten, nicht in Vorlagen.
- Wiederkehrende Abschnitte als **Patterns**: ein Abschnitt pro Datei, Kategorie des Themes, sprechender Titel, `Inserter: true`. Seiteninhalte werden aus diesen Patterns zusammengesetzt.
- Patterns sind statisches Block-Markup. Keine Abfragen, keine Logik. Übersetzbare Texte über `esc_html_e()` nur, wenn das Theme mehrsprachig sein soll; sonst Text direkt.
- Struktur, die nicht verändert werden darf, mit `templateLock` (`contentOnly` für Abschnitte, die nur Texte ändern lassen).
- Header-/Footer-Varianten als eigene Vorlagenteile; die Vorlage wählt den Teil (z. B. `page-angebot.html` nutzt `header-akzent`).

## Eigene Blöcke

- Nur wenn Core-Blöcke + Stile nicht reichen (z. B. Icon-Auswahl, Inhalte mit fester Struktur).
- `block.json` (apiVersion 3), Darstellung serverseitig über `render.php`, Ausgabe escapen. Kein Build-Schritt, solange der Editor mit `ServerSideRender` bzw. einfachen Steuerelementen auskommt; sonst `@wordpress/scripts` nach `build/`.
- Interaktion über die Interactivity API (siehe Plugin-Skill), nicht über eigenes JS-Framework.

## Icons

- SVG-Dateien in `assets/icons/`, `viewBox="0 0 24 24"`, `fill="none"`, `stroke="currentColor"` – Farbe kommt aus CSS.
- Ausgabe über den Block `bw/icon` (Auswahl Symbol, Größe, Akzent) bzw. eine Hilfsklasse in PHP, die die Datei inline einbettet und `aria-hidden="true"` setzt.

## CSS

- Regeln aus `html-css-richtlinien` (keine Inline-Styles, Klassen statt Wiederholung, Tokens, Präfix `bw-`).
- Möglichst viel über theme.json; Komponenten-CSS nur für das, was theme.json nicht ausdrücken kann.
- CSS pro Block registrieren (`wp_enqueue_block_style`), damit nur geladen wird, was die Seite nutzt.
- Responsive: Komponenten mit Container Queries, Seitenlayout über theme.json-Layout und `clamp()`; feste Breakpoints nur, wo es nicht anders geht.
- Editor und Frontend sehen gleich aus: dieselben Stylesheets im Editor laden (`add_editor_style` bzw. Block-Styles).

## Schriften, Datenschutz, Barrierefreiheit, SEO

- Schriften nur lokal (`assets/fonts/`, mit Lizenzdatei). Keine Google Fonts, keine fremden CDNs.
- Je Seite genau eine H1, Hierarchie H2/H3 ohne Sprünge; Icons dekorativ (`aria-hidden`), Bilder mit Alt-Text.
- Fokus sichtbar, Kontraste nach WCAG AA, Bedienung per Tastatur (Navigation, Details/Akkordeon).
- Titel und Meta-Beschreibungen gehören in die Seite bzw. ein SEO-Plugin, nicht ins Theme.

## Verifikation

- Jede sichtbare Änderung im Frontend **und** im Editor ansehen, Desktop und Handybreite (390 px).
- Bei Übertragung aus einer HTML-Vorschau: Screenshot-Vergleich je Abschnitt gegen die Vorschau.
- `php -l` auf alle PHP-Dateien; theme.json gegen das Schema prüfen (`$schema`), keine Fehler im Website-Editor.

## Vor jedem Commit

1. theme.json neu erzeugt, wenn Tokens geändert wurden.
2. `php -l` grün, keine PHP-Warnungen im Debug-Log.
3. Neue Patterns/Stile/Blöcke in der `CLAUDE.md` bzw. Theme-Doku eingetragen.
4. Bei Release: Version in `style.css` erhöhen.

## Nicht tun

- Keine Farben, Schriftgrößen oder Abstände direkt in Patterns oder Vorlagen setzen (keine `style`-Attribute mit Rohwerten) – nur Presets und Stil-Varianten.
- Keine Geschäftslogik, keine Formular-Verarbeitung, keine Datenbankzugriffe im Theme.
- Keine Page-Builder, keine Abhängigkeit von Elementor o. Ä.
- Keine Schriften oder Skripte von fremden Servern.
