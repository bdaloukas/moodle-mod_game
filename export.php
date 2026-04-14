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
 * This page exports a game to another platform, for example HTML or Java ME.
 *
 * @package    mod_game
 * @subpackage game
 * @copyright  2007 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
ob_start();

require_once("headergame.php");

require_login($course->id, false, $cm);
$context = game_get_context_module_instance($cm->id);
require_capability('mod/game:view', $context);
require_once($CFG->dirroot . '/lib/formslib.php');

if (!has_capability('mod/game:viewreports', $context)) {
    return;
}

$target = optional_param('target', "", PARAM_ALPHANUM); // The target is HTML or JavaMe.

/**
 * The mod_game_exporthtml_form show the export form.
 *
 * @package    mod_game
 * @copyright  2007 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_game_exporthtml_form extends moodleform {
    /**
     * Definition of form.
     */
    public function definition() {
        global $game;

        $mform = $this->_form;
        $html = $this->_customdata['html'];

        $mform->addElement('header', 'general', get_string('general', 'form'));

        if ($game->gamekind == 'hangman') {
            $options = [];
            $options['0'] = 'Hangman with phrases';
            $options['hangmanp'] = 'Hangman with pictures';
            $mform->addElement('select', 'type', get_string('javame_type', 'game'), $options);
            if ($html->type == 0) {
                $mform->setDefault('type', '0');
            } else {
                $mform->setDefault('type', 'hangmanp');
            }
        }

        // Input the filename.
        $mform->addElement('text', 'filename', get_string('javame_filename', 'game'), ['size' => '30']);
        $mform->setDefault('filename', $html->filename);
        $mform->setType('filename', PARAM_TEXT);

        // Input the html title.
        $mform->addElement('text', 'title', get_string('html_title', 'game'), ['size' => '80']);
        $mform->setDefault('title', $html->title);
        $mform->setType('title', PARAM_TEXT);

        // Inputs special fields for hangman.
        if ($game->gamekind == 'hangman') {
            $mform->addElement('text', 'maxpicturewidth', get_string('javame_maxpicturewidth', 'game'), ['size' => '5']);
            $mform->setDefault('maxpicturewidth', $html->maxpicturewidth);
            $mform->setType('maxpicturewidth', PARAM_INT);
            $mform->addElement('text', 'maxpictureheight', get_string('javame_maxpictureheight', 'game'), ['size' => '5']);
            $mform->setDefault('maxpictureheight', $html->maxpictureheight);
            $mform->setType('maxpictureheight', PARAM_INT);
        }

        // Input special fields for crossword.
        if ($game->gamekind == 'cross') {
            $mform->addElement('selectyesno', 'checkbutton', get_string('html_hascheckbutton', 'game'));
            $mform->setDefault('checkbutton', $html->checkbutton);
            $mform->addElement('selectyesno', 'printbutton', get_string('html_hasprintbutton', 'game'));
            $mform->setDefault('printbutton', $html->printbutton);
        }

        $mform->addElement('hidden', 'q', $game->id);
        $mform->setType('q', PARAM_INT);
        $mform->addElement('hidden', 'target', 'html');
        $mform->setType('target', PARAM_TEXT);

        $mform->addElement('submit', 'submitbutton', get_string('export', 'game'));
        $mform->closeHeaderBefore('submitbutton');
    }

    /**
     * Do the exporting.
     */
    public function export() {
        global $game, $DB;

        $mform = $this->_form;

        $html = new stdClass();
        $html->id = $this->_customdata['html']->id;
        $html->type = optional_param('type', 0, PARAM_ALPHANUM);
        $html->filename = $mform->getElementValue('filename');
        $html->title = $mform->getElementValue('title');
        $html->maxpicturewidth = optional_param('maxpicturewidth', 0, PARAM_INT);
        $html->maxpictureheight = optional_param('maxpictureheight', 0, PARAM_INT);
        if ($mform->elementExists('checkbutton')) {
            $checkbuttonvalue = $mform->getElementValue('checkbutton');
            $html->checkbutton = $checkbuttonvalue[0];
        }
        if ($mform->elementExists('printbutton')) {
            $printbuttonvalue = $mform->getElementValue('printbutton');
            $html->printbutton = $printbuttonvalue[0];
        }

        if (!($DB->update_record('game_export_html', $html))) {
            throw new moodle_exception('game_error', 'game', "game_export_html: not updated id=$html->id");
        }

        $cm = get_coursemodule_from_instance('game', $game->id, $game->course);
        $context = game_get_context_module_instance($cm->id);

        require_once("export/exporthtml.php");
        game_OnExportHTML($game, $context, $html);
    }
}

