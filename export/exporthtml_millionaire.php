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
 * Export the hangman game to HTML.
 *
 * @package    mod_game
 * @subpackage export
 * @copyright  2007 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Exports millionaire.
 *
 * @param stdClass $game
 * @param stdClass $context
 * @param int $maxanswers
 * @param int $countofquestions
 * @param string $retfeedback
 * @param string $destdir
 * @param array $files
 */
function game_millionaire_html_getquestions($game, $context, &$maxanswers, &$countofquestions, &$retfeedback, $destdir, &$files) {
    global $CFG, $DB;

    $maxanswers = 0;
    $countofquestions = 0;

    $files = [];

    if (($game->sourcemodule != 'quiz') && ($game->sourcemodule != 'question')) {
        throw new moodle_exception(
            'millionaire_sourcemodule_must_quiz_question',
            'game',
            get_string('modulename', 'quiz') . ' ' . get_string('modulename', $game->sourcemodule)
        );
    }

    if ($game->sourcemodule == 'quiz') {
        if ($game->quizid == 0) {
            throw new moodle_exception('must_select_quiz', 'game');
        }
        $select = "qtype='multichoice' AND quiz='$game->quizid' " .
            " AND qqi.question=q.id";
        $table = "{question} q,{quiz_question_instances} qqi";
    } else {
        if ($game->questioncategoryid == 0) {
            throw new moodle_exception('must_select_questioncategory', 'game');
        }

        $table = "{question} q";

        // Include subcategories.
        if (game_get_moodle_version() >= '04.00') {
            $table .= ",{$CFG->prefix}question_bank_entries qbe,{$CFG->prefix}question_versions qv ";
            $select = 'qbe.id=qv.questionbankentryid AND q.id=qv.questionid ' .
                ' AND qbe.questioncategoryid=' . $game->questioncategoryid;
            if ($game->subcategories) {
                $cats = question_categorylist($game->questioncategoryid);
                if (count($cats) > 0) {
                    $select = 'qbe.questioncategoryid in (' . implode(',', $cats) . ')';
                }
            }
        } else {
            $select = 'category=' . $game->questioncategoryid;
            if ($game->subcategories) {
                $cats = question_categorylist($game->questioncategoryid);
                if (count($cats)) {
                    $select = 'category in (' . implode(',', $cats) . ')';
                }
            }
        }

        $select .= " AND qtype='multichoice'";

    }
    $sql = "SELECT q.id as id, q.questiontext FROM $table WHERE $select";
    $recs = $DB->get_records_sql($sql);
    $ret = '';
    $retfeedback = '';
    foreach ($recs as $rec) {
        $recs2 = $DB->get_records('question_answers', ['question' => $rec->id], 'fraction DESC', 'id,answer,feedback');

        // Must parse the questiontext and get the name of files.
        $line = game_export_split_files($game->course, $context, 'questiontext', $rec->id, $rec->questiontext, $destdir, $files);
        $linefeedback = '';
        foreach ($recs2 as $rec2) {
            $line .= '#' .
                str_replace(
                    ['"', '#'],
                    ["'", ' '],
                    game_export_split_files($game->course, $context, 'answer', $rec2->id, $rec2->answer, $destdir, $files)
                );
            $linefeedback .= '#' . str_replace(['"', '#'], ["'", ' '], $rec2->feedback);
        }
        if ($ret != '') {
            $ret .= ",\r";
        }
        $ret .= '"' . base64_encode($line) . '"';

        if ($retfeedback != '') {
            $retfeedback .= ",\r";
        }
        $retfeedback .= '"' . base64_encode($linefeedback) . '"';

        if (count($recs2) > $maxanswers) {
            $maxanswers = count($recs2);
        }
        $countofquestions++;
    }

    return $ret;
}

/**
 * Exports to html a "Millionaire" game.
 *
 * @param stdClass $game
 * @param string $questions
 * @param int $maxquestions
 */
