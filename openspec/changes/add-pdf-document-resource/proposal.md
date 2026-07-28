# Proposal: add-pdf-document-resource

## Why

Teachers need to share PDF documents (course notes, exams, copyrighted material) with students inside Moodle courses, but Moodle's built-in File resource always allows students to download the original file. There is currently no way to let students *read* a PDF on screen while preventing (or strongly deterring) them from downloading and redistributing it.

## What Changes

- New Moodle 5.x activity module plugin `mod_pdfdocument` ("Documento PDF") that can be added to any course section.
- Teachers upload a single PDF file when creating/editing the resource instance (standard Moodle filepicker, stored in the module's file area).
- Students open the resource and view the PDF in a custom in-browser viewer built on PDF.js that renders pages to `<canvas>` elements — the browser never receives a native PDF document it can "Save As".
- The PDF bytes are served through a protected plugin endpoint that enforces login, course enrolment, and capability checks, sends `Content-Disposition: inline` with anti-caching headers, and is never exposed as a plain pluginfile download URL to students.
- Viewer-level deterrents: download/print/save UI removed, right-click context menu disabled, text-layer selection disabled, keyboard shortcuts (Ctrl+S / Ctrl+P) blocked, optional per-user watermark overlay (name/email) rendered over each page.
- Users with a management capability (`mod/pdfdocument:download`, granted to teachers/managers by default) can still download the original file.
- Standard plugin plumbing: install schema, capabilities, language strings (ES + EN), completion-on-view support, backup/restore, privacy API (null provider or user-data declaration), GDPR compliance.

**Honest limitation (documented, not hidden):** no web technology can make on-screen content impossible to capture (screenshots, browser devtools, network inspection by a determined user). The goal is to remove all casual download paths and make extraction inconvenient, which is the realistic maximum.

## Capabilities

### New Capabilities

- `pdf-resource`: Activity module lifecycle — creating, editing, deleting the "Documento PDF" resource in a course; PDF upload via mod_form; storage in Moodle File API; visibility, completion, backup/restore, privacy.
- `protected-viewer`: The student-facing in-browser viewer — PDF.js canvas rendering, page navigation, zoom, responsive layout, accessibility fallbacks, and the UI-level anti-download deterrents (no toolbar download/print, blocked context menu/shortcuts, optional watermark).
- `secure-file-delivery`: The server-side protected endpoint that streams PDF bytes only to authorized enrolled users with proper session/capability checks, inline disposition, no-store caching headers, and a teacher-only original-file download path.

### Modified Capabilities

_None — this is a greenfield plugin; no existing specs._

## Impact

- **New code**: entire plugin tree `mod/pdfdocument/` (version.php, mod_form.php, lib.php, view.php, db/, classes/, amd/, lang/, styles.css, pix/).
- **Dependencies**: PDF.js (bundled as AMD/ESM module inside the plugin — Moodle core does not ship it for reuse by third-party plugins).
- **Moodle APIs used**: Activity module API, File API, Capability/Access API, Output/Templates (Mustache), AMD JS modules, Completion API, Backup/Restore API, Privacy API.
- **Target**: Moodle 5.x (also expected to work on 4.5 LTS with same APIs), PHP 8.2+.
- **No changes to Moodle core** — pure standard plugin, installable via ZIP or plugins directory.
