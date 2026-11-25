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
 * Event, session and registration operations.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eventbooking;

/**
 * Persistence and business rules for event registration.
 */
class manager {
    /** @var int Confirmed registration. */
    public const STATUS_CONFIRMED = 1;
    /** @var int Cancelled registration. */
    public const STATUS_CANCELLED = 0;

    /**
     * Empty event used by the create form.
     *
     * @return \stdClass
     */
    public static function blank_event(): \stdClass {
        return (object) [
            'id' => 0,
            'name' => '',
            'intro' => '',
            'introformat' => \FORMAT_HTML,
            'marketing' => '',
            'marketingformat' => \FORMAT_HTML,
            'capacity' => 0,
            'visible' => 1,
            'registrationopen' => 1,
            'allowcancel' => 1,
            'closeafterstart' => 0,
            'confirmation' => '',
        ];
    }

    /**
     * @param int $id
     * @return \stdClass|null
     */
    public static function get_event(int $id): ?\stdClass {
        global $DB;
        $event = $DB->get_record('local_eventbooking', ['id' => $id]);
        return $event ?: null;
    }

    /**
     * @param int $id
     * @return \stdClass
     */
    public static function require_event(int $id): \stdClass {
        $event = self::get_event($id);
        if (!$event) {
            throw new \moodle_exception('eventnotfound', 'local_eventbooking');
        }
        return $event;
    }

    /**
     * @param int $eventid
     * @return \stdClass[]
     */
    public static function get_sessions(int $eventid): array {
        global $DB;
        return $DB->get_records('local_eventbooking_sess', ['eventid' => $eventid], 'timestart ASC, sortorder ASC, id ASC');
    }

    /**
     * @param int $eventid
     * @return \moodle_url
     */
    public static function event_url(int $eventid): \moodle_url {
        return new \moodle_url('/local/eventbooking/view.php', ['id' => $eventid]);
    }

    /**
     * @param \context $context
     * @return array
     */
    public static function editor_options(\context $context): array {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
        return [
            'maxfiles' => \EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => true,
            'context' => $context,
            'subdirs' => false,
        ];
    }

    /**
     * @return array
     */
    public static function banner_options(): array {
        global $CFG;
        return [
            'subdirs' => 0,
            'maxfiles' => 5,
            'maxbytes' => $CFG->maxbytes,
            'accepted_types' => ['web_image'],
        ];
    }

    /**
     * Prepare editors and session defaults for the event form.
     *
     * @param \stdClass $event
     * @param \stdClass[] $sessions
     * @return \stdClass
     */
    public static function prepare_for_form(\stdClass $event, array $sessions): \stdClass {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $context = \context_system::instance();
        $options = self::editor_options($context);
        $itemid = (int) $event->id;
        $event = file_prepare_standard_editor($event, 'intro', $options, $context, 'local_eventbooking', 'intro', $itemid);
        $event = file_prepare_standard_editor($event, 'marketing', $options, $context, 'local_eventbooking', 'marketing', $itemid);

        $draft = file_get_submitted_draft_itemid('banner');
        file_prepare_draft_area($draft, $context->id, 'local_eventbooking', 'banner', $itemid, self::banner_options());
        $event->banner = $draft;

        $event->sessionid = [];
        $event->sessionname = [];
        $event->timestart = [];
        $event->timeend = [];
        $event->sessioncapacity = [];
        $event->location = [];
        $event->sessiondelete = [];
        $index = 0;
        foreach ($sessions as $session) {
            $event->sessionid[$index] = $session->id;
            $event->sessionname[$index] = $session->name;
            $event->timestart[$index] = $session->timestart;
            $event->timeend[$index] = $session->timeend ?: 0;
            $event->sessioncapacity[$index] = $session->capacity;
            $event->location[$index] = $session->location;
            $event->sessiondelete[$index] = 0;
            $index++;
        }
        return $event;
    }

    /**
     * Create or update an event and its dates.
     *
     * @param \stdClass $data Submitted event form data.
     * @return int Event id.
     */
    public static function save_event(\stdClass $data): int {
        global $DB, $USER;

        $context = \context_system::instance();
        $now = time();
        $id = (int) ($data->id ?? 0);
        if ($id && !self::get_event($id)) {
            throw new \moodle_exception('eventnotfound', 'local_eventbooking');
        }

        $record = new \stdClass();
        $record->name = trim($data->name);
        $record->capacity = max(0, (int) $data->capacity);
        $record->visible = empty($data->visible) ? 0 : 1;
        $record->registrationopen = empty($data->registrationopen) ? 0 : 1;
        $record->allowcancel = empty($data->allowcancel) ? 0 : 1;
        $record->closeafterstart = empty($data->closeafterstart) ? 0 : 1;
        $record->confirmation = trim($data->confirmation ?? '');
        $record->timemodified = $now;
        $record->modifiedby = $USER->id;

        if ($id) {
            $record->id = $id;
            $DB->update_record('local_eventbooking', $record);
        } else {
            $record->intro = '';
            $record->introformat = \FORMAT_HTML;
            $record->marketing = '';
            $record->marketingformat = \FORMAT_HTML;
            $record->timecreated = $now;
            $record->createdby = $USER->id;
            $id = (int) $DB->insert_record('local_eventbooking', $record);
            $data->id = $id;
        }

        $editoroptions = self::editor_options($context);
        $data = file_postupdate_standard_editor(
            $data, 'intro', $editoroptions, $context, 'local_eventbooking', 'intro', $id
        );
        $data = file_postupdate_standard_editor(
            $data, 'marketing', $editoroptions, $context, 'local_eventbooking', 'marketing', $id
        );
        file_save_draft_area_files(
            (int) $data->banner,
            $context->id,
            'local_eventbooking',
            'banner',
            $id,
            self::banner_options()
        );

        $DB->update_record('local_eventbooking', (object) [
            'id' => $id,
            'intro' => $data->intro ?? '',
            'introformat' => $data->introformat ?? \FORMAT_HTML,
            'marketing' => $data->marketing ?? '',
            'marketingformat' => $data->marketingformat ?? \FORMAT_HTML,
            'timemodified' => time(),
        ]);

        self::sync_sessions($id, $data);
        return $id;
    }

