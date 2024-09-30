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
 * mod_game generator tests
 *
 * @package    mod_game
 * @category   test
 * @copyright  2019 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/game/cross/cross_class.php');
require_once($CFG->dirroot . '/mod/game/cross/crossdb_class.php');
require_once($CFG->dirroot . '/mod/game/lib.php');
require_once($CFG->dirroot . '/mod/game/locallib.php');

/**
 * Genarator tests class for mod_game.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_game_generator_testcase extends advanced_testcase {

    /**
     * Test for create instance.
     */
    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('game', ['course' => $course->id]));
        $game = $this->getDataGenerator()->create_module('game',
            ['course' => $course, 'kind' => 'hangman', 'name' => 'hangman']);
        $records = $DB->get_records('game', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertTrue(array_key_exists($game->id, $records));

        $params = ['course' => $course->id, 'name' => 'Another game', 'kind' => 'hangman'];
        $game = $this->getDataGenerator()->create_module('game', $params);
        $records = $DB->get_records('game', ['course' => $course->id], 'id');
        $this->assertEquals(2, count($records));
        $this->assertEquals('Another game', $records[$game->id]->name);
    }

    /**
     * Test for creating a crossword.
     */
    public function test_createcross_instance() {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('game', ['course' => $course->id]));
        $glossary = $this->getDataGenerator()->create_module('glossary', ['course' => $course, 'name' => 'numbers']);
        $glossarygenerator = $this->getDataGenerator()->get_plugin_generator('mod_game');

        $glossarygenerator->create_glossary_content($glossary, [], 'ONE', 'ONE');
        $glossarygenerator->create_glossary_content($glossary, [], 'TWO', 'TWO');
        $glossarygenerator->create_glossary_content($glossary, [], 'THREE', 'THREE');
        $glossarygenerator->create_glossary_content($glossary, [], 'FOUR', 'FOUR');
        $glossarygenerator->create_glossary_content($glossary, [], 'FIVE', 'FIVE');
        $glossarygenerator->create_glossary_content($glossary, [], 'SIX', 'SIX');
        $glossarygenerator->create_glossary_content($glossary, [], 'SEVEN', 'SEVEN');

        $game = $this->getDataGenerator()->create_module('game',
            ['course' => $course, 'gamekind' => 'cross', 'name' => 'cross',
            'sourcemodule' => 'glossary', 'glossaryid' => $glossary->id]);
        $records = $DB->get_records('game', ['course' => $course->id], 'id');
        $this->assertEquals(1, count($records));
        $this->assertTrue(array_key_exists($game->id, $records));
        $cm = get_coursemodule_from_instance('game', $game->id, $course->id);
        $context = game_get_context_module_instance( $cm->id);
        $cross = new CrossDB();
        $answers = [ 'ONE' => 'ONE', 'TWO' => 'TWO', 'THREE' => 'THREE', 'FOUR' => 'FOUR'];
        $reps = [];
        $cross->setwords( $answers, 0, $reps);
        $cross->computedata( $crossm, $crossd, $letters, $minwords = 0, $maxwords = 0, $mtimelimit = 3);
        $this->assertEquals(38, $cross->mbestscore);

        $_GET['q'] = $game->id;
        ob_start();
        require_once($CFG->dirroot . '/mod/game/attempt.php');
        ob_end_clean();

        $params = ['course' => $course->id, 'name' => 'Another game', 'kind' => 'hangman'];
        $game = $this->getDataGenerator()->create_module('game', $params);
        $records = $DB->get_records('game', ['course' => $course->id], 'id');
        $this->assertEquals(2, count($records));
        $this->assertEquals('Another game', $records[$game->id]->name);
    }
}
