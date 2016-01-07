=== Head Cleaner ===
Contributors: wokamoto, megumithemes, Webnist, tai
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: head, header, footer, javascript, css, optimization, minified, performance, facebook, OGP
Requires at least: 2.5
Tested up to: 4.4
Stable tag: 1.4.3

Cleaning tags from your WordPress header and footer.

== Description ==

Cleaning tags from your WordPress header and footer.
To speed up the loading of JavaScript and CSS.

**PHP5 Required.**

= Features =

* IE6 at the top of the non-grant xml declaration.
* Tags and duplicate, unnecessary tags, comments, and remove the blank.
* `<meta Name="description" />` If you have multiple tags into one.
* `<meta Name="keyword" />` If you have multiple tags into one.
* `<link rel="canonical" />` add tags. 
* Add OGP (Open Graph Protocol) tags.
* IE conditional Tag, if your browser is IE, display when that tag.
* CSS, JavaScript, if your browser supports gzip compression transfer.
* Have multiple CSS files into the media and a binding for each attribute.
  Of course, the file contains inline CSS. 
* CSS minified. 
* URLs of images in CSS will be converted into the data scheme URIs.
* Have multiple JavaScript files into a binding and all. 
  Of course, the file also includes an inline JavaScript. 
* JavaScript source code minified at [JSMin](http://code.google.com/p/jsmin-php/ "JSMin").
* JavaScript can also be moved to the footer area. 
* JavaScript footer areas are combined into a single file as well. 
* Prototype.js, script.aculo.us, jQuery, mootools is being loaded more than one case, one to read only once. 
* Prototype.js, script.aculo.us, jQuery, mootools and fix the load order to avoid possible conflicts. 

= Localization =
"Head Cleaner" has been translated into languages. Our thanks and appreciation must go to the following for their contributions:

* Belorussian (by) - [Marcis Gasuns](http://www.comfi.com/ "Marcis Gasuns")
* Bulgarian (bg_BG) - [Web Geek](http://webhostinggeeks.com/ "Web Geek")
* Dutch (nl_NL) - [Rene](http://wpwebshop.com/blog "WPWebshop Blog")
* French (fr_FR) - [NicoR](http://wordpress.org/support/profile/nicor-1 "NicoR")
* German (de) - Carsten
* Japanese (ja) - [OKAMOTO Wataru](http://dogmap.jp/ "dogmap.jp") (plugin author)
* Spanish (es) - [Franz Hartmann](http://tolingo.com/ "tolingo.com - Franz Hartmann")
* Russian (ru) - [ilyuha](http://antsar.info/ "ilyuha")
* Romanian (ro_RO) - [Web Geek Sciense](http://webhostinggeeks.com/ "Web Hosting Geeks")
* Turkish (tr_TR) - [Hakan Demiray](http://www.dmry.net/ "Günlük Haftalık Aylık")

If you have translated into your language, please let me know.

== Installation ==

1. Upload the entire `head-cleaner` folder to the `/wp-content/plugins/` directory.
2. Please make `js` and `css` a directory under the `/wp-content/cache/head-cleaner/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

The control panel of Head Cleaner is in 'Settings > Head Cleaner'.

**PHP libraries are using [Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net/ "Simple HTML DOM Parser") and [JSMin](http://code.google.com/p/jsmin-php/ "JSMin"). PHP5 Required.**

== Frequently Asked Questions ==

If you want to exclude certain statements in shaping the header.php theme needs to be modified. 
Specifically, the portion to be excluded from shaping <?php wp_head ();?> Please describe below. 

The theme you are using plug-ins do not work together properly. 
If you do not work properly, after stopping all the other plug-in plug-in, 
To activate each one, please check if any conflicts with plugins. 

= CSS, JavaScript caches is not made.  =

Two directories: CSS, JavaScript caches.
If you want to enable the cache, please create a folder.

* `wp-content/cache/head-cleaner/css`
* `wp-content/cache/head-cleaner/js`

= PHP Warning: get_browser(): browscap ini directive not set in head-cleaner.php =

In order for this to work, your browscap configuration setting in php.ini must point to the correct location of the browscap.ini file on your system.
see. [PHP: get_browser]("http://php.net/manual/en/function.get-browser.php "PHP: get_browser - Manual")

Head Cleaner Ver.1.1.5 includes 'php_browscap.ini' . 

== Screenshots ==

1. The admin page
2. YSlow Result

== Changelog == 

**1.4.3 - December 22, 2015**
Support WordPress 4.4.
Thx [msng](http://www.msng.info/)

**1.4.2.12 - August 29, 2014**
Minor bug fix.

**1.4.2.9 - April 20, 2012**  
Fixed a bug, input value validate issue.

**1.4.2.6 - December 13, 2011**  
Support for WordPress 3.3.

**1.4.2.3 - June 8, 2011**  
Support for WordPress 3.2.

**1.4.2.2 - April 26, 2011**  
Fixed a bug, wp_die() error messages none at line 3021.

**1.4.2.1 - April 20, 2011**  
Fixed a bug, html tag issue.

**1.4.2 - April 20, 2011**  
Add an item to the Options page set.
(Added the option to "Add Last-Modified tag" and "Paranoia mode")

**1.4.1.3 - March 8, 2011**  
Minor bug fixes. thx [HaRD](http://www.karuta.org/wp/ HaRD) !

**1.4.1.1 - January 25, 2011**  
Fixed a bug, Canonical tag issue.

**1.4.1 - January 19, 2011**  
Add OPG (Open Graph Protocol) tags.

**1.4.0 - December 2, 2010**  
URLs of images in CSS will be converted into the data scheme URIs.

**1.3.13 - August 20, 2010**  
Twenty Ten Themes support.

**1.3.12 - August 11, 2010**  
Fixed a bug, IE Conditional tags.

**1.3.11 - July 7, 2010**  
Minor bug fixes.

**1.3.7 - April 15, 2010**  
Fixed a bug, css @import interpreting the instructions.

**1.3.6 - February 15, 2010**  
Minor bug fixes.

**1.3.5 - February 10, 2010**  
Fixed a conflict with "[wpSEO](http://www.wpseo.org/)".

**1.3.4 - February  9, 2010**  
Fixed a conflict with "[All in One SEO Pack](http://wordpress.org/extend/plugins/all-in-one-seo-pack/)".

**1.3.3 - January 27, 2010**  
Add an item to the Options page set.
(Added the option to remove the JavaScript)

**1.3.2 - January 25, 2010**  
In the inline CSS "@import", &lt;link rel=&quot;stylesheet&quot;&gt; was modified to convert the tag.

**1.3.1 - September 8, 2009**  
Minor bug fixes.

**1.3.0 - July 5, 2009**  
Google AJAX Libraries support.

**1.2.0 - April 5, 2009**  
Add an item to the Options page set.

**1.1.3 - March 19, 2009**  
Adjust the filter to display the active filters.

**1.1.2 - March 17, 2009**  
Minor bug fixes.

**1.1.1 - March 10, 2009**  
JavaScript, filter set an expiration date of the analysis.

**1.1.0 - March 9, 2009**  
JavaScript has to be moved to the footer.

**1.0.3 - March 7, 2009**  
Fixed a bug in IE conditional tags Decision.

**1.0.2 - March 7, 2009**  
Fixed a conflict with "[WordPress.com Stats](http://wordpress.org/extend/plugins/stats/)" and "[Ultimate Google Analytics](http://wordpress.org/extend/plugins/ultimate-google-analytics/)".

**1.0.1 - March 6, 2009**  
script.aculo.us was to correct the load order.

**1.0.0 - March 5, 2009**  
Initial release.
