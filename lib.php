<?php  // $Id: lib.php,v 1.15.2.11 2012/01/16 21:45:04 bdaloukas Exp $
/**
 * Library of functions and constants for module game
 *
 * @author 
 * @version $Id: lib.php,v 1.15.2.11 2012/01/16 21:45:04 bdaloukas Exp $
 * @package game
 **/


/// CONSTANTS ///////////////////////////////////////////////////////////////////

/**#@+
 * The different review options are stored in the bits of $game->review
 * These constants help to extract the options
 */
/**
 * The first 6 bits refer to the time immediately after the attempt
 */
define('GAME_REVIEW_IMMEDIATELY', 0x3f);
/**
 * the next 6 bits refer to the time after the attempt but while the game is open
 */
define('GAME_REVIEW_OPEN', 0xfc0);
/**
 * the final 6 bits refer to the time after the game closes
 */
define('GAME_REVIEW_CLOSED', 0x3f000);

// within each group of 6 bits we determine what should be shown
define('GAME_REVIEW_RESPONSES',   1*0x1041); // Show responses
define('GAME_REVIEW_SCORES',      2*0x1041); // Show scores
define('GAME_REVIEW_FEEDBACK',    4*0x1041); // Show feedback
define('GAME_REVIEW_ANSWERS',     8*0x1041); // Show correct answers
// Some handling of worked solutions is already in the code but not yet fully supported
// and not switched on in the user interface.
define('GAME_REVIEW_SOLUTIONS',  16*0x1041); // Show solutions
define('GAME_REVIEW_GENERALFEEDBACK', 32*0x1041); // Show general feedback
/**#@-*/


/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted game record
 **/

