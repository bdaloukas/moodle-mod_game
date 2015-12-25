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
 * This is a very rough importer for powerpoint slides
 * Export a powerpoint presentation with powerpoint as html pages
 * Do it with office 2002 (I think?) and no special settings
 * Then zip the directory with all of the html pages 
 * and the zip file is what you want to upload
 * 
 * The script supports book and lesson.
 *
 * @version $Id: importppt.php,v 1.3 2012/07/25 11:16:05 bdaloukas Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);         // Course Module ID.
$pageid = optional_param('pageid', '', PARAM_INT); // Page ID.
global $matches;

if (! $cm = get_coursemodule_from_id('lesson', $id)) {
    print_error('Course Module ID was incorrect');
}

if (! $course = $DB->get_record('course', array( 'id' => $cm->course))) {
    print_error('Course is misconfigured');
}

// Allows for adaption for multiple modules.
if (!$modname = $DB->get_field('modules', 'name', array( 'id' => $cm->module))) {
    print_error('Could not find module name');
}

if (! $mod = $DB->get_record($modname, array( "id" => $cm->instance))) {
    print_error('Course module is incorrect');
}

require_login($course->id, false);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/lesson:edit', $context);

$strimportppt = get_string("importppt", "lesson");
$strlessons = get_string("modulenameplural", "lesson");

echo $OUTPUT->heading("$strimportppt", " $strimportppt",
    "<a href=\"index.php?id=$course->id\">$strlessons</a>".
    " -> <a href=\"{$CFG->wwwroot}/mod/$modname/view.php?id=$cm->id\">".format_string($mod->name, true)."</a>-> $strimportppt");

if ($form = data_submitted()) {   // Filename.
    if (empty($_FILES['newfile'])) {      // File was just uploaded.
        notify(get_string("uploadproblem") );
    }

    if ((!is_uploaded_file($_FILES['newfile']['tmp_name']) or $_FILES['newfile']['size'] == 0)) {
        notify(get_string("uploadnofilefound") );
    } else {
        // Valid file is found.
        if ($rawpages = readdata($_FILES, $course->id, $modname)) {
            // First try to reall all of the data in.
            // parse all the html files into objects.
            $pageobjects = extract_data($rawpages, $course->id, $mod->name, $modname);
            clean_temp(); // All done with files so dump em.

            $modcreateobjects = $modname.'_create_objects';
            $modsaveobjects = $modname.'_save_objects';

            // Function to preps the data to be sent to DB.
            $objects = $modcreateobjects($pageobjects, $mod->id);

            if (! $modsaveobjects($objects, $mod->id, $pageid)) {
                // Sends it to DB.
                print_error( 'could not save');
            }
        } else {
            print_error('could not get data');
        }

        echo "<hr>";
        print_continue("{$CFG->wwwroot}/mod/$modname/view.php?id=$cm->id");
        echo $OUTPUT->footer($course);
        exit;
    }
}

// Print upload form.
print_heading_with_help($strimportppt, "importppt", "lesson");

echo $OUTPUT->box_start('center');
echo "<form id=\"theform\" enctype=\"multipart/form-data\" method=\"post\">";
echo "<input type=\"hidden\" name=\"id\" value=\"$cm->id\" />\n";
echo "<input type=\"hidden\" name=\"pageid\" value=\"$pageid\" />\n";
echo "<table cellpadding=\"5\">";

echo "<tr><td align=\"right\">";
print_string("upload");
echo ":</td><td>";
echo "<input name=\"newfile\" type=\"file\" size=\"50\" />";
echo "</td></tr><tr><td>&nbsp;</td><td>";
echo "<input type=\"submit\" name=\"save\" value=\"".get_string("uploadthisfile")."\" />";
echo "</td></tr>";

echo "</table>";
echo "</form>";
echo $OUTPUT->box_end();

echo $OUTPUT->footer($course);

// START OF FUNCTIONS.

