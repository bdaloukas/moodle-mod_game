<?php

//
// Edit History:
//
//  Dick Munroe (munroe@csworks.com) 02-Nov-2005
//      Initial version created.
//
//  Dick Munroe (munroe@csworks.com) 12-Nov-2005
//      Allow initialzePuzzle to accept a file name in addition
//      to a resource.  Windows doesn't do file redirection properly
//      so the examples have to be able to handle a file NAME as
//      input as well as a redirected file.
//      Allow initializePuzzle to accept a string of 81 characters.
//
//  Dick Munroe (munroe@csworks.com) 13-Nov-2005
//	It appears that getBoardAsString screws up somehow.  Rewrite it.
//
//  Dick Munroe (munroe@csworks.com) 16-Nov-2005
//      Add a "pair" inference.
//
//	Dick Munroe (munroe@csworks.com) 17-Nov-2005
//		Add comments to input files.
//		There was a bug in _applyTuple that caused premature exiting of the inference
//		engine.
//		If SDD isn't present, don't display error.
//
//	Dick Munroe (munroe@csworks.com) 19-Nov-2005
//		Add a new tuple inference.
//		Do a ground up ObjectS oriented redesign to make the addition of arbitrary
//		inferences MUCH easier.
//		Get the printing during solving right.
//		Somehow array_equal developed a "bug".
//
//	Dick Munroe (munroe@csworks.com) 22-Nov-2005
//		Add n,n+1 tuple recognition for n=2.
//		Restructure inference engine to get maximum benefit out of each pass.
//
//	Dick Munroe (munroe@csworks.com) 28-Nov-2005
//		Attempt to build harder Sudoku by implementing a coupling coefficient
//		attempting to distribute clues more optimally.
//

@include_once "SDD/class.SDD.php" ;

/**
 * @author Dick Munroe <munroe@csworks.com>
 * @copyright copyright @ 2005 by Dick Munroe, Cottage Software Works, Inc.
 * @license http://www.csworks.com/publications/ModifiedNetBSD.html
 * @version 2.2.0
 * @package Sudoku
 */

/**
 * Basic functionality needed for ObjectSs in the Sudoku solver.
 *
 * Technically speaking these aren't restricted to the Sudoku classes
 * and are of use generally.
 *
 * @package Sudoku
 */

class ObjectS
{
	/**
	 * @desc Are two array's equal (have the same contents).
	 * @param array
	 * @param array
	 * @return boolean
	 */
	
	function array_equal($theArray1, $theArray2)
	{
		if (!(is_array($theArray1) && is_array($theArray2)))
		{
			return false ;
		}
		
		if (count($theArray1) != count($theArray2))
		{
			return false ;
		}
		
		$xxx = array_diff($theArray1, $theArray2) ;
		
	    return (count($xxx) == 0) ;
	}
	
    /**
     * Deep copy anything.
     * 
     * @access public
     * @param array $theArray [optional] Something to be deep copied.  [Default is the current
     *                        ObjectS.
     * @return mixed The deep copy of the input.  All references embedded within
     *               the array have been resolved into copies allowing things like the
     *               board array to be copied.
     */
    
    function deepCopy($theArray = NULL)
    {
    	if ($theArray === NULL)
    	{
    		return unserialize(serialize($this)) ;
    	}
    	else
		{
    		return unserialize(serialize($theArray)) ;
    	}
    }
    
	/**
	 * @desc Debugging output interface.
	 * @access public
	 * @param mixed $theValue The "thing" to be pretty printed.
	 * @param boolean $theHTMLFlag True if the output will be seen in a browser, false otherwise.
	 */

    function print_d(&$theValue, $theHTMLFlag = true)
    {
    	print SDD::dump($theValue, $theHTMLFlag) ;
    }
}

/**
 * The individual cell on the Sudoku board.
 *
 * These cells aren't restricted to 9x9 Sudoku (although pretty much everything else
 * at the moment).  This class provides the state manipulation and searching capabilities
 * needed by the inference engine (class RCS).
 *
 * @package Sudoku
 */

class Cell extends ObjectS
{
	var $r ;
	var $c ;
	
	var $state = array() ;
	var $applied = false ;
	

	/**
	 * @desc Constructor
	 * @param integer $r row address of this cell (not used, primarily for debugging purposes).
	 * @param integer $c column address of this cell (ditto).
	 * @param integer $nStates The number of states each cell can have.  Looking forward to
	 *                         implementing Super-doku.
	 */
	 
	function Cell($r, $c, $nStates = 9)
	{
	
		$this->r = $r ;
		$this->c = $c ;
		
		for ($i = 1; $i <= $nStates; $i++)
		{
			$this->state[$i] = $i ;
		}
	}
	
	/**
	 * @desc This cell has been "applied", i.e., solved, to the board.
	 */
	 
	function applied()
	{
		$this->applied = true ;
	}
	
	/**
	 * Only those cells which are not subsets of the tuple have the
	 * contents of the tuple removed.
	 *
	 * @desc apply a 23Tuple to a cell.
	 * @access public
	 * @param array $aTuple the tuple to be eliminated.
	 */
	 
	function apply23Tuple($aTuple)
	{
		if (is_array($this->state))
		{
			$xxx = array_intersect($this->state, $aTuple) ;
			if ((count($xxx) > 0) && (count($xxx) != count($this->state)))
			{
				return $this->un_set($aTuple) ;
			}
			else
			{
				return false ;
			}
		}
		else
		{
			return false ;
		}
	}
	
	/**
	 * For more details on the pair tuple algorithm, see RCS::_pairSolution.
	 *
	 * @desc Remove all values in the tuple, but only if the cell is a superset.
	 * @access public
	 * @param array A tuple to be eliminated from the cell's state.
	 */
	 
	function applyTuple($aTuple)
	{
		if (is_array($this->state))
		{
			if (!$this->array_equal($aTuple, $this->state))
			{
				return $this->un_set($aTuple) ;
			}
		}
		
		return false ;
	}
	
	/**
	 * @desc Return the string representation of the cell.
	 * @access public
	 * @param boolean $theFlag true if the intermediate states of the cell are to be visible.
	 * @return string
	 */
	 
	function asString($theFlag = false)
	{
		if (is_array($this->state))
		{
			if (($theFlag) || (count($this->state) == 1))
			{
				return implode(", ", $this->state) ;
			}
			else
			{
				return " " ;
			}
		}
		else
		{
			return $this->state ;
		}
	}
	
    /**
     * Used to make sure that solved positions show up at print time.
     * The value is used as a candidate for "slicing and dicing" by elimination in
     * Sudoku::_newSolvedPosition.
     *
     * @desc Assert pending solution.
     * @access public
     * @param integer $value The value for the solved position.
     */
    
    function flagSolvedPosition($value)
    {
        $this->state = array($value => $value) ;
    }
    
    /**
     * @desc return the state of a cell.
     * @access protected
     * @return mixed Either solved state or array of state pending solution.
     */
     
    function &getState()
    {
    	return $this->state ;
    }
    
	/**
	 * @desc Has the state of this cell been applied to the board.
	 * @access public
	 * @return boolean True if it has, false otherwise.  Implies that IsSolved is true as well.
	 */
	 
    function IsApplied()
    {
    	return $this->applied ;
    }
    
    /**
     * @desc Has this cell been solved?
     * @access public
     * @return boolean True if this cell has hit a single state.
     */
     
    function IsSolved()
    {
    	return !is_array($this->state) ;
    }
    
    /**
     * This is used primarily by the pretty printer, but has other applications
     * in the code.
     *
     * @desc Return information about the state of a cell.
     * @access public
     * @return integer 0 => the cell has been solved.
     *                 1 => the cell has been solved but not seen a solved.
     *                 2 => the cell has not been solved.
     */
     
