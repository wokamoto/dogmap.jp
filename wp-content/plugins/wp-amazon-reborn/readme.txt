=== WP-Amazon 1.x for WordPress 2.5+ ===
Contributors: wokamoto
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: amazon
Requires at least: 2.5
Tested up to: 3.1
Stable tag: 1.4.3.1

WP-Amazon adds the ability to search and include items from Amazon to your entries.  This plugin adds a button called "Amazon" on the post page.  Make sure to configure the plugin before using. This Plugin was based on WP-Amazon Plugin 1.3.2 by Rich Manalang

== Description ==

WP-Amazon adds the ability to search and include items from Amazon to your entries.  This plugin adds a button called "Amazon" on the post page.  Make sure to configure the plugin before using. This Plugin was based on WP-Amazon Plugin 1.3.2 by Rich Manalang

= Localization =
* Japanese (ja) - [OKAMOTO Wataru](http://dogmap.jp/ "dogmap.jp") (plugin author)

If you have translated into your language, please let me know.

== Installation ==

1. Upload the entire `wp-amazon-reborn` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter your 'Associates ID', 'Subscription ID' and 'Secret Key' of Amazon E-Commerce Service through the 'Amazon' menu under 'Settings' in your dashboard.

== Changelog == 

1.4.3 - Added "delete options" button.
        wokamoto (http://dogmap.jp/) @ 2009.07.28
1.4.2 - Added support for Amazon Product Advertising API.
        wokamoto (http://dogmap.jp/) @ 2009.07.28
1.4.1 - Fixed bug of "Multi language support".
        wokamoto (http://dogmap.jp/) @ 2009.01.07
1.4.0 - Added support for WordPress 2.5.x  Still supports 2.3.x. or older
      - Multi language support (but Japanese and English only...)
        wokamoto (http://dogmap.jp/) @ 2008.06.19
1.3.2 - Reverted to using a hyperlink below the post textarea to launch
        WP-Amazon... too many people complained about it not working
        when a WYSIWYG plugin was enabled.
1.3.1 - Amazon changed their XML format which prevented WP-Amazon from
        displaying images returned from results.  Thanks go to Nick Walton
        (http://www.nickwalton.com/) for finding and reporting the problem.
1.3   - Added plugin options page so users can update the default country, 
        associate ID, and subscription ID without modifying the plugin source.
      - Added a "check for update" feature that allows users to easily see if 
        their plugin is up-to-date.
      - Dropped support for 1.2.x
1.2.8 - Changed the Amazon Link on the edit page to be a button in the
        Quicktags region.  Allows for WP-Amazon to work with the Tiger
        Style Administration plugin.
1.2.7 - Added some inline CSS to hide the admin header
1.2.6 - Added Added Michiel Maandag's enhancements 
        (http://wordpress.org/support/topic/32412)
1.2.5 - Fixed code that checks if curl is installed.  By default,
        curl is preferred, but if your host doesn't have it installed,
        it downgrades to using fopen.
1.2.4 - Added support for WordPress 1.5.  Still supports 1.2.x.
      - Relocated wp-amazon.php to the plugins directory.
1.2.3 - Added support for Amazon ECS 4.0 France and Canada
1.2.2 - Added check to see if magic quotes is turned on or off.  This was
        causing a JavaScript problem with servers that had magic quotes turned
        off.
1.2.1 - Replaced htmlentities to htmlspecialchars to support special characters
        in Japanese and German
1.2   - Upgraded to Amazon E-Commerce Service 4.0
      - Removed dependency on NuSOAP and moved to using standard PHP XML support
      - Added ability to change the Amazon country site to search form
1.1   - Cleanup
1.0   - David Schlosnagle's blended search has been included (thanks David!)
0.92  - Removed target="_blank" and properly encodes entities to preserve
        XHTML compliance.
0.91  - Reworked search features. Removed search by and replaced with product
        line search field.  All searches are now by product.
      - Added support for Amazon UK, Amazon Germany, and Amazon Japan. Needs
        to be thoroughly tested still.       
0.9   - Initial release