/* this function expects a zip file to be uploaded.  Then it parses
 * outline.htm to determine the slide path.  Then parses each
 * slide to get data for the content
 */
function readdata($file, $courseid, $modname) {

    global $CFG;

    // Create an upload directory in temp.
    make_upload_directory('temp/'.$modname);

    $base = $CFG->dataroot."/temp/$modname/";

    $zipfile = $_FILES["newfile"]["name"];
    $tempzipfile = $_FILES["newfile"]["tmp_name"];

    // Create our directory.
    $pathparts = pathinfo($zipfile);
    // Take off the extension.
    $dirname = substr($zipfile, 0, strpos($zipfile, '.'.$pathparts['extension']));
    if (!file_exists($base.$dirname)) {
        mkdir($base.$dirname);
    }

    // Move our uploaded file to temp/lesson.
    move_uploaded_file($tempzipfile, $base.$zipfile);

    // Unzip it!
    unzip_file($base.$zipfile, $base, false);

    $base = $base.$dirname;  // Update the base.

    // This is the file where we get the names of the files for the slides (in the correct order too).
    $outline = $base.'/outline.htm';

    $pages = array();

    if (file_exists($outline) and is_readable($outline)) {
        $outlinecontents = file_get_contents($outline);
        $filenames = array();
        // This gets all of our files names.
        preg_match_all("/javascript:GoToSld\('(.*)'\)/", $outlinecontents, $filenames);

        // File $pages with the contents of all of the slides.
        foreach ($filenames[1] as $file) {
            $path = $base.'/'.$file;
            if (is_readable($path)) {
                $pages[$path] = file_get_contents($path);
            } else {
                return false;
            }
        }
    } else {
        // Cannot find the outline, so grab all files that start with slide.
        $dh  = opendir($base);
        while (false !== ($file = readdir($dh))) {  // Read throug the directory.
            if ('slide' == substr($file, 0, 5)) {
                // Check for name (may want to check extension later).
                $path = $base.'/'.$file;
                if (is_readable($path)) {
                    $pages[$path] = file_get_contents($path);
                } else {
                    return false;
                }
            }
        }

        ksort($pages);  // Order them by file name.
    }

    if (empty($pages)) {
        return false;
    }

    return $pages;
}

/* This function attempts to extract the content out of the slides
 * the slides are ugly broken xml.  and the xml is broken... yeah...
 */
