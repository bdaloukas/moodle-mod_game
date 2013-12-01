<?php  // $Id: preview.php,v 1.10 2012/07/25 11:16:04 bdaloukas Exp $
/**
 * This page prints a particular attempt of game
 * 
 * @author  bdaloukas
 * @version $Id: preview.php,v 1.10 2012/07/25 11:16:04 bdaloukas Exp $
 * @package game
 **/
 
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");

    require_once( "hangman/play.php");
    require_once( "cross/play.php");
    require_once( "cryptex/play.php");
    require_once( "millionaire/play.php");
    require_once( "sudoku/play.php");
    require_once( "bookquiz/play.php");
	
    require_once( "headergame.php");

    $context = game_get_context_module_instance( $cm->id);

    if (!has_capability('mod/game:viewreports', $context)){
		print_error( get_string( 'only_teachers', 'game'));
	}

    $action  = required_param('action', PARAM_ALPHANUM);
    $gamekind  = required_param('gamekind', PARAM_ALPHANUM);
    $update  = required_param('update', PARAM_INT);

    $attemptid = required_param('attemptid', PARAM_INT);
	$attempt = $DB->get_record( 'game_attempts', array('id' => $attemptid));
	$game = $DB->get_record( 'game', array( 'id' => $attempt->gameid));
	$detail = $DB->get_record( 'game_'.$gamekind, array( 'id' => $attemptid));
    $solution = ($action == 'solution');

    $PAGE->navbar->add(get_string('preview', 'game'));
        
	switch( $gamekind)
	{
	case 'cross':
	    $g = '';
	    $onlyshow = true;
	    $endofgame = false;
	    $print = false;
	    $checkbutton = false;
	    $showhtmlsolutions = false;
	    $showhtmlprintbutton = true;
	    $showstudentguess = false;
		game_cross_play( $update, $game, $attempt, $detail, $g, $onlyshow, $solution, $endofgame, $print, $checkbutton, $showhtmlsolutions, $showhtmlprintbutton, $showstudentguess, $context);
		break;
	case 'sudoku':
		game_sudoku_play( $update, $game, $attempt, $detail, true, $solution, $context);
		break;
	case 'hangman':
        $preview = ($action == 'preview');
		game_hangman_play( $update, $game, $attempt, $detail, $preview, $solution, $context);
		break;
	case 'cryptex':
		$crossm = $DB->get_record( 'game_cross', array('id' => $attemptid));
		game_cryptex_play( $update, $game, $attempt, $detail, $crossm, false, true, $solution, $context);
		break;
	}

    echo $OUTPUT->footer();
