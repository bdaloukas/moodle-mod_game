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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Authorization and server-side picture state for the Hidden Picture game.
 *
 * @package    mod_game
 * @copyright  2026 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_game;

defined('MOODLE_INTERNAL') || die();

/**
 * Only serve the picture and reveal state of the authenticated player's attempt.
 */
class hiddenpicture {
    /**
     * Return the attachment hash stored in the authorized attempt.
     *
     * @param int $cmid Game course module id.
     * @param int $attemptid Hidden Picture attempt id.
     * @return string The stored file's pathname hash.
     */
    public static function get_attachment_hash($cmid, $attemptid) {
        $state = self::get_picture_state($cmid, $attemptid);
        return $state->attachment;
    }

    /**
     * Resolve the authorized picture and its reveal state from the database.
     * No image selection or reveal information is accepted from the URL.
     *
     * @param int $cmid Game course module id.
     * @param int $attemptid Hidden Picture attempt id.
     * @return \stdClass Attachment, grid dimensions, visible and numbered cells.
     */
    public static function get_picture_state($cmid, $attemptid) {
        global $DB, $USER;

        $cm = get_coursemodule_from_id('game', (int)$cmid, 0, false, MUST_EXIST);
        require_login($cm->course, false, $cm);
        $context = \context_module::instance($cm->id);
        require_capability('mod/game:view', $context);

        $game = $DB->get_record('game', ['id' => $cm->instance], '*', MUST_EXIST);
        if ($game->gamekind !== 'hiddenpicture') {
            throw new \moodle_exception('hiddenpicture_error', 'game');
        }

        $attempt = $DB->get_record('game_attempts', [
            'id' => (int)$attemptid,
            'gameid' => $game->id,
            'userid' => $USER->id,
        ]);
        if (!$attempt) {
            throw new \moodle_exception('hiddenpicture_error', 'game');
        }

        // Guest attempts all use userid=1; Moodle's game logic additionally ties these to the session.
        if (isguestuser()) {
            $sessionkey = 'mod/game:instanceid' . $game->id;
            if (empty($_SESSION[$sessionkey]) || (int)$_SESSION[$sessionkey] !== (int)$attempt->id) {
                throw new \moodle_exception('hiddenpicture_error', 'game');
            }
        }

        $detail = $DB->get_record('game_hiddenpicture', ['id' => $attempt->id]);
        $mainquery = $DB->get_record('game_queries', [
            'attemptid' => $attempt->id,
            'gameid' => $game->id,
            'userid' => $USER->id,
            'gamekind' => 'hiddenpicture',
            'mycol' => 0,
            'sourcemodule' => 'glossary',
        ]);
        if (!$detail || !$mainquery || empty($mainquery->attachment) ||
                !preg_match('/^[a-f0-9]{40}$/', $mainquery->attachment)) {
            throw new \moodle_exception('hiddenpicture_error', 'game');
        }

        $cols = (int)$game->param1;
        $rows = (int)$game->param2;
        if ($cols <= 0 || $rows <= 0 || $cols * $rows > 10000) {
            throw new \moodle_exception('hiddenpicture_error', 'game');
        }

        $state = new \stdClass();
        $state->attachment = $mainquery->attachment;
        $state->cols = $cols;
        $state->rows = $rows;
        $state->found = !empty($detail->found);
        $state->visiblecells = [];
        $state->numberedcells = [];

        // Reconstruct the same positions as game_sudoku_compute_offsetquestions(), without client input.
        $queries = $DB->get_records('game_queries', [
            'attemptid' => $attempt->id,
            'gameid' => $game->id,
            'userid' => $USER->id,
            'gamekind' => 'hiddenpicture',
        ], '', 'id,mycol,score');
        foreach ($queries as $query) {
            $position = (int)$query->mycol;
            if ($position < 1 || $position > $cols * $rows) {
                continue;
            }
            $state->numberedcells[$position] = true;
            if ((float)$query->score == 1.0) {
                $state->visiblecells[$position] = true;
            }
        }

        return $state;
    }
}
