<?php

/**
 * Dump structured data, i.e., Objects and Arrays, in either plain text or
 * html.  This is a class wrapper for a couple of utility routines that I use
 * all the time.  It's handier to have them as a class.
 *
 * Its also the class interface for logging functions that I use in developing
 * web enabled applications.
 *
 * @author Dick Munroe <munroe@csworks.com>
 * @copyright copyright @ by Dick Munroe, 2004
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package StructuredDataDumper
 * @version 1.0.4
 */

//
// Edit History:
//
//  Dick Munroe munroe@cworks.com 04-Dec-2004
//      Initial version created.
//
//  Dick Munroe munroe@csworks.com 08-Dec-2004
//      Translate < to &lt; for html output.
//
//  Dick Munroe munroe@csworks.com 23-Dec-2004
//      Add interface for writing "stuff".  Extend SDD
//      to get things "written".
//
//  Dick Munroe munroe@csworks.com 25-Dec-2004
//      If a class extends a base class, but doesn't add
//      data members, a warning winds up appearing when
//      printing.
//      Added a memeber to fetch the state of the logging
//      flag.
//
//  Dick Munroe munroe@csworks.com 11-Mar-2006
//      The test for html flag should have assumed that
//      $this can be defined for objects calling SDD::dump.
//
//  Dick Munroe (munroe@csworks.com) 22-Mar-2006
//      Add a function to generate "newlines".
//

class SDD
{
  /**
   * HTML to be generated flag.
   */

  var $m_htmlFlag ;

  /**
   * logging flag.
   */

  var $m_logging = false ;

  /**
   * In memory log file.
   */

  var $m_log = array() ;

  /**
   * Constructor.
   *
   * @access public
   * @param boolean $theHTMLFlag [optional] True if HTML is to be generated.
   *                If omitted, $_SERVER is used to "guess" the state of
   *                    the HTML flag.  Be default, HTML is generated when
   *                    accessed by a web server.
   * @param boolean $theLoggingFlag [optional] the state of logging for
   *                this object.  By default, logging is off.
   */

  function SDD($theHtmlFlag=null, $theLoggingFlag=false)
    {
      if ($theHtmlFlag === null)
        {
          $theHtmlFlag = (!empty($_SERVER['DOCUMENT_ROOT'])) ;
        }

      $this->m_htmlFlag = $theHtmlFlag ;
      $this->m_logging = $theLoggingFlag ;
    }

  /**
   * Close the log file.
   *
   * @access public
   * @abstract
   */

  function close()
    {
    }

  /**
   * Dump a structured variable.
   *
   * @static
   * @param mixed $theVariable the variable to be dumped.
   * @param boolean $theHtmlFlag [optional] true if HTML is to be generated,
   *                false if plain text is to be generated, null (default) if
   *                dump is to guess which to display.
   * @return string The data to be displayed.
   * @link http://www.php.net/manual/en/reserved.variables.php#reserved.variables.server Uses $_SERVER
   */

  function dump(&$theVariable, $theHtmlFlag=null)
    {
      if ($theHtmlFlag === null)
        {
          if (empty($this))
            {
              $theHtmlFlag = (!empty($_SERVER['DOCUMENT_ROOT'])) ;
            }
          else
            {
              if (is_subclass_of($this, "sdd"))
              {
                $theHtmlFlag = $this->m_htmlFlag ;
              }
              else
              {
                $theHtmlFlag = (!empty($_SERVER['DOCUMENT_ROOT'])) ;
              }
            }
        }

      switch (gettype($theVariable))
        {
        case 'array':
          {
            return SDD::dArray($theVariable, $theHtmlFlag) ;
          }

        case 'object':
          {
            return SDD::dObject($theVariable, $theHtmlFlag) ;
          }

        default:
          {
            return SDD::scalar($theVariable, $theHtmlFlag) ;
          }
        }
    }

  /**
   * Dump the contents of an array.
   *
   * @param array $theArray the array whose contents are to be displayed.
   * @param boolean $theHTMLFlag True if an HTML table is to be generated,
   *                false otherwise.
   * @param string $theIndent [optional] Used by SDD::dArray during recursion
   *               to get indenting right.
   * @return string The display form of the array.
   */

