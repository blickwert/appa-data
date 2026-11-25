# APPA Mitgliedschaft (`bw-woo-membership`)

Mitgliederbereich der APPA auf Basis von WooCommerce. PHP 8.3, WordPress 7.x, WooCommerce 11.x. Namespace `BW\WooMembership`, Präfix `bw_wm_`, CSS `bwm-`.

## Module

| Modul | Inhalt |
|---|---|
| `membership` | Mitgliedsstatus (manuell › WooCommerce Subscriptions › Bestellung), Mitgliedsnummer und „Mitglied seit“ bei der ersten Zahlung, Produktfeld „Mitgliedsart“, Schutz der Übersicht und Login-Weiterleitung, Blöcke `user`, `overview`, `events`, `profile`, Profil-Formular, druckbare Mitgliedsbestätigung (`?bw_wm_confirmation=1`) |
| `documents` | Beitragstyp „Mitglieder-Dokumente“ (`bw_member_doc`) mit Kategorien, Datei und Sichtbarkeit; Uploads in `uploads/bw-member-docs/` mit Zufallsnamen; Download nur über `?bw_wm_doc=<ID>` nach Prüfung; Block `documents` |
| `events` | Anmeldung zu Veranstaltungen (`appa_event` aus `bw-appa`) über ein verstecktes, virtuelles WooCommerce-Produkt je Veranstaltung; Mitgliederpreis, Plätze als Bestand, Block `event-registration`, „Meine Anmeldungen“, Spalte „Anmeldungen“ und Teilnehmerliste (CSV) |

## Mitgliedschaft

- Ein Produkt wird zum Mitgliedschaftsprodukt über das Feld **Mitgliedsart** (Produktdaten › Allgemein): ordentlich, studentisch, Ehrenmitglied, institutionell.
- **Ohne WooCommerce Subscriptions:** Die letzte bezahlte Bestellung (Status „In Bearbeitung“ oder „Abgeschlossen“) gilt für das Kalenderjahr der Zahlung; Zahlung im Dezember gilt bis Ende des Folgejahres. Offene Bestellung (Überweisung) = „Zahlung offen“, kein Zugang.
- **Mit WooCommerce Subscriptions:** Status, Start und nächste Zahlung kommen aus dem Abo (aktiv / pausiert / ausstehend / beendet); bezahlt bis zum Vortag der nächsten Zahlung. Einrichtung: Produkte als „Einfaches Abonnement“, jährlich, synchron zum 1. 1.; „Sign-up cutoff“ 31 Tage (Beitritt im Dezember: erste Zahlung am 1. 1., gilt für das Folgejahr).
- **Verlängerung im Abo:** Kurz bevor das Abo die Verlängerungsbestellung anlegt, stellt `SubscriptionController` die Mitgliedsart um: studentisch ohne gültigen Nachweis für den Verlängerungstag → ordentlich; ordentlich mit gültigem Nachweis → studentisch; Nachweis in Prüfung → unverändert (Notiz am Abo). Der Knopf „Mitgliedschaft verlängern“ führt bei Abos zur offenen Verlängerungsbestellung, nie zu einem zweiten Abo.
- **Ehren- und institutionelle Mitglieder:** Benutzerfelder `bw_wm_manual_type` (und optional `bw_wm_manual_until`, Y-m-d). Verwaltung im Backend unter **Mitglieder** (Liste, Filter, Bearbeiten, Neu, CSV-Export).
- Mitgliedsnummer `A-0001` …, vergeben bei der ersten bezahlten Mitgliedschaft, bleibt für immer (auch bei Austritt und Wiedereintritt). Deinstallieren löscht keine Mitgliedsdaten.

## Beitritt

