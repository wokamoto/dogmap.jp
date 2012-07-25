=== Total Backup ===
Contributors: wokamoto, megumithemes
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: Backup, Ajax
Requires at least: 2.9
Tested up to: 3.3.1
Stable tag: 0.3.5.2

This plugin backs up a whole WordPress site and its database into a zip file.

== Description ==

This plugin backs up a whole WordPress site and its database into a zip file. 

Click the "Site Backup" button to start the backup.
While backing up, the site is in the maintenance mode.
Completed with no problem, the back up files are listed up.
Backing up is a kind of heavy process, so too many files may cause timeout errors.


**PHP5 Required.**

Special thx
 [DigitalCube Co. Ltd.](http://www.digitalcube.jp/ "DigitalCube Co. Ltd.")
 [takenori matsuura](https://twitter.com/tmatsuur "@tmatsuur")

= Localization =
"Total Backup" has been translated into languages. Our thanks and appreciation must go to the following for their contributions:

* Japanese (ja) - [OKAMOTO Wataru](http://dogmap.jp/ "dogmap.jp") (plugin author)

If you have translated into your language, please let me know.

== Installation ==

1. Upload the entire `total-backup` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

= Usage =

In the "Option" screen, you can set 2 options for now.

* Archive Path: the directory to which the plugin save the back up files.
* Excluded Dir: the directories which the plugin exclude to back up.

For Archive Path, specify the directory which the web server has the permission to write, but can not be accessed via the web. 
The default path is the directory for the temp files returned by sys_get_temp_dir().

For Excluded Path, specify the directories which you don't want to back up.
Default directories are below:

* wp-content/cache/ : the directory for the cache files used by WP super cache and so on.
* wp-content/tmp/ : the directory for the cache files used by DB Cache Reloaded Fix so on.
* wp-content/upgrade/ : the directory for the temp files used by the WordPress upgrade function.
* wp-content/uploads/ : the directory for the uploaded files like images.

It the Total Backup screen, click the "Site Backup" button to start the backup right away.
While backing up, the site is in the maintenance mode.
Completed with no problem, success.png shows up next to the button, and the back up files (.zip) are listed up below.
Backing up is a kind of heavy process, so too many files may cause timeout errors. In such a case, please modify the timeout value of your Web server or PHP.

You can download the backup files from the links in "Backup Files" area.
To delete old backup files, check a box and click the "Delete" button.

The backup file of DB is included in the zip file as {the directory name of WordPress}.yyyymmdd.xxx.sql. When you restore, please use phpMyAdmin or the mysql command. 


== Frequently Asked Questions ==

none

== Changelog == 

**0.3.1 - Oct. 12, 2011**  
Minor bug fix.

**0.3.0 - Oct. 11, 2011**  
Initial release.

