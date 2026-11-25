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
 * Central events screen.
 *
 * Site administrators manage every event here. Other users see the events
 * they can register for.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_eventbooking\manager;

require_login(null, false);

$context = context_system::instance();
require_capability('local/eventbooking:view', $context);
$canmanage = has_capability('local/eventbooking:manage', $context);

$search = trim(optional_param('q', '', PARAM_TEXT));
$page = optional_param('page', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$base = new moodle_url('/local/eventbooking/index.php');
if ($search !== '') {
    $base->param('q', $search);
}

$PAGE->set_context($context);
$PAGE->set_url($base);
$PAGE->set_pagelayout('standard');
$title = $canmanage
    ? get_string('manageevents', 'local_eventbooking')
    : get_string('pluginname', 'local_eventbooking');
$PAGE->set_title($title);
$PAGE->set_heading($title);

if ($canmanage && $delete) {
    require_sesskey();
    $event = manager::get_event($delete);
    if (!$event) {
        throw new moodle_exception('eventnotfound', 'local_eventbooking');
    }
    if ($confirm) {
        manager::delete_event($delete);
        redirect(new moodle_url('/local/eventbooking/index.php'), get_string('eventdeleted', 'local_eventbooking'));
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('deleteconfirm', 'local_eventbooking', $event->name),
        new moodle_url('/local/eventbooking/index.php', [
            'delete' => $event->id,
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]),
        new moodle_url('/local/eventbooking/index.php')
    );
    echo $OUTPUT->footer();
    exit;
}

$perpage = $canmanage ? 20 : 12;
$list = manager::list_events(!$canmanage, $search, $page, $perpage);
$renderer = $PAGE->get_renderer('local_eventbooking');

echo $OUTPUT->header();

if ($canmanage) {
    echo $renderer->stats(manager::stats());
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/eventbooking/edit.php'),
            get_string('newevent', 'local_eventbooking'),
            ['class' => 'btn btn-primary']
        ),
        'mb-3'
    );
}

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/eventbooking/index.php'), 'class' => 'form-inline mb-3']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'q',
    'value' => $search,
    'class' => 'form-control mr-2 mb-2',
    'placeholder' => get_string('searchevents', 'local_eventbooking'),
    'aria-label' => get_string('searchevents', 'local_eventbooking'),
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-secondary mb-2',
    'value' => get_string('search', 'local_eventbooking'),
]);
echo html_writer::end_tag('form');

if (!$list['events']) {
    echo $OUTPUT->notification(
        get_string('noevents', 'local_eventbooking') . ' ' . get_string('noevents_desc', 'local_eventbooking'),
        'info'
    );
} else if ($canmanage) {
    $table = new html_table();
    $table->head = [
        get_string('eventname', 'local_eventbooking'),
        get_string('dates', 'local_eventbooking'),
        get_string('registered', 'local_eventbooking'),
        get_string('status', 'local_eventbooking'),
        get_string('actions', 'local_eventbooking'),
    ];
    $table->attributes['class'] = 'generaltable';
    $table->data = [];
    foreach ($list['events'] as $event) {
        $actions = html_writer::div(
            html_writer::link(new moodle_url('/local/eventbooking/view.php', ['id' => $event['id']]),
                get_string('view', 'local_eventbooking'), ['class' => 'btn btn-sm btn-outline-secondary']) .
            html_writer::link(new moodle_url('/local/eventbooking/view.php', ['id' => $event['id'], 'preview' => 1]),
                get_string('userview', 'local_eventbooking'), ['class' => 'btn btn-sm btn-outline-secondary']) .
            html_writer::link(new moodle_url('/local/eventbooking/edit.php', ['id' => $event['id']]),
                get_string('edit', 'local_eventbooking'), ['class' => 'btn btn-sm btn-outline-secondary']) .
            html_writer::link(new moodle_url('/local/eventbooking/report.php', ['id' => $event['id']]),
                get_string('report', 'local_eventbooking'), ['class' => 'btn btn-sm btn-outline-secondary']) .
            html_writer::link(new moodle_url('/local/eventbooking/send.php', ['id' => $event['id']]),
                get_string('sendlink', 'local_eventbooking'), ['class' => 'btn btn-sm btn-outline-primary']) .
            html_writer::link(new moodle_url('/local/eventbooking/index.php', [
                'delete' => $event['id'],
                'sesskey' => sesskey(),
            ]), get_string('deleteevent', 'local_eventbooking'), ['class' => 'btn btn-sm btn-outline-danger']),
            'local-eventbooking-actions'
        );
        $name = html_writer::link(
            new moodle_url('/local/eventbooking/view.php', ['id' => $event['id']]),
            $event['name']
        );
        $name .= html_writer::div(
            get_string('sessioncount', 'local_eventbooking', $event['sessioncount']),
            'small text-muted'
        );
        $table->data[] = [
            $name,
            s($event['dates']),
            s($event['occupancy']),
            html_writer::span($event['badge'], 'badge ' . $event['badgeclass']),
            $actions,
        ];
    }
    echo html_writer::table($table);
} else {
    echo $renderer->catalogue($list['events']);
}

echo $OUTPUT->paging_bar($list['total'], $page, $perpage, $base);

$mine = manager::my_registrations($USER->id);
echo html_writer::tag('h3', get_string('myregistrations', 'local_eventbooking'), ['class' => 'mt-4']);
if (!$mine) {
    echo html_writer::div(get_string('nomyregistrations', 'local_eventbooking'), 'text-muted');
} else {
    $table = new html_table();
    $table->head = [
        get_string('eventname', 'local_eventbooking'),
        get_string('sessionname', 'local_eventbooking'),
        get_string('status', 'local_eventbooking'),
        get_string('registeredon', 'local_eventbooking'),
    ];
    $table->data = [];
    foreach ($mine as $registration) {
        $session = (object) [
            'name' => $registration->sessionname ?? '',
            'timestart' => $registration->timestart ?? 0,
            'timeend' => $registration->timeend ?? 0,
        ];
        $nametext = $registration->eventname;
        if ((int) $registration->visible || $canmanage) {
            $name = html_writer::link(
                new moodle_url('/local/eventbooking/view.php', ['id' => $registration->eventid]),
                $nametext
            );
        } else {
            $name = $nametext;
        }
        $status = (int) $registration->status === manager::STATUS_CONFIRMED
            ? get_string('confirmed', 'local_eventbooking')
            : get_string('cancelled', 'local_eventbooking');
        $table->data[] = [
            $name,
            s($registration->sessionname ? manager::session_label($session) : ''),
            s($status),
            userdate($registration->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