    /**
     * Replace the event dates with the submitted set.
     *
     * @param int $eventid
     * @param \stdClass $data
     * @return void
     */
    protected static function sync_sessions(int $eventid, \stdClass $data): void {
        global $DB;

        $count = (int) ($data->sessioncount ?? 0);
        $keep = [];
        $sort = 0;
        $now = time();
        for ($i = 0; $i < $count; $i++) {
            $start = self::field_value($data, 'timestart', $i, null);
            if ($start === null) {
                continue;
            }
            if (!empty(self::field_value($data, 'sessiondelete', $i, 0))) {
                continue;
            }
            $name = trim((string) self::field_value($data, 'sessionname', $i, ''));
            $timestart = (int) $start;
            if ($name === '') {
                $name = userdate($timestart, get_string('strftimedatetime', 'langconfig'));
            }
            $session = (object) [
                'eventid' => $eventid,
                'name' => \core_text::substr($name, 0, 255),
                'timestart' => $timestart,
                'timeend' => max(0, (int) self::field_value($data, 'timeend', $i, 0)),
                'capacity' => max(0, (int) self::field_value($data, 'sessioncapacity', $i, 0)),
                'location' => \core_text::substr(trim((string) self::field_value($data, 'location', $i, '')), 0, 255),
                'sortorder' => $sort++,
                'timemodified' => $now,
            ];
            $sessionid = (int) self::field_value($data, 'sessionid', $i, 0);
            if ($sessionid && $DB->record_exists('local_eventbooking_sess', ['id' => $sessionid, 'eventid' => $eventid])) {
                $session->id = $sessionid;
                $DB->update_record('local_eventbooking_sess', $session);
                $keep[] = $sessionid;
            } else {
                $session->timecreated = $now;
                $keep[] = (int) $DB->insert_record('local_eventbooking_sess', $session);
            }
        }

        $existing = $DB->get_records('local_eventbooking_sess', ['eventid' => $eventid], '', 'id');
        foreach ($existing as $row) {
            if (in_array((int) $row->id, $keep, true)) {
                continue;
            }
            if (self::count_active($eventid, (int) $row->id) > 0) {
                continue;
            }
            $DB->delete_records('local_eventbooking_sess', ['id' => $row->id]);
        }
    }

    /**
     * Read one repeated form value.
     *
     * @param \stdClass $data
     * @param string $name
     * @param int $index
     * @param mixed $default
     * @return mixed
     */
    protected static function field_value(\stdClass $data, string $name, int $index, $default = null) {
        if (!isset($data->{$name})) {
            return $default;
        }
        $value = $data->{$name};
        if (is_array($value)) {
            return array_key_exists($index, $value) ? $value[$index] : $default;
        }
        return $index === 0 ? $value : $default;
    }

    /**
     * Delete an event, its dates, files, registrations and send log.
     *
     * @param int $eventid
     * @return void
     */
    public static function delete_event(int $eventid): void {
        global $DB;
        $context = \context_system::instance();
        $fs = get_file_storage();
        foreach (['banner', 'intro', 'marketing'] as $area) {
            $fs->delete_area_files($context->id, 'local_eventbooking', $area, $eventid);
        }
        $DB->delete_records('local_eventbooking_reg', ['eventid' => $eventid]);
        $DB->delete_records('local_eventbooking_sent', ['eventid' => $eventid]);
        $DB->delete_records('local_eventbooking_sess', ['eventid' => $eventid]);
        $DB->delete_records('local_eventbooking', ['id' => $eventid]);
    }

    /**
     * @param int $eventid
     * @param int $sessionid 0 counts the whole event.
     * @param int $ignoreuserid
     * @return int
     */
    public static function count_active(int $eventid, int $sessionid = 0, int $ignoreuserid = 0): int {
        global $DB;
        $params = [
            'eventid' => $eventid,
            'status' => self::STATUS_CONFIRMED,
        ];
        $sql = 'eventid = :eventid AND status = :status';
        if ($sessionid) {
            $sql .= ' AND sessionid = :sessionid';
            $params['sessionid'] = $sessionid;
        }
        if ($ignoreuserid) {
            $sql .= ' AND userid <> :ignoreuserid';
            $params['ignoreuserid'] = $ignoreuserid;
        }
        return $DB->count_records_select('local_eventbooking_reg', $sql, $params);
    }

