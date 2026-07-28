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
 * Backup structure step for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete pdfdocument structure for backup.
 */
class backup_pdfdocument_activity_structure_step extends backup_activity_structure_step {

    /**
     * Builds the backup structure.
     *
     * @return backup_nested_element The root element wrapped by the activity.
     */
    protected function define_structure() {
        // The pdfdocument instance holds no user-specific data, so userinfo is not used.
        $pdfdocument = new backup_nested_element('pdfdocument', ['id'], [
            'name', 'intro', 'introformat', 'watermark', 'timemodified',
        ]);

        $pdfdocument->set_source_table('pdfdocument', ['id' => backup::VAR_ACTIVITYID]);

        // Define file annotations: the stored PDF in the content area.
        $pdfdocument->annotate_files('mod_pdfdocument', 'intro', null);
        $pdfdocument->annotate_files('mod_pdfdocument', 'content', null);

        return $this->prepare_activity_structure($pdfdocument);
    }
}
