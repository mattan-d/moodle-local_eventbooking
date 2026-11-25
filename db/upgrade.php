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
 * Upgrade steps for local_eventbooking.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_eventbooking_upgrade($oldversion) {
    if ($oldversion < 2026112500) {
        upgrade_plugin_savepoint(true, 2026112500, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112501) {
        upgrade_plugin_savepoint(true, 2026112501, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112502) {
        upgrade_plugin_savepoint(true, 2026112502, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112503) {
        upgrade_plugin_savepoint(true, 2026112503, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112504) {
        upgrade_plugin_savepoint(true, 2026112504, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112505) {
        upgrade_plugin_savepoint(true, 2026112505, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112506) {
        upgrade_plugin_savepoint(true, 2026112506, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112507) {
        upgrade_plugin_savepoint(true, 2026112507, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112508) {
        upgrade_plugin_savepoint(true, 2026112508, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112509) {
        upgrade_plugin_savepoint(true, 2026112509, 'local', 'eventbooking');
    }
    if ($oldversion < 2026112510) {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_eventbooking_reg');

        $field = new xmldb_field('outlookeventid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('outlookhash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('outlookchecked', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026112510, 'local', 'eventbooking');
    }
    return true;
}
