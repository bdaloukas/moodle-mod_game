<?php  // $Id: play.php,v 1.18.2.9 2011/07/29 05:34:57 bdaloukas Exp $
/**
 * This files plays the game millionaire
 * 
 * @author  bdaloukas
 * @version $Id: play.php,v 1.18.2.9 2011/07/29 05:34:57 bdaloukas Exp $
 * @package game
 **/

function game_millionaire_continue( $id, $game, $attempt, $millionaire)
{
	//User must select quiz or question as a source module
	if( ($game->quizid == 0) and ($game->questioncategoryid == 0)){
        if( $game->sourcemodule == 'quiz')
    		error( get_string('millionaire_must_select_quiz', 'game'));
        else
            error( get_string('millionaire_must_select_questioncategory','game'));
	}
	
	if( $attempt != false and $millionaire != false){
		//continue an existing game
		return game_millionaire_play( $id, $game, $attempt, $millionaire);
	}
	
	if( $attempt == false){
		$attempt = game_addattempt( $game);
	}
	
	$newrec->id = $attempt->id;
	$newrec->queryid = 0;
	$newrec->level = 0;
	$newrec->state = 0;
	
	if( !game_insert_record(  'game_millionaire', $newrec)){
		error( 'error inserting in game_millionaire');
	}

	game_millionaire_play( $id, $game, $attempt, $newrec);
}


function game_millionaire_play( $id, $game, $attempt, $millionaire)
{
	global $CFG;
    $help5050 = optional_param('Help5050_x', 0, PARAM_INT);
    $helptelephone = optional_param('HelpTelephone_x', 0, PARAM_INT);
    $helppeople = optional_param('HelpPeople_x', 0, PARAM_INT);
    $quit =  optional_param('Quit_x', 0, PARAM_INT);
	
	if( $millionaire->queryid){
		$query = get_record( 'game_queries', 'id', $millionaire->queryid);
	}else
	{
		$query = new StdClass;
	}
    
    $buttons = optional_param('buttons', 0, PARAM_INT);

    $found = 0;
    for($i=1; $i <= $buttons; $i++){
        $letter = substr( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $i-1, 1);
        $bt = optional_param('btAnswer'.$letter, 0, PARAM_BOOL);
        $bt1 = optional_param("btAnswer{$letter}1", 0, PARAM_BOOL);
        if( !empty($bt) or !empty($bt1)){
	    	game_millionaire_OnAnswer( $id, $game, $attempt, $millionaire, $query, $i);
	    	$found = 1;
	    }
	}
		
	if($found != 1){
        if( !empty($help5050))
            game_millionaire_OnHelp5050( $game, $id,  $millionaire, $game, $query);
        else if( !empty($helptelephone))
            game_millionaire_OnHelpTelephone( $game, $id, $millionaire, $query);
        else if( !empty($helppeople))
            game_millionaire_OnHelpPeople( $game, $id, $millionaire, $query);
        else if( !empty($quit))
            game_millionaire_OnQuit( $id,  $game, $attempt, $query);
        else
        {
            $millionaire->state = 0;
            $millionaire->grade = 1;

            game_millionaire_ShowNextQuestion( $id, $game, $attempt, $millionaire);
        }
    }
}
  