// Creates form and set initial data.
if ($target == 'html') {
    $html = $DB->get_record('game_export_html', [ 'id' => $game->id]);
    if ($html == false) {
        $html = new stdClass();
        $html->id = $game->id;
        $html->checkbutton = 1;
        $html->printbutton = 1;
        game_insert_record('game_export_html', $html);
        $html = $DB->get_record('game_export_html', [ 'id' => $game->id]);
    }
    $html->type = 0;
    $mform = new mod_game_exporthtml_form(null, ['id' => $id, 'html' => $html]);
}

if ($mform->is_cancelled()) {
    ob_end_flush();
    if ($id) {
        redirect("view.php?id=$cm->id&amp;mode=entry&amp;hook=$id");
    } else {
        redirect("view.php?id=$cm->id");
    }
} else if ($entry = $mform->get_data()) {
    $mform->export();
} else {
    ob_end_flush();
    if (!empty($id)) {
        $PAGE->navbar->add(get_string('export', 'game'));
    }

    $mform->display();
}
echo $OUTPUT->footer();

/**
 * Sends via html a file.
 *
 * @param string $file
 * @throws moodle_exception
 * @package mod_game
 *
 */
function game_send_stored_file($file) {
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    } else {
        throw new moodle_exception('game_error', 'game', "export.php: File does not exists " . $file);
    }
}

/**
 * Exports to javame.
 *
 * @param stdClass $game
 * @param stdClass $context
 * @param boolean $exportattachment
 * @param string $dest
 * @param array $files
 */
function game_exmportjavame_getanswers( $game, $context, $exportattachment, $dest, &$files) {
    $files = [];

    switch ($game->sourcemodule) {
        case 'question':
            return game_exmportjavame_getanswers_question( $game, $context, $dest, $files);
        case 'glossary':
            return game_exmportjavame_getanswers_glossary( $game, $context, $exportattachment, $dest, $files);
        case 'quiz':
            return game_exmportjavame_getanswers_quiz( $game, $context, $dest, $files);
    }

    return false;
}

/**
 * Exports to javame.
 *
 * @param stdClass $game
 * @param stdClass $context
 * @param string $destdir
 * @param array $files
 * @return array|null
 * @throws dml_exception
 * @throws moodle_exception
 */
function game_exmportjavame_getanswers_question( $game, $context, $destdir, &$files) {
    $select = 'hidden = 0 AND category='.$game->questioncategoryid;

    $select .= game_showanswers_appendselect( $game);

    return game_exmportjavame_getanswers_question_select( $game, $context, 'question',
        $select, '*', $game->course, $destdir, $files);
}


/**
 * Exports to javame.
 *
 * @param stdClass $game
 * @param stdClass $context
 * @param string $table
 * @param string $select
 * @param string $fields
 * @param int $courseid
 * @param string $destdir
 * @param array $files
 * @return array|void
 * @throws dml_exception
 * @throws moodle_exception
 */
function game_exmportjavame_getanswers_question_select(
    $game,
    $context,
    $table,
    $select,
    $fields,
    $courseid,
    $destdir,
    &$files
) {
    global $DB;

    if (($questions = $DB->get_records_select( $table, $select, null, '', $fields)) === false) {
        return;
    }

    $map = [];
    foreach ($questions as $question) {
        unset( $ret);
        $ret = new stdClass();
        $ret->qtype = $question->qtype;
        $ret->question = $question->questiontext;
        $ret->question = str_replace( [ '"', '#'], [ "'", ' '],
            game_export_split_files( $game->course, $context, 'questiontext',
                $question->id, $ret->question, $destdir, $files));

        switch ($question->qtype) {
            case 'shortanswer':
                $rec = $DB->get_record( 'question_answers', [ 'question' => $question->id],
                    'id,answer,feedback');
                $ret->answer = $rec->answer;
                $ret->feedback = $rec->feedback;
                $map[] = $ret;
                break;
            default:
                break;
        }
    }

    return $map;
}


/**
 * Exports to javame.
 *
 * @param stdClass $game
 * @param stdClass $context
 * @param boolean $exportattachment
 * @param string $destdir
 * @param array $files
 * @return array|false
 * @throws coding_exception
 * @throws dml_exception
 */
