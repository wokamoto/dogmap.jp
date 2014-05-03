=== Simple Category Search ===
Contributors: wokamoto
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: search, ajax, category
Requires at least: 3.2
Tested up to: 3.9
Stable tag: 0.2.1

You can choose (child) categories and see the posts belong to the chosen category with AJAX.

== Description ==

You can choose (child) categories and see the posts belong to the chosen category with AJAX.

When the category you choose from the list box has child categories, the child categories will be shown in a new list box.
Clicking the "Search" button, the list of posts belong to the bottom category which you choose will be displayed under the "Search" button.

== Installation ==

1. Upload the entire `simple-category-search` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the following code in your Post or Page:

`[category-search]`

= Plugin Usage =

After enabling the plugin, put the short code below into a page or a post.
`[category-search]`

Confining to a specific category, put the category ID like below.
`[category-search parent=10]`
(Put the category ID after "parent=".)

The list box is wrapped by "div.categories_search", the search result is wrapped by "div.search_result".
You can apply CSS as you like.

e.g.)
`.categories_search select{
  display:inline;
  margin-right: .25em;
  float:left;
  height:22px
}`

== Changelog ==

**0.2.1 - April 29, 2014**
Source code refactoring

**0.2.0 - September 15, 2011**
Initial release.

== Upgrade Notice ==

none

