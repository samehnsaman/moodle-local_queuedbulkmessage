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

namespace local_queuedbulkmessage\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for Queued bulk message.
 *
 * @package    local_queuedbulkmessage
 * @copyright  2026 Sameh Naim <naim.a@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata about stored attachment files.
     *
     * @param collection $collection Metadata collection.
     * @return collection Updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_queuedbulkmessage_att',
            [
                'itemid' => 'privacy:metadata:attachment:itemid',
                'senderid' => 'privacy:metadata:attachment:senderid',
                'timecreated' => 'privacy:metadata:attachment:timecreated',
            ],
            'privacy:metadata:attachment'
        );
        $collection->add_database_table(
            'local_queuedbulkmessage_rec',
            [
                'attachmentid' => 'privacy:metadata:recipient:attachmentid',
                'userid' => 'privacy:metadata:recipient:userid',
            ],
            'privacy:metadata:recipient'
        );
        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');
        return $collection;
    }

    /**
     * Gets contexts containing attachment files uploaded by a user.
     *
     * @param int $userid User ID.
     * @return contextlist Context list.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {files} f ON f.contextid = ctx.id
             LEFT JOIN {local_queuedbulkmessage_att} a ON a.itemid = f.itemid
             LEFT JOIN {local_queuedbulkmessage_rec} r ON r.attachmentid = a.id
                 WHERE f.component = :component
                   AND f.filearea = :filearea
                   AND (f.userid = :userid1 OR a.senderid = :userid2 OR r.userid = :userid3)";
        $contextlist->add_from_sql($sql, [
            'component' => 'local_queuedbulkmessage',
            'filearea' => 'attachment',
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Exports attachment files uploaded by the approved user.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->get_user()->id) || empty($contextlist->get_contextids())) {
            return;
        }

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT DISTINCT f.itemid, f.contextid
                  FROM {files} f
             LEFT JOIN {local_queuedbulkmessage_att} a ON a.itemid = f.itemid
             LEFT JOIN {local_queuedbulkmessage_rec} r ON r.attachmentid = a.id
                 WHERE f.contextid {$contextsql}
                   AND f.component = :component
                   AND f.filearea = :filearea
                   AND (f.userid = :userid1 OR a.senderid = :userid2 OR r.userid = :userid3)
                   AND f.filename <> :directory";
        $params = $contextparams + [
            'component' => 'local_queuedbulkmessage',
            'filearea' => 'attachment',
            'userid1' => $contextlist->get_user()->id,
            'userid2' => $contextlist->get_user()->id,
            'userid3' => $contextlist->get_user()->id,
            'directory' => '.',
        ];

        foreach ($DB->get_records_sql($sql, $params) as $record) {
            $context = \context::instance_by_id($record->contextid);
            writer::with_context($context)->export_area_files(
                [get_string('pluginname', 'local_queuedbulkmessage')],
                'local_queuedbulkmessage',
                'attachment',
                $record->itemid
            );
        }
    }

    /**
     * Deletes all attachment files in a context.
     *
     * @param \context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        global $DB;

        $DB->delete_records('local_queuedbulkmessage_rec');
        $DB->delete_records('local_queuedbulkmessage_att');
        get_file_storage()->delete_area_files($context->id, 'local_queuedbulkmessage', 'attachment');
    }

    /**
     * Deletes attachment files uploaded by the approved user.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // Attachments are part of messages already sent to multiple users. Individual deletion is intentionally
        // handled by Moodle core message retention/deletion rather than breaking recipient attachment links here.
    }
}
