<?php  // $Id: picture.php,v 1.2.2.2 2010/07/24 02:57:32 arborrow Exp $

require( '../../../config.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$attemptid = optional_param('id2', 0, PARAM_INT); // Course Module ID

$foundcells = optional_param('f', PARAM_SEQUENCE); //CSV
$cells = optional_param('cells', PARAM_SEQUENCE); //CSV
$filename = optional_param('p', PARAM_PATH);
$cols = optional_param('cols', PARAM_INT);
$rows = optional_param('rows', PARAM_INT);
$filenamenumbers = optional_param('n', PARAM_PATH); //Path to numbers picture

create_image( $id, $attemptid, $foundcells, $cells, $filename, $cols, $rows, $filenamenumbers);

function create_image( $id, $attemptid, $foundcells, $cells, $filename, $cols, $rows, $filenamenumbers)
{
    global $CFG;
            
    $a = explode( ',', $foundcells);
    $found = array();
    foreach( $a as $s){
        $found[ $s] = 1;
    }
    
    $a = explode( ',', $cells);
    $cells = array();
    foreach( $a as $s){
        $cells[ $s] = 1;
    }

    $size = getimagesize ($filename);
    if( $size == false){
        die("Aknown filename $filename");
        return false;
    }

    $mime = $size[ 'mime'];
    switch( $mime){
    case 'image/png':
        $img_handle = imageCreateFromPNG( $filename);
        break;
    case 'image/jpeg':
        $img_handle = imageCreateFromJPEG( $filename);
        break;
    case 'image/gif':
        $img_handle = imageCreateFromGIF( $filename);
        break;
    default:
        die('Aknown mime type $mime');
        return false;
    }
    
    $img_numbers = imageCreateFromPNG( $filenamenumbers);
    $size_numbers = getimagesize ($filenamenumbers);

    Header ("Content-type: $mime");
    
    $color = ImageColorAllocate ($img_handle, 100, 100, 100);
    $s = $CFG->wwwroot;
    ImageString ($img_handle, 3, 10, 9,  $id.' '.$attemptid, $color);

    $colortext = imagecolorallocate( $img_handle, 100, 100, 100); //Text

    $width = $size[ 0];
    $height = $size[ 1];
    $pos = 0;
    
    $font = 1;

    for($y = 0; $y < $rows; $y++){
        for( $x=0; $x < $cols; $x++){
            $pos++;
            if( !array_key_exists( $pos, $found)){
                $x1 = $x * $width / $cols;
                $y1 = $y * $height / $rows;
                imagefilledrectangle( $img_handle, $x1, $y1, $x1 + $width / $cols, $y1 + $height / $rows, $color);
                
                if( array_key_exists( $pos, $cells)){
                    shownumber( $img_handle, $img_numbers, $pos, $x1 , $y1, $width / $cols, $height / $rows, $size_numbers);
                }
            }
        }
    }

    switch( $mime){
    case 'image/png':
        ImagePng ($img_handle);
        break;
    case 'image/jpeg':
        ImageJpeg ($img_handle);
        break;
    case 'image/gif':
        ImageGif ($img_handle);
        break;
    default:
        die('Aknown mime type $mime');
        return false;
    }

    ImageDestroy ($img_handle); 
} 

function shownumber( $img_handle, $img_numbers, $number, $x1 , $y1, $width, $height, $size_numbers){
    if( $number < 10){
        $width_number = $size_numbers[ 0] / 10;
        $dstX = $x1 + $width / 3;
        $dstY = $y1 + $height / 3;
        $srcX = $number * $size_numbers[ 0] / 10;
        $srcW = $size_numbers[ 0]/10;
        $srcH = $size_numbers[ 1];
        $dstW = $width / 10;
        $dstH = $dstW * $srcH / $srcW;
        imagecopyresized( $img_handle, $img_numbers, $dstX, $dstY, $srcX, 0, $dstW, $dstH, $srcW, $srcH);
    }else
    {
        $number1 = floor( $number / 10);
        $number2 = $number % 10;
        shownumber( $img_handle, $img_numbers, $number1, $x1-$width/20, $y1, $width, $height, $size_numbers);
        shownumber( $img_handle, $img_numbers, $number2, $x1+$width/20, $y1, $width, $height, $size_numbers);
    }
}
    


?>
