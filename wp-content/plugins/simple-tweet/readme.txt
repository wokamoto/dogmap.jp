=== Simple Tweet ===
Contributors: wokamoto
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: twitter, posts, OAuth, shortlink
Requires at least: 2.8
Tested up to: 3.1.2
Stable tag: 1.3.8.2

This is a plugin creating a new tweet including a URL of new post on your wordpress.

== Description ==

This is a plugin creating a new tweet including a URL of new post on your wordpress.

You can use specific tweet phrases by post authors.
And You can also tweet short URL of your domain if you introduce the plugin **[Short link maker](http://wordpress.org/extend/plugins/short-link-maker/ "Short link maker")**.

**PHP5 Required.**

= Localization =

* English (en) - [odyssey](http://www.odysseygate.com/ "Odysseygate.com")
* Japanese (ja) - [wokamoto](http://dogmap.jp/ "wokamoto") (plugin author)
* Belorussian (be_BY) - [Patricia](http://pc.de/ "PC.DE")
* Dutch (nl_NL) - [Rene](http://wpwebshop.com/blog "WPWebshop Blog")
* German (de_DE) - [Rian Klijn](http://www.creditriskmanager.com/ "Credit Risk Manager")

If you have translated into your language, please let me know.

== Installation ==

= Installation =
1. Upload the entire `simple-tweet` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter your user name and password of Twitter through the 'Simple Tweet' menu under 'Settings' in your dashboard.
4. If you use specific settings by post author, you can set through 'your profile' menu under 'Users' in your dashboard.
5. You can also tweet short URL of your domain if you introduce the plugin **[Short link maker](http://wordpress.org/extend/plugins/short-link-maker/ "Short link maker")**.
6. You can also tweet short URL of wp.me if you introduce the plugin **[WordPress.com Stats](http://wordpress.org/extend/plugins/stats/ "WordPress.com Stats")**.

= OAuth Settings =
Simple Tweet ver.1.3.0 or later supports OAuth on Twitter.

**Register a new application on Twitter**

First of all, you have to register a new application on Twitter, and get "**Consumer Key**" and "**Consumer Secret**".

1. While you are logged into Twitter, click the link "<a href="http://twitter.com/oauth_clients" title="Applications Using Twitter">Applications Using Twitter</a> on settings of Simple Tweet.
2. On the page registering an new application on Twitter, click the link "<a href="http://twitter.com/apps/new" title="Register an Application">Register an Application</a>".
3. Input the information of the application you register on Twitter.
   Items you need to input on registering are as follows...
   * Application Icon ... Select the icon of the application. You don't need to set this item.
   * Application Name ... Input the name of the application.
   * Description ... Input the explanation of the application.
   * Application Website ... Input the URL of your company or organization..
   * Organization ... Input the name of the organization whichi makes the application. Your name or nickname may be good.
   * Website ... Input the website of the organization which makes the application.
   * Application Type ... Input the type of the application.
     Select "**Client**" on this item.
   * Default Access type ... This item allows the application to "read and write" or "read-only".
     Select "**Read &amp; Write**" on this item.
   * Use Twitter for login ... If you use Twitter id on authentication, check this. You may not need to check this.
4. Upon success of the application registration, then you can see application information.
   "Consumer Key" and "Consumer Secret" displayed here are important.
5. Go back to settings of Simple Tweet, then input "Consumer Key" and "Consumer Secret" and update settings.

**Authorize your acount by OAuth**

Next, you have to authorize your account by OAuth.

1. If you input "Consumer Key" and "Consumer Secret", you can see the link "Click on the link to go to twitter to authorize your account." on settings of "Simple Tweet" or profile of each users.
   Click this link.
2. When You're asked to accept access of the application you registerd, then click "Allow".
   If you can't this page, application registration on twitter may be incomplete ,or "Consumer Key" and "Consumer Secret" you input may be wrong.
   If they are not wrong, after a while try to click the link "Click on the link to go to twitter to authorize your account." again
3. You can see a few digits of a number as PIN.
4. Go back to settings of Simple Tweet, input PIN and update settings.

If you want to delete OAuth settings, check "OAuth Reset" and update settings.

== Screenshots == 

1. Simple Tweet Settings
2. User Profile


== Licence ==

Released under the [GPL license](http://www.gnu.org/copyleft/gpl.html).


== Changelog == 

**1.3.3 - May 1, 2010**  
Minor bug fixes.

**1.3.2 - April 30, 2010**  
Added support to j.mp and is.gd.

**1.3.1 - February 15, 2010**  
Minor bug fixes.

**1.3.0 - February 10, 2010**  
Added support for OAuth.

**1.2.1 - January 20, 2010**  
Fixed a bug that didn't work, "publish future post".

**1.2.0 - November 5, 2009**  
Initial release.
