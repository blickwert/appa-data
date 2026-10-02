# APPA – Elementor-Pro-Templates

Native Elementor-Pro-Umsetzung des UXMagic-Mockups aus `../APPA-Mockup`.
Gebaut wird ausschließlich mit Flexbox-/Grid-Containern und Standard-Widgets
(Heading, Text-Editor, Button, Image, Icon, Icon-List, Social Icons) plus den
Pro-Widgets Nav Menu, Form, Login und Share Buttons. Es gibt kein HTML-Widget
und kein Custom CSS. Farben und Schriften laufen über **Global Colors / Global Fonts**.

## Inhalt

| Datei | Zweck |
|---|---|
| `appa-kit.zip` | **Website-Kit** mit Global Colors, Global Fonts, Theme Style, Layout (1104 px + 24 px Rand), Header/Footer mit Anzeigebedingungen und allen 7 Seiten |
| `appa-elementor-templates.zip` | alle Einzel-Templates für *Vorlagen › Gespeicherte Vorlagen › Importieren* |
| `templates/*.json` | dieselben Templates einzeln |
| `kit-site-settings.json` | nur die Kit-Einstellungen (Referenz) |
| `build.py` | Generator: erzeugt alles oben aus den Design-Tokens des Mockups (`python3 build.py`) |
| `vergleich/*.png` | Screenshots: links das Mockup, rechts das gerenderte Elementor-Template |

### Templates

| Template | Typ | Anmerkung |
|---|---|---|
| APPA – Header | Header | Logo + Pro Nav Menu (Burger ab Tablet), Bedingung: ganze Website |
| APPA – Footer (Home) | Footer | großer Footer der Startseite |
| APPA – Footer (kompakt) | Footer | Footer der Unterseiten |
| Home, Über uns, Veranstaltungen, Mitgliedschaft, Kontakt | Seite | Vorlage *Elementor Volle Breite* (Header/Footer kommen aus dem Theme Builder) |
| Mitgliederbereich (Login) | Seite | Vorlage *Elementor Canvas*, eigener Header/Footer im Inhalt, Pro Login-Widget |
| Mitgliederbereich – Übersicht | Seite | Canvas, Sidebar, mobile Tabs und Bottom-Navigation (Pro Sticky), Begrüßung und Name per Dynamic Tag *User Info* |
| Block: CTA Mitglied werden, Block: Seitenkopf | Abschnitt | wiederverwendbare Blöcke |

## Import

Voraussetzungen: Elementor + Elementor Pro (aktuelle Version), Theme **Hello Elementor**.

1. **Elementor › Werkzeuge › Website-Kit importieren** und `appa-kit.zip` hochladen.
   Damit werden Kit, Header/Footer und Seiten angelegt. „Home“ wird als Startseite gesetzt.
   *Alternative:* `appa-elementor-templates.zip` unter *Vorlagen › Gespeicherte Vorlagen › Importieren*
   hochladen. Dann muss man aber die Global Colors/Fonts aus `kit-site-settings.json` manuell übernehmen
   und die Bedingungen von Header/Footer selbst setzen.
2. **Menü anlegen:** *Design › Menüs*, Menü „Hauptmenü“ mit den 5 Seiten. Das Nav-Menu-Widget nimmt
   es automatisch (das erste Menü wird verwendet, solange kein anderes gewählt ist).
3. **Bedingungen prüfen** (*Theme Builder*):
   - Header: ganze Website, **außer** den beiden Mitgliederbereich-Seiten (die nutzen Canvas).
   - Footer (Home): Startseite. Footer (kompakt): alle anderen Seiten.
4. **Kontaktformular:** Empfänger ist `info@appa.at` (*Aktionen nach dem Absenden › E-Mail*).
5. **Login-Seite:** Nach dem Login geht es weiter zu `/mitgliederbereich-uebersicht/`.
   Die Übersicht sollte für Gäste gesperrt werden (z. B. mit einem Members-/Membership-Plugin).
6. **Bilder:** Die Templates verweisen auf die Mockup-Bilder (uxmagic.blob.core.windows.net).
   Elementor versucht beim Import, sie in die Mediathek zu laden (in der Testumgebung nicht
   prüfbar). Für die Live-Seite sollten sie ohnehin durch eigene Fotos ersetzt werden.

### Interne Links / Slugs

