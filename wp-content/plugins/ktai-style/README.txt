=== Ktai Style ===
Contributors: lilyfan
Tags: mobile, keitai, ktai, phone, admin, Japan, pictogram, docomo, i-mode, au, EZweb, SoftBank, iPhone
Requires at least: 2.8
Tested up to: 3.0.6
Stable tag: 2.0.5

"Ktai Style" provides lightweight pages and simple admin interfaces for mobile phones.

== Description ==

[日本語の説明を読む](http://wppluginsj.sourceforge.jp/ktai_style/)

"Ktai Style" is a plugin providing lightweight pages and simple admin interfaces for mobile phones. Especially, this plugin is optimized for Japanese mobile phone: NTT docomo, au by KDDI, SoftBank, E-MOBILE, and WILLCOM.

* Show lightweight output for mobile phones.
* Has simple admin feature.
* Mobile themes can be customized based on WordPress theme spec.
* Supports pictograms in mobile themes.
* Shrink image and creates small thumbnails.
* Split web pages along the page limit.
* Check if the link target has mobile sites, along [Mobile Link Discovery](http://www.sixapart.jp/docs/tech/mobile_link_discovery_en.html).
* Can switch mobile/PC mode for smart phones (iPhone, Windows Mobile, etc) and non-phone devices (PSP, Nintendo DS, etc).

== Screenshots ==

1. Mobile view with Default mobile theme. Fist post with content, following posts are only title. Pictograms (icon of arrows, face, clock, numbers) are used for mobile theme. Menus are located at the lower part of screen.
2. Comment form for mobile.
3. Recent comments page is an individual page. Sidebars and widgets are not available.
4. Login form for mobile.
5. Mobile dashboard. Current status, recent comments, recent drafts are available. Menus are located at the lower part of screen.
6. Edit posts panel.
7. Mobile theme select panel (At the PC admin). You can select themes from screenshot of mobile theme and check the mobile output by preview.

== Requirements ==

* WordPress 2.8 or later
* PHP 5.0 or later (NOT support PHP 4.x!!)
* Available phone/gadgets: NTT docomo, au by KDDI, SoftBank, E-MOBILE, WILLCOM, iPhone/iPod touch (need to configure), Nokia, BlackBerry, Windows Mobile terminals, Palm OS handheld, Sharp Zaurus, PSP, Nintendo DS

== Installation ==

1. Unzip the plugin archive and put `ktai-style` folder into your plugins directory (`wp-content/plugins/`) of the server. 
1. If you do NOT need simple admin feature, omit `admin` folder in `ktai-style` folder.
1. Activate the plugin.
1. Configure the plugin at "Mobile Output" menu at the WordPress admin panel. The default configuration is optimized for generic use.

= Moving wp-content, or wp-content/plugins directory to non-standard position =

After WordPress 2.6, you can move wp-content, or wp-content/plugins directory to non-standard position. If you move the directory, additional configuration is needed.

1. When installing the plugin, the permission of the `ktai-style` directory to 757 or 777, so that the webserver can touch the directory.
1. Activate the plugin, then `wp-load-conf.php` file is automatically created. It is OK.
1. If the file is not created, you need to edit `ktai-style/wp-load.php` manually. At line 20, change `$wp_root` variable to specify the absolute path to WordPress installed directory.

	e.g. WP diretory is `/home/foo/public_html/wp-core/` and wp-content directory is `/home/foo/public_html/wp-content/`
	`$wp_root = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-core/';`

= Combination with WP Suer Cache =
If you use [WP Super Cache](http://ocaoimh.ie/wp-super-cache/) by Donncha, additional configuration is needed.

1. Locate the file `ktai-style/patches/supercache-plugin-ktaistyle.php` into the directory under WP Super Cache. It will be ordinary `wp-content/plugins/wp-super-cache/plugins`.
1. Activate Ktai Style and WP Super Cache.
1. Enable `Ktai Style Support` option in the lower part of WP Super Cache admin panel.
1. Turn on `Mobile Device Support` at WP Super Cache admin panel. Or, set `Mod Rewrite Rules` while Mobile Device Support` is turned off. Make sure below two or three lines is included in the Rewrite rule.
  `RewriteCond %{HTTP_USER_AGENT} !^(DoCoMo/|J-PHONE/|J-EMULATOR/|Vodafone/|MOT(EMULATOR)?-|SoftBank/|[VS]emulator/|KDDI-|UP\.Browser|emobile/|Huawei/|IAC/|Nokia|mixi-mobile-converter/)
  RewriteCond %{HTTP_USER_AGENT} !(DDIPOCKET;|WILLCOM;|Opera\ Mini|Opera\ Mobi|PalmOS|Windows\ CE;|PDA;\ SL-|PlayStation\ Portable;|SONY/COM|Nitro|Nintendo)`
1. If you want to apply mobile output for iPhone, iPod, and Android, below line is need after anove 2 lines.
  `RewriteCond %{HTTP_USER_AGENT} !(iPhone;|iPod;|Android)`

= Combination with WP-Cache =
If you use [WP-Cache](http://mnm.uib.es/gallir/wp-cache-2/) by gallir, additional configuration is needed.

1. Create `wp-cache-config.php` file by duplicating `wp-cache-config-sample.php` file.
1. Around line 19 of `wp-cache-config.php`, next to the line setting `cache_rejected_user_agent`, add below code. If you move `wp-content` directory to non-standard position, adjust the path to the directory.
  `if (file_exists(ABSPATH. 'wp-content/plugins/ktai-style/patches/wpcache.php')) {
        include ABSPATH. 'wp-content/plugins/ktai-style/patches/wpcache.php';`
1. Locate `wp-cache-config.php` under `wp-content/` and activate WP-Cache plugin.

== Licence ==

The license of this plugin is GPL v2 or later.

== Getting a support ==

To get support for this plugin, please contact below methods:

1. Send an email to yuriko-ktaistyle _@_ YURIKO (dot) NET. (You need adjust to the valid address)
1. Use contact form at http://www.yuriko.net/contact/ (Japanese site)
1. Post WordPress forum with 'ktai-style' tag.

== Changelog ==

= 2.0.5 (2011-08-21) =
* Fixed the bug that settings of individual theme for smart phones (iPhone, Android, BlackBerry) is not useless.
* Enabled Dashboard and Profile panels for WordPress 3.1 or later.

= 2.0.4 (2011-08-18) =
* Fixed the bug that close tag of comments list was not printed. Syntax error with iPhone/ipod touch is corrected.
* Fixed the bug that strings outside `img` element inside `a` element are ignored. (e.g. `<a href="XXXX">IGNORED<img src="YYYY" /></a>`)
* Fixed the bug that images at multi sites with WordPress 3.0 or later is treated as single sites.
* Stop admin feature at WordPress 3.1 or later. Because, editing comments panel is not compatible.
* Fixed the bug that the link of canceling edit/back to the list is not shown at editing pages.
* Ktai Style considers that termnial ID cannot get at SSL connection. `ks_is_require_term_id()` returns false.
* `ks_in_network()` followed the new IP addresses of major four careers.
* Added recent phones of au by KDDI for `ks_term_name()`.

= 2.0.3 (2010-05-13) =
* Fixed HTML syntax error at footer.php of Classic, Default theme.
* Fixed bug that `ks_term_name()` does not show the proper name for some EZweb phones.

= 2.0.2 (2010-04-22) =
* Fixed infinity loop problem (causes HTTP error 502 etc) at Ktai Style 2.0.1.1.
* Fixed appearing white screen at mobile theme preview when you use P2 theme for PC.
* Improved compatibility for PHP without mbstring extension.

= 2.0.1.1 (2010-04-20) =
* `ks_in_network()` followed the new IP addresses of SoftBank.

= 2.0.1 (2010-04-20) =
* Exclude Apple iPad from mobile output.
* Fixed not shown images which does not have alt attributes. (bug only at Ktai Style 2.0.0)
* Fixed not shown images whose path has a number-only level on Windows servers.
* Show message at login form if mobile login session has been expired.
* Show security notice at mobile admin panel for cookie-disabled phones.
* Fix to remove the current mobile theme from mobile themes list, if the current theme is in `ktai-themes` directory.

= 2.0.0 (2010-03-22) =
* Limit supporting WordPress version into 2.8 and later.
* Distributed at the official WordPress plugin direcotry.
* Move settings menu into individual admin menu.
* Abolish "Image output for 3G phone, WILLCOM, smartphones, etc" setting.
* You can select a mobile theme by list of screenshots.
* Enabled viewing mobile pages with logging-in if you use cookie available phones.
* You can post comments with logging-in with cookie available phones.
* Show an "edit" link if you have the privilege to change a post/page/comment  with cookie available phones.
* Abolish logging-in feature for NTT docomo mova phones, and SoftBank PDC phones.
* When clicking "Logout" at comment form for log-in users, return to the comment form insted of login form.
* Support "Trash" of WordPress 2.9.
* Fixed a problem that white screen of PHP error are sometimes shown by loading mobile theme in spite of viewing by PC.
* Fixed a problem the number of comments is invalid (two are shown for zero).
* Detect Mobage-town, and Hatena Mobile Gateway as search engines.
* Fixed garbling under `mbstring.encoding_translation = On`.
* Reads functions.php of current PC theme for mobile mode.
* Abolish transferring path/URI of get_stylesheet(), get_templates(), load_theme_textdomain(), etc from PC themes to mobile themes. Theme functions now returns PC themes. Please use `ks_get_theme(), ks_get_theme_directory(), ks_get_theme_uri()` to get mobile theme.
* `ks_term_name()` now returns a name of the search engine. To get a terminal name of the engine, use `ks_term_name(KTAI_NOT_ECHO, false);`.
* Change the default value of the `before` parameter of `ks_login_link()` into empty.
* Abolish "redir/ktai_syle.php" filter. Please use redir.php template.
* Change the name of exclusive filters to "ktai_XXXXX" from "XXXXX/ktai_style.php"
* Change the name of constants to "KTAI_XXXXX" from "KS_XXXXX".

= 1.83 =
* (Version 1.83 to 1.99 has been branched. Please see the history of 1.83 package.)

= 1.82 (2010-03-06) =
* Support the new phones of au (by KDDI), and EMOIBLE "H31IA".
* Not convert of the math images by WP-LaTeX plugin.
* Not use redirect page for external link if accessed by search engines.
* Fixed a problem that chages of "Date and time format of posts/comments" are not reflected.
* Fixed a problem that deleting of the plugin causes an error.
* Fixed a problem that backslashes at post, page, comments are erased (NOT an SQL injection).
* `ks_in_network()` supports the new IP address of au, WILLCOM at .
* Renamed `ks_image_alignment` filter into `ktai_image_alignment`.

= --snip-- =

= 0.7.0 (2007-09-19) =
* Initial version.

== Upgrade Notice == 

= 2.0.5 =
Mobile themes in `ktai-style/themes/*` are initialized to the distribution state. If you customize these themes directory, create a `wp-content/ktai-themes/` directory and move your themes to there.

= 2.0.3 =
Please follow the change of footer.php at Classic/Default theme, if you customized from these themes.
