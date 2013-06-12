<?php
/**
 * Project: GE Update Detector & Item tracker
 * File: ./geupdater/includes/runescape.php
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
 
$categories = array();
$categories['numeric'][1] = 'Ammo';
$categories['numeric'][2] = 'Arrows';
$categories['numeric'][3] = 'Bolts';
$categories['numeric'][4] = 'Construction materials';
$categories['numeric'][5] = 'Construction products';
$categories['numeric'][6] = 'Cooking ingredients';
$categories['numeric'][7] = 'Costumes';
$categories['numeric'][8] = 'Crafting materials';
$categories['numeric'][9] = 'Familiars';
$categories['numeric'][10] = 'Farming produce';
$categories['numeric'][11] = 'Fletching materials';
$categories['numeric'][12] = 'Food and Drink';
$categories['numeric'][13] = 'Herblore materials';
$categories['numeric'][14] = 'Hunting equipment';
$categories['numeric'][15] = 'Hunting Produce';
$categories['numeric'][16] = 'Jewellery';
$categories['numeric'][17] = 'Mage armour';
$categories['numeric'][18] = 'Mage weapons';
$categories['numeric'][21] = 'Melee armour - high level';
$categories['numeric'][19] = 'Melee armour - low level';
$categories['numeric'][20] = 'Melee armour - mid level';
$categories['numeric'][24] = 'Melee weapons - high level';
$categories['numeric'][22] = 'Melee weapons - low level';
$categories['numeric'][23] = 'Melee weapons - mid level';
$categories['numeric'][25] = 'Mining and Smithing';
$categories['numeric'][0] = 'Miscellaneous';
$categories['numeric'][26] = 'Potions';
$categories['numeric'][27] = 'Prayer armour';
$categories['numeric'][28] = 'Prayer materials';
$categories['numeric'][29] = 'Range armour';
$categories['numeric'][30] = 'Range weapons';
$categories['numeric'][31] = 'Runecrafting';
$categories['numeric'][32] = 'Runes, Spells and Teleports';
$categories['numeric'][33] = 'Seeds';
$categories['numeric'][34] = 'Summoning scrolls';
$categories['numeric'][35] = 'Tools and containers';
$categories['numeric'][36] = 'Woodcutting product';
$categories['assoc'] = array_flip($categories['numeric']);

function getConfig($key = false) {
    if (!$key) {
        return false;
    }
    
    global $db;
    
    $result = @mysqli_query($db, "SELECT `value` FROM " . SETTINGS_TABLE . " 
        WHERE `item` = '".@mysqli_real_escape_string($db, $key)."' LIMIT 1");

    if (!$result) {
        return false;
    } else {
        $row = @mysqli_fetch_assoc($result);
        @mysqli_free_result($result);
        return $row['value'];
    }
}

function cleanNumbers($string = false) {
    if (!$string) {
        return 0;
    } elseif (strstr($string, "k")) {
        $string = floatval($string) * 1000;
    } elseif (strstr($string, "m")) {
        $string = floatval($string) * 1000000;
    } else if (strstr($string, "b")) {
        $string = floatval($string) * 1000000000;
    }
    return str_replace(',', '', $string);
}

function getItem($id = false) {
    if (!$id || !is_numeric($id)) {
        return false;
    }

    global $db;

    $result = @mysqli_query($db, "SELECT `name`,`added_on`,`last_updated`,`category`,`page_id`,
        `price`,`change`,`members`,`description` FROM " . DATA_TABLE . " WHERE `id` = '".intval($id)."' LIMIT 1");

    if (!$result || @mysqli_num_rows($result) == 0) {
        return false;
    }

    $row = @mysqli_fetch_assoc($result);
    @mysqli_free_result($result);
    return $row;
}

function getLastUpdateTime() {
    $result = getConfig('lastUpdateTime');
    return ($result) ? intval($result) : time();
}

function checkFailedPages() {
    return ((time() - getLastUpdateTime()) <= 3600) ? true : false;
}

function popFailedPage() {
    return popFromPage('ge_failed_pages');
}
function popEmptyPage() {
    return popFromPage('ge_empty_pages');
}
function popfromPage($page = 'ge_failed_pages') {
    global $db, $failed_pages;
    $result = @mysqli_query($db, "SELECT `category`,`alpha`,`page` 
            FROM `{$page}` ORDER BY `last_checked` DESC LIMIT 1");
    if (!(@mysqli_num_rows($result) > 0))
        return false;
    $row = @mysqli_fetch_assoc($result);
    @mysqli_free_result($result); 
    // add the page to the failed pages table
    $failed_pages[(int)$row['category']][(int)$row['alpha']][(int)$row['page']] = true;
    // we store the numeric char
    // convert it to ASCII here
    $row['alpha'] = chr($row['alpha']);
    // pop the rows off the failed pages list
    @mysqli_query($db, "DELETE FROM `{$page}` WHERE `category` = '{$row['category']}' AND
        `alpha` = '".ord($row['alpha'])."' AND `page` = '{$row['page']}' LIMIT 1");
    if (!(@mysqli_affected_rows($db) > 0) && (($sqli_errno = @mysqli_errno($db)) > 0)) {
        logger('MySQL(i) error removing a page '.sprintf("[Category=%s,Alpha='%s',Page=%s]", $row['category'], 
            $row['alpha'], $row['page']).' from the '.$page.' index: ['.$sqli_errno.'] '.@mysqli_error($db));
    }
    return $row;           
}

function getRandomSearch() {
    global $db;
    $results = array();
    // for the first 2 hours after an update, all we will do is check the
    // failed pages table so that we can completely recover any lost
    // or unchecked data
    if (($checkFailedPages = checkFailedPages()) == true) {
        while (count($results) < CHECK_MAX) {
            $result = popFailedPage();
            if (!$result) {
                break;
            }
            $results[] = $result;
        }            
    } 
    // do not proceed if we pop off enough results from the error list
    if (count($results) >= CHECK_MAX) {
        return $results;
    }
    // during the first hour, don't check new pages
    if ($checkFailedPages && count($results) == 0) {
        return false;
    }
    // pop a failed page first
    $failed_page = popFailedPage();
    if (!empty($failed_page)) 
        $results[] = $failed_page;
    $empty_page = popEmptyPage();
    if (!empty($empty_page)) 
        $results[] = $empty_page;
    
    // while failed/empty pages exist, only need 3 active pages
    $result = @mysqli_query($db, "SELECT `category`,`alpha`,`page` 
        FROM `ge_index` WHERE `results` = 12 ORDER BY RAND() LIMIT 5");    
    if (@mysqli_num_rows($result) > 0) {        
        while (($row = @mysqli_fetch_assoc($result)) != false) {
            // we store the numeric char
            // convert it to ASCII here
            $row['alpha'] = chr($row['alpha']);
            // save the row. The associative indexes
            // line up with what we need anyhow
            $results[] = $row;
        }
        @mysqli_free_result($result);        
    } else {
        // require the static list 
        global $search_list;
        // loop through until we have 5
        while (count($results) < CHECK_MAX) {
            // get a random pair from the search array
            $index = rand(0, count($search_list));
            // determine if $item is in the search list
            if (!in_array($search_list[$index], $results)) {
                $results[] = $search_list[$index];
            }            
        } 
    }
    if (!empty($failed_page)) {
        logger("Popping ".count($failed_page)."URL's from ".FAILED_PAGE_TABLE." index.");
    }
    return $results;
}

function getSleepTime() {
    $result = getConfig('intervalSleepTime');
    return ($result) ? intval($result) : 3600;
}

// generate the link
function getLink($category, $alpha = 'a', $page = 1) {
    return "http://services.runescape.com/m=itemdb_rs/api/catalogue/items.json?category={$category}&alpha={$alpha}&page={$page}";
}

function checkUpdate($page_source = false, $source_url = false, $info = array(), $benchmark = array()) {
    global $failed_pages, $index_cache;    
    // verify that we have the source
    if (!$page_source || empty($page_source)) {
        if ($source_url) {
            logger('Failed to retrieve data from ' . $source_url);
            // save the new failed url
            jagexFailedURLs($source_url, $info['result_code']);
        }
        return;
    }
    // parse the json data
    $json = json_decode($page_source);
    // verify if we have the correct data
    if (!isset($json->total) || !isset($json->items)) {
        if ($source_url) {
            logger('Incomplete data from ' . $source_url);
            // save the new failed url
            jagexFailedURLs($source_url, ERROR_INCOMPLETE_DATA);
        }
        return;
    }
    // process empty pages
    if ($json->total == 0 || count($json->items) == 0) {
        jagexFailedURLs($source_url, $info['result_code']);
    }
    // local changed item count
    $item_changes = 0;
    // parse the source url for data
    $url = array();
    // parse the quesy string
    parse_str(parse_url($source_url, PHP_URL_QUERY), $url);
    // index variables
    $category = intval($url['category']);
    $alpha    = ($url['alpha'] == '%23') ? ord('#') : ord($url['alpha']);
    $page     = isset($url['page']) ? intval($url['page']) : 0;
    // loop through the items list
    foreach ($json->items as $item) {
        // check the database to see if it exists
        $saved = getItem($item->id);
        // the function returns false on a failure
        if ($saved == false) {
            // log the change 
            logger("Possible update on item {$item->name} (#{$item->id}) :: Does not exist in database");
            
            // TODO - check the page for changes in results
            // to see if we need to add another index
            
            // save the item to the database with the correct index
            save_item($item, index_page($source_url, $json->total, count($json->items)));
            // increment the update counter
            $item_changes++;
        } else {
            // check that we have a page_id set
            if ($saved['page_id'] == '0') {
                setPageID($saved['id'], index_page($source_url, $json->total, count($json->items)));
            }
            // clean the current price
            $current_price = floatval(cleanNumbers($item->current->price));
            // clean the current change
            $current_change = floatval(cleanNumbers($item->today->price));
            // check for a price change
            if (NO_DAEMON) {
                printf("%s (#%d) -> ((%d != %d) || (%s != %s))\n", 
                    $item->name, $item->id, $current_price, $saved['price'], $current_change, $saved['change']);
            }
            if (isset($failed_pages[$category][$alpha][$page])) {
                // if the item comes form a failed GE Page 
                // save the data now to keep the GE database updated
                save_item($item);
                // remove it from the list because we don't have to
                // process it anymore
                unset($failed_pages[$category][$alpha][$page]);
            }
            if (($current_price != $saved['price']) || ($current_change != $saved['change'])) {
                 // save the possible update to the log
                 logger('Possible update found in '.$item->name.' (Database: '.$saved['change'].'/'.$saved['price'].
                         ') and socket returned ('.$current_change.'/'.$current_price.')');
                 $item_changes++;
            } // if change found
        } // else
    } // foreach
    return $item_changes;
}

// this cache represents the URL's that have been indexed to download

function generateURLs($use_sql = true) {
    // the database maintains a unique index. There for the data held in
    // $index_cache is doesn't need to be cleared. If for some reason data
    // changes in this function, it is overriden with the new data
    // down the road
    global $db, $index_cache, $runmode;
    // hold the list of the new urls for teh socketQueue
    $urlList = array();
    // we only enter this block of code for the recursive call
    if ($use_sql === false) {
        $limiter = array();
        for ($range = ord('a'); $range <= ord('z'); $range++) 
            $limiter[] = chr($range);
        $limiter[] = urlencode('#');
        // call up the categories variable
        global $categories;    
        // run the process loop to generate the urls
        foreach ($categories['numeric'] as $category => $index) { 
            foreach ($limiter as $alpha) {
                // do not override an existing index. We know they exist
                // we don't know if these exist
                if (!isset($index_cache[$category][$alpha][1])) {
                    $urlList[] = getLink($category, $alpha, 1);
                }                
            }
        }
        return $urlList;
    }
    $result = @mysqli_query($db, 'SELECT `id`,`category`,`alpha`,`page`,`results`,`total` 
        FROM '.INDEX_TABLE);
    if (@mysqli_num_rows($result) > 0) {
        while (($row = @mysqli_fetch_assoc($result)) != false) {
            // index variables
            $category = intval($row['category']);
            $alpha    = chr($row['alpha']);
            $page     = intval($row['page']);
            // create the url
            $urlList[] = getLink($category, $alpha, $page);
            // store the id in the index cache
            $index_cache[$category][$alpha][$page] = array(
                'page_index'    => intval($row['id']),
                'total_results' => intval($row['total']),
                'paged_results' => intval($row['results'])
            );
        }
        @mysqli_free_result($result);
        // return the merged array here
        if ($runmode['full-grab']) {
            return array_merge($urlList, generateURLs(false));
        }        
    } else {
        $limiter = array();
        for ($range = ord('a'); $range <= ord('z'); $range++) 
            $limiter[] = chr($range);
        $limiter[] = urlencode('#');
        // call up the categories variable
        global $categories;    
        // run the process loop to generate the urls
        foreach ($categories['numeric'] as $index => $category) { 
            foreach ($limiter as $alpha) {        
                $urlList[] = getLink($index, $alpha, 1);
            }
        }
    }    
    return $urlList;
}

// save a global index cache to prevent querying as often
$index_cache = array();
$failed_pages = array();

function index_page($source_url = false, $total = 0, $results = 0) {
    // grab the database object
    global $db, $index_cache;
    // don't proceed if source url isn't valid
    if (!$source_url || !is_string($source_url)) {
        // category/page index 0
        return SOURCE_URL_MISSING;
    }
    // parse the source url for data
    $url = array();
    // parse the quesy string
    parse_str(parse_url($source_url, PHP_URL_QUERY), $url);
    // validate the array and grab the needed values
    if (!isset($url['category']) || !isset($url['alpha'])) {
        // category/page index 0
        return SOURCE_URL_MALFORMED;
    }
    // check the page value
    if (!isset($url['page']) || !is_numeric($url['page'])) {
        $url['page'] = 1;
    }
    // index variables
    $category = intval($url['category']);
    $alpha    = ($url['alpha'] == '%23') ? ord('#') : ord($url['alpha']);
    $page     = intval($url['page']);
    // check if the cache entry exists
    if (isset($index_cache[$category][$alpha][$page])) {
        $page_index = $index_cache[$category][$alpha][$page];
        // we check to verify we don't have to generate more pages
        if ((isset($page_index['total_results']) && $page_index['total_results'] > 0) &&
            (is_numeric($total) && $total > 0) && ($total > $page_index['total_results'])) {
            $new_pages = intval(ceil($total / MAX_PAGED_ITEMS));
            $total_results = $total;
            for ($i = 0; ($page + $i) <= $new_pages; $i++) {
                // delete index entries in the cache if the exist
                // prevents a failed recursive loop
                if (isset($index_cache[$category][$alpha][($page + $i)])) {
                    unset($index_cache[$category][$alpha][($page + $i)]);
                }
                // create the new indexes
                $new_page_index = index_page(getLink($category, chr($alpha), ($page + $i)), 
                    $total, (($total_results > MAX_PAGED_ITEMS)? MAX_PAGED_ITEMS : $total_results));
                $total_results -= MAX_PAGED_ITEMS;
            }
        }
        return $page_index['page_index']; 
    }  else {
        // query the database
        // we only get this far when the row exists and the data has not changed
        $result = @mysqli_query($db, "SELECT `id`,`total`,`results` FROM ".
           INDEX_TABLE." WHERE `category` = '".$category."' AND 
           `alpha` = '".$alpha."' AND `page` = '".$page."' LIMIT 1");   
        if (@mysqli_num_rows($result) > 0) {
            $row = @mysqli_fetch_assoc($result);
            @mysqli_free_result($result);
            $index_cache[$category][$alpha][$page] = array(
                'page_index'    => intval($row['id']),
                'total_results' => intval($row['total']),
                'paged_results' => intval($row['results'])
            );
            // verify that the cached page data is equal
            // if not issue an UPDATE
            if (($total > 0 && $results > 0) && 
                ($total != intval($row['total']) || $results != intval($row['results']))) {
                // we preserve index integrity as the index is the backbone of the
                // optimizations here. It is important to track how the index changes
                @mysqli_query($db, "UPDATE ".INDEX_TABLE." SET `total` = '".intval($total)."', 
                    `results` = '".intval($results)."' WHERE `category` = '".$category."' AND 
                    `alpha` = '".$alpha."' AND `page` = '".$page."' LIMIT 1");  
                if (@mysql_affected_rows($db) == 0 && @mysqli_errno($db) > 0) {
                    logger('MySQL(i) error when updating existing page index: ['.@mysqli_errno($db).
                        '] '.@mysqli_error($db));
                } else {
                    logger(sprintf("Updated index cache for [Category=%s,Alpha='%s',Page=%s] with results %d and per page of %d",
                        $category, $alpha, $page, $total, $results));
                }
            }
            // update the saved total/results when needed
            return intval($row['id']);
        } else {
            // check the status variables
            if (empty($category) || empty($alpha) || empty($page)) {
                return EMPTY_STATUS_VARIABLES;
            }
            logger(sprintf("Creating new Index Page for [Category=%s,Alpha='%s',Page=%s]",
                    $category, $alpha, $page));
            // the data is not cached, and does not exist in the database yet
            // insert into the DB
            $result = @mysqli_query($db, "INSERT INTO ".INDEX_TABLE." (`category`, `alpha`, `page`, `total`, `results`)
                VALUES ('".$category."', '".$alpha."', '".$page."', '".intval($total)."', '".intval($results)."')
                ON DUPLICATE KEY UPDATE `total` = '".intval($total)."', `results` = '".intval($results)."'");
            // error checking
            if (@mysqli_affected_rows($db) > 0) {
                $page_index = @mysqli_insert_id($db);
                $index_cache[$category][$alpha][$page] = array(
                    'page_index'    => $page_index,
                    'total_results' => intval($total),
                    'paged_results' => intval($results)
                );
                return $page_index;
            } elseif (($sqli_errno = @mysqli_errno($db)) > 0) {
                logger('MySQL(i) error when inserting new page index: ['.$sqli_errno.'] '.@mysqli_error($db));
                // return a falied id
                return INDEX_MYSQL_ERROR;
            } else {
                return UGLY_FAIL_ERROR;
            } // very inner else
        } // inner else
    } // outer else
}

define('ERROR_EMPTY_RESULT', 901);

function jagexFailedURLs($failed_url = false, $response_code = 0) {
    global $db;
    // do not proceed if the data is invalid
    if (empty($failed_url) || !is_numeric($response_code)) {
        return;
    }
    // parse the source url for data
    $url = array();
    // parse the quesy string
    parse_str(parse_url($failed_url, PHP_URL_QUERY), $url);
    // index variables
    $category = intval($url['category']);
    $alpha    = ($url['alpha'] == '%23') ? ord('#') : ord($url['alpha']);
    $page     = (!empty($url['page'])) ? intval($url['page']) : 1;
    // work off the response code
    switch ($response_code) {
        case 404:
        case ERROR_MAX_RETRIES:
            // only save the 404 errors and failed pages to the failed table
            @mysqli_query($db, "INSERT INTO ".FAILED_PAGE_TABLE." (`category`, `alpha`, `page`, `error_code`)
                VALUES ('{$category}', '{$alpha}', '{$page}', '{$response_code}') 
                ON DUPLICATE KEY UPDATE `last_checked` = CURRENT_TIMESTAMP(), `error_code` = '{$response_code}'");
            if (@mysqli_affected_rows($db) < 1 && @mysqli_errno($db) > 0) {
                logger('MySQL(i) error when trying to insert failed page :: ['.@mysqli_errno($db).'] :: '.@mysqli_error($db));
            }
            break;
        default:
            // don't add non 0/1 pages
            if ($page > 1)
                break;
            @mysqli_query($db, "INSERT INTO ".EMPTY_PAGE_TABLE." (`category`, `alpha`, `page`)
                VALUES ('{$category}', '{$alpha}', '{$page}') 
                ON DUPLICATE KEY UPDATE `last_checked` = CURRENT_TIMESTAMP()");
            if (@mysqli_affected_rows($db) < 1 && @mysqli_errno($db) > 0) {
                logger('MySQL(i) error when trying to insert empty page :: ['.@mysqli_errno($db).'] :: '.@mysqli_error($db));
            }
            break;
    }
    return ;
}

function processUpdate($possibleUpdates = 0, $validUpdate = false) {
    // grab the run time variable array
    global $runmode;
    // detection for an update .. or a mnual one
    if (($possibleUpdates > 0 && $validUpdate) || $possibleUpdates > ($quietCount = getConfig('quietUpdateCount')) || ($runmode['manual-update'])) {
        try {
            // grab the db object
            global $db;
            // tell the system that we are now updating
            if (setConfig('geIsUpdating', time())) {
                logger('Alerted system of oncomming update');
            } else {
                throw new Exception('Failed to update the settings table for confirmation of new update. SQL error: ' . @mysqli_error($db));
            }
            // add a record to the ge_update table
            @mysqli_query($db, "INSERT INTO ".TRACKER_TABLE." (`length`) 
                VALUES ('".(time() - getLastUpdateTime())."')");
            if (@mysqli_affected_rows($db) > 0) {
                logger('Saving new update time of '.(time() - getLastUpdateTime()).' the the database.');
                // get the last update ID
                $lastUpdateID = @mysqli_insert_id($db);
                // get the new update time
                $result = @mysqli_query($db, "SELECT `id`,`length`,`timestamp` 
                    FROM ".TRACKER_TABLE." WHERE `id` = '".$lastUpdateID."'");
                // if we have the entry proceed
                if (@mysqli_num_rows($result) > 0) {
                    $row = @mysqli_fetch_assoc($result);
                    setConfig('lastUpdateTime', time());
                    @mysqli_free_result($result);
                    logger('Setting the new lastUpdateTime to '.$row['timestamp'].' ('.getLastUpdateTime().')');
                } else {
                    // otherwise exit quickly
                    throw new Exception('Failed to insert new tracker timestamp entry. SQL error: '.@mysqli_error($db));
                }
            } else {
                throw new Exception('Failed to insert new tracker entry. SQL error: '.@mysqli_error($db));
            }
            // copy data to the tracker table
            @mysqli_query($db, "INSERT INTO ".TRACKER_DATA_TABLE." (`item_id`,`rise`,`item_price`)
                    SELECT `id`,`change`,`price` FROM " . DATA_TABLE) ;
            if (@mysqli_affected_rows($db) >= 0 && @mysqli_errno($db) == 0) {
                @mysqli_query($db, "UPDATE ".TRACKER_DATA_TABLE." 
                    SET update_num = '".$lastUpdateID."' WHERE update_num = 0") ;
                logger('Transfered Item data to the tracker table as update #'.$lastUpdateID);
            } else {
                throw new Exception('Failed to update tracker table with the new data values. SQL Error: '.@mysqli_error($db));
            }
            // run the update process
            // ->
            // building the limiter for the initial pass of the loop
            $use_sql = (isset($runmode['full-frab']) && $runmode['full-frab'] === true) ? true : false;
            $urlList = generateURLs(!($use_sql));
            logger('Now checking '.count($urlList).' urls for download...');
            // pass the urlList to the socketQueue
            $secondaryPages = socketQueue($urlList, 'parseItemJson', true);
            // process the error set, if and only if it
            // already exists
            if (isset($secondaryPages['failed_urls'])) {
                logger('There were '.count($secondaryPages['failed_urls']).' failed pages after the initial scrape');
                processErrors($secondaryPages['failed_urls'], 'jagexFailedURLs');
            }
            // loop through the array and handle new pages
            $urlList = array();
            foreach ($secondaryPages['result'] as $result) {
                // don't proceed if it is malformed or missing
                if (!isset($result['pages']))
                    continue;
                // parse the $result['pages'] array because this contains
                // the child pages for the parents
                for ($page = 2; $page <= $result['pages']; $page++) {
                    // index variables
                    $category = intval($result['category']);
                    $alpha    = ord($result['alpha']);
                    // the key here is to check the index_cache
                    // if this value exists, we've already checked it
                    // otherwise we need to continue to process new pages
                    // check if the cache entry exists
                    if (!isset($index_cache[$category][$alpha][$page])) {
                        $urlList[] = getLink($category, chr($alpha), $page);
                    }                    
                } // for
            } // foreach
            $tertiaryPages = socketQueue($urlList, 'parseItemJson', true);
            // process the error set, if and only if it
            // already exists
            if (isset($tertiaryPages['failed_urls'])) {
                logger('There were '.count($tertiaryPages['failed_urls']).' failed pages after the tertiary scrape');
                processErrors($tertiaryPages['failed_urls'], 'jagexFailedURLs');
            }
            // handle failed download pages
            if (count($tertiaryPages['failed_urls']) > 0) {
                // we attempt to re-process the pages
                $finalCall = socketQueue($tertiaryPages['failed_urls'], 'parseItemJson', true);
                logger('After the final call, there were '.count($finalCall['failed_urls']).' failed urls.', System_Daemon::LOG_NOTICE);
                processErrors($finalCall['failed_urls'], 'jagexFailedURLs');
            }
            // calculate total update time as stuff like that
            $total_time = 0;
            // final call
            if (isset($finalCall)) {
                $total_time += $finalCall['benchmark']['difference'];
            }            
            $total_time += $tertiaryPages['benchmark']['difference'];
            $total_time += $secondaryPages['benchmark']['difference'];
            logger('Total download time: '.(int)$total_time);
            // ->
            // tell the system we are no longer updating
            setConfig('geIsUpdating', 0);
            // log the completion and return
            logger('Update has completed successfully');
        } catch (Exception $e) {
            // log the error message
            logger($e->getMessage());
            // roll back changes
            setConfig('geIsUpdating', 0);
        } // catch
    } // if
}

define('ERROR_INCOMPLETE_DATA', 902);

function parseItemJson($page_source = false, $source_url = false, $info = array(), $benchmark = array()) {
    // verify that we have the source
    if (!$page_source || empty($page_source)) {
        if ($source_url) {
            logger('Failed to retrieve data from ' . $source_url);
            // save the new failed url
            jagexFailedURLs($source_url, $info['result_code']);
        }
        return;
    }
    // parse the json data
    $json = json_decode($page_source);
    // verify if we have the correct data
    if (!isset($json->total) || !isset($json->items)) {
        if ($source_url) {
            logger('Incomplete data from ' . $source_url);
            // save the new failed url
            jagexFailedURLs($source_url, ERROR_INCOMPLETE_DATA);
        }
        return;
    }
    // parse the source url for data
    $url = array();
    // parse the quesy string
    parse_str(parse_url($source_url, PHP_URL_QUERY), $url);
    // total results per category and alphanum (MAX=12 per page)
    $total = count($json->items);
    // ERROR HANDLING
    if ($total == 0 || $json->total == 0) {
        // Get outta dodge on these errors
        // but save the incomplete data to the cache
        jagexFailedURLs($source_url, $info['result_code']);
        // {"total":14,"items":[]}
        // {"total":0,"items":[]}
        return;
    }
    // update the page index
    $page_index = index_page($source_url, $json->total, $total);
    // echo "Total for page 0 is ".count($result->items)."\n";
    // percentage retried in the first salvo
    foreach ($json->items as $num => $obj) {
         //printf("%s (#%d) (Index=%d) [Category=%s :: Alpha=%s :: Page=%s]\n", 
         //       $obj->name, $obj->id, intval($page_index), $url['category'], $url['alpha'], $url['page']);
         // pass the item onto the databasing funtion
         save_item($obj, $page_index);
    }
    // this builds a recursive-like setting
    // this will hold the extra data
    // the return value of this call will be 
    // parsed as a new call to socketQueue
    if (($json->total > 12) && (!isset($url['page']) || intval($url['page']) < 2)) {
        // return a proper array of information for later
        return array(
            'results'  => $json->total,
            'total'    => $total,
            'category' => $url['category'],
            'alpha'    => $url['alpha'],
            'pages'    => intval(ceil($json->total / 12))
        );
    }
    // return nothing here
    return ;
}

function save_item($item = false, $page_index = 0) {
    global $categories, $db;
    // validate the object
    if (!$item || !is_object($item)) {
        return;
    }
    // clean the current price
    $current_price = cleanNumbers($item->current->price);
    // clean the current change
    $current_change = cleanNumbers($item->today->price);
    // item category
    $category = (isset($categories['assoc'][$item->type])) ? $categories['assoc'][$item->type] : 0;
    // initiate the query
    @mysqli_real_query($db, "INSERT INTO ".DATA_TABLE." (`added_on`, `category`, `page_id`, `change`, `description`, `id`, `name`, `price`)
        VALUES ('" . date('Y-n-d') . "', '{$category}', '".intval($page_index)."', '{$current_change}', 
        '" . mysqli_real_escape_string($db, $item->description) . "', '{$item->id}', '" . mysqli_real_escape_string($db, ucwords($item->name)) . "', 
        '{$current_price}') ON DUPLICATE KEY UPDATE `category` = '{$category}', `change` = '{$current_change}', `last_updated` = CURRENT_TIMESTAMP(), 
        `page_id` = '".intval($page_index)."', `price` = '{$current_price}'");

    if (@mysqli_affected_rows($db) == 0 && @mysqli_errno($db) > 0) {
        logger('MySQL(i) Error to save_item('.$item->name.' [#'.$item->id.']) 
            :: [' . @mysqli_errno($db) . '] ' . @mysqli_error($db));
        return false;
    } else {
        return true;
    }
}

function setConfig($key = false, $value = '') {
    if (!$key) {
        return false;
    }
    
    global $db;
    
    if (!empty($value)) {
        @mysqli_query($db, "INSERT INTO " . SETTINGS_TABLE . "(`item`,`value`) 
            VALUES ('".@mysqli_real_escape_string($db, $key)."', '".@mysqli_real_escape_string($db, $value)."') 
            ON DUPLICATE KEY UPDATE `value` = '".@mysqli_real_escape_string($db, $value)."'");
    } else {
        @mysqli_query($db, "DELETE FROM " . SETTINGS_TABLE . " 
            WHERE `item` = '".@mysqli_real_escape_string($db, $key)."' 
            LIMIT 1");
    }
    
    return (@mysqli_affected_rows($db) > 0) ? true : false;
}

function setPageID($item_id = false, $index_page = 0) {
    // if we don't have a numeric item_id get out
    if (!$item_id || !is_numeric($item_id)) {
        return;
    }
    // if we don't have a numeric index page, escape quickly
    if (!is_numeric($index_page)) {
        return;
    }
    // grab the DB object
    global $db;
    // we only do a UPDATE query because by the time this
    // function is called, a database entry will always exist
    @mysqli_query($db, "UPDATE ".DATA_TABLE." SET `page_id` = '".intval($index_page)."' 
        WHERE `id` = '".intval($item_id)."'");
    if (@mysqli_affected_rows($db) > 0) {
        return true;
    } elseif (($sqli_error = @mysqli_errno($db)) == 0) {
      return true;  
    } else {
        logger("Failed to update page ID for item #{$item_id}. SQL Error: [".$sqli_error."] ".@mysqli_error($db));
        return false;
    }
}

function isValidUpdate(){
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone('Europe/London'));
    $Now = $date->getTimestamp() + (3600 * 7 + date('I')) ;
    $Time  = ($Now - (time() - getLastUpdateTime())) ;
    $Open  = mktime(3, 0, 0, date('m', $Now), date('d', $Now), date('Y', $Now)) ;
    $Close = mktime(23, 59, 59, date('m', $Now), date('d', $Now), date('Y', $Now)) ;    
    return (($Time >= $Open) && ($Time <= $Close)) ? false : true ;    
}

?>
