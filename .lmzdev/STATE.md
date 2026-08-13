# Current state

## Confirmed

- Gemeinsamer Promotion-Kern ist auf direkten Webrequest-Betrieb reduziert: keine Promotion-Commands, Auditmail/Ankerfelder oder separaten Zugriffskontexte; synchrone HMAC-Auditkette bleibt.
- Blog-Rich-HTML wird im Admin vor jeder Erstellung und Aktualisierung mit einer engen, editorgerechten DOM-Allowlist sanitisiert. Aktive Inhalte, beliebige Styles/Klassen, Event-Attribute und unsichere URL-Schemata gelangen nicht mehr in `posts.body`.
- LMZ Dev workspace initialized.
- Der gespiegelte Admin-Auditanker verarbeitet pending Ereignisse auch bei deaktivierter Promotion, liest nur MAC-geschuetzte DB-Settings und verifiziert bei jedem Lauf auch bereits vollstaendig verankerte Koepfe.
- Promotion-Mitarbeiter erhalten nur dann irgendein Teamrecht, wenn die normalisierte Matrix ihres aktuellen Promotion-Teams exakt den drei verbindlichen Promotion-Rechten entspricht; Zusatzrechte und fehlende Pflichtrechte sperren fail-closed.
- The embedded `ZUM ARTIKEL` button, link icon, and button-only color constant were removed from all News social-image formats.
- Der Promotion-Betrieb ist vollstaendig request-basiert: Adminwerte beschraenken sich auf Freigabe, Einloeseadresse und QR-Laufzeit; Mitarbeiter-Einladung/Hochstufung legt das exakte Promotion-Team direkt in derselben Webaktion an oder haertet es. Es gibt keine Promotion-Commands, Jobs oder Scheduler-Abhaengigkeit.
- The title/excerpt block keeps the former 108 px button height empty above the existing 96 px format-scaled bottom margin.
- Social-image layout version is `5`, forcing cached images to be regenerated with the corrected spacing.
- Each News can now persist separate Story, Post, and Link layout settings in `posts.social_image_settings`.
- The collapsible modal controls typography, horizontal/bottom/section spacing, logo size, and category size through allowlisted dropdown values.
- Social-image layout version is now `6`; the normalized JSON configuration is part of the cache fingerprint.
- Saving is scoped to the active Story, Post, or Link format; its preview URL and cache entry change immediately, while other format settings, dirty state, and cached images remain untouched.
- The former central settings dropdown is replaced by seven image-anchored hover/focus/tap hotspots for logo, category, title, text gap, excerpt, side padding, and bottom spacing.
- Every select change now persists and re-renders immediately without a save button; logo variants are persisted separately per format as well.
- The shared-database migration for `posts.social_image_settings` was executed successfully on 2026-08-07.
- Benutzerstatus-Aenderungen aus Liste und Profil laufen ueber einen gemeinsamen, atomaren Dienst: delegiertes `users.manage` kann globale Admins weder einzeln noch im Bulk beruehren; Selbstdeaktivierung und das Entfernen des letzten aktiven globalen Admins sind fail-closed gesperrt.
- Jetstream-Account-Deletion ist im Admin vollstaendig deaktiviert; der serverseitige Deleter blockiert auch direkte bzw. alte Livewire-Aufrufe mit 403.
- Delegierte Web-Editoren erhalten `custom_css`, `custom_js` und `custom_meta` nicht im Snapshot und koennen diese Felder weder setzen noch ueberschreiben; globale Admins duerfen sie weiterhin kontrolliert pflegen. WebPage-Titel und SVG-Icons werden strikt validiert.

## Verification

