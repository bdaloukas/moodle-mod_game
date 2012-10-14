<?php  // $Id: preview.php,v 1.8.2.5 2011/10/31 06:11:53 bdaloukas Exp $
/**
 * This page shows info about an user's attempt of game
 * 
 * @author  bdaloukas
 * @version $Id: preview.php,v 1.8.2.5 2011/10/31 06:11:53 bdaloukas Exp $
 * @package game
 **/
 
    require_once("../../config.php");
    require_once("$CFG->libdir/questionlib.php");
    require_once("$CFG->dirroot/question/type/shortanswer/questiontype.php");
    require_once("lib.php");
    require_once("locallib.php");

    require_once( "header.php");

    require_once( "hangman/play.php");
    require_once( "cross/play.php");
    require_once( "cryptex/play.php");
    require_once( "millionaire/play.php");
    require_once( "sudoku/play.php");
    require_once( "bookquiz/play.php");
    
    if( !isteacher( $game->course, $USER->id)){
    	error( get_string( 'only_teachers', 'game'));
    }

    $currenttab = required_param('action', PARAM_TEXT);

    include('tabs.php');

    switch( $currenttab){
    case 'showattempts':
        echo get_string( 'group').': ';
        game_showgroups( $game);    
        echo get_string('user').': ';
        game_showusers($game);
        echo '<br><br>';
        game_showattempts($game);
        print_footer();
        die;
    case 'delete':
        if (has_capability('mod/game:deleteattempts', $context))
        {
		    $attemptid = required_param('attemptid', PARAM_INT); 	
		    game_ondeleteattempt($game, $attemptid);
            print_footer();
        }
        die;
    case 'answers':
        showanswers( $game, false);
        print_footer();
        die;
    }

    $attemptid = required_param('attemptid', PARAM_INT);
    $update = required_param('update', PARAM_INT);
		
	$attempt = get_record_select( 'game_attempts', "id=$attemptid");
	$game = get_record_select( 'game', "id=$attempt->gameid");
	$detail = get_record_select( 'game_'.$game->gamekind, "id=$attemptid");
    $solution = ($currenttab == 'solution');

	switch( $game->gamekind)
	{
	case 'cross':
		game_cross_play( $update, $game, $attempt, $detail, '', true, $solution, false, false, false, false, true);
		break;
	case 'sudoku':
		game_sudoku_play( $update, $game, $attempt, $detail, true, $solution);
		break;
	case 'hangman':
		game_hangman_play( $update, $game, $attempt, $detail, true, $solution);
		break;
	case 'cryptex':
		$crossm = get_record_select( 'game_cross', "id=$attemptid");
		game_cryptex_play( $update, $game, $attempt, $detail, $crossm, false, true, $solution);
		break;
	}

    function game_showattempts($game){
        global $CFG, $USER;

        $gamekind = $game->gamekind;
        $update = get_coursemodule_from_instance( 'game', $game->id, $game->course)->id;

        //Here are user attempts
        $table = "game_attempts as ga, {$CFG->prefix}user u, {$CFG->prefix}game as g";
        $select = "ga.userid=u.id AND ga.gameid={$game->id} AND g.id={$game->id}";
        $fields = "ga.id, u.lastname, u.firstname, ga.attempts,".
          "timestart, timefinish, timelastattempt, score, ga.lastip, ga.lastremotehost";
        
        $userid = optional_param('userid',0,PARAM_INT);          
        if( $userid != 0)
            $select .= ' AND ga.userid='.$userid;
            
        $count = count_records_select( $table, $select, 'COUNT( *)');
        $maxlines = 20;
        $limitfrom = optional_param('limitfrom', 0, PARAM_INT);
        $recslimitfrom = $recslimitnum = '';
        if( $count > $maxlines){
            $recslimitfrom = ( $limitfrom ? $limitfrom * $maxlines : '');
            $recslimitnum = $maxlines;

            for($i=0; $i*$maxlines < $count; $i++){
                if( $i == $limitfrom){
                    echo ($i+1).' ';
                }else
                {
                    echo "<A HREF=\"{$CFG->wwwroot}/mod/game/preview.php?action=showattempts&amp;update=$update&amp;q={$game->id}&amp;limitfrom=$i&\">".($i+1)."</a>";
                    echo ' &nbsp;';
                }
            }
            echo "<br>";
        }

        if( ($recs = get_records_select( $table, $select, 'timelastattempt DESC,timestart DESC', $fields, $recslimitfrom, $recslimitnum)) != false){
            echo '<table border="1">';
            echo '<tr><td>'.get_string( 'delete').'</td><td>'.get_string('user').'</td>';
            echo '<td>'.get_string('lastip', 'game').'</td>';
            echo '<td>'.get_string('timestart', 'game').'</td>';
            echo '<td>'.get_string('timelastattempt', 'game').'</td>';
            echo '<td>'.get_string('timefinish', 'game').'</td>';
            echo '<td>'.get_string('score', 'game').'</td>';
            echo '<td>'.get_string('attempts', 'game').'</td>';
            echo '<td>'.get_string('preview', 'game').'</td>';
            echo '<td>'.get_string('showsolution', 'game').'</td>';
            echo "</tr>\r\n";
        	$strftimedate = get_string('formatdatetime', 'game');

            foreach( $recs as $rec){
                echo '<tr>';
                echo '<td><center>';
                if( $rec->timefinish == 0){
                    echo "\r\n<a href=\"{$CFG->wwwroot}/mod/game/preview.php?attemptid={$rec->id}&amp;q={$game->id}&amp;action=delete\">";
                    echo '<img src="'.$CFG->wwwroot.'/pix/t/delete.gif" alt="'.get_string( 'delete').'" /></a>';
                }
                echo '</center></td>';
                echo '<td><center>'.$rec->firstname. ' '.$rec->lastname.'</center></td>';
                echo '<td><center>'.(strlen( $rec->lastremotehost) > 0 ? $rec->lastremotehost : $rec->lastip).'</center></td>';
                echo '<td><center>'.( $rec->timestart != 0 ? userdate($rec->timestart, $strftimedate) : '')."</center></td>\r\n";
                echo '<td><center>'.( $rec->timelastattempt != 0 ? userdate($rec->timelastattempt, $strftimedate) : '').'</center></td>';
                echo '<td><center>'.( $rec->timefinish != 0 ? userdate($rec->timefinish, $strftimedate) : '').'</center></td>';
                echo '<td><center>'.round($rec->score * 100).'</center></td>';
                echo '<td><center>'.$rec->attempts.'</center></td>';
                echo '<td><center>';
	        	//Preview
	        	if( ($gamekind == 'cross') or ($gamekind == 'sudoku') or ($gamekind == 'hangman') or ($gamekind == 'cryptex')){
	        		echo "\r\n<a href=\"{$CFG->wwwroot}/mod/game/preview.php?action=preview&amp;attemptid={$rec->id}&amp;gamekind=$gamekind";
	        		echo '&amp;update='.$update."&amp;q={$game->id}\">";
                    echo '<img src="'.$CFG->wwwroot.'/pix/t/preview.gif" alt="'.get_string( 'preview', 'game').'" /></a>';
	        	}
                echo '</center></td>';

	    	    //Show solution
                echo '<td><center>';
	    	    if( ($gamekind == 'cross') or ($gamekind == 'sudoku') or ($gamekind == 'hangman') or ($gamekind == 'cryptex') ){
	    		    echo "\r\n<a href=\"{$CFG->wwwroot}/mod/game/preview.php?action=solution&amp;attemptid={$rec->id}&amp;gamekind={$gamekind}&amp;update=$update&amp;solution=1&amp;q={$game->id}\">";
	    		    echo '<img src="'.$CFG->wwwroot.'/pix/t/preview.gif" alt="'.get_string( 'showsolution', 'game').'" /></a>';
    	    	}
                echo '</center></td>';
                echo "</tr>\r\n";
            }
            echo "</table>\r\n";
        }
    }

	function game_ondeleteattempt( $game, $attemptid)
	{
		global $CFG;
		
		$attempt = get_record_select( 'game_attempts', 'id='.$attemptid);
		$game = get_record_select( 'game', 'id='.$attempt->gameid);
				
		switch( $game->gamekind)
		{
		case 'bookquiz':
			delete_records( 'game_bookquiz_chapters', 'attemptid', $attemptid);
			break;
		}
		delete_records( 'game_queries', 'attemptid', $attemptid);
		delete_records( 'game_attempts', 'id', $attemptid);
		
		$url = $CFG->wwwroot.'/mod/game/preview.php?action=showattempts&q='.$game->id;
        redirect( $url);
	}
	
