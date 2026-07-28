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
 * Instance settings form for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * The main mod_pdfdocument configuration form.
 */
class mod_pdfdocument_mod_form extends moodleform_mod {

    /**
     * Defines the form fields.
     */
    public function definition() {
        $mform = $this->_form;

        // General section: name and intro.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'pdfdocument'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // PDF file: exactly one, PDF only.
        $filemanageroptions = [
            'subdirs'        => 0,
            'maxfiles'       => 1,
            'accepted_types' => ['.pdf'],
        ];
        $mform->addElement(
            'filemanager',
            'pdffile',
            get_string('pdffile', 'pdfdocument'),
            null,
            $filemanageroptions
        );
        $mform->addHelpButton('pdffile', 'pdffile', 'pdfdocument');
        $mform->addRule('pdffile', get_string('erroremptypdf', 'pdfdocument'), 'required', null, 'client');

        // Watermark toggle.
        $mform->addElement('advcheckbox', 'watermark', get_string('watermark', 'pdfdocument'));
        $mform->addHelpButton('watermark', 'watermark', 'pdfdocument');
        $mform->setDefault('watermark', 0);

        // Standard course module elements (visibility, completion, etc.).
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Prepares the draft file area for editing an existing instance.
     *
     * @param array $defaultvalues Passed by reference; the draft item id is injected.
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current && !empty($this->current->coursemodule)) {
            $draftitemid = file_get_submitted_draft_itemid('pdffile');
            $context = context_module::instance($this->current->coursemodule);
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'mod_pdfdocument',
                'content',
                0,
                ['subdirs' => 0, 'maxfiles' => 1]
            );
            $defaultvalues['pdffile'] = $draftitemid;
        }
    }

    /**
     * Server-side validation.
     *
     * @param array $data Submitted form data.
     * @param array $files Submitted files.
     * @return array Array of "element => error" pairs.
     */
    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);

        // Ensure a file actually exists in the draft area.
        if (empty($data['pdffile'])) {
            $errors['pdffile'] = get_string('erroremptypdf', 'pdfdocument');
        } else {
            $draftfiles = file_get_draft_area_info_safe($data['pdffile']);
            if ($draftfiles === 0) {
                $errors['pdffile'] = get_string('erroremptypdf', 'pdfdocument');
            }
        }

        return $errors;
    }
}

/**
 * Returns the number of files in a draft area, tolerating a missing area.
 *
 * Wrapper kept separate so validation() stays readable.
 *
 * @param int $draftitemid The draft item id.
 * @return int Number of files present.
 */
function file_get_draft_area_info_safe($draftitemid) {
    $info = file_get_draft_area_info($draftitemid);
    return isset($info['filecount']) ? (int)$info['filecount'] : 0;
}
