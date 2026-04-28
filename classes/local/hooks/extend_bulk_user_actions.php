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

namespace local_queuedbulkmessage\local\hooks;

/**
 * Adds the queued bulk message action to Moodle's bulk user action menu.
 *
 * @package    local_queuedbulkmessage
 * @copyright  2026 Sameh Naim <naim.a@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extend_bulk_user_actions {

    /**
     * Adds a queueing action for users who can configure the site.
     *
     * @param \core_user\hook\extend_bulk_user_actions $hook Bulk user action hook.
     */
    public static function callback(\core_user\hook\extend_bulk_user_actions $hook): void {
        if (!has_capability('moodle/site:config', \context_system::instance())) {
            return;
        }

        $hook->add_action(
            'local_queuedbulkmessage_queue',
            new \action_link(
                new \moodle_url('/local/queuedbulkmessage/queue.php'),
                get_string('queuebulkmessage', 'local_queuedbulkmessage')
            )
        );
    }
}
