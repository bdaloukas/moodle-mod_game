<?php  // $Id: play.php,v 1.1.2.3 2012/01/16 23:01:10 bdaloukas Exp $

// This files plays the game contest

function game_contest_view( $game){
    global $CFG, $USER;
    
    $sql = "SELECT SUM(score) as s FROM {$CFG->prefix}game_queries WHERE gameid=$game->id AND userid=$USER->id";
    $rec = get_record_sql( $sql);

    echo get_string( 'marks', 'game').': '.$rec->s.'<br><br>';
    echo $game->toptext.'<br>';
    echo '<br><center><b>'.get_string( 'contest_not_solved', 'game').'</b></center><br>';
    game_contest_view_wherescore( $game, false);
    echo '<br><center><b>'.get_string( 'contest_solved', 'game').'</b></center><br>';
    game_contest_view_wherescore( $game, true);
    echo '<br><center><b>'.get_string( 'contest_top', 'game').'</b></center><br>';
    game_contest_top( $game);
}

function game_contest_view_wherescore( $game, $answered){
    global $CFG;
    
    $where = ( $answered ? ' AND ' : ' AND NOT ')." EXISTS (SELECT * FROM {$CFG->prefix}game_queries ".
		" WHERE gameid={$game->id} AND glossaryentryid=ge.id AND questionid=0 AND score>0)";
    //sql1: within a category sql2: without a category
    $sql1 = "SELECT gec.id, gec.categoryid, ge.id as glossaryentryid, ge.concept ".
           " FROM {$CFG->prefix}glossary_entries ge, {$CFG->prefix}glossary_entries_categories gec".
           " WHERE glossaryid=$game->glossaryid AND gec.entryid=ge.id ".$where.
           " ORDER BY gec.categoryid,ge.concept";
    $sql2 = "SELECT ge.id, -1 as categoryid, ge.id as glossaryentryid, ge.concept ".
           " FROM {$CFG->prefix}glossary_entries ge".
           " WHERE glossaryid=$game->glossaryid".$where.
           " AND NOT EXISTS (SELECT * FROM {$CFG->prefix}glossary_entries_categories gec WHERE gec.entryid=ge.id)".
           " ORDER BY ge.concept";

    $lines = array();
    $categoryid = 0;
    $categoryname = '';
    $line = '';
    $cm = get_coursemodule_from_instance('game', $game->id, $game->course);
    for( $pass = 1; $pass <= 2; $pass++)
    {
        $recs = get_records_sql( $pass == 1 ? $sql1 : $sql2);
        if( $recs)
        foreach( $recs as $rec)
        {
            if( $answered)
            {
                $select = "gameid={$game->id} AND glossaryentryid={$rec->glossaryentryid} AND questionid=0 AND score>0";
                $query = get_record_select( 'game_queries', $select);
                $dir = $CFG->dataroot.'/'.$game->course.'/moddata/game/'.$game->id.'/queries/'.$query->id;
                if( file_exists( $dir))
                {
                    $dir = dir( $dir);
                    //List files in images directory
                    $file = '';
                    while (($f = $dir->read()) !== false)
                    {
                        if( ($f != '.') && ($f != '..'))
                            $file=$f;
                    }
                    $name = '<a href="'.$CFG->wwwroot.'/file.php/'.$game->course.'/moddata/game/'. $game->id.
                        '/queries/'.$query->id.'/'.$file.'">'.$rec->concept.'</a>';
                 }else
                    $name = $rec->concept;
            }else
            {
                $name = '<a href="'.$CFG->wwwroot.'/mod/game/attempt.php?id='.$cm->id.
                    '&entryid='.$rec->glossaryentryid.'">'.$rec->concept.'</a>';
            }
            if( $rec->categoryid == $categoryid)
                $line .= ' '.$name;
            else
            {
                if( $categoryid != 0){
                    $lines[ $categoryname] = $line;
                }
                $line = $name;
                $categoryid = $rec->categoryid;
                $reccat = get_record( 'glossary_categories', 'id', $categoryid);
                $categoryname = $reccat->name;
            }
        }
        if( $categoryid != 0)
            $lines[ $categoryname] = $line;
    }
    ksort( $lines);
    echo '<table border=1>';
    foreach( $lines as $name => $line){
        if( $name == '')
            $name = '&nbsp;';
        echo '<tr><td>'.$name.'</td><td>'.$line.'</td></tr>';
    }
    echo '</table>';
}

require_once ($CFG->libdir.'/formslib.php');

class game_contest_submit_form extends moodleform {
    
    function set_data($default_values) {
        parent::set_data($default_values);
    }

