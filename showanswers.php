<?php  // $Id: showanswers.php,v 1.1.2.10 2010/08/05 16:14:12 bdaloukas Exp $
/**
 * This page shows the answers of the current game
 * 
 * @author  bdaloukas
 * @version $Id: showanswers.php,v 1.1.2.10 2010/08/05 16:14:12 bdaloukas Exp $
 * @package game
 **/

    require_once("../../config.php");
    require_once( "header.php");

    if( !isteacher( $game->course, $USER->id)){
    	error( get_string( 'only_teachers', 'game'));
    }

    $currenttab = 'answers';

    include('tabs.php');

    $action  = optional_param('action', "", PARAM_ALPHANUM);  // action
    if( $action == 'delstats')
        delete_records('game_repetitions', 'gameid', $game->id, 'userid', $USER->id);
    if( $action == 'computestats')
        game_compute_repetitions($game);
    
    echo '<b>'.get_string('repetitions', 'game').': &nbsp;&nbsp;</b>';
    echo get_string('user').': ';
    game_showusers($game);
    echo " &nbsp;<a href=\"$CFG->wwwroot/mod/game/showanswers.php?q=$q&action=delstats\">".get_string('clearrepetitions','game').'</a>';
    echo " &nbsp;&nbsp;<a href=\"$CFG->wwwroot/mod/game/showanswers.php?q=$q&action=computestats\">".get_string('computerepetitions','game').'</a>';
    echo '<br><br>';

    game_showanswers( $game, false);
    print_footer();

function game_compute_repetitions($game){
    global $CFG, $USER;

    delete_records('game_repetitions', 'gameid', $game->id,'userid',$USER->id);

    $sql = "INSERT INTO {$CFG->prefix}game_repetitions( gameid,userid,questionid,glossaryentryid,repetitions) ".
           "SELECT $game->id,$USER->id,questionid,glossaryentryid,COUNT(*) ".
           "FROM {$CFG->prefix}game_queries WHERE gameid=$game->id AND userid=$USER->id GROUP BY questionid,glossaryentryid";

    if( !execute_sql( $sql, false))
        error('Problem on computing statistics for repetitions');
}

function game_showusers($game)
{
    global $CFG, $USER;

    $users = array();

    $context = get_context_instance(CONTEXT_COURSE, $game->course);

    if ($courseusers = get_course_users( $game->course)) {
        foreach ($courseusers as $courseuser) {
            $users[$courseuser->id] = fullname($courseuser, has_capability('moodle/site:viewfullnames', $context));
        }
    }
    if ($guest = get_guest()) {
        $users[$guest->id] = fullname($guest);
    }

    $userid = optional_param('userid',$USER->id,PARAM_INT);

    ?>
        <script type="text/javascript">
            function onselectuser()
            {
                window.location.href = "<?php echo $CFG->wwwroot.'/mod/game/showanswers.php?q='.$game->id.'&userid=';?>" + document.getElementById('menuuser').value;
            }
        </script>
    <?php
    choose_from_menu ($users, 'user', $userid, get_string("allparticipants"), 'javascript:onselectuser();');
}

function game_showanswers( $game, $existsbook)
{
    if( $game->gamekind == 'bookquiz' and $existsbook){
        showanswers_bookquiz( $game);
        return;
    }
    
    switch( $game->sourcemodule){
    case 'question':
        showanswers_question( $game);
        break;
    case 'glossary':
        showanswers_glossary( $game);
        break;
    case 'quiz':
        showanswers_quiz( $game);
        break;
    }
}

function showanswers_appendselect( $game)
{
    switch( $game->gamekind){
    case 'hangman':
    case 'cross':
    case 'crypto':
        return " AND qtype='shortanswer'";
    case 'millionaire':
        return " AND qtype = 'multichoice'";
    case 'sudoku':
    case 'bookquiz':
    case 'snakes':
        return " AND qtype in ('shortanswer', 'truefalse', 'multichoice')";
    }
    
    return '';
}

