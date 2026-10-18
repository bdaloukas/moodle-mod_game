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
 * Check which question files may be served by a game.
 *
 * @package    mod_game
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questionfile {
    /**
     * Check whether the question of a question text or answer file is used by the game.
     *
     * @param int $gameid The game id.
     * @param string $filearea The file area, questiontext or answer.
     * @param int $itemid The question id or the answer id.
     * @return bool
     */
    public static function is_used(int $gameid, string $filearea, int $itemid): bool {
        global $DB;

        if ($filearea === 'answer') {
            $itemid = (int)$DB->get_field('question_answers', 'question', ['id' => $itemid]);
        }

        return $itemid > 0 && $DB->record_exists('game_queries', ['gameid' => $gameid, 'questionid' => $itemid]);
    }

    /**
     * Check whether a question text or answer file may be served in the context of a game.
     *
     * Users who can view the reports of the game see all questions of the game source on the show answers page,
     * so for them the question does not have to be used in an attempt yet.
     *
     * @param \context $context The context of the game.
     * @param int $gameid The game id.
     * @param string $filearea The file area, questiontext or answer.
     * @param int $itemid The question id or the answer id.
     * @return bool
     */
    public static function can_serve(\context $context, int $gameid, string $filearea, int $itemid): bool {
        return has_capability('mod/game:viewreports', $context) || self::is_used($gameid, $filearea, $itemid);
    }
}
