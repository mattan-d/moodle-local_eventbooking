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
        $mform->addElement('html', $this->section_heading(get_string('choosesession', 'local_eventbooking'), 1, true));
        $mform->addGroup($radios, 'sessiongroup', '', '', false);

        $mform->addElement('html', $this->section_heading(get_string('personaldetails', 'local_eventbooking'), 2));
        $this->add_text('firstname', get_string('firstname'), 'placeholderfirstname', true);
        $this->add_text('lastname', get_string('lastname'), 'placeholderlastname', true);
        $this->add_text('email', get_string('email'), 'placeholderemail', true, ['dir' => 'ltr']);
        $mform->addRule('email', get_string('invalidemail', 'local_eventbooking'), 'email', null, 'client');
        $this->add_text('phone', get_string('phone', 'local_eventbooking'), 'placeholderphone', false, ['dir' => 'ltr']);
        $this->add_text('idnumber', get_string('idnumber'), 'placeholderidnumber', false, ['dir' => 'ltr']);

        $mform->addElement('html', $this->section_heading(get_string('organisationdetails', 'local_eventbooking'), 3));
        $this->add_text('institution', get_string('institution', 'local_eventbooking'), 'placeholderinstitution');
        $this->add_text('department', get_string('department', 'local_eventbooking'), 'placeholderdepartment');
        $this->add_text('jobtitle', get_string('jobtitle', 'local_eventbooking'), 'placeholderjobtitle');
        $this->add_text('city', get_string('city', 'local_eventbooking'), 'placeholdercity');
        $mform->addElement('textarea', 'notes', get_string('notes', 'local_eventbooking'), [
            'rows' => 3,
            'placeholder' => get_string('placeholdernotes', 'local_eventbooking'),
        ]);
        $mform->setType('notes', \PARAM_TEXT);

        $label = $current
            ? get_string('updateregistration', 'local_eventbooking')
            : get_string('register', 'local_eventbooking');
        $this->add_action_buttons(false, $label);
    }

    /**
     * Text field with an example placeholder.
     *
     * @param string $name
     * @param string $label
     * @param string $placeholderkey
     * @param bool $required
     * @param array $extra
     */
    protected function add_text(
        string $name,
        string $label,
        string $placeholderkey,
        bool $required = false,
        array $extra = []
    ): void {
        $attributes = ['size' => 40, 'placeholder' => get_string($placeholderkey, 'local_eventbooking')] + $extra;
        $this->_form->addElement('text', $name, $label, $attributes);
        $this->_form->setType($name, $name === 'email' ? \PARAM_RAW_TRIMMED : \PARAM_TEXT);
        if ($required) {
            $this->_form->addRule($name, null, 'required', null, 'client');
        }
    }

    /**
     * Numbered section title for the registration form.
     *
     * @param string $text
     * @param int $step
     * @param bool $first
     * @return string
     */
    protected function section_heading(string $text, int $step, bool $first = false): string {
        $class = 'local-eventbooking-section' . ($first ? ' is-first' : '');
        $badge = \html_writer::span((string) $step, 'local-eventbooking-step');
        $title = \html_writer::tag('h3', $badge . s($text), ['class' => 'local-eventbooking-sectiontitle']);
        return \html_writer::div($title, $class);
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
