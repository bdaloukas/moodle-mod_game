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
 * The class CryptexDB loads/save the cryptex from/to database.
 *
 * @package mod_game
 * @copyright 2007 Vasilis Daloukas
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The class CryptexDB loads/save the cryptex from/to database.
 *
 * @package    mod_game
 * @copyright  2007 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class CryptexDB extends CrossDB {

    /** @var array Contains the words that cannot be created in the game. */
    protected $badwords;

    /**
     * Sets bad words.
     *
     * @param array $badwords
     *
     * @return the saved record
     */
    public function setbadwords( $badwords) {
        $this->badwords = $badwords;
    }

    /**
     * Save cryptex.
     *
     * @param stdClass $game
     * @param array $crossm
     * @param array $crossd
     * @param int $id
     * @param string $letters
     *
     * @return the saved record
     */
    public function savecryptex( $game, &$crossm, $crossd, $id, $letters) {
        global $USER;

        CrossDB::delete_records( $id);

        if ((CrossDB::savecross( $game, $crossm, $crossd, $id)) == false) {
            return false;
        }

        $crossm->id = $id;

        $newrec = new stdClass();
        $newrec->id = $id;
        $newrec->letters = $letters;

        if (!($cryptexid = game_insert_record( "game_cryptex", $newrec))) {
            print_error( 'Insert page: new page game_cryptex not inserted');
        }

        return $newrec;
    }

    /**
     * Compute letters.
     *
     * @param array $crossm
     * @param array $crossd
     * @param array $badwords
     *
     * @return the letters.
     */
    public function computeletters( $crossm, $crossd, $badwords) {
        $letters = '';
        $cols = $crossm->usedcols + 1;
        $letters = str_repeat('.', $crossm->usedcols).'#';
        $letters = str_repeat($letters, $crossm->usedrows);

        $freqs1 = array();  // If one letter appears three times there is three times in the array.
        $count1 = $count2 = 0;
        foreach ($crossd as $rec) {
            $pos = $rec->mycol - 1 + ($rec->myrow - 1) * $cols;
            $s = $rec->answertext;
            $len = game_strlen( $s);

            $a = array();
            for ($i = 0; $i < $len; $i++) {
                $a[] = game_substr( $s, $i, 1);
            }

            for ($i = 0; $i < $len; $i++) {
                $this->setchar( $letters, $pos,  $a[ $i]);
                $pos += ( $rec->horizontal ? 1 : $cols);

                $freqs1[ ++$count1] = $a[ $i];
                if ($i + 1 < $len) {
                    $freqs2[ ++$count2] = $a[ $i].$a[ $i + 1];
                }
            }
        }

        $len = game_strlen( $letters);
        $spaces = 0;
        for ($i = 0; $i < $len; $i++) {
            if (game_substr( $letters, $i, 1) == '.') {
                $spaces++;
            }
        }

        $originalletters = $letters;
        $step = 1;
        while ($spaces) {
            if ($step == 1) {
                $step = 2;
                $i = array_rand( $freqs1);
                $this->insertchar( $letters, $crossm->usedcols, $crossm->usedrows, $freqs1[ $i], $spaces);
            } else {
                $step = 1;
                $i = array_rand( $freqs2);
                $this->insertchars( $letters, $crossm->usedcols, $crossm->usedrows, $freqs2[ $i], $spaces);
            }
        }

        $retletters = "";
        for ($row = 0; $row < $crossm->usedrows; $row++) {
            $retletters .= game_substr( $letters, $cols * $row, ($cols - 1));
        }

        $this->repair_bad_words( $retletters, $freqs1, $originalletters, $badwords, $crossm);

        return $retletters;
    }

    /**
     * Displays the cryptex.
     *
     * @param int $cols
     * @param int $rows
     * @param string $letters
     * @param string $mask
     * @param boolean $showsolution
     * @param boolean $textdir
     */
    public function displaycryptex( $cols, $rows, $letters, $mask, $showsolution, $textdir) {
        echo "<table border=1 $textdir class=\"mod-game-cryptex\">";
        for ($row = 0; $row < $rows; $row++) {
            echo "<tr>";
            for ($col = 0; $col < $cols; $col++) {
                $pos = $cols * $row + $col;
                $c = game_substr( $letters, $pos, 1);
                $m = game_substr( $mask, $pos, 1);

                if ($showsolution and $m > '0') {
                    echo "<td><b><font color=red>".$c."</font></td>";
                } else if ( $m == '1') {
                    echo "<td><b><font color=red>".$c."</font></td>";
                } else {
                    echo "<td>".$c."</td>";
                }
            }
            echo "</tr>\r\n";
        }
        echo "</table>";
    }

    /**
     * Inserts a char.
     *
     * @param string $letters
     * @param int $cols
     * @param int $rows
     * @param string $char
     * @param int $spaces
     */
    public function insertchar( &$letters, $cols, $rows, $char, &$spaces) {
        $len = game_strlen( $letters);
        for ($i = 0; $i < $len; $i++) {
            if (game_substr( $letters, $i, 1) == '.') {
                $this->setchar( $letters, $i, $char);
                $spaces--;
                return;
            }
        }
    }

    /**
     * Inserts chars.
     *
     * @param string $letters
     * @param int $cols
     * @param int $rows
     * @param string $char
     * @param int $spaces
     */
    public function insertchars( &$letters, $cols, $rows, $char, &$spaces) {
        $len = game_strlen( $letters);
        for ($i = 0; $i < $len; $i++) {
            if (game_substr( $letters, $i, 1) == '.'  and game_substr( $letters, $i + 1, 1) == '.' ) {
                $this->setchar( $letters, $i, game_substr( $char, 0, 1));
                $this->setchar( $letters, $i + 1, game_substr( $char, 1, 1));
                $spaces -= 2;
                return true;
            }
            if (game_substr( $letters, $i, 1) == '.' and game_substr( $letters, $i + $cols + 1, 1) == '.' ) {
                $this->setchar( $letters, $i, game_substr( $char, 0, 1));
                $this->setchar( $letters, $i + $cols + 1, game_substr( $char, 1, 1));
                $spaces -= 2;
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the hash of a word.
     *
     * @param string $word
     *
     * @return the hash
     */
    public function gethash( $word) {
        $x = 37;
        $len = game_strlen( $word);

        for ($i = 0; $i < $len; $i++) {
            $x = $x xor ord( game_substr( $word, $i, 1));
        }

        return $x;
    }

    /**
     * Loads the cryptex from database.
     *
     * @param array $crossm
     * @param string $mask
     * @param int $corrects
     * @param string $language
     *
     * @return questions
     */
    public function loadcryptex( $crossm, &$mask, &$corrects, &$language) {
        global $DB;

        $questions = array();
        $corrects = array();

        $mask = str_repeat( '0', $crossm->usedcols * $crossm->usedrows);

        if ($recs = $DB->get_records( 'game_queries', array( 'attemptid' => $crossm->id))) {
            foreach ($recs as $rec) {
                if ($rec->questiontext == '') {
                    $rec->questiontext = ' ';
                }
                $key = $this->gethash( $rec->questiontext).'-'.$rec->answertext.'-'.$rec->id;
                $questions[ $key] = $rec;

                $word = $rec->answertext;
                $pos = $crossm->usedcols * ($rec->myrow - 1) + ($rec->mycol - 1);
                $len = game_strlen( $word);
                $found = ($rec->answertext == $rec->studentanswer);

                for ($i = 0; $i < $len; $i++) {
                    $c = ( $found ? '1' : '2');

                    if (game_substr( $mask, $pos,  1) != '1') {
                        game_setchar( $mask, $pos, $c);
                    }

                    $pos += ($rec->horizontal ? 1 : $crossm->usedcols);
                }

                if ($found) {
                    $corrects[ $rec->id] = 1;
                }

                if ($language == '') {
                    $language = game_detectlanguage( $rec->answertext);
                }
            }
            ksort( $questions);
        }

        return $questions;
    }

    /**
     * Calls the setwords of class Cross.
     *
     * @param string $answers
     * @param int $maxcols
     * @param array $reps
     *
     * @return Cross::setwords
     */
    public function setwords( $answers, $maxcols, $reps) {
        return Cross::setwords( $answers, $maxcols, $reps);
    }

    /**
     * Calls the computedata of class Cross.
     *
     * @param stdClass $crossm
     * @param stdClass $crossd
     * @param string $letters
     * @param int $minwords
     * @param int $maxwords
     * @param int $mtimelimit
     */
    public function computedata( &$crossm, &$crossd, &$letters, $minwords, $maxwords, $mtimelimit=3) {
        if (!cross::computedata( $crossm, $crossd, $letters, $minwords, $maxwords, $mtimelimit)) {
            return false;
        }

        $letters = $this->computeletters( $crossm, $crossd, $this->badwords);

        return true;
    }

    /**
     * Removed bad words.
     *
     * @param array $letters
     * @param array $freqs1
     * @param string $original
     * @param array $badwords
     * @param stdClass $crossm
     */
    public function repair_bad_words( &$letters, $freqs1, $original, $badwords, $crossm) {
        $cols = $crossm->usedcols;
        $rows = $crossm->usedrows;
        for (;;) {
            $ret = false;
            // Horizontaly.
            for ($y = 0; $y < $rows; $y++) {
                $ret |= $this->repair_bad_words_step( $letters, $freqs1, $original, $badwords, $cols, $rows, 0, $y, 1, 0);
            }
            // Verticaly.
            for ($x = 0; $x < $cols; $x++) {
                $ret |= $this->repair_bad_words_step( $letters, $freqs1, $original, $badwords, $cols, $rows, $x, 0, 0, 1);
            }

            // Diagonial 1.
            for ($x = 0; $x < $cols; $x++) {
                $ret |= $this->repair_bad_words_step( $letters, $freqs1, $original, $badwords, $cols, $rows, $x, 0, 1, 1);
            }

            // Diagonial 2.
            for ($x = 0; $x < $cols; $x++) {
                $ret |= $this->repair_bad_words_step( $letters, $freqs1, $original, $badwords, $cols, $rows, $x, $rows - 1, 1, -1);
            }

            // Diagonial 3.
            for ($x = 0; $x < $cols; $x++) {
                $ret |= $this->repair_bad_words_step( $letters, $freqs1, $original, $badwords, $cols, $rows, $x, 0, -1, 1);
            }

            // Diagonial 4.
            for ($x = 0; $x < $cols; $x++) {
                $ret |= $this->repair_bad_words_step( $letters, $freqs1, $original, $badwords, $cols, $rows, $cols - 1,
                    $rows - 1, -1, -1);
            }

            if ($ret == false) {
                break;
            }
        }
    }


    /**
     * Removes bad words horizontally.
     *
     * @param array $letters
     * @param array $freqs1
     * @param string $original
     * @param array $badwords
     * @param int $cols
     * @param int $rows
     * @param int $x
     * @param int $y
     * @param int $dx
     * @param int $dy
     */
    public function repair_bad_words_step( &$letters, $freqs1, $original, $badwords, $cols, $rows, $x, $y, $dx, $dy) {

        $xx = $x;
        $yy = $y;

        $found = false;
        $max = $cols > $rows ? $cols : $rows;
        $nl = $no = '';
        for ($i = 0; $i < $max; $i++) {
            if (($x >= $cols) || ($x < 0) || ($y >= $rows) || ($y < 0)) {
                break;
            }

            $nl .= game_substr( $letters, $x + $cols * $y, 1);
            $no .= game_substr( $original, $x + ($cols + 1) * $y, 1);

            $x += $dx;
            $y += $dy;
        }

        foreach ($badwords as $bad) {
            $pos = game_strpos( $nl, $bad);
            if ($pos === false) {
                continue;
            }
            $lenb = game_strlen( $bad);
            for ($i = 0; $i < $lenb; $i++) {
                if (game_substr( $no, $pos + $i, 1) != '.') {
                    continue;
                }

                $new = $freqs1[ array_rand( $freqs1)];
                $pos2 = $xx + ($i + $pos) * $dx + ($yy + $i * $dy + $pos * $dy) * $cols;
                $this->setchar( $letters, $pos2, $new);
                $found = true;
            }
        }

        return $found;
    }
}
