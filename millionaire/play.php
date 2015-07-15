<?php  // $Id: play.php,v 1.31 2012/07/25 11:16:05 bdaloukas Exp $
/**
 * This file plays the game millionaire
 * 
 * @author  bdaloukas
 * @version $Id: play.php,v 1.31 2012/07/25 11:16:05 bdaloukas Exp $
 * @package game
 **/

function game_millionaire_continue( $id, $game, $attempt, $millionaire, $context)
{
	//User must select quiz or question as a source module
	if( ($game->quizid == 0) and ($game->questioncategoryid == 0)){
        if( $game->sourcemodule == 'quiz')
		    print_error( get_string( 'millionaire_must_select_quiz', 'game'));
        else
            print_error( get_string( 'millionaire_must_select_questioncategory', 'game'));
	}
	
	if( $attempt != false and $millionaire != false){
		//continue an existing game
		return game_millionaire_play( $id, $game, $attempt, $millionaire, $context);
	}
	
	if( $attempt == false){
		$attempt = game_addattempt( $game);
	}

	$newrec = new stdClass();
	$newrec->id = $attempt->id;
	$newrec->queryid = 0;
	$newrec->level = 0;
	$newrec->state = 0;
	
	if( !game_insert_record(  'game_millionaire', $newrec)){
		print_error( 'error inserting in game_millionaire');
	}

	game_millionaire_play( $id, $game, $attempt, $newrec, $context);
}


function game_millionaire_play( $id, $game, $attempt, $millionaire, $context)
{
	global $DB;

    $buttons = optional_param('buttons', 0, PARAM_INT);
    $Help5050_x = optional_param('Help5050_x', 0, PARAM_INT);
    $HelpTelephone_x = optional_param('HelpTelephone_x', 0, PARAM_INT);
    $HelpPeople_x = optional_param('HelpPeople_x', 0, PARAM_INT);
    $Quit_x = optional_param('Quit_x', 0, PARAM_INT);

	if( $millionaire->queryid){
		$query = $DB->get_record( 'game_queries', array( 'id' => $millionaire->queryid));
	}else
	{
		$query = new StdClass;
	}

    $found = 0;
    for($i=1; $i <= $buttons; $i++){
        $name = 'btAnswer'.$i;
        $answer = optional_param($name, '', PARAM_RAW);
        if (!empty($answer)) {
            game_millionaire_OnAnswer( $id, $game, $attempt, $millionaire, $query, $i, $context);
            $found = 1;
        }
	}
		
	if( $found == 1)
	    ;//nothing
	else if( !empty($Help5050_x))
		game_millionaire_OnHelp5050( $game, $id,  $millionaire, $game, $query, $context);
	else if( !empty($HelpTelephone_x))
		game_millionaire_OnHelpTelephone( $game, $id, $millionaire, $query, $context);
	else if( !empty($HelpPeople_x))
		game_millionaire_OnHelpPeople( $game, $id, $millionaire, $query, $context);
	else if( !empty($Quit_x))
		game_millionaire_OnQuit( $id,  $game, $attempt, $query, $context);
    else
      game_millionaire_ShowNextQuestion( $id, $game, $attempt, $millionaire, $context);
  }
  

