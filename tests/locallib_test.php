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

namespace mod_game;

/**
 * Tests for the export helpers in locallib.php.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::game_create_zip
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Create a source directory with one file to archive.
     *
     * @return string The directory.
     */
    private function create_srcdir(): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/game/locallib.php');
        $srcdir = make_request_directory();
        file_put_contents($srcdir . '/index.htm', 'test');
        return $srcdir;
    }

    /**
     * A filename with directory traversal must not leave the course export directory.
     */
    public function test_create_zip_rejects_traversal(): void {
        global $CFG;
        $this->resetAfterTest();
        $srcdir = $this->create_srcdir();

        $filezip = game_create_zip($srcdir, 7, '../../traversal.zip');

        $this->assertFileDoesNotExist($CFG->dataroot . '/traversal.zip');
        $this->assertSame(realpath($CFG->dataroot . '/7/export'), dirname(realpath($filezip)));
    }

    /**
     * A plain filename is written to the course export directory.
     */
    public function test_create_zip_with_plain_filename(): void {
        global $CFG;
        $this->resetAfterTest();
        $srcdir = $this->create_srcdir();

        $filezip = game_create_zip($srcdir, 7, 'hangman.zip');

        $this->assertSame($CFG->dataroot . '/7/export/hangman.zip', $filezip);
        $this->assertFileExists($filezip);
    }
}
