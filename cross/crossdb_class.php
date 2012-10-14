<?php  // $Id: crossdb_class.php,v 1.8.2.7 2011/07/24 12:36:53 bdaloukas Exp $
/**
 * This class is a cross that can load and save to a table
 * 
 * @author  bdaloukas
 * @version $Id: crossdb_class.php,v 1.8.2.7 2011/07/24 12:36:53 bdaloukas Exp $
 * @package game
 **/

class CrossDB extends Cross
{
	function save( $game, &$crossm, $crossd, $id)
	{
		global $USER;
		
		$crossm->id = $id;
		$crossm->sourcemodule = $game->sourcemodule;

		if (!(game_insert_record( 'game_cross', $crossm))){
			error('Insert page: new page game_cross not inserted');
		}
		foreach( $crossd as $rec)
		{
			$rec->attemptid = $id;
			$rec->questiontext = addslashes( $rec->questiontext);
			
			$rec->gameid = $game->id;
			$rec->userid = $USER->id;
			$rec->sourcemodule = $game->sourcemodule;

			if (!insert_record( 'game_queries', $rec)){
				error('Insert page: new page game_queries not inserted');
			}
            game_update_repetitions($game->id, $USER->id, $rec->questionid, $rec->glossaryentryid);
		}

        return true;
	}

    function delete_records( $id)
    {
        if( !delete_records( 'game_queries', 'attemptid', $id)){
            error( "Can't delete from game_queries attemptid=$id");
        }
        if( !delete_records( 'game_cross', 'id', $id)){
            error( "Can't delete from game_cross id=$id");
        }
    }
		
	

  function load( $g, &$done, &$html, $game, $attempt, $crossrec, $onlyshow, $showsolution, &$finishattempt, $showhtmlsolutions, &$language, $showstudentguess=true)
  {
	$info = '';  
    $correctLetters = 0;
    $allLetters = 0;
    $wrongLetters = 0;
	$html = '';
	$done = false;

    if( $g == ""){
		$game_questions = false;
	}

    $this->m_mincol = $this->m_minrow = 0;
    $this->m_maxcol = $crossrec->cols;
    $this->m_maxrow = $crossrec->rows;

    if( $g == ""){
		$g = str_repeat( ' ', $this->m_maxcol * $this->m_maxrow);
	}

	$load = false;
	
    $puzzle = str_repeat('.', $this->m_maxrow * $this->m_maxcol);
	if ($recs = get_records_select('game_queries', "attemptid=$crossrec->id"))
    {
		$a = array();
		foreach ($recs as $rec)
		{
			if( $rec->horizontal)
				$key = sprintf( 'h%10d %10d', $rec->row, $rec->col);
			else
				$key = sprintf( 'v%10d %10d', $rec->col, $rec->row);
			
			$a[ $key] = $rec;			
		}

		ksort( $a);
		$b = array();
		$correctletters = $wrongletters = $restletters = 0;
		foreach( $a as $rec){
			$this->updatecrossquestions( $rec, $g, $pos, $correctletters, $wrongletters, $restletters, $game, $attempt, $crossrec);
			$b[] = $rec;

			if( ($rec->col != 0) and ($rec->row != 0)){
				$load = true;
			}
		}
		$info = $this->game_cross_computecheck( $correctletters,  $wrongletters, $restletters, $pos, $game, $attempt, $done, $onlyshow, $showsolution, $finishattempt);
		$html = $this->showhtml_base( $crossrec, $b, $showsolution, $showhtmlsolutions, $showstudentguess);
        if( $language == '')
            $language = game_detectlanguage( $rec->answertext);
    }
	
	if( $load == false)
	{
		$finishattempt = true;
	}

    return $info;
  }

function game_cross_computecheck( $correctletters,  $wrongletters, $restletters, $allletters, $game, $attempt, &$done, $onlyshow, $showsolution, $finishattempt)
{
	$ret = '';
	
	if( $correctletters == 0 and $wrongletters == 0){
		return $ret;
	}
	
	$and = get_string( 'and', 'game');
	
	$a = array();
	if( $correctletters)
		$a[] = $correctletters.' '.( $correctletters > 1 ? get_string( 'cross_corrects', 'game') :get_string( 'cross_correct', 'game'));
	if( $wrongletters)
		$a[] = $wrongletters.' '.( $wrongletters > 1 ? get_string( 'cross_errors', 'game') : get_string( 'cross_error', 'game'));
	
	if(  $correctletters > 1 or $wrongletters > 1) {
		$ret = get_string( 'cross_found_many', 'game');
	}else
	{
		$ret = get_string( 'cross_found_one', 'game');
	}

	$i = 0;
	foreach( $a as $msg)
	{
		$i++;
		
		if( $i == 1){
			$ret .= ' '.$msg;
		}else if( $i == count($a))
		{
			$ret .= ' '.get_string( 'and', 'game').' '.$msg;
		}else
		{
			$ret .= ', '.$msg;
		}		
	}
	$done = ( $restletters == 0 ? true : false);

	if( $finishattempt == false){
		if( $onlyshow or $showsolution){
			return $ret;
		}
	}else{
		$done = 1;
	}

	$grade = $correctletters / $allletters;
	$ret .= '<br>'.get_string( 'grade', 'game').' '.round( $grade * 100).' %';

	game_updateattempts( $game, $attempt, $grade, $done);
	
	return $ret;
}

	//rec is a record of cross_questions
	function updatecrossquestions( &$rec, &$g, &$pos, &$correctletters, &$wrongletters, &$restletters, $game, $attempt, $crossrec)
	{
		$textlib = textlib_get_instance();

		global $USER;
	
		$word = $rec->answertext;
		$len = $textlib->strlen( $word);
		$guess = $textlib->substr( $g, $pos, $len);
		$len_guess = $textlib->strlen( $guess);;
		$pos += $len;
		
		$is_empty = true;
    
		for($i = 0; $i < $len; $i++)
		{
			if( $i < $len_guess)
				$letterguess = $textlib->substr( $guess, $i, 1);
			else
				$letterguess = " ";
				
			if( $letterguess != ' ')
			    $is_empty = false;
				
			$letterword= $textlib->substr( $word, $i, 1);
			if( $letterword != $letterguess)
			{
                if( ($letterguess != ' ' and $letterguess != '_') or ($letterword == ' '))
                    $wrongletters++;
                game_setchar( $guess, $i, '_');
                $restletters++;
		    }else
			{
			    $correctletters++;
			}
		}
		
		if( $is_empty){
			return;
		}
		if( ($rec->studentanswer == $guess )){
			return;
		}
		$rec->studentanswer = $guess;
		
		$updrec->studentanswer = $guess;
		$updrec->id = $rec->id;
		if (!update_record('game_queries', $updrec, $rec->id)){
			error('Update game_queries: not updated');
		}
		
		$score = $correctletters / $len;
		game_update_queries( $game, $attempt, $rec, $score, $guess);
	}
}

