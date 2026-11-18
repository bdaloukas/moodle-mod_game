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
 * The script supports book
 *
 * @package mod_game
 * @copyright 2007 Vasilis Daloukas
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../../config.php");
require_once("../headergame.php");
require_once("../locallib.php");

require_login($course->id, false, $cm);
require_capability('mod/game:manage', $context);

$attempt = game_getattempt($game, $detail);
if ($game->bookid == 0) {
    throw new moodle_exception('bookquiz_not_select_book', 'game');
}

if ($form = data_submitted()) {
    require_sesskey();
    game_bookquiz_save($game->id, $game->bookid, $form);
}

// Print upload form.
$OUTPUT->heading($course->fullname);

$select = "gameid={$game->id}";
$categories = [];
if (($recs = $DB->get_records_select('game_bookquiz_questions', $select, null, '', 'chapterid,questioncategoryid')) != false) {
    foreach ($recs as $rec) {
        $categories[$rec->chapterid] = $rec->questioncategoryid;
    }
}

$context = game_get_context_course_instance($COURSE->id);
$select = " contextid in ($context->id)";

$a = [0 => get_string('none')];
if ($recs = $DB->get_records_select('question_categories', $select, null, 'id,name')) {
    foreach ($recs as $rec) {
        $s = $rec->name;
        if (($count = $DB->count_records('question', ['category' => $rec->id])) != 0) {
            $s .= " ($count)";
        }
        $a[$rec->id] = $s;
    }
}

$sql = "SELECT chapterid, COUNT(*) as c " .
    "FROM {game_bookquiz_questions} gbq,{question} q " .
    "WHERE gbq.questioncategoryid=q.category " .
    "AND gameid=$game->id " .
    "GROUP BY chapterid";
$numbers = [];
if (($recs = $DB->get_records_sql($sql)) != false) {
    foreach ($recs as $rec) {
        $numbers[$rec->chapterid] = $rec->c;
    }
}

echo '<form name="form" method="post" action="questions.php">';
echo '<table border=1>';
echo '<tr>';
echo '<td><center>' . get_string('bookquiz_chapters', 'game') . '</td>';
echo '<td><center>' . get_string('bookquiz_categories', 'game') . '</td>';
echo '<td><center>' . get_string('bookquiz_numquestions', 'game') . '</td>';
echo "</tr>\r\n";
if (($recs = $DB->get_records('book_chapters', ['bookid' => $game->bookid], 'pagenum', 'id,title')) != false) {
    foreach ($recs as $rec) {
        echo '<tr>';
        echo '<td>' . $rec->title . '</td>';
        echo '<td>';
        if (array_key_exists($rec->id, $categories)) {
            $categoryid = $categories[$rec->id];
        } else {
            $categoryid = 0;
        }
        echo game_showselectcontrol('categoryid_' . $rec->id, $a, $categoryid, '');
        echo '</td>';

        echo '<td>';
        if (array_key_exists($rec->id, $numbers)) {
            echo '<center>' . $numbers[$rec->id] . '</center>';
        } else {
            echo '&nbsp;';
        }
        echo '</td>';

        echo "</tr>\r\n";

    }
}
?>
</table>
<br>
<!-- These hidden variables are always the same -->
<input type="hidden" name=id       value="<?php  p($id) ?>" />
<input type="hidden" name=q       value="<?php  p($q) ?>" />
<input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
<center>
<input type="submit" value="<?php  print_string("savechanges") ?>" />
</center>

</form>
<?php

echo $OUTPUT->footer($course);

/**
 * Save the selected question categories for chapters of the configured Book.
 *
 * Chapter and category identifiers are derived/validated server-side; the
 * submitted form must not control which Book chapters can be edited.
 *
 * @package mod_game
 * @param int $gameid Game instance id.
 * @param int $bookid Configured Book instance id.
 * @param stdClass $form Submitted POST data.
 * @throws dml_exception
 * @throws moodle_exception
 */
function game_bookquiz_save($gameid, $bookid, $form) {
    global $DB;

    $game = $DB->get_record('game', ['id' => $gameid], 'id,course,bookid', MUST_EXIST);
    if ((int) $game->bookid !== (int) $bookid) {
        throw new invalid_parameter_exception('The Book does not belong to this Game.');
    }

    // Use the same course question categories as those displayed in the form.
    $coursecontext = game_get_context_course_instance($game->course);
    $validcategories = $DB->get_records('question_categories',
        ['contextid' => $coursecontext->id], '', 'id');

    // Do not trust a submitted list of chapter IDs. Read them from the Book.
    $chapters = $DB->get_records('book_chapters', ['bookid' => $bookid], '', 'id');
    $submitted = [];
    foreach ($chapters as $chapter) {
        $fieldname = 'categoryid_' . $chapter->id;
        if (!isset($form->{$fieldname}) ||
                !is_scalar($form->{$fieldname}) ||
                !ctype_digit((string) $form->{$fieldname})) {
            throw new invalid_parameter_exception('Missing or invalid chapter category.');
        }
        $categoryid = (int) $form->{$fieldname};
        if ($categoryid !== 0 && !isset($validcategories[$categoryid])) {
            throw new invalid_parameter_exception('Category is not available in this course.');
        }
        $submitted[$chapter->id] = $categoryid;
    }

    // Only perform writes after every submitted value has been validated.
    $existing = $DB->get_records('game_bookquiz_questions',
        ['gameid' => $gameid], '', 'id,chapterid,questioncategoryid');
    $existingbychapter = [];
    foreach ($existing as $record) {
        $existingbychapter[$record->chapterid] = $record;
    }

    $transaction = $DB->start_delegated_transaction();
    foreach ($submitted as $chapterid => $categoryid) {
        if (!isset($existingbychapter[$chapterid])) {
            if ($categoryid === 0) {
                continue;
            }
            $record = (object) [
                'gameid' => $gameid,
                'chapterid' => $chapterid,
                'questioncategoryid' => $categoryid,
            ];
            $DB->insert_record('game_bookquiz_questions', $record);
            continue;
        }

        $current = $existingbychapter[$chapterid];
        if ((int) $current->questioncategoryid === $categoryid) {
            continue;
        }
        if ($categoryid === 0) {
            $DB->delete_records('game_bookquiz_questions',
                ['id' => $current->id, 'gameid' => $gameid]);
        } else {
            $DB->update_record('game_bookquiz_questions', (object) [
                'id' => $current->id,
                'questioncategoryid' => $categoryid,
            ]);
        }
    }
    $transaction->allow_commit();
}