    function definition() {
        global $CFG, $COURSE;

        $mform =& $this->_form;
        $data = &$this->_customdata;
        $game = $data[ 'game'];
        $rec = get_record('glossary_entries', 'id', $data[ 'entryid']);
        if( $rec != false)
            $name = $rec->concept;
        else
            $name = '';
        $mform->addElement('header', 'gradeoptions', $name);
        
        $mform->addElement('hidden', 'id', $data[ 'id']);
        $mform->addElement('hidden', 'entryid', $data[ 'entryid']);
        
        $this->set_upload_manager(new upload_manager('attachment', true, false, $course, false, $game->param2, true, true));
        $attachmentoptions = array('subdirs'=>false, 'maxfiles'=>1, 'maxbytes' => $game->param2);
        $mform->addElement('file', 'attachment', get_string('attachment', 'forum'), $attachmentoptions);

        if( $game->param1){
            //Has notes
            $mform->addElement('htmleditor', 'studentanswer', get_string( 'remark', 'game'), array('size' => 4));
        }
//---------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values){

    }

    function validation($data, $files){
        $errors = parent::validation($data, $files);
        
        
        return $errors;
    }
}

function game_contest_continue($id, $game, $attempt, $detail, $entryid)
{
    global $USER;
    
    if( $attempt == false){
        $attempt = game_addattempt( $game);
    }
    
    $mform =& new game_contest_submit_form(null, compact('game', 'entryid', 'id'));
    if ($mform->is_cancelled()){
        $cm = get_coursemodule_from_instance('game', $game->id, $game->course);
        redirect("view.php?id=$cm->id");
    } elseif ($fromform = $mform->get_data()) {
        $cm = get_coursemodule_from_instance('game', $game->id, $game->course);
        
        $todb = new object();
        $todb->attemptid = $attempt->id;
        $todb->gameid = $game->id;
        $todb->userid = $USER->id;
        $todb->sourcemodule = $game->sourcemodule;
        $todb->glossaryentryid = $entryid;
        $todb->questionid = 0;
        $todb->score = 0;
        $todb->correct = 0;     //means check if it is the winner or not
        if( isset( $fromform->studentanswer)){
            $todb->studentanswer = $fromform->studentanswer;
        }
        if ($todb->id=insert_record('game_queries', $todb)) {
            add_to_log($game->course, 'game', 'submit', '', $todb->id, $cm->id);
            $dir = game_file_area_query_name( $game, $todb);
            if ($mform->save_files( $dir)){
                $file = $mform->_upload_manager->files[ 'attachment'];
                $newfilename = $file[ 'originalname'];
                set_field("game_queries", "attachment", $newfilename, 'id', $todb->id);
            }
        } else {
            error("Could not submit to contest");
        }
        redirect("view.php?q=$game->id");
    }else
    {
        $mform->display();
    }
}

function game_contest_top( $game)
{
    global $CFG;
    
    $sql = "SELECT userid as id, SUM(score) as s FROM {$CFG->prefix}game_queries WHERE gameid=$game->id GROUP BY userid ORDER BY SUM(score) DESC LIMIT 0,10";
    $recs = get_records_sql( $sql);
    echo '<table border=1>';
    $line=0;
    foreach( $recs as $rec)
    {
        $user = get_record( 'user', 'id', $rec->id);
        echo '<tr>';
        echo '<td>'.(++$line).'</td><td>';
        echo '<img src="'.$CFG->wwwroot.'/user/pix.php/'.$rec->id.'/f2.jpg"></td>'; 
        echo '<td>'.$user->firstname.' '.$user->lastname.'</td>';
        echo '<td>'.$rec->s.'</td>';
        for($step=1;$step<=2;$step++)
        {
            $score = ($step == 1 ? ' AND score>0' : ' AND score=0');
            $sql = "SELECT ge.id as id,ge.concept,gq.id as queryid FROM {$CFG->prefix}game_queries gq, {$CFG->prefix}glossary_entries ge ".
        		" WHERE gameid={$game->id} AND gq.userid={$rec->id} AND ge.id=gq.glossaryentryid $score ORDER BY ge.concept";
            $recs2 = get_records_sql( $sql);
            $s = '';
            if( $recs2)
            {
                foreach( $recs2 as $rec2)
                {
                    if( $s != '')
                        $s .= ' ';
                        
                    $dir = $CFG->dataroot.'/'.$game->course.'/moddata/game/'.$game->id.'/queries/'.$rec2->queryid;
                    if( file_exists( $dir))
                    {
                        $dir = dir( $dir);
                        //List files in images directory
                        $file = '';
                        while (($f = $dir->read()) !== false)
                        {
                            if( ($f != '.') && ($f != '..'))
                                $file=$f;
                        }
                        $name = '<a href="'.$CFG->wwwroot.'/file.php/'.$game->course.'/moddata/game/'. $game->id.
                            '/queries/'.$rec2->queryid.'/'.$file.'">'.$rec2->concept.'</a>';
                     }else
                        $name = $rec2->concept;
                        
                    $s .= $name;
                }
            }
            if( $s == '')
                $s = '&nbsp';
            echo '<td>'.$s.'</td>';
        }
        echo '</tr>';
    }
    echo "</table>\r\n";
}
