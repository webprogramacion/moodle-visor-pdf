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
 * Backup task for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pdfdocument/backup/moodle2/backup_pdfdocument_stepslib.php');

/**
 * Provides the steps to perform one complete backup of a pdfdocument activity.
 */
class backup_pdfdocument_activity_task extends backup_activity_task {

    /**
     * No particular settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the backup steps for this activity.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_pdfdocument_activity_structure_step(
            'pdfdocument_structure',
            'pdfdocument.xml'
        ));
    }

    /**
     * Encodes URLs to the view.php script so they can be restored portably.
     *
     * @param string $content The content to encode.
     * @return string The encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '#');

        // Link to the view page by course module id: view.php?id=<id>.
        $search = "#($base/mod/pdfdocument/view\.php\?id=)([0-9]+)#";
        $content = preg_replace($search, '$@PDFDOCUMENTVIEWBYID*$2@$', $content);

        // Link to the index page by course id: index.php?id=<id>.
        $search = "#($base/mod/pdfdocument/index\.php\?id=)([0-9]+)#";
        $content = preg_replace($search, '$@PDFDOCUMENTINDEX*$2@$', $content);

        return $content;
    }
}
