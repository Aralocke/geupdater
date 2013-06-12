<?php
/**
 * Project: GE Update Detector & Item tracker
 * File: ./geupdater/ge.php
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
define('GEUPDATER_SERVICE', true);

$runmode = array();
$retry_attempts = 0;

include "common.php";

while (!System_Daemon::isDying()) {
    // set the log location
    // we rotate logs automatically every day
    System_Daemon::setOption('logLocation', APPPATH . 'logs/' . APPNAME . '.' . date('Y-n-d') . '.log');
    // make sure the flag that the ge is updating is false
    setConfig('geIsUpdating', 0);
    // sleep to initiate a wait    
    if (defined('FIRST_RUN')) {
        // don't sleep on the first run
        System_Daemon::iterate(((defined('SLEEP_INTERVAL')) ? SLEEP_INTERVAL : 60));
    } else {
        define('FIRST_RUN', time());
    }
    // verify mysql connectivity
    if (@mysqli_ping($db) === false) {
        if ($retry_attempts == 0) {
            logger('MySQL(i) connection has failed; attempting to restart...');
        }
        if ($retry_attempts >= MAX_RETRIES) {
            logger('Max retries [' . MAX_RETRIES . '] reached. shutting down. Last DB error: ' . mysqli_error($db));
            System_Daemon::stop();
            break;
        } elseif (($db = @mysqli_connect($dbhost, $dbuser, $dbpass, $dbname, $dbport)) === false) {
            logger('[' . $retry_attempts++ . '/' . MAX_RETRIES . '] Unable to connect to the database. Reason: ' . mysqli_error($db));
            continue;
        } else {
            $retry_attempts = 0;
        }
    } 
    // this will catch the total number of possible updates from the
    // socketQueue call
    $possibleUpdates = 0;
    // variable to determine if we force a grab update
    $override_update = (isset($runmode['manual-update']) && $runmode['manual-update']) ? true : false;            
    // don't proceed if we don't have to
    if (!$override_update) {
       // generate a list of items to search
        $items = getRandomSearch();
        // will return false if there is nothing in the failed pages queue
        // and the last update was less than an hour before
        if (!$items) {
            continue;
        }
        // generate the URL list
        $urlList = array();
        foreach ($items as $index => $search) {
            if (!isset($search['category']) || !isset($search['alpha']) || !isset($search['page'])) {
                continue;
            } else {
                // grab the actual link
                $urlList[] = getLink($search['category'], $search['alpha'], $search['page']);
                // rewrite the item to a string
                $items[$index] = sprintf("[Category=%s,Alpha='%s',Page=%s]", $search['category'], 
                    $search['alpha'], $search['page']);
            }        
        }
        logger('Checking items: '.implode(', ', $items));
        // data returned is an array from teh socketQueue
        $updateCheck = socketQueue($urlList, 'checkUpdate', true);
        // process the error set, if and only if it
        // already exists
        if (isset($updateCheck['failed_urls'])) {
            processErrors($updateCheck['failed_urls'], 'jagexFailedURLs');
        }        
        // loop through and get the total
        foreach ($updateCheck['result'] as $check) {
            $possibleUpdates += intval($check);
        } 
    }
    // do nothing if we are within SLEEP_TIME of an update
    if (((time() - getLastUpdateTime()) < getSleepTime()) && !$override_update) {
        /**
         * We don't want to proceed here because
         * the GE shouldn't be checked before the
         * one hour mark but we also want to keep 
         * the SQL connection alive calls mysqli_ping
         */
        if (NO_DAEMON) {
            logger('Cannot proceed with update :: Last update was '.(time() - getLastUpdateTime()).' seconds ago (<'.getSleepTime().')');
        }
        continue;
    }
    // pass the results to the updater
    processUpdate($possibleUpdates, 
       ((!$override_update) ? isValidUpdate() : $override_update));
    // if we aren't in a daemon, exit now
    //if (NO_DAEMON) {
    //    System_Daemon::stop();
    //}
    if ($override_update) {
        System_Daemon::stop();
    }
}

if ($db) {
    @mysqli_close($db);
}
    
?>
