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
        get_string('hidebookquiz', 'game'), get_string('confighidebookquiz', 'game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidecross',
        get_string('hidecross', 'game'), get_string('confighidecross', 'game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidehiddenpicture',
        get_string('hidehiddenpicture', 'game'), get_string('confighidehiddenpicture', 'game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidecryptex',
        get_string('hidecryptex', 'game'), get_string('confighidecryptex', 'game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidehangman',
        get_string('hidehangman', 'game'), get_string('confighidehangman', 'game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidemillionaire',
        get_string('hidemillionaire', 'game'), get_string('confighidemillionaire', 'game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidesnakes',
        get_string('hidesnakes', 'game'), get_string('confighidesnakes', 'game'), 0));

    $settings->add(new admin_setting_configcheckbox('game/hidesudoku',
        get_string('hidesudoku', 'game'), get_string('confighidesudoku', 'game'), 0));

    $settings->add(new admin_setting_configtext('game/hangmanimagesets', get_string('hangmanimagesets', 'game'),
            get_string('confighangmanimagesets', 'game'), 1, PARAM_INT));

}
