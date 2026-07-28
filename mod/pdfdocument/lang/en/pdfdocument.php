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
 * English strings for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'PDF document';
$string['modulename'] = 'PDF document';
$string['modulenameplural'] = 'PDF documents';
$string['modulename_help'] = 'The PDF document activity lets a teacher share a PDF that students can read on screen through a protected viewer but cannot download by ordinary means. Note: no web technology can fully prevent screen capture; this activity removes casual download paths and, optionally, watermarks each page with the reader\'s identity.';
$string['pluginadministration'] = 'PDF document administration';

// Form.
$string['name'] = 'Name';
$string['pdffile'] = 'PDF file';
$string['pdffile_help'] = 'Upload a single PDF file. Students will view it in the protected on-screen viewer.';
$string['watermark'] = 'Watermark each page with the reader\'s identity';
$string['watermark_help'] = 'When enabled, every page is overlaid with a semi-transparent diagonal watermark showing the viewing user\'s full name and email, to deter redistribution of screenshots.';
$string['erroremptypdf'] = 'You must upload a PDF file.';

// Viewer.
$string['loading'] = 'Loading document…';
$string['page'] = 'Page';
$string['of'] = 'of';
$string['previouspage'] = 'Previous page';
$string['nextpage'] = 'Next page';
$string['zoomin'] = 'Zoom in';
$string['zoomout'] = 'Zoom out';
$string['fitwidth'] = 'Fit width';
$string['errordisplay'] = 'This document cannot be displayed.';
$string['nofile'] = 'No PDF file has been uploaded for this activity yet.';

// Capabilities.
$string['pdfdocument:addinstance'] = 'Add a new PDF document';
$string['pdfdocument:view'] = 'View the PDF document';

// Privacy.
$string['privacy:metadata'] = 'The PDF document plugin does not store any personal data. Views are recorded through the standard Moodle logging system.';

// Events.
$string['eventcoursemoduleviewed'] = 'PDF document viewed';
