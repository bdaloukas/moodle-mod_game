<?php  // $Id: play.php,v 1.10.2.5 2011/07/23 08:45:06 bdaloukas Exp $

function game_hiddenpicture_continue( $id, $game, $attempt, $hiddenpicture)
{
    global $CFG, $USER;

	if( $attempt != false and $hiddenpicture != false){
	    //Continue a previous attempt
		return game_hiddenpicture_play( $id, $game, $attempt, $hiddenpicture);
	}

	if( $attempt == false){
	    //Start a new attempt
		$attempt = game_addattempt( $game);
	}


	$cols = $game->param1;
	$rows = $game->param2;
	if( $cols == 0){
		error( get_string( 'hiddenpicture_nocols', 'game'));
	}
	if( $rows == 0){
		error( get_string( 'hiddenpicture_norows', 'game'));
	}

	//new attempt
    $n = $game->param1 * $game->param2;
    $recs = game_questions_selectrandom( $game, CONST_GAME_TRIES_REPETITION*$n);
    $selected_recs = game_select_from_repetitions( $game, $recs, $n);

    $newrec = game_hiddenpicture_selectglossaryentry( $game, $attempt);
	
	if( $recs === false){
		error( get_string( 'no_questions', 'game'));
	}	

	$positions = array();
	$pos=1;
	for($col=0; $col < $cols; $col++){
	    for( $row=0; $row < $rows; $row++){
	        $positions[] = $pos++;
	    }
	}
	$i = 0;
    $field = ($game->sourcemodule == 'glossary' ? 'glossaryentryid' : 'questionid');
	foreach( $recs as $rec)
	{
        if( !array_key_exists( $rec->$field, $selected_recs))
            continue;

		unset( $query);
		$query->attemptid = $newrec->id;
		$query->gameid = $game->id;
		$query->userid = $USER->id;

		$pos = array_rand( $positions);
		$query->col = $positions[ $pos];
		unset( $positions[ $pos]);
		
		$query->sourcemodule = $game->sourcemodule;
		$query->questionid = $rec->questionid;
		$query->glossaryentryid = $rec->glossaryentryid;
		$query->score = 0;
		if( ($query->id = insert_record( "game_queries", $query)) == 0){
			error( 'error inserting in game_queries');
		}
        game_update_repetitions($game->id, $USER->id, $query->questionid, $query->glossaryentryid);
	}
	
	//The score is zero
	game_updateattempts( $game, $attempt, 0, 0);

	game_hiddenpicture_play( $id, $game, $attempt, $newrec);
}

