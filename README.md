geupdater
=========

A daemon application coded in PHP. Based off an open source daemon handler, it will parse the RuneScape grand Exchange 
and maintain a MySQL database cache of all item data as well as build a per-update item data tracker.

This project was initially created to support statistics and item lookup data for the online MMORPG by Jagex Ltd.
RuneScape (http://www.runescape.com). At the time this was the backbone to a system that served thousands of lookups per hour.
In the hopes of building our own Item tracker and reduce the load time and page hits to Jagex servers, this system was built.

The key idea is that the Grand Exchange operated on a revolving door that opens at given intervals throughout the day. Usually
only 1 time every 23 hours or so, but sometimes more. Our goal was to track the change in value of every tradable item in the game,
both to provide cutting edge "merchanting" tools (real time predictive data about items, when to sell, what to buy, etc).

This project is not endorsed by or supported by ANYONE at Jagex Ltd. or of the official RuneScape team, and I'd venture to guess 
it's no longer needed or allowed. I also have NO idea if the code even works as I no longer play the game. I hope the many hours 
I spent divising these types of tools will be educational for many other developers.

For any legal issues, I'm releasing this software with NO culpability or responsibility for how it is used. I hope it can 
be educational, but i provide no assurances or support for it.

It's not the cleanest code, but it is commented up the wazoot in my usual "let you know what I'm thinking" style. 

Enjoy :)

System_daemon
=============
The backbone process of this project. Admittedly it's quite old and there are several better ways to do all this now.

The System_daemon class was written by Kevin van Zonneveld back in 2009.
http://kvz.io/blog/2009/01/09/create-daemons-in-php/

System_daemon in a nutshell is an open source class for daemonizing PHP applications. It's very well written and comes 
with a large number of features.

Configuration & Execution
==========================

For configuration, see the internal configuration file located in the docroot (./includes/config.php). Everything is pretty
much setup already.

The search list coresponds to the Item category, Alpha-Numeric character, and page number it belongs to with the paginated 
search results. (Again this may mean nothing to you becaus ethe RuneScape website has probably been updated already).

Run the file via the command line
/usr/bin/php /path/to/ge.php

(Optional) Command-Line Options
--no-daemon     - Run without daemonizing. Enhanced debug output will print to the console.
--full-grab     - Download everything from the GE. Useful for the first run through to build the cache.
--categories    - Download only the categories index, and not the item data.
--manual-update - Force an update.
--help          - Self-Explanatory.


