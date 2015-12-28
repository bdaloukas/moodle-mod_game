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

// This script uses installed report plugins to print game reports.

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/game/locallib.php');

//require_login($course->id, false);

require_once($CFG->dirroot.'/mod/game/headergame.php');
//require_once($CFG->dirroot.'/mod/game/report/reportlib.php');
/*
$id = optional_param('id', 0, PARAM_INT);    // Course Module ID.
$q = optional_param('q', 0, PARAM_INT);     // Game ID.


if ($id) {
    if (! $cm = get_coursemodule_from_id('game', $id)) {
        print_error( "There is no coursemodule with id $id");
    }

    if (! $course = $DB->get_record('course', array( 'id' => $cm->course))) {
        print_error( 'Course is misconfigured');
    }

    if (! $game = $DB->get_record( 'game', array( 'id' => $cm->instance))) {
        print_error( "The game with id $cm->instance corresponding to this coursemodule $id is missing");
    }
} else {
    if (! $game = $DB->get_record( 'game', array( 'id' => $q))) {
        print_error( "There is no game with id $q");
    }
    if (! $course = $DB->get_record( 'course', array( 'id' => $game->course))) {
        print_error( "The course with id $game->course that the game with id $a belongs to is missing");
    }
    if (! $cm = get_coursemodule_from_instance( 'game', $game->id, $course->id)) {
        print_error( "The course module for the game with id $q is missing");
    }
}
*/

$context = game_get_context_module_instance( $cm->id);
require_capability('mod/game:viewreports', $context);

//add_to_log($course->id, "game", "report", "report.php?id=$cm->id", "$game->id", "$cm->id");

// Open the selected game report and display it.
$mode = optional_param('mode', 'overview', PARAM_ALPHA);        // Report mode.

$mode = clean_param( $mode, PARAM_SAFEDIR);

if (! is_readable("report/$mode/report.php")) {
    print_error("Report not known ($mode)");
}

require("report/default.php");  // Parent class.
require("report/$mode/report.php");

$report = new game_overview_report();

if (! $report->display( $game, $cm, $course)) {             // Run the report!
    print_error( 'Error occurred during pre-processing!');
}

// Print footer.
echo $OUTPUT->footer($course);