function game_millionaire_showgrid( $game, $millionaire, $id, $query, $aAnswer, $info, $context)
{
    global $OUTPUT;

    $question = str_replace( array("\'", '\"'), array("'", '"'), $query->questiontext);

	if( $game->param8 == '')
	    $color = 408080;
	else
	    $color = substr( '000000'.base_convert($game->param8, 10, 16), -6);
	
	$color1 = 'black';
	$color2 = 'DarkOrange';
	$colorback="white";
	$stylequestion = "background:$colorback;color:$color1";
	$stylequestionselected = "background:$colorback;color:$color2";	
	
	global $CFG;

	$state = $millionaire->state;
	$level = $millionaire->level;
	
	$background = "style='background:#$color'";

	echo '<form name="Form1" method="post" action="attempt.php" id="Form1">';
	echo "<table cellpadding=0 cellspacing=0 border=0>\r\n";
	echo "<tr $background>";
	echo '<td rowspan='.(17+count( $aAnswer)).'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
	echo "<td colspan=6>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
	echo '<td rowspan='.(17+count( $aAnswer)).'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
	echo "</tr>\r\n";

	echo "<tr height=10%>";
	echo "<td $background rowspan=3 colspan=2>";
    
	$dirgif = 'millionaire/1/';
    if( $state & 1)
    {
		$gif = "5050x";
		$disabled = "disabled=1";
    }else
    {
		$gif = "5050";
		$disabled = "";
    }
	echo '<input type="image" '.$disabled.' name="Help5050" id="Help5050" Title="50 50" src="'.$OUTPUT->pix_url($dirgif.$gif, 'mod_game').'" alt="" border="0">&nbsp;';

    if( $state & 2)
    {
      $gif = "telephonex";
      $disabled = "disabled=1";
    }else
    {
      $gif = "telephone";
      $disabled = "";
    }		
		echo '<input type="image" name="HelpTelephone" '.$disabled.' id="HelpTelephone" Title="'.get_string( 'millionaire_telephone', 'game').'" src="'.$OUTPUT->pix_url($dirgif.$gif, 'mod_game').'" alt="" border="0">&nbsp;';

    if( $state & 4)
    {
      $gif = "peoplex";
      $disabled = "disabled=1";
    }else
    {
      $gif = "people";
      $disabled = "";
    }		
	echo '<input type="image" name="HelpPeople" '.$disabled.' id="HelpPeople" Title="'.get_string( 'millionaire_helppeople', 'game').'" src="'.$OUTPUT->pix_url($dirgif.$gif, 'mod_game').'" alt="" border="0">&nbsp;';

	echo '<input type="image" name="Quit" id="Quit" Title="'.get_string( 'millionaire_quit', 'game').'" src="'.$OUTPUT->pix_url($dirgif.'x', 'mod_game').'" alt="" border="0">&nbsp;';
	echo "\r\n";
    echo "</td>\r\n";

    $styletext = "";
    if( strpos( $question, 'color:') == false and strpos( $question, 'background:') == false){
        $styletext = "style='$stylequestion'";
    }

    $aVal = array( 100, 200, 300, 400, 500, 1000, 1500, 2000, 4000, 5000, 10000, 20000, 40000, 80000, 150000);
    for( $i=15; $i >= 1; $i--)
    {
      $bTR = false;
      switch( $i)
      {
      case 15:
        echo "<td rowspan=".(16+count( $aAnswer))." $background>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\r\n";
        $bTR = true;
        break;
      case 14:
      case 13:
        echo "<tr>\n";
        $bTR = true;
        break;
      case 12:
        //$question = game_filterquestion($question, $query->questionid, $context->id, $game->course);
        $question = game_show_query( $game, $query, $question);
        echo "<tr>";
        echo "<td rowspan=12 colspan=2 valign=top style=\"$styletext\">$question</td>\r\n";
        $bTR = true;
        break;
      case 11:
      case 10:
      case 9:
      case 8:
      case 7:
      case 6:
      case 5:
      case 4:
      case 3:
      case 2:
      case 1:
        echo "<tr>";
        $bTR = true;
        break;
      default:
        echo "<tr>";
        $bTR = true;
      }
      
      if( $i == $level+1)
        $style = "background:$color1;color:$color2";
      else
        $style = $stylequestion;
      echo "<td style='$style' align=right>$i</td>";
      
      if( $i < $level+1)
        echo "<td style='$style'>&nbsp;&nbsp;*&nbsp;&nbsp;&nbsp;</td>";
      else if( $i == 15 and $level <= 1)
        echo "<td style='$style'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
      else
        echo "<td style='$style'></td>";
      echo "<td style='$style' align=right>".sprintf( "%10d", $aVal[ $i-1])."</td>\r\n";
      if( $bTR)
        echo "</tr>\r\n";
    }
    echo "<tr $background><td colspan=10>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>\r\n";

    $bFirst = true;
    $letters = get_string( 'lettersall', 'game');
    for( $i=1; $i <= count( $aAnswer); $i++)
    {
		$name = "btAnswer".$i;
		$s = game_substr( $letters, $i-1, 1);
      
		$disabled = ( $state == 15 ? "disabled=1" : "");
        
		$style = $stylequestion;
        if( (strpos( $aAnswer[ $i-1], 'color:') != false) or (strpos( $aAnswer[ $i-1], 'background:') != false)){
            $style = '';
        }
		if( $state == 15 and $i+1 == $query->correct){
			$style = $stylequestionselected;
		}
            		
		$button = '<input style="'.$style.'" '.$disabled.'type="submit" name="'.$name.'" value="'.$s.'" id="'.$name."1\"".
                " onmouseover=\"this.style.backgroundColor = '$color2';$name.style.backgroundColor = '$color2';\" ".
                " onmouseout=\"this.style.backgroundColor = '$colorback';$name.style.backgroundColor = '$colorback';\" >";
        $text = game_filtertext($aAnswer[ $i-1],$game->course);
  	 	$answer = "<span id=$name style=\"$style\" ".
          	 	" onmouseover=\"this.style.backgroundColor = '$color2';{$name}1.style.backgroundColor = '$color2';\" ".
          	 	" onmouseout=\"this.style.backgroundColor = '$colorback';{$name}1.style.backgroundColor = '$colorback';\" >".
          	 	$text.'</span>';
		if( $aAnswer[ $i-1] != ""){
			echo "<tr>\n";
			
            echo "<td style='$stylequestion'> $button</td>\n";
			echo "<td $style width=100%> &nbsp; $answer</td>";
			if( $bFirst){
				$bFirst = false;
				$info = game_filtertext($info, $game->course);
				echo "<td style=\"$style\" rowspan=".count( $aAnswer)." colspan=3>$info</td>";
			}
    		echo "\r\n</tr>\r\n";
		}
	}
	echo "<tr><td colspan=10 $background>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>\r\n";
	echo "<input type=hidden name=state value=\"$state\">\r\n";
	echo '<input type=hidden name=id value="'.$id.'">';
	echo "<input type=hidden name=buttons value=\"".count( $aAnswer)."\">\r\n";

    echo "</table>\r\n";
    echo "</form>\r\n";
}

