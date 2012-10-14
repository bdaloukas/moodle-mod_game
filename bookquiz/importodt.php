<?php // $Id: importodt.php,v 1.5 2012/07/25 11:16:04 bdaloukas Exp $
/**
 * This is a very rough importer for odt
 * 
 * The script supports book
 * Is based on class  office  from http://www.phpclasses.org/browse/package/2586.html
 *
 * @version $Id: importodt.php,v 1.5 2012/07/25 11:16:04 bdaloukas Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package game
 **/

    require_once("../../../config.php");
	require_once( "../header.php");
    require_once("../locallib.php");

     $subchapter = optional_param('subchapter',  '', PARAM_ALPHA);
     $overwrite = optional_param('overwrite',  0, PARAM_INT);
	$attempt = game_getattempt( $game, $detail);
    $bookid = $game->bookid;
    if( $bookid == 0){
        print_error( get_string( 'bookquiz_not_select_book', 'game'));
    }

    if ($form = data_submitted())
    {   /// Filename

        if (empty($_FILES['newfile'])) 
		{      // file was just uploaded
            notify(get_string("uploadproblem") );
        }

        if ((!is_uploaded_file($_FILES['newfile']['tmp_name']) or $_FILES['newfile']['size'] == 0)) 
		{
            notify(get_string("uploadnofilefound") );
        } else 
		{  // Valid file is found            
            if ( readdata( $course->id, 'game', $dirtemp, $r_levels, $r_titles, $r_texts, $dirfordelete)) 
			{  // first try to reall all of the data in
				if( $overwrite){
					game_bookquiz_deletebook( $course->id, $bookid);
				}				
                $pageobjects = extract_data( $course->id, 'book', $bookid, $dirtemp, $subchapter, $r_levels, $r_titles, $r_texts); // parse all the html files into objects
                clean_temp( $dirfordelete); // all done with files so dump em
				
                $objects = game_bookquiz_create_objects( $pageobjects, $bookid);  // function to preps the data to be sent to DB
               
                if( !game_bookquiz_save_objects($objects)) 
				{  // sends it to DB
                    print_error('could not save');
                }
            }else
                print_error('could not get data');

            print_continue("{$CFG->wwwroot}/mod/game/view.php?id=$cm->id");
            echo $OUTPUT->footer($course);
            exit;
        }
    }

    /// Print upload form

    print_heading_with_help( get_string( "bookquiz_import_odt", "game"), "importodt", "game");

    echo $OUTPUT->box_start('center');
	?>
    <form id="theform" enctype="multipart/form-data" method="post">
    <input type="hidden" name="id" value="<?php echo $cm->id; ?>" />
    <table cellpadding="5">

    <tr><td align="right"><b>
    <?php print_string("upload"); ?> :</td><td>
    <input name="newfile" type="file" size="50" />
	
	<tr valign="top">
        <td valign="top" align="right">
            <b><?php echo get_string( 'bookquiz_subchapter', 'game');?>:</b>
        </td>
        <td>
        <input name="subchapter" type="checkbox" value="1" checked="1" />        </td>
    </tr>

	<tr valign="top"><td align="right"><b></b></td><td align="left"><select id="overwrite" name="overwrite" >
		<option value="0" selected="selected">Προσθήκη στο τέλος</option>
		<option value="1">Αντικατάσταση</option>
		</select>
	</td></tr>

    </td></tr><tr><td>&nbsp;</td><td>
    <input type="submit" name="save" value="<?php echo get_string("uploadthisfile"); ?>" />
    </td></tr>

    </table>
    </form>
	<?php
    echo $OUTPUT->box_end();

    echo $OUTPUT->footer($course);
    
// START OF FUNCTIONS

