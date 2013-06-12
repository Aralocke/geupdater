<?php
/**
 * Project: GE Update Detector & Item tracker
 * File: ./geupdater/includes/functions.php
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

define('ERROR_MAX_RETRIES', 900);

define('SOCKET_USER_AGENT', 
    'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 ( .NET CLR 3.5.30729; .NET4.0E)');

function get($url, $socket_opts = array()) {
    // parse socket_opts array
    
    global $interfaces;
    // initiate cURL
    $ch = curl_init();
    // pick an IP from the list    
    $interface = $interfaces[array_rand($interfaces)];
    if (!$ch) {
        return null;
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_INTERFACE, $interface);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_REFERER, $socket_opts[SOCKET_REFERER]);
    curl_setopt($ch, CURLOPT_USERAGENT, $socket_opts[SOCKET_USER_AGENT]);

    // retrieve the handle
    $file = curl_exec($ch);
    if (!curl_errno($ch)) {
        // no error, close the handle
        @curl_close($ch);
        // escape!!
        return $file;
    }
    // Close the handle
    @curl_close($ch);

    if ($socket_opts[SOCKET_RETRIES] == 0)
        return null;

    // Attempt to retry the request
    for ($attempt = 1; ($attempt < $socket_opts[SOCKET_RETRIES]); $attempt++)
        if (($data = get($url, 0)) !== null)
            return $data;

    // We're escaping, get outta dodge
    return null;
}

function processErrors($failed_urls = array(), $callback = false) {
    //if it's a single string make it an array
    if (is_string($failed_urls)) {
        $failed_urls = array($failed_urls);
    }
    // verify that failed_urls is an array
    // and it is not empty
    if (!is_array($failed_urls) || empty($failed_urls)) {
        return ;
    }
    // verify the callback is not empty and is callable
    if (empty($callback) || !is_callable($callback)) {
        return;
    }
    // save the return value as an array if needed
    $return_data = array();
    // loop through all the fields and run the call back
    // function on valid fields
    foreach ($failed_urls as $key => $value) {
        // validate the array with keys we are expecting
        if (!is_array($value)) {
            continue;
        }
        // checks for expected keys
        if (empty($value['url']) || empty($value['response_code'])) {
            continue ;
        }        
        // collect and save the return to the call of $callback
        $return = call_user_func_array($callback, array(
            'failed_url' => $value['url'],
            'response_code' => $value['response_code']
        ));
        // if teh callback returns any data, return it
        if (!empty($return)) {
            $return_data[$key] = $return;
        }
    }
    // we're done - get outta dodge
    return $return_data;
}

function socketQueue($urlList = array(), $callback = false, $return_result = true, $debug = false) {
    // verify the queue isn't empty
    if (!is_array($urlList) || empty($urlList)) {
        return;
    }
    // verify the callback is not empty and is callable
    if (empty($callback) || !is_callable($callback)) {
        return;
    }
    // we don't return a result if it's not a boolean
    if (!is_bool($return_result)) {
        $return_result = false;
    }
    // the result array
    $result = array(
        'benchmark' => array(
            'startTime' => microtime(true)
        ),
        'failed_urls' => array(),
        'result' => array()
    );
    // interfaces list used for the sockets
    global $interfaces;
    // array to hold the socket queue
    $queue = array();
    // loop through the urlList and build the array
    // to handle the system with
    foreach ($urlList as $url) {
        $queue[] = array(
            'url' => $url,
            'active' => false,
            'handle' => null,
            'interface' => null,
            'failures' => 0,
            'startTime' => 0
        );
    }
    // we use a fake referer to to pretend we are a fake client
    $referer = 'http://services.runescape.com/m=itemdb_rs/frontpage.ws';
    // variable socket timeout
    $socket_timeout = 20;
    // cURL handle for holding the handles
    $multi_handle = curl_multi_init();
    // outer processing loop 
    while (count($queue) > 0) {        
        curl_multi_exec($multi_handle, $running);
        // sleep for a short time to prevent massive
        // overload of processing
        // usleep(10000);
        // maintain a counter of active handles
        $active_sockets = 0;
        foreach ($queue as $key => $request) {
            // DOn't execute anything if we have enough
            // active sockets running
            if ($active_sockets > QUEUE_MAX) {
                break;
            }
            if (!isset($request['failures'])) {
                $request['failures'] = 0;
            }
            // STarting the socket
            if ($request['active'] == false) {
                $request['interface'] = $interfaces[array_rand($interfaces)];
                // track the start time of the request
                $request['startTime'] = microtime(true);
                // create a cURL handle
                $cURL = curl_init();
                // set the cURL options
                curl_setopt($cURL, CURLOPT_URL, $request['url']);
                curl_setopt($cURL, CURLOPT_INTERFACE, $request['interface']);
                curl_setopt($cURL, CURLOPT_TIMEOUT, $socket_timeout);
                curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($cURL, CURLOPT_FOLLOWLOCATION, false);
                curl_setopt($cURL, CURLOPT_HTTPGET, true);
                curl_setopt($cURL, CURLOPT_HEADER, false);
                curl_setopt($cURL, CURLOPT_REFERER, $referer);
                curl_setopt($cURL, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 ( .NET CLR 3.5.30729; .NET4.0E)');
                // add the new cURL to the batch
                curl_multi_add_handle($multi_handle, $cURL);
                // set the status of this handle to active
                $request['active'] = true;
                // save the handle
                $request['handle'] = $cURL;
            } elseif (curl_errno($request['handle']) != 0 || (microtime(true) - $request['startTime']) > 60) {
                // handle errors in the socket
                if ($request['failures'] > MAX_RETRIES) {
                    // Final failure
                    logger("Socket #{$key} Failed after " . MAX_RETRIES . " Attempts. URL: {$request['url']}", 
                            System_Daemon::LOG_NOTICE);
                    // remove the socket from the multi-threaded handle
                    curl_multi_remove_handle($multi_handle, $request['handle']);
                    // close the failed cURL handle
                    curl_close($request['handle']);
                    // saved the url
                    $result['failed_urls'][$key] = array(
                        'response_code' => ERROR_MAX_RETRIES, 
                        'url' => $request['url']
                    );
                    // unset the handle from the queue
                    unset($queue[$key]);
                    // get out of this area of the loop
                    continue;
                } else {
                    // Retry the socket
                    if ($debug) {
                        logger("Socket #{$key} Failed. Trying again :: [" . curl_errno($request['handle']) . "] " . curl_error($request['handle']));
                    }
                    // remove the socket from the multi-threaded handle
                    curl_multi_remove_handle($multi_handle, $request['handle']);
                    // close the failed cURL handle
                    curl_close($request['handle']);
                    // set the status of this handle to false
                    $request['active'] = false;
                    // increment the failure count
                    $request['failures']++;
                }
            } elseif (($error_code = curl_getinfo($request['handle'], CURLINFO_HTTP_CODE)) != 0) { // process successful data
                $request_source = curl_multi_getcontent($request['handle']);                
                if (empty($request_source)) {
                    continue;
                }
                $content_length = (int)curl_getinfo($request['handle'], CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                // verify content length is equal or greater than expected
                if (strlen($request_source) < $content_length) {
                    continue;
                }
                // Socket completed
                if ($debug) {
                    logger("Successfully downloaded: " . $request['url']);
                }
                // call the call back function and pass the parameters to it
                $return = call_user_func_array($callback, array(
                        'page_source'    => $request_source,
                        'url'            => $request['url'],
                        'info'           => array(
                            'content_length' => $content_length,
                            'interface'      => $request['interface'],
                            'result_code'    => $error_code,  
                        ),
                        'benchmark'      => array(
                            'failures'   => $request['failures'],
                            'startTime'  => $request['startTime'],
                            'stopTime'   => microtime(true)
                        )
                    )
                );
                // only save the return if it has a value
                if (!empty($return)) {
                    // we don't want to save any empty returns
                    $result['result'][$key] = $return;
                }
                // unset the value from the queue
                unset($queue[$key]);
                // remove the socket from the multi-threaded handle
                curl_multi_remove_handle($multi_handle, $request['handle']);
                // close the failed cURL handle
                curl_close($request['handle']);
                // unset the data
                unset($request_source);
                // move on, let's go
                continue;
            }
            // save override the current data with the updated 
            // data from the loop
            $queue[$key] = $request;
            // Update the active socket count
            $active_sockets++;
        } // foreach loop
    } // outer while loop
    // set the end time
    $result['benchmark']['endTime'] = microtime(true);
    // set the difference
    $result['benchmark']['difference'] = $result['benchmark']['endTime'] - $result['benchmark']['startTime'];
    // return teh result if needed
    if ($return_result) {
        return $result;
    }
}
?>