function game_millionaire_ShowNextQuestion( $id, $game, $attempt, $millionaire, $context)
{
	game_millionaire_SelectQuestion( $aAnswer, $game, $attempt, $millionaire, $query, $context);
	
	if( $game->toptext != ''){
		echo $game->toptext.'<br><br>';
	}
	
	game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, "", $context);
	
	if( $game->bottomtext != ''){
		echo '<br>'.$game->bottomtext;
	}
}

//updates tables: games_millionaire, game_attempts, game_questions
function game_millionaire_SelectQuestion( &$aAnswer, $game, $attempt, &$millionaire, &$query, $context)
{
	global $DB, $USER;
	
	if( ($game->sourcemodule != 'quiz') and ($game->sourcemodule != 'question')){
		print_error( get_string('millionaire_sourcemodule_must_quiz_question', 'game', get_string( 'modulename', 'quiz')).' '.get_string( 'modulename', $attempt->sourcemodule));
	}
	
	if( $millionaire->queryid != 0){
		game_millionaire_loadquestions( $game, $millionaire, $query, $aAnswer, $context);
		return;
	}

	if( $game->sourcemodule == 'quiz'){
		if( $game->quizid == 0){
			print_error( get_string( 'must_select_quiz', 'game'));
		}
        if( game_get_moodle_version() < '02.07')
        {
		    $select = "qtype='multichoice' AND quiz='$game->quizid' AND qmo.questionid=q.id".
						" AND qqi.question=q.id";
		    $table = "{quiz_question_instances} qqi,{question} q, {qtype_multichoice_options} qmo";
        }else
        {
		    $select = "qtype='multichoice' AND qs.quizid='$game->quizid' AND qmo.questionid=q.id".
						" AND qs.questionid=q.id";
		    $table = "{quiz_slots} qs,{question} q, {qtype_multichoice_options} qmo";    
        }
	}else
	{
		if( $game->questioncategoryid == 0){
			print_error( get_string( 'must_select_questioncategory', 'game'));
		}	
		
		//include subcategories				
		$select = 'category='.$game->questioncategoryid;
        if( $game->subcategories){
            $cats = question_categorylist( $game->questioncategoryid);
            if( count( $cats)){
                $select = 'q.category in ('.implode(',', $cats).')';
            }            
        }  						
		$select .= " AND qtype='multichoice' AND qmo.single=1 AND qmo.questionid=q.id";
		
		$table = '{question} q, {qtype_multichoice_options} qmo';
	}
	$select .= ' AND hidden=0';
	if( $game->shuffle or $game->quizid == 0)
		$questionid = game_question_selectrandom( $game, $table, $select, 'q.id as id', true);
	else
		$questionid = game_millionaire_select_serial_question( $game, $table, $select, 'q.id as id', $millionaire->level);
	
	if( $questionid == 0){
		print_error( get_string( 'no_questions', 'game'));
	}
	
	$q = $DB->get_record( 'question', array( 'id' => $questionid), 'id,questiontext');

	$recs = $DB->get_records( 'question_answers', array( 'question' => $questionid));
	
	if( $recs === false){
		print_error( get_string( 'no_questions', 'game'));
	}
	
	$correct = 0;
	$ids = array();
	foreach( $recs as $rec){
        $aAnswer[] = game_filterquestion_answer(str_replace( '\"', '"', $rec->answer), $rec->id, $context->id, $game->course);

		$ids[] = $rec->id;
		if( $rec->fraction == 1){
			$correct = $rec->id;
		}
	}

	$count = count( $aAnswer);
	for($i=1; $i <= $count; $i++){
		$sel = mt_rand(0, $count-1);
		
		$temp = array_splice( $aAnswer, $sel, 1);
		$aAnswer[ ] = $temp[ 0];

		$temp = array_splice( $ids, $sel, 1);
		$ids[ ] = $temp[ 0];
	}
	
	$query = new StdClass;
	$query->attemptid =$attempt->id;
	$query->gamekind = $game->gamekind;
	$query->gameid = $game->id;
	$query->userid = $USER->id;
	$query->sourcemodule = $game->sourcemodule;
    $query->glossaryentryid = 0;
	$query->questionid = $questionid;
	$query->questiontext = addslashes( $q->questiontext);
	$query->answertext = implode( ',', $ids);
	$query->correct = array_search( $correct, $ids) + 1;
	if( !$query->id = $DB->insert_record(  'game_queries', $query)){
	    print_object( $query);
		print_error( 'error inserting to game_queries');
	}
	
	$updrec = new StdClass;
	$updrec->id = $millionaire->id;
	$updrec->queryid = $query->id;	
	
	if( !$newid = $DB->update_record(  'game_millionaire', $updrec)){
		print_error( 'error updating in game_millionaire');
	}
	
	$score = $millionaire->level / 15;
	game_updateattempts( $game, $attempt, $score, 0);
	game_update_queries( $game, $attempt, $query, $score, '');
}

