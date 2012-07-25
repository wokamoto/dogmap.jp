=== Short link maker ===
Contributors: wokamoto
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: revcanonical, links, url, shorter, shorturl
Requires at least: 2.7
Tested up to: 3.0
Stable tag: 0.1.5.5

This is a plugin creating a shorter URL of post, page and media permalink.

== Description ==

The **Short link maker** WordPress creating a shorter URL of post, page and media permalink.
The URL in the href attribute defaults to the id number of the post in question.

Example:

    `http://example.com/archives/1606 -> http://example.com/Bb`


This plugin automatically creates a link element in the <head> section of the post's page with a rel="shorturl" attribute. 
It also creates an HTTP `Link` header that also points to the shorter link.


The <link> element looks like this:

    `<link rel="shorturl" href="{url}" />`


The HTTP header is:

    `Link: <{url}>; rel=shorturl`

= Related Links =

* [Google Code Site:](http://code.google.com/p/shortlink/)

= Localization =

* Belorussian (be_BY) - [FatCow](http://www.fatcow.com/ "FatCow")
* Dutch (nl_NL) - [Rene](http://wpwebshop.com/blog "WPWebshop Blog")
* German (de_DE) - [Rian Klijn](http://www.creditriskmanager.com/ "Credit Risk Manager")
* Japanese (ja) - [OKAMOTO Wataru](http://dogmap.jp/ "dogmap.jp") (plugin author)

If you have translated into your language, please let me know.

== Installation ==

1. Upload the entire `short-link-maker` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress


== Screenshots == 

1. Edit Posts
2. Edit Post


== Licence ==

Released under the [GPL license](http://www.gnu.org/copyleft/gpl.html).


== Changelog == 

**0.1.5 - 27 May 2010**  
Minor bug fixes.

**0.1.4 - 27 May 2010**  
Minor bug fixes.

**0.1.3 - 27 May 2010**  
Support for WordPress 3.0-beta 2.

**0.1.2 - 14 May 2010**  
Support for WordPress 3.0-beta 1.

**0.1.1 -  6 January 2010**  
Fixed a conflict with [WordPress.com Stats](http://wordpress.org/extend/plugins/stats/).

**0.1.0 - 27 October 2009**  
Initial release.