function game_millionaire_html_print($game, $questions, $maxquestions) {
    $color1 = 'black';
    $color2 = 'DarkOrange';
    $colorback = 'white';

    $winmessage = get_string('win', 'game');
    $wronganswerinfo = get_string('millionaire_info_wrong_answer', 'game');
    $loosemessage = strip_tags(get_string('hangman_loose', 'game'));
    $telephonemessage = get_string('millionaire_info_telephone', 'game') . '<br><b>';
    $lettersall = get_string('lettersall', 'game');
    $peoplemessage = '<br>' . get_string('millionaire_info_people', 'game') . ':<br>';
    $telephonetitle = get_string('millionaire_telephone', 'game');
    $helppeopletitle = get_string('millionaire_helppeople', 'game');
    $quittitle = get_string('millionaire_quit', 'game');

    echo '<body onload="Reset();">' . "\n";
    echo '<script type="text/javascript">' . "\n";
    echo '// Millionaire for Moodle by Vasilis Daloukas.' . "\n";
    echo 'var questions = new Array(' . $questions . ');' . "\n";
    echo 'var current_question = 0;' . "\n";
    echo 'var level = 0;' . "\n";
    echo 'var posCorrect = 0;' . "\n";
    echo 'var infoCorrect = "";' . "\n";
    echo 'var flag5050 = 0;' . "\n";
    echo 'var flagTelephone = 0;' . "\n";
    echo 'var flagPeople = 0;' . "\n";
    echo 'var countQuestions = 0;' . "\n";
    echo 'var maxQuestions = ' . (int)$maxquestions . ';' . "\n";
    echo 'var color1 = ' . json_encode($color1) . ';' . "\n";
    echo 'var color2 = ' . json_encode($color2) . ';' . "\n";
    echo 'var colorback = ' . json_encode($colorback) . ';' . "\n";
    echo 'var winMessage = ' . json_encode($winmessage) . ';' . "\n";
    echo 'var wrongAnswerInfo = ' . json_encode($wronganswerinfo . ' ') . ';' . "\n";
    echo 'var looseMessage = ' . json_encode($loosemessage) . ';' . "\n";
    echo 'var telephoneInfo = ' . json_encode($telephonemessage . ' ') . ';' . "\n";
    echo 'var lettersAll = ' . json_encode($lettersall) . ';' . "\n";
    echo 'var peopleInfo = ' . json_encode($peoplemessage) . ';' . "\n";

    echo <<<JS

function Highlite(ans) {
    document.getElementById("btAnswer" + ans).style.backgroundColor = color2;
}

function Restore(ans) {
    document.getElementById("btAnswer" + ans).style.backgroundColor = colorback;
}

function OnSelectAnswer(ans) {
    if (posCorrect == ans) {
        if (level + 1 > 15) {
            alert(winMessage);
            Reset();
        } else {
            UpdateLevel(level + 1);
            SelectNextQuestion();
        }
    } else {
        OnGameOver(ans);
    }
}

function OnGameOver(ans) {
    document.getElementById("info").innerHTML =
        wrongAnswerInfo + document.getElementById("lblAnswer" + posCorrect).innerHTML;
    Highlite(posCorrect);
    Restore(ans);
    document.getElementById("lblAnswer" + posCorrect).style.backgroundColor = color2;

    alert(looseMessage);

    Restore(posCorrect);
    document.getElementById("lblAnswer" + posCorrect).style.backgroundColor = colorback;

    Reset();
}

function UpdateLevel(newlevel) {
    if (level > 0) {
        document.getElementById("levela" + level).bgColor = colorback;
        document.getElementById("levelb" + level).bgColor = colorback;
        document.getElementById("levelc" + level).bgColor = colorback;
        document.getElementById("levela" + level).style.color = color1;
        document.getElementById("levelb" + level).style.color = color1;
        document.getElementById("levelc" + level).style.color = color1;
    }

    level = newlevel;

    document.getElementById("levela" + level).bgColor = color2;
    document.getElementById("levelb" + level).bgColor = color2;
    document.getElementById("levelc" + level).bgColor = color2;
    document.getElementById("levela" + level).style.color = colorback;
    document.getElementById("levelb" + level).style.color = colorback;
    document.getElementById("levelc" + level).style.color = colorback;
}

function OnHelp5050(ans) {
    var i;
    var pos;

    if (flag5050) {
        return;
    }

    document.getElementById("Help5050").src = "5050x.png";
    flag5050 = 1;

    for (pos = posCorrect; pos == posCorrect; pos = 1 + Math.floor(Math.random() * countQuestions)) {
    }

    for (i = 1; i <= countQuestions; i++) {
        if ((i != pos) && (i != posCorrect)) {
            document.getElementById("lblAnswer" + i).style.visibility = "hidden";
            document.getElementById("btAnswer" + i).style.visibility = "hidden";
        }
    }
}

function OnHelpTelephone(ans) {
    var wrong;
    var pos;

    if (flagTelephone) {
        return;
    }
    flagTelephone = 1;
    document.getElementById("HelpTelephone").src = "telephonex.png";

    if (countQuestions < 2) {
        wrong = posCorrect;
    } else {
        for (;;) {
            wrong = 1 + Math.floor(Math.random() * countQuestions);
            if (wrong != posCorrect) {
                break;
            }
        }
    }

    if (Math.random() <= 0.8) {
        pos = posCorrect;
    } else {
        pos = wrong;
    }

    document.getElementById("info").innerHTML =
        telephoneInfo + document.getElementById("lblAnswer" + pos).innerHTML;
}

function OnHelpPeople(ans) {
    var i;
    var sum;
    var percent;
    var maxPos;
    var temp;
    var info;
    var aPercent = new Array();

    if (flagPeople) {
        return;
    }
    flagPeople = 1;
    document.getElementById("HelpPeople").src = "peoplex.png";

    sum = 0;
    for (i = 0; i < countQuestions - 1; i++) {
        percent = Math.floor(Math.random() * (100 - sum));
        aPercent[i] = percent;
        sum += percent;
    }
    aPercent[countQuestions - 1] = 100 - sum;

    if (Math.random() <= 0.8) {
        maxPos = 0;
        for (i = 1; i < countQuestions; i++) {
            if (aPercent[i] >= aPercent[maxPos]) {
                maxPos = i;
            }
        }
        temp = aPercent[maxPos];
        aPercent[maxPos] = aPercent[posCorrect - 1];
        aPercent[posCorrect - 1] = temp;
    }

    info = peopleInfo;
    for (i = 0; i < countQuestions; i++) {
        info += "<br>" + lettersAll.charAt(i) + " : " + aPercent[i] + " %";
    }

    document.getElementById("info").innerHTML = info;
}

function OnQuit(ans) {
    Reset();
}

function Reset() {
    var i;

    for (i = 1; i <= 15; i++) {
        document.getElementById("levela" + i).bgColor = colorback;
        document.getElementById("levelb" + i).bgColor = colorback;
        document.getElementById("levelc" + i).bgColor = colorback;
        document.getElementById("levela" + i).style.color = color1;
        document.getElementById("levelb" + i).style.color = color1;
        document.getElementById("levelc" + i).style.color = color1;
    }

    flag5050 = 0;
    flagTelephone = 0;
    flagPeople = 0;

    document.getElementById("Help5050").src = "5050.png";
    document.getElementById("HelpPeople").src = "people.png";
    document.getElementById("HelpTelephone").src = "telephone.png";

    document.getElementById("info").innerHTML = "";
    UpdateLevel(1);
    SelectNextQuestion();
}

function RandomizeAnswers(elements) {
    var i;
    var pos;
    var temp;

    posCorrect = 1;
    countQuestions = elements.length - 1;

    for (i = 1; i <= countQuestions; i++) {
        pos = 1 + Math.floor(Math.random() * countQuestions);
        if (posCorrect == i) {
            posCorrect = pos;
        } else if (posCorrect == pos) {
            posCorrect = i;
        }

        temp = elements[i];
        elements[i] = elements[pos];
        elements[pos] = temp;
    }
}

function SelectNextQuestion() {
    var i;
    var question;
    var elements = new Array();

    current_question = Math.floor(Math.random() * questions.length);
    question = Base64.decode(questions[current_question]);
    elements = question.split("#");

    RandomizeAnswers(elements);

    document.getElementById("question").innerHTML = elements[0];
    for (i = 1; i < elements.length; i++) {
        document.getElementById("lblAnswer" + i).innerHTML = elements[i];
        document.getElementById("lblAnswer" + i).style.visibility = "visible";
        document.getElementById("btAnswer" + i).style.visibility = "visible";
    }
    for (i = elements.length; i <= maxQuestions; i++) {
        document.getElementById("lblAnswer" + i).style.visibility = "hidden";
        document.getElementById("btAnswer" + i).style.visibility = "hidden";
    }

    document.getElementById("info").innerHTML = "";
}

/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/

var Base64 = {
    _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    decode : function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\\+\\/\\=]/g, "");

        while (i < input.length) {
            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }
        }

        output = Base64._utf8_decode(output);
        return output;
    },

    _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = 0;
        var c2 = 0;
        var c3 = 0;

        while (i < utftext.length) {
            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            } else if ((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i + 1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            } else {
                c2 = utftext.charCodeAt(i + 1);
                c3 = utftext.charCodeAt(i + 2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }
        }

        return string;
    }
};
</script>
JS;

    echo '<table cellpadding="0" cellspacing="0" border="0">' . "\n";
    echo '<tr style="background:#408080">' . "\n";
    echo '<td rowspan="' . (int)(17 + $maxquestions) . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>' . "\n";
    echo '<td colspan="6">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>' . "\n";
    echo '<td rowspan="' . (int)(17 + $maxquestions) . '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>' . "\n";
    echo '</tr>' . "\n";

    echo '<tr height="10%">' . "\n";
    echo '<td style="background:#408080" rowspan="3" colspan="2">' . "\n";
    echo '<input type="image" name="Help5050" id="Help5050" title="50 50" src="5050.png" alt="" border="0"
                onmousedown="OnHelp5050();">&nbsp;' . "\n";
    echo '<input type="image" name="HelpTelephone" id="HelpTelephone" title="' . s($telephonetitle) .
        '" src="telephone.png" alt="" border="0" onmousedown="OnHelpTelephone();">&nbsp;' . "\n";
    echo '<input type="image" name="HelpPeople" id="HelpPeople" title="' . s($helppeopletitle) .
        '" src="people.png" alt="" border="0" onmousedown="OnHelpPeople();">&nbsp;' . "\n";
    echo '<input type="image" name="Quit" id="Quit" title="' . s($quittitle) .
        '" src="x.png" alt="" border="0" onmousedown="OnQuit();">&nbsp;' . "\n";
    echo '</td>' . "\n";
    echo '<td rowspan="' . (int)(16 + $maxquestions) . '" style="background:#408080">&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;</td>' . "\n";
    echo '<td id="levela15" align="right">15</td>' . "\n";
    echo '<td id="levelb15">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>' . "\n";
    echo '<td id="levelc15" align="right">150000</td>' . "\n";
    echo '</tr>' . "\n";

    echo '<tr><td id="levela14" align="right">14</td><td id="levelb14"></td><td id="levelc14"
     align="right">800000</td></tr>' . "\n";
    echo '<tr><td id="levela13" align="right">13</td><td id="levelb13"></td><td id="levelc13"
     align="right">400000</td></tr>' . "\n";

    echo '<tr><td rowspan="12" colspan="2" valign="top" style="background:' . s($colorback) .
        ';color:' . s($color1) . '">';
    echo '<div id="question">aa</div></td>';
    echo '<td id="levela12" align="right">12</td><td id="levelb12"></td><td id="levelc12"
     align="right">200000</td></tr>' . "\n";

    echo '<tr><td id="levela11" align="right">11</td><td id="levelb11"></td><td id="levelc11"
     align="right">10000</td></tr>' . "\n";
    echo '<tr><td id="levela10" align="right">10</td><td id="levelb10"></td><td id="levelc10"
     align="right">5000</td></tr>' . "\n";
    echo '<tr><td id="levela9" align="right">9</td><td id="levelb9"></td><td id="levelc9"
     align="right">4000</td></tr>' . "\n";
    echo '<tr><td id="levela8" align="right">8</td><td id="levelb8"></td><td id="levelc8"
     align="right">2000</td></tr>' . "\n";
    echo '<tr><td id="levela7" align="right">7</td><td id="levelb7"></td><td id="levelc7"
     align="right">1500</td></tr>' . "\n";
    echo '<tr><td id="levela6" align="right">6</td><td id="levelb6"></td><td id="levelc6" align="right">1000</td></tr>' . "\n";
    echo '<tr><td id="levela5" align="right">5</td><td id="levelb5"></td><td id="levelc5" align="right">500</td></tr>' . "\n";
    echo '<tr><td id="levela4" align="right">4</td><td id="levelb4"></td><td id="levelc4" align="right">400</td></tr>' . "\n";
    echo '<tr><td id="levela3" align="right">3</td><td id="levelb3"></td><td id="levelc3" align="right">300</td></tr>' . "\n";
    echo '<tr><td id="levela2" align="right">2</td><td id="levelb2"></td><td id="levelc2" align="right">200</td></tr>' . "\n";
    echo '<tr><td id="levela1" align="right">1</td><td id="levelb1"></td><td id="levelc1" align="right">100</td></tr>' . "\n";

    echo '<tr style="background:#408080"><td colspan="10">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>' . "\n";

    $letters = get_string('lettersall', 'game');
    for ($i = 1; $i <= $maxquestions; $i++) {
        $s = game_substr($letters, $i - 1, 1);
        echo '<tr>' . "\n";
        echo '<td style="background:' . s($colorback) . ';color:' . s($color1) . '">';
        echo '<input style="background:' . s($colorback) . ';color:' . s($color1) . ';" ';
        echo 'type="submit" name="btAnswer' . $i . '" value="' . s($s) . '" id="btAnswer' . $i . '" ';
        echo 'onmouseover="Highlite(' . $i . ');" onmouseout="Restore(' . $i . ');" onmousedown="OnSelectAnswer(' . $i . ');">';
        echo '</td>' . "\n";
        echo '<td style="background:' . s($colorback) . ';color:' . s($color1) . ';" width="100%">&nbsp; ';
        echo '<span id="lblAnswer' . $i . '" style="background:' . s($colorback) . ';color:' . s($color1) . '" ';
        echo 'onmouseover="Highlite(' . $i . ');" onmouseout="Restore(' . $i .
            ');" onmousedown="OnSelectAnswer(' . $i . ');"></span>';
        echo '</td>' . "\n";
        if ($i == 1) {
            echo '<td style="background:#408080" rowspan="' . (int)$maxquestions .
                '" colspan="3"><div id="info"></div></td>' . "\n";
        }
        echo '</tr>' . "\n";
    }

    echo '<tr><td colspan="10" style="background:#408080">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>' . "\n";
    echo '</table>' . "\n";
    echo '</body>' . "\n";
    echo '</html>' . "\n";
}