    /**
     * Places left for a date, considering both date and event limits.
     *
     * Null means unlimited.
     *
     * @param \stdClass $event
     * @param \stdClass $session
     * @param int $ignoreuserid Exclude this user from the count (when they already hold a place).
     * @return int|null
     */
    public static function seats_left(\stdClass $event, \stdClass $session, int $ignoreuserid = 0): ?int {
        $limits = [];
        if ((int) $session->capacity > 0) {
            $limits[] = (int) $session->capacity - self::count_active((int) $event->id, (int) $session->id, $ignoreuserid);
        }
        if ((int) $event->capacity > 0) {
            $limits[] = (int) $event->capacity - self::count_active((int) $event->id, 0, $ignoreuserid);
        }
        if (!$limits) {
            return null;
        }
        return min($limits);
    }

    /**
     * @param \stdClass $event
     * @param \stdClass $session
     * @return bool
     */
    public static function session_is_open(\stdClass $event, \stdClass $session): bool {
        if (!(int) $event->registrationopen) {
            return false;
        }
        if ((int) $event->closeafterstart && (int) $session->timestart > 0 && (int) $session->timestart < time()) {
            return false;
        }
        return true;
    }

    /**
     * @param int|null $start
     * @param int|null $end
     * @return string
     */
    public static function format_range(?int $start, ?int $end): string {
        if (empty($start)) {
            return get_string('nodates', 'local_eventbooking');
        }
        $starttext = userdate($start, get_string('strftimedatetime', 'langconfig'));
        if (empty($end) || $end === $start) {
            return $starttext;
        }
        $endtext = userdate($end, get_string('strftimedatetime', 'langconfig'));
        if (userdate($start, '%Y%m%d') === userdate($end, '%Y%m%d')) {
            $endtext = userdate($end, get_string('strftimetime', 'langconfig'));
        }
        return $starttext . ' – ' . $endtext;
    }

    /**
     * @param \stdClass $session
     * @return string
     */
    public static function session_label(\stdClass $session): string {
        $when = self::format_range((int) $session->timestart, (int) ($session->timeend ?? 0));
        $name = trim($session->name ?? '');
        if ($name !== '' && $name !== $when) {
            return $name . ' — ' . $when;
        }
        return $when;
    }

    /**
     * Label used on the registration radio, including location and availability.
     *
     * @param \stdClass $session
     * @param int|null $left
     * @param bool $open
     * @return string
     */
    public static function session_choice_label(\stdClass $session, ?int $left, bool $open): string {
        $parts = [self::session_label($session)];
        $location = trim($session->location ?? '');
        if ($location !== '') {
            $parts[] = $location;
        }
        if (!$open) {
            $parts[] = get_string('sessionclosed', 'local_eventbooking');
        } else if ($left === null) {
            $parts[] = get_string('unlimited', 'local_eventbooking');
        } else if ($left <= 0) {
            $parts[] = get_string('full', 'local_eventbooking');
        } else {
            $parts[] = get_string('seatsleft', 'local_eventbooking', $left);
        }
        return implode(' · ', $parts);
    }

    /**
     * Rich label for a session choice. The text is escaped; the markup is not.
     *
     * @param \stdClass $session
     * @param int|null $left
     * @param bool $open
     * @return string
     */
    public static function session_choice_html(\stdClass $session, ?int $left, bool $open): string {
        $start = (int) $session->timestart;
        $end = (int) ($session->timeend ?? 0);
        $name = trim($session->name ?? '');
        $location = trim($session->location ?? '');

        if (!$open) {
            $statustext = get_string('sessionclosed', 'local_eventbooking');
            $statusclass = 'is-closed';
        } else if ($left === null) {
            $statustext = get_string('unlimited', 'local_eventbooking');
            $statusclass = 'is-unlimited';
        } else if ($left <= 0) {
            $statustext = get_string('full', 'local_eventbooking');
            $statusclass = 'is-full';
        } else {
            $statustext = get_string('seatsleft', 'local_eventbooking', $left);
            $statusclass = 'is-open';
        }

        $html = \html_writer::start_span('local-eventbooking-choice');
        if ($start > 0) {
            $time = userdate($start, get_string('strftimetime', 'langconfig'));
            if ($end > $start) {
                $time .= '–' . userdate($end, get_string('strftimetime', 'langconfig'));
            }
            $date = \html_writer::span(s(userdate($start, '%d')), 'local-eventbooking-choice-day');
            $date .= \html_writer::span(s(userdate($start, '%b')), 'local-eventbooking-choice-month');
            $date .= \html_writer::span(s($time), 'local-eventbooking-choice-time', ['dir' => 'ltr']);
            $html .= \html_writer::span($date, 'local-eventbooking-choice-date');
        }
        $html .= \html_writer::start_span('local-eventbooking-choice-body');
        if ($name !== '') {
            $html .= \html_writer::span(s($name), 'local-eventbooking-choice-name');
        }
        if ($location !== '') {
            $html .= \html_writer::span(s($location), 'local-eventbooking-choice-location');
        }
        $html .= \html_writer::end_span();
        $html .= \html_writer::span(s($statustext), 'local-eventbooking-choice-status ' . $statusclass);
        $html .= \html_writer::end_span();
        return $html;
    }

