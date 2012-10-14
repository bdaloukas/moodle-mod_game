<?php  // $Id: default.php,v 1.2 2012/07/25 11:16:07 bdaloukas Exp $ 

////////////////////////////////////////////////////////////////////
/// Default class for report plugins                            
///                                                               
/// Doesn't do anything on it's own -- it needs to be extended.   
/// This class displays quiz reports.  Because it is called from 
/// within /mod/game/report.php you can assume that the page header
/// and footer are taken care of.
/// 
/// This file can refer to itself as report.php to pass variables 
/// to itself - all these will also be globally available.  You must 
/// pass "id=$cm->id" or q=$quiz->id", and "mode=reportname".
////////////////////////////////////////////////////////////////////

// Included by ../report.php

class game_default_report {

    function display($cm, $course, $game) {     /// This function just displays the report
        return true;
    }

    function print_header_and_tabs($cm, $course, $game, $reportmode="overview", $meta=""){
        global $CFG;
        /// Define some strings
        $strgames = get_string("modulenameplural", "game");
        $strgame  = get_string("modulename", "game");
        /// Print the page header

        if( function_exists( 'build_navigation')){
            $navigation = build_navigation('', $cm);
            echo $OUTPUT->heading( $course->shortname, $course->shortname, $navigation);
        }else{    
            echo $OUTPUT->heading(format_string($game->name), "",
                     "<a href=\"index.php?id=$course->id\">$strgames</a>
                      -> ".format_string($game->name),
                     '', $meta, true, update_module_button($cm->id, $course->id, $strgame), navmenu($course, $cm));
        }
    
        /// Print the tabs    
        $currenttab = 'reports';
        $mode = $reportmode;
		
        include('tabs.php');
    }
}

?>
