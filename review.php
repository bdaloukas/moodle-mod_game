<?php  // $Id: review.php,v 1.9.2.4 2010/08/10 18:11:02 bdaloukas Exp $
/**
* This page prints a review of a particular game attempt
*
* @version $Id: review.php,v 1.9.2.4 2010/08/10 18:11:02 bdaloukas Exp $
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package game
*/

    require_once("../../config.php");
    require_once("locallib.php");
    require_once( 'header.php');

    if( !isteacher( $game->course, $USER->id)){
    	error( get_string( 'only_teachers', 'game'));
    }

    $currenttab = 'reports';
    include( 'tabs.php');

    $attemptid = required_param('attempt', PARAM_INT);    // A particular attempt ID for review

    if( $game->sourcemodule == 'glossary'){
        game_review_glossary( $game, $attemptid);
        print_footer();
        die;
    }

    function game_repair_queries( $game, &$rec){
        if($game->gamekind == 'sudoku'){
            if( $rec->sourcemodule == 'glossary')
            {
                $rec2 = get_record_select( 'glossary_entries', 'id='.$rec->glossaryentryid, 'id,concept,definition');
                $rec->questiontext = $rec2->concept;
                $rec->answertext = $rec2->definition;
            }else
            {
                $rec2 = get_record_select( 'question', 'id='.$rec->questionid, 'id,questiontext');
                $rec->questiontext = $rec2->questiontext;
                //$rec2 = get_record_select( 'question', 'id='.$rec->question, 'id,questiontext');
                //$rec->answertext = $rec2->definition;
            }
        }    
    }

    require_once($CFG->libdir."/questionlib.php");

    $attempt = required_param('attempt', PARAM_INT);    // A particular attempt ID for review
    $page = optional_param('page', 0, PARAM_INT); // The required page
    $showall = optional_param('showall', 0, PARAM_BOOL);
	
    if (! $attempt = get_record("game_attempts", "id", $attempt)) {
        error("No such attempt ID exists");
    }
    if (! $game = get_record("game", "id", $attempt->gameid)) {
        error("The game with id $attempt->gameid belonging to attempt $attempt is missing");
    }
	
	game_compute_attempt_layout( $game, $attempt);
	
    if (! $course = get_record("course", "id", $game->course)) {
        error("The course with id $game->course that the game with id $game->id belongs to is missing");
    }
    if (! $cm = get_coursemodule_from_instance("game", $game->id, $course->id)) {
        error("The course module for the game with id $game->id is missing");
    }

    $grade = game_score_to_grade( $attempt->score, $game);
    $feedback = game_feedback_for_grade( $grade, $attempt->gameid);

    require_login( $course->id, false, $cm);
    $context = get_context_instance( CONTEXT_MODULE, $cm->id);
    $coursecontext = get_context_instance( CONTEXT_COURSE, $cm->course);
    $isteacher = isteacher( $game->course, $USER->id);
    $options = game_get_reviewoptions( $game, $attempt, $context);

    add_to_log($course->id, "game", "review", "review.php?id=$cm->id&amp;attempt=$attempt->id", "$game->id", "$cm->id");

/// Print the page header

    $strgames = get_string('modulenameplural', 'game');
    $strreview  = get_string('review', 'game');
    $strscore  = get_string('score', 'game');
    $strgrade  = get_string('grade');
    $strbestgrade  = get_string('bestgrade', 'quiz');
    $strtimetaken     = get_string('timetaken', 'game');
    $strtimecompleted = get_string('completedon', 'game');


    $strupdatemodule = has_capability('moodle/course:manageactivities', $coursecontext)
            ? update_module_button($cm->id, $course->id, get_string('modulename', 'game'))
            : "";
                 
    $strgames = get_string('modulenameplural', 'game');
    $strgame  = get_string('modulename', 'game');
 /*                    
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
*/              
    echo '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'; // for overlib