    /**
     * @param int $eventid
     * @param int $userid
     * @return \stdClass|null
     */
    public static function get_active_registration(int $eventid, int $userid): ?\stdClass {
        global $DB;
        $record = $DB->get_record('local_eventbooking_reg', [
            'eventid' => $eventid,
            'userid' => $userid,
            'status' => self::STATUS_CONFIRMED,
        ]);
        return $record ?: null;
    }

    /**
     * Register the current user, or update their existing registration.
     *
     * @param int $eventid
     * @param int $sessionid
     * @param array $details
     * @return array{ok:bool,updated:bool,message:string}
     */
    public static function register(int $eventid, int $sessionid, array $details): array {
        global $DB, $USER;

        $context = \context_system::instance();
        if (!has_capability('local/eventbooking:register', $context)) {
            return self::fail('cannotregister');
        }
        if (empty($USER->email) || !validate_email($USER->email)) {
            return self::fail('invalidemail');
        }

        $lockfactory = \core\lock\lock_config::get_lock_factory('local_eventbooking');
        $lock = $lockfactory->get_lock('register_' . $eventid, 10);
        if (!$lock) {
            return self::fail('lockfailed');
        }

        $updated = false;
        $committed = false;
        $event = null;
        $session = null;
        try {
            $transaction = $DB->start_delegated_transaction();
            $event = self::get_event($eventid);
            $session = $DB->get_record('local_eventbooking_sess', ['id' => $sessionid, 'eventid' => $eventid]);
            if (!$event || !$event->visible || !$session) {
                $transaction->allow_commit();
                return self::fail('eventnotavailable');
            }
            if (!(int) $event->registrationopen) {
                $transaction->allow_commit();
                return self::fail('registrationclosed');
            }

            $existing = self::get_active_registration($eventid, (int) $USER->id);
            $same = $existing && (int) $existing->sessionid === (int) $session->id;
            if (!$same) {
                if (!self::session_is_open($event, $session)) {
                    $transaction->allow_commit();
                    return self::fail('registrationclosed');
                }
                $left = self::seats_left($event, $session, $existing ? (int) $USER->id : 0);
                if ($left !== null && $left <= 0) {
                    $transaction->allow_commit();
                    return self::fail('sessionfull');
                }
            }

            $record = self::registration_record($eventid, (int) $session->id, $details);
            $now = time();
            $record->status = self::STATUS_CONFIRMED;
            $record->timemodified = $now;
            $record->timecancelled = 0;
            if ($existing) {
                self::keep_organisation($record, $existing);
                $record->id = $existing->id;
                $record->timecreated = $existing->timecreated;
                $DB->update_record('local_eventbooking_reg', $record);
                $updated = true;
            } else {
                $cancelled = $DB->get_record('local_eventbooking_reg', [
                    'eventid' => $eventid,
                    'userid' => $USER->id,
                    'status' => self::STATUS_CANCELLED,
                ], '*', \IGNORE_MULTIPLE);
                if ($cancelled) {
                    self::keep_organisation($record, $cancelled);
                    $record->id = $cancelled->id;
                    $record->timecreated = $cancelled->timecreated;
                    $DB->update_record('local_eventbooking_reg', $record);
                } else {
                    $record->timecreated = $now;
                    $DB->insert_record('local_eventbooking_reg', $record);
                }
            }
            $transaction->allow_commit();
            $committed = true;
        } catch (\Throwable $e) {
            if (isset($transaction) && !$transaction->is_disposed()) {
                $transaction->rollback($e);
            }
            throw $e;
        } finally {
            $lock->release();
        }

        if ($committed && $event && $session) {
            self::send_confirmation($event, $session, $USER, $updated);
        }
        return ['ok' => true, 'updated' => $updated, 'message' => ''];
    }

    /**
     * @param string $identifier
     * @return array{ok:bool,updated:bool,message:string}
     */
    protected static function fail(string $identifier): array {
        return [
            'ok' => false,
            'updated' => false,
            'message' => get_string($identifier, 'local_eventbooking'),
        ];
    }

    /**
     * @param int $eventid
     * @param int $sessionid
     * @param array $details
     * @return \stdClass
     */
    protected static function registration_record(int $eventid, int $sessionid, array $details): \stdClass {
        global $USER;
        $record = new \stdClass();
        $record->eventid = $eventid;
        $record->sessionid = $sessionid;
        $record->userid = $USER->id;
        $record->firstname = \core_text::substr(trim($USER->firstname ?? ''), 0, 100);
        $record->lastname = \core_text::substr(trim($USER->lastname ?? ''), 0, 100);
        $record->email = \core_text::substr(trim($USER->email ?? ''), 0, 100);
        $record->phone = \core_text::substr(trim($details['phone'] ?? ''), 0, 30);
        $record->idnumber = \core_text::substr(trim($USER->idnumber ?? ''), 0, 255);
        $record->institution = '';
        $record->department = '';
        $record->city = '';
        $record->jobtitle = '';
        $record->notes = '';
        return $record;
    }