function extract_data($pages, $courseid, $lessonname, $modname) {

    global $CFG;
    global $matches;

    $extratedpages = array();

    // Directory for images.
    make_mod_upload_directory($courseid); // Make sure moddata is made.
    // We store our images in a subfolder in here.
    make_upload_directory($courseid.'/moddata/'.$modname, false);

    $imagedir = $CFG->dataroot.'/'.$courseid.'/moddata/'.$modname;

    if ($CFG->slasharguments) {
        $imagelink = $CFG->wwwroot.'/file.php/'.$courseid.'/moddata/'.$modname;
    } else {
        $imagelink = $CFG->wwwroot.'/file.php?file=/'.$courseid.'/moddata/'.$modname;
    }

    // Try to make a unique subfolder to store the images.
    $lessonname = str_replace(' ', '_', $lessonname); // Get rid of spaces.
    $i = 0;
    while (true) {
        if (!file_exists($imagedir.'/'.$lessonname.$i)) {
            // Ok doesnt exist so make the directory and update our paths.
            mkdir($imagedir.'/'.$lessonname.$i);
            $imagedir = $imagedir.'/'.$lessonname.$i;
            $imagelink = $imagelink.'/'.$lessonname.$i;
            break;
        }
        $i++;
    }

    foreach ($pages as $file => $content) {
        /* to make life easier on our preg_match_alls, we strip out all tags except
         * for div and img (where our content is).  We want div because sometimes we
         * can identify the content in the div based on the div's class
         */

        $tags = '<div><img>'; // Should also allow <b><i>.
        $string = strip_tags($content, $tags);

        $matches = array();
        /* this will look for a non nested tag that is closed
         * want to allow <b><i>(maybe more) tags but when we do that
         * the preg_match messes up.
         */
        preg_match_all("/(<([\w]+)[^>]*>)([^<\\2>]*)(<\/\\2>)/", $string, $matches);

        $pathparts = pathinfo($file);
        // Get rid of the extension.
        $file = substr($pathparts['basename'], 0, strpos($pathparts['basename'], '.'));

        $imgs = array();
        // This preg matches all images.
        preg_match_all("/<img[^>]*(src\=\"(".$file."\_image[^>^\"]*)\"[^>]*)>/i", $string, $imgs);

        // Start building our page.
        $page = new stdClass;
        $page->title = '';
        $page->contents = array();
        $page->images = array();
        $page->source = $pathparts['basename']; // Need for book only.

        /* This foreach keeps the style intact.
         * Found it doesn't help much.  But if you want back uncomment
         * this foreach and uncomment the line with the comment imgstyle in it.
         * Also need to comment out
         * the $page->images[]... line in the next foreach
         */
        foreach ($imgs[2] as $img) {
            copy($pathparts['dirname'].'/'.$img, $imagedir.'/'.$img);
            // Comment out this line if you are using the above foreach loop.
            $page->images[] = "<img src=\"$imagelink/$img\" title=\"$img\" />";
        }
        for ($i = 0; $i < count($matches[1]); $i++) { // Go through all of our div matches.
            $class = isolate_class($matches[1][$i]); // First step in isolating the class.

            // Check for any static classes.
            switch ($class) {
                case 'T':  // Class T is used for Titles.
                    $page->title = $matches[3][$i];
                    break;
                // I would guess that all bullet lists would start with B then go to B1, B2, etc.
                case 'B':
                    // B1-B4 are just insurance, should just hit B and all be taken care of.
                case 'B1':
                case 'B2':
                case 'B3':
                case 'B4':
                    // This is a recursive function that will grab all the bullets and rebuild the list in html.
                    $page->contents[] = build_list('<ul>', $i, 0);
                    break;
                default:
                    if ($matches[3][$i] != '&#13;') {  // Odd crap generated... sigh.
                        if (substr($matches[3][$i], 0, 1) == ':') {// Check for leading : ..hate MS .
                            $page->contents[] = substr($matches[3][$i], 1);  // Get rid of :.
                        } else {
                            $page->contents[] = $matches[3][$i];
                        }
                    }
                    break;
            }
        }

        // Add the page to the array.
        $extratedpages[] = $page;

    } // End $pages foreach loop.

    return $extratedpages;
}