function game_showusers($game)
{
    global $CFG, $USER;

    $users = array();

    $context = get_context_instance(CONTEXT_COURSE, $game->course);

    $groupid = optional_param('groupid',0, PARAM_INT);
    $sql =  "SELECT DISTINCT ra.userid,u.lastname,u.firstname FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}user u ".
                " WHERE ra.contextid={$context->id} AND ra.userid=u.id";
    if( $groupid != 0)
        $sql .= " AND ra.userid IN (SELECT gm.userid FROM {$CFG->prefix}groups_members gm WHERE gm.groupid=$groupid)";
    if( ($recs = get_records_sql( $sql))){
		foreach( $recs as $rec){
		    $users[ $rec->userid] = $rec->lastname.' '.$rec->firstname;
	    }
	}
    
    if ($guest = get_guest()) {
        $users[$guest->id] = fullname($guest);
    }

    $userid = optional_param('userid',0,PARAM_INT);

    ?>
        <script type="text/javascript">
            function onselectuser()
            {
                window.location.href = "<?php echo $CFG->wwwroot.'/mod/game/preview.php?action=showattempts&q='.$game->id.'&userid=';?>" + document.getElementById('menuuser').value + '&groupid=' + document.getElementById('menugroup').value;
            }
        </script>
    <?php
    choose_from_menu ($users, 'user', $userid, get_string("allparticipants"), 'javascript:onselectuser();');
}

    function game_showgroups($game)
    {
        global $CFG, $USER;

        $groups = array();
		if( ($recs = get_records_sql( "SELECT id,name FROM {$CFG->prefix}groups WHERE courseid=$game->course ORDER BY name"))){
			foreach( $recs as $rec){
				$groups[ $rec->id] = $rec->name;
			}
		}

        ?>
            <script type="text/javascript">
                function onselectgroup()
                {
                    window.location.href = "<?php echo $CFG->wwwroot.'/mod/game/preview.php?action=showattempts&q='.$game->id.'&groupid=';?>" + document.getElementById('menugroup').value;
                }
            </script>
        <?php

        $attributes = 'onchange="javascript:onselectgroup();"';
        $name = 'group';
        $id = 'menu'.$name;
        $class = 'menu'.$name;
        $class = 'select ' . $class; /// Add 'select' selector always
        $nothing = get_string("allgroups");
        $nothingvalue='0';
        $options = $groups;
        $selected = optional_param('groupid',0, PARAM_INT);
    
        $output = '<select id="'. $id .'" class="'. $class .'" name="'. $name .'" '. $attributes .'>' . "\n";
        $output .= '   <option value="'. s($nothingvalue) .'"'. "\n";
        if ($nothingvalue === $selected) {
            $output .= ' selected="selected"';
        }
        $output .= '>'. $nothing .'</option>' . "\n";

        if (!empty($options)) {
            foreach ($options as $value => $label) {
                $output .= '   <option value="'. s($value) .'"';
                if ((string)$value == (string)$selected ||
                    (is_array($selected) && in_array($value, $selected))) {
                    $output .= ' selected="selected"';
                }
                if ($label === '') {
                    $output .= '>'. $value .'</option>' . "\n";
                } else {
                    $output .= '>'. $label .'</option>' . "\n";
                }
            }
        }
        echo $output . '</select>' . "\n";
    }

