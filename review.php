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

// This page prints a review of a particular game attempt.

require_once("../../config.php");
require_once("locallib.php");
require_once("../../lib/questionlib.php");

$attempt = required_param('attempt', PARAM_INT);    // A particular attempt ID for review.
$page = optional_param('page', 0, PARAM_INT); // The required page.
$showall = optional_param('showall', 0, PARAM_BOOL);

if (! $attempt = $DB->get_record('game_attempts', array( 'id' => $attempt))) {
    print_error("No such attempt ID exists");
}
if (! $game = $DB->get_record('game', array( 'id' => $attempt->gameid))) {
    print_error("The game with id $attempt->gameid belonging to attempt $attempt is missing");
}

game_compute_attempt_layout( $game, $attempt);

if (! $course = $DB->get_record('course', array( 'id' => $game->course))) {
    print_error("The course with id $game->course that the game with id $game->id belongs to is missing");
}
if (! $cm = get_coursemodule_from_instance("game", $game->id, $course->id)) {
    print_error("The course module for the game with id $game->id is missing");
}

$grade = game_score_to_grade( $attempt->score, $game);
$feedback = game_feedback_for_grade( $grade, $attempt->gameid);

require_login( $course->id, false, $cm);
$context = game_get_context_module_instance( $cm->id);
$coursecontext = game_get_context_course_instance( $cm->course);

$isteacher = has_capability('mod/game:manage', $context);

$options = game_get_reviewoptions( $game, $attempt, $context);
$popup = $isteacher ? 0 : $game->popup; // Controls whether this is shown in a javascript-protected window.

//add_to_log($course->id, "game", "review", "review.php?id=$cm->id&amp;attempt=$attempt->id", "$game->id", "$cm->id");

// Print the page header.

$strgames = get_string('modulenameplural', 'game');
$strreview  = get_string('review', 'game');
$strscore  = get_string('score', "game");
$strgrade  = get_string('grade');
$strbestgrade  = get_string('bestgrade', 'quiz');
$strtimetaken     = get_string('timetaken', 'game');
$strtimecompleted = get_string('completedon', 'game');

$strupdatemodule = has_capability('moodle/course:manageactivities',
    $coursecontext) ? update_module_button($cm->id, $course->id, get_string('modulename', 'game')) : '';

$strgames = get_string("modulenameplural", "game");
$strgame  = get_string("modulename", "game");

require( "headergame.php");
/*
if (function_exists( 'build_navigation')) {
    $navigation = build_navigation('', $cm);
    echo $OUTPUT->heading("$course->shortname: $game->name", "$course->shortname: $game->name", $navigation,
    "", "", true, update_module_button($cm->id, $course->id, $strgame),
    navmenu($course, $cm));
} else {
    if ($course->category) {
        $navigation = "<a href=\"{$CFG->wwwroot}/course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    echo $OUTPUT->heading("$course->shortname: $game->name", "$course->fullname",
        "$navigation <a href=index.php?id=$course->id>$strgames</a> -> $game->name",
        "", "", true, update_module_button($cm->id, $course->id, $strgame),
        navmenu($course, $cm));
}
*/
echo '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'; // For overlib.

// Print heading and tabs if this is part of a preview.
if ($attempt->userid == $USER->id) {
    // This is the report on a preview.
    $currenttab = 'preview';
} else {
    $currenttab = 'reports';
    $mode = '';
}

require('tabs.php');

// Load all the questions and states needed by this script.
// Load the questions needed by page.
$pagelist = $showall ? game_questions_in_game( $attempt->layout) : game_questions_on_page( $attempt->layout, $page);
$a = explode( ',', $pagelist);
$pagelist = '';
foreach ($a as $item) {
    if (substr( $item, 0, 1)) {
        if (substr( $item, -1) != 'G') {
            $pagelist .= ','.$item;
        }
    }
}

$pagelist = substr( $pagelist, 1);

if ($pagelist != '') {
    $sql = "SELECT q.*, i.id AS instance,i.id as iid,".
        "i.score AS score,i.studentanswer".
        "  FROM {question} q,".
        "       {game_queries} i".
        " WHERE i.attemptid = '$attempt->id' AND q.id = i.questionid AND (i.sourcemodule='question' or i.sourcemodule = 'quiz')".
        "   AND q.id IN ($pagelist)";

    if (!$questions = $DB->get_records_sql( $sql)) {
        print_error('No questions found');
    }
} else {
    $questions = array();
}

// Load the question type specific information.
if (!get_question_options( $questions)) {
    print_error('Could not load question options');
}

$states = game_compute_states( $game, $questions);
// Print infobox.

$timelimit = 0;
$overtime = 0;

if ($attempt->timefinish) {
    if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
        if ($timelimit && $timetaken > ($timelimit + 60)) {
            $overtime = $timetaken - $timelimit;
            $overtime = format_time($overtime);
        }
        $timetaken = format_time($timetaken);
    } else {
        $timetaken = "-";
    }
} else {
    $timetaken = get_string('unfinished', 'game');
}

$table->align  = array("right", "left");
if ($attempt->userid <> $USER->id) {
    $student = $DB->get_record('user', array( 'id' => $attempt->userid));
    $picture = print_user_picture($student->id, $course->id, $student->picture, false, true);
    $table->data[] = array($picture,
        '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$student->id.'&amp;course='.
        $course->id.'">'.fullname($student, true).'</a>');
}

