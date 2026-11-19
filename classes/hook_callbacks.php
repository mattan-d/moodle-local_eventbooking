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
 * Navigation callbacks.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eventbooking;

/**
 * Hook callbacks for local_eventbooking.
 */
class hook_callbacks {
    /**
     * Add the events item to the primary navigation.
     *
     * @param \core\hook\navigation\primary_extend $hook
     * @return void
     */
    public static function extend_primary_navigation(\core\hook\navigation\primary_extend $hook): void {
        global $PAGE;

        if (!isloggedin() || isguestuser() || !get_capability_info('local/eventbooking:view')) {
            return;
        }
        $context = \context_system::instance();
        if (!has_capability('local/eventbooking:view', $context)) {
            return;
        }

        $primary = $hook->get_primaryview();
        $node = $primary->add(
            get_string('pluginname', 'local_eventbooking'),
            new \moodle_url('/local/eventbooking/index.php'),
            \navigation_node::TYPE_CUSTOM,
            null,
            'local_eventbooking',
            new \pix_icon('i/calendar', '')
        );

        $path = $PAGE->url->get_path();
        if (str_contains($path, '/local/eventbooking/')) {
            $node->make_active();
        }
    }
}
