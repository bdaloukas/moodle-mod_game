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

use core_comment\privacy\provider as providerAlias;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\metadata\collection;

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
    \core_privacy\local\request\plugin\provider,
    core_userlist_provider {
    // This plugin currently implements the original plugin_provider interface.
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   collection  $items  The collection to add metadata to.
     * @return  collection  The array of metadata
     */
    public static function get_metadata(collection $items): collection {
        // These tables contain user-linked attempts, answers, grades or per-attempt game state.
        // The subtype tables use the attempt id as their primary key.

        $items->add_database_table('game_attempts', [
            'id' => 'privacy:metadata:game_attempts:id',
            'gameid' => 'privacy:metadata:game_attempts:gameid',
            'userid' => 'privacy:metadata:game_attempts:userid',
            'timestart' => 'privacy:metadata:game_attempts:timestart',
            'timefinish' => 'privacy:metadata:game_attempts:timefinish',
            'timelastattempt' => 'privacy:metadata:game_attempts:timelastattempt',
            'preview' => 'privacy:metadata:game_attempts:preview',
            'attempt' => 'privacy:metadata:game_attempts:attempt',
            'score' => 'privacy:metadata:game_attempts:score',
            'attempts' => 'privacy:metadata:game_attempts:attempts',
            'language' => 'privacy:metadata:game_attempts:language',
        ], 'privacy:metadata:game_attempts');

        $items->add_database_table('game_bookquiz', [
            'id' => 'privacy:metadata:game_bookquiz:id',
            'lastchapterid' => 'privacy:metadata:game_bookquiz:lastchapterid',
        ], 'privacy:metadata:game_bookquiz');

        $items->add_database_table('game_bookquiz_chapters', [
            'id' => 'privacy:metadata:game_bookquiz_chapters:id',
            'attemptid' => 'privacy:metadata:game_bookquiz_chapters:attemptid',
            'chapterid' => 'privacy:metadata:game_bookquiz_chapters:chapterid',
        ], 'privacy:metadata:game_bookquiz_chapters');

        $items->add_database_table('game_cross', [
            'id' => 'privacy:metadata:game_cross:id',
            'usedcols' => 'privacy:metadata:game_cross:usedcols',
            'usedrows' => 'privacy:metadata:game_cross:usedrows',
            'words' => 'privacy:metadata:game_cross:words',
            'wordsall' => 'privacy:metadata:game_cross:wordsall',
            'createscore' => 'privacy:metadata:game_cross:createscore',
            'createtries' => 'privacy:metadata:game_cross:createtries',
            'createtimelimit' => 'privacy:metadata:game_cross:createtimelimit',
            'createconnectors' => 'privacy:metadata:game_cross:createconnectors',
            'createfilleds' => 'privacy:metadata:game_cross:createfilleds',
            'createspaces' => 'privacy:metadata:game_cross:createspaces',
            'triesplay' => 'privacy:metadata:game_cross:triesplay',
        ], 'privacy:metadata:game_cross');

        $items->add_database_table('game_cryptex', [
            'id' => 'privacy:metadata:game_cryptex:id',
            'letters' => 'privacy:metadata:game_cryptex:letters',
        ], 'privacy:metadata:game_cryptex');

        $items->add_database_table('game_hangman', [
            'id' => 'privacy:metadata:game_hangman:id',
            'queryid' => 'privacy:metadata:game_hangman:queryid',
            'letters' => 'privacy:metadata:game_hangman:letters',
            'allletters' => 'privacy:metadata:game_hangman:allletters',
            'try' => 'privacy:metadata:game_hangman:try',
            'maxtries' => 'privacy:metadata:game_hangman:maxtries',
            'finishedword' => 'privacy:metadata:game_hangman:finishedword',
            'corrects' => 'privacy:metadata:game_hangman:corrects',
            'iscorrect' => 'privacy:metadata:game_hangman:iscorrect',
        ], 'privacy:metadata:game_hangman');

        $items->add_database_table('game_hiddenpicture', [
            'id' => 'privacy:metadata:game_hiddenpicture:id',
            'correct' => 'privacy:metadata:game_hiddenpicture:correct',
            'wrong' => 'privacy:metadata:game_hiddenpicture:wrong',
            'found' => 'privacy:metadata:game_hiddenpicture:found',
        ], 'privacy:metadata:game_hiddenpicture');

        $items->add_database_table('game_millionaire', [
            'id' => 'privacy:metadata:game_millionaire:id',
            'queryid' => 'privacy:metadata:game_millionaire:queryid',
            'state' => 'privacy:metadata:game_millionaire:state',
            'level' => 'privacy:metadata:game_millionaire:level',
        ], 'privacy:metadata:game_millionaire');

        $items->add_database_table('game_snakes', [
            'id' => 'privacy:metadata:game_snakes:id',
            'snakesdatabaseid' => 'privacy:metadata:game_snakes:snakesdatabaseid',
            'position' => 'privacy:metadata:game_snakes:position',
            'queryid' => 'privacy:metadata:game_snakes:queryid',
            'dice' => 'privacy:metadata:game_snakes:dice',
        ], 'privacy:metadata:game_snakes');

        $items->add_database_table('game_sudoku', [
            'id' => 'privacy:metadata:game_sudoku:id',
            'level' => 'privacy:metadata:game_sudoku:level',
            'data' => 'privacy:metadata:game_sudoku:data',
            'opened' => 'privacy:metadata:game_sudoku:opened',
            'guess' => 'privacy:metadata:game_sudoku:guess',
        ], 'privacy:metadata:game_sudoku');

        $items->add_database_table('game_grades', [
            'id' => 'privacy:metadata:game_grades:id',
            'gameid' => 'privacy:metadata:game_grades:gameid',
            'userid' => 'privacy:metadata:game_grades:userid',
            'score' => 'privacy:metadata:game_grades:score',
            'timemodified' => 'privacy:metadata:game_grades:timemodified',
        ], 'privacy:metadata:game_grades');

        $items->add_database_table('game_queries', [
            'id' => 'privacy:metadata:game_queries:id',
            'attemptid' => 'privacy:metadata:game_queries:attemptid',
            'gamekind' => 'privacy:metadata:game_queries:gamekind',
            'gameid' => 'privacy:metadata:game_queries:gameid',
            'userid' => 'privacy:metadata:game_queries:userid',
            'sourcemodule' => 'privacy:metadata:game_queries:sourcemodule',
            'questionid' => 'privacy:metadata:game_queries:questionid',
            'glossaryentryid' => 'privacy:metadata:game_queries:glossaryentryid',
            'questiontext' => 'privacy:metadata:game_queries:questiontext',
            'score' => 'privacy:metadata:game_queries:score',
            'timelastattempt' => 'privacy:metadata:game_queries:timelastattempt',
            'studentanswer' => 'privacy:metadata:game_queries:studentanswer',
            'mycol' => 'privacy:metadata:game_queries:mycol',
            'myrow' => 'privacy:metadata:game_queries:myrow',
            'horizontal' => 'privacy:metadata:game_queries:horizontal',
            'answertext' => 'privacy:metadata:game_queries:answertext',
            'correct' => 'privacy:metadata:game_queries:correct',
            'attachment' => 'privacy:metadata:game_queries:attachment',
            'answerid' => 'privacy:metadata:game_queries:answerid',
            'tries' => 'privacy:metadata:game_queries:tries',
        ], 'privacy:metadata:game_queries');

        $items->add_database_table('game_repetitions', [
            'id' => 'privacy:metadata:game_repetitions:id',
            'gameid' => 'privacy:metadata:game_repetitions:gameid',
            'userid' => 'privacy:metadata:game_repetitions:userid',
            'questionid' => 'privacy:metadata:game_repetitions:questionid',
            'glossaryentryid' => 'privacy:metadata:game_repetitions:glossaryentryid',
            'repetitions' => 'privacy:metadata:game_repetitions:repetitions',
        ], 'privacy:metadata:game_repetitions');

        // Game, question-category mappings, board catalogues and export settings are
        // configuration data, not per-user attempt records.
        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a game, or been involved with manual marking
     * and/or grading of a game.
     *
     * @param   int             $userid The user to search.
     * @return  contextlist     $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'game',
            'userid' => $userid,
        ];

        // A grade, response or repetition may exist even when the parent attempt is absent.
        foreach (['game_attempts', 'game_grades', 'game_queries', 'game_repetitions'] as $table) {
            $sql = "SELECT c.id
                      FROM {context} c
                      JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                      JOIN {game} g ON g.id = cm.instance
                      JOIN {" . $table . "} userdata ON userdata.gameid = g.id
                     WHERE userdata.userid = :userid";
            $contextlist->add_from_sql($sql, $params);
        }

        return $contextlist;
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
        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

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
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'game',
            'userid' => $userid,
        ];
        $params += $contextparams;

        // Fetch the individual games.
        $games = $DB->get_recordset_sql($sql, $params);
        foreach ($games as $game) {
            [$course, $cm] = get_course_and_cm_from_cmid($game->cmid, 'game');
            $context = game_get_context_module_instance($cm->id);

            $gamedata = \core_privacy\local\request\helper::get_context_data($context, $contextlist->get_user());

            \core_privacy\local\request\helper::export_context_files($context, $contextlist->get_user());

            $gamedata->accessdata = (object) [];

            if (empty((array) $gamedata->accessdata)) {
                unset($gamedata->accessdata);
            }

            writer::with_context($context)->export_data([], $gamedata);

            // Grades may exist without a corresponding attempt. Export them separately
            // so they are not hidden by the attempt-oriented export below.
            if ($game->hasgrade !== null) {
                $gradedata = (object) [
                    'id' => $game->hasgrade,
                    'gameid' => $game->id,
                    'userid' => $userid,
                    'score' => $game->bestscore,
                    'timemodified' => transform::datetime($game->grademodified),
                ];
                writer::with_context($context)->export_data(
                    [get_string('privacy:export:grades', 'game')], $gradedata
                );
            }
        }
        $games->close();

        // Export all user-linked data, including responses without a parent attempt.
        static::export_game_attempts($contextlist);
        static::export_game_recorded_data($contextlist);
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

        // Remove only the users' data; keep the Game activity and its configuration.
        game_delete_attempts_data($cm->instance);
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
            game_delete_user_attempts($cm->instance, $user);
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
        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    c.id AS contextid,
                    cm.id AS cmid, g.gamekind,
                    ga.*
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'game'
                  JOIN {game} g ON g.id = cm.instance
                  JOIN {game_attempts} ga ON ga.gameid = g.id
            WHERE ga.userid = :userid AND c.id {$contextsql}";

        $params = ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid];
        $params += $contextparams;

        $attempts = $DB->get_recordset_sql($sql, $params);
        foreach ($attempts as $attempt) {
            $attemptsubcontext = helper::get_game_attempt_subcontext($attempt, $contextlist->get_user());
            $context = game_get_context_module_instance($attempt->cmid);

            // Store the game attempt data, including the identity and preview flags.
            $data = (object) [
                'id' => $attempt->id,
                'gameid' => $attempt->gameid,
                'userid' => $attempt->userid,
                'attempt' => $attempt->attempt,
                'preview' => $attempt->preview,
            ];

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

            switch ($attempt->gamekind) {
                case 'bookquiz':
                    self::export_game_attempts_bookquiz($attempt, $data);
                    break;
                case 'cross':
                    self::export_game_attempts_cross($attempt, $data);
                    break;
                case 'cryptex':
                    self::export_game_attempts_cryptex($attempt, $data);
                    break;
                case 'hangman':
                    self::export_game_attempts_hangman($attempt, $data);
                    break;
                case 'hiddenpicture':
                    self::export_game_attempts_hiddenpicture($attempt, $data);
                    break;
                case 'millionaire':
                    self::export_game_attempts_millionaire($attempt, $data);
                    break;
                case 'snakes':
                    self::export_game_attempts_snakes($attempt, $data);
                    break;
                case 'sudoku':
                    self::export_game_attempts_sudoku($attempt, $data);
                    break;
            }

            writer::with_context($context)->export_data($attemptsubcontext, $data);
        }
        $attempts->close();
    }

    /**
     * Export question responses and repetition counters, including records without
     * a surviving parent attempt. Both queries are scoped to the approved contexts
     * and the subject of the privacy request, never just the Game activity.
     *
     * @param approved_contextlist $contextlist Approved contexts and user.
     */
    protected static function export_game_recorded_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        [$contextsql, $contextparams] = $DB->get_in_or_equal(
            $contextlist->get_contextids(), SQL_PARAMS_NAMED, 'privacycontext'
        );

        $tables = [
            'game_queries' => 'privacy:export:questionresponses',
            'game_repetitions' => 'privacy:export:repetitions',
        ];
        foreach ($tables as $table => $label) {
            // SQL table names and labels above are compile-time constants.
            $sql = "SELECT userdata.*, cm.id AS cmid
                      FROM {context} c
                      JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                      JOIN {game} g ON g.id = cm.instance
                      JOIN {" . $table . "} userdata ON userdata.gameid = g.id
                     WHERE userdata.userid = :userid AND c.id {$contextsql}";
            $params = [
                'contextlevel' => CONTEXT_MODULE,
                'modname' => 'game',
                'userid' => $userid,
            ] + $contextparams;
            $records = $DB->get_recordset_sql($sql, $params);
            foreach ($records as $record) {
                $context = game_get_context_module_instance($record->cmid);
                $data = clone $record;
                unset($data->cmid);
                if ($table === 'game_queries' && !empty($data->timelastattempt)) {
                    $data->timelastattempt = transform::datetime($data->timelastattempt);
                }
                // The row id makes the subcontext unique even for repeated questions.
                writer::with_context($context)->export_data(
                    [get_string($label, 'game'), (string) $record->id], $data
                );
            }
            $records->close();
        }
    }

    /**
     * Export data for each attempt on game bookquiz.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_bookquiz($attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_bookquiz WHERE id={$attempt->id}";
        $bookquiz = $DB->get_record_sql($sql);
        if ($bookquiz !== false) {
            $data->bookquiz_lastchapterid = $bookquiz->lastchapterid;
        }
        if ($bookquiz !== false && $bookquiz->lastchapterid != 0) {
            $rec = $DB->get_record('book_chapters', ['id' => $bookquiz->lastchapterid], 'id,title');
            if ($rec != false) {
                $data->bookquiz_lastchapter = $rec->title;
            }
        }

        // Book Quiz stores per-attempt chapter progress in a separate table.
        $chapters = $DB->get_records('game_bookquiz_chapters',
            ['attemptid' => $attempt->id], 'id ASC');
        $data->bookquiz_chapters = array_values($chapters);
    }

    /**
     * Export data for each attempt on game crosswod.
     *
     * @param stdClass $attempt The attempt to be exported.
     * @param stdClass $data    The data to be returned
     */
    private static function export_game_attempts_cross($attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_cross WHERE id={$attempt->id}";
        $cross = $DB->get_record_sql($sql);
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
    private static function export_game_attempts_cryptex($attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_cryptex WHERE id={$attempt->id}";
        $cryptex = $DB->get_record_sql($sql);
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
    private static function export_game_attempts_hangman($attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_hangman WHERE id={$attempt->id}";
        $hangman = $DB->get_record_sql($sql);
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
    private static function export_game_attempts_hiddenpicture($attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_hiddenpicture WHERE id={$attempt->id}";
        $hiddenpicture = $DB->get_record_sql($sql);
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
    private static function export_game_attempts_millionaire($attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_millionaire WHERE id={$attempt->id}";
        $millionaire = $DB->get_record_sql($sql);
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
    private static function export_game_attempts_snakes($attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_snakes WHERE id={$attempt->id}";
        $snakes = $DB->get_record_sql($sql);
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
    private static function export_game_attempts_sudoku($attempt, &$data) {
        global $CFG, $DB;

        $sql = "SELECT * FROM {$CFG->prefix}game_sudoku WHERE id={$attempt->id}";
        $sudoku = $DB->get_record_sql($sql);
        if ($sudoku === false) {
            return;
        }
        $data->sudoku_level = $sudoku->level;
        $data->sudoku_data = $sudoku->data;
        $data->sudoku_opened = $sudoku->opened;
        $data->sudoku_guess = $sudoku->guess;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'contextid' => $context->id,
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'game',
        ];

        // Find users with game attempt entries.
        $sql = "SELECT ga.userid
                    FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                    JOIN {game} game ON game.id = cm.instance
                    JOIN {game_attempts} ga ON ga.gameid = game.id
                WHERE c.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);

        // Find users with game grades entries.
        $sql = "SELECT gg.userid
                    FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                    JOIN {game} game ON game.id = cm.instance
                    JOIN {game_grades} gg ON gg.gameid = game.id
                WHERE c.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);

        // Find users with game queries entries.
        $sql = "SELECT gq.userid
                    FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                    JOIN {game} game ON game.id = cm.instance
                    JOIN {game_queries} gq ON gq.gameid = game.id
                WHERE c.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);

        // Find users with game repetitions entries.
        $sql = "SELECT gp.userid
                    FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                    JOIN {game} game ON game.id = cm.instance
                    JOIN {game_repetitions} gp ON gp.gameid = game.id
                WHERE c.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);

        // Find users with game attempts.
        providerAlias::get_users_in_context_from_sql($userlist, 'com', 'mod_game', 'game_attempts', $context->id);

        // Find users with game grades.
        providerAlias::get_users_in_context_from_sql($userlist, 'com', 'mod_game', 'game_grades', $context->id);

        // Find users with game queries.
        providerAlias::get_users_in_context_from_sql($userlist, 'com', 'mod_game', 'game_queries', $context->id);

        // Find users with game queries.
        providerAlias::get_users_in_context_from_sql($userlist, 'com', 'mod_game', 'game_repetitions', $context->id);
    }


    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist    $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        $cm = get_coursemodule_from_id('game', $context->instanceid);
        if (!$cm) {
            return;
        }

        game_delete_attempts_data($cm->instance, $userids);
    }

}
