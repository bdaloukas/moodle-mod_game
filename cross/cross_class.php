<?php

/*
Crossing Words for
Codewalkers PHP Coding Contest of July 2002
(http://codewalkers.com/php-contest.php)

Author Àngel Fenoy from Arenys de Mar, Barcelona.
*/ 
class Cross
{
    var $m_input_answers; 	//contains the words and the answers
    var $m_words;				//the words that will be used

	var $m_time_limit = 30;
  
	//computed by computenextcross
	var $m_best_cross_pos;    	//the best puzzle
	var $m_best_cross_dir;    	//the best puzzle
	var $m_best_cross_word;   //the best puzzle
	var $m_best_puzzle;
  
	var $m_bests;             		//best score as a phrase
	var $m_best_score;        		//the best score

    var $m_best_connectors;  
    var $m_best_filleds;    
    var $m_best_spaces;     
    var $m_best_N20;      
  
    //computepuzzleinfo
    var $m_mincol;    //computed by ComputePuzzleInfo
    var $m_maxcol;    //computed by ComputePuzzleInfo
    var $m_minrow;    //computed by ComputePuzzleInfo
    var $m_maxrow;    //computed by ComputePuzzleInfo
    var $m_cLetter;   //computed by ComputePuzzleInfo
    var $m_reps;        //repetition of each word
    var $m_average_reps;//average of repetitions
	
	function setwords( $answers, $maxcols, $reps)
	{
        $this->m_reps = array();
		foreach( $reps as $word => $r){
			$this->m_reps[ game_upper( $word)] = $r;
		}

        $this->m_average_reps=0;
        foreach( $reps as $r)
            $this->m_average_reps += $r;
        if( count( $reps))
            $this->m_average_reps /= count( $reps);

		$this->m_input_answers = array();
		foreach( $answers as $word => $answer){
			$this->m_input_answers[ game_upper( $word)] = $answer;
		}		
		
		$this->m_words = array();
			
		$maxlen = 0;
		foreach( $this->m_input_answers as $word => $answer)
		{
			$len = game_strlen( $word);
			if( $len > $maxlen){
				$maxlen = $len;
			}
		}
		
		$N20 = $maxlen;
		if( $N20 < 15)
		    $N20 = 15;

		$this->m_N20min = round( $N20 - $N20/4);
		$this->m_N20max = round( $N20 + $N20/4);
		if( $this->m_N20max > $maxcols and $maxcols > 0){
			$this->m_N20max = $maxcols;
		}
		if( $this->m_N20min > $this->m_N20max){
			$this->m_N20min = $this->m_N20max;
		}
		
		$this->m_words = array();
		foreach( $this->m_input_answers as $word => $answer)
		{
			$len = game_strlen( $word);

			if( $len <= $this->m_N20max){
				$this->m_words[] = game_upper( $word);
			}
		}
    
		$this->randomize();
		    
		return count( $this->m_words);
    }
  
    function randomize()
    {
        $n = count( $this->m_words);
		for($j=0; $j <= $n/4; $j++)
		{
			$i = array_rand( $this->m_words);

			$this->swap( $this->m_words[ $i], $this->m_words[ 0]);
		}
	}

    function computedata( &$crossm, &$crossd, &$letters, $minwords, $maxwords)
    {
        $t1 = time();
    
        $ctries = 0;
        $m_best_score = 0;

        $m_best_connectors = $m_best_filleds = $m_best_spaces = 0;
        $m_best_N20 = 0;
	
        $nochange = 0;
        for(;;)
        {
            //selects the size of the cross
            $N20 = mt_rand( $this->m_N20min, $this->m_N20max);
      
            if( !$this->computenextcross( $N20, $t1, $ctries, $minwords, $maxwords, $nochange))
                break;
                
            $ctries++;

            if (time() - $t1 > $this->m_time_limit - 3){
                break;
            }

            if( $nochange > 10)
                break;
        }
        $this->computepuzzleinfo( $this->m_best_N20, $this->m_best_cross_pos, $this->m_best_cross_dir, $this->m_best_cross_word, false);
    
        //set_time_limit( 30);
    
        return $this->savepuzzle( $crossm, $crossd, $ctries, time()-$t1);
    }
  
