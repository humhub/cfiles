# cfiles auf Vue.js und Core-API v2

**Datum:** 2026-08-27
**Branch:** `enh/vue` (cfiles), begleitet von einem Beitrag auf `enh/vuejs-integration` (Core)
**Status:** umgesetzt auf `enh/vue` — Korrekturen aus der Umsetzung unten in §12

---

## 1. Ziel

cfiles wird das erste HumHub-Modul, das vollständig als Vue.js-Insel auf der
Core-API v2 läuft. Es dient als Referenz, an der sich die Vue-Architektur des
Cores beweisen muss; ein Marketplace-Release folgt, wenn es trägt.

Drei Ziele, in dieser Reihenfolge:

1. **Backend maximal reduzieren.** Übrig bleiben Domänenlogik und eine saubere
   v2-API. Keine Darstellungslogik in PHP außer den zwei Stream-Widgets.
2. **Einfache Oberfläche** im HumHub-Stil, mit Drag&Drop.
3. **Eine tragfähige Antwort auf das Kontextmenü-Problem**, die für die
   folgenden Modul-Migrationen (wiki, calendar, tasks) taugt.

Nicht-Ziele: keine Single-Page-App, kein Client-Router, keine Änderung am
Datenmodell, kein Bruch bestehender Permalinks.

## 2. Rahmenentscheidungen

| Frage | Entscheidung |
|---|---|
| Datenmodell | `File` **und** `Folder` bleiben `ContentActiveRecord` — mit Stream-Eintrag, Kommentaren, Likes, Sichtbarkeit, Topics, Soft-Delete und Suche. Ersetzt wird ausschließlich die Präsentationsschicht. |
| Migration | Bestehende Installationen müssen migrieren können. Die Datenbank ändert sich nur um das Löschen der Posted-Folder-Records. |
| UI | Darf radikal anders aussehen. |
| Gestrichene Features | „Files from Stream", ZIP-Import/-Export, Datei-Versionen, REST v1, custom_pages-Template-Elemente. |
| Suche im Browser | Nicht in Phase 1. cfiles-Inhalte stehen ohnehin im globalen Suchindex (`File::getSearchAttributes()`, `Folder::getSearchAttributes()`). Der Listen-Endpoint bekommt trotzdem ein optionales Filter-Argument, damit ein Suchfeld später ohne Umbau dazukommt. |

### Warum „Files from Stream" wegfällt

Der virtuelle Ordner ist kein Ordner, sondern eine Query über Anhänge *fremder*
Inhalte (Posts, Kommentare) plus ein Marker-Content-Record vom Typ `posted`.
Er zwingt jeder Schicht einen Sonderfall auf: eigene Zeilenklassen, eigene
Sortier-Defaults, eigene Kontextmenü-Zweige, Ausschlussfilter in jeder
Ordner-Query, eine eigene ID-Konstante (`All_POSTED_FILES_ID = -1`).

cfiles-Dateien sind davon nicht berührt — sie bleiben Content und bleiben
auffindbar. Was wegfällt, ist ausschließlich die Anzeige *fremder* Anhänge im
Dateimodul.

## 3. Der Backend-Schnitt

Ausgangslage: 9.508 Zeilen PHP plus 950 Zeilen JS/CSS (ohne `vendor/`, `messages/`,
`tests/`).

Ergebnis: **PHP 9.508 → 4.998 (−47 %)**, und davon sind rund 1.560 Zeilen *neu*
(API-Controller, Serializer, Services, Asset-Bundle) — vom alten serverseitigen Code
überleben etwa 3.440 Zeilen. Das alte Frontend (610 Zeilen jQuery, 151 Zeilen
Verzeichnis-CSS, 17 PNG-Icons) ist ersatzlos weg; an seine Stelle treten 1.321 Zeilen
Vue-Quellen und 117 Zeilen CSS.

### 3.1 Gestrichen

