# protected-viewer

## ADDED Requirements

### Requirement: In-browser canvas rendering
The view page SHALL render the PDF with a bundled PDF.js (pdfjs-dist) AMD module that draws each page onto `<canvas>` elements. The browser SHALL never receive the PDF as a navigable document (no `<iframe>`, `<embed>`, `<object>`, or direct navigation to the PDF URL), and the PDF.js text layer SHALL be disabled so page text cannot be selected or copied.

#### Scenario: Student opens the resource
- **WHEN** an enrolled student opens a "Documento PDF" activity
- **THEN** the first page renders on a canvas inside the page, with no native browser PDF viewer involved

#### Scenario: Text selection attempted
- **WHEN** the student tries to select or copy text on a rendered page
- **THEN** no text is selectable (canvas only, `user-select: none` on the container)

### Requirement: Viewer navigation controls
The viewer SHALL provide a toolbar with previous/next page buttons, a current-page input showing "page X of Y", zoom in/out, and fit-to-width. The toolbar SHALL NOT contain download, print, or open-in-new-tab controls.

#### Scenario: Multi-page navigation
- **WHEN** the student clicks "next" on a 10-page document showing page 1
- **THEN** page 2 renders and the indicator shows "2 / 10"

#### Scenario: Zoom
- **WHEN** the student clicks zoom-in
- **THEN** the current page re-renders at the larger scale without reloading the page

### Requirement: Casual-download deterrents
Within the viewer container the plugin SHALL suppress the right-click context menu, swallow Ctrl/Cmd+S and Ctrl/Cmd+P keydown events, and include a print stylesheet that hides the viewer content when printing (`@media print`).

#### Scenario: Right-click on page
- **WHEN** the student right-clicks a rendered page
- **THEN** no context menu appears (no "Save image as…" path)

#### Scenario: Print attempt
- **WHEN** the student presses Ctrl+P or uses the browser's print menu
- **THEN** the shortcut is blocked, and if the browser print dialog is reached anyway the printed output shows no document pages

### Requirement: Per-user watermark option
When the instance setting `watermark` is enabled, the viewer SHALL overlay each rendered page with a semi-transparent diagonal watermark containing the viewing user's full name and email, drawn onto the same canvas after page render. The strings SHALL be provided server-side to the AMD module init.

#### Scenario: Watermark enabled
- **WHEN** `watermark = 1` and student "Ana Pérez <ana@example.com>" views any page
- **THEN** every rendered page shows a diagonal semi-transparent overlay with her name and email

#### Scenario: Watermark disabled
- **WHEN** `watermark = 0`
- **THEN** pages render with no overlay

### Requirement: Loading and error states
The viewer SHALL show a loading indicator until the first page renders and SHALL show a localised error message (not a blank area or JS console-only error) if the PDF cannot be fetched or parsed.

#### Scenario: Corrupt PDF
- **WHEN** the stored file cannot be parsed by PDF.js
- **THEN** the student sees a localised "document cannot be displayed" message and the event is logged to the JS console for debugging

### Requirement: Responsive layout
The viewer SHALL be usable on mobile-width viewports: pages scale to container width by default (fit-to-width initial zoom) and toolbar controls remain reachable.

#### Scenario: Mobile browser
- **WHEN** a student opens the resource on a 375px-wide viewport
- **THEN** the page canvas fits the width without horizontal scrolling and navigation controls are visible
