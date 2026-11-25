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
 * Event page and registration.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_eventbooking\form\register_form;
use local_eventbooking\manager;

require_login(null, false);

$context = context_system::instance();
require_capability('local/eventbooking:view', $context);

$id = required_param('id', PARAM_INT);
$event = manager::require_event($id);
$canmanage = has_capability('local/eventbooking:manage', $context);
$preview = $canmanage && optional_param('preview', 0, PARAM_BOOL);
if (!(int) $event->visible && !$canmanage) {
    throw new moodle_exception('eventnotavailable', 'local_eventbooking');
}

$url = new moodle_url('/local/eventbooking/view.php', ['id' => $id]);
if ($preview) {
    $url->param('preview', 1);
}
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('embedded');
$PAGE->add_body_class('local-eventbooking-view');
if (isset($PAGE->activityheader) && method_exists($PAGE->activityheader, 'disable')) {
    $PAGE->activityheader->disable();
}
$PAGE->set_title(format_string($event->name));
$PAGE->set_heading('');

$sessions = manager::get_sessions($id);
$current = manager::get_active_registration($id, (int) $USER->id);
$canregister = has_capability('local/eventbooking:register', $context);

$action = optional_param('action', '', PARAM_ALPHA);
if (!$preview && $action === 'cancel' && confirm_sesskey() && data_submitted()) {
    if (manager::cancel($id, (int) $USER->id)) {
        redirect($url, get_string('registrationcancelled', 'local_eventbooking'));
    }
    redirect($url, get_string('cancelnotallowed', 'local_eventbooking'), null, \core\output\notification::NOTIFY_ERROR);
}

// Preview shows a participant who has not registered, not the manager's own booking.
if ($preview) {
    $current = null;
}

$selectable = false;
foreach ($sessions as $session) {
    $iscurrent = $current && (int) $current->sessionid === (int) $session->id;
    $open = manager::session_is_open($event, $session);
    $left = manager::seats_left($event, $session, $current ? (int) $USER->id : 0);
    if ($iscurrent || ($open && ($left === null || $left > 0))) {
        $selectable = true;
        break;
    }
}
$visibletouser = $preview || (int) $event->visible;
$showform = $canregister && $visibletouser && (int) $event->registrationopen && $sessions && $selectable;

$form = null;
if ($showform) {
    $form = new register_form($url, [
        'event' => $event,
        'sessions' => $sessions,
        'current' => $current,
        'userid' => $USER->id,
    ]);
    $defaults = (object) [
        'firstname' => $USER->firstname,
        'lastname' => $USER->lastname,
        'email' => $USER->email,
        'phone' => $current->phone ?? ($USER->phone1 ?? ''),
        'idnumber' => $USER->idnumber ?? '',
        'sessionid' => $current->sessionid ?? 0,
    ];
    if (empty($defaults->sessionid)) {
        foreach ($sessions as $session) {
            $open = manager::session_is_open($event, $session);
            $left = manager::seats_left($event, $session, 0);
            if ($open && ($left === null || $left > 0)) {
                $defaults->sessionid = $session->id;
                break;
            }
        }
    }
    $form->set_data($defaults);
    if ($data = $form->get_data()) {
        if ($preview) {
            redirect($url, get_string('previewnosave', 'local_eventbooking'), null, \core\output\notification::NOTIFY_INFO);
        }
        $result = manager::register($id, (int) $data->sessionid, (array) $data);
        if ($result['ok']) {
            $message = $result['updated']
                ? get_string('registrationupdated', 'local_eventbooking')
                : get_string('registrationsuccess', 'local_eventbooking');
            redirect($url, $message);
        }
        redirect($url, $result['message'], null, \core\output\notification::NOTIFY_ERROR);
    }
}

$renderer = $PAGE->get_renderer('local_eventbooking');
echo $OUTPUT->header();

if ($canmanage && !$preview) {
    echo html_writer::start_div('local-eventbooking-page local-eventbooking-chrome');
    echo $renderer->event_tabs($id, 'view');
    echo $renderer->link_box((new moodle_url('/local/eventbooking/view.php', ['id' => $id]))->out(false));
    if (!(int) $event->visible) {
        echo $OUTPUT->notification(get_string('notvisiblewarning', 'local_eventbooking'), 'warning');
    } else if (!(int) $event->registrationopen) {
        echo $OUTPUT->notification(get_string('registrationnotopenwarning', 'local_eventbooking'), 'warning');
    }
    echo html_writer::end_div();
}

if ($preview) {
    $previewnote = get_string('userview', 'local_eventbooking');
    if (!(int) $event->visible) {
        $previewnote .= ' ' . get_string('previewhidden', 'local_eventbooking');
    }
    echo html_writer::div(
        html_writer::span($previewnote, 'local-eventbooking-previewnote') .
        html_writer::link(
            new moodle_url('/local/eventbooking/view.php', ['id' => $id]),
            get_string('previewback', 'local_eventbooking'),
            ['class' => 'btn btn-sm btn-outline-primary']
        ),
        'local-eventbooking-page local-eventbooking-chrome local-eventbooking-previewbar'
    );
}

echo $renderer->hero($event);

echo html_writer::start_div('local-eventbooking-page');

if ($current) {
    $currentsession = null;
    foreach ($sessions as $session) {
        if ((int) $session->id === (int) $current->sessionid) {
            $currentsession = $session;
            break;
        }
    }
    $label = $currentsession ? manager::session_label($currentsession) : '';
    echo $OUTPUT->notification(get_string('alreadyregistered', 'local_eventbooking', $label), 'success');
    if (trim($event->confirmation ?? '') !== '') {
        echo html_writer::div(nl2br(s($event->confirmation)), 'alert alert-info');
    }
    if ($showform) {
        echo html_writer::div(get_string('changehint', 'local_eventbooking'), 'local-eventbooking-changehint text-muted mb-3');
    }
}

if ($showform) {
    echo html_writer::start_div('local-eventbooking-sessions card mb-3');
    echo html_writer::start_div('card-body');
    $form->display();
    echo html_writer::end_div();
    echo html_writer::end_div();
} else if ($sessions) {
    if ($visibletouser && !$current && !(int) $event->registrationopen) {
        echo $OUTPUT->notification(get_string('registrationclosed', 'local_eventbooking'), 'info');
    } else if ($visibletouser && !$current && !$selectable) {
        echo $OUTPUT->notification(get_string('eventfull', 'local_eventbooking'), 'warning');
    }
    echo html_writer::start_div('card local-eventbooking-sessionlist mb-3');
    echo html_writer::start_div('list-group list-group-flush');
    foreach ($sessions as $session) {
        $left = manager::seats_left($event, $session);
        $open = manager::session_is_open($event, $session);
        echo html_writer::div(
            manager::session_choice_html($session, $left, $open),
            'list-group-item'
        );
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

if ($current && (int) $event->allowcancel) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url, 'class' => 'local-eventbooking-cancel']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'cancel']);
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-outline-danger',
        'value' => get_string('cancelregistration', 'local_eventbooking'),
    ]);
    echo html_writer::end_tag('form');
}

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/eventbooking/index.php'),
        get_string('backtoevents', 'local_eventbooking'),
        ['class' => 'btn btn-link px-0']
    ),
    'local-eventbooking-back'
);

echo html_writer::end_div();
echo $OUTPUT->footer();