//Create the game_hiddenpicture record
function game_hiddenpicture_selectglossaryentry( $game, $attempt){
    global $CFG, $USER;

    srand( (double)microtime()*1000000);

	if( $game->glossaryid2 == 0){
		error( get_string( 'must_select_glossary', 'game'));
	}
    $select = "ge.glossaryid={$game->glossaryid2}";
	$table = 'glossary_entries ge';
	if( $game->glossarycategoryid2){
		$table .= ",{$CFG->prefix}glossary_entries_categories gec";
		$select .= " AND gec.entryid = ge.id AND gec.categoryid = {$game->glossarycategoryid2}";
	}
	if( $game->param7 == 0){
	    //Allow spaces
    	$select .= " AND concept NOT LIKE '% %'";
    }
    
    $select .= " AND attachment LIKE '%.%'";
	if( ($recs=get_records_select( $table, $select, '', 'ge.id,attachment')) == false){	    
	    $a->name = "'".get_field_select('glossary', 'name', "id=$game->glossaryid2")."'";
        error( get_string( 'hiddenpicture_nomainquestion', 'game', $a));
        return false;
    }
    $ids = array();
    foreach( $recs as $rec){
        $s = strtoupper( $rec->attachment);
        $s = substr( $s, -4);
        if( $s == '.GIF' or $s == '.JPG' or $s == '.PNG'){
            $ids[] = $rec->id;
        } 
    }
	if( count( $ids) == 0){
    	$a->name = "'".get_field_select('glossary', 'name', "id=$game->glossaryid2")."'";
        error( get_string( 'hiddenpicture_nomainquestion', 'game', $a));
        return false;
    }

    //Have to select randomly one glossaryentry
    shuffle( $ids);
    $min_num = 0;
    for($i=0;$i<count($ids);$i++){
        $tempid = $ids[ $i];
        $select2 = "gameid=$game->id AND userid='$USER->id' AND questionid=0 AND glossaryentryid='$tempid'";
        if(($rec2 = get_record_select('game_repetitions', $select2, 'id,repetitions r')) != false){
            if( ($rec2->r < $min_num) or ($min_num == 0)){
                $min_num = $rec2->r;
                $glossaryentryid = $tempid;
            }
        }
        else{
            $glossaryentryid = $tempid;
            break;
        }
    }
                  
    $sql = 'SELECT id, concept as answertext, definition as questiontext, id as glossaryentryid, 0 as questionid, glossaryid, attachment'.
           " FROM {$CFG->prefix}glossary_entries WHERE id = $glossaryentryid";
    if( ($rec = get_record_sql( $sql)) == false)
        return false;
        
    $query->attemptid = $attempt->id;
    $query->gameid = $game->id;
    $query->userid = $USER->id;

    $query->col = 0;
    $query->sourcemodule = 'glossary';
    $query->questionid = 0;
    $query->glossaryentryid = $rec->glossaryentryid;
	$query->attachment = str_replace( "\\", '/', $CFG->dataroot)."/{$game->course}/moddata/glossary/{$game->glossaryid2}/{$query->glossaryentryid}/{$rec->attachment}";
	$query->questiontext = $rec->questiontext;
	$query->answertext = $rec->answertext;
    $query->score = 0;
    if( ($query->id = insert_record( "game_queries", $query)) == 0){
        error( 'error inserting in game_queries');
    }
	$newrec->id = $attempt->id;
	if( !game_insert_record(  'game_hiddenpicture', $newrec)){
		error( 'error inserting in game_hiddenpicture');
	}

    game_update_repetitions($game->id, $USER->id, $query->questionid, $query->glossaryentryid);
	
	return $newrec;
}

function game_hiddenpicture_play( $id, $game, $attempt, $hiddenpicture, $showsolution=false)
{
	if( $game->toptext != ''){
		echo $game->toptext.'<br>';
	}
	
	//Show picture
    $offsetquestions = game_sudoku_compute_offsetquestions( $game->sourcemodule, $attempt, $numbers, $correctquestions);
    unset( $offsetquestions[ 0]);

    game_hiddenpicture_showhiddenpicture( $id, $game, $attempt, $hiddenpicture, $showsolution, $offsetquestions, $correctquestions, $id, $attempt, $showsolution);

    //Show questions
    $onlyshow = false;
    $showsolution = false;
				
	switch( $game->sourcemodule)
	{
	case 'quiz':
	case 'question':
		game_sudoku_showquestions_quiz( $id, $game, $attempt, $hiddenpicture, $offsetquestions, $numbers, $correctquestions, $onlyshow, $showsolution);
		break;
	case 'glossary':
		game_sudoku_showquestions_glossary( $id, $game, $attempt, $hiddenpicture, $offsetquestions, $numbers, $correctquestions, $onlyshow, $showsolution);
		break;
	}
	
	if( $game->bottomtext != ''){
		echo '<br><br>'.$game->bottomtext;
	}	
}

function game_hidden_picture_computescore( $game, $hiddenpicture){
    $correct = $hiddenpicture->correct;
    if( $hiddenpicture->found){
        $correct++;
    }
    $remaining = $game->param1 * $game->param2 - $hiddenpicture->correct;
    $div2 = $correct + $hiddenpicture->wrong + $remaining;
    if( $hiddenpicture->found){
        $percent = ($correct + $remaining) / $div2;
    }else{
        $percent = $correct / $div2;
    }
    
    return $percent;
}

