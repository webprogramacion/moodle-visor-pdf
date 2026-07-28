# pdf-resource

## ADDED Requirements

### Requirement: Resource creation in a course
The plugin SHALL provide an activity module named "Documento PDF" (`mod_pdfdocument`) that teachers with `mod/pdfdocument:addinstance` can add to any course section from the activity chooser, where it SHALL appear as a resource (`MOD_ARCHETYPE_RESOURCE`).

#### Scenario: Teacher adds the resource
- **WHEN** a teacher opens the activity chooser in a course with editing on and selects "Documento PDF"
- **THEN** the mod_form opens with fields for name, description, a PDF file picker, and a watermark toggle

#### Scenario: User without addinstance capability
- **WHEN** a user lacking `mod/pdfdocument:addinstance` browses the activity chooser
- **THEN** "Documento PDF" is not offered

### Requirement: Single PDF upload
The mod_form SHALL require exactly one file with accepted type `.pdf` (filemanager, maxfiles = 1), stored via the Moodle File API in component `mod_pdfdocument`, filearea `content`, itemid 0. Saving without a file SHALL fail validation with an error message.

#### Scenario: Valid PDF uploaded
- **WHEN** the teacher uploads a `.pdf` file and saves the form
- **THEN** the instance is created and the file is stored in the `content` file area of the module context

#### Scenario: No file provided
- **WHEN** the teacher submits the form without uploading a file
- **THEN** the form redisplays with a validation error on the file field and no instance is created

#### Scenario: Replacing the PDF
- **WHEN** the teacher edits an existing instance and replaces the file
- **THEN** the previous file is removed from the file area and the new PDF is served to students thereafter

### Requirement: Instance lifecycle
The plugin SHALL implement `pdfdocument_add_instance`, `pdfdocument_update_instance`, and `pdfdocument_delete_instance`, persisting to a `pdfdocument` table (`id, course, name, intro, introformat, watermark, timemodified`). Deleting an instance SHALL remove its DB record and all files in its file areas.

#### Scenario: Instance deleted
- **WHEN** a teacher deletes a "Documento PDF" activity
- **THEN** the `pdfdocument` row and the stored PDF file are removed

### Requirement: Completion on view
The module SHALL declare `FEATURE_COMPLETION_TRACKS_VIEWS` and, when a student opens the view page, SHALL trigger the `course_module_viewed` event and mark view-based completion.

#### Scenario: Student views with view-completion enabled
- **WHEN** completion is set to "Student must view this activity" and a student opens the resource
- **THEN** the activity is marked complete for that student and a `course_module_viewed` event is logged

### Requirement: Backup and restore
The plugin SHALL implement Moodle backup/restore (backup_pdfdocument_activity_task / restore counterpart) including the instance settings and the PDF file area.

#### Scenario: Course backup and restore
- **WHEN** a course containing a "Documento PDF" instance is backed up and restored into another course
- **THEN** the restored instance contains the same settings and a working copy of the PDF

### Requirement: Privacy API compliance
The plugin SHALL implement `\core_privacy\local\metadata\null_provider`, declaring that it stores no personal data.

#### Scenario: Privacy registry
- **WHEN** an admin views the plugin in the privacy registry
- **THEN** it reports that no personal data is stored, with a lang-string explanation

### Requirement: Localisation
All user-facing strings SHALL come from language packs, with `lang/en` and `lang/es` provided. The plugin display name SHALL be "PDF document" (en) / "Documento PDF" (es).

#### Scenario: Spanish site
- **WHEN** a user with language `es` uses the plugin
- **THEN** all plugin UI strings (form labels, viewer toolbar, errors) appear in Spanish
