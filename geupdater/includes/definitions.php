<?php
/**
 * Project: GE Update Detector & Item tracker
 * File: ./geupdater/includes/definitions.php
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

define("PATH_LOCATION", dirname(__FILE__));

define('APPNAME', 'geupdater');
define('APPPATH', '/appdir/runescape/'.APPNAME.'/');

define('DATABASE',           '`Parsers`');
define('DATA_TABLE',         DATABASE.'.`ge_data`') ;
define('EMPTY_PAGE_TABLE',   DATABASE.'.`ge_empty_pages`');
define('FAILED_PAGE_TABLE',  DATABASE.'.`ge_failed_pages`');
define('INDEX_TABLE',        DATABASE.'.`ge_index`');
define('TRACKER_TABLE',      DATABASE.'.`ge_update`') ;
define('TRACKER_DATA_TABLE', DATABASE.'.`ge_tracker`') ;
define('SETTINGS_TABLE',     DATABASE.'.`ge_config`') ;

define('CHECK_MAX', 5) ;
define('QUEUE_MAX', 12) ;
define('MAX_RETRIES', 4) ;
define('SLEEP_INTERVAL', 20) ;
define('NEEDED_TO_DOWNLOAD', 8) ;

define('MAX_PAGED_ITEMS', 12);

define('GE_REFERER_AGENT', 'http://services.runescape.com/m=itemdb_rs/frontpage.ws');

define('SOURCE_URL_MISSING', 900);
define('SOURCE_URL_MALFORMED', 901);
define('EMPTY_STATUS_VARIABLES', 902);
define('INDEX_MYSQL_ERROR', 903);
define('UGLY_FAIL_ERROR', 904);
?>
