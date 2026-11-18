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

namespace mod_game;

/**
 * Access check for the picture of a hidden picture game.
 *
 * @package    mod_game
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hiddenpicture {
    /**
     * Check the access to the game and return the picture file hash stored for the attempt of the current user.
     *
     * @param int $cmid The course module id.
     * @param int $attemptid The attempt id.
     * @return string The path name hash of the picture.
     */
    public static function get_attachment_hash(int $cmid, int $attemptid): string {
        global $DB, $USER;

        $cm = get_coursemodule_from_id('game', $cmid, 0, false, MUST_EXIST);
        require_login($cm->course, false, $cm);
        require_capability('mod/game:view', \context_module::instance($cm->id));

        $attempt = $DB->get_record(
            'game_attempts',
            ['id' => $attemptid, 'gameid' => $cm->instance, 'userid' => $USER->id],
            'id',
            MUST_EXIST
        );

        return $DB->get_field('game_queries', 'attachment', ['attemptid' => $attempt->id, 'mycol' => 0], MUST_EXIST);
    }
}
