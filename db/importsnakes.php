<?php  // $Id: importsnakes.php,v 1.5 2012/07/25 11:16:05 bdaloukas Exp $

game_importsnakes();

function game_importsnakes()
{
    global $DB;

    if( $DB->count_records( 'game_snakes_database') != 0){
        return;
    }
    
    $newrec = new stdClass();
    $newrec->name = '8x8 - 4 Snakes - 4 Ladders';
    $newrec->cols = 8;
    $newrec->rows = 8;
    $newrec->fileboard = 'fidaki.jpg';
    $newrec->direction = 1;
    $newrec->headerx = 4;
    $newrec->headery = 4;
    $newrec->footerx = 4;
    $newrec->footery = 4;
    $newrec->width = 487;
    $newrec->height = 487;
    $newrec->data = 'L3-18,S5-19,S8-27,L24-39,L29-53,S32-62,S41-58,L48-63';
    game_importsnakes_do( $newrec);

    $newrec = new stdClass();
    $newrec->name = '6x6 - 3 Snakes - 3 Ladders';
    $newrec->cols = 6;
    $newrec->rows = 6;
    $newrec->fileboard = 'fidaki2.jpg';
    $newrec->direction = 1;
    $newrec->headerx = 8;
    $newrec->headery = 8;
    $newrec->footerx = 8;
    $newrec->footery = 8;
    $newrec->width = 502;
    $newrec->height = 436;
    $newrec->data = 'L2-25,S4-23,L8-18,S16-20,L19-29,S27-33';
    game_importsnakes_do( $newrec);
 }


function game_importsnakes_do( $newrec)
{
    global $DB;

	if( !$DB->insert_record( 'game_snakes_database', $newrec)){
		print_object( $newrec);
		print_error( "Can't insert to table game_snakes_database");
	}
}
