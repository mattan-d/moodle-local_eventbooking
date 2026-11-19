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
 * Create or edit an event. Site administrators only.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_eventbooking\form\event_form;
use local_eventbooking\manager;

require_login(null, false);

$context = context_system::instance();
require_capability('local/eventbooking:manage', $context);

$id = optional_param('id', 0, PARAM_INT);
$event = $id ? manager::require_event($id) : manager::blank_event();
$sessions = $id ? manager::get_sessions($id) : [];

$url = new moodle_url('/local/eventbooking/edit.php', $id ? ['id' => $id] : []);
$title = $id
    ? get_string('editevent', 'local_eventbooking')
    : get_string('newevent', 'local_eventbooking');

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($id ? format_string($event->name) : $title);
$PAGE->navbar->add(get_string('manageevents', 'local_eventbooking'), new moodle_url('/local/eventbooking/index.php'));
$PAGE->navbar->add($title);

$form = new event_form($url, [
    'repeatcount' => max(1, count($sessions)),
    'sessions' => $sessions,
]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/eventbooking/index.php'));
}
$form->set_data(manager::prepare_for_form($event, $sessions));
if ($data = $form->get_data()) {
    $savedid = manager::save_event($data);
    redirect(
        new moodle_url('/local/eventbooking/view.php', ['id' => $savedid]),
        get_string('eventsaved', 'local_eventbooking')
    );
}

echo $OUTPUT->header();
if ($id) {
    echo $PAGE->get_renderer('local_eventbooking')->event_tabs($id, 'edit');
}
$form->display();
echo $OUTPUT->footer();
