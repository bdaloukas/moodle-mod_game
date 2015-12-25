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
 * Check translation of module Game
 *
 * @author 
 * @version $Id: translate.php,v 1.10 2012/07/25 23:07:43 bdaloukas Exp $
 * @package game
 **/
require( "../../config.php");
require( 'locallib.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html  dir="ltr" lang="el" xml:lang="el" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Μάθημα: game23</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<?php

$moodle19dir = '/var/www/moodle19';
$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
if (!has_capability('mod/game:viewreports', $context)) {
    error( get_string( 'only_teachers', 'game'));
}

$langname = array();
$langname['ca'] = 'Català (ca)';
$langname['de'] = 'Deutsch (de)';
$langname['el'] = 'Ελληνικά (el)';
$langname['en'] = 'English (en)';
$langname['es'] = 'Español - Internacional (es)';
$langname['eu'] = 'Euskara (eu)';
$langname['fr'] = 'Français (fr)';
$langname['he'] = 'ית (he';
$langname['hr'] = 'Hrvatski (hr)';
$langname['it'] = 'Italiano (it)';
$langname['lt'] = 'Lietuviškai (lt)';
$langname['nl'] = 'Nederlands (nl)';
$langname['no'] = 'Norsk - bokmål (no)';
$langname['pl'] = 'Polski (pl)';
$langname['ru'] = 'Русский (ru)';
$langname['sq'] = 'Shqip (sq)';
$langname['uk'] = 'Українська (uk)';
$langname['pt_br'] = 'Português - Brasil (pt_br)';
$langname['zh_cn'] = '简体中文 (zh_cn)';
ksort( $langname);
$a = read_dir( $CFG->dirroot.'/mod/game', 'php');
$strings = array();
$files = array();
foreach ($a as $file) {
    $files[] = $file;
}
sort( $files);

foreach ($files as $file) {
    readsourcecode( $file, $strings);
}

$strings[ 'game:attempt'] = '/db/access.php * game:attempt';
$strings[ 'game:deleteattempts'] = '/db/access.php * game:deleteattempts';
$strings[ 'game:grade'] = '/db/access.php * game:grade';
$strings[ 'game:manage'] = '/db/access.php * game:manage';
$strings[ 'game:manageoverrides'] = '/db/access.php * game:manageoverrides';
$strings[ 'game:preview'] = '/db/access.php * game:preview';
$strings[ 'game:reviewmyattempts'] = '/db/access.php * game:reviewmyattempts';
$strings[ 'game:view'] = '/db/access.php * game:view';
$strings[ 'game:viewreports'] = '/db/access.php * game:viewreports';
$strings[ 'pluginname'] = 'index.php * pluginname';
$strings[ 'pluginadministration'] = 'index.php * pluginadministration';
$strings[ 'convertfrom'] = 'locallib.php * convertfrom';
$strings[ 'convertto'] = 'locallib.php * convertto';
$strings[ 'helpbookquiz'] = 'index.php * helpbookquiz';
$strings[ 'helphangman'] = 'index.php * helphangman';
$strings[ 'helpcross'] = 'index.php * helpcross';
$strings[ 'helpcryptex'] = 'index.php * helpcryptex';
$strings[ 'helpbookquiz'] = 'index.php * helpbookquiz';
$strings[ 'helpsudoku'] = 'index.php * helpsudoku';
$strings[ 'helphiddenpicture'] = 'index.php * helphiddenpicture';
$strings[ 'helpsnakes'] = 'index.php * helpsnakes';
$strings[ 'helpmillionaire'] = 'index.php * helpmillionaire';

$en = readlangfile( 'en', $header);
unset( $en[ 'convertfrom']);
unset( $en[ 'convertto']);
$langs = computelangs();
$sum = array();
$destdir = game_export_createtempdir();
foreach ($langs as $lang) {
    if ($lang != 'en' and $lang != 'CVS' and strpos( $lang, '_utf8') == 0 and strpos( $lang, '_uft8') == 0) {
        computediff( $en, $lang, $strings, $langname, $sum, $destdir, $untranslated);
        $auntranslated[ $lang] = $untranslated;
    }
}
$filenotranslation = 'game_lang_no_translation.zip';
$filezip = game_create_zip( $destdir, $COURSE->id, $filenotranslation);
remove_dir( $destdir);
$ilang = 0;
echo '<table border=1>';
echo "<tr><td><b>Counter</td><td><b>Language</td><td><b>Missing words</td><td><b>Percent completed</td></tr>";
foreach ($sum as $s) {
    echo '<tr><td>'.(++$ilang).'</td>'.$s."\r\n";
}
echo "</table>";

echo "<br><a href=\"{$CFG->wwwroot}/file.php/1/export/$filenotranslation\">Words that is not translated yet in each language</a>";

// Find missing strings on en/game.php.
$not = array();
$prevfile = '';
foreach ($strings as $info) {
    $pos = strpos( $info, '*');
    $name = substr( $info, $pos + 2);
    $file = substr( $info, 0, $pos - 1);
    if (substr( $file, 0, 1) == '/') {
        $file = substr( $file, 1);
    }
    if ($file != $prevfile) {
        $prevfile = $file;
    }
    if (!array_key_exists( $name, $en)) {
        $not[ $info] = $info;
    }
}
$oldfile = '';
unset( $not[ 'tabs.php * $report']);
unset( $not[ 'mod_form.php * game_\'.$gamekin']);
unset( $not[ 'mod.html * game_\'.$form->gamekin']);
unset( $not[ '/report/overview/report.php * fullname\')."\t".get_string(\'startedon']);
unset( $not[ '/hangman/play.php * hangman_correct_\'.$ter']);

if (count( $not)) {
    echo "<br><br>Missing strings on en/game.php<br>";
}
foreach ($not as $key => $value) {
    $pos = strpos( $value, '*');
    $file = trim( substr( $value, 0, $pos));
    $key = trim( substr( $value, $pos + 1));
    if ($key == 'convertfrom' or $key == 'convertto') {
        continue;
    }

    if (substr( $file, 0, 1) == '/') {
        $file = substr( $file, 1);
    }
    if ($file != $oldfile) {
        echo "<br>//$file<br>\r\n";
        $fileold = $file;
    }
    echo '$'."string[ '$key'] = \"\";<br>";
}

// Finds translations to en that are not used now.
$ret = '';
foreach ($en as $key => $value) {
    if (!array_key_exists( $key, $strings)) {
        $ret .= "$key = $value<br>";
    }
}
if ($ret != '') {
    echo '<hr><b><center>Translations that are not used</center></b><br>'.$ret;
}

// Creates the zip files of translations.
$destdir = game_export_createtempdir();
sort( $strings);
foreach ($langname as $lang => $name) {
    $stringslang = readlangfile( $lang, $header);
    if (empty($stringlang)) {
        continue;
    }
    $ret = '';

    foreach ($stringslang as $key => $value) {
        if (!array_key_exists( $key, $en)) {
            if ($key != 'convertfrom' and $key != 'convertto') {
                $ret .= '<br>'.$key."\r\n";
            }
        }
    }
    if ($ret != '') {
        echo '<hr><b><center>Unused translation for lang '.$lang.'</center></b><br>'.substr( $ret, 4)."\r\n";
    }

    $ret = $header;
    foreach ($strings as $info) {
        $pos = strpos( $info, '*');
        $name = substr( $info, $pos + 2);
        $file = substr( $info, 0, $pos - 1);
        if (substr( $file, 0, 1) == '/') {
            $file = substr( $file, 1);
        }
        if ($file != $prevfile) {
            $prevfile = $file;
            $ret .= "\r\n//".$file."\r\n";
        }
        if (array_key_exists( $name, $stringslang)) {
            $ret .= '$string'."[ '$name'] = ".$stringslang[ $name]."\r\n";
        }
    }
    if ($lang != 'en') {
        $untranslated = $auntranslated[ $lang];
        if ($untranslated != '') {
            $ret .= "\r\n//Untranslated\r\n".$untranslated;
        }
    }
    mkdir( $destdir.'/'.$lang);
    $file = $destdir.'/'.$lang.'/game.php';
    file_put_contents( $file, $ret);
}

$filesorted = 'game_lang_sorted.zip';
$filezip = game_create_zip( $destdir, $COURSE->id, $filesorted);
remove_dir( $destdir);
echo "<br><a href=\"{$CFG->wwwroot}/file.php/1/export/$filesorted\">Sorted translation files</a>";

asort( $en);
$sprev = '';
$keyprev = '';
$ret = '';
foreach ($en as $key => $s) {
    if ($s == $sprev) {
        $ret .= "<tr><td>$key</td><td>$keyprev</td><td>$s</td></tr>\r\n";
    }
    $sprev = $s;
    $keyprev = $key;
}
if ($ret != '') {
    echo '<br><center><b>Same translations<center></b><br>';
    echo '<table border=1><tr><td><b>Word1</td><td><b>Word2</td><td><b>Translation</td></tr>'.$ret.'</table>';
}

function readlangfile( $lang, &$header) {
    global $CFG;

    $file = $CFG->dirroot.'/mod/game/lang/'.$lang.'/game.php';
    if (!is_file($file)) {
        return null;
    }

    $a = array();

    $lines = file( $file);
    $header = '';
    $endofheader = false;
    foreach ($lines as $line) {
        if ($endofheader == false) {
            if (strpos( $line, '//') === false) {
                $endofheader = true;
            } else {
                $header .= $line;
            }
        }
        if (splitlangdefinition($line, $name, $trans)) {
            $a[ $name] = $trans;
        }
    }

    if ($lang != 'en') {
        readlangfile_repairmoodle19( $lang, $a);
    }

    return $a;
}

function splitlangdefinition($line, &$name, &$trans) {
    $pos1 = strpos( $line, '=');
    if ($pos1 == 0) {
        return false;
    }

    $pos2 = strpos( $line, '//');
    if ($pos2 != 0 or substr( $line, 0, 2) == '//') {
        if ($pos2 < $pos1) {
            return false;   // Commented line.
        }
    }

    $name = trim(substr( $line, 0, $pos1 - 1));
    $trans = trim(substr( $line, $pos1 + 1));

    $pos = strpos( $name, '\'');
    if ($pos) {
        $name = substr( $name, $pos + 1);
        $pos = strrpos( $name, '\'');
        $name = substr( $name, 0, $pos);
    }

    return true;
}

function readsourcecode( $file, &$strings) {
    global $CFG;

    $lines = file( $file);
    foreach ($lines as $line) {
        parseline( $strings, $line, $file);
    }

    return $strings;
}

function parseline( &$strings, $line, $filename) {
    global $CFG;

    $filename = substr( $filename, strlen( $CFG->dirroot.'/mod/game/'));
    if (strpos($filename, '/')) {
        $filename = '/'.$filename;
    }
    $pos0 = 0;
    for (;;) {
        $pos = strpos( $line, 'get_string', $pos0);
        if ($pos == false) {
            $pos = strpos( $line, 'print_string', $pos0);
        }
        if ($pos === false) {
            break;
        }
        $pos1 = strpos( $line, '(', $pos);
        $pos2 = strpos( $line, ',', $pos);
        $pos3 = strpos( $line, ')', $pos);
        if ($pos1 == 0 or $pos2 == 0 or $pos3 == 0) {
            $pos0 = $pos + 1;
            continue;
        }
        $name = gets( substr( $line, $pos1 + 1, $pos2 - $pos1 - 1));
        $file = gets( substr( $line, $pos2 + 1, $pos3 - $pos2 - 1));

        if ($file == 'game') {
            if (!array_key_exists( $name, $strings)) {
                $strings[ $name] = $filename.' * '.$name;
            }
        } else {
            $pos4 = strpos($file, '\'');
            if ($pos4) {
                $file = substr( $file, 0, $pos4);
            }
            $pos4 = strpos($file, '"');
            if ($pos4) {
                $file = substr( $file, 0, $pos4);
            }

            if ($file == 'game') {
                if (!array_key_exists( $name, $strings)) {
                    $strings[ $name] = $filename.' * '.$name;
                }
            }
        }

        $pos0 = $pos + 1;
    }
}

function gets( $s) {
    $s = trim( $s);
    if (substr( $s, 0, 1) == '"') {
        $s = substr( $s, 1, -1);
    }
    if (substr( $s, 0, 1) == '\'') {
        $s = substr( $s, 1, -1);
    }

    return $s;
}

function read_dir($dir, $ext) {
    if ($ext != '') {
        $ext = '.' .$ext;
    }
    $len = strlen( $ext);

    $a = array( $dir);
    $ret = array();
    while (count( $a)) {
        $dir = array_pop( $a);
        if (strpos( $dir, '/lang/') != 0) {
            continue;
        }

        $d = dir($dir);
        while (false !== ($entry = $d->read())) {
            if ($entry != '.' && $entry != '..') {
                $entry = $dir.'/'.$entry;
                if (is_dir($entry)) {
                    $a[] = $entry;
                } else {
                    if ($len == 0) {
                        $ret[] = $entry;
                    } else if (substr( $entry, -$len) == $ext) {
                        $ret[] = $entry;
                    }
                }
            }
        }
        $d->close();
    }

    return $ret;
}

function computelangs() {
    global $CFG;

    $dir = $CFG->dirroot.'/mod/game/lang';
    $ret = array();
    $d = dir( $dir);
    while (false !== ($entry = $d->read())) {
        if ($entry != '.' and $entry != '..') {
            if (is_dir($dir.'/'.$entry)) {
                $ret[] = $entry;
            }
        }
    }

    return $ret;
}

function computediff( $en, $lang, $strings, $langname, &$sum, $outdir, &$untranslated) {
    global $CFG;
    $untranslated = '';
    $counten = count($en);
    $trans = readlangfile( $lang, $header);
    foreach ($trans as $s => $key) {
        unset( $en[ $s]);
    }

    $file = $CFG->dirroot.'/mod/game/lang/'.$lang.'/game.php';
    $lines = file( $file);
    $count = 0;
    $s = '';
    foreach ($lines as $line) {
        $s .= $line;
        if (++$count >= 3) {
            break;
        }
    }

    $a = array();
    foreach ($en as $name => $t) {
        if (array_key_exists( $name, $strings)) {
            $file = $strings[ $name];
        } else {
            $file = '';
        }
        $t = strip_tags( $t);
        $a[ $file.' * '.$name] = '$'."string[ '$name'] = $t\r\n";
    }
    ksort( $a);

    if (array_key_exists( $lang, $langname)) {
        $langprint = $langname[ $lang];
    } else {
        $langprint = $lang;
    }

    $sum[] = "<td>$langprint</td><td><center>".count($a)."</td><td><center>".
        round(100 * ($counten - count($a)) / $counten, 0)." %</td>";
    $prevfile = '';
    foreach ($a as $key => $line) {
        $pos = strpos( $key, '*');
        $file = trim( substr( $key, 0, $pos - 1));
        if (substr( $file, 0, 1) == '/') {
            $file = substr( $file, 1);
        }
        if ($file != $prevfile) {
            $s .= "\r\n//$file\r\n";
            $prevfile = $file;
        }
        $s .= $line;
        $untranslated .= "//$prevfile ".$line;
    }
    $file = $outdir.'/'.$lang.'.php';
    file_put_contents( $file, $s);
}

// Copies the translation from Moodle 19.
function readlangfile_repairmoodle19( $lang, &$stringslang) {
    global $moodle19dir;

    if ($moodle19dir == '') {
        return;
    }

    $file = $moodle19dir.'/mod/game/lang/'.$lang.'_utf8/game.php';

    $a = array();

    $lines = file( $file);
    foreach ($lines as $line) {
        if (splitlangdefinition($line, $name, $trans)) {
            if (!array_key_exists( $name, $stringslang)) {
                $stringslang[ $name] = $trans;
            }
        }
    }
}
