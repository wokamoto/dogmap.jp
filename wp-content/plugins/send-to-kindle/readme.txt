=== Send to Kindle ===
Contributors: dskidmor
Tags: amazon, kindle, reader, sharing, reading, news, sending, plugin
Requires at least: 3.1
Tested up to: 3.5.1
Stable tag: 1.0.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Send to Kindle Button lets readers enjoy your blog anytime, everywhere on
their Kindle devices and free reading apps.

== Description ==

Adding this button to your site opens it up to millions of Kindle customers that
want to enjoy your blog on their Kindle.

= Features =

* Readers can send posts to multiple Kindles at once.
* Posts can be saved in the cloud and accessed anytime, everywhere.
* Configure how the button will look and which page types it will appear on.
* Use the shortcode `[sendtokindle]` to place a Send to Kindle button anywhere.

== Installation ==

Use the built-in WordPress plugin installer to automatically put everything in
the right place. If you prefer to install plugins manually, put Send to Kindle
with your other plugins: `wp-content/plugins/send-to-kindle/`

To activate Send to Kindle, look inside the admin panel for the "Plugins" tab.
By clicking "Activate" you agree to the [Send to Kindle Terms of
Use](https://www.amazon.com/gp/help/customer/display.html?&nodeId=201139380).

== Frequently Asked Questions ==

= I use a custom theme and my content doesn't extract! =

Some themes make a lot of changes to the structure of your blog without reusing
the normal CSS classes. In this case, you should use the Advanced settings
screen to create custom CSS selectors. These will allow the button to find your
content body and exclude unrelated information.

= How do I design the button to fit on my page? =

In the Advanced settings screen, there is an option to provide your own HTML
markup to customize how you want the button to look. Just make sure to give it
the `kindleWidget` class so our JavaScript can still find it.

= Why don't my images appear in the preview window? =

Usually, this is caused by hosting images on a different domain than what the
content is hosted on, such as a content delivery network. By default, web
browsers restrict scripts from downloading resources if the domains do not
match. You can override this behavior by modifying HTTP headers related to
[Cross-Origin Resource Sharing](http://www.w3.org/TR/cors/). Otherwise, place a
copy of the image on the same server as the content and that will be downloaded
instead. Images that cannot be downloaded will not appear in the preview window.

= What does "content body" versus "exclude" mean? =

The content body refers an element on the page containing all of the content for
a given article. If content is spread out across multiple paragraph tags, and
all of those tags are contained in single section tag, the content refers to the
section tag. Sometimes, unwanted elements such as sidebars, advertising,
comments and other sections are inline with the content. You can exclude all of
these pieces with a comma-separated list of CSS selectors.

= How do I indicate multi-page articles? =

Use the Advanced settings to create a custom CSS selector for your content. The
selector should point to a single link which goes to the next page. For example,
if you have a link to the next page with an id "next", use the selector `a#next`.

== Screenshots ==

1. The settings screen allows you to easily change how the Send to Kindle Button
will look on your blog with the live preview feature.
2. The advanced settings screen lets you specify exactly what content gets
extracted and exactly how you want to design your button.

== Changelog ==

= 1.0.2 =
* Improved the button display for certain WordPress themes.

= 1.0.1 =
* Changed the script to being included in the footer to prevent initialization
before the button has appeared on the page.

= 1.0.0 =
* Initial release!
