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
 * Choose a date and confirm the phone number. Name, ID and email come from the account.
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
        $mform->addElement('html', \html_writer::div(
            get_string('profilelocked', 'local_eventbooking'),
            'local-eventbooking-lockedhint'
        ));
        $this->add_text('firstname', get_string('firstname'), '', false, [], true);
        $this->add_text('lastname', get_string('lastname'), '', false, [], true);
        $this->add_text('idnumber', get_string('idnumber'), '', false, ['dir' => 'ltr'], true);
        $this->add_text('email', get_string('email'), '', false, ['dir' => 'ltr'], true);
        $this->add_text('phone', get_string('phone', 'local_eventbooking'), 'placeholderphone', false, ['dir' => 'ltr']);

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
     * @param bool $locked Value is shown but cannot be edited.
     */
    protected function add_text(
        string $name,
        string $label,
        string $placeholderkey = '',
        bool $required = false,
        array $extra = [],
        bool $locked = false
    ): void {
        $attributes = ['size' => 40] + $extra;
        if ($placeholderkey !== '') {
            $attributes['placeholder'] = get_string($placeholderkey, 'local_eventbooking');
        }
        if ($locked) {
            $attributes['readonly'] = 'readonly';
        }
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
        global $USER;
        $errors = parent::validation($data, $files);
        if (empty($USER->email) || !validate_email($USER->email)) {
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
