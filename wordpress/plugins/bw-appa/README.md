# APPA Funktionen (`bw-appa`)

Funktions-Plugin der APPA-Website: Veranstaltungen, Formulare und SEO. PHP 8.3, WordPress 7.x. Namespace `BW\Appa`, Präfix `bw_appa_`, Konstanten `BW_APPA_*`, Text Domain `bw-appa` (deutsche Grundtexte, übersetzbar über `languages/`).

## Module

| Modul | Inhalt |
|---|---|
| `events` | Beitragstyp `appa_event` (Archiv `/veranstaltungen/`) mit Terminfeldern als Post-Meta (`bw_appa_event_*`), eigene Seitenleiste „Termin und Anmeldung“ im Block-Editor (`assets/js/event-panel.js`), Abfrageschleifen zeigen nur kommende Termine, Block-Bindungen, Zoom-Daten nur für Berechtigte |
| `forms` | Formulare auf Basis von Contact Form 7 (wird einmalig angelegt, danach in CF7 bearbeitbar), Block `bw-appa/form`, Spamschutz (Honeypot, signierte Ausfüllzeit mit Höchstalter 24 h, Link-Grenze, Sperrwörter, Rate-Limit je Stunde), Empfänger und Grenzwerte unter **Einstellungen › APPA Formulare** |
| `seo` | Einstellungen für Yoast (Titel, Beschreibungen, Organisation) im Code, strukturierte Daten `Event` für Veranstaltungen |

Weitere Module kann ein anderes Plugin über `bw_appa_register_modules` anmelden.

## Hooks

| Hook | Art | Zweck |
|---|---|---|
| `bw_appa_register_modules` | Filter | Liste der Module (`Module[]`) |
| `bw_appa_event_fields` | Filter | Einzige Quelle der Veranstaltungsdaten für andere Plugins: `apply_filters( 'bw_appa_event_fields', null, $post_id )` liefert die normalisierten Felder (`date`, `time`, `end_date`, `end_time`, `format`, `location`, `zoom_link`, `zoom_access`, `audience`, `registration`, `price_member`, `price_public`, `capacity`, `deadline`, `reminder_days`, `cancel_days`) und die Texte `when_label`, `place_label` |
| `bw_appa_event_editor_info` | Filter | Anmeldezahlen für die Editor-Seitenleiste (`enabled`, `registered`, `capacity`, `left`, `waitlist`, `url`), beantwortet von `bw-woo-membership` |
| `bw_appa_event_private_visible` | Filter | Ob die aktuelle Person Zoom-Link und Zugangsdaten einer Veranstaltung sehen darf (`$visible, $post_id, $user_id`) |
| `bw_appa_seo_settings` | Filter | Im Code gesetzte Yoast-Einstellungen (Schlüssel der Option `wpseo_titles`) |
| `bw_appa_logo_url` | Filter | Logo der Organisation in den strukturierten Daten |

## Block-Bindungen

Quelle `bw-appa/event` (Kontext `postId`), Argument `key`:

| `key` | Wert |
|---|---|
| `when` | Datum und Uhrzeit, z. B. „8. Oktober 2027 · 9:00–17:00 Uhr“ |
| `place` | Format und Ort, z. B. „Vor Ort · Universität Graz“ |
| `badge` | „Exklusive Veranstaltung für Mitglieder“ oder leer |
| `cta` | „Jetzt anmelden“ (mit Anmeldung) oder „Mehr erfahren“ |
| `url` | Adresse der Veranstaltung (mit `#anmeldung`, wenn Anmeldung möglich) |
| `access` | „Zugangsdaten: …“ (nur für Berechtigte) |
| `zoom_link` | Zoom-Link (nur für Berechtigte) |

Absätze und Buttons, deren gebundener Wert leer ist, werden nicht ausgegeben.

## Entwicklung

```bash
composer install
composer test   # PHPUnit
composer lint   # php -l und PHPCS (WordPress-Extra, phpcs.xml.dist)
```

Die Editor-Skripte (`assets/js/`, `blocks/*/index.js`) nutzen die `wp.*`-Globals ohne Build-Schritt.
