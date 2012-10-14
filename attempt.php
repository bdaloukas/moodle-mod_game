<?php  // $Id: attempt.php,v 1.10.2.6 2012/01/16 21:45:04 bdaloukas Exp $
/**
 * This page prints a particular attempt of game
 * 
 * @author  bdaloukas
 * @version $Id: attempt.php,v 1.10.2.6 2012/01/16 21:45:04 bdaloukas Exp $
 * @package game
 **/

    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");
    
    // remember the current time as the time any responses were submitted
    // (so as to make sure students don't get penalized for slow processing on this page)
    $timestamp = time();
        
	require_once( "header.php");

    require_once( "hangman/play.php");
    require_once( "cross/play.php");
    require_once( "cryptex/play.php");
    require_once( "millionaire/play.php");
    require_once( "sudoku/play.php");
    require_once( "bookquiz/play.php");
    require_once( "snakes/play.php");
    require_once( "hiddenpicture/play.php");

    $currenttab = 'info';
    include('tabs.php');
    
    // Now is the right time to check the open and close times.
    if (($timestamp < $game->timeopen || ($game->timeclose && $timestamp > $game->timeclose))) {
        if (!has_capability('mod/game:manage', $context)) {
            if ($timestamp < $game->timeopen) {
                $s = get_string("gamenotavailable", "game", userdate($game->timeopen));
            } else {
                $s = get_string("gameclosed", "game", userdate($game->timeclose));
            }
            error($s, "view.php?id={$cm->id}");
        }        
    }
    

	$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Teacher has requested new preview
    
    // Hangman params
    $newletter = optional_param('newletter', '', PARAM_TEXT);
    
    // Bookquiz params
    $chapterid = optional_param('chapterid', 0, PARAM_INT);

    // Sudoku params
    $pos = optional_param('pos', 0, PARAM_INT);
    $num = optional_param('num', 0, PARAM_INT);

    // Cryptex (Wordfind) params
    $q = optional_param('q', '', PARAM_TEXT);
    $answer = optional_param('answer', '', PARAM_TEXT);

    // Crossword params
    $get_g = optional_param('g', '', PARAM_TEXT); 

    
    $endofgame = optional_param('endofgame', 0, PARAM_INT);
    $finishattempt = optional_param('finishattempt', 0, PARAM_INT);

    /// Print the main part of the page
	switch( $action)
	{
	case 'crosscheck':
		$attempt = game_getattempt($game, $detail);
		$g = game_cross_unpackpuzzle($get_g);
		game_cross_continue( $id, $game, $attempt, $detail, $g, $finishattempt);
		break;
	case 'crossprint':
		$attempt = game_getattempt($game, $detail);
		game_cross_play($id, $game, $attempt, $detail, '', true, false, false, true);
		break;
    case 'sudokucheck':		//the student tries to answer a question
		$attempt = game_getattempt($game, $detail);
		game_sudoku_check_questions($id, $game, $attempt, $detail, $finishattempt);
        break;
    case 'sudokucheckg':		//the student tries to guess a glossaryenry
		$attempt = game_getattempt($game, $detail);
		game_sudoku_check_glossaryentries($id, $game, $attempt, $detail, $endofgame);
        break;
    case 'sudokucheckn':	//the user tries to guess a number
		$attempt = game_getattempt($game, $detail);
		game_sudoku_check_number($id, $game, $attempt, $detail, $pos, $num);
        break;
	case 'cryptexcheck':	//the user tries to guess a question
		$attempt = game_getattempt($game, $detail);
		game_cryptex_check($id, $game, $attempt, $detail, $q, $answer);
        break;
    case 'bookquizcheck':		//the student tries to answer a question
		$attempt = game_getattempt($game, $detail);
		game_bookquiz_check_questions($id, $game, $attempt, $detail);
        break;
    case 'snakescheck':		//the student tries to answer a question
		$attempt = game_getattempt($game, $detail);
		game_snakes_check_questions($id, $game, $attempt, $detail);
        break;
    case 'snakescheckg':		//the student tries to answer a question
		$attempt = game_getattempt($game, $detail);
		game_snakes_check_glossary($id, $game, $attempt, $detail);
        break;
    case 'hiddenpicturecheck':		//the student tries to answer a question
		$attempt = game_getattempt($game, $detail);
        $finishattempt = optional_param('finishattempt', 0, PARAM_INT);
		game_hiddenpicture_check_questions($id, $game, $attempt, $detail, $finishattempt);
        break;
    case 'hiddenpicturecheckg':		//the student tries to guess a glossaryenry
		$attempt = game_getattempt($game, $detail);
        $endofgame = optional_param('endofgame', 0, PARAM_INT);
		game_hiddenpicture_check_mainquestion($id, $game, $attempt, $detail, $endofgame);
        break;        
	case "":
		game_create($game, $id, $forcenew, $course);
		break;
	default:
		error('Not found action='.$action);
	}
    /// Finish the page
    print_footer($course);


	function game_create($game, $id, $forcenew, $course){
		global $USER, $CFG;
		
		$attempt = game_getattempt($game, $detail);

		switch($game->gamekind){
		case 'cross':
			game_cross_continue($id, $game, $attempt, $detail, '', $forcenew);
			break;
        case 'hangman':
            $action2 = optional_param('action2', '', PARAM_TEXT);
            $newletter = optional_param('newletter', '', PARAM_TEXT);
			game_hangman_continue($id, $game, $attempt, $detail, $newletter, $action2);
			break;
		case 'millionaire':
			game_millionaire_continue($id, $game, $attempt, $detail);
			break;
		case 'bookquiz':
            $chapterid = optional_param('chapterid', 0, PARAM_INT);
			game_bookquiz_continue($id, $game, $attempt, $detail, $chapterid);
			break;
		case 'sudoku':
			game_sudoku_continue($id, $game, $attempt, $detail);
			break;
		case 'cryptex':
			game_cryptex_continue($id, $game, $attempt, $detail, $forcenew);
			break;
		case 'snakes':
			game_snakes_continue($id, $game, $attempt, $detail);
			break;
		case 'hiddenpicture':
			game_hiddenpicture_continue($id, $game, $attempt, $detail);
			break;
		case 'contest':
		    require( 'contest/play.php');
            $entryid = optional_param('entryid', 0, PARAM_INT);
			game_contest_continue($id, $game, $attempt, $detail, $entryid);
			break;
		default:
			error("Game {$game->gamekind} not found");
			break;
		}
	}
	
    //inserts a record to game_attempts
    function game_addattempt($game){
        global $CFG, $USER;
        
        $newrec->gamekind = $game->gamekind;
        $newrec->gameid = $game->id;
        $newrec->userid = $USER->id;
        $newrec->timestart = time();
        $newrec->timefinish = 0;
        $newrec->timelastattempt = 0;
        $newrec->preview = 0;
        $newrec->attempt = get_field( 'game_attempts', 'max(attempt)', 'gameid', $game->id, 'userid', $USER->id) + 1;
        $newrec->score = 0;

        if (!($newid = insert_record( 'game_attempts', $newrec))){
            error("Insert game_attempts: new rec not inserted");
        }
        
        if($USER->username == 'guest'){
            $key = 'mod/game:instanceid'.$game->id;
            $_SESSION[ $key] = $newid;
        }

        return get_record_select('game_attempts', 'id='.$newid);
    }
        
        
    function game_cross_unpackpuzzle($g){
        $ret = "";
        $textlib = textlib_get_instance();
        
        $len = $textlib->strlen($g);
        while($len){
            for($i=0; $i < $len; $i++){
                $c = $textlib->substr($g, $i, 1);
                if($c >= '1' and $c <= '9'){
                    if($i > 0){
                        //found escape character
                        if($textlib->substr( $g, $i-1, 1) == '/'){
                            $g = $textlib->substr( $g, 0, $i-1).$textlib->substr( $g, $i);
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
                for( $j=$i+1; $j < $len; $j++){
                    $c = $textlib->substr( $g, $j, 1);
                    if( $c < '0' or $c > '9'){
                        break;
                    }
                }
                $count = $textlib->substr( $g, $i, $j-$i);
                $ret .= $textlib->substr( $g, 0, $i) . str_repeat( '_', $count);
                
                $g = $textlib->substr( $g, $j);
                $len = $textlib->strlen( $g);
                
            }
            else{
                $ret .= $g;
                break;
            }
        }
        
        return $ret;
    }