	function computenextcross( $N20, $t1, $ctries, $minwords, $maxwords, &$nochange)
	{
        $MAXW = $N20;
    
		$N21 = $N20 + 1;
		$N22 = $N20 + 2;
		$N2222 = $N22 * $N22;
    
		$base_puzzle = str_repeat('0', $N22) .
		str_repeat('0' . str_repeat('.', $N20) . '0', $N20) .
        str_repeat('0', $N22);

		$cross_pos = array();
		$cross_dir = array();
		$cross_word = array();

		$magics = array();
		for ($n = 2; $n < $N21; $n++)
		{
			$a = array();
			for ($r = 2; $r < ($n + 2); $r++)
				$a[] = $r;

			uasort($a, array( $this, 'cmp_magic'));
			$magics[ $n] = $a;
		}
	
		uasort($this->m_words,  array( $this, 'cmp'));
		
		$words = ';' . implode(';', $this->m_words) . ';';

		$puzzle = $base_puzzle;

		$row = mt_rand(3, max( 3, $N20-3));
		$col = mt_rand(3, max( 3, $N20-3));
		$pos = $N22 * $row + $col;

		$poss = array();
		$ret = $this->scan_pos($pos, 'h', true, $puzzle, $words, $magics, $poss, $cross_pos, $cross_dir, $cross_word, $N20);
		
		while ($s = sizeof($poss))
		{
			$p = array_shift($poss);

			if ($this->scan_pos($p[0], $p[1], false, $puzzle, $words, $magics, $poss, $cross_pos, $cross_dir, $cross_word, $N20)){
				$n_words = count( $cross_word);
				if( $maxwords)
				{
					if( $n_words >= $maxwords){
						break;
					}
				}
			}
			if (time() - $t1 > $this->m_time_limit - 3){
				return false;
			}
		}

    $n_words = count( $cross_word);
    if( $minwords)
    {
        if( $n_words < $minwords)
            return true;
    }
    
	$score = $this->computescore( $puzzle, $N20, $N22, $N2222, $n_words, $n_connectors, $n_filleds, $cSpaces, $cross_word);

	if ($score > $this->m_best_score)
	{
		$this->m_best_cross_pos = $cross_pos;
		$this->m_best_cross_dir = $cross_dir;
		$this->m_best_cross_word = $cross_word;
		$this->m_best_puzzle = $puzzle;

		$this->m_bests = array('Words' => "$n_words * 5 = " . ($n_words * 5),
			'Connectors' => "$n_connectors * 3 = " . ($n_connectors * 3),
			'Filled in spaces' => $n_filleds,
			"N20" => $N20
		);
                  
		$this->m_best_score = $score;
		
		$this->m_best_connectors = $n_connectors;
		$this->m_best_filleds = $n_filleds;
		$this->m_best_spaces = $cSpaces;
		$this->m_best_N20 = $N20;      
		$nochange = 0;
	}else
	{
		$nochange++;
	}

	return true;
}

