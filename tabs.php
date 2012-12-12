<?php  // $Id: tabs.php,v 1.7 2012/07/25 11:16:04 bdaloukas Exp $
/**
 * Sets up the tabs used by the game pages based on the users capabilites.
 *
 * @author Vasilis Daloukas.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package game
 */

if (empty($game)) {
   print_error('You cannot call this script in that way');
}
if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($cm)) {
    $cm = get_coursemodule_from_instance('game', $game->id);
}
if (!isset($course)) {
    $course = $DB->get_record('course', array( 'id' => $game->course));
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$tabs = array();
$row  = array();
$inactive = array();
$activated = array();

global $USER;


if (has_capability('mod/game:view', $context)) {
    $row[] = new tabobject('info', "{$CFG->wwwroot}/mod/game/view.php?q=$game->id", get_string('info', 'game'));
}
if (has_capability('mod/game:viewreports', $context)) {
//if( isteacher( $game->course, $USER->id)){
    $row[] = new tabobject('reports', "{$CFG->wwwroot}/mod/game/report.php?q=$game->id", get_string('results', 'game'));  
//}
}
if (has_capability('mod/game:preview', $context)) {
    $row[] = new tabobject('preview', "{$CFG->wwwroot}/mod/game/attempt.php?a=$game->id", get_string('preview', 'game'));
}
if (has_capability('mod/game:manage', $context)) {
//if( isteacher( $game->course, $USER->id)){
	global $USER;
	$sesskey = $USER->sesskey;
	$url = "{$CFG->wwwroot}/course/mod.php?update=$cm->id&return=true&sesskey=$sesskey";
    $row[] = new tabobject('edit', $url, get_string('edit'));
//}
}

if ($currenttab == 'info' && count($row) == 1) {
    // Don't show only an info tab (e.g. to students).
} else {
    $tabs[] = $row;
}

if ($currenttab == 'reports' and isset($mode)) {
    $inactive[] = 'reports';
    $activated[] = 'reports';
    
    $allreports = get_list_of_plugins("mod/game/report");
    $reportlist = array ('overview' /*, 'regrade' , 'grading' , 'analysis'*/);   // Standard reports we want to show first

    foreach ($allreports as $report) {
        if (!in_array($report, $reportlist)) {
            $reportlist[] = $report;
        }
    }

    $row  = array();
    $currenttab = '';
    foreach ($reportlist as $report) {
        $row[] = new tabobject($report, "{$CFG->wwwroot}/mod/game/report.php?q=$game->id&amp;mode=$report",
                                get_string($report, 'game'));
        if ($report == $mode) {
            $currenttab = $report;
        }
    }
    $tabs[] = $row;
}

if ($currenttab == 'edit' and isset($mode)) {
    $inactive[] = 'edit';
    $activated[] = 'edit';

    $row  = array();
    $currenttab = $mode;

    $strgames = get_string('modulenameplural', 'game');
    $strgame = get_string('modulename', 'game');
    $streditinggame = get_string("editinga", "moodle", $strgame);
    $strupdate = get_string('updatethis', 'moodle', $strgame);
    $row[] = new tabobject('editq', "{$CFG->wwwroot}/mod/game/edit.php?gameid=$game->id", $strgame, $streditinggame);
    questionbank_navigation_tabs($row, $context, $course->id);
    $tabs[] = $row;
}

print_tabs($tabs, $currenttab, $inactive, $activated);

?>
