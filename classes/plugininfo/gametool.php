<?php
/**
 * Subplugin info class.
 *
 * @package   mod_game
 * @copyright 2014 Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_game\plugininfo;

use core\plugininfo\base;

defined('MOODLE_INTERNAL') || die();


class gametool extends base {
    public function is_uninstall_allowed() {
        return true;
    }
}
