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

namespace local_queuedbulkmessage\task;

/**
 * Adhoc task that sends one queued bulk message chunk.
 *
 * @package    local_queuedbulkmessage
 * @copyright  2026 Sameh Naim <naim.a@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_bulk_message extends \core\task\adhoc_task {

    /**
     * Returns the task display name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('tasksendbulkmessage', 'local_queuedbulkmessage');
    }

    /**
     * Sends the queued messages.
     */
    public function execute(): void {
        global $CFG;

        require_once($CFG->libdir . '/messagelib.php');

        $data = $this->get_custom_data();

        if (empty($data->userids) || empty($data->senderid)) {
            mtrace('Queued bulk message task has no recipients or sender.');
            return;
        }

        try {
            $sender = \core_user::get_user($data->senderid, '*', MUST_EXIST);
        } catch (\Throwable $e) {
            mtrace('Queued bulk message task failed because the sender could not be loaded: ' . $e->getMessage());
            return;
        }

        foreach ($data->userids as $userid) {
            $userid = (int) $userid;

            try {
                $user = \core_user::get_user(
                    $userid,
                    'id, deleted, suspended, email, firstname, lastname',
                    MUST_EXIST
                );

                if (!empty($user->deleted) || !empty($user->suspended)) {
                    mtrace("Skipping deleted/suspended user {$userid}");
                    continue;
                }

                $sendmode = $data->sendmode ?? 'notification';
                $messagehtml = $data->message;
                $attachments = $data->attachments ?? [];
                $attachmentlines = [];

                foreach ($attachments as $attachment) {
                    if (empty($attachment->itemid) || empty($attachment->name)) {
                        continue;
                    }
                    $isimage = preg_match('/\.(gif|jpe?g|png|svg|webp)$/i', $attachment->name);
                    $attachmenturl = \moodle_url::make_pluginfile_url(
                        \context_system::instance()->id,
                        'local_queuedbulkmessage',
                        'attachment',
                        $attachment->itemid,
                        '/',
                        $attachment->name,
                        !$isimage,
                        $user->id
                    )->out(false);
                    $attachmenttext = get_string(
                        'attachmenttext',
                        'local_queuedbulkmessage',
                        $attachment->name
                    );
                    $attachmentlink = \html_writer::link($attachmenturl, $attachmenttext);
                    $messagehtml .= \html_writer::tag('p', $attachmentlink);
                    $attachmentlines[] = $isimage ? $attachmenturl : $attachmenttext . "\n" . $attachmenturl;
                }

                $plainmessage = html_to_text($data->message, 0, false);
                if ($attachmentlines) {
                    $plainmessage .= "\n\n" . implode("\n\n", $attachmentlines);
                }
                if ($sendmode === 'private') {
                    $messageid = message_post_message($sender, $user, $plainmessage, FORMAT_MOODLE);
                    if ($messageid === false) {
                        mtrace("Queued bulk message was not accepted by Moodle for user {$userid}");
                        continue;
                    }

                    mtrace("Queued bulk message accepted by Moodle for user {$userid}");
                    continue;
                }

                $message = new \core\message\message();
                $message->component = 'local_queuedbulkmessage';
                $message->name = 'bulkmessage';
                $message->userfrom = $sender;
                $message->userto = $user;
                $message->subject = $data->subject;
                $message->courseid = SITEID;
                $message->fullmessage = $plainmessage;
                $message->fullmessageformat = $data->messageformat ?? FORMAT_HTML;
                $message->fullmessagehtml = $messagehtml;
                $message->smallmessage = html_to_text($data->message, 0, false);
                $message->customdata = null;
                $message->notification = 1;

                $messageid = message_send($message);
                if ($messageid === false) {
                    mtrace("Queued bulk message was not accepted by Moodle for user {$userid}");
                    continue;
                }

                mtrace("Queued bulk message accepted by Moodle for user {$userid}");
            } catch (\Throwable $e) {
                mtrace("Failed sending queued bulk message to user {$userid}: " . $e->getMessage());
                continue;
            }
        }
    }
}
