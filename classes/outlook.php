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
 * Outlook meeting invitations through Microsoft Graph.
 *
 * A registration creates a meeting on the organizer mailbox and invites the
 * employee, which blocks that time on their calendar. Declining the invitation
 * frees the workshop place. Registration is kept when Graph is unavailable.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eventbooking;

/**
 * Sends, updates and cancels the Outlook meeting for a registration.
 */
class outlook {

    /** @var string|null Cached application token. */
    protected static $token = null;

    /** @var int Unix time when the cached token should be renewed. */
    protected static $tokenuntil = 0;

    /** @var int Unix time until a failed token request is not retried. */
    protected static $tokenfaileduntil = 0;

    /**
     * Whether the site has turned the calendar connection on and filled in the app.
     *
     * @return bool
     */
    public static function configured(): bool {
        if (!(int) get_config('local_eventbooking', 'outlookenabled')) {
            return false;
        }
        $secret = trim((string) get_config('local_eventbooking', 'outlooksecret'));
        $client = trim((string) get_config('local_eventbooking', 'outlookclientid'));
        $organizer = trim((string) get_config('local_eventbooking', 'outlookorganizer'));
        return self::tenant() !== ''
            && $secret !== ''
            && (bool) preg_match('/^[A-Za-z0-9-]{8,80}$/', $client)
            && (bool) validate_email($organizer);
    }

    /**
     * Create or update the meeting for the user's active registration.
     *
     * A renewed registration (after a cancellation) replaces the previous meeting.
     * Failures are recorded for debugging and do not affect the registration.
     *
     * @param int $eventid
     * @param int $userid
     * @param bool $renew
     * @return void
     */
    public static function sync_registration(int $eventid, int $userid, bool $renew = false): void {
        if (!self::configured()) {
            return;
        }
        try {
            self::push($eventid, $userid, $renew);
        } catch (\Throwable $e) {
            debugging('local_eventbooking outlook sync failed', DEBUG_DEVELOPER);
        }
    }

    /**
     * Cancel the organizer meeting. A missing meeting counts as cancelled.
     *
     * @param string $graphid
     * @param int $registrationid Cleared only when this id is still stored there.
     * @return bool
     */
    public static function cancel_invite(string $graphid, int $registrationid = 0): bool {
        $graphid = trim($graphid);
        if ($graphid === '' || !self::configured()) {
            return false;
        }
        try {
            $path = self::graph_user_path();
            if ($path === null) {
                return false;
            }
            [$code] = self::request('POST', $path . '/events/' . rawurlencode($graphid) . '/cancel', [
                'comment' => get_string('outlookcancelcomment', 'local_eventbooking'),
            ]);
            if (in_array($code, [200, 202, 204, 404], true)) {
                if ($registrationid > 0) {
                    manager::clear_outlook_event($registrationid, $graphid);
                }
                return true;
            }
            self::note_failure($code, null);
        } catch (\Throwable $e) {
            debugging('local_eventbooking outlook cancel failed', DEBUG_DEVELOPER);
        }
        return false;
    }

    /**
     * Cancel every meeting that belongs to an event, before its rows are removed.
     *
     * @param int $eventid
     * @return void
     */
    public static function cancel_outstanding(int $eventid): void {
        global $DB;
        if (!self::configured()) {
            return;
        }
        $ids = $DB->get_fieldset_select(
            'local_eventbooking_reg',
            'outlookeventid',
            'eventid = :eventid AND outlookeventid <> :empty',
            ['eventid' => $eventid, 'empty' => '']
        );
        foreach ($ids as $graphid) {
            self::cancel_invite((string) $graphid);
        }
    }

    /**
     * Cancel meetings for one user before their registration rows are deleted.
     *
     * @param int $userid
     * @return void
     */
    public static function cancel_for_user(int $userid): void {
        global $DB;
        if (!self::configured()) {
            return;
        }
        $rows = $DB->get_records_select(
            'local_eventbooking_reg',
            'userid = :userid AND outlookeventid <> :empty',
            ['userid' => $userid, 'empty' => ''],
            '',
            'id, outlookeventid'
        );
        foreach ($rows as $row) {
            self::cancel_invite((string) $row->outlookeventid, (int) $row->id);
        }
    }

    /**
     * Send meetings that are still missing, update ones whose details changed,
     * finish cancellations, and free a place when the employee has declined.
     *
     * @return void
     */
    public static function release_declined(): void {
        if (!self::configured()) {
            return;
        }
        try {
            self::finish_cancellations();
            self::poll_responses();
        } catch (\Throwable $e) {
            debugging('local_eventbooking outlook poll failed', DEBUG_DEVELOPER);
        }
    }

