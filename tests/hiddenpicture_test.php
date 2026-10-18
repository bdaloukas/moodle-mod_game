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
 * Tests for the access check of the hidden picture image.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_game\hiddenpicture
 */
final class hiddenpicture_test extends \advanced_testcase {
    /**
     * Create a game with an attempt of a student.
     *
     * @return array The course module, the attempt and the students.
     */
    private function create_attempt(): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $game = $this->getDataGenerator()->create_module('game', ['course' => $course->id, 'kind' => 'hiddenpicture']);
        $owner = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $other = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $attemptid = $DB->insert_record('game_attempts', [
            'gameid' => $game->id,
            'userid' => $owner->id,
            'timestart' => time(),
            'timefinish' => 0,
        ]);
        $DB->insert_record('game_queries', [
            'attemptid' => $attemptid,
            'gameid' => $game->id,
            'userid' => $owner->id,
            'mycol' => 0,
            'attachment' => sha1('/1/mod_glossary/attachment/1/picture.png'),
        ]);
        return [$game->cmid, $attemptid, $owner, $other];
    }

    /**
     * A user cannot request the picture of an attempt of another user.
     */
    public function test_get_attachment_hash_rejects_foreign_attempt(): void {
        $this->resetAfterTest();
        [$cmid, $attemptid, $owner, $other] = $this->create_attempt();

        $this->setUser($other);
        $this->expectException(\dml_missing_record_exception::class);
        hiddenpicture::get_attachment_hash($cmid, $attemptid);
    }

    /**
     * The owner of the attempt gets the file hash stored for the attempt.
     */
    public function test_get_attachment_hash_returns_stored_hash(): void {
        $this->resetAfterTest();
        [$cmid, $attemptid, $owner, $other] = $this->create_attempt();

        $this->setUser($owner);
        $this->assertSame(
            sha1('/1/mod_glossary/attachment/1/picture.png'),
            hiddenpicture::get_attachment_hash($cmid, $attemptid)
        );
    }
}