  function dArray(&$theArray, $theHTMLFlag, $theIndent = "")
    {
      $theOutput = array() ;

      foreach($theArray as $theIndex => $theValue)
        {
          if (is_array($theValue))
            {
              $theString = SDD::dArray($theValue, $theHTMLFlag, $theIndent . "    ") ;
              $theOutput[$theIndex] = substr($theString, 0, strlen($theString) - 1) ;
            }
          else if (is_object($theValue))
            {
              $theOutput[$theIndex] = SDD::dObject($theValue, $theHTMLFlag) ;
            }
          else
            {
              $theOutput[$theIndex] =  ($theHTMLFlag ?
                                        preg_replace('|<|s', '&lt;', var_export($theValue, true)) :
                                        var_export($theValue, true)) ;
            }
        }

      if ($theHTMLFlag)
        {
          $theString = "<table border=1>\n" ;
          $theString .= "<tr><td align=left>Array (</td></tr>\n" ;

          foreach ($theOutput as $theIndex => $theVariableOutput)
            {
              $theString .= "<tr>\n<td align=right>$theIndex = ></td><td align=left>\n$theVariableOutput\n</td>\n</tr>\n" ;
            }

          $theString .= "<tr><td align=left>)</td></tr>\n" ;
          $theString .= "</table>\n" ;
        }
      else
        {
          $theString = "Array\n$theIndent(\n" ;

          foreach ($theOutput as $theIndex => $theVariableOutput)
            {
              $theString .= "$theIndent    [$theIndex] => " . $theVariableOutput . "\n" ;
            }

          $theString .= "$theIndent)\n" ;
        }

      return $theString ;
    }

  /**
   * Dump the contents of an object.
   *
   * Provide a structured display of an object and all the
   * classes from which it was derived.  The contents of
   * the object is displayed from most derived to the base
   * class, in order.
   *
   * @param object $theObject the object to be dumped.
   * @param boolean $theHTMLFlag true if HTML is to be generated.
   * @return string the display form of the object.
   */

  function dObject(&$theObject, $theHTMLFlag)
    {
      $theObjectVars = get_object_vars($theObject) ;

      //
      // Get the inheritance tree starting with the object and going
      // through all the parent classes from there.
      //

      $theClass = get_class($theObject) ;

      $theClasses[] = $theClass ;

      while ($theClass = get_parent_class($theClass))
        {
          $theClasses[] = $theClass ;
        }

      //
      // Get all the class variables for each class in the inheritance
      // tree.  There will be some duplication, but we'll sort that out
      // in the output process.
      //

      foreach($theClasses as $theClass)
        {
          $theClassVars[$theClass] = get_class_vars($theClass) ;
        }

      //
      // Put the inheritance tree from base class to most derived order
      // (this is how we get rid of duplication of the variable names)
      // Go through the object variables starting with the base class,
      // capture the output and delete the variable from the object
      // variables.
      //

      $theClasses = array_reverse($theClasses) ;

      $theOutput = array() ;

      foreach ($theClasses as $theClass)
        {
          $theOutput[$theClass] = array() ;

          foreach ($theClassVars[$theClass] as $theVariable => $value)
            {
              if (array_key_exists($theVariable, $theObjectVars))
                {
                  if (is_array($theObjectVars[$theVariable]))
                    {
                      $theOutput[$theClass][] = $theVariable . " = " .  SDD::dArray($theObjectVars[$theVariable], $theHTMLFlag) ;
                    }
                  else if (is_object($theObjectVars[$theVariable]))
                    {
                      $theOutput[$theClass][] = $theVariable . " = " .  SDD::dObject($theObjectVars[$theVariable], $theHTMLFlag) ;
                    }
                  else
                    {
                      $theOutput[$theClass][] =
                         $theVariable . " = " .
                         ($theHTMLFlag ?
                          preg_replace('|<|s', '&lt;', var_export($theObjectVars[$theVariable], true)) :
                          var_export($theObjectVars[$theVariable], true)) ;
                    }

                  unset($theObjectVars[$theVariable]) ;
                }
            }
        }

      //
      // Put the classes back in most derived order for generating printable
      // output.
      //

      $theClasses = array_reverse($theClasses) ;

      if ($theHTMLFlag)
        {
          $theString = "<table>\n<thead>\n" ;

          foreach ($theClasses as $theClass)
            {
              $theString .= "<th>\n$theClass\n</th>\n" ;
            }

          $theString .= "</thead>\n<tr valign=top>\n" ;

          foreach ($theClasses as $theClass)
            {
              $theString .= "<td>\n<table border=1>\n" ;

              foreach ($theOutput[$theClass] as $theVariableOutput)
                {
                  $theString .= "<tr>\n<td>\n$theVariableOutput\n</td>\n</tr>\n" ;
                }

              $theString .= "</table>\n</td>\n" ;
            }

          $theString .= "</tr>\n</table>\n" ;
        }
      else
        {

          $classIndent = "" ;

          $classDataIndent = "    " ;

          $theString = "" ;

          foreach ($theClasses as $theClass)
            {
              $theString .= "{$classIndent}class $theClass\n\n" ;

              foreach ($theOutput[$theClass] as $theVariableOutput)
                {
                  $theString .= "$classDataIndent$theVariableOutput\n" ;
                }

              $theString .= "\n" ;

              $classIndent .= "    " ;

              $classDataIndent .= "    " ;
            }
        }

      return $theString ;
    }

