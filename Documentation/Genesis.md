# Genesis — Migrationsprotokoll phlorder (TYPO3 7.6 → 13.4)

Dieses Dokument protokolliert **alle** Änderungen der Migration der Extension
`phlorder` von TYPO3 7.6 auf 13.4. Neueste Einträge unten anhängen.

Umgebung: Monorepo `phl13`, TYPO3 13.4, PHP 8.4 (`config.platform.php = 8.4.20`),
Betrieb via DDEV.
Referenz-Extensions (bereits auf v13): `packages/phlvote`, `packages/phlusereditor`,
`packages/phlsitepackage`. Parallel in Migration: `packages/phlaponot`.

Arbeitsleitfaden: [../CLAUDE.md](../CLAUDE.md) — dort sind die Baustellen #1–#22
nummeriert, auf die sich die Schritte unten beziehen.

---

## 2026-07-29 — Schritt 0: Analyse + CLAUDE.md angelegt

Bestandsaufnahme der unveränderten 7.6-Extension (Stand `ext_emconf.php` v1.0.8,
`state = alpha`, letzte Code-Änderung 2019, letzte SQL-Änderung 2021). Kein Code
geändert.

- **Neu:** `CLAUDE.md` (Package-Root) — Leitfaden für die Migration: Zweck der
  Extension, Domänenmodell, die zwei Rendering-Pfade (Extbase-Plugin + eID), die
  Fremd-Abhängigkeiten, Migrationsziel/Umgebung und eine nummerierte Liste der
  22 bekannten Migrations-Baustellen mit Datei-/Zeilenverweisen.
- **Neu:** `Documentation/Genesis.md` (diese Datei). Der einzig inhaltlich wertvolle
  Teil von `Documentation.tmpl/genesis.txt` (eID-Beispiel-URLs, Versionshistorie
  1.0.4–1.0.7) ist unten unter „Altbestand" übernommen; der Rest von
  `Documentation.tmpl/` ist unbearbeitetes Extension-Builder-Gerüst von 2018.

### Ausgangszustand (Kurzfassung)

| Aspekt | Befund |
|--------|--------|
| Composer | **keine `composer.json`**, kein Eintrag in Root-`composer.json`, kein Symlink in `vendor/pharmaline/` → Extension aktuell **nicht geladen** |
| Git | kein eigenes Repo (anders als `phlvote`/`phlaponot`) |
| Constraint | `ext_emconf.php`: `typo3 = 7.6.0-7.6.99` |
| Plugin | eines (`Order`, Signatur `phlorder_order`), Auswahl der Funktion per **`switchableControllerActions`** |
| Controller | `Order` (548 Z., echte Logik), `Item`/`Log`/`Token` (unveränderter EB-CRUD-Boilerplate, nicht erreichbar) |
| eID | `Classes/Utility/Ajax/phlorderEid.php`, 1055 Z., via `$TYPO3_CONF_VARS['FE']['eID_include']` |
| Tabellen | 4 genutzte (`order`, `log`, `item`, `token`) + 5 Leichen in `ext_tables.sql`, davon 4 **ohne jede Spalte** |
| TypoScript | `constants.ts` / `setup.ts` (Alt-Endung), Pfade als `typo3conf/ext/…` |
| Tests | erben von `\TYPO3\CMS\Core\Tests\UnitTestCase`, PHPUnit-4/5-Signaturen, 5 Tests für nie existierende Klassen |

### Zählung der Legacy-Muster in `Classes/`

```
debugster(                   26   (Funktion nirgends definiert → jeder Aufruf ein Fatal)
$GLOBALS['TSFE']             50
makeInstanceService           9   (extPhluser ×7, extPhlqr ×1, extAuth ×1)
@inject                       7
AbstractMessage               8
$_REQUEST                     8
ObjectManager                 3
$GLOBALS['TYPO3_DB']          2
var_dump(                     2
GeneralUtility::_GP(          2
StandaloneView                1
Swift_Attachment              1
EidUtility                    1
TYPO3_version                 1
```

### Befunde, die über „reine Migration" hinausgehen

Diese Punkte sind **keine** Versionsinkompatibilitäten, sondern Fehler bzw. Risiken im
Bestandscode. Sie sind in `CLAUDE.md` unter #12–#16 festgehalten und sollten beim
Umbau nicht unbesehen mitgezogen werden:

1. **Secrets im Repo.** `tokenSalt`, `ordersalt`, `ut`, `ot`, `oto` stehen im Klartext
   in `Configuration/TypoScript/setup.ts:37-52`, `$SECRETSMO` in
   `Classes/Utility/Ajax/phlorderEid.php:112`. Gelten als kompromittiert → bei der
   Migration rotieren und nach Site-Konfiguration/`.env` verlagern.
2. **`isValidRequest()`** (`phlorderEid.php:433`) gibt in **beiden** Zweigen `true`
   zurück — die Token-Prüfung ist wirkungslos.
3. **`_f=smo`** nimmt Empfänger, Betreff und Body ungefiltert aus `$_POST`;
   **`_f=smocomp`** verschickt Mails ohne Hash-Prüfung → offenes Mail-Relay.
4. **`validateOrderRequest()`** existiert, wird aber nirgends aufgerufen
   (Aufrufe in Z. 211 und 324 sind auskommentiert).
5. **`getOrdernumberlatest()`** (`OrderRepository.php:38`) zählt nur die Treffer,
   statt die höchste vergebene Nummer zu ermitteln → Duplikat-/Race-Risiko bei der
   Bestellnummer.
6. **`$reg->conf`** liest `plugin.tx_phlorder_cockpit.` (`phlorderEid.php:48`) — dieser
   TypoScript-Zweig existiert nicht (das Setup definiert `plugin.tx_phlorder_order`),
   die Property ist seit jeher leer.
7. **PHP-8-Fatals** durch unquotierte Array-/Funktions-Argumente: `date(ymd)`
   (Z. 185, 307, 482), `$this->request[_f]` (Z. 122).
8. **QR-Code-Feature ist tot.** `makeInstanceService('extPhlqr')`
   (`phlorderEid.php:982`) verweist auf eine Extension, die im Monorepo nicht
   existiert. Vor dem Umbau klären: QR-Bibliothek einziehen oder Feature streichen.
9. **`pathToBlindImg`** (`setup.ts:94`) zeigt auf die fremde, nicht vorhandene
   Extension `phlnewsletter`; `cssFile.0` auf `bootstrap3.css`, im Repo liegt
   `bootstrap.min.css`; `pathToJquery` auf `js/…`, die Datei liegt unter
   `Resources/Public/Js/`.
10. **`phlorder.js`** ruft in `controlSearch()` die eID einer **fremden** Extension auf
    (`eID: "phluserEID"`).

### Verwertbares aus den Nachbar-Extensions

- `phlusereditor` hat den Legacy-`_sv`-Service `extPhluser` bereits ersetzt durch
  `Pharmaline\Phlusereditor\Service\PhluserService` (`Configuration/Services.yaml`,
  `public: true`) — **API bewusst unverändert** (`getUserInfo`, `getUserByToken`,
  `getUserByTokenFromPref`, `selectPrefOfUser`). `phlorder` muss nur die
  Instanziierung umstellen.
- `phlusereditor`/`phlvote` haben das Muster „eID-Skript → PSR-15-Middleware +
  self-contained Worker" bereits durchgezogen
  (`Configuration/RequestMiddlewares.php`, Marker `mw=<name>EID`, Guard auf
  Request-Attribut `frontend.typoscript`). Für Baustelle #14 als Vorlage nehmen.
- `phlvote` hat die SCA-Auflösung in getrennte Plugins bereits vorgemacht
  (`Vote`/`Votelist`) — Vorlage für Baustelle #4.
