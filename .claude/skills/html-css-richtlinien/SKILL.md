---
name: html-css-richtlinien
description: Regeln für sauberes, klassenbasiertes HTML und CSS mit einheitlichen Design-Tokens (Farben, Schriften, Spacing, Rahmen, Buttons), übertragbar in ein WordPress-Block-Theme (theme.json). Verwenden, wenn HTML/CSS für Webseiten, Landingpages, Design-Vorschauen oder WordPress-Themes und -Plugins geschrieben oder überarbeitet wird.
---

# HTML & CSS schreiben – Richtlinien

Oberstes Ziel: **Konsistenz.** Jede Definition (Farbe, Schrift, Abstand, Rahmen, Button) existiert genau einmal und ist auf allen Seiten identisch. Der Code muss ohne Umbau in ein WordPress-Block-Theme übernommen werden können: Tokens werden zu `theme.json`-Presets (siehe Skill `wp-theme-architecture`).

## 1. Keine Inline-Styles

- Im HTML **kein** `style="..."`. Styling läuft ausschließlich über Klassen.
- Ausnahme nur, wenn technisch unvermeidbar (z. B. dynamisch berechnete Werte wie ein Fortschrittsbalken `--progress: 70%` als Custom Property). Dann nur die Variable setzen, nie fertige Eigenschaften.
- Keine `<style>`-Blöcke im Body verstreuen. CSS gehört gesammelt in ein Stylesheet (oder einen einzelnen Block im Head).
- Kein `!important`, außer zum bewussten Überschreiben von Core- oder Plugin-Defaults, die sich anders nicht erreichen lassen, und dann kommentiert.

## 2. Wiederholung = eigene Klasse

- Sobald ein Stil an mehr als einer Stelle vorkommt, wird er als Klasse definiert, nicht am Element wiederholt.
- Zwei Ebenen verwenden:
  - **Komponenten-Klassen** für wiederkehrende Bausteine: `.bw-card`, `.bw-button`, `.bw-section`, `.bw-hero`
  - **Modifier/Utilities** für Varianten: `.bw-button--secondary`, `.bw-section--dark`, `.bw-mt-m`
- Mehrere Klassen pro Element sind ok und erwünscht (`class="bw-card bw-card--highlight"`), statt Sonderfälle neu zu stylen.
- Benennung: Präfix `bw-`, Kleinbuchstaben, Bindestriche, BEM-nah (`.bw-card__title`, `.bw-card--highlight`). Namen beschreiben **Funktion**, nicht Aussehen (`.bw-button--primary`, nicht `.bw-blue-button`).
- Selektoren flach halten: maximal 2 Ebenen, keine ID-Selektoren, keine Selektoren, die an der DOM-Struktur hängen (`div > div > p`).

## 3. Design-Tokens als CSS Custom Properties

Alle Werte kommen aus Variablen im `:root`. Im restlichen CSS stehen **keine** Hex-Codes, Pixelwerte für Abstände oder Schriftnamen direkt.

Phase 1 (Design-Vorschau): Die Tokens tragen konkrete Werte aus dem Corporate Design (`tokens/tokens.v2.json`, erzeugt `tokens.css`). Schriftnamen und Hex-Codes stehen **nur** dort im `:root`.
Phase 2 (WordPress-Block-Theme): Aus derselben Token-Datei entsteht `theme.json`. Die `--bw-*`-Variablen zeigen dann auf die WordPress-Presets (`--bw-color-text: var(--wp--preset--color--navy)` usw.), damit das Komponenten-CSS unverändert weiterläuft. Schriften liegen lokal im Theme (`fontFace`), nie auf fremden Servern.

Das Beispiel zeigt das Prinzip. Die Stylesheets liegen in `design/v2/styles.css` bzw. `design/v2b/styles.css` (Komponenten, mobile-first, Breakpoints 561/821/1001 px). Maßgeblich sind die bestehenden Tokens in `design/v2/tokens.css` (`--bw-*`, Quelle `tokens/tokens.v2.json`); neue Tokens (z. B. Spacing, Radius) werden dort ergänzt.

```css
:root {
  /* Farben (Vorschau: feste Werte, Theme: var(--wp--preset--color--*)) */
  --bw-color-primary: #00416B;
  --bw-color-accent:  #16A5D2;

  /* Schriften (Vorschau: feste Namen, Theme: var(--wp--preset--font-family--*)) */
  --bw-font-heading: 'Gotham','Montserrat',sans-serif;
  --bw-font-body:    'Roboto',system-ui,sans-serif;

  /* Spacing-Skala: --bw-space-N = N x 0.25rem (nur diese Werte verwenden, bei Bedarf erweitern) */
  --bw-space-2:  0.5rem;
  --bw-space-4:  1rem;
  --bw-space-6:  1.5rem;
  --bw-space-10: 2.5rem;
  --bw-space-16: 4rem;

  /* Rahmen, Radius, Schatten */
  --bw-radius: 8px;
  --bw-border: 1px solid var(--bw-color-accent);
  --bw-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
}
```

