<?php  // $Id: locallib.php,v 1.26.2.10 2011/12/18 15:24:56 bdaloukas Exp $

/// CONSTANTS ///////////////////////////////////////////////////////////////////

/**#@+
* Options determining how the grades from individual attempts are combined to give
* the overall grade for a user
*/

define( "GAME_GRADEMETHOD_HIGHEST", "1");
define( "GAME_GRADEMETHOD_AVERAGE", "2");
define( "GAME_GRADEMETHOD_FIRST",   "3");
define( "GAME_GRADEMETHOD_LAST",    "4");

$GAME_GRADE_METHOD = array ( GAME_GRADEMETHOD_HIGHEST => get_string("gradehighest", "game"),
                             GAME_GRADEMETHOD_AVERAGE => get_string("gradeaverage", "game"),
                             GAME_GRADEMETHOD_FIRST => get_string("attemptfirst", "game"),
                             GAME_GRADEMETHOD_LAST  => get_string("attemptlast", "game"));                                  

define( "CONST_GAME_TRIES_REPETITION", "3");

/**#@-*/


function game_upper( $str, $lang='')
{
    global  $CFG;
    
	$textlib = textlib_get_instance();
    $str = $textlib->strtoupper( $str);	
	
    if( $lang != ''){
        $langfile = "{$CFG->dirroot}/mod/game/lang/$lang/game.php";
        if (file_exists($langfile)) {
            if ($result = get_string_from_file('convertfrom', $langfile, "\$convert1")) {
                eval($result);
                if ($result = get_string_from_file('convertto', $langfile, "\$convert2")) {
                    eval($result);
                    if( $convert1 != '' and $convert2 != ''){
		                $len = $textlib->strlen( $convert1);
                        for($i=0; $i < $len; $i++){
                            $str = str_replace( $textlib->substr( $convert1, $i, 1), $textlib->substr( $convert2, $i, 1), $str);
                        }
                        return $str;
                    }
                }
            }
       }
    }
	
    $convert1 = get_string( 'convertfrom', 'game');
    if( $convert1 != ""){
		$convert2 = get_string( 'convertto', 'game');
		$len = $textlib->strlen( $convert1);
		for($i=0; $i < $len; $i++){
			$str = str_replace( $textlib->substr( $convert1, $i, 1), $textlib->substr( $convert2, $i, 1), $str);
		}
    }
	
    return $str;
}


function game_showselectcontrol( $name, $a,  $input, $events=''){
	$ret = "<select id=\"$name\" name=\"$name\" $events>";
	foreach( $a as $key => $caption){
		$ret .= '<option value="'.$key.'" ';
		if( $key == $input){
			$ret .= ' selected="selected" ';
		}
		$ret .= '>'.$caption."</option>\r\n";
	}
	$ret .= "</select>\r\n";
	
	return $ret;
}

function game_showcheckbox( $name, $value)
{
	$a = array();
	$a[ 0] = get_string( 'no');
	$a[ 1] = get_string( 'yes');
	
	return game_showselectcontrol( $name, $a, $value);
	
	$ret = '<input type="checkbox" name="'.$name.'"  value="'.$value.'"';
	if( $value == 1)
		$ret .= 'checked="checked"';
	$ret .= '/>';

	return $ret;
}

//used by hangman
function game_question_shortanswer( $game, $allowspaces=false, $use_repetitions=true)
{
	switch( $game->sourcemodule)
	{
	case 'glossary':
		return game_question_shortanswer_glossary( $game, $allowspaces, $use_repetitions);
	case 'quiz':
		return game_question_shortanswer_quiz( $game, $allowspaces, $use_repetitions);
	case 'question':
		return game_question_shortanswer_question( $game, $allowspaces, $use_repetitions);
	}

	return false;
}

//used by hangman
function game_question_shortanswer_glossary( $game, $allowspaces, $use_repetitions)
{
    global $CFG;

	if( $game->glossaryid == 0){
		error( get_string( 'must_select_glossary', 'game'));
	}

    $select = "glossaryid={$game->glossaryid}";
	$table = 'glossary_entries';
	if( $game->glossarycategoryid){
		$table .= ",{$CFG->prefix}glossary_entries_categories";
		$select .= " AND {$CFG->prefix}glossary_entries_categories.entryid = {$CFG->prefix}glossary_entries.id ".
					    " AND {$CFG->prefix}glossary_entries_categories.categoryid = {$game->glossarycategoryid}";
	}
	if( $allowspaces == false){
    	$select .= " AND concept NOT LIKE '% %'  ";
    }
	
    if( ($id = game_question_selectrandom( $game, $table, $select, "{$CFG->prefix}glossary_entries.id", $use_repetitions)) == false)
        return false;
              
    $sql = 'SELECT id, concept as answertext, definition as questiontext, id as glossaryentryid, 0 as questionid, glossaryid, attachment, 0 as answerid'.
           " FROM {$CFG->prefix}glossary_entries WHERE id = $id";
    if( ($rec = get_record_sql( $sql)) == false)
        return false;
        
    if( $rec->attachment != ''){
        $rec->attachment = "glossary/{$game->glossaryid}/$rec->id/$rec->attachment";
    }
    
    return $rec;
}

//used by hangman
function game_question_shortanswer_quiz( $game, $allowspaces, $use_repetitions)
{
    global $CFG;

	if( $game->quizid == 0){
		error( get_string( 'must_select_quiz', 'game'));
	}

	$select = "qtype='shortanswer' AND quiz='$game->quizid' ".
					" AND {$CFG->prefix}quiz_question_instances.question={$CFG->prefix}question.id";
	$table = "question,{$CFG->prefix}quiz_question_instances";
	$fields = "{$CFG->prefix}question.id";
	
    if( ($id = game_question_selectrandom( $game, $table, $select, $fields, $use_repetitions)) == false)
        return false;	

	$select = "q.id=$id AND qa.question=$id".
					" AND q.hidden=0 AND qtype='shortanswer'";
	$table = "question q,{$CFG->prefix}question_answers qa";
	$fields = "qa.id as answerid, q.id, q.questiontext as questiontext, ".
	          "qa.answer as answertext, q.id as questionid, ".
	          "0 as glossaryentryid, '' as attachment";
    
    //Maybe there are more answers to one question. I use as correct the one with bigger fraction
	$recs = get_records_select( $table, $select, 'fraction DESC', $fields);
	if( $recs == false){
	    return false;
	}
	foreach( $recs as $rec){
	    return $rec;
	}
}