function game_hiddenpicture_showhiddenpicture( $id, $game, $attempt, $hiddenpicture, $showsolution, $offsetquestions, $correctquestions){
	global $CFG;

    $foundcells='';
    foreach( $correctquestions as $key => $val){
        $foundcells .= ','.$key;
    }
    $cells='';
    foreach( $offsetquestions as $key => $val){
        if( $key != 0){
            $cells .= ','.$key;
        }
    }
    
    $query = get_record_select( 'game_queries', "attemptid=$hiddenpicture->id AND col=0", 'id,glossaryentryid,attachment,questiontext');

    //Grade
	echo "<br/>".get_string( 'grade', 'game').' : '.round( $attempt->score * 100).' %';
       
    game_hiddenpicture_showquestion_glossary( $id, $query);
    
    $cells = substr( $cells, 1);
    $foundcells = substr( $foundcells, 1);
    game_showpicture( $id, $game, $attempt, $query, $cells, $foundcells, true);
}

function game_hiddenpicture_showquestion_glossary( $id, $query)
{
	global $CFG;
	
	$entry = get_record( 'glossary_entries', 'id', $query->glossaryentryid);

	/// Start the form
	echo '<br>';
    echo "<form id=\"responseform\" method=\"post\" action=\"{$CFG->wwwroot}/mod/game/attempt.php\" onclick=\"this.autocomplete='off'\">\n";
	echo "<center><input type=\"submit\" name=\"finishattempt\" value=\"".get_string('hiddenpicture_mainsubmit', 'game')."\"></center>\n";

    // Add a hidden field with the queryid
    echo '<input type="hidden" name="id" value="' . s($id) . "\" />\n";
    echo '<input type="hidden" name="action" value="hiddenpicturecheckg" />';
    echo '<input type="hidden" name="queryid" value="' . $query->id . "\" />\n";

    // Add a hidden field with glossaryentryid
    echo '<input type="hidden" name="glossaryentryid" value="'.$query->glossaryentryid."\" />\n";

    echo game_filtertext( $entry->definition, 0).'<br>';
    
    echo get_string( 'answer').': ';
	echo "<input type=\"text\" name=\"answer\" size=30 /><br>";

    echo "</form><br>\n";
}

function game_hiddenpicture_check_questions( $id, $game, &$attempt, &$hiddenpicture, $finishattempt)
{
    global $QTYPES, $CFG;

    $responses = data_submitted();
    
    $offsetquestions = game_sudoku_compute_offsetquestions( $game->sourcemodule, $attempt, $numbers, $correctquestions);

	$questionlist = game_sudoku_getquestionlist( $offsetquestions);
	
    $questions = game_sudoku_getquestions( $questionlist);

    $actions = question_extract_responses($questions, $responses, QUESTION_EVENTSUBMIT);

    $correct = $wrong = 0;
    foreach($questions as $question) {
        if( !array_key_exists( $question->id, $actions)){
            //no answered
            continue;
        }
        unset( $state);
        unset( $cmoptions);
        $question->maxgrade = 100;
        $state->responses = $actions[ $question->id]->responses;
		$state->event = QUESTION_EVENTGRADE;

		$cmoptions = array();
        $QTYPES[$question->qtype]->grade_responses( $question, $state, $cmoptions);            

		unset( $query);

        $select = "attemptid=$attempt->id";
        $select .= " AND questionid=$question->id";
        if( ($query->id = get_field_select( 'game_queries', 'id', $select)) == 0){
			die("problem game_hiddenpicture_check_questions (select=$select)");
            continue;
        }

		$answertext = $state->responses[ ''];
	    if( $answertext != ''){
            $grade = $state->raw_grade;
            if( $grade < 50){
	    		//wrong answer
	    		game_update_queries( $game, $attempt, $query, $grade/100, $answertext);
    			$wrong++;
    		}else{
                //correct answer
        		game_update_queries( $game, $attempt, $query, 1, $answertext);
	    	    $correct++;
	    	}
        }
    }
    
    $hiddenpicture->correct += $correct;
    $hiddenpicture->wrong += $wrong;
    
    if( !update_record(  'game_hiddenpicture', $hiddenpicture)){
        error( 'game_hiddenpicture_check_questions: error updating in game_hiddenpicture');
    }
    
    $attempt->score = game_hidden_picture_computescore( $game, $hiddenpicture);
    if( !update_record(  'game_attempts', $attempt)){
        error( 'game_hiddenpicture_check_questions: error updating in game_attempt');
    }    

    game_sudoku_check_last( $id, $game, $attempt, $hiddenpicture, $finishattempt);
}

