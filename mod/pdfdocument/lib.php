<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Declares which optional Moodle features this module supports.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed True/false for supported features, null for unknown ones.
 */
function pdfdocument_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        default:
            return null;
    }
}

/**
 * Adds a new pdfdocument instance.
 *
 * @param stdClass $data Data from the module form.
 * @param mod_pdfdocument_mod_form|null $mform The form instance.
 * @return int The id of the newly created instance.
 */
function pdfdocument_add_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->watermark = empty($data->watermark) ? 0 : 1;

    $data->id = $DB->insert_record('pdfdocument', $data);

    // Persist the uploaded file into the module file area now that we have a context.
    pdfdocument_save_draft_file($data);

    return $data->id;
}

/**
 * Updates an existing pdfdocument instance.
 *
 * @param stdClass $data Data from the module form.
 * @param mod_pdfdocument_mod_form|null $mform The form instance.
 * @return bool Always true.
 */
function pdfdocument_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->watermark = empty($data->watermark) ? 0 : 1;

    $DB->update_record('pdfdocument', $data);

    pdfdocument_save_draft_file($data);

    return true;
}

/**
 * Deletes a pdfdocument instance and all of its files.
 *
 * @param int $id The instance id.
 * @return bool True on success.
 */
function pdfdocument_delete_instance($id) {
    global $DB;

    $instance = $DB->get_record('pdfdocument', ['id' => $id]);
    if (!$instance) {
        return false;
    }

    // Remove stored files (the module context is deleted by core afterwards, but be explicit).
    $cm = get_coursemodule_from_instance('pdfdocument', $id);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_pdfdocument', 'content');
    }

    $DB->delete_records('pdfdocument', ['id' => $id]);

    return true;
}

/**
 * Copies the uploaded PDF from the form draft area into the module content file area.
 *
 * Enforces a single file: the content area is emptied before the draft is committed.
 *
 * The course module id is read from $data->coursemodule, which Moodle populates
 * before calling add_instance()/update_instance(). We must NOT rely on
 * get_coursemodule_from_instance() here: during initial creation the course
 * module is not yet linked to the instance, so that lookup would fail and the
 * uploaded file would silently not be saved.
 *
 * @param stdClass $data Form data with the draft item id in $data->pdffile and the cm id in $data->coursemodule.
 * @return void
 */
function pdfdocument_save_draft_file($data) {
    if (empty($data->pdffile) || empty($data->coursemodule)) {
        return;
    }

    $context = context_module::instance($data->coursemodule);

    // Replace any existing content so only the latest single PDF remains.
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_pdfdocument', 'content');

    file_save_draft_area_files(
        $data->pdffile,
        $context->id,
        'mod_pdfdocument',
        'content',
        0,
        ['subdirs' => 0, 'maxfiles' => 1]
    );
}

/**
 * Returns the single stored PDF file for an instance, or null if none.
 *
 * @param context_module $context The module context.
 * @return stored_file|null The stored PDF, or null if the area is empty.
 */
function pdfdocument_get_file($context) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_pdfdocument', 'content', 0, 'itemid, filepath, filename', false);
    if (empty($files)) {
        return null;
    }
    return reset($files);
}

/**
 * Serves files from the mod_pdfdocument file areas.
 *
 * This is the single protected delivery endpoint. Everyone receives the PDF
 * inline (never as an attachment) and only after login, enrolment and the
 * mod/pdfdocument:view capability check pass. There is no download path: the
 * $forcedownload flag is always overridden to false.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The module context.
 * @param string $filearea The file area requested.
 * @param array $args Remaining path arguments (itemid, filepath, filename).
 * @param bool $forcedownload Ignored; kept for the required callback signature.
 * @param array $options Additional serving options.
 * @return bool False if the file could not be served (Moodle emits the error).
 */
function pdfdocument_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Only the content area is served.
    if ($filearea !== 'content') {
        return false;
    }

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Enforce login + enrolment + activity visibility/availability.
    require_login($course, true, $cm);

    // Enforce the view capability.
    require_capability('mod/pdfdocument:view', $context);

    // There is no download path at all: the file is always served inline,
    // regardless of any forcedownload parameter a user may append to the URL.
    $forcedownload = false;

    // Locate the file. Path is /<contextid>/mod_pdfdocument/content/0/<filename>.
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_pdfdocument', 'content', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    // Prevent shared/disk caches from retaining the protected document.
    // send_stored_file supports byte-range (HTTP 206) responses natively, which
    // PDF.js relies on for progressive loading.
    send_stored_file($file, 0, 0, $forcedownload, [
        'cacheability' => 'private',
        'immutable'    => false,
        'dontdie'      => false,
    ]);

    return true;
}