//used by hangman
function game_question_shortanswer_question( $game, $allowspaces, $use_repetitions)
{
    global $CFG;
	
	if( $game->questioncategoryid == 0){
		error( get_string( 'must_select_questioncategory', 'game'));
	}
        		
    $select = $CFG->prefix.'question.category='.$game->questioncategoryid;        
    if( $game->subcategories){
        $cats = question_categorylist( $game->questioncategoryid);
        if( strpos( $cats, ',') > 0){
            $select = $CFG->prefix.'question.category in ('.$cats.')';
        }
    }
	$select .= " AND qtype='shortanswer'";
	
	$table = "question";
	$fields = "{$CFG->prefix}question.id";

    if( ($id = game_question_selectrandom( $game, $table, $select, $fields, $use_repetitions)) == false)
        return false;	

	$select = "q.id=$id AND qa.question=$id".
					" AND q.hidden=0 AND qtype='shortanswer'";
	$table = "question q,{$CFG->prefix}question_answers qa";
	$fields = "qa.id as answerid, q.id, q.questiontext as questiontext, ".
	          "qa.answer as answertext, q.id as questionid, ".
	          "0 as glossaryentryid, '' as attachment";
    
    //Maybe there are more answers to one question. I use as correct the one with bigger fraction
	$recs = get_records_select( $table, $select, 'fraction DESC', $fields);
	if( $recs == false){
	    return false;
	}
	foreach( $recs as $rec){
	    return $rec;
	}
}

//used by millionaire, game_question_shortanswer_quiz, hidden picture
function game_question_selectrandom( $game, $table, $select, $id_fields="id", $use_repetitions=true)
{
    global $CFG, $USER; 
		
	$sql = "SELECT COUNT(*) AS c FROM {$CFG->prefix}$table WHERE $select";
    if( ($rec = get_record_sql( $sql)) == false)
        return false;
        
    $count = $rec->c;
    if( $count == 0)
        return false;

    $min_num = 0;
    $min_id = 0;
    for($i=1; $i <= CONST_GAME_TRIES_REPETITION; $i++){
        $sel = mt_rand(0, $count-1);
	        
	    $sql  = "SELECT $id_fields,$id_fields FROM {$CFG->prefix}$table WHERE $select";
    	if( ($recs=get_records_sql( $sql, $sel, 1)) == false){
            return false;
        }

        $id = 0;
        foreach( $recs as $rec){
            $id = $rec->id;
        }
        if( $min_id == 0){
            $min_id = $id;
        }
        
        if( $use_repetitions == false){
            return $id;
        }
        
        if( $count == 1){
            break;
        }
                
        $questionid = $glossaryentryid = 0;
        if( $game->sourcemodule == 'glossary')
            $glossaryentryid = $id;
        else
            $questionid = $id;
        
        $select2 = "gameid=$game->id AND userid='$USER->id' AND questionid='$questionid' AND glossaryentryid='$glossaryentryid'";
        if( ($rec = get_record_select( 'game_repetitions', $select2, 'id,repetitions r')) != false){
            if( ($rec->r < $min_num) or ($min_num == 0)){
                $min_num = $rec->r;
                $min_id = $id;
            }
        }else
        {
            $min_id = $questionid;
            break;
        }
  
    }

    if( $game->sourcemodule == 'glossary')
        game_update_repetitions( $game->id, $USER->id, 0, $min_id);
    else
        game_update_repetitions( $game->id, $USER->id, $min_id, 0);
    
    return $min_id;
}

function game_update_repetitions( $gameid, $userid, $questionid, $glossaryentryid){
    $select = "gameid=$gameid AND userid='$userid' AND questionid='$questionid' AND glossaryentryid='$glossaryentryid'";
    if( ($rec = get_record_select( 'game_repetitions', $select, 'id,repetitions r')) != false){
        $updrec->id = $rec->id;
        $updrec->repetitions = $rec->r + 1;
        if( !update_record( 'game_repetitions', $updrec)){
            error("Update page: can't update game_repetitions id={$updrec->id}");
        }
    }else
    {
        $newrec->gameid = $gameid;
        $newrec->userid = $userid;
        $newrec->questionid = $questionid;
        $newrec->glossaryentryid = $glossaryentryid;
        $newrec->repetitions = 1;
        
        if( $newrec->questionid == ''){
            $newrec->questionid = 0;
        }
        if( $newrec->glossaryentryid == ''){
            $newrec->glossaryentryid = 0;
        }
            
        if (!insert_record( 'game_repetitions', $newrec)){
            print_r( $newrec);
            error("Insert page: new page game_repetitions not inserted");
        }
    }
}

