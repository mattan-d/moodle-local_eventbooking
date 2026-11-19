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
 * Email the registration link for one event.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_eventbooking\form\send_form;
use local_eventbooking\manager;

require_login(null, false);

$context = context_system::instance();
require_capability('local/eventbooking:manage', $context);

$id = required_param('id', PARAM_INT);
$event = manager::require_event($id);
$url = new moodle_url('/local/eventbooking/send.php', ['id' => $id]);
$eventurl = manager::event_url($id);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$title = get_string('sendlink', 'local_eventbooking');
$PAGE->set_title($title . ': ' . format_string($event->name));
$PAGE->set_heading(format_string($event->name));
$PAGE->navbar->add(get_string('manageevents', 'local_eventbooking'), new moodle_url('/local/eventbooking/index.php'));
$PAGE->navbar->add(format_string($event->name), new moodle_url('/local/eventbooking/view.php', ['id' => $id]));
$PAGE->navbar->add($title);

$form = new send_form($url, ['eventid' => $id]);
if ($data = $form->get_data()) {
    $userids = $data->userids ?? [];
    if (!is_array($userids)) {
        $userids = [$userids];
    }
    $result = manager::send_links($event, $userids, $data->message ?? '');
    if ($result['skipped'] > 0) {
        $message = get_string('linksentpartial', 'local_eventbooking', (object) $result);
        $level = $result['sent'] ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_ERROR;
    } else {
        $message = get_string('linksent', 'local_eventbooking', $result['sent']);
        $level = \core\output\notification::NOTIFY_SUCCESS;
    }
    redirect($url, $message, null, $level);
}

$renderer = $PAGE->get_renderer('local_eventbooking');
echo $OUTPUT->header();
echo $renderer->event_tabs($id, 'send');
echo html_writer::tag('h3', get_string('sendlinkheading', 'local_eventbooking'));
echo $renderer->link_box($eventurl->out(false));
if (!(int) $event->visible) {
    echo $OUTPUT->notification(get_string('notvisiblewarning', 'local_eventbooking'), 'warning');
}
$form->display();

$sends = manager::recent_sends($id);
echo html_writer::tag('h3', get_string('recentsends', 'local_eventbooking'), ['class' => 'mt-4']);
if (!$sends) {
    echo html_writer::div(get_string('nosends', 'local_eventbooking'), 'text-muted');
} else {
    $userids = [];
    foreach ($sends as $send) {
        $userids[] = (int) $send->userid;
        $userids[] = (int) $send->sentby;
    }
    $users = $DB->get_records_list('user', 'id', array_unique(array_filter($userids)), '',
        'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename, email');
    $table = new html_table();
    $table->head = [
        get_string('sentto', 'local_eventbooking'),
        get_string('email'),
        get_string('sentby', 'local_eventbooking'),
        get_string('timesent', 'local_eventbooking'),
    ];
    $table->data = [];
    foreach ($sends as $send) {
        $recipient = $users[$send->userid] ?? null;
        $sender = $users[$send->sentby] ?? null;
        $table->data[] = [
            $recipient ? s(fullname($recipient)) : s($send->email),
            s($send->email),
            $sender ? s(fullname($sender)) : '',
            userdate($send->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
