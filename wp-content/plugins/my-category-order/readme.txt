=== My Category Order ===
Contributors: froman118
Donate link: http://geekyweekly.com/gifts-and-donations
Tags: categories, category, order, sidebar, widget
Requires at least: 2.8
Tested up to: 3.3.2
Stable tag: 3.3.2

My Category Order allows you to set the order in which categories will appear in the sidebar.

== Description ==

[My Category Order](http://geekyweekly.com/mycategoryorder) allows you to set the order in which categories will appear in the sidebar. Uses a drag 
and drop interface for ordering. Adds a widget with additional options for easy installation on widgetized themes.

= Big Update! =

My Category Order has been out since WP 1.5 or 2.0 (2006) and it's been a struggle to keep it working smoothly. The taxonomy.php hack was hideous and a filter finally got added that let me avoid this. No more visiting the My Category Order page after every single Wordpress update!

As of version 2.8.6 of the plugin I'm breaking backwards compatibility to add new features like a multiple widget instances. Keep using version [2.8.3](http://downloads.wordpress.org/plugin/my-category-order.2.8.3.zip) if you are not on WP 2.8 yet.

== Change Log ==
= 3.3.1 =
* Added Danish translation
= 3.0.1 =
* Added support for multiple Category dropdowns on the page, previously only the first one worked
* Updated drag and drop to include a placeholder, makes it much easier to see where items will move
* Updated styles to fit in with Wordpress better
* Updated page code to use regular submit buttons, less reliance on Javascript and query strings
* Added Ukranian translation, thanks Jurko
= 2.8.7 =
* Widget and translation bug fixes
* Added Portuguese translation, thanks Tiago
= 2.8.6 =
* Significant backend changes, only compatible with 2.8 and above
* Transitioned to new Widget API, breaking backwards compatibility in the process
* Multiple widgets are now supported
* Widget options will have to repopulated
* Added more complete widget options, should be able to do just about everything you can in code
* Removed taxonomy.php hack, hooking into "get_terms_orderby" filter for category ordering now, no more visiting page after each Wordpress update
* The PO file has changed and translations will need to be updated
= 2.8.3 =
* Trying to fix Javascript onload issues. Settled on using the addLoadEvent function built into Wordpress. If the sorting does not initialize then you have a plugin that is incorrectly overriding the window.onload event. There is nothing I can do to help. 
= 2.8.1 =
* Added Czech translation (Jan)
= 2.8 =
* Updated for 2.8 compatibility
= 2.7.1 =
* If your categories don't show up for ordering your DB user account must have ALTER permissions, the plugin adds columns to store the order
* Added a call to $wpdb->show_errors(); to help debug any issues
* Translations added and thanks: Spanish (Karin), German (Wolfgang and Mike), Swedish (Mans), Italian (Stefano)
= 2.7 =
* Updated for 2.7, now under the the new Pages menu
* Moved to jQuery for drag and drop
* Removed finicky AJAX submission
* Translations added and thanks: Russian (Flector and Pink), Dutch (Anja), Polish (Zbigniew)
* Keep those translations coming
= 2.6.1a =
* The plugin has been modified to be fully translated
* The plugin patch no correctly patches taxonomy.php
* New translation added : French, by Brahim Machkouri (http://www.category-icons.com)
* The widget has now a description
= 2.6.1 =
* Finally no more taxonomy.php overwriting, well kind of. After you upgrade Wordpress visit the My Category Order page and it will perform the edit automatically.
* Thanks to Submarine at http://www.category-icons.com for the code.
* Also added string localization, email me if you are interested in translating.


== Installation ==

1. Install and activate the plugin
3. Go to the "My Category Order" tab under Posts and specify your desired order for post categories
4. If you are using widgets then replace the standard "Category" widget with the "My Category Order" widget. That's it.
5. If you aren't using widgets, modify sidebar template to use correct orderby value:
	`wp_list_categories('orderby=order&title_li=');`

== Frequently Asked Questions ==

= Why isn't the order changing on my site? =

The change isn't automatic. You need to modify your theme or widgets.

= Like the plugin? =

If you like the plugin, consider showing your appreciation by saying thank you or making a [small donation](http://geekyweekly.com/gifts-and-donations).