//the r_basedir variable contains the directory where the temp files are
//At the end the directory must be deleted
function readdata( $courseid, $modname, &$r_basedir, &$r_levels, &$r_titles, &$r_texts, &$dirfordelete) 
{
// this function expects a odt file to be uploaded.  Then it parses
// the content.xml to determine.  
// Then copies the image 
    global $CFG;
	
    // create a random upload directory in temp
	$newdir = $CFG->dataroot."/temp/$modname";
    if (!file_exists( $newdir)) 
		mkdir( $newdir);

	$i = 1;
	srand((double)microtime()*1000000); 
	while(true)
	{
		$r_basedir = "$modname/$i-".rand(0,10000);
		$newdir = $CFG->dataroot.'/temp/'.$r_basedir;
        if (!file_exists( $newdir)) 
		{
    		mkdir( $newdir);
			$newdir .= '/';
            break;
        }
        $i++;
    }
	$dirfordelete = $r_basedir;
	$r_basedir .= '/';

    $zipfile = $_FILES["newfile"]["name"];
    $tempzipfile = $_FILES["newfile"]["tmp_name"];
	
    // create our directory
    $path_parts = pathinfo($zipfile);
    $dirname = substr($zipfile, 0, strpos($zipfile, '.'.$path_parts['extension'])); // take off the extension
    if (!file_exists($newdir.$dirname)){
        mkdir($newdir.$dirname);
    }

    // move our uploaded file to temp/game
    move_uploaded_file( $tempzipfile, $newdir.$zipfile);
	
	//if the file ends with .lnk then use .odt instead
	if( substr( $zipfile, -4) == ".lnk")
		$zipfile = substr( $zipfile, 0, -4).".odt";

    // unzip it!
	unzip_file ( $newdir.$zipfile, $newdir.$dirname, false);

    $r_basedir .= $dirname;  // update the base
	$newdir .= $dirname;
    
	// this is the file where we get the names of the files for the slides (in the correct order too)
    $content = $newdir.'/content.xml';
	$data = file_get_contents( $content);

    $content = $newdir.'/styles.xml';
    if (file_exists( $content)){
		$datastyle = file_get_contents( $content);
	}else
	{
		$datastyle = '';
	}

	oo_game_convert_ver2( $data, $datastyle, $r_levels, $r_titles, $r_texts);
	
	return true;
}


	////////////////////////
	function oo_game_convert_ver2( $data, $datastyle, &$r_levels, &$r_titles, &$r_texts)
	{
		$r_levels = array();
		$r_titles = array();
		$r_texts = array();
		
		// we have tables, encode it here so all <text:p in them don't get preg_match_all few lines later
		IF(ereg('table:table', $data))
		{
			$data = str_replace('<table:table', '<text:p text:style-name="RKRK"><table:table', $data);
			$data = str_replace('</table:table>', '</table:table></text:p>', $data);
			$data = preg_replace('#<table:table(.*?)</table:table>#es', "base64_encode('\\1')", $data);
		}

		$styles = array();
		game_bookquiz_convert_ver2_computestyles( $datastyle, $styles, true);
		game_bookquiz_convert_ver2_computestyles( $data, $styles, false);

		game_bookquiz_splitsections($data, $positions, $inputs, $titles, $titleframes, $texts);
		for( $i=0; $i < count( $positions); $i++)
		{
			preg_match_all( "#text:outline-level=\"([0-9]*)\"#es", $inputs[ $i], $matches);
			$levels = $matches[ 1];
			if( count( $levels) > 0){
				$level = $levels[ 0];
			}else
			{
				$level = 0;
			}

			$r_levels[] = $level;
			$r_titles[] = strip_tags( $titles[ $i]);

            $textframe = game_bookquiz_convert($titleframes[ $i], $styles, $images);
            $text = game_bookquiz_convert($texts[ $i], $styles, $images);
            if( $textframe != ''){
                $text = $textframe.'<BR>'.$text;
            }

            echo "<hr><b>".$titles[ $i]."</b><br>".$text."\r\n\r\n\r\n\r\n";

			$r_texts[] = $text;
		}
	}