	function solvedState()
	{
		if (is_array($this->state))
		{
			if (count($this->state) == 1)
			{
				return 1 ;
			}
			else
			{
				return 2 ;
			}
		}
		else
		{
			return 0 ;
		}
	}
	
	/**
	 * This is the negative inference of Sudoku.  By eliminating values the
	 * cells approach solutions.  Once a cell has been completely eliminated,
	 * the value causing the complete elimination must be the solution and the
	 * cell is promoted into the solved state.
	 *
	 * @desc Eliminate one or more values from the state information of the cell.
	 * @access public
	 * @param mixed The value or values to be removed from the cell state.
	 * @return boolean True if the cell state was modified, false otherwise.
	 */
	 
	function un_set($theValues)
	{
		if (is_array($theValues))
		{
			$theReturn = FALSE ;
			
			foreach ($theValues as $theValue)
			{
				$theReturn |= $this->un_set($theValue) ;
			}
			
			return $theReturn ;
		}
		
		if (is_array($this->state))
		{
			$theReturn = isset($this->state[$theValues]) ;
			unset($this->state[$theValues]) ;
			if (count($this->state) == 0)
			{
				$this->state = $theValues ;
			}
			return $theReturn ;
		}
		else
		{
			return false ;
		}
	}
}

/**
 * The individual row column or square on the Sudoku board.
 *
 * @package Sudoku
 */

class RCS extends ObjectS
{
	var $theIndex ;
	
	var $theRow = array() ;
	
	var $theHeader = "" ;
	
	var $theTag = "" ;
	
	/**
	 * This 
	 * @desc Constructor
	 * @access public
	 * @param string $theTag "Row", "Column", "Square", used primarily in debugging.
	 * @param integer $theIndex 1..9, where is this on the board.  Square are numbered top
	 *                          left, ending bottom right
	 * @param ObjectS $a1..9 of class Cell.  The cells comprising this entity.  This interface is what
	 *                                      limts things to 9x9 Sudoku currently.
	 */
	 
	function RCS($theTag, $theIndex, &$a1, &$a2, &$a3, &$a4, &$a5, &$a6, &$a7, &$a8, &$a9)
	{
		$this->theTag = $theTag ;
		$this->theIndex = $theIndex ;
		$this->theRow[1] = &$a1 ;
		$this->theRow[2] = &$a2 ;
		$this->theRow[3] = &$a3 ;
		$this->theRow[4] = &$a4 ;
		$this->theRow[5] = &$a5 ;
		$this->theRow[6] = &$a6 ;
		$this->theRow[7] = &$a7 ;
		$this->theRow[8] = &$a8 ;
		$this->theRow[9] = &$a9 ;
	}

	/**
	 * There is a special case that comes up a lot in Sudoku.  If there
	 * are values i, j, k and cells of the form (i, j), (j, k), (i, j, k)
	 * the the values i, j, and k cannot appear in any other cells.  The
	 * proof is a simple "by contradiction" proof.  Assume that the values
	 * do occur elsewhere and you always get a contradiction for these
	 * three cells.  I'm pretty sure that this is a general rule, but for
	 * 9x9 Sudoku, they probably aren't of interested.
	 *
	 * @desc
	 * @access private
	 * @return boolean True if a 23 solution exists and has been applied.
	 */
	 
	function _23Solution()
	{
        $theCounts = array() ;
        $theTuples = array() ;
		$theUnsolved = 0 ;
        
        for ($i = 1; $i <= 9; $i++)
        {
          $j = count($this->theRow[$i]->getState());
        	$theCounts[ $j][] = $i ;
        	$theUnsolved++ ;
        }

    if( array_key_exists( 2, $theCounts) and array_key_exists( 3, $theCounts))
    {
  		if ((count($theCounts[2]) < 2) || (count($theCounts[3]) < 1))
  			return false ;
		}
		
		/*
		 * Look at each pair of 2 tuples and see if their union exists in the 3 tuples.
		 * If so, eliminate everything from the set and bail.
		 */

		$the2Tuples = &$theCounts[2] ;
		$the3Tuples = &$theCounts[3] ;		 
		$theCount2 = count($the2Tuples) ;
		$theCount3 = count($the3Tuples) ;
		
		for ($i = 0; $i < $theCount2 - 1; $i++)
		{
			for ($j = $i + 1; $j < $theCount2; $j++)
			{
				$xxx = array_unique(array_merge($this->theRow[$the2Tuples[$i]]->getState(),
												$this->theRow[$the2Tuples[$j]]->getState())) ;
				for ($k = 0; $k < $theCount3; $k++)
				{
					if ($this->array_equal($xxx, $this->theRow[$the3Tuples[$k]]->getState()))
					{
						$theTuples[] = $xxx ;
						break ;
					}
				}
			}
		}
		
		/*
		 * Since it takes 3 cells to construct the 23 tuple, unless there are more than 3
		 * unsolved cells, further work doesn't make any sense.
		 */
		 
		$theReturn = false ;
		
		if ((count($theTuples) != 0) && ($theUnsolved > 3))
		{
			foreach ($theTuples as $aTuple)
			{
				foreach($this->theRow as $theCell)
				{
					$theReturn |= $theCell->apply23Tuple($aTuple) ;
				}
			}
		}
		
		if ($theReturn) 
		{
			$this->theHeader[] = sprintf("<br />Apply %s[%d] 23 Tuple Inference:", $this->theTag, $this->theIndex) ;
		}
		
        return $theReturn ;
	}
	
    /**
     * @desc apply a tuple to exclude items from within the row/column/square.
     * @param array $aTuple the tuple to be excluded.
     * @access private
	 * @return boolean true if anything changes.
     */
    
    function _applyTuple(&$aTuple)
    {
        $theReturn = FALSE ;
        
        for ($i = 1; $i <=9; $i++)
        {
        	$theReturn |= $this->theRow[$i]->applyTuple($aTuple) ;
        }
        
        return $theReturn ;
    }
    
    /**
     * This is a placeholder to be overridden to calculate the "coupling" for
     * a cell.  Coupling is defined to be the sum of the sizes of the intersection
     * between this cell and all others in the row/column/square.  This provides
     * a metric for deciding placement of clues within puzzles.  In effect, this
     * forces the puzzle generator to select places for new clues depending upon
     * how little information is changed by altering the state of a cell.  The larger
     * the number returned by the coupling, function, the less information is currently
     * available for the state of the cell.  By selecting areas with the least information
     * the clue sets are substantially smaller than simple random placement.
     *
     * @desc Calculate the coupling for a cell within the row/column/square.
     * @access abstract
     * @param integer $theRow the row coordinate on the board of the cell.
     * @param integer $theColumn the column coordinate on the board of the cell.
     * @return integer the degree of coupling between the cell and the rest of the cells
     *				   within the row/column/square.
     */
    
    function coupling($theRow, $theColumn)
    {
    	return 0 ;
    }
    
    /**
     * I think that the goal of the inference engine is to eliminate
     * as much "junk" state as possible on each pass.  Therefore the
     * order of the inferences should be 23 tuple, pair, unique because
     * the 23 tuple allows you to eliminate 3 values (if it works), and the
     * pair (generally) only 2.  The unique solution adds no new information.
     *
     * @desc Run the inference engine for a row/column/square.
     * @access public
     * @param array theRow A row/column/square data structure.
     * @param string theType A string merged with the standard headers during
     *               intermediate solution printing.
     * @return boolean True when at least one inference has succeeded.
     */
     
    function doAnInference()
    {
    	$this->theHeader = NULL ;
    	
		$theReturn = $this->_23Solution() ;
		$theReturn |= $this->_pairSolution() ;
 		$theReturn |= $this->_uniqueSolution() ;
 		
 		return $theReturn ;
 	}

