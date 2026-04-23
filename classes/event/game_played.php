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
 * The mod_game chapter viewed event.
 *
 * @package    mod_game
 * @copyright  2014 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_game\event;

use coding_exception;
use context_module;
use core\event\base;
use core\exception\moodle_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_game chapter viewed event class.
 *
 * @package    mod_game
 * @since      Moodle 2.6
 * @copyright  2014 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class game_played extends base {
    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '$this->userid' played the game with id '$this->objectid' for the game with the " .
                "course module id '$this->contextinstanceid'.";
    }

    /**
     * Create instance of event.
     *
     * @param stdClass $game
     * @param context_module $context
     * @return base
     * @throws coding_exception
     * @since Moodle 2.7
     *
     */
    public static function played(stdClass $game, context_module $context): base {
        $data = ['context' => $context, 'objectid' => $game->id];
        $event = self::create($data);
        $event->add_record_snapshot('game', $game);
        return $event;
    }


    /**
     * Return localised event name.
     *
     * @return string
     * @throws coding_exception
     */
    public static function get_name(): string {
        return get_string('eventgameviewed', 'mod_game');
    }

    /**
     * Get URL related to the action.
     *
     * @return moodle_url
     * @throws moodle_exception
     */
    public function get_url(): moodle_url {
        return new moodle_url('/mod/game/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'game';
    }
}
