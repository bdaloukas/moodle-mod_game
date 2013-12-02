<?php

require( "../../../config.php");
include_once("class.Sudoku.php");
require( '../header.php');

$action = optional_param('action', PARAM_ALPHA);   // action

if( $action == 'create'){
	AppendSudokuB();
}else
{
	showform();
}

function showform()
{
	$id = required_param('id', PARAM_NUMBER);   // action
	
	?>
<form name="form" method="post" action="create.php">
<center>
<table cellpadding="5">
<tr valign="top">
    <td align="right"><b><?php  echo get_string( 'sudoku_create_count', 'game'); ?>:</b></td>
    <td>
        <input type="text" name="count" size="6" value="2" /><br>
    </td>
</tr>	
<tr><td colspan=2><center><br><input type="submit" value="<?php  print_string('sudoku_create_start', 'game') ?>" /></td></tr>
</table>
<input type="hidden" name=action        value="create" >
<input type="hidden" name=level1        value="1" >
<input type="hidden" name=level2        value="10" >
<input type="hidden" name=id        value="<?php  echo $id; ?>" />
</form>

	<?php
	
}

function AppendSudokuB()
{
    global $DB;

	$level1 = required_param('level1', PARAM_NUMBER);   // action
	$level2 = required_param('level2', PARAM_NUMBER);   // action
	$count = required_param('count', PARAM_NUMBER);   // action

	$level = $level1;
  
	for( $i=1; $i <= $count; $i++)
	{
		//set_time_limit( 30);
		Create( $si, $sp, $level);
  
		$newrec->data = PackSudoku( $si, $sp);
		if( strlen( $newrec->data) != 81){
			return 0;
		}
		$newrec->level = $level;
		$newrec->opened = GetOpened( $si);
  
		$DB->insert_record( 'game_sudoku_database', $newrec, true);
    
		$level++;
		if( $level > $level2){
			$level = $level1;
		}
		
		echo get_string( 'sudoku_creating', 'game', $i)."<br>\r\n";
	}
}

function PackSudoku( $si, $sp)
{
	$data = "";

	for ($i = 1; $i <= 9; $i++)
	{
		for ($j = 1; $j <= 9; $j++)
		{	
			$c = &$sp->theSquares[$i];
			$c = &$c->getCell($j) ;
			$solution = $c->asString( false);

 		   $c = &$si->theSquares[$i] ;
 		   $c = &$c->getCell($j) ;
 		   $theSolvedState = $c->solvedState() ;
 		    		
			if( $theSolvedState == 1) {  //hint
				$solution = substr( 'ABCDEFGHI', $c->asString( false) - 1, 1);	
			}
	
			$data .= $solution;
		}
	}

	return $data;
}


function create( &$si, &$sp, $level=1)
{
	for( $i=1; $i <= 40; $i++)
	{
		//set_time_limit( 30);
		$sp = new Sudoku() ;
		$theInitialPosition = $sp->generatePuzzle( 10, 50, $level) ;
		if( count( $theInitialPosition)){
			break;
		}
	}
	if( $i > 40){
		return false;
	}

	$si = new Sudoku() ;

	$si->initializePuzzleFromArray($theInitialPosition);
  
	return true;
}

function GetOpened( $si)
{
  $count = 0;
  
  for ($i = 1; $i <= 9; $i++)
  {
  	for ($j = 1; $j <= 9; $j++)
  	{		    		
 		   $c = &$si->theSquares[$i] ;
 		   $c = &$c->getCell($j) ;
 		   $theSolvedState = $c->solvedState() ;
 		    		
	     if( $theSolvedState == 1)   //hint
          $count++;
  	}
  }

  return $count;
}