    /**
     * Meeting body sent to Graph.
     *
     * @param \stdClass $event
     * @param \stdClass $session
     * @param \stdClass $registration
     * @return array
     */
    public static function build_event(\stdClass $event, \stdClass $session, \stdClass $registration): array {
        $eventname = trim(format_string($event->name ?? ''));
        $sessionname = trim(format_string($session->name ?? ''));
        $subject = $sessionname !== '' ? $sessionname : $eventname;
        if ($subject === '') {
            $subject = get_string('pluginname', 'local_eventbooking');
        }
        $subject = \core_text::substr($subject, 0, 250);
        $location = \core_text::substr(trim(format_string($session->location ?? '')), 0, 255);

        $zone = self::timezone();
        $startstamp = (int) $session->timestart;
        $endstamp = (int) ($session->timeend ?? 0);
        if ($endstamp <= $startstamp) {
            $endstamp = $startstamp + HOURSECS;
        }
        $tz = new \DateTimeZone($zone);
        $start = (new \DateTimeImmutable('@' . $startstamp))->setTimezone($tz);
        $end = (new \DateTimeImmutable('@' . $endstamp))->setTimezone($tz);

        $blocks = ['<p><strong>' . s($subject) . '</strong></p>'];
        if ($eventname !== '' && $eventname !== $subject) {
            $blocks[] = '<p>' . s($eventname) . '</p>';
        }
        if ($location !== '') {
            $blocks[] = '<p>' . s($location) . '</p>';
        }
        $url = manager::event_url((int) $event->id)->out(false);
        $blocks[] = '<p><a href="' . s($url) . '">' . s($url) . '</a></p>';
        $orgname = trim((string) get_config('local_eventbooking', 'outlookorganizername'));
        if ($orgname !== '') {
            $blocks[] = '<p>' . s($orgname) . '</p>';
        }

        $attendee = [
            'emailAddress' => [
                'address' => trim($registration->email),
            ],
            'type' => 'required',
        ];
        $name = trim(trim($registration->firstname ?? '') . ' ' . trim($registration->lastname ?? ''));
        if ($name !== '') {
            $attendee['emailAddress']['name'] = \core_text::substr($name, 0, 200);
        }

        return [
            'subject' => $subject,
            'body' => [
                'contentType' => 'HTML',
                'content' => implode('', $blocks),
            ],
            'start' => [
                'dateTime' => $start->format('Y-m-d\TH:i:s'),
                'timeZone' => $zone,
            ],
            'end' => [
                'dateTime' => $end->format('Y-m-d\TH:i:s'),
                'timeZone' => $zone,
            ],
            'location' => [
                'displayName' => $location,
            ],
            'attendees' => [$attendee],
            'showAs' => 'busy',
            'responseRequested' => true,
            'allowNewTimeProposals' => false,
            'isOnlineMeeting' => false,
            'isReminderOn' => true,
            'reminderMinutesBeforeStart' => 60,
        ];
    }

    /**
     * Stable fingerprint of the details that should appear on the invitation.
     *
     * @param array $payload
     * @return string
     */
    public static function payload_hash(array $payload): string {
        $parts = [
            $payload['subject'] ?? '',
            $payload['location']['displayName'] ?? '',
            $payload['start']['dateTime'] ?? '',
            $payload['start']['timeZone'] ?? '',
            $payload['end']['dateTime'] ?? '',
            $payload['end']['timeZone'] ?? '',
            $payload['body']['content'] ?? '',
        ];
        return sha1(implode("\n", $parts));
    }

    /**
     * The employee declined this meeting.
     *
     * @param mixed $payload
     * @param string $email
     * @return bool
     */
    public static function attendee_declined($payload, string $email): bool {
        if (!is_object($payload) || empty($payload->attendees) || !is_iterable($payload->attendees)) {
            return false;
        }
        $email = \core_text::strtolower(trim($email));
        if ($email === '') {
            return false;
        }
        foreach ($payload->attendees as $attendee) {
            if (!is_object($attendee)) {
                continue;
            }
            $address = '';
            if (isset($attendee->emailAddress->address)) {
                $address = \core_text::strtolower(trim((string) $attendee->emailAddress->address));
            }
            $response = '';
            if (isset($attendee->status->response)) {
                $response = \core_text::strtolower(trim((string) $attendee->status->response));
            }
            if ($address === $email && $response === 'declined') {
                return true;
            }
        }
        return false;
    }

    /**
     * The organizer meeting has already been cancelled.
     *
     * @param mixed $payload
     * @return bool
     */
    public static function meeting_cancelled($payload): bool {
        return is_object($payload) && !empty($payload->isCancelled);
    }

