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
 * Tests for authorization and server-side image reveal state in Hidden Picture.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2026 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hidden Picture access control test case.
 */
class mod_game_hiddenpicture_testcase extends advanced_testcase {
    /**
     * Set up a game with two students and one picture attempt.
     *
     * @return array An array containing the game, course module, students and attempt.
     */
    private function create_picture_attempt() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($first->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($second->id, $course->id, 'student');

        $game = $this->getDataGenerator()->create_module('game', [
            'course' => $course->id,
            'name' => 'Picture access test',
            'gamekind' => 'hiddenpicture',
            'param1' => 2,
            'param2' => 2,
        ]);
        $cm = get_coursemodule_from_instance('game', $game->id, $course->id);
        $attemptid = $DB->insert_record('game_attempts', (object)[
            'gameid' => $game->id,
            'userid' => $first->id,
            'timestart' => time(),
            'timefinish' => 0,
            'score' => 0,
        ]);
        $DB->insert_record('game_hiddenpicture', (object)[
            'id' => $attemptid,
            'correct' => 0,
            'wrong' => 0,
            'found' => 0,
        ]);
        $hash = str_repeat('a', 40);
        $DB->insert_record('game_queries', (object)[
            'attemptid' => $attemptid,
            'gameid' => $game->id,
            'userid' => $first->id,
            'gamekind' => 'hiddenpicture',
            'sourcemodule' => 'glossary',
            'mycol' => 0,
            'questionid' => 0,
            'glossaryentryid' => 123,
            'attachment' => $hash,
            'score' => 0,
        ]);
        $DB->insert_record('game_queries', (object)[
            'attemptid' => $attemptid,
            'gameid' => $game->id,
            'userid' => $first->id,
            'gamekind' => 'hiddenpicture',
            'sourcemodule' => 'glossary',
            'mycol' => 1,
            'score' => 1,
        ]);
        $DB->insert_record('game_queries', (object)[
            'attemptid' => $attemptid,
            'gameid' => $game->id,
            'userid' => $first->id,
            'gamekind' => 'hiddenpicture',
            'sourcemodule' => 'glossary',
            'mycol' => 2,
            'score' => 0,
        ]);

        return [$game, $cm, $first, $second, $attemptid, $hash];
    }

    /**
     * A player may retrieve their own image and reveal only cells earned by their answers.
     */
    public function test_owner_receives_authorized_hash_and_reveal_state() {
        $this->resetAfterTest();
        [$game, $cm, $first, $second, $attemptid, $hash] = $this->create_picture_attempt();
        $this->setUser($first);

        $this->assertSame($hash, \mod_game\hiddenpicture::get_attachment_hash($cm->id, $attemptid));
        $state = \mod_game\hiddenpicture::get_picture_state($cm->id, $attemptid);
        $this->assertFalse($state->found);
        $this->assertEquals(2, $state->cols);
        $this->assertEquals(2, $state->rows);
        $this->assertEquals([1], array_keys($state->visiblecells));
        $this->assertEquals([1, 2], array_keys($state->numberedcells));
    }

    /**
     * A different enrolled student cannot access the picture for another user's attempt.
     */
    public function test_other_user_cannot_get_picture() {
        $this->resetAfterTest();
        [$game, $cm, $first, $second, $attemptid] = $this->create_picture_attempt();
        $this->setUser($second);

        $this->expectException(moodle_exception::class);
        \mod_game\hiddenpicture::get_attachment_hash($cm->id, $attemptid);
    }

    /**
     * A player cannot use a different Game course module to select an attempt.
     */
    public function test_attempt_from_another_game_is_rejected() {
        $this->resetAfterTest();
        [$game, $cm, $first, $second, $attemptid] = $this->create_picture_attempt();
        $course = get_course($cm->course);
        $othergame = $this->getDataGenerator()->create_module('game', [
            'course' => $course->id,
            'name' => 'Other picture game',
            'gamekind' => 'hiddenpicture',
            'param1' => 2,
            'param2' => 2,
        ]);
        $othercm = get_coursemodule_from_instance('game', $othergame->id, $course->id);
        $this->setUser($first);

        $this->expectException(moodle_exception::class);
        \mod_game\hiddenpicture::get_attachment_hash($othercm->id, $attemptid);
    }

    /**
     * A successfully guessed main question reveals the entire picture for its owner.
     */
    public function test_correct_main_guess_reveals_picture() {
        global $DB;

        $this->resetAfterTest();
        [$game, $cm, $first, $second, $attemptid] = $this->create_picture_attempt();
        $this->setUser($first);
        $DB->set_field('game_hiddenpicture', 'found', 1, ['id' => $attemptid]);
        $state = \mod_game\hiddenpicture::get_picture_state($cm->id, $attemptid);
        $this->assertTrue($state->found);
    }
}