function game_exmportjavame_getanswers_glossary( $game, $context, $exportattachment, $destdir, &$files) {
    global $CFG, $DB;

    $table = '{glossary_entries} ge';
    $select = "glossaryid={$game->glossaryid}";
    if ($game->glossarycategoryid) {
        $select .= " AND gec.entryid = ge.id ".
            " AND gec.categoryid = {$game->glossarycategoryid}";
        $table .= ",{glossary_entries_categories} gec";
    }

    if ($exportattachment) {
        $select .= " AND attachment <> ''";
    }

    $fields = 'ge.id,definition,concept';
    if ($exportattachment) {
        $fields .= ',attachment';
    }
    $sql = "SELECT $fields FROM $table WHERE $select ORDER BY definition";
    if (($questions = $DB->get_records_sql( $sql)) === false) {
        return false;
    }

    $fs = get_file_storage();
    $map = [];
    $cmglossary = false;

    foreach ($questions as $question) {
        $ret = new stdClass();
        $ret->id = $question->id;
        $ret->qtype = 'shortanswer';
        $ret->question = strip_tags( $question->definition);
        $ret->answer = $question->concept;
        $ret->feedback = '';
        $ret->attachment = '';

        // Copies the appropriate files from the file storage to destdir.
        if ($exportattachment) {
            if ($question->attachment != '') {
                if ($cmglossary === false) {
                    $cmglossary = get_coursemodule_from_instance('glossary', $game->glossaryid, $game->course);
                    $contextglossary = game_get_context_module_instance($cmglossary->id);
                }

                $ret->attachment = "glossary/{$game->glossaryid}/$question->id/$question->attachment";
                $myfiles = $fs->get_area_files($contextglossary->id, 'mod_glossary', 'attachment', $ret->id);
                $i = 0;

                foreach ($myfiles as $f) {
                    if ($f->is_directory()) {
                        continue;
                    }
                    $filename = $f->get_filename();
                    $pos = strrpos( $filename, '.');
                    $ext = substr( $filename, $pos);
                    $destfile = $ret->id;
                    if ($i > 0) {
                        $destfile .= '_'.$i;
                    }
                    $destfile = $destdir.'/'.$destfile.$ext;
                    $f->copy_content_to( $destfile);
                    $ret->attachment = $destfile;
                    $i++;
                    $files[] = $destfile;
                }
            }
        }

        $map[] = $ret;
    }

    return $map;
}


/**
 * Exports to javame.
 *
 * @param stdClass $game
 * @param stdClass $context
 * @param string $destdir
 * @param array $files
 * @return array|null
 * @throws dml_exception
 * @throws moodle_exception
 */
function game_exmportjavame_getanswers_quiz( $game, $context, $destdir, $files) {
    $select = "quiz='$game->quizid' ".
        " AND qqi.question=q.id".
        " AND q.hidden=0".
        game_showanswers_appendselect( $game);
    $table = "{question} q,{quiz_question_instances} qqi";

    return game_exmportjavame_getanswers_question_select(
        $game,
        $context,
        $table,
        $select,
        "q.*",
        $game->course,
        $destdir,
        $files
    );
}

/**
 * Copy images
 *
 * @param string $filename
 * @param string $dest
 * @param int $maxwidth
 */
function game_export_javame_smartcopyimage($filename, $dest, $maxwidth) {
    if ($maxwidth == 0) {
        copy($filename, $dest);
        return;
    }

    $size = getimagesize($filename);
    if ($size === false) {
        copy($filename, $dest);
        return;
    }

    $mul = $maxwidth / $size[0];
    if ($mul > 1) {
        copy($filename, $dest);
        return;
    }

    $mime = $size['mime'];
    switch ($mime) {
        case 'image/png':
            $srcimage = imagecreatefrompng($filename);
            break;
        case 'image/jpeg':
            $srcimage = imagecreatefromjpeg($filename);
            break;
        case 'image/gif':
            $srcimage = imagecreatefromgif($filename);
            break;
        default:
            die('Aknown mime type $mime');
            return false;
    }

    $dstw = $size[0] * $mul;
    $dsth = $size[1] * $mul;
    $dstimage = imagecreatetruecolor($dstw, $dsth);
    imagecopyresampled($dstimage, $srcimage, 0, 0, 0, 0, $dstw, $dsth, $size[0], $size[1]);

    imagejpeg($dstimage, $dest);
}
