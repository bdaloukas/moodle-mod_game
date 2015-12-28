<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @author Dick Munroe <munroe@csworks.com>
 * @copyright copyright @ by Dick Munroe, 2004
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package StructuredDataDumper
 * @version 1.0.1
 */

/*
 * Edit History:
 *
 *  Dick Munroe munroe@cworks.com 23-Dec-2004
 *  Initial version created/
 */

require_once('SDD/class.SDD.php');

class logfile extends SDD {

    /*
     * The open file handle.
     *
     * @access private
     */

    protected $m_handle;

    /*
     * Constructor
     *
     * @access public
     */

    public function init($thefilename) {
        if (file_exists($thefilename)) {
            $this->m_handle = fopen($thefilename, 'a');
        } else {
            $this->m_handle = fopen($thefilename, 'w');
        }
    }

    public function close() {
        fclose($this->m_handle);
    }

    /*
     * Write a debugging value to a log file.
     *
     * @access public
     * @abstract
     * @param mixed Data to be logged.
     * @return integer number of bytes written to the log.
     */

    public function log(&$thedata) {
        return fwrite($this->m_handle, date('[Y-m-d H:i:s]: ') . $this->dump($thedata) . "\n");
    }
}

