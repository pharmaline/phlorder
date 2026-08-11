# CLAUDE.md — Extension `phlorder`

Diese Datei leitet die Arbeit an dieser TYPO3-Extension. **Sprache: Deutsch** (der User
kommuniziert auf Deutsch). Technische Begriffe/Code bleiben englisch.

> **Auftrag:** `phlorder` wird von seiner aktuellen Version (**TYPO3 7.6**,
> `ext_emconf.php` v1.0.8, Extension Builder 2018) auf **TYPO3 13.4 LTS** gehoben.
> Zielzustand: composer-verwaltet, im Monorepo installiert, im Backend bedienbar und
> im Frontend inklusive Mail-/AJAX-Pfad lauffähig — auf dem Stand der bereits
> migrierten Schwester-Extensions `phlvote`, `phlusereditor` und `phlsitepackage`.
> Der Plan steht unter „Migrationsplan"; die 22 Baustellen darunter sind die
> Arbeitsliste.

> **Dokumentationspflicht:** *Alle* Anpassungen an dieser Extension werden in
> `Documentation/Genesis.md` protokolliert (neueste Einträge unten anhängen, Format
> wie in `packages/phlaponot/Documentation/genesis.md`). Kein Änderungsschritt ohne
> Eintrag.

## Was diese Extension ist