    /**
     * Keep organisation fields already stored when the form no longer collects them.
     *
     * @param \stdClass $record
     * @param \stdClass $previous
     * @return void
     */
    protected static function keep_organisation(\stdClass $record, \stdClass $previous): void {
        foreach (['institution', 'department', 'city', 'jobtitle', 'notes'] as $field) {
            $record->$field = $previous->$field;
        }
    }

    /**
     * Cancel the user's active registration and free the place.
     *
     * @param int $eventid
     * @param int $userid
     * @return bool
     */
    public static function cancel(int $eventid, int $userid): bool {
        global $DB;
        $event = self::get_event($eventid);
        if (!$event || !(int) $event->allowcancel) {
            return false;
        }
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_eventbooking');
        $lock = $lockfactory->get_lock('register_' . $eventid, 10);
        if (!$lock) {
            return false;
        }
        try {
            $existing = self::get_active_registration($eventid, $userid);
            if (!$existing) {
                return false;
            }
            $existing->status = self::STATUS_CANCELLED;
            $existing->timecancelled = time();
            $existing->timemodified = time();
            $DB->update_record('local_eventbooking_reg', $existing);
            return true;
        } finally {
            $lock->release();
        }
    }

    /**
     * Email a confirmation to the participant.
     *
     * @param \stdClass $event
     * @param \stdClass $session
     * @param \stdClass $user
     * @param bool $updated
     * @return void
     */
    protected static function send_confirmation(\stdClass $event, \stdClass $session, \stdClass $user, bool $updated): void {
        global $SITE;
        $recipient = \core_user::get_user($user->id);
        if (!$recipient || empty($recipient->email)) {
            return;
        }
        $a = (object) [
            'fullname' => fullname($recipient),
            'eventname' => format_string($event->name),
            'session' => self::session_label($session),
            'confirmation' => trim($event->confirmation ?? ''),
            'link' => self::event_url((int) $event->id)->out(false),
            'sitename' => format_string($SITE->fullname),
        ];
        $string = $updated ? 'updatesubject' : 'confirmationsubject';
        $subject = get_string($string, 'local_eventbooking', format_string($event->name));
        $text = get_string('confirmationbody', 'local_eventbooking', $a);
        email_to_user($recipient, \core_user::get_support_user(), $subject, $text, text_to_html($text, false, false, true));
    }

    /**
     * Email the registration link to the selected users.
     *
     * @param \stdClass $event
     * @param int[] $userids
     * @param string $message
     * @return array{sent:int,skipped:int}
     */
    public static function send_links(\stdClass $event, array $userids, string $message): array {
        global $DB, $USER, $SITE;

        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        $from = \core_user::get_user($USER->id);
        if (!$from || empty($from->email) || !empty($from->deleted)) {
            $from = \core_user::get_noreply_user();
        }
        $link = self::event_url((int) $event->id)->out(false);
        $eventname = format_string($event->name);
        $message = trim($message);
        $sent = 0;
        $skipped = 0;

        foreach ($userids as $userid) {
            $user = \core_user::get_user($userid);
            if (!$user || !empty($user->deleted) || !empty($user->suspended) || isguestuser($user) ||
                    empty($user->email) || !validate_email($user->email)) {
                $skipped++;
                continue;
            }
            $a = (object) [
                'fullname' => fullname($user),
                'eventname' => $eventname,
                'message' => $message,
                'link' => $link,
                'sitename' => format_string($SITE->fullname),
            ];
            $subject = get_string('emailsubject', 'local_eventbooking', $eventname);
            $text = get_string('emailbody', 'local_eventbooking', $a);
            $ok = email_to_user($user, $from, $subject, $text, text_to_html($text, false, false, true));
            if (!$ok) {
                $skipped++;
                continue;
            }
            $DB->insert_record('local_eventbooking_sent', (object) [
                'eventid' => $event->id,
                'userid' => $user->id,
                'email' => $user->email,
                'sentby' => $USER->id,
                'message' => $message,
                'timecreated' => time(),
            ]);
            $sent++;
        }
        return ['sent' => $sent, 'skipped' => $skipped];
    }

    /**
     * @param int $eventid
     * @param int $limit
     * @return \stdClass[]
     */
    public static function recent_sends(int $eventid, int $limit = 20): array {
        global $DB;
        return $DB->get_records('local_eventbooking_sent', ['eventid' => $eventid], 'timecreated DESC', '*', 0, $limit);
    }

