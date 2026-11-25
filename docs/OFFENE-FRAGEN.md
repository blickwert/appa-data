# Offene Fragen an APPA / David

Stand: 24. November 2026 (Antworten von David eingetragen und umgesetzt, siehe Pfeile →). Jede Frage hat eine **Arbeitsannahme**: So ist es umgesetzt, bis eine Antwort kommt. Antworten bitte direkt hier eintragen (Spalte „Antwort“), dann passt Claude die Umsetzung an.

Die Annahmen wurden mit einem zweiten Claude-Agenten gegengeprüft (Abgleich mit den Besprechungen vom 2. und 9. 10. 2026, `docs/MITGLIEDERBEREICH.md` und den Architektur-Skills).

## Mitgliedschaft

| # | Frage | Arbeitsannahme (umgesetzt) | Antwort |
|---|---|---|---|
| M1 | Muss eine neue Mitgliedschaft vom Vorstand freigegeben werden? | Beitritt direkt über die Kasse (kein Antragsformular mehr); wer bezahlt, hat sofort Zugang. Laut Statuten entscheidet der Vorstand über die Aufnahme – deshalb Hinweis auf der Seite: „lehnt er sie ab, erstatten wir den Beitrag“. Bitte bestätigen. | **David, 24. 11.:** Nein. Freigabe nur bei Organisationen (über das Kontaktformular). → umgesetzt: Texte ohne Freigabe, Organisationen über das Kontaktformular (Betreff vorbelegt). |
| M2 | Studierende beim Beitritt: erst Nachweis, dann Kauf – oder Kauf mit Nachweis-Frist? | Kauf möglich, Nachweis innerhalb von 30 Tagen im Mitgliederbereich hochladen. Ohne gültigen Nachweis ist die nächste Verlängerung die ordentliche Mitgliedschaft. | **David, 24. 11.:** Nachweis bei der Buchung erforderlich; ohne Nachweis ordentliche Mitgliedschaft. → umgesetzt: Seite /studiennachweis/ vor der Kasse, Kasse lehnt ohne Nachweis ab. |
| M3 | Wer prüft Nachweise und verwaltet Mitglieder? Wohin gehen Benachrichtigungen? | Rolle „Shop-Manager“ (und Administrator) mit eigener Berechtigung „Mitglieder verwalten“. Benachrichtigungen an die Admin-E-Mail (derzeit bw-cms@blickwert.at, später info@appa.net). | **David, 24. 11.:** Shop-Manager und Admin. |
| M4 | Bei Umstellung Studierende → ordentlich: Differenz sofort nachzahlen? | Nein, erst ab der nächsten Verlängerung. | **David, 24. 11.:** Wechsel ab der nächsten Verlängerung, mit oder ohne Nachweis. |
| M5 | Institutionelle Mitglieder: Inhalte, Preis, Zahlung? | Manuelle Mitgliedschaft im Backend; Rechnung bei Bedarf über eine WooCommerce-Bestellung, die das Office anlegt. | **David, 24. 11.:** Ja. |
| M6 | Staffel beim ersten Beitrag (ab Juni ½, ab Oktober ⅓, Dezember gratis) | Kommt mit WooCommerce Subscriptions: Verlängerung synchron zum 1. 1., Staffel als Abzug beim ersten Beitrag (nicht als geänderter Preis, sonst würde zum ermäßigten Preis verlängert). Bis dahin voller Beitrag, Dezember-Beitritte gelten bis Ende des Folgejahres. | **David, 24. 11.:** Wie besprochen nach Beitrittsmonat. → umgesetzt: Abzug beim ersten Beitrag (½ ab Juni, ⅓ ab Oktober). |
| M7 | Abo-Plugin: welches – oder ohne? | Empfehlung: **WooCommerce Subscriptions** (ca. 279 $/Jahr) – Verlängerung zum 1. 1., Stripe-Abbuchung und Überweisung, vom Plugin `bw-woo-membership` schon unterstützt. **Milo Subscriptions 1.8.41 geprüft (24. 11. 2026, Code und Oberfläche):** Verlängerung zum Stichtag im Kern (eine Einstellung für alle Abos); bei Abos im Warenkorb lässt der kostenlose Kern nur „Milo Payments“ zu (1 % Gebühr, keine Überweisung, nicht das vorhandene Stripe-Plugin); Überweisung und vorhandenes Stripe nur mit Add-on „Beliebiges Gateway“ (29 $/Monat laut Add-on-Liste, also teurer als das offizielle Plugin). Staffel beim ersten Beitrag in keinem Plugin – eigener Code. Ohne Abo-Plugin: Verlängerung mit Erinnerung und Bezahl-Link. Automatische Abbuchung selbst programmieren: nein. | **Entschieden (David, 24. 11. 2026): WooCommerce Subscriptions.** Milo auf 2603b deaktiviert. |