| Pfad | LOC | Grund |
|---|---:|---|
| `widgets/` ohne `WallEntryFile`/`WallEntryFolder` | ~1.570 | Darstellung wandert in die Insel |
| `models/rows/` | 951 | reine Darstellungslogik |
| `resources/js/humhub.cfiles.js` | 610 | ersetzt durch die Insel |
| `controllers/rest/` + `helpers/RestDefinitions.php` | 457 | ersetzt durch v2 |
| `extensions/custom_pages/` | 364 | gestrichen |
| `models/forms/` | 296 | die API nimmt JSON |
| ZIP (`libs/ZIPCreator|ZipExtractor|ZipUtil`, `ZipController`, `UploadZipAction`, `ZipImportHandler`) | 511 | gestrichen |
| `controllers/` Delete, Move, Upload, Version ganz; Browse und Edit bis auf je eine Rest-Action | 622 | ersetzt durch API-Controller, siehe 3.4 |
| `views/edit`, `views/move`, `views/version` | 171 | gestrichen |
| `resources/css/directorylist.css` + 17 PNG-Icons | 151 | gestrichen |

### 3.2 Reduktion in den Models (~750 LOC)

**Fremdinhalte-Logik — komplett raus:**

- `Folder`: `TYPE_FOLDER_POSTED`, `ALL_POSTED_FILES_TITLE`, `ALL_POSTED_FILES_DESCRIPTION`,
  `initPostedFilesFolder()`, `getPostedFilesFolder()`, `ensurePostedFilesFolderRoot()`,
  `isAllPostedFiles()`, `getSpecialFolders()`, die `type <> 'posted'`-Ausschlüsse in
  `getSubFoldersByParent()`, die Sonderzweige in `getTitle()`/`getDescription()`/`getVisibilityTitle()`
- `File`: `getPostedFiles()`, `getBasePost()`
- `Module`: `defaultPostedFilesSort`, `defaultPostedFilesOrder`
- `libs/FileUtils::getBasePost()` — hat schon heute keinen Aufrufer
- `BaseController::All_POSTED_FILES_ID`

**Darstellungslogik raus aus den Models** — URLs und lokalisierte Labels gehören
in Serializer bzw. Vue:

- `Folder::getCrumb()`, `getEditUrl()`, `createUrl()`, `getVisibilityTitle()`, `attributeHints()`
- `File::getEditUrl()`, `getVisibilityTitle()`
- auf beiden: `getVersionsUrl()`, `getDeleteVersionUrl()`

Bleiben muss, was der Core braucht: `getUrl()` (Permalink), `getIcon()`
(Content-Typ-Icon für Stream und Suche), `getSearchAttributes()`, `getFullPath()`.

**Toter Code:** `Folder::getFolderList()` (nur die gestrichene Move-View) und
`libs/FileUtils::getBasePost()` (kein Aufrufer, und es gab nie einen Rückgabewert).
`getFiles()`, `getFolders()`, `noSpaces()` und `getChildren()` sind **nicht** tot — siehe §12.

**Eine Abstraktionsebene weniger:** `ItemInterface` (60 LOC) hat nach dem Wegfall
von `models/rows/` nur noch einen Implementierer und wird aufgelöst; die zwei Methoden,
die Aufrufer über den abstrakten Typ erreichen (`getTitle()`, `getUrl()`), werden zu
abstrakten Deklarationen auf `FileSystemItem`. `$visibility` und `$hidden` bleiben —
siehe §12.

**Legacy:** `Folder::migrateFromOldStructure()` migriert eine Struktur von vor 2017
(Einführung des Root-Ordners). Fällt mit `humhub.minVersion` 1.20 weg, samt
`tests/codeception/unit/MigrateFromOldTest.php`.

**Und dann noch einmal, härter: Logik raus aus den Modellen.** Was danach übrig blieb, war
immer noch überwiegend Verhalten *um* einen Datensatz herum statt der Datensatz selbst. Es
liegt jetzt in `services/`:

