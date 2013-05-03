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

$module->component = 'mod_game';  // Full name of the plugin (used for diagnostics)
$module->version   = 2013050305;  // The current module version (Date: YYYYMMDDXX)
$module->requires  = 2010112400;  // Requires Moodle 2.0
$module->cron      = 0;           // Period for cron to check this module (secs)
$module->release   = '3.5.3.5 (2013050305)';