## Veranstaltungen

| # | Frage | Arbeitsannahme (umgesetzt) | Antwort |
|---|---|---|---|
| V1 | Bekommt man den Mitgliederpreis, wenn die Mitgliedschaft im selben Warenkorb liegt? | Nein – Mitgliederpreis nur mit bereits aktiver Mitgliedschaft (einfach und eindeutig). Hinweis im Warenkorb: erst Mitglied werden, dann anmelden. | **David, 24. 11.:** Nur mit aktiver Mitgliedschaft. |
| V2 | Nach wie vielen Tagen wird eine unbezahlte Anmeldung (Überweisung) storniert? | 7 Tage, mit E-Mail an die Person. | Mit „nur Stripe“ im Live-Betrieb kaum noch relevant (betrifft nur offene Zahlungen); Regel bleibt als Absicherung. |
| V3 | Eine Anmeldung pro Person oder auch für Dritte? | Nur für sich selbst, eine Anmeldung pro Konto. | **David, 24. 11.:** Ja, nur für sich selbst. |
| V4 | Dürfen Veranstaltungen für Mitglieder gratis sein (Gemeinnützigkeit, Steuerberatung)? | Technisch vorgesehen (Art „für Mitglieder gratis“); die Entscheidung bleibt bei APPA. | **David, 24. 11.:** Aktuell nicht. → Option im Editor ausgeblendet. |
| V5 | Wo liegen Aufzeichnungen (Video-Plattform), wer lädt hoch, wer sieht sie? | Noch nicht gebaut. Vorschlag: Vimeo (privat, Domain-Freigabe), sichtbar für alle aktiven Mitglieder. | **David, 24. 11.:** Muss geklärt werden, aktuell nicht. |
| V6 | Anmeldefrist, Warteliste, Gruppenanmeldung? | Nicht gebaut. Vergangene Termine sind nicht mehr buchbar; ausgebucht = nicht buchbar. | **David, 24. 11.:** Bauen. → umgesetzt: Anmeldeschluss und Warteliste (Gruppenanmeldung entfällt wegen V3). |
| V7 | Anmeldeablauf wie bei der WKO: Anmelden mit Namen, Bestätigung, Erinnerung, selbst abmelden – und bei kostenpflichtigen Veranstaltungen? | **Umgesetzt (25. 11. 2026, Vorschlag):** Alles läuft über WooCommerce. Kostenlos + angemeldet: „Jetzt anmelden“ mit einem Klick unter dem eigenen Namen (0-€-Bestellung). Kostenpflichtig oder ohne Konto: über die Kasse (Stripe). Bestätigungs-E-Mail mit Kalenderdatei (.ics), Erinnerung X Tage vorher (Feld, Standard 2; bei Online-Terminen mit Zoom-Link). Selbst abmelden auf der Veranstaltungsseite und unter „Meine Anmeldungen“: kostenlos bis zum Termin; kostenpflichtig bis X Tage vorher mit voller Erstattung (Feld, Standard 14; Stripe erstattet automatisch, bei Überweisung E-Mail „Erstattung nötig“ ans Office); danach Hinweis auf die Office-Adresse. | **Bitte bestätigen:** Standardfristen 14 Tage (Storno mit voller Erstattung) und 2 Tage (Erinnerung)? Gibt es eine Storno-Staffel (z. B. 50 % bis 7 Tage vorher) oder Ersatzperson? → AGB 10.1–10.3. |
| V8 | Was steht bei nicht offenen Veranstaltungen? | **Umgesetzt:** Etikett „Exklusive Veranstaltung für Mitglieder“ in Liste und Anmelde-Box; Knopf „Jetzt anmelden“ (Login) und „Mitglied werden“. Zoom-Link nie in Übersichten, nur für Angemeldete auf der Veranstaltungsseite, in „Meine Anmeldungen“ und in der Erinnerung. | – |

## Mitgliederbereich

