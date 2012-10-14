<?php  // $Id: picture.php,v 1.3 2010/07/26 00:13:32 bdaloukas Exp $

require( '../../../config.php');

$id = required_param('id', PARAM_INT); // Course Module ID
$attemptid = required_param('id2', PARAM_INT); // Course Module ID

$foundcells = required_param('f', PARAM_SEQUENCE); //CSV
$cells = required_param('cells', PARAM_SEQUENCE); //CSV
$filehash = required_param('p', PARAM_PATH);
$cols = required_param('cols', PARAM_INT);
$rows = required_param('rows', PARAM_INT);
$filenamenumbers = required_param('n', PARAM_PATH); //Path to numbers picture
create_image( $id, $attemptid, $foundcells, $cells, $filehash, $cols, $rows, $filenamenumbers);

function create_image( $id, $attemptid, $foundcells, $cells, $filehash, $cols, $rows, $filenamenumbers)
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

    $file = get_file_storage()->get_file_by_hash( $filehash);
    $image = $file->get_imageinfo();

    if( $image === false){
        die("Aknown filehash $filehash");
        return false;
    }
    $img_handle = imagecreatefromstring($file->get_content());

    $mime = $image[ 'mimetype'];
    
    $img_numbers = imageCreateFromPNG( $filenamenumbers);
    $size_numbers = getimagesize ($filenamenumbers);

    Header ("Content-type: $mime");
    
    $color = ImageColorAllocate ($img_handle, 100, 100, 100);

    $width = $image[ 'width'];
    $height = $image[ 'height'];
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
