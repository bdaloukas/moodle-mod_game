<?php // $Id: index.php,v 1.5.2.2 2010/07/24 03:30:22 arborrow Exp $
/**
 * This page lists all the instances of game module in a particular course
 *
 * @author 
 * @version $Id: index.php,v 1.5.2.2 2010/07/24 03:30:22 arborrow Exp $
 * @package game
 **/

    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record('course', 'id', $id)) {
        error('Course ID is incorrect');
    }

    $coursecontext = get_context_instance(CONTEXT_COURSE, $id);

    require_login($course->id);

    add_to_log($course->id, "game", "view all", "index.php?id=$course->id", "");


/// Get all required strings game

    $strgames = get_string("modulenameplural", "game");
    $strgame = get_string("modulename", "game");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }
    
    $navlinks = array();
    $navlinks[] = array('name' => $strgames, 'link' => "index.php?id=$course->id", 'type' => 'activity');
        
    if( function_exists( 'build_navigation')){
        $navigation = build_navigation( $navlinks);
        
        print_header( $course->shortname, $course->shortname, $navigation);
    }else{    
        if ($course->category) {
            $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
        } else {
            $navigation = '';
        }
        print_header("$course->shortname: $strgames", "$course->fullname", "$navigation $strgames", "", "", true, "", navmenu($course));
    }
    
/// Get all the appropriate data

    if (! $games = get_all_instances_in_course("game", $course)) {
        notice("There are no games", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $headings  = array ($strweek, $strname);
        $align = array ("center", "left");
    } else if ($course->format == "topics") {
        $headings  = array ($strtopic, $strname);
        $align = array ("center", "left", "left", "left");
    } else {
        $headings  = array ($strname);
        $align = array ("left", "left", "left");
    }

    $showing = '';  // default

    if (has_capability('mod/game:viewreports', $coursecontext)) {
        array_push($headings, get_string('attempts', 'game'));
        array_push($align, 'left');
        $showing = 'stats';
    } else if (has_any_capability(array('mod/game:reviewmyattempts', 'mod/game:attempt'), $coursecontext)) {
        array_push($headings, get_string('bestgrade', 'quiz'));
        array_push($align, 'left');
        $showing = 'scores';  // default
    }

    $table->head  = $headings;
    $table->align = $align;

    /// Populate the table with the list of instances.
    $currentsection = '';
    foreach ($games as $game) {
        $cm = get_coursemodule_from_instance('game', $game->id);
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $data = array();

        // Section number if necessary.
        $strsection = '';
        if ($game->section != $currentsection) {
            if ($game->section) {
                $strsection = $game->section;
            }

            $currentsection = $game->section;
        }
        $data[] = $strsection;

        // Link to the instance.
        $class = '';
        if (!$game->visible) {
            $class = ' class="dimmed"';
        }
        $link = "<a$class href=\"view.php?id=$game->coursemodule\">" . format_string($game->name, true) . '</a>';

        $data[] = $link;

        if ($showing == 'stats') {
            // The $game objects returned by get_all_instances_in_course have the necessary $cm
            // fields set to make the following call work.
            $attemptcount = game_num_attempt_summary($game, $game);
            if ($attemptcount) {
                $data[] = "<a$class href=\"report.php?id=$game->coursemodule\">$attemptcount</a>";
            } else {
                $data[] = '';
            }
        } else if ($showing == 'scores') {

            // Grade and feedback.
            $bestscore = game_get_best_score($game, $USER->id);
            $attempts = game_get_user_attempts($game->id, $USER->id, 'all');
            list($someoptions, $alloptions) = game_get_combined_reviewoptions($game, $attempts, $context);

            $grade = '';
            $feedback = '';
            if ($game->grade && !is_null($bestscore)) {
                if ($alloptions->scores) {
                    $bestgrade = round( $bestscore * $game->grade / 100, $game->decimalpoints);
                    $grade = "$bestgrade / $game->grade";
                }
            }else if( $bestscore != 0){
                $grade = round( 100 * $bestscore, $game->decimalpoints).' %';
            }
            $data[] = $grade;
        }

        $table->data[] = $data;
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