function extract_data( $courseid, $modname, $id, $basedir, $subchapter, $levels, $titles, $texts) 
{
    global $CFG;
    global $matches;
	
	$dirtemp = $CFG->dataroot.'/temp/'.$basedir;
	
	for($i=0; $i < count( $levels); $i++){
		echo $levels[ $i]." ".$titles[ $i]."<BR>";
	}

    $extractedpages = array();
    
    // directory for images
    make_mod_upload_directory( $courseid); // make sure moddata is made
    make_upload_directory( $courseid.'/moddata/'.$modname, false);  
    make_upload_directory( $courseid.'/moddata/'.$modname."/".$id, false);  // we store our images in a subfolder in here 
    
    $imagedir = $CFG->dataroot.'/'.$courseid.'/moddata/'.$modname."/".$id;

    if ($CFG->slasharguments)
        $imagelink = $CFG->wwwroot.'/file.php/'.$courseid.'/moddata/'.$modname."/".$id;
    else
        $imagelink = $CFG->wwwroot.'/file.php?file=/'.$courseid.'/moddata/'.$modname."/".$id;
    
	// try to make a unique subfolder to store the images
	$i = 1;
	while(true) 
	{
        $newdir = $imagedir.'/'.$i;
        if (!file_exists( $newdir)) 
		{
            // ok doesnt exist so make the directory and update our paths
            mkdir( $newdir);
            $imagedir = $newdir;
            $imagelink = $imagelink.'/'.$i;
            break;
        }
        $i++;
    }

    for( $i=0; $i < count( $titles); $i++)
	{		
        // start building our page
        $page = new stdClass;
        $page->title = $titles[ $i];
		$page->content = $texts[ $i];
        //$page->source = $path_parts['basename']; // need for book only
		$page->subchapter = ( $levels[ $i] >= 2);
		
		//check if the nexts are subchapters
		for( $j=$i+1; $j < count( $titles); $j++){
			if( $levels[ $j] > 2){
				$page->content .= '<br><b><u>'.$titles[ $j].'</u></b><br>'.$texts[ $j];
				$i = $j;
				continue;
			}
			break;
		}
		
		preg_match_all('#="Pictures/([a-z .A-Z_0-9]*)"#es', $page->content, $imgs);

        foreach ($imgs[1] as $img) 
		{
            $src = $dirtemp.'/Pictures/'.$img;
			$dest = $imagedir.'/'.$img;
        	rename( $src, $dest);
			
			$page->content = str_replace( "Pictures/$img", $imagelink."/".$img, $page->content);
        }

        // add the page to the array;
        $extractedpages[] = $page;
        
    } // end $pages foreach loop

    return $extractedpages;
}

/**
    Clean up the temp directory
*/
function clean_temp( $base) 
{
    global $CFG;

    // this function is broken, use it to clean up later
    // should only clean up what we made as well because someone else could be importing ppt as well
	$dir = $CFG->dataroot.'/temp/'.$base;

	remove_dir( $dir);
	//game_full_rmdir( $dir);	
}


/**
    Creates objects an chapter object that is to be inserted into the database
*/

function game_bookquiz_create_objects( $pageobjects, $bookid)
{
    global $DB;

    $chapters = array();

    $lastpagenum = $DB->get_field('book_chapters', 'MAX(pagenum) as num', array( 'bookid' => $bookid));

    foreach ($pageobjects as $pageobject) 
    {
        $chapter = new stdClass;
    
        // same for all chapters
        $chapter->bookid = $bookid;
        $chapter->pagenum = ++$lastpagenum;
        $chapter->timecreated = time();
        $chapter->timemodified = time();
        $chapter->subchapter = 0;

	    if ($pageobject->title == '')
		    $chapter->title = "Page $count";  // no title set so make a generic one
    	else
	    	$chapter->title = addslashes($pageobject->title);
	
		$chapter->subchapter = $pageobject->subchapter;
    
      	$content = str_replace("\n", '', $pageobject->content);
	    $content = str_replace("\r", '', $content);
    	$content = str_replace('&#13;', '', $content);  // puts in returns?
    	$content = '<p>'.$content.'</p>';
		
    	$chapter->content = addslashes( $content);

        $chapters[] = $chapter;         
    }

    return $chapters;
}

/**
    Save the chapter objects to the database
*/
function game_bookquiz_save_objects($chapters) 
{
    global $DB;

    // nothing fancy, just save them all in order
    foreach ($chapters as $chapter) 
    {
        if (!$newid=$DB->insert_record('book_chapters', $chapter)) {
            print_error('Could not insert to table book_chapters');
        }
    }
	
    return true;
}

