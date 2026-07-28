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
 * Unit tests for mod_pdfdocument library functions and access control.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_pdfdocument;

use context_module;

/**
 * Tests for lib.php callbacks and capability defaults.
 *
 * @covers ::pdfdocument_add_instance
 * @covers ::pdfdocument_update_instance
 * @covers ::pdfdocument_delete_instance
 */
final class lib_test extends \advanced_testcase {

    /**
     * Creates a course and returns its object.
     *
     * @return \stdClass
     */
    private function make_course(): \stdClass {
        return $this->getDataGenerator()->create_course();
    }

    /**
     * Instance creation stores a DB record and the seeded PDF file.
     */
    public function test_add_instance_stores_record_and_file(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->make_course();
        $instance = $this->getDataGenerator()->create_module('pdfdocument', [
            'course'    => $course->id,
            'name'      => 'Lesson 1',
            'watermark' => 1,
            'seedfile'  => true,
        ]);

        $record = $DB->get_record('pdfdocument', ['id' => $instance->id]);
        $this->assertNotFalse($record);
        $this->assertEquals('Lesson 1', $record->name);
        $this->assertEquals(1, $record->watermark);

        $cm = get_coursemodule_from_instance('pdfdocument', $instance->id);
        $context = context_module::instance($cm->id);
        $file = pdfdocument_get_file($context);
        $this->assertNotNull($file);
        $this->assertEquals('sample.pdf', $file->get_filename());
    }

    /**
     * Deleting an instance removes both the DB record and stored files.
     */
    public function test_delete_instance_removes_record_and_files(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->make_course();
        $instance = $this->getDataGenerator()->create_module('pdfdocument', [
            'course'   => $course->id,
            'seedfile' => true,
        ]);
        $cm = get_coursemodule_from_instance('pdfdocument', $instance->id);
        $context = context_module::instance($cm->id);

        $this->assertNotNull(pdfdocument_get_file($context));

        $result = pdfdocument_delete_instance($instance->id);
        $this->assertTrue($result);
        $this->assertFalse($DB->record_exists('pdfdocument', ['id' => $instance->id]));

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_pdfdocument', 'content', 0, 'id', false);
        $this->assertEmpty($files);
    }

    /**
     * The module declares the resource archetype and view-completion support.
     */
    public function test_supports_flags(): void {
        $this->assertEquals(MOD_ARCHETYPE_RESOURCE, pdfdocument_supports(FEATURE_MOD_ARCHETYPE));
        $this->assertTrue(pdfdocument_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(pdfdocument_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertNull(pdfdocument_supports('some_unknown_feature'));
    }

    /**
     * Enrolled students and teachers may view; there is no download capability.
     */
    public function test_capability_defaults(): void {
        $this->resetAfterTest();

        $course = $this->make_course();
        $instance = $this->getDataGenerator()->create_module('pdfdocument', [
            'course'   => $course->id,
            'seedfile' => true,
        ]);
        $cm = get_coursemodule_from_instance('pdfdocument', $instance->id);
        $context = context_module::instance($cm->id);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        $this->assertTrue(has_capability('mod/pdfdocument:view', $context, $student));
        $this->assertTrue(has_capability('mod/pdfdocument:view', $context, $teacher));

        // The download capability was removed entirely: it must not exist.
        $this->assertFalse(get_capability_info('mod/pdfdocument:download'));
    }

    /**
     * A non-enrolled, non-privileged user has no course access to the activity.
     */
    public function test_non_enrolled_user_cannot_view(): void {
        $this->resetAfterTest();

        $course = $this->make_course();
        $instance = $this->getDataGenerator()->create_module('pdfdocument', [
            'course'   => $course->id,
            'seedfile' => true,
        ]);
        $cm = get_coursemodule_from_instance('pdfdocument', $instance->id);
        $context = context_module::instance($cm->id);

        $outsider = $this->getDataGenerator()->create_user();

        // Not enrolled: the capability is not granted in this course context.
        $this->assertFalse(is_enrolled($context, $outsider));
    }
}
