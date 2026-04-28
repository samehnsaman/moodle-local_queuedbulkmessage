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
 * Upgrade steps for Queued bulk message.
 *
 * @package    local_queuedbulkmessage
 * @copyright  2026 Sameh Naim <naim.a@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Performs plugin upgrade steps.
 *
 * @param int $oldversion Installed plugin version.
 * @return bool
 */
function xmldb_local_queuedbulkmessage_upgrade(int $oldversion): bool {
    global $CFG, $DB;

    require_once($CFG->libdir . '/messagelib.php');

    if ($oldversion < 2026042801) {
        message_update_providers('local_queuedbulkmessage');
        upgrade_plugin_savepoint(true, 2026042801, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042802) {
        upgrade_plugin_savepoint(true, 2026042802, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042803) {
        upgrade_plugin_savepoint(true, 2026042803, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042804) {
        upgrade_plugin_savepoint(true, 2026042804, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042805) {
        upgrade_plugin_savepoint(true, 2026042805, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042806) {
        upgrade_plugin_savepoint(true, 2026042806, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042807) {
        upgrade_plugin_savepoint(true, 2026042807, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042808) {
        upgrade_plugin_savepoint(true, 2026042808, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042809) {
        $dbman = $DB->get_manager();

        $table = new xmldb_table('local_queuedbulkmessage_att');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('senderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('sender', XMLDB_KEY_FOREIGN, ['senderid'], 'user', ['id']);
        $table->add_index('itemid', XMLDB_INDEX_UNIQUE, ['itemid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_queuedbulkmessage_rec');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('attachmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key(
            'attachment',
            XMLDB_KEY_FOREIGN,
            ['attachmentid'],
            'local_queuedbulkmessage_att',
            ['id']
        );
        $table->add_key('user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('attachmentuser', XMLDB_INDEX_UNIQUE, ['attachmentid', 'userid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026042809, 'local', 'queuedbulkmessage');
    }

    if ($oldversion < 2026042810) {
        upgrade_plugin_savepoint(true, 2026042810, 'local', 'queuedbulkmessage');
    }

    return true;
}
