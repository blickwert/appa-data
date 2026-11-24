# Offene Fragen an APPA / David

Stand: 24. November 2026. Jede Frage hat eine **Arbeitsannahme**: So ist es umgesetzt, bis eine Antwort kommt. Antworten bitte direkt hier eintragen (Spalte „Antwort“), dann passt Claude die Umsetzung an.

Die Annahmen wurden mit einem zweiten Claude-Agenten gegengeprüft (Abgleich mit den Besprechungen vom 2. und 9. 10. 2026, `docs/MITGLIEDERBEREICH.md` und den Architektur-Skills).

## Mitgliedschaft

| # | Frage | Arbeitsannahme (umgesetzt) | Antwort |
|---|---|---|---|
| M1 | Muss eine neue Mitgliedschaft vom Vorstand freigegeben werden? | Beitritt direkt über die Kasse (kein Antragsformular mehr); wer bezahlt, hat sofort Zugang. Laut Statuten entscheidet der Vorstand über die Aufnahme – deshalb Hinweis auf der Seite: „lehnt er sie ab, erstatten wir den Beitrag“. Bitte bestätigen. | |
| M2 | Studierende beim Beitritt: erst Nachweis, dann Kauf – oder Kauf mit Nachweis-Frist? | Kauf möglich, Nachweis innerhalb von 30 Tagen im Mitgliederbereich hochladen. Ohne gültigen Nachweis ist die nächste Verlängerung die ordentliche Mitgliedschaft. | |
| M3 | Wer prüft Nachweise und verwaltet Mitglieder? Wohin gehen Benachrichtigungen? | Rolle „Shop-Manager“ (und Administrator) mit eigener Berechtigung „Mitglieder verwalten“. Benachrichtigungen an die Admin-E-Mail (derzeit bw-cms@blickwert.at, später info@appa.net). | |
| M4 | Bei Umstellung Studierende → ordentlich: Differenz sofort nachzahlen? | Nein, erst ab der nächsten Verlängerung. | |
| M5 | Institutionelle Mitglieder: Inhalte, Preis, Zahlung? | Manuelle Mitgliedschaft im Backend; Rechnung bei Bedarf über eine WooCommerce-Bestellung, die das Office anlegt. | |
| M6 | Staffel beim ersten Beitrag (ab Juni ½, ab Oktober ⅓, Dezember gratis) | Kommt mit WooCommerce Subscriptions: Verlängerung synchron zum 1. 1., Staffel als Abzug beim ersten Beitrag (nicht als geänderter Preis, sonst würde zum ermäßigten Preis verlängert). Bis dahin voller Beitrag, Dezember-Beitritte gelten bis Ende des Folgejahres. | |
| M7 | Abo-Plugin: welches – oder ohne? | Empfehlung: **WooCommerce Subscriptions** (ca. 279 $/Jahr) – Verlängerung zum 1. 1., Stripe-Abbuchung und Überweisung, vom Plugin `bw-woo-membership` schon unterstützt. **Milo Subscriptions 1.8.41 geprüft (24. 11. 2026, Code und Oberfläche):** Verlängerung zum Stichtag im Kern (eine Einstellung für alle Abos); bei Abos im Warenkorb lässt der kostenlose Kern nur „Milo Payments“ zu (1 % Gebühr, keine Überweisung, nicht das vorhandene Stripe-Plugin); Überweisung und vorhandenes Stripe nur mit Add-on „Beliebiges Gateway“ (29 $/Monat laut Add-on-Liste, also teurer als das offizielle Plugin). Staffel beim ersten Beitrag in keinem Plugin – eigener Code. Ohne Abo-Plugin: Verlängerung mit Erinnerung und Bezahl-Link. Automatische Abbuchung selbst programmieren: nein. | **Entschieden (David, 24. 11. 2026): WooCommerce Subscriptions.** Milo auf 2603b deaktiviert. |

## Veranstaltungen

