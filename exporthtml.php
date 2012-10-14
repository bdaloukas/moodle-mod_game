<?php  // $Id: exporthtml.php,v 1.12.2.8 2011/07/29 21:07:02 bdaloukas Exp $
/**
 * This page export the game to html for games: cross, hangman
 * 
 * @author  bdaloukas
 * @version $Id: exporthtml.php,v 1.12.2.8 2011/07/29 21:07:02 bdaloukas Exp $
 * @package game
 **/
 
    require_once( "exportjavame.php");
    require_once( "exporthtml_millionaire.php");
        
    function game_OnExportHTML( $game, $html){
        global $CFG;

        if( $game->gamekind == 'cross'){
            $destdir = "{$CFG->dataroot}/{$game->course}/export";
            if( !file_exists( $destdir)){
                mkdir( $destdir);
            }
            game_OnExportHTML_cross( $game, $html, $destdir);
            return;
        }

        $destdir = game_export_createtempdir();
                
        switch( $game->gamekind){
        case 'hangman':
            game_OnExportHTML_hangman( $game, $html, $destdir);
            break;
        case 'millionaire':
            game_OnExportHTML_millionaire( $game, $html, $destdir);
            break;
        case 'snakes':
            game_OnExportHTML_snakes( $game, $html, $destdir);
            break;
        }

        remove_dir( $destdir);
    }
    
    function game_OnExportHTML_cross( $game, $html, $destdir){
  
        global $CFG;
    
        if( $html->filename == ''){
            $html->filename = 'cross';
        }
        
        $filename = $html->filename . '.htm';
        
        require( "cross/play.php");
        $attempt = game_getattempt( $game, $crossrec);
        if( $crossrec == false){
            game_cross_new( $game, $game->id, $crossm);
            $attempt = game_getattempt( $game, $crossrec);
        }
        
        $ret = game_export_printheader( $html->title);
        
        echo "$ret<br>";
        
        ob_start();

        game_cross_play( 0, $game, $attempt, $crossrec, '', true, false, false, false, $html->checkbutton, true, $html->printbutton, false);

        $output_string = ob_get_contents();
        ob_end_clean();
                
        $course = get_record_select( 'course', "id={$game->course}");
        
        $filename = $html->filename . '.htm';
        
        file_put_contents( $destdir.'/'.$filename, $ret . "\r\n" . $output_string);
        game_send_stored_file($destdir.'/'.$filename);
    }
    
    function game_export_printheader( $title, $showbody=true)
    {
        $ret = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
        $ret .= '<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="el" xml:lang="el">'."\n";
        $ret .= "<head>\n";
        $ret .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
        $ret .= '<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">'."\n";
        $ret .= "<title>$title</title>\n";
        $ret .= "</head>\n";
        if( $showbody)
            $ret .= "<body>";              
        
        return $ret;
    }    
    
    function game_OnExportHTML_hangman( $game, $html, $destdir){
    
        global $CFG;
        
        if( $html->filename == ''){
            $html->filename = 'hangman';
        }
        
        if( $game->param10 <= 0)
            $game->param10 = 6;
        
        $filename = $html->filename . '.htm';
        
        $ret = game_export_printheader( $html->title, false);
        $ret .= "\r<body onload=\"reset()\">\r";

        $export_attachment = ( $html->type == 'hangmanp');
        $map = game_exmportjavame_getanswers( $game, $export_attachment);
        if( $map == false){
            error( 'No Questions');
        }

        ob_start();
        
        //Here is the code of hangman
        require( "exporthtml_hangman.php");        
          
        $output_string = ob_get_contents();
        ob_end_clean();
               
        $courseid = $game->course;
        $course = get_record_select( 'course', "id=$courseid");
                
        $filename = $html->filename . '.htm';
        
        file_put_contents( $destdir.'/'.$filename, $ret . "\r\n" . $output_string);
        
        if( $html->type != 'hangmanp')
        {
            //Not copy the standard pictures when we use the "Hangman with pictures"
            $src = $CFG->dirroot.'/mod/game/hangman/1';
            game_copyfiles( $src, $destdir);
	    }
		
		$filezip = game_create_zip( $destdir, $courseid, $html->filename.'.zip');
        game_send_stored_file($filezip);
    }


    function game_OnExportHTML_millionaire( $game, $html, $destdir){
    
        global $CFG;
        
        if( $html->filename == ''){
            $html->filename = 'millionaire';
        }
        
        $filename = $html->filename . '.htm';
        
        $ret = game_export_printheader( $html->title, false);
        $ret .= "\r<body onload=\"Reset();\">\r";

        $questions = game_millionaire_html_getquestions( $game, $maxquestions, $countofquestions);
        ob_start();

        game_millionaire_html_print( $game, $questions, $maxquestions);
                        
        //End of millionaire code        
        $output_string = ob_get_contents();
        ob_end_clean();
                        
        $courseid = $game->course;
        $course = get_record_select( 'course', "id=$courseid");
                
        $filename = $html->filename . '.htm';
        
        file_put_contents( $destdir.'/'.$filename, $ret . "\r\n" . $output_string);
        
        //Copy the standard pictures of Millionaire
        $src = $CFG->dirroot.'/mod/game/millionaire/1';
        game_copyfiles( $src, $destdir);
		
		$filezip = game_create_zip( $destdir, $courseid, $html->filename.'.zip');	
        game_send_stored_file($filezip);
    }

    function game_OnExportHTML_snakes( $game, $html, $destdir){
    
        global $CFG;
        
        if( $html->filename == ''){
            $html->filename = 'snakes';
        }
        
        $filename = $html->filename . '.htm';
        
        $ret = '';

        $board = get_record_select( 'game_snakes_database', 'id='.$game->param3);

    	if( ($game->sourcemodule == 'quiz') or ($game->sourcemodule == 'question'))
            $questionsM = game_millionaire_html_getquestions( $game, $maxquestions, $countofquestionsM, $retfeedback);
        else
        {
            $questionsM = array();
            $countofquestionsM = 0;
            $retfeedback = '';
        }
        $questionsS = game_exmportjavame_getanswers( $game, false);

        ob_start();
        
        //Here is the code of hangman
        require( "exporthtml_snakes.php");        
          
        $output_string = ob_get_contents();
        ob_end_clean();
               
        $courseid = $game->course;
        $course = get_record_select( 'course', "id=$courseid");
                
        $filename = $html->filename . '.htm';
        
        file_put_contents( $destdir.'/'.$filename, $ret . "\r\n" . $output_string);
        
        $src = $CFG->dirroot.'/mod/game/export/html/snakes';
        game_copyfiles( $src, $destdir);

        mkdir( $destdir .'/css');
        $src = $CFG->dirroot.'/mod/game/export/html/snakes/css';
        game_copyfiles( $src, $destdir.'/css');

        mkdir( $destdir .'/js');
        $src = $CFG->dirroot.'/mod/game/export/html/snakes/js';
        game_copyfiles( $src, $destdir.'/js');

        mkdir( $destdir .'/images');
        $src = $CFG->dirroot.'/mod/game/snakes/1';
        game_copyfiles( $src, $destdir.'/images');
        copy( $CFG->dirroot.'/mod/game/snakes/boards/'.$board->fileboard, $destdir.'/images/'.$board->fileboard);

		$filezip = game_create_zip( $destdir, $courseid, $html->filename.'.zip');
        game_send_stored_file($filezip);
    }

    function game_copyfiles( $src, $destdir)
    {
	    $handle = opendir( $src);
	    while (($item = readdir($handle)) !== false)
        {
            if( $item == '.' or $item == '..')
                continue;

            if( strpos( $item, '.') === false)
                continue;
        
	    	if(is_dir($src.'/'.$item))
                continue;

	    	copy( $src.'/'.$item, $destdir.'/'.$item);
	    }
        closedir($handle);
    }

