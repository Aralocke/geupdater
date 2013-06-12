<?php
/**
 * Project: GE Update Detector & Item tracker
 * File: ./geupdater/includes/config.php
 * 
 * Copyright (C) 2013 Arconiaprime (Danny Weiner [info@phantomnet.net])
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link        https://github.com/Arconiaprime/geupdater
 * @copyright   2013
 * @author      Danny Weiner <info@phantomnet.net>
 * @package     GE Update Detector & Item tracker
 * @version     3.0
 */

if (!defined('GEUPDATER_SERVICE') || !GEUPDATER_SERVICE)
    exit();

##########################
# Database info
$dbhost = '127.0.0.1';
$dbuser = 'geupdater';
$dbpass = 'abc123abc';
$dbname = 'ge';
$dbport = 3306;
##########################
##########################
# Interface config
$interfaces = array(
    '1.2.3.4',
    '1.2.3.5',
    '1.2.3.6'
);
##########################
##########################
# System Daemon Options
System_Daemon::setOptions(array(
    'appName' => APPNAME,
    'appDir' => APPPATH,
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '32M',
    'appRunAsGID' => 504,
    'appRunAsUID' => 508,
    'appPidLocation' => APPPATH . APPNAME . '/' . APPNAME . '.pid',
    'logPhpErrors' => true,
    'logFilePosition' => true,
    'logLinePosition' => true
));
##########################
##########################
# Run time variables
#
// Attempts to reconnect to the mysql server
$retry_attempts = 0;
// search options
$search_list = array(
    array('category' => '1', 'alpha' => 'a', 'page' => '1'),
    array('category' => '1', 'alpha' => 'b', 'page' => '1'),
    array('category' => '1', 'alpha' => 'i', 'page' => '1'),
    array('category' => '9', 'alpha' => 's', 'page' => '1'),
    array('category' => '9', 'alpha' => 's', 'page' => '2'),
    array('category' => '17', 'alpha' => 'a', 'page' => '1'),
    array('category' => '17', 'alpha' => 'i', 'page' => '1'),
    array('category' => '17', 'alpha' => 't', 'page' => '1'),
    array('category' => '18', 'alpha' => 't', 'page' => '1'),
    array('category' => '18', 'alpha' => 'd', 'page' => '1')
);
##########################
?>
