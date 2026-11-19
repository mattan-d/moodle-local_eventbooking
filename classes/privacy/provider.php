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
 * Privacy provider.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eventbooking\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Stores registration details and sent registration links.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_eventbooking_reg', [
            'userid' => 'privacy:metadata:local_eventbooking_reg:userid',
            'firstname' => 'privacy:metadata:local_eventbooking_reg:firstname',
            'lastname' => 'privacy:metadata:local_eventbooking_reg:lastname',
            'email' => 'privacy:metadata:local_eventbooking_reg:email',
            'phone' => 'privacy:metadata:local_eventbooking_reg:phone',
            'idnumber' => 'privacy:metadata:local_eventbooking_reg:idnumber',
            'institution' => 'privacy:metadata:local_eventbooking_reg:institution',
            'department' => 'privacy:metadata:local_eventbooking_reg:department',
            'city' => 'privacy:metadata:local_eventbooking_reg:city',
            'jobtitle' => 'privacy:metadata:local_eventbooking_reg:jobtitle',
            'notes' => 'privacy:metadata:local_eventbooking_reg:notes',
            'status' => 'privacy:metadata:local_eventbooking_reg:status',
            'timecreated' => 'privacy:metadata:local_eventbooking_reg:timecreated',
        ], 'privacy:metadata:local_eventbooking_reg');

        $collection->add_database_table('local_eventbooking_sent', [
            'userid' => 'privacy:metadata:local_eventbooking_sent:userid',
            'email' => 'privacy:metadata:local_eventbooking_sent:email',
            'sentby' => 'privacy:metadata:local_eventbooking_sent:sentby',
            'message' => 'privacy:metadata:local_eventbooking_sent:message',
            'timecreated' => 'privacy:metadata:local_eventbooking_sent:timecreated',
        ], 'privacy:metadata:local_eventbooking_sent');

        return $collection;
    }

    /**
     * Contexts that contain data for this user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :level
                   AND (
                        EXISTS (SELECT 1 FROM {local_eventbooking_reg} r WHERE r.userid = :userid)
                     OR EXISTS (SELECT 1 FROM {local_eventbooking_sent} s WHERE s.userid = :recipient OR s.sentby = :sender)
                   )";
        $contextlist->add_from_sql($sql, [
            'level' => \CONTEXT_SYSTEM,
            'userid' => $userid,
            'recipient' => $userid,
            'sender' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Export registrations and link messages.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != \CONTEXT_SYSTEM) {
                continue;
            }

            $registrations = [];
            $records = $DB->get_records('local_eventbooking_reg', ['userid' => $userid], 'timecreated ASC');
            foreach ($records as $record) {
                $registrations[] = [
                    'eventid' => $record->eventid,
                    'sessionid' => $record->sessionid,
                    'firstname' => $record->firstname,
                    'lastname' => $record->lastname,
                    'email' => $record->email,
                    'phone' => $record->phone,
                    'idnumber' => $record->idnumber,
                    'institution' => $record->institution,
                    'department' => $record->department,
                    'city' => $record->city,
                    'jobtitle' => $record->jobtitle,
                    'notes' => $record->notes,
                    'status' => $record->status,
                    'timecreated' => transform::datetime($record->timecreated),
                    'timemodified' => transform::datetime($record->timemodified),
                ];
            }

            $messages = [];
            $sent = $DB->get_records_select(
                'local_eventbooking_sent',
                'userid = :recipient OR sentby = :sender',
                ['recipient' => $userid, 'sender' => $userid],
                'timecreated ASC'
            );
            foreach ($sent as $record) {
                $messages[] = [
                    'eventid' => $record->eventid,
                    'email' => $record->email,
                    'message' => $record->message,
                    'timecreated' => transform::datetime($record->timecreated),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_eventbooking')],
                (object) [
                    'registrations' => $registrations,
                    'messages' => $messages,
                ]
            );
        }
    }

    /**
     * Delete one user's data in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == \CONTEXT_SYSTEM) {
                self::delete_user($userid);
            }
        }
    }

    /**
     * Delete all plugin user data stored in this context.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel != \CONTEXT_SYSTEM) {
            return;
        }
        $DB->delete_records('local_eventbooking_reg');
        $DB->delete_records('local_eventbooking_sent');
    }

    /**
     * Users who have data in the context.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != \CONTEXT_SYSTEM) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_eventbooking_reg}', []);
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_eventbooking_sent}', []);
        $userlist->add_from_sql('sentby', 'SELECT sentby AS sentby FROM {local_eventbooking_sent}', []);
    }

    /**
     * Delete data for the listed users.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        if ($userlist->get_context()->contextlevel != \CONTEXT_SYSTEM) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            self::delete_user($userid);
        }
    }

    /**
     * Remove rows that belong to one user.
     *
     * @param int $userid
     * @return void
     */
    protected static function delete_user(int $userid): void {
        global $DB;
        $DB->delete_records('local_eventbooking_reg', ['userid' => $userid]);
        $DB->delete_records_select(
            'local_eventbooking_sent',
            'userid = :recipient OR sentby = :sender',
            ['recipient' => $userid, 'sender' => $userid]
        );
    }
}
