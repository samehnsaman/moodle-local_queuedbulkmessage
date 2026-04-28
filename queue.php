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

/**
 * Queues a bulk user message for background processing.
 *
 * @package    local_queuedbulkmessage
 * @copyright  2026 Sameh Naim <naim.a@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/repository/lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$return = new moodle_url($returnurl ?: '/admin/user/user_bulk.php');

if (empty($CFG->messaging)) {
    throw new \moodle_exception('messagingdisable', 'error');
}

if (empty($SESSION->bulk_users) || !is_array($SESSION->bulk_users)) {
    redirect(
        $return,
        get_string('noselectedusers', 'local_queuedbulkmessage'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$syscontext = context_system::instance();
$PAGE->set_url(new moodle_url('/local/queuedbulkmessage/queue.php', ['returnurl' => $returnurl ?: null]));
$PAGE->set_context($syscontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('queuebulkmessage', 'local_queuedbulkmessage'));
$PAGE->set_heading(get_string('queuebulkmessage', 'local_queuedbulkmessage'));
$PAGE->set_primary_active_tab('siteadminnode');
$PAGE->set_secondary_active_tab('users');

$editoroptions = [
    'maxfiles' => 0,
    'context' => $syscontext,
    'trusttext' => trusttext_trusted($syscontext),
];

$attachmentoptions = [
    'subdirs' => 0,
    'maxfiles' => 5,
    'maxbytes' => get_max_upload_file_size(),
    'accepted_types' => \local_queuedbulkmessage\form\message_form::ALLOWED_ATTACHMENT_TYPES,
    'return_types' => FILE_INTERNAL,
];

$draftitemid = file_get_unused_draft_itemid();
file_prepare_draft_area(
    $draftitemid,
    $syscontext->id,
    'local_queuedbulkmessage',
    'attachment',
    0,
    $attachmentoptions
);

$form = new \local_queuedbulkmessage\form\message_form(null, [
    'editoroptions' => $editoroptions,
    'attachmentoptions' => $attachmentoptions,
]);
$form->set_data([
    'returnurl' => $returnurl,
    'messagebody' => [
        'text' => '',
        'format' => FORMAT_HTML,
    ],
    'attachments' => $draftitemid,
]);

if ($form->is_cancelled()) {
    redirect($return);
}

if ($data = $form->get_data()) {
    require_sesskey();

    $userids = array_values(array_unique(array_map('intval', $SESSION->bulk_users)));
    $userids = array_values(array_filter($userids, static fn(int $userid): bool => $userid > 0));

    if (empty($userids)) {
        redirect(
            $return,
            get_string('noselectedusers', 'local_queuedbulkmessage'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
    $validuserids = $DB->get_fieldset_select('user', 'id', "id {$insql}", $params);
    $validuserids = array_values(array_map('intval', $validuserids));

    if (empty($validuserids)) {
        redirect(
            $return,
            get_string('noselectedusers', 'local_queuedbulkmessage'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $batchsize = (int) get_config('local_queuedbulkmessage', 'batchsize');
    if ($batchsize < 1 || $batchsize > 1000) {
        $batchsize = 100;
    }

    $formatoptions = new stdClass();
    $formatoptions->para = false;
    $formatoptions->newlines = true;
    $formatoptions->trusted = trusttext_trusted($syscontext);

    $message = format_text($data->messagebody['text'], $data->messagebody['format'], $formatoptions);
    $chunks = array_chunk($validuserids, $batchsize);
    $sendmode = $data->sendmode ?? 'notification';
    if (!in_array($sendmode, ['notification', 'private'], true)) {
        $sendmode = 'notification';
    }

    $attachments = [];
    if (!empty($data->attachments)) {
        $itemid = random_int(1, 999999999);
        file_save_draft_area_files(
            $data->attachments,
            $syscontext->id,
            'local_queuedbulkmessage',
            'attachment',
            $itemid,
            $attachmentoptions
        );

        $storedfiles = get_file_storage()->get_area_files(
            $syscontext->id,
            'local_queuedbulkmessage',
            'attachment',
            $itemid,
            'filename',
            false
        );

        foreach ($storedfiles as $storedfile) {
            $attachments[] = [
                'name' => $storedfile->get_filename(),
                'itemid' => $itemid,
            ];
        }

        if ($attachments) {
            $attachmentrecord = (object) [
                'itemid' => $itemid,
                'senderid' => $USER->id,
                'timecreated' => time(),
            ];
            $attachmentid = $DB->insert_record('local_queuedbulkmessage_att', $attachmentrecord);

            foreach ($validuserids as $userid) {
                $DB->insert_record('local_queuedbulkmessage_rec', (object) [
                    'attachmentid' => $attachmentid,
                    'userid' => $userid,
                ]);
            }
        }
    }

    $queuedtasks = 0;
    foreach ($chunks as $chunk) {
        $task = new \local_queuedbulkmessage\task\send_bulk_message();
        $task->set_userid($USER->id);
        $task->set_custom_data([
            'userids' => $chunk,
            'subject' => $data->subject,
            'message' => $message,
            'messageformat' => FORMAT_HTML,
            'senderid' => $USER->id,
            'sendmode' => $sendmode,
            'attachments' => $attachments,
        ]);

        if (\core\task\manager::queue_adhoc_task($task)) {
            $queuedtasks++;
        }
    }

    $messageinfo = (object) [
        'tasks' => $queuedtasks,
        'users' => count($validuserids),
    ];
    redirect(
        $return,
        get_string('messagesqueued', 'local_queuedbulkmessage', $messageinfo),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('queuebulkmessage', 'local_queuedbulkmessage'));
$form->display();
echo $OUTPUT->footer();
