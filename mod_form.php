<?php  // $Id: mod_form.php,v 1.7.2.15 2012/01/16 21:45:04 bdaloukas Exp $
/**
 * Form for creating and modifying a game 
 *
 * @package   game
 * @author    Alastair Munro <alastair@catalyst.net.nz>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require( 'locallib.php');

class mod_game_mod_form extends moodleform_mod {
    
    function set_data($default_values) {
        if( isset( $default_values->type))
        {
            //Default values for every game.
            if( $default_values->type == 'hangman')
            {
                $default_values->param10 = 6;    //maximum number of wrongs
            }else if( $default_values->type == 'snakes')
            {
                $default_values->gamekind = $default_values->type;
                $default_values->param3 = 1;
                $default_values->questioncategoryid = 0;
            }else if( $default_values->type == 'millionaire')
            {
                $default_values->shuffle = 1;
            } 
        }

        if( isset( $default_values->gamekind)){
            if( $default_values->gamekind == 'millionaire'){        
                $default_values->param8 = '#'.strtoupper( dechex( $default_values->param8));
            }
            //append contextid to questioncategoryid
            $categoryid = $default_values->questioncategoryid;
            $contextid = get_field( 'question_categories', 'contextid', 'id', $categoryid);
            $default_values->questioncategoryid = $categoryid.','.$contextid;
            
            if( $default_values->gamekind == 'snakes'){
                if( isset( $default_values->param9)){
                    $a = explode( '#',$default_values->param9);
                    foreach( $a as $s){
                        $pos = strpos( $s, ':');
                        if( $pos){
                            $name = substr( $s, 0, $pos);
                            $default_values->$name = substr( $s, $pos+1);
                        }
                    }                                       
                }
                if( isset( $default_values->param3)){
                    $board = $default_values->param3;
                    if( $board != 0){
                        $rec = get_record_select( 'game_snakes_database', 'id='.$board);
                        $default_values->snakes_board = $rec->data;
                        $default_values->snakes_cols = $rec->cols;
                        $default_values->snakes_rows = $rec->rows;
                        $default_values->snakes_headerx = $rec->headerx;
                        $default_values->snakes_headery = $rec->headery;
                        $default_values->snakes_footerx = $rec->footerx;
                        $default_values->snakes_footery = $rec->footery;
                    }
                }
            }
        }
        parent::set_data($default_values);
    }

    function definition() {
        global $CFG, $COURSE;

        $mform =& $this->_form;

        if(!empty($this->_instance)){
            if($g = get_record('game', 'id', (int)$this->_instance)){
                $gamekind = $g->gamekind;
            }
            else{
                error('incorrect game');
            }
        } 
        else {     
            $gamekind = required_param('type', PARAM_ALPHA);
        }

        //Hidden elements
        $mform->addElement('hidden', 'gamekind', $gamekind);
        $mform->setDefault('gamekind',$gamekind);
        $mform->addElement('hidden', 'type', $gamekind);
        $mform->setDefault('type',$gamekind);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', 'Name', array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)){
            $mform->setType('name', PARAM_TEXT);
        }
        else{
            $mform->setType('name', PARAM_CLEAN);
        }
        if( !isset( $g))
            $mform->setDefault('name', get_string( 'game_'.$gamekind, 'game'));
        $mform->addRule('name', null, 'required', null, 'client');

        $hasglossary = ($gamekind == 'hangman' || $gamekind == 'cross' || $gamekind == 'cryptex' || $gamekind == 'sudoku' || $gamekind == 'hiddenpicture' || $gamekind == 'snakes' || $gamekind == 'contest');

        $questionsourceoptions = array();
        if($hasglossary)
            $questionsourceoptions['glossary'] = get_string('modulename', 'glossary');
        $questionsourceoptions['question'] = get_string('sourcemodule_question', 'game');
        if( $gamekind != 'bookquiz')
            $questionsourceoptions['quiz'] = get_string('modulename', 'quiz');
        $mform->addElement('select', 'sourcemodule', get_string('sourcemodule','game'), $questionsourceoptions);

        if($hasglossary){
            $a = array();
            if($recs = get_records_select('glossary', "course='$COURSE->id'", 'id,name')){
                foreach($recs as $rec){
                    $a[$rec->id] = $rec->name;
                }                                            
            }
            $mform->addElement('select', 'glossaryid', get_string('sourcemodule_glossary', 'game'), $a);
            $mform->disabledIf('glossaryid', 'sourcemodule', 'neq', 'glossary');

            if( count( $a) == 0)
                $select = 'glossaryid=-1';
            else if( count( $a) == 1)
                $select = 'glossaryid='.$rec->id;
            else
            {
                $select = '';
                foreach($recs as $rec){
                    $select .= ','.$rec->id;
                }
                $select = 'g.id IN ('.substr( $select, 1).')';
            }
            $select .= ' AND g.id=gc.glossaryid';
            $table = "glossary g, {$CFG->prefix}glossary_categories gc";
            $a = array();
            $a[ ] = '';
            if($recs = get_records_select( $table, $select, 'g.name,gc.name', 'gc.id,gc.name,g.name as name2')){
                foreach($recs as $rec){
                    $a[$rec->id] = $rec->name2.' -> '.$rec->name;
                }
            }
            $mform->addElement('select', 'glossarycategoryid', get_string('sourcemodule_glossarycategory', 'game'), $a);
            $mform->disabledIf('glossarycategoryid', 'sourcemodule', 'neq', 'glossary');
        }

        
        //*********************
        // Question Category
        if( $gamekind != 'bookquiz'){
            $contexts = game_get_contexts();
            $mform->addElement('questioncategory', 'questioncategoryid', get_string('sourcemodule_questioncategory', 'game'), compact('contexts'));
            $mform->disabledIf('questioncategoryid', 'sourcemodule', 'neq', 'question');

            //subcategories
            $mform->addElement('selectyesno', 'subcategories', get_string('sourcemodule_include_subcategories', 'game'));
            $mform->disabledIf('subcategories', 'sourcemodule', 'neq', 'question');
        }

        //***********************
        // Quiz        
        if( $gamekind != 'bookquiz'){
            $a = array();
            if( $recs = get_records_select( 'quiz', "course='$COURSE->id'", 'id,name')){
                foreach( $recs as $rec){
                    $a[$rec->id] = $rec->name;
                }
            }
            $mform->addElement('select', 'quizid', get_string('sourcemodule_quiz', 'game'), $a);
            $mform->disabledIf('quizid', 'sourcemodule', 'neq', 'quiz');
        }


        //***********************
        // Book
        if($gamekind == 'bookquiz'){
            $a = array();
            if($recs = get_records_select('book', "course='$COURSE->id'", 'id,name')){
                foreach($recs as $rec){
                    $a[$rec->id] = $rec->name;
                }                                            
            }
            $mform->addElement('select', 'bookid', get_string('sourcemodule_book', 'game'), $a);
        }

//---------------------------------------------------------------------------
// Grade options 

        $mform->addElement('header', 'gradeoptions', get_string('grades', 'grades'));
        $mform->addElement('text', 'grade', get_string( 'grademax', 'grades'), array('size' => 4));
        $mform->setType('grade', PARAM_INT);
        $gradingtypeoptions = array();
        $gradingtypeoptions[0] = get_string('gradehighest','game');
        $gradingtypeoptions[1] = get_string('gradeaverage','game');
        $gradingtypeoptions[2] = get_string('attemptfirst','game');
        $gradingtypeoptions[3] = get_string('attemptlast','game');
        $mform->addElement('select', 'grademethod', get_string('grademethod','game'), $gradingtypeoptions);
        
        $mform->addElement('header', 'timinghdr', get_string('timing', 'form'));
        $mform->addElement('date_time_selector', 'timeopen', get_string('gameopen', 'game'), array('optional'=>true));
        $mform->setHelpButton('timeopen', array('timeopen', get_string('gameopen', 'game'), 'game'));

        $mform->addElement('date_time_selector', 'timeclose', get_string('gameclose', 'game'), array('optional'=>true));
        $mform->setHelpButton('timeclose', array('timeopen', get_string('gameclose', 'game'), 'game'));
                
//---------------------------------------------------------------------------
// Hangman options

        if($gamekind == 'hangman'){
            $mform->addElement('header', 'hangman', get_string( 'hangman_options', 'game'));
            $mform->addElement('text', 'param4', get_string('hangman_maxtries', 'game'), array('size' => 4));
            $mform->setType('param4', PARAM_INT);
            $mform->addElement('selectyesno', 'param1', get_string('hangman_showfirst', 'game'));
            $mform->addElement('selectyesno', 'param2', get_string('hangman_showlast', 'game'));
            $mform->addElement('selectyesno', 'param7', get_string('hangman_allowspaces','game'));
            $mform->addElement('selectyesno', 'param8', get_string('hangman_allowsub', 'game'));

            $mform->addElement('text', 'param10', get_string( 'hangman_maximum_number_of_errors', 'game'), array('size' => 4));
            $mform->setType('param10', PARAM_INT);

            $a = array( 1 => 1);
            $mform->addElement('select', 'param3', get_string('hangman_imageset','game'), $a);

            $mform->addElement('selectyesno', 'param5', get_string('hangman_showquestion', 'game'));
            $mform->addElement('selectyesno', 'param6', get_string('hangman_showcorrectanswer', 'game'));

            $a = array();
            $a = get_list_of_languages();
		    $a[ ''] = '----------';
            ksort( $a);
            $mform->addElement('select', 'language', get_string('hangman_language','game'), $a);
        }

        if($gamekind == 'contest'){
            $mform->addElement('header', 'contest', get_string( 'contest_options', 'game'));
            $mform->addElement('selectyesno', 'param1', get_string('contest_has_notes', 'game'));
            
            $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
            $choices[1] = get_string('uploadnotallowed');
            $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
            $mform->addElement('select', 'param2', get_string('maxattachmentsize', 'forum'), $choices);
        }

//---------------------------------------------------------------------------
// Crossword options

        if($gamekind == 'cross'){ 
            $mform->addElement('header', 'cross', get_string( 'cross_options', 'game'));
            $mform->addElement('text', 'param1', get_string('cross_maxcols', 'game'));
            $mform->setType('param1', PARAM_INT);
            $mform->addElement('text', 'param2', get_string('cross_maxwords', 'game'));
            $mform->setType('param2', PARAM_INT);
            $mform->addElement('selectyesno', 'param7', get_string('hangman_allowspaces','game'));
            $crosslayoutoptions = array();
            $crosslayoutoptions[0] = get_string('cross_layout0', 'game');
            $crosslayoutoptions[1] = get_string('cross_layout1', 'game');
            $mform->addElement('select','param3', get_string('cross_layout', 'game'), $crosslayoutoptions);
        }

//---------------------------------------------------------------------------
// Cryptex options

        if($gamekind == 'cryptex'){
            $mform->addElement('header', 'cryptex', get_string( 'cryptex_options', 'game'));
            $mform->addElement('text', 'param1', get_string('cryptex_maxcols', 'game'));
            $mform->setType('param1', PARAM_INT);
            $mform->addElement('text', 'param2', get_string('cryptex_maxwords', 'game'));
            $mform->setType('param2', PARAM_INT);
            $mform->addElement('selectyesno', 'param7', get_string('hangman_allowspaces','game'));
            $mform->addElement('text', 'param8', get_string('cryptex_maxtries','game'));
            $mform->setType('param8', PARAM_INT);
        }
        
//---------------------------------------------------------------------------
// Millionaire options

        if($gamekind == 'millionaire'){
            $mform->addElement('header', 'millionaire', get_string( 'millionaire_options', 'game'));

            $mform->addElement('text', 'param8', get_string('millionaire_background', 'game'));
            $mform->setDefault('param8', '#408080');

            $mform->addElement('selectyesno', 'shuffle', get_string('millionaire_shuffle','game'));
        }

//---------------------------------------------------------------------------
// Sudoku options

        if($gamekind == 'sudoku'){
            $mform->addElement('header', 'sudoku', get_string( 'sudoku_options', 'game'));
            $mform->addElement('text', 'param2', get_string('sudoku_maxquestions', 'game'));
        }

//---------------------------------------------------------------------------
// Snakes and Ladders options

        if($gamekind == 'snakes'){
            $mform->addElement('header', 'snakes', get_string( 'snakes_options', 'game'));
            $snakesandladdersbackground = array();
            if($recs = get_records_select( 'game_snakes_database', "", 'id,name')){
                foreach( $recs as $rec){
                    $snakesandladdersbackground[$rec->id] = $rec->name;
                }
            }
            if(count($snakesandladdersbackground) == 0){
                require("{$CFG->dirroot}/mod/game/db/importsnakes.php");

                if($recs = get_records_select('game_snakes_database', "", 'id,name')){
                    foreach($recs as $rec){
                        $snakesandladdersbackground[$rec->id] = $rec->name;
                    }
                }
            }
            
            $snakesandladdersbackground[ 0] = get_string( 'userdefined', 'game');
            ksort( $snakesandladdersbackground);
            $mform->addElement('select', 'param3', get_string('snakes_background', 'game'), $snakesandladdersbackground);

            //param4 = itemid for file_storage
            //param5 (=1 means dirty file and and have to be computed again)

            $attachmentoptions = array('subdirs'=>false, 'maxfiles'=>1);
            $mform->addElement('file', 'param4', get_string('snakes_file', 'game'), $attachmentoptions);
            $mform->disabledIf('param4', 'param3', 'neq', '0');

            $mform->addElement('textarea', 'snakes_board', get_string('snakes_board', 'game'), 'rows="2" cols="70"');
            $mform->disabledIf('snakes_board', 'param3', 'neq', '0');

            $mform->addElement('text', 'snakes_cols', get_string('snakes_cols', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_cols', 'param3', 'neq', '0');

            $mform->addElement('text', 'snakes_rows', get_string('snakes_rows', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_rows', 'param3', 'neq', '0');

            $mform->addElement('text', 'snakes_headerx', get_string('snakes_headerx', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_headerx', 'param3', 'neq', '0');

            $mform->addElement('text', 'snakes_headery', get_string('snakes_headery', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_headery', 'param3', 'neq', '0');

            $mform->addElement('text', 'snakes_footerx', get_string('snakes_footerx', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_footerx', 'param3', 'neq', '0');

            $mform->addElement('text', 'snakes_footery', get_string('snakes_footery', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_footery', 'param3', 'neq', '0');            
        }

//---------------------------------------------------------------------------
// Hidden Picture options

        if($gamekind == 'hiddenpicture'){
            $mform->addElement('header', 'hiddenpicture', get_string( 'hiddenpicture_options', 'game'));
            $mform->addElement('text', 'param1', get_string('hiddenpicture_across', 'game'));
            $mform->setType('param1', PARAM_INT);
            $mform->setDefault('param1',3);
            $mform->addElement('text', 'param2', get_string('hiddenpicture_down', 'game'));
            $mform->setType('param2', PARAM_INT);
            $mform->setDefault('param2',3);

            $a = array();
            if($recs = get_records_select('glossary', "course='$COURSE->id'", 'id,name')){
                foreach($recs as $rec){
                    $a[$rec->id] = $rec->name;
                }                                            
            }
            $mform->addElement('select', 'glossaryid2', get_string('hiddenpicture_pictureglossary', 'game'), $a);

            if( count( $a) == 0)
                $select = 'glossaryid=-1';
            else if( count( $a) == 1)
                $select = 'glossaryid='.$rec->id;
            else
            {
                $select = '';
                foreach($recs as $rec){
                    $select .= ','.$rec->id;
                }
                $select = 'g.id IN ('.substr( $select, 1).')';
            }
            $select .= ' AND g.id=gc.glossaryid';
            $table = "glossary g, {$CFG->prefix}glossary_categories gc";
            $a = array();
            $a[ ] = '';
            if($recs = get_records_select( $table, $select, 'g.name,gc.name', 'gc.id,gc.name,g.name as name2')){
                foreach($recs as $rec){
                    $a[$rec->id] = $rec->name2.' -> '.$rec->name;
                }
            }
            $mform->addElement('select', 'glossarycategoryid2', get_string('hiddenpicture_pictureglossarycategories', 'game'), $a);
            $mform->disabledIf('glossarycategoryid2', 'glossaryid', 'eq', 0);

            $mform->addElement('text', 'param4', get_string('hiddenpicture_width', 'game'));
            $mform->setType('param4', PARAM_INT);
            $mform->addELement('text', 'param5', get_string('hiddenpicture_height', 'game'));
            $mform->setType('param5', PARAM_INT);
            $mform->addElement('selectyesno', 'param7', get_string('hangman_allowspaces','game'));
        }

//---------------------------------------------------------------------------
// Header/Footer options

        $mform->addElement('header', 'headerfooteroptions', get_string( 'headerfooter_options', 'game'));
        $mform->addElement('htmleditor', 'toptext', get_string('toptext','game'));
        $mform->addElement('htmleditor', 'bottomtext', get_string('bottomtext','game'));

//---------------------------------------------------------------------------
        $this->standard_coursemodule_elements(array('groups'=>false, 'groupmembersonly'=>true));

//---------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values){

    }

    function validation($data, $files){
        $errors = parent::validation($data, $files);
        
        // Check open and close times are consistent.
        if ($data['timeopen'] != 0 && $data['timeclose'] != 0 && $data['timeclose'] < $data['timeopen']) {
            $errors['timeclose'] = get_string('closebeforeopen', 'quiz');
        }
        
        return $errors;
    }
}
 