    /**
     * @desc Find all tuples with the same contents.
     * @param array Array of n size tuples.
     * @returns array of tuples that appear the same number of times as the size of the contents
     */
    
    function _findTuples(&$theArray)
    {
        $theReturn = array() ;
        for ($i = 0; $i < count($theArray); $i++)
        {
            $theCount = 1 ;

            for ($j = $i + 1; $j < count($theArray); $j++)
            {
            	$s1 = &$this->theRow[$theArray[$i]] ;
            	$s1 =& $s1->getState() ;
            	
            	$s2 = &$this->theRow[$theArray[$j]] ;
            	$s2 =& $s2->getState() ;
            	
            	$aCount = count($s1) ;
            	
                if ($this->array_equal($s1, $s2))
                {
                    $theCount++ ;

                    if ($theCount == $aCount)
                    {
                        $theReturn[] = $s1 ;
                        break ;
                    }
                }
            }
        }

        return $theReturn ;
    }
    
    /**
     * @desc Get a reference to the specified cell.
     * @access public
     * @return reference to ObjectS of class Cell.
     */
    
	function &getCell($i)
	{
		return $this->theRow[$i] ;
	}
	
	/**
	 * @desc Get the header set by the last call to doAnInference.
	 * 
	 */
	
	function getHeader()
	{
		return $this->theHeader ;
	}
	
    /**
     * Turns out if you every find a position of n squares which can only contain
     * the same values, then those values cannot appear elsewhere in the structure.
     * This is a second positive inference that provides additional negative information.
     * Thanks to Ghica van Emde Boas (also an author of a Sudoku class) for convincing
     * me that these situations really occurred.
     * 
     * @desc Eliminate tuple-locked alternatives.
     * @access private
	 * @return boolean True if something changed.
     */
    
    function _pairSolution()
    {
        $theCounts = array() ;
        $theTuples = array() ;
        
        for ($i = 1; $i <= 9; $i++)
        {
        	$c = &$this->theRow[$i] ;
        	$theCounts[count($c->getState())][] = $i ;
        }
        
		unset($theCounts[1]) ;
		
        /*
        ** Get rid of any set of counts which cannot possibly meet the
        ** requirements.
        */
        
        $thePossibilities = $theCounts ;
        
        foreach ($theCounts as $theKey => $theValue)
        {
            if (count($theValue) < $theKey)
            {
                unset($thePossibilities[$theKey]) ;
            }
        }
        
        if (count($thePossibilities) == 0)
        {
            return false ;
        }
        
        /*
         * At this point there are 1 or more tuples which MAY satisfy the conditions.
         */

		$theReturn = false ;
		
        foreach ($thePossibilities as $theValue)
        {
            $theTuples = $this->_findTuples($theValue) ;
            
            if (count($theTuples) != 0)
            {
                foreach ($theTuples as $aTuple)
                {
                    $theReturn |= $this->_applyTuple($aTuple) ;
                }
            }
        }

		if ($theReturn) 
		{
			$this->theHeader[] = sprintf("<br />Apply %s[%d] Pair Inference:", $this->theTag, $this->theIndex) ;
		}
		
        return $theReturn ;
    }

	function un_set($theValues)
	{
		$theReturn = false ;
		
		for ($i = 1; $i <= 9; $i++)
		{
			$c = &$this->theRow[$i] ;
			$theReturn |= $c->un_set($theValues) ;
		}
		
		return $theReturn ;
	}
	
    /**
     * Find a solution to a row/column/square.
     * 
     * Find any unique numbers within the row/column/square under consideration.
     * Look through a row structure for a value that appears in only one cell.  
     * When you find one, that's a solution for that cell.
     *
     * There is a second inference that can be taken.  Given "n" cells in a row/column/square
     * and whose values can only consist of a set of size "n", then those values may obtain
     * there and ONLY there and may be eliminated from consideration in the rest of the set.
     * For example, if two cells must contain the values 5 or 6, then no other cell in that
     * row/column/square may contain those values, similarly for 3 cells, etc.
     *
     * @access private
     * @return boolean True if one or more values in the RCS has changed state.
     */
    
    function _uniqueSolution()
    {
        $theSet = array() ;
        
        for ($i = 1; $i <= 9; $i++)
        {
        	$c = &$this->theRow[$i] ;
            if (!$c->IsSolved())
            {
            	foreach ($c->getState() as $theValue)
            	{
            		$theSet[$theValue][] = $i ;
            	}
            }
        }
        
		/*
		 * If there were no unsolved positions, then we're done and nothing has
		 * changed.
		 */
		 
        if (count($theSet) == 0)
        {
            return false ;
        }
        		
		/*
		 * Pull out all those keys having only one occurrance in the RCS.
		 */
		 
		foreach ($theSet as $theKey => $theValues)
		{
			if (count($theValues) != 1)
			{
				unset($theSet[$theKey]) ;
			}
		}

		/*
		 * If there aren't any unique values, we're done.
		 */
		 
		if (count($theSet) == 0)
		{
			return false ;
		}
		
		foreach ($theSet as $theValue => $theIndex)
		{
			$this->theRow[$theIndex[0]]->flagSolvedPosition($theValue) ;
		}
				
		$this->theHeader[] = sprintf("<br />Apply %s[%d] Unique Inference:", $this->theTag, $this->theIndex) ;
		
		return true ;
    }

	/**
	 * @desc Check to see if the RCS contains a valid state.
	 * @access public
	 * @return boolean True if the state of the RCS could be part of a valid
	 *				   solution, false otherwise.
	 */
	 
	function validateSolution()
	{
		$theNewSet = array() ;
		
		foreach ($this->theRow as $theCell)
		{
			if ($theCell->solvedState() == 0)
			{
				$theNewSet[] = $theCell->getState() ;
			}
		}
		
		$xxx = array_unique($theNewSet) ;
		
		return (count($xxx) == count($this->theRow)) ;
	}
	
    /**
     * Validate a part of a trial solution.
     * 
     * Check a row/column/square to see if there are any invalidations on this solution.
     * Only items that are actually solved are compared.  This is used during puzzle
     * generation.
     *
     * @access public
     * @return True if the input parameter contains a valid solution, false otherwise.
     */
    
    function validateTrialSolution()
    {
        $theNewSet = array() ;
        
        foreach($this->theRow as $theCell)
        {
        	if ($theCell->solvedState() == 0)
        	{
        		$theNewSet[] = $theCell->getState() ;
        	}
        }
        
        $xxx = array_unique($theNewSet) ;

        return ((count($xxx) == count($theNewSet) ? TRUE : FALSE)) ;
    }
}

/**
 * Row ObjectS.
 *
 * @package Sudoku
 */

class R extends RCS
{
	/**
	 * @desc Constructor
	 * @access public
	 * @param string $theTag "Row", "Column", "Square", used primarily in debugging.
	 * @param integer $theIndex 1..9, where is this on the board.  Square are numbered top
	 *                          left, ending bottom right
	 * @param ObjectS $a1..9 of class Cell.  The cells comprising this entity.  This interface is what
	 *                                      limts things to 9x9 Sudoku currently.
	 */
	 
	function R($theTag, $theIndex, &$a1, &$a2, &$a3, &$a4, &$a5, &$a6, &$a7, &$a8, &$a9)
	{
		$this->RCS($theTag, $theIndex, $a1, $a2, $a3, $a4, $a5, $a6, $a7, $a8, $a9) ;
	}

	/**
	 * @see RCS::coupling
	 */
	 
	function coupling($theRow, $theColumn)
	{
		return $theState = $this->_coupling($theColumn) ;
	}
	
	/**
	 * @see RCS::coupling
	 * @desc Heavy lifting for row/column coupling calculations.
	 * @access private
	 * @param integer $theIndex the index of the cell within the row or column.
	 * @return integer the "coupling coefficient" for the cell.  The sum of the
	 *    			   sizes of the intersection between this and all other
	 *				   cells in the row or column.
	 */
	 
