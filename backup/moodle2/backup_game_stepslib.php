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
 * Define all the backup steps that will be used by the backup_game_activity_task
 *
 * @package mod_game
 * @subpackage backup-moodle2
 * @copyright 2007 Vasilis Daloukas
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete game structure for backup, with file and id annotations
 *
 * @copyright 2007 Vasilis Daloukas
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_game_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the needed structures.
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated (exclude fields: course).
        $game = new backup_nested_element('game', ['id'],
            ['name', 'intro', 'introformat', 'sourcemodule', 'timeopen', 'timeclose', 'quizid',
            'glossaryid', 'glossarycategoryid', 'questioncategoryid', 'bookid',
            'gamekind', 'param1', 'param2', 'param3',
            'param4', 'param5', 'param6', 'param7', 'param8', 'param9', 'param10',
            'shuffle', 'timemodified', 'toptext', 'bottomtext',
            'grademethod', 'grade', 'decimalpoints', 'popup',
            'review', 'attempts', 'glossaryid2', 'glossarycategoryid2',
            'language', 'subcategories', 'maxattempts', 'userlanguage', 'disablesummarize', 'glossaryonlyapproved',
            'completionattemptsexhausted', 'completionpass', 'highscore']);

        $exporthtmls = new backup_nested_element('game_export_htmls');
        $exporthtml = new backup_nested_element('game_export_html', ['id'],
            ['filename', 'title', 'checkbutton', 'printbutton', 'inputsize', 'maxpicturewidth', 'maxpictureheight', 'type']);

        $exportjavames = new backup_nested_element('game_export_javames');
        $exportjavame = new backup_nested_element('game_export_javame', ['id'],
            ['filename', 'icon', 'createdby', 'vendor', 'name', 'description', 'version',
            'maxpicturewidth', 'maxpictureheight', 'type']);

        $grades = new backup_nested_element('game_grades');
        $grade = new backup_nested_element('game_grade', ['id'], ['userid', 'score', 'timemodified']);

        $repetitions = new backup_nested_element('game_repetitions');
        $repetition = new backup_nested_element('game_repetition', ['id'],
            ['userid', 'questionid', 'glossaryentryid', 'repetitions']);

        $attempts = new backup_nested_element('game_attempts');
        $attempt = new backup_nested_element('game_attempt', ['id'],
           ['userid', 'timestart', 'timefinish', 'timelastattempt', 'lastip',
            'lastremotehost', 'preview', 'attempt', 'score', 'attempts', 'language']);

        $querys = new backup_nested_element('game_queries');
        $query = new backup_nested_element('game_query', ['id'],
           ['gamekind', 'userid', 'sourcemodule', 'questionid', 'glossaryentryid',
            'questiontext', 'score', 'timelastattempt', 'studentanswer', 'col', 'row',
            'horizontal', 'answertext', 'correct', 'attachment', 'answerid', 'tries']);

        $bookquizquestions = new backup_nested_element('game_bookquiz_questions');
        $bookquizquestion = new backup_nested_element('game_bookquiz_question', ['id'],
            ['chapterid', 'questioncategoryid']);

        // The games attemtps.
        $bookquiz = new backup_nested_element('game_bookquiz', ['id'], ['lastchapterid']);
        $bookquizchapters = new backup_nested_element('game_bookquiz_chapters');
        $bookquizchapter = new backup_nested_element('game_bookquiz_chapter', ['id'], ['chapterid']);
        $cross = new backup_nested_element('game_cross', ['id'],
            ['usedcols', 'usedrows', 'words', 'wordsall', 'createscore', 'createtries',
            'createtimelimit', 'createconnectors', 'createfilleds', 'createspaces', 'triesplay']);
        $cryptex = new backup_nested_element('game_cryptex', ['id'], ['letters']);
        $hangman = new backup_nested_element('game_hangman', ['id'],
            ['queryid', 'letters', 'allletters', 'try', 'maxtries', 'finishedword',
            'corrects', 'iscorrect']);
        $hiddenpicture = new backup_nested_element('game_hiddenpicture', ['id'], ['correct', 'wrong', 'found']);
        $millionaire = new backup_nested_element('game_millionaire', ['id'], ['queryid', 'state', 'level']);
        $snake = new backup_nested_element('game_snake', ['id'], ['snakesdatabaseid', 'position', 'queryid', 'dice']);
        $sudoku = new backup_nested_element('game_sudoku', ['id'], ['level', 'data', 'opened', 'guess']);

        // Build the tree.
        $game->add_child( $bookquizquestions);
        $bookquizquestions->add_child( $bookquizquestion);

        $game->add_child( $exporthtmls);
        $exporthtmls->add_child( $exporthtml);

        $game->add_child( $exportjavames);
        $exportjavames->add_child( $exportjavame);

        // All these source definitions only happen if we are including user info.
        if ($userinfo) {
            $game->add_child( $grades);
            $grades->add_child( $grade);

            $game->add_child( $repetitions);
            $repetitions->add_child( $repetition);

            $game->add_child( $attempts);
            $attempts->add_child( $attempt);

            $attempt->add_child( $querys);
            $querys->add_child( $query);

            // All games.
            $attempt->add_child( $bookquiz);
            $attempt->add_child( $bookquizchapters);
            $bookquizchapters->add_child($bookquizchapter);
            $attempt->add_child( $cross);
            $attempt->add_child( $cryptex);
            $attempt->add_child( $hangman);
            $attempt->add_child( $hiddenpicture);
            $attempt->add_child( $millionaire);
            $attempt->add_child( $snake);
            $attempt->add_child( $sudoku);
        }

        // Define sources.
        $game->set_source_table('game', ['id' => backup::VAR_ACTIVITYID]);
        $bookquizquestion->set_source_table('game_bookquiz_questions', ['gameid' => backup::VAR_ACTIVITYID]);
        $exporthtml->set_source_table('game_export_html', ['id' => backup::VAR_ACTIVITYID]);
        $exportjavame->set_source_table('game_export_javame', ['id' => backup::VAR_ACTIVITYID]);

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $grade->set_source_table('game_grades', ['gameid' => backup::VAR_ACTIVITYID]);
            $repetition->set_source_table('game_repetitions', ['gameid' => backup::VAR_ACTIVITYID]);

            $attempt->set_source_table('game_attempts', ['gameid' => backup::VAR_ACTIVITYID]);
            $query->set_source_table('game_queries', [ 'attemptid' => backup::VAR_PARENTID]);

            $bookquiz->set_source_table('game_bookquiz', [ 'id' => backup::VAR_ACTIVITYID]);
            $bookquizchapter->set_source_table('game_bookquiz_chapters', [ 'id' => backup::VAR_PARENTID]);

            $cross->set_source_table('game_cross', [ 'id' => backup::VAR_PARENTID]);
            $cryptex->set_source_table('game_cryptex', [ 'id' => backup::VAR_PARENTID]);
            $hangman->set_source_table('game_hangman', [ 'id' => backup::VAR_PARENTID]);
            $hiddenpicture->set_source_table('game_hiddenpicture', [ 'id' => backup::VAR_PARENTID]);
            $millionaire->set_source_table('game_millionaire', [ 'id' => backup::VAR_PARENTID]);
            $snake->set_source_table('game_snakes', [ 'id' => backup::VAR_PARENTID]);
            $sudoku->set_source_table('game_sudoku', [ 'id' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $attempt->annotate_ids('user', 'userid');
        $grade->annotate_ids('user', 'userid');
        $repetition->annotate_ids('user', 'userid');
        $repetition->annotate_ids('question', 'questionid');
        $repetition->annotate_ids('glossary_entries', 'glossaryentryid');
        $query->annotate_ids('user', 'userid');
        $query->annotate_ids('question', 'questionid');
        $query->annotate_ids('glossary_entries', 'glossaryentryid');
        $query->annotate_ids('question_answer', 'answerid');

        $bookquizquestion->annotate_ids('book_chapter', 'chapterid');
        $bookquizquestion->annotate_ids('question_category', 'questioncategoryid');
        $bookquizchapter->annotate_ids('book_chapter', 'chapterid');

        // Define file annotations.
        $game->annotate_files('mod_game', 'snakes_file', null); // This file area hasn't itemid.
        $game->annotate_files('mod_game', 'snakes_board', null); // This file area hasn't itemid.

        // Return the root element (game), wrapped into standard activity structure.
        return $this->prepare_activity_structure( $game);
    }
}