function game_millionaire_select_serial_question( $game, $table, $select, $id_fields="id", $level)
{
    global $DB, $USER;
    
    if( $game->sourcemodule == 'quiz')
    {
        $rec = $DB->get_record( 'quiz', array( 'id' => $game->quizid));
        if( $rec === false)
            return false;

        $questions = $rec->questions;
        $questions = explode( ',', $rec->questions);
        array_pop( $questions);
    }else
    {
        $sql  = "SELECT $id_fields,$id_fields FROM ".$table." WHERE $select ORDER BY q.name";
    	if( ($recs = $DB->get_records_sql( $sql)) == false)
            return false;
        $questions = array();
        foreach( $recs as $rec)
            $questions[] = $rec->id;            
    }
         
    $count = count( $questions);
    if( $count == 0)
        return false;
    
    $from = round($level * ($count-1) / 15);
    $to = round(max( $from, ($level+1) * ($count-1) / 15))-1;
    if( $to < $from)
        $to = $from;
    $pos = mt_rand( round( $from), round( $to));
    return $questions[ $pos];
}

function game_millionaire_loadquestions( $game, $millionaire, &$query, &$aAnswer, $context)
{
    global $DB;

	$query = $DB->get_record( 'game_queries', array( 'id' => $millionaire->queryid), 'id,questiontext,answertext,correct,questionid');

	$aids = explode( ',', $query->answertext);
	$aAnswer = array();
	foreach( $aids as $id)
	{
		$rec = $DB->get_record( 'question_answers', array( 'id' => $id), 'id,answer');
		
        $aAnswer[] = game_filterquestion_answer(str_replace( '\"', '"', $rec->answer), $id, $context->id, $game->course);		
	}
}

//flag 1:5050, 2:telephone 4:people
function game_millionaire_setstate( &$millionaire, $mask)
{
    global $DB;

	$millionaire->state |= $mask;
    
    $updrec = new stdClass();
	$updrec->id = $millionaire->id;
	$updrec->state = $millionaire->state;
	if( !$DB->update_record(  'game_millionaire', $updrec)){
		print_error( 'error updating in game_millionaire');
	}	
}