    function computescore( $puzzle, $N20, $N22, $N2222, $n_words, &$n_connectors, &$n_filleds, &$cSpaces, $cross_word)
    {
		$n_connectors = $n_filleds = 0;
		$puzzle00 = str_replace('.', '0', $puzzle);

		$used=0;
		for ($n = 0; $n < $N2222; $n++)
		{
			if ($puzzle00[$n]){
				$used ++;

			    if (($puzzle00[$n - 1] or $puzzle00[$n + 1]) and ($puzzle00[$n - $N22] or $puzzle00[$n + $N22])){
				    $n_connectors++;
			    } else{
				    $n_filleds++;
                }
		    }
        }

        $cSpaces = substr_count( $puzzle, ".");
        $score = ($n_words * 5) + ($n_connectors * 3) + $n_filleds;

        $sum_rep = 0;
        foreach( $cross_word as $word){
            $word = game_substr( $word, 1, -1);        

            if( array_key_exists( $word, $this->m_reps))
                $sum_rep += $this->m_reps[ $word] - $this->m_average_reps;
        }
	    
        return $score-10*$sum_rep;
    }

 
    function computepuzzleinfo( $N20, $cross_pos, $cross_dir, $cross_word, $bPrint=false)
    {
	    $bPrint=false;
        $N22 = $N20 + 2;

        $this->m_mincol = $N22;
        $this->m_maxcol = 0;
        $this->m_minrow = $N22;
        $this->m_maxrow = 0;
        $this->m_cletter = 0;
	
	    if( count( $cross_word) == 0){
		    return;
    	}
		
        if( $bPrint)
          echo "<BR><BR>PuzzleInfo N20=$N20 words=".count($cross_word)."<BR>";
        for($i = 0; $i < count($cross_pos); $i++)
        {
          $pos = $cross_pos[ $i];
          $col = $pos % $N22;
          $row = floor( $pos / $N22);
          $dir = $cross_dir[ $i];

          $len =  game_strlen($cross_word[ $i])-3;

          if( $bPrint)
            echo "col=$col row=$row dir=$dir word=".$cross_word[ $i]."<br>";

          $this->m_cletter += $len;

          if( $col < $this->m_mincol)
            $this->m_mincol = $col;

          if( $row < $this->m_minrow)
            $this->m_minrow = $row;

          if( $dir == 'h')
            $col += $len;
          else
            $row += $len;

          if( $col > $this->m_maxcol)
            $this->m_maxcol = $col;
          if( $row > $this->m_maxrow)
            $this->m_maxrow = $row;
        }
    
        if( $bPrint)
          echo "mincol={$this->m_mincol} maxcol={$this->m_maxcol} minrow={$this->m_minrow} maxrow={$this->m_maxrow}<br>";
      
        if( $this->m_mincol > $this->m_maxcol)
          $this->m_mincol = $this->m_maxcol;
        if( $this->m_minrow > $this->m_maxrow)
          $this->m_minrow = $this->m_maxrow;
      }


      function savepuzzle( &$crossm, &$crossd, $ctries, $time)
      {
            $N22 = $this->m_best_N20 + 2;

            $cols = $this->m_maxcol - $this->m_mincol + 1;
            $rows = $this->m_maxrow - $this->m_minrow + 1;

            //if( $cols < $rows)
            //  $bSwapColRow = 1;
            //else
            $bSwapColRow = 0;

            if( $bSwapColRow)
            {
                Swap( $cols, $rows);
                Swap( $this->m_mincol, $this->m_minrow);
                Swap( $this->m_maxcol, $this->m_maxrow);
            }
	
	        $crossm = new stdClass();
            $crossm->datebegin = time();
            $crossm->time = $time;
            $crossm->cols = $cols;
            $crossm->rows = $rows;
            $crossm->words = count( $this->m_best_cross_pos);
            $crossm->wordsall = count( $this->m_input_answers);

            $crossm->createscore = $this->m_best_score;
            $crossm->createtries = $ctries;
            $crossm->createtimelimit = $this->m_time_limit;
            $crossm->createconnectors = $this->m_best_connectors;
            $crossm->createfilleds = $this->m_best_filleds;
            $crossm->createspaces = $this->m_best_spaces;
	
            for($i = 0; $i < count($this->m_best_cross_pos); $i++)
            {
                $pos = $this->m_best_cross_pos[ $i];

                $col = $pos % $N22;
                $row = floor( ($pos-$col) / $N22);

                $col += - $this->m_mincol + 1;
                $row += - $this->m_minrow + 1;

                $dir = $this->m_best_cross_dir[ $i];
                $word = $this->m_best_cross_word[ $i];
                $word = substr( $word, 1, strlen( $word)-2);

                $rec = new stdClass();
      
                $rec->col = $col;
                $rec->row = $row;
                $rec->horizontal = ($dir == "h" ? 1 : 0);
      
                $rec->answertext = $word;
                $rec->questiontext = $this->m_input_answers[ $word];
		
                if( $rec->horizontal)
                    $key = sprintf( 'h%10d %10d', $rec->row, $rec->col);
                else
                    $key = sprintf( 'v%10d %10d', $rec->col, $rec->row);
		
                $crossd[ $key] = $rec;
            }
            if( count( $crossd) > 1){
		        ksort( $crossd);
	        }
	
            return (count( $crossd) > 0);
    }