Die Links zeigen auf `/`, `/ueber-uns/`, `/veranstaltungen/`, `/mitgliedschaft/`, `/kontakt/`,
`/mitgliederbereich/`, `/mitgliederbereich-uebersicht/`, `/datenschutz/` und `/impressum/`.
Mit deutscher/österreichischer Sprache wird aus „Über uns“ der Slug `ueber-uns`. Bei einer
englischen Installation wird daraus `uber-uns`. Dann bitte den Slug anpassen.
Die URLs stehen zentral in `build.py` (`URL = {...}`).

## Mapping Mockup → Elementor

| Mockup (Tailwind) | Elementor |
|---|---|
| `:root`-Tokens (`--primary`, `--foreground` …) | Global Colors: System „Primär – Rot #D93629“, „Sekundär – Navy #12345A“, „Text – Gedämpft #52677A“, „Akzent – Gold #E9B434“ plus 8 eigene (Hellblau, Grau hell, Grün, Rahmen …) |
| `text-sm/lg/xl/2xl…`, `font-semibold/bold`, `uppercase tracking-widest` | 30 Global Fonts (Inter), z. B. „H1 Hero (60/36)“, „Eyebrow (14, versal)“, „Lead (18)“ |
| `max-w-6xl mx-auto px-6` | Container „Boxed“, Inhaltsbreite 1104 px, 24 px Innenabstand |
| `grid lg:grid-cols-12` + `col-span-4/8` | Grid-Container mit exakten Spaltenbreiten (`calc(33.333% - 26.667px) 1fr`) |
| `lg:` / `md:` Breakpoints | Elementor Desktop (> 1024 px) / Tablet (≥ 768 px) |
| `rounded-lg/xl`, `shadow-md` | Border Radius 12 px, Box Shadow 0 4 6 -1 rgba(0,0,0,.1) |
| `aspect-video object-cover` | Bild-Widget, Höhe „custom“ `auto; aspect-ratio: 16 / 9` + Object-Fit Cover |
| Lucide-Icons (iconify) | Font-Awesome-Entsprechungen (nativ in Elementor), z. B. `arrow-right`, `map-marker-alt`, `bullseye` |
| Formular `<form>` | Pro Form-Widget (Felder 50/50/100/100 + Akzeptanz-Checkbox) |
| Login-Formular | Pro Login-Widget, Weiterleitung zur Übersicht |
| „Anna Berger“, Avatar | Dynamic Tags *User Info* / *User Profile Picture*, mit Mockup-Text als Fallback |

## Prüfung

Getestet in einem lokalen WordPress 6.x mit Elementor 4.4 (aus dem Quellcode gebaut) und Hello Elementor:

- `appa-kit.zip` lässt sich über `wp elementor kit import` importieren. Kit und alle 7 Seiten werden angelegt.
- Alle Seiten- und Block-Templates lassen sich über `wp elementor library import` importieren.
- Die Seiten wurden gerendert und bei 1280 px und 390 px Breite mit dem Mockup verglichen
  (Tailwind lokal kompiliert, gleiche Platzhalterbilder), siehe `vergleich/`. Die Abweichung
  liegt im Bereich von 1–3 px.

**Nicht getestet** (Elementor Pro stand in der Testumgebung nicht zur Verfügung):
Header/Footer-Import mit Bedingungen sowie die Darstellung der Pro-Widgets Nav Menu, Form,
Login, Share Buttons, Sticky und Dynamic Tags. Ihre Einstellungen folgen den bekannten
Pro-Control-Namen. Wenn ein Detail (z. B. eine Farbe im Formular) nicht greift, lässt es
sich direkt im Widget nachstellen.

## Bewusste Abweichungen

- **Icons:** Lucide gibt es nicht nativ in Elementor, daher die nächstliegenden Font-Awesome-Icons.
  Wer exakt Lucide will, lädt die SVGs als Pro *Custom Icons* hoch.
- **Zeilenhöhe:** Das Mockup setzt über `styles.css` global `html { line-height: 1.15 }`. Das ist
  1:1 übernommen (Global Font „Fließtext“). Für längere Texte sind 1.5 lesbarer, das lässt sich
  an einer Stelle im Kit ändern.
- **Footer-Links:** Im Mockup unterscheiden sich die Links im Footer je Unterseite leicht.
  Hier gibt es einen einheitlichen kompakten Footer (Home · Kontakt · Datenschutz · Impressum).
- **Platzhalter** wie `[Datum]`, `€ [Betrag]` und `[Name]` sind unverändert aus dem Mockup übernommen.
- **Sichtbarkeits-Schalter** im Mitgliederprofil ist nur ein Icon. Für einen echten Schalter braucht es
  eine Profil-/Membership-Lösung.