function game_millionaire_onhelp5050( $game, $id,  &$millionaire, $query, $context)
{
	game_millionaire_loadquestions( $game, $millionaire, $query, $aAnswer, $context);
	
	if( ($millionaire->state & 1) != 0)
	{
		game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, '', $context);
		return;
	}
		
	game_millionaire_setstate( $millionaire, 1);
	
	$n = count( $aAnswer);
	if( $n > 2)
	{
		for(;;)
		{
			$wrong = mt_rand( 1, $n);
			if( $wrong != $query->correct){
				break;
			}
		}
		for( $i=1; $i <= $n; $i++)
		{
			if( $i <> $wrong and $i <> $query->correct){
				$aAnswer[ $i-1] = "";
			}
		}
	}
	
	game_millionaire_showgrid(  $game, $millionaire, $id, $query, $aAnswer, '', $context);
}

    function game_millionaire_OnHelpTelephone(  $game, $id,  &$millionaire, $query, $context)
    {
		game_millionaire_loadquestions( $game, $millionaire, $query, $aAnswer, $context);

		if( ($millionaire->state & 2) != 0)
		{
			game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, '', $context);
			return;
		}
		
		game_millionaire_setstate( $millionaire, 2);
        
		$n = count( $aAnswer);
		if( $n < 2){
			$wrong = $query->correct;
		}else
		{
			for(;;)
			{
				$wrong = mt_rand( 1, $n);
				if( $wrong != $query->correct)
					break;
			}
		}
		//with 80% gives the correct answer
		if( mt_rand( 1, 10) <= 8)
			$response = $query->correct;
		else
			$response = $wrong;
          
		$info = get_string( 'millionaire_info_telephone','game').'<br><b>'.$aAnswer[ $response-1].'</b>';
		
        game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, $info, $context);
    }

    function game_millionaire_OnHelpPeople( $game, $id,  &$millionaire, $query, $context)
    {
		game_millionaire_loadquestions( $game, $millionaire, $query, $aAnswer, $context);
		
		if( ($millionaire->state & 4) != 0){
			game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, '', $context);
			return;
		}
		
		game_millionaire_setstate( $millionaire, 4);
		
        $n = count( $aAnswer);
        $sum = 0;
        $aPercent = array();
        for( $i = 0; $i+1 < $n; $i++)
        {
			$percent = mt_rand( 0, 100-$sum);
			$aPercent[ $i] = $percent;
			$sum += $percent;
        }
        $aPercent[ $n-1] = 100 - $sum;
        if( mt_rand( 1, 100) <= 80)
        {
          //with percent 80% sets in the correct answer the biggest percent
          $max_pos = 0;
          for( $i=1; $i+1 < $n; $i++)
          {
            if( $aPercent[ $i] >= $aPercent[ $max_pos])
              $max_pos = $i;
          }
          $temp = $aPercent[ $max_pos];
          $aPercent[ $max_pos] = $aPercent[ $query->correct-1];
          $aPercent[ $query->correct-1] = $temp;
        }
        
        $info = '<br>'.get_string( 'millionaire_info_people', 'game').':<br>';
        for( $i=0; $i < $n; $i++){
			$info .= "<br>".  game_substr(  get_string( 'lettersall', 'game'), $i, 1) ." : ".$aPercent[ $i]. ' %';
		}  
		
        game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, game_substr( $info, 4), $context);
    }
  

    function game_millionaire_OnAnswer( $id, $game, $attempt, &$millionaire, $query, $answer, $context)
    {
		global $DB;

		game_millionaire_loadquestions( $game, $millionaire, $query, $aAnswer, $context);
		if( $answer == $query->correct)
		{
			if( $millionaire->level < 15){
				$millionaire->level++;
			}
			$finish = ($millionaire->level == 15 ? 1 : 0);			
			$scorequestion = 1;
		}else
		{
			$finish = 1;
			$scorequestion = 0;
		}

		$score = $millionaire->level / 15;
		
		game_update_queries( $game, $attempt, $query, $scorequestion, $answer);
		game_updateattempts( $game, $attempt, $score, $finish);

        $updrec = new stdClass();
		$updrec->id = $millionaire->id;
		$updrec->level = $millionaire->level;
		$updrec->queryid = 0;
		if( !$DB->update_record(  'game_millionaire', $updrec)){
			print_error( 'error updating in game_millionaire');
		}
		
		if( $answer == $query->correct)
		{
			//correct
			if( $finish){
				echo get_string( 'win', 'game');
				game_millionaire_OnQuit( $id, $game, $attempt, $query);
			}else
			{
				$millionaire->queryid = 0;		//so the next function select a new question
				
				game_millionaire_ShowNextQuestion( $id, $game, $attempt, $millionaire, $context);
			}
		}else
		{
			//wrong answer
			$info = get_string( 'millionaire_info_wrong_answer', 'game').
					'<br><br><b><center>'.$aAnswer[ $query->correct-1].'</b>';
				
			$millionaire->state = 15;
			game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, $info, $context);
		}
    }

	function game_millionaire_onquit( $id, $game, $attempt, $query)
	{
		global $CFG, $DB;

		game_updateattempts( $game, $attempt, -1, true);

		if (! $cm = $DB->get_record( 'course_modules', array( 'id' => $id))) {
			print_error( "Course Module ID was incorrect id=$id");
		}
		
		echo '<br>';	
		echo "<a href=\"{$CFG->wwwroot}/mod/game/attempt.php?id=$id\">".get_string( 'nextgame', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp; ';
		echo "<a href=\"{$CFG->wwwroot}/course/view.php?id=$cm->course\">".get_string( 'finish', 'game').'</a> ';
	}

