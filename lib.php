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
 * Library callbacks for Queued bulk message.
 *
 * @package    local_queuedbulkmessage
 * @copyright  2026 Sameh Naim <naim.a@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serves queued bulk message attachment files.
 *
 * @param stdClass $course Course object.
 * @param cm_info|null $cm Course module, unused for local plugins.
 * @param context $context File context.
 * @param string $filearea File area.
 * @param array $args File path arguments.
 * @param bool $forcedownload Whether the file should be downloaded.
 * @param array $options Additional file serving options.
 * @return bool
 */
function local_queuedbulkmessage_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
): bool {
    global $DB, $USER;

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'attachment') {
        return false;
    }

    require_login();

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';

    $attachment = $DB->get_record('local_queuedbulkmessage_att', ['itemid' => $itemid]);
    if (!$attachment) {
        return false;
    }

    $canaccess = has_capability('moodle/site:config', $context) || (int) $attachment->senderid === (int) $USER->id;
    if (!$canaccess) {
        $canaccess = $DB->record_exists('local_queuedbulkmessage_rec', [
            'attachmentid' => $attachment->id,
            'userid' => $USER->id,
        ]);
    }

    if (!$canaccess) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_queuedbulkmessage', 'attachment', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