	function _coupling($theIndex)
	{
		$theCommonState =& $this->getCell($theIndex) ;
		$theCommonState =& $theCommonState->getState() ;

		$theCoupling = 0 ;
		
		for ($i = 1; $i <= count($this->theRow); $i++)
		{
			if ($i != $theIndex)
			{
				$theCell =& $this->getCell($i) ;
				if ($theCell->solvedState() != 0)
				{
					$theCoupling += count(array_intersect($theCommonState, $theCell->getState())) ;
				}
			}
		}
		
		return $theCoupling ;
	}
}

/**
 * The column ObjectS.
 *
 * @package Sudoku
 */

class C extends R
{
	/**
	 * @desc Constructor
	 * @access public
	 * @param string $theTag "Row", "Column", "Square", used primarily in debugging.
	 * @param integer $theIndex 1..9, where is this on the board.  Square are numbered top
	 *                          left, ending bottom right
	 * @param ObjectS $a1..9 of class Cell.  The cells comprising this entity.  This interface is what
	 *                                      limts things to 9x9 Sudoku currently.
	 */
	 
	function C($theTag, $theIndex, &$a1, &$a2, &$a3, &$a4, &$a5, &$a6, &$a7, &$a8, &$a9)
	{
		$this->R($theTag, $theIndex, $a1, $a2, $a3, $a4, $a5, $a6, $a7, $a8, $a9) ;
	}

	/**
	 * @see R::coupling
	 */
	 
	function coupling($theRow, $theColumn)
	{
		return $theState = $this->_coupling($theRow) ;
	}
	
}

/**
 * The Square ObjectS.
 *
 * @package Sudoku
 */

class S extends RCS
{
	/**
	 * The cells within the 3x3 sudoku which participate in the coupling calculation for a square.
	 * Remember that the missing cells have already participated in the row or column coupling
	 * calculation.
	 *
	 * @access private
	 * @var array
	 */
	 
	var $theCouplingOrder =
		array( 1 => array(5, 6, 8, 9),
			   2 => array(4, 6, 7, 9),
			   3 => array(4, 5, 7, 8),
			   4 => array(2, 3, 8, 9),
			   5 => array(1, 3, 7, 9),
			   6 => array(1, 2, 7, 8),
			   7 => array(2, 3, 5, 6),
			   8 => array(1, 3, 4, 6),
			   9 => array(1, 2, 4, 5)) ;
			   
	/**
	 * @desc Constructor
	 * @access public
	 * @param string $theTag "Row", "Column", "Square", used primarily in debugging.
	 * @param integer $theIndex 1..9, where is this on the board.  Square are numbered top
	 *                          left, ending bottom right
	 * @param ObjectS $a1..9 of class Cell.  The cells comprising this entity.  This interface is what
	 *                                      limts things to 9x9 Sudoku currently.
	 */
	 
	function S($theTag, $theIndex, &$a1, &$a2, &$a3, &$a4, &$a5, &$a6, &$a7, &$a8, &$a9)
	{
		$this->RCS($theTag, $theIndex, $a1, $a2, $a3, $a4, $a5, $a6, $a7, $a8, $a9) ;
	}

	/**
	 * @see RCS::coupling
	 */
	 
	function coupling($theRow, $theColumn)
	{
		$theIndex = ((($theRow - 1) % 3) * 3) + (($theColumn - 1) % 3) + 1 ;
		$theCommonState =& $this->getCell($theIndex) ;
		$theCommonState =& $theCommonState->getState() ;
		
		$theCoupling = 0 ;
		
		foreach ($this->theCouplingOrder[$theIndex] as $i)
		{
			$theCell =& $this->getCell($i) ;
			if ($theCell->solvedState() != 0)
			{
				$theCoupling += count(array_intersect($theCommonState, $theCell->getState())) ;
			}
		}
		
		return $theCoupling ;
	}
}

/**
 * Solve and generate Sudoku puzzles.
 *
 * Solve and generate Sudoku.  A simple output interface is provided for
 * web pages.  The primary use of this class is as infra-structure for
 * Sudoku game sites.
 *
 * The solver side of this class (solve) relies on the usual characteristic
 * of logic puzzles, i.e., at any point in time there is one (or more)
 * UNIQUE solution to some part of the puzzle.  This solution can be
 * applied, then iterated upon to find the next part of the puzzle.  A
 * properly constructed Sudoku can have only one solution which guarangees
 * that this is the case. (Sudoku with multiple solutions will always
 * require guessing at some point which is specifically disallowed by
 * the rules of Sudoku).
 *
 * While the solver side is algorithmic, the generator side is much more
 * difficult and, in fact, the generation of Sudoku appears to be NP
 * complete.  That being the case, I observed that most successful
 * generated initial conditions happened quickly, typically with < 40
 * iterations.  So the puzzle generator runs "for a while" until it
 * either succeeds or doesn't generated a solveable puzzle.  If we get
 * to that position, I just retry and so far I've always succeeded in
 * generating an initial state.  Not guarateed, but in engineering terms
 * "close enough".
 * 
 * @package Sudoku
 * @example ./example.php
 * @example ./example1.php
 * @example ./example2.php
 * @example ./example3.php
*/

class Sudoku extends ObjectS
{
	/**
	 * An array of Cell ObjectSs, organized into rows and columns.
	 *
	 * @access private
	 * @var array of ObjectSs of type Cell.
	 */
	 
	var $theBoard = array() ;

    /**
     * True if debugging output is to be provided during a run.
     *
     * @access private
     * @var boolean
     */

    var $theDebug = FALSE ;

    /**
     * An array of RCS ObjectSs, one ObjectS for each row.
     *
     * @access private
     * @var ObjectS of type R
    */

    var $theRows = array() ;
	
    /**
     * An array of RCS ObjectSs, one ObjectS for each Column.
     *
     * @access private
     * @var ObjectS of type C
    */

	var $theColumns = array() ;
	
    /**
     * An array of RCS ObjectSs, one ObjectS for each square.
     *
     * @access private
     * @var ObjectS of type S
    */

	var $theSquares = array() ;
		
    /**
     * Used during puzzle generation for debugging output.  There may
     * eventually be some use of theLevel to figure out where to stop
     * the backtrace when puzzle generation fails.
     * 
     * @access private
     * @var integer.
     */

    var $theLevel = 0 ;

    /**
     * Used during puzzle generation to determine when the generation
     * will fail.  Failure, in this case, means to take a LONG time.  The
     * backtracing algorithm used in the puzzle generator will always find
     * a solution, it just might take a very long time.  This is a way to
     * limit the damage before taking another guess.
     * 
     * @access private
     * @var integer.
     */

    var $theMaxIterations = 50 ;
    
    /**
     * Used during puzzle generation to limit the number of trys at
     * generation a puzzle in the event puzzle generation fails
     * (@see Suduko::$theMaxIterations).  I've never seen more than
     * a couple of failures in a row, so this should be sufficient
     * to get a puzzle generated.
     * 
     * @access private
     * @var integer.
     */

    var $theTrys = 10 ;
    
    /**
     * Used during puzzle generation to count the number of iterations
     * during puzzle generation.  It the number gets above $theMaxIterations,
     * puzzle generation has failed and another try is made.
     * 
     * @access private
     * @var integer.
     */

    var $theGenerationIterations = 0 ;
        
	function Sudoku($theDebug = FALSE)
	{
		$this->theDebug = $theDebug ;

		for ($i = 1; $i <= 9; $i++)
		{
			for ($j = 1; $j <= 9; $j++)
			{
				$this->theBoard[$i][$j] = new Cell($i, $j) ;
			}
		}

		$this->_buildRCS() ;
	}
	
