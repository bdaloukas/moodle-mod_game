<?php

	$id = optional_param('id', 0, PARAM_INT); // Course Module ID
    $q  = optional_param('q', 0, PARAM_INT);  // game ID
    $action  = optional_param('action', "", PARAM_ALPHANUM);  // action

	if ($id) {
        if (! $cm = get_record("course_modules", "id", $id)) {
            print_error("Course Module ID was incorrect id=$id");
        }
        if (! $course = get_record("course", "id", $cm->course)) {
            print_error("Course is misconfigured id=$cm->course");
        }
    
        if (! $game = get_record("game", "id", $cm->instance)) {
            print_error("Game id is incorrect (id=$cm->instance)");
        }
    } else {
        if (! $game = get_record("game", "id", $q)) {
            print_error("Game module is incorrect (id=$q)");
        }
        if (! $course = get_record("course", "id", $game->course)) {
            print_error("Course is misconfigured (id=$game->course)");
        }
        if (! $cm = get_coursemodule_from_instance("game", $game->id, $course->id)) {
            print_error("Course Module ID was incorrect");
        }
    }

    require_login($course->id);

    add_to_log($course->id, "game", "view", "view.php?id=$cm->id", $game->name);

/// Print the page header

    $strgames = get_string("modulenameplural", "game");
    $strgame  = get_string("modulename", "game");

    $cm->modname = 'game';
    $cm->name = $game->name;
    
    if( function_exists( 'build_navigation')){
        $navigation = build_navigation('', $cm);
        print_header("$course->shortname: $game->name", "$course->shortname: $game->name", $navigation, 
                  "", "", true, update_module_button($cm->id, $course->id, $strgame), 
                  navmenu($course, $cm));
    }else{
        if ($course->category) {
            $navigation = "<a href=\"{$CFG->wwwroot}/course/view.php?id=$course->id\">$course->shortname</a> ->";
        } else {
            $navigation = '';
        }    
        print_header("$course->shortname: $game->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strgames</a> -> $game->name", 
                  "", "", true, update_module_button($cm->id, $course->id, $strgame), 
                  navmenu($course, $cm));        
    }