- Links `?bw_wm_join=regular` bzw. `?bw_wm_join=student` (Seite Mitgliedschaft) legen das Produkt der Mitgliedsart in den Warenkorb (eine andere Mitgliedschaft fliegt raus) und öffnen die Kasse. Aktive Mitglieder landen stattdessen im Mitgliederbereich.
- An der Kasse wird das Konto angelegt. Zusätzliche Felder der Block-Kasse, nur wenn eine Mitgliedschaft im Warenkorb liegt: Beruf/Studienrichtung (Pflicht), Fachgruppe (freiwillig), „Statuten gelesen, Aufnahme beantragt“ (Pflicht). Bemerkungen gehen in die Bestellnotiz.
- Die Angaben landen am Konto: `bw_wm_occupation`, `bw_wm_statutes_accepted` (Datum), Fachgruppe im Profil; sichtbar in der Verwaltung unter „Angaben beim Beitritt“.
- Das frühere Antragsformular (Contact Form 7) entfällt.
- **Studiennachweis bei der Buchung:** `?bw_wm_join=student` führt ohne Nachweis zuerst auf die Seite `/studiennachweis/` (Block `join-proof`, Filter `bw_wm_join_proof_page`). Angemeldete laden direkt auf ihr Konto hoch; Personen ohne Konto behalten die Datei in der WooCommerce-Sitzung, nach der Bestellung hängt sie „in Prüfung“ am neuen Konto. Die Kasse lehnt die studentische Mitgliedschaft ohne Nachweis ab. Nicht übernommene Dateien löscht der tägliche Lauf nach zwei Tagen.
- **Staffel beim ersten Beitrag** (nur wer noch nie Mitglied war): Jänner–Mai voller Beitrag, Juni–September die Hälfte, Oktober–November ein Drittel; Beitritt im Dezember zum vollen Beitrag, gültig bis Ende des Folgejahres. Umgesetzt als Abzugszeile im Warenkorb (`JoinDiscount`), nicht als geänderter Preis – Verlängerungen und ein späteres Abo bleiben beim vollen Beitrag.

## Verwaltung (Backend)

Menü **Mitglieder** (Berechtigung `bw_wm_manage_members`, vergeben an Administrator und Shop-Manager): Mitgliederliste mit Filter und Suche, Mitglied bearbeiten (Art, gültig bis, manuelle Mitgliedschaft), Ehren- und institutionelle Mitglieder anlegen, CSV-Export, Unterseite **Nachweise prüfen**. Die Oberfläche ist eine React-App (`src-js/admin.js`, gebaut mit `@wordpress/scripts`) auf der REST-Schnittstelle `bw-wm/v1`.

Konten mit Redaktionsrechten (`edit_posts`) können dort nicht bearbeitet werden.

**Beitragsjournal** (Kopf der Mitgliederliste): CSV mit allen Zahlungen eines Zeitraums (Zahlungsdatum, Bestellung, Art, Mitgliedsnummer, Name, E-Mail, Betrag, Erstattet, Zahlungsart, Stripe-Gebühr, Netto, Transaktions-ID) und einer Summenzeile. Grundlage für Sammelbuchungen in der Buchhaltung; Stripe-Gebühr und Netto stammen aus den Bestelldaten des Stripe-Plugins (`_stripe_fee`, `_stripe_net`). Zeitraum frei wählbar (von/bis, Knopf „Vorjahr“ als Sammelexport); Download über `admin-post.php?action=bw_wm_journal&from=JJJJ-MM-TT&to=JJJJ-MM-TT`.

## Studiennachweis

Studierende laden den Nachweis im Mitgliederbereich hoch (PDF/JPG/PNG, max. 5 MB, Ordner `uploads/bw-member-proofs/`). Das Office gibt frei oder lehnt ab; die Datei wird danach gelöscht, gespeichert bleiben Status, gültig bis, Prüfdatum und Prüfer:in. 28 Tage vor Ablauf kommt eine Erinnerung. Ohne gültigen Nachweis kann die studentische Mitgliedschaft nicht verlängert werden.

## Veranstaltungen

