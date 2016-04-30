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

require_once( $CFG->libdir.'/pagelib.php');
require_once( $CFG->dirroot.'/course/lib.php'); // Needed for some blocks.

define('PAGE_GAME_VIEW',   'mod-game-view');

page_map_class( PAGE_GAME_VIEW, 'page_game');

/**
 * Class that models the behavior of a game
 *
 * @author Jon Papaioannou
 * @package pages
 */

class page_game extends page_generic_activity {

    public function init_quick($data) {
        if (empty($data->pageid)) {
            print_error('Cannot quickly initialize page: empty course id');
        }
        $this->activityname = 'game';
        parent::init_quick($data);
    }

    public function print_header($title, $morebreadcrumbs = null, $navigation ='') {
        global $USER, $CFG;

        $this->init_full();
        $replacements = array(
            '%fullname%' => format_string($this->activityrecord->name)
        );
        foreach ($replacements as $search => $replace) {
            $title = str_replace($search, $replace, $title);
        }

        if ($this->courserecord->id == SITEID) {
            $breadcrumbs = array();
        } else {
            $breadcrumbs = array($this->courserecord->shortname => $CFG->wwwroot.'/course/view.php?id='.$this->courserecord->id);
        }

        $breadcrumbs[get_string('modulenameplural', 'game')] = $CFG->wwwroot.'/mod/game/index.php?id='.$this->courserecord->id;
        $breadcrumbs[format_string($this->activityrecord->name)]
            = $CFG->wwwroot.'/mod/game/view.php?id='.$this->modulerecord->id;

        if (!empty($morebreadcrumbs)) {
            $breadcrumbs = array_merge($breadcrumbs, $morebreadcrumbs);
        }

        if (empty($morebreadcrumbs) && $this->user_allowed_editing()) {
            $buttons = '<table><tr><td>'.
               update_module_button($this->modulerecord->id, $this->courserecord->id, get_string('modulename', 'game')).'</td>';
            if (!empty($CFG->showblocksonmodpages)) {
                $buttons .= '<td><form '.$CFG->frametarget.' method="get" action="view.php">'.
                    '<div>'.
                    '<input type="hidden" name="id" value="'.$this->modulerecord->id.'" />'.
                    '<input type="hidden" name="edit" value="'.($this->user_is_editing() ? 'off' : 'on').'" />'.
                    '<input type="submit" value="'.get_string($this->user_is_editing() ? 'blockseditoff' : 'blocksediton').'" />'.
                    '</div></form></td>';
            }
            $buttons .= '</tr></table>';
        } else {
            $buttons = '&nbsp;';
        }

        print_header($title, $this->courserecord->fullname, $navigation);
    }

    public function get_type() {
        return PAGE_GAME_VIEW;
    }
}