function game_add_instance($game) {
    
    $game->timemodified = time();
	
    # May have to add extra stuff in here #
    
    game_before_add_or_update( $game);

    $id = insert_record('game', $game);
    
    $game = get_record_select( 'game', "id=$id");
    
    // Do the processing required after an add or an update.
    game_after_add_or_update( $game);
    
    return $id;
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function game_update_instance($game) {
    $game->timemodified = time();
    $game->id = $game->instance;

    if( !isset( $game->glossarycategoryid)){
        $game->glossarycategoryid = 0;
    }
    
    if( !isset( $game->glossarycategoryid2)){
        $game->glossarycategoryid2 = 0;
    }
        
    if( $game->grade == ''){
        $game->grade = 0;
    }

    if( !isset( $game->param1)){
        $game->param1 = 0;
    }

    if( $game->param1 == ''){
        $game->param1 = 0;
    }

    if( !isset( $game->param2)){
        $game->param2 = 0;
    }

    if( $game->param2 == ''){
        $game->param2 = 0;
    }
    
    game_before_add_or_update( $game);
    if( !update_record('game', $game)){
        return false;
    }
    
    // Do the processing required after an add or an update.
    game_after_add_or_update( $game);
    
    return true;    
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function game_delete_instance($gameid) {
    global $CFG;
       
    $result = true;

    # Delete any dependent records here #
	
	if( ($recs = get_records_select( 'game_attempts', "gameid='$gameid'")) != false){
	    $ids = '';
	    $count = 0;
	    $aids = array();
		foreach( $recs as $rec){
		    $ids .= ','.$rec->id;
		    if( ++$count > 10){
		        $count = 0;
		        $aids[] = $ids;
		        $ids = '';
		    }
		}
		if( $ids != ''){
    		$aids[] = $ids;
        }
        
		foreach( $aids as $ids){
		    if( $result == false){
		        break;
		    }
	        $tables = array( 'game_hangman', 'game_cross', 'game_cryptex', 'game_millionaire', 'game_bookquiz', 'game_sudoku', 'game_snakes');
	        foreach( $tables as $t){
	            $sql = "DELETE FROM {$CFG->prefix}$t WHERE id IN (".substr( $ids, 1).')';
		        if (!execute_sql( $sql, false)) {
			        $result = false;
			        break;
                }
            }
		}
	}
		    
    $tables = array( 'game_attempts', 'game_grades', 'game_bookquiz_questions', 'game_queries', 'game_repetitions');
    foreach( $tables as $t){
        if( $result == false){
            break;
        }
		    
        if (!delete_records( $t, 'gameid', $gameid)) {
            $result = false;
		}
	}

    $tables = array( 'game_export_javame', 'game_export_html');
    foreach( $tables as $t){
        if( $result == false){
            break;
        }
		    
        if (!delete_records( $t, 'id', $gameid)) {
            $result = false;
		}
	}
	
	if( $result){
        if (!delete_records( 'game', "id", $gameid)) {
            $result = false;
        }
    }
        
    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 **/
function game_user_outline($course, $user, $mod, $game) {
    if ($grade = get_record_select('game_grades', "userid=$user->id AND gameid = $game->id", 'id,score,timemodified')) {

        $result = new stdClass;
        if ((float)$grade->score) {
            $result->info = get_string('grade').':&nbsp;'.round($grade->score * $game->grade, $game->decimalpoints).' '.
                            get_string('percent', 'game').':&nbsp;'.round(100 * $grade->score, $game->decimalpoints).' %';
        }
        $result->time = $grade->timemodified;
        return $result;
    }
    return NULL;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 **/
function game_user_complete($course, $user, $mod, $game) {
    if ($attempts = get_records_select('game_attempts', "userid='$user->id' AND gameid='$game->id'", 'attempt ASC')) {
        if ($game->grade && $grade = get_record('game_grades', 'userid', $user->id, 'gameid', $game->id)) {
            echo get_string('grade').': '.round($grade->score * $game->grade, $game->decimalpoints).'/'.$game->grade.'<br />';
        }
        foreach ($attempts as $attempt) {
            echo get_string('attempt', 'game').' '.$attempt->attempt.': ';
            if ($attempt->timefinish == 0) {
                print_string('unfinished');
            } else {
                echo round($attempt->score * $game->grade, $game->decimalpoints).'/'.$game->grade;
            }
            echo ' - '.userdate($attempt->timelastattempt).'<br />';
        }
    } else {
       print_string('noattempts', 'game');
    }

    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in game activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function game_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function game_cron () {
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $gameid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function game_grades($gameid) {
/// Must return an array of grades, indexed by user, and a max grade.

    $game = get_record('game', 'id', intval($gameid));
    if (empty($game) || empty($game->grade)) {
        return NULL;
    }

    $return = new stdClass;
    $return->grades = get_records_menu('game_grades', 'gameid', $game->id, '', "userid, score * {$game->grade}");
    $return->maxgrade = $game->grade;

    return $return;
}

/**
 * Return grade for given user or all users.
 *
 * @param int $gameid id of game
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function game_get_user_grades($game, $userid=0) {
    global $CFG;

    $user = $userid ? "AND u.id = $userid" : "";

    $sql = "SELECT u.id, u.id AS userid, $game->grade * g.score AS rawgrade, g.timemodified AS dategraded, MAX(a.timefinish) AS datesubmitted
            FROM {$CFG->prefix}user u, {$CFG->prefix}game_grades g, {$CFG->prefix}game_attempts a
            WHERE u.id = g.userid AND g.gameid = {$game->id} AND a.gameid = g.gameid AND u.id = a.userid
                  $user
            GROUP BY u.id, g.score, g.timemodified";

    return get_records_sql($sql);
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of game. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $gameid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function game_get_participants($gameid) {
    return false;   //todo
}

/**
 * This function returns if a scale is being used by one game
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $gameid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function game_scale_used ($gameid,$scaleid) {
    $return = false;

    //$rec = get_record("game","id","$gameid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

/**
 * Update grades in central gradebook
 *
 * @param object $game null means all games
 * @param int $userid specific user only, 0 mean all
 */
function game_update_grades($game=null, $userid=0, $nullifnone=true) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        if( file_exists( $CFG->libdir.'/gradelib.php')){
            require_once($CFG->libdir.'/gradelib.php');
        }else{
            return;
        }
    }

    if ($game != null) {
        if ($grades = game_get_user_grades($game, $userid)) {
            game_grade_item_update($game, $grades);

        } else if ($userid and $nullifnone) {
            $grade = new object();
            $grade->userid   = $userid;
            $grade->rawgrade = NULL;
            game_grade_item_update( $game, $grade);

        } else {
            game_grade_item_update( $game);
        }

    } else {
        $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
                  FROM {$CFG->prefix}game a, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
                 WHERE m.name='game' AND m.id=cm.module AND cm.instance=a.id";
        if ($rs = get_recordset_sql($sql)) {
            while ($game = rs_fetch_next_record( $rs)) {
                if ($game->grade != 0) {
                    game_update_grades( $game, 0, false);
                } else {
                    game_grade_item_update( $game);
                }
            }
            rs_close( $rs);
        }
    }
}

/**
 * Create grade item for given game
 *
 * @param object $game object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function game_grade_item_update($game, $grades=NULL) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        if( file_exists( $CFG->libdir.'/gradelib.php')){
            require_once($CFG->libdir.'/gradelib.php');
        }else{
            return;
        }
    }

    if (array_key_exists('cmidnumber', $game)) { //it may not be always present
        $params = array('itemname'=>$game->name, 'idnumber'=>$game->cmidnumber);
    } else {
        $params = array('itemname'=>$game->name);
    }

    if ($game->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $game->grade;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/game', $game->course, 'mod', 'game', $game->id, 0, $grades, $params);
}


/**
 * Delete grade item for given game
 *
 * @param object $game object
 * @return object game
 */
function game_grade_item_delete( $game) {
    global $CFG;
    
    if( file_exists( $CFG->libdir.'/gradelib.php')){
        require_once($CFG->libdir.'/gradelib.php');
    }else{
        return;
    }    

    return grade_update('mod/game', $game->course, 'mod', 'game', $game->id, 0, NULL, array('deleted'=>1));
}

/**
 * Returns all game graded users since a given time for specified game
 */
function game_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0)  {
    global $CFG, $COURSE, $USER;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = get_record('course', 'id', $courseid);
    }

    $modinfo =& get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    if ($userid) {
        $userselect = "AND u.id = $userid";
    } else {
        $userselect = "";
    }

    if ($groupid) {
        $groupselect = "AND gm.groupid = $groupid";
        $groupjoin   = "JOIN {$CFG->prefix}groups_members gm ON  gm.userid=u.id";
    } else {
        $groupselect = "";
        $groupjoin   = "";
    }
    
    if (!$attempts = get_records_sql("SELECT qa.*, q.grade,
                                             u.firstname, u.lastname, u.email, u.picture 
                                        FROM {$CFG->prefix}game_attempts qa
                                             JOIN {$CFG->prefix}game q ON q.id = qa.gameid
                                             JOIN {$CFG->prefix}user u ON u.id = qa.userid
                                             $groupjoin
                                       WHERE qa.timefinish > $timestart AND q.id = $cm->instance
                                             $userselect $groupselect
                                    ORDER BY qa.timefinish ASC")) {
         return;
    }


    $cm_context      = get_context_instance(CONTEXT_MODULE, $cm->id);
    $grader          = has_capability('moodle/grade:viewall', $cm_context);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cm_context);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cm_context);
    //$grader          = has_capability('mod/game:grade', $cm_context);
    $grader          = isteacher( $courseid, $userid);
    $groupmode       = groups_get_activity_groupmode($cm, $course);

    if (is_null($modinfo->groups)) {
        $modinfo->groups = groups_get_user_groups($course->id); // load all my groups and cache it in modinfo
    }

    $aname = format_string($cm->name,true);
    foreach ($attempts as $attempt) {
        if ($attempt->userid != $USER->id) {
            if (!$grader) {
                // grade permission required
                continue;
            }

            if ($groupmode == SEPARATEGROUPS and !$accessallgroups) { 
                $usersgroups = groups_get_all_groups($course->id, $attempt->userid, $cm->groupingid);
                if (!is_array($usersgroups)) {
                    continue;
                }
                $usersgroups = array_keys($usersgroups);
                $interset = array_intersect($usersgroups, $modinfo->groups[$cm->id]);
                if (empty($intersect)) {
                    continue;
                }
            }
       }

        $tmpactivity = new object();

        $tmpactivity->type      = 'game';
        $tmpactivity->cmid      = $cm->id;
        $tmpactivity->name      = $aname;
        $tmpactivity->sectionnum= $cm->sectionnum;
        $tmpactivity->timestamp = $attempt->timefinish;
        
        $tmpactivity->content->attemptid = $attempt->id;
        $tmpactivity->content->sumgrades = $attempt->score * $attempt->grade;
        $tmpactivity->content->maxgrade  = $attempt->grade;
        $tmpactivity->content->attempt   = $attempt->attempt;
        
        $tmpactivity->user->userid   = $attempt->userid;
        $tmpactivity->user->fullname = fullname($attempt, $viewfullnames);
        $tmpactivity->user->picture  = $attempt->picture;
        
        $activities[$index++] = $tmpactivity;
    }

  return;
}

function game_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    print_user_picture($activity->user->userid, $courseid, $activity->user->picture);
    echo "</td><td>";

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo "<img src=\"$CFG->modpixpath/{$activity->type}/icon.gif\" ".
             "class=\"icon\" alt=\"$modname\" />";
        echo "<a href=\"$CFG->wwwroot/mod/game/view.php?id={$activity->cmid}\">{$activity->name}</a>";
        echo '</div>';
    }

    echo '<div class="grade">';
    echo  get_string("attempt", "game")." {$activity->content->attempt}: ";
    $grades = "({$activity->content->sumgrades} / {$activity->content->maxgrade})";
    echo "<a href=\"$CFG->wwwroot/mod/game/review.php?attempt={$activity->content->attemptid}\">$grades</a>";
    echo '</div>';

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->userid}&amp;course=$courseid\">"
         ."{$activity->user->fullname}</a> - ".userdate($activity->timestamp);
    echo '</div>';

    echo "</td></tr></table>";

    return;
}