//splits the data to 
function game_bookquiz_splitsections($data, &$positions, &$inputs, &$titles, &$titleframes, &$texts)
{
	preg_match_all('#<text:h (.*?)>(.*?)</text:h>#es', $data, $matches, PREG_OFFSET_CAPTURE);
		
	$in = $matches[ 1] ;
	$title = $matches[ 2];
	
	$positions = array();
	$inputs = array();
	$titles = array();
	
	$oldposition = 0;
	$oldlen = 0;
	for($i=0; $i < count( $in); $i++)
	{
		$inputs[] = $in[ $i][ 0];

		$newposition = $in[ $i][ 1];
		$positions[] = $newposition;

		$titlenet = $title[ $i][ 0];
		$titleframe = '';
		
        //frames inside header
		preg_match_all('#<draw:frame (.*?)>(.*?)</draw:frame>#es', $titlenet, $titlematches, PREG_OFFSET_CAPTURE);
		$frames = $titlematches[ 2];
		if( count( $frames) > 0){
			for($j=0; $j < count( $frames); $j++)
			{
				$titleframe .= $frames[ $j][ 0];
				$titlenet = substr( $titlenet, $frames[ $j][ 1] + strlen( $frames[ $j][ 0]) + 13);
			}
		}

		$titles[] = $titlenet;
		$titleframes[] = $titleframe;
		
		if( $i > 0){
			$texts[] = substr( $data, $oldposition+$oldlen, $newposition - $oldposition - $oldlen);
		}

		$oldlen = strlen( $title[ $i][ 0]) + strlen( $in[ $i][ 0]) + 10;
		$oldposition = $newposition;

	}
	$newposition = strlen( $data);
	$texts[] = substr( $data, $oldposition+$oldlen, $newposition - $oldposition - $oldlen);
}
	
	function game_bookquiz_convert( $data, $styles, &$images)
	{
		$images = array();
	
		// get data
		preg_match_all('#<text:p text:style-name="([a-z A-Z_0-9]*)">(.*?)</text:p>#es', $data, $text);
        $originals = $text[ 0];
        $names = $text[ 1];
        $texts = $text[ 2];

		for( $i=0; $i < count( $texts); $i++)
		{
            $name = $names[ $i];
            $text = $texts[ $i];

            //repairs draw:frame
			$pattern =  "#<draw:frame draw:style-name=\"([a-z .A-Z_0-9]*)\" (.*?)<draw:image xlink:href=\"Pictures/([a-z .A-Z_0-9]*)(.*?)</draw:frame>#es";
			preg_match_all( $pattern, $text, $matches);
			if( count( $matches[ 1]) ){
				$new = game_bookquiz_convert_image( $matches, $styles, $images);
				$data = str_replace( $originals[ $i], $new, $data);
			}else IF($name == 'RKRK')
			{
				$new = game_bookquiz_convert_RKRK( $text);
				$data = str_replace( $originals[ $i], $new, $data);
			}else
			{
				$new = '<P>'.game_bookquiz_convert_text( $text, $styles).'</P>';
				$data = str_replace( $originals[ $i], $new, $data);
			}
		}

		// repairs text:span text:style-name
		preg_match_all( '#<text:span text:style-name="([a-z .A-Z_0-9]*)">(.*?)</text:span>#es', $data, $text);
        $originals = $text[ 0];
        $names = $text[ 1];
        $texts = $text[ 2];
		for( $i=0; $i < count( $texts); $i++)
		{
            $name = $names[ $i];
            $text = $texts[ $i];

			$pattern =  "#<draw:frame draw:style-name=\"([a-z .A-Z_0-9]*)\" (.*?)<draw:image xlink:href=\"Pictures/([a-z .A-Z_0-9]*)(.*?)</draw:frame>#es";
			preg_match_all( $pattern, $text, $matches);
			if( count( $matches[ 1]) ){
		        $new = game_bookquiz_convert_image( $matches, $styles, $images);
				$data = str_replace( $originals[ $i], $new, $data);
			}else IF($name == 'RKRK')
			{
		        $new = game_bookquiz_convert_RKRK( $text);
				$data = str_replace( $originals[ $i], $new, $data);
			}else
			{
		        $new = "<span ".$styles[ $name].'>'.game_bookquiz_convert_text( $text, $styles).'</span>';
				$data = str_replace( $originals[ $i], $new, $data);
			}
		}

		// repairs text:a
		preg_match_all( '#<text:a (.*?) xlink:href="(.*?)">(.*?)</text:a>#es', $data, $text);
        $originals = $text[ 0];
        $hrefs = $text[ 2];
        $texts = $text[ 3];
		for( $i=0; $i < count( $texts); $i++)
		{
            $href = $hrefs[ $i];
            $text = $texts[ $i];

			$new = "<a href=\"$href\">$text</a>";
			$data = str_replace( $originals[ $i], $new, $data);
		}

        //repair text:list
        preg_match_all( '#<text:list text:style-name="([a-z A-Z_0-9]*)">(.*?)</text:list>#es', $data, $text);
        $originals = $text[ 0];
        $names = $text[ 1];
        $texts = $text[ 2];

        for( $i=0; $i < count( $texts); $i++)
        {
            $new = '<UL>'.$texts[ $i].'</UL>';
            $data = str_replace( $originals[ $i], $new, $data);

            //I have to repair the listitems
            preg_match_all( '#<text:list-item>(.*?)</text:list-item>#es', $data, $listitems);
            $originallistitems = $listitems[ 0];
            $items = $listitems[ 1];
            for( $j=0; $j < count( $items); $j++){
                $new = '<LI>'.$items[ $j];
                $data = str_replace( $originallistitems[ $j], $new, $data);
                
            }
        }

		$data = str_replace( '<text:line-break/>', '<br>', $data);

		
		
		return $data;
	}

	function game_bookquiz_convert_text( $text, $styles)
	{		
		$pattern = "#<text:span text:style-name=\"([a-z .A-Z_0-9]*)\">(.*?)</text:span>#es";
		preg_match_all( $pattern, $text, $matches);
	
		$originals = $matches[ 0];
		$names = $matches[ 1];
		$spantexts = $matches[ 2];

		for( $i=0; $i < count( $names); $i++)
		{
			$name = $names[ $i];
			$style = $styles[ $name];
			
			$new = "<text:span style=\"$style\">".$spantexts[ $i]."</text:span>";
			$text = str_replace( $originals[ $i], $new, $text);
		}
	
		return $text;
	}
	
	function game_bookquiz_convert_image( $matches, $xmlstyles, &$images)
	{
		$ret = '';
		
		$styles = $matches[ 1];
		$pictures = $matches[ 3];
		
		for( $j=0; $j < count( $pictures); $j++){
			$style = $styles[ $j];
			
			$ret .= '<div class="'.$style.'"><img src="Pictures/'.$pictures[$j].'"></div>';
			$images[] = $pictures[$j];
		}
		
		return $ret;
	}
	
	function game_bookquiz_convert_RKRK( $text)
	{
		$table = base64_decode($text);
		$table = stripslashes($table);
		$table = strtr($table, array('</table:table>' => '</table>', '<table:table-row>' => '<tr>', '</table:table-row>' => '</tr>', '</table:table-cell>' => '</td>', '</table:table-header-rows>' => '', '<table:table-header-rows>' => '', '>' => ">\n", '</text:p>'  => ''));
				
		//preg_match_all('#table:name="([a-z A-Z_0-9]*)" table:style-name="([a-z A-Z_0-9]*)">#es', $table, $repl);
		preg_match_all('#table:name="(.*?)" table:style-name="(.*?)">#es', $table, $repl);
		foreach($repl[0] as $val)
		{
			//$table = str_replace($val, '<table border="1"><tr><td>', $table);
			$table = str_replace($val, '<table border="1"><tr>', $table);
		}
		//preg_match_all('#<text:p text:style-name="([a-z A-Z_0-9]*)">#es', $table, $repl);
		preg_match_all('#<text:p text:style-name="(.*?)">#es', $table, $repl);
		foreach($repl[0] as $key => $val)
		{
			$table = str_replace($val, '', $table);
		}
		preg_match_all('#<table:table-column (.*?)">#es', $table, $repl);
		foreach($repl[0] as $val)
		{
			$table = str_replace($val, '', $table);
		}
		//preg_match_all('#<table:table-cell table:style-name="([\.a-z A-Z_0-9]*)" office:value-type="([a-z A-Z_0-9]*)">#es', $table, $repl);
		preg_match_all('#<table:table-cell table:style-name="(.*?)" office:value-type=(.*?)">#es', $table, $repl);
		foreach($repl[0] as $val)
		{
			$table = str_replace($val, '<td>', $table);
		}
		//maybe there are a lot of pictures inside a table
		preg_match_all('#xlink:href="Pictures/([a-z.A-Z_0-9]*)"#es', $table, $repl);
		foreach( $repl[ 1] as $picture)
		{
			$table = str_replace('<draw:image xlink:href="Pictures/'.$picture.'" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>', '<img src="Pictures/'.$picture.'">', $table);
		}
		if( strpos( $table,"</table>") === false)
				$table .= "</table>";
				
		$ret = '<BR>'.$table.'<BR>';
		
		return $ret;
	}