function game_hiddenpicture_check_mainquestion( $id, $game, &$attempt, &$hiddenpicture, $finishattempt)
{
    global $QTYPES, $CFG;

    $responses = data_submitted();

	$glossaryentryid = $responses->glossaryentryid;
	$queryid = $responses->queryid;

    // Load the glossary entry
    if (!($entry = get_record_select( 'glossary_entries', "id=$glossaryentryid"))) {
        error(get_string('noglossaryentriesfound', 'game'));
    }	
    $answer = $responses->answer;
    $correct = false;
    if( $answer != ''){
		if( game_upper( $entry->concept) == game_upper( $answer)){
            $correct = true;
        }
    }
    
    // Load the query
    if (!($query = get_record_select( 'game_queries', "id=$queryid"))) {
        error("The query $queryid not found");
    }	
        
    game_update_queries( $game, $attempt, $query, $correct, $answer);
        
    if( $correct){
        $hiddenpicture->found = 1;
    }else{
        $hiddenpicture->wrong++;
    }
    if( !update_record(  'game_hiddenpicture', $hiddenpicture)){
        error( 'game_hiddenpicture_check_mainquestion: error updating in game_hiddenpicture');
    }
 
    $score = game_hidden_picture_computescore( $game, $hiddenpicture);   
    game_updateattempts( $game, $attempt, $score, $correct);

    if( $correct == false){
        game_hiddenpicture_play( $id, $game, $attempt, $hiddenpicture);
        return;
    }
    
    //Finish the game
    $query = get_record_select( 'game_queries', "attemptid=$hiddenpicture->id AND col=0", 'id,glossaryentryid,attachment,questiontext');
    game_showpicture( $id, $game, $attempt, $query, '', '', false);
	echo '<p><BR/><font size="5" color="green">'.get_string( 'win', 'game').'</font><BR/><BR/></p>';
	global $CFG;
	
	echo '<br/>';
	
    echo "<a href=\"$CFG->wwwroot/mod/game/attempt.php?id=$id\">";
    echo get_string( 'nextgame', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp;';

	if (! $cm = get_record("course_modules", "id", $id)) {
		error("Course Module ID was incorrect id=$id");
	}

	echo "<a href=\"$CFG->wwwroot/course/view.php?id=$cm->course\">".get_string( 'finish', 'game').'</a> ';
}

function game_showpicture( $id, $game, $attempt, $query, $cells, $foundcells, $usemap)
{
    global $CFG;
    
	$filename = $query->attachment;
    $filenamenumbers = str_replace( "\\", '/', $CFG->dirroot)."/mod/game/hiddenpicture/numbers.png";
    if( $usemap){
        $cols = $game->param1;
        $rows = $game->param2;
    }else{
        $cols = $rows = 0;
    }    
    $params = "id=$id&id2=$attempt->id&f=$foundcells&cols=$cols&rows=$rows&cells=$cells&p=$filename&n=$filenamenumbers";
    $imagesrc = "hiddenpicture/picture.php?$params";  

    $size = getimagesize ($filename);
    if( $game->param4 > 10){
        $width = $game->param4;
        $height = $size[ 1] * $width / $size[ 0];        
    }else if( $game->param5 > 10){
        $height = $game->param5;
        $width = $size[ 0] * $height / $size[ 1];
    }else
    {
        $width = $size[ 0];
        $height = $size[ 1];
    }
    
    echo "<IMG SRC=\"$imagesrc\" width=$width ";
    if( $usemap){
        echo " USEMAP=\"#mapname\" "; 
    }
    echo " BORDER=\"1\">\r\n";
    
    if( $usemap){
        echo "<MAP NAME=\"mapname\">\r\n";
        $pos=0;
        for($row=0; $row < $rows; $row++){
            for( $col=0; $col < $cols; $col++){
                $pos++;
                $x1 = $col * $width / $cols;
                $y1 = $row * $height / $rows;
                $x2 = $x1 + $width / $cols;
                $y2 = $y1 + $height / $rows;
                $q = "a$pos";
                echo "<AREA SHAPE=\"rect\" COORDS=\"$x1,$y1,$x2,$y2\" HREF=\"#$q\" ALT=\"$pos\">\r\n";
            }
        }
        echo "</MAP>";    
    }
}