if (count($attempts = $DB->get_records('game_attempts', array( 'gameid' => $game->id,
    'userid' => $attempt->userid), 'attempt ASC')) > 1) {
    // Print list of attempts.
    $attemptlist = '';
    foreach ($attempts as $at) {
        $attemptlist .= ($at->id == $attempt->id) ? '<strong>'.$at->attempt.'</strong>, ' : '<a href="review.php?attempt='.
        $at->id.($showall ? '&amp;showall=true' : '').'">'.$at->attempt.'</a>, ';
    }
    $table->data[] = array(get_string('attempts', 'game').':', trim($attemptlist, ' ,'));
}

$table->data[] = array(get_string('startedon', 'game').':', userdate($attempt->timestart));
if ($attempt->timefinish) {
    $table->data[] = array("$strtimecompleted:", userdate($attempt->timefinish));
    $table->data[] = array("$strtimetaken:", $timetaken);
}

// If the student is allowed to see their score.
if ($options->scores) {
    if ($game->grade) {
        if ($overtime) {
            $result->sumgrades = "0";
            $result->grade = "0.0";
        }

        $a = new stdClass;
        $percentage = round($attempt->score * 100, 0);
        $a->grade = game_score_to_grade( $attempt->score, $game);
        $a->maxgrade = $game->grade;
        $table->data[] = array("$strscore:", "{$a->grade}/{$game->grade} ($percentage %)");
    }
}

if ($options->overallfeedback && $feedback) {
    $table->data[] = array(get_string('feedback', 'game'), $feedback);
}

if ($isteacher and $attempt->userid == $USER->id) {
    // The teacher is at the end of a preview. Print button to start new preview.
    unset($buttonoptions);
    $buttonoptions['q'] = $game->id;
    $buttonoptions['forcenew'] = true;
    echo '<div class="controls">';
    print_single_button($CFG->wwwroot.'/mod/game/attempt.php', $buttonoptions, get_string('startagain', 'game'));
    echo '</div>';
} else {
    // Print number of the attempt.
    print_heading(get_string('reviewofattempt', 'game', $attempt->attempt));
}
print_table($table);

// Print javascript button to close the window, if necessary.
if (!$isteacher) {
    include('attempt_close_js.php');
}

// Print the navigation panel if required.
$numpages = game_number_of_pages( $attempt->layout);
if ($numpages > 1 and !$showall) {
    print_paging_bar($numpages, $page, 1, 'review.php?attempt='.$attempt->id.'&amp;');
    echo '<div class="controls"><a href="review.php?attempt='.$attempt->id.'&amp;showall=true">';
    print_string('showall', 'game');
    echo '</a></div>';
}

// Print all the questions.
if ($pagelist) {
    game_print_questions( $pagelist, $attempt, $questions, $options, $states, $game);
}

// Print the navigation panel if required.
if ($numpages > 1 and !$showall) {
    print_paging_bar($numpages, $page, 1, 'review.php?attempt='.$attempt->id.'&amp;');
}

// Print javascript button to close the window, if necessary.
if (!$isteacher) {
    require('attempt_close_js.php');
}

if (empty($popup)) {
    echo $OUTPUT->footer($course);
}

function game_compute_states( $game, $questions) {
    global $QTYPES;

    // Restore the question sessions to their most recent states creating new sessions where required.
    $states = array();
    foreach ($questions as $question) {
        $state = new StdClass;

        $cmoptions->course = $game->course;
        $cmoptions->optionflags->optionflags = 0;
        $cmoptions->id = 0;
        $cmoptions->shuffleanswers = 1;

        $state->last_graded = new StdClass;
        $state->last_graded->event = QUESTION_EVENTOPEN;

        $state->raw_grade = 0;

        $attempt = 0;
        if (!$QTYPES[$question->qtype]->create_session_and_responses( $question, $state, $cmoptions, $attempt)) {
            print_error( 'game_compute_states: problem');
        }

        $state->event = QUESTION_EVENTOPEN;
        $state->manualcomment = '';

        $state->responses = array( '' => $question->studentanswer);
        $state->attempt = $question->iid;

        $states[ $question->id] = $state;
    }
    return $states;
}

function game_print_questions( $pagelist, $attempt, $questions, $options, $states, $game) {
    global $QTYPES;

    $pagequestions = explode(',', $pagelist);
    $number = game_first_questionnumber( $attempt->layout, $pagelist);
    foreach ($pagequestions as $i) {
        if (!isset($questions[$i])) {
            echo $OUTPUT->box_start('center', '90%');
            echo '<strong><font size="+1">' . $number . '</font></strong><br />';
            notify(get_string('errormissingquestion', 'quiz', $i));
            echo $OUTPUT->box_end();
            $number++; // Just guessing that the missing question would have lenght 1.
            continue;
        }
        $options->validation = QUESTION_EVENTVALIDATE === $states[$i]->event;
        $options->history = false;
        unset( $options->questioncommentlink);

        // Print the question.
        if ($i > 0) {
            echo "<br />\n";
        }

        $questions[$i]->maxgrade = 0;

        $options->correct_responses = 0;
        $options->feedback = 0;
        $options->readonly = 0;

        unset( $cmoptions);
        $cmoptions->course = $game->course;
        $cmoptions->optionflags->optionflags = 0;
        $cmoptions->id = 0;
        $cmoptions->shuffleanswers = 1;
        $attempt = 0;
        $question = $questions[ $i];
        if (!$QTYPES[$question->qtype]->create_session_and_responses( $question, $state, $cmoptions, $attempt)) {
            print_error( 'game_sudoku_showquestions_quiz: problem');
        }
        $cmoptions->optionflags = 0;
        print_question( $question, $states[$i], $number, $cmoptions, $options);
        $number += $questions[$i]->length;
    }
}
