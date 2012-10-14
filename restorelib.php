<?php //$Id: restorelib.php,v 1.6.2.4 2011/08/27 19:40:28 bdaloukas Exp $
    //This php script contains all the stuff to restore game mods

// Todo:

    // whereever it says "/// We have to recode the .... field" we should put in a check
    // to see if the recoding was successful and throw an appropriate error otherwise

//This is the "graphical" structure of the game mod:
    //To see, put your terminal to 160cc

    //
    //                           game
    //                        (CL,pk->id)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          SL->site level info
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files
    //
    //-----------------------------------------------------------
    require( 'locallib.php');

    function game_restore_mods($mod,$restore) {

        global $CFG;
        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('Game', $restore, $info['MOD']['#'], array('TIMEOPEN', 'TIMECLOSE'));
            }            
            //Now, build the GAME record structure
            $game = new stdClass;
            $game->course = $restore->course_id;
                        
            $mod_info = $info[ 'MOD'];

			$fields = array( 'name', 'sourcemodule', 'timeopen', 'timeclose', 'quizid', 'glossaryid', 'glossarycategoryid', 'questioncategoryid',
					'bookid', 'gameinputid', 'gamekind', 'param1', 'param2', 'param3', 'param4', 'param5', 'param6', 'param7', 'param8',
					'timemodified', 'bottomtext', 'grademethod', 'grade', 'decimalpoints', 'review', 'attempts',
					'glossaryid2', 'glossarycategoryid2', 'language');
			game_restore_record( $mod_info, $game, $fields);
			
			game_recode( $restore->backup_unique_code, $game, 'quizid', 'quiz');
			game_recode( $restore->backup_unique_code, $game, 'glossaryid', 'glossary');
			game_recode( $restore->backup_unique_code, $game, 'glossarycategoryid', 'glossary_categories');
			game_recode( $restore->backup_unique_code, $game, 'glossaryid2', 'glossary');
			game_recode( $restore->backup_unique_code, $game, 'glossarycategoryid2', 'glossary_categories');
			
            game_restore_stamp( $info, $restore, $map_question_categories, 'QUESTION_CATEGORIES', 'QUESTION_CATEGORY');
            game_restore_stamp( $info, $restore, $map_questions, 'QUESTIONS', 'QUESTION');

			game_recode_questioncategoryid( $game, $map_question_categories);
			game_recode( $restore->backup_unique_code, $game, 'bookid', 'book');
						
            //The structure is equal to the db, so insert the game
            $newid = insert_record ("game", $game);

            //Do some output
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","game")." \"".format_string(stripslashes($game->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype, $mod->id, $newid);
                
                 //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore,'game',$mod->id)) {
                    $status = game_restore_grades( $newid, $info, $restore);
                    $status = game_restore_snakes_database( $info, $restore);
                    $status = game_restore_bookquiz_questions( $newid, $info, $restore, $map_question_categories);
                
                    //Restore game_attempts
                    $status = game_attempts_restore_mods ($newid,$info,$restore, $map_questions);                    
                }
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }
    
    function game_recode( $backup_unique_code, &$rec, $field, $table){
        $data = backup_getid( $backup_unique_code, $table, $rec->$field);
        
        if( $data != false){
            $rec->$field = $data->new_id;
        }
    }

	function game_restore_record( $info, &$table, $fields){
	    $info = $info[ '#'];
		foreach( $fields as $f){
		    $table->$f = backup_todb($info[ strtoupper( $f)]['0']['#']);
		}
	}
	
    function game_recode_questioncategoryid( &$game, $map_question_categories){
    
        $stamp = $map_question_categories[ $game->questioncategoryid];
        
        $game->questioncategoryid = get_field_select( 'question_categories', 'id', "stamp='$stamp'");
    }
	
    //This function restores the quiz_attempts
    function game_attempts_restore_mods($newgameid,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the game_attempts array
        if (array_key_exists('GAME_ATTEMPTS', $info['MOD']['#'])) {
            $attempts = $info['MOD']['#']['GAME_ATTEMPTS']['0']['#']['GAME_ATTEMPT'];
        } else {
            $attempts = array();
        }

        //Iterate over attempts
        for($i = 0; $i < sizeof($attempts); $i++) {
            $att_info = $attempts[$i];
            
            //We'll need this later!!
            $oldid = backup_todb($att_info['#']['ID']['0']['#']);

            //Now, build the ATTEMPTS record structure
            $attempt = new stdClass;
            $attempt->gameid = $newgameid;
			$fields = array( 'userid', 'timestart', 'timefinish', 'timelastattempt', 'lastip',
					'lastremotehost', 'preview', 'attempt', 'score', 'attempts');
			game_restore_record( $att_info, $attempt, $fields);
            //We have to recode
			game_recode( $restore->backup_unique_code, $attempt, 'userid', 'users');

            //The structure is equal to the db, so insert the quiz_attempts
            $newid = insert_record ("game_attempts", $attempt);

            //Do some output
            game_do_some_output( $i);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code, "game_attempts", $oldid, $newid);
                //Now process game_hangman, game_cross
                $status = game_restore_queries( $newgameid, $newid, $att_info, $restore);
                $status = game_restore_hangman( $newid, $att_info, $restore);
                $status = game_restore_cross( $newid, $att_info, $restore);
                $status = game_restore_cryptex( $newid, $att_info, $restore);
                $status = game_restore_millionaire( $newid, $att_info, $restore);
                $status = game_restore_sudoku( $newid, $att_info, $restore);
                $status = game_restore_snakes( $newid, $att_info, $restore);
                $status = game_restore_hiddenpicture( $newid, $att_info, $restore);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function restores the game_snakes_database
    function game_restore_snakes_database( $info, $restore) {

        global $CFG;

        $status = true;
        //Get the game_attempts array
        if (array_key_exists('GAME_SNAKES_DATABASE', $info['MOD']['#'])) {
            $recs = $info['MOD']['#']['GAME_SNAKES_DATABASE']['0']['#']['GAME_SNAKES_DATABASE_RECORD'];
        } else {
            return $status;
        }
        
        //Iterate over snakes_database
        for($i = 0; $i < sizeof($recs); $i++) {
            $att_info = $recs[$i];
            
            //Now, build the game_snakes_database record structure
            $snakes_database = new stdClass;
            $fields = array( 'id', 'name', 'cols', 'rows', 'data', 'fileboard', 'direction', 'headerx', 'headery',
                    'footerx', 'footery', 'width', 'height');
            game_restore_record( $att_info, $snakes_database, $fields);
            
            if( ($recdb = get_record_select( 'game_snakes_database', "name='{$snakes_database->name}'")) != false){
                backup_putid($restore->backup_unique_code, 'game_snakes_database', $snakes_database->id, $recdb->id);
                continue;
            }
        
            //The structure is equal to the db, so insert the quiz_attempts
            $newid = insert_record ("game_snakes_database", $snakes_database);

            //Do some output
            game_do_some_output( $i);

            if ($newid == false) {
                $status = false;
            }
        }        
                      
        return $status;
    }

    //This function restores the game_grades
    function game_restore_grades($newgameid,$info,$restore) {

        global $CFG;

        $status = true;
        //Get the game_attem array
        if (array_key_exists('GAME_GRADES', $info['MOD']['#'])) {
            $recs = $info['MOD']['#']['GAME_GRADES']['0']['#']['GAME_GRADE'];
        } else {
            return $status;
        }
        
        //Iterate over game_grades
        for($i = 0; $i < sizeof($recs); $i++) {
            $att_info = $recs[$i];
            
            //Now, build the game_grades record structure
            $game_grade = new stdClass;
            $game_grade->gameid = $newgameid;
            $fields = array( 'userid', 'score', 'timemodified');
            game_restore_record( $att_info, $game_grade, $fields);
                    
            //The structure is equal to the db, so insert the quiz_attempts
            $newid = insert_record ("game_grades", $games_grade);

            //Do some output
            game_do_some_output( $i);
        }
             
        return $status;
    }

    function game_restore_stamp( $info, $restore, &$map, $tags, $tag) {

        global $CFG;

        $map = array();
        
        $status = true;
        //Get the game_attempt array
        if (array_key_exists($tags, $info['MOD']['#'])) {
            $recs = $info['MOD']['#'][$tags]['0']['#'][$tag];
        } else {
            return $status;
        }
        
        //Iterate 
        for($i = 0; $i < sizeof($recs); $i++) {
            $att_info = $recs[$i];
            
            $rec = new stdClass;
            $fields = array( 'id', 'stamp');
            game_restore_record( $att_info, $rec, $fields);
            
            $map[ $rec->id] = $rec->stamp;
        }
        
        return $status;
    }
    
    //This function restores the game_bookquiz_questions
    function game_restore_bookquiz_questions($newgameid,$info,$restore, $map_question_categories) {

        global $CFG;

        $status = true;
        //Get the c array
        if (array_key_exists('GAME_BOOKQUIZ_QUESTIONS', $info['MOD']['#'])) {
            $recs = $info['MOD']['#']['GAME_BOOKQUIZ_QUESTIONS']['0']['#']['GAME_BOOKQUIZ_QUESTION'];
        } else {
            return $status;
        }
        
        //Iterate over game_bookquiz_questions
        for($i = 0; $i < sizeof($recs); $i++) {
            $att_info = $recs[$i];
            
            //Now, build the game_snakes_database record structure
            $game_bookquiz_question = new stdClass;
            $game_bookquiz_question->gameid = $newgameid;
            $fields = array( 'chapterid', 'questioncategoryid');
            game_restore_record( $att_info, $game_bookquiz_question, $fields);
                    
            //We have to recode the some 
            game_recode( $restore->backup_unique_code, $game_bookquiz_question, 'chapterid', 'book_chapters');                    
            game_recode_questioncategoryid( $game_bookquiz_question, $map_question_categories);
                    
            //The structure is equal to the db, so insert the game_bookquiz_question
            $newid = insert_record ("game_bookquiz_questions", $game_bookquiz_question);

            game_do_some_output( $i);
        }        
                      
        return $status;
    }
    
    
    function game_restore_queries( $newgameid, $newattemptid, $info, $restore) {

        global $CFG;

        $status = true;
        //Get the c array
        if (array_key_exists('GAME_QUERIES', $info['#'])) {
            $recs = $info['#']['GAME_QUERIES']['0']['#']['GAME_QUERY'];
        } else {
            return $status;
        }
        
        //Iterate over game_queries
        for($i = 0; $i < sizeof($recs); $i++) {
            $att_info = $recs[$i];
            
            //Now, build the game_queries record structure
            $game_query = new stdClass;
            $game_query->gameid = $newgameid;
            $game_query->attemptid = $newattemptid;
            $fields = array( 'gamekind', 'userid', 'sourcemodule','questionid',
                             'glossaryentryid', 'questiontext', 'score', 'timelastattempt', 'studentanswer', 
                             'col', 'row', 'horizontal', 'answertext', 'correct');
            game_restore_record( $att_info, $game_query, $fields);

            //We have to recode the some
            game_recode( $restore->backup_unique_code, $game_query, 'userid', 'users');                    
            game_recode( $restore->backup_unique_code, $game_query, 'questionid', 'questions');                    
            game_recode( $restore->backup_unique_code, $game_query, 'glossaryentryid', 'glossary_entries');                    
                    
            //The structure is equal to the db, so insert the game_bookquiz_question
            $newid = insert_record ("game_queries", $game_query);
        }
                      
        return $status;
    }

    function game_do_some_output( $i){
        //Do some output
        if (defined('RESTORE_SILENTLY')) {
            return;
        }
        
        if( ($i+1) % 100 == 0){
            return;
        }
        
        echo ".";
        if (($i+1) % 200*100 == 0) {
            echo "<br />";
        }
        
        backup_flush( 300);
    }
    
    //This function restores the game_hangman
    function game_restore_hangman( $newattemptid, $info, $restore) {

        global $CFG;

        $status = true;
        

        //Get the game_hangman array
        if (array_key_exists('GAME_HANGMAN', $info['#'])) {
            $info = $info['#']['GAME_HANGMAN'][ 0];
        } else {
            return $status;
        }

        //Now, build the game_hangman record structure
        $game_hangman = new stdClass;
        $game_hangman->id = $newattemptid;
        $fields = array( 'queryid', 'letters', 'allletters', 'try', 'maxtries', 'finishedword', 'corrects', 'iscorrect');
        game_restore_record( $info, $game_hangman, $fields);

        //We have to recode the some
		game_recode( $restore->backup_unique_code, $game_hangman, 'queryid', 'game_queries');

        //The structure is equal to the db, so insert the game_hangman
        if( ( game_insert_record ("game_hangman", $game_hangman) == false)){
            $status = false;
        }
        return $status;
    }

    //This function restores the game_hiddenpicture
    function game_restore_hiddenpicture( $newattemptid, $info, $restore) {

        global $CFG;

        $status = true;
        

        //Get the game_hiddenpicture array
        if (array_key_exists('GAME_HIDDENPICTURE', $info['#'])) {
            $info = $info['#']['GAME_HIDDENPICTURE'][ 0];
        } else {
            return $status;
        }

        //Now, build the game_hiddenpicture record structure
        $game_hiddenpicture = new stdClass;
        $game_hiddenpicture->id = $newattemptid;
        $fields = array( 'correct', 'wrong', 'found');
        game_restore_record( $info, $game_hiddenpicture, $fields);

        //The structure is equal to the db, so insert the game_hiddenpicture
        if( ( game_insert_record ("game_hiddenpicture", $game_hiddenpicture) == false)){
            $status = false;
        }
        return $status;
    }

    //This function restores the game_cross
    function game_restore_cross( $newattemptid, $info, $restore) {

        global $CFG;

        $status = true;
        
        //Get the game_cross array
        if (array_key_exists('GAME_CROSS', $info['#'])) {
            $info = $info['#']['GAME_CROSS'][ 0];
        } else {
            return $status;
        }

        //Now, build the game_cross record structure
        $game_cross = new stdClass;
        $game_cross->id = $newattemptid;
        $fields = array( 'cols', 'rows', 'words', 'wordsall', 'createscore', 'createtries', 
                        'createtimelimit', 'createconnectors', 'createfilleds', 'createspaces');
        game_restore_record( $info, $game_cross, $fields);

        //The structure is equal to the db, so insert the game_hangman
        if( ( game_insert_record ("game_cross", $game_cross) == false)){
            $status = false;
        }
        return $status;
    }

  //This function restores the game_cryptex
    function game_restore_cryptex( $newattemptid, $info, $restore) {

        global $CFG;

        $status = true;
        
        //Get the game_cryptex array
        if (array_key_exists('GAME_CRYPTEX', $info['#'])) {
            $info = $info['#']['GAME_CRYPTEX'][ 0];
        } else {
            return $status;
        }

        //Now, build the game_cryptex record structure
        $game_cryptex = new stdClass;
        $game_cryptex->id = $newattemptid;
        $fields = array( 'letters');
        game_restore_record( $info, $game_cryptex, $fields);

        //The structure is equal to the db, so insert the game_cryptex
        if( ( game_insert_record ("game_cryptex", $game_cryptex) == false)){
            $status = false;
        }
        return $status;
    }

  //This function restores the game_millionaire
    function game_restore_millionaire( $newattemptid, $info, $restore) {

        global $CFG;

        $status = true;
        
        //Get the game_cryptex array
        if (array_key_exists('GAME_MILLIONAIRE', $info['#'])) {
            $info = $info['#']['GAME_MILLIONAIRE'][ 0];
        } else {
            return $status;
        }

        //Now, build the game_millionaire record structure
        $game_millionaire = new stdClass;
        $game_millionaire->id = $newattemptid;
        $fields = array( 'queryid', 'state', 'level');
        game_restore_record( $info, $game_millionaire, $fields);

        //We have to recode the some 
		game_recode( $restore->backup_unique_code, $game_millionaire, 'queryid', 'game_queries');

        //The structure is equal to the db, so insert the game_millionaire
        if( ( game_insert_record ("game_millionaire", $game_millionaire) == false)){
            $status = false;
        }
        return $status;
    }

    //This function restores the game_sudoku
    function game_restore_sudoku( $newattemptid, $info, $restore) {

        global $CFG;

        $status = true;
        
        //Get the game_cryptex array
        if (array_key_exists('GAME_SUDOKU', $info['#'])) {
            $info = $info['#']['GAME_SUDOKU'][ 0];
        } else {
            return $status;
        }

        //Now, build the game_cryptex record structure
        $game_sudoku = new stdClass;
        $game_sudoku->id = $newattemptid;
        $fields = array( 'level', 'data', 'opened', 'guess');
        game_restore_record( $info, $game_sudoku, $fields);

        //The structure is equal to the db, so insert the game_sudoku
        if( ( game_insert_record ("game_sudoku", $game_sudoku) == false)){
            $status = false;
        }
        return $status;
    }

    //This function restores the game_snakes
    function game_restore_snakes( $newattemptid, $info, $restore) {

        global $CFG;

        $status = true;
        
        //Get the game_snakes array
        if (array_key_exists('GAME_SNAKES', $info['#'])) {
            $info = $info['#']['GAME_SNAKES'][ 0];
        } else {
            return $status;
        }

        //Now, build the game_snakes record structure
        $game_snakes = new stdClass;
        $game_snakes->id = $newattemptid;
        $fields = array( 'snakesdatabaseid', 'queryid', 'position', 'dice');
        game_restore_record( $info, $game_snakes, $fields);

        //We have to recode the some 
        game_recode( $restore->backup_unique_code, $game_snakes, 'queryid', 'game_queries');
        game_recode( $restore->backup_unique_code, $game_snakes, 'snakesdatabaseid', 'game_snakes_database');

        //The structure is equal to the db, so insert the game_snakes
        if( ( game_insert_record ("game_snakes", $game_snakes) == false)){
            $status = false;
        }
        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function game_restore_logs($restore,$log) {

        $status = false;

        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            $log->url = "index.php?id=".$log->course;
            $status = true;
            break;
        case "report":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "report.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "attempt":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    //Extract the attempt id from the url field
                    $attid = substr(strrchr($log->url,"="),1);
                    //Get the new_id of the attempt (to recode the url field)
                    $att = backup_getid($restore->backup_unique_code,"game_attempts",$attid);
                    if ($att) {
                        $log->url = "review.php?id=".$log->cmid."&attempt=".$att->new_id;
                        $log->info = $mod->new_id;
                        $status = true;
                    }
                }
            }
            break;
        case "submit":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    //Extract the attempt id from the url field
                    $attid = substr(strrchr($log->url,"="),1);
                    //Get the new_id of the attempt (to recode the url field)
                    $att = backup_getid($restore->backup_unique_code,"game_attempts",$attid);
                    if ($att) {
                        $log->url = "review.php?id=".$log->cmid."&attempt=".$att->new_id;
                        $log->info = $mod->new_id;
                        $status = true;
                    }
                }
            }
            break;
        case "review":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    //Extract the attempt id from the url field
                    $attid = substr(strrchr($log->url,"="),1);
                    //Get the new_id of the attempt (to recode the url field)
                    $att = backup_getid($restore->backup_unique_code,"game_attempts",$attid);
                    if ($att) {
                        $log->url = "review.php?id=".$log->cmid."&attempt=".$att->new_id;
                        $log->info = $mod->new_id;
                        $status = true;
                    }
                }
            }
            break;
        case "editquestions":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the url field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "preview":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the url field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "attempt.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "start attempt":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    //Extract the attempt id from the url field
                    $attid = substr(strrchr($log->url,"="),1);
                    //Get the new_id of the attempt (to recode the url field)
                    $att = backup_getid($restore->backup_unique_code,"game_attempts",$attid);
                    if ($att) {
                        $log->url = "review.php?id=".$log->cmid."&attempt=".$att->new_id;
                        $log->info = $mod->new_id;
                        $status = true;
                    }
                }
            }
            break;
        case "close attempt":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    //Extract the attempt id from the url field
                    $attid = substr(strrchr($log->url,"="),1);
                    //Get the new_id of the attempt (to recode the url field)
                    $att = backup_getid($restore->backup_unique_code,"game_attempts",$attid);
                    if ($att) {
                        $log->url = "review.php?id=".$log->cmid."&attempt=".$att->new_id;
                        $log->info = $mod->new_id;
                        $status = true;
                    }
                }
            }
            break;
        case "continue attempt":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    //Extract the attempt id from the url field
                    $attid = substr(strrchr($log->url,"="),1);
                    //Get the new_id of the attempt (to recode the url field)
                    $att = backup_getid($restore->backup_unique_code,"game_attempts",$attid);
                    if ($att) {
                        $log->url = "review.php?id=".$log->cmid."&attempt=".$att->new_id;
                        $log->info = $mod->new_id;
                        $status = true;
                    }
                }
            }
            break;
        case "continue attemp":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    //Extract the attempt id from the url field
                    $attid = substr(strrchr($log->url,"="),1);
                    //Get the new_id of the attempt (to recode the url field)
                    $att = backup_getid($restore->backup_unique_code,"game_attempts",$attid);
                    if ($att) {
                        $log->url = "review.php?id=".$log->cmid."&attempt=".$att->new_id;
                        $log->info = $mod->new_id;
                        $log->action = "continue attempt";  //To recover some bad actions
                        $status = true;
                    }
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }  

?>
