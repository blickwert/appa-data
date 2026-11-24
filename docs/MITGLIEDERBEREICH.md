# Mitgliederbereich: statische und dynamische Elemente

Stand: 24. November 2026. Grundlage: die Vorschau im Theme (`page-member`, `page-member-dashboard`, Pattern `member-login`, `member-dashboard`) und die Besprechungen vom 2. und 9. Oktober 2026. Die Funktion kommt mit dem eigenen Plugin `bw-woo-membership` (WooCommerce + Subscriptions, Stripe).

## Umsetzungsstand

| Stufe | Inhalt | Stand |
|---|---|---|
| 1 | Plugin `bw-woo-membership`: Status aus Bestellung bzw. Abo, Mitgliedsnummer, geschützte Übersicht, Login-Weiterleitung, Blöcke (Kopf, Übersicht, Veranstaltungen, Dokumente, Profil), Profil und Verzeichnis-Einwilligung, Mitgliedsbestätigung zum Drucken, Mitglieder-Dokumente mit geschütztem Download | umgesetzt (24. 11. 2026) |
| 2 | Verwaltung im Backend wie Mockup: Mitgliederliste, Mitglied bearbeiten (Ehren-/institutionelle Mitglieder), Studiennachweis hochladen, prüfen, erinnern, umstellen | umgesetzt (24. 11. 2026, Plugin 0.2.0) |
| 3 | Veranstaltungen: Feld „Art“ (nur Mitglieder / offen / für Mitglieder gratis), Anmeldung über verstecktes WooCommerce-Produkt mit Mitgliederpreis, „Meine Anmeldungen“, Teilnehmerliste | umgesetzt (24. 11. 2026, Plugin 0.3.0, `bw-appa` 0.2.0); Aufzeichnungen offen (Frage V5) |
| – | Beitritt über die Kasse statt Antragsformular: Buttons „Gründungsmitglied werden“/„Als Student:in beitreten“ öffnen die Kasse, Antragsfelder in der Kasse | umgesetzt (24. 11. 2026, Plugin 0.4.0) |
| – | WooCommerce Subscriptions (Lizenz) für automatische Verlängerung; Staffel Juni ½ / Oktober ⅓ / Dezember gratis beim ersten Beitrag | offen |

Ohne WooCommerce Subscriptions gilt eine bezahlte Mitgliedschaftsbestellung für das Kalenderjahr (Dezember: bis Ende des Folgejahres).

## 1. Statische Elemente (Theme, ändern sich nicht pro Mitglied)

| Element | Wo gepflegt |
|---|---|
| Aufbau: Kopf, Seitenleiste, Fuß, Abstände, Farben | Theme (Vorlagen, Vorlagenteile, `member.css`) |
| Navigation (Übersicht, Veranstaltungen, Dokumente, Profil), Hilfe-Box, „Zur öffentlichen Website“ | Theme, Pattern `member-sidebar` |
| Überschriften und Beschriftungen: „Meine Mitgliedschaft“, „Status“, „Mitgliedschaftsart“, „Nächste Verlängerung“, „Veranstaltungen für Mitglieder“, „Dokumente & Downloads“, „Mein Profil“ | Seiteninhalt (Block-Editor) |
| Login-Seite: Texte, Hinweis „Nur für APPA-Mitglieder“, Link „Informationen zur Mitgliedschaft“ | Seiteninhalt (Block-Editor) |
| Anmeldeformular | WordPress (`core/loginout`) |

## 2. Dynamische Platzhalter (pro angemeldetem Mitglied) und ihre Quelle