    /**
     * Create or update one active registration.
     *
     * @param int $eventid
     * @param int $userid
     * @param bool $renew
     * @return void
     */
    protected static function push(int $eventid, int $userid, bool $renew): void {
        global $DB;
        $registration = manager::get_active_registration($eventid, $userid);
        if (!$registration || !validate_email($registration->email)) {
            return;
        }
        $event = manager::get_event($eventid);
        $session = $DB->get_record('local_eventbooking_sess', [
            'id' => $registration->sessionid,
            'eventid' => $eventid,
        ]);
        if (!$event || !$session || !self::session_is_upcoming($session)) {
            return;
        }

        $existing = trim((string) ($registration->outlookeventid ?? ''));
        if ($renew && $existing !== '') {
            self::cancel_invite($existing, (int) $registration->id);
            $existing = '';
        }

        $payload = self::build_event($event, $session, $registration);
        $hash = self::payload_hash($payload);
        $path = self::graph_user_path();
        if ($path === null) {
            return;
        }

        if ($existing !== '') {
            [$code, $decoded] = self::request(
                'PATCH',
                $path . '/events/' . rawurlencode($existing),
                $payload
            );
            if ($code === 200 && is_object($decoded) && !empty($decoded->id)) {
                manager::store_outlook_event((int) $registration->id, (string) $decoded->id, $hash);
                return;
            }
            if ($code !== 404) {
                self::note_failure($code, $decoded);
                return;
            }
        }

        $payload['transactionId'] = 'local-eventbooking-' . (int) $registration->id . '-' . (int) $registration->timemodified;
        [$code, $decoded] = self::request('POST', $path . '/events', $payload);
        if (in_array($code, [200, 201], true) && is_object($decoded) && !empty($decoded->id)) {
            manager::store_outlook_event((int) $registration->id, (string) $decoded->id, $hash);
            return;
        }
        self::note_failure($code, $decoded);
    }

    /**
     * Read responses and send a single update when the session details changed.
     *
     * A missing meeting (HTTP 404) does not free the place.
     *
     * @return void
     */
    protected static function poll_responses(): void {
        global $DB;
        $now = time();
        $sql = "SELECT r.*
                  FROM {local_eventbooking_reg} r
                  JOIN {local_eventbooking_sess} s ON s.id = r.sessionid
                 WHERE r.status = :status
                   AND (
                        (s.timeend > s.timestart AND s.timeend >= :nowend)
                     OR (s.timeend <= s.timestart AND s.timestart + :hour >= :nowstart)
                   )
              ORDER BY CASE WHEN r.outlookeventid = '' THEN 0 ELSE 1 END, r.outlookchecked ASC";
        $rows = $DB->get_records_sql($sql, [
            'status' => manager::STATUS_CONFIRMED,
            'nowend' => $now,
            'nowstart' => $now,
            'hour' => HOURSECS,
        ], 0, 30);
        foreach ($rows as $row) {
            self::poll_one($row);
        }
    }

    /**
     * @param \stdClass $row
     * @return void
     */
    protected static function poll_one(\stdClass $row): void {
        global $DB;
        manager::mark_outlook_checked((int) $row->id);
        $event = manager::get_event((int) $row->eventid);
        $session = $DB->get_record('local_eventbooking_sess', [
            'id' => $row->sessionid,
            'eventid' => $row->eventid,
        ]);
        if (!$event || !$session || !self::session_is_upcoming($session) || !validate_email($row->email)) {
            return;
        }

        $graphid = trim((string) $row->outlookeventid);
        if ($graphid === '') {
            self::push((int) $row->eventid, (int) $row->userid, false);
            return;
        }

        $payload = self::build_event($event, $session, $row);
        $hash = self::payload_hash($payload);
        $path = self::graph_user_path();
        if ($path === null) {
            return;
        }

        $snapshot = null;
        if ($hash !== (string) $row->outlookhash) {
            [$code, $snapshot] = self::request('PATCH', $path . '/events/' . rawurlencode($graphid), $payload);
            if ($code === 200 && is_object($snapshot) && !empty($snapshot->id)) {
                manager::store_outlook_event((int) $row->id, (string) $snapshot->id, $hash);
            } else if ($code === 404) {
                return;
            } else {
                self::note_failure($code, $snapshot);
                return;
            }
        } else {
            [$code, $snapshot] = self::request(
                'GET',
                $path . '/events/' . rawurlencode($graphid) . '?%24select=id,isCancelled,attendees'
            );
            if ($code === 404) {
                return;
            }
            if ($code !== 200 || !is_object($snapshot)) {
                self::note_failure($code, $snapshot);
                return;
            }
        }

        if (self::meeting_cancelled($snapshot) || self::attendee_declined($snapshot, (string) $row->email)) {
            manager::release_seat((int) $row->id);
        }
    }

    /**
     * Retry Graph cancellations that did not complete when the place was freed.
     *
     * @return void
     */
    protected static function finish_cancellations(): void {
        global $DB;
        $rows = $DB->get_records_select(
            'local_eventbooking_reg',
            'status = :status AND outlookeventid <> :empty',
            ['status' => manager::STATUS_CANCELLED, 'empty' => ''],
            'outlookchecked ASC',
            'id, outlookeventid',
            0,
            20
        );
        foreach ($rows as $row) {
            self::cancel_invite((string) $row->outlookeventid, (int) $row->id);
        }
    }