- Admin-Auditkern fokussiert: 5 Tests/55 Assertions bestanden. Gemeinsamer Settings-Service und -Model sind byte-identisch mit Base; PHP-Lint der geaenderten Kerndateien bestanden.
- Blog-HTML-Sicherheit: Admin 3 Tests/48 Assertions bestanden; echter Livewire-Speicherweg, erlaubte Formatierung, externe/interne Links und verschleierte aktive Payloads sind abgedeckt. PHP-Lint und Pint-Test der eng geaenderten PHP-Dateien bestanden.
- Auditanker-Fokus: `AdminPromotionSecurityTest` mit 31 Tests/181 Assertions bestanden; zwei PHP-Lints, Pint fuer den Command und `git diff --check` bestanden.
- DB-gesteuerte Promotion-Einstellungen inklusive Volladmin-UI: gesamter Admin-Promotion-Ordner 67 Tests/538 Assertions gruen.
- `php artisan test tests\Unit\NewsSocialImageTest.php tests\Unit\NewsSocialImageRouteTest.php`: 19 tests passed, 94 assertions.
- `php -l` passed for the renderer, controller, and focused test file.
- `git diff --check` passed.
- Story, square, and landscape artifacts were re-rendered and visually checked with the preserved CTA-height spacing.
- `php artisan test tests\Unit\NewsSocialImageTest.php tests\Unit\NewsSocialImageRouteTest.php tests\Unit\NewsCacheVersionTest.php`: 37 tests passed, 180 assertions.
- The Base migration was checked with `php artisan migrate --pretend`; PHP syntax, `git diff --check`, and the News Tailwind contract passed.
- Custom Story, Post, and Link layouts were rendered and visually inspected.
- Latest focused verification: 39 tests passed with 196 assertions; the News Tailwind contract and both repository diff checks passed.
- Final verification: 40 focused tests passed with 206 assertions. Authenticated browser QA confirmed desktop hover/click, mobile tap, immediate preview URL revision, database persistence after reload, and isolated Story/Post values and logo variants.
- Finale Promotion-/RBAC-/Audit-Suite nach Gate-Review: 43 Tests mit 342 Assertions bestanden. Base-Promotion: 29 Tests mit 178 Assertions. Die Negativtests decken Zusatzrecht, fehlendes Pflichtrecht, manipulierte Transitionen, erneute Tokenbindung und unauditierte Konfigurationsaenderungen ab.
- Nach dem letzten P1-Gate sind auch alle verschachtelten Admin-Livewire-Komponenten vor Mount und bei jeder Hydration exakt autorisiert. Der gemeinsame Icon-Sanitizer verwirft aktive SVG-Inhalte, externe Referenzen, Event-Attribute und nicht erlaubte FontAwesome-Klassen fail-closed; Altbestaende werden vor jedem Raw-Render erneut geprueft.
- Abschliessender kombinierter Admin-Sicherheitslauf: 72 Tests mit 485 Assertions bestanden (gesamter `tests/Feature/Promotion`-Ordner plus Icon-Sanitizer-Unit-Test). Darin: Nested-RBAC 4/75, Icon-Livewire 3/17, Sanitizer 15/25.
- Admin-Promotion-/RBAC-Suite inklusive User-Target-Guard: 50 Tests mit 368 Assertions bestanden; fokussierter Guard-Lauf: 7 Tests mit 26 Assertions. Vier PHP-Lints, Scoped-Diff-Check und Pint fuer die zwei neuen Dateien bestanden.
- Legacy-P1-Fokus: Admin 7 Tests/51 Assertions, Base 4 Tests/18 Assertions bestanden; zusaetzlich PHP-Lint, Pint fuer neue/eng geaenderte Dateien und beide Repository-Diff-Checks bestanden.
- Vereinfachter Promotion-Webbetrieb: kompletter Admin-Ordner `tests/Feature/Promotion` 68 Tests/544 Assertions bestanden; Settings-UI 7/47; automatische Team-Webaktionen und nicht registrierte Promotion-Commands sind abgedeckt. PHP-Lint, Pint-Test der eng geaenderten PHP-Dateien und `git diff --check` bestanden.

## Risks and blockers

- Full-file Pint remains non-clean because these existing files contain broader legacy style and line-ending deviations; no broad formatter churn was applied for this narrow change.
- Authenticated QA used a uniquely named temporary local Admin and News record; both records and the generated image-cache directory were deleted and verified absent afterward.
- Global `php artisan view:cache` still fails on the pre-existing unresolved `x-admin-layout` component outside this modal; direct Livewire rendering succeeds.
- Der waehrend paralleler Promotion-Settings-Arbeit ausgefuehrte Gesamtgate war temporaer rot, weil neue DB-Settings-Tabellen/Seeds in den bestehenden Promotion-Testschemas noch fehlten; der zustaendige Settings-Agent integriert diese Tests. Die hier fokussierten Sicherheitslaeufe sind vollstaendig gruen.
