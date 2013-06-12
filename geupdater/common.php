<?php
/**
 * Project: GE Update Detector & Item tracker
 * File: ./geupdater/common.php
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

$runmode = array(
    'no-daemon'     => false,
    'full-grab'     => false,
    'categories'    => false,
    'manual-update' => false,
    'help'          => false,
);
foreach ($argv as $k => $arg) {
    if (substr($arg, 0, 2) == '--' && isset($runmode[substr($arg, 2)])) {
        $runmode[substr($arg, 2)] = true;
    }
}
if ($runmode['help'] == true) {
    echo 'Usage: ' . $argv[0] . ' [runmode]' . "\n";
    echo 'Available runmodes:' . "\n";
    foreach ($runmode as $runmod => $val) {
        echo ' --' . $runmod . "\n";
    }
    die();
}

$timezone = 'Europe/London';
ini_set('date.timezone', $timezone);
date_default_timezone_set($timezone);

// handle all processing of the $args variable and 
// command line processing
require_once 'includes/daemon.php';
require_once 'includes/definitions.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/runescape.php';

if ($runmode['categories'] == true) {
    echo "Printing out available category types: \n";
    foreach ($categories['numeric'] as $key => $value)
        printf("\t%d) %s\n", $key, $value);
    die();
}

// set a log path
System_Daemon::setOption('logLocation', APPPATH . 'logs/' . APPNAME . '.' . date('Y-n-d') . '.log');

function logger($message = '', $level = System_Daemon::LOG_INFO) {
    if (class_exists('System_Daemon')) {
        return System_Daemon::log($level, $message);
    }
}

if (($db = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport)) === false) {
    $error_message = sprintf("Failed to connect to %s@%s:%d/%s\nReason: [%d] %s\n", $dbuser, $dbhost, $dbport, $dbname, @mysqli_connect_errno(), @mysqli_connect_error());
    logger($error_message, System_Daemon::LOG_ERR);
    die("{$error_message}\n");
} else {
    logger('Successfully started the MySQL connection.', System_Daemon::LOG_NOTICE);
}

// @mysqli_query($db, "SET SESSION binlog_format = ROW");

// handle daemon mode processing
if (!$runmode['no-daemon']) {
    // Spawn Daemon 
    System_Daemon::start();
    // save the PID
    define('MY_PID', posix_getpid());
    // Write to the log file
    logger("Daemon: '" . System_Daemon::getOption("appName") . "' spawned! This will be written to " . System_Daemon::getOption("logLocation"));
    logger("Daemon is running under PID " . MY_PID);
} else {
    define('MY_PID', posix_getpid());    
}

define('NO_DAEMON', (is_bool($runmode['no-daemon'])) ? $runmode['no-daemon'] : false);
?>