//used by sudoku
function game_questions_selectrandom( $game, $count=1)
{
	global $CFG;

	switch( $game->sourcemodule)
	{
	case 'quiz':

		if( $game->quizid == 0){
			error( get_string( 'must_select_quiz', 'game'));
		}
	
		$table = "question,{$CFG->prefix}quiz_question_instances";
		$select = " {$CFG->prefix}quiz_question_instances.quiz=$game->quizid".
			" AND {$CFG->prefix}quiz_question_instances.question={$CFG->prefix}question.id ".			
			" AND {$CFG->prefix}question.qtype in ('shortanswer', 'truefalse', 'multichoice')".
			" AND {$CFG->prefix}question.hidden=0";
//todo 'match'
		$field = "{$CFG->prefix}question.id as id";
		
		$table2 = 'question';
		$fields2 = 'id as questionid,0 as glossaryentryid,qtype';
		break;
	case 'glossary':
		if( $game->glossaryid == 0){
			error( get_string( 'must_select_glossary', 'game'));
		}	
		$table = 'glossary_entries ge';
		$select = "glossaryid='$game->glossaryid' ";
		if( $game->glossarycategoryid){
		    $table .= ",{$CFG->prefix}glossary_entries_categories gec";
		    $select .= " AND gec.entryid = ge.id ".
					    " AND gec.categoryid = {$game->glossarycategoryid}";
		}
		$field = 'ge.id';
		$table2 = 'glossary_entries ge';
		$fields2 = 'ge.id as glossaryentryid, 0 as questionid';
		break;
	case 'question':
		if( $game->questioncategoryid == 0){
			error( get_string( 'must_select_questioncategory', 'game'));
		}
		$table = "question";
		
		//inlcude subcategories
        $select = 'category='.$game->questioncategoryid;        
        if( $game->subcategories){
            $cats = question_categorylist( $game->questioncategoryid);
            if( strpos( $cats, ',') > 0){
                $select = 'category in ('.$cats.')';
            }
        }    		
		
		$select .= " AND {$CFG->prefix}question.qtype in ('shortanswer', 'truefalse', 'multichoice') ".
			"AND {$CFG->prefix}question.hidden=0";
//todo 'match'
		$field = "id";
		
		$table2 = 'question';
		$fields2 = 'id as questionid,0 as glossaryentryid';
		break;
	default:
		error( 'No sourcemodule defined');
		break;
	}

	$ids = game_questions_selectrandom_detail( $table, $select, $field, $count);
	if( $ids === false){
		error( get_string( 'no_questions', 'game'));
	}

	if( count( $ids) > 1){
		//randomize the array
		shuffle( $ids);
	}
	
	$ret = array();
	foreach( $ids as $id)
	{
		if( $recquestion = get_record_select( $table2, "id=$id", $fields2)){
			unset( $new);
			$new->questionid = (int )$recquestion->questionid;
			$new->glossaryentryid = (int )$recquestion->glossaryentryid;
			$ret[] = $new;
		}
	}

	return $ret;	
}

//used by game_questions_selectrandom
function game_questions_selectrandom_detail( $table, $select, $id_field="id", $count=1)
{
    global $CFG;
		
	$sql  = "SELECT $id_field,$id_field FROM {$CFG->prefix}$table WHERE $select";
	if( ($recs=get_records_sql( $sql)) == false)
        return false;
    	
	//the array contains the ids of all questions
	$a = array();
    foreach( $recs as $rec){
        $a[ $rec->id] = $rec->id;
    }

	if( $count >= count( $a)){
		return $a;
	}else
	{
		srand();
		
		$id = array_rand(  $a, $count);
		return ( $count == 1  ? array( $id) : $id);
	}
}

//Tries to detect the language of word
function game_detectlanguage( $word){
    global $CFG;
    
    $langs = get_directory_list( "{$CFG->dirroot}/mod/game/lang", '', true, true, false);
    sort( $langs);
    
    //English has more priority
    $value = 'en_utf8';
    $key = array_search( $value, $langs);
    if( $key > 0)
    {
        unset( $langs[ $key]);
        array_unshift( $langs, $value);
    }
    $langs_installed = get_list_of_languages();

    foreach( $langs as $lang){
        if( !array_key_exists( $lang, $langs_installed))
            continue;       //Not install yet this language

        $langfile = "{$CFG->dirroot}/mod/game/lang/$lang/game.php";
        if (file_exists($langfile)) {
            if ($result = get_string_from_file('lettersall', $langfile, "\$letters")) {
                eval($result);
                if( $letters != ''){
                    $word2 = game_upper( $word, $lang);
                    if( hangman_existall( $word2, $letters)){
                        return $lang;
                    }
                }
            }
       }    
    }
    
    return false;
}

//The words maybe are in two languages e.g. greek or english
//so I try to find the correct one.
function game_getallletters( $word, $lang='')
{
    global $CFG;
    
    if( $lang != ''){
        $langfile = "{$CFG->dirroot}/mod/game/lang/$lang/game.php";
        if (file_exists($langfile)) {
            if ($result = get_string_from_file('lettersall', $langfile, "\$letters")) {
                eval($result);
                if( $letters != ''){
                    if( hangman_existall( $word, $letters)){
                        return $letters;
                    }
                }
            }
       }
    }
    
    $letters = game_upper( get_string( 'lettersall', 'game'));
    if( hangman_existall( $word, $letters)){
        return $letters;
    }
    
    return '';
}

function game_get_string_lang( $identifier, $module, $lang)
{
    global $CFG;
    
    $langfile = "{$CFG->dirroot}/mod/game/lang/$lang/game.php";

    if ($result = get_string_from_file( $identifier, $langfile, "\$ret")) {
        eval($result);
        if( $ret != '')
            return $ret;
    }

    return get_string( $identifier, $module);
}

function hangman_existall( $str, $strfind)
{
	$textlib = textlib_get_instance();
	
    $n = $textlib->strlen( $str);
    for( $i=0; $i < $n; $i++)
    {
		$pos = $textlib->strpos( $strfind, $textlib->substr( $str, $i, 1));
        if( $pos === false)
            return false;
    }
  
    return true;
}

//used by cross
function game_questions_shortanswer( $game)
{
	switch( $game->sourcemodule)
	{
	case "glossary":
		$recs = game_questions_shortanswer_glossary( $game);
		break;
	case "quiz";
		$recs = game_questions_shortanswer_quiz( $game);
		break;
	case "question";
		$recs = game_questions_shortanswer_question( $game);
		break;
	}

	return $recs;
}

