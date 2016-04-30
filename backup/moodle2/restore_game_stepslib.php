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
 * @package mod_game
 * @subpackage backup-moodle2
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_game_activity_task
 */

/**
 * Structure step to restore one game activity
 */
class restore_game_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('game', '/activity/game');
        $paths[] = new restore_path_element('game_export_html', '/activity/game/game_export_htmls/game_export_html');
        $paths[] = new restore_path_element('game_export_javame', '/activity/game/game_export_htmls/game_export_javame');
        $paths[] = new restore_path_element(
            'game_bookquiz_question', '/activity/game/game_bookquiz_questions/game_bookquiz_question');
        if ($userinfo) {
            $paths[] = new restore_path_element('game_grade', '/activity/game/game_grades/game_grade');
            $paths[] = new restore_path_element('game_repetition', '/activity/game/game_repetiotions/game_repetition');
            $paths[] = new restore_path_element('game_attempt', '/activity/game/game_attempts/game_attempt');
            $paths[] = new restore_path_element('game_query', '/activity/game/game_querys/game_query');
            $paths[] = new restore_path_element('game_bookquiz', '/activity/game/game_bookquizs/game_bookquiz');
            $paths[] = new restore_path_element('game_bookquiz_chapter',
                '/activity/game/game_bookquiz_chapters/game_bookquiz_chapter');
            $paths[] = new restore_path_element('game_cross', '/activity/game/game_crosss/game_cross');
            $paths[] = new restore_path_element('game_cryptex', '/activity/game/game_cryptexs/game_cryptex');
            $paths[] = new restore_path_element('game_hangman', '/activity/game/game_hangmans/game_hangman');
            $paths[] = new restore_path_element('game_hiddenpicture', '/activity/game/game_hiddenpictures/game_hiddenpicture');
            $paths[] = new restore_path_element('game_millionaire', '/activity/game/game_millionaires/game_millionaire');
            $paths[] = new restore_path_element('game_snake', '/activity/game/game_snakes/game_snake');
            $paths[] = new restore_path_element('game_sudoku', '/activity/game/game_sudokus/game_sudoku');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_game($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the game record.
        $newitemid = $DB->insert_record('game', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_game_export_html($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');
        if ($data->id != 0) {
            $DB->insert_record('game_export_html', $data);
        }
    }

    protected function process_game_export_javame($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');
        if ($data->id != 0) {
            $DB->insert_record('game_export_javame', $data);
        }
    }

    protected function process_game_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->gameid = $this->get_new_parentid('game');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('game_grades', $data);
    }

    protected function process_game_repetition($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->gameid = $this->get_new_parentid('game');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('game_repetitions', $data);
    }

    protected function process_game_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->gameid = $this->get_new_parentid('game');
        $data->userid = $this->get_mappingid('user', $data->userid);

        if (!isset( $data->timestart)) {
            $data->timestart = 0;
        }
        if (!isset( $data->timefinish)) {
            $data->timefinish = 0;
        }
        if (!isset( $data->timelastattempt)) {
            $data->timelastattempt = 0;
        }

        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timefinish = $this->apply_date_offset($data->timefinish);
        $data->timelastattempt = $this->apply_date_offset($data->timelastattempt);

        $newitemid = $DB->insert_record('game_attempts', $data);
        $this->set_mapping('game_attempt', $oldid, $newitemid);
    }

    protected function process_game_query($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->gameid = $this->get_new_parentid('game');
        $data->attemptid = get_mappingid('game_attempt', $data->attemptid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('game_queries', $data);
        $this->set_mapping('game_query', $oldid, $newitemid);
    }

    protected function process_game_bookquiz($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');

        $DB->insert_record('game_bookquiz', $data);
    }

    protected function process_game_bookquiz_chapter($data) {
        global $DB;

        $data = (object)$data;

        $data->gameid = $this->get_new_parentid('game');

        $DB->insert_record('game_bookquiz_chapters', $data);
    }

    protected function process_game_bookquiz_question($data) {
        global $DB;

        $data = (object)$data;

        $data->gameid = $this->get_new_parentid('game');

        $DB->insert_record('game_bookquiz_questions', $data);
    }

    protected function process_game_cross($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');

        $DB->insert_record('game_cross', $data);
    }

    protected function process_game_cryptex($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');

        $DB->insert_record('game_cryptex', $data);
    }

    protected function process_game_hangman($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');
        $data->queryid = $this->get_mappingid('game_query', $data->queryid);

        $DB->insert_record('game_hangman', $data);
    }

    protected function process_game_hiddenpicture($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');

        $DB->insert_record('game_hiddenpicture', $data);
    }

    protected function process_game_millionaire($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');
        $data->queryid = $this->get_mappingid('game_query', $data->queryid);

        $DB->insert_record('game_millionaire', $data);
    }

    protected function process_game_snake($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');
        $data->queryid = $this->get_mappingid('game_query', $data->queryid);

        $DB->insert_record('game_snakes', $data);
    }

    protected function process_game_sudoku($data) {
        global $DB;

        $data = (object)$data;

        $data->id = $this->get_new_parentid('game');

        $DB->insert_record('game_sudoku', $data);
    }

    protected function after_execute() {
        // Add Game related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_game', 'snakes_file', null);
        $this->add_related_files('mod_game', 'snakes_board', null);
    }
}
