<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for creating and modifying a game 
 *
 * @package   game
 * @author    Alastair Munro <alastair@catalyst.net.nz>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once( $CFG->dirroot.'/course/moodleform_mod.php');
require( 'locallib.php');

class mod_game_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB, $COURSE;

        $config = get_config('game');

        $mform =& $this->_form;
        $id = $this->_instance;

        if (!empty($this->_instance)) {
            if ($g = $DB->get_record('game', array('id' => $id))) {
                $gamekind = $g->gamekind;
            } else {
                print_error('incorrect game');
            }
        } else {
            $gamekind = required_param('type', PARAM_ALPHA);
        }

        // Hidden elements.
        $mform->addElement('hidden', 'gamekind', $gamekind);
        $mform->setDefault('gamekind', $gamekind);
        $mform->setType('gamekind', PARAM_ALPHA);
        $mform->addElement('hidden', 'type', $gamekind);
        $mform->setDefault('type', $gamekind);
        $mform->setType('type', PARAM_ALPHA);

        $mform->addElement( 'hidden', 'gameversion', game_get_version());
        $mform->setType('gameversion', PARAM_INT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', 'Name', array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        if (!isset( $g)) {
            $mform->setDefault('name', get_string( 'game_'.$gamekind, 'game'));
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $hasglossary = ($gamekind == 'hangman' || $gamekind == 'cross' ||
                $gamekind == 'cryptex' || $gamekind == 'sudoku' ||
                $gamekind == 'hiddenpicture' || $gamekind == 'snakes');

        $questionsourceoptions = array();
        if ($hasglossary) {
            $questionsourceoptions['glossary'] = get_string('modulename', 'glossary');
        }
        $questionsourceoptions['question'] = get_string('sourcemodule_question', 'game');
        if ($gamekind != 'bookquiz') {
            $questionsourceoptions['quiz'] = get_string('modulename', 'quiz');
        }
        $mform->addElement('select', 'sourcemodule', get_string('sourcemodule', 'game'), $questionsourceoptions);

        if ($hasglossary) {
            $a = array();
            if ($recs = $DB->get_records('glossary', array( 'course' => $COURSE->id), 'id,name')) {
                foreach ($recs as $rec) {
                    $a[$rec->id] = $rec->name;
                }
            }
            $mform->addElement('select', 'glossaryid', get_string('sourcemodule_glossary', 'game'), $a);
            $mform->disabledIf('glossaryid', 'sourcemodule', 'neq', 'glossary');

            if (count( $a) == 0) {
                $select = 'glossaryid=-1';
            } else if (count( $a) == 1) {
                $select = 'glossaryid='.$rec->id;
            } else {
                $select = '';
                foreach ($recs as $rec) {
                    $select .= ','.$rec->id;
                }
                $select = 'g.id IN ('.substr( $select, 1).')';
            }
            $select .= ' AND g.id=gc.glossaryid';
            $table = "{glossary} g, {glossary_categories} gc";
            $a = array();
            $a[ ] = '';
            $sql = "SELECT gc.id,gc.name,g.name as name2 FROM $table WHERE $select ORDER BY g.name,gc.name";
            if ($recs = $DB->get_records_sql( $sql)) {
                foreach ($recs as $rec) {
                    $a[$rec->id] = $rec->name2.' -> '.$rec->name;
                }
            }
            $mform->addElement('select', 'glossarycategoryid', get_string('sourcemodule_glossarycategory', 'game'), $a);
            $mform->disabledIf('glossarycategoryid', 'sourcemodule', 'neq', 'glossary');
        }

        // Question Category - Short Answer.
        if ($gamekind != 'bookquiz') {
            $context = game_get_context_course_instance( $COURSE->id);
            $select = " contextid in ($context->id)";

            $a = array();
            if ($recs = $DB->get_records_select('question_categories', $select, null, 'id,name')) {
                foreach ($recs as $rec) {
                    $s = $rec->name;
                    if (($count = $DB->count_records('question', array( 'category' => $rec->id))) != 0) {
                        $s .= " ($count)";
                    }
                    $a[$rec->id] = $s;
                }
            }

            $mform->addElement('select', 'questioncategoryid', get_string('sourcemodule_questioncategory', 'game'), $a);
            $mform->disabledIf('questioncategoryid', 'sourcemodule', 'neq', 'question');

            // Subcategories.
            $mform->addElement('selectyesno', 'subcategories', get_string('sourcemodule_include_subcategories', 'game'));
            $mform->disabledIf('subcategories', 'sourcemodule', 'neq', 'question');
        }

        // Quiz Category.
        if ($gamekind != 'bookquiz') {
            $a = array();
            if ($recs = $DB->get_records('quiz', array( 'course' => $COURSE->id), 'id,name')) {
                foreach ($recs as $rec) {
                    $a[$rec->id] = $rec->name;
                }
            }
            $mform->addElement('select', 'quizid', get_string('sourcemodule_quiz', 'game'), $a);
            $mform->disabledIf('quizid', 'sourcemodule', 'neq', 'quiz');
        }

        // Book.
        if ( $gamekind == 'bookquiz') {
            $a = array();
            if ($recs = $DB->get_records('book', array( 'course' => $COURSE->id), 'id,name')) {
                foreach ($recs as $rec) {
                    $a[$rec->id] = $rec->name;
                }
            }
            $mform->addElement('select', 'bookid', get_string('sourcemodule_book', 'game'), $a);
        }

        // Common settings to all games.
        $mform->addElement('text', 'maxattempts', get_string('cross_max_attempts', 'game'));
        $mform->setType('maxattempts', PARAM_INT);

        // Disable summarize.
        $mform->addElement('selectyesno', 'disablesummarize', get_string('disablesummarize', 'game'));

        // Grade options.
        $mform->addElement('header', 'gradeoptions', get_string('grades', 'grades'));
        $mform->addElement('text', 'grade', get_string( 'grademax', 'grades'), array('size' => 4));
        $mform->setType('grade', PARAM_INT);
        $gradingtypeoptions = array();
        $gradingtypeoptions[ GAME_GRADEHIGHEST] = get_string('gradehighest', 'game');
        $gradingtypeoptions[ GAME_GRADEAVERAGE] = get_string('gradeaverage', 'game');
        $gradingtypeoptions[ GAME_ATTEMPTFIRST] = get_string('attemptfirst', 'game');
        $gradingtypeoptions[ GAME_ATTEMPTLAST] = get_string('attemptlast', 'game');
        $mform->addElement('select', 'grademethod', get_string('grademethod', 'game'), $gradingtypeoptions);

        // Open and close dates.
        $mform->addElement('date_time_selector', 'timeopen', get_string('gameopen', 'game'),
                array('optional' => true, 'step' => 1));
        $mform->addHelpButton('timeopen', 'gameopenclose', 'game');

        $mform->addElement('date_time_selector', 'timeclose', get_string('gameclose', 'game'),
                array('optional' => true, 'step' => 1));

        // Bookquiz options.
        if ($gamekind == 'bookquiz') {
            $mform->addElement('header', 'bookquiz', get_string( 'bookquiz_options', 'game'));
            $bookquizlayoutoptions = array();
            $bookquizlayoutoptions[0] = get_string('bookquiz_layout0', 'game');
            $bookquizlayoutoptions[1] = get_string('bookquiz_layout1', 'game');
            $mform->addElement('select', 'param3',
                get_string('bookquiz_layout', 'game'), $bookquizlayoutoptions);
        }

        // Hangman options.
        if ($gamekind == 'hangman') {
            $mform->addElement('header', 'hangman', get_string( 'hangman_options', 'game'));
            $mform->addElement('text', 'param4', get_string('hangman_maxtries', 'game'), array('size' => 4));
            $mform->setType('param4', PARAM_INT);
            $mform->addElement('selectyesno', 'param1', get_string('hangman_showfirst', 'game'));
            $mform->addElement('selectyesno', 'param2', get_string('hangman_showlast', 'game'));
            $mform->addElement('selectyesno', 'param7', get_string('hangman_allowspaces', 'game'));
            $mform->addElement('selectyesno', 'param8', get_string('hangman_allowsub', 'game'));

            $mform->addElement('text', 'param10', get_string( 'hangman_maximum_number_of_errors', 'game'), array('size' => 4));
            $mform->setType('param10', PARAM_INT);

            if (!isset( $config->hangmanimagesets)) {
                $number = 1;
            } else {
                $number = $config->hangmanimagesets;
            }
            if ($number > 1) {
                $a = array();
                for ($i = 1; $i <= $number; $i++) {
                    $a[ $i] = $i;
                }
                $mform->addElement('select', 'param3', get_string('hangman_imageset', 'game'), $a);
            }

            $mform->addElement('selectyesno', 'param5', get_string('hangman_showquestion', 'game'));
            $mform->setDefault('param5', 1);
            $mform->addElement('selectyesno', 'param6', get_string('hangman_showcorrectanswer', 'game'));

            $a = array();
            $a = get_string_manager()->get_list_of_translations();
            $a[ ''] = '----------';
            $a[ 'user'] = get_string('language_user_defined', 'game');
            ksort( $a);
            $mform->addElement('select', 'language', get_string('hangman_language', 'game'), $a);

            $mform->addElement('text', 'userlanguage', get_string('language_user_defined', 'game'));
            $mform->setType('userlanguage', PARAM_TEXT);
            $mform->disabledIf('userlanguage', 'language', 'neq', 'user');
        }

        // Crossword options.
        if ($gamekind == 'cross') {
            $mform->addElement('header', 'cross', get_string( 'cross_options', 'game'));
            $mform->addElement('text', 'param1', get_string('cross_maxcols', 'game'));
            $mform->setType('param1', PARAM_INT);
            $mform->addElement('text', 'param4', get_string('cross_minwords', 'game'));
            $mform->setType('param4', PARAM_INT);
            $mform->addElement('text', 'param2', get_string('cross_maxwords', 'game'));
            $mform->setType('param2', PARAM_INT);
            $mform->addElement('selectyesno', 'param7', get_string('hangman_allowspaces', 'game'));
            $crosslayoutoptions = array();
            $crosslayoutoptions[0] = get_string('cross_layout0', 'game');
            $crosslayoutoptions[1] = get_string('cross_layout1', 'game');
            $mform->addElement('select', 'param3', get_string('cross_layout', 'game'), $crosslayoutoptions);
            $mform->setType('param5', PARAM_INT);
            $mform->addElement('selectyesno', 'param6', get_string('cross_disabletransformuppercase', 'game'));
        }

        // Cryptex options.
        if ($gamekind == 'cryptex') {
            $mform->addElement('header', 'cryptex', get_string( 'cryptex_options', 'game'));
            $mform->addElement('text', 'param1', get_string('cross_maxcols', 'game'));
            $mform->setType('param1', PARAM_INT);
            $mform->addElement('text', 'param4', get_string('cross_minwords', 'game'));
            $mform->setType('param4', PARAM_INT);
            $mform->addElement('text', 'param2', get_string('cross_maxwords', 'game'));
            $mform->setType('param2', PARAM_INT);
            $mform->addElement('selectyesno', 'param7', get_string('hangman_allowspaces', 'game'));
            $mform->addElement('text', 'param8', get_string('cryptex_maxtries', 'game'));
            $mform->setType('param8', PARAM_INT);
        }

        // Millionaire options.
        if ($gamekind == 'millionaire') {
            global $OUTPUT, $PAGE;

            $mform->addElement('header', 'millionaire', get_string( 'millionaire_options', 'game'));
            $mform->addElement('text', 'param8', get_string('millionaire_background', 'game'));
            $mform->setDefault('param8', '#408080');
            $mform->setType('param8', PARAM_TEXT);

            $mform->addElement('selectyesno', 'shuffle', get_string('millionaire_shuffle', 'game'));
        }

        // Sudoku options.
        if ($gamekind == 'sudoku') {
            $mform->addElement('header', 'sudoku', get_string( 'sudoku_options', 'game'));
            $mform->addElement('text', 'param2', get_string('sudoku_maxquestions', 'game'));
            $mform->setType('param2', PARAM_INT);
        }

        // Snakes and Ladders options.
        if ($gamekind == 'snakes') {
            $mform->addElement('header', 'snakes', get_string( 'snakes_options', 'game'));
            $snakesandladdersbackground = array();
            if ($recs = $DB->get_records( 'game_snakes_database', null, 'id,name')) {
                foreach ($recs as $rec) {
                    $snakesandladdersbackground[$rec->id] = $rec->name;
                }
            }

            $snakeslayoutoptions = array();
            $snakeslayoutoptions[0] = get_string('snakes_layout0', 'game');
            $snakeslayoutoptions[1] = get_string('snakes_layout1', 'game');
            $mform->addElement('select', 'param8', get_string('bookquiz_layout', 'game'), $snakeslayoutoptions);

            if (count($snakesandladdersbackground) == 0) {
                require("{$CFG->dirroot}/mod/game/db/importsnakes.php");

                if ($recs = $DB->get_records('game_snakes_database', null, 'id,name')) {
                    foreach ($recs as $rec) {
                        $snakesandladdersbackground[$rec->id] = $rec->name;
                    }
                }
            }
            $snakesandladdersbackground[ 0] = get_string( 'userdefined', 'game');
            ksort( $snakesandladdersbackground);
            $mform->addElement('select', 'param3', get_string('snakes_background', 'game'), $snakesandladdersbackground);

            // Param3 = background.
            // Param4 = itemid for file_storage.
            // Param5 (=1 means dirty file and and have to be computed again).
            // Param6 = width of autogenerated picture.
            // Param7 = height of autogenerated picture.
            // Param8 = layout.

            $attachmentoptions = array('subdirs' => false, 'maxfiles' => 1);
            $mform->addElement('filepicker', 'param4', get_string('snakes_file', 'game'), $attachmentoptions);
            $mform->disabledIf('param4', 'param3', 'neq', '0');

            $mform->addElement('textarea', 'snakes_data', get_string('snakes_data', 'game'), 'rows="2" cols="70"');
            $mform->disabledIf('snakes_data', 'param3', 'neq', '0');

            $mform->addElement('text', 'snakes_cols', get_string('snakes_cols', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_cols', 'param3', 'neq', '0');
            $mform->setType('snakes_cols', PARAM_INT);

            $mform->addElement('text', 'snakes_rows', get_string('snakes_rows', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_rows', 'param3', 'neq', '0');
            $mform->setType('snakes_rows', PARAM_INT);

            $mform->addElement('text', 'snakes_headerx', get_string('snakes_headerx', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_headerx', 'param3', 'neq', '0');
            $mform->setType('snakes_headerx', PARAM_INT);

            $mform->addElement('text', 'snakes_headery', get_string('snakes_headery', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_headery', 'param3', 'neq', '0');
            $mform->setType('snakes_headery', PARAM_INT);

            $mform->addElement('text', 'snakes_footerx', get_string('snakes_footerx', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_footerx', 'param3', 'neq', '0');
            $mform->setType('snakes_footerx', PARAM_INT);

            $mform->addElement('text', 'snakes_footery', get_string('snakes_footery', 'game'), array('size' => 4));
            $mform->disabledIf('snakes_footery', 'param3', 'neq', '0');
            $mform->setType('snakes_footery', PARAM_INT);

            $mform->addElement('text', 'snakes_width', get_string('hiddenpicture_width', 'game'), array('size' => 6));
            $mform->setType('snakes_width', PARAM_INT);

            $mform->addELement('text', 'snakes_height', get_string('hiddenpicture_height', 'game'), array('size' => 6));
            $mform->setType('snakes_height', PARAM_INT);
        }

        // Hidden Picture options.
        if ($gamekind == 'hiddenpicture') {
            $mform->addElement('header', 'hiddenpicture', get_string( 'hiddenpicture_options', 'game'));
            $mform->addElement('text', 'param1', get_string('hiddenpicture_across', 'game'));
            $mform->setType('param1', PARAM_INT);
            $mform->setDefault('param1', 3);
            $mform->addElement('text', 'param2', get_string('hiddenpicture_down', 'game'));
            $mform->setType('param2', PARAM_INT);
            $mform->setDefault('param2', 3);

            $a = array();
            if ($recs = $DB->get_records('glossary', array( 'course' => $COURSE->id), 'id,name')) {
                foreach ($recs as $rec) {
                    $cmg = get_coursemodule_from_instance('glossary', $rec->id, $COURSE->id);
                    $context = game_get_context_module_instance( $cmg->id);
                    if ($DB->record_exists( 'files', array( 'contextid' => $context->id))) {
                        $a[$rec->id] = $rec->name;
                    }
                }
            }
            $mform->addElement('select', 'glossaryid2', get_string('hiddenpicture_pictureglossary', 'game'), $a);

            $mform->addElement('text', 'param4', get_string('hiddenpicture_width', 'game'));
            $mform->setType('param4', PARAM_INT);
            $mform->addELement('text', 'param5', get_string('hiddenpicture_height', 'game'));
            $mform->setType('param5', PARAM_INT);
            $mform->addElement('selectyesno', 'param7', get_string('hangman_allowspaces', 'game'));
        }

        // Header/Footer options.
        $mform->addElement('header', 'headerfooteroptions', 'Header/Footer Options');
        $mform->addElement('htmleditor', 'toptext', get_string('toptext', 'game'));
        $mform->addElement('htmleditor', 'bottomtext', get_string('bottomtext', 'game'));

        $features = new stdClass;
        $this->standard_coursemodule_elements($features);

        // Buttons.
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check open and close times are consistent.
        if ($data['timeopen'] != 0 && $data['timeclose'] != 0 &&
                $data['timeclose'] < $data['timeopen']) {
            $errors['timeclose'] = get_string('closebeforeopen', 'quiz');
        }

        return $errors;
    }


    public function set_data($defaultvalues) {
        global $DB;

        if (isset( $defaultvalues->type)) {
            // Default values for every game.
            if ($defaultvalues->type == 'hangman') {
                $defaultvalues->param10 = 6;    // Maximum number of wrongs.
            } else if ($defaultvalues->type == 'snakes') {
                $defaultvalues->gamekind = $defaultvalues->type;
                $defaultvalues->param3 = 1;
                $defaultvalues->questioncategoryid = 0;
            } else if ($defaultvalues->type == 'millionaire') {
                $defaultvalues->shuffle = 1;
            }
        }

        if (isset( $defaultvalues->gamekind)) {
            if ($defaultvalues->gamekind == 'hangman') {
                if ($defaultvalues->param10 == 0) {
                    $defaultvalues->param10 = 6;
                }
            } else if ($defaultvalues->gamekind == 'millionaire') {
                if (isset( $defaultvalues->param8)) {
                    $defaultvalues->param8 = '#'.substr( '000000'.strtoupper( dechex( $defaultvalues->param8)), -6);
                }
            } else if ($defaultvalues->gamekind == 'cross') {
                if ($defaultvalues->param5 == null) {
                    $defaultvalues->param5 = 1;
                }
            }

            if ($defaultvalues->gamekind == 'snakes') {
                if (isset( $defaultvalues->param9)) {
                    $a = explode( '#', $defaultvalues->param9);
                    foreach ($a as $s) {
                        $pos = strpos( $s, ':');
                        if ($pos) {
                            $name = substr( $s, 0, $pos);
                            $defaultvalues->$name = substr( $s, $pos + 1);
                        }
                    }
                }
            }
        }

        if (!isset( $defaultvalues->gamekind)) {
            $defaultvalues->gamekind = $defaultvalues->type;
        }
        if ($defaultvalues->gamekind == 'snakes') {
            if (isset( $defaultvalues->param3)) {
                $board = $defaultvalues->param3;
                if ($board != 0) {
                    $rec = $DB->get_record( 'game_snakes_database', array( 'id' => $board));
                    $defaultvalues->snakes_data = $rec->data;
                    $defaultvalues->snakes_cols = $rec->cols;
                    $defaultvalues->snakes_rows = $rec->rows;
                    $defaultvalues->snakes_headerx = $rec->headerx;
                    $defaultvalues->snakes_headery = $rec->headery;
                    $defaultvalues->snakes_footerx = $rec->footerx;
                    $defaultvalues->snakes_footery = $rec->footery;
                }
            }
        }

        parent::set_data($defaultvalues);
    }
}