- `phlaponot` Schritt 17 zeigt die Umstellung eines FAL-Feldes auf `type => 'file'`
  (Baustelle #7), Schritt 20 das Site Set (Baustelle #17).

### Nachtrag zu Schritt 0 — Upgrade-Auftrag + Phasenplan in CLAUDE.md

`CLAUDE.md` um den expliziten Auftrag und den Weg dorthin ergänzt. Weiterhin kein
Code geändert.

- **Auftrag** und **Dokumentationspflicht** als Blockquotes am Dateikopf.
- Neuer Abschnitt **„Migrationsplan (7.6 → 13.4)"** mit den Phasen 1–9, jeweils mit
  Verweis auf die betroffenen Baustellen und ein Abnahmekriterium.
- Neuer Abschnitt **„Code-Qualität"**; „Validierung" um Testlauf, BE-/FE-Smoke-Test
  und das Rezept für FE-Testdaten erweitert.

**Gegen den v13-Core in `vendor/typo3/cms-core` verifiziert** — drei Punkte waren in
der ersten Fassung zu optimistisch bzw. fehlten ganz:

| API | vorher notiert | tatsächlich |
|-----|----------------|-------------|
| `getFileFieldTCAConfig()` | „deprecated, Entfernung in v14" | **entfernt** — Aufruf beendet den TCA-Aufbau fatal (#7) |
| `allowTableOnStandardPages()` | nicht erfasst | **entfernt** — 4× in `ext_tables.php` (#3) |
| `addLLrefForTCAdescr()` | nicht erfasst | **entfernt** — 4× in `ext_tables.php` (#3) |

Dazu: `config/sites/phlsitepackage/config.yaml` arbeitet **ausschließlich über Site
Sets** (`dependencies:`), es gibt keine `sys_template`-Records. Das `addStaticFile()`
aus `ext_tables.php:18` griffe also selbst dann ins Leere, wenn die Methode noch
existierte → `phlorder` braucht ein eigenes Site Set plus Eintrag in den
`dependencies` der Site (#17).

Weitere Erfahrungswerte aus `phlusereditor`/`phlvote` übernommen:

- **`ssch/typo3-rector` ist mit diesem Projekt inkompatibel** (verlangt
  `symfony/string ≤ 7`, Projekt ist auf v8 gelockt) → nur isoliert außerhalb des
  Repos installieren; nach jedem Lauf `php -l`.
- **Host-PHP ist 8.3.4**, das Projekt verlangt ≥ 8.4.1 (Container: 8.4.20) →
  `phpunit`/`phpstan`/`php-cs-fixer` nur via `ddev exec`.
- **`Services.yaml`-Fallstrick:** der Resource-Scan über `../Classes/*` reißt den
  kompletten Container-Build (FE **und** BE) um, wenn eine Datei nicht sauber lädt.
  `Classes/Utility/Ajax/phlorderEid.php` hat prozeduralen Code auf Dateiebene
  (Z. 5-49) → Ordner bis Phase 7 im `exclude` halten (#10).
- **FlexForm-Regeln** beim Auflösen von `switchableControllerActions` (Werte
  erhalten, FlexForms pro CType zuschneiden, Upgrade Wizard) (#4).
- **Fluid-Partials werden case-sensitiv aufgelöst** — für `phlorder` geprüft: alle 11
  `f:render partial=`-Aufrufe passen. Dabei aufgefallen: `Partials/Mail/
  OrderForCompany.html` und `OrderForCustomer.html` haben **keinen Aufrufer** (#19).
- Aktuelle PHPUnit-Versionen ignorieren `@test`/`@dataProvider` → Attribute (#21).

## 2026-07-29 — Phase 1: Composer-Anbindung

Ziel: Die Extension überhaupt vom Composer verwalten und von TYPO3 laden lassen.
Noch **keine** Code-Änderung an `Classes/`, TCA oder Templates.

- **Neu:** `composer.json` (Package-Root) nach dem Muster von `phlusereditor`:
  - `name: pharmaline/phlorder`, `type: typo3-cms-extension`
  - `extra.typo3/cms.extension-key: phlorder`
  - PSR-4-Autoload `Pharmaline\Phlorder\ → Classes/` (+ `…\Tests\ → Tests/`)
  - `require`: PHP `^8.2`, `typo3/cms-core|extbase|fluid|frontend|backend` je `^13.4`
  - **zusätzlich `pharmaline/phlusereditor: @dev`** — anders als bei den
    Schwester-Extensions ist das hier eine **echte** Abhängigkeit: `OrderController`
    injiziert `PhluserRepository`/`PhlfrefsRepository`, der eID-Worker holt Userdaten
    über den (künftigen) `PhluserService`.
  - `require-dev`, `scripts`, `config.allow-plugins`, `replace` von `phlusereditor`
    übernommen
- **Geändert:** `ext_emconf.php`
  - `$EM_CONF[$_EXTKEY]` → `$EM_CONF['phlorder']` (in v13 ist `$_EXTKEY` im Scope
    dieser Datei nicht mehr gesetzt)
  - `depends.typo3`: `7.6.0-7.6.99` → `13.4.0-13.4.99`
  - `depends.php`: neu `8.2.0-8.5.99`; `depends.phlusereditor`: neu `13.0.0-13.99.99`
  - `version`: `1.0.8` → `13.0.0`
  - leere `description` gefüllt
  - veraltete Keys entfernt: `internal`, `uploadfolder`, `createDirs`, `clearCacheOnLoad`
- **Geändert:** Root-`composer.json` — `"pharmaline/phlorder": "@dev"` im `require`
  ergänzt (alphabetisch bei den anderen pharmaline-Paketen).
- **Ausgeführt:** `ddev composer update pharmaline/phlorder`

### Ergebnis

- Symlink steht: `vendor/pharmaline/phlorder → ../../packages/phlorder`
- Lockfile-Eintrag `pharmaline/phlorder dev-main`
- PSR-4-Autoloading verifiziert (**im Container**, Host-PHP 8.3.4 scheitert am
  `platform_check.php`, gefordert ≥ 8.4.1):
  ```
  ddev exec 'php -r "require \"vendor/autoload.php\"; var_dump(class_exists(...));"'
  → OrderController: true, Domain\Model\Order: true
  ```
  *(Der eID-Worker wurde bewusst **nicht** per `class_exists()` geprüft — die Datei
  führt beim Laden prozeduralen Code aus, siehe Baustelle #10/#14.)*
- `phlorder` steht in `vendor/typo3/PackageArtifact.php` → TYPO3 lädt die Extension.

### ⚠ Zustand nach Phase 1: Instanz ist offline

Die Post-Install-Skripte melden 3× `Access denied.`, und zwar **nicht nur** in der CLI:

```
curl https://phl13.ddev.site/       → HTTP 200, Body: "Access denied."
curl https://phl13.ddev.site/typo3/ → HTTP 200, Body: "Access denied."
```

Ursache ist `ext_localconf.php:2` — `defined('TYPO3_MODE') || die('Access denied.')`.
Die Konstante `TYPO3_MODE` gibt es seit v12 nicht mehr, also greift der `die()` bei
**jedem** Request und beendet den TYPO3-Bootstrap, sobald die Extension registriert
ist (Baustelle #1).

**Das ist der erwartete Übergangszustand** (so angekündigt im Migrationsplan und
identisch zum Verlauf bei `phlaponot`), aber FE und BE sind bis zum Abschluss von
Phase 2 nicht benutzbar. Ein Teilfix bringt nichts: entfernt man nur den Guard, läuft
`ext_localconf.php:32` in `ExtensionManagementUtility::extRelPath()` — in v9 entfernt —
und stirbt mit *Call to undefined method*. Die Instanz kommt erst mit dem kompletten
Bootstrap-Umbau (Baustellen #1–#3) zurück.

### Nächster Schritt (offen)

Phase 2 — Bootstrap reparieren (#1, #2, #3): `TYPO3_MODE`-Guards, `$_EXTKEY`,
`extRelPath()`, Plugin-Registrierung ohne Vendor-Präfix, `ext_tables.php` auflösen
(die drei darin genutzten, in v13 entfernten APIs ersetzen). Abnahme:
`ddev typo3 extension:setup` läuft durch, FE und BE antworten wieder.

## 2026-07-29 — Phase 2: Bootstrap repariert, Instanz wieder online

Ziel: Extension lädt, Plugin ist registrierbar, FE/BE laufen wieder.

- **Geändert:** `ext_localconf.php`
  - Guard `defined('TYPO3_MODE') || die('Access denied.')` → `defined('TYPO3') or die();`
  - `call_user_func(function($extKey){…}, $_EXTKEY)`-Wrapper entfernt — `$_EXTKEY` ist
    in v13 im Scope dieser Datei nicht mehr gesetzt.
  - `configurePlugin('Pharmaline.Phlorder', …)` → `configurePlugin('Phlorder', …)`.
    Die Controller-Keys sind jetzt **FQCN** (`OrderController::class` usw.) statt
    Kurznamen — sonst LogicException 1679051921 beim FE-Dispatch (Lehre aus
    `phlusereditor` #5).
  - Wizard-Block (`addPageTSConfig` mit `mod.wizards.newContentElement…`) **entfernt**:
    er nutzte das in v9 entfernte `extRelPath()`, und `registerPlugin()` legt den
    Wizard-Eintrag selbst an.
  - eID-Registrierung: schrieb bisher auf das **lokale** `$TYPO3_CONF_VARS`, das es im
    Scope dieser Datei nicht (mehr) gibt → die eID war faktisch **nie registriert**.
    Auf `$GLOBALS['TYPO3_CONF_VARS']` korrigiert und mit TODO auf Phase 7 versehen.
    (`eID_include` existiert in v13 noch, ausgewertet von
    `TYPO3\CMS\Frontend\Middleware\EidHandler` — der Worker selbst bleibt aber kaputt.)
- **Neu:** `Configuration/TCA/Overrides/tt_content.php` — `registerPlugin('Phlorder',
  'Order', 'Bestellung')`, dazu `subtypes_addlist` + `addPiFlexFormValue` mit der
  zurückgegebenen Plugin-Signatur. Bleibt vorerst `list_type` (wie `phlvote`/
  `phlaponot`); die Entscheidung CType-vs-`list_type` fällt in Phase 6.
- **Gelöscht:** `ext_tables.php`. Die vier darin genutzten APIs:
  - `registerPlugin` + `subtypes_addlist` + `addPiFlexFormValue` → nach `tt_content.php`
  - `allowTableOnStandardPages()` (4×, **in v13 entfernt**) →
    `ctrl.security.ignorePageTypeRestriction = true` in allen vier TCA-Dateien
  - `addLLrefForTCAdescr()` (4×, **in v13 entfernt**) → ersatzlos gestrichen
    (die vier `locallang_csh_*.xlf` liegen jetzt ungenutzt herum, Aufräumen in Phase 9)
  - `addStaticFile()` → **noch offen**, kommt in Phase 8 als Site Set. Bis dahin lädt
    die Extension **kein TypoScript**.

### Zwei Punkte aus späteren Phasen vorgezogen (sonst kein lauffähiger Zustand)

1. **#7 — `getFileFieldTCAConfig()`** (`…_order.php:291`). Der Aufruf brach
   `cache:flush` und `extension:setup` hart ab:
   ```
   Call to undefined method ...ExtensionManagementUtility::getFileFieldTCAConfig()
   ```
   → `order_image` auf `type => 'file'`, `allowed => 'common-image-types'`,
   `maxitems => 5` umgestellt. Der komplette `foreign_types`-Block samt der
   `File::FILETYPE_*`-Konstanten und der `LLL:EXT:lang/…`-Palettenpfade ist damit weg.
2. **#9 (Teil) — leere `CREATE TABLE`-Blöcke.** Danach brach `extension:setup` mit
   `NoColumnsSpecifiedForTable "tx_phlorder_domain_model_fastorder_data"` ab.
   → Die vier spaltenlosen Blöcke (`…_fastorder_data`, `…_fastorder_token`,
   `…_feedback`, `…_sepa_lastschrift`) aus `ext_tables.sql` entfernt.
   **Vorher in der DB geprüft: keine dieser vier Tabellen existiert** — kein Datenverlust.
   Der Rest von #9 (`t3ver_*`, `cruser_id`, Systemspalten, nullable datetime) bleibt
   in Phase 4.

### Datenbestand — wichtig für Phase 4

Beim Prüfen der Tabellen kam heraus, dass hier **produktive Daten** liegen:

| Tabelle | Zeilen |
|---|---|
| `tx_phlorder_domain_model_order` | **3511** |
| `tx_phlorder_domain_model_log` | **736** |
| `tx_phlorder_domain_model_token` | 37 |
| `tx_phlorder_domain_model_fastorder` | **18** |
| `tx_phlorder_domain_model_item` | 0 |

Zwei Folgerungen:

- **`tx_phlorder_domain_model_fastorder` NICHT löschen.** In `CLAUDE.md` #9 stand
  „weder TCA noch Modell noch Code — vor dem Löschen prüfen, ob live Daten drinstehen".
  Antwort: ja, 18 Zeilen. Die Tabelle bleibt, bis geklärt ist, was sie speist.
- Die Umstellung von `timestamp` (`dbType=datetime`, Default `'0000-00-00 00:00:00'`)
  auf nullable trifft in Phase 4 **3511 bestehende Order-Zeilen** und 736 Log-Zeilen.
  Das braucht eine Datenmigration, keinen reinen TCA-Edit.

### Ergebnis / Abnahme

```
ddev typo3 extension:setup            → [OK] ... phlorder ... successfully set up.
ddev typo3 database:updateschema      → Change fields: 45
curl https://phl13.ddev.site/         → HTTP 200, gerendertes Sitepackage-HTML
curl https://phl13.ddev.site/typo3/   → HTTP 200
```

Datenbestand nach dem Schema-Update unverändert (3511 / 736 / 0 / 37 / 18).

Registrierung in der kompilierten TCA verifiziert:

```
list_type-Item  : phlorder_order  (Bestellung)
subtypes_addlist: pi_flexform
FlexForm-DS     : [phlorder_order,list]        (list_type-Form, wie phlaponot/phlvote)
order_image type: file
ignorePageType  : true
```

Legacy-Sweep über das Package: `TYPO3_MODE`, `$_EXTKEY`, `extRelPath`,
`allowTableOnStandardPages`, `addLLrefForTCAdescr`, `getFileFieldTCAConfig` und der
Vendor-Präfix `Pharmaline.Phlorder` kommen **nur noch in Erklärkommentaren** vor,
kein einziger echter Aufruf mehr.

### Offen / bewusst nicht angefasst

- Kein TypoScript geladen (Site Set fehlt, Phase 8) → das Plugin rendert im FE noch
  nicht sinnvoll.
- Der eID-Worker ist registriert, aber weiterhin kaputt (Phase 7).
- Die FlexForm enthält noch `switchableControllerActions` (Phase 6).
- TCA-Datetime-Felder unverändert → **die BE-Maske hängt vermutlich noch am Spinner**
  (#5). Das ist der erste Punkt in Phase 4.

### Nächster Schritt (offen)

Phase 3 — Rector-Lauf (isoliert installiert, siehe CLAUDE.md „Rector-Warnung"),
danach Phase 4 (TCA + Datenbank).

## 2026-07-29 — Phase 4: TCA + Datenbank (Phase 3 übersprungen)

Phase 3 (Rector) auf Wunsch übersprungen — die TCA-Arbeit wurde von Hand gemacht.

**Vor jeder Datenänderung** wurden alle fünf Tabellen gedumpt
(`mysqldump` → Scratchpad, 1,2 MB, 5 `INSERT`-Statements).

### TCA (alle vier Dateien, #5, #6, #8)

- `ctrl.cruser_id` entfernt (in v12 entfernt).
- `interface`-Block mit `showRecordFieldList` entfernt (in v12 entfernt).
- **Systemspalten aus `columns` gelöscht** — `sys_language_uid`, `l10n_parent`,
  `l10n_diffsource`, `t3ver_label`, `hidden`, `starttime`, `endtime`. Der Core
  erzeugt sie über **TcaEnrichment** v13-korrekt aus der `ctrl`. Damit sind auch alle
  `LLL:EXT:lang/…`-Pfade weg (die saßen ausschließlich in diesen Spalten).
  Vorbild: `phlusereditor` #15. Dateigrößen: item 192→111, log 203→122,
  order 347→266, token 160→79 Zeilen.
- **`timestamp`** in `order` und `log`: `type=input` + `eval=datetime` +
  `default='0000-00-00 00:00:00'` → **`type=datetime`**, `dbType=datetime`,
  `nullable=true`. Das war die Spinner-Ursache (Memory
  [[v13-datetime-tca-migration]]).
- **`eval => 'int'`** → `type => 'number'` (order: `phluserfid`, `feusefrid`;
  token: `phluserfid`).
- `token.timestamp` bleibt bewusst `type=input`: die Spalte ist `varchar(255)`,
  kein Datumsfeld.

### ext_tables.sql (#9, Rest)

Auf Fachspalten reduziert; `uid`, `pid`, `tstamp`, `crdate`, `cruser_id`, `deleted`,
`hidden`, `starttime`, `endtime`, `sys_language_uid`, `l10n_*`, alle `t3ver_*` sowie
`PRIMARY KEY`/Indizes legt `DefaultTcaSchema` aus der `ctrl` an.
`timestamp`/`regdate` auf `datetime DEFAULT NULL`.

**Zwei Korrekturen am eigenen ersten Wurf** (beide vor dem Schema-Lauf gefunden):

1. Das Entfernen der `PRIMARY KEY`-Zeilen ließ **hängende Kommas** vor `);` zurück —
   ungültiges SQL.
2. `tx_phlorder_domain_model_fastorder` hat **keine TCA** → der Core kann dort
   nichts ergänzen. Die Tabelle behält deshalb `uid`, `pid`, `PRIMARY KEY` und die
   Enable-Spalten vollständig. (Sie hat 18 produktive Zeilen, siehe Phase 2.)

### Datenmigration

```sql
UPDATE tx_phlorder_domain_model_log       SET timestamp = NULL WHERE timestamp = '0000-00-00 00:00:00';  -- 716 Zeilen
UPDATE tx_phlorder_domain_model_order     SET timestamp = NULL WHERE timestamp = '0000-00-00 00:00:00';  -- 0 Zeilen
UPDATE tx_phlorder_domain_model_fastorder SET regdate   = NULL WHERE regdate   = '0000-00-00 00:00:00';  -- 18 Zeilen
```

Vorher gemessen: `order` hatte **keine** Nulldaten (alle 3511 mit echten Werten,
2020-01-08 bis 2026-07-28), `log` dagegen **716 von 736**. Der DB-`sql_mode` ist
`STRICT_TRANS_TABLES` **ohne** `NO_ZERO_DATE`, die Altwerte waren also bisher toleriert.

### Ergebnis / Abnahme

```
ddev typo3 cache:flush            → ohne Fehler
ddev typo3 database:updateschema  → Change fields: 32
curl …/ → HTTP 200      curl …/typo3/ → HTTP 200
```

Datenbestand unverändert: 3511 / 736 / 0 / 37 / 18.
Systemspalten in `order` weiterhin vollständig vorhanden (der Core hat sie aus der
`ctrl` erzeugt, nichts ging verloren). `timestamp` jetzt `datetime NULL DEFAULT NULL`,
`starttime`/`endtime` `int unsigned NOT NULL DEFAULT 0` — genau das v13-Verhalten.

**FormEngine headless verifiziert** (temporäres Skript mit `FormDataCompiler` +
`TcaDatabaseRecord`, danach entfernt):

```
OK  tx_phlorder_domain_model_order   Felder=28  timestamp=datetime starttime=datetime endtime=datetime
OK  tx_phlorder_domain_model_log     Felder=14  timestamp=datetime starttime=datetime endtime=datetime
OK  tx_phlorder_domain_model_item    Felder=13             starttime=datetime endtime=datetime
OK  tx_phlorder_domain_model_token   Felder=10  timestamp=input    starttime=datetime endtime=datetime
```

Legacy-Sweep über `Configuration/TCA/`: `cruser_id`, `interface`,
`eval => 'int'`, `eval => 'datetime'`, `LLL:EXT:lang`, `showRecordFieldList` —
**je 0 Treffer** außerhalb von Kommentaren.

### ⚠ Neuer Blocker: Storage-Pid 29 existiert nicht

Beim FormEngine-Test mit `command => 'edit'` brachen `order` und `log` ab:

```
DatabaseRecordException: Record with uid 29 from table pages not found
```

Ursache ist **kein** TCA-Problem, sondern die Datenlage:

| Tabelle | pid | Zeilen | Seite |
|---|---|---|---|
| `order` | 29 | 3511 | **existiert nicht** (auch nicht als `deleted=1`) |
| `log` | 29 | 736 | **existiert nicht** |
| `token` | 0 | 37 | – (Root) |

Vorhandene Sysordner in dieser Instanz: 6 (Footer Menu), 11 (fe_users),
12 (Umfragen), **13 (phlusers)**. Der phlorder-Speicherordner wurde beim Aufsetzen
der v13-Instanz nie angelegt — dasselbe Muster wie bei `phlusereditor`
(Memory [[phl13-phlusereditor-daten]]: Altdaten auf pid 13).

Deshalb wurde für den TCA-Nachweis auf `command => 'new'` mit pid 13 ausgewichen —
dort kompilieren alle vier Tabellen sauber.

**Bewusst nicht entschieden:** ob ein Sysordner „phlorder" neu angelegt und die 4247
Datensätze dorthin verschoben werden, oder ob eine Seite mit uid 29 rekonstruiert
wird. Das Verschieben von Produktivdaten ist eine Entscheidung des Users und hängt
außerdem an der `storagePid`, die erst in Phase 8 (Site Set) gesetzt wird.

### Nächster Schritt (offen)

Erst die Storage-Pid-Frage klären (siehe oben), dann Phase 5 — Controller,
Repository, Models (#10–#13).

## 2026-07-29 — Nachtrag Phase 4: Sysordner „phlorder" angelegt, Datensätze verschoben

Auf Anweisung des Users: der fehlende Speicherordner wurde neu angelegt (nicht
pid 29 rekonstruiert) und die verwaisten Datensätze dorthin verschoben.

**Vorprüfung** — der Fall ist sauber, ein reiner `pid`-Update reicht:

| Tabelle | Zeilen | Übersetzt | `l10n_parent` | WS-Version | gelöscht |
|---|---|---|---|---|---|
| `order` | 3511 | 0 | 0 | 0 | 0 |
| `log` | 736 | 0 | 0 | 0 | 0 |
| `token` | 37 | 0 | 0 | 0 | 0 |

Dazu: **0** Einträge in `sys_file_reference` für
`tablenames='tx_phlorder_domain_model_order'` — das FAL-Feld `order_image` wurde nie
benutzt, es hängen also keine Dateireferenzen an den Bewegungen.

- **Backup:** `mysqldump` von `pages` + allen fünf phlorder-Tabellen vor dem Eingriff.
- **Neu:** Sysordner `phlorder` (`pages.uid = 17`, `pid = 1`, `doktype = 254`),
  angelegt über den **DataHandler** (nicht per SQL-INSERT), damit Sortierung,
  Berechtigungen und Zeitstempel korrekt gesetzt sind. Liegt damit neben den
  bestehenden Ordnern 6 (Footer Menu), 11 (fe_users), 12 (Umfragen), 13 (phlusers).
- **Verschoben:**
  ```sql
  UPDATE tx_phlorder_domain_model_order SET pid = 17 WHERE pid = 29;  -- 3511
  UPDATE tx_phlorder_domain_model_log   SET pid = 17 WHERE pid = 29;  --  736
  UPDATE tx_phlorder_domain_model_token SET pid = 17 WHERE pid =  0;  --   37
  ```
  Die Tokens lagen auf pid 0 (Root) und waren damit ebenfalls nicht editierbar —
  sie sind mit umgezogen.
- **`referenceindex:update`** gelaufen: 6514 Datensätze aus 50 Tabellen geprüft,
  1300 Aktualisierungen.

**Bewusst NICHT verschoben:** `tx_phlorder_domain_model_fastorder` (18 Zeilen,
weiterhin pid 0). Die Tabelle hat weder TCA noch Modell noch Code — TYPO3 verwaltet
sie nicht, ein pid-Wechsel brächte im Backend nichts und könnte ein externes System
stören, das die Zeilen über die pid findet. Bleibt offen (CLAUDE.md #9).

### Abnahme

FormEngine jetzt mit `command => 'edit'` auf **bestehenden** Datensätzen geprüft
(temporäres Skript, danach entfernt) — das ist der eigentliche Spinner-Test:

```
OK  tx_phlorder_domain_model_order  uid=152  Felder=28  timestamp='2020-01-08T19:40:34+00:00'
OK  tx_phlorder_domain_model_order  uid=190  Felder=28  timestamp='2020-01-10T13:21:00+00:00'
OK  tx_phlorder_domain_model_log    uid=1    Felder=14  timestamp='2018-11-08T18:47:39+00:00'
OK  tx_phlorder_domain_model_token  uid=1    Felder=10  timestamp='2019-01-28 15:29:38'
```

Auch die in Phase 4 auf NULL migrierten Log-Zeilen kompilieren sauber:

```
OK  tx_phlorder_domain_model_log    uid=20   Felder=14  timestamp=NULL
OK  tx_phlorder_domain_model_log    uid=21   Felder=14  timestamp=NULL
```

Damit liefert die FormEngine gültige ISO8601-Strings bzw. NULL statt der `0`, die den
hängenden Lade-Spinner ausgelöst hat (Memory [[v13-datetime-tca-migration]]).

Endstand: alle 4284 Datensätze (3511 + 736 + 37) liegen im Ordner „phlorder".
FE und BE antworten mit HTTP 200. Repo-Root ist frei von Hilfsskripten.

### Nächster Schritt (offen)

Phase 5 — Controller, Repository, Models (#10–#13): Constructor-Injection +
`Services.yaml`, `ResponseInterface` in jeder Action, PSR-7 statt
`$GLOBALS['TSFE']`/`$_REQUEST`, typisierte Properties, `debugster()`/Testcode raus.
**Achtung:** beim Anlegen der `Services.yaml` den Ordner `Classes/Utility/Ajax`
ausschließen (CLAUDE.md #10).

## 2026-07-29 — Phase 5: Controller, Repository, Models

- **Neu:** `Configuration/Services.yaml` — Autowiring über `../Classes/*`, mit
  Ausschluss von `Domain/Model/*` **und `Utility/Ajax/*`**. Letzteres ist zwingend:
  `phlorderEid.php` führt auf Dateiebene prozeduralen Code aus; würde der
  Symfony-Resource-Scan die Datei einlesen, bräche der Container-Build für FE **und**
  BE (Lehre aus `phlusereditor` #8). Der Ausschluss fällt erst mit Phase 7.

### Controller (#10, #11, #12)

Alle vier: `@inject`-Property → **Constructor Injection** (`private readonly`),
**jede Action liefert `ResponseInterface`** (`htmlResponse()` / `redirect()`),
`AbstractMessage::WARNING` → `ContextualFeedbackSeverity::WARNING`.

`ItemController`, `LogController`, `TokenController` waren unveränderter
Extension-Builder-CRUD und sind damit fertig.

`OrderController` (548 → 331 Zeilen) zusätzlich:

- **PSR-7 statt Globals:** `$_REQUEST['t']` → `getRequestToken()`
  (`$this->request->getQueryParams()`), `$GLOBALS['TSFE']->id` →
  `getCurrentPageId()` (Request-Attribut `frontend.page.information`),
  `$GLOBALS['TSFE']->fe_user->user` → `getFrontendUserRecord()` (`frontend.user`).
  Muster 1:1 von `phlusereditor` übernommen.
- **Typisierte Properties** `?Phluser $phluser`, `?QueryResultInterface $orders`
  statt `var $phluser` und dynamischer Properties (ab PHP 8.2 deprecated).
- **Echter Bug behoben:** `listAction()`/`statusAction()` benutzten `$order` und
  `$data` bedingungslos im `view->assign()`, setzten sie aber nur **innerhalb** des
  Token-Zweigs → „undefined variable", sobald kein `?t=` in der URL stand. Jetzt
  sauber vorbelegt.
- **`initStoragePid()`** neu gegen die v13-API: der entfernte Class-Alias
  `Tx_Extbase_Configuration_ConfigurationManagerInterface` ist weg, der Zweig auf
  `$this->settings['ff']['sourceDB']` ebenfalls (das FlexForm-Feld existiert nicht).
- **`initSettingsWidthFlexform()` entfernt** — FlexForm-Felder heißen ab Phase 6
  `settings.<x>` und werden von Extbase nativ gemergt.
- **`getPageRenderer()`** ohne `TYPO3_version`-Weiche; `initCSS()`/`initJquery()`
  benutzen jetzt den übergebenen PageRenderer statt `$GLOBALS['TSFE']->getPageRenderer()`.
  Der `t3jquery`-Zweig (`require_once class.tx_t3jquery.php`, Konstante `T3JQUERY`)
  ist entfallen — die Extension gibt es seit Jahren nicht mehr.
- **Gelöscht** (alles ohne Aufrufer bzw. reiner Debug-Code): `testAction()`,
  `getTestmailData()`, `fileAsCode64()`, `getCURL()`, `submitMail()`,
  `submitOrderMail()`, `initFalStorageFolder()`, `serviceGetUser()`,
  `updateRemoteViaService()`. Damit sind auch die hart verdrahtete Entwickler-URL
  `https://local.typo76.de/index.php` und alle `debugster()`/`var_dump()`-Aufrufe
  aus dem Controller raus.
  Vorher geprüft: `serviceGetUser`/`updateRemoteViaService` haben im Controller
  **null** Aufrufer (die Grep-Treffer waren die eigenen Kopien im eID-Worker, die
  bis Phase 7 bleiben); die Treffer auf `getTestmailData`/`submitOrderMail` waren
  auskommentierte Zeilen.
- **`phlfrefsRepository` entfernt** — die Property war deklariert, aber nirgends
  benutzt. `composer.json`/`ext_emconf.php` behalten die Abhängigkeit auf
  `phlusereditor`, weil `PhluserRepository` weiterhin gebraucht wird.
- **`ext_localconf.php`** nachgezogen: `test` aus der Action-Liste von
  `configurePlugin` entfernt. Der SCA-Eintrag `Order->test` steht noch in der
  FlexForm und verschwindet in Phase 6.

### Repository (#13)

- `$GLOBALS['TYPO3_DB']->debugOutput = true` (2×, seit v9 entfernt) gelöscht.
- `getOrdernumberlatest()`: `logicalAnd()` bekam ein **Array**, ist aber seit v11
  variadisch → auf `matching()` mit einer Bedingung vereinfacht.
- Die fehlerhafte Bestellnummern-Logik ist **bewusst nicht** geändert, aber im
  Docblock dokumentiert: der einzige Aufrufer (eID-Worker) bildet die nächste Nummer
  aus `$result->count()`, was bei gelöschten oder gleichzeitigen Bestellungen
  Duplikate erzeugt. Gehört zum Worker-Umbau in Phase 7.

### Models

- `Order::$timestamp` und `Log::$timestamp`: Docblock `@var \DateTime|null`, Setter
  `setTimestamp(?\DateTime $timestamp)`. Notwendig, seit die Spalte in Phase 4
  nullable ist — 716 Log-Zeilen liefern jetzt NULL, der alte Setter hätte einen
  TypeError geworfen.
- **Beim Test aufgefallen:** `Order.php` hatte 3× die Alt-Annotation
  `@cascade remove` (bei `orderImage`, `tolog`, `toitem`). Doctrine bricht in v13
  darauf hart ab:
  ```
  [Semantical Error] The annotation "@cascade" in property ...::$orderImage was never imported.
  ```
  → auf das im Repo übliche PHP-Attribut umgestellt:
  `#[\TYPO3\CMS\Extbase\Annotation\ORM\Cascade(['value' => 'remove'])]`
  (gleiche Form wie `phlvote`/`phlusereditor`). **Das stand in keiner Baustelle** —
  gefunden, weil der Verifikations-Lauf echte Objekte gemappt hat.

### Abnahme

Verifikationsskript (temporär, danach entfernt) im BE-Kontext:

```
DI  OK   OrderController  (orderRepository:OrderRepository, phluserRepository:PhluserRepository)
DI  OK   ItemController   (itemRepository:ItemRepository)
DI  OK   LogController    (logRepository:LogRepository)
DI  OK   TokenController  (tokenRepository:TokenRepository)

Actions OrderController  4 Actions, alle ResponseInterface
Actions ItemController   4 Actions, alle ResponseInterface
Actions LogController    5 Actions, alle ResponseInterface
Actions TokenController  4 Actions, alle ResponseInterface

Repo getOrderByToken('c5e3ed1a-dfd5...') -> 1 Treffer
     uid=152  ordernumber=0028M0005  timestamp=2020-01-08 19:40:34 (DateTime)
Repo getOrdernumberlatest(28) -> 264 Treffer
Log mit timestamp=NULL -> 1 gefunden, uid=20 getTimestamp()=NULL
```

Damit ist belegt: DI löst über den Container auf, alle 17 Actions sind
v13-konform, das Repository liefert echte Treffer aus der DB, `timestamp` mappt
sowohl auf `DateTime` als auch auf `NULL`.

*(Randnotiz: die 264 Treffer bei `getOrdernumberlatest(28)` zeigen genau das oben
beschriebene Problem — die nächste Bestellnummer wäre schlicht „264".)*

`php -l` über alle `Classes/`-Dateien sauber, FE und BE HTTP 200. In
`Classes/Controller` und `Classes/Domain` gibt es keinen echten Treffer mehr auf
`@inject`, `AbstractMessage`, `@cascade`, `$GLOBALS['TYPO3_DB']`, `debugster()`,
`var_dump()`, `makeInstanceService` oder `ObjectManager` — nur noch in Erklärkommentaren.

**Unverändert (Phase 7):** `Classes/Utility/Ajax/phlorderEid.php` mit seinen 1055
Zeilen, allen Globals, `ObjectManager`, `StandaloneView`, `Swift_Attachment` und den
PHP-8-Fatals.

### Nächster Schritt (offen)

Phase 6 — Plugin-Aufteilung (#4): `switchableControllerActions` auflösen, getrennte
Plugins für `Display` und `Status`, FlexForm neu schneiden.

## 2026-07-29 — Phase 6: Plugin-Aufteilung (#4)

### Ausgangslage geprüft

```sql
SELECT ... FROM tt_content WHERE list_type LIKE 'phlorder%' OR CType LIKE 'phlorder%';
→ 0 Zeilen
```

**Es existiert keine einzige `tt_content`-Instanz von phlorder.** Damit entfällt
Regel 4 der FlexForm-Migration (Upgrade Wizard) komplett — es gibt keine Werte zu
retten. Und die Wahl `list_type` vs. CType ist frei.

**Entscheidung: eigene CTypes** (`PLUGIN_TYPE_CONTENT_ELEMENT`), nicht `list_type`.
Begründung: `list_type` ist in v13 deprecated und fällt in v14 weg, `phlusereditor`
hat den Schritt in derselben Situation (auch dort keine Datensätze) bereits gemacht,
und ohne Bestandsdaten ist er risikofrei. `phlvote` und `phlaponot` stehen weiterhin
auf `list_type` — beim Vergleichen der Extensions daran denken.

### Zwei Plugins statt SCA

Die alten SCA-Varianten aus `flexform_order.xml`:

| SCA-Eintrag | Actions | wird zu |
|---|---|---|
| Display | `Order->list;Order->status` | Plugin **`Order`** (`phlorder_order`), Default `list` |
| Status | `Order->status;Order->list` | Plugin **`Orderstatus`** (`phlorder_orderstatus`), Default `status` |
| Test | `Order->test` | **entfällt** (`testAction` war Debug-Code, in Phase 5 gelöscht) |

- **Geändert:** `ext_localconf.php` — zwei `configurePlugin`-Aufrufe mit
  `PLUGIN_TYPE_CONTENT_ELEMENT`. `status` bleibt in beiden non-cacheable
  (wie vorher).
- **Geändert:** `Configuration/TCA/Overrides/tt_content.php` — zwei `registerPlugin`
  mit Icon `user_plugin_order.svg`, keine FlexForm-Zuordnung mehr.
- **Gelöscht:** `Configuration/FlexForms/flexform_order.xml` (samt Verzeichnis).
  Die Datei enthielt **ausschließlich** `switchableControllerActions` — nach dessen
  Wegfall bleibt kein einziges Feld übrig, das an ein Plugin zu hängen wäre.
  Regel 2 der FlexForm-Migration („pro CType zuschneiden") läuft hier also auf
  „keine FlexForm" hinaus.

### Sicherheitsrelevante Verkleinerung der Angriffsfläche

Bisher registrierte `configurePlugin` **vier** Controller mit zusammen 17 Actions,
während die SCA nur `Order->list`, `Order->status` und `Order->test` freigab.
Ohne SCA ist aber **jede registrierte Action per URL dispatchbar** — die alte
Registrierung 1:1 zu übernehmen hätte u. a. drei ungeschützte `delete`-Actions
öffentlich gemacht (die Extension-Builder-Meldung dazu lautet wörtlich
„publicly accessible unless you implement an access check").

Deshalb registrieren beide Plugins jetzt **nur `list` und `status`** — exakt das,
was vorher über die SCA erreichbar war:

- `Order->show` und `Order->delete`: nicht mehr registriert. Beide Methoden bleiben
  vorerst in der Klasse (für `Show.html`), sind aber im Docblock als *nicht
  registriert* markiert. `deleteAction` braucht vor einer Reaktivierung einen
  Owner-Check.
- **`LogController`, `ItemController`, `TokenController` sind gar nicht mehr
  registriert.** Sie waren über die SCA nie erreichbar und sind damit jetzt
  vollständig toter Code — samt ihrer Templates (`Templates/{Log,Item,Token}/*`)
  und teilweise ihrer Repositories. Löschen ist Phase 9.

### Abnahme

Kompilierte TCA:

```
### CType-Items ###
  phlorder_order           Bestellung (Cockpit)
  phlorder_orderstatus     Bestellung (Status)

### list_type-Altlast ###   keine (korrekt - jetzt eigene CTypes)
### FlexForm-Reste ###      keine (korrekt)
```

Extbase-Plugin-Konfiguration:

```
Plugin 'Order'        (pluginType: CType)
    OrderController: actions=[list, status]  non-cacheable=[status]
    Default-Action: list
Plugin 'Orderstatus'  (pluginType: CType)
    OrderController: actions=[status, list]  non-cacheable=[status]
    Default-Action: status

Registrierte Controller insgesamt: 1 (OrderController)
```

`php -l` sauber, FE und BE HTTP 200.

### Nächster Schritt (offen)

Phase 7 — eID → PSR-15-Middleware (#14, #15, #16). **Vorher die Sicherheitspunkte
(#16) mit dem User klären**: offenes Mail-Relay über `_f=smo`, fehlende Hash-Prüfung
bei `_f=smocomp`, wirkungsloses `isValidRequest()`, Secrets im Repo.

## 2026-07-31 — Phase 7: eID → PSR-15-Middleware (#14, #15, #16)

Der Worker ist von 1055 auf 636 Zeilen geschrumpft und komplett PSR-7.

### Warum Middleware und nicht eID

Zunächst wurde geprüft, ob sich die alte URL `?eID=phlorderEID` erhalten lässt — der
Core-`Dispatcher` löst eID-Targets nämlich über den DI-Container auf, ein moderner
`__invoke()`-Service wäre also möglich. **Geht trotzdem nicht:** die Core-Middleware
`EidHandler` ist in `cms-frontend/Configuration/RequestMiddlewares.php` direkt nach
`normalized-params-attribute` einsortiert und läuft damit vor `site`, `tsfe` und der
FE-User-Authentifizierung. Ein eID-Request hätte weder `frontend.typoscript` noch
einen FE-User — beides braucht der Worker (Plugin-Settings, `fe_user` im Template,
`absRefPrefix`). Also derselbe Weg wie bei `phlvote`/`phlusereditor`.

- **Neu:** `Configuration/RequestMiddlewares.php` — zwei Middlewares:
  - `pharmaline/phlorder/eid-cache-disabler` (nach `tsfe`, vor
    `prepare-tsfe-rendering`): schaltet für `mw=phlorderEID` den Seiten-Cache ab,
    damit `needsFullSetup` gilt und der volle TypoScript-Baum gebaut wird. Sonst wäre
    `getSetupArray()` leer und der Mailversand liefe ohne Konfiguration.
  - `pharmaline/phlorder/eid-resolver` (nach beiden, vor `content-length-headers`).
- **Neu:** `Classes/Middleware/PhlorderEidCacheDisabler.php`,
  `Classes/Middleware/PhlorderEidMiddleware.php` (Worker per Konstruktor-DI).
- **Geändert:** `Configuration/Services.yaml` — der Ausschluss von
  `Utility/Ajax/*` ist entfallen (der Worker hat keinen Code auf Dateiebene mehr),
  beide Middlewares als `public: true`.
- **Geändert:** `ext_localconf.php` — `eID_include`-Registrierung ersatzlos raus.

### ⚠ Die eID-URL ändert sich — externe Aufrufer betroffen

```
alt:  index.php?eID=phlorderEID&p=<pid>&_f=smtco&mt=…&oto=…&hs=…
neu:  index.php?id=<pid>&mw=phlorderEID&_f=smtco&mt=…&oto=…&hs=…
```

Im Repo gibt es nur **einen** Aufrufer (`phlorder.js`, Funktion `test()`) und der ist
toter Debug-Code. Die produktiven Aufrufer sind extern (das System, das die
Bestellmails auslöst) und müssen umgestellt werden.

**Dabei aufgefallen:** Ohne weitere Vorkehrung antwortet TYPO3 auf diese URLs mit
**404**, sobald mehr als `mw` und `_f` im Query stehen — der `PageArgumentValidator`
wertet `FE.cacheHash.enforceValidation` (im Projekt aktiv) aus und stuft den Request
als „cachebar mit fehlendem cHash" ein. Es reicht nicht, nur `mw` auszunehmen; **alle**
Parameter des AJAX-Pfades müssen in
`FE.cacheHash.excludedParameters`. Genau derselbe Fallstrick wie in `phlusereditor`.
Eingetragen: `mw, _f, p, mt, oto, hs, ct, ot, on, t, lang, csh, _`.

### Worker (`Classes/Utility/Ajax/phlorderEid.php`)

- **Kein Code mehr auf Dateiebene.** Der prozedurale Bootstrap (Z. 1-49 der
  Altfassung) mit `EidUtility::initFeUser()`, `TypoScriptFrontendController`,
  `connectToDB()`, `determineId()`, `initTemplate()`, `getConfigArray()`,
  `settingLanguage()`, `settingLocale()`, `Bootstrap::getInstance()->loadCachedTca()`
  ist ersatzlos weg — in v13 existiert davon nichts mehr.
- **Konstruktor-DI** statt `ObjectManager` (3×) und `initializeRepository()`:
  `OrderRepository`, `PhluserService`, `ViewFactoryInterface`,
  `PersistenceManagerInterface`, `TypoScriptService`.
- **`main(ServerRequestInterface): ResponseInterface`** — Daten aus
  `getQueryParams()`/`getParsedBody()` statt `$_REQUEST`/`$_FILES`.
  **Kein einziges `echo`/`exit` mehr** (vorher über die ganze Datei verstreut).
- **`StandaloneView`** → `ViewFactoryInterface` + `ViewFactoryData`; der
  Extbase-Request setzt `controllerExtensionName = 'Phlorder'`, damit die
  `f:translate`-Kurzkeys in den Mail-Templates auflösen.
- **SwiftMailer** (`Swift_Attachment`, `setBody()`, `addPart()`) → `MailMessage` mit
  `->html()`, `->attachFromPath()` und `Symfony\Component\Mime\Address`.
- **`makeInstanceService('extPhluser')`** (5×) → `PhluserService` aus `phlusereditor`
  (dort bereits als DI-Service mit unveränderter API vorhanden).
- **Settings** kommen aus `frontend.typoscript` → `plugin.tx_phlorder_order.settings`.
  Der Altcode las `plugin.tx_phlorder_cockpit.` — diesen Zweig gibt es im Setup nicht,
  die Property war seit jeher leer.
- **PHP-8-Fatals behoben** (#15): `date(ymd)` (3×) und `$this->request[_f]` —
  unquotierte Strings, die PHP 8 als Konstante liest. Dazu die Altbugs
  `$order->getOrderid` ohne Klammern, `$exit;` statt `exit;` (3×) und `$sendto` vs.
  `$sendTo` (3×).
- **Gelöscht** (toter/kaputter Code): der komplette Login-Block (`loginUser`,
  `simpleLoginByUsername`, `LoginFeUser` mit undefiniertem `$password`),
  `getUserUc()`, `mkpath()`, `writeFileToIn()`, `initInName()`, `checkBit()`,
  `resortPrefs()`, `convertImagesToBinhex()`, `getImagesFromHtml()`,
  `getImageSourceFromLink()`, `getURLbyCurl()`, `readFromFile()`,
  `serviceUserByPrefToken()`, `serviceGetUserInfo()`, `updateRemoteViaService()`,
  `getPrefFieldT()`, `validateOrderRequest()` (nie aufgerufen), alle
  `____DIVIDER____()`-Attrappen sowie `error_reporting()`/`ini_set()`.

### Sicherheitsentscheidungen (#16)

Die Rückfrage blieb offen, deshalb hier die getroffenen Entscheidungen — alle
umkehrbar:

1. **`isValidRequest()` gelöscht.** Die Funktion gab in *beiden* Zweigen `true`
   zurück und wurde ohnehin nirgends aufgerufen. Ein Platzhalter, der wie eine
   Prüfung aussieht, ist schlechter als keine Prüfung.
2. **`_f=smocomp` prüft den Hash jetzt wirklich.** Bisher wurde nur geprüft, *ob*
   der Parameter `hs` da ist, nicht ob er stimmt. `smocomp` und `smtco` rufen
   dieselbe Funktion auf, `smtco` validierte bereits — jetzt beide gleich.
   **Verhaltensänderung:** Aufrufer mit beliebigem `hs` bekommen jetzt
   „Sicherheitstoken ist falsch (948)".
3. **`_f=smo` ist standardmäßig AUS.** Der Endpunkt verschickt Mails mit Empfänger,
   Betreff und Body aus dem Request; der einzige Schutz war ein Hash aus
   `sha1(mt + festes Secret + fester String)` — er hängt also nur von `mt` ab, ein
   einziges bekanntes Paar genügt für beliebigen Mailversand. Aktivierung jetzt
   explizit über `settings.enableFreeMailEndpoint = 1` **plus**
   `settings.freeMailSecret`; der Hash wird mit `hash_equals()` verglichen.
   Im Repo gibt es keinen Aufrufer. Vor dem Einschalten sollte der Hash durch eine
   echte Autorisierung ersetzt werden.
4. **Secrets** liegen weiterhin im TypoScript (`setup.ts`) — das wird in Phase 8
   angefasst. Sie **gelten als kompromittiert und müssen rotiert werden**.
5. Alle Hash-Vergleiche laufen jetzt über `hash_equals()` statt `!=`.

### QR-Code: Feature ohne Implementierung

`makeInstanceService('extPhlqr')` verweist auf eine Extension, die es im Monorepo
nicht gibt (und das Service-Subsystem für Nicht-Auth-Typen gibt es in v13 auch nicht
mehr). Konsequenz:

- `_f=gqc` meldet jetzt sauber „QR-Code nicht verfügbar" statt in einen undefinierten
  Dienst zu laufen.
- In den Bestellmails bleibt `settings.qrcode.filepath` leer; das Template zeigt dann
  keinen Code, **die Mail geht trotzdem raus**.

Offene Entscheidung: QR-Bibliothek einziehen oder Feature streichen.

### Templates: drei Blocker, die den Mailversand verhindert haben

Eigentlich Phase 8 (#19), aber ohne sie rendert keine Mail:

1. **`<f:case default="TRUE">`** (3× in `MailOrderToCompany.html`) → `<f:defaultCase>`.
   Fluid wirft sonst einen Parse-Fehler: *Required argument "value" was not supplied*.
2. **`treatIdAsReference="1"`** bei `f:image` (in `MailOrderToCompany.html` und
   `Partials/Order/Orderstatus.html`) — in v11 entfernt → `image="{image}"`
   (mit TCA `type=file` liefert die ObjectStorage fertige FileReference-Objekte).
3. **QR-Bild ohne Datei:** `<f:image src="{settings.qrcode.filepath}">` mit leerem
   `src` wirft *You must either specify a string src or a File object* → in ein
   `<f:if>` gefasst.

### ⚠ Datenbefund: `order_image`-Zähler ohne Referenzen

Beim Debuggen aufgefallen:

| | |
|---|---|
| Bestellungen mit `order_image > 0` | **2826** |
| Zeilen in `sys_file_reference` für `tx_phlorder_domain_model_order` | **0** |

Der Zählerspalte nach hängen an 2826 Bestellungen Bilder, es existiert aber keine
einzige FAL-Referenz. Extbase liefert korrekterweise eine leere ObjectStorage
(geprüft: `count() = 0`, die `f:for`-Schleife läuft nicht), die Bilder sind also
schlicht weg — vermutlich nie mit migriert. **Nicht angefasst**: das ist ein
fachlicher Datenbefund, kein Migrationsschritt.

### Abnahme

Fehlerpfade (alle HTTP 200, JSON-Kontrakt unverändert):

```
<ohne _f>                             {"success":"false","message":"Funktion unbekannt:"}
_f=quatsch                            {"success":"false","message":"Funktion unbekannt: quatsch"}
_f=smtco                              {"success":"false","message":"Token fehlt. (950)"}
_f=smtco&mt=abc                       {"success":"false","message":"OrderToken fehlt. (951)"}
_f=smtco&mt=abc&oto=xyz               {"success":"false","message":"Hash fehlt. (952)"}
_f=smtco&mt=abc&oto=xyz&hs=falsch     {"success":"false","message":"Sicherheitstoken ist falsch (948)"}
_f=smocomp&mt=abc&oto=xyz             {"success":"false","message":"Hash fehlt. (952)"}
_f=smo                                {"success":"false","message":"Endpunkt _f=smo ist deaktiviert (…)"}
_f=gqc&oto=xyz                        {"success":"false","message":"Konnte Bestellung nicht finden (1050)"}
_f=lii                                {"success":"false","message":"Kein pathToBlindImg konfiguriert."}
```

Happy Path mit echten Produktivdaten (Phluser „Testfirma", Order 1553,
gültiger Hash `sha1(mt + oto + date('dmy'))`):

```
_f=smtco  HTTP 200  {"success":"true","message":"Bestellmail wurde an david.koerntgen@pharmaline.de versendet"}
_f=smtcu  HTTP 200  {"success":"true","message":"Bestellmail wurde an sabrina.kurschus@pharmaline.de versendet"}
```

Beide Mails in **Mailpit** kontrolliert (DDEV fängt Mail lokal ab, es ging nichts nach
außen):

```
von noreply@pharmaline.de an david.koerntgen@pharmaline.de
  "Online Bestellung  Sabrina Kurschus - 0001K0041"
  → "Online-Bestellung von Frau Sabrina Kurschus, Bestellung Nr. 0001K0041, Datum: 31-0…"
von info@pharmaline.de an sabrina.kurschus@pharmaline.de
  "Bestellung bei Testfirma am 31.07.2026 16:13 von Sabrina Kurschus"
  → "Bestellbestätigung Bestellung Nr. 0001K0041 …"
```

Damit ist die ganze Kette belegt: Middleware → Worker → `PhluserService` →
`OrderRepository` → Fluid (`f:defaultCase` liefert korrekt „von **Frau**") →
Symfony-Mailer.

`php -l` über alle Klassen sauber, FE und BE HTTP 200. Im Worker gibt es keinen
echten Treffer mehr auf `ObjectManager`, `StandaloneView`, `Swift_`,
`makeInstanceService`, `$GLOBALS['TSFE']`, `$_REQUEST`, `$_FILES`, `debugster()`,
`echo` oder `exit` — nur noch in Erklärkommentaren.

### Nächster Schritt (offen)

Phase 8 — TypoScript/Site Set (#17), Assets (#18), restliche Templates (#19),
XLIFF-Duplikate (#20). Dabei: `storagePid = 17`, Secrets rotieren und aus dem
Repo nehmen.

## 2026-07-31 — Phase 8: Site Set, Assets, Templates, Sprachdateien

### TypoScript → Site Set (#17)

- **Umbenannt:** `constants.ts` → `constants.typoscript`, `setup.ts` → `setup.typoscript`.
- **Neu:** `Configuration/Sets/Phlorder/{config.yaml,constants.typoscript,setup.typoscript}`
  (`name: pharmaline/phlorder`, Label „PHL Bestellung"), die beiden TS-Dateien
  importieren nur. Muster von `phlusereditor`.
- **Geändert:** `config/sites/phlsitepackage/config.yaml` — `pharmaline/phlorder`
  in `dependencies` ergänzt. Die Site nutzt ausschließlich Site Sets, ein
  `addStaticFile()` hätte nie gegriffen.
- **`storagePid = 17`** (Sysordner „phlorder" aus Phase 4).
- **Neu:** `plugin.tx_phlorder_orderstatus < plugin.tx_phlorder_order` — das zweite
  Plugin aus Phase 6 erbt die komplette Konfiguration.
- `_CSS_DEFAULT_STYLE` **beibehalten**: erst geprüft, ob es das in v13 noch gibt
  (ja, ausgewertet in `cms-frontend/Http/RequestHandler.php`), deshalb unverändert
  übernommen statt stillschweigend zu streichen.

### Secrets aus dem Repository entfernt

Beim Aufräumen kam heraus, dass die meisten Secrets gar nicht mehr gebraucht werden —
ihr Code ist in den Phasen 5–7 entfallen:

| Setting | Verwendungen im Code | Konsequenz |
|---|---|---|
| `tokenSalt` | 0 (nutzte nur `isValidRequest()`) | **gelöscht** |
| `ut`, `ot`, `oto` (Test-Tokens) | 0 (nutzte nur der gelöschte Testcode) | **gelöscht** |
| `envPath`, `extensionname`, `useT3jquery`, `pathToFonts`, `forgotPid`, `orderPid`, `orderinfoPid` | 0 | **gelöscht** |
| `ordersalt` | 1 (`getPageinfo()`, csh-Hash) | Konstante, **leer** im Repo |
| `freeMailSecret` (neu, Phase 7) | 1 | Konstante, **leer** im Repo |

Die Konstanten sind mit `# cat=…/secret` ausgezeichnet; die echten Werte gehören in
die Site-/Server-Konfiguration. **Die alten Werte gelten weiterhin als kompromittiert
und müssen rotiert werden** — sie stehen in der Git-Historie.

### Assets (#18)

Drei kaputte Referenzen, alle vorher gegen das Dateisystem geprüft:

1. `cssFile.0` zeigte auf `bootstrap3.css` — **existiert nicht**, die vorhandene Datei
   heißt `bootstrap.min.css`.
2. `pathToJquery` zeigte auf `typo3conf/ext/phlorder/js/…` — die Datei liegt unter
   `Resources/Public/Js/`.
3. `pathToBlindImg` zeigte auf `/typo3conf/ext/phlnewsletter/…` — **diese Extension
   gibt es im Monorepo nicht**. Default ist jetzt das 1×1-GIF aus phlorder selbst
   (`EXT:phlorder/Resources/Public/Images/ie-spacer.gif`).
   Dazu im Worker: `EXT:`-Pfade werden über `PathUtility::getPublicResourceWebPath()`
   aufgelöst — in v13 liegen publizierte Assets unter `/_assets/<hash>/`, ein fester
   Pfad wäre falsch.

Alle `typo3conf/ext/…`-Pfade sind auf `EXT:…` umgestellt.

**Zusätzlich gefunden:** `Resources/Public/Css/phlorder.css` lud das Loader-Bild
`.regajax-loader` ebenfalls aus `/typo3conf/ext/phlnewsletter/…` — der Ladeindikator
war damit unsichtbar. Jetzt relativer Pfad auf das in phlorder vorhandene
`../Images/ajax-loader-2.gif`.

**`phlorder.js` aufgeräumt.** Entfernt wurden `test()` (rief die eID im alten Format
`?eID=phlorderEID` auf — seit Phase 7 ungültig), `getTestData()`, `controlSearch()`
(rief die eID einer **fremden** Extension auf, `eID: "phluserEID"`), `controlStatus()`
(leer) und der `#susers`-keyup-Handler, der `analyseInput()` und `getDeliverybills()`
aufruft — **beide sind nirgends definiert** (ReferenceError), und die adressierten
Elemente `#susers`/`#searchbutton` kommen in keinem phlorder-Template vor.
Übrig bleiben der AJAX-Lade-Overlay, `growler()` und das doTimeout-Plugin.
**Offen:** jQuery 2.2.0 / gritter mitziehen oder wie in `phlaponot` auf Vanilla JS
umstellen (CLAUDE.md #18).

### Templates (#19)

Die drei blockierenden Punkte waren schon in Phase 7 nötig (`f:case default`,
`treatIdAsReference`, leeres QR-`src`). Nachkontrolle über alle Templates und
Partials: **je 0 Treffer** für `treatIdAsReference`, `f:case default`, `{namespace`,
`f:widget`, `pageUid=`, `f:be.*`. Die zwei verbliebenen `f:format.raw` stehen in
`Partials/Mail/OrderFor{Company,Customer}.html` — beide weiterhin ohne Aufrufer.

### Sprachdateien (#20)

Doppelte `trans-unit`-IDs bereinigt. In allen Fällen wurde das **erste**
(überschattete) Vorkommen entfernt, das zweite gewinnt in TYPO3 ohnehin — die
Bereinigung ist damit verhaltenserhaltend:

| Datei | ID | entfernt | wirksam bleibt |
|---|---|---|---|
| `locallang.xlf` | `tx_phlorder_domain_model_order` | `Bestellung` | `Order` |
| `locallang_db.xlf` | `tx_phlorder_domain_model_order` | `Bestellung` | `Order` |
| `de.locallang.xlf` | `tx_phlorder_domain_model_order` | Streuner mit `<source>order_image</source>` | `Order` → `Bestellung` |
| `de.locallang.xlf` | `order.ready` | `bereitgestellt` | `fertig zur Abholung` |

Der Streuner in `de.locallang.xlf` war ein Copy-Paste-Fehler: ID lautete
`tx_phlorder_domain_model_order`, Quelle aber `order_image` — ein korrekter Eintrag
`tx_phlorder_domain_model_order.order_image` existiert separat.
Alle vier Dateien danach mit `xml.dom.minidom` auf Wohlgeformtheit geprüft.

### Abnahme

Zum Nachweis wurde **temporär** ein `tt_content`-Element angelegt und nach dem Test
wieder gelöscht (Endstand: 0 phlorder-Elemente, wie vorgefunden).

Plugin `Order` (CType `phlorder_order`) auf der Startseite:

```
HTTP 200, Markup enthält .tx-phlorder, #pagedata, .regajax-loader
Assets geladen: phlorder.css, jquery.gritter.css, bootstrap.min.css,
                phlorder.js, jquery.gritter.min.js, bootstrap.min.js, jquery.base64.min.js
```

Plugin `Orderstatus` (CType `phlorder_orderstatus`) mit `?t=<orderid>`, nicht
eingeloggt (Modus „info"), echte Bestellung 1553:

```
Bestellstatus  Datum 21.09.2022 12:50 Uhr  Abholnummer 0001K0041
Bestell ID 29ac0e27-6195-4a75-a018-2599acc90bd1
```

Die deutschen Labels lösen auf, das Datum kommt als echtes `DateTime` aus der DB.

Publizierte Assets einzeln geprüft — alle **HTTP 200**:
`Css/phlorder.css`, `Images/ajax-loader-2.gif`, `Js/phlorder.js`,
`Css/bootstrap.min.css`, `Js/jquery-2.2.0.min.js`.

Beweis, dass das TypoScript den Worker erreicht (Site Set → Konstante → Setup →
`EXT:`-Auflösung):

```
?mw=phlorderEID&_f=lii
→ HTTP 301, Location: /_assets/29143c1bcd2887ffcccc3b2180d6aa3c/Images/ie-spacer.gif
```

Mailversand weiterhin grün, FE und BE HTTP 200, `php -l` sauber. Suche über den
Quellcode (ohne `Documentation/`) nach den alten Secrets `jomeiilsabvgh`, `KJuzds`,
`JniIcc…`, den Test-Tokens und `JjiW@U`: **je 0 Treffer**.

### Nächster Schritt (offen)

Phase 9 — Tests, PHPStan, Aufräumen (#21, #22): Orphan-Tests löschen, Rest auf
`typo3/testing-framework: ^9` heben, `Build/phpunit/UnitTests.xml`, `phpstan.neon`
und `.php-cs-fixer.dist.php` anlegen, `Documentation.tmpl/` entfernen. Dazu die in
Phase 6 unregistrierten Controller (`Log`, `Item`, `Token`) samt Templates und die
aufruferlosen Mail-Partials.

## 2026-07-31 — Phase 9: Tests, PHPStan, Aufräumen (#21, #22)

### ⚠ Vorab: dieses Paket steht unter KEINER Versionskontrolle

Erst in dieser Phase geprüft — zu spät: weder `phl13/` noch `packages/phlorder/`
enthalten ein `.git` (anders als `phlvote` und `phlaponot`, die eigene Repos haben).
**Die Löschungen unten sind damit endgültig und nicht über Git wiederherstellbar.**

Alle gelöschten Dateien waren vorher nachweislich tot (siehe Begründungen), aber die
Prüfung hätte vor dem ersten `rm` kommen müssen. Nach den Löschungen wurde ein
Snapshot gezogen:
`scratchpad/backup/phlorder_nach_phase9_loeschungen.tar.gz` (168 Dateien).
**Empfehlung: `git init` im Monorepo oder zumindest in diesem Paket.**

Aus demselben Grund wurden `Order->showAction()`/`deleteAction()` samt
`Templates/Order/Show.html` und `Partials/Order/Properties.html` **nicht** gelöscht,
obwohl sie seit Phase 6 unregistriert sind — das ist jetzt eine Entscheidung des
Users.

### Gelöscht (#21, #22)

Referenz-Inventur über alle Templates/Partials vorweg. **Wichtig dabei:**
`Templates/Order/List.html` und `Status.html` zeigen 0 explizite Referenzen, weil
Extbase sie **per Konvention** (Controller/Action) auflöst — sie sind in Gebrauch und
wurden nicht angefasst.

| Gruppe | Dateien | Begründung |
|---|---|---|
| Orphan-Tests | `LogsControllerTest`, `TokensControllerTest`, `ItemsTest`, `LogsTest`, `TokensTest` | Testen Klassen, die es **nie gab** |
| Controller | `ItemController`, `LogController`, `TokenController` + deren Tests | seit Phase 6 nicht mehr registriert, über die alte SCA nie erreichbar |
| Templates | `Templates/{Item,Log,Token}/`, `Templates/Component/`, `Order/Test.html`, `Mail/MailOrderResetToCustomer.html` | ohne Aufrufer; `Test.html` gehörte zur in Phase 5 gelöschten `testAction` |
| Partials | `Partials/{Item,Log,Token}/`, `FormErrors.html`, `Partials/Mail/` | nur von den gelöschten Templates benutzt |
| Gerüste | `Documentation.tmpl/`, `Configuration/ExtensionBuilder/`, `ExtensionBuilder.json` | Extension-Builder-Reste von 2018 |

**Bewusst behalten:** die Modelle `Item`, `Log`, `Token` und ihre Repositories. `Item`
und `Log` hängen als ObjectStorage an `Order` (`tolog`/`toitem`) und werden im
Status-Template iteriert; `Token` hat TCA und 37 Datensätze. Die Domänenschicht zu
löschen wäre eine fachliche Entscheidung, keine Migration.

### Tests neu geschrieben (#21)

Die Altfassung erbte von `\TYPO3\CMS\Core\Tests\UnitTestCase` (existiert nicht mehr),
nutzte PHPUnit-4/5-Signaturen — und bestand aus Testmethoden mit **leerem Rumpf**,
also ohne eine einzige Assertion. Ersetzt durch echte Tests auf
`TYPO3\TestingFramework\Core\Unit\UnitTestCase` mit **Attributen**
(`#[Test]`, `#[DataProvider]` — Annotationen werden von PHPUnit 13 ignoriert):

- `Domain/Model/OrderTest.php` — ObjectStorage-Initialisierung, Scalar-Roundtrip,
  Add/Remove für `tolog`/`toitem` und vor allem **`timestamp` nullable** (die
  Absicherung der Phase-4-Migration).
- `Domain/Model/LogTest.php` — dito; hier besonders relevant, weil 716 der 736
  Log-Zeilen jetzt NULL tragen.
- `Utility/Ajax/PhlorderEidTest.php` — die reinen Hilfsfunktionen des Workers:
  `getWhatsAppPhone()` (5 Fälle über DataProvider), `getPrefField()`, `checkTime()`
  und `toAddressList()` inklusive des Falls `[""]` → `null`, der sonst den
  Symfony-Mailer werfen ließe. Zugriff über Reflection, da protected.

**Neu:** `Build/phpunit/UnitTests.xml` (Bootstrap vier Ebenen hoch, `vendor/` liegt im
Monorepo-Root).

```
PHPUnit 13.2.5, PHP 8.4.20
OK (23 tests, 55 assertions)
```

*Zwischenschritt:* der erste Lauf meldete 12 PHPUnit-Notices — `createMock()` für
Konstruktor-Abhängigkeiten, die im Test nie benutzt werden. PHPUnit 13 will dafür
`createStub()`; umgestellt, danach ohne Beanstandung.

### PHPStan / CS-Fixer

**Neu:** `phpstan.neon` (Level 5, `treatPhpDocTypesAsCertain: false`, Muster von
`phlusereditor`), `phpstan-baseline.neon`, `.php-cs-fixer.dist.php`
(TYPO3 Coding Standards).

Der erste Lauf fand **4 Fehler** — alle behoben statt in die Baseline geschoben:

1. `OrderController.php` — `@param void` in einem Docblock (PHPDoc-Parse-Fehler),
2. `OrderRepository.php` (2×) — `@param string token` / `@param int phluserfid`,
   also ohne `$`,
3. `OrderController::getPhluserById()` — `findByUid()` ist auf
   `DomainObjectInterface` typisiert, `$this->phluser` nimmt aber nur `?Phluser`.
   Jetzt mit `instanceof Phluser` abgesichert.

```
phpstan analyse -c phpstan.neon  →  [OK] No errors
```

**Die Baseline ist leer** — Level 5 läuft ohne eine einzige Unterdrückung durch.

### Abnahme

```
ci:test:unit    OK (23 tests, 55 assertions)
ci:phpstan      [OK] No errors            (Baseline leer)
extension:setup phlorder OK
FE              HTTP 200
BE              HTTP 200
eID _f=lii      HTTP 301
eID _f=smtco    HTTP 200  {"success":"true","message":"Bestellmail wurde an …"}
```

Paket jetzt: 135 Dateien, 12 PHP-Klassen (vorher u. a. 13 Testdateien ohne
Assertions, 4 Controller, 24 Templates/Partials).

### Damit ist die Migration 7.6 → 13.4 abgeschlossen

Alle 22 Baustellen aus `CLAUDE.md` sind erledigt. Was bewusst offen bleibt, steht
unten.

---

## Altbestand — übernommen aus `Documentation.tmpl/genesis.txt`

Notizen und Versionshistorie des ursprünglichen Autors (2018/2019), unverändert
übernommen. Die URLs beziehen sich auf die damalige Entwicklungsumgebung
`local.typo76.de` und dokumentieren den **eID-Aufrufkontrakt**, der bei der Migration
auf die Middleware erhalten bleiben muss (bzw. bewusst zu brechen ist).

```
//gs get status - ganz allgemein
https://local.typo76.de/index.php?id=106&tx_phlorder_order[token]=3724b1ef-…&tx_phlorder_order[_f]=gs

//get qrcode
https://local.typo76.de/index.php?eID=phlorderEID&_f=gqc&oto=3724b1ef-…&p=103&lang=de&csh=sha1vondingsbums

// send mail to company  token phluser id phluser hs= sha1(mt+oto+date('dmy'))
https://local.typo76.de/index.php?eID=phlorderEID&p=103&_f=smtco&mt=myToken&oto=59b555db-…&hs=25fa4f39d10f21eb9f5c8ba4c4714326a7ea8313
```

- **1.0.7** — 2019-02-27
  - new frontend action status
  - eid action for qr code, `phlqr` needed
- **1.0.6** — 2018-12-05
  - new Output for Client Order
- **1.0.5** — 2018-12-02
  - changed TCA Order to display `last_name` in Backend
- **1.0.4** — 2018-11-29
  - add field `order.company`, `order.timestamp`, `order.salutation`
---

## Offene Punkte nach Abschluss der Migration

Keiner davon blockiert den Betrieb; alle brauchen eine fachliche Entscheidung.

1. **Kein Versionskontrollsystem.** `packages/phlorder` hat kein `.git`, das Monorepo
   auch nicht. Dringend nachholen.
2. **Secrets rotieren.** `tokenSalt`, `ordersalt`, `ut`, `ot`, `oto`, `SECRETSMO`
   standen im Klartext im Code und gelten als kompromittiert. Die Konstanten
   `ordersalt` und `freeMailSecret` sind im Repo leer und müssen in der
   Site-Konfiguration gesetzt werden.
3. **Externe eID-Aufrufer umstellen.** Die URL hat sich geändert:
   `?eID=phlorderEID&p=<pid>&…` → `?id=<pid>&mw=phlorderEID&…`. Bis das passiert,
   kommen von extern ausgelöste Bestellmails nicht mehr an.
4. ~~**QR-Code.** `extPhlqr` existiert nicht. Entweder eine QR-Bibliothek einziehen
   oder das Feature streichen (`_f=gqc`, `srvOrderSendMailQr`).~~
   **Erledigt am 2026-08-04** — angebunden an `Pharmaline\Phlqr\Service\QrCodeService`,
   siehe den Nachtrag am Ende dieser Datei.
5. **`_f=smo` (freie Mail)** ist deaktiviert. Vor einer Aktivierung sollte der
   statische Hash durch eine echte Autorisierung ersetzt werden.
6. **Bestellnummern-Vergabe** bildet die nächste Nummer aus `count()` der
   bestehenden Bestellungen → Duplikate bei gelöschten oder gleichzeitigen
   Bestellungen. Braucht `MAX(ordernumber)` oder eine echte Sequenz.
7. **`order_image`:** 2826 Bestellungen tragen einen Zähler > 0, es gibt aber **null**
   `sys_file_reference`-Zeilen. Die Bilder fehlen; Ursache klären.
8. **`tx_phlorder_domain_model_fastorder`** (18 Zeilen, pid 0) hat weder TCA noch
   Modell noch Code. Klären, welches System sie schreibt und liest.
9. **`Order->show`/`Order->delete`** sind unregistriert und damit toter Code.
   `deleteAction` bräuchte vor einer Reaktivierung einen Owner-Check.
10. **Frontend-Assets:** jQuery 2.2.0, Bootstrap 3 und `jquery.gritter` sind EOL.
    Mitziehen oder wie in `phlaponot` auf Vanilla JS umstellen?
11. **CS-Fixer noch nicht gelaufen.** `.php-cs-fixer.dist.php` liegt bereit; der
    erste Lauf wird ein sehr großes Diff erzeugen (Bestandscode nutzt Tabs) und
    gehört deshalb in einen eigenen Commit.

## 2026-08-04 — Nachtrag: QR-Code angebunden (offener Punkt 4)

Der bei Abschluss der Migration offen gelassene Punkt 4 ist erledigt. `phlqr` ist
inzwischen auf 13.4 migriert (dort Phasen 1–7) und stellt mit
`Pharmaline\Phlqr\Service\QrCodeService` genau die Schnittstelle bereit, die den
Legacy-„_sv"-Dienst `extPhlqr` ersetzt. Protokoll dieser Arbeit auf der anderen
Seite: `packages/phlqr/Documentation/Genesis.md`, Schritt 11.

### Abhängigkeit

`composer.json` und `ext_emconf.php` um `pharmaline/phlqr` (`13.0.0-13.99.99`)
ergänzt, `ddev composer update pharmaline/phlorder pharmaline/phlqr`.

### `prepareQrCode()`

Erzeugt den Code jetzt wirklich, statt `filepath` leer zu lassen:

*   **PNG, nicht SVG** — der Code geht in eine E-Mail, und SVG rendert weder in
    Outlook noch in den meisten Webmailern.
*   Dateiname weiterhin `op<ordernumber>`, Unterordner `phlorder/`, also
    `typo3temp/assets/phlqr/phlorder/op<ordernumber>.png`. Weil der Name aus der
    Bestellnummer kommt und nicht aus dem Inhalt gehasht ist, läuft der Aufruf
    mit `imageoverride = 1`.
*   Die Methode liefert den **absoluten** Pfad zurück (vorher `void`); der
    relative bleibt wie bisher in `settings.qrcode.filepath` für das Template.
*   Ohne konfigurierte Basis-URL entsteht kein Code, und eine Ausnahme aus dem
    Service wird gefangen: **ein fehlender QR-Code darf den Mailversand nicht
    verhindern.**

### Der Code hängt jetzt in der Mail, nicht am Server

`MailOrderToCustomer.html` band den Code als `<f:image src="{settings.qrcode.filepath}">`
ein — ein Pfad, den das Postfach der Kundin nicht auflösen kann, und selbst als
absolute URL bliebe das Bild hinter der Sperre für externe Inhalte.

Stattdessen wird die Datei über `Email::embedFromPath()` als **Inline-Teil**
mitgeschickt und im Template mit `<img src="cid:qrcode">` referenziert.
`sendMail()` hat dafür einen neuen Parameter `$inlineImages` ([Content-ID =>
absoluter Pfad]). Nur die **Kundenmail** bettet ein — nur ihr Template zeigt den
Code an; die Firmenmail bereitet ihn weiterhin auf (für `settings.qrcode.*`),
schickt ihn aber nicht mit.

### `_f=gqc` liefert wieder ein Bild

Der Endpunkt meldete seit Phase 7 „keine QR-Bibliothek eingebunden". Er antwortet
jetzt — anders als alle übrigen Endpunkte — **nicht mit JSON, sondern mit dem Bild
selbst** (`image/svg+xml`), ist also direkt als Bildquelle verwendbar:
`<img src="…&mw=phlorderEID&_f=gqc&oto=<token>">`. SVG, weil die Antwort im
Browser landet; für den Mailweg bleibt es bei PNG. Absicherung unverändert der
Bestell-Token — ein zusätzlicher Hash wie bei den Mail-Endpunkten würde
bestehende Aufrufer brechen.

### `qrcodeText` ist ein Site-Setting geworden

Der Wert enthält eine Seiten-ID und ist damit instanzabhängig. Neu:
`Configuration/Sets/Phlorder/settings.definitions.yaml` mit
`phlorder.qrcodeText`; der Wert steht in `config/sites/<site>/settings.yaml`.
Die Konstante `plugin.tx_phlorder_order.settings.qrcodeText` ist entfallen.

**Fallstrick, der dabei Zeit gekostet hat:** der naheliegende Weg — die Konstante
stehen lassen und auf `{$phlorder.qrcodeText}` zeigen — funktioniert **nicht**.
`{$…}`-Platzhalter werden nur im **Setup** ersetzt, nicht innerhalb der Konstanten
selbst. Der Platzhalter landete deshalb unaufgelöst als Zeichenkette im QR-Code
(verifiziert: die erste Testmail enthielt einen Code auf
`{$phlorder.qrcodeText}&t=…`). Das Setting wird jetzt direkt im Setup eingesetzt.

Im selben Zug aus dem `qrcode`-Block entfernt: `magnification`, `quality` und
`blackwhite` — die drei hatten schon in der Altfassung keine Wirkung. Geblieben
sind `text`, `width` und `imageoverride`.

### Gefunden und behoben: `addAbsPrefix()` zerlegte jede absolute URL

Die Methode lief mit `str_replace('//', '/')` über den **gesamten** Mailtext, um
doppelte Schrägstriche nach dem Voranstellen von `absRefPrefix` aufzulösen. Sie
traf damit aber jede URL im Dokument: aus
`href="https://phl13.ddev.site/index.php?id=20"` wurde
`href="https:/phl13.ddev.site/index.php?id=20"` — im Postfach ein toter Link.
Betroffen waren der Link unter dem QR-Code und der WhatsApp-Link.

Die Methode arbeitet jetzt über einen Callback pro `src`-Attribut:
Quellen mit eigenem Schema (`cid:`, `data:`, `http:`, `https:`) bleiben
unangetastet, relative bekommen den Prefix, und ein führender Schrägstrich der
Quelle wird abgeschnitten — der Doppel-Schrägstrich kann gar nicht mehr
entstehen. Ohne diese Korrektur hätte das eingebettete Bild als
`src="https://…/cid:qrcode"` geendet.

Abgesichert mit sieben neuen Testfällen (`PhlorderEidTest`), darunter der
Link-Fall, der vorher kaputtging.

### Gates

- **PHPStan Level 5: 0 Fehler. PHPUnit: 30 Tests, 62 Assertions, grün**
  (vorher 23/55; der Konstruktor-Stub im Test brauchte den neuen Service).
- **Bestellmail an die Kundin** (`_f=smtcu`, Phluser 1 „Testfirma" mit
  `srvOrderSendMailQr = 1`, Order `0001K0041`) gegen Mailpit:
  ein Inline-Teil `image/png`, 3.627 Bytes, und der Link unter dem Code zeigt
  auf `https://phl13.ddev.site/index.php?id=20&t=29ac0e27-…` — vollständig, mit
  beiden Schrägstrichen.
- **Der Code wurde tatsächlich gescannt**, nicht nur erzeugt: `zbarimg` liest aus
  dem eingebetteten PNG
  `https://phl13.ddev.site/index.php?id=20&t=29ac0e27-6195-4a75-a018-2599acc90bd1`.
  Dieselbe URL liefert HTTP 200 und die Statusseite mit der Bestellnummer
  `0001K0041`.
- Der Inline-Teil ist **byteweise identisch** mit
  `typo3temp/assets/phlqr/phlorder/op0001K0041.png` und mit dem, was der Service
  für dieselbe URL neu erzeugt (MD5 `745bb8e4…` in allen drei Fällen).
- **`_f=gqc`**: HTTP 200, `image/svg+xml`, 5.738 Bytes; gerastert und mit
  `zbarimg` zurückgelesen — dieselbe Status-URL. Mit falschem Token weiterhin
  die JSON-Fehlermeldung.
- **Firmenmail** (`_f=smtco`) als Regressionsprobe: geht raus, hat **keinen**
  Inline-Teil, und keine kaputte URL im Text.
- Für den Test angelegt: Seite 20 „Bestellstatus" (`/bestellstatus`) mit einem
  Inhaltselement `phlorder_orderstatus`. Vorher gab es in dieser Instanz **kein
  einziges** `phlorder`-Inhaltselement — die Statusseite, auf die der QR-Code
  zeigt, existierte gar nicht.

### Was offen bleibt

Punkt 4 der Liste unten ist damit erledigt. Unverändert offen: die Secrets
(Punkt 1), die externen eID-Aufrufer (Punkt 3) und die Bestellnummern-Vergabe
(Punkt 6). `settings.edit.statusurl` wird weiterhin gesetzt, aber von keinem
Template gelesen — beim nächsten Aufräumen prüfen.

---

## 2026-08-09 — FlexForm für das Cockpit-Plugin: Quelle der Bestellungen

### Ausgangslage: wo die alten FlexForm-Einstellungen geblieben sind

Es gab in dieser Extension genau **eine** FlexForm,
`Configuration/FlexForms/flexform_order.xml`, und ihr Inhalt war
**ausschließlich `switchableControllerActions`** (die drei Varianten `Display`,
`Status`, `Test`). SCA ist in v13 entfernt; in Phase 6 wurden daraus zwei eigene
CTypes, womit kein einziges Feld übrig blieb — die Datei wurde gelöscht.

Fachliche Einstellungen sind dabei **nicht** verloren gegangen, es gab schlicht
keine. Insbesondere gab es **nie** ein Feld für die Quelle der Bestellungen:
`OrderController::initStoragePid()` las in der Altfassung zwar
`$this->settings['ff']['sourceDB']`, dieser Key war in der Datenstruktur aber
nie definiert — ein toter Zweig. Die Storage-Pid kam immer aus dem TypoScript
(`plugin.tx_phlorder_order.persistence.storagePid`, Konstante `17`).

### Neu

`Configuration/FlexForms/flexform_order.xml` — Sheet `sSource` („Quelle") mit
einem Feld `settings.sourcePid` (`type=group`, `allowed=pages`, bis zu zehn
Ordner). Registriert **nur am Cockpit-Plugin** `phlorder_order` über
`addToAllTCAtypes` + `addPiFlexFormValue('*', …, $signatureOrder)` — bei eigenen
CTypes (kein `list_type`) ist das der Weg, `subtypes_addlist` greift nicht.
`phlorder_orderstatus` bekommt die FlexForm bewusst nicht (im FE verifiziert:
dessen DS-Identifier bleibt `default`).

Labels in `locallang.xlf` / `de.locallang.xlf` (`flexform.sheet_source`,
`flexform.source.sourcePid[.description]`).

### Zwei Wege, die nicht funktionieren — beide im FE durchgemessen

**1. Das Feld einfach `persistence.storagePid` nennen.**
Naheliegend, weil `FrontendConfigurationManager::overrideConfigurationFromFlexForm()`
diesen Namen nativ in die Framework-Konfiguration merged. Aber: ein **leeres**
Feld überschreibt den TypoScript-Wert mit `''`, `QueryFactory::create()` macht
daraus über `intExplode` die Pid-Liste `[0]`, und die Bestellliste bleibt
kommentarlos leer. „Feld nicht ausgefüllt" muss aber „TypoScript-Default
benutzen" heißen. `ignoreFlexFormSettingsIfEmpty` hilft nicht — das greift
ausschließlich für `settings.*`.

**2. Den Wert im Controller über `configurationManager->setConfiguration()`
setzen** (der Weg, den `initStoragePid()` für `setCurrentPageAsStoragePid` geht).
Erste Fassung dieser Änderung tat genau das — und **der Gegentest im FE fiel
durch**: mit `sourcePid = 13` wurde die Bestellung von Pid 17 weiterhin
gefunden. Grund steht in
`FrontendConfigurationManager::getContextSpecificFrameworkConfiguration()`:
die per `setConfiguration()` übergebenen Werte werden **vor**
`overrideConfigurationFromPlugin()` verrechnet, und dieses merged anschließend
`plugin.tx_phlorder_order.persistence` erneut darüber. Solange im TypoScript
eine `storagePid` steht (hier: `17`), gewinnt immer das TypoScript. Der alte
Code fiel nur deshalb nicht auf, weil sein einziger Schreibzugriff in
`if (empty($configuration['persistence']['storagePid']))` gekapselt ist — also
genau im Fall, in dem das TypoScript nichts beisteuert.

### Der Weg, der funktioniert

Neuer EventListener
`Classes/EventListener/ApplySourcePidToStoragePid.php` auf
`BeforeFlexFormConfigurationOverrideEvent`: er übersetzt
`settings.sourcePid` in `persistence.storagePid` **innerhalb des FlexForm-Arrays**,
bevor dieses gemerged wird. Der FlexForm-Merge ist der letzte Schritt von
`getContextSpecificFrameworkConfiguration()` und schlägt damit das TypoScript.
Ist das Feld leer, schreibt der Listener nichts — der TypoScript-Default bleibt
unberührt. Damit ist Problem 1 nicht umschifft, sondern strukturell ausgeschlossen.

Der Listener normalisiert die Liste (`GeneralUtility::trimExplode` + Ziffern pro
Eintrag, gleiche Absicherung wie in `phlvote`) und greift nur, wenn
`frameworkConfiguration['extensionName'] === 'Phlorder'` — das Event läuft für
jedes Extbase-Plugin der Seite.

`OrderController::initStoragePid()` bleibt damit **unverändert** in der Sache;
ergänzt wurde nur der Kommentar, der auf den Listener verweist.

Unbedenklich für die Phluser-Auflösung: `PhluserRepository::getPhlUserByFID()`
und `findByUid()` setzen `setRespectStoragePage(false)`, die geänderte Storage-Pid
trifft sie nicht.

### Gates

- **PHPStan Level 5: 0 Fehler. PHPUnit: 30 Tests, 62 Assertions, grün.**
  `php-cs-fixer` über die neue Datei (nur über diese — der Bestand ist
  tab-eingerückt und wird nicht nebenbei umformatiert).
- **DS-Auflösung** über den echten FormEngine-Weg (`FlexFormTools`): für
  `CType=phlorder_order` Identifier
  `{"type":"tca",…,"dataStructureKey":"*,phlorder_order"}`, Sheet `sSource`,
  Feld `settings.sourcePid`; für `phlorder_orderstatus` `default`.
- **FE-Test** mit einem temporären Inhaltselement (`phlorder_order` auf Seite 16,
  nach dem Test gelöscht), Order `f9fd6522-…` / `0031S0604` auf Pid 17,
  URL `…/presets/phlorder?t=<orderid>`. Ausgewertet wurde gezielt der
  Cockpit-Block (`<h1>Bestellungen</h1>` bis `id="oresults"`), weil auf derselben
  Seite ein `phlorder_orderstatus`-Element dieselbe Bestellung rendert:
  | `settings.sourcePid` | Bestellung im Cockpit-Block | erwartet |
  |---|---|---|
  | `17` (richtige Quelle) | ja | ja |
  | `13` (falsche Quelle) | **nein** | nein |
  | leer | ja (TypoScript-Default 17) | ja |
  | `13,17` (Mehrfachauswahl) | ja | ja |
  Im Fall `13` blieb das Nachbar-Element auf derselben Seite unverändert bei
  „gefunden" — die Änderung wirkt also genau auf das Element mit der FlexForm
  und nicht global.

### Was offen bleibt

- `phlorder_orderstatus` hat kein solches Feld. In dieser Instanz unkritisch
  (beide CTypes lesen von Pid 17), fachlich aber die gleiche Frage.
- Keine Rekursionstiefe (`persistence.recursive`) — Unterordner der gewählten
  Seite werden nicht mitgelesen.

---

## 2026-08-09 — Nachtrag: Cockpit war cachebar (Bestelldaten-Leck), und die Bestellliste fehlt

### Gemeldet

„In Bestellungen mit Quelle pid=17 wird nichts angezeigt." Dahinter stecken zwei
voneinander unabhängige Sachverhalte.

### 1. Das Cockpit-Plugin war cachebar — mit Datenleck

`Order->list` war in `ext_localconf.php` als **cachebare** Action registriert (nur
`status` stand in der non-cacheable-Liste). Die Action hängt aber an zwei Dingen,
die **nicht** in den Seiten-Cache-Schlüssel eingehen:

- dem Order-Token `?t=<orderid>` — der steht seit Phase 7 in
  `FE.cacheHash.excludedParameters` (nötig, damit der `PageArgumentValidator` die
  eID-Aufrufe nicht als „cachebar mit fehlendem cHash" mit 404 abweist), und
  ausgeschlossene Parameter sind kein Bestandteil des Cache-Identifiers;
- dem angemeldeten FE-User — `getPageinfo()` schreibt einen aus Phluser-Token und
  `ordersalt` gebildeten Hash nach `#pagedata`.

Folge: **der erste Aufruf fror die Seite ein.** Im FE reproduziert (Seite 16,
Element `phlorder_order`, Quelle 17):

| Reihenfolge nach `cache:flush` | Ergebnis |
|---|---|
| 1. Aufruf **mit** Token A | Bestellung A (korrekt) |
| 2. Aufruf **ohne** Token | Bestellung A — obwohl gar kein Token übergeben wurde |
| 3. Aufruf mit Token **B** | Bestellung **A** — fremde Bestellung inkl. Name, Abholnummer, Status |

Umgekehrt genauso: wer die Seite zuerst ohne Token aufrief, für den blieb sie
dauerhaft leer — das ist die gemeldete Beobachtung. Der `csh`-Hash aus
`#pagedata` wäre auf demselben Weg an fremde Besucher ausgeliefert worden.

**Behoben:** `list` steht jetzt bei **beiden** Plugins in der
non-cacheable-Liste. Gegengeprüft: Token A → Bestellung A, Token B → Bestellung B,
ohne Token → keine Bestellung; keine fremden Nummern mehr im Dokument.

Die Statusseite war **nicht** betroffen — `Order->status` stand von Anfang an in
der non-cacheable-Liste (mit zwei Tokens gegengeprüft). Da die QR-Codes dorthin
zeigen, war der Kundenpfad also sauber.

### 2. Die Bestellliste des Cockpits existiert nicht

Unabhängig vom Cache zeigt das Cockpit auch im besten Fall **genau eine**
Bestellung an, nämlich die zum Token `?t=<orderid>`. Eine Liste gibt es im
migrierten Stand nirgends:

- `OrderRepository` hat genau zwei Methoden — `getOrderByToken()` (eine Bestellung)
  und `getOrdernumberlatest()` (Zählung für die Nummernvergabe).
- `List.html` legt ein leeres `<div id="oresults">` an; **kein** Template,
  **kein** PHP und **kein** JavaScript schreibt jemals hinein.
- `phlorder.js` enthält nach Phase 8 nur noch das Lade-Overlay, den
  Gritter-Helfer und das doTimeout-Plugin.
- Der eID-Worker kennt vier Funktionen (`smtco`/`smocomp`, `smtcu`, `smo`,
  `gqc`, `lii`) — keine davon liefert eine Bestellliste.

Die Liste wurde also nie migriert (in der Altfassung holte sie sich ein
inzwischen entferntes JS über die eID). Das ist **kein Nebeneffekt** der
FlexForm-Änderung: die Quelle greift nachweislich (Messwerte im Eintrag oben),
es gibt nur nichts, was mehr als eine Bestellung abfragen würde.

Damit hat auch eine **Sortierrichtung** derzeit kein Ziel — ein FlexForm-Feld
dafür wäre genau die tote Konfiguration, die diese Extension mit
`settings.ff.sourceDB` schon einmal hatte. Zurückgestellt bis geklärt ist, was
sortiert werden soll.
