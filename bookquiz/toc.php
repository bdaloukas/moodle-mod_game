<?PHP // $Id: toc.php,v 1.1 2008/03/26 17:40:38 arborrow Exp $

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');
 
define('NUM_NONE',     '0');
define('NUM_NUMBERS',  '1');
define('NUM_BULLETS',  '2');
define('NUM_INDENTED', '3');

/// included from mod/book/view.php and print.php
///
/// uses:
///   $chapters - all book chapters
///   $chapter - may be false
///   $cm - course module
///   $book - book
///   $edit - force editing view


/// fills:
///   $toc
///   $title (not for print)

$currtitle = '';    //active chapter title (plain text)
$currsubtitle = ''; //active subchapter if any
$prevtitle = '&nbsp;';
$toc = '';          //representation of toc (HTML)

$nch = 0; //chapter number
$ns = 0;  //subchapter number
$title = '';
$first = 1;

if (!isset($print)) {
    $print = 0;
}

switch ($book->numbering) {
  case NUM_NONE:
      $toc .= '<div class="book_toc_none">';
      break;
  case NUM_NUMBERS:
      $toc .= '<div class="book_toc_numbered">';
      break;
  case NUM_BULLETS:
      $toc .= '<div class="book_toc_bullets">';
      break;
  case NUM_INDENTED:
      $toc .= '<div class="book_toc_indented">';
      break;
}


if ($print) { ///TOC for printing
    $toc .= '<a name="toc"></a>';
    if ($book->customtitles) {
        $toc .= '<h1>'.get_string('toc', 'book').'</h1>';
    } else {
        $toc .= '<p class="book_chapter_title">'.get_string('toc', 'book').'</p>';
    }
    $titles = array();
    $toc .= '<ul>';
    foreach($chapters as $ch) {
        $title = trim(strip_tags($ch->title));
        if (!$ch->hidden) {
            if (!$ch->subchapter) {
                $nch++;
                $ns = 0;
                $toc .= ($first) ? '<li>' : '</ul></li><li>';
                if ($book->numbering == NUM_NUMBERS) {
                      $title = "$nch $title";
                }
            } else {
                $ns++;
                $toc .= ($first) ? '<li><ul><li>' : '<li>';
                if ($book->numbering == NUM_NUMBERS) {
                      $title = "$nch.$ns $title";
                }
            }
            $titles[$ch->id] = $title;
            $toc .= '<a title="'.htmlspecialchars($title).'" href="#ch'.$ch->id.'">'.$title.'</a>';
            $toc .= (!$ch->subchapter) ? '<ul>' : '</li>';
            $first = 0;
        }
    }
    $toc .= '</ul></li></ul>';
} else { //normal students view
    $toc .= '<font size="-1"><ul>';
    foreach($chapters as $ch) {
        $title = trim(strip_tags($ch->title));
        if (!$ch->hidden) {
            if (!$ch->subchapter) {
                $nch++;
                $ns = 0;
                $toc .= ($first) ? '<li>' : '</ul></li><li>';
                if ($book->numbering == NUM_NUMBERS) {
                      $title = "$nch $title";
                }
            $prevtitle = $title;
            } else {
                $ns++;
                $toc .= ($first) ? '<li><ul><li>' : '<li>';
                if ($book->numbering == NUM_NUMBERS) {
                      $title = "$nch.$ns $title";
                }
            }
            if ($ch->id == $chapter->id) {
                $toc .= '<strong>'.$title.'</strong>';
                if ($ch->subchapter) {
                    $currtitle = $prevtitle;
                    $currsubtitle = $title;
                } else {
                    $currtitle = $title;
                    $currsubtitle = '&nbsp;';
                }
            } else {
				if( array_key_exists( $ch->id, $okchapters)){
					$toc .= '<a title="'.htmlspecialchars($title).'" href="attempt.php?id='.$id.'&chapterid='.$ch->id.'">'.$title.'</a>';
				}else
				{
					$toc .= htmlspecialchars($title);
				}
            }
            $toc .= (!$ch->subchapter) ? '<ul>' : '</li>';
            $first = 0;
        }
    }
    $toc .= '</ul></li></ul></font>';
}

$toc .= '</div>';

$toc = str_replace('<ul></ul>', '', $toc); //cleanup of invalid structures

?>
