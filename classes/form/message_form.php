<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_queuedbulkmessage\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Message composition form.
 *
 * @package    local_queuedbulkmessage
 * @copyright  2026 Sameh Naim <naim.a@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_form extends \moodleform {

    /** @var string[] Allowed attachment file types. */
    public const ALLOWED_ATTACHMENT_TYPES = [
        'image',
        '.pdf',
        '.doc',
        '.docx',
        '.odt',
        '.rtf',
        '.txt',
        '.xls',
        '.xlsx',
        '.ods',
        '.ppt',
        '.pptx',
        '.odp',
    ];

    /**
     * Defines the form fields.
     */
    protected function definition(): void {
        $mform = $this->_form;

        $editoroptions = $this->_customdata['editoroptions'] ?? [];

        $mform->addElement('text', 'subject', get_string('messagesubject', 'local_queuedbulkmessage'), ['size' => 64]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'select',
            'sendmode',
            get_string('sendmode', 'local_queuedbulkmessage'),
            [
                'notification' => get_string('sendmode_notification', 'local_queuedbulkmessage'),
                'private' => get_string('sendmode_private', 'local_queuedbulkmessage'),
            ]
        );
        $mform->setType('sendmode', PARAM_ALPHA);
        $mform->setDefault('sendmode', 'notification');
        $mform->addHelpButton('sendmode', 'sendmode', 'local_queuedbulkmessage');

        $mform->addElement('editor', 'messagebody', get_string('messagebody', 'local_queuedbulkmessage'), null, $editoroptions);
        $mform->setType('messagebody', PARAM_RAW);
        $mform->addRule('messagebody', get_string('required'), 'required', null, 'client');

        $attachmentoptions = $this->_customdata['attachmentoptions'] ?? [];
        $mform->addElement(
            'filemanager',
            'attachments',
            get_string('attachments', 'local_queuedbulkmessage'),
            null,
            $attachmentoptions
        );
        $mform->addHelpButton('attachments', 'attachments', 'local_queuedbulkmessage');

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $this->add_action_buttons(true, get_string('queuebulkmessage', 'local_queuedbulkmessage'));
    }

    /**
     * Validates message content.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        global $USER;

        $errors = parent::validation($data, $files);

        if (trim($data['subject'] ?? '') === '') {
            $errors['subject'] = get_string('required');
        }

        if (empty($data['messagebody']['text']) || trim($data['messagebody']['text']) === '') {
            $errors['messagebody'] = get_string('required');
        }

        if (!in_array($data['sendmode'] ?? '', ['notification', 'private'], true)) {
            $errors['sendmode'] = get_string('invalidsendmode', 'local_queuedbulkmessage');
        }

        $draftitemid = $data['attachments'] ?? 0;
        if ($draftitemid) {
            $files = get_file_storage()->get_area_files(
                \context_user::instance($USER->id)->id,
                'user',
                'draft',
                $draftitemid,
                'id',
                false
            );

            if (count($files) > 5) {
                $errors['attachments'] = get_string('toomanyattachments', 'local_queuedbulkmessage');
            }

            $filetypesutil = new \core_form\filetypes_util();
            foreach ($files as $file) {
                if (!$filetypesutil->is_allowed_file_type($file->get_filename(), self::ALLOWED_ATTACHMENT_TYPES)) {
                    $errors['attachments'] = get_string('invalidattachmenttype', 'local_queuedbulkmessage');
                    break;
                }
            }
        }

        return $errors;
    }
}
