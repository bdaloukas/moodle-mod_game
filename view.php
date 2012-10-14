<?php  // $Id: view.php,v 1.7.2.8 2012/01/16 21:45:04 bdaloukas Exp $

// This page prints a particular instance of game

    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");
    require_once('pagelib.php');

    $id   = optional_param('id', 0, PARAM_INT); // Course Module ID, or
    $q    = optional_param('q',  0, PARAM_INT);  // game ID
    $edit = optional_param('edit', -1, PARAM_BOOL);

    if ($id) {
        if (!$cm = get_coursemodule_from_id('game', $id)){
            error("There is no coursemodule with id $id");
        }

        if (!$course = get_record("course", "id", $cm->course)){
            error("Course is misconfigured");
        }

        if (!$game = get_record("game", "id", $cm->instance)){
            error("The game with id $cm->instance corresponding to this coursemodule $id is missing");
        }

    } 
    else {
        if (!$game = get_record('game', 'id', $q)){
            error("There is no game with id $q");
        }
        if (!$course = get_record('course', 'id', $game->course)){
            error("The course with id $game->course that the game with id $q belongs to is missing");
        }
        if (!$cm = get_coursemodule_from_instance('game', $game->id, $course->id)){
            error("The course module for the game with id $q is missing");
        }
    }

    if( $game->sourcemodule == ''){
        redirect("$CFG->wwwroot/course/mod.php?update=$cm->id&return=true&sesskey=$USER->sesskey");
    }
	
	$game->showtimetaken = 1;	//check bdaloukas
	$game->timelimit = 0;				//check bdaloukas
	$game->timeclose = 0;			//check bdaloukas

    // Check login and get context.
    require_login($course->id, false, $cm);
    
    if( $USER->username == 'guest'){
        redirect( "{$CFG->wwwroot}/mod/game/attempt.php?id=$id");
    }
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    add_to_log( $course->id, "game", "view", "view.php?id=$cm->id", $game->id, $cm->id);

    // Initialize $PAGE, compute blocks
    $PAGE       = page_create_instance($game->id);
    $pageblocks = blocks_setup($PAGE);
    $blocks_preferred_width = bounded_number(180, blocks_preferred_width( $pageblocks[BLOCK_POS_LEFT]), 210);

    // Print the page header
    if ($edit != -1 and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }

    if(function_exists('build_navigation')){
        //for version 1.9
        $navigation = build_navigation('', $cm);
        $PAGE->print_header($course->shortname.': %fullname%','',$navigation);
    }
    else{
        if ($course->category){
            $navigation = "<a href=\"{$CFG->wwwroot}/course/view.php?id=$course->id\">$course->shortname</a> ->";
        } 
        else{
            $navigation = '';
        }

        $strgames = get_string("modulenameplural", "game");
        $strgame  = get_string("modulename", "game");

        print_header("$course->shortname: $game->name", "$course->fullname",
                 "$navigation <a href=index.php?id=$course->id>$strgames</a> -> $game->name", 
                  "", "", true, update_module_button($cm->id, $course->id, $strgame), 
                  navmenu($course, $cm));        
    }

    echo '<table id="layout-table"><tr>';

    if(!empty($CFG->showblocksonmodpages) && (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $PAGE->user_is_editing())){
        echo '<td style="width: '.$blocks_preferred_width.'px;" id="left-column">';
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        echo '</td>';
    }

    echo '<td id="middle-column">';

    // Print the main part of the page

    // Print heading and tabs (if there is more than one).
    $currenttab = 'info';
    include('tabs.php');

    // Print game name
    print_heading(format_string($game->name));

    // Print information about timings.
    $timenow = time();
    $available = $game->timeopen < $timenow && ($timenow < $game->timeclose || !$game->timeclose);
    if ($available) {
        if ($game->timeopen) {
            echo '<p>', get_string('gameopens', 'game'), ': ', userdate($game->timeopen), '</p>';
        }
        if ($game->timeclose) {
            echo '<p>', get_string('gamecloses', 'game'), ': ', userdate($game->timeclose), '</p>';
        }
    } else if ($timenow < $game->timeopen) {
        echo "<p>".get_string("gamenotavailable", "game", userdate($game->timeopen))."</p>";
    } else {
        echo "<p>".get_string("gameclosed", "game", userdate($game->timeclose))."</p>";
    }
    echo '</div>';
        
    if (has_capability('mod/game:manage', $context)) {
        $available = true;
    }

    // Show number of attempts summary to those who can view reports.
    if( isteacher($game->course, $USER->id)){
        if ($a->attemptnum = count_records('game_attempts', 'gameid', $game->id /*, 'preview', 0 */)) {
            $a->studentnum = count_records_select('game_attempts', "gameid = '$game->id' ", 'COUNT(DISTINCT userid)');
            $a->studentstring  = $course->students;

            notify("<a href=\"report.php?mode=overview&amp;id=$cm->id\">".get_string('numattempts', 'game', $a).'</a>');
        }
    }
        
    if (has_capability('mod/game:attempt', $context)) {
        //Only if the teacher sets the parameters allow playing
		game_view_capability_attempt($game, $context, $course, $available, $cm);
    }
    // Should we not be seeing if we need to print right-hand-side blocks?

    // Finish the page.
    echo '</td></tr></table>';
    print_footer($course);