| Platzhalter in der Vorschau | Quelle | Bemerkung |
|---|---|---|
| Name, Foto im Kopf; „Willkommen zurück, Anna“ | WordPress-Benutzer (Vorname, Anzeigename, Profilfoto) | Foto: eigenes Feld (Upload), nicht Gravatar (Datenschutz) |
| „Ordentliches Mitglied“ (Kopf), Karte „Mitgliedschaftsart“ | WooCommerce Subscriptions: abonniertes Produkt bzw. Variante | Arten laut Meeting 9. 10.: ordentlich, studentisch, Ehrenmitglied, institutionell |
| Abzeichen „Mitgliedschaft aktiv“, Karte „Status: Aktiv“ | Status des Abos: aktiv / pausiert (Zahlung offen) / gekündigt zum Laufzeitende / abgelaufen | Bei offener Zahlung: 3 automatische Versuche, danach Zugang vorübergehend gesperrt (Meeting 2. 10.) |
| „Mitglied seit 2023“ | eigenes Benutzerfeld `bw_member_since` | Bleibt bei Austritt und Wiedereintritt erhalten, wie die Mitgliedsnummer |
| Mitgliedsnummer (noch nicht in der Vorschau) | eigenes Benutzerfeld, automatisch fortlaufend | Meeting 2. 10.: bleibt bei Austritt/Wiedereintritt bestehen |
| „Jahresbeitrag entrichtet“ | letzte Verlängerungs-Bestellung des Abos (bezahlt / offen) | |
| „Nächste Verlängerung: 01. Jänner 2027“, „Automatische Verlängerung“ | Abo: nächstes Zahlungsdatum, Zahlungsart (automatisch per Stripe oder manuell) | Staffel ab Juni ½, ab Oktober ⅓, Dezember gratis betrifft nur den ersten Beitrag (Eigenprogrammierung) |
| Studierende: Frist für den Studiennachweis (noch nicht in der Vorschau) | eigenes Benutzerfeld (Ablaufdatum des Nachweises) | Upload im Mitgliederbereich, Freigabe durch den Verein, Erinnerung 3–4 Wochen vorher, sonst automatisch ordentliche Mitgliedschaft |
| „Mitgliedsbestätigung herunterladen (PDF)“ | wird beim Klick aus den Daten oben erzeugt (Name, Nummer, Art, Zeitraum) | Rechnungen kommen aus WooCommerce (PDF-Rechnungs-Plugin), nicht aus dieser Bestätigung |
| „Veranstaltungen für Mitglieder“ (Liste) | Beitragstyp `appa_event` (Plugin `bw-appa`), nur kommende Termine | Neues Feld „Zugang“: nur Mitglieder / offen mit Mitgliederpreis / für Mitglieder gratis (Meeting 9. 10.) |
| Schlagworte „Exklusiv für Mitglieder“, „Online“ | Felder des Termins (Zugang, Format: online / vor Ort) | |
| „Anmelden“ beim Termin | Buchung: WooCommerce-Produkt je Veranstaltung mit Mitgliederpreis | Mitglieder sehen den ermäßigten oder Gratis-Preis automatisch |
| „Aufzeichnung … Ansehen“ | Feld am vergangenen Termin: Video-Link, Dauer | Offen: wo die Videos liegen (z. B. Vimeo privat, nicht öffentlich auf YouTube) |
| „Dokumente & Downloads“ | neuer Beitragstyp „Mitglieder-Dokument“ (siehe Abschnitt 3) | Dateityp und Größe („PDF · 4,2 MB“) werden aus der Datei gelesen |
| „Im öffentlichen Verzeichnis sichtbar“ (Schalter) | Benutzerfeld mit Einwilligung (Opt-in), Standard aus | DSGVO: ausdrückliche Zustimmung, jederzeit widerrufbar |
| „Profil bearbeiten“ | Formular im Mitgliederbereich: Titel, Kontakt, Website, Schwerpunkte, Fachgruppen, Kurzprofil, Foto | Dieselben Daten speisen das öffentliche Mitgliederverzeichnis (Meeting 2. 10.) |
| Glocke (Benachrichtigungen) | noch nicht definiert | Vorschlag: weglassen, bis klar ist, was dort erscheinen soll |

## 3. Verwaltung der Dokumente (Vorschlag)

- **Backend:** Menüpunkt „Mitglieder-Dokumente“ (eigener Beitragstyp im Plugin). Pro Dokument: Titel, Datei, Kategorie (z. B. Tagung, Jahresbericht, Vereinsunterlagen), sichtbar für (alle Mitglieder / bestimmte Mitgliedsarten / Vorstand), Datum. Reihenfolge nach Datum, wichtige Dokumente anheftbar.
- **Anzeige:** Die Übersicht zeigt die neuesten drei, „Alle“ führt zu einer Liste mit Filter nach Kategorie.
- **Schutz:** Dateien in `wp-content/uploads` sind sonst über ihre Adresse für alle abrufbar. Daher eigener Ordner, der direkt nicht erreichbar ist; der Download läuft über das Plugin, das vorher Anmeldung, aktives Abo und Sichtbarkeit prüft. Auf dem Server prüfen, ob Apache (`.htaccess` wirkt) oder nginx (eigene Regel nötig).
- **Statuten:** öffentlich (Über uns) und zusätzlich im Mitgliederbereich – als normale Mediendatei öffentlich, nicht geschützt.

## 4. Wie Inhalte angelegt werden

| Inhalt | Wer | Wie |
|---|---|---|
| Texte und Aufbau der Seiten Mitgliederbereich und Übersicht | blickwert | Block-Editor (Seite) bzw. Repo (`scripts/build_patterns.py`) |
| Dynamische Bereiche | Plugin | Eigene Blöcke (z. B. „Mitgliedschaft“, „Veranstaltungen für Mitglieder“, „Dokumente“, „Profil“), die serverseitig die Daten des angemeldeten Mitglieds ausgeben. Sie ersetzen die statische Vorschau, Position und Überschriften bleiben im Editor änderbar |
| Mitglieder | automatisch | Beitritt über die Website: Mitgliedschaft kaufen → bezahlt = Mitglied (keine Freigabe durch den Vorstand, siehe Frage M1); Studierende laden danach den Nachweis hoch. Ehren- und institutionelle Mitglieder legt das Office von Hand an (Abo ohne bzw. mit manuellem Betrag) |
| Veranstaltungen | Office / Vorstand | Veranstaltungen › Neu (bestehend) plus Felder Zugang, Format, Preise, Aufzeichnung |
| Dokumente | Office / Vorstand | Mitglieder-Dokumente › Neu: Datei hochladen, Kategorie und Sichtbarkeit wählen |
| Profil und Verzeichnis-Eintrag | Mitglied selbst | „Profil bearbeiten“ im Mitgliederbereich |

**Schutz der Seiten:** Die Vorlage `page-member-dashboard` zeigt Inhalte nur angemeldeten Mitgliedern mit aktivem Abo; alle anderen werden zur Anmeldung umgeleitet. Nach dem Login geht es direkt zur Übersicht. Rechnungen, Zahlungsart und Kündigung bleiben im WooCommerce-Bereich „Mein Konto“ und werden aus der Übersicht verlinkt.

## Offene Fragen an APPA

Gesammelt mit Arbeitsannahmen in [`OFFENE-FRAGEN.md`](OFFENE-FRAGEN.md).