function game_before_add_or_update(&$game) {

    $pos = strpos( $game->questioncategoryid, ',');
    if( $pos != false)
        $game->questioncategoryid = substr( $game->questioncategoryid, 0, $pos);

    if( $game->gamekind == 'millionaire')
    {
        if( substr( $game->param8, 0, 1) == '#')
        {
            $game->param8 = hexdec(substr( $game->param8, 1));
        }
    }else if( $game->gamekind == 'snakes')
    {
        $s = '';
        if( $game->param3 == 0)
        {   
            if( isset( $_POST[ 'snakes_cols']))
            {
                $fields = array( 'snakes_board', 'snakes_cols', 'snakes_rows', 'snakes_headerx', 'snakes_headery', 'snakes_footerx', 'snakes_footery');
                foreach( $fields as $f)
                    $s .= '#'.$f.':'.$_POST[ $f];
                $s = substr( $s, 1);
            }
        }
        $game->param9 = $s;
    }
}

/**
 * This function is called at the end of game_add_instance
 * and game_update_instance, to do the common processing.
 *
 * @param object $game the game object.
 */
function game_after_add_or_update($game) {

    if( $game->gamekind == 'snakes')
    {    
        game_snakes_savefile( $game);
    }

    //update related grade item
    game_grade_item_update( stripslashes_recursive( $game));
}

