<?php  //$Id: upgrade.php,v 1.21.2.9 2011/08/27 19:40:28 bdaloukas Exp $

// This file keeps track of upgrades to 
// the lesson module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_game_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

	//game.questioncategoryid
    if ($result && $oldversion < 2007082802) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('questioncategoryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'glossarycategoryid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_hangman.quizid
    if ($result && $oldversion < 2007082802) {

    /// Define field format to be added to data_comments
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('quizid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'questionid');

    /// Launch add field format
        $result = $result && add_field($table, $field);

    }

	//game_hangman.glossaryid
    if ($result && $oldversion < 2007082803) {
    /// Define field format to be added to data_comments
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('glossaryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'quizid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}

	//game_hangman.glossarycategoryid
    if ($result && $oldversion < 2007082803) {
    /// Define field format to be added to data_comments
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('glossarycategoryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'glossaryid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_hangman.questioncategoryid
    if ($result && $oldversion < 2007082803) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('questioncategoryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'glossarycategoryid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_millionaire.questioncategoryid
    if ($result && $oldversion < 2007082804) {
        $table = new XMLDBTable('game_millionaire');
        $field = new XMLDBField('questioncategoryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'quizid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_hangman.try
    if ($result && $oldversion < 2007082805) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('try');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'answer');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
	//game_hangman.maxtries
    if ($result && $oldversion < 2007082805) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('maxtries');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'try');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}

	//game_hangman.finishedword
    if ($result && $oldversion < 2007082807) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('finishedword');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'maxtries');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}


	//game_hangman.corrects
    if ($result && $oldversion < 2007082807) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('corrects');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'finishedword');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}


	//game.param7
    if ($result && $oldversion < 2007082808) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('param7');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'param6');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}


	//game_hangman.letters : change to char( 30)
    if ($result && $oldversion < 2007082809) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('letters');
        $field->setAttributes(XMLDB_TYPE_CHAR, '30', null, null, null, null, null, null);

    /// Launch change of precision for field lang
        $result = $result && change_field_precision($table, $field);
    }


    //gamg_hangman.glossaryid
    if ($result && $oldversion < 2007082901) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('glossaryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'quizid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_instances.lastip : change to char( 30)
    if ($result && $oldversion < 2007083002) {
        $table = new XMLDBTable('game_instances');
        $field = new XMLDBField('lastip');
        $field->setAttributes(XMLDB_TYPE_CHAR, '30', null, null, null, null, null, null, '', 'grade');

    /// Launch change of precision for field lang
        $result = $result && add_field($table, $field);
    }
	
	//game_bookquiz_questions.glossarycategoryid
    if ($result && $oldversion < 2007091001) {
        $table = new XMLDBTable('game_bookquiz_questions');
        $field = new XMLDBField('questioncategoryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}
	
	//new table game_bookquiz_chapters
    if ($result && $oldversion < 2007091701) {
        /// Define table scorm_scoes_data to be created
        $table = new XMLDBTable( 'game_bookquiz_chapters');

        /// Adding fields to table scorm_scoes_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('gameinstanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('chapterid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        
        /// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table scorm_scoes_data
        $table->addIndexInfo('gameinstanceidchapterid', XMLDB_INDEX_NOTUNIQUE, array('gameinstanceid', 'chapterid'));

        /// Launch create table for scorm_scoes_data
        $result = $result && create_table($table);
	}

	//new table game_snakes_database
    if ($result && $oldversion < 2007092207) {
        /// Define table scorm_scoes_data to be created
        $table = new XMLDBTable( 'game_snakes_database');

        /// Adding fields to table scorm_scoes_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null, '');
        $table->addFieldInfo('cols', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('rows', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('data', XMLDB_TYPE_TEXT, '0', null, XMLDB_NOTNULL, null, null, null, '');
        $table->addFieldInfo('file', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null, '');
        $table->addFieldInfo('direction', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('headerx', XMLDB_TYPE_INTEGER, '5', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('headery', XMLDB_TYPE_INTEGER, '5', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('footerx', XMLDB_TYPE_INTEGER, '5', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('footery', XMLDB_TYPE_INTEGER, '5', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

		/// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table for scorm_scoes_data
        $result = $result && create_table($table);
	}
	
    if ($result && $oldversion < 2007092208) {
        /// Define table scorm_scoes_data to be created
        $table = new XMLDBTable( 'game_snakes');

        /// Adding fields to table scorm_scoes_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('snakesdatabaseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('position', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

		/// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table for scorm_scoes_data
        $result = $result && create_table($table);
	}
	
	//game_snakes_database.width
    if ($result && $oldversion < 2007092301) {
        $table = new XMLDBTable('game_snakes_database');
        $field = new XMLDBField('width');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_snakes_database.height
    if ($result && $oldversion < 2007092302) {
        $table = new XMLDBTable('game_snakes_database');
        $field = new XMLDBField('height');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_snakes.sourcemodule
    if ($result && $oldversion < 2007092306) {
        $table = new XMLDBTable('game_snakes');
        $field = new XMLDBField('sourcemodule');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, null, null, null, null, '');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
	//game_snakes.questionid
    if ($result && $oldversion < 2007092307) {
        $table = new XMLDBTable('game_snakes');
        $field = new XMLDBField('questionid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_snakes.glossaryentryid
    if ($result && $oldversion < 2007092308) {
        $table = new XMLDBTable('game_snakes');
        $field = new XMLDBField('glossaryentryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }


	//game_snakes.dice
    if ($result && $oldversion < 2007092309) {
        $table = new XMLDBTable('game_snakes');
        $field = new XMLDBField('dice');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_instances.lastremotehost
    if ($result && $oldversion < 2007100601) {
        $table = new XMLDBTable('game_instances');
        $field = new XMLDBField('lastremotehost');
        $field->setAttributes(XMLDB_TYPE_CHAR, '50', null, null, null, null, null, '');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_questions.timelastattempt
    if ($result && $oldversion < 2007100605) {
        $table = new XMLDBTable('game_questions');
        $field = new XMLDBField('timelastattempt');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
	//game_instances.tries
    if ($result && $oldversion < 2007101301) {
        $table = new XMLDBTable('game_instances');
        $field = new XMLDBField('tries');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
	//1.4
	//drop game_bookquiz_questions.bookid
    if ($result && $oldversion < 2007110801) {
        $table = new XMLDBTable('game_bookquiz_questions');
        $field = new XMLDBField('bookid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }
	

	//new table game_grades
    if ($result && $oldversion < 2007110802) {
        /// Define table scorm_scoes_data to be created
        $table = new XMLDBTable( 'game_grades');

        /// Adding fields to table scorm_scoes_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('gameid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('score', XMLDB_TYPE_FLOAT, null, null, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

		/// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes
        $table->addIndexInfo('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->addIndexInfo('gameid', XMLDB_INDEX_NOTUNIQUE, array('gameid'));

        /// Launch create table for scorm_scoes_data
        $result = $result && create_table($table);
	}
	
	//drop game_hangman.sourcemodule
    if ($result && $oldversion < 2007110811) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('sourcemodule');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_hangman.questionid
    if ($result && $oldversion < 2007110812) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('questionsid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_hangman.sourcemodule
    if ($result && $oldversion < 2007110813) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('quizid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_hangman.glossaryid
    if ($result && $oldversion < 2007110814) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('glossaryid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_hangman.glossarycategoryid
    if ($result && $oldversion < 2007110815) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('glossarycategoryid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_hangman.glossaryentryid
    if ($result && $oldversion < 2007110816) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('glossaryentryid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	

	//drop game_hangman.question
    if ($result && $oldversion < 2007110818) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('question');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_hangman.answer
    if ($result && $oldversion < 2007110819) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('answer');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_millionaire.sourcemodule
    if ($result && $oldversion < 2007110820) {
        $table = new XMLDBTable('game_millionaire');
        $field = new XMLDBField('sourcemodule');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_millionaire.quizid
    if ($result && $oldversion < 2007110821) {
        $table = new XMLDBTable('game_millionaire');
        $field = new XMLDBField('quizid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_millionaire.questionid
    if ($result && $oldversion < 2007110822) {
        $table = new XMLDBTable('game_millionaire');
        $field = new XMLDBField('questionid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//game_millionaire.queryid
    if ($result && $oldversion < 2007110823) {
        $table = new XMLDBTable('game_millionaire');
        $field = new XMLDBField('queryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'id');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }	
	
	//drop game_bookquiz.bookid
    if ($result && $oldversion < 2007110824) {
        $table = new XMLDBTable('game_bookquiz');
        $field = new XMLDBField('bookid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }

	//drop game_sudoku.sourcemodule
    if ($result && $oldversion < 2007110825) {
        $table = new XMLDBTable('game_sudoku');
        $field = new XMLDBField('sourcemodule');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }

	//game_sudoku.level
    if ($result && $oldversion < 2007110826) {
        $table = new XMLDBTable('game_millionaire');
        $field = new XMLDBField('queryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, '0', 'id');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }	

	//drop game_sudoku.quizid
    if ($result && $oldversion < 2007110827) {
        $table = new XMLDBTable('game_sudoku');
        $field = new XMLDBField('quizid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }

	//drop game_sudoku.glossaryid
    if ($result && $oldversion < 2007110828) {
        $table = new XMLDBTable('game_sudoku');
        $field = new XMLDBField('glossaryid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }

	//drop game_sudoku.glossarycategoryid
    if ($result && $oldversion < 2007110829) {
        $table = new XMLDBTable('game_sudoku');
        $field = new XMLDBField('glossarycategoryid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }

	//drop game_sudoku.glossarycategoryid
    if ($result && $oldversion < 2007110830) {

        $result = $result && drop_table(new XMLDBTable('game_sudoku_questions'));
    }
	
	//drop game_cross.sourcemodule
    if ($result && $oldversion < 2007110832) {
        $table = new XMLDBTable('game_cross');
        $field = new XMLDBField('sourcemodule');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }
	
	//game_cross.createscore
    if ($result && $oldversion < 2007110833) {
        $table = new XMLDBTable('game_cross');
        $field = new XMLDBField('createscore');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'wordsall');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }	

	//rename field game_cross.
    if ($result && $oldversion < 2007110834) {
        $table = new XMLDBTable( 'game_bookquiz');
		$field = new XMLDBField( 'attemptid');
        $field->setAttributes(XMLDB_TYPE_FLOAT, null, null, null, null, null, null, '0');
		
        $result = $result && rename_field( $table, $field, 'score');
    }

	//drop game_cross.tries
    if ($result && $oldversion < 2007110835) {
        $table = new XMLDBTable('game_cross');
        $field = new XMLDBField('tries');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }

	//rename field game_cross.createtimelimit
    if ($result && $oldversion < 2007110836) {
        $table = new XMLDBTable( 'game_cross');
		$field = new XMLDBField( 'timelimit');
        $field->setAttributes(XMLDB_TYPE_FLOAT, null, null, null, null, null, null, '0');
		
        $result = $result && rename_field( $table, $field, 'createtimelimit');
    }
	
	//game_cross.createconnectors
    if ($result && $oldversion < 2007110837) {
        $table = new XMLDBTable('game_cross');
        $field = new XMLDBField('createconnectors');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }		
	
	//game_cross.createfilleds
    if ($result && $oldversion < 2007110838) {
        $table = new XMLDBTable('game_cross');
        $field = new XMLDBField('createfilleds');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }		

	//game_cross.createspaces
    if ($result && $oldversion < 2007110839) {
        $table = new XMLDBTable('game_cross');
        $field = new XMLDBField('createspaces');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }		
	
	//drop game_cross_questions
    if ($result && $oldversion < 2007110840) {
        $result = $result && drop_table(new XMLDBTable('game_cross_questions'));
    }	

	//rename table game_instances to game_attempts
    if ($result && $oldversion < 2007110841) {
        $table = new XMLDBTable( 'game_questions');
        $result = $result && rename_table( $table, 'game_queries');
    }


	//drop game_snakes.sourcemodule
    if ($result && $oldversion < 2007110853) {
        $table = new XMLDBTable('game_snakes');
        $field = new XMLDBField('sourcemodule');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_snakes.questionid
    if ($result && $oldversion < 2007110854) {
        $table = new XMLDBTable('game_snakes');
        $field = new XMLDBField('questionid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//drop game_snakes.glossaryentryid
    if ($result && $oldversion < 2007110855) {
        $table = new XMLDBTable('game_snakes');
        $field = new XMLDBField('glossaryentryid');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	

	//rename table game_instances to game_attempts
    if ($result && $oldversion < 2007110856) {
        $table = new XMLDBTable( 'game_instances');
        $result = $result && rename_table( $table, 'game_attempts');
    }

	//drop game_attempts.gamekind
    if ($result && $oldversion < 2007110857) {
        $table = new XMLDBTable('game_attempts');
        $field = new XMLDBField('gamekind');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	

	//drop game_attempts.finished
    if ($result && $oldversion < 2007110858) {
        $table = new XMLDBTable('game_attempts');
        $field = new XMLDBField( 'finished');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }	
	
	//game_attempts.timestart
    if ($result && $oldversion < 2007110859) {
        $table = new XMLDBTable( 'game_attempts');
		$field = new XMLDBField( 'timestarted');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
		
        $result = $result && rename_field( $table, $field, 'timestart');
    }		

	//game_attempts.timefinished
    if ($result && $oldversion < 2007110860) {
        $table = new XMLDBTable( 'game_attempts');
		$field = new XMLDBField( 'timefinished');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
		
        $result = $result && rename_field( $table, $field, 'timefinish');
    }		
	
	//drop game_attempts.grade
    if ($result && $oldversion < 2007110861) {
        $table = new XMLDBTable('game_attempts');
        $field = new XMLDBField( 'grade');

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }		
	
	//drop game_attempts.attempts
    if ($result && $oldversion < 2007110862) {
        $table = new XMLDBTable( 'game_attempts');
		$field = new XMLDBField( 'tries');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
		
        $result = $result && rename_field( $table, $field, 'attempts');
    }		
	
	//game_attempts.preview
    if ($result && $oldversion < 2007110863) {
        $table = new XMLDBTable( 'game_attempts');
		$field = new XMLDBField( 'preview');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '0', 'lastremotehost');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }		
	
	//game_attempts.attempt
    if ($result && $oldversion < 2007110864) {
        $table = new XMLDBTable( 'game_attempts');
		$field = new XMLDBField( 'attempt');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'preview');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }		
	
	//game_attempts.score
    if ($result && $oldversion < 2007110865) {
        $table = new XMLDBTable( 'game_attempts');
		$field = new XMLDBField( 'score');
        $field->setAttributes(XMLDB_TYPE_FLOAT, null, XMLDB_UNSIGNED, null, null, null, null, '0', 'attempt');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }		


	//new table game_grades
    if ($result && $oldversion < 2007110866) {
        /// Define table scorm_scoes_data to be created
        $table = new XMLDBTable( 'game_course_input');

        /// Adding fields to table scorm_scoes_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null, '');
        $table->addFieldInfo('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('sourcemodule', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, '');
        $table->addFieldInfo('ids', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, null, '');

		/// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table for scorm_scoes_data
        $result = $result && create_table($table);
	}

	//1.4-repair
	//game.gameinputid
    if ($result && $oldversion < 2007111302) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('gameinputid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'bookid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
		
	//game.bottomtext
    if ($result && $oldversion < 2007111303) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('bottomtext');
        $field->setAttributes(XMLDB_TYPE_TEXT, null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
	//game.grademethod
    if ($result && $oldversion < 2007111304) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('grademethod');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game.grade
    if ($result && $oldversion < 2007111305) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('grade');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'bottomtext');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game.decimalpoints
    if ($result && $oldversion < 2007111306) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('decimalpoints');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game.popup
    if ($result && $oldversion < 2007111307) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('popup');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game.review
    if ($result && $oldversion < 2007111308) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('review');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
	//game.attempts
    if ($result && $oldversion < 2007111309) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('attempts');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
    if ($result && $oldversion < 2007111310) {
		execute_sql("UPDATE {$CFG->prefix}game SET grade=0 WHERE grade IS NULL", true);
	}
	
	//ver 1.4 repair2
	//game_queries.attemptid
    if ($result && $oldversion < 2007111842) {
        $table = new XMLDBTable( 'game_queries');
		$field = new XMLDBField( 'gameinstanceid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
		
        $result = $result && rename_field( $table, $field, 'attemptid');
    }		

	//drop game_cross.tries
    if ($result && $oldversion < 2007111843) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('grade');
	//game.bottomtext
    if ($result && $oldversion < 2007111303) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('bottomtext');
        $field->setAttributes(XMLDB_TYPE_TEXT, null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

    /// Launch add field format
        $result = $result && drop_field($table, $field);
    }

	//game_queries.questiontext
    if ($result && $oldversion < 2007111844) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('questiontext');
        $field->setAttributes(XMLDB_TYPE_TEXT, null, null, null, null, null, null, '','glossaryentryid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_queries.score
    if ($result && $oldversion < 2007111845) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('score');
        $field->setAttributes(XMLDB_TYPE_FLOAT, null, null, null, null, null, null, '0','questiontext');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
	//game_queries.studentanswer
    if ($result && $oldversion < 2007111846) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('studentanswer');
        $field->setAttributes(XMLDB_TYPE_TEXT, null, null, null, null, null, null, '','glossaryentryid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }	

	//game_queries.col
    if ($result && $oldversion < 2007111847) {
        $table = new XMLDBTable( 'game_queries');
		$field = new XMLDBField( 'col');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
		
        $result = $result && add_field($table, $field);
    }		

	//game_queries.row
    if ($result && $oldversion < 2007111848) {
        $table = new XMLDBTable( 'game_queries');
		$field = new XMLDBField( 'row');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
		
        $result = $result && add_field($table, $field);
    }		
	
	//game_queries.horizontal
    if ($result && $oldversion < 2007111849) {
        $table = new XMLDBTable( 'game_queries');
		$field = new XMLDBField( 'horizontal');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '0');
		
        $result = $result && add_field($table, $field);
    }		

	//game_queries.answertext
    if ($result && $oldversion < 2007111850) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('answertext');
        $field->setAttributes(XMLDB_TYPE_TEXT, null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }	

	//game_queries.correct
    if ($result && $oldversion < 2007111851) {
        $table = new XMLDBTable( 'game_queries');
		$field = new XMLDBField( 'correct');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');
		
        $result = $result && add_field($table, $field);
    }		
	
    if ($result && $oldversion < 2007111853) {
		execute_sql("UPDATE {$CFG->prefix}game SET grademethod=1 WHERE grademethod=0 OR grademethod IS NULL", true);
	}

	//game_hangman.queryid
    if ($result && $oldversion < 2007111854) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('queryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'id');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }	

	//game_snakes.queryid
    if ($result && $oldversion < 2007111855) {
        $table = new XMLDBTable('game_snakes');
        $field = new XMLDBField('queryid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'snakesdatabaseid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }	

	//game_bookquiz_chapters.attemptid
    if ($result && $oldversion < 2007111856) {
        $table = new XMLDBTable( 'game_bookquiz_chapters');
		$field = new XMLDBField( 'attemptid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'id');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//game_hangman.letters : change to char( 100)
    if ($result && $oldversion < 2007120103) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('letters');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null, null, null);

    /// Launch change of precision for field lang
        $result = $result && change_field_precision($table, $field);
    }

	//game_hangman.allletters : change to char( 100)
    if ($result && $oldversion < 2007120104) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('allletters');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null, null, null);

    /// Launch change of precision for field lang
        $result = $result && change_field_precision($table, $field);
    }

    //1.4.c
	//game_queries.attachment
    if ($result && $oldversion < 2007120106) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('attachment');
        $field->setAttributes(XMLDB_TYPE_CHAR, '100', null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
 
    //1.6  
    
	//game.glossaryid2
    if ($result && $oldversion < 2008011301) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('glossaryid2');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
    
	//game.glossarycategoryid2
    if ($result && $oldversion < 2008011302) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('glossarycategoryid2');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }    
    
	//game_queries.attachment
    if ($result && $oldversion < 2008011308) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('attachment');
        $field->setAttributes(XMLDB_TYPE_CHAR, '200', null, null, null, null, null, '');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }    
    
	//new table game_hiddenpicture
    if ($result && $oldversion < 2008011504) {
        /// Define table game_hiddenpicture to be created
        $table = new XMLDBTable( 'game_hiddenpicture');

        /// Adding fields to table scorm_scoes_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('correct', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('wrong', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('found', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

		/// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Launch create table
        $result = $result && create_table($table);
	}    
	
	
	//game.param8
    if ($result && $oldversion < 2008012701) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('param8');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'param7');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}
	
	//game_queries.language
    if ($result && $oldversion < 2008071101) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('language');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, '');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

	//new table game_export_javame
    if ($result && $oldversion < 2008072204) {
        /// Define table game_export_javame to be created
        $table = new XMLDBTable( 'game_export_javame');

        /// Adding fields to table scorm_scoes_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('gameid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('filename', XMLDB_TYPE_CHAR, '20');
        $table->addFieldInfo('icon', XMLDB_TYPE_CHAR, '100');
        $table->addFieldInfo('createdby', XMLDB_TYPE_CHAR, '50');
        $table->addFieldInfo('vendor', XMLDB_TYPE_CHAR, '50');
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '20');
        $table->addFieldInfo('description', XMLDB_TYPE_CHAR, '100');
        $table->addFieldInfo('version', XMLDB_TYPE_CHAR, '10');

		/// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        $table->addIndexInfo('gameid', XMLDB_INDEX_UNIQUE, array('gameid'));        

        /// Launch create table
        $result = $result && create_table($table);
	} 
	
	//Delete field game_hangman.quizid
    if ($result && $oldversion < 2008072501) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('quizid');

    /// Launch drop field grade_high
        $result = $result && drop_field($table, $field);
    }

	//Delete field game_hangman.glossaryid
    if ($result && $oldversion < 2008072502) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('glossaryid');

    /// Launch drop field grade_high
        $result = $result && drop_field($table, $field);
    }
    
	//Delete field game_hangman.questioncategoryid
    if ($result && $oldversion < 2008072503) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('questioncategoryid');

    /// Launch drop field grade_high
        $result = $result && drop_field($table, $field);
    }

	//Delete field game_hangman.gameinputid
    if ($result && $oldversion < 2008072504) {
        $table = new XMLDBTable('game_hangman');
        $field = new XMLDBField('gameinputid');

    /// Launch drop field grade_high
        $result = $result && drop_field($table, $field);
    }
  
	//game.subcategories
    if ($result && $oldversion < 2008090101) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('subcategories');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}
    

	//game.state
    if ($result && $oldversion < 2008101103) {
        $table = new XMLDBTable('game_millionaire');
        $field = new XMLDBField('state');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch change_field_precision
        $result = $result && change_field_precision($table, $field);
	}
	
	//game_millionaire.level
    if ($result && $oldversion < 2008101104) {
        $table = new XMLDBTable('game_millionaire');
        $field = new XMLDBField('level');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch change_field_precision
        $result = $result && change_field_precision($table, $field);
	}

	//game_sudoku.level
    if ($result && $oldversion < 2008101106) {
        $table = new XMLDBTable('game_sudoku');
        $field = new XMLDBField('level');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch change_field_precision
        $result = $result && change_field_precision($table, $field);
	}
	
	//game_hiddenpicture.correct
    if ($result && $oldversion < 2008101107) {
        $table = new XMLDBTable('game_hiddenpicture');
        $field = new XMLDBField('correct');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch change_field_precision
        $result = $result && change_field_precision($table, $field);
	}		
	
	//game_hiddenpicture.wrong
    if ($result && $oldversion < 2008101108) {
        $table = new XMLDBTable('game_hiddenpicture');
        $field = new XMLDBField('wrong');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch change_field_precision
        $result = $result && change_field_precision($table, $field);
	}	
	
	//game_hiddenpicture.found
    if ($result && $oldversion < 2008101109) {
        $table = new XMLDBTable('game_hiddenpicture');
        $field = new XMLDBField('found');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch change_field_precision
        $result = $result && change_field_precision($table, $field);
	}	
	
	//game_queries.answerid
    if ($result && $oldversion < 2008102701) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('answerid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}
	
	//new table game_export_html
    if ($result && $oldversion < 2008110701) {
        /// Define table game_export_html to be created
        $table = new XMLDBTable( 'game_export_html');

        /// Adding fields to table scorm_scoes_data
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('gameid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('filename', XMLDB_TYPE_CHAR, '30');
        $table->addFieldInfo('title', XMLDB_TYPE_CHAR, '200');
        $table->addFieldInfo('checkbutton', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addFieldInfo('printbutton', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL);

		/// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        $table->addIndexInfo('gameid', XMLDB_INDEX_UNIQUE, array('gameid'));        

        /// Launch create table
        $result = $result && create_table($table);
	} 

	//rename field game_snakes_database.file to fileboard
    if ($result && $oldversion < 2008111701) {
        $table = new XMLDBTable( 'game_snakes_database');
		$field = new XMLDBField( 'file');
        $field->setAttributes(XMLDB_TYPE_CHAR, 100, null, null, null, null, null, '');
		
        $result = $result && rename_field( $table, $field, 'fileboard');
    }

	//game_exp	//game.bottomtext
    if ($result && $oldversion < 2007111303) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('bottomtext');
        $field->setAttributes(XMLDB_TYPE_TEXT, null, null, null, null, null, null);

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009010502) {
        $table = new XMLDBTable('game_export_javame');
        $field = new XMLDBField('maxpicturewidth');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '7');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}
	
	//new table game_repetitions
    if ($result && $oldversion < 2009031801) {
        /// Define table game_repetitions to be created
        $table = new XMLDBTable( 'game_repetitions');

        /// Adding fields to table game_repetitions
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('gameid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('questionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('glossaryentryid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('repetitions', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

		/// Adding keys to table scorm_scoes_data
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        $table->addIndexInfo('main', XMLDB_INDEX_UNIQUE, array('gameid,userid,questionid,glossaryentryid'));        

        /// Launch create table
        $result = $result && create_table($table);
	}

	//game.shuffle
    if ($result && $oldversion < 2009071403) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('shuffle');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '1', 'param8');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}

    if ($result && $oldversion < 2009072801) {
        $table = new XMLDBTable('game_export_html');
        $field = new XMLDBField('inputsize');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED);

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}
    
    //game_export_html.maxpicturewidth
    if ($result && $oldversion < 2009072901) {
        $table = new XMLDBTable('game_export_html');
        $field = new XMLDBField('maxpicturewidth');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '7');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}

    //game_export_html.maxpictureheight
    if ($result && $oldversion < 2009073101) {
        $table = new XMLDBTable('game_export_html');
        $field = new XMLDBField('maxpictureheight');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '7');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}

	//game_export_javame.maxpictureheight
    if ($result && $oldversion < 2009073102) {
        $table = new XMLDBTable('game_export_javame');
        $field = new XMLDBField('maxpictureheight');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '7');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}

	//game.toptext
    if ($result && $oldversion < 2009083102) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('toptext');
        $field->setAttributes(XMLDB_TYPE_TEXT, null, null, null, null, null, null, null, 'gameinputid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }
	
	//game.toptext
    if ($result && $oldversion < 2010031101) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('tries');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, null, null, '0', 'answerid');

    /// Launch add field format
        $result = $result && add_field($table, $field);
    }


    if ($result && $oldversion < 2010071606) {
        $table = new XMLDBTable('game_export_html');
        $field = new XMLDBField('id');
        $result = $result && drop_field($table, $field, false);
    }
	
	//rename field game_export_html.gameid to id
    if ($result && $oldversion < 2010071607) {
        $table = new XMLDBTable( 'game_export_html');
		$field = new XMLDBField( 'gameid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, 10, null, null, null, null, null, '0');
		
        $result = $result && rename_field( $table, $field, 'id', false);
    }

    if ($result && $oldversion < 2010071609) {
        $table = new XMLDBTable('game_export_javame');
        $field = new XMLDBField('id');
        $result = $result && drop_field($table, $field, false);
    }
	
	//rename field game_export_html.gameid to id
    if ($result && $oldversion < 2010071610) {
        $table = new XMLDBTable( 'game_export_javame');
		$field = new XMLDBField( 'gameid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, 10, null, null, null, null, null, '0');
		
        $result = $result && rename_field( $table, $field, 'id', false);
    }

	//game_export_html.type
    if ($result && $oldversion < 2010071611) {
        $table = new XMLDBTable('game_export_javame');
        $field = new XMLDBField('type');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}

  if ($result && $oldversion < 2010072201) {
        $table = new XMLDBTable('game');
        $field = new XMLDBField('popup');
        $result = $result && drop_field($table, $field, false);
    }

    if ($result && $oldversion < 2010072605) {

    /// Define field language to be added to game_attempts
        $table = new XMLDBTable('game_attempts');
        $field = new XMLDBField('language');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'attempts');

    /// Launch add field language
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010081001) {
        $table = new XMLDBTable('game_queries');
        $field = new XMLDBField('gamekind');
        $result = $result && drop_field($table, $field, false);
    }

    if ($result && $oldversion < 2011071903) {

    /// Define field param9 to be added to game
        $table = new XMLDBTable('game');
        $field = new XMLDBField('param9');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'param8');

    /// Launch add field param9
        $result = $result && add_field($table, $field);
    }

	//game_export_html.type
    if ($result && $oldversion < 2011072401) {
        $table = new XMLDBTable('game_export_html');
        $field = new XMLDBField('type');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10');

    /// Launch add field format
        $result = $result && add_field($table, $field);
	}
	
	//game.param10
    if ($result && $oldversion < 2011072902) {
    /// Define field param10 to be added to game
        $table = new XMLDBTable('game');
        $field = new XMLDBField('param10');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'param9');

    /// Launch add field param10
        $result = $result && add_field($table, $field);
	}
	
    if ($result && $oldversion < 2011082603) {

    /// Define field timeopen to be added to game
        $table = new XMLDBTable('game');
        $field = new XMLDBField('timeopen');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'sourcemodule');

    /// Launch add field timeopen
        $result = $result && add_field($table, $field);
    }
	
    if ($result && $oldversion < 2011082604) {

    /// Define field timeopen to be added to game
        $table = new XMLDBTable('game');
        $field = new XMLDBField('timeclose');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'timeopen');

    /// Launch add field timeopen
        $result = $result && add_field($table, $field);
    }
	
    
    return $result;
}


?>
