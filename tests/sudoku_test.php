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
 * Regression tests for Sudoku creation and completion (Issue #90).
 *
 * @package    mod_game
 * @category   test
 * @copyright  2026 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/game/sudoku/play.php');

/**
 * Sudoku game logic tests.
 */
class mod_game_sudoku_testcase extends advanced_testcase {
    /**
     * The board must contain 81 encoded cells with its declared number of clues.
     */
    public function test_board_validation() {
        $board = str_repeat('A', 26) . str_repeat('1', 55);
        $this->assertTrue(game_sudoku_valid_board($board, 26));
        $this->assertFalse(game_sudoku_valid_board(str_repeat('0', 81), 0));
        $this->assertFalse(game_sudoku_valid_board(substr($board, 0, 80), 26));
        $this->assertFalse(game_sudoku_valid_board($board, 25));
    }

    /**
     * Only available questions get positions, including a single question.
     */
    public function test_select_positions_with_small_question_banks() {
        $closed = array_combine(range(27, 81), range(27, 81));
        foreach ([1, 3, 11] as $count) {
            $positions = game_sudoku_select_positions($closed, $count);
            $this->assertCount($count, $positions);
            $this->assertCount($count, array_unique($positions));
            foreach ($positions as $position) {
                $this->assertArrayHasKey($position, $closed);
            }
        }
        $this->assertSame([], game_sudoku_select_positions($closed, 0));
        $this->assertSame([], game_sudoku_select_positions($closed, 56));
    }

    /**
     * No-win for unanswered questions, unsolved cells or empty question sets.
     */
    public function test_win_requires_answers_and_complete_grid() {
        $board = str_repeat('A', 26) . str_repeat('1', 55);
        $questions = [27 => 101, 28 => 102, 29 => 103];
        $correct = [27 => 1, 28 => 1, 29 => 1];
        $guess = str_repeat(' ', 81);

        $this->assertFalse(game_sudoku_check_found_all_numbers($board, $guess, [], []));
        $this->assertFalse(game_sudoku_check_found_all_numbers($board, $guess, $questions, $correct));
        for ($pos = 30; $pos <= 81; $pos++) {
            $guess[$pos - 1] = '1';
        }
        $this->assertFalse(game_sudoku_check_found_all_numbers($board, $guess, $questions, [27 => 1, 28 => 1]));
        $this->assertTrue(game_sudoku_check_found_all_numbers($board, $guess, $questions, $correct));
        $guess[45] = '2';
        $this->assertFalse(game_sudoku_check_found_all_numbers($board, $guess, $questions, $correct));
    }
}
