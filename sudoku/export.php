<?php  // $Id: export.php,v 1.3 2012/07/25 11:16:07 bdaloukas Exp $

require( "../../../config.php");

export();

function export()
{
	global $CFG;
	
	
	$file = "import.php";
	$h = fopen($file, 'w') or die("can't open file");
	
	fwrite( $h, "<?php\r\n");
	fwrite( $h, "require( \"../../../config.php\");\r\n\r\n");
	
	if( ($recs=get_records_select( 'game_sudoku_database')) == false)
		print_error('empty');

	$i = 0;
	foreach( $recs as $rec)
	{
		fwrite( $h, "execute_sql( \"INSERT INTO {game_sudoku_database} ( level, opened, data) ".
				 "VALUES ($rec->level, $rec->opened, '$rec->data')\", false);\r\n");
		if( ++$i % 10 == 0)
			fwrite( $h, "\r\n");
	}
	fwrite( $h, "\r\necho'Finished importing';");
	
	fclose($h);

}