function showanswers_question( $game)
{
    if( $game->gamekind != 'bookquiz'){
        $select = ' category='.$game->questioncategoryid;

        if( $game->subcategories){
            $cats = question_categorylist( $game->questioncategoryid);
            if( strpos( $cats, ',') > 0){
                $select = ' category in ('.$cats.')';
            }
        }
    }else
    {
        $select = '';
        $recs = get_records_select( 'question_categories', '', '', '*', 0, 1);
        foreach( $recs as $rec){
            if( array_key_exists( 'course', $rec)){
                $select = "course=$game->course";
            }else{
                $context = get_context_instance(50, $game->course);
                $select = "contextid in ($context->id)";
            }
            break;
        }
        $select2 = '';
        if( $recs = get_records_select( 'question_categories', $select, 'id,id')){
            foreach( $recs as $rec){
                $select2 .= ','.$rec->id;
            }
        }
        $select = ' category IN ('.substr( $select2, 1).')';
    }
    
    $select .= ' AND hidden = 0 ';
    $select .= showanswers_appendselect( $game);
    
    $showcategories = ($game->gamekind == 'bookquiz');
    $order = ($showcategories ? 'category,questiontext' : 'questiontext');
    showanswers_question_select( $game, 'question q', $select, '*', $order, $showcategories, $game->course);
}


function showanswers_quiz( $game)
{
    global $CFG;

	$select = "quiz='$game->quizid' ".
			  " AND qzi.question=q.id".
			  " AND q.hidden=0".
              showanswers_appendselect( $game);
	$table = "question q,{$CFG->prefix}quiz_question_instances qzi";
	
    showanswers_question_select( $game, $table, $select, "q.*", 'category,questiontext', false, $game->course);
}


function showanswers_question_select( $game, $table, $select, $fields='*', $order='questiontext', $showcategoryname=false, $courseid=0)
{
    global $CFG;

    if( ($questions = get_records_select( $table, $select, $order, $fields)) === false){
        return;
    }

    $table .= ",{$CFG->prefix}game_repetitions gr";
    $select .= " AND gr.questionid=q.id AND gr.glossaryentryid=0 AND gr.gameid=".$game->id;
    $userid = optional_param('userid',0,PARAM_INT);
    if( $userid)
        $select .= " AND gr.userid=$userid";
    $sql = "SELECT q.id as id,SUM(repetitions) as c FROM {$CFG->prefix}$table WHERE $select GROUP BY q.id";
    $reps = get_records_sql( $sql);
	
	$categorynames = array();
	if( $showcategoryname){
	    $select = '';
    	$recs = get_records_select( 'question_categories', '', '', '*', 0, 1);
	    foreach( $recs as $rec){
	    	if( array_key_exists( 'course', $rec)){
	    		$select = "course=$courseid";
	    	}else{
	    		$context = get_context_instance(50, $courseid);
	        		$select = " contextid in ($context->id)";
	    	}
	    	break;
    	}

		if( ($categories = get_records_select( 'question_categories', $select, '', 'id,name'))){
			foreach( $categories as $rec){
				$categorynames[ $rec->id] = $rec->name;
			}
		}
	}
    
    echo '<table border="1">';
    echo '<tr><td></td>';
	if( $showcategoryname){
		echo '<td><b>'.get_string( 'categories', 'quiz').'</b></td>';
	}
    echo '<td><b>'.get_string( 'questions', 'quiz').'</b></td>';
    echo '<td><b>'.get_string( 'answers', 'quiz').'</b></td>';
    echo '<td><b>'.get_string( 'feedbacks', 'game').'</b></td>';
    if( $reps)
        echo '<td><b>'.get_string( 'repetitions', 'game').'</b></td>';
    echo "</tr>\r\n";
    $line = 0;
    foreach( $questions as $question){
        echo '<tr>';
        echo '<td>'.(++$line);
        echo '</td>';

		if( $showcategoryname){
			echo '<td>';
			if( array_key_exists( $question->category, $categorynames)){
				echo $categorynames[ $question->category];
			}else{
				echo '&nbsp;';
			}
			echo '</td>';
		}

        echo '<td>';
        echo "<a title=\"Edit\" href=\"$CFG->wwwroot/question/question.php?inpopup=1&amp;id=$question->id&courseid=$courseid\"  target=\"_blank\"><img src=\"$CFG->wwwroot/pix/t/edit.gif\" alt=\"Edit\" /></a> ";
        echo $question->questiontext.'</td>';
        
        switch( $question->qtype){
        case 'shortanswer':
	        $recs = get_records_select( 'question_answers', "question=$question->id", 'fraction DESC', 'id,answer,feedback');
	        if( $recs == false){
	            $rec = false;
	        }else{
	            foreach( $recs as $rec)
	                break;
	        }
	        echo "<td>$rec->answer</td>";
	        if( $rec->feedback == '')
	            $rec->feedback = '&nbsp;';
	        echo "<td>$rec->feedback</td>";
            break;
        case 'multichoice':
        case 'truefalse':
            $recs = get_records_select( 'question_answers', "question=$question->id");
            $feedback = '';
            echo '<td>';
            $i = 0;
            foreach( $recs as $rec){
                if( $i++ > 0)
                    echo '<br>';
		        if( $rec->fraction == 1){
			        echo " <b>$rec->answer</b>";
	                if( $rec->feedback == '')
	                    $feedback .= '<br>';
	                else
                        $feedback .= "<b>$rec->feedback</b><br>";
			        
                }else
                {
			        echo " $rec->answer";
	                if( $rec->feedback == '')
	                    $feedback .= '<br>';
	                else
                        $feedback .= "<br>";
                }
            }
            echo '</td>';
	        if( $feedback == '')
	            $feedback = '&nbsp;';
	        echo "<td>$feedback</td>";
            break;
        default:
            echo "<td>$question->qtype</td>";
	        if( $feedback == '')
	            $feedback = '&nbsp;';
	        echo "<td>$feedback</td>";
            break;
        }
        //Show repetitions
        if( $reps){
            if( array_key_exists( $question->id, $reps)){
                $rep = $reps[ $question->id];
                echo '<td><center>'.$rep->c.'</td>';
            }else
                echo '<td>&nbsp;</td>';
        }
        echo "</tr>\r\n";
    }
    echo "</table><br>\r\n\r\n";
}