/// Print heading and tabs if this is part of a preview
    //if (has_capability('mod/game:preview', $context)) {
    /*    if ($attempt->userid == $USER->id) { // this is the report on a preview
            $currenttab = 'preview';
        } else {
            $currenttab = 'reports';
            $mode = '';
        }
        include('tabs.php');
    //} else {
    //    print_heading(format_string($game->name));
    //}
*/
/// Load all the questions and states needed by this script

    // load the questions needed by page
    $pagelist = $showall ? game_questions_in_game( $attempt->layout) : game_questions_on_page( $attempt->layout, $page);
	$a = explode( ',', $pagelist);
	$pagelist = '';
	foreach( $a as $item){
		if( substr( $item, 0, 1)){
		    if( substr( $item, -1) != 'G'){
    			$pagelist .= ','.$item;
    		}
		}
	}
	$pagelist = substr( $pagelist, 1);
	
	if( $pagelist != ''){
		$sql = "SELECT q.*, i.id AS instance,i.id as iid,".
				"i.score AS score,i.studentanswer".
			"  FROM {$CFG->prefix}question q,".
			"       {$CFG->prefix}game_queries i".
			" WHERE i.attemptid = '$attempt->id' AND q.id = i.questionid AND (i.sourcemodule='question' or i.sourcemodule = 'quiz')".
			"   AND q.id IN ($pagelist)";

		if (!$questions = get_records_sql( $sql)) {
			error('No questions found');
		}
	}else
	{
		$questions = array();
	}

    // Load the question type specific information
    if (!get_question_options( $questions)) {
        error('Could not load question options');
    }

	$states = game_compute_states( $game, $questions);