//used by cross
function game_questions_shortanswer_glossary( $game)
{
    global $CFG;
    
    $select = "glossaryid={$game->glossaryid}";
    $table = "{$CFG->prefix}glossary_entries ge";
    if( $game->glossarycategoryid){
		$table .= ",{$CFG->prefix}glossary_entries_categories gec";
		$select .= " AND gec.entryid = ge.id ".
					    " AND gec.categoryid = {$game->glossarycategoryid}";
    }        

    $sql = 'SELECT ge.id, concept as answertext, definition as questiontext, ge.id as glossaryentryid, 0 as questionid, attachment '.
           " FROM $table WHERE $select";

	return get_records_sql( $sql);

}
//used by cross
function game_questions_shortanswer_quiz( $game)
{
    global $CFG;
	
    if( $game->quizid == 0){
        error( get_string( 'must_select_quiz', 'game'));
    }	
    		
	$select = "qtype='shortanswer' AND quiz='$game->quizid' ".
					" AND qqi.question=q.id".
					" AND qa.question=q.id".
					" AND q.hidden=0";
	$table = "question q,{$CFG->prefix}quiz_question_instances qqi,{$CFG->prefix}question_answers qa";
	$fields = "qa.id as qaid, q.id, q.questiontext as questiontext, ".
				   "qa.answer as answertext, q.id as questionid,".
				   " 0 as glossaryentryid,'' as attachment";

	return game_questions_shortanswer_question_fraction( $table, $fields, $select);
}

//used by cross
function game_questions_shortanswer_question( $game)
{
    global $CFG;

    if( $game->questioncategoryid == 0){
        error( get_string( 'must_select_questioncategory', 'game'));
    }
    		
    //include subcategories    		
    $select = 'q.category='.$game->questioncategoryid;        
    if( $game->subcategories){
        $cats = question_categorylist( $game->questioncategoryid);
        if( strpos( $cats, ',') > 0){
            $select = 'q.category in ('.$cats.')';
        }
    }    		
    		
	$select .= " AND qtype='shortanswer' ".
					" AND qa.question=q.id".
					" AND q.hidden=0";
	$table = "question q,{$CFG->prefix}question_answers qa";
	$fields = "qa.id as qaid, q.id, q.questiontext as questiontext, ".
				   "qa.answer as answertext, q.id as questionid";
	
	return game_questions_shortanswer_question_fraction( $table, $fields, $select);
}
	
function game_questions_shortanswer_question_fraction( $table, $fields, $select)
{
    global $CFG;
    
	$sql = "SELECT $fields FROM {$CFG->prefix}$table WHERE $select ORDER BY fraction DESC";
    
	$recs = get_records_sql( $sql);
	if( $recs == false){
	    error( get_string( 'no_questions', 'game'));
	}
	
	$recs2 = array();
	$map = array();
	foreach( $recs as $rec){
	    unset( $rec2);
	    if( array_key_exists( $rec->questionid, $map)){
	        continue;
	    }
	    $rec2->id = $rec->id;
	    $rec2->questiontext = $rec->questiontext;
	    $rec2->answertext = $rec->answertext;
	    $rec2->questionid = $rec->questionid;
	    $rec2->glossaryentryid = 0;
	    $rec2->attachment = '';
	    $recs2[] = $rec2;
	    
	    $map[ $rec->questionid] = $rec->questionid;
	}

	return $recs2;
}

	function game_setchar( &$s, $pos, $char)
	{
		$ret = "";
		
		$textlib = textlib_get_instance();
	
		if( $pos > 0){
			$ret .= $textlib->substr( $s, 0, $pos);
		}
		
		$s = $ret . $char . $textlib->substr( $s, $pos+1);
	}
	
	function game_insert_record( $table, $rec)
	{
		global $CFG;
		
        if( get_record_select($table, "id=$rec->id", 'id,id') == false){
            $sql = "INSERT INTO {$CFG->prefix}$table(id) VALUES($rec->id)";
	    	if( !execute_sql( $sql, false)){
	    		error( "Cannot insert an empty $table with id=$rec->id");
	    		return false;
	    	}
	    }
		if( isset( $rec->question)){
    		$temp = $rec->question;
	    	$rec->question = addslashes( $rec->question);
	    }
		
		$ret = update_record( $table, $rec);

		if( isset( $rec->question)){
    		$rec->question = $temp;
    	}
		
		return $ret;
	}

	//if score is negative doesn't update the record
	//score is between 0 and 1
	function game_updateattempts( $game, $attempt, $score, $finished)
	{
	    if( $attempt != false){	    
		    $updrec->id = $attempt->id;
    		$updrec->timelastattempt = time();
    		$updrec->lastip = getremoteaddr();
	    	if( isset( $_SERVER[ 'REMOTE_HOST']))
	    		$updrec->lastremotehost = $_SERVER[ 'REMOTE_HOST'];
	    	else if( $updrec->lastip != '')
	    		$updrec->lastremotehost = gethostbyaddr( $updrec->lastip);
	    	else
                $updrec->lastremotehost = '';
            $updrec->lastremotehost = substr( $updrec->lastremotehost, 0, 50);

	    	if( $score >= 0){
	    		$updrec->score = $score;
	    	}

	    	if( $finished){
	    		$updrec->timefinish = $updrec->timelastattempt;
		    }
		
    		$updrec->attempts = $attempt->attempts + 1;

	    	if( !update_record( 'game_attempts', $updrec)){
	    		error( "game_updateattempts: Can't update game_attempts id=$updrec->id");
	    	}
	    	
            // update grade item and send all grades to gradebook
            game_grade_item_update( $game);
            game_update_grades( $game);    
	    }
		
		//Update table game_grades
		if( $finished){
			global $USER;
			game_save_best_score( $game, $USER->id);
		}
	}
	

	function game_updateattempts_maxgrade( $game, $attempt, $grade, $finished)
	{
		$recgrade = get_field( 'game_attempts', 'score', 'id', $attempt->id);

		if( $recgrade >  $grade){
			$grade = -1;		//don't touch the grade
		}
		
		game_updateattempts( $game, $attempt, $grade, $finished);
	}

	function game_update_queries( $game, $attempt, $query, $score, $studentanswer, $updatetries=false)
	{
		global $USER;
		
		if( $query->id != 0){
			$select = "id=$query->id";
		}else
		{
			$select = "attemptid = $attempt->id AND sourcemodule = '{$query->sourcemodule}'";
			switch( $query->sourcemodule)
			{
			case 'quiz':
				$select .= " AND questionid='$query->questionid' ";
				break;
			case 'glossary':
				$select .= " AND glossaryentryid='$query->glossaryentryid'";
				break;
			}		
		}

		if( ($recq = get_record_select( 'game_queries', $select)) === false)
		{
			unset( $recq);
			$recq->gamekind = $game->gamekind;
			$recq->gameid = $attempt->gameid;
			$recq->userid = $attempt->userid;
			$recq->attemptid = $attempt->id;
			$recq->sourcemodule = $query->sourcemodule;
			$recq->questionid = $query->questionid;
			$recq->glossaryentryid = $query->glossaryentryid;
			if ($updatetries)
				$recq->tries = 1;

			if (!($recq->id = insert_record( "game_queries", $recq))){
				error("Insert page: new page game_queries not inserted");
			}
		}
		
		$updrec->id = $recq->id;
		$updrec->timelastattempt = time();
		
        if( $score >= 0){
            $updrec->score = $score;
        }
		
		if( $studentanswer != ''){
			$updrec->studentanswer = $studentanswer;
		}
		
		if ($updatetries)
			$updrec->tries = $recq->tries + 1;
			
		if (!(update_record( "game_queries", $updrec))){
			error("game_update_queries: not updated id=$updrec->id");
		}
	}
	

	function game_getattempt( $game, &$detail)
	{
		global $USER;
		
		$select = "gameid=$game->id AND userid=$USER->id and timefinish=0 ";
		if( $USER->id == 1){
			$key = 'mod/game:instanceid'.$game->id;
			if( array_key_exists( $key, $_SESSION)){
				$select .= ' AND id="'.$_SESSION[ $key].'"';
			}else{
				$select .= ' AND id=-1';
			}
		}

		if( ($recs=get_records_select( 'game_attempts', $select))){
			foreach( $recs as $attempt){
				if( $USER->id == 1){
					$_SESSION[ $key] = $attempt->id;
				}
				
				$detail = get_record_select( 'game_'.$game->gamekind, "id=$attempt->id");

				return $attempt;
			}
        }
		return false;
	}