| Service | war vorher |
|---|---|
| `FolderTreeService` | `Folder::initRoot/getRoot/getOrInitRoot/ensureRootFolder*/getSubFoldersByParent/getContainerOwnerId` |
| `FolderContentService` | `Folder::addUploadedFile/addFileFromPath/newFolder/getSubFolders/getSubFiles/getChildren/find*ByName/*Exists/getAddedFileName/getFileInstance/getNewItemVisibility` |
| `ItemMoveService` | `Folder::moveItem/checkForDuplicate/moveSubFilesToContainer/moveSubFoldersToContainer`, `File::renameConflicted`, `Folder::renameConflicted` |
| `ItemVisibilityService` | `Folder::updateVisibility` (rekursiv), `File::updateVisibility` |
| `FolderListingService` | die Auflistung selbst — vorher im `FileList`-Widget |
| `DownloadCounterService` | `Events::onAfterFileAction` (40 Zeilen Request-Bedingungen), `File::getFileByGuid` |
| `IntegrityService` | `Events::onIntegrityCheck` |

Die Modelle behalten Tabelle, Relationen, Regeln, Validatoren, Lifecycle-Hooks (die jetzt an
die Services delegieren), `getSearchAttributes()` und die Berechtigungen — Persistenz und
Content-Integration, sonst nichts.

Ergebnis: `models/` 3.440 → **1.050** (−69 %), `Folder.php` 1.101 → 318,
`File.php` 515 → 281, `FileSystemItem.php` 343 → 316, `ItemInterface.php` → 0.
`Events.php` 251 → 146. Dazu 1.010 Zeilen in `services/`, davon rund 790 aus den Modellen
verlagert.

### 3.3 Was bleibt

`models/File.php`, `models/Folder.php`, `models/FileSystemItem.php` (reduziert),
`permissions/`, `notifications/`, `jobs/`, `libs/FileUploadBatch.php`,
`libs/FileUtils.php`, alle Migrationen, `Events.php` (ohne Custom-Pages-Hook),
die Konfigurationsformulare (`ConfigController`, `ConfigContainerController` samt
Views), `WallEntryFile`/`WallEntryFolder`, `components/UrlRule.php` und
`DownloadController` (Cache-Bypass für umbenannte Dateien).

### 3.4 Neu

- `controllers/api/` — ein Controller-Paar (~350 LOC)
- `serializers/` — `FolderSerializer`, `FileSerializer` (~250 LOC)
- `vue/` — die Insel (~1.200 LOC `.vue`)
- `assets/CfilesVueAsset.php`
- `controllers/BrowseController.php` schrumpft auf eine Action, die die Insel mountet
- `controllers/EditController.php` schrumpft auf eine Action, die ein Modal mit der
  Formular-Insel rendert (Ziel des Stream-Wall-Entry-Edit)

### 3.5 Folge für den Stream

`WallEntryFile` öffnet zum Bearbeiten `/cfiles/edit/file` als Modal
(`$editRoute`, `EDIT_MODE_MODAL`). Der Stream ist noch kein Vue, braucht dieses
Ziel also weiter. `EditController` überlebt als ~20-Zeilen-Action, die ein Modal
rendert, in dem `ItemForm.vue` als Insel steckt — kein zweites Formular, kein
serverseitiges Rendering des Formularinhalts.

## 4. Navigation und URL

Das URL-Schema bleibt **exakt** wie heute:
`<container>/cfiles/browse/index?fid=<id>`, `fid=0` oder fehlend = Root. Damit
bleiben Permalinks, `Folder::getUrl()`, Suchtreffer, Benachrichtigungen und der
Ordner-Link im Stream-Eintrag gültig — kein Redirect, keine URL-Migration.

- **Erster Paint:** Der Page-Controller rendert die Mountstelle mit Ordner-ID
  *und* erster Seite als Props. Kein Request beim Laden — das Muster von
  `ActivityBox`.
- **Ordnerwechsel innerhalb der Insel:** kein Seitenwechsel. Die Insel holt den
  Ordner über die API, tauscht ihren Inhalt und ruft
  `history.pushState({cfiles: {folderId}}, '', url)`.