    /**
     * Apply a pending solved position to the row/square/column.
     * 
     * At this point, the board has been populated with any pending solutions.
     * This applies the "negative" inference that no row, column, or square 
     * containing the value within the cell.
     *
     * @access private
     * @param integer $row The row of the board's element whose value is now fixed.
     * @param integer $col The column of the board's element whose value is now fixed.
     */
    
    function _applySolvedPosition($row, $col)
    {
        $theValue = $this->theBoard[$row][$col]->getState() ;
		
        /*
        ** No other cell in the row, column, or square can take on the value "value" any longer.
        */
        
        $i = (((int)(($row - 1) / 3)) * 3) ;
        $i = $i + ((int)(($col - 1) / 3)) + 1 ;

        $this->theRows[$row]->un_set($theValue) ;
        
        $this->theColumns[$col]->un_set($theValue) ;
        
        $this->theSquares[$i]->un_set($theValue) ;
    }
    
    /**
     * @desc Apply all pending solved positions to the board.
     * @access private
     * @return boolean True if at least one solved position was applied, false
     *                 otherwise.
     */
    
    function _applySolvedPositions()
    {
        $theReturn = false ;
        
        for ($i = 1; $i <= 9; $i++)
        {
            for ($j = 1; $j <= 9; $j++)
            {
            	if (!$this->theBoard[$i][$j]->IsApplied())
                {
                    if ($this->theBoard[$i][$j]->solvedState() == 0)
                    {
                        $this->_applySolvedPosition($i, $j) ;
        
                        /*
                        ** Update the solved position matrix and make sure that the board actually
                        ** has a value in place.
                        */
        
                        $this->theBoard[$i][$j]->applied() ;
                        $theReturn = TRUE ;
                    }
                }
            }
        }
        
        return $theReturn ;
    }
    
    /**
     * @desc build the row/column/square structures for the board.
     * @access private
     */
     
	function _buildRCS()
	{
		for ($i = 1; $i <= 9; $i++)
		{
			$this->theRows[$i] = 
				new R("Row",
						$i,
						$this->theBoard[$i][1],
						$this->theBoard[$i][2],
						$this->theBoard[$i][3],
						$this->theBoard[$i][4],
						$this->theBoard[$i][5],
						$this->theBoard[$i][6],
						$this->theBoard[$i][7],
						$this->theBoard[$i][8],
						$this->theBoard[$i][9]) ;
			$this->theColumns[$i] = 
				new C("Column",
						$i,
						$this->theBoard[1][$i],
						$this->theBoard[2][$i],
						$this->theBoard[3][$i],
						$this->theBoard[4][$i],
						$this->theBoard[5][$i],
						$this->theBoard[6][$i],
						$this->theBoard[7][$i],
						$this->theBoard[8][$i],
						$this->theBoard[9][$i]) ;
			
			$r = ((int)(($i - 1) / 3)) * 3 ;
			$c = (($i - 1) % 3) * 3 ;
			
			$this->theSquares[$i] = 
				new S("Square",
						$i,
						$this->theBoard[$r + 1][$c + 1],
						$this->theBoard[$r + 1][$c + 2],
						$this->theBoard[$r + 1][$c + 3],
						$this->theBoard[$r + 2][$c + 1],
						$this->theBoard[$r + 2][$c + 2],
						$this->theBoard[$r + 2][$c + 3],
						$this->theBoard[$r + 3][$c + 1],
						$this->theBoard[$r + 3][$c + 2],
						$this->theBoard[$r + 3][$c + 3]) ;
		}
	}
	
    /**
     * Seek alternate solutions in a solution set.
     * 
     * Given a solution, see if there are any alternates within the solution.
     * In theory this should return the "minimum" solution given any solution.
     *
     * @access public
     * @param array $theInitialState (@see Sudoku::initializePuzzleFromArray)
     * @return array A set of triples containing the minimum solution.
     */
    
    function findAlternateSolution($theInitialState)
    {
        $j = count($theInitialState) ;

        for ($i = 0; $i < $j; $i++)
        {
            $xxx = $theInitialState ;
            
            $xxx = array_splice($xxx, $i, 1) ;
            
            $this->Sudoku() ;
            
            $this->initializePuzzleFromArray($xxx) ;
            
            if ($this->solve())
            {
                return $this->findAlternateSolution($xxx) ;
            }
        }

        return $theInitialState ;
    }
    
    /**
     * Initialize Sudoku puzzle generation and generate a puzzle.
     * 
     * Turns out that while the solution of Sudoku is mechanical, the creation of
     * Sudoku is an NP-Complete problem.  Which means that I can use the inference
     * engine to help generate puzzles, but I need to test the solution to see if
     * I've gone wrong and back up and change my strategy.  So something in the
     * recursive descent arena will be necessary.  Since the generation can take
     * a long time to force a solution, it's easier to probe for a solution
     * if you go "too long".
     *
     * @access public
     * @param integer $theDifficultyLevel [optional] Since virtually everybody who
     *                plays sudoku wants a variety of difficulties this controls that.
     *                1 is the easiest, 10 the most difficult.  The easier Sudoku have
     *                extra information.
     * @param integer $theMaxInterations [optional] Controls the number of iterations
     *                before the puzzle generator gives up and trys a different set
     *                of initial parameters.
     * @param integer $theTrys [optional] The number of attempts at resetting the
     *                initial parameters before giving up.
     * @return array A set of triples suitable for initializing a new Sudoku class
     *               (@see Sudoku::initializePuzzleFromArray).
     */
    
    function generatePuzzle($theDifficultyLevel = 10, $theMaxIterations = 50, $theTrys = 10)
    {
        $theDifficultyLevel = min($theDifficultyLevel, 10) ;
        $theDifficultyLevel = max($theDifficultyLevel, 1) ;
        
        $this->theLevel = 0 ;
        $this->theTrys = $theTrys ;
        $this->theMaxIterations = $theMaxIterations ;
        $this->theGenerationIterations = 0 ;
        
        for ($theTrys = 0; $theTrys < $this->theTrys ; $theTrys++)
        {
            $theAvailablePositions = array() ;
            $theCluesPositions = array() ;
            $theClues = array() ;
            
            for ($i = 1; $i <= 9; $i++)
            {
                for ($j = 1; $j <= 9; $j++)
                    $theAvailablePositions[] = array($i, $j) ;
            }
            
            $theInitialState = $this->_generatePuzzle($theAvailablePositions, $theCluesPositions, $theClues) ;
        
            if ($theInitialState)
            {
                if ($theDifficultyLevel != 10)
                {
                    $xxx = array() ;
                
                    foreach ($theInitialState as $yyy)
                        $xxx[] = (($yyy[0] - 1) * 9) + ($yyy[1] - 1) ;

                    /*
                    ** Get rid of the available positions already used in the initial state.
                    */
                    
                    sort($xxx) ;
                    $xxx = array_reverse($xxx) ;
                    
                    foreach ($xxx as $i)
                        array_splice($theAvailablePositions, $i, 1) ;

                    /*
                    ** Easy is defined as the number of derivable clues added to the minimum
                    ** required information to solve the puzzle as returned by _generatePuzzle.
                    */
                    
                    for ($i = 0; $i < (10 - $theDifficultyLevel); $i++)
                    {
                        $xxx = mt_rand(0, count($theAvailablePositions)-1) ;
                        $row = $theAvailablePositions[$xxx][0] ;
                        $col = $theAvailablePositions[$xxx][1] ;
                        $theInitialState[] = array($row, $col, $this->theBoard[$row][$col]) ;
                        array_splice($theAvailablePositions, $xxx, 1) ;
                    }
                }
                
                //echo "found $theTrys<br>";
                return $theInitialState ;
            }

            if ($this->theDebug)
              printf("<br>Too many iterations (%d), %d\n", $this->theMaxIterations, $theTrys);
            
            $this->Sudoku($this->theDebug) ;      
        }
        
        /*
        ** No solution possible, we guess wrong too many times.
        */
        
        //echo "try=$theTrys<br>";
        return array() ;
    }
    