- Felder am Termin (Plugin `bw-appa`): Art (`open` offen, `members` nur Mitglieder, `free_members` für Mitglieder gratis), Preis Mitglieder, Preis Nichtmitglieder, Plätze, Anmeldung über die Website ein/aus.
- Beim Speichern entsteht bzw. aktualisiert sich ein verstecktes Produkt (`_bw_wm_product_id` am Termin, `_bw_wm_event_id` am Produkt). Bestand = Plätze − Anmeldungen.
- Der Preis wird im Warenkorb gesetzt: Mitgliederpreis nur mit bereits aktiver Mitgliedschaft. „Nur Mitglieder“ lehnt alle anderen ab (auch über die Store-API).
- **Anmeldeschluss** (Feld in `bw-appa`, `bw_appa_event_deadline`): danach keine Anmeldung und kein Kauf mehr.
- **Ablauf wie bei der WKO** (`RegistrationController`): Kostenlos und angemeldet → „Jetzt anmelden“ mit einem Klick unter dem eigenen Namen (0-€-Bestellung, `admin-post.php?action=bw_wm_register`); kostenpflichtig oder ohne Konto → Kasse. Bestätigung per E-Mail mit Kalenderdatei (`Service/Ics`), Erinnerung X Tage vorher (Feld `bw_appa_event_reminder_days`, Standard 2, 0 = keine; täglich mit `bw_wm_daily_after_sync`, bei Online-Terminen mit Zoom-Link). Selbst abmelden (`bw_wm_cancel_registration`) auf der Veranstaltungsseite und unter „Meine Anmeldungen“: kostenlos bis zum Termin, kostenpflichtig bis X Tage vorher (Feld `bw_appa_event_cancel_days`, Standard 14) mit voller Erstattung – über Stripe automatisch, sonst E-Mail „Erstattung nötig“ ans Office. Regeln in `Service/RegistrationRules`. Die WooCommerce-Mail „Bestellung abgeschlossen“ entfällt bei Anmeldungen (unsere Bestätigung ersetzt sie), die Office-Mail „Neue Bestellung“ bei kostenlosen Anmeldungen.
- **Teilnehmende** (Backend: Veranstaltungen › Teilnehmende, auch als Link in der Liste und in der Editor-Seitenleiste): Plätze maximal / angemeldet / frei / davon Zahlung offen / Warteliste, Liste der Anmeldungen mit Link zur Bestellung, CSV, Warteliste. **Person hinzufügen:** E-Mail (vorhandenes Konto) oder E-Mail + Name (neues Kundenkonto, WooCommerce schickt den Zugang); kostenlos (abgeschlossen, Bestätigung) oder mit Betrag (in Wartestellung, hält den Platz, E-Mail mit Zahlungslink); Überbuchung möglich. Filter `bw_appa_event_editor_info` liefert die Zahlen an die Editor-Seitenleiste von `bw-appa`.
- **Warteliste**, wenn ausgebucht: Angemeldete setzen sich in der Box auf die Liste (oder nehmen sich herunter). Wird ein Platz frei (Storno, Erstattung, mehr Plätze), bekommen die nächsten noch nicht Benachrichtigten eine E-Mail – so viele wie Plätze frei sind; wer zuerst bucht, bekommt den Platz. Nach der Anmeldung fällt man automatisch von der Liste. Spalte „Anmeldungen“ zeigt die Länge der Warteliste. Aktion `bw_wm_event_places_changed`.
- Eine Anmeldung pro Konto (Kauf nur mit Konto; ein Gast erhält an der Kasse eines). Vergangene und ausgebuchte Termine sind nicht buchbar; unbezahlte Anmeldungen vergangener Termine werden beim täglichen Lauf storniert.
- Plätze zählen Bestellungen in Wartestellung, in Bearbeitung und abgeschlossen (wie der WooCommerce-Bestand). Bei jeder Statusänderung, auch Erstattung, wird der Bestand neu gesetzt.
- Redaktion und Office zahlen den regulären Preis (Filter `bw_wm_user_is_member`).
- Bestellungen nur mit Anmeldungen werden nach der Zahlung abgeschlossen (0 € sofort). Unbezahlte Überweisungen werden nach 7 Tagen storniert (Filter `bw_wm_unpaid_registration_days`).
- Zugangsdaten (Zoom) sehen nur Angemeldete und Redaktion: Das Modul beantwortet den Filter `bw_appa_event_private_visible` von `bw-appa`; die REST-Ausgabe enthält die Felder für alle anderen nicht.

## Seiten-Cache

Der Seiten-Cache des Hostings nimmt angemeldete Nutzer:innen nicht aus (Antwort T2). Deshalb:
- Persönliche Antworten (Mitgliederbereich, Anmeldeseite, Seite Studiennachweis, Beitrittslinks, AJAX) senden `Cache-Control: no-store, private`, `X-Accel-Expires: 0` und setzen `DONOTCACHEPAGE` (`Support\NoCache`).
- Die Anmelde-Box auf der Veranstaltungsseite wird per `admin-ajax.php?action=bw_wm_event_box&event=ID` nachgeladen (`assets/event-box.js`); die Veranstaltungsseite selbst bleibt cachebar. Seiten mit Zoom-Zugangsdaten schließt `bw-appa` vom Cache aus.

