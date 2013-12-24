<?php  // $Id: settings.php
/**
 * Form for creating and modifying a game 
 *
 * @package   game
 * @author    Vasilis Daloukas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once(dirname(__FILE__).'/lib.php');

    // General settings

    $settings->add(new admin_setting_configcheckbox('game/hidebookquiz',
        get_string('hidebookquiz', 'mod_game'), get_string('confighidebookquiz', 'mod_game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidecross',
        get_string('hidecross', 'mod_game'), get_string('confighidecross', 'mod_game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidehiddenpicture',
        get_string('hidehiddenpicture', 'mod_game'), get_string('confighidehiddenpicture', 'mod_game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidecryptex',
        get_string('hidecryptex', 'mod_game'), get_string('confighidecryptex', 'mod_game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidehangman',
        get_string('hidehangman', 'mod_game'), get_string('confighidehangman', 'mod_game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidemillionaire',
        get_string('hidemillionaire', 'mod_game'), get_string('confighidemillionaire', 'mod_game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidesnakes',
        get_string('hidesnakes', 'mod_game'), get_string('confighidesnakes', 'mod_game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidesudoku',
        get_string('hidesudoku', 'mod_game'), get_string('confighidesudoku', 'mod_game'), 0));

}
