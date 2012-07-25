=== WP Memcached Manager ===
Contributors: warwickp
Tags: memcached, memcache, cache
Requires at least: 2.7
Tested up to: 2.8.6
Stable tag: 0.1

A very simple tool to manage Memcached servers from inside the WordPress admin interface. Requires PECL Memcache client.

== Description ==

If you use Memcached, then sometimes it's handy to be able to administer your Memcached instances from inside WordPress.

With this plugin you can check your Memcached server is running, some basic usage stats and initiate cache flushes. In future version you'll be able to do more useful Memcached admin things.

== Installation ==

1: You need the PECL Memcache extension / client installed. Please see: http://www.php.net/manual/en/memcache.installation.php 

2: Install the WP Memcached Manager WordPress plugin and activate it.

3: Navigate to the Memcached menu in WordPress, and use the Edit Servers screen to define your Memcached servers. 

4: Then use the main Memcached menu to manage each instance. 

== Frequently Asked Questions ==

= Is this safe to use in production? =

Should be, but be very careful. Memcached data is obviously volatile and you should never expect it to survive most any server problems, however, besides clobbering data you could perform tasks on Memcached instances using this tool which block regular Memcached cache activity, or agressively claim RAM like it's the end of days.

= What exactly does the View Data tool do?  =

Shows you a very, very rudimentary listing of 100 keys and values from your Memcached instance. In future versions it will be more useful.

= Should I use the View Data tool on a large Memcached instance?  =

Negative. No. You don't want to be doing a slab dump on any significant set of data. Please don't. 

= Does this add stuff to my cache? =

Yes, it sets some testing keys. As of right now a PECL client bug prevents nice orderly deletes of these test keys, so they are not deleted. But this will change.


== Screenshots ==

1. An overview of the main stats and management screen. 

== Changelog ==

= 0.1 =

* Initial Release.
