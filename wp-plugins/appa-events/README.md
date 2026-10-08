# APPA Veranstaltungen

Veranstaltungen als eigener Beitragstyp (`veranstaltung`) mit ACF-Feldern. Der Elementor-Loop zeigt nur Termine, die heute noch laufen oder in der Zukunft liegen.

Benötigt: Advanced Custom Fields (Pro), Elementor 4.x (atomarer Loop).

## Installation
Ordner `appa-events` als ZIP hochladen und aktivieren. Beitragstyp und Feldgruppe liegen als ACF-JSON im Plugin (`acf-json/`) und erscheinen in ACF als „lokal“ (bei Bedarf „Sync“ verfügbar).

## Felder
| Feld | Name | Hinweis |
|---|---|---|
| Beginn | `datum_von` | leer = „in Vorbereitung“, bleibt sichtbar |
| Ende | `datum_bis` | optional, mehrtägig; nicht vor Beginn |
| Uhrzeit | `uhrzeit` | nur mit Datum angezeigt |
| Datumshinweis | `datum_hinweis` | z. B. „vorläufig geplant“, „In Vorbereitung“ |
| Ort | `ort` | |
| Beschreibung | `beschreibung` | |
| Button-Text / -Link | `button_text`, `button_link` | |
| Bild | Beitragsbild | |

## Sichtbarkeit
Query-ID `appa_events` (Elementor-Loop › Abfrage › Query-ID): nur veröffentlichte Termine mit Ende ≥ heute (Zeitzone der Website); Sortierung nach Beginn, Termine ohne Datum zuerst. Abgeleitete Meta-Felder `_appa_ende`/`_appa_sort` werden automatisch gepflegt (auch bei REST/Import). In der Admin-Liste zeigt die Spalte „Anzeige“, ob ein Termin sichtbar ist.

## Terminzeile
Shortcode `[appa_termin]` (im Loop über das Dynamic Tag „Shortcode“), z. B. „8. und 9. Oktober 2027 · vorläufig geplant“; österreichische Monatsnamen („Jänner“).

## Loop einsetzen
Atomarer Loop: Quelle „Veranstaltung“, Query-ID `appa_events`; Inhalte über ACF-Dynamic-Tags (`acf-text`, `acf-url`), Titel `post-title`, Bild `post-featured-image`.

## Tests
```
php tests/test-events.php                     # reine Logik, ohne WordPress
wp eval-file tests/it-wordpress.php           # Integration; löscht ALLE Veranstaltungen – nur Testinstanz!
```