/**
 * @param integer $gameid the game id.
 * @param integer $userid the userid.
 * @param string $status 'all', 'finished' or 'unfinished' to control
 * @return an array of all the user's attempts at this game. Returns an empty array if there are none.
 */
function game_get_user_attempts( $gameid, $userid, $status = 'finished') {
    $status_condition = array(
        'all' => '',
        'finished' => ' AND timefinish > 0',
        'unfinished' => ' AND timefinish = 0'
    );
    if ($attempts = get_records_select( 'game_attempts',
            "gameid = '$gameid' AND userid = '$userid' AND preview = 0" . $status_condition[$status],
            'attempt ASC')) {
        return $attempts;
    } else {
        return array();
    }
}


/**
 * Returns an unfinished attempt (if there is one) for the given
 * user on the given game. This function does not return preview attempts.
 *
 * @param integer $gameid the id of the game.
 * @param integer $userid the id of the user.
 *
 * @return mixed the unfinished attempt if there is one, false if not.
 */
function game_get_user_attempt_unfinished( $gameid, $userid) {
    $attempts = game_get_user_attempts( $gameid, $userid, 'unfinished');
    if ($attempts) {
        return array_shift($attempts);
    } else {
        return false;
    }
}

/**
 * Get the best current score for a particular user in a game.
 *
 * @param object $game the game object.
 * @param integer $userid the id of the user.
 * @return float the user's current grade for this game.
 */
function game_get_best_score($game, $userid) {
    $score = get_field( 'game_grades', 'score', 'gameid', $game->id, 'userid', $userid);

    // Need to detect errors/no result, without catching 0 scores.
    if (is_numeric($score)) {
        return $score;
    } else {
        return NULL;
    }
}

function game_get_best_grade($game, $userid) {
    $score = game_get_best_score( $game, $userid);
	
	if( is_numeric( $score)){
		return round( $score * $game->grade, $game->decimalpoints);
	}else
	{
        return NULL;
    }
}

/**
 * @param integer $gameid the id of the game object.
 * @return boolean Whether this game has any non-blank feedback text.
 */
function game_has_feedback($gameid) {
	return false;
    static $cache = array();
    if (!array_key_exists($gameid, $cache)) {
        $cache[$gameid] = record_exists_select('game_feedback',
                "gameid = $gameid AND feedbacktext <> ''");
    }
    return $cache[$gameid];
}


/**
* Returns a comma separated list of question ids for the game
*
* @return string         Comma separated list of question ids
* @param string $layout  The string representing the game layout. Each page is represented as a
*                        comma separated list of question ids and 0 indicating page breaks.
*                        So 5,2,0,3,0 means questions 5 and 2 on page 1 and question 3 on page 2
*/
function game_questions_in_game($layout) {
    return str_replace(',0', '', $layout);
}

/**
 * Convert the raw score stored in $attempt into a grade out of the maximum
 * grade for this game.
 *
 * @param float $rawgrade the unadjusted grade, fof example $attempt->sumgrades
 * @param object $game the game object. Only the fields grade, sumgrades and decimalpoints are used.
 * @return float the rescaled grade.
 */