function game_file_area_name( $game) {
    global $CFG;
//  Creates a directory file name, suitable for make_upload_directory()

    return "$CFG->dataroot/$game->course/$CFG->moddata/game/$game->id";
}

function game_snakes_savefile( &$game)
{
    $filename = basename( $_FILES['param4']['name']);
    if( $filename == '')
    {
        $game->param4 = 0;
        return true;
    }
    
    $target_path = game_file_area_name( $game);            
    if( !check_dir_exists( $target_path, true, true))
    {
        $game->param4 = 0;
        return false;    
    }
    game_snakes_removefile( $target_path, 'board');
    
    $pos = strrpos( $filename, '.');
    if( $pos == false)
    {
        $game->param4 = 0;
        return false;
    }
    $filename = 'file'.substr( $filename, $pos);
    
    game_snakes_removefile( $target_path, 'file');

    $target_path = $target_path . '/'.$filename;

    if( move_uploaded_file($_FILES['param4']['tmp_name'], $target_path)) {
        $game->param4 = 1;
        return true;
    } else{
        $game->param4 = 0;
        return false;
    }
}

//remove all files that start with name $file
function game_snakes_removefile( $target_path, $file)
{
    $d = dir( $target_path);
    while (false !== ($entry = $d->read())) {
        if( substr( $entry, 0, strlen( $file)) != $file)
            continue;
                
        unlink( $target_path.'/'.$entry);
    }
    $d->close();        
}


/**
 * Removes all grades from gradebook
 * @param int $courseid
 * @param string optional type
 */
function game_reset_gradebook($courseid, $type='') {
    global $CFG;

    $sql = "SELECT q.*, cm.idnumber as cmidnumber, q.course as courseid
              FROM {$CFG->prefix}game q, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
             WHERE m.name='game' AND m.id=cm.module AND cm.instance=q.id AND q.course=$courseid";

    if ($games = get_records_sql( $sql)) {
        foreach ($games as $game) {
            game_grade_item_update( $game, 'reset');
        }
    }
}

/**
 * Returns an array of game type objects to construct
 * menu list when adding new game 
 *
 */
