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
 * English strings for Queued bulk message.
 *
 * @package    local_queuedbulkmessage
 * @copyright  2026 Sameh Naim <naim.a@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['attachments'] = 'Attachments';
$string['attachments_help'] = 'Upload up to five document or image files. The queued message will include links to these files.';
$string['attachmenttext'] = 'Attachment: {$a}';
$string['batchsize'] = 'Batch size';
$string['batchsize_desc'] = 'Number of selected users to place in each background task.';
$string['invalidattachmenttype'] = 'Attachments must be image or document files.';
$string['invalidbatchsize'] = 'The batch size must be between 1 and 1000.';
$string['invalidsendmode'] = 'Choose a valid send mode.';
$string['messagebody'] = 'Message';
$string['messageprovider:bulkmessage'] = 'Queued bulk messages';
$string['messagesqueued'] = '{$a->tasks} background task(s) queued for {$a->users} selected user(s).';
$string['messagesubject'] = 'Subject';
$string['noselectedusers'] = 'No users were selected.';
$string['pluginname'] = 'Queued bulk message';
$string['privacy:metadata:attachment'] = 'Stores attachment file-area ownership for queued bulk messages.';
$string['privacy:metadata:attachment:itemid'] = 'The Moodle file-area item ID for an attachment batch.';
$string['privacy:metadata:attachment:senderid'] = 'The user who uploaded and queued the attachment.';
$string['privacy:metadata:attachment:timecreated'] = 'The time the attachment batch was created.';
$string['privacy:metadata:core_files'] = 'The Queued bulk message plugin stores uploaded message attachments.';
$string['privacy:metadata:recipient'] = 'Stores users allowed to access queued bulk message attachments.';
$string['privacy:metadata:recipient:attachmentid'] = 'The attachment batch access record.';
$string['privacy:metadata:recipient:userid'] = 'The user allowed to access the attachment batch.';
$string['queuebulkmessage'] = 'Queue bulk message';
$string['sendmode'] = 'Send mode';
$string['sendmode_help'] = 'Notifications support HTML. Private messages are sent as plain text.';
$string['sendmode_notification'] = 'Notification';
$string['sendmode_private'] = 'Private user message';
$string['tasksendbulkmessage'] = 'Send queued bulk message';
$string['toomanyattachments'] = 'You can upload a maximum of five attachments.';
