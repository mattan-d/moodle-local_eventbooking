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
 * Event edit form.
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
 * Create or edit an event and its dates.
 */
class event_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $repeatcount = max(1, (int) ($this->_customdata['repeatcount'] ?? 1));

        $mform->addElement('header', 'general', get_string('general', 'local_eventbooking'));
        $mform->addElement('text', 'name', get_string('eventname', 'local_eventbooking'), ['size' => 64]);
        $mform->setType('name', \PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255);

        $mform->addElement('editor', 'intro_editor', get_string('description', 'local_eventbooking'), null, manager::editor_options(\context_system::instance()));
        $mform->setType('intro_editor', \PARAM_RAW);
        $mform->addElement('editor', 'marketing_editor', get_string('marketing', 'local_eventbooking'), null, manager::editor_options(\context_system::instance()));
        $mform->setType('marketing_editor', \PARAM_RAW);
        $mform->addHelpButton('marketing', 'marketing', 'local_eventbooking');

        $mform->addElement('filemanager', 'banner', get_string('banner', 'local_eventbooking'), null, manager::banner_options());
        $mform->setType('banner', \PARAM_INT);
        $mform->addHelpButton('banner', 'banner', 'local_eventbooking');

        $mform->addElement('header', 'availability', get_string('availability', 'local_eventbooking'));
        $mform->addElement('text', 'capacity', get_string('capacity', 'local_eventbooking'), ['size' => 6]);
        $mform->setType('capacity', \PARAM_INT);
        $mform->setDefault('capacity', 0);
        $mform->addHelpButton('capacity', 'capacity', 'local_eventbooking');

        $mform->addElement('advcheckbox', 'visible', get_string('visible', 'local_eventbooking'));
        $mform->setDefault('visible', 1);
        $mform->addElement('advcheckbox', 'registrationopen', get_string('registrationopen', 'local_eventbooking'));
        $mform->setDefault('registrationopen', 1);
        $mform->addElement('advcheckbox', 'allowcancel', get_string('allowcancel', 'local_eventbooking'));
        $mform->setDefault('allowcancel', 1);
        $mform->addElement('advcheckbox', 'closeafterstart', get_string('closeafterstart', 'local_eventbooking'));
        $mform->setDefault('closeafterstart', 0);

        $mform->addElement('textarea', 'confirmation', get_string('confirmation', 'local_eventbooking'), 'rows="4" cols="60"');
        $mform->setType('confirmation', \PARAM_TEXT);
        $mform->addHelpButton('confirmation', 'confirmation', 'local_eventbooking');

        $mform->addElement('header', 'sessionsheader', get_string('sessionsheader', 'local_eventbooking'));
        $mform->setExpanded('sessionsheader');

        $repeat = [];
        $repeat[] = $mform->createElement(
            'header',
            'sessionheader',
            get_string('sessionnumber', 'local_eventbooking', '{no}')
        );
        $repeat[] = $mform->createElement('hidden', 'sessionid', 0);
        $repeat[] = $mform->createElement('text', 'sessionname', get_string('sessionname', 'local_eventbooking'), ['size' => 48]);
        $repeat[] = $mform->createElement('date_time_selector', 'timestart', get_string('timestart', 'local_eventbooking'));
        $repeat[] = $mform->createElement(
            'date_time_selector',
            'timeend',
            get_string('timeend', 'local_eventbooking'),
            ['optional' => true]
        );
        $repeat[] = $mform->createElement('text', 'sessioncapacity', get_string('sessioncapacity', 'local_eventbooking'), ['size' => 6]);
        $repeat[] = $mform->createElement('text', 'location', get_string('location', 'local_eventbooking'), ['size' => 48]);
        $repeat[] = $mform->createElement('advcheckbox', 'sessiondelete', get_string('deletesession', 'local_eventbooking'));

        $tomorrow = strtotime('tomorrow 09:00');
        $repeatopts = [
            'sessionheader' => ['expanded' => true],
            'sessionid' => ['type' => \PARAM_INT, 'default' => 0],
            'sessionname' => ['type' => \PARAM_TEXT],
            'timestart' => ['default' => $tomorrow],
            'timeend' => ['default' => $tomorrow + 3 * \HOURSECS],
            'sessioncapacity' => [
                'type' => \PARAM_INT,
                'default' => 0,
                'helpbutton' => ['sessioncapacity', 'local_eventbooking'],
            ],
            'location' => ['type' => \PARAM_TEXT],
            'sessiondelete' => ['type' => \PARAM_BOOL, 'default' => 0],
        ];

        $this->repeat_elements(
            $repeat,
            $repeatcount,
            $repeatopts,
            'sessioncount',
            'sessionadd',
            1,
            get_string('addsession', 'local_eventbooking'),
            true
        );

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', \PARAM_INT);

        $this->add_action_buttons(true, get_string('saveevent', 'local_eventbooking'));
    }

    /**
     * Validate dates, limits and deletions.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ((int) ($data['capacity'] ?? 0) < 0) {
            $errors['capacity'] = get_string('invalidcapacity', 'local_eventbooking');
        }

        $count = (int) ($data['sessioncount'] ?? 0);
        $keep = [];
        $alive = 0;
        for ($i = 0; $i < $count; $i++) {
            if (!isset($data['timestart'][$i]) && !isset($data['sessionid'][$i])) {
                continue;
            }
            if (!empty($data['sessiondelete'][$i])) {
                continue;
            }
            $alive++;
            $sessionid = (int) ($data['sessionid'][$i] ?? 0);
            if ($sessionid) {
                $keep[] = $sessionid;
            }
            $start = (int) ($data['timestart'][$i] ?? 0);
            $end = (int) ($data['timeend'][$i] ?? 0);
            if ($end && $start && $end < $start) {
                $errors['sessionadd'] = get_string('endbeforestart', 'local_eventbooking');
            }
            if ((int) ($data['sessioncapacity'][$i] ?? 0) < 0) {
                $errors['sessionadd'] = get_string('invalidcapacity', 'local_eventbooking');
            }
        }
        if ($alive < 1) {
            $errors['sessionadd'] = get_string('requiresession', 'local_eventbooking');
        }

        $eventid = (int) ($data['id'] ?? 0);
        foreach ($this->_customdata['sessions'] ?? [] as $session) {
            if (in_array((int) $session->id, $keep, true)) {
                continue;
            }
            if ($eventid && manager::count_active($eventid, (int) $session->id) > 0) {
                $errors['sessionadd'] = get_string('cannotdeletesession', 'local_eventbooking', $session->name);
                break;
            }
        }
        return $errors;
    }
}