function game_get_types(){
    global $CFG;

    $types = array();

    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game_group_start";
    $type->typestr = '--'.get_string( 'modulenameplural', 'game');
    $types[] = $type;

    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game&amp;type=hangman";
    $type->typestr = get_string('game_hangman', 'game');
    $types[] = $type;

    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game&amp;type=contest";
    $type->typestr = get_string('game_contest', 'game');
    $types[] = $type;

    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game&amp;type=cross";
    $type->typestr = get_string('game_cross', 'game');
    $types[] = $type;
    
    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game&amp;type=cryptex";
    $type->typestr = get_string('game_cryptex', 'game');
    $types[] = $type;

    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game&amp;type=hiddenpicture";
    $type->typestr = get_string('game_hiddenpicture', 'game');
    $types[] = $type;
 
    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game&amp;type=millionaire";
    $type->typestr = get_string('game_millionaire', 'game');
    $types[] = $type;
   
    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game&amp;type=snakes";
    $type->typestr = get_string('game_snakes', 'game');
    $types[] = $type;
 
    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game&amp;type=sudoku";
    $type->typestr = get_string('game_sudoku', 'game');
    $types[] = $type;
   
    if(get_record_select( 'modules', "name='book'", 'id,id')){
        $type = new object();
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->type = "game&amp;type=bookquiz";
        $type->typestr = get_string('game_bookquiz', 'game');
        $types[] = $type;
    }

    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "game_group_end";
    $type->typestr = '--';
    $types[] = $type;

    return $types;

}

/**
 * Return a textual summary of the number of attemtps that have been made at a particular game,
 * returns '' if no attemtps have been made yet, unless $returnzero is passed as true.
 * @param object $game the game object. Only $game->id is used at the moment.
 * @param object $cm the cm object. Only $cm->course, $cm->groupmode and $cm->groupingid fields are used at the moment.
 * @param boolean $returnzero if false (default), when no attempts have been made '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return string a string like "Attempts: 123", "Attemtps 123 (45 from your groups)" or
 *          "Attemtps 123 (45 from this group)".
 */
function game_num_attempt_summary($game, $cm, $returnzero = false, $currentgroup = 0) {
    global $CFG, $USER;
    $numattempts = count_records('game_attempts', 'gameid', $game->id, 'preview', 0);
    if ($numattempts || $returnzero) {
        if (groups_get_activity_groupmode($cm)) {
            $a->total = $numattempts;
            if ($currentgroup) {
                $a->group = count_records_sql('SELECT count(1) FROM ' .
                        $CFG->prefix . 'game_attempts qa JOIN ' .
                        $CFG->prefix . 'groups_members gm ON qa.userid = gm.userid ' .
                        'WHERE gameid = ' . $game->id . ' AND preview = 0 AND groupid = ' . $currentgroup);
                return get_string('attemptsnumthisgroup', 'quiz', $a);
            } else if ($groups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid)) { 
                $a->group = count_records_sql('SELECT count(1) FROM ' .
                        $CFG->prefix . 'game_attempts qa JOIN ' .
                        $CFG->prefix . 'groups_members gm ON qa.userid = gm.userid ' .
                        'WHERE gameid = ' . $game->id . ' AND preview = 0 AND ' .
                        'groupid IN (' . implode(',', array_keys($groups)) . ')');
                return get_string('attemptsnumyourgroups', 'quiz', $a);
            }
        }
        return get_string('attemptsnum', 'quiz', $numattempts);
    }
    return '';
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the Game.
 * @param object $mform form passed by reference
 */
function game_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'gameheader', get_string('modulenameplural', 'game'));
    $mform->addElement('checkbox', 'reset_game_all', get_string('reset_game_all','game'));
    
    $mform->addElement('checkbox', 'reset_game_deleted_course', get_string('reset_game_deleted_course', 'game'));
}

/**
 * Course reset form defaults.
 * @return array
 */
