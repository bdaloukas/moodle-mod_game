<?php // $Id: version.php,v 1.49 2012/07/25 22:46:42 bdaloukas Exp $
/**
 * Code fragment to define the version of game
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author 
 * @version $Id: version.php,v 1.49 2012/07/25 22:46:42 bdaloukas Exp $
 * @package game
 **/

defined('MOODLE_INTERNAL') || die();

if( !isset( $plugin))
{
    $plugin = new stdClass;
    $useplugin = 0;
}else if( $plugin == 'mod_game')
{
    $plugin = new stdClass;
    $useplugin = 1;
}else
    $useplugin = 2;

$plugin->component = 'mod_game';  // Full name of the plugin (used for diagnostics)
$plugin->version   = 2015071503;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires  = 2010112400;  // Requires Moodle 2.0
$plugin->cron      = 0;           // Period for cron to check this module (secs)
$plugin->release   = '3.30.15.3';

if( $useplugin != 2)
    $module = $plugin;