function game_score_to_grade($score, $game) {
    if ($score) {
        return round($score*$game->grade, $game->decimalpoints);
    } else {
        return 0;
    }
}

/**
 * Get the feedback text that should be show to a student who
 * got this grade on this game.
 *
 * @param float $grade a grade on this game.
 * @param integer $gameid the id of the game object.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function game_feedback_for_grade($grade, $gameid) {
	return '';
    $feedback = get_field_select('game_feedback', 'feedbacktext',
            "gameid = $gameid AND mingrade <= $grade AND $grade < maxgrade");

    if (empty($feedback)) {
        $feedback = '';
    }

    return $feedback;
}


/**
 * Determine review options
 *
 * @param object $game the game instance.
 * @param object $attempt the attempt in question.
 * @param $context the roles and permissions context,
 *          normally the context for the game module instance.
 *
 * @return object an object with boolean fields responses, scores, feedback,
 *          correct_responses, solutions and general feedback
 */
function game_get_reviewoptions($game, $attempt, $context=null) {

    $options = new stdClass;
    $options->readonly = true;
    // Provide the links to the question review and comment script
    $options->questionreviewlink = '/mod/game/reviewquestion.php';

    if ($context /* && has_capability('mod/game:viewreports', $context) */ and !$attempt->preview) {
        // The teacher should be shown everything except during preview when the teachers
        // wants to see just what the students see
        $options->responses = true;
        $options->scores = true;
        $options->feedback = true;
        $options->correct_responses = true;
        $options->solutions = false;
        $options->generalfeedback = true;
        $options->overallfeedback = true;

        // Show a link to the comment box only for closed attempts
        if ($attempt->timefinish) {
            $options->questioncommentlink = '/mod/game/comment.php';
        }
    } else {
        if (((time() - $attempt->timefinish) < 120) || $attempt->timefinish==0) {
            $game_state_mask = GAME_REVIEW_IMMEDIATELY;
        } else if (!$game->timeclose or time() < $game->timeclose) {
            $game_state_mask = GAME_REVIEW_OPEN;
        } else {
            $game_state_mask = GAME_REVIEW_CLOSED;
        }
        $options->responses = ($game->review & $game_state_mask & GAME_REVIEW_RESPONSES) ? 1 : 0;
        $options->scores = ($game->review & $game_state_mask & GAME_REVIEW_SCORES) ? 1 : 0;
        $options->feedback = ($game->review & $game_state_mask & GAME_REVIEW_FEEDBACK) ? 1 : 0;
        $options->correct_responses = ($game->review & $game_state_mask & GAME_REVIEW_ANSWERS) ? 1 : 0;
        $options->solutions = ($game->review & $game_state_mask & GAME_REVIEW_SOLUTIONS) ? 1 : 0;
        $options->generalfeedback = ($game->review & $game_state_mask & GAME_REVIEW_GENERALFEEDBACK) ? 1 : 0;
        $options->overallfeedback = $attempt->timefinish && ($game->review & $game_state_mask & GAME_REVIEW_FEEDBACK);
    }

    return $options;
}

/**
* Returns a comma separated list of question ids for the current page
*
* @return string         Comma separated list of question ids
* @param string $layout  The string representing the game layout. Each page is represented as a
*                        comma separated list of question ids and 0 indicating page breaks.
*                        So 5,2,0,3,0 means questions 5 and 2 on page 1 and question 3 on page 2
* @param integer $page   The number of the current page.
*/
function game_questions_on_page($layout, $page) {
    $pages = explode(',0', $layout);
	
    return trim($pages[$page], ',');
}

function game_compute_attempt_layout( $game, &$attempt)
{
	$ret = '';
	$recs = get_records_select( 'game_queries', "attemptid=$attempt->id", '', 'id,questionid,sourcemodule,glossaryentryid');
	if( $recs){
		foreach( $recs as $rec){
			if( $rec->sourcemodule == 'glossary'){
				$ret .= $rec->glossaryentryid.'G,';
			}else{
				$ret .= $rec->questionid.',';
			}
		}
	}
	
	$attempt->layout = $ret.'0';
}

/**
 * Combines the review options from a number of different game attempts.
 * Returns an array of two ojects, so he suggested way of calling this
 * funciton is:
 * list($someoptions, $alloptions) = game_get_combined_reviewoptions(...)
 *
 * @param object $game the game instance.
 * @param array $attempts an array of attempt objects.
 * @param $context the roles and permissions context,
 *          normally the context for the game module instance.
 *
 * @return array of two options objects, one showing which options are true for
 *          at least one of the attempts, the other showing which options are true
 *          for all attempts.
 */
function game_get_combined_reviewoptions($game, $attempts, $context=null) {
    $fields = array('readonly', 'scores', 'feedback', 'correct_responses', 'solutions', 'generalfeedback', 'overallfeedback');
    $someoptions = new stdClass;
    $alloptions = new stdClass;
    foreach ($fields as $field) {
        $someoptions->$field = false;
        $alloptions->$field = true;
    }
    foreach ($attempts as $attempt) {
        $attemptoptions = game_get_reviewoptions( $game, $attempt, $context);
        foreach ($fields as $field) {
            $someoptions->$field = $someoptions->$field || $attemptoptions->$field;
            $alloptions->$field = $alloptions->$field && $attemptoptions->$field;
        }
    }
    return array( $someoptions, $alloptions);
}

/**
 * Save the overall grade for a user at a game in the game_grades table
 *
 * @param object $quiz The game for which the best grade is to be calculated and then saved.
 * @param integer $userid The userid to calculate the grade for. Defaults to the current user.
 * @return boolean Indicates success or failure.
 */
