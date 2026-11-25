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
 * Site administration entry to the central events screen.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_eventbooking',
        get_string('manageevents', 'local_eventbooking'),
        new moodle_url('/local/eventbooking/index.php'),
        'local/eventbooking:manage'
    ));

    $settings = new admin_settingpage(
        'local_eventbooking_outlook',
        get_string('outlooksettings', 'local_eventbooking')
    );
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'local_eventbooking/outlookheading',
            get_string('outlookheading', 'local_eventbooking'),
            get_string('outlookheading_desc', 'local_eventbooking')
        ));
        $settings->add(new admin_setting_configcheckbox(
            'local_eventbooking/outlookenabled',
            get_string('outlookenabled', 'local_eventbooking'),
            get_string('outlookenabled_desc', 'local_eventbooking'),
            0
        ));
        $settings->add(new admin_setting_configtext(
            'local_eventbooking/outlooktenant',
            get_string('outlooktenant', 'local_eventbooking'),
            get_string('outlooktenant_desc', 'local_eventbooking'),
            '',
            PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configtext(
            'local_eventbooking/outlookclientid',
            get_string('outlookclientid', 'local_eventbooking'),
            get_string('outlookclientid_desc', 'local_eventbooking'),
            '',
            PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_eventbooking/outlooksecret',
            get_string('outlooksecret', 'local_eventbooking'),
            get_string('outlooksecret_desc', 'local_eventbooking'),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            'local_eventbooking/outlookorganizer',
            get_string('outlookorganizer', 'local_eventbooking'),
            get_string('outlookorganizer_desc', 'local_eventbooking'),
            '',
            PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configtext(
            'local_eventbooking/outlookorganizername',
            get_string('outlookorganizername', 'local_eventbooking'),
            get_string('outlookorganizername_desc', 'local_eventbooking'),
            '',
            PARAM_TEXT
        ));
    }
    $ADMIN->add('localplugins', $settings);
}
