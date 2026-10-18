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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/game/locallib.php');
require_once($CFG->dirroot . '/mod/game/cross/play.php');
require_once($CFG->dirroot . '/mod/game/cryptex/play.php');

/**
 * Tests for the cryptex answer check.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::game_cryptex_check
 */
final class cryptex_test extends \advanced_testcase {
    /**
     * Create a cryptex attempt with one query for a user.
     *
     * @param \stdClass $game The game.
     * @param \stdClass $user The user.
     * @return array [attempt, cryptex record, query id]
     */
    private function create_attempt(\stdClass $game, \stdClass $user): array {
        global $DB;
        $attempt = (object) ['gameid' => $game->id, 'userid' => $user->id, 'timestart' => time(), 'timefinish' => 0,
            'attempt' => 1, 'attempts' => 0, 'preview' => 0, 'language' => ''];
        $attempt = $DB->get_record('game_attempts', ['id' => $DB->insert_record('game_attempts', $attempt)]);
        $DB->insert_record_raw('game_cross', ['id' => $attempt->id, 'usedcols' => 5, 'usedrows' => 1], false, false, true);
        $DB->insert_record_raw('game_cryptex', ['id' => $attempt->id, 'letters' => 'ABCDE'], false, false, true);
        $queryid = $DB->insert_record('game_queries', ['gamekind' => 'cryptex', 'gameid' => $game->id, 'userid' => $user->id,
            'attemptid' => $attempt->id, 'sourcemodule' => 'glossary', 'questiontext' => 'Question', 'answertext' => 'ABCDE',
            'mycol' => 1, 'myrow' => 1, 'horizontal' => 1, 'studentanswer' => '', 'score' => 0, 'tries' => 0]);
        return [$attempt, $DB->get_record('game_cryptex', ['id' => $attempt->id]), $queryid];
    }

    /**
     * Create a course with a cryptex game.
     *
     * @return array [course, game, cm, context]
     */
    private function create_game(): array {
        $course = $this->getDataGenerator()->create_course();
        $glossary = $this->getDataGenerator()->create_module('glossary', ['course' => $course->id]);
        $game = $this->getDataGenerator()->create_module('game', ['course' => $course->id, 'gamekind' => 'cryptex',
            'sourcemodule' => 'glossary', 'glossaryid' => $glossary->id]);
        $cm = get_coursemodule_from_instance('game', $game->id, $course->id);
        return [$course, $game, $cm, \context_module::instance($cm->id)];
    }

    /**
     * A student must not overwrite a query row of another attempt.
     */
    public function test_check_rejects_foreign_query(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $game, $cm, $context] = $this->create_game();
        $attacker = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $victim = $this->getDataGenerator()->create_and_enrol($course, 'student');
        [$attempt, $cryptex] = $this->create_attempt($game, $attacker);
        [, , $victimqueryid] = $this->create_attempt($game, $victim);
        $this->setUser($attacker);

        ob_start();
        try {
            game_cryptex_check($cm, $game, $attempt, $cryptex, $victimqueryid, 'WRONG', false, $context, $course);
            $this->fail('Expected dml_missing_record_exception');
        } catch (\dml_missing_record_exception $e) {
            $this->assertSame('invalidrecord', $e->errorcode);
        } finally {
            ob_end_clean();
        }
        $victimquery = $DB->get_record('game_queries', ['id' => $victimqueryid]);
        $this->assertSame('', $victimquery->studentanswer);
        $this->assertEquals(0, $victimquery->tries);
    }

    /**
     * A student can still answer a query of their own attempt.
     */
    public function test_check_accepts_own_query(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $game, $cm, $context] = $this->create_game();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        [$attempt, $cryptex, $queryid] = $this->create_attempt($game, $student);
        $this->setUser($student);

        ob_start();
        try {
            game_cryptex_check($cm, $game, $attempt, $cryptex, $queryid, 'XYZ', false, $context, $course);
        } finally {
            ob_end_clean();
        }

        $query = $DB->get_record('game_queries', ['id' => $queryid]);
        $this->assertSame('XYZ', $query->studentanswer);
        $this->assertEquals(1, $query->tries);
    }
}