	function swap( &$a, &$b)
	{
		$temp = $a;
		$a = $b;
		$b = $temp;
	}

	function displaycross($puzzle, $N20) 
    {
		$N21 = $N20 + 1;
		$N22 = $N20 + 2;
		$N2222 = $N22 * $N22;
		$N2221 = $N2222  - 1;
		$N2200 = $N2222 - $N22;
    
		$ret = "<table border=0 cellpadding=2 cellspacing=1><tr>";
		for ($n = 0;; $n ++) {
			$c = game_substr( $puzzle, $n, 1);
		
			if (($m = $n % $N22) == 0 or $m == $N21 or $n < $N22 or $n > $N2200) {
				$ret .= "<td class=marc>  </td>";
			} elseif ( $c == '0') {
				$ret .= "<td class=limit> </td>";
			} elseif ($c == '.') {
				$ret .= "<td class=blanc> </td>";
			} else {
				if ((game_substr( $puzzle, $n - 1, 1) > '0' or 
				game_substr( $puzzle, $n + 1, 1) > '0') and
				(game_substr( $puzzle, $n - $N22, 1) > '0' 
				  or game_substr( $puzzle, $n + $N22, 1) > '0')) {
					$ret .= "<td align=center class=connector>$c</td>";
				} else {
					$ret .= "<td align=center class=filled>$c</td>";
				}
			}

		    if ($n == $N2221) {
			    return "$ret</tr></table>";
    		} elseif ($m == $N21) {
	    		$ret .= "</tr><tr>";
	    	}
        }
        return $ret.'</tr></table>';
    }


