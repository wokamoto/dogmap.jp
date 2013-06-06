=== Head Cleaner ===
Contributors: wokamoto, megumithemes, Webnist, tai
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: head optimization, javascript, css, optimization, minified, performance
Requires at least: 2.5
Tested up to: 3.3.1
Stable tag: 1.4.2.10

Head と footer をお掃除します。

== Description ==

WordPress サイトの `<head>` の中身と、フッタ領域を整形しなおします。
**PHP5 以降のみ対応です。**

= Features =

* IE6 以外の時は先頭に xml 宣言を付与。
* 重複タグや、不要なタグ、コメント、空白を削除。
* `<meta name="description" />` タグが複数ある場合、一つにまとめる
* `<meta name="keyword" />` タグが複数ある場合、一つにまとめる
* `<link rel="canonical" />` タグを追加。
* OGP (Open Graph Protocol) に対応したタグを追加。
* IE コンディショナルタグを判定して、ブラウザが IE の時だけ対象タグを表示。
* 複数ある CSS を media 属性ごとに結合して一ファイルにまとめる。
  もちろん、そのファイルには インライン CSS も含まれる。
* CSS を圧縮する。
* CSS に含まれる画像の URL を、データスキーマ URI に変換する。
* 複数ある JavaScript をすべて結合して一ファイルにまとめる。
  もちろん、そのファイルには インライン JavaScript も含まれる。
* [JSMin](http://code.google.com/p/jsmin-php/ "JSMin") で、JavaScript のソースコードを圧縮する。
* JavaScript をフッタ領域に移動することもできる。
* フッタ領域の JavaScript も同様に結合して一ファイルにまとめる。
* Prototype.js, script.aculo.us, jQuery, mootools が複数読み込まれている場合、１回だけ読み込むようにする。
* Prototype.js, script.aculo.us, jQuery, mootools の読み込み順を修正して、できるだけコンフリクトが発生しないようにする。

= Localization =
"Head Cleaner" を各国語に翻訳してくださった方々に感謝を込めて。

* Belorussian (by) - [Marcis Gasuns](http://www.comfi.com/ "Marcis Gasuns")
* Bulgarian (bg_BG) - [Web Geek](http://webhostinggeeks.com/ "Web Geek")
* Dutch (nl_NL) - [Rene](http://wpwebshop.com/blog "WPWebshop Blog")
* German (de) - Carsten
* Japanese (ja) - [OKAMOTO Wataru](http://dogmap.jp/ "dogmap.jp") (plugin author)
* Spanish (es) - [Franz Hartmann](http://tolingo.com/ "tolingo.com - Franz Hartmann")
* Russian (ru) - [ilyuha](http://antsar.info/ "ilyuha")
* Romanian (ro_RO) - [Web Geek Sciense](http://webhostinggeeks.com/ "Web Hosting Geeks")
* Turkish (tr_TR) - [Hakan Demiray](http://www.dmry.net/ "Günlük Haftalık Aylık")


== Installation ==

1. `/wp-content/plugins/` ディレクトリに `head-cleaner` ディレクトリを作成し、その中にプラグインファイルを格納してください。
　一般的には .zip から展開された head-cleaner フォルダをそのままアップロードすれば OK です。
2. `/wp-content/` ディレクトリ以下に `cache/head-cleaner` というディレクトリを作成し、さらにその中に `js`, `css` という２つのディレクトリを作成して、書き込み権限を与えてください。
3. WordPress の "プラグイン" メニューから "Head Cleaner" を有効化してください。

Head Cleaner のオプション設定は "設定 > Head Cleaner" で行えます。


**使用しているPHPライブラリ [Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net/ "Simple HTML DOM Parser") と [JSMin](http://code.google.com/p/jsmin-php/ "JSMin") の制限上、PHP5 以降のみ対応です。**

== Frequently Asked Questions ==

特定のプラグインが書き出すコードを整形対象外にしたい場合は、設定画面の「アクティブなフィルタ」から、対象外にしたいフィルタ名を選択してください。


テーマ内の特定の記述を整形対象外にしたい場合は `header.php` を修正する必要があります。
具体的には、整形対象外にしたい部分を `<?php wp_head(); ?>` より下に記述してください。


同時に使用しているプラグイン・テーマによっては正常に動作しません。
正常に動作しない場合は、このプラグイン以外のすべてのプラグインを停止した後、一つ一つ有効化して、どのプラグインと競合するか確かめてみてください。
競合するプラグインが判明した場合、作者まで連絡いただけると非常に助かります。
[http://dogmap.jp/2009/02/20/head-cleaner/](http://dogmap.jp/2009/02/20/head-cleaner/ "http://dogmap.jp/2009/02/20/head-cleaner/")


現在、以下のプラグインと同時に使用すると、正常に動作しないことが報告されています。

**[All in One SEO Pack](http://wordpress.org/extend/plugins/all-in-one-seo-pack/ "All in One SEO Pack")**
　一部環境で「タイトルの書き換え」オプションが有効になっていると、このプラグインが正常動作しないという報告がありました。
　通常は問題がありませんが、問題が発生した場合は「タイトルの書き換え」をオフにして、タイトルの書き換えは別プラグインを使用するようにしてください。
( Ver.1.3.4 で修正 )

= CSS, JavaScript のキャッシュファイルが作成されません =

以下の二つのディレクトリに CSS, JavaScript をキャッシュします。
キャッシュを有効にしたい場合は、それぞれのフォルダを作成してください。

* `wp-content/cache/head-cleaner/css/`
* `wp-content/cache/head-cleaner/js/`

= ワーニングが表示されます「get_browser(): browscap ini directive not set in ～」 =

この関数が正常に機能するためには、php.ini の browscap  設定が、システム上の browscap.ini の正確な位置を 指している必要があります。
参照：[PHP: get_browser](http://jp.php.net/manual/ja/function.get-browser.php "PHP: get_browser - Manual")