function game_millionaire_showgrid( $game, $millionaire, $id, $query, $aAnswer, $info)
{	
	$question = str_replace( '\"', '"', $query->questiontext);
	
	$textlib = textlib_get_instance();
	
	$color1 = 'black';
	$color2 = 'DarkOrange';
	$colorback="white";
	$stylequestion = "background:$colorback;color:$color1";
	$stylequestionselected = "background:$colorback;color:$color2";

	global $CFG;

	$state = $millionaire->state;
	$level = $millionaire->level;
	
	if( $game->param8 == '')
	    $color = 408080;
	else
	    $color = base_convert($game->param8, 10, 16);
	    
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
    
	$dirgif = "{$CFG->wwwroot}/mod/game/millionaire/1/";
    if( $state & 1)
    {
		$gif = "5050x.gif";
		$disabled = "disabled=1";
    }else
    {
		$gif = "5050.gif";
		$disabled = "";
    }
		echo '<input type="image" '.$disabled.' name="Help5050" id="Help5050" Title="50 50" src="'.$dirgif.$gif.'" alt="" border="0">&nbsp;';

    if( $state & 2)
    {
      $gif = "telephonex.gif";
      $disabled = "disabled=1";
    }else
    {
      $gif = "telephone.gif";
      $disabled = "";
    }		
		echo '<input type="image" name="HelpTelephone" '.$disabled.' id="HelpTelephone" Title="'.get_string( 'millionaire_telephone', 'game').'" src="'.$dirgif.$gif.'" alt="" border="0">&nbsp;';

    if( $state & 4)
    {
      $gif = "peoplex.gif";
      $disabled = "disabled=1";
    }else
    {
      $gif = "people.gif";
      $disabled = "";
    }		
	echo '<input type="image" name="HelpPeople" '.$disabled.' id="HelpPeople" Title="'.get_string( 'millionaire_helppeople', 'game').'" src="'.$dirgif.$gif.'" alt="" border="0">&nbsp;';

	echo '<input type="image" name="Quit" id="Quit" Title="'.get_string( 'millionaire_quit', 'game').'" src="'.$dirgif.'x.gif" alt="" border="0">&nbsp;';
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
        echo "<tr>";
        $question = game_filtertext($question, $game->course);
        echo "<td rowspan=12 colspan=2 valign=top $styletext>$question</td>\r\n";
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
        $style = "background:$color2;color:$color1";
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
    for( $i=0; $i < count( $aAnswer); $i++)
    {
		$name = "btAnswer".substr( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $i, 1);
		$s = $textlib->substr( $letters, $i, 1);
      
		$disabled = ( $state == 15 ? "disabled=1" : "");
        
		$style = $stylequestion;
        if( (strpos( $aAnswer[ $i], 'color:') != false) or (strpos( $aAnswer[ $i], 'background:') != false)){
            $style = '';
        }
		if( $state == 15 and $i+1 == $query->correct){
			$style = $stylequestionselected;
		}
            		
		$button = '<input style="'.$style.'" '.$disabled.'type="submit" name="'.$name.'1" value="'.$s.'" id="'.$name."1\"".
                " onmouseover=\"this.style.backgroundColor = '$color2';$name.style.backgroundColor = '$color2';\" ".
                " onmouseout=\"this.style.backgroundColor = '$colorback';$name.style.backgroundColor = '$colorback';\" >";
  	 	$answer = "<span id=$name style=\"$style\" ".
          	 	" onmouseover=\"this.style.backgroundColor = '$color2';{$name}1.style.backgroundColor = '$color2';\" ".
          	 	" onmouseout=\"this.style.backgroundColor = '$colorback';{$name}1.style.backgroundColor = '$colorback';\" >".
          	 	game_filtertext($aAnswer[ $i],$game->course).'</span>';
		if( $aAnswer[ $i] != ""){
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

function game_millionaire_ShowNextQuestion( $id, $game, $attempt, $millionaire)
{
	game_millionaire_SelectQuestion( $aAnswer, $game, $attempt, $millionaire, $query);
	
	if( $game->toptext != ''){
		echo $game->toptext.'<br><br>';
	}
	
	game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, "");
	
	if( $game->bottomtext != ''){
		echo '<br>'.$game->bottomtext;
	}
}

//updates tables: games_millionaire, game_attempts, game_questions
function game_millionaire_SelectQuestion( &$aAnswer, $game, $attempt, &$millionaire, &$query)
{
	global $CFG, $USER;
	
	if( ($game->sourcemodule != 'quiz') and ($game->sourcemodule != 'question')){
		error( get_string('millionaire_sourcemodule_must_quiz_question', 'game', get_string( 'modulename', 'quiz')).' '.get_string( 'modulename', $attempt->sourcemodule));
	}
	
	if( $millionaire->queryid != 0){
		game_millionaire_loadquestions( $millionaire, $query, $aAnswer);
		return;
	}

	if( $game->sourcemodule == 'quiz'){
		if( $game->quizid == 0){
			error( get_string( 'must_select_quiz', 'game'));
		}		
		$select = "qtype='multichoice' AND quiz='$game->quizid' ".
						" AND {$CFG->prefix}quiz_question_instances.question={$CFG->prefix}question.id";
		$table = "question,{$CFG->prefix}quiz_question_instances";
	}else
	{
		if( $game->questioncategoryid == 0){
			error( get_string( 'must_select_questioncategory', 'game'));
		}	
		
		//include subcategories				
		$select = 'category='.$game->questioncategoryid;
        if( $game->subcategories){
            $cats = question_categorylist( $game->questioncategoryid);
            if( strpos( $cats, ',') > 0){
                $select = 'category in ('.$cats.')';
            }
        }  						
		$select .= " AND qtype='multichoice'";
		
		$table = "question";
	}
	$select .= " AND {$CFG->prefix}question.hidden=0";
	if( $game->shuffle)
		$questionid = game_question_selectrandom( $game, $table, $select, "{$CFG->prefix}question.id as id", true);
	else
		$questionid = game_millionaire_select_serial_question( $game, $table, $select, "{$CFG->prefix}question.id as id", $millionaire->level);
	
	if( $questionid == 0){
		error( get_string( 'no_questions', 'game'));
	}
	
	$q = get_record_select( 'question', "id=$questionid",'id,questiontext');

	$recs = get_records_select( 'question_answers', "question=$questionid");
	
	if( $recs === false){
		error( get_string( 'no_questions', 'game'));
	}
	
	$correct = 0;
	$ids = array();
	foreach( $recs as $rec){
		$aAnswer[] = $rec->answer;
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
	$query->gameid = $game->id;
	$query->userid = $USER->id;
	$query->sourcemodule = $game->sourcemodule;	
    $query->glossaryentryid = 0;
	$query->questionid = $questionid;
	$query->questiontext = addslashes( $q->questiontext);
	$query->answertext = implode( ',', $ids);
	$query->correct = array_search( $correct, $ids) + 1;
	if( !$query->id = insert_record(  'game_queries', $query)){
	    print_object( $query);
		error( 'error inserting to game_queries');
	}
	
	$updrec->id = $millionaire->id;
	$updrec->queryid = $query->id;	
	
	if( !$newid = update_record(  'game_millionaire', $updrec)){
		error( 'error updating in game_millionaire');
	}
	
	$score = $millionaire->level / 15;
	game_updateattempts( $game, $attempt, $score, 0);
	game_update_queries( $game, $attempt, $query, $score, '');
}

function game_millionaire_select_serial_question( $game, $table, $select, $id_fields="id", $level)
{
    global $CFG, $USER;
    
    if( $game->sourcemodule == 'quiz')
    {        
        $rec = get_record_select( 'quiz', "id=$game->quizid");
        if( $rec === false)
            return false;
        $questions = $rec->questions;
        $questions = explode( ',', $rec->questions);
        array_pop( $questions);
    }else
    {
        $sql  = "SELECT $id_fields,$id_fields FROM {$CFG->prefix}".$table." WHERE $select ORDER BY {$CFG->prefix}question.name";
    	if( ($recs = get_records_sql( $sql)) == false)
            return false;
        $questions = array();
        foreach( $recs as $rec)
            $questions[] = $rec->id;            
    }
    $count = count( $questions);
    
    $from = $level * $count / 15;
    $to = max( $from, ($level+1) * $count / 15 - 1);
    $pos = mt_rand( round( $from), round( $to));
    
    return $questions[ $pos];		
}

function game_millionaire_loadquestions( $millionaire, &$query, &$aAnswer)
{
	$query = get_record_select( 'game_queries', "id=$millionaire->queryid",'id,questiontext,answertext,correct');

	$aids = explode( ',', $query->answertext);
	$aAnswer = array();
	foreach( $aids as $id)
	{
		$rec = get_record_select( 'question_answers', "id=$id",'id,answer');
		$aAnswer[] = $rec->answer;
	}
}

//flag 1:5050, 2:telephone 4:people
function game_millionaire_setstate( &$millionaire, $mask)
{	
	$millionaire->state |= $mask;
	
	$updrec->id = $millionaire->id;
	$updrec->state = $millionaire->state;
	if( !update_record(  'game_millionaire', $updrec)){
		error( 'error updating in game_millionaire');
	}	
}


function game_millionaire_onhelp5050( $game, $id,  &$millionaire, $query)
{
	game_millionaire_loadquestions( $millionaire, $query, $aAnswer);
	
	if( ($millionaire->state & 1) != 0)
	{
		game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, '');
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
	
	game_millionaire_showgrid(  $game, $millionaire, $id, $query, $aAnswer, '');
}

    function game_millionaire_OnHelpTelephone(  $game, $id,  &$millionaire, $query)
    {
		game_millionaire_loadquestions( $millionaire, $query, $aAnswer);

		if( ($millionaire->state & 2) != 0)
		{
			game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, '');
			return;
		}
		
		game_millionaire_setstate( $millionaire, 2);
        
		$n = count( $aAnswer);
		if( $n < 2){
			$wrong = $correct;
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
		
        game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, $info);
    }

    function game_millionaire_OnHelpPeople( $game, $id,  &$millionaire, $query)
    {
		$textlib = textlib_get_instance();

		game_millionaire_loadquestions( $millionaire, $query, $aAnswer);
		
		if( ($millionaire->state & 4) != 0){
			game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, '');
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
			$info .= "<br>".  $textlib->substr(  get_string( 'lettersall', 'game'), $i, 1) ." : ".$aPercent[ $i]. ' %';
		}  
		
        game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, $textlib->substr( $info, 4));
    }
  

    function game_millionaire_OnAnswer( $id, $game, $attempt, &$millionaire, $query, $answer)
    {
		global $CFG;

		game_millionaire_loadquestions( $millionaire, $query, $aAnswer);
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

		$updrec->id = $millionaire->id;
		$updrec->level = $millionaire->level;
		$updrec->queryid = 0;
		if( !update_record(  'game_millionaire', $updrec)){
			error( 'error updating in game_millionaire');
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
				
				game_millionaire_ShowNextQuestion( $id, $game, $attempt, $millionaire, $query);
			}
		}else
		{
			//wrong answer
			$info = get_string( 'millionaire_info_wrong_answer', 'game').
					'<br><br><b><center>'.$aAnswer[ $query->correct-1].'</b>';
				
			$millionaire->state = 15;
			game_millionaire_ShowGrid( $game, $millionaire, $id, $query, $aAnswer, $info);
		}
    }

	function game_millionaire_onquit( $id, $game, $attempt, $query)
	{
		global $CFG;

		game_updateattempts( $game, $attempt, -1, true);

		if (! $cm = get_record("course_modules", "id", $id)) {
			error("Course Module ID was incorrect id=$id");
		}
		
		echo '<br>';	
		echo "<a href=\"$CFG->wwwroot/mod/game/attempt.php?id=$id\">".get_string( 'nextgame', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp; ';
		echo "<a href=\"$CFG->wwwroot/course/view.php?id=$cm->course\">".get_string( 'finish', 'game').'</a> ';
	}
	

?>
<script language="javascript">
	
	function Highlite(obj)
	{
		obj.style.backgroundColor = 'DarkOrange';
	}

	function Restore(obj)
	{
		obj.style.backgroundColor = 'Black';
	}

</script>
