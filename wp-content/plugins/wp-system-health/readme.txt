=== WP System Health ===
Contributors: codestyling
Tags: wordpress, php, memory, system, information, tool, dashboard, monitoring, health, quota, 
Requires at least: 2.7
Tested up to: 3.5.1
Stable tag: 1.4.0

Comprehensive Overview for your WordPress Parameter and Server Performance.

== Description ==

This plugin provides a new Dashboard Widget (limit to administrators) that displays information provided by 5 different categories:

1. System - basic server information and WordPress PHP memory utilization
1. PHP - most important PHP configuration values, that influence WordPress
1. WordPress - several configurable values and transport capabilities
1. Translation - shows all loaded and utilized language files
1. Database - current database utilization status

Furthermore the Plugin also introduces a new Dashboard Sub Page, Memory Usage information at Admin Footer and if permitted Server Storage Quota at Dashboard.
Additional you can switch on the performance values HTML comment output at your visitors pages to measure your system.
The new Dashboard Administration Submenu entry allows deactivation of several features if not necessary.

All WordPress related constants can be inspected. The amount ahs been adapted to WordPress 3.5.1 now.

Newly introduced you will find a CPU Usage History chart at your Dashboard. It will show the approx. last 2 minutes of server load.
This will be extended and fully configurable with next upcomming versions. If you don't get any values shown, your provider may have disabled any type of performance functions.

The plugin is now fully translatable and gots as first shot a german translation (approx. 40% translated). Feel free to contribute your translations.

With all the provided information it's easy to detect configuration issues, memory race problems and also to help somebody by just request some values.
I use this plugin normally as a "service desk" info point at my friends if something went wrong on their installations.

Remark: The current version provides database analysis at full spectrum only for normal WordPress installations. Multi Site installation analysis will follow with the next update.

= Attention = 

This plugins will be restructed now and will require WordPress min version 3.5 with next upcomming updates.
Please be prepared to have a suitable WordPress version running to continue using this plugins.

= Requirements =

1. WordPress version 2.7 and later
1. PHP Interpreter version 4.4.2 or later

Please visit [the official website](http://www.code-styling.de/english/development/wordpress-plugin-wp-system-health-en "WP System Health") for further details, documentation and the latest information on this plugin.

== Installation ==

1. Uncompress the download package
1. Upload folder including all files and sub directories to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Navigate to your Dashboard and enjoy status informations

== Changelog ==

= Attention = 
Version(s) 1.4.x will be the last version(s) with compatibility support down to WordPress 2.7!
Upcomming higher versions will required WordPress 3.5 as minimum version.

= Version 1.4.0 =
* Bugfix: compatibility with WP 3.5
* Bugfix: progress bar styles adjusted
* Bugfix: repaired broken dashboard drag/drop and click handler within widget
* Feature: extended list of WordPress constants
* Feature: smaller plugin code because of extraction overview to external file loaded on demand only
* Feature: PHP section now list the full information php_info() would show
* Feature: CPU usage chart at Dashboard with live data of last 2 min.

= Version 1.3.3 =
* Feature: first part of test suite: memory limit tester
* Bugfix: cloning call at PHP 4.4.9 avoided

= Version 1.3.2 =
* Bugfix: percentage calculation failed
* Bugfix: disabled function calls produced errors/warnings

= Version 1.3.1 =
* Bugfix: division by zero (percentages at quota's)
* Bugfix: open basedir restriction problem retrieving quota's

= Version 1.3 =
* Bugfix: clone made compatible to PHP 4.x
* Bugfix: detect and respect WordPress 3.x Multi Site Installations
* Feature: rewitten boot loader
* Feature: new tab for translation file monitoring
* Feature: support and visualize server diskspace quota's if permitted
* Feature: support HTML comment section at front pages for performance measurement
* Feature: blog overview at Multi Side WordPress tab
* Feature: moved the Plugins Admin Page form Settings Menu to Dashboard Menu

= Version 1.2 =
* Bugfix: cloning option data PHP 5.3 version missing, fixed.
* Feature: separate Settings page introduced
* Feature: Dashboard Widget can be switched off
* Feature: Footer Memory Usage can be switched off
* Feature: more comprehensive Data added for analysing Installation
* Feature: completely translatable into your language now

= Version 1.1.5 =
* Bugfix: non object usage by PHP 5.3 version patch, fixed.

= Version 1.1.4 =
* Bugfix: breaks admin pages using PHP 5.3 version, get_class was emmitting warnings with non objects.

= Version 1.1.3 =
* Bugfix: in case of massive security restrictions of your provider PHP warnings avoided and additional infos will be show (see screenshot 2)
* Sorting: the order of versio release notes at changelog has been changed to "newest first"

= Version 1.1.2 =
* Bugfix: jQuery 1.2.6 Bug - damages table rows in Opera, Safari and Firefox during expand (WP 2.7)

= Version 1.1.1 =
* Bugfix: jQuery 1.3.2 Bug #4661 prevents expanding of sections at IE 8 and Safari 4 (WP 2.8)
* Bugfix: missing image attached causes unnecessary 404 request
* Sorting: some values of PHP tab has been moved in sections and got a modified text
* Feature: rewrite of info file to use this new changelog presentation option

= Version 1.1 =
* Feature: Database section extended
* Bugfix: Correct value display for WordPress settings

= Version 1.0 =
* initial version (formally hosted at my site)





== Frequently Asked Questions ==
= History? =
Please visit [the official website](http://www.code-styling.de/english/development/wordpress-plugin-wp-system-health-en "WP System Health") for the latest information on this plugin.

= Where can I get more information? =
Please visit [the official website](http://www.code-styling.de/english/development/wordpress-plugin-wp-system-health-en "WP System Health") for the latest information on this plugin.


== Screenshots ==
1. the Dashboard Widget with live performance monitoring for last approx. 2 minutes.
1. the System Tab (with one expanded detail section)
1. the System Tab (if your provider has massive security restrictions)
1. the PHP Tab (all details collapsed)
1. the WordPress Tab (all details collapsed)
1. the Database Tab (all details collapsed)

