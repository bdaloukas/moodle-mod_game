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
 * Privacy Subsystem implementation for mod_game.
 *
 * @package mod_game
 * @copyright 2018 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_game\privacy;

use \core_privacy\local\request\writer;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\deletion_criteria;
use \core_privacy\local\metadata\collection;
use \core_privacy\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/game/lib.php');
require_once($CFG->dirroot . '/mod/game/locallib.php');

/**
 * Privacy Subsystem implementation for mod_game.
 *
 * @copyright 2018 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin_provider interface.
    \core_privacy\local\request\plugin\provider {

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   collection  $items  The collection to add metadata to.
     * @return  collection  The array of metadata
     */
    public static function get_metadata(collection $items) : collection {
        // The table 'game' stores a record for each game.
        // It does not contain user personal data, but data is returned from it for contextual requirements.

        // The table 'game_attempts' stores a record of each game attempt.
        // It contains a userid which links to the user making the attempt and contains information about that attempt.
        $items->add_database_table('game_attempts', [
                'attempt'               => 'privacy:metadata:game_attempts:attempt',
                'preview'               => 'privacy:metadata:game_attempts:preview',
                'timestart'             => 'privacy:metadata:game_attempts:timestart',
                'timefinish'            => 'privacy:metadata:game_attempts:timefinish',
                'timelastattempt'          => 'privacy:metadata:game_attempts:timelastattempt',
                'score'             => 'privacy:metadata:game_attempts:score',
                'language'             => 'privacy:metadata:game_attempts:language',

                'cross_usedcols'               => 'privacy:metadata:game_attempts:cross_usedcols',
                'cross_usedrows'             => 'privacy:metadata:game_attempts:cross_usedrows',
                'cross_words'            => 'privacy:metadata:game_attempts:cross_words',
                'cross_wordsall'            => 'privacy:metadata:game_attempts:cross_wordsall',
                'cross_createscore'            => 'privacy:metadata:game_attempts:cross_createscore',
                'cross_createtries'            => 'privacy:metadata:game_attempts:cross_createtries',
                'cross_createlimit'            => 'privacy:metadata:game_attempts:cross_createlimit',
                'cross_createconnectors'            => 'privacy:metadata:game_attempts:cross_createconnectors',
                'cross_createfilleds'            => 'privacy:metadata:game_attempts:cross_createfilleds',
                'cross_createspaces'            => 'privacy:metadata:game_attempts:cross_createspaces',
                'cross_triesplay'            => 'privacy:metadata:game_attempts:cross_triesplay',

                'cryptex_letters'               => 'privacy:metadata:game_attempts:cryptex_letters',

                'hangman_queryid'               => 'privacy:metadata:game_attempts:hangman_queryid',
                'hangman_letters'               => 'privacy:metadata:game_attempts:hangman_letters',
                'hangman_allletters'               => 'privacy:metadata:game_attempts:hangman_allletters',
                'hangman_try'               => 'privacy:metadata:game_attempts:hangman_try',
                'hangman_maxtries'               => 'privacy:metadata:game_attempts:hangman_maxtries',
                'hangman_finishedword'               => 'privacy:metadata:game_attempts:hangman_finishedword',
                'hangman_corrects'               => 'privacy:metadata:game_attempts:hangman_corrects',
                'hangman_iscorrect'               => 'privacy:metadata:game_attempts:hangman_iscorrect',

                'hiddenpicture_correct'               => 'privacy:metadata:game_attempts:hiddenpicture_corect',
                'hiddenpicture_wrong'               => 'privacy:metadata:game_attempts:hiddenpicture_wrong',
                'hiddenpicture_found'               => 'privacy:metadata:game_attempts:hiddenpicture_found',

                'millionaire_queryid'               => 'privacy:metadata:game_attempts:millionaire_queryid',
                'millionaire_state'               => 'privacy:metadata:game_attempts:millionaire_state',
                'millionaire_level'               => 'privacy:metadata:game_attempts:millionaire_level',

                'snakes_snakesdatabaseid'               => 'privacy:metadata:game_attempts:snakes_snakesdatabaseid',
                'snakes_position'               => 'privacy:metadata:game_attempts:snakes_position',
                'snakes_queryid'               => 'privacy:metadata:game_attempts:snakes_queryid',
                'snakes_dice'               => 'privacy:metadata:game_attempts:snakes_dice',

                'sudoku_level'               => 'privacy:metadata:game_attempts:sudoku_level',
                'sudoku_data'               => 'privacy:metadata:game_attempts:sudoku_data',
                'sudoku_opened'               => 'privacy:metadata:game_attempts:sudoku_opened',
                'sudoku_guess'               => 'privacy:metadata:game_attempts:sudoku_guess',

            ], 'privacy:metadata:game_attempts');

        // The table 'game_bookquiz_chapters' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_bookquiz_questions' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_export_html' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_export_javame' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_grades' contains the current grade for each game/user combination.
        $items->add_database_table('game_grades', [
                'game'                  => 'privacy:metadata:game_grades:game',
                'userid'                => 'privacy:metadata:game_grades:userid',
                'score'                 => 'privacy:metadata:game_grades:score',
                'timemodified'          => 'privacy:metadata:game_grades:timemodified',
            ], 'privacy:metadata:game_grades');

        // The table 'game_queries' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_queries', [
                'id'               => 'privacy:metadata:game_queries:id',
                'attemptid'               => 'privacy:metadata:game_queries:attemptid',
                'questionid'               => 'privacy:metadata:game_queries:questionid',
                'glossaryentryid'               => 'privacy:metadata:game_queries:glossaryentryid',
                'questiontext'               => 'privacy:metadata:game_queries:questiontext',
                'score'               => 'privacy:metadata:game_queries:score',
                'timelastattempt'               => 'privacy:metadata:game_queries:timelastattempt',
                'studentanswer'               => 'privacy:metadata:game_queries:studentanswer',
                'mycol'               => 'privacy:metadata:game_queries:mycol',
                'myrow'               => 'privacy:metadata:game_queries:myrow',
                'horizontal'               => 'privacy:metadata:game_queries:horizontal',
                'answertext'               => 'privacy:metadata:game_queries:answertext',
                'correct'               => 'privacy:metadata:game_queries:correct',
                'attachment'               => 'privacy:metadata:game_queries:attachment',
                'answerid'               => 'privacy:metadata:game_queries:answerid',
                'tries'               => 'privacy:metadata:game_queries:tries',
            ], 'privacy:metadata:game_queries');

        // The table 'game_repetitions' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_repetitions', [
                'id'               => 'privacy:metadata:game_repetitions:id',
                'gameid'               => 'privacy:metadata:game_repetitions:gameid',
                'userid'               => 'privacy:metadata:game_repetitions:userid',
                'questionid'               => 'privacy:metadata:game_repetitions:questionid',
                'glossaryentryid'               => 'privacy:metadata:game_repetitions:glossaryentryid',
                'repetitions'               => 'privacy:metadata:game_repetitions:repetitions',
            ], 'privacy:metadata:game_repetitions');

        // The table 'game_snakes_database' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_sudoku_database' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // Although the game supports the core_completion API and defines custom completion items, these will be
        // noted by the manager as all activity modules are capable of supporting this functionality.

        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a game, or been involved with manual marking
     * and/or grading of a game.
     *
     * @param   int             $userid The user to search.
     * @return  contextlist     $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        // Select the context of any game attempt where a user has an attempt, plus the related usages.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {game} g ON g.id = cm.instance
                  JOIN {game_attempts} ga ON ga.gameid = g.id
            WHERE ga.userid = :userid";

        $params = array(
                    'contextlevel'      => CONTEXT_MODULE,
                    'modname'           => 'game',
                    'userid'          => $userid
            );

        $resultset = new contextlist();
        $resultset->add_from_sql($sql, $params);

        return $resultset;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $CFG, $DB;

        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    g.*,
                    gg.id AS hasgrade,
                    gg.score AS bestscore,
                    gg.timemodified AS grademodified,
                    c.id AS contextid,
                    cm.id AS cmid
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {game} g ON g.id = cm.instance
             LEFT JOIN {game_grades} gg ON gg.gameid = g.id AND gg.userid = :userid
                 WHERE c.id {$contextsql}";

        $params = [
            'contextlevel'      => CONTEXT_MODULE,
            'modname'           => 'game',
            'userid'          => $userid,
        ];
        $params += $contextparams;

        // Fetch the individual games.
        $games = $DB->get_recordset_sql($sql, $params);
        foreach ($games as $game) {
            list($course, $cm) = get_course_and_cm_from_cmid($game->cmid, 'game');
            $context = game_get_context_module_instance( $cm->id);

            $gamedata = \core_privacy\local\request\helper::get_context_data($context, $contextlist->get_user());

            \core_privacy\local\request\helper::export_context_files($context, $contextlist->get_user());

            $gamedata->accessdata = (object) [];

            if (empty((array) $gamedata->accessdata)) {
                unset($gamedata->accessdata);
            }

            writer::with_context($context)->export_data([], $gamedata);
        }
        $games->close();

        // Store all game attempt data.
        static::export_game_attempts($contextlist);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only game module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('game', $context->instanceid);
        if (!$cm) {
            // Only game module will be handled.
            return;
        }

        // This will delete all attempts and game grades for this game.
        game_delete_instance( $cm->instance);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                // Only game module will be handled.
                continue;
            }

            $cm = get_coursemodule_from_id('game', $context->instanceid);
            if (!$cm) {
                // Only game module will be handled.
                continue;
            }

            // Fetch the details of the data to be removed.
            $user = $contextlist->get_user();

            // This will delete all attempts and game grades for this game.
            game_delete_user_attempts( $cm->instance, $user);
        }
    }

    /**
     * Store all game attempts for the contextlist.
     *
     * @param   approved_contextlist    $contextlist
     */
    protected static function export_game_attempts(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    c.id AS contextid,
                    cm.id AS cmid, g.gamekind,
                    ga.*
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'game'
                  JOIN {game} g ON g.id = cm.instance
                  JOIN {game_attempts} ga ON ga.gameid = g.id
            WHERE ga.userid = :userid";

        $params = array(
                    'contextlevel'      => CONTEXT_MODULE,
                    'userid'          => $userid
            );

        $attempts = $DB->get_recordset_sql($sql, $params);
        foreach ($attempts as $attempt) {
            $attemptsubcontext = helper::get_game_attempt_subcontext($attempt, $contextlist->get_user());
            $context = game_get_context_module_instance( $attempt->cmid);

            // Store the game attempt data.
            $data = (object) [];

            if (!empty($attempt->timestart)) {
                $data->timestart = transform::datetime($attempt->timestart);
            }
            if (!empty($attempt->timefinish)) {
                $data->timefinish = transform::datetime($attempt->timefinish);
            }
            if (!empty($attempt->timelastattempt)) {
                $data->timemodified = transform::datetime($attempt->timelastattempt);
            }
            $data->score = $attempt->score;
            $data->attempts = $attempt->attempts;
            $data->language = $attempt->language;

            switch( $attempt->gamekind) {
                case 'bookquiz':
                    self::export_game_attempts_bookquiz( $attempt, $data);
                    break;
                case 'cross':
                    self::export_game_attempts_cross( $attempt, $data);
                    break;
                case 'cryptex':
                    self::export_game_attempts_cryptex( $attempt, $data);
                    break;
                case 'hangman':
                    self::export_game_attempts_hangman( $attempt, $data);
                    break;
                case 'hiddenpicture':
                    self::export_game_attempts_hiddenpicture( $attempt, $data);
                    break;
                case 'snakes':
                    self::export_game_attempts_snakes( $attempt, $data);
                    break;
                case 'sudoku':
                    self::export_game_attempts_sudoku( $attempt, $data);
                    break;
            }

            writer::with_context($context)->export_data($attemptsubcontext, $data);
        }
        $attempts->close();
    }

    /**
     * Export data for each attempt on game bookquiz.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_bookquiz( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_bookquiz WHERE id={$attempt->id}";
        $bookquiz = $DB->get_record_sql( $sql);
        if ($bookquiz === false) {
            return;
        }
        if ($bookquiz->lastchapterid != 0) {
            $sql = "SELECT title FROM {$CFG->prefix}book_chapters WHERE id={$bookquiz->lastchapterid}";
            $rec = $DB->get_record_sql( $sql);
            if ($rec != false) {
                $data->bookquiz_lastchapter = $rec->title;
            }
        }
    }

    /**
     * Export data for each attempt on game crosswod.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_cross( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_cross WHERE id={$attempt->id}";
        $cross = $DB->get_record_sql( $sql);
        if ($cross === false) {
            return;
        }
        $data->crossletters_usedcols = $cross->usedcols;
        $data->cross_usedrows = $cross->usedrows;
        $data->cross_words = $cross->words;
        $data->cross_wordsall = $cross->wordsall;
        $data->cross_createscore = $cross->createscore;
        $data->cross_createtries = $cross->createtries;
        $data->cross_createtimelimit = $cross->createtimelimit;
        $data->cross_createconnectors = $cross->createconnectors;
        $data->cross_createfilleds = $cross->createfilleds;
        $data->cross_createspaces = $cross->createspaces;
        $data->cross_triesplay = $cross->triesplay;
    }

    /**
     * Export data for each attempt on game cryptex.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_cryptex( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_cryptex WHERE id={$attempt->id}";
        $cryptex = $DB->get_record_sql( $sql);
        if ($cryptex === false) {
            return;
        }
        $data->cryptex_letters = $cryptex->letters;
    }

    /**
     * Export data for each attempt on game hangman.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_hangman( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_hangman WHERE id={$attempt->id}";
        $hangman = $DB->get_record_sql( $sql);
        if ($hangman === false) {
            return;
        }
        $data->hangman_letters = $hangman->letters;
        $data->hangman_allletters = $hangman->allletters;
        $data->hangman_try = $hangman->try;
        $data->hangman_maxtries = $hangman->maxtries;
        $data->hangman_finishedword = $hangman->finishedword;
        $data->hangman_corrects = $hangman->corrects;
        $data->hangman_iscorrect = $hangman->iscorrect;
    }

    /**
     * Export data for each attempt on game hiddenpicture.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_hiddenpicture( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_hiddenpicture WHERE id={$attempt->id}";
        $hiddenpicture = $DB->get_record_sql( $sql);
        if ($hiddenpicture === false) {
            return;
        }
        $data->hiddenpicture_correct = $hiddenpicture->correct;
        $data->hiddenpicture_wrong = $hiddenpicture->wrong;
        $data->hiddenpicture_found = $hiddenpicture->found;
    }

    /**
     * Export data for each attempt on game millionaire.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_millionaire( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_millionaire WHERE id={$attempt->id}";
        $millionaire = $DB->get_record_sql( $sql);
        if ($millionaire === false) {
            return;
        }
        $data->millionaire_queryid = $millionaire->queryid;
        $data->millionaire_state = $millionaire->state;
        $data->millionaire_level = $millionaire->level;
    }

    /**
     * Export data for each attempt on game snakes.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_snakes( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_snakes WHERE id={$attempt->id}";
        $snakes = $DB->get_record_sql( $sql);
        if ($snakes === false) {
            return;
        }
        $data->snakes_snakesdatabaseid = $snakes->snakesdatabaseid;
        $data->snakes_position = $snakes->position;
        $data->snakes_queryid = $snakes->queryid;
        $data->snakes_dice = $snakes->dice;
    }

    /**
     * Export data for each attempt on game sudoku.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_sudoku( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_sudoku WHERE id={$attempt->id}";
        $sudoku = $DB->get_record_sql( $sql);
        if ($sudoku === false) {
            return;
        }
        $data->sudoku_level = $sudoku->level;
        $data->sudoku_data = $sudoku->data;
        $data->sudoku_opened = $sudoku->opened;
        $data->sudoku_guess = $sudoku->guess;
    }
}