    /**
     * A session still occupies time when it has not finished.
     *
     * @param \stdClass $session
     * @return bool
     */
    protected static function session_is_upcoming(\stdClass $session): bool {
        $start = (int) $session->timestart;
        $end = (int) ($session->timeend ?? 0);
        $until = $end > $start ? $end : $start + HOURSECS;
        return $until >= time();
    }

    /**
     * @return string
     */
    protected static function tenant(): string {
        $tenant = trim((string) get_config('local_eventbooking', 'outlooktenant'));
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,253}$/', $tenant)) {
            return '';
        }
        return $tenant;
    }

    /**
     * @return string
     */
    protected static function timezone(): string {
        $zone = \core_date::get_server_timezone();
        if ($zone === '' || $zone === '99') {
            return 'Asia/Jerusalem';
        }
        try {
            new \DateTimeZone($zone);
        } catch (\Exception $e) {
            return 'Asia/Jerusalem';
        }
        return $zone;
    }

    /**
     * @return string|null
     */
    protected static function graph_user_path(): ?string {
        $organizer = trim((string) get_config('local_eventbooking', 'outlookorganizer'));
        if (!validate_email($organizer)) {
            return null;
        }
        return 'https://graph.microsoft.com/v1.0/users/' . rawurlencode($organizer);
    }

    /**
     * @return string|null
     */
    protected static function token(): ?string {
        if (self::$token !== null && self::$tokenuntil > time() + 60) {
            return self::$token;
        }
        if (self::$tokenfaileduntil > time()) {
            return null;
        }
        $tenant = self::tenant();
        $client = trim((string) get_config('local_eventbooking', 'outlookclientid'));
        $secret = trim((string) get_config('local_eventbooking', 'outlooksecret'));
        if ($tenant === '' || $client === '' || $secret === '') {
            return null;
        }

        $curl = new \curl();
        $curl->setopt(['CURLOPT_TIMEOUT' => 12, 'CURLOPT_CONNECTTIMEOUT' => 5]);
        $raw = $curl->post('https://login.microsoftonline.com/' . rawurlencode($tenant) . '/oauth2/v2.0/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client,
            'client_secret' => $secret,
            'scope' => 'https://graph.microsoft.com/.default',
        ]);
        $decoded = is_string($raw) ? json_decode($raw) : null;
        $info = $curl->get_info();
        $code = (int) ($info['http_code'] ?? 0);
        if ($code !== 200 || !is_object($decoded) || empty($decoded->access_token)) {
            self::note_failure($code, $decoded);
            self::$token = null;
            self::$tokenuntil = 0;
            self::$tokenfaileduntil = time() + 60;
            return null;
        }
        self::$token = (string) $decoded->access_token;
        self::$tokenuntil = time() + max(120, (int) ($decoded->expires_in ?? 3600));
        return self::$token;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $body
     * @return array{0:int,1:mixed}
     */
    protected static function request(string $method, string $url, ?array $body = null): array {
        $token = self::token();
        if ($token === null) {
            return [0, null];
        }
        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
            'Prefer: IdType="ImmutableId"',
        ]);
        $curl->setopt(['CURLOPT_TIMEOUT' => 12, 'CURLOPT_CONNECTTIMEOUT' => 5]);
        $encoded = $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($body !== null && $encoded === false) {
            return [0, null];
        }

        $method = strtoupper($method);
        if ($method === 'GET') {
            $raw = $curl->get($url);
        } else if ($method === 'POST') {
            $raw = $curl->post($url, $encoded);
        } else if ($method === 'PATCH') {
            $raw = $curl->patch($url, $encoded);
        } else {
            $raw = $curl->request($url, ['CURLOPT_CUSTOMREQUEST' => $method]);
        }
        if (!is_string($raw) || $raw === '') {
            $decoded = null;
        } else {
            $decoded = json_decode($raw);
        }
        $info = $curl->get_info();
        return [(int) ($info['http_code'] ?? 0), $decoded];
    }

    /**
     * @param int $code
     * @param mixed $decoded
     * @return void
     */
    protected static function note_failure(int $code, $decoded): void {
        $error = '';
        if (is_object($decoded) && isset($decoded->error)) {
            if (is_string($decoded->error)) {
                $error = $decoded->error;
            } else if (is_object($decoded->error) && isset($decoded->error->code)) {
                $error = (string) $decoded->error->code;
            }
        }
        $error = clean_param($error, PARAM_ALPHANUMEXT);
        debugging('local_eventbooking outlook request failed: HTTP ' . $code . ' ' . $error, DEBUG_DEVELOPER);
    }
}