Regeln:
- Es gibt nur die definierten Spacing-Stufen. Keine Zwischenwerte wie `13px` oder `2.2rem`. Wird eine Stufe gebraucht, die fehlt, wird die Skala zentral im Token-File erweitert (benannt, wiederverwendbar), nicht am Element improvisiert.
- Pro Projekt eine feste Anzahl Farben und Schriften (aus dem Design System). Neue Werte erst ins Design System aufnehmen, dann verwenden.
- Schriftgrößen ebenfalls als Skala (`--bw-text-s/m/l`, Überschriftenstufen), idealerweise fluid mit `clamp()`.

## 4. Konsistenz über alle Seiten

Diese Elemente sind projektweit **einmal** definiert und überall gleich:

- **Spacing:** Section-Padding, Container-Breite, Abstände zwischen Elementen (Skala oben)
- **Typografie:** h1–h6, Fließtext, Links, Listen
- **Buttons:** Primär, Sekundär, Outline; Padding, Radius, Hover/Focus-Zustand
- **Rahmen & Radien, Schatten**
- **Formularfelder:** Höhe, Rahmen, Focus-Zustand
- **Karten, Icons, Bilder** (Seitenverhältnisse, Radius)

Beim Erstellen einer neuen Seite zuerst vorhandene Klassen wiederverwenden. Neue Klasse nur, wenn es den Baustein noch nicht gibt, und dann so, dass andere Seiten sie ebenfalls nutzen können.

## 5. WordPress-Kompatibilität

- Gestaltung zuerst über `theme.json` (Elemente, Core-Blöcke, Presets), Komponenten-CSS nur für den Rest (siehe `wp-theme-architecture`).
- Varianten im Editor als Blockstil (`is-style-<name>`) statt freier CSS-Klassen pro Block.
- Mehrere Schriften als eigene Font-Families in `theme.json` und über Variablen ansprechen, nie per Schriftname außerhalb von `:root`/`theme.json`.
- Buttons über `core/button` + Stil-Varianten vereinheitlichen, nicht pro Block einzeln stylen.
- Farben, Schriften und Abstände im Editor nur aus den Presets wählen; freie Werte sind im Theme abgeschaltet.

## 6. HTML-Struktur

- Semantisch: `header`, `nav`, `main`, `section`, `article`, `footer`; genau ein `h1` pro Seite, Überschriften-Hierarchie nicht überspringen.
- Optik **nie** über die Tag-Wahl steuern: Ein `h3` wird nicht verwendet, weil es kleiner aussieht. Dafür gibt es Klassen (`.bw-heading-s`).
- Keine leeren Elemente oder `<br>` für Abstände; dafür Spacing-Klassen.
- Bilder mit `alt`, Links mit sprechendem Text, Buttons als `<a>` oder `<button>` korrekt.
- Barrierefreiheit: ausreichender Kontrast, sichtbarer `:focus-visible`-Zustand.

## 7. Responsive

- Mobile-first, zentrale Breakpoints einmal definieren und überall gleich verwenden. Im WordPress-Theme für Komponenten Container Queries bevorzugen.
- Layout mit Flexbox/Grid, Größen mit `rem`/`clamp()` statt fester Pixelwerte.
- Anpassungen für Breakpoints innerhalb der jeweiligen Komponentenklasse, nicht als verstreute Einzelregeln.

## 8. CSS-Organisation

Reihenfolge im Stylesheet:
1. Tokens (`:root`)
2. Base (Reset, Typografie, Links)
3. Layout (Container, Sections, Grid)
4. Komponenten (Button, Card, Formular …)
5. Utilities/Modifier
6. Overrides für Core- oder Plugin-Defaults (kommentiert und gebündelt)

Kommentare als Abschnittsüberschriften, keine toten oder doppelten Regeln.

## Checkliste vor der Ausgabe

- [ ] Kein `style=""` im HTML
- [ ] Wiederholte Stile sind Klassen
- [ ] Keine Hex-Werte, Schriftnamen oder freien Pixelabstände außerhalb von `:root`
- [ ] Farben und Schriften kommen aus Tokens (im Theme über `theme.json`-Presets)
- [ ] Keine Schriften oder Skripte von fremden Servern
- [ ] Spacing, Radius, Rahmen, Buttons entsprechen den bestehenden Definitionen
- [ ] Klassennamen mit `bw-` Präfix und funktional benannt
- [ ] Semantische Tags, Überschriftenhierarchie korrekt
- [ ] Mobile geprüft