// A recursive function to build a html list.
function build_list($list, &$i, $depth) {
    global $matches; // Not sure why I global this...

    while ($i < count($matches[1])) {
        $class = isolate_class($matches[1][$i]);

        if (strstr($class, 'B')) {  // Make sure we are still working with bullet classes.
            if ($class == 'B') {
                $thisdepth = 0;  // Calling class B depth 0.
            } else {
                // Set the depth number.  So B1 is depth 1 and B2 is depth 2 and so on.
                $thisdepth = substr($class, 1);
                if (!is_numeric($thisdepth)) {
                    print_error( 'Depth not parsed!');
                            }
            if ($thisdepth < $depth) {
                // We are moving back a level in the nesting.
                break;
            }
            if ($thisdepth > $depth) {
                // We are moving in a lvl in nesting.
                $list .= '<ul>';
                $list = build_list($list, $i, $thisdepth);
                // Once we return back, should go to the start of the while.
                continue;
            }
            // No depth changes, so add the match to our list.
            if ($cleanstring = ppt_clean_text($matches[3][$i])) {
                $list .= '<li>'.ppt_clean_text($matches[3][$i]).'</li>';
            }
            $i++;
        } else {
            // Not a B class, so get out of here...
            break;
        }
    }

    // End the list and return it.
    $list .= '</ul>';
    return $list;
}

// Given an html tag, this function will.
function isolate_class($string) {
    if ($class = strstr($string, 'class=')) {
        // First step in isolating the class.
        // This gets rid of <div blawblaw class=  there are no "" or '' around the clas name   ...sigh...
        $class = substr($class, strpos($class, '=') + 1);
        if (strstr($class, ' ')) {
            // Spaces found, so cut off everything off after the first space.
            return substr($class, 0, strpos($class, ' '));
        } else {
            // No spaces so nothing else in the div tag, cut off the >.
            return substr($class, 0, strpos($class, '>'));
        }
    } else {
        // No class defined in the tag.
        return '';
    }
}

// This function strips off the random chars that ppt puts infront of bullet lists.
function ppt_clean_text($string) {
    $chop = 1; // Default: just a single char infront of the content.

    // Look for any other crazy things that may be infront of the content.
    if (strstr($string, '&lt;') and strpos($string, '&lt;') == 0) {
        // Look for the &lt; in the sting and make sure it is in the front.
        $chop = 4;  // Increase the $chop.
    }
    // May need to add more later....

    $string = substr($string, $chop);

    if ($string != '&#13;') {
        return $string;
    } else {
        return false;
    }
}

// Clean up the temp directory.
function clean_temp() {
    global $CFG;
    /* this function is broken, use it to clean up later
     * should only clean up what we made as well because someone else could be importing ppt as well
     * delDirContents($CFG->dataroot.'/temp/lesson');
     */
}

// Creates objects an chapter object that is to be inserted into the database.
function book_create_objects($pageobjects, $bookid) {
    $chapters = array();
    $chapter = new stdClass;

    // Same for all chapters.
    $chapter->bookid = $bookid;
    $chapter->pagenum = $DB->count_records('book_chapters', array( 'bookid' => $bookid)) + 1;
    $chapter->timecreated = time();
    $chapter->timemodified = time();
    $chapter->subchapter = 0;

    $i = 1;
    foreach ($pageobjects as $pageobject) {
        $page = prep_page($pageobject, $i);  // Get title and contents.
        $chapter->importsrc = addslashes($pageobject->source); // Add the source.
        $chapter->title = $page->title;
        $chapter->content = $page->contents;
        $chapters[] = $chapter;

        // Increment our page number and our counter.
        $chapter->pagenum = $chapter->pagenum + 1;
        $i++;
    }

    return $chapters;
}

// Builds the title and content strings from an object.
function prep_page($pageobject, $count) {
    if ($pageobject->title == '') {
        $page->title = "Page $count";  // No title set so make a generic one.
    } else {
        $page->title = addslashes($pageobject->title);
    }

    $page->contents = '';

    // Nab all the images first.
    foreach ($pageobject->images as $image) {
        $image = str_replace("\n", '', $image);
        $image = str_replace("\r", '', $image);
        $image = str_replace("'", '"', $image);  // Imgstyle.

        $page->contents .= addslashes($image);
    }
    // Go through the contents array and put <p> tags around each element and strip out \n which I have found to be uneccessary.
    foreach ($pageobject->contents as $content) {
        $content = str_replace("\n", '', $content);
        $content = str_replace("\r", '', $content);
        $content = str_replace('&#13;', '', $content);  // Puts in returns?
        $content = '<p>'.$content.'</p>';
        $page->contents .= addslashes($content);
    }
    return $page;
}

// Save the chapter objects to the database.
function book_save_objects($chapters, $bookid, $pageid='0') {
    global $DB;

    // Nothing fancy, just save them all in order.
    foreach ($chapters as $chapter) {
        if (!$chapter->id = $DB->insert_record('book_chapters', $chapter)) {
            print_error('Could not update your book');
        }
    }
    return true;
}
