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
 * Restore task for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/pdfdocument/backup/moodle2/restore_pdfdocument_stepslib.php');

/**
 * Provides the steps to perform one complete restore of a pdfdocument activity.
 */
class restore_pdfdocument_activity_task extends restore_activity_task {

    /**
     * No particular settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the restore steps for this activity.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_pdfdocument_activity_structure_step(
            'pdfdocument_structure',
            'pdfdocument.xml'
        ));
    }

    /**
     * Defines the contents in the activity that must be processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('pdfdocument', ['intro'], 'pdfdocument');
        return $contents;
    }

    /**
     * Defines the decoding rules for links belonging to the activity.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule(
            'PDFDOCUMENTVIEWBYID',
            '/mod/pdfdocument/view.php?id=$1',
            'course_module'
        );
        $rules[] = new restore_decode_rule(
            'PDFDOCUMENTINDEX',
            '/mod/pdfdocument/index.php?id=$1',
            'course'
        );
        return $rules;
    }
}