// Utility functions =================================================================

    function game_review_allowed($game) {
        return true;
    }

    /** Make some text into a link to review the game, if that is appropriate. */
    function make_review_link($linktext, $game, $attempt){
        // If not even responses are to be shown in review then we don't allow any review
        if (!($game->review & GAME_REVIEW_RESPONSES)){
            return $linktext;
        }

        // If the game is still open, are reviews allowed?
        if ((!$game->timeclose or time() < $game->timeclose) and !($game->review & GAME_REVIEW_OPEN)){
            // If not, don't link.
            return $linktext;
        }

        // If the game is closed, are reviews allowed?
        if (($game->timeclose and time() > $game->timeclose) and !($game->review & GAME_REVIEW_CLOSED)){
            // If not, don't link.
            return $linktext;
        }

        // If the attempt is still open, don't link.
        if (!$attempt->timefinish){
            return $linktext;
        }

        $url = "review.php?q=$game->id&amp;attempt=$attempt->id";
        return "<a href='$url'>$linktext</a>";
    }
	
	function game_view_capability_attempt( $game, $context, $course, $available, $cm){
		global $CFG, $USER;

        $unfinished = false;
		
        // Get this user's attempts.
        if($USER->username == 'guest'){
            $attempts = array();
            $mygrade = array();
        }
        else if( $game->gamekind == 'contest')
            $attempts = array();
        else{
            $attempts = game_get_user_attempts($game->id, $USER->id); 
            if ($unfinishedattempt = game_get_user_attempt_unfinished($game->id, $USER->id)) {
                $attempts[] = $unfinishedattempt;
                $unfinished = true;
            }
            $mygrade = game_get_best_grade($game, $USER->id);
        }
        $numattempts = count($attempts);

        // Get some strings.
        $strattempt       = get_string("attempt", "game");
        $strtimetaken     = get_string("timetaken", "game");
        $strtimecompleted = get_string("timecompleted", "game");
        $strgrade         = get_string("grade");
        $strmarks         = get_string('marks', 'game');
        $strfeedback      = get_string('feedback', 'game');

        // Print table with existing attempts
        if ($attempts){

            // Work out which columns we need, taking account what data is available in each attempt.
            list($someoptions, $alloptions) = game_get_combined_reviewoptions($game, $attempts, $context);

            $gradecolumn = $someoptions->scores && $game->grade;
            $overallstats = $alloptions->scores;

            $feedbackcolumn = game_has_feedback($game->id);
            $overallfeedback = $feedbackcolumn && $alloptions->overallfeedback;

            // prepare table header
            $table->head = array($strattempt, $strtimecompleted);
            $table->align = array("center", "left");
            $table->size = array("", "");

            if ($gradecolumn){
                $table->head[] = "$strgrade / $game->grade";
                $table->align[] = 'center';
                $table->size[] = '';
            }
            if ($feedbackcolumn){
                $table->head[] = $strfeedback;
                $table->align[] = 'left';
                $table->size[] = '';
            }
            if (isset($game->showtimetaken)){
                $table->head[] = $strtimetaken;
                $table->align[] = 'center';
                $table->size[] = '';
            }

            // One row for each attempt
            foreach ($attempts as $attempt){
                $attemptoptions = game_get_reviewoptions($game, $attempt, $context);
                $row = array();

                // Add the attempt number, making it a link, if appropriate.
                $row[] = make_review_link('#' . $attempt->attempt, $game, $attempt);

                // prepare strings for time taken and date completed
                $timetaken = '';
                $datecompleted = '';
                if ($attempt->timefinish > 0){
                    // attempt has finished
                    $timetaken = format_time($attempt->timefinish - $attempt->timestart);
                    $datecompleted = userdate($attempt->timefinish);
                } 
                else if ($available){
                    // The attempt is still in progress.
                    $timetaken = format_time(time() - $attempt->timestart);
                    $datecompleted = '';
                } 
                else if ($game->timeclose){
                    // The attempt was not completed but is also not available any more becuase the game is closed.
                    $timetaken = format_time($game->timeclose - $attempt->timestart);
                    $datecompleted = userdate($game->timeclose);
                } 
                else{
                    // Something wheird happened.
                    $timetaken = '';
                    $datecompleted = '';
                }
                $row[] = $datecompleted;

                // Ouside the if because we may be showing feedback but not grades.
                $attemptgrade = game_score_to_grade( $attempt->score, $game);

                if ($gradecolumn) {
                    if ($attemptoptions->scores) {
                        // highlight the highest grade if appropriate
                        if ($overallstats && !is_null($mygrade) && $attemptgrade == $mygrade && $game->grademethod == GAME_GRADEMETHOD_HIGHEST) {
                            $formattedgrade = "<span class='highlight'>$attemptgrade</span>";
                        } 
                        else{
                            $formattedgrade = $attemptgrade;
                        }

                        $row[] = make_review_link($formattedgrade, $game, $attempt);
                    } 
                    else{
                        $row[] = '';
                    }
                }

                if ($feedbackcolumn){
                    if ($attemptoptions->overallfeedback) {
                        $row[] = game_feedback_for_grade($attemptgrade, $game->id);
                    } else {
                        $row[] = '';
                    }
                }

                if (isset($game->showtimetaken)) {
                    $row[] = $timetaken;
                }

                $table->data[] = $row;
            } // End of loop over attempts.
            print_table($table);
        }

        // Print information about the student's best score for this game if possible.
        $moreattempts = $unfinished || $numattempts < $game->attempts || $game->attempts == 0;
        if (!$moreattempts) {
            print_heading(get_string("nomoreattempts", "game"));
        }
        if ($numattempts && !is_null($mygrade)) {
            if ($overallstats) {
                if ($available && $moreattempts) {

                    $GAME_GRADE_METHOD = array();
                    $GAME_GRADE_METHOD[0] = get_string("gradehighest", "game"); 
                    $GAME_GRADE_METHOD[1] = get_string("gradeaverage", "game");
                    $GAME_GRADE_METHOD[2] = get_string("attemptfirst", "game");
                    $GAME_GRADE_METHOD[3] = get_string("attemptlast", "game");

                    $a = new stdClass;
                    $a->method = $GAME_GRADE_METHOD[$game->grademethod];
                    $a->mygrade = $mygrade;
                    $a->gamegrade = $game->grade;
                    print_heading(get_string('gradesofar', 'game', $a));
                } else {
                    print_heading(get_string('yourfinalgradeis', 'game', "$mygrade / $game->grade"));
                }
            }

            if ($overallfeedback) {
                echo '<p class="gamegradefeedback">'.game_feedback_for_grade($mygrade, $game->id).'</p>';
            }
        }

        // Print a button to start/continue an attempt, if appropriate.

		if ($available && $moreattempts) {
		    if( $game->gamekind == 'contest'){
		        require( "contest/play.php");
		    
		        game_contest_view( $game);
		    }else    
			    game_view_capability_attempt_showinfo( $game, $course, $cm, $unfinished, $numattempts);
		}else {
            print_continue($CFG->wwwroot . '/course/view.php?id=' . $course->id);
        }
    }
	
	function game_view_capability_attempt_showinfo( $game, $course, $cm, $unfinished, $numattempts)
	{
		global $CFG;
		
		echo "<br />";
		echo "<div class=\"gameattempt\">";

		if ($unfinished) {
			$buttontext = get_string('continueattemptgame', 'game');
		} else {
			// Work out the appropriate button caption.
			if ($numattempts == 0) {
				$buttontext = get_string('attemptgamenow', 'game');
			} else {
				$buttontext = get_string('reattemptgame', 'game');
			}

			// Work out if the game is temporarily unavailable because of the delay option.
			if (!empty($attempts)) {
				$tempunavailable = '';
				$lastattempt = end( $attempts);
				$lastattempttime = $lastattempt->timefinish;
                print_object($course);
				// If so, display a message and prevent the start button from appearing.
				if ($tempunavailable) {
					print_simple_box($tempunavailable, "center");
					print_continue($CFG->wwwroot . '/course/view.php?id=' . $course->id);
					$buttontext = '';
				}
			}
		}

		// Actually print the start button.
		if ($buttontext) {
			$buttontext = htmlspecialchars($buttontext, ENT_QUOTES);

			// Do we need a confirm javascript alert?
			if ($unfinished) {
				$strconfirmstartattempt =  '';
			} else if ($game->attempts) {
				$strconfirmstartattempt = addslashes(get_string('confirmstartattemptlimit','quiz', $game->attempts));
			} else {
				$strconfirmstartattempt =  '';
			}

		    $window = '_self';
			$windowoptions = '';

			// Determine the URL to use.
			$attempturl = "attempt.php?id=$cm->id";
			if (!empty($CFG->usesid) && !isset($_COOKIE[session_name()])) {
				$attempturl = sid_process_url($attempturl);
			}

                // TODO eliminate this nasty JavaScript that prints the button.
?>
<script type="text/javascript">
//<![CDATA[
document.write('<center><input type="button" value="<?php echo $buttontext ?>" onclick="javascript: <?php
                if ($strconfirmstartattempt) {
                    echo "if (confirm(\\'".addslashes_js($strconfirmstartattempt)."\\'))";
                }
?> window.open(\'<?php echo $attempturl ?>\', \'<?php echo $window ?>\', \'<?php echo $windowoptions ?>\'); " /></center>');
//]]>
</script>
<noscript>
<div>
    <?php print_heading(get_string('noscript', 'quiz')); ?>
</div>
</noscript>
<?php
		}

		echo "</div>\n";
	}


?>