/// Print infobox

    //$timelimit = (int)$game->timelimit * 60;
	$timelimit = 0;
    $overtime = 0;

    if ($attempt->timefinish) {
        if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
            if($timelimit && $timetaken > ($timelimit + 60)) {
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
       $student = get_record('user', 'id', $attempt->userid);
       $picture = print_user_picture($student->id, $course->id, $student->picture, false, true);
       $table->data[] = array($picture, '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$student->id.'&amp;course='.$course->id.'">'.fullname($student, true).'</a>');
    }
    //if (has_capability('mod/game:grade', $context)){
        if( count($attempts = get_records_select('game_attempts', "gameid = '$game->id' AND userid = '$attempt->userid'", 'attempt ASC')) > 1) {
            // print list of attempts
            $attemptlist = '';
            foreach ($attempts as $at) {
                $attemptlist .= ($at->id == $attempt->id)
                    ? '<strong>'.$at->attempt.'</strong>, '
                    : '<a href="review.php?attempt='.$at->id.($showall?'&amp;showall=true':'').'">'.$at->attempt.'</a>, ';
            }
            $table->data[] = array(get_string('attempts', 'game').':', trim($attemptlist, ' ,'));
        }
    //}

    $table->data[] = array(get_string('startedon', 'game').':', userdate($attempt->timestart));
    if ($attempt->timefinish) {
        $table->data[] = array("$strtimecompleted:", userdate($attempt->timefinish));
        $table->data[] = array("$strtimetaken:", $timetaken);
    }
    //if the student is allowed to see their score
    if ($options->scores) {
        if ($game->grade) {
            if($overtime) {
                $result->sumgrades = "0";
                $result->grade = "0.0";
            }

            $a = new stdClass;
            $percentage = round($attempt->score*100, 0);
            $a->grade = game_score_to_grade( $attempt->score, $game);
            $a->maxgrade = $game->grade;
            $table->data[] = array("$strscore:", "{$a->grade}/{$game->grade} ($percentage %)");
            //$table->data[] = array("$strgrade:", get_string('outof', 'game', $a));
        }
    }
    if ($options->overallfeedback && $feedback) {
        $table->data[] = array(get_string('feedback', 'game'), $feedback);
    }
    if ($isteacher and $attempt->userid == $USER->id) {
        // the teacher is at the end of a preview. Print button to start new preview
        unset($buttonoptions);
        $buttonoptions['q'] = $game->id;
        $buttonoptions['forcenew'] = true;
        echo '<div class="controls">';
        print_single_button($CFG->wwwroot.'/mod/game/attempt.php', $buttonoptions, get_string('startagain', 'game'));
        echo '</div>';
    } else { // print number of the attempt
        print_heading(get_string('reviewofattempt', 'game', $attempt->attempt));
    }
    print_table($table);

    // print javascript button to close the window, if necessary
    if (!$isteacher) {
        include('attempt_close_js.php');
    }

/// Print the navigation panel if required
    $numpages = game_number_of_pages( $attempt->layout);
    if ($numpages > 1 and !$showall) {
        print_paging_bar($numpages, $page, 1, 'review.php?attempt='.$attempt->id.'&amp;');
        echo '<div class="controls"><a href="review.php?attempt='.$attempt->id.'&amp;showall=true">';
        print_string('showall', 'quiz');
        echo '</a></div>';
    }

/// Print all the questions
    if( $pagelist){
    	game_print_questions( $pagelist, $attempt, $questions, $options, $states, $game);
    }

    // Print the navigation panel if required
    if ($numpages > 1 and !$showall) {
        print_paging_bar($numpages, $page, 1, 'review.php?attempt='.$attempt->id.'&amp;');
    }

    // print javascript button to close the window, if necessary
    if (!$isteacher) {
        include('attempt_close_js.php');
    }

    print_footer($course);
	
	function game_compute_states( $game, $questions)
	{
		global $QTYPES;
		
		// Restore the question sessions to their most recent states
		// creating new sessions where required
	
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
				error( 'game_compute_states: problem');
			}
		
			$state->event = QUESTION_EVENTOPEN;
			//$question->maxgrade = 100;
			$state->manualcomment = '';
	
			$state->responses = array( '' => $question->studentanswer);
			$state->attempt = $question->iid;					

			$states[ $question->id] = $state; 
		}
		return $states;
	}
	
	
	
	function game_print_questions( $pagelist, $attempt, $questions, $options, $states, $game)
	{
	    $pagequestions = explode(',', $pagelist);
		$number = game_first_questionnumber( $attempt->layout, $pagelist);
		foreach ($pagequestions as $i) {
			if (!isset($questions[$i])) {
				print_simple_box_start('center', '90%');
				echo '<strong><font size="+1">' . $number . '</font></strong><br />';
				notify(get_string('errormissingquestion', 'quiz', $i));
				print_simple_box_end();
				$number++; // Just guessing that the missing question would have lenght 1
				continue;
			}
			$options->validation = QUESTION_EVENTVALIDATE === $states[$i]->event;
			//$options->history = ($isteacher and !$attempt->preview) ? 'all' : 'graded';
			$options->history = false;
			unset( $options->questioncommentlink);
			// Print the question
			if ($i > 0) {
				echo "<br />\n";
			}
			$questions[$i]->maxgrade = 0;
			
		    $options->correct_responses = 0;
		    $options->feedback = 0;
		    $options->readonly = 0;	
			
            global $QTYPES;
            
		    unset( $cmoptions);
            $cmoptions->course = $game->course;
            $cmoptions->optionflags->optionflags = 0;
		    $cmoptions->id = 0;
		    $cmoptions->shuffleanswers = 1;
		    $attempt = 0;
		    $question = $questions[ $i];
		    if (!$QTYPES[$question->qtype]->create_session_and_responses( $question, $state, $cmoptions, $attempt)) {
			    error( 'game_sudoku_showquestions_quiz: problem');
		    }						
			$cmoptions->optionflags = 0;
			print_question( $question, $states[$i], $number, $cmoptions, $options);
			$number += $questions[$i]->length;
		}
	}

    function game_review_glossary( $game, $attemptid){
        echo '<center><table border=1>';
        echo '<tr>';
        echo '<td>'.get_string( 'question').'</td>';
        echo '<td>'.get_string( 'answer').'</td>';
        echo '<td>'.get_string( 'student').'</td>';
        echo '<td>'.get_string( 'grade').'</td>';
        echo '</tr>';
        $fields = 'id,questiontext,studentanswer,answertext,score,sourcemodule,questionid';
        $recs = get_records_select( 'game_queries', 'attemptid='.$attemptid, 'id', $fields);
        if( $recs){
            foreach( $recs as $rec){
                echo '<tr>';
                game_repair_queries( $game, $rec);
                echo '<td>'.$rec->questiontext.'</td>';
                echo '<td>'.$rec->answertext.'</td>';
                echo '<td>'.$rec->studentanswer.'</td>';
                echo '<td>'.(round(100*$rec->score) >=1 ? round($rec->score*100, 0).' %' : '').'</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
    }
