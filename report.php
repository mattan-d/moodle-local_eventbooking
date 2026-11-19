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
 * Participant report with personal and organisational details.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_eventbooking\manager;

require_login(null, false);

$context = context_system::instance();
require_capability('local/eventbooking:manage', $context);

$id = required_param('id', PARAM_INT);
$event = manager::require_event($id);
$sessionid = optional_param('sessionid', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);
if (!in_array($status, ['', 'confirmed', 'cancelled'], true)) {
    $status = '';
}
$search = trim(optional_param('q', '', PARAM_TEXT));
$dataformat = optional_param('dataformat', '', PARAM_ALPHA);

if ($dataformat !== '') {
    $report = manager::report($id, $sessionid, $status, $search);
    $keys = array_keys($report['columns']);
    $rows = [];
    foreach ($report['rows'] as $row) {
        $ordered = [];
        foreach ($keys as $key) {
            $ordered[$key] = $row[$key] ?? '';
        }
        $rows[] = $ordered;
    }
    \core\dataformat::download_data(
        clean_filename('event-registrations-' . $id),
        $dataformat,
        $report['columns'],
        $rows
    );
    exit;
}

$url = new moodle_url('/local/eventbooking/report.php', ['id' => $id]);
if ($sessionid) {
    $url->param('sessionid', $sessionid);
}
if ($status !== '') {
    $url->param('status', $status);
}
if ($search !== '') {
    $url->param('q', $search);
}

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$title = get_string('report', 'local_eventbooking');
$PAGE->set_title($title . ': ' . format_string($event->name));
$PAGE->set_heading(format_string($event->name));
$PAGE->navbar->add(get_string('manageevents', 'local_eventbooking'), new moodle_url('/local/eventbooking/index.php'));
$PAGE->navbar->add(format_string($event->name), new moodle_url('/local/eventbooking/view.php', ['id' => $id]));
$PAGE->navbar->add($title);

$sessions = manager::get_sessions($id);
$report = manager::report($id, $sessionid, $status, $search);

echo $OUTPUT->header();
echo $PAGE->get_renderer('local_eventbooking')->event_tabs($id, 'report');
echo html_writer::tag('h3', $title);
echo html_writer::div(get_string('reportsummary', 'local_eventbooking', (object) [
    'confirmed' => $report['confirmed'],
    'cancelled' => $report['cancelled'],
]), 'mb-3');

echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/eventbooking/report.php'), 'class' => 'mb-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::start_div('form-row align-items-end');

echo html_writer::start_div('col-md-3 mb-2');
echo html_writer::tag('label', get_string('sessionname', 'local_eventbooking'), ['for' => 'sessionid', 'class' => 'd-block']);
echo html_writer::start_tag('select', ['name' => 'sessionid', 'id' => 'sessionid', 'class' => 'custom-select']);
$allattrs = ['value' => 0];
if (!$sessionid) {
    $allattrs['selected'] = 'selected';
}
echo html_writer::tag('option', get_string('allsessions', 'local_eventbooking'), $allattrs);
foreach ($sessions as $session) {
    $attrs = ['value' => $session->id];
    if ((int) $session->id === $sessionid) {
        $attrs['selected'] = 'selected';
    }
    echo html_writer::tag('option', manager::session_label($session), $attrs);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('col-md-2 mb-2');
echo html_writer::tag('label', get_string('status', 'local_eventbooking'), ['for' => 'status', 'class' => 'd-block']);
echo html_writer::start_tag('select', ['name' => 'status', 'id' => 'status', 'class' => 'custom-select']);
foreach (['' => 'allstatuses', 'confirmed' => 'confirmed', 'cancelled' => 'cancelled'] as $value => $stringid) {
    $attrs = ['value' => $value];
    if ($value === $status) {
        $attrs['selected'] = 'selected';
    }
    echo html_writer::tag('option', get_string($stringid, 'local_eventbooking'), $attrs);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('col-md-4 mb-2');
echo html_writer::tag('label', get_string('search', 'local_eventbooking'), ['for' => 'q', 'class' => 'd-block']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'q',
    'id' => 'q',
    'value' => $search,
    'class' => 'form-control',
]);
echo html_writer::end_div();

echo html_writer::start_div('col-md-3 mb-2');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-secondary mr-2',
    'value' => get_string('filter', 'local_eventbooking'),
]);
echo html_writer::link(new moodle_url('/local/eventbooking/report.php', ['id' => $id]), get_string('reset', 'local_eventbooking'), [
    'class' => 'btn btn-link',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo $OUTPUT->download_dataformat_selector(
    get_string('downloadreport', 'local_eventbooking'),
    new moodle_url('/local/eventbooking/report.php'),
    'dataformat',
    ['id' => $id, 'sessionid' => $sessionid, 'status' => $status, 'q' => $search]
);

if (!$report['rows']) {
    echo $OUTPUT->notification(get_string('noregistrations', 'local_eventbooking'), 'info');
} else {
    $table = new html_table();
    $table->head = array_values($report['columns']);
    $table->attributes['class'] = 'generaltable';
    $table->data = [];
    foreach ($report['rows'] as $row) {
        $cells = [];
        foreach (array_keys($report['columns']) as $key) {
            $cells[] = s($row[$key] ?? '');
        }
        $table->data[] = $cells;
    }
    echo html_writer::div(html_writer::table($table), 'local-eventbooking-report mt-3');
}

echo $OUTPUT->footer();