| # | Frage | Arbeitsannahme (umgesetzt) | Antwort |
|---|---|---|---|
| V1 | Bekommt man den Mitgliederpreis, wenn die Mitgliedschaft im selben Warenkorb liegt? | Nein – Mitgliederpreis nur mit bereits aktiver Mitgliedschaft (einfach und eindeutig). Hinweis im Warenkorb: erst Mitglied werden, dann anmelden. | |
| V2 | Nach wie vielen Tagen wird eine unbezahlte Anmeldung (Überweisung) storniert? | 7 Tage, mit E-Mail an die Person. | |
| V3 | Eine Anmeldung pro Person oder auch für Dritte? | Nur für sich selbst, eine Anmeldung pro Konto. | |
| V4 | Dürfen Veranstaltungen für Mitglieder gratis sein (Gemeinnützigkeit, Steuerberatung)? | Technisch vorgesehen (Art „für Mitglieder gratis“); die Entscheidung bleibt bei APPA. | |
| V5 | Wo liegen Aufzeichnungen (Video-Plattform), wer lädt hoch, wer sieht sie? | Noch nicht gebaut. Vorschlag: Vimeo (privat, Domain-Freigabe), sichtbar für alle aktiven Mitglieder. | |
| V6 | Anmeldefrist, Warteliste, Gruppenanmeldung? | Nicht gebaut. Vergangene Termine sind nicht mehr buchbar; ausgebucht = nicht buchbar. | |

## Mitgliederbereich

| # | Frage | Arbeitsannahme (umgesetzt) | Antwort |
|---|---|---|---|
| B1 | Was soll die Glocke anzeigen – oder entfällt sie? | Entfällt vorerst. | |
| B2 | Welche Dokumentkategorien und Sichtbarkeiten werden gebraucht? | Kategorien frei anlegbar; Sichtbarkeit „alle Mitglieder“ oder „nur Vorstand und Office“. | |
| B3 | Fragebogen-Tools (30 Anwendungen/Monat): eigener Bereich oder externes Tool? | Noch nicht gebaut (Martin bevorzugt ein externes Tool). | |
| B4 | Studiennachweise: Datei nach der Prüfung löschen? | Ja, sofort nach Freigabe oder Ablehnung (Datenminimierung); gespeichert bleiben nur Status, gültig bis, Prüfdatum und Prüfer:in. Muss in die Datenschutzerklärung. | |

## Technik und Betrieb

| # | Frage | Arbeitsannahme (umgesetzt) | Antwort |
|---|---|---|---|
| T1 | Build-Schritt für die Backend-Verwaltung (`@wordpress/scripts`) einführen? | Ja, wie im Skill vorgesehen (JSX, kein TypeScript). Vor dem ZIP `npm run build`; `build/` ist nicht im Git. | |
| T2 | Seiten-Cache beim Hosting: werden angemeldete Nutzer:innen ausgenommen? | Wird angenommen (übliche Einstellung). Bitte beim Hosting prüfen, sonst sehen Mitglieder falsche Preise. | |
| T3 | nginx-Regel für geschützte Ordner | Auf 2603b nicht nötig (direkter Aufruf liefert 403). Bei Umzug auf die Live-Domain erneut prüfen. | |
| T4 | Stripe verbinden, Bankdaten für Überweisung, AGB/Beitragsordnung, Datenschutzseite | Offen, Platzhalter auf 2603b. | |
| T6 | Buchhaltung: Wer bucht, mit welchem Programm (Steuerberater, BMD, RZL, Excel …)? Umsatzsteuer auf Mitgliedsbeiträge? | Vorschlag: WooCommerce ist das Nebenbuch je Mitglied; die Buchhaltung bucht nur Summen (Stripe je Auszahlung, Überweisungen je Monat). Zahlung bevorzugt per Stripe (Karte, SEPA-Lastschrift), Überweisung mit Bestellnummer als Verwendungszweck. Automatische Zahlungsbestätigung als PDF und ein monatlicher Export „Beitragsjournal“ (CSV) aus dem Plugin. Beiträge ohne Umsatzsteuer, falls echter Mitgliedsbeitrag eines gemeinnützigen Vereins – vom Steuerberater bestätigen lassen. | |
| T5 | Turnstile-Schlüssel, endgültiger Formular-Empfänger, Statuten-PDF, ZVR-Zahl | Offen. | |