| # | Frage | Arbeitsannahme (umgesetzt) | Antwort |
|---|---|---|---|
| B1 | Was soll die Glocke anzeigen – oder entfällt sie? | Entfällt vorerst. | **David, 24. 11.:** Ok (entfällt). |
| B2 | Welche Dokumentkategorien und Sichtbarkeiten werden gebraucht? | Kategorien frei anlegbar; Sichtbarkeit „alle Mitglieder“ oder „nur Vorstand und Office“. | **David, 24. 11.:** Nur Vorstand und Office. |
| B3 | Fragebogen-Tools (30 Anwendungen/Monat): eigener Bereich oder externes Tool? | Noch nicht gebaut (Martin bevorzugt ein externes Tool). | **David, 24. 11.:** Eigenes Tool mit anonymisierter Auswertung. → Noch nicht gebaut – eigenes Projekt (Fragebögen, anonymisierte Auswertung). |
| B4 | Studiennachweise: Datei nach der Prüfung löschen? | Ja, sofort nach Freigabe oder Ablehnung (Datenminimierung); gespeichert bleiben nur Status, gültig bis, Prüfdatum und Prüfer:in. Muss in die Datenschutzerklärung. | **David, 24. 11.:** Ja. |

## Technik und Betrieb

| # | Frage | Arbeitsannahme (umgesetzt) | Antwort |
|---|---|---|---|
| T1 | Build-Schritt für die Backend-Verwaltung (`@wordpress/scripts`) einführen? | Ja, wie im Skill vorgesehen (JSX, kein TypeScript). Vor dem ZIP `npm run build`; `build/` ist nicht im Git. | **David, 24. 11.:** Ja. |
| T2 | Seiten-Cache beim Hosting: werden angemeldete Nutzer:innen ausgenommen? | Wird angenommen (übliche Einstellung). Bitte beim Hosting prüfen, sonst sehen Mitglieder falsche Preise. | **David, 24. 11.:** Nein (angemeldete Nutzer:innen werden nicht ausgenommen) – mit AJAX lösen. → umgesetzt: persönliche Seiten nicht cachebar, Anmelde-Box per AJAX. |
| T3 | nginx-Regel für geschützte Ordner | Auf 2603b nicht nötig (direkter Aufruf liefert 403). Bei Umzug auf die Live-Domain erneut prüfen. | **David, 24. 11.:** Ja. |
| T4 | Stripe verbinden, Bankdaten für Überweisung, AGB/Beitragsordnung, Datenschutzseite | Offen, Platzhalter auf 2603b. | **Entschieden (David, 24. 11.): Im Live-Betrieb nur Stripe (Karte, SEPA-Lastschrift); Überweisung wird deaktiviert.** Bankdaten für Überweisung entfallen. Offen: Stripe verbinden, AGB/Beitragsordnung, Datenschutzseite. **Nachtrag David, 24. 11.:** Später; Claude schreibt Entwürfe der Rechtsseiten. → Entwürfe: docs/rechtstexte/. |
| T6 | Buchhaltung: Wer bucht, mit welchem Programm (Steuerberater, BMD, RZL, Excel …)? Umsatzsteuer auf Mitgliedsbeiträge? | Vorschlag: WooCommerce ist das Nebenbuch je Mitglied; die Buchhaltung bucht nur Summen (Stripe je Auszahlung, Überweisungen je Monat). Zahlung bevorzugt per Stripe (Karte, SEPA-Lastschrift), Überweisung mit Bestellnummer als Verwendungszweck. Automatische Zahlungsbestätigung als PDF und ein monatlicher Export „Beitragsjournal“ (CSV) aus dem Plugin. Beiträge ohne Umsatzsteuer, falls echter Mitgliedsbeitrag eines gemeinnützigen Vereins – vom Steuerberater bestätigen lassen. | **Entschieden (David, 24. 11.): Empfehlung umsetzen.** Beitragsjournal (CSV je Monat) ist eingebaut (Mitglieder › Beitragsjournal); Zahlungsbestätigung als PDF kommt mit WooCommerce Subscriptions. Offen bleibt: Programm des Steuerberaters, Umsatzsteuer bestätigen. **Nachtrag David, 24. 11.:** Noch nicht geklärt – deshalb Sammelexport über beliebigen Zeitraum gewünscht. → umgesetzt: Beitragsjournal mit frei wählbarem Zeitraum. |
| T5 | Turnstile-Schlüssel, endgültiger Formular-Empfänger, Statuten-PDF, ZVR-Zahl | Offen. | **David, 24. 11.:** Später. |