function showanswers_glossary( $game)
{
    global $CFG;
    
	$table = 'glossary_entries ge';
    $select = "glossaryid={$game->glossaryid}";
    if( $game->glossarycategoryid){
		$select .= " AND gec.entryid = ge.id ".
					    " AND gec.categoryid = {$game->glossarycategoryid}";
		$table .= ",{$CFG->prefix}glossary_entries_categories gec";		
	}
 
    if( ($questions = get_records_select( $table, $select, 'definition', "ge.id,definition,concept")) === false){
        return;
    }
    
    //Show repetiotions of questions
    $table = "glossary_entries ge, {$CFG->prefix}game_repetitions gr";
    $select = "glossaryid={$game->glossaryid} AND gr.glossaryentryid=ge.id AND gr.gameid=".$game->id;
    $userid = optional_param('userid',0,PARAM_INT);
    if( $userid)
        $select .= " AND gr.userid=$userid";
        if( $game->glossarycategoryid){
	    $select .= " AND gec.entryid = ge.id ".
		           " AND gec.categoryid = {$game->glossarycategoryid}";
        $table .= ",{$CFG->prefix}glossary_entries_categories gec";
    }
    $sql = "SELECT ge.id,SUM(repetitions) as c FROM {$CFG->prefix}$table WHERE $select GROUP BY ge.id";
    $reps = get_records_sql( $sql);

    echo '<table border="1">';
    echo '<tr><td></td>';
    echo '<td><b>'.get_string( 'questions', 'quiz').'</b></td>';
    echo '<td><b>'.get_string( 'answers', 'quiz').'</b></td>';
    if( $reps != false)
        echo '<td><b>'.get_string( 'repetitions', 'game').'</b></td>';
    echo "</tr>\r\n";
    $line = 0;
    foreach( $questions as $question){
        echo '<tr>';
        echo '<td>'.(++$line);
        echo '</td>';
        
        echo '<td>'.$question->definition.'</td>';
        echo '<td>'.$question->concept.'</td>';
        if( $reps != false){
            if( array_key_exists( $question->id, $reps))
            {
                $rep = $reps[ $question->id];
                echo '<td><center>'.$rep->c.'</td>';
            }else
                echo '<td>&nbsp;</td>';
        }
        echo "</tr>\r\n";
    }
    echo "</table><br>\r\n\r\n";
}

function showanswers_bookquiz( $game)
{
    global $CFG;
    
	$select = "{$CFG->prefix}game_bookquiz_questions.questioncategoryid={$CFG->prefix}question.category ".
			  " AND {$CFG->prefix}game_bookquiz_questions.bookid = $game->bookid".
			  " AND {$CFG->prefix}book_chapters.id = {$CFG->prefix}game_bookquiz_questions.chapterid";
	$table = "question,{$CFG->prefix}game_bookquiz_questions,{$CFG->prefix}book_chapters";
	
    showanswers_question_select( $game, $table, $select, "{$CFG->prefix}question.*", "{$CFG->prefix}book_chapters.pagenum,questiontext");
}
