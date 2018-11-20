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
 * Privacy Subsystem helper for mod_game.
 *
 * @package    mod_game
 * @copyright 2018 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_game\privacy;

use \core_privacy\local\request\writer;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\deletion_criteria;
use \core_privacy\local\metadata\collection;
use \core_privacy\manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/game/lib.php');
require_once($CFG->dirroot . '/mod/game/locallib.php');

/**
 * Privacy Subsystem implementation for mod_game.
 *
 * @copyright 2018 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Determine the subcontext for the specified game attempt.
     *
     * @param   \stdClass       $attempt    The attempt data retrieved from the database.
     * @param   \stdClass       $user       The user record.
     * @return  \array                      The calculated subcontext.
     */
    public static function get_game_attempt_subcontext(\stdClass $attempt, \stdClass $user) {
        $subcontext = [
            get_string('attempts', 'mod_game'),
        ];
        if ($attempt->userid != $user->id) {
            $subcontext[] = fullname($user);
        }
        $subcontext[] = $attempt->attempt;

        return $subcontext;
    }
}