`phlorder` (Titel „phlorder Bestellung") ist die **Bestellstrecke des NOWEDA-Pharmacy-
Portals**: Eine Apotheken-Kundin gibt eine Bestellung ab, die Extension speichert sie,
vergibt eine Bestellnummer, verschickt Bestellmails an Apotheke (Company) und Kundin,
erzeugt einen QR-Code zur Status-Seite und zeigt Status/Historie im Frontend an.

- **Vendor / Namespace:** `Pharmaline\Phlorder\` → `Classes/`
- **Extension-Key:** `phlorder`
- **Plugin:** ein einziges — `Order` (Signatur `phlorder_order`), Controller
  `Order`, `Log`, `Item`, `Token`
- **Erzeugt mit:** Extension Builder 2018, Stand `ext_emconf.php` = **TYPO3 7.6**,
  Version 1.0.8, `state = alpha`
- **DB-Tabellen:** `tx_phlorder_domain_model_{order,log,item,token}`
  (dazu vier Leichen in `ext_tables.sql`, siehe Baustelle #9)

### Domäne

| Modell  | Tabelle | Bedeutung |
|---------|---------|-----------|
| `Order` | `…_order` | die Bestellung: Kundendaten, `orderid` (UUID = öffentliches Token), `ordernumber`, `status`, `phluserfid` (FK auf Phluser = Apotheke), FAL-Bilder (`order_image`), 1:n `tolog`, 1:n `toitem` |
| `Log`   | `…_log`   | Vorgangshistorie zu einer Bestellung (`tx_order`), `action`/`result`/`value1`/`value2`/`actor` |
| `Item`  | `…_item`  | Bestellposition (`pzn`, `name`, `size`, `dar`, `qty`, `price`) |
| `Token` | `…_token` | Token/Status je Phluser — im Code aktuell **nicht** benutzt |

`Order`, `Log`, `Item`, `Token` haben je einen Extbase-Controller, aber nur der
`OrderController` enthält echte Logik (`list`, `status`, `show`, `delete`, `test`).
Die drei anderen sind unveränderter Extension-Builder-CRUD-Boilerplate und über die
FlexForm gar nicht erreichbar — beim Aufräumen prüfen, ob sie ersatzlos weg können.

### Zwei Rendering-Pfade

1. **Extbase-Plugin** — `Order->list` (Cockpit-Ansicht mit `#pagedata`-JSON) und
   `Order->status` (Statusseite, Aufruf per `?t=<orderid>`; ohne Login `modus=info`,
   als Inhaber `modus=admin`).
2. **eID/AJAX** — `Classes/Utility/Ajax/phlorderEid.php`, registriert über
   `$TYPO3_CONF_VARS['FE']['eID_include']['phlorderEID']`. Hier hängt der ganze
   Mail-/QR-Versand. Funktionsschalter ist der Request-Parameter `_f`:

   | `_f` | Funktion | Absicherung |
   |------|----------|-------------|
   | `smtco` | Bestellmail an die Apotheke | `hs = sha1(mt + oto + date('dmy'))` |
   | `smtcu` | Bestellmail an die Kundin | dito |
   | `smocomp` | Bestellmail an die Apotheke (Altvariante, **ohne** Hash-Prüfung) | – |
   | `smo` | freie Mail aus `$_POST` | `hs = sha1(mt + SECRETSMO + "rNejxjSJgK")` |
   | `gqc` | QR-Code zur Bestellung als GIF | – |
   | `lii` | Redirect auf 1×1-Zählpixel | – |

   Der JSON-Kontrakt zum Frontend ist `{"success": "true|false", "message": "…"}`
   (`getMessage()`); `phlorder.js` wertet `response.success`/`response.message` aus.
   Beispiel-URLs des ursprünglichen Autors stehen in `Documentation/Genesis.md`.

### Fremd-Abhängigkeiten (wichtig)

`phlorder` ist **nicht** self-contained:

- `OrderController` injiziert `Pharmaline\Phlusereditor\Domain\Repository\{PhluserRepository,
  PhlfrefsRepository}` → Extension **`phlusereditor`** (liegt im Monorepo, bereits auf v13).
- Der eID-Worker holt Userdaten/Präferenzen über den Legacy-Service
  `GeneralUtility::makeInstanceService('extPhluser')`. Diesen Service gibt es in v13
  nicht mehr — `phlusereditor` hat ihn bereits ersetzt durch
  **`Pharmaline\Phlusereditor\Service\PhluserService`** (in `Services.yaml` als
  `public: true` registriert, API bewusst identisch: `getUserInfo`, `getUserByToken`,
  `getUserByTokenFromPref`, `selectPrefOfUser`). Darauf umstellen, nichts neu bauen.
- Der QR-Code kommt aus `makeInstanceService('extPhlqr')`. **Diese Extension existiert
  im Monorepo nicht** — die QR-Funktion ist damit tot. Vor dem Umbau klären, ob eine
  QR-Bibliothek eingezogen oder das Feature gestrichen wird.
- `phlaponot` (parallele Migration) und `phlvote`/`phlsitepackage` (fertig auf v13) sind
  die Stil-Referenzen.

## Migrationsziel & Umgebung

- Ziel: **TYPO3 13.4 LTS**, Ausgangspunkt **TYPO3 7.6**.
- **PHP:** DDEV-Container läuft auf 8.4 (`.ddev/config.yaml: php_version: "8.4"`,
  konkret 8.4.20). Root-`composer.json` pinnt `config.platform.php = 8.4.20` —
  bewusst **mit Patch-Level**, weil mehrere Pakete `>= 8.4.1` verlangen und glattes
  `8.4` als 8.4.0 gelesen würde. Die Extension selbst bleibt breiter: `composer.json`
  `^8.2`, `ext_emconf` `8.2.0-8.5.99`.
  **Host-PHP ist 8.3.4** und damit zu alt → `phpunit`/`phpstan`/`php-cs-fixer`
  **nur im Container** starten (`ddev exec …`). `php -l` geht auch auf dem Host.
- Monorepo `phl13`; alle `packages/*` hängen über das Composer-`path`-Repository
  `./packages/*` drin und werden nach `vendor/pharmaline/<name>` gesymlinkt.
  **Kein `typo3conf/ext`-Symlink** — Greps/Checks über den vendor-Pfad. Betrieb via DDEV.
- **Referenz-Extensions, die bereits auf v13 laufen:** `packages/phlvote`,
  `packages/phlusereditor`, `packages/phlsitepackage`. Deren `composer.json`,
  `Configuration/Services.yaml`, `Configuration/RequestMiddlewares.php`, `phpstan.neon`
  und Verzeichnisstruktur als Vorlage nehmen — nicht neu erfinden.
- Root bietet Tooling: `rector` (^2), `php-cs-fixer` (^3), `saschaegerer/phpstan-typo3`,
  `typo3/coding-standards`, `typo3/testing-framework` (^9).
- **Rector-Warnung (Erfahrung aus `phlusereditor`):** `ssch/typo3-rector` ist mit
  diesem Projekt **inkompatibel** (verlangt `symfony/string ≤ 7`, das Projekt ist auf
  v8 gelockt). Nur **isoliert außerhalb des Repos** installieren und von dort auf
  `packages/phlorder` zeigen lassen. Nach jedem Lauf `php -l` über alle geänderten
  Dateien — der Lauf in `phlusereditor` hat einen Syntaxfehler produziert.

## Ausgangszustand

`phlorder` ist die **am wenigsten migrierte** Extension im Monorepo — deutlich älter als
`phlaponot` (7.6 statt 9.5) und ohne jede Composer-Anbindung:

- **Keine `composer.json`**, kein Eintrag in der Root-`composer.json`, kein Symlink in
  `vendor/pharmaline/`. Die Extension ist im laufenden System aktuell **gar nicht geladen**.
- Kein eigenes Git-Repo (anders als `phlvote`/`phlaponot`) — Änderungen gehen über das
  Monorepo.
- `Documentation.tmpl/` ist das unbearbeitete Extension-Builder-Gerüst von 2018;
  echte Doku gehört nach `Documentation/`.

## Migrationsplan (7.6 → 13.4)

Phasenweise, jede Phase lässt die Extension in einem prüfbaren Zustand zurück.
Die Ziffern in Klammern verweisen auf die Baustellen im nächsten Abschnitt.
Jede Phase = mindestens ein Eintrag in `Documentation/Genesis.md`.

**Phase 1 — Composer-Anbindung (Extension wird überhaupt geladen)**
`composer.json` nach dem Muster von `phlusereditor` (`type: typo3-cms-extension`,
`extra.typo3/cms.extension-key: phlorder`, PSR-4 `Pharmaline\Phlorder\`),
`ext_emconf.php` auf `13.4.0-13.4.99` + PHP-Constraint, veraltete Keys (`uploadfolder`,
`createDirs`, `clearCacheOnLoad`) raus, Root-`composer.json` um
`"pharmaline/phlorder": "@dev"` ergänzen, `ddev composer update pharmaline/phlorder`.
Erwartet: `extension:setup` bricht mit `Access denied.` ab → Phase 2.

**Phase 2 — Bootstrap reparieren (#1, #2, #3)**
`TYPO3_MODE`-Guards, `$_EXTKEY`, `extRelPath()`, Plugin-Registrierung ohne
Vendor-Präfix. `ext_tables.php` **auflösen und löschen**: `registerPlugin` +
FlexForm-Zuordnung nach `Configuration/TCA/Overrides/tt_content.php`, die drei
entfernten APIs (`allowTableOnStandardPages`, `addLLrefForTCAdescr`, `addStaticFile`
→ Site Set) ersetzen. Ziel: `extension:setup` läuft durch.

**Phase 3 — Rector-Lauf**
TYPO3-Set + PHP-Set, isoliert installiert (siehe Rector-Warnung oben). Nimmt
`AbstractMessage`, `TYPO3_version`, `GeneralUtility::_GP()`, `$GLOBALS['TSFE']`-
Zugriffe und einen Teil der `@inject`-Annotationen mit. Diff **von Hand nachlesen** —
Rector fasst weder den toten TSFE-Bootstrap im eID-Worker noch die PHP-8-Fatals
(#15) noch die Sicherheitspunkte (#16) an. Danach `php -l` über alles.

**Phase 4 — TCA + Datenbank (#5, #6, #7, #8, #9)**
Datetime-Felder (sonst hängt die FormEngine am Spinner), `ctrl`/`interface`-Altlasten,
FAL-Feld auf `type => 'file'`, `ext_tables.sql` auf Fachspalten reduzieren, die vier
spaltenlosen `CREATE TABLE`-Blöcke löschen. Gate: BE-Maske öffnet sich für alle vier
Tabellen ohne Spinner.

**Phase 5 — Controller, Repository, Models (#10, #11, #12, #13)**
Constructor-Injection + `Configuration/Services.yaml`, `ResponseInterface` in jeder
Action, PSR-7 statt `$GLOBALS['TSFE']`/`$_REQUEST`, typisierte Properties,
`initStoragePid()` neu, `debugster()`/Testcode raus. `PhluserRepository`/
`PhlfrefsRepository` aus `phlusereditor` per DI.

**Phase 6 — Plugin-Aufteilung (#4)**
`switchableControllerActions` auflösen: getrennte Plugins für `Display` und `Status`
(`Order->test` entfällt). FlexForm neu schneiden, Felder als `settings.<x>` benennen
(nativer Extbase-Merge). Vorbild `phlvote` (`Vote`/`Votelist`); `phlusereditor` hat
zusätzlich auf **eigene CTypes statt `list_type`** umgestellt — für `phlorder`
entscheiden, ob wir gleich mitziehen.

**Phase 7 — eID → PSR-15-Middleware (#14, #15, #16)**
Der größte Brocken. Muster: `phlusereditor`/`phlvote` (Marker `mw=phlorderEID`, Guard
auf `frontend.typoscript`, self-contained Worker per Konstruktor-DI, Antwort als
`Response` statt `echo`/`exit`). Vorher die Sicherheitspunkte (#16) mit dem User
klären. `ViewFactoryInterface` statt `StandaloneView`, Symfony Mailer statt Swift,
`PhluserService` statt `makeInstanceService`. JSON-Kontrakt zum JS erhalten.

**Phase 8 — TypoScript, Templates, Assets (#17, #18, #19, #20)**
Site Set statt `addStaticFile`, `EXT:`-Pfade, kaputte Asset-Referenzen,
`treatIdAsReference` raus, XLIFF-Duplikate bereinigen.

**Phase 9 — Tests, PHPStan, Aufräumen (#21, #22)**
Orphan-Tests löschen, Rest auf `typo3/testing-framework: ^9` / PHPUnit 12+ heben,
`Build/phpunit/UnitTests.xml` + `phpstan.neon` + Baseline anlegen,
`Documentation.tmpl/` entfernen.

## Bekannte Migrations-Baustellen (v7.6 → v13.4)

Konkrete Legacy-Muster, die in diesem Code stecken und angefasst werden müssen.
Nummerierung ist die Referenz für die Einträge in `Documentation/Genesis.md`.

> **Stand nach Phase 6:** #1–#3 und #5–#13 sind **erledigt**.
> `Configuration/Services.yaml` liegt vor (mit Ausschluss von `Utility/Ajax`), alle
> Controller nutzen Constructor-Injection, alle Actions liefern `ResponseInterface`,
> der `OrderController` ist frei von Globals und Debug-Code (548 → 331 Zeilen).
> **Zusätzlich gefunden und behoben:** `Order.php` hatte 3× die Alt-Annotation
> `@cascade remove`, an der Doctrine in v13 hart abbricht → jetzt
> `#[\TYPO3\CMS\Extbase\Annotation\ORM\Cascade(['value' => 'remove'])]`.
>
> **Plugins (seit Phase 6): zwei eigene CTypes**, nicht mehr `list_type` —
> `Order` (`phlorder_order`, Default `Order->list`) und `Orderstatus`
> (`phlorder_orderstatus`, Default `Order->status`). Die alte FlexForm enthielt nur
> SCA und wurde gelöscht; die neue `flexform_order.xml` hängt **nur am Cockpit-Plugin**
> und legt mit `settings.sourcePid` die Quelle der Bestellungen fest. Ausgewertet wird
> das **nicht** im Controller, sondern im EventListener `ApplySourcePidToStoragePid`
> (`BeforeFlexFormConfigurationOverrideEvent`) — was `initStoragePid()` über
> `setConfiguration()` setzt, überschreibt der `FrontendConfigurationManager`
> anschließend wieder mit dem Plugin-TypoScript. Leeres Feld = TypoScript-Default.
> Begründung und FE-Messwerte in `Documentation/Genesis.md`, Eintrag 2026-08-09.
> Beide Plugins registrieren **nur `list` und `status`**:
> ohne SCA ist jede registrierte Action per URL dispatchbar, und die alte
> Registrierung hätte u. a. drei ungeschützte `delete`-Actions freigelegt.
> Folge: `LogController`, `ItemController`, `TokenController` sowie
> `Order->show`/`Order->delete` sind **nicht mehr registriert** und damit toter Code
> → Löschen in Phase 9.
>
> **Stand nach Phase 7:** #14, #15, #16 sind ebenfalls **erledigt**. Der eID-Worker
> läuft als PSR-15-Middleware (`mw=phlorderEID`, 1055 → 636 Zeilen), ist frei von
> `ObjectManager`, `StandaloneView`, SwiftMailer, `$GLOBALS['TSFE']`, `$_REQUEST`,
> `echo`/`exit`. Mailversand end-to-end mit Produktivdaten gegen Mailpit verifiziert.
>
> **Drei Dinge, die dabei zu beachten sind:**
> 1. **Die eID-URL hat sich geändert** — `?eID=phlorderEID&p=<pid>&…` wird zu
>    `?id=<pid>&mw=phlorderEID&…`. Externe Aufrufer (das System, das die Bestellmails
>    auslöst) müssen umgestellt werden. Alle AJAX-Parameter stehen in
>    `FE.cacheHash.excludedParameters`, sonst antwortet TYPO3 mit 404.
> 2. **`_f=smo` ist standardmäßig deaktiviert** (`settings.enableFreeMailEndpoint`),
>    `_f=smocomp` validiert den Hash jetzt wirklich. Begründung in
>    `Documentation/Genesis.md`.
> 3. ~~**QR-Code hat keine Implementierung** (`extPhlqr` existiert nicht) — `_f=gqc`
>    meldet das sauber, Mails gehen ohne QR raus. Entscheidung offen.~~
>    **Seit 2026-08-04 angebunden:** `Pharmaline\Phlqr\Service\QrCodeService` aus
>    dem inzwischen migrierten `phlqr`. `_f=gqc` liefert ein SVG, die Kundenmail
>    trägt den Code als Inline-Bild (`cid:qrcode`). Die Basis-URL steht als
>    Site-Setting `phlorder.qrcodeText`. Details im Nachtrag am Ende von
>    `Documentation/Genesis.md`.
>
> **Datenbefund:** 2826 Bestellungen haben `order_image > 0`, aber es gibt **null**
> `sys_file_reference`-Zeilen — die Bilder sind nie mitmigriert worden. Extbase
> liefert korrekt eine leere ObjectStorage; nicht angefasst.
> `ext_tables.php` ist gelöscht, die Instanz läuft (FE + BE HTTP 200),
> `extension:setup` und `database:updateschema` laufen durch, die FormEngine-Daten
> aller vier Tabellen kompilieren headless ohne Fehler (kein Spinner mehr).
> Phase 3 (Rector) wurde übersprungen, die TCA-Arbeit von Hand gemacht.
>
> **Storage-Pid: erledigt.** Der Sysordner **„phlorder" = `pages.uid 17`** (pid 1,
> doktype 254) ist angelegt; alle 4284 Datensätze (3511 Orders, 736 Logs, 37 Tokens)
> liegen dort. Die alte pid 29 existierte in der v13-Instanz nie, die Tokens lagen auf
> pid 0. **Diese 17 ist die `storagePid`, die in Phase 8 ins Site Set gehört.**
> Nicht verschoben: `tx_phlorder_domain_model_fastorder` (18 Zeilen, weiterhin pid 0,
> hat keine TCA — siehe #9).
>
> ## ✅ Migration abgeschlossen (Phase 9, 2026-07-31)
>
> **Alle 22 Baustellen sind erledigt.** `ci:test:unit` (23 Tests, 55 Assertions) und
> `ci:phpstan` (Level 5, **leere Baseline**) laufen grün, `extension:setup` durch,
> FE/BE HTTP 200, Mailversand end-to-end verifiziert.
>
> **⚠ Dieses Paket steht unter KEINER Versionskontrolle** — kein `.git` im Monorepo
> und keins in `packages/phlorder` (anders als `phlvote`/`phlaponot`). Löschungen sind
> endgültig. Snapshot des Stands nach Phase 9 liegt im Scratchpad. **`git init`
> nachholen.**
>
> Die Liste der bewusst offen gelassenen Punkte (Secrets rotieren, externe
> eID-Aufrufer umstellen, QR-Feature, Bestellnummern-Vergabe, fehlende
> `order_image`-Referenzen, `fastorder`-Tabelle, EOL-Assets, CS-Fixer-Lauf) steht am
> Ende von `Documentation/Genesis.md`.
>
> ---
>
> **Stand nach Phase 8:** #17–#20 sind **erledigt**. Site Set
> `Configuration/Sets/Phlorder/` ist angelegt und in
> `config/sites/phlsitepackage/config.yaml` eingetragen, `storagePid = 17`,
> `plugin.tx_phlorder_orderstatus < plugin.tx_phlorder_order`.
> Beide Plugins im FE verifiziert, alle Assets liefern HTTP 200.
>
> **Secrets sind aus dem Code raus.** Die meisten wurden schlicht nicht mehr
> gebraucht (`tokenSalt`, `ut`, `ot`, `oto`, `envPath`, … → gelöscht, ihr Code war in
> Phase 5–7 entfallen). `ordersalt` und `freeMailSecret` sind Konstanten mit **leerem**
> Default; echte Werte gehören in die Site-Konfiguration.
> **Die alten Werte stehen weiter in der Git-Historie und müssen rotiert werden.**

1. **`TYPO3_MODE`-Konstante** (`ext_localconf.php:2`, `ext_tables.php:2`): in v12
   entfernt → `defined('TYPO3') or die();`. (Die TCA-Dateien haben keinen Guard.)
2. **`$_EXTKEY`** (`ext_localconf.php:46`, `ext_tables.php:33`) → String `'phlorder'`.
   Im selben Zug **`ExtensionManagementUtility::extRelPath()`** (`ext_localconf.php:32`,
   in v9 entfernt) → `EXT:phlorder/…`-Pfad.
3. **Plugin-Registrierung:** `configurePlugin('Pharmaline.Phlorder', …)`
   (`ext_localconf.php:9`) und `registerPlugin('Pharmaline.Phlorder', …)`
   (`ext_tables.php:9`) — Vendor-Präfix seit v10 weg → Extension-Name `'Phlorder'`.
   `registerPlugin` + `subtypes_addlist` + `addPiFlexFormValue` gehören nach
   `Configuration/TCA/Overrides/tt_content.php`; **`ext_tables.php` entfällt danach
   komplett**. Der `addPageTSConfig`-Wizard-Block (`ext_localconf.php:27-44`) ist mit
   dem v13-CType-Handling hinfällig — `registerPlugin()` legt den Wizard-Eintrag selbst an.
   **Achtung, drei der aufgerufenen APIs sind in v13 ersatzlos entfernt** (im Core
   verifiziert) und beenden den TCA-/Bootstrap-Aufbau fatal:
   - `allowTableOnStandardPages()` (`ext_tables.php:21,24,27,30`) →
     `ctrl.security.ignorePageTypeRestriction = true` im jeweiligen TCA,
   - `addLLrefForTCAdescr()` (`ext_tables.php:20,23,26,29`) → ersatzlos streichen;
     die vier `locallang_csh_*.xlf` liegen dann ungenutzt herum (mit löschen),
   - `addStaticFile()` (`ext_tables.php:18`) → **Site Set**, siehe #17.
4. **`switchableControllerActions`** (`Configuration/FlexForms/flexform_order.xml:10`):
   in v13 entfernt. Die drei SCA-Varianten (`Display`, `Status`, `Test`) müssen zu
   **getrennten Plugins** werden — genau wie in `phlvote` (`Vote`/`Votelist`).
   `Order->test` ist reiner Debug-Code und kann dabei entfallen.
   Beim Aufteilen gelten die Regeln aus `phlusereditor` (dort Abschnitt
   „Plugin-Migration"):
   1. Bestehende FlexForms **müssen erhalten bleiben** — keine Felder oder Werte
      verlieren.
   2. Jede FlexForm auf ihr neues Plugin **zuschneiden**: nur die Felder behalten, die
      für den jeweiligen CType relevant sind. Keine gemeinsame FlexForm für alle.
   3. `ds_pointerField`/FlexForm-Zuordnung pro neuem CType in
      `Configuration/TCA/Overrides/tt_content.php` registrieren, nicht mehr über
      `list_type`.
   4. Werte aus alten FlexForm-Datensätzen per **Upgrade Wizard** in die neue Struktur
      übernehmen; Feldnamen vorher abgleichen, damit beim Mapping nichts verloren geht.

   Für `phlorder` ist Punkt 4 voraussichtlich gegenstandslos — die einzige FlexForm
   enthält nur `switchableControllerActions`, es gibt also keine fachlichen Werte zu
   retten. **Vor dem Umbau trotzdem prüfen**, ob in `tt_content` produktive
   `phlorder_order`-Elemente mit `pi_flexform` liegen; falls ja, entscheidet der
   gewählte SCA-Wert, welches neue Plugin der Datensatz bekommt.
5. **TCA-Datetime-Felder:** `type => 'input'` + `eval => 'datetime'` in allen vier
   TCA-Dateien (`starttime` Z. 90-92, `endtime` Z. 101-103) sowie das Fachfeld
   `timestamp` mit `dbType => 'datetime'` in `…_order.php:113-117` und
   `…_log.php:113-117`. → auf `type => 'datetime'` migrieren. **Wichtig, sonst hängt
   die Backend-Maske am Spinner** — siehe Memory [[v13-datetime-tca-migration]] für
   Ursache/Lösung. Bei `dbType=datetime` zusätzlich `nullable => true` statt des
   Legacy-Defaults `'0000-00-00 00:00:00'` (strict-mode-sicher).
6. **Veraltete TCA-`ctrl`/`interface`-Keys** in allen vier Dateien: `cruser_id` (Z. 8,
   in v12 entfernt), `interface.showRecordFieldList` (Z. 22-24, in v12 entfernt).
   `versioningWS => true` (Z. 9) ist ok. Alle `LLL:EXT:lang/locallang_general.xlf`- und
   `LLL:EXT:lang/locallang_tca.xlf`-Pfade → `LLL:EXT:core/Resources/Private/Language/…`.
   Im Log-TCA listet `showRecordFieldList` die Felder `orderid, ordernumber`, die es in
   der Tabelle gar nicht gibt — beim Aufräumen mit entsorgen.
7. **FAL-Feld `order_image`** (`…_order.php:288`): `ExtensionManagementUtility::
   getFileFieldTCAConfig()` ist in v13 **entfernt** (im Core verifiziert — der Aufruf
   beendet den TCA-Aufbau fatal) → `type => 'file'` mit
   `allowed => 'common-image-types'`. Mit weg müssen die Konstanten
   `\TYPO3\CMS\Core\Resource\File::FILETYPE_*` und der ganze `foreign_types`-Block
   (`…_order.php:292-325`). Vorbild: `phlusereditor` #14, `phlaponot` Schritt 17.
8. **`eval => 'int'`** bei `phluserfid`/`feusefrid` (`…_order.php:125,134`) →
   `type => 'number'`.
9. **`ext_tables.sql`:** `cruser_id`, alle `t3ver_*`, `tstamp`/`crdate`/`deleted`/
   `hidden`/`starttime`/`endtime`/`sys_language_uid`/`l10n_*` verwaltet der Core heute
   automatisch aus dem TCA → auf echte Fachfelder reduzieren. Außerdem:
   - `datetime DEFAULT '0000-00-00 00:00:00'` → nullable (siehe #5),
   - **vier leere `CREATE TABLE`-Blöcke ohne Spalten** (`tx_phlorder_domain_model_
     fastorder_data:242`, `…_fastorder_token:249`, `…_feedback:256`,
     `…_sepa_lastschrift:263`) — der Schema-Analyzer von v13 verträgt das nicht,
     ersatzlos löschen,
   - `tx_phlorder_domain_model_fastorder` hat weder TCA noch Modell noch Code, **aber
     18 Zeilen Live-Daten** (in Phase 2 in der DB geprüft) → **nicht löschen**, bis
     geklärt ist, was sie schreibt und liest.

   **Achtung Datenmigration:** `order` (3511 Zeilen) und `log` (736) sind produktiv
   gefüllt. Die Umstellung von `timestamp` auf nullable (#5) ist deshalb kein reiner
   TCA-Edit — die bestehenden `'0000-00-00 00:00:00'`-Werte müssen mitgezogen werden.
10. **Extbase-DI:** `@inject`-Annotation + `protected $xRepository = null` in allen vier
    Controllern (`OrderController:26,34,42`, `ItemController:26`, `LogController:26`,
    `TokenController:26`) → **Constructor Injection** (`private readonly`) +
    `Configuration/Services.yaml`.
    **Fallstrick beim Anlegen der `Services.yaml`** (teuer gelernt in `phlusereditor`):
    der Symfony-Resource-Scan über `../Classes/*` bricht den **kompletten
    Container-Build** ab (FE **und** BE = 500), sobald eine Datei nicht sauber lädt.
    `Classes/Utility/Ajax/phlorderEid.php` ist genau so ein Fall: die Datei enthält auf
    **Dateiebene prozeduralen Code** (Z. 5-49 — `error_reporting()`, TSFE-Bootstrap,
    `$reg->main()`), der beim Einlesen sofort ausgeführt würde. Den Ordner
    `Classes/Utility/Ajax` deshalb im `exclude` lassen, bis der Worker in Phase 7
    zu einer reinen Klasse ohne Top-Level-Code umgebaut ist.
11. **Controller-Vertrag (v11+):** **jede** Action muss `ResponseInterface` liefern
    (`$this->htmlResponse()` / `$this->redirect(...)`) — aktuell geben alle `void`
    zurück. `AbstractMessage::WARNING` (8×) → `ContextualFeedbackSeverity::WARNING`.
12. **`OrderController` — Laufzeit:**
    - `$GLOBALS['TSFE']` (Z. 58, 190-191, 334, 357-376, 441, 455-456, 518, 537) → PSR-7-
      Request-Attribute (`frontend.user`, `frontend.page.information`).
    - `$_REQUEST['t']` (Z. 68, 105) → `$request->getQueryParams()`.
    - Undeklarierte Properties `$args`, `$orders`, `$phluser` (nur `var $phluser`,
      Z. 21) → typisierte Properties; dynamische Properties sind ab PHP 8.2 deprecated.
    - `listAction`/`statusAction` verwenden `$order`/`$data` **auch dann, wenn kein
      Token übergeben wurde** → „undefined variable"-Warnings; sauber vorbelegen.
    - `initStoragePid()` (Z. 423-447): `Tx_Extbase_Configuration_ConfigurationManagerInterface`
      (uralter Class-Alias) und `configurationManager->setConfiguration()` → Neuschreiben
      gegen die v13-ConfigurationManager-API.
    - `initSettingsWidthFlexform()` (Z. 391): entfällt. FlexForm-Felder in der neuen
      DS `settings.<x>` benennen, dann **mergt Extbase nativ** in `$this->settings`
      (Lehre aus `phlvote`, dort `changes.md` N.5).
    - `getPageRenderer()` mit `TYPO3_version`-Vergleich (Z. 300) → `GeneralUtility::
      makeInstance(PageRenderer::class)`, Fallback weg.
    - `initJquery()` (Z. 346): `t3jquery`/`T3JQUERY`-Zweig ist tote Uralt-Logik → raus.
    - `getCURL()` (Z. 270) mit `CURLOPT_SSL_VERIFYPEER = 0` → `RequestFactory` (PSR-18)
      mit Timeout und aktivierter TLS-Prüfung.
    - `submitMail()`/`submitOrderMail()` (Z. 512, 528) rufen die eigene eID über die
      **hart verdrahtete URL `https://local.typo76.de/index.php`** und beenden mit
      `echo`+`exit` → Testcode, ersatzlos löschen.
    - `getTestmailData()`, `fileAsCode64()`, `testAction()` sowie die
      `function ____HELPER____(){}`-Divider-Attrappen: Debug-/Toten Code entfernen.
      **26× `debugster()`** und 2× `var_dump()` in `Classes/` — `debugster()` ist
      nirgends definiert, jeder Aufruf ist ein Fatal.
13. **`OrderRepository`** (`getOrderByToken`, `getOrdernumberlatest`): setzt
    `$GLOBALS['TYPO3_DB']->debugOutput` (Z. 31, 47) — in v9 entfernt, raus.
    `getOrdernumberlatest()` zählt nur Treffer, statt die höchste Nummer zu holen —
    das ist ein **Race-/Duplikat-Risiko bei der Bestellnummer**, beim Umbau mitdenken.
14. **eID → Middleware** (`Classes/Utility/Ajax/phlorderEid.php`, 1055 Zeilen — der
    größte Brocken). `$TYPO3_CONF_VARS['FE']['eID_include']` (`ext_localconf.php:52`)
    und der prozedurale Bootstrap am Dateikopf (Z. 5-49) sind komplett tot:
    `EidUtility::initFeUser()`, `TypoScriptFrontendController`-Konstruktor,
    `connectToDB()`, `determineId()`, `initTemplate()`, `getConfigArray()`,
    `settingLanguage()`, `settingLocale()`, `Bootstrap::getInstance()->loadCachedTca()`
    — alle entfernt. → **PSR-15-Middleware** nach dem Muster von
    `phlusereditor` (`Configuration/RequestMiddlewares.php`, Marker `mw=phlorderEID`,
    Guard auf Request-Attribut `frontend.typoscript`, Delegation an einen
    self-contained Worker mit Konstruktor-DI). Weiter im Worker:
    - `ObjectManager` (Z. 385, 548, 704) → DI.
    - `StandaloneView` (Z. 512) → `ViewFactoryInterface`/`ViewFactoryData`; der
      Extbase-Request braucht `controllerExtensionName = 'Phlorder'`, damit die
      `f:translate`-Kurzkeys in den Mail-Partials auflösen.
    - `Swift_Attachment` (Z. 405) und die SwiftMailer-API `setBody`/`addPart`/`isSent`
      (Z. 391-416) → Symfony Mailer / `FluidEmail` / `attachFromPath()`.
    - `makeInstanceService('extPhluser')` (Z. 963, 1002, 1014, 1027, 1044) →
      `Pharmaline\Phlusereditor\Service\PhluserService` (siehe oben).
    - `makeInstanceService('extPhlqr')` (Z. 982) → **kein Ersatz vorhanden**, Feature
      klären (siehe „Fremd-Abhängigkeiten").
    - `$reg->conf` liest `plugin.tx_phlorder_cockpit.` (Z. 48) — dieser TS-Zweig
      **existiert nicht** (das Setup definiert `plugin.tx_phlorder_order`), die Property
      ist immer leer. Beim Umbau auf `frontend.typoscript` richtigstellen.
    - `error_reporting()` / `ini_set('display_errors', 1)` (Z. 5-6) raus.
    - Der ganze Login-Block (`loginUser`, `simpleLoginByUsername`, `LoginFeUser`,
      Z. 828-883) ist toter, kaputter Code (u.a. undefiniertes `$password`) und nutzt
      entfernte `fe_user`-APIs → löschen.
15. **PHP-8-Fatals im eID-Worker** (unquoted Strings werden als Konstante gelesen):
    `date(ymd)` (Z. 185, 307, 482), `$this->request[_f]` (Z. 122).
    Dazu echte Bugs: `$order->getOrderid` ohne Klammern (Z. 143), `$exit;` statt
    `exit;` (Z. 648, 654, 660), `$sendto` vs. `$sendTo` in Meldungen (Z. 277, 351, 355).
16. **Sicherheit — vor dem Umbau bewerten, nicht blind portieren:**
    - Secrets im Klartext: `tokenSalt`/`ordersalt`/`ut`/`ot`/`oto` in
      `Configuration/TypoScript/setup.ts:37-52`, `$SECRETSMO` in
      `phlorderEid.php:112`. Gehören in Site-Konfiguration/`.env`, nicht ins Repo.
      **Diese Werte gelten als kompromittiert und müssen bei der Migration rotiert
      werden.**
    - `isValidRequest()` (Z. 433) gibt in **beiden** Zweigen `true` zurück — die
      Prüfung ist wirkungslos.
    - `_f=smocomp` verschickt Mails ohne Hash-Prüfung; `_f=smo` nimmt Empfänger,
      Betreff und Body ungefiltert aus `$_POST` → offenes Mail-Relay.
    - `validateOrderRequest()` existiert, wird aber nirgends aufgerufen (Z. 211, 324
      auskommentiert).
17. **TypoScript → Site Set (kein `sys_template`!):** `constants.ts`/`setup.ts` →
    `constants.typoscript`/`setup.typoscript`. **`addStaticFile()` reicht nicht** — die
    Site (`config/sites/phlsitepackage/config.yaml`) arbeitet **ausschließlich über
    Site Sets** (`dependencies:` mit `pharmaline/phlvote`, `…/phlaponot`,
    `…/phlusereditor`), es gibt keine `sys_template`-Records. Also
    `Configuration/Sets/Phlorder/` anlegen (`config.yaml`, `setup.typoscript`,
    `constants.typoscript`) und `pharmaline/phlorder` in die `dependencies` der Site
    eintragen. Vorbild: `phlusereditor` Schritt 8, `phlaponot` Schritt 20.
    Alle `typo3conf/ext/phlorder/…`-Pfade → `EXT:phlorder/…`
    (setup.ts:16, 43, 56, 59-61, 94). `cssFile.0` zeigt auf `bootstrap3.css`, im Repo
    liegt aber `bootstrap.min.css`; `pathToJquery` zeigt auf `js/jquery-2.2.0.min.js`,
    die Datei liegt unter `Resources/Public/Js/`. `pathToBlindImg` verweist auf die
    **fremde, nicht vorhandene Extension `phlnewsletter`**. `qrcode.text` und
    `orderinfoPid`/`orderPid`/`forgotPid` sind auf die alte Entwicklungsumgebung
    (`local.typo76.de`, PIDs 105/106/69) verdrahtet → konfigurierbar machen.
18. **Frontend-Assets:** gebündeltes jQuery 2.2.0, Bootstrap 3, `jquery.gritter`,
    `jquery.base64`. `phlorder.js` hängt an jQuery und ruft in `controlSearch()`
    zudem eine **fremde eID (`eID: "phluserEID"`)** auf. Entscheiden: mitziehen oder
    (wie in `phlaponot` Schritt 10) auf Vanilla JS umstellen. `test()` und
    `getTestData()` sind Debug-Reste.
19. **Fluid-Templates:** `treatIdAsReference` bei `f:image`
    (`Partials/Order/Orderstatus.html:106`, `Templates/Mail/MailOrderToCompany.html:99`)
    ist in v11 entfernt → FileReference direkt übergeben. Die Mail-Templates enthalten
    ein `<head>` **innerhalb** von `<f:section name="main">` unter einem `f:layout` —
    beim Umbau auf `FluidEmail` sauber trennen. Templates für `Item`/`Log`/`Token` sind
    Extension-Builder-Boilerplate ohne Aufrufer.
    **Merke (Lehre aus `phlusereditor` #24):** Fluid löst Partial-Namen
    **case-sensitiv** auf — unter macOS funktioniert ein falsch geschriebener Name,
    im Linux-Container nicht. Für `phlorder` geprüft: alle 11 `f:render partial=`-
    Aufrufe passen exakt zu ihren Dateinamen. Bei neu hinzukommenden Partials aber
    weiter darauf achten.
    Umgekehrter Fall: `Partials/Mail/OrderForCompany.html` und
    `Partials/Mail/OrderForCustomer.html` haben **gar keinen Aufrufer** — die
    Mail-Templates rendern ihren Inhalt selbst. Beim Aufräumen klären, ob die beiden
    Partials der neuere oder der ältere Stand sind, bevor sie gelöscht werden.
20. **Sprachdateien:** XLIFF 1.0 mit **doppelten `trans-unit`-IDs**
    (`tx_phlorder_domain_model_order` in allen drei Dateien, zusätzlich `order.ready`
    in `de.locallang.xlf`) — der zweite Eintrag gewinnt stillschweigend. Bereinigen.
21. **Tests:** `Tests/Unit/**` erbt von `\TYPO3\CMS\Core\Tests\UnitTestCase` (existiert
    nicht mehr) und nutzt PHPUnit-4/5-Signaturen (`setUp()` ohne `: void`,
    `setMethods()`). Außerdem gibt es Tests für Klassen, die es nie gab:
    `ItemsTest`, `LogsTest`, `TokensTest`, `LogsControllerTest`, `TokensControllerTest`
    → löschen. Rest auf `typo3/testing-framework: ^9` heben, dazu
    `Build/phpunit/UnitTests.xml` + `phpstan.neon` + Baseline nach dem Muster von
    `phlusereditor`. **Merke:** aktuelle PHPUnit-Versionen ignorieren `@test` und
    `@dataProvider` als Annotation — **Attribute** verwenden
    (`#[Test]`, `#[DataProvider]`).
22. **`Documentation.tmpl/`** ist das Extension-Builder-Gerüst von 2018 (RST-Stubs,
    Beispielbilder). Der einzig wertvolle Inhalt — `genesis.txt` mit den eID-Beispiel-
    URLs und der Versionshistorie — ist nach `Documentation/Genesis.md` übernommen.
    Verzeichnis kann entfallen.

## Arbeitsweise

- **Umbauten in kleinen, einzeln prüfbaren Schritten** statt Sammel-Edits — nach jedem
  Schritt ein Eintrag in `Documentation/Genesis.md` (siehe Memory
  [[phl13-migration-arbeitsweise]]).
- **Reihenfolge:** siehe „Migrationsplan" (Phasen 1–9). Nicht vorgreifen — Phase 2 muss
  durch sein, bevor `extension:setup` überhaupt läuft, und ohne das ist nichts testbar.
- Vor dem eID-Umbau die **Sicherheitspunkte (#16)** mit dem User klären — hier ändert
  sich Verhalten, das nicht stillschweigend „mitmigriert" werden darf.
- Nach TCA-Änderungen: **Mutagen-/DDEV-Sync abwarten**, dann `ddev typo3 cache:flush` —
  sonst baut der TCA-Cache aus veralteten Quellen (siehe Memory
  [[v13-datetime-tca-migration]]).
- Bestehenden Code-Stil beim Editieren übernehmen: **Tabs**, deutsche Kommentare,
  `function ____DIVIDER____(){}`-Attrappen zur Gliederung. Nicht ungefragt „aufräumen";
  CS-Fixer nur in einem **separaten** Commit.
- Dateiname **muss** = Klassenname bleiben bei den bewusst nicht-PSR-konformen Klassen
  (`phlorderEid` in `Ajax/phlorderEid.php`), sonst bricht PSR-4-Autoloading.
  Namespace-Casing `Pharmaline\Phlorder` exakt (PSR-4 ist case-sensitiv).

## Code-Qualität

Nach jeder Änderung an PHP-Dateien — **im Container**, weil Host-PHP 8.3.4 unter dem
geforderten 8.4.1 liegt:

```
ddev exec "cd packages/phlorder && ../../vendor/bin/php-cs-fixer fix"
ddev exec "cd packages/phlorder && ../../vendor/bin/phpstan analyse -c phpstan.neon"
```

Eine Änderung gilt erst als fertig, wenn beide Befehle ohne Fehler durchlaufen.
`.php-cs-fixer.dist.php`, `phpstan.neon` und die Baseline gibt es hier noch nicht —
in Phase 9 nach dem Muster von `phlvote`/`phlusereditor` anlegen. Solange sie fehlen,
mindestens `php -l` über jede geänderte Datei.

Der Bestandscode ist **tab-eingerückt** mit sehr loser Klammer-/Abstandssetzung. Der
erste CS-Fixer-Lauf wird dadurch riesig → **eigener Schritt, eigener Commit**, damit
die inhaltlichen Diffs der Migration lesbar bleiben.

## Validierung

Syntax-Lint nach Edits (geht auch auf dem Host):
```
php -l <datei>
```
Klassen-Autoload prüfen (vom Repo-Root):
```
php -r 'require "vendor/autoload.php"; var_dump(class_exists("Pharmaline\\Phlorder\\Controller\\OrderController"));'
```
Bei „Class not found" trotz `composer dump-autoload`: `vendor/composer/installed.json`
cached die Paket-Autoload-Daten → `composer update pharmaline/phlorder --lock`, danach
TYPO3-Caches leeren.

Testlauf (nach Phase 9, **im Container**):
```
ddev exec "cd packages/phlorder && ../../vendor/bin/phpunit -c Build/phpunit/UnitTests.xml"
```

**BE-Smoke-Test:** Plugin platzierbar, Order/Log/Item/Token-Datensätze anlegbar,
FormEngine rendert **ohne hängenden Spinner**, Inline-Relationen (`tolog`, `toitem`)
editierbar, Bild-Upload an `order_image` funktioniert.

**FE-Smoke-Test:** `Order->list` mit angemeldetem Phluser, `Order->status` per
`?t=<orderid>` in beiden Modi (`info` ohne Login, `admin` als Inhaber), Mailversand
über den neuen Middleware-Endpunkt (`mw=phlorderEID&_f=smtco|smtcu`).

Für den FE-Pfad werden Testdaten gebraucht (Erfahrung aus `phlusereditor`):

1. FE-User **mit** `fe_groups`-Eintrag — ohne Gruppe verwirft TYPO3 die Session
   kommentarlos.
2. Ein `tt_content`-Element `felogin_login` mit FlexForm `settings.pages` =
   Speicherordner der FE-User, sonst schlägt der Login stumm fehl.
3. Ein Phluser-Datensatz, dessen `feuser` auf diesen FE-User zeigt (`phlorder`
   findet ihn über `getPhlUserByFID()`), plus mindestens eine Order mit gesetzter
   `orderid` auf der Storage-Pid.
4. Ein Inhaltselement mit dem Order-Plugin auf einer Seite.

Ablauf für den AJAX-Test: Login-Seite abrufen, `__RequestToken` aus dem Formular
ziehen, per POST (`user`, `pass`, `logintype=login`, `__RequestToken`) einloggen,
dann mit dem Cookie `…?mw=phlorderEID&_f=…` aufrufen.

## Änderungshistorie

`Documentation/Genesis.md` — vollständiges Migrationsprotokoll.