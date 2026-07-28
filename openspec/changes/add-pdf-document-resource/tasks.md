# Tasks: add-pdf-document-resource

## 1. Plugin skeleton

- [x] 1.1 Create `mod/pdfdocument/` tree with `version.php` (component `mod_pdfdocument`, requires Moodle 4.5+, MATURITY_STABLE) and `pix/monologo.svg` icon
- [x] 1.2 Create `db/install.xml` with the `pdfdocument` table (`id, course, name, intro, introformat, watermark, timemodified`)
- [x] 1.3 Create `db/access.php` with `addinstance`, `view`, `download` capabilities and default archetype grants
- [x] 1.4 Create `lang/en/pdfdocument.php` and `lang/es/pdfdocument.php` with all strings (plugin name, form labels, viewer toolbar, errors, capabilities, privacy)

## 2. Instance lifecycle (pdf-resource)

- [x] 2.1 Implement `lib.php`: `pdfdocument_supports()` (resource archetype, completion-tracks-views, backup, intro), `_add_instance`, `_update_instance`, `_delete_instance`
- [x] 2.2 Implement `mod_form.php`: name/intro, filemanager (`.pdf` only, maxfiles 1, required with validation), watermark checkbox; save draft area to `content` filearea in add/update callbacks
- [x] 2.3 Implement `view.php`: `require_login` + `mod/pdfdocument:view`, fire `course_module_viewed` event, mark completion, render viewer via Mustache template
- [x] 2.4 Implement `classes/event/course_module_viewed.php`

## 3. Secure delivery (secure-file-delivery)

- [x] 3.1 Implement `mod_pdfdocument_pluginfile()` in `lib.php`: require_login with cm, capability check, filearea whitelist (`content`), serve via `send_stored_file` with inline disposition and `Cache-Control: private, no-store`
- [x] 3.2 Honour `forcedownload=1` only for holders of `mod/pdfdocument:download`; ignore it (serve inline) otherwise
- [x] 3.3 Verify byte-range (HTTP 206) responses work through `send_stored_file` for progressive PDF.js loading
- [x] 3.4 Add "Download original" button on view page, rendered only when the user has the download capability, linking with `forcedownload=1`

## 4. Viewer (protected-viewer)

- [x] 4.1 Vendor pdfjs-dist build into the plugin (`amd/src` wrapper + worker file), wire Grunt/rollup build to `amd/build`
- [x] 4.2 Create Mustache template `templates/viewer.mustache`: toolbar (prev/next, page X/Y input, zoom in/out/fit-width), canvas container, loading spinner, error region — no download/print controls
- [x] 4.3 Implement `amd/src/viewer.js`: fetch PDF via config URL, render pages to canvas with text layer disabled, page navigation, zoom, fit-to-width initial scale, loading/error states with lang strings
- [x] 4.4 Add deterrents: `contextmenu` suppression, Ctrl/Cmd+S and Ctrl/Cmd+P interception, `user-select:none`, `@media print` stylesheet hiding the viewer (in `styles.css`)
- [x] 4.5 Implement watermark: when enabled, server passes user fullname/email to AMD init; draw diagonal semi-transparent overlay on each canvas after render
- [x] 4.6 Responsive CSS: fit-to-width on narrow viewports, toolbar usable at 375px

## 5. Plugin hygiene

- [x] 5.1 Implement privacy `null_provider` (`classes/privacy/provider.php`) with `privacy:metadata` lang string
- [x] 5.2 Implement backup/restore (`backup/moodle2/` tasks and steps including `content` file area)
- [x] 5.3 Write `README.md` (install, capabilities, honest limitation note about screenshots/devtools, PDF.js upgrade procedure)

## 6. Verification

- [x] 6.1 PHPUnit: lib callbacks (add/update/delete instance), pluginfile access control matrix (guest / non-enrolled / student / teacher, forcedownload behaviour)
- [ ] 6.2 Manual test pass on Moodle 5.x: add resource as teacher, view as student (nav/zoom/watermark, no download paths, right-click/Ctrl+P blocked), teacher download works, backup→restore round-trip _(blocked: requires a running Moodle 5.x site — no core checkout in this environment)_
- [ ] 6.3 Run Moodle code checker (moodle-cs) and fix warnings _(blocked: moodle-cs/phpcs not installed here; code written to Moodle style, PHP 8.4 lint clean)_