function game_save_best_score($game, $userid = null) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    // Get all the attempts made by the user
    if (!$attempts = game_get_user_attempts( $game->id, $userid)) {
        notify('Could not find any user attempts');
        return false;
    }

    // Calculate the best grade
    $bestscore = game_calculate_best_score($game, $attempts);
    //$bestgrade = game_rescale_grade($bestgrade, $game);

    // Save the best grade in the database
    if ($grade = get_record('game_grades', 'gameid', $game->id, 'userid', $userid)) {
        $grade->score = $bestscore;
        $grade->timemodified = time();
        if (!update_record('game_grades', $grade)) {
            notify('Could not update best grade');
            return false;
        }
    } else {
        $grade->gameid = $game->id;
        $grade->userid = $userid;
        $grade->score = $bestscore;
        $grade->timemodified = time();
        if (!insert_record('game_grades', $grade)) {
            notify('Could not insert new best grade');
            return false;
        }
    }
    return true;
}

/**
* Calculate the overall grade for a game given a number of attempts by a particular user.
*
* @return float          The overall grade
* @param object $quiz    The game for which the best grade is to be calculated
* @param array $attempts An array of all the attempts of the user at the game
*/
function game_calculate_best_score($game, $attempts) {

    switch ($game->grademethod) {

        case GAME_GRADEMETHOD_FIRST:
            foreach ($attempts as $attempt) {
                return $attempt->score;
            }
            break;

        case GAME_GRADEMETHOD_LAST:
            foreach ($attempts as $attempt) {
                $final = $attempt->score;
            }
            return $final;

        case GAME_GRADEMETHOD_AVERAGE:
            $sum = 0;
            $count = 0;
            foreach ($attempts as $attempt) {
                $sum += $attempt->score;
                $count++;
            }
            return (float)$sum/$count;

        default:
        case GAME_GRADEMETHOD_HIGHEST:
            $max = 0;
            foreach ($attempts as $attempt) {
                if ($attempt->score > $max) {
                    $max = $attempt->score;
                }
            }
            return $max;
    }
}

/**
* Return the attempt with the best grade for a game
*
* Which attempt is the best depends on $game->grademethod. If the grade
* method is GRADEAVERAGE then this function simply returns the last attempt.
* @return object         The attempt with the best grade
* @param object $quiz    The game for which the best grade is to be calculated
* @param array $attempts An array of all the attempts of the user at the game
*/
function game_calculate_best_attempt($game, $attempts) {

    switch ($game->grademethod) {

        case GAME_ATTEMPTFIRST:
            foreach ($attempts as $attempt) {
                return $attempt;
            }
            break;

        case GAME_GRADEAVERAGE: // need to do something with it :-)
        case GAME_ATTEMPTLAST:
            foreach ($attempts as $attempt) {
                $final = $attempt;
            }
            return $final;

        default:
        case GAME_GRADEHIGHEST:
            $max = -1;
            foreach ($attempts as $attempt) {
                if ($attempt->sumgrades > $max) {
                    $max = $attempt->sumgrades;
                    $maxattempt = $attempt;
                }
            }
            return $maxattempt;
    }
}

/**
* Returns the number of pages in the game layout
*
* @return integer         Comma separated list of question ids
* @param string $layout  The string representing the game layout.
*/
function game_number_of_pages($layout) {
    return substr_count($layout, ',0');
}