## Seiten

Die Anmeldeseite ist die Seite mit der Theme-Vorlage `page-member`, die Übersicht die mit `page-member-dashboard` (Filter `bw_wm_login_page_id`, `bw_wm_dashboard_page_id`). Jede Seite mit einem der Blöcke `overview`, `events`, `documents`, `profile` ist geschützt.

## Hooks

| Hook | Art | Zweck |
|---|---|---|
| `bw_wm_register_modules` | Filter | Module ergänzen |
| `bw_wm_membership_types` | Filter | Mitgliedsarten |
| `bw_wm_membership` | Filter | ermittelte Mitgliedschaft ändern |
| `bw_wm_user_has_access` | Filter | darf ein Benutzer Mitgliederinhalte sehen (vom Modul `membership` beantwortet) |
| `bw_wm_user_is_member` | Filter | hat ein Benutzer eine aktive Mitgliedschaft (ohne Ausnahme für Redaktion; für Preise) |
| `bw_wm_membership_paid` | Aktion | Mitgliedschaftsbestellung bezahlt (`$user_id`, `$type`, `$order`) |
| `bw_wm_member_number_prefix` | Filter | Präfix der Mitgliedsnummer (Standard `A-`) |
| `bw_wm_overview_before_cards` | Aktion | Ausgabe vor den Statuskarten |
| `bw_wm_office_email` | Filter | Empfänger der Office-Benachrichtigungen (Standard: Admin-E-Mail) |
| `bw_wm_setup` | Aktion | nach Aktivierung bzw. Versionswechsel (Berechtigung, Cron) |
| `bw_wm_daily_after_sync` | Aktion | nach dem täglichen Abgleich (`bw_wm_daily`, 03:15) |
| `bw_wm_admin_menu_parent` | Filter | übergeordnetes Menü der Verwaltung |
| `bw_wm_events_before_list` | Aktion | vor der Veranstaltungsliste im Mitgliederbereich („Meine Anmeldungen“) |
| `bw_wm_event_places_changed` | Aktion | freie Plätze einer Veranstaltung neu berechnet (`$event_id`, `$left`) |
| `bw_wm_join_proof_page` | Filter | Seite mit dem Upload des Studiennachweises beim Beitritt |
| `bw_wm_unpaid_registration_days` | Filter | Tage bis zur Stornierung unbezahlter Anmeldungen (Standard 7) |
| `bw_wm_template` | Filter | Vorlagendatei; Theme-Überschreibung unter `bw-woo-membership/dashboard/<name>.php` |
| `bw_wm_page_url` | Filter | Adresse fester Seiten (`$url`, `$page`: `area` = `/mitgliederbereich-uebersicht/`, `membership` = `/mitgliedschaft/`, `events` = `/veranstaltungen/`); zentral in `MemberPages` |
| `bw_wm_shop_replacement_url` | Filter | Ziel statt der WooCommerce-Shopseite (Standard: Veranstaltungen) |

## Geschützte Dateien unter nginx

nginx liest keine `.htaccess`. Die Dateinamen sind zufällig, die Adresse ist also nicht zu erraten; für echten Schutz in der Server-Konfiguration ergänzen:

```nginx
location ^~ /wp-content/uploads/bw-member-docs/ { deny all; return 403; }
location ^~ /wp-content/uploads/bw-member-proofs/ { deny all; return 403; }
```

Bei Unterverzeichnis-Installationen den Pfad anpassen (z. B. `/wp/2603b-appa/wp-content/uploads/bw-member-docs/`).

## Entwicklung

`composer install`, `composer test` (PHPUnit + Brain Monkey), `composer lint`.

Backend-App: `npm install`, `npm run build` (erzeugt `build/`, nicht im Git). Vor jedem ZIP bauen; ins ZIP gehören `build/`, nicht aber `node_modules/`, `src-js/`, `tests/`, `vendor/`.