	function scan_pos($pos, $dir, $val_blanc, &$puzzle, &$words, &$magics, &$poss, &$cross_pos, &$cross_dir, &$cross_word, $N20)
	{
		$MAXW = $N20;
    
		$N22 = $N20 + 2;
		$N2222 = $N22 * $N22;

		if ($dir == 'h'){
			$inc = 1;
			if ($pos + $inc >= $N2222){
				return false;
			}
			$oinc = $N22;
			$new_dir = 'v';
		}else
		{
			$inc = $N22;
			if ($pos + $inc >= $N2222){
				return false;
			}
			$oinc = 1;
			$new_dir = 'h';
		}

	    $regex  = game_substr( $puzzle, $pos, 1);
    	if ( ($regex  == '0' or $regex == '.') and (! $val_blanc)){
	    	return false;
	    }

        if ((game_substr( $puzzle, $pos -  $inc, 1) > '0')){
	    	return false;
	    }

        if ((game_substr( $puzzle, $pos + $inc, 1) > '0')){
	    	return false;
	    }

        $left = $right = 0;
        for ($limit_a = $pos - $inc; ($w = game_substr( $puzzle, $limit_a, 1)) !== '0'; $limit_a -= $inc)
        {
	    	if ($w == '.' and ((game_substr( $puzzle, $limit_a -  $oinc, 1) > '0') or (game_substr( $puzzle, $limit_a +  $oinc, 1) > '0'))){
	    		break;
	    	}
		
	    	if (++$left == $MAXW){
	    		$left --;
	    		break;
	    	}
		
	    	$regex = $w . $regex;
        }

        for ($limit_b = $pos + $inc; ($w = game_substr( $puzzle, $limit_b, 1)) !== '0'; $limit_b += $inc)
        {
	    	if ($w== '.' and ((game_substr( $puzzle, $limit_b -  $oinc, 1) > '0') or (game_substr( $puzzle, $limit_b +  $oinc, 1) > '0'))){
	    		break;
	    	}
		
	    	if (++$right == $MAXW){
	    		$right--;
	    		break;
	    	}
		
	    	$regex .= $w;
        }

        if (($len_regex = game_strlen($regex)) < 2){
	    	return false;
	    }

        foreach ($magics[$len_regex] as $m => $lens)
        {
	    	$ini = max(0, ($left + 1) - $lens);
	    	$fin = $left;

	    	$pos_p = max($limit_a + $inc, $pos - (($lens - 1 ) * $inc));

	    	for($pos_c = $ini; $pos_c <= $fin; $pos_c++, $pos_p += $inc)
	    	{
	    		if (game_substr( $puzzle, $pos_p - $inc, 1) > '0'){
	    			continue;
	    		}

	    		$w = game_substr($regex, $pos_c, $lens);

	    		if( !$this->my_preg_match( $w, $words, $word))
    				continue;

    			$larr0 = $pos_p + ((game_strlen( $word) - 2) * $inc);

    			if ($larr0 >= $N2222){
    				continue;
    			}

    			if (game_substr( $puzzle, $larr0, 1) > '0'){
    				continue;
    			}

    			$words = str_replace( $word, ';', $words);

    			$len = game_strlen( $word) ;
    			for ($n = 1, $pp = $pos_p; $n < $len - 1; $n++, $pp += $inc)
    			{				
    				$this->setchar( $puzzle, $pp,  game_substr( $word , $n, 1));
				
    				if ($pp == $pos)
    					continue;
				
				    $c = game_substr( $puzzle, $pp, 1);
				    $poss[] = array($pp, $new_dir, ord( $c));
			    }

			    $cross_pos[] = $pos_p;
			    $cross_dir[] = ($new_dir == 'h' ? 'v' : 'h');
			    $cross_word[] = $word;

			    $this->setchar( $puzzle, $pos_p - $inc, '0');
			    $this->setchar( $puzzle, $pp, '0');
				
			    return true;
		    }
        }

        return false;
    }

	function my_preg_match( $w, $words, &$word)
	{
		$a = explode( ";", $words);
		$len_w = game_strlen( $w);
		foreach( $a as $test)
		{
			if( game_strlen( $test) != $len_w)
				continue;
			
			for( $i=0; $i <$len_w; $i++)
			{
				if( game_substr( $w, $i, 1) == '.')
					continue;
				if( game_substr( $w, $i, 1)  != game_substr( $test, $i, 1) )
					break;
			}
			if( $i < $len_w)
				continue;
			$word = ';'.$test.';';
			
			return true;
		}
		return false;
	}


	function setchar( &$s, $pos, $char)
	{
		$ret = "";
		
		if( $pos > 0)
			$ret .= game_substr( $s, 0, $pos);
		
		$s = $ret . $char . game_substr( $s, $pos+1, game_strlen( $s)-$pos-1);
	}
	