    /**
     * Sudoku puzzle generator.
     * 
     * This is the routine that does the heavy lifting
     * for the puzzle generation.  It works by taking a guess for a value of a cell, applying
     * the solver, testing the solution, and if it's a valid solution, calling itself
     * recursively.  If during this process, a solution cannot be found, the generator backs
     * up (backtrace in Computer Science parlance) and trys another value.  Since the generation
     * appears to be an NP complete problem (according to the literature) limits on the number
     * of iterations are asserted.  Once these limits are passed, the generator gives up and
     * makes another try.  If enough tries are made, the generator gives up entirely.
     *
     * @access private
     * @param array $theAvailablePositions A set of pairs for all positions which have not been
     *              filled by the solver or the set of guesses.  When we run out of available
     *              positions, the solution is in hand.
     * @param array $theCluesPositions A set of pairs for which values have been set by the
     *              puzzle generator.
     * @param array $theClues A set of values for each pair in $theCluesPositions.
     * @return array NULL array if no solution is possible, otherwise a set of triples
     *               suitable for feeding to {@link Sudoku::initializePuzzleFromArray}
     */
    
    function _generatePuzzle($theAvailablePositions, $theCluesPositions, $theClues)
    {
        $this->theLevel++ ;
        
        $this->theGenerationIterations++ ;
        
        /*
        ** Since the last solution sequence may have eliminated one or more positions by
        ** generating forced solutions for them, go through the list of available positions
        ** and get rid of any that have already been solved.
        */
        
        $j = count($theAvailablePositions) ;
        
        for ($i = 0; $i < $j; $i++)
        {
        	if ($this->theBoard[$theAvailablePositions[$i][0]][$theAvailablePositions[$i][1]]->IsApplied())
        	{
                array_splice($theAvailablePositions, $i, 1) ;
                $i = $i - 1;
                $j = $j - 1;
            }
        }

        if (count($theAvailablePositions) == 0)
        {
            /*
            ** We're done, so we can return the clues and their positions to the caller.
            ** This test is being done here to accommodate the eventual implementation of
            ** generation from templates in which partial boards will be fed to the solver
            ** and then the remaining board fed in.
            */
            
            for ($i = 0; $i < count($theCluesPositions); $i++)
                array_push($theCluesPositions[$i], $theClues[$i]) ;

            return $theCluesPositions ;
        }

		/*
		** Calculate the coupling for each available position.
		**
		** "coupling" is a measure of the amount of state affected by any change
		** to a given cell.  In effect, the larger the coupling, the less constrained
		** the state of the cell is and the greater the effect of any change made to
		** the cell.  There is some literature to this effect associated with Roku puzzles
		** (4x4 grid).  I'm trying this attempting to find a way to generate consistently
		** more difficult Sudoku and it seems to have worked; the clue count drops to 25 or
		** fewer, more in line with the numbers predicted by the literature.  The remainder
		** of the work is likely to be associated with finding better algorithms to solve
		** Sudoku (which would have the effect of generating harder ones).
		*/
		
		$theCouplings = array() ;
		
		foreach ($theAvailablePositions as $xxx)
		{
			$theRowCoupling = $this->theRows[$xxx[0]]->coupling($xxx[0], $xxx[1]) ;
			$theColumnCoupling = $this->theColumns[$xxx[1]]->coupling($xxx[0], $xxx[1]) ;
			$theSquareCoupling = $this->theSquares[$this->_squareIndex($xxx[0], $xxx[1])]->coupling($xxx[0], $xxx[1]) ;
			$theCouplings[$theRowCoupling + $theColumnCoupling + $theSquareCoupling][] = $xxx ;
		}

		$theMaximumCoupling = max(array_keys($theCouplings)) ;
		
        /*
        ** Pick a spot on the board and get the clues set up.
        */
        
        $theChoice = mt_rand(0, count($theCouplings[$theMaximumCoupling])-1) ;
        $theCluesPositions[] = $theCouplings[$theMaximumCoupling][$theChoice] ;
        $theRow = $theCouplings[$theMaximumCoupling][$theChoice][0] ;
        $theColumn = $theCouplings[$theMaximumCoupling][$theChoice][1] ;
        
        /*
        ** Capture the necessary global state of the board
        */
        
        $theCurrentBoard = $this->deepCopy($this->theBoard) ;
        
        /*
        ** This is all possible states for the chosen cell.  All values will be
        ** randomly tried to see if a solution results.  If all solutions fail,
        ** the we'll back up in time and try again.
        */
        
        $thePossibleClues = array_keys($this->theBoard[$theRow][$theColumn]->getState()) ;
        
        while (count($thePossibleClues) != 0)
        {
            if ($this->theGenerationIterations > $this->theMaxIterations)
            {
                $this->theLevel = $this->theLevel - 1 ;
                return array() ;
            }
            
            $theClueChoice = mt_rand(0, count($thePossibleClues)-1) ;
            $theValue = $thePossibleClues[$theClueChoice] ;
            array_splice($thePossibleClues, $theClueChoice, 1) ;
        
            $theClues[] = $theValue ;
                    
            $this->theBoard[$theRow][$theColumn]->flagSolvedPosition($theValue) ;
            
            if ($this->theDebug ) { printf("<br>(%03d, %03d) Trying (%d, %d) = %d\n", $this->theLevel, $this->theGenerationIterations, $theRow, $theColumn, $theValue) ; } ;
            
            $theFlag = $this->solve(false) ;
        
            if ($this->_validateTrialSolution())
            {
                if ($theFlag)
                {
                    /*
                    ** We're done, so we can return the clues and their positions to the caller.
                    */
                    
                    for ($i = 0; $i < count($theCluesPositions); $i++)
                    {
                        array_push($theCluesPositions[$i], $theClues[$i]) ;
                    }
                    
                    return $theCluesPositions ;
                }
                else
                {
                    $xxx = $this->_generatePuzzle($theAvailablePositions, $theCluesPositions, $theClues) ;
                    
                    if ($xxx)
                    {
                        return $xxx ;
                    }
                }
            }
            
            /*
            ** We failed of a solution, back out the state and try the next possible value
            ** for this position.
            */
            
            $this->theBoard = $theCurrentBoard ;
            $this->_buildRCS() ;
            array_pop($theClues) ;
        }
                
        $this->theLevel = $this->theLevel - 1 ;

        /*
        ** If we get here, we've tried all possible values remaining for the chosen
        ** position and couldn't get a solution.  Back out and try something else.
        */
        
        return array() ;
    }
    
    /**
     * Return the contents of the board as a string of digits and blanks.  Blanks
     * are used where the corresponding board item is an array, indicating the cell
     * has not yet been solved.
     * 
     * @desc Get the current state of the board as a string.
     * @access public
     */
    
    function getBoardAsString()
    {
    	$theString = "" ;
    	
    	for ($i = 1; $i <= 9; $i++)
    	{
    		for ($j = 1; $j <= 9; $j++)
    		{
    			$theString .= $this->theBoard[$i][$j]->asString() ;
			}
    	}
		
		return $theString ;
    }
    
	function &getCell($r, $c)
	{
		return $this->theBoard[$r][$c] ;
	}

    /**
     * Each element of the input array is a triple consisting of (row, column, value).
     * Each of these values is in the range 1..9.
     *
     * @access public
     * @param array $theArray
     */
    
    function initializePuzzleFromArray($theArray)
    {
        foreach ($theArray as $xxx)
        {
        	$c =& $this->getCell($xxx[0], $xxx[1]) ;
        	$c->flagSolvedPosition($xxx[2]) ;
        }
    }
    
