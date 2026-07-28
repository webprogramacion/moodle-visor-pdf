# Design: add-pdf-document-resource

## Context

Greenfield Moodle activity module plugin. The repo currently contains only OpenSpec scaffolding; the plugin will be developed here and installed into a Moodle 5.x site under `mod/pdfdocument`.

Moodle background relevant to this design:

- Files uploaded through mod_form are stored via the **File API** in a component file area (`mod_pdfdocument`, filearea `content`, itemid 0) and are normally served through `pluginfile.php` → the plugin's `<plugin>_pluginfile()` callback. That callback is where access control happens; the URL itself is guessable but useless without a valid session that passes the checks.
- Any URL the browser can render can also be fetched by the user (same cookie). Therefore "non-downloadable" can only mean: **no native PDF is ever handed to the browser as a document**, no download UI exists, and casual extraction paths (right-click, Ctrl+S, print) are blocked. A determined user with devtools can always capture the network response or screenshot pages. This is a deterrence design, not DRM.

## Goals / Non-Goals

**Goals:**
- Standard, self-contained `mod_pdfdocument` plugin for Moodle 5.x (compatible with 4.5 LTS APIs), installable via ZIP.
- Teacher uploads exactly one PDF per instance; students read it in an embedded viewer with good UX (paging, zoom, mobile-friendly).
- Remove every casual download path for students; keep an explicit download ability for teachers/managers via capability.
- Optional per-user watermark to deter screenshots/redistribution.
- Full plugin hygiene: capabilities, lang strings (en + es), completion-on-view, backup/restore, privacy API.

**Non-Goals:**
- Real DRM / making capture impossible (impossible on the web; documented limitation).
- Multi-file collections, folders, or non-PDF formats.
- Server-side rasterization to images (rejected — see Decisions).
- Annotation, highlighting, or collaborative features.

## Decisions

### D1 — Plugin type: activity module (`mod_pdfdocument`), not `resource` subtype
Moodle has no "resource subplugin" mechanism; the File resource is itself a `mod`. A custom activity module is the standard way to add a new "thing you add to a course". `FEATURE_MOD_ARCHETYPE = MOD_ARCHETYPE_RESOURCE` makes it behave/appear as a resource in the activity chooser.

### D2 — Client rendering with bundled PDF.js (canvas), not `<iframe>`/`<object>`/server-side images
- `<iframe src=file.pdf>` uses the browser's native viewer, which has a built-in download button and hands the full PDF to the browser as a document → rejected.
- Server-side conversion (Ghostscript/Imagick page→PNG) never ships PDF bytes, but adds server dependencies, heavy CPU/storage cost, blurry zoom, and breaks text accessibility → rejected.
- **PDF.js rendering to `<canvas>`** is the accepted approach: the PDF arrives as an XHR/fetch response consumed by JS (not a navigable document), the browser shows no native PDF UI, and we fully control the toolbar. We bundle the pdfjs-dist build inside `amd/` (Moodle core's copy is internal to mod_assign's annotator and not a supported public API).
- The PDF.js **text layer is disabled** to prevent select-all/copy of full text (trade-off: sacrifices in-page text selection; acceptable given the product goal).

### D3 — Delivery: standard `pluginfile.php` endpoint hardened in `mod_pdfdocument_pluginfile()`
One protected endpoint, using the normal Moodle mechanism (sesskey-free, works with SSO, respects course visibility):
- `require_login($course, true, $cm)` + group/availability checks → enrolment enforced.
- Capability `mod/pdfdocument:view` required.
- Serve with `send_stored_file($file, 0, 0, false, ['dontdie' => …])` forced **inline** (`Content-Disposition: inline`) and `Cache-Control: private, no-store` so proxies/disk caches don't retain it.
- The student-facing page never prints this URL in a link/`href`; it is passed to the viewer AMD module as a JS config value. (Security by obscurity is NOT the barrier — the capability check is; hiding it just removes the casual copy-link path.)
- A separate `viewurl` vs `download` distinction: requests carrying `?forcedownload=1` are only honoured when the user has `mod/pdfdocument:download`; otherwise served inline or rejected.

### D4 — Anti-download deterrents live in the viewer module (AMD)
- Custom toolbar: page prev/next, page number input, zoom in/out/fit — **no** download, print, or open-in-new-tab buttons.
- `contextmenu` suppressed inside the viewer container; `keydown` handler swallows Ctrl/Cmd+S and Ctrl/Cmd+P (and window `beforeprint` gets a CSS `@media print { .viewer { display:none } }` fallback that blanks pages when printing).
- `user-select: none` on the viewer container.
- Optional watermark (admin/teacher setting per instance): after each page render, the user's full name + email is drawn diagonally onto the same canvas (server injects the strings via the AMD init call), so screenshots identify the leaker.

### D5 — Instance settings (mod_form)
- Name, intro (standard), **one** PDF via filemanager (accepted type `.pdf`, maxfiles 1).
- `watermark` (yes/no, default no), `allowteacherdownload` handled purely by capability (no setting needed).
- Completion: supports `FEATURE_COMPLETION_TRACKS_VIEWS`; viewing fires `\mod_pdfdocument\event\course_module_viewed` and marks completion.

### D6 — DB schema
Single table `pdfdocument`: `id, course, name, intro, introformat, watermark, timemodified`. File itself lives in the file storage pool; no file metadata duplicated in the table.

### D7 — Privacy API
The plugin stores no per-user data (views are core logstore events) → implements `\core_privacy\local\metadata\null_provider`.

## Risks / Trade-offs

- [Determined user extracts PDF via devtools/network tab] → Accepted residual risk; watermark option ties leaks to a user; limitation documented in README so admins don't assume DRM.
- [Disabled text layer harms accessibility (screen readers can't read canvas)] → Documented; future option could enable an ARIA text layer per instance where copy-protection matters less than accessibility. Intro/description remains fully accessible.
- [Very large PDFs slow to load in one fetch] → PDF.js range-request support left enabled; `send_stored_file` supports byte ranges natively in Moodle. Renders page 1 as soon as metadata + first chunk arrive.
- [Bundled PDF.js ages / CVEs] → Pin a current pdfjs-dist version, note upgrade procedure in README; it's a single vendored directory swap.
- [Mobile app (Moodle App) support] → Out of scope initially; module works in mobile browser. `FEATURE_*` flags declared honestly so the app shows "open in browser".

## Migration Plan

Fresh install only (no upgrade path needed): place plugin at `mod/pdfdocument`, visit Site administration → Notifications. Uninstall removes table and file areas via standard plugin uninstall. Backup/restore implemented so instances survive course backups from day one.

## Open Questions

- None blocking. (Post-MVP candidates: per-instance toggle for text layer/accessibility mode, page-view progress completion, Moodle App remote add-on.)
