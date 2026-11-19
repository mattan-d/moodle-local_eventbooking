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
 * Registration form.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eventbooking\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use local_eventbooking\manager;

/**
 * Choose a date and confirm personal and organisational details.
 */
class register_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $attributes = $mform->getAttributes();
        $attributes['class'] = trim(($attributes['class'] ?? 'mform') . ' full-width-labels');
        $mform->setAttributes($attributes);
        $event = $this->_customdata['event'];
        $sessions = $this->_customdata['sessions'];
        $current = $this->_customdata['current'];
        $userid = (int) $this->_customdata['userid'];

        $radios = [];
        foreach ($sessions as $session) {
            $iscurrent = $current && (int) $current->sessionid === (int) $session->id;
            $ignore = $iscurrent ? $userid : ($current ? $userid : 0);
            $open = manager::session_is_open($event, $session);
            $left = manager::seats_left($event, $session, $ignore);
            $label = manager::session_choice_html($session, $left, $open || $iscurrent);
            $disabled = !$iscurrent && (!$open || ($left !== null && $left <= 0));
            $radios[] = $mform->createElement(
                'radio',
                'sessionid',
                '',
                $label,
                $session->id,
                $disabled ? ['disabled' => 'disabled'] : []
            );
        }
        $mform->addElement('html', $this->section_heading(get_string('choosesession', 'local_eventbooking'), true));
        $mform->addGroup($radios, 'sessiongroup', '', '', false);

        $mform->addElement('html', $this->section_heading(get_string('personaldetails', 'local_eventbooking')));
        $mform->addElement('text', 'firstname', get_string('firstname'), ['size' => 40]);
        $mform->setType('firstname', \PARAM_TEXT);
        $mform->addRule('firstname', null, 'required', null, 'client');
        $mform->addElement('text', 'lastname', get_string('lastname'), ['size' => 40]);
        $mform->setType('lastname', \PARAM_TEXT);
        $mform->addRule('lastname', null, 'required', null, 'client');
        $mform->addElement('text', 'email', get_string('email'), ['size' => 40, 'dir' => 'ltr']);
        $mform->setType('email', \PARAM_RAW_TRIMMED);
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addRule('email', get_string('invalidemail', 'local_eventbooking'), 'email', null, 'client');
        $mform->addElement('text', 'phone', get_string('phone', 'local_eventbooking'), ['size' => 24, 'dir' => 'ltr']);
        $mform->setType('phone', \PARAM_TEXT);
        $mform->addElement('text', 'idnumber', get_string('idnumber'), ['size' => 24, 'dir' => 'ltr']);
        $mform->setType('idnumber', \PARAM_TEXT);

        $mform->addElement('html', $this->section_heading(get_string('organisationdetails', 'local_eventbooking')));
        $mform->addElement('text', 'institution', get_string('institution', 'local_eventbooking'), ['size' => 40]);
        $mform->setType('institution', \PARAM_TEXT);
        $mform->addElement('text', 'department', get_string('department', 'local_eventbooking'), ['size' => 40]);
        $mform->setType('department', \PARAM_TEXT);
        $mform->addElement('text', 'jobtitle', get_string('jobtitle', 'local_eventbooking'), ['size' => 40]);
        $mform->setType('jobtitle', \PARAM_TEXT);
        $mform->addElement('text', 'city', get_string('city', 'local_eventbooking'), ['size' => 40]);
        $mform->setType('city', \PARAM_TEXT);
        $mform->addElement('textarea', 'notes', get_string('notes', 'local_eventbooking'), 'rows="3" cols="50"');
        $mform->setType('notes', \PARAM_TEXT);

        $label = $current
            ? get_string('updateregistration', 'local_eventbooking')
            : get_string('register', 'local_eventbooking');
        $this->add_action_buttons(false, $label);
    }

    /**
     * Plain section title. These replace collapsible form headers on the public page.
     *
     * @param string $text
     * @param bool $first
     * @return string
     */
    protected function section_heading(string $text, bool $first = false): string {
        $class = 'local-eventbooking-section' . ($first ? ' is-first' : '');
        return \html_writer::div(\html_writer::tag('h3', s($text), ['class' => 'h5 mb-3']), $class);
    }

    /**
     * Check the chosen date still has room.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['email']) || !validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail', 'local_eventbooking');
        }

        $event = $this->_customdata['event'];
        $current = $this->_customdata['current'];
        $userid = (int) $this->_customdata['userid'];
        $sessionid = (int) ($data['sessionid'] ?? 0);
        $session = null;
        foreach ($this->_customdata['sessions'] as $candidate) {
            if ((int) $candidate->id === $sessionid) {
                $session = $candidate;
                break;
            }
        }
        if (!$session) {
            $errors['sessiongroup'] = get_string('requiredsession', 'local_eventbooking');
            return $errors;
        }

        $iscurrent = $current && (int) $current->sessionid === $sessionid;
        if (!$iscurrent) {
            if (!manager::session_is_open($event, $session)) {
                $errors['sessiongroup'] = get_string('registrationclosed', 'local_eventbooking');
            } else {
                $left = manager::seats_left($event, $session, $current ? $userid : 0);
                if ($left !== null && $left <= 0) {
                    $errors['sessiongroup'] = get_string('sessionfull', 'local_eventbooking');
                }
            }
        }
        return $errors;
    }
}
