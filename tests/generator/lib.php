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
 * mod_gmae data generator.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2019 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_game data generator class.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2019 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_game_generator extends testing_module_generator {

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
    public function create_glossary_content($glossary, $record = array(), $concept, $definition) {
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
}