    /**
     * Initialize puzzle from an input file.
     * 
     * The input file is a text file, blank or tab delimited, with each line being a
     * triple consisting of "row column value".  Each of these values is in the range
     * 1..9.  Input lines that are blank (all whitespace) or which begin with whitespace
     * followed by a "#" character are ignored.
     *
     * @access public
     * @param mixed $theHandle [optional] defaults to STDIN.  If a string is passed
     *              instead of a file handle, the file is opened.
     */
    
    function initializePuzzleFromFile($theHandle = STDIN)
    {
        $theOpenedFileFlag = FALSE ;
        
        /*
        ** If a file name is passed instead of a resource, open the
        ** file and process it.
        */
        
        if (is_string($theHandle))
        {
            $theHandle = fopen($theHandle, "r") ;
            if ($theHandle === FALSE)
            {
                exit() ;
            }
        }
        
        $yyy = array() ;
        
        if ($theHandle)
        {
            while (!feof($theHandle))
            {
                $theString = trim(fgets($theHandle)) ;
                if (($theString != "") &&
                	(!preg_match('/^\s*#/', $theString)))
                {
	                $xxx = preg_split('/\s+/', $theString) ;
	                if (!feof($theHandle))
	                {
	                    $yyy[] = array((int)$xxx[0], (int)$xxx[1], (int)$xxx[2]) ;
	                }
	        	}
            }
        }
        
        $this->initializePuzzleFromArray($yyy) ;

        if ($theOpenedFileFlag)
        {
            fclose($theHandle) ;
        }
    }
    
    /**
     * The input parameter consists of a string of 81 digits and blanks.  If fewer characters
     * are provide, the string is padded on the right.
     *
     * @desc Initialize puzzle from a string.
     * @access public
     * @param string $theString The initial state of each cell in the puzzle.  
     */
    
    function initializePuzzleFromString($theString)
    {
        $theString = str_pad($theString, 81, " ") ;
        
        for ($i = 0; $i < 81; $i++)
        {
            if ($theString{$i} != " ")
            {
                $theArray[] = array((int)($i/9) + 1, ($i % 9) + 1, (int)$theString{$i}) ;
            }
        }
        
        $this->initializePuzzleFromArray($theArray) ;
    }
    
    /**
     * @desc predicate to determine if the current puzzle has been solved.
     * @access public
     * @return boolean true if the puzzle has been solved.
     */
     
	function isSolved()
	{
		for ($i = 1; $i <= 9; $i++)
		{
			for ($j = 1; $j <=9; $j++)
			{
				if (!$this->theBoard[$i][$j]->IsSolved())
				{
					return false ;
				}
			}
		}
		
		return true ;
	}
	
    /**
     * Convert pending to actual solutions.
     * 
     * This step is actually unnecessary unless you want a pretty output of the
     * intermediate.
     *
     * @access private
     * @return boolean True if at least on pending solution existed, false otherwise.
     */
    
    function _newSolvedPosition()
    {
        $theReturn = false ;
        
        for ($i = 1; $i <= 9; $i++)
        {
            for ($j = 1; $j <= 9; $j++)
            {
                if ($this->theBoard[$i][$j]->solvedState() == 1)
                {
                	$this->theBoard[$i][$j]->un_set($this->theBoard[$i][$j]->getState()) ;
                    $theReturn = true ;
                }
            }
        }
        
        return $theReturn ;
    }
    
    /**
     * Print the contents of the board in HTML format.
     * 
     * A "hook" so that extension classes can show all the steps taken by
     * the solve function.
     *
     * @see SudokuIntermediateSolution.
     *
     * @access private
     * @param string $theHeader [optional] The header line to be output along
     *               with the intermediate solution.
     */
    
    function _printIntermediateSolution($theHeader = NULL)
    {
    	if ($this->theDebug)
        $this->printSolution($theHeader) ;
    }
    
    /**
     * Print the contents of the board in HTML format.
     *
     * Simple output, is tailored by hand so that an initial state and
     * a solution will find nicely upon a single 8.5 x 11 page of paper.
     *
     * @access public
     * @param mixed $theHeader [optional] The header line[s] to be output along
     *               with the solution.
     */
    
    function printSolution($theHeader = NULL)
    {
    	if (($this->theDebug) && ($theHeader != NULL))
    	{
    		if (is_array($theHeader))
    		{
    			foreach ($theHeader as $aHeader)
    				print $aHeader ;
    		}
    		else
	    		print $theHeader ;
    	}
    	
    	$theColors = array("green", "blue", "red") ;
    	$theFontSize = array("1em", "1em", ".8em") ;
    	$theFontWeight = array("bold", "bold", "lighter") ;
    	
        printf("<br /><table border=\"1\" style=\"border-collapse: separate; border-spacing: 0px;\">\n") ;
        
        $theLast = 2 ;
        
        for ($i = 1; $i <= 9; $i++)
        {
        	if ($theLast == 2)
        		printf("<tr>\n") ;
        	
            printf("<td><table border=\"1\" width=\"100%%\">\n") ;

			$theLast1 = 2 ;
			
        	for ($j = 1; $j <=9; $j++)
        	{
        		if ($theLast1 == 2)
        			printf("<tr>\n") ;
        		
        		$c = &$this->theSquares[$i] ;
        		$c =& $c->getCell($j) ; ;
        		$theSolvedState = $c->solvedState() ;
        		
                printf("<td style=\"text-align: center; padding: .6em; color: %s; font-weight: %s; font-size: %s;\">",
                	   $theColors[$theSolvedState],
                	   $theFontWeight[$theSolvedState],
                	   $theFontSize[$theSolvedState]) ;
               	$xxx = $c->asString($this->theDebug) ;
                print ($xxx == " " ? "&nbsp;" : $xxx) ;
		       	printf("</td>\n") ;
	        		
				$theLast1 = ($j - 1) % 3 ;
        		if ($theLast1 == 2)
        			printf("</tr>\n") ;
        	}
        	
			printf("</table></td>\n") ;
			
			$theLast = ($i - 1) % 3 ;
        	if ($theLast == 2)
        		printf("</tr>\n") ;
        }

        printf("</table>\n") ;
    }
    
    /**
     * Solve a Sudoku.
     *
     * As explained earlier, this works by iterating upon three different
     * types of inference:
     *
     * 1. A negative one, in which a value used within a row/column/square
     * may not appear elsewhere within the enclosing row/column/square.
     * 2. A positive one, in which any value with is unique in a row
     * or column or square must be the solution to that position.
     * 3. A tuple based positive one which comes in a number of flavors:
     * 3a. The "Pair" rule as stated by the author of the "other" Sudoku
     *     class on phpclasses.org and generalized by me, e.g., in any RCS
     *     two cells containing a pair of values eliminate those values from
     *     consideration in the rest of the RC or S.
     * 3b. The n/n+1 set rule as discovered by me, e.g., in any RCS, three cells
     *     containing the following pattern, (i, j)/(j, k)/(i, j, k) eliminate 
     *     the values i, j, k from consideration in the rest of the RC or S.
     *
     * During processing I explain which structures (row, column, square)
     * are being used to infer solutions.
     *
     * @access public
     * @param boolean $theInitialStateFlag [optional] True if the initial
     *                state of the board is to be printed upon entry, false
     *                otherwise.  [Default = true]
     * @return boolean true if a solution was possible, false otherwise.
     */
    