  /**
   * Write a debugging value to a log file.
   *
   * @access public
   * @abstract
   * @param mixed Data to be logged.
   * @param string $theHeader [optional] string to be emitted prior to
   *               logging the data.  By default it is a date/time
   *                   stamp.
   */

  function log(&$theData, $theHeader=null)
    {
      $theHeader = date('[Y-m-d H:i:s]: ') . $theHeader ;

      if ($this->m_logging)
        {
          if ($this->m_htmlFlag)
            {
              $xxx = $this->dump($theData) ;
              if (substr($xxx, 0, 5) == '<pre>')
                {
                  $xxx = '<pre>' . $theHeader . substr($xxx, 5) ;
                }
              else
                {
                  $xxx = $theHeader . $xxx ;
                }

              $this->writeLog($xxx) ;
            }
          else
            {
              $xxx = $theHeader . $this->dump($theData) ;
              $this->writeLog($xxx) ;
            }
        }
    }

  /**
   * @desc Generate context specific new line equivalents.
   * @param integer [optional] the number of newlines.
   * @param boolean [optional] true if generating html newlines.
   * @return string newlines.
   * @access public
   */
   
  function newline($theCount=1, $theHtmlFlag=null)
    {
      if ($theHtmlFlag === null)
        {
          if (empty($this))
            {
              $theHtmlFlag = (!empty($_SERVER['DOCUMENT_ROOT'])) ;
            }
          else
            {
              if (is_subclass_of($this, "sdd"))
              {
                $theHtmlFlag = $this->m_htmlFlag ;
              }
              else
              {
                $theHtmlFlag = (!empty($_SERVER['DOCUMENT_ROOT'])) ;
              }
            }
        }
       
      if ($theHtmlFlag)
        {
          return str_repeat("<br />", max($theCount, 0)) . "\n" ;
        }
      else
        {
          return str_repeat("\n", max($theCount, 0)) ;
        }
    }

  /**
   * Dump any scalar value
   *
   * @param mixed $theVariable the variable to be dumped.
   * @param boolean $theHtmlFlag true if html is to be generated.
   */

  function scalar(&$theVariable, $theHtmlFlag)
    {
      if ($theHtmlFlag)
        {
          return "<pre>" . preg_replace('|<|s', '&lt;', var_export($theVariable, true)) . "</pre>" ;
        }
      else
        {
          return var_export($theVariable, true) ;
        }
    }

  /**
   * Write data to the log file.
   *
   * @access public
   * @abstract
   * @parameter string $theData [by reference] the data to be written
   *                       into the log file.
   * @return integer the number of bytes written into the log file.
   */

  function writeLog(&$theData)
    {
      return strlen($this->m_log[] = $theData) ;
    }

  /**
   * Return the state of the logging flag.
   *
   * @access public
   * @return boolean
   */

  function getLogging()
    {
      return $this->m_logging ;
    }

  /**
   * Set the state of the logging flag.
   *
   * @access public
   * @return boolean
   */

  function setLogging($theLogging=false)
    {
      $this->m_logging = $theLogging ;
    }
}
?>