function game_reset_course_form_defaults($course) {
    return array('reset_game_all'=>0, 'reset_game_deleted_course' => 0);
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * Game responses for course $data->courseid.
 *
 * @global object
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function game_reset_userdata($data) {
    global $CFG;

    $componentstr = get_string('modulenameplural', 'game');
    $status = array();

    for($i=1; $i <= 2; $i++){
        if( $i == 1){
            if (empty($data->reset_game_all))
                continue;           
            $allgamessql = "SELECT g.id FROM {$CFG->prefix}game g WHERE g.course = ".$data->courseid;
            $allattemptssql = "SELECT ga.id FROM {$CFG->prefix}game g LEFT JOIN {$CFG->prefix}game_attempts ga ON g.id = ga.gameid WHERE g.course = ".$data->courseid;
            $newstatus = array('component'=>$componentstr, 'item'=>get_string('reset_game_all', 'game'), 'error'=>false);
        }else if( $i == 2)
        {
            //Delete data of deleted courses
            if (empty($data->reset_game_deleted_course))
                continue;           
            $allgamessql = "SELECT g.id FROM {$CFG->prefix}game g WHERE NOT EXISTS( SELECT * FROM {$CFG->prefix}course c WHERE c.id = g.course)";
            $allattemptssql = "SELECT ga.id FROM {$CFG->prefix}game_attempts ga WHERE NOT EXISTS( SELECT * FROM {$CFG->prefix}game g WHERE ga.gameid = g.id)";
            $newstatus = array('component'=>$componentstr, 'item'=>get_string('reset_game_deleted_course', 'game'), 'error'=>false);
        }
        
        $recs = get_recordset_sql($allgamessql);
        if ($recs != false) {
            foreach ($recs as $rec) {
                $game = get_record_select( 'game', 'id='.$rec[ 'id'], 'id,name,course');
                
                remove_dir( $CFG->dataroot.'/'.$game->course.'/moddata/game/'.$game->id);
                
                //reset grades
                $grades = NULL;
                $params = array('itemname'=>$game->name, 'idnumber'=>0);
                $params['reset'] = true;
                grade_update('mod/game', $game->course, 'mod', 'game', $game->id, 0, $grades, $params);
            }
       }
        
        delete_records_select('game_bookquiz', "id IN ($allgamessql)");
        delete_records_select('game_bookquiz_chapters', "attemptid IN ($allattemptssql)");
        delete_records_select('game_bookquiz_questions', "gameid IN ($allgamessql)");
        delete_records_select('game_cross', "id IN ($allgamessql)");
        delete_records_select('game_cryptex', "id IN ($allgamessql)");
        delete_records_select('game_export_html', "id IN ($allgamessql)");
        delete_records_select('game_export_javame', "id IN ($allgamessql)");
        delete_records_select('game_grades', "gameid IN ($allgamessql)");
        delete_records_select('game_hangman', "id IN ($allgamessql)");
        delete_records_select('game_hiddenpicture', "id IN ($allgamessql)");
        delete_records_select('game_millionaire', "id IN ($allgamessql)");
        delete_records_select('game_queries', "gameid IN ($allgamessql)");
        delete_records_select('game_repetitions', "gameid IN ($allgamessql)");
        delete_records_select('game_snakes', "id IN ($allgamessql)");
        delete_records_select('game_sudoku', "id IN ($allgamessql)");

        if( $i == 2)
            delete_records_select('game_attempts', "NOT EXISTS (SELECT * FROM {$CFG->prefix}game g WHERE {$CFG->prefix}game_attempts.gameid=g.id)");
        else
            delete_records_select('game_attempts', "gameid IN ($allgamessql)");        
        
        $status[] = $newstatus;
    }
    
    if (empty($data->reset_game_deleted_course))
        return $status;
        
    //Delete data from deleted games
    $a = array( 'bookquiz', 'cross', 'cryptex', 'grades', 'bookquiz_questions', 'export_html', 'export_javame', 'hangman', 
            'hiddenpicture', 'millionaire', 'snakes', 'sudoku');
    foreach( $a as $table)
        delete_records_select( 'game_'.$table, "NOT EXISTS( SELECT * FROM {$CFG->prefix}game g WHERE {$CFG->prefix}game_$table.id=g.id)");
    
    //Tables that have the field gameid
    $a = array( 'grades', 'queries', 'repetitions');
    foreach( $a as $table)
        delete_records_select( 'game_'.$table, "NOT EXISTS( SELECT * FROM {$CFG->prefix}game g WHERE {$CFG->prefix}game_$table.gameid=g.id)");
    
    //Tables that have the key attemptid
    $a = array( 'bookquiz_chapters');
    foreach( $a as $table)
        delete_records_select( 'game_'.$table, "NOT EXISTS( SELECT * FROM {$CFG->prefix}game_attempts ga WHERE {$CFG->prefix}game_$table.attemptid=ga.id)");

    return $status;
}

function game_file_area_query_name( $game, $query) {
    return game_file_area_name( $game).'/queries/'.$query->id;
}


