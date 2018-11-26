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
            ], 'privacy:metadata:game_attempts');

        // The table 'game_bookquiz' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_bookquiz_chapters' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_bookquiz_questions' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_cross' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_cross', [
                'id'               => 'privacy:metadata:game_cross:id',
                'usedcols'               => 'privacy:metadata:game_cross:usedcols',
                'usedrows'             => 'privacy:metadata:game_cross:usedrows',
                'words'            => 'privacy:metadata:game_cross:words',
                'wordsall'            => 'privacy:metadata:game_cross:wordsall',
                'createscore'            => 'privacy:metadata:game_cross:createscore',
                'createtries'            => 'privacy:metadata:game_cross:createtries',
                'createlimit'            => 'privacy:metadata:game_cross:createlimit',
                'createconnectors'            => 'privacy:metadata:game_cross:createconnectors',
                'createfilleds'            => 'privacy:metadata:game_cross:createfilleds',
                'createspaces'            => 'privacy:metadata:game_cross:createspaces',
                'triesplay'            => 'privacy:metadata:game_cross:triesplay',
            ], 'privacy:metadata:game_cross');

        // The table 'game_cryptex' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_cryptex', [
                'id'               => 'privacy:metadata:game_cryptex:id',
                'letters'               => 'privacy:metadata:game_cryptex:letters',
            ], 'privacy:metadata:game_cryptex');

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

        // The table 'game_hangman' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_hangman', [
                'id'               => 'privacy:metadata:game_hangman:id',
                'queryid'               => 'privacy:metadata:game_hangman:queryid',
                'letters'               => 'privacy:metadata:game_hangman:letters',
                'allletters'               => 'privacy:metadata:game_hangman:allletters',
                'try'               => 'privacy:metadata:game_hangman:try',
                'maxtries'               => 'privacy:metadata:game_hangman:maxtries',
                'finishedword'               => 'privacy:metadata:game_hangman:finishedword',
                'corrects'               => 'privacy:metadata:game_hangman:corrects',
                'iscorrect'               => 'privacy:metadata:game_hangman:iscorrect',
            ], 'privacy:metadata:game_hangman');

        // The table 'game_hiddenpicture' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_hiddenpicture', [
                'id'               => 'privacy:metadata:game_hiddenpicture:id',
                'correct'               => 'privacy:metadata:game_hiddenpicture:corect',
                'wrong'               => 'privacy:metadata:game_hiddenpicture:wrong',
                'found'               => 'privacy:metadata:game_hiddenpicture:found',
            ], 'privacy:metadata:game_hiddenpicture');

        // The table 'game_millionaire' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_millionaire', [
                'id'               => 'privacy:metadata:game_millionaire:id',
                'queryid'               => 'privacy:metadata:game_millionaire:queryid',
                'state'               => 'privacy:metadata:game_millionaire:state',
                'level'               => 'privacy:metadata:game_millionaire:level',
            ], 'privacy:metadata:game_millionaire');

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

        // The table 'game_snakes' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_snakes', [
                'id'               => 'privacy:metadata:game_snakes:id',
                'snakesdatabaseid'               => 'privacy:metadata:game_snakes:snakesdatabaseid',
                'position'               => 'privacy:metadata:game_snakes:position',
                'queryid'               => 'privacy:metadata:game_snakes:queryid',
                'dice'               => 'privacy:metadata:game_snakes:dice',
            ], 'privacy:metadata:game_snakes');

        // The table 'game_snakes_database' contains data about the structure of a game.
        // It does not contain any user identifying data and does not need a mapping.

        // The table 'game_snakes' stores a record of each attempt at cross game.
        // It contains id that linked to game_attempts.
        $items->add_database_table('game_sudoku', [
                'id'               => 'privacy:metadata:game_sudoku:id',
                'level'               => 'privacy:metadata:game_sudoku:level',
                'data'               => 'privacy:metadata:game_sudoku:data',
                'opened'               => 'privacy:metadata:game_sudoku:opened',
                'guess'               => 'privacy:metadata:game_sudoku:guess',
            ], 'privacy:metadata:game_sudoku');

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
/*
        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only game module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('game', $context->instanceid);
        if (!$cm) {
            // Only game module will be handled.
            return;
        }

        $gameobj = \game::create($cm->instance);
        $game = $gameobj->get_game();

        // This will delete all question attempts, game attempts, and game grades for this game.
        game_delete_all_attempts($game);
*/
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
/*
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
            $gameobj = \game::create($cm->instance);
            $game = $gameobj->get_game();
            $user = $contextlist->get_user();

            // This will delete all question attempts, game attempts, and game grades for this game.
            game_delete_user_attempts($gameobj, $user);
        }
*/
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
            case 'cryptex':
                provider::export_game_attempts_cryptex( $attempt, $data);
                break;
            }

            writer::with_context($context)->export_data($attemptsubcontext, $data);
        }
        $attempts->close();
    }

    static function export_game_attempts_cryptex( $attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_cryptex WHERE id={$attempt->id}";
        $cryptex = $DB->get_record_sql( $sql);
        if( $cryptex === false) {
            return;
        }
        $data->cryptex_letters = $cryptex->letters;
    }
}
