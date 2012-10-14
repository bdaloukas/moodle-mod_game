<?php  // $Id: export.php,v 1.16.2.4 2011/07/23 08:45:05 bdaloukas Exp $
/**
 * This page exports a game to a html or jar file
 * 
 * @author  bdaloukas
 * @version $Id: export.php,v 1.16.2.4 2011/07/23 08:45:05 bdaloukas Exp $
 * @package game
 **/

require( '../../config.php');
require_once ($CFG->dirroot.'/lib/formslib.php');
require( 'locallib.php');
ob_start();
require( 'header.php');

if( !isteacher( $game->course, $USER->id)){
	error( get_string( 'only_teachers', 'game'));
}

$target  = optional_param('target', "", PARAM_ALPHANUM);  // action

    $currenttab = 'export'.$target;

    include('tabs.php');

class mod_game_exporthtml_form extends moodleform {

    function definition() {
        global $CFG, $game;

        $mform = $this->_form;
        $html = $this->_customdata['html'];
//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        if( $game->gamekind == 'hangman'){
            $options = array();
            $options[ '0'] = 'Hangman with phrases';
            $options[ 'hangmanp'] = 'Hangman with pictures';
            $mform->addElement('select', 'type', get_string('javame_type', 'game'), $options);
            $mform->setDefault('type',$html->type);
        }

    //filename
        $mform->addElement('text', 'filename', get_string('javame_filename', 'game'), array('size'=>'30'));
        $mform->setDefault('filename',$html->filename);

    //html title
        $mform->addElement('text', 'title', get_string('html_title', 'game'), array('size'=>'80'));
        $mform->setDefault('title',$html->title);

    //fields for hangman
        if( $game->gamekind == 'hangman')
        {
            $mform->addElement('text', 'maxpicturewidth', get_string('javame_maxpicturewidth', 'game'), array('size'=>'5'));
            $mform->setDefault('maxpicturewidth',$html->maxpicturewidth);
            $mform->addElement('text', 'maxpictureheight', get_string('javame_maxpictureheight', 'game'), array('size'=>'5'));
            $mform->setDefault('maxpictureheight',$html->maxpictureheight);
        }
       
    //fiels for cross
        if( $game->gamekind == 'cross')
        {
            $mform->addElement('selectyesno', 'checkbutton', get_string('html_hascheckbutton', 'game'));
            $mform->setDefault('checkbutton',$html->checkbutton);
            $mform->addElement('selectyesno', 'printbutton', get_string('html_hasprintbutton', 'game'));
            $mform->setDefault('printbutton',$html->printbutton);
        }

        $mform->addElement('hidden', 'q', $game->id);
        $mform->addElement('hidden', 'target', 'html');

//-------------------------------------------------------------------------------
        $mform->addElement('submit', 'submitbutton', get_string( 'export', 'game'));
        $mform->closeHeaderBefore('submitbutton');
//-------------------------------------------------------------------------------
    }

    function validation($data, $files) {
        global $CFG, $USER;
        $errors = parent::validation($data, $files);


        return $errors;
    }

    function export() {
        global $game;

        $mform = $this->_form;
        
        $html = $this->_customdata['html'];

    	$html->type = optional_param('type', 0, PARAM_ALPHANUM);
        $html->filename = $mform->getElementValue('filename');
        $html->title = $mform->getElementValue('title');
        $html->maxpicturewidth = optional_param('maxpicturewidth', 0, PARAM_INT);
        $html->maxpictureheight = optional_param('maxpictureheight', 0, PARAM_INT);
        if( $mform->elementExists( 'checkbutton'))
            $html->checkbutton = $mform->getElementValue('checkbutton');
        if( $mform->elementExists( 'printbutton'))
            $html->printbutton = $mform->getElementValue('printbutton');

        if (!(update_record( 'game_export_html', $html))){
            print_error("game_export_html: not updated id=$html->id");
        }
	            
        require_once("exporthtml.php");
        game_OnExportHTML( $game, $html);
    }
}

class mod_game_exportjavame_form extends moodleform {

    function definition() {
        global $CFG, $game;

        $mform = $this->_form;
        $javame = $this->_customdata['javame'];

//-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        if( $game->gamekind == 'hangman'){
            $options = array();
            $options[ '0'] = 'Hangman with phrases';
            $options[ 'hangmanp'] = 'Hangman with pictures';
            $mform->addElement('select', 'type', get_string('javame_type', 'game'), $options);     
        }

