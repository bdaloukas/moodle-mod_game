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
 * @author bdaloukas
 * @version $Id: backup_game_stepslib.php,v 1.5 2012/07/25 11:16:04 bdaloukas Exp $
 */

/**
 * Define all the backup steps that will be used by the backup_game_activity_task
 */

/**
 * Define the complete game structure for backup, with file and id annotations
 */
class backup_game_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $game = new backup_nested_element('game', array('id'), array(
            'name', 'sourcemodule', 'timeopen', 'timeclose', 'quizid',
            'glossaryid', 'glossarycategoryid', 'questioncategoryid', 'bookid',
            'gamekind', 'param1', 'param2', 'param3',
            'param4', 'param5', 'param6', 'param7', 'param8', 'param9',
            'shuffle', 'timemodified', 'toptext', 'bottomtext',
            'grademethod', 'grade', 'decimalpoints', 'popup',
            'review', 'attempts', 'glossaryid2', 'glossarycategoryid2',
            'language', 'subcategories', 'maxattempts'
            ));

        $exporthtmls = new backup_nested_element('game_export_htmls');
        $exporthtml = new backup_nested_element('game_export_html', array('id'), array(
            'filename', 'title', 'checkbutton', 'printbutton', 'inputsize', 'maxpicturewidth', 'maxpictureheight'));

        $exportjavames = new backup_nested_element('game_export_javames');
        $exportjavame = new backup_nested_element('game_export_javame', array('id'), array(
            'filename', 'icon', 'createdby', 'vendor', 'name', 'description', 'version',
            'maxpicturewidth', 'maxpictureheight'));

        $grades = new backup_nested_element('game_grades');
        $grade = new backup_nested_element('game_grade', array('id'), array(
            'userid', 'score', 'timemodified'));

        $repetitions = new backup_nested_element('game_repetitions');
        $repetition = new backup_nested_element('game_repetition', array('id'), array(
            'userid', 'questionid', 'glossaryentryid', 'repetitions'));

        $attempts = new backup_nested_element('game_attempts');
        $attempt = new backup_nested_element('game_attempt', array('id'), array(
           'userid', 'timestart', 'timefinish', 'timelastattempt', 'lastip',
            'lastremotehost', 'preview', 'attempt', 'score', 'attempts', 'language'));

        $querys = new backup_nested_element('game_queries');
        $query = new backup_nested_element('game_query', array('id'), array(
           'gamekind', 'userid', 'sourcemodule', 'questionid', 'glossaryentryid',
            'questiontext', 'score', 'timelastattempt', 'studentanswer', 'col', 'row',
            'horizontal', 'answertext', 'correct', 'attachment', 'answerid', 'tries'));

        $bookquizs = new backup_nested_element('game_bookquizs');
        $bookquiz = new backup_nested_element('game_bookquiz', array('id'), array('lastchapterid'));

        $bookquizchapters = new backup_nested_element('game_bookquiz_chapters');
        $bookquizchapter = new backup_nested_element('game_bookquiz_chapter', array('id'), array( 'chapterid'));

        $bookquizquestions = new backup_nested_element('game_bookquiz_questions');
        $bookquizquestion = new backup_nested_element('game_bookquiz_question', array('id'), array(
            'chapterid', 'questioncategoryid'));

        $crosss = new backup_nested_element('game_crosss');
        $cross = new backup_nested_element('game_cross', array('id'), array(
            'cols', 'rows', 'words', 'wordsall', 'createscore', 'createtries',
            'createtimelimit', 'createconnectors', 'createfilleds', 'createspaces', 'triesplay'));

        $cryptexs = new backup_nested_element('game_cryptexs');
        $cryptex = new backup_nested_element('game_cryptex', array('id'), array('letters'));

        $hangmans = new backup_nested_element('game_hangmans');
        $hangman = new backup_nested_element('game_hangman', array('id'), array(
            'queryid', 'letters', 'allletters', 'try', 'maxtries', 'finishedword',
            'corrects', 'iscorrect'));

        $hiddenpictures = new backup_nested_element('game_hiddenpictures');
        $hiddenpicture = new backup_nested_element('game_hiddenpicture', array('id'), array('correct', 'wrong', 'found'));

        $millionaires = new backup_nested_element('game_millionaires');
        $millionaire = new backup_nested_element('game_millionaire', array('id'), array('queryid', 'state', 'level'));

        $snakes = new backup_nested_element('game_snakes');
        $snake = new backup_nested_element('game_snake', array('id'), array('snakesdatabaseid', 'position', 'queryid', 'dice'));

        $sudokus = new backup_nested_element('game_sudokus');
        $sudoku = new backup_nested_element('game_sudoku', array('id'), array('level', 'data', 'opened', 'guess'));

        // Build the tree.
        $game->add_child( $bookquizquestions);
        $bookquizquestions->add_child( $bookquizquestion);

        $game->add_child( $exporthtmls);
        $exporthtmls->add_child( $exporthtml);

        $game->add_child( $exportjavames);
        $exportjavames->add_child( $exportjavame);

        $game->add_child( $grades);
        $grades->add_child( $grade);

        $game->add_child( $repetitions);
        $repetitions->add_child( $repetition);

        // All these source definitions only happen if we are including user info.
        if ($userinfo) {
            $game->add_child( $attempts);
            $attempts->add_child( $attempt);

            $attempts->add_child( $querys);
            $querys->add_child( $query);

            $attempts->add_child( $bookquizs);
            $bookquizs->add_child( $bookquiz);

            $game->add_child( $bookquizchapters);
            $bookquizchapters->add_child($bookquizchapter);

            $attempts->add_child( $crosss);
            $crosss->add_child( $cross);

            $attempts->add_child( $cryptexs);
            $cryptexs->add_child( $cryptex);

            $attempts->add_child( $hangmans);
            $hangmans->add_child( $hangman);

            $attempts->add_child( $hiddenpictures);
            $hiddenpictures->add_child( $hiddenpicture);

            $attempts->add_child( $millionaires);
            $millionaires->add_child( $millionaire);

            $attempts->add_child( $snakes);
            $snakes->add_child( $snake);

            $attempts->add_child( $sudokus);
            $sudokus->add_child( $sudoku);
        }

        // Define sources.
        $game->set_source_table('game', array('id' => backup::VAR_ACTIVITYID));
        $bookquizquestion->set_source_table('game_bookquiz_questions', array('gameid' => backup::VAR_ACTIVITYID));
        $exporthtml->set_source_table('game_export_html', array('id' => backup::VAR_ACTIVITYID));
        $exportjavame->set_source_table('game_export_javame', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $grade->set_source_table('game_grades', array('gameid' => backup::VAR_ACTIVITYID));
            $repetition->set_source_table('game_repetitions', array('gameid' => backup::VAR_ACTIVITYID));

            $attempt->set_source_table('game_attempts', array( 'gameid' => backup::VAR_ACTIVITYID));
            $attempt->set_source_table('game_queries', array( 'attemptid' => backup::VAR_PARENTID));

            $bookquiz->set_source_table('game_bookquiz', array( 'id' => backup::VAR_ACTIVITYID));
            $bookquizchapter->set_source_table('game_bookquiz_chapters', array( 'id' => backup::VAR_PARENTID));

            $cross->set_source_table('game_cross', array( 'id' => backup::VAR_PARENTID));
            $cryptex->set_source_table('game_cryptex', array( 'id' => backup::VAR_PARENTID));
            $hangman->set_source_table('game_hangman', array( 'id' => backup::VAR_PARENTID));
            $hiddenpicture->set_source_table('game_hiddenpicture', array( 'id' => backup::VAR_PARENTID));
            $millionaire->set_source_table('game_millionaire', array( 'id' => backup::VAR_PARENTID));
            $snake->set_source_table('game_snakes', array( 'id' => backup::VAR_PARENTID));
            $sudoku->set_source_table('game_sudoku', array( 'id' => backup::VAR_PARENTID));
        }
        // Define id annotations.
        $attempt->annotate_ids('user', 'userid');
        $grade->annotate_ids('user', 'userid');
        $repetition->annotate_ids('user', 'userid');
        $repetition->annotate_ids('question', 'questionid');
        $repetition->annotate_ids('glossary_entry', 'glossaryentryid');
        $query->annotate_ids('user', 'userid');
        $query->annotate_ids('question', 'questionid');
        $query->annotate_ids('glossary_enrty', 'glossaryentryid');
        $query->annotate_ids('question_answer', 'answerid');

        $bookquizquestion->annotate_ids('book_chapter', 'chapterid');
        $bookquizquestion->annotate_ids('question_category', 'questioncategoryid');
        $bookquizchapter->annotate_ids('book_chapter', 'chapterid');
        $hangman->annotate_ids('game_query', 'queryid');
        $millionaire->annotate_ids('game_query', 'queryid');

        // Define file annotations.
        $game->annotate_files('mod_game', 'snakes_file', null); // This file area hasn't itemid.
        $game->annotate_files('mod_game', 'snakes_board', null); // This file area hasn't itemid.

        // Return the root element (game), wrapped into standard activity structure.
        return $this->prepare_activity_structure( $game);
    }
}
