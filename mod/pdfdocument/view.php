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
 * Displays a single PDF document in the protected viewer.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$p  = optional_param('p', 0, PARAM_INT);  // Instance id.

if ($p) {
    $instance = $DB->get_record('pdfdocument', ['id' => $p], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('pdfdocument', $instance->id, $instance->course, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('pdfdocument', $id, 0, false, MUST_EXIST);
    $instance = $DB->get_record('pdfdocument', ['id' => $cm->instance], '*', MUST_EXIST);
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/pdfdocument:view', $context);

// Trigger the viewed event and mark completion on view.
$event = \mod_pdfdocument\event\course_module_viewed::create([
    'objectid' => $instance->id,
    'context'  => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('pdfdocument', $instance);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Page setup.
$PAGE->set_url('/mod/pdfdocument/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_activity_record($instance);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name));

if (trim(strip_tags($instance->intro)) !== '') {
    echo $OUTPUT->box(format_module_intro('pdfdocument', $instance, $cm->id), 'generalbox', 'intro');
}

$file = pdfdocument_get_file($context);

if (!$file) {
    // No file uploaded yet: show a notice instead of an empty viewer.
    echo $OUTPUT->notification(get_string('nofile', 'pdfdocument'), \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->footer();
    exit;
}

// Build the protected pluginfile URL for the viewer (never rendered as a link).
$fileurl = moodle_url::make_pluginfile_url(
    $context->id,
    'mod_pdfdocument',
    'content',
    0,
    $file->get_filepath(),
    $file->get_filename()
);

// Worker URL for PDF.js, served from the plugin.
$workerurl = new moodle_url('/mod/pdfdocument/js/pdfjs/pdf.worker.min.js');

// Watermark text (only meaningful when enabled).
$watermarktext = '';
if (!empty($instance->watermark)) {
    $watermarktext = fullname($USER) . ' · ' . $USER->email;
}

// Render the viewer template.
$templatedata = [
    'loadinglabel'  => get_string('loading', 'pdfdocument'),
    'errorlabel'    => get_string('errordisplay', 'pdfdocument'),
    'pagelabel'     => get_string('page', 'pdfdocument'),
    'oflabel'       => get_string('of', 'pdfdocument'),
    'prevlabel'     => get_string('previouspage', 'pdfdocument'),
    'nextlabel'     => get_string('nextpage', 'pdfdocument'),
    'zoominlabel'   => get_string('zoomin', 'pdfdocument'),
    'zoomoutlabel'  => get_string('zoomout', 'pdfdocument'),
    'fitwidthlabel' => get_string('fitwidth', 'pdfdocument'),
];

echo $OUTPUT->render_from_template('mod_pdfdocument/viewer', $templatedata);

// Initialise the AMD viewer with the protected file URL and options.
$PAGE->requires->js_call_amd('mod_pdfdocument/viewer', 'init', [[
    'fileUrl'       => $fileurl->out(false),
    'workerUrl'     => $workerurl->out(false),
    'watermark'     => !empty($instance->watermark),
    'watermarkText' => $watermarktext,
]]);

echo $OUTPUT->footer();
