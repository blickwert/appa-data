# APPA Loop-Abfrage Veranstaltungen

Kleines Plugin für den Beitragstyp `veranstaltungen` (ACF-Felder `date`, `time`, `zoom-link`, `zoom-access`):

- **Query-ID `appa_events`** (Elementor-Loop › Abfrage › Query-ID): nur veröffentlichte Termine mit `date` ≥ heute, aufsteigend nach Datum sortiert. `date` ist ein ACF-Datumsfeld mit Speicherformat `Ymd`.
- **Shortcode `[appa_termin]`** (Dynamic Tag „Shortcode“): „8. Oktober 2027 · 18:30 Uhr“ (österreichische Monatsnamen); `[appa_termin feld="zugang"]` liefert „Zugangsdaten: …“ oder nichts.

Das Veranstaltungsarchiv (`/veranstaltungen/`) rendert das Theme-Builder-Template „APPA – Veranstaltungen (Archiv)“ (Bedingung `include/archive/veranstaltungen_archive`).
