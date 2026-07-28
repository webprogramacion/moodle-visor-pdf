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
 * Restore structure step for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the structure step to restore one pdfdocument activity.
 */
class restore_pdfdocument_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the structure to be restored.
     *
     * @return array The restore paths.
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('pdfdocument', '/activity/pdfdocument');
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores one pdfdocument record.
     *
     * @param array|stdClass $data The data to restore.
     */
    protected function process_pdfdocument($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timemodified = time();

        $newitemid = $DB->insert_record('pdfdocument', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restores the files after the instance has been created.
     */
    protected function after_execute() {
        // Restore both the intro files and the protected content PDF.
        $this->add_related_files('mod_pdfdocument', 'intro', null);
        $this->add_related_files('mod_pdfdocument', 'content', null);
    }
}
