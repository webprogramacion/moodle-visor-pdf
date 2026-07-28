# secure-file-delivery

## ADDED Requirements

### Requirement: Access-controlled file serving
The plugin SHALL serve PDF bytes only through `mod_pdfdocument_pluginfile()`, which MUST enforce `require_login($course, true, $cm)` (login + enrolment + module visibility/availability) and require the `mod/pdfdocument:view` capability in the module context before sending any bytes. Any failed check SHALL result in the standard Moodle access error, never partial content.

#### Scenario: Enrolled student fetches the file
- **WHEN** the viewer requests the pluginfile URL with an enrolled student's session
- **THEN** the server streams the PDF bytes with HTTP 200 (or 206 for range requests)

#### Scenario: Unauthenticated request
- **WHEN** the pluginfile URL is requested without a valid session
- **THEN** the server redirects to login and serves no file bytes

#### Scenario: Non-enrolled user
- **WHEN** a logged-in user not enrolled in the course requests the URL
- **THEN** access is denied with a Moodle error page

#### Scenario: Hidden activity
- **WHEN** the activity is hidden or restricted by availability conditions for the requesting student
- **THEN** the request is denied

### Requirement: Inline, non-cacheable delivery
File responses to viewers SHALL be sent with `Content-Disposition: inline` (never `attachment`) and `Cache-Control: private, no-store` so the response is not written to shared or disk caches. Byte-range requests SHALL be supported for progressive loading.

#### Scenario: Response headers
- **WHEN** the viewer fetches the PDF
- **THEN** the response carries `Content-Disposition: inline`, `Cache-Control` including `no-store`, and `Content-Type: application/pdf`

#### Scenario: Range request
- **WHEN** PDF.js requests bytes 0–65535 via a `Range` header
- **THEN** the server answers HTTP 206 with the requested slice

### Requirement: No student-facing download path
The student view page SHALL contain no anchor, button, or link whose target is the PDF file URL; the URL SHALL only be passed as configuration to the viewer AMD module. Requests with `forcedownload=1` (attachment disposition) SHALL be honoured only for users holding `mod/pdfdocument:download`; for other users the parameter SHALL be ignored and the file served inline.

#### Scenario: Student forces download parameter
- **WHEN** a student manually appends `forcedownload=1` to the file URL
- **THEN** the response is still served inline (no attachment disposition)

#### Scenario: Page source inspection
- **WHEN** a student inspects the view page HTML
- **THEN** no `<a href>` pointing at the PDF exists; the URL appears only inside the AMD module init data

### Requirement: Teacher original-file download
Users holding `mod/pdfdocument:download` (granted by default to editingteacher and manager) SHALL have a visible "Download original" control on the view page that returns the file with `Content-Disposition: attachment`.

#### Scenario: Teacher downloads
- **WHEN** a teacher clicks "Download original"
- **THEN** the browser downloads the unmodified PDF file

#### Scenario: Student sees no control
- **WHEN** a student (without the download capability) views the resource
- **THEN** no "Download original" control is rendered

### Requirement: Capability definitions
The plugin SHALL define in `db/access.php`: `mod/pdfdocument:addinstance` (editingteacher, manager), `mod/pdfdocument:view` (all enrolled archetypes incl. guest per course settings), and `mod/pdfdocument:download` (editingteacher, manager).

#### Scenario: Default role assignment
- **WHEN** the plugin is installed
- **THEN** students receive only `view`; editing teachers and managers additionally receive `addinstance` and `download`
