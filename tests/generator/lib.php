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
 * mod_game data generator.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2019 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * mod_game data generator class.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2019 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_game_generator extends testing_module_generator {

    /** @var define some reasonalbe default settings for some gamekinds. */
    private static $defaultparams = [
        'bookquiz' => [
            'param3' => 0 // Bookquiz layout.
        ],
        'hangman' => [
            'param1' => 0, // Show first letter of hangman.
            'param2' => 0, // Show last letter of hangman.
            'param3' => 1, // Select the images of hangman.
            'param4' => 0, // Number of words per game.
            'param5' => 1, // Show the questions?
            'param6' => 0, // Show the correct answer after the end.
            'param7' => 0, // Allow spaces in words.
            'param8' => 0, // Allow the symbol - in words.
            'param10' => 6, // Maximum number or errors (have to be images named hangman_0.jpg, hangman_1.jpg, ...).
        ],
        'cross' => [
            'param1' => 5, // Maximum number of cols/rows.
            'param4' => 2, // Minimum number of words.
            'param2' => 4, // Maximum number of words.
            'param7' => 0, // Allow spaces in words.
            'param3' => 0, // Layout.
            'param6' => 0, // Disables text-transform:uppercase in CSS.
            'param8' => 60 // Maximum compute time in seconds.
        ],
        'cryptex' => [
            'param1' => 5, // Maximum number of cols/rows.
            'param4' => 2, // Minimum number of words.
            'param2' => 4, // Maximum number of words.
            'param7' => 0, // Allow spaces in words.
            'param3' => 0, // Layout.
            'param8' => 1, // Max tries.
            'param3' => 60 // Maximum compute time in seconds.
        ]
    ];

    /**
     * Creates instance of game record with default values.
     *
     * @param stdClass $record
     * @param array $options
     *
     * @return the game instance
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->libdir.'/resourcelib.php');

        // Add default values for game.
        $record = (array)$record + array(
            'name' => 'Hangman',
            'gamekind' => 'hangman',
        );

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Creates a glossary entry.
     *
     * @param stdClass $glossary
     * @param stdClass $record
     * @param string $concept
     * @param string $definition
     *
     * @return the glossary_entries
     */
    public function create_glossary_content($glossary, $record, $concept, $definition) {
        global $DB, $USER, $CFG;

        $now = time();
        $record = (array)$record + array(
            'glossaryid' => $glossary->id,
            'timecreated' => $now,
            'timemodified' => $now,
            'userid' => $USER->id,
            'concept' => $concept,
            'definition' => $definition,
            'definitionformat' => FORMAT_MOODLE,
            'definitiontrust' => 0,
            'usedynalink' => $CFG->glossary_linkentries,
            'casesensitive' => $CFG->glossary_casesensitive,
            'fullmatch' => $CFG->glossary_fullmatch
        );
        if (!isset($record['teacherentry']) || !isset($record['approved'])) {
            $context = context_module::instance($glossary->cmid);
            if (!isset($record['teacherentry'])) {
                $record['teacherentry'] = has_capability('mod/glossary:manageentries', $context, $record['userid']);
            }
            if (!isset($record['approved'])) {
                $defaultapproval = $glossary->defaultapproval;
                $record['approved'] = ($defaultapproval || has_capability('mod/glossary:approve', $context));
            }
        }

        $id = $DB->insert_record('glossary_entries', $record);

        return $DB->get_record('glossary_entries', array('id' => $id), '*', MUST_EXIST);
    }

    /**
     * Get default settings for a gamekind.
     *
     * @param string $gamekind
     * @return array
     */
    private function get_default_params($gamekind) {

        $default = [
            'param1' => 0,
            'param2' => 0,
            'param3' => 0,
            'param4' => 0,
            'param5' => 0,
            'param6' => 0,
            'param7' => 0,
            'param8' => 0,
            'param9' => '',
            'param10' => 0,
        ];

        if (isset(self::$defaultparams[$gamekind])) {
            $default = array_merge($default, self::$defaultparams[$gamekind]);
        }

        return $default;

    }

    /**
     * Create an instance of a game.
     *
     * This is method with an extended functionallity from the mebis group.
     * You may generate special game types with verious option here.
     *
     * May be deleted, if vendor of this plugin extends functionallity of
     * method create_instance above.
     *
     * @param array $record
     * @param array $options
     * @return object an instance of mod_game
     * @throws \moodle_exception
     */
    public function create_mbsgame_instance($record = null, array $options = null) {
        static $count = 1;

        $defaultvalues = [
            'name' => 'Game '. $count,
            'sourcemodule' => 'glossary',
            'timeopen' => 0,
            'timeclose' => 0,
            'gamekind' => 'hangman',
            'shuffle' => 1,
            'toptext' => '',
            'bottomtext' => '',
            'grademethod' => 1,
            'grade' => 100,
            'maxattempts' => 0,
            'disablesummarize' => 0,
            'glossaryonlyapproved' => 0
        ];

        $record = array_merge($defaultvalues, $record);
        $record = array_merge($this->get_default_params($record['gamekind']), $record);

        if (!isset($record['course'])) {
            throw new \moodle_exception('missing course');
        }

        if ($record['sourcemodule'] == 'glossary') {
            if (!isset($record['glossaryid'])) {
                throw new \moodle_exception('missing glossary id');
            }
        }

        if ($record['sourcemodule'] == 'question') {
            if (!isset($record['questioncategoryid'])) {
                throw new \moodle_exception('missing question category id');
            }
        }

        if ($record['sourcemodule'] == 'quiz') {
            if ($record['gamekind'] == 'bookquiz') {
                if (!isset($record['bookid'])) {
                    throw new \moodle_exception('missing book id');
                }
            } else {
                if (!isset($record['quizid'])) {
                    throw new \moodle_exception('missing quiz id');
                }
            }
        }

        $count++;
        return parent::create_instance($record, (array) $options);
    }

}
