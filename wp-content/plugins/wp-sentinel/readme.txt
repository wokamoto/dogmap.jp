=== WP-Sentinel ===
Contributors: evilsocket
Author: evilsocket
Donate link: http://www.evilsocket.net/
Plugin URI: http://www.evilsocket.net/
Tags: security, hack, ids, ips, exploit, security, safe, protection, guard, hackers, hacker, bruteforcing, flood, csrf, cross site request forgery, xss, cross site scripting, rfi, remote file inclusion, lfi, local file inclusion, sql injection, sqli
Requires at least: 2.8
Tested up to: 3.3.1
Stable tag: 2.0.3

A wordpress security system plugin which will check every HTTP request against a given set of rules to filter out malicious requests.

== Description ==

WP-Sentinel, is a plugin for the WordPress platform which will increase the security of your blog against attacks
from crackers, lamers, black hats, h4x0rs, etc .
The plugin will be loaded by wordpress before every other installed plugin and will execute some security checks upon incoming http requests and, when one of more
requests turn on the system alarm, they will be blocked, the sentinel then will show a warning message to the user and send a notification email to the blog
administrator with the whole attack details.
Furthermore wp-sentinel will communicate with a centralized server to collect attackers data and build a ip address blacklist.

This plugin is able to block those kind of attacks :

* Cross Site Scriptings
* HTML Injections
* Remote File Inclusions
* Remote Command Executions
* Local File Inclusions
* SQL Injections 
* Integer & string overflows
* Cross Site Request Forgery 
* Login bruteforcing
* Flooding
* ... and so on :)

WP-Sentinel will NOT check requests from the user logged in as administrator, so if you want to check the installation you have to log out first.

== Installation ==

1. Upload `wp-sentinel` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the plugin through the settings panel.

== Frequently Asked Questions ==

= How can i check if the plugin is installed and working properly? =

If you are logged in as administrator, perform a logout, go to the index of your blog and open an url such as :

http://your-blog-url/?s=../../this/is/just/a/test

To activate the local file inclusion block for instance.

= Some bot is launching an high number of attacks to my blog, will the plugin be flooded with logs? =

No, the plugin has an anti-flood mechanism that can be configured through the settings menu.

== Changelog ==

= 2.0.3 =
* Fixed a bug in email reporting.

= 2.0.2 =
* Added whitelisted variable to make JetPack work properly.

= 2.0.1 =
* Implemented a full set of rules, tnx to PHPIDS guys.
* Fixed routine which checks if the user is an admin.
* Fixed issue that caused many other plugins such as JetPack, WP Stats and so on not to work.
* Fixed issue that caused the admin to be banned.
* Fixed minor issues that caused php notices.
* Fixed admin html.

= 2.0.0 =
* Complete rewriting of the engine.
* Big performance boost.
* Implemented pre rules hooks and alarm hooks.
* Configuration and rules are now json encoded.
* Fixed bug when short tags support is off.
* Fixed value flattening bug.
* Fixed value decoding.

= 1.3.0 =
* Implemented centralized log server safe comunication.

= 1.2.0 =
* Little fixes.

= 1.1.0 =
* Fixed a bug when a variable is an array.
* Fixed interaction with caching plugins.

= 1.0.9 =
* Small graphical restyle.
* Added small banner.
* Implemented ip manual and automatic banning.
* Ban management.

= 1.0.8.3 =
* Fixed a query in ipdetails.php

= 1.0.8.2 =
* Fixed ipdetails.php

= 1.0.8.1 =
* Update php/settings.php

= 1.0.8 =
* Graphics fixes.
* Updated screenshots.
* Daily count fix.
* New IP details and statistics dialog.
* RFI vectors are now linked in alarm dialog.
* Full history tab with paging.

= 1.0.7.3 =
* Same as 1.0.7.2 -.-

= 1.0.7.2 =
* Same as 1.0.7.1 -.-

= 1.0.7.1 =
* Fixed svn mess.

= 1.0.7 =
* Implemented user editable HTML tags whitelist.
* Better log summary with facebox details display.
* User now can delete a single log entry.
* Other minor fixes.

= 1.0.6.1 =
* Fixed a bug upon plugin update from 1.0.5 to 1.0.6

= 1.0.6 =
* Porting to MySQL.
* Better CSV log export.
* Fixed readme typo.
* Some minor fixes and code restyle.

= 1.0.5 =
* Fixed minor bug on php/settings.php.

= 1.0.4 =
* Implemented anti login brute forcing engine.
* Implemented ip address geo localization.

= 1.0.3 =
* Settings panel little code restyle.
* Now, upon RFI matching, the plugin attempts to classify the remote file (caching is handled) as allowed or not.

= 1.0.2 =
* Implemented log downloading and wipeing.

= 1.0.1 =
* Better default alarm layout in english language.
* Fixed initialization hook with a better wp action.
* Added file permission checking on admin panel.

= 1.0 =
* First implementation of anti-flood mechanism.
* Layout manager.

== Upgrade Notice ==

= 1.0.9 =
Important upgrade, implemented ip banning.

= 1.0.6 =
MySQL enabled with local caching.

= 1.0.1 =
See changelog.

= 1.0 =
Well, this is just the first release :)
