<?php  // $Id: print.php,v 1.7 2012/07/25 11:16:04 bdaloukas Exp $
/**
 * This page export the game to html
 * 
 * @author  bdaloukas
 * @version $Id: print.php,v 1.7 2012/07/25 11:16:04 bdaloukas Exp $
 * @package game
 **/
    require_once("../../config.php");
    require_once("lib.php");
    require_once("locallib.php");
    
    $id = required_param('id', PARAM_INT); // Course Module ID, or
    $gameid = required_param('gameid', PARAM_INT); 

    $game = $DB->get_record( 'game', array( 'id' => $gameid));
        
    require_login( $game->course);
    
    $context = game_get_context_module_instance( $id);
    require_capability('mod/game:view', $context);    
    
    game_print( $game, $id, $context);
        
    function game_print( $game, $update, $context){
        
        if( $game->gamekind == 'cross')
            game_print_cross( $game, $update, $context);
        else if( $game->gamekind == 'cryptex')
            game_print_cryptex( $game, $update, $context);
    }
    
    function game_print_cross( $game, $update, $context)
    {
        require( "cross/play.php");

        $attempt = game_getattempt( $game, $crossrec); 
        
        $g = '';
        $onlyshow = true;
        $showsolution = false;
        $endofgame = false;
        $print = true;
        $checkbutton = false;
        $showhtmlsolutions = false;
        $showhtmlprintbutton = false;
        $showstudentguess = false;

?>
<html  dir="ltr" lang="el" xml:lang="el" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Print</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php    
        game_cross_play( $update, $game, $attempt, $crossrec, $g, $onlyshow, $showsolution, $endofgame, $print, $checkbutton, $showhtmlsolutions, $showhtmlprintbutton,$showstudentguess, $context);

    }   

    function game_print_cryptex( $game, $update, $context)
    {
        global $DB;

        require( 'cross/cross_class.php');
        require( 'cross/crossdb_class.php');
        require( "cryptex/play.php");

        $attempt = game_getattempt( $game, $crossrec); 
        
        $updateattempt = false;
        $onlyshow = true;
        $showsolution = false;
        $showhtmlprintbutton = false;    
        $print = true;
        $crossm = $DB->get_record_select( 'game_cross', "id=$attempt->id");

?>
<html  dir="ltr" lang="el" xml:lang="el" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Print</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php    
        game_cryptex_play( $update, $game, $attempt, $crossrec, $crossm, $updateattempt, $onlyshow, $showsolution, $context, $print, $showhtmlprintbutton);
    }    
 

