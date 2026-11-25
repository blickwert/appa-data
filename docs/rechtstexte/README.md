# Rechtstexte (Entwürfe)

> **Entwurf – vor Veröffentlichung rechtlich prüfen lassen.**

Stand: 24. November 2026. Die Texte in diesem Ordner sind Entwürfe von Claude nach österreichischem Recht. Sie ersetzen keine Rechtsberatung und müssen vom Vorstand der APPA und von einer Juristin bzw. einem Juristen geprüft werden, bevor sie online gehen. Kästen mit „Prüfhinweis“ sind interne Hinweise und werden nicht veröffentlicht.

## Dateien und Ziel auf der Website

| Datei | Inhalt | Seite (Slug-Vorschlag) |
|---|---|---|
| `agb-beitrag-veranstaltungen.md` | Beitrags- und Teilnahmebedingungen: Beiträge, Zahlung, Verlängerung, Kündigung, Studiennachweis, Veranstaltungen, Storno, Rücktrittsrecht (FAGG), Haftung | `/beitragsbedingungen/` – neue Seite; verlinken in der Kasse („Ich habe die Beitrags- und Teilnahmebedingungen gelesen“, WooCommerce › Einstellungen › Erweitert › Seite mit AGB), im Fuß unter „Rechtliche Hinweise“ und auf der Seite Mitgliedschaft |
| `datenschutz-ergaenzungen.md` | Abschnitte für die Datenschutzerklärung: Kundenkonto, Stripe, Mitgliederdaten, Studiennachweis, Mitgliederverzeichnis, Veranstaltungen, Warteliste, Zoom, E-Mails, Speicherdauer; dazu Grundteile (Hosting, Kontaktformular, Turnstile) | `/datenschutz/` – bestehende Seite; Text kommt in `PRIVACY_BODY` in `scripts/build_patterns.py` (Pattern `privacy.php` ist erzeugt) |

Die Seite `/beitragsbedingungen/` braucht einen Eintrag in `scripts/build_patterns.py` und `scripts/import_pages.py` wie die Datenschutzseite.

## Platzhalter zum Ausfüllen

Alle Stellen sind im Text als `[Platzhalter: …]` markiert.

**Vereinsdaten und Statuten**
- ZVR-Zahl, Domain, Link zum Statuten-PDF
- Beschluss der Generalversammlung zur Beitragshöhe
- Statuten an den Beitritt ohne Vorstandsfreigabe anpassen (Absatz 3.3, Prüfhinweis), Schlichtungseinrichtung, Folgen bei Zahlungsrückstand
- Umsatzsteuer auf Mitgliedsbeiträge (Steuerberatung)

**Beiträge und Mitgliedschaft**
- Frist für die Ankündigung von Beitragsänderungen und Änderungen der Bedingungen
- Frist der SEPA-Vorabankündigung, Kosten einer Rücklastschrift
- Kündigung im Konto nach dem 30. November: fristgerecht oder nicht
- Gültigkeit des Studiennachweises, anerkannte Hochschulen, Folge unrichtiger Angaben
- Wertersatz bei Rücktritt von der Mitgliedschaft (Empfehlung: verzichten)

**Veranstaltungen**
- Storno: entschieden (bis 14 Tage vorher volle Erstattung, keine Staffel); offen nur Ersatzperson und Erstattung bei Nachbesetzung
- Mindestteilnehmerzahl, Frist für unbezahlte Anmeldungen
- Aufzeichnung von Online-Veranstaltungen, Anzeigename bei Zoom
- Weitere Felder bei der Anmeldung und in der Teilnehmerliste
- Teilnahme an Verbraucherschlichtung, Gerichtsstand Graz

**Datenschutz**
- [Hosting-Anbieter]: Name, Sitz, Serverstandort, Log-Speicherdauer
- E-Mail-Versand (Mailserver/SMTP-Dienst), E-Mail für Datenschutzanfragen
- Liste der Cookies auf der Live-Seite
- Auftragsverarbeitungsverträge: Hosting, Cloudflare, Zoom, blickwert
- Speicherfristen (Tabelle), Aufbewahrung von Mitgliedsnummer und Prüfvermerk
- Ausblenden des Verzeichniseintrags nach Austritt (technisch prüfen)
- Austragen von der Warteliste
- Newsletter (falls geplant), Fragebogen-Werkzeuge (vor Inbetriebnahme)
- Datum „Stand“ beider Texte

## Rechtliche Punkte, die besonders zu prüfen sind

- Ist die APPA bei diesen Verträgen Unternehmerin nach § 1 Abs 2 KSchG (dann gilt das FAGG)?
- Rücktrittsrecht bei Beitritt zum Verein; Ausschluss nach § 18 Abs 1 Z 10 FAGG für terminierte Veranstaltungen (Fortbildung und Online-Formate).
- Automatische Verlängerung und Kündigungsfrist (§ 6 Abs 1 Z 2, § 15 KSchG) im Abgleich mit den Statuten.
- Turnstile und Stripe-Skripte im Verhältnis zu § 165 Abs 3 TKG 2021.
