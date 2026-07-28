# PDF document (mod_pdfdocument)

A Moodle 5.x activity module named **Documento PDF** / **PDF document**. A teacher
uploads a single PDF; students read it on screen through a protected viewer built
on PDF.js, with all casual download paths removed.

## Features

- Adds as a **resource** in the activity chooser.
- One PDF per instance, uploaded via the standard file picker (`.pdf` only).
- In-browser viewer that renders pages to `<canvas>` (no native browser PDF
  viewer, no built-in download button).
- Page navigation, zoom in/out, fit-to-width, responsive/mobile layout.
- Anti-download deterrents: no download/print toolbar, blocked right-click,
  blocked Ctrl/Cmd+S and Ctrl/Cmd+P, disabled text selection, print stylesheet
  that blanks the viewer.
- Optional **per-user watermark** (full name + email) drawn over every page.
- No download path for anyone: the original file is never served as an
  attachment, only streamed inline for on-screen rendering.
- Completion-on-view, backup/restore, GDPR (null provider), English + Spanish.

## Important limitation (please read)

**This is deterrence, not DRM.** No web technology can prevent a determined user
from capturing on-screen content — screenshots, screen recording, or reading the
network response through browser developer tools are always possible when the
browser can display the document. This plugin removes every *casual* download
path and, when the watermark is enabled, ties any leaked screenshot to the user
who viewed it. Do not rely on it to protect content whose leakage would be
catastrophic.

## Requirements

- Moodle 4.5 LTS or later (targets 5.x).
- PHP 8.2+.
- PDF.js 4.x is bundled in `js/pdfjs/` (see `js/pdfjs/README.md`).

## Installation

1. Copy this directory to `mod/pdfdocument` in your Moodle install.
2. Log in as admin and visit **Site administration → Notifications** to run the
   installer.

## Capabilities

| Capability | Default roles | Purpose |
| --- | --- | --- |
| `mod/pdfdocument:addinstance` | editingteacher, manager | Add the activity to a course. |
| `mod/pdfdocument:view` | all enrolled roles (+ guest) | View the document in the viewer. |

## How the protection works

- The PDF is stored in a module file area and served **only** through
  `mod_pdfdocument_pluginfile()`, which enforces login, enrolment, activity
  visibility, and the `view` capability before sending any bytes.
- Bytes are always sent with `Content-Disposition: inline` and private, no-store
  cache headers. There is no download path: any `forcedownload=1` a user appends
  to the URL is ignored and the file is still served inline.
- The view page never renders a link to the file; the URL is passed only to the
  viewer JavaScript. (The real barrier is the capability check, not URL
  obscurity.)

## Rebuilding the JavaScript

`amd/build/viewer.min.js` is committed so the plugin runs out of the box. If you
edit `amd/src/viewer.js`, regenerate the build from the Moodle root:

```bash
grunt amd --root=mod/pdfdocument
```

## Upgrading PDF.js

Replace the two files in `js/pdfjs/` with a newer **legacy** build and bump the
version in `thirdpartylibs.xml`. See `js/pdfjs/README.md`.

## License

GPL v3 or later. PDF.js is Apache-2.0.
