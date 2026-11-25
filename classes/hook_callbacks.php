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
     * Add the events item from the Moodle 4.4 navigation hook.
     *
     * @param \core\hook\navigation\primary_extend $hook
     * @return void
     */
    public static function extend_primary_navigation($hook): void {
        self::add_primary_node($hook->get_primaryview());
    }

    /**
     * Add the events item on Moodle releases without the navigation hook.
     *
     * @return void
     */
    public static function extend_primary_navigation_legacy(): void {
        global $PAGE;

        if (class_exists('\core\hook\navigation\primary_extend')) {
            return;
        }
        self::add_primary_node($PAGE->primarynav);
    }

    /**
     * @param \navigation_node $primary
     * @return void
     */
    protected static function add_primary_node(\navigation_node $primary): void {
        global $PAGE;

        if (!isloggedin() || isguestuser() || !get_capability_info('local/eventbooking:view')) {
            return;
        }
        $context = \context_system::instance();
        if (!has_capability('local/eventbooking:view', $context)) {
            return;
        }
        if ($primary->find('local_eventbooking', \navigation_node::TYPE_CUSTOM)) {
            return;
        }

        $node = $primary->add(
            get_string('pluginname', 'local_eventbooking'),
            new \moodle_url('/local/eventbooking/index.php'),
            \navigation_node::TYPE_CUSTOM,
            null,
            'local_eventbooking',
            new \pix_icon('i/calendar', '')
        );

        $path = $PAGE->url->get_path();
        if (strpos($path, '/local/eventbooking/') !== false) {
            $node->make_active();
        }
    }
}
