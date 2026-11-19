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
 * Creates an image dynamically.
 *
 * @package    mod_game
 * @copyright  2007 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

$id = required_param('id', PARAM_INT); // Game course module id.
$attemptid = required_param('id2', PARAM_INT); // Hidden Picture attempt id.

// Ignore legacy p, f, cells, cols and rows URL parameters. All picture state is trusted server data.
$state = \mod_game\hiddenpicture::get_picture_state($id, $attemptid);
$filenamenumbers = $CFG->dirroot . '/mod/game/hiddenpicture/numbers.png';
create_image($state, $filenamenumbers);

/**
 * Render the authenticated player's picture with server-derived reveal state.
 *
 * @param stdClass $state Authorized picture state.
 * @param string $filenamenumbers Number spritesheet location.
 */
function create_image($state, $filenamenumbers) {
    $file = get_file_storage()->get_file_by_hash($state->attachment);
    if (!$file || $file->is_directory()) {
        throw new moodle_exception('hiddenpicture_error', 'game');
    }
    $image = $file->get_imageinfo();
    if (!$image || !in_array($image['mimetype'], ['image/png', 'image/jpeg', 'image/gif'], true)) {
        throw new moodle_exception('hiddenpicture_error', 'game');
    }

    $imghandle = imagecreatefromstring($file->get_content());
    if ($imghandle === false) {
        throw new moodle_exception('hiddenpicture_error', 'game');
    }

    $imgnumbers = imagecreatefrompng($filenamenumbers);
    $sizenumbers = getimagesize($filenamenumbers);
    if ($imgnumbers === false || $sizenumbers === false) {
        imagedestroy($imghandle);
        throw new moodle_exception('hiddenpicture_error', 'game');
    }

    // Never allow an HTTP parameter to bypass the masking of an unfinished picture.
    $cols = $state->cols;
    $rows = $state->rows;
    $width = $image['width'];
    $height = $image['height'];
    $color = imagecolorallocate($imghandle, 100, 100, 100);
    $pos = 0;

    if (!$state->found) {
        for ($y = 0; $y < $rows; $y++) {
            for ($x = 0; $x < $cols; $x++) {
                $pos++;
                if (isset($state->visiblecells[$pos])) {
                    continue;
                }

                $x1 = round($x * $width / $cols);
                $y1 = round($y * $height / $rows);
                imagefilledrectangle(
                    $imghandle,
                    $x1,
                    $y1,
                    round($x1 + $width / $cols),
                    round($y1 + $height / $rows),
                    $color
                );

                if (isset($state->numberedcells[$pos])) {
                    shownumber(
                        $imghandle,
                        $imgnumbers,
                        $pos,
                        $x1,
                        $y1,
                        round($width / $cols),
                        round($height / $rows),
                        $sizenumbers
                    );
                }
            }
        }
    }

    // Generated pictures are specific to one user's current assessment state.
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Content-Type: ' . $image['mimetype']);
    switch ($image['mimetype']) {
        case 'image/png':
            imagepng($imghandle);
            break;
        case 'image/jpeg':
            imagejpeg($imghandle);
            break;
        case 'image/gif':
            imagegif($imghandle);
            break;
    }

    imagedestroy($imgnumbers);
    imagedestroy($imghandle);
}

/**
 * Show number.
 *
 * @param object $imghandle
 * @param object $imgnumbers
 * @param int $number
 * @param int $x1
 * @param int $y1
 * @param int $width
 * @param int $height
 * @param int $sizenumbers
 */
function shownumber($imghandle, $imgnumbers, $number, $x1, $y1, $width, $height, $sizenumbers) {
    if ($number < 10) {
        $dstx = round($x1 + $width / 3);
        $dsty = round($y1 + $height / 3);
        $srcx = round($number * $sizenumbers[0] / 10);
        $srcw = round($sizenumbers[0] / 10);
        $srch = $sizenumbers[1];
        $dstw = round($width / 10);
        $dsth = round($dstw * $srch / $srcw);
        imagecopyresized($imghandle, $imgnumbers, $dstx, $dsty, $srcx, 0, $dstw, $dsth, $srcw, $srch);
    } else {
        $number1 = floor($number / 10);
        $number2 = $number % 10;
        shownumber($imghandle, $imgnumbers, $number1, $x1 - $width / 20, $y1, $width, $height, $sizenumbers);
        shownumber($imghandle, $imgnumbers, $number2, $x1 + $width / 20, $y1, $width, $height, $sizenumbers);
    }
}
