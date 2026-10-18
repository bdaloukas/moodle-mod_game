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
 * Tests for the cleaning of glossary content in locallib.php.
 *
 * @package    mod_game
 * @category   test
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::game_filterglossary
 */
final class filterglossary_test extends \advanced_testcase {
    /**
     * Create a glossary entry.
     *
     * @param string $definition The definition.
     * @param int $trust The trust flag of the definition.
     * @return \stdClass The entry.
     */
    private function create_entry(string $definition, int $trust): \stdClass {
        global $CFG;
        require_once($CFG->dirroot . '/mod/game/locallib.php');
        $course = $this->getDataGenerator()->create_course();
        $glossary = $this->getDataGenerator()->create_module('glossary', ['course' => $course->id]);
        $entry = $this->getDataGenerator()->get_plugin_generator('mod_glossary')->create_content($glossary, [
            'concept' => 'Word',
            'definition' => $definition,
            'definitionformat' => FORMAT_HTML,
            'definitiontrust' => $trust,
        ]);
        $entry->contextid = \context_module::instance($glossary->cmid)->id;
        $entry->courseid = $course->id;
        return $entry;
    }

    /**
     * Script of an untrusted glossary definition must not reach the game page.
     */
    public function test_filterglossary_cleans_untrusted_definition(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enabletrusttext', 1);
        $entry = $this->create_entry('<b>Bold</b><img src="x" onerror="alert(1)"><script>alert(2)</script>', 0);

        $result = game_filterglossary($entry->definition, $entry->id, $entry->contextid, $entry->courseid);

        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringContainsString('<b>Bold</b>', $result);
    }

    /**
     * Formatting and files of a definition are kept; the markup of a trusted definition is not cleaned.
     */
    public function test_filterglossary_keeps_legitimate_content(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enabletrusttext', 1);
        $entry = $this->create_entry('<b>Bold</b> <img src="@@PLUGINFILE@@/a.png" alt="a">', 0);

        $result = game_filterglossary($entry->definition, $entry->id, $entry->contextid, $entry->courseid);

        $this->assertStringContainsString('<b>Bold</b>', $result);
        $this->assertStringContainsString(
            $CFG->wwwroot . '/pluginfile.php/' . $entry->contextid . '/mod_glossary/entry/' . $entry->id . '/a.png',
            $result
        );

        $trusted = $this->create_entry('<span onclick="x()">Trusted</span>', 1);
        $result = game_filterglossary($trusted->definition, $trusted->id, $trusted->contextid, $trusted->courseid);
        $this->assertStringContainsString('onclick="x()"', $result);
    }
}
