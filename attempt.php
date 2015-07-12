<?php  // $Id: attempt.php,v 1.22 2012/07/25 23:07:43 bdaloukas Exp $
/**
 * This page prints a particular attempt of game
 * 
 * @author  bdaloukas
 * @version $Id: attempt.php,v 1.22 2012/07/25 23:07:43 bdaloukas Exp $
 * @package game
 **/    
    require_once( "../../config.php");
    require_once( "lib.php");
    require_once( "locallib.php");

    require_once( "hangman/play.php");
    require_once( "cross/play.php");
    require_once( "cryptex/play.php");
    require_once( "millionaire/play.php");
    require_once( "sudoku/play.php");
    require_once( "bookquiz/play.php");
    require_once( "snakes/play.php");
    require_once( "hiddenpicture/play.php");

    $action  = optional_param('action', "", PARAM_ALPHANUM);  // action
	
    game_show_header( $id, $game, $course, $context);
    game_do_attempt( $id, $game, $action, $course, $context);

    function game_show_header( &$id, &$game, &$course, &$context)
    {
        global $DB, $USER, $PAGE, $OUTPUT;

        $id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
        $q = optional_param('q',  0, PARAM_INT);  // game ID

        if ($id) {
            if (! $cm = get_coursemodule_from_id('game', $id)) {
                print_error('invalidcoursemodule');
            }
            if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
                print_error('coursemisconf');
            }
            if (! $game = $DB->get_record('game', array('id' => $cm->instance))) {
                print_error('invalidcoursemodule');
            }
        } else {
            if (! $game = $DB->get_record('game', array('id' => $q))) {
                print_error('invalidgameid', 'game');
            }
            if (! $course = $DB->get_record('course', array('id' => $game->course))) {
                print_error('invalidcourseid');
            }
            if (! $cm = get_coursemodule_from_instance('game', $game->id, $course->id)) {
                print_error('invalidcoursemodule');
            }
        }

        /// Check login and get context.
        require_login($course->id, false, $cm);
        $context = game_get_context_module_instance( $cm->id);
        require_capability('mod/game:view', $context);

        /// Cache some other capabilites we use several times.
        $canattempt = has_capability('mod/game:attempt', $context);
        $canreviewmine = has_capability('mod/game:reviewmyattempts', $context);

        /// Create an object to manage all the other (non-roles) access rules.
        $timenow = time();
        //$accessmanager = new game_access_manager(game::create($game->id, $USER->id), $timenow);

        /// If no questions have been set up yet redirect to edit.php
        //if (!$game->questions && has_capability('mod/game:manage', $context)) {
        //    redirect($CFG->wwwroot . '/mod/game/edit.php?cmid=' . $cm->id);
        //}

        /// Log this request.
        if( game_use_events())
        {
            require( 'classes/event/game_played.php');
            \mod_game\event\game_played::played($game, $context)->trigger();
        }else
            add_to_log($course->id, 'game', 'view', "view.php?id=$cm->id", $game->id, $cm->id);      

        /// Initialize $PAGE, compute blocks
        $PAGE->set_url('/mod/game/view.php', array('id' => $cm->id));

        $edit = optional_param('edit', -1, PARAM_BOOL);
        if ($edit != -1 && $PAGE->user_allowed_editing()) {
            $USER->editing = $edit;
        }

        // Note: MDL-19010 there will be further changes to printing header and blocks.
        // The code will be much nicer than this eventually.
        $title = $course->shortname . ': ' . format_string($game->name);

        if ($PAGE->user_allowed_editing() && !empty($CFG->showblocksonmodpages)) {
            $buttons = '<table><tr><td><form method="get" action="view.php"><div>'.
                '<input type="hidden" name="id" value="'.$cm->id.'" />'.
                '<input type="hidden" name="edit" value="'.($PAGE->user_is_editing()?'off':'on').'" />'.
                '<input type="submit" value="'.get_string($PAGE->user_is_editing()?'blockseditoff':'blocksediton').'" /></div></form></td></tr></table>';
            $PAGE->set_button($buttons);
        }

        $PAGE->set_title($title);
        $PAGE->set_heading($course->fullname);

        echo $OUTPUT->header();
    }

    function game_do_attempt( $id, $game, $action, $course, $context)
    {
        global $OUTPUT;

        $forcenew = optional_param('forcenew', false, PARAM_BOOL); // Teacher has requested new preview
        $endofgame = optional_param('endofgame', false, PARAM_BOOL);
        $pos = optional_param('pos',  0, PARAM_INT);
        $num = optional_param('num',  0, PARAM_INT);
        $q = optional_param('q',  0, PARAM_INT);
        $attemptid = optional_param('attemptid',  0, PARAM_INT);
        $g = optional_param('g',  '', PARAM_RAW);
        $finishattempt = optional_param('finishattempt',  '', PARAM_TEXT);
        $answer = optional_param('answer',  '', PARAM_TEXT);
        $continue = false;

/// Print the main part of the page
    	switch( $action)
    	{
    	case 'crosscheck':
    		$attempt = game_getattempt( $game, $detail);
    		$g = game_cross_unpackpuzzle( $g);
    		game_cross_continue( $id, $game, $attempt, $detail, $g, $finishattempt, $context);
    		break;
    	case 'crossprint':
    		$attempt = game_getattempt( $game, $detail);
    		game_cross_play( $id, $game, $attempt, $detail, '', true, false, false, true, $context);
    		break;
        case 'sudokucheck':		//the student tries to answer a question
    		$attempt = game_getattempt( $game, $detail);
    		game_sudoku_check_questions( $id, $game, $attempt, $detail, $finishattempt, $course, $context);
            $continue = true;
            break;
        case 'sudokucheckg':		//the student tries to guess a glossaryenry
    		$attempt = game_getattempt( $game, $detail);
    		$endofgame = array_key_exists( 'endofgame', $_GET);
    		$continue = game_sudoku_check_glossaryentries( $id, $game, $attempt, $detail, $endofgame, $course);
            $continue = true;
            break;
        case 'sudokucheckn':	//the user tries to guess a number
    		$attempt = game_getattempt( $game, $detail);
    		game_sudoku_check_number( $id, $game, $attempt, $detail, $pos, $num, $context);
            $continue = false;
            break;
    	case 'cryptexcheck':	//the user tries to guess a question
    		$attempt = game_getattempt( $game, $detail);
    		game_cryptex_check( $id, $game, $attempt, $detail, $q, $answer, $finishattempt, $context);
            break;
        case 'bookquizcheck':		//the student tries to answer a question
    		$attempt = game_getattempt( $game, $detail);
    		game_bookquiz_check_questions( $id, $game, $attempt, $detail, $context);
            break;
        case 'snakescheck':		//the student tries to answer a question
    		$attempt = game_getattempt( $game, $detail);
    		game_snakes_check_questions( $id, $game, $attempt, $detail, $context);
            break;
        case 'snakescheckg':		//the student tries to answer a question from glossary
    		$attempt = game_getattempt( $game, $detail);
    		game_snakes_check_glossary( $id, $game, $attempt, $detail, $context);
            break;        
        case 'hiddenpicturecheck':		//the student tries to answer a question
	    	$attempt = game_getattempt( $game, $detail);
	    	$continue = game_hiddenpicture_check_questions( $id, $game, $attempt, $detail, $finishattempt, $context);
            break;
        case 'hiddenpicturecheckg':		//the student tries to guess a glossaryenry
	    	$attempt = game_getattempt( $game, $detail);
	    	game_hiddenpicture_check_mainquestion( $id, $game, $attempt, $detail, $endofgame, $context);
            break;
        default:
            $continue = true;
            break;    
	    }
        if( $continue){
            game_create( $game, $id, $forcenew, $course, $context);
        }
/// Finish the page
        echo $OUTPUT->footer();
    }


	function game_create( $game, $id, $forcenew, $course, $context)
	{
		global $USER, $CFG, $DB;
		
		$attempt = game_getattempt( $game, $detail);
        $chapterid = optional_param('chapterid',  0, PARAM_INT);
        $newletter = optional_param('newletter',  '', PARAM_ALPHA);
        $action2 = optional_param('action2',  '', PARAM_ALPHA);

		switch( $game->gamekind)
		{
		case 'cross':
			game_cross_continue( $id, $game, $attempt, $detail, '', $forcenew, $context);
			break;
		case 'hangman':
			game_hangman_continue( $id, $game, $attempt, $detail, $newletter, $action2, $context);
			break;
		case 'millionaire':
			game_millionaire_continue( $id, $game, $attempt, $detail, $context);
			break;
		case 'bookquiz':
			game_bookquiz_continue( $id, $game, $attempt, $detail, $chapterid, $context);
			break;
		case 'sudoku':
			game_sudoku_continue( $id, $game, $attempt, $detail, '', $context);
			break;
		case 'cryptex':
			game_cryptex_continue( $id, $game, $attempt, $detail, $forcenew, $context);
			break;
		case 'snakes':
			game_snakes_continue( $id, $game, $attempt, $detail, $context);
			break;
		case 'hiddenpicture':
			game_hiddenpicture_continue( $id, $game, $attempt, $detail, $context);
			break;
		default:
			print_error( "Game {$game->gamekind} not found");
			break;
		}
	}
	
function game_cross_unpackpuzzle( $g)
{
	$ret = "";
	$len = game_strlen( $g);
	while( $len)
	{
		for( $i=0; $i < $len; $i++)
		{
			$c = game_substr( $g, $i, 1);
			if( $c >= '1' and $c <= '9'){
			    if( $i > 0){
			        //found escape character
			        if(  game_substr( $g, $i-1, 1) == '/'){
			            $g = game_substr( $g, 0, $i-1).game_substr( $g, $i);
			            $i--;
			            $len--;
			            continue;
			        }
			    }
				break;
			}
		}

		if( $i < $len){
			//found the start of a number
			for( $j=$i+1; $j < $len; $j++)
			{
				$c = game_substr( $g, $j, 1);
				if( $c < '0' or $c > '9'){
					break;
				}
			}
			$count = game_substr( $g, $i, $j-$i);
			$ret .= game_substr( $g, 0, $i) . str_repeat( '_', $count);
			
			$g = game_substr( $g, $j);
			$len = game_strlen( $g);
			
		}else
		{
			$ret .= $g;
			break;
		}
	}
	
	return $ret;
}
