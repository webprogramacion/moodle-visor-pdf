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
 * PHPUnit data generator for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Creates pdfdocument instances for testing.
 */
class mod_pdfdocument_generator extends testing_module_generator {

    /**
     * Creates a new pdfdocument instance, optionally seeding a PDF file.
     *
     * @param array|stdClass|null $record Instance data.
     * @param array|null $options Generator options.
     * @return stdClass The created instance record.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (array)$record;

        $defaults = [
            'watermark' => 0,
        ];
        foreach ($defaults as $key => $value) {
            if (!isset($record[$key])) {
                $record[$key] = $value;
            }
        }

        // Remember whether a file should be seeded, then strip it from the record.
        $seedfile = !empty($record['seedfile']);
        unset($record['seedfile']);

        $instance = parent::create_instance((object)$record, (array)$options);

        if ($seedfile) {
            $cm = get_coursemodule_from_instance('pdfdocument', $instance->id);
            $context = context_module::instance($cm->id);
            $fs = get_file_storage();
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'mod_pdfdocument',
                'filearea'  => 'content',
                'itemid'    => 0,
                'filepath'  => '/',
                'filename'  => 'sample.pdf',
            ];
            // A minimal but valid PDF header is enough for storage/access tests.
            $fs->create_file_from_string($filerecord, "%PDF-1.4\n%%EOF\n");
        }

        return $instance;
    }
}
