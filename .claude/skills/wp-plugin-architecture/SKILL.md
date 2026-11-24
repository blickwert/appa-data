---
name: wp-plugin-architecture
description: Blickwert-Architektur für WordPress-Plugins (OOP, Module, Free/Pro, PHP 8.3). Verwenden beim Anlegen eines neuen Plugins, eines neuen Moduls, beim Verschieben eines Moduls nach Pro oder bei jeder Code-Änderung in einem BW-Plugin-Repo.
---

# WordPress-Plugin-Architektur (Blickwert)

Gilt für alle Plugins, die aus dem Template `<OWNER>/bw-plugin-template` entstehen.
Projektspezifisches steht in der `CLAUDE.md` des jeweiligen Repos – bei Widerspruch gilt die `CLAUDE.md`.

## Grundregeln

- PHP 8.3, `declare(strict_types=1);` in jeder Datei, Typen für alle Parameter und Rückgaben.
- Namespace `BW\<Plugin>\`, Pro: `BW\<Plugin>\Pro\`. PSR-4 über Composer, keine `require`-Ketten.
- Präfix `bw_<plugin>_` für Hooks, Optionen, Shortcodes, Meta-Keys, Nonces. Konstanten `BW_<PLUGIN>_*`.
- Code, Kommentare, UI-Texte (Text Domain = Slug) auf Englisch. Übersetzung über `/languages`.
- WordPress-Coding-Style: Tabs, Leerzeichen in Klammern, `snake_case` für Methoden.

## Ordnerstruktur

```
free/
├─ <slug>.php          nur Konstanten, Autoloader, Plugin::instance()->boot()
├─ src/Core/           Plugin, ModuleManager, Module, AbstractModule, Settings, View, Admin/
├─ src/Modules/<Name>/ <Name>Module.php + Model/ Repository/ Service/ Controller/
├─ src/Support/        kleine reine Hilfsfunktionen
├─ templates/<modul>/  Views
└─ uninstall.php
pro/
├─ <slug>-pro.php      Versionscheck, dann ProPlugin::boot()
└─ src/Modules/<Name>/
tests/Unit/            PHPUnit + Brain Monkey
```

## Schichten (Abhängigkeit nur in Pfeilrichtung)

Controller → Service → Repository → Model. Views nur aus Controllern.

| Schicht | Aufgabe | Darf nicht |
|---|---|---|
| Module | `register()` instanziiert Controller und hängt Hooks ein | Logik enthalten |
| Controller | Admin-Seiten, REST-Routen, Shortcodes, Blöcke, AJAX: Eingabe prüfen, Service aufrufen, View rendern | SQL, Geschäftsregeln |
| Service | Geschäftslogik | `$_POST`, HTML, `add_action` |
| Repository | Laden/Speichern (WP_Query, `$wpdb`, Optionen, Meta) | Geschäftsregeln |
| Model | Daten als Objekt (readonly Properties, Getter, kleine Prüfungen) | Datenbankzugriff |
| View | Ausgabe, alles escapen (`esc_html`, `esc_attr`, `esc_url`) | Queries, Logik |

Abhängigkeiten per Konstruktor übergeben (`private readonly`), nicht im Inneren erzeugen – dann sind Services testbar.

## Module

- Jedes Modul implementiert `Core\Module` (meist über `AbstractModule`): `id()`, `name()`, `description()`, `requires()`, `enabled_by_default()`, `register()`.
- `id()` ist stabil und wird gespeichert – nie nach einem Release umbenennen.
- Hooks eines Moduls werden **nur** in `register()` registriert. Inaktive Module registrieren nichts.
- **Module rufen andere Module nie direkt auf.** Kommunikation nur über eigene Hooks:
  - Ereignis: `do_action( 'bw_<plugin>_<ereignis>', $objekt )`
  - Erweiterbarer Wert: `apply_filters( 'bw_<plugin>_<wert>', $wert, ... )`
  - Jeden neuen Hook in der Klasse, die ihn feuert, im PHPDoc dokumentieren.
- Braucht ein Modul ein anderes, steht dessen ID in `requires()`. Der ModuleManager deaktiviert sonst automatisch.

### Neues Modul anlegen
1. Ordner `free/src/Modules/<Name>/` (oder `pro/…`) mit `<Name>Module.php`.
2. Nur die Schichten anlegen, die das Modul wirklich braucht.
3. Free-Modul in `ModuleManager::discover()` eintragen, Pro-Modul in `ProPlugin::add_modules()`.
4. Templates unter `templates/<modul-id>/`.
5. Daten, die das Modul anlegt, in `uninstall.php` aufräumen.
6. Unit-Test für jeden Service.
7. Modultabelle in der `CLAUDE.md` ergänzen.

### Modul von Free nach Pro verschieben
Ordner nach `pro/src/Modules/` verschieben, Namespace auf `BW\<Plugin>\Pro\Modules\…` ändern, Registrierung von `discover()` nach `ProPlugin::add_modules()` umziehen, Text Domain anpassen. `id()` bleibt gleich, damit Einstellungen erhalten bleiben.

## Free / Pro

- Free funktioniert allein vollständig und kennt Pro nicht (kein `class_exists`-Check auf Pro-Klassen, keine gesperrten Pro-Funktionen im Free-Code).
- Pro hängt sich nur über `bw_<plugin>_register_modules` und die Hooks der Module an. Pro kopiert keinen Core-Code.
- Pro prüft `BW_<PLUGIN>_VERSION` gegen `BW_<PLUGIN>_PRO_REQUIRES_CORE`. Bei Core-Änderungen, die Pro braucht, beide Versionen erhöhen.
- Lizenzprüfung (später): über den Filter `bw_<plugin>_module_allowed` im Pro-Plugin – nicht im Free-Code.

## Frontend

Zwei Arten von Oberfläche, je mit eigenen Regeln. Vorbild ist WooCommerce: Kundenseiten im Theme, Verwaltung im WordPress-Admin.

### Seiten auf der Website (Kundenbereich, Seiten hinter Links in Mails)

- HTML kommt vom Server aus PHP-Vorlagen in `templates/<bereich>/`. Kein React, kein Shadow Root, kein JSON-Block, den erst ein Skript zeichnet.
- Jede Vorlage lässt sich im Theme überschreiben: `View::locate()` sucht zuerst im Theme unter `<slug>/<bereich>/…`, dann im Plugin. Ein Filter (`bw_<plugin>_template`) erlaubt eine andere Datei.
- Vorlagen bekommen fertige Daten. Rechnen, Texte und Formatieren passieren vorher in einem Service (Seitenmodell). In der Vorlage nur Schleifen, Bedingungen und escapte Ausgabe.
- An festen Stellen in den Vorlagen steht ein Hook (`do_action( 'bw_<plugin>_…' )`), damit Module und Themes etwas einhängen, ohne die Vorlage zu kopieren.
- Unterseiten sind Endpunkte unter der Seite (Rewrite-Regeln, wie „Mein Konto“), die Namen sind einstellbar und standardmäßig deutsch.
- Die Seite selbst ist eine normale WordPress-Seite mit einem Block (`block.json`, `render.php` oder `render_callback`). Ein Shortcode bleibt als zweiter Weg.
- Seiten ohne eigene WordPress-Seite (Bestätigen per Link usw.) laufen mit Kopf und Fuß des Themes, bei Block-Themes über die Vorlagenteile `header` und `footer`. Nicht indexieren.
- Bewegliche Teile über die Interactivity API: `wp_register_script_module` mit Abhängigkeit `@wordpress/interactivity`, Store mit Namen `<slug>/<bereich>`, abgeleiteten Zustand auch in PHP setzen (`wp_interactivity_state`), damit die Seite ohne Skript richtig aussieht. Formulare sind normale Formulare mit Nonce und Weiterleitung.
- Stil in einer eigenen CSS-Datei, kein `<style>` in Vorlagen. Klassen mit Präfix (`bwo-`), Farben über CSS-Variablen (`--bwo-*`), die das Theme über `theme.json` (`settings.custom.<slug>`) setzen kann. Container Queries statt fester Bildschirmbreiten.
- Skripte und Stile nur auf den Seiten laden, die sie brauchen.

### Verwaltung im WordPress-Admin (Team-App)

- Eigenes Menü im Admin. Jede Seite ist ein Menüpunkt, den Inhalt zeichnet eine App. CPTs dienen als Speicher, die klassischen Listen von WordPress werden nicht genutzt.
- Gebaut mit `@wordpress/scripts` (TypeScript) nach `build/`, nicht im Git. Abhängigkeiten und Version kommen aus `<name>.asset.php`.
- Nur WordPress-Pakete, kein eigenes React: `@wordpress/element`, `@wordpress/components`, `@wordpress/api-fetch` (Nonce über die Middleware), `@wordpress/i18n`, `@wordpress/hooks`.
- Daten nur über eigene REST-Routen (`<slug>/v1`), jede mit `permission_callback`.
- Felder mit `TextControl`, `SelectControl`, `TextareaControl`, `CheckboxControl`, `FormTokenField`, `SearchControl`, `FormFileUpload`, immer mit `__next40pxDefaultSize` und `__nextHasNoMarginBottom`. Knöpfe mit `Button` (`variant`, `size="compact"` in Zeilen). Laden mit `Spinner`, Fehler mit `Notice`, kurze Meldungen mit `Snackbar`.
- Schlichte `<input>` und `<select>` nur in kompakten Zeilen und Tabellen (Positionen, Prozent, Häkchen). Sie bekommen das Aussehen über eine gemeinsame CSS-Datei (`wp.css`).
- Farben aus dem Admin-Farbschema (`--wp-admin-theme-color`), Grautöne des Admins. Alle Stile nur innerhalb des App-Containers (`.bwo-app`), damit der Admin sonst unverändert bleibt.
- Jede Seite hat eine Adresse (`admin.php?page=…&path=/…`), die PHP und App gleich bauen. Links im selben Menüpunkt wechseln ohne Neuladen.
- Seiten je Modul: Der Kern meldet seine Seiten, jedes Modul hängt seine über einen JS-Filter an (`addFilter( '<slug>.routes', … )`), nur wenn es aktiv ist. So kann ein Modul später ein eigenes Skript bekommen.
- Gemeinsame Bausteine (Seitenkopf, Reiter, Laden, Meldungen, Auswahl, Blättern) liegen an einer Stelle und werden wiederverwendet, nicht je Seite neu gebaut.
- Texte als `__( 'English', '<slug>' )`, Übersetzung mit `wp_set_script_translations` aus denselben Dateien wie PHP.

### Für beide

- Keine Schriften von Google oder anderen fremden Servern (Datenschutz).
- Nur eine helle Fassung, solange nichts anderes entschieden ist.
- Jede sichtbare Änderung lokal ansehen, am Desktop und in Handybreite.

## Kommentare

- Jede Klasse: ein PHPDoc-Satz, wofür sie da ist.
- Jede public Methode: PHPDoc mit kurzer Beschreibung, `@param`/`@return` nur wenn der Typ allein nicht reicht (z. B. Array-Shapes `string[]`, `array<string, Module>`).
- Interface-Implementierungen: `{@inheritDoc}`.
- Hilfsfunktionen: ein Einzeiler.
- Inline-Kommentare nur für das **Warum** (Sonderfall, WP-Eigenheit, Geschäftsregel), nie für das Was.
- Sprechende Namen statt Kommentare: `get_open_bookings()` statt `get_data()` + Kommentar.

## Sicherheit (immer)

- Eingaben: `sanitize_*`, `absint`, `wp_unslash`; erlaubte Werte per Whitelist prüfen.
- Formulare/AJAX: Nonce + `current_user_can()`. REST: `permission_callback` nie `__return_true` bei schreibenden Routen.
- SQL nur mit `$wpdb->prepare()`.
- Ausgabe in Views immer escapen.

## Neues Plugin anlegen

```bash
gh repo create <OWNER>/<slug> --private --template <OWNER>/bw-plugin-template --clone
cd <slug>
./init.sh <slug> "<Plugin Name>" <NamespacePart>   # z. B. bw-booking "BW Booking" Booking
./scripts/dev-install.sh
composer test
```
Danach: Beispielmodule `example` und `pro-example` durch echte Module ersetzen (Tests mit anpassen), `CLAUDE.md` ausfüllen, ersten Commit machen.

## Vor jedem Commit

1. `composer lint` und `composer test` laufen grün.
2. Neue Hooks sind dokumentiert, Modultabelle in `CLAUDE.md` aktuell.
3. Bei Release: Version in Plugin-Header, Konstante und `readme.txt` (Free) erhöhen, dann `git tag vX.Y.Z && git push --tags`.

## Nicht tun

- Keine Logik in der Plugin-Hauptdatei.
- Keine globalen Funktionen außer reinen Helfern in `Support/`.
- Kein Singleton außer `Core\Plugin`.
- Kein `extract()`, keine Abfragen in Templates.
- Kein Over-Engineering: keine DI-Container, Event-Busse oder Abstraktionen, solange ein Konstruktorparameter reicht.