    //filename
        $mform->addElement('text', 'filename', get_string('javame_filename', 'game'), array('size'=>'30'));
        $mform->setDefault('filename',$javame->filename);
        $mform->addElement('text', 'icon', get_string('javame_icon', 'game'));
        $mform->setDefault('icon',$javame->icon);
        $mform->addElement('text', 'createdby', get_string('javame_createdby', 'game'));
        $mform->setDefault('createdby',$javame->createdby);
        $mform->addElement('text', 'vendor', get_string('javame_vendor', 'game'));
        $mform->setDefault('vendor',$javame->vendor);
        $mform->addElement('text', 'name', get_string('javame_name', 'game'), array('size'=>'80'));
        $mform->setDefault('name',$javame->name);
        $mform->addElement('text', 'description', get_string('javame_description', 'game'), array('size'=>'80'));
        $mform->setDefault('description',$javame->description);
        $mform->addElement('text', 'version', get_string('javame_version', 'game'), array('size'=>'10'));
        $mform->setDefault('version',$javame->version);
        $mform->addElement('text', 'maxpicturewidth', get_string('javame_maxpicturewidth', 'game'), array('size'=>'5'));
        $mform->setDefault('maxpicturewidth',$javame->maxpicturewidth);
        $mform->addElement('text', 'maxpictureheight', get_string('javame_maxpictureheight', 'game'), array('size'=>'5'));
        $mform->setDefault('maxpictureheight',$javame->maxpictureheight);
    
        $mform->addElement('hidden', 'q', $game->id);
        $mform->addElement('hidden', 'target', 'javame');

//-------------------------------------------------------------------------------
        $mform->addElement('submit', 'submitbutton', get_string( 'export', 'game'));
        $mform->closeHeaderBefore('submitbutton');
//-------------------------------------------------------------------------------
        //$this->set_data($currententry);
    }

    function validation($data, $files) {
        global $CFG, $USER;
        $errors = parent::validation($data, $files);


        return $errors;
    }

    function export() {
        global $game;

        $mform = $this->_form;
        
        $javame = $this->_customdata['javame'];

    	$javame->type = optional_param('type', 0, PARAM_ALPHANUM);
        $javame->filename = $mform->getElementValue('filename');
        $javame->icon = $mform->getElementValue('icon');
        $javame->createdby = $mform->getElementValue('createdby');
        $javame->vendor = $mform->getElementValue('vendor');
        $javame->name = $mform->getElementValue('name');
        $javame->description = $mform->getElementValue('description');
        $javame->version = $mform->getElementValue('version');
        $javame->maxpicturewidth = $mform->getElementValue('maxpicturewidth');
        $javame->maxpictureheight = $mform->getElementValue('maxpictureheight');

        if (!(update_record( 'game_export_javame', $javame))){
            print_error("game_export_javame: not updated id=$javame->id");
        }
	            
        require_once("exportjavame.php");
        game_OnExportJavaME( $game, $javame);
    }

} 


// create form and set initial data
if( $target == 'html'){    
    $html = get_record_select( 'game_export_html', 'id='.$game->id);
    if( $html == false){
        unset( $html);
        $html->id = $game->id;
        $html->checkbutton = 1;
        $html->printbutton = 1;
        game_insert_record( 'game_export_html', $html);
        $html = get_record_select( 'game_export_html', 'id='.$game->id);
    }
    $mform = new mod_game_exporthtml_form(null, array('id'=>$id, 'html' => $html));
}else
{
    $javame = get_record_select( 'game_export_javame', 'id='.$game->id);
    if( $javame == false){
        unset( $javame);
        $javame->id = $game->id;
        game_insert_record( 'game_export_javame', $javame);
        $javame = get_record_select( 'game_export_javame', 'id='.$game->id);
    }
    $mform = new mod_game_exportjavame_form(null, array('id'=>$id, 'javame' => $javame));
}


if ($mform->is_cancelled()){
    ob_end_flush();
    if ($id){
        redirect("view.php?id=$cm->id&amp;mode=entry&amp;hook=$id");
    } else {
        redirect("view.php?id=$cm->id");
    }

} else if ($entry = $mform->get_data()) {
    $mform->export();
}else{
    ob_end_flush();
    $mform->display();
}

print_footer();

function game_send_stored_file($file) {
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }else
        die("file does not exists");
}