/*
	function game_bookquiz_oo_unzip($file, $dir)
	{
		unzip_file ( $file, $dir, false);
		
		$dir .= '/';
		if( file_exists( $dir.'content.xml')){
			$content = file_get_contents( $dir.'content.xml');
		}else
		{
			$content = '';
		}
		
		if( file_exists( $dir.'styles.xml')){
			$contentstyles = file_get_contents( $dir.'styles.xml');
		}else
		{
			$contentstyles = '';
		}
		
		$img = array();
		$handle = opendir($dir.'Pictures');
		while (false!==($item = readdir($handle))) {
			if($item != '.' && $item != '..') {
				if(!is_dir($dir.'/'.$item)) {
					$img[ $item] = file_get_contents( $dir.'/'.$item);
				}else{
                unlink($dir.'/'.$item);
				}
			}
		}
	}
*/
	function old_game_bookquiz_oo_unzip($file, $save, $dir)
	{	
		IF($zip = game_zip_open($file))
		{
			while ($zip_entry = game_zip_read($zip))
			{
				$filename = game_zip_entry_name($zip_entry);
				
				IF($filename  == 'content.xml' and game_zip_entry_open($zip, $zip_entry, "r"))
				{
					$content = game_zip_entry_read($zip_entry, game_zip_entry_filesize($zip_entry));
					game_zip_entry_close($zip_entry);
				}
				
				IF( $filename  == 'styles.xml' and game_zip_entry_open($zip, $zip_entry, "r"))
				{
					$contentstyles = game_zip_entry_read($zip_entry, game_zip_entry_filesize($zip_entry));
					game_zip_entry_close($zip_entry);
				}
				
				IF(ereg('Pictures/', $filename) and !ereg('Object', $filename)  and game_zip_entry_open($zip, $zip_entry, "r"))
				{
					$img[$filename] = game_zip_entry_read($zip_entry, game_zip_entry_filesize($zip_entry));
					game_zip_entry_close($zip_entry);
				}
			}
			IF(isset($content))
			{
				IF($save == false)
					return array($content, $img);
				else
				{
					file_put_contents("$dir/content.xml", $content);
					IF(isset($contentstyles)){
						file_put_contents("$dir/styles.xml", $contentstyles);
					}
					
					IF(is_array($img))
					{
						IF(!is_dir("$dir/Pictures"))
							mkdir( "$dir/Pictures");

						foreach($img as $key => $val)
							file_put_contents("$dir/$key", $val);
					}
				}
			}
		}
	}

	function game_bookquiz_deletebook( $courseid, $bookid)
	{
		global $CFG;
		
		if( !delete_records( 'book_chapters', 'bookid', $bookid)){
			print_error( "Can't delete records from book_chapters bookid=$bookid");
		}
		
		game_full_rmdir( "$CFG->dataroot/$courseid/moddata/book/$bookid");
		
	}
	
	function game_bookquiz_convert_ver2_computestyles( $data, &$styles, $isstyle)
	{
		preg_match_all('#<style:style style:name="(.*?)"(.*?)>(.*?)</style:style>#es', $data, $style);

		$stylenames = $style[ 1];
		$styleinfos = $style[ 2];
		$styledatas = $style[ 3];
		for($i=0; $i < count( $stylenames); $i++){
			$name = $stylenames[ $i];
			
			$change = false;
			for(;;){
				$pos1 = strpos( $styledatas[ $i], 'style:parent-style-name');
				$pos2 = strpos( $styledatas[ $i], '/>');
				if( ($pos1 === false) or ($pos2 === false)){
					break;
				}
				if( $pos1 > $pos2){
					break;
				}
				//is a parent style
				$s = substr( $styledatas[ $i], 0, $pos2+2);
				game_bookquiz_convertstyle_parent( $s, $styles);

				$styledatas[ $i] = substr( $styledatas[ $i], $pos2 + 2);
				$change = true;
			}
			if( $change){
				//Must to recompute name, styledatas, styleinfos
				preg_match_all('#<style:style style:name="(.*?)"(.*?)>(.*?)</style:style>#es', $data, $style);
				$name = $style[ 1][ 0];
				$styleinfos[ $i] = $style[ 2][ 0];
				$styledatas[ $i] = $style[ 3][ 0];				
			}
			
			$styles[ $name] = game_bookquiz_convertstyle( $styledatas[ $i], $styleinfos[ $i], $styles);
		}
	}
	
	function game_bookquiz_convertstyle_parent( $data, &$styles)
	{		
		$styleitems = array();

		preg_match_all( '#(.*?)style:name="(.*?)"(.*?)style:parent-style-name="(.*?)"(.*?)#es', $data, $infos);
		$names = $infos[ 2];
		$parents = $infos[ 4];
		if( count( $parents)){
			if( array_key_exists( $parents[ 0], $styles)){
				//is a child style. Must to copy the properties of the parent style
				$a = explode( ';', $styles[ $parents[ 0]]);
				foreach( $a as $s){
					$pos = strpos( $s, ':');
					$key = substr( $s, 0, $pos);
					$item = substr( $s, $pos + 1);
					if( $item == ''){
						continue;
					}
					$styleitems[ $key] = $item;
				}
			}
			$name = $names[ 0];
		}

		$style = '';
		foreach( $styleitems as $key => $item){
			$style .= ';'.$key.':'.$item;
		}
		$styles[ $name] = substr( $style, 1);
	}
	
	function game_bookquiz_convertstyle( $data, $styleinfo, $styles)
	{		
		$styleitems = array();
		
		preg_match_all( '#<style:paragraph-properties (.*?)/>#es', $data, $infos);
		$lines = $infos[ 1];
		if( count( $lines)){
			$line = $lines[ 0]; //print_object( $lines);
		
			if( $line != ''){
				game_bookquiz_convertstyle_paragraph( $line, $styleitems);
			}
		}
		
		preg_match_all( '#<style:text-properties (.*?)/>#es', $data, $infos);
		$lines = $infos[ 1];
		if( count( $lines)){
			$line = $lines[ 0];
		
			if( $line != ''){
				game_bookquiz_convertstyle_textproperties( $line, $styleitems);
			}
		}
	
		if( count( $styleitems) == 0){
			return '';
		}

		$style = '';
		foreach( $styleitems as $key => $item){
			$style .= ';'.$key.':'.$item;
		}
	
		return substr( $style, 1);
	}

	function game_bookquiz_convertstyle_paragraph( $line, &$styleitems)
	{
		preg_match_all( '#(.*?)=(.*?) #es', $line.' ', $datas);
		$data1 = $datas[ 1];
		$data2 = $datas[ 2];
		
		$ret = '';
		for( $i=0; $i < count( $data1); $i++){
			$eq1 = $data1[ $i];
			$eq2 = $data2[ $i];

            if( (substr( $eq2, 0, 1) == '"') and (substr( $eq2, -1, 1) == '"')){
                $eq2 = substr( $eq2, 1, -1);
            }

            switch( $eq1){
            case 'fo:text-align':
                $styleitems[ 'align'] = $eq2;
				//print_object( $styleitems);
                break;
            case 'fo:background-color':
                $styleitems[ 'background-color'] = $eq2;
                break;
		    }
        }
	}

	function game_bookquiz_convertstyle_textproperties( $line, &$styleitems)
	{
        preg_match_all( '#(.*?)=(.*?) #es', $line.' ', $datas);
        $data1 = $datas[ 1];
        $data2 = $datas[ 2];

        $ret = '';
        for( $i=0; $i < count( $data1); $i++){
            $eq1 = $data1[ $i];
            $eq2 = $data2[ $i];

            if( (substr( $eq2, 0, 1) == '"') and (substr( $eq2, -1, 1) == '"')){
                $eq2 = substr( $eq2, 1, -1);
            }

            switch( $eq1){
            case 'fo:font-size':
            case 'fo:color':
            case 'fo:background-color':
            case 'fo:font-style':
            case 'fo:font-weight':
                $styleitems[ substr( $eq1, 3)] = $eq2;
                break;
            case 'style_text_underline_style':
                if( $eq2 == 'solid'){
                    $styleitems[ 'text-decoration'] = 'underline';
                }
                break;
            }
        }
	}
