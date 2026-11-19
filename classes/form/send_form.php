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
 * Send the event registration link.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eventbooking\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Choose users and an optional message, then email the event link.
 */
class send_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $options = [
            'ajax' => 'core_user/form_user_selector',
            'multiple' => true,
            'noselectionstring' => get_string('nousersselected', 'local_eventbooking'),
            'valuehtmlcallback' => static function($userid): string {
                $user = \core_user::get_user((int) $userid);
                if (!$user || !empty($user->deleted)) {
                    return '';
                }
                $name = fullname($user);
                if (!empty($user->email)) {
                    $name .= ' (' . $user->email . ')';
                }
                return $name;
            },
        ];
        $mform->addElement('autocomplete', 'userids', get_string('recipients', 'local_eventbooking'), [], $options);
        $mform->addRule('userids', get_string('norecipients', 'local_eventbooking'), 'required', null, 'client');

        $mform->addElement('textarea', 'message', get_string('personalmessage', 'local_eventbooking'), 'rows="4" cols="60"');
        $mform->setType('message', \PARAM_TEXT);
        $mform->addHelpButton('message', 'personalmessage', 'local_eventbooking');

        $mform->addElement('hidden', 'id', (int) $this->_customdata['eventid']);
        $mform->setType('id', \PARAM_INT);

        $this->add_action_buttons(false, get_string('send', 'local_eventbooking'));
    }
}