/**
* Returns the first question number for the current game page
*
* @return integer  The number of the first question
* @param string $gamelayout The string representing the layout for the whole game
* @param string $pagelayout The string representing the layout for the current page
*/
function game_first_questionnumber($gamelayout, $pagelayout) {
    // this works by finding all the questions from the gamelayout that
    // come before the current page and then adding up their lengths.
    global $CFG;
    $start = strpos($gamelayout, ','.$pagelayout.',')-2;
    if ($start > 0) {
        $prevlist = substr($gamelayout, 0, $start);
        return get_field_sql("SELECT sum(length)+1 FROM {$CFG->prefix}question
         WHERE id IN ($prevlist)");
    } else {
        return 1;
    }
}


/**
* Loads the most recent state of each question session from the database
*
* For each question the most recent session state for the current attempt
* is loaded from the game_questions table and the question type specific data
*
* @return array           An array of state objects representing the most recent
*                         states of the question sessions.
* @param array $questions The questions for which sessions are to be restored or
*                         created.
* @param object $cmoptions
* @param object $attempt  The attempt for which the question sessions are
*                         to be restored or created.
* @param mixed either the id of a previous attempt, if this attmpt is
*                         building on a previous one, or false for a clean attempt.
*/
function game_get_question_states(&$questions, $cmoptions, $attempt, $lastattemptid = false) {
    global $CFG, $QTYPES;

    // get the question ids
    $ids = array_keys( $questions);
    $questionlist = implode(',', $ids);

    $statefields = 'questionid as question, manualcomment, score as grade';

    $sql = "SELECT $statefields".
           "  FROM {$CFG->prefix}game_questions ".
           " WHERE attemptid = '$attempt->id'".
           "   AND questionid IN ($questionlist)";
    $states = get_records_sql($sql);
	
    // loop through all questions and set the last_graded states
    foreach ($ids as $i) {	
		// Create the empty question type specific information
        if (!$QTYPES[$questions[$i]->qtype]->create_session_and_responses(
			$questions[$i], $states[$i], $cmoptions, $attempt)) {
				return false;
		}

		$states[$i]->last_graded = clone($states[$i]);
    }
    return $states;
}

function game_sudoku_getquestions( $questionlist)
{
    // Load the questions
    if (!$questions = get_records_select( 'question', "id IN ($questionlist)")) {
        error(get_string('noquestionsfound', 'quiz'));
    }

    // Load the question type specific information
    if (!get_question_options($questions)) {
        error('Could not load question options');
    }
	
    return $questions;
}

function game_filtertext( $text, $courseid){
    $formatoptions->noclean = true;
    $formatoptions->filter = 1;
    
    $text = trim( format_text( $text, FORMAT_MOODLE, $formatoptions, $courseid));
    
    if( substr( $text, 0, 3) == '<p>'){
        if( substr( $text, -4) == '</p>'){
            $text = substr( $text, 3, -4);
        }
    }
    if( substr( $text, 0, 3) == '<p>'){
        if( substr( $text, -4) == '</p>'){
            $text = substr( $text, 3, -4);
        }
    }
    
    return $text;
}

function game_tojavascriptstring( $text)
{
    $from = array('"',"\r", "\n");
    $to = array('\"', ' ', ' ');
    
    $from[] = '<script ';   $to[] = '<" + "script ';
    $from[] = '</script>';   $to[] = '<" + "/script>';
    
    $text = str_replace( $from, $to, $text);
        
    return $text;
}

function game_repairquestion( $s){
    if( substr( $s, 0, 3) == '<p>'){
        $s = substr( $s, 3);
    } 
    if( substr( $s, -4) == '</p>'){
        $s = substr( $s, 0, -4);
    }
    if( substr( $s, 0, 4) == '<br>'){
        $s = substr( $s, 4);
    }
    if( substr( $s, 0, 6) == '<br />'){
        $s = substr( $s, 6);
    }		
    if( substr( $s, 0, 5) == '<div ' and substr( $s, -6) == '</div>'){
        $pos = strpos( $s, '>');
        if( $pos != false){
            $s = substr( $s, $pos+1);
        }
        $s = substr( $s, 0, -6);
    }
    
    return $s;
}

/**
 * Delete a game attempt.
 */
function game_delete_attempt($attempt, $quiz) {
    if (is_numeric($attempt)) {
        if (!$attempt = get_record('game_attempts', 'id', $attempt)) {
            return;
        }
    }

    if ($attempt->gameid != $game->id) {
        debugging("Trying to delete attempt $attempt->id which belongs to game $attempt->gameid " .
                "but was passed gameid $game->id.");
        return;
    }

    delete_records('game_attempts', 'id', $attempt->id);
    delete_attempt( $attempt->id);

    // Search game_attempts for other instances by this user.
    // If none, then delete record for this game, this user from game_grades
    // else recalculate best grade

    $userid = $attempt->userid;
    if (!record_exists('game_attempts', 'userid', $userid, 'gameid', $game->id)) {
        delete_records('game_grades', 'userid', $userid,'gameid', $game->id);
    } else {
        game_save_best_score( $game, $userid);
    }

    game_update_grades( $game, $userid);
}

function game_right_to_left( $lang){
    switch( $lang)
    {
    case 'he':
        return true;
    default:
        return false;
    } 
}

function game_compute_reserve_print( $attempt, &$wordrtl, &$reverseprint){
    if( function_exists( 'right_to_left')){
        if( $attempt->language != '')
            $wordrtl = game_right_to_left( $attempt->language);
        else
            $wordrtl = right_to_left();
        $reverseprint = ($wordrtl != right_to_left());
    }else{
        $reverseprint = false;
        $wordrtl = 'ltr';
    }
}

function game_select_from_repetitions( $game, $recs, $need){
    global $USER;

    $ret = array();

    $field = ($game->sourcemodule == 'glossary' ? 'glossaryentryid' : 'questionid');

    if( count($recs) <= $need){
        foreach( $recs as $rec)
            $ret[ $rec->$field] = 1;
        return $ret;
    }

    $countzero = 0;
    foreach( $recs as $rec){
        $select = "gameid=$game->id AND userid='$USER->id' AND questionid='$rec->questionid' AND glossaryentryid='$rec->glossaryentryid'";
        $id = $rec->$field;
        if( ($rec = get_record_select( 'game_repetitions', $select, 'id,repetitions r')) != false){
            $reps[ $id] = $rec->r;
        }else
        {
            $reps[ $id] = 0;
            if( ++$countzero >= $need)
                break;
        }
    }
    asort( $reps);
    foreach( $reps as $id => $r){
        $ret[ $id] = 1;
        if( count( $ret) >= $need)
            break;
    }

    return $ret;
}

function game_get_contexts(){
    global $CFG, $COURSE;

    require( $CFG->dirroot.'/question/editlib.php');
    $thiscontext = get_context_instance(CONTEXT_COURSE, $COURSE->id);
    $contexts = new question_edit_contexts( $thiscontext);
    $caps = array( 'moodle/question:viewmine', 'moodle/question:viewall');

    return $contexts->having_one_cap( $caps);
}

function game_export_createtempdir(){
    global $CFG;
        
    // create a random upload directory in temp
    $newdir = $CFG->dataroot."/temp/game";
    if (!file_exists( $newdir)) 
        mkdir( $newdir);

    srand( (double)microtime()*1000000); 
    while(true)
    {
        $r_basedir = "game/". date("Y-m-d H.i.s-").rand(0,10000);
        $newdir = $CFG->dataroot.'/temp/'.$r_basedir;
        if (!file_exists( $newdir)) 
        {
            mkdir( $newdir);
            return $newdir;
        }
    }
}

function game_create_zip( $srcdir, $courseid, $filename){
    global $CFG;
        
    $dir = $CFG->dataroot . '/' . $courseid;
    $filezip = $dir . "/export/{$filename}";

    if (file_exists( $filezip)){
        unlink( $filezip);
    }
        
    if (!file_exists( $dir)){
        mkdir( $dir);
    }
        
    if (!file_exists( $dir.'/export')){
        mkdir( $dir.'/export');
    }
        
    $srcfiles = get_directory_list( $srcdir, '', true, true, true);
    $fullsrcfiles = array();
    foreach( $srcfiles as $file){
        $fullsrcfiles[] = $srcdir.'/'.$file;
    }
                
    zip_files( $fullsrcfiles, $filezip);
            
    return (file_exists( $filezip) ? $filezip : '');
}
