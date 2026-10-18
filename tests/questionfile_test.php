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
 * Tests for the question file check of the game.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_game\questionfile
 */
final class questionfile_test extends \advanced_testcase {
    /**
     * Create a game that uses one of two questions.
     *
     * @return array The game, the used question and the unused question.
     */
    private function create_game(): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $game = $this->getDataGenerator()->create_module('game', ['course' => $course->id, 'kind' => 'hangman']);
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();
        $used = $generator->create_question('shortanswer', null, ['category' => $category->id]);
        $unused = $generator->create_question('shortanswer', null, ['category' => $category->id]);
        $DB->insert_record('game_queries', ['attemptid' => 1, 'gameid' => $game->id, 'questionid' => $used->id]);
        return [$game, $used, $unused];
    }

    /**
     * Files of a question that is not used by the game are not served.
     */
    public function test_is_used_rejects_unused_question(): void {
        global $DB;
        $this->resetAfterTest();
        [$game, $used, $unused] = $this->create_game();

        $this->assertFalse(questionfile::is_used($game->id, 'questiontext', $unused->id));
        $answerid = $DB->get_field('question_answers', 'id', ['question' => $unused->id], IGNORE_MULTIPLE);
        $this->assertFalse(questionfile::is_used($game->id, 'answer', $answerid));
    }

    /**
     * Files of a question that is used by the game are served.
     */
    public function test_is_used_accepts_used_question(): void {
        global $DB;
        $this->resetAfterTest();
        [$game, $used, $unused] = $this->create_game();

        $this->assertTrue(questionfile::is_used($game->id, 'questiontext', $used->id));
        $answerid = $DB->get_field('question_answers', 'id', ['question' => $used->id], IGNORE_MULTIPLE);
        $this->assertTrue(questionfile::is_used($game->id, 'answer', $answerid));
    }

    /**
     * Teachers who can view the reports also get the files of questions that are not used in an attempt yet.
     */
    public function test_can_serve_for_teacher_and_student(): void {
        $this->resetAfterTest();
        [$game, $used, $unused] = $this->create_game();
        $context = \context_module::instance($game->cmid);
        $teacher = $this->getDataGenerator()->create_and_enrol(get_course($game->course), 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol(get_course($game->course), 'student');

        $this->setUser($student);
        $this->assertFalse(questionfile::can_serve($context, $game->id, 'questiontext', $unused->id));
        $this->assertTrue(questionfile::can_serve($context, $game->id, 'questiontext', $used->id));

        $this->setUser($teacher);
        $this->assertTrue(questionfile::can_serve($context, $game->id, 'questiontext', $unused->id));
    }
}