- **Zurück/Vorwärts:** eigener `popstate`-Listener. `jquery.pjax` steigt in
  `onPjaxPopstate` bei `if (state && state.container)` aus
  (`assets/*/js/jquery.pjax.modified.js:495`) — ein State ohne `container`-Key
  wird von PJAX ignoriert, es kollidiert also nichts.
- **Beim Mount gewinnt die URL**, das Prop ist nur Fallback. Das deckt den einen
  Fall ab, in dem beide auseinanderlaufen können: PJAX stellt beim
  Zurücknavigieren gecachtes HTML wieder her, dessen Prop noch den
  ursprünglichen Ordner trägt.
- **Ordner bleiben echte `<a href>`** — Mittelklick, „In neuem Tab öffnen" und
  Link-Kopieren funktionieren. `preventDefault()` nur, wenn keine Modifier-Taste
  gedrückt ist.
- Ist der Zielordner der Root, pusht die Insel die kanonische Form `?fid=0`.

Keine Router-Bibliothek, keine Routentabelle: ein Parameter, eine
`pushState`-Stelle, ein `popstate`-Handler. Der Core-Grundsatz „kein
Client-Router" bleibt gewahrt — es wird nichts geroutet, es wird ein
Query-Parameter gespiegelt.

## 5. Die v2-API

Routing über `ApiRules::v2()` in `config.php`, Controller in `controllers/api/`,
Wire-Shapes in `serializers/`. Session-Authentifizierung per
`$enableSessionAuth = true`, CSRF für zustandsändernde Requests durch den
Core-Stack.

```
GET    /api/v2/cfiles/folder/<id>            Inhalt eines Ordners
PATCH  /api/v2/cfiles/folder/<id>            Titel, Beschreibung, Sichtbarkeit
POST   /api/v2/cfiles/folder/<id>/folders    Unterordner anlegen
POST   /api/v2/cfiles/folder/<id>/files      Upload (multipart, mehrere Dateien)
PATCH  /api/v2/cfiles/file/<id>              Titel, Beschreibung, Sichtbarkeit
POST   /api/v2/cfiles/items/move             { items: [{type, id}], targetFolderId }
POST   /api/v2/cfiles/items/delete           { items: [{type, id}] }
```

### 5.1 Item-Identität

`{type: 'file' | 'folder', id: <int>}` statt der heutigen `file_<id>`-Strings.
Löschen und Verschieben gibt es **nur** in der Mengenform; das Kontextmenü
schickt ein einelementiges Array, damit es genau einen Weg gibt.

### 5.2 Antwortform von `GET folder/<id>`

```jsonc
{
  "folder":  { /* FolderSerializer, der geöffnete Ordner */ },
  "path":    [ { "id": 1, "title": "Dateien", "isRoot": true }, … ],
  "results": [ /* FolderSerializer und FileSerializer gemischt, Ordner zuerst */ ],
  "total": 137, "page": 1, "pageSize": 50, "pages": 3
}
```

Der Listen-Envelope folgt der Core-Konvention (`results/total/page/pageSize/pages`),
damit die Insel dieselbe Paging-Mechanik nutzt wie `UserList` und `ActivityBox`.

### 5.3 Caller-Kontext ist nicht Teil der Payload

