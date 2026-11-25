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
 * Plugin renderer.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eventbooking\output;

use local_eventbooking\manager;
use tabobject;

/**
 * Renders event pages.
 */
class renderer extends \plugin_renderer_base {
    /** @var bool Whether the copy-link script has been queued. */
    protected $copyqueued = false;

    /**
     * Admin tabs for one event.
     *
     * @param int $eventid
     * @param string $active
     * @return string
     */
    public function event_tabs(int $eventid, string $active): string {
        $tabs = [
            new tabobject(
                'view',
                new \moodle_url('/local/eventbooking/view.php', ['id' => $eventid]),
                get_string('view', 'local_eventbooking')
            ),
            new tabobject(
                'preview',
                new \moodle_url('/local/eventbooking/view.php', ['id' => $eventid, 'preview' => 1]),
                get_string('userview', 'local_eventbooking')
            ),
            new tabobject(
                'edit',
                new \moodle_url('/local/eventbooking/edit.php', ['id' => $eventid]),
                get_string('edit', 'local_eventbooking')
            ),
            new tabobject(
                'report',
                new \moodle_url('/local/eventbooking/report.php', ['id' => $eventid]),
                get_string('report', 'local_eventbooking')
            ),
            new tabobject(
                'send',
                new \moodle_url('/local/eventbooking/send.php', ['id' => $eventid]),
                get_string('sendlink', 'local_eventbooking')
            ),
        ];
        return $this->output->tabtree($tabs, $active);
    }

    /**
     * Summary counts for the management screen.
     *
     * @param array $stats
     * @return string
     */
    public function stats(array $stats): string {
        return $this->render_from_template('local_eventbooking/stats', [
            'items' => [
                ['value' => $stats['events'], 'label' => get_string('statsevents', 'local_eventbooking')],
                ['value' => $stats['open'], 'label' => get_string('statsopen', 'local_eventbooking')],
                ['value' => $stats['registrations'], 'label' => get_string('statsregistrations', 'local_eventbooking')],
            ],
        ]);
    }

    /**
     * Participant-facing event cards.
     *
     * @param array $events
     * @return string
     */
    public function catalogue(array $events): string {
        return $this->render_from_template('local_eventbooking/catalogue', ['events' => $events]);
    }

    /**
     * Banner, description and marketing content.
     *
     * @param \stdClass $event
     * @return string
     */
    public function hero(\stdClass $event): string {
        return $this->render_from_template('local_eventbooking/hero', manager::hero_context($event));
    }

    /**
     * Copyable registration link.
     *
     * @param string $url
     * @return string
     */
    public function link_box(string $url): string {
        $this->queue_copy_script();
        $id = \html_writer::random_id('eventlink');
        $input = \html_writer::empty_tag('input', [
            'type' => 'text',
            'class' => 'form-control',
            'id' => $id,
            'value' => $url,
            'readonly' => 'readonly',
            'aria-label' => get_string('eventlink', 'local_eventbooking'),
        ]);
        $button = \html_writer::tag('button', get_string('copylink', 'local_eventbooking'), [
            'type' => 'button',
            'class' => 'btn btn-secondary',
            'data-action' => 'local-eventbooking-copy',
            'data-target' => $id,
            'data-copied' => get_string('linkcopied', 'local_eventbooking'),
        ]);
        $group = \html_writer::div($input . \html_writer::div($button, 'input-group-append'), 'input-group');
        $hint = \html_writer::div(get_string('linkhint', 'local_eventbooking'), 'text-muted mb-2');
        return \html_writer::div($hint . $group, 'local-eventbooking-linkbox mb-4');
    }

    /**
     * Load the copy button behaviour once per page.
     *
     * @return void
     */
    protected function queue_copy_script(): void {
        if ($this->copyqueued) {
            return;
        }
        $this->copyqueued = true;
        $this->page->requires->js_amd_inline(<<<'JS'
require(['core/toast'], function(Toast) {
    document.querySelectorAll('[data-action="local-eventbooking-copy"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) {
                return;
            }
            var notify = function() {
                Toast.add(btn.getAttribute('data-copied'));
            };
            var fallback = function() {
                input.focus();
                input.select();
                document.execCommand('copy');
                notify();
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(notify).catch(fallback);
            } else {
                fallback();
            }
        });
    });
});
JS);
    }
}
