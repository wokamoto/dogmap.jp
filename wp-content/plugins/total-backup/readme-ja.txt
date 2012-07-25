=== Total Backup ===
Contributors: wokamoto, megumithemes
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: Backup, Ajax
Requires at least: 2.9
Tested up to: 3.3.1
Stable tag: 0.3.5.2

稼働中の WordPress サイトの DB とソース一式をバックアップして一つの zip ファイルを作成するプラグインです。

== Description ==

稼働中の WordPress サイトの DB とソース一式をバックアップして一つの zip ファイルを作成するプラグインです。

「Site Backup」ボタンをクリックするとバックアップを開始します。
バックアップ中は、サイトがメンテナンスモードに移行します。
問題なければ、下の「Backup Files」欄にリストアップされます。
ちょっと処理が重いので、ファイル数が多かったりするとタイムアウトでエラーになってしまうかもしれません。


**PHP5 Required.**

Special thx
 [DigitalCube Co. Ltd.](http://www.digitalcube.jp/ "DigitalCube Co. Ltd.")
 [takenori matsuura](https://twitter.com/tmatsuur "@tmatsuur")

= Localization =
"Total Backup" has been translated into languages. Our thanks and appreciation must go to the following for their contributions:

* Japanese (ja) - [OKAMOTO Wataru](http://dogmap.jp/ "dogmap.jp") (plugin author)

If you have translated into your language, please let me know.

== Installation ==

1. Upload the entire `total-backup` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

= Usage =

「Option」画面では、現在は以下の二つの項目が設定できます。

* Archive Path: バックアップファイルを保存しておくディレクトリ
* Excluded Dir: バックアップ対象外のディレクトリ

「Archive Path」は web 経由でアクセスできないディレクトリ(ただし、Web サーバが書き込み権限を持っているディレクトリ)を指定してください。
デフォルトでは sys_get_temp_dir() で取得される一時ファイル用のディレクトリがセットされています。

「Excluded Dir」は、バックアップ対象外にしたいディレクトリを指定してください。
デフォルトでは、以下の4ディレクトリが指定されています。

* wp-content/cache/ : WP Super Cache とかが使うキャッシュファイル置き場
* wp-content/tmp/ : DB Cache Reloaded Fix とかが使うキャッシュファイル置き場
* wp-content/upgrade/ : コアやテーマ・プラグインをアップデートする際に WordPress が使う一時ファイル置き場
* wp-content/uploads/ : 画像ファイル等がアップロードされているディレクトリ

「Total Backup」画面では、「Site Backup」ボタンをクリックするとバックアップを開始します。
バックアップ中は、サイトがメンテナンスモードに移行します。
問題なければ、ボタンの脇に success.png が表示され、下の「Backup Files」欄にリストアップされます。
問題が有った場合は、ボタンの脇に failure.png が表示されます。
ちょっと処理が重いので、ファイル数が多かったりするとタイムアウトでエラーになってしまうかもしれません。
そんな時は Web サーバや PHP のタイムアウト設定を見直してみてください。

バックアップファイルは「Backup Files」欄のリンクからダウンロードできます。
また、古いバックアップファイルを消したいときは、チェックボックスにチェックを入れて「Delete」ボタンをクリックしてください。

DB のバックアップファイルは、zip ファイルの中に {WordPressインストールディレクトリ名}.yyyymmdd.xxx.sql というファイル(例: wordpress.20111003.330.sql) として含まれます。
phpMyAdmin や mysql コマンド等で復元してください。

== Frequently Asked Questions ==

none

== Changelog == 

**0.3.1 - Oct. 12, 2011**  
Minor bug fix.

**0.3.0 - Oct. 11, 2011**  
Initial release.