	function showhtml_base( $crossm, $crossd, $showsolution, $showhtmlsolutions, $showstudentguess, $context, $game)
	{
		$this->m_LegendH = array();
		$this->m_LegendV = array();
          
		$sRet = "CrosswordWidth  = {$crossm->cols};\n";
		$sRet .= "CrosswordHeight = {$crossm->rows};\n";
	  
		$sRet .= "Words=".count( $crossd).";\n";
		$sWordLength = "";
		$sguess = "";
		$ssolutions = '';
		$shtmlsolutions = '';
		$sWordX = "";
		$sWordY = "";
		$sClue = "";
		$LastHorizontalWord = -1;
		$i = -1;
		$LegendV = array();
		$LegendH = array();

        if( $game->glossaryid)
        {
            $cmglossary = get_coursemodule_from_instance('glossary', $game->glossaryid, $game->course);
            $contextglossary = game_get_context_module_instance( $cmglossary->id);
        }
		foreach ($crossd as $rec)
		{
			if( $rec->horizontal == false and $LastHorizontalWord == -1){
				$LastHorizontalWord = $i;
			}
		
			$i++;

			$sWordLength .= ",".game_strlen( $rec->answertext);
			if( $rec->questionid != 0)
			{
    			$q = game_filterquestion(str_replace( '\"', '"', $rec->questiontext), $rec->questionid, $context->id, $game->course);
                $rec->questiontext = game_repairquestion( $q);
    	    }else
    	    {
    	        //glossary
    			$q = game_filterglossary(str_replace( '\"', '"', $rec->questiontext), $rec->glossaryentryid, $contextglossary->id, $game->course);
                $rec->questiontext = game_repairquestion( $q);
            }
    	    
			$sClue .= ',"'.game_tojavascriptstring( game_filtertext( $rec->questiontext, 0))."\"\r\n";
			if( $showstudentguess)
    			$sguess .= ',"'.$rec->studentanswer.'"';
            else
    			$sguess .= ",''";  
			$sWordX .= ",".($rec->col-1);
			$sWordY .= ",".($rec->row-1);
			if( $showsolution){
				$ssolutions .= ',"'.$rec->answertext.'"';
			}else
			{
				$ssolutions .= ',""';
			}
      
     		if( $showhtmlsolutions){
				$shtmlsolutions .= ',"'.base64_encode( $rec->answertext).'"';
			}
			
			$attachment = '';
		    //if( game_issoundfile( $rec->attachment)){
            //    $attachment = game_showattachment( $rec->attachment);
		    //}
						
            $s = $rec->questiontext.$attachment;
			if( $rec->horizontal){
				if( array_key_exists( $rec->row, $LegendH)){
				    $LegendH[ $rec->row][] = $s;
				}else
				{
					$LegendH[ $rec->row] = array( $s);
				}
			}else
			{
				if( array_key_exists( $rec->col, $LegendV)){
				    $LegendV[ $rec->col][] = $s;
				}else
				{
					$LegendV[ $rec->col] = array( $s);
				}
			}
		}
		
		$letters = get_string( 'lettersall', 'game');

		$this->m_LegendH = array();
		foreach( $LegendH as $key => $value)
		{
		    if( count( $value) == 1)
		       $this->m_LegendH[ $key] = $value[ 0]; 
		    else
		    {
		        for( $i=0; $i < count( $value); $i++)
		        {
		            $this->m_LegendH[ $key.game_substr( $letters, $i, 1)] = $value[ $i];
                }
		    }
		}
		
		$this->m_LegendV = array();
		foreach( $LegendV as $key => $value)
		{
		    if( count( $value) == 1)
		       $this->m_LegendV[ $key] = $value[ 0]; 
		    else
		    {
		        for( $i=0; $i < count( $value); $i++)
		        {
		            $this->m_LegendV[ $key.game_substr( $letters, $i, 1)] = $value[ $i];
                }
		    }
		}
		
		ksort( $this->m_LegendH);
		ksort( $this->m_LegendV);

		$sRet .= "WordLength = new Array( ".game_substr( $sWordLength, 1).");\n";
		$sRet .= "Clue = new Array( ".game_substr( $sClue, 1).");\n";
		$sguess = str_replace( ' ', '_', $sguess);
		$sRet .= "Guess = new Array( ".game_substr( $sguess, 1).");\n";
		$sRet .= "Solutions = new Array( ".game_substr( $ssolutions, 1).");\n";
		if( $showhtmlsolutions){
		    $sRet .= "HtmlSolutions = new Array( ".game_substr( $shtmlsolutions, 1).");\n";
		}
		$sRet .= "WordX = new Array( ".game_substr( $sWordX, 1).");\n";
		$sRet .= "WordY = new Array( ".game_substr( $sWordY, 1).");\n";
		$sRet .= "LastHorizontalWord = $LastHorizontalWord;\n";

		return $sRet;
    }


	function cmp($a, $b) {
		return game_strlen($b) - game_strlen($a);
	}


	function cmp_magic($a, $b) {
		return (game_strlen($a) + mt_rand(0, 3)) - (game_strlen($b) - mt_rand(0, 1));
	}
}