    function solve($theInitialStateFlag = true)
    {
		$theHeader = "<br />Initial Position:" ;
		
        do
        {
	        do
	        {
	            $this->_applySolvedPositions() ;
				if ($theInitialStateFlag)
				{
					$this->_printIntermediateSolution($theHeader) ;
					$theHeader = NULL ;
				}
				else
				{
					$theInitialStateFlag = true ;
					$theHeader = "<br />Apply Slice and Dice:" ;
				}
	        } while ($this->_newSolvedPosition()) ;

            $theRowIteration = FALSE ;
            
            for ($i = 1; $i <= 9; $i++)
            {
				if ($this->theRows[$i]->doAnInference())
				{
					$theHeader = $this->theRows[$i]->getHeader() ;
					$theRowIteration = TRUE ;
					break ;
				}
            }
            
            $theColumnIteration = FALSE ;
            
            if (!$theRowIteration)
            {
	            for ($i = 1; $i <= 9; $i++)
	            {
					if ($this->theColumns[$i]->doAnInference())
					{
						$theHeader = $this->theColumns[$i]->getHeader() ;
						$theColumnIteration = TRUE ;
						break ;
					}
	            }
			}
			
            $theSquareIteration = FALSE ;
            
            if (!($theRowIteration || $theColumnIteration))
            {
	            for ($i = 1; $i <= 9; $i++)
	            {
					if ($this->theSquares[$i]->doAnInference())
					{
						$theHeader = $this->theSquares[$i]->getHeader() ;
						$theSquareIteration = TRUE ;
						break ;
					}
	            }
			}
        } while ($theRowIteration || $theColumnIteration || $theSquareIteration) ;
        
		return $this->IsSolved() ;
    }

    /**
     * Here there be dragons.  In conversations with other Sudoku folks, I find that there ARE Sudoku with
     * unique solutions for which a clue set may be incomplete, i.e., does not lead to a solution.  The
     * solution may only be found by guessing the next move.  I'm of the opinion that this violates the
     * definition of Sudoku (in which it's frequently said "never guess") but if it's possible to find
     * a solution, this will do it.
     *
     * The problem is that it can take a LONG time if there ISN'T a solution since this is basically a
     * backtracing solution trier.
     *
     * The basic algorithm is pretty simple:
     *
     * 1. Find the first unsolved cell.
     * 2. For every possible value, substutite value for the cell, apply inferences.
     * 3. If a solution was found, we're done.
     * 4. Recurse looking for the next cell to try a value for.
     *
     * There's a bit of bookkeeping to keep the state right when backing up, but that's pretty
     * straightforward and looks a lot like that of generatePuzzle.
     * 
     * @desc Brute force additional solutions.
     * @access public
     * @returns array The clues added sufficient to solve the puzzle.
     */

    function solveBruteForce($i = 1, $j = 1)
    {
        for (; $i <= 9; $i++)
        {
            for (; $j <= 9; $j++)
            {
                if ($this->theBoard[$i][$j]->solvedState() != 0)
                {
			    	if ($this->theDebug)
			    	{
			    		printf("<br />Applying Brute Force to %d, %d\n", $i, $j) ;
			    	}
			    	
                    $theCurrentBoard = $this->deepCopy($this->theBoard) ;
                    $theValues = $this->theBoard[$i][$j]->getState() ;
                    
                    foreach ($theValues as $theValue)
                    {
                        $this->theBoard[$i][$j]->flagSolvedPosition($theValue) ;
                        
                        $theSolutionFlag = $this->solve() ;
                        $theTrialSolutionFlag = $this->_validateTrialSolution() ;
                        
                        if ($theTrialSolutionFlag && $theSolutionFlag)
                        {
                            return array(array($i, $j, $theValue)) ;
                        }

                        if ($theTrialSolutionFlag)
                        {
                            $theNewGuesses = $this->solveBruteForce($i, $j+1) ;
                                
                            if ($theNewGuesses)
                            {
                                $theNewGuesses[] = array($i, $j, $theValue) ;
                            
                                return $theNewGuesses ;
                            }
                        }
                        
           				if ($this->theDebug)
           				{
           					printf("<br />Backing out\n") ;
           				}
           				
                        $this->theBoard = $theCurrentBoard ;
                        $this->_buildRCS() ;
                    }
                    
                    return array() ;
                }
            }
        }
    }
    
	/**
	 * @desc Calculate the index of the square containing a specific cell.
	 * @param integer $theRow the row coordinate.
	 * @param integer $theColumn the column coordinate.
	 * @return integer the square index in the range 1..9
	 */
	 
    function _squareIndex($theRow, $theColumn)
    {
    	$theIndex = ((int)(($theRow - 1) / 3) * 3) + (int)(($theColumn - 1) / 3) + 1 ;
    	return $theIndex ;
    }
    
    /**
     * Validate a complete solution.
     * 
     * After a complete solution has been generated check the board and
     * report any inconsistencies.  This is primarily intended for debugging
     * purposes.
     *
     * @access public
     * @return mixed true if the solution is valid, an array containing the
     *               error details.
     */
    
    function validateSolution()
    {
        $theReturn = array() ;
        
        for ($i = 1; $i <= 9; $i++)
        {
            if (!$this->theRows[$i]->validateSolution())
            {
                $theReturn[0][] = $i ;
            }
            if (!$this->theColumns[$i]->validateSolution())
            {
                $theReturn[1][] = $i ;
            }
            if (!$this->theSquares[$i]->validateSolution())
            {
                $theReturn[2][] = $i ;
            }
        }

        return (count($theReturn) == 0 ? TRUE : $theReturn) ;
    }

    /**
     * Validate an entire trial solution.
     *
     * Used during puzzle generation to determine when to backtrace.
     *
     * @access private
     * @return True when the intermediate soltuion is valid, false otherwise.
     */
    
    function _validateTrialSolution()
    {
        
        for ($i = 1; $i <= 9; $i++)
        {
        	if (!(($this->theRows[$i]->validateTrialSolution()) &&
        		  ($this->theColumns[$i]->validateTrialSolution()) &&
        		  ($this->theSquares[$i]->validateTrialSolution())))
            {
                return FALSE ;
            }
        }
		
		return TRUE ;
    }
}

/**
 * Extend Sudoku to generate puzzles based on templates.
 *
 * Templates are either input files or arrays containing doubles.
 * 
 * @package Sudoku
 */

class SudokuTemplates extends Sudoku
{
    function SudokuTemplates($theDebug = FALSE)
    {
        $this->Sudoku($theDebug) ;
    }
    
    function generatePuzzleFromFile($theHandle = STDIN, $theDifficultyLevel = 10)
    {
        $yyy = array() ;
        
        if ($theHandle)
        {
            while (!feof($theHandle))
            {
                $theString = trim(fgets($theHandle)) ;
                $xxx = preg_split("/\s+/", $theString) ;
                if (!feof($theHandle))
                {
                    $yyy[] = array((int)$xxx[0], (int)$xxx[1]) ;
                }
            }
        }
        
        return $this->generatePuzzleFromArray($yyy, $theDifficultyLevel) ;
    }
    
    function generatePuzzleFromArray($theArray, $theDifficultyLevel = 10)
    {
        $this->_generatePuzzle($theArray, array(), array()) ;
        
        /*
        ** Because the generation process may infer values for some of the
        ** template cells, we construct the clues from the board and the
        ** input array before continuing to generate the puzzle.
        */
        
        foreach ($theArray as $theKey => $thePosition)
        {
            $theTemplateClues[] = array($thePosition[0], $thePosition[1], $this->theBoard[$thePosition[0]][$thePosition[1]]) ;
        }

        $theOtherClues = $this->generatePuzzle($theDifficultyLevel) ;

        return array_merge($theTemplateClues, $theOtherClues) ;
    }
}

/**
 * Extend Sudoku to print all intermediate results.
 * 
 * @package Sudoku
 */

class SudokuIntermediateSolution extends Sudoku
{
    function SudokuIntermediateResults($theDebug = FALSE)
    {
        $this->Sudoku($theDebug) ;
    }
    
    function _printIntermediateSolution($theHeader = NULL)
    {
        $this->printSolution($theHeader) ;
    }
}

function make_seed()
{
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000);
}

?>