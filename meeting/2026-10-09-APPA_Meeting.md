# APPA Meeting 2026-10-09 09:30
TeilnehmerInnen: David, Martin, Brigitte

## Wichtigste Ergebnisse

David hat das zuvor entwickelte Konzept in eine funktionierende Website umgesetzt und den Teilnehmern einen Link zur Ansicht bereitgestellt.  Der Schwerpunkt des Meetings lag auf der technischen Umsetzung des Mitgliedsbeitragssystems mit WooCommerce und einem kostenpflichtigen Plugin für wiederkehrende Zahlungen sowie auf der Strukturierung unterschiedlicher Mitgliedsstufen.  Zentrale Entscheidungen wurden zu Zahlungsabwicklung, studentischer Mitgliedschaft, Veranstaltungsmanagement und dem Aufbau eines öffentlichen Mitgliederverzeichnisses getroffen. Das Angebot von David wird entsprechend dem erweiterten Projektumfang angepasst.

## Getroffene Entscheidungen

### Zahlungssystem

WooCommerce (kostenlos, da vom selben Anbieter wie WordPress) wird als Shop-Lösung eingesetzt, ergänzt durch ein Plugin für wiederkehrende Zahlungen zum Preis von 280 € pro Jahr.

Das kostenpflichtige Plugin wird gegenüber kostenlosen Alternativen bevorzugt, da diese 2 % pro Buchung verrechnen – bei wachsender Mitgliederzahl deutlich teurer.

Stripe wird als Zahlungsanbieter für automatische Zahlungen eingesetzt.

Zahlungswiederholung ist standardmäßig auf dreimalige Versuche eingestellt.

Alle Datensätze können gesammelt exportiert werden – wichtig für die Steuer, da sonst jede E-Mail einzeln geprüft werden müsste.

### Staffelung der Mitgliedsbeiträge

Eigenprogrammierung (~8 Stunden) erforderlich für spezielle Preisregeln: z. B. ab Juni halber Beitrag, ab Oktober ein Drittel, Dezember gratis.

Der geschützte Bereich wird ebenfalls selbst programmiert, da er sich regelmäßig erweitern soll und kein bestehendes Plugin die Anforderungen abdeckt.

### Mitgliedsstufen

Folgende Stufen wurden definiert: ordentliches Mitglied, studentisches Mitglied, Ehrenmitglied (überschaubar) und institutionelles Mitglied (Organisation).

Studenten erhalten denselben Inhalt im geschützten Bereich wie ordentliche Mitglieder, jedoch zu einem reduzierten Beitrag.

Institutionelle Mitgliedschaften werden manuell bearbeitet (geringe Anzahl erwartet); Interessenten kontaktieren den Vorstand direkt.

### Überprüfung studentischer Mitgliedschaft

Kein API zur Matrikelnummernprüfung möglich, da Matrikelnummern personenbezogene Daten sind.

Studenten müssen jährlich eine aktuelle Inskriptionsbestätigung hochladen; ohne Nachweis wird die Mitgliedschaft automatisch in eine ordentliche Mitgliedschaft umgewandelt.

Ablauf: Student lädt Dokument im geschützten Bereich hoch → Verein erhält E-Mail → nach Bestätigung erhält Student einen Freischaltungslink per E-Mail.

### Veranstaltungsstruktur

Drei Veranstaltungstypen werden implementiert: geschlossene Events (nur Mitglieder), offene Events (Mitglieder mit Rabatt, Nichtmitglieder zahlen vollen Preis), kostenlose Events (für Mitglieder gratis, Nichtmitglieder zahlen).

Startpunkt: nur die APPA-Tagung und das Mitglieder/Nichtmitglieder-Modell; weitere Veranstaltungsformen werden später ergänzt.

Bei der Tagung (geplant für Oktober nächsten Jahres) können sich Mitglieder mit reduzierten Preisen anmelden; Nichtmitglieder zahlen den vollen Preis.

Die rechtliche Frage, ob als gemeinnütziger Verein ausschließlich kostenpflichtige Veranstaltungen zulässig sind, muss noch mit dem Steuerberater abgeklärt werden.

### Mitgliederverzeichnis

Das Mitgliederverzeichnis mit fachlichen Schwerpunkten und Kontaktdaten soll öffentlich zugänglich sein, um Professionisten Sichtbarkeit zu geben.

Der Mitgliederbereich muss nicht in der Hauptnavigation erscheinen – die Fußnavigation reicht aus.

### Gemeinnützigkeit & Offenheit

Der Verein muss für alle Interessierten an Positiver Psychologie offen sein; hochselektive Aufnahmekriterien (z. B. bestimmte Studienabschlüsse) wären nicht mit dem gemeinnützigen Status vereinbar.

Einzige zulässige Überprüfung: Studiennachweis für die studentische Mitgliedschaft.

## Offene Fragen

Rechtliche Klärung mit dem Steuerberater: Sind ausschließlich kostenpflichtige Veranstaltungen für einen gemeinnützigen Verein zulässig?

Gestaltung der institutionellen Mitgliedschaft (Inhalte, Preise, Zugang) noch nicht festgelegt.

Ob Fragebögen im Mitgliederbereich herunterladbar sein sollen oder über ein externes Tool (mit Datenverwaltung) bereitgestellt werden – Martin präferiert ein Tool.

## Ausstehende Bestätigungen

Inhaltliche Freigabe der Website: Brigitte und Michi müssen Texte, Layout und Inhalte (inkl. KI-generierter Texte im Bereich Positive Psychologie) in 2–3 Feedbackschleifen prüfen und Feedback gesammelt in einem Dokument zusammenfassen.

Farbgebung: Mehr Gelb auf der Website fehlt noch und muss ergänzt werden.

Anpassung des Angebots: David passt das bestehende Angebot an den erweiterten Projektumfang an.

## Maßnahmen

David: Detaillierte Beschreibung der zusätzlich benötigten Programmierung und des Leistungsumfangs des Plugins an Brigitte und Martin senden.

David: Angebot entsprechend dem geänderten Projektumfang anpassen.

David: Beispiel-Veranstaltungsseite erstellen und Struktur (Kategorien, Anmeldeprozess, Preismodelle) definieren.

David: Automatische Erinnerungs-E-Mail mit Frist (ca. 3–4 Wochen) vor Ablauf der studentischen Mitgliedschaft implementieren; bei fehlendem Nachweis automatische Umstellung auf ordentliche Mitgliedschaft.

Brigitte & Martin (+ Michi): Website-Inhalte prüfen, Feedback gesammelt in einem Dokument an David übermitteln.

## Nächster Termin

Donnerstag (David und Martin treffen sich); weiterer Termin mit David wird nach Abschluss der inhaltlichen Feedbackschleifen vereinbart.