Core-Konvention (`docs/develop/concept-api.md`, „Caller context is not part of a
payload") — das macht die Ordner-Payload cachebar:

| Wert | Woher |
|---|---|
| `canEdit`, `canDelete` pro Item | vom Kontextmenü-Endpoint, der beim Öffnen des `⋮` ohnehin lädt |
| `canWrite` für den Container | einmal als Prop vom Page-Controller — container-, nicht itembezogen, und der Seitenrender ist ohnehin pro Benutzer |

### 5.4 Sortierung und Paginierung

`?sort=name|size|updatedAt&order=asc|desc`. Wird `sort` explizit übergeben, wird
die Wahl in den Benutzer-Einstellungen des Moduls persistiert — dasselbe
Verhalten wie `FileList::initSortOrder()` heute, nur im Endpoint statt im Widget.

**Ein bestehender Fehler wird dabei behoben:** Heute lädt `FileList` *alle*
Unterordner und *alle* Dateien ungeblättert; nur der Posted-Files-Zweig
paginiert. Ein Ordner mit 5.000 Dateien rendert 5.000 Zeilen. Der neue Endpoint
blättert über eine `UNION ALL`-Projektion `(id, type, title, size, updated_at)`
aus `cfiles_folder` und `cfiles_file`; Ordner sortieren vor Dateien
(`ORDER BY is_folder DESC, <sort>`), Sortierung und Slice macht die Datenbank.

### 5.5 Serializer

- `FolderSerializer` — `id`, `type: "folder"`, `contentId`, `title`, `description`,
  `visibility`, `isRoot`, `parentId`, `itemCount`, `createdAt`, `updatedAt`,
  `creator` (Core-`UserSerializer`), `url`
- `FileSerializer` — `id`, `type: "file"`, `contentId`, `guid`, `title`,
  `description`, `fileName`, `mimeType`, `size`, `downloadUrl`, `previewUrl`,
  `downloadCount`, `createdAt`, `updatedAt`, `creator`, `url`

Beide caller-neutral. `downloadCount` wandert von einer eigenen Spalte in die
Metazeile und wird nur ausgeliefert, wenn die Moduleinstellung
`displayDownloadCount` gesetzt ist.

## 6. Die Oberfläche

Zeilenliste im HumHub-eigenen `.hh-list`-Muster (`protected/humhub/resources/scss/_list.scss`)
innerhalb des bestehenden `.panel` — dasselbe Muster, das `NotificationList`,
`UserList`, `ActivityBox` und `SpaceChooser` im Core schon benutzen. Kein neues
CSS-Vokabular: `h4` als Titel, `h5`/`.time` als Metazeile, Hover und
Accent-Rand links kommen aus Theme-Variablen.

Eine Zeile: Auswahl-Checkbox · Icon oder Thumbnail · Titel (mit
Sichtbarkeits-Badge) · Metazeile (Größe · Zeit · Beschreibung) · Ersteller-Avatar · `⋮`.

Toolbar: Breadcrumb links; rechts Sortier-Dropdown, „Ordner", „Dateien".
Bei aktiver Auswahl blendet sich die Auswahl-Leiste (Verschieben, Löschen) ein.

Weggefallen gegenüber heute: die Spalte „Likes/Kommentare"; der Download-Zähler verliert seine Spalte und erscheint, wenn `displayDownloadCount` gesetzt ist, in der Metazeile;
die Sichtbarkeit wird vom Spalten-Icon zum Badge an der Zeile. Mehrfachauswahl
bleibt, dient aber nur noch Löschen und Verschieben.

### 6.1 Komponenten

```
cfiles/
├── assets/CfilesVueAsset.php
├── vue/
│   ├── FileBrowser.vue          die Insel
│   ├── ItemForm.vue             Umbenennen/Beschreiben, auch Ziel des Stream-Modals
│   └── browser/
│       ├── BrowserToolbar.vue   Breadcrumb, Sortierung, Buttons, Auswahl-Aktionen
│       ├── ItemList.vue         .hh-list, Drop-Zone, „Mehr laden"
│       ├── ItemRow.vue          Icon/Thumbnail, Titel, Metazeile, Auswahl, ⋮
│       ├── MoveDialog.vue       UiModal + Ordnerbaum
│       └── useItems.js          Laden, Sortierung, Auswahl, optimistische Mutationen
└── resources/js/humhub.cfiles.vue.js   committetes Build-Artefakt
```

Gebaut mit `node vue.build.mjs --module <pfad-zu-cfiles>` aus dem Core-Checkout;
der Build akzeptiert einen Modulpfad als Ziel (`vue.build.mjs`, Auflösung in
`resolveTarget`).

Aus dem Core wiederverwendet: `UiModal`, `DropdownMenu`, `HumHubForm` samt
`TextField`/`TextareaField`/`SelectField`, `UploadField`, `UserImage`,
`StatusBar` (über die Bridge), `ContentControls` (neu, siehe Abschnitt 7).

`i18nCategories: ['CfilesModule.base', 'base', 'ContentModule.base', 'UserModule.base']`
auf `FileBrowser.vue` — die Insel deklariert die Kategorien ihres gesamten
Teilbaums, nicht nur die eigenen.

### 6.2 Drag&Drop

Kein Fremdpaket, HTML5-Events in `useItems.js` (~60 Zeilen):

- **Upload:** Dateien vom Desktop auf die Liste ziehen. Drop-Zone ist die
  gesamte Liste; ein Overlay zeigt das Ziel.
- **Verschieben:** Zeilen ziehen, Drop-Ziele sind Ordnerzeilen,
  Breadcrumb-Segmente und der „..".-Eintrag. Mehrfachauswahl zieht die ganze
  Auswahl.
- Beide Wege enden auf denselben Endpoints wie die Menü-Aktionen.

## 7. Core-Beitrag: `ContentControls`

Auf dem Core-Branch `enh/vuejs-integration`. Das Kontextmenü einer Datei ist
heute `FileListContextMenu extends WallEntryControls` — also kein cfiles-eigenes
Menü, sondern der zentrale Content-Kontextmenü-Stack. Beiträge kommen per
`WallEntryControls::EVENT_INIT` unter anderem aus `topic` (Core),
`share-between-humhub`, `reportcontent`, `polls`, `gdd-certificates`. Dieselbe
Konstruktion nutzen wiki, calendar und tasks — die Lösung hier ist Vorlage für
alle folgenden Migrationen.

`ContentControls.vue` wird die erste Insel des `content`-Moduls (das hat bisher
weder `vue/` noch `controllers/api/` noch `serializers/`).

### 7.1 Drei Quellen, ein Menü

1. **Native Vue** — die Core-Aktionen (Bearbeiten, Löschen, Permalink,
   Anpinnen, Verschieben, Themen, Archivieren), gesteuert über ein
   `capabilities`-Objekt vom Endpoint. Kein HTML vom Server.
2. **Server-Deskriptoren** — bestehende PHP-Beiträge, unverändert.
   `MenuLink::describe()` und `WallEntryControlLink::describe()` liefern
   `{id, label, icon, sortOrder, url, htmlOptions}`. Da `EditPageLink` (wiki),
   `ShareLink` (share-between-humhub) und `ContentTopicButton` (topic) alle von
   `WallEntryControlLink` erben, deckt eine einzige Standardimplementierung sie
   ab, ohne dass ein Modul etwas ändert.
3. **Vue-Registry** — `registerMenuEntry('content.controls', …)` für migrierte
   und neue Module. Kann Einträge aus 1 und 2 per `id` überschreiben oder per
   `removeMenuEntry()` entfernen.

Zusammengeführt mit den Auflösungsregeln, die `DropdownMenu` schon hat: gleiche
`id` überschreibt, `removeMenuEntry()` gewinnt dauerhaft, `sortOrder` aufsteigend,
`condition(context)` filtert.

### 7.2 Der Notausgang

Ein `WidgetMenuEntry` mit einer Widget-Klasse, die sich nicht beschreiben lässt
(eigene View statt `WallEntryControlLink` — etwa `CloseButton`/`ResetButton` aus
polls), wird serverseitig gerendert und als `html`-Feld im Deskriptor
ausgeliefert. Die Insel hängt es per `v-html` + `v-additions` ein und sortiert es
über `sortOrder` normal mit ein.

Nichts bricht, kein Modul muss sofort etwas tun. Jeder solche Eintrag erzeugt
eine Deprecation-Warnung im Log; der Pfad wird für eine spätere Version zur
Entfernung angekündigt.

### 7.3 Lazy Loading

Der `⋮`-Toggle rendert sofort, ohne Request. Erst beim Öffnen holt die
Komponente `capabilities` und Deskriptoren:

```
GET /api/v2/content/<id>/controls?viewContext=browser
```

Genau das Muster, das der Core beim Kommentar-Kontextmenü mit
`GET comment/<id>/permissions` schon fährt. Bewusst **nicht** gebatcht: eine
gebatchte Auslieferung würde die Listen-Payload vom Betrachter abhängig und
damit uncachebar machen.

### 7.4 Verwendungsort als Parameter

`view-context="stream" | "browser" | "detail" | "modal"` — knüpft an das
serverseitig bereits existierende `StreamEntryOptions::VIEW_CONTEXT_*` an. Im
Dateibrowser fallen Anpinnen, Archivieren und Permalink weg, im Stream die
Datei-Aktionen. Serverseitig wählt der Endpoint daraus das passende
`WallStreamEntryOptions`-Profil.

### 7.5 Umfang des Core-Beitrags

- `protected/humhub/modules/content/vue/ContentControls.vue` + `ContentVueAsset`
- `humhub\modules\ui\menu\MenuLink::describe()`,
  `humhub\modules\content\widgets\WallEntryControlLink::describe()`
- `humhub\modules\content\controllers\api\ControlsController`
- Doku in `docs/develop/ui-js-vuejs-extensions.md`,
  Breaking-Change-Eintrag in `docs/develop/module-migrate.md`
- vitest-Tests für die Komponente, Codeception-Tests für den Endpoint

## 8. Migration

- **Datenbank:** eine Migration löscht die Ordner-Records vom Typ `posted` samt
  ihrer Content-Records. Sonst ändert sich nichts.
- **URLs:** unverändert, siehe Abschnitt 4.
- **`module.json`:** `version` → `1.0.0`, `humhub.minVersion` → `1.20`
  (die Core-Version mit Vue-Layer und API v2). Neue CHANGELOG-Sektion
  `1.0.0 (Unreleased)`.
- **Drittmodule:** REST v1 (`/cfiles/…` unter `/api/v1`) entfällt ersatzlos.
  custom_pages-Templates mit File-/Folder-Elementen verlieren diese Elemente.
  Beides gehört als Breaking Change in den CHANGELOG.

## 9. Reihenfolge der Umsetzung

1. **Core:** `describe()` auf `MenuLink`/`WallEntryControlLink`,
   `ControlsController`, `ContentControls.vue`, `ContentVueAsset`, Doku, Tests.
2. **cfiles:** v2-API — Routing, `controllers/api/`, `serializers/`, Tests.
3. **cfiles:** Abriss der ~6.500 Zeilen aus Abschnitt 3.
4. **cfiles:** die Insel — Liste, Navigation, Upload, Umbenennen, Löschen,
   Verschieben, Drag&Drop.
5. **cfiles:** `BrowseController` und `EditController` auf ihre Rest-Actions
   reduzieren, `WallEntryFile`/`WallEntryFolder` gegen die reduzierten Models
   prüfen.
6. **cfiles:** Migration, `module.json`, CHANGELOG.
7. **Tests:** vitest für die Insel, Codeception für die API.

## 10. Phase 2 (nicht Teil dieses Branches)

- Persistenter Ordnerbaum links als Drop-Ziel und Directory-Selector, wie im
  Wiki-Modul im Enterprise-Theme.
- Suchfeld im Browser über das vorgesehene Filter-Argument.
- Grid-Ansicht mit Vorschaubildern für bildlastige Ordner.

## 11. Offene Risiken

- **`UploadField` und der Modul-Endpoint.** Der Core-`UploadField` und
  `upload/uploadClient.js` sind auf `POST /api/v2/file` zugeschnitten. Ob der
  Client sich auf `POST cfiles/folder/<id>/files` parametrisieren lässt, ist noch
  zu prüfen; sonst braucht die Insel einen eigenen, kleinen Upload-Client.
- **`ContentControls` als Core-Abhängigkeit.** Der cfiles-Branch ist erst
  lauffähig, wenn der Core-Beitrag steht. Schritt 1 kommt deshalb zuerst.
- **`WallStreamEntryOptions`-Profile pro `viewContext`.** Die serverseitige
  Zuordnung von `viewContext` auf ein Options-Profil existiert bisher nur
  implizit über die aufrufenden Widgets; sie muss im Endpoint explizit gemacht
  werden.

## 12. Korrekturen aus der Umsetzung

Vier Annahmen dieses Entwurfs haben der Umsetzung nicht standgehalten. Sie stehen hier,
statt oben stillschweigend korrigiert zu werden.

**`getFiles()`/`getFolders()` sind keine toten Methoden, sondern Relationen.** Sie werden
als `$this->files` / `$this->folders` in `Folder::afterSoftDelete()` und
`Folder::beforeDelete()` benutzt und tragen dort die Kaskadenlöschung. Die Suche, die sie
für tot erklärt hat (`grep 'getFiles('`), konnte den Relationszugriff nicht sehen. Ebenso
`noSpaces()` — als String in `rules()` referenziert — und `getChildren()`, das `moveItem()`
und `MoveTest` benutzen. Alle vier bleiben.

**`getItemType()` und die zusammengesetzten Item-IDs sind ganz weg.** Die alte Adressierung
(`file_5`, `folder_5`, dazu `folder-root`/`folder-posted` als Typ) hatte nach der Umstellung
auf `{type, id}` keinen Aufrufer mehr — mitsamt `FileSystemItem::getItemById()`,
`getItemId()` auf beiden Modellen und `FileUtils::getItemTypeByExt()`. Ebenfalls tot und
entfernt: `getFullPath()`/`getPathFromId()` auf beiden Modellen (70 Zeilen, hingen an der
alten REST-Definition), `Folder::resolveConflictsBeforeCreate()`,
`Folder::getFolderList()`, `FileUtils::getBasePost()` und `FileUtils::getBaseContent()`.

**`FileSystemItem::$hidden` ist kein Formular-Hilfsfeld.** Es trägt die
„Hide in Stream"-Vorgabe: `afterFind()` liest sie aus dem Content-Record, `beforeSave()`
setzt beim Einfügen die Modul-Vorgabe, `afterSave()` schreibt sie zurück. Entfernt man das
Feld, verliert jede neue Datei die Container-Vorgabe. `$visibility` funktioniert genauso
(`Folder::beforeSave()` und `File::afterSave()` wenden es rekursiv an). Beide bleiben, jetzt
mit einem Docblock, der erklärt warum.

**Es sind drei API-Controller, nicht zwei.** `FolderController` (lesen, anlegen, ändern,
hochladen), `FileController` (ändern) und `ItemController` (verschieben, löschen). Die
Alternative wäre gewesen, die Datei-Änderung in einen „Item"-Controller zu legen, in dem sie
nicht hingehört. Das Listing selbst liegt in `services/FolderListingService`, weil der
Page-Controller dieselbe Payload für den ersten Paint braucht.

**`ContentControls` Lane 1 liefert keine nativen Core-Aktionen.** Der Entwurf las sich so,
als würden Bearbeiten/Löschen/Permalink/Anpinnen/Archivieren in Vue nachgebaut. Das ist
nicht passiert: die Insel nimmt die nativen Einträge als `entries`-Prop **vom Host** entgegen
— für cfiles sind das Herunterladen, Bearbeiten, Verschieben, Löschen — und der Endpoint
liefert zusätzlich ein `capabilities`-Objekt, an dem der Host seine Einträge ausrichtet. Die
Core-Widgets (`EditLink`, `DeleteLink`, `PermaLink`, …) laufen vorerst über den
Deskriptor- bzw. HTML-Pfad. Für cfiles fällt das nicht ins Gewicht, weil das alte
`FileListContextMenu` genau diese Core-Einträge ohnehin abgeschaltet hat. Sie nativ
nachzubauen bleibt offen und ist eine eigene Aufgabe.
