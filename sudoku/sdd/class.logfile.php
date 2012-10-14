<?php

/**
 * @author Dick Munroe <munroe@csworks.com>
 * @copyright copyright @ by Dick Munroe, 2004
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package StructuredDataDumper
 * @version 1.0.1
 */

//
// Edit History:
//
//  Dick Munroe munroe@cworks.com 23-Dec-2004
//	Initial version created/
//

include_once('SDD/class.SDD.php') ;

class logfile extends SDD
{

  /**
   * The open file handle.
   *
   * @access private
   */

  var $m_handle ;

  /**
   * Constructor
   *
   * @access public
   */

  function logfile($theFileName)
    {
      if (file_exists($theFileName))
	{
	  $this->m_handle = fopen($theFileName, 'a') ;
	}
      else
	{
	  $this->m_handle = fopen($theFileName, 'w') ;
	}
    }

  function close()
    {
      fclose($this->m_handle) ;
    }

  /**
   * Write a debugging value to a log file.
   *
   * @access public
   * @abstract
   * @param mixed Data to be logged.
   * @return integer number of bytes written to the log.
   */

  function log(&$theData)
    {
      return fwrite($this->m_handle, date('[Y-m-d H:i:s]: ') . $this->dump($theData) . "\n") ;
    }

}
?>