    /**
     * Banner image URLs for an event, in file order.
     *
     * @param int $eventid
     * @return array<int,array{url:string,alt:string}>
     */
    public static function banner_files(int $eventid): array {
        $context = \context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'local_eventbooking',
            'banner',
            $eventid,
            'filepath, filename',
            false
        );
        $images = [];
        foreach ($files as $file) {
            $info = $file->get_imageinfo();
            $width = is_array($info) ? (int) ($info['width'] ?? 0) : 0;
            $height = is_array($info) ? (int) ($info['height'] ?? 0) : 0;
            $images[] = [
                'url' => \moodle_url::make_pluginfile_url(
                    $context->id,
                    'local_eventbooking',
                    'banner',
                    $eventid,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false),
                'alt' => $file->get_filename(),
                'logo' => $height > 0 && $height <= 220 && $width > 0 && $width <= 800,
            ];
        }
        return $images;
    }

    /**
     * @param string $text
     * @param int $format
     * @param string $filearea
     * @param int $itemid
     * @return string
     */
    public static function format_editor(string $text, int $format, string $filearea, int $itemid): string {
        if (trim($text) === '') {
            return '';
        }
        $context = \context_system::instance();
        $text = file_rewrite_pluginfile_urls(
            $text, 'pluginfile.php', $context->id, 'local_eventbooking', $filearea, $itemid
        );
        return format_text($text, $format, ['context' => $context]);
    }

    /**
     * Template context for the event page header and marketing content.
     *
     * @param \stdClass $event
     * @return array
     */
    public static function hero_context(\stdClass $event): array {
        $images = self::banner_files((int) $event->id);
        $banner = null;
        if ($images) {
            $banner = array_shift($images);
            $banner['alt'] = $event->name;
        }
        $intro = self::format_editor($event->intro ?? '', (int) $event->introformat, 'intro', (int) $event->id);
        $marketing = self::format_editor(
            $event->marketing ?? '',
            (int) $event->marketingformat,
            'marketing',
            (int) $event->id
        );
        $gallery = array_values($images);
        $sessions = self::get_sessions((int) $event->id);
        $summary = self::present_event($event, $sessions, self::count_active((int) $event->id));
        $locations = [];
        foreach ($sessions as $session) {
            $location = trim($session->location ?? '');
            if ($location !== '' && !in_array($location, $locations, true)) {
                $locations[] = $location;
            }
        }
        $dates = $summary['dates'];
        if ($dates === get_string('nodates', 'local_eventbooking')) {
            $dates = '';
        }
        return [
            'name' => $event->name,
            'banner' => $banner,
            'hasgallery' => !empty($gallery),
            'gallery' => $gallery,
            'intro' => $intro,
            'marketing' => $marketing,
            'badge' => $summary['badge'],
            'badgeclass' => $summary['badgeclass'],
            'statusclass' => self::hero_status_class($summary['badgeclass']),
            'dates' => $dates,
            'when' => self::hero_when($sessions),
            'haslocations' => !empty($locations),
            'locations' => array_map(static fn(string $name): array => ['name' => $name], $locations),
            'hasfollow' => $marketing !== '' || !empty($gallery),
        ];
    }

    /**
     * Visual state for the header status pill.
     *
     * @param string $badgeclass
     * @return string
     */
    protected static function hero_status_class(string $badgeclass): string {
        if (strpos($badgeclass, 'warning') !== false) {
            return 'is-closed';
        }
        if (strpos($badgeclass, 'secondary') !== false) {
            return 'is-muted';
        }
        return 'is-open';
    }

    /**
     * Compact date for the event header.
     *
     * @param \stdClass[] $sessions
     * @return array
     */
    protected static function hero_when(array $sessions): array {
        $starts = [];
        foreach ($sessions as $session) {
            $start = (int) $session->timestart;
            if ($start > 0) {
                $starts[] = $start;
            }
        }
        if (!$starts) {
            return [];
        }
        $first = min($starts);
        $last = max($starts);
        $sameday = userdate($first, '%Y%m%d') === userdate($last, '%Y%m%d');
        $samemonth = userdate($first, '%Y%m') === userdate($last, '%Y%m');
        $day = userdate($first, '%d');
        if (!$sameday && $samemonth) {
            $day .= '–' . userdate($last, '%d');
        }
        $time = '';
        if ($sameday) {
            $end = 0;
            foreach ($sessions as $session) {
                if ((int) $session->timestart === $first) {
                    $end = max($end, (int) ($session->timeend ?? 0));
                }
            }
            $time = userdate($first, get_string('strftimetime', 'langconfig'));
            if ($end > $first) {
                $time .= '–' . userdate($end, get_string('strftimetime', 'langconfig'));
            }
        }
        $until = '';
        if (!$sameday && !$samemonth) {
            $until = get_string(
                'dateuntil',
                'local_eventbooking',
                userdate($last, '%d') . ' ' . userdate($last, '%b')
            );
        }
        return [
            'day' => $day,
            'month' => userdate($first, '%b'),
            'time' => $time,
            'until' => $until,
        ];
    }

    /**
     * Paged event list for the management screen or the participant catalogue.
     *
     * @param bool $visibleonly
     * @param string $search
     * @param int $page
     * @param int $perpage
     * @return array{events:array,total:int}
     */
    public static function list_events(bool $visibleonly, string $search, int $page, int $perpage): array {
        global $DB;

        $params = [];
        $where = ['1=1'];
        if ($visibleonly) {
            $where[] = 'e.visible = 1';
        }
        if ($search !== '') {
            $where[] = $DB->sql_like('e.name', ':search', false, false);
            $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
        }
        $wheresql = implode(' AND ', $where);
        $total = $DB->count_records_sql("SELECT COUNT(1) FROM {local_eventbooking} e WHERE $wheresql", $params);

        if ($visibleonly) {
            $order = "COALESCE((SELECT MIN(s.timestart)
                                   FROM {local_eventbooking_sess} s
                                  WHERE s.eventid = e.id), 2147483647) ASC";
        } else {
            $order = 'e.timemodified DESC';
        }
        $records = $DB->get_records_sql(
            "SELECT e.* FROM {local_eventbooking} e WHERE $wheresql ORDER BY $order, e.id DESC",
            $params,
            $page * $perpage,
            $perpage
        );

        $grouped = [];
        $counts = [];
        if ($records) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($records), \SQL_PARAMS_NAMED, 'ev');
            $sessions = $DB->get_records_select('local_eventbooking_sess', "eventid $insql", $inparams, 'timestart ASC');
            foreach ($sessions as $session) {
                $grouped[$session->eventid][] = $session;
            }
            $countrecords = $DB->get_records_sql(
                "SELECT eventid, COUNT(1) AS taken
                   FROM {local_eventbooking_reg}
                  WHERE status = :status AND eventid $insql
               GROUP BY eventid",
                $inparams + ['status' => self::STATUS_CONFIRMED]
            );
            foreach ($countrecords as $row) {
                $counts[$row->eventid] = (int) $row->taken;
            }
        }

        $events = [];
        foreach ($records as $event) {
            $events[] = self::present_event(
                $event,
                $grouped[$event->id] ?? [],
                $counts[$event->id] ?? 0
            );
        }
        return ['events' => $events, 'total' => $total];
    }

    /**
     * @return array{events:int,open:int,registrations:int}
     */
    public static function stats(): array {
        global $DB;
        return [
            'events' => $DB->count_records('local_eventbooking'),
            'open' => $DB->count_records('local_eventbooking', ['visible' => 1, 'registrationopen' => 1]),
            'registrations' => $DB->count_records('local_eventbooking_reg', ['status' => self::STATUS_CONFIRMED]),
        ];
    }

    /**
     * Display data for one event card or table row.
     *
     * @param \stdClass $event
     * @param \stdClass[] $sessions
     * @param int $taken
     * @return array
     */
    public static function present_event(\stdClass $event, array $sessions, int $taken): array {
        $starts = array_map(static fn($session): int => (int) $session->timestart, $sessions);
        $ends = array_map(static fn($session): int => (int) ($session->timeend ?: $session->timestart), $sessions);
        $first = $starts ? min($starts) : 0;
        $last = $ends ? max($ends) : 0;
        $images = self::banner_files((int) $event->id);
        $past = $last && $last < time();

        if (!(int) $event->visible) {
            $badge = get_string('hidden', 'local_eventbooking');
            $badgeclass = 'badge-secondary';
        } else if ($past) {
            $badge = get_string('past', 'local_eventbooking');
            $badgeclass = 'badge-secondary';
        } else if (!(int) $event->registrationopen) {
            $badge = get_string('registrationclosedstatus', 'local_eventbooking');
            $badgeclass = 'badge-warning';
        } else {
            $badge = get_string('registrationopenstatus', 'local_eventbooking');
            $badgeclass = 'badge-success';
        }

        $occupancy = (int) $event->capacity > 0
            ? $taken . ' / ' . (int) $event->capacity
            : get_string('registeredcount', 'local_eventbooking', $taken);

        return [
            'id' => (int) $event->id,
            'name' => $event->name,
            'url' => self::event_url((int) $event->id)->out(false),
            'bannerurl' => $images[0]['url'] ?? '',
            'dates' => self::format_range($first ?: null, ($last && $last !== $first) ? $last : null),
            'summary' => shorten_text(strip_tags($event->intro ?? ''), 160),
            'badge' => $badge,
            'badgeclass' => $badgeclass,
            'occupancy' => $occupancy,
            'sessioncount' => count($sessions),
            'taken' => $taken,
            'visible' => (int) $event->visible,
            'registrationopen' => (int) $event->registrationopen,
            'past' => $past,
        ];
    }

    /**
     * Registrations of one user, newest first.
     *
     * @param int $userid
     * @return \stdClass[]
     */
    public static function my_registrations(int $userid): array {
        global $DB;
        $sql = "SELECT r.id, r.status, r.timecreated, e.id AS eventid, e.name AS eventname, e.visible,
                       s.name AS sessionname, s.timestart, s.timeend, s.location
                  FROM {local_eventbooking_reg} r
                  JOIN {local_eventbooking} e ON e.id = r.eventid
             LEFT JOIN {local_eventbooking_sess} s ON s.id = r.sessionid
                 WHERE r.userid = :userid
              ORDER BY r.timecreated DESC";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Participant rows for the on-screen report and the download.
     *
     * @param int $eventid
     * @param int $sessionid
     * @param string $status confirmed, cancelled, or empty for all
     * @param string $search
     * @return array{columns:array<string,string>,rows:array<int,array<string,string>>,confirmed:int,cancelled:int}
     */
    public static function report(int $eventid, int $sessionid, string $status, string $search): array {
        global $DB;

        $params = ['eventid' => $eventid];
        $where = ['r.eventid = :eventid'];
        if ($sessionid) {
            $where[] = 'r.sessionid = :sessionid';
            $params['sessionid'] = $sessionid;
        }
        if ($status === 'confirmed') {
            $where[] = 'r.status = :status';
            $params['status'] = self::STATUS_CONFIRMED;
        } else if ($status === 'cancelled') {
            $where[] = 'r.status = :status';
            $params['status'] = self::STATUS_CANCELLED;
        }
        if ($search !== '') {
            $like = '%' . $DB->sql_like_escape($search) . '%';
            $parts = [];
            $fields = [
                'r.firstname', 'r.lastname', 'r.email', 'r.phone', 'r.idnumber',
                'r.institution', 'r.department', 'r.jobtitle', 'r.city', 'u.username',
            ];
            foreach ($fields as $index => $field) {
                $key = 'q' . $index;
                $parts[] = $DB->sql_like($field, ':' . $key, false, false);
                $params[$key] = $like;
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        $sql = "SELECT r.*, s.name AS sessionname, s.timestart, s.timeend, s.location AS sessionlocation,
                       u.username
                  FROM {local_eventbooking_reg} r
             LEFT JOIN {local_eventbooking_sess} s ON s.id = r.sessionid
             LEFT JOIN {user} u ON u.id = r.userid
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY s.timestart ASC, r.lastname ASC, r.firstname ASC, r.id ASC";
        $records = $DB->get_records_sql($sql, $params);

        $profile = self::profile_values(array_map(static fn($row): int => (int) $row->userid, $records));
        $columns = [
            'fullname' => get_string('fullname'),
            'username' => get_string('username'),
            'email' => get_string('email'),
            'phone' => get_string('phone', 'local_eventbooking'),
            'idnumber' => get_string('idnumber'),
            'institution' => get_string('institution', 'local_eventbooking'),
            'department' => get_string('department', 'local_eventbooking'),
            'jobtitle' => get_string('jobtitle', 'local_eventbooking'),
            'city' => get_string('city', 'local_eventbooking'),
            'session' => get_string('sessionname', 'local_eventbooking'),
            'sessionstart' => get_string('timestart', 'local_eventbooking'),
            'location' => get_string('location', 'local_eventbooking'),
            'status' => get_string('status', 'local_eventbooking'),
            'registeredon' => get_string('registeredon', 'local_eventbooking'),
            'notes' => get_string('notes', 'local_eventbooking'),
        ];
        foreach ($profile['fields'] as $field) {
            $columns['cf_' . $field->id] = $field->name;
        }

        $rows = [];
        $confirmed = 0;
        $cancelled = 0;
        foreach ($records as $record) {
            if ((int) $record->status === self::STATUS_CONFIRMED) {
                $confirmed++;
            } else {
                $cancelled++;
            }
            $session = (object) [
                'name' => $record->sessionname ?? '',
                'timestart' => $record->timestart ?? 0,
                'timeend' => $record->timeend ?? 0,
                'location' => $record->sessionlocation ?? '',
            ];
            $row = [
                'fullname' => trim($record->firstname . ' ' . $record->lastname),
                'username' => $record->username ?? '',
                'email' => $record->email,
                'phone' => $record->phone,
                'idnumber' => $record->idnumber,
                'institution' => $record->institution,
                'department' => $record->department,
                'jobtitle' => $record->jobtitle,
                'city' => $record->city,
                'session' => $record->sessionname ? self::session_label($session) : '',
                'sessionstart' => !empty($record->timestart)
                    ? userdate($record->timestart, get_string('strftimedatetime', 'langconfig')) : '',
                'location' => $record->sessionlocation ?? '',
                'status' => (int) $record->status === self::STATUS_CONFIRMED
                    ? get_string('confirmed', 'local_eventbooking')
                    : get_string('cancelled', 'local_eventbooking'),
                'registeredon' => userdate($record->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
                'notes' => $record->notes ?? '',
            ];
            foreach ($profile['fields'] as $field) {
                $row['cf_' . $field->id] = self::format_profile_value(
                    $field,
                    $profile['values'][$record->userid][$field->id] ?? ''
                );
            }
            $rows[] = $row;
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'confirmed' => $confirmed,
            'cancelled' => $cancelled,
        ];
    }

    /**
     * Custom profile values keyed by userid then field id.
     *
     * @param int[] $userids
     * @return array{fields:\stdClass[],values:array<int,array<int,string>>}
     */
    protected static function profile_values(array $userids): array {
        global $DB;
        $fields = $DB->get_records('user_info_field', null, 'sortorder ASC', 'id, shortname, name, datatype');
        $userids = array_values(array_filter(array_unique(array_map('intval', $userids))));
        if (!$fields || !$userids) {
            return ['fields' => $fields ?: [], 'values' => []];
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, \SQL_PARAMS_NAMED, 'uid');
        $stored = $DB->get_records_sql(
            "SELECT id, userid, fieldid, data FROM {user_info_data} WHERE userid $insql",
            $params
        );
        $values = [];
        foreach ($stored as $row) {
            $values[$row->userid][$row->fieldid] = $row->data;
        }
        return ['fields' => $fields, 'values' => $values];
    }

    /**
     * @param \stdClass $field
     * @param string $value
     * @return string
     */
    protected static function format_profile_value(\stdClass $field, string $value): string {
        if ($value === '') {
            return '';
        }
        if ($field->datatype === 'checkbox') {
            return $value ? get_string('yes') : get_string('no');
        }
        if ($field->datatype === 'datetime' && is_numeric($value) && (int) $value > 0) {
            return userdate((int) $value, get_string('strftimedate', 'langconfig'));
        }
        return trim(strip_tags($value));
    }
}
