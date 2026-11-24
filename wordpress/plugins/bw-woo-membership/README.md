# APPA Mitgliedschaft (`bw-woo-membership`)

Mitgliederbereich der APPA auf Basis von WooCommerce. PHP 8.3, WordPress 7.x, WooCommerce 11.x. Namespace `BW\WooMembership`, Präfix `bw_wm_`, CSS `bwm-`.

## Module

| Modul | Inhalt |
|---|---|
| `membership` | Mitgliedsstatus (manuell › WooCommerce Subscriptions › Bestellung), Mitgliedsnummer und „Mitglied seit“ bei der ersten Zahlung, Produktfeld „Mitgliedsart“, Schutz der Übersicht und Login-Weiterleitung, Blöcke `user`, `overview`, `events`, `profile`, Profil-Formular, druckbare Mitgliedsbestätigung (`?bw_wm_confirmation=1`) |
| `documents` | Beitragstyp „Mitglieder-Dokumente“ (`bw_member_doc`) mit Kategorien, Datei und Sichtbarkeit; Uploads in `uploads/bw-member-docs/` mit Zufallsnamen; Download nur über `?bw_wm_doc=<ID>` nach Prüfung; Block `documents` |

## Mitgliedschaft

- Ein Produkt wird zum Mitgliedschaftsprodukt über das Feld **Mitgliedsart** (Produktdaten › Allgemein): ordentlich, studentisch, Ehrenmitglied, institutionell.
- **Ohne WooCommerce Subscriptions:** Die letzte bezahlte Bestellung (Status „In Bearbeitung“ oder „Abgeschlossen“) gilt für das Kalenderjahr der Zahlung; Zahlung im Dezember gilt bis Ende des Folgejahres. Offene Bestellung (Überweisung) = „Zahlung offen“, kein Zugang.
- **Mit WooCommerce Subscriptions:** Status, Start und nächste Zahlung kommen aus dem Abo (aktiv / pausiert / ausstehend / beendet).
- **Ehren- und institutionelle Mitglieder:** Benutzerfelder `bw_wm_manual_type` (und optional `bw_wm_manual_until`, Y-m-d). Verwaltung im Backend folgt mit Stufe 2.
- Mitgliedsnummer `A-0001` …, vergeben bei der ersten bezahlten Mitgliedschaft, bleibt für immer (auch bei Austritt und Wiedereintritt). Deinstallieren löscht keine Mitgliedsdaten.

## Seiten

Die Anmeldeseite ist die Seite mit der Theme-Vorlage `page-member`, die Übersicht die mit `page-member-dashboard` (Filter `bw_wm_login_page_id`, `bw_wm_dashboard_page_id`). Jede Seite mit einem der Blöcke `overview`, `events`, `documents`, `profile` ist geschützt.

## Hooks

| Hook | Art | Zweck |
|---|---|---|
| `bw_wm_register_modules` | Filter | Module ergänzen |
| `bw_wm_membership_types` | Filter | Mitgliedsarten |
| `bw_wm_membership` | Filter | ermittelte Mitgliedschaft ändern |
| `bw_wm_user_has_access` | Filter | darf ein Benutzer Mitgliederinhalte sehen (vom Modul `membership` beantwortet) |
| `bw_wm_membership_paid` | Aktion | Mitgliedschaftsbestellung bezahlt (`$user_id`, `$type`, `$order`) |
| `bw_wm_member_number_prefix` | Filter | Präfix der Mitgliedsnummer (Standard `A-`) |
| `bw_wm_overview_before_cards` | Aktion | Ausgabe vor den Statuskarten |
| `bw_wm_template` | Filter | Vorlagendatei; Theme-Überschreibung unter `bw-woo-membership/dashboard/<name>.php` |

## Geschützte Dateien unter nginx

nginx liest keine `.htaccess`. Die Dateinamen sind zufällig, die Adresse ist also nicht zu erraten; für echten Schutz in der Server-Konfiguration ergänzen:

```nginx
location ^~ /wp-content/uploads/bw-member-docs/ { deny all; return 403; }
```

Bei Unterverzeichnis-Installationen den Pfad anpassen (z. B. `/wp/2603b-appa/wp-content/uploads/bw-member-docs/`).

## Entwicklung

`composer install`, `composer test` (PHPUnit + Brain Monkey), `composer lint`.
