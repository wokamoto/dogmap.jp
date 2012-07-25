=== Simple Tweet ===
Contributors: wokamoto
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=9S8AJCY7XB8F4&lc=JP&item_name=WordPress%20Plugins&item_number=wp%2dplugins&currency_code=JPY&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: twitter, posts
Requires at least: 2.8
Tested up to: 3.1.2
Stable tag: 1.3.8.2

WordPress に新規投稿した際、自動的に Twitter に通知するプラグインです。

== Description ==

WordPress に新規投稿した際、自動的に Twitter に通知するプラグインです。

投稿ユーザごとに、Twitter アカウント・Twitter に通知する文章などを変更することができます。
また、プラグイン **[Short link maker](http://wordpress.org/extend/plugins/short-link-maker/ "WordPress › Short link maker « WordPress Plugins")** を導入すると、自ドメインを利用した短縮URLを Twitter に投稿することができます。

**PHP5 以降のみ対応です。**

= Localization =

* English (en) - [odyssey](http://www.odysseygate.com/ "Odysseygate.com")
* Japanese (ja) - [wokamoto](http://dogmap.jp/ "wokamoto") (plugin author)
* Belorussian (be_BY) - [Patricia](http://pc.de/ "PC.DE")
* Dutch (nl_NL) - [Rene](http://wpwebshop.com/blog "WPWebshop Blog")
* German (de_DE) - [Rian Klijn](http://www.creditriskmanager.com/ "Credit Risk Manager")

== Installation ==

1. `/wp-content/plugins/` ディレクトリに `simple-tweet` ディレクトリを作成し、その中にプラグインファイルを格納してください。
2. WordPress の "プラグイン" メニューから "Simple Tweet" を有効化してください。
3. ダッシュボードの「設定」-「Simple Tweet」を選択して、ユーザ名・パスワードなど設定してください。
4. 投稿ユーザごとに設定を変更したい場合は、ダッシュボードの「プロフィール」-「あなたのプロフィール」から設定できます。
5. [Short link maker](http://wordpress.org/extend/plugins/short-link-maker/ "Short link maker")プラグインを導入すると、自ドメインを利用した短縮URLを Twitter に投稿することができます。
6. [WordPress.com Stats](http://wordpress.org/extend/plugins/stats/ "WordPress.com Stats") プラグインを導入すると、wp.me ドメインを利用した短縮URLを Twitter に投稿することができます。

= OAuth 設定方法 =
Simple Tweet の Ver.1.3.0 以降は Twitter の OAuth 認証に対応しました。

**Twitter へのアプリケーション登録**

まず、Twitter にアプリケーションを登録し、「**Consumer Key**」と「**Consumer Secret**」を取得する必要があります。

1. Twitterにログインしている状態で、Simple Tweet の設定画面にある「<a href="http://twitter.com/oauth_clients" title="Applications Using Twitter">Twitter にアプリケーションを登録する</a>」というリンクをクリックしてください。
2. Twitter のアプリケーション登録画面が表示されるので、「<a href="http://twitter.com/apps/new" title="Register an Application">Register an Application</a>」というリンクをクリックしてください。
3. Twitter に登録するアプリケーションの情報を入力します。
   登録に必要な項目は以下の通りです。
   * Application Icon  ... アプリケーションのアイコン。設定しなくても大丈夫です
   * Application Name ... アプリケーション名称
   * Description ... アプリケーションについての説明
   * Application Website ... アプリケーションのサイト
   * Organization ... 作ってる組織。自分の名前（ニックネーム）でいいでしょう
   * Website ... 作ってる組織のウェブサイト
   * Application Type ... アプリケーションの種類
     ※これは、「**Client**」を選択してください。
   * Default Access type ... アプリケーションに「読み書き」を許可するか「読込のみ」を許可するか
     ※これは、「**Read &amp; Write**」を選択してください。
   * Use Twitter for login ... twitterのidを認証に使用する場合チェック。未チェックで良いです
4. アプリケーションの登録に成功すると、アプリケーション情報が表示されます。
   ここで表示される「Consumer Key」と「Consumer Secret」が重要です。
5. Simple Tweet の設定画面に戻り、「Consumer Key」と「Consumer Secret」を入力して設定を更新してください。

**OAuth でアカウントを承認する**

次に OAuth でアカウントを承認します。

1. 「Consumer Key」と「Consumer Secret」が入力されている場合、Simple Tweet の設定画面または各ユーザーのプロフィール画面に「アカウントを承認するためにリンクをクリックして Twitter に移動します。」というリンクが表示されているはずです。
   このリンクをクリックしてください。
2. 先ほど登録したアプリケーションのアクセスを許可するか聞かれますので、「許可する」のボタンをクリックしてください。
   この画面が表示されない場合は、Twitter側でアプリケーションの登録がまだ済んでいないか、入力した「Consumer Key」「Consumer Secret」が誤っている可能性があります。
   入力した「Consumer Key」「Consumer Secret」が誤っていない場合は、しばらくしてから再度「アカウントを承認するためにリンクをクリックして Twitter に移動します。」というリンクをクリックしてみてください。
3. 暗証番号(PIN)として、何桁かの数字が表示されます。
4. Simple Tweet の設定画面に戻り、「PIN」を入力して設定を更新してください。

OAuth の設定を削除したい場合は、「Oauth Reset」のチェックボックスにチェックして設定を更新してください。


== Screenshots == 

1. Simple Tweet Settings
2. User Profile


== Licence ==

[GPL license](http://www.gnu.org/copyleft/gpl.html).


== Changelog == 

**1.3.3 - 2010年5月1日**  
細かな不具合の修正

**1.3.2 - 2010年4月30日**  
短縮URLサービス j.mp と is.gd に対応

**1.3.1 - 2010年2月15日**  
細かな不具合の修正

**1.3.0 - 2010年2月10日**  
Oauth 対応

**1.2.1 - 2010年1月20日**  
「予約投稿」時に動作しなかった不具合を修正

**1.2.0 - 2009年11月5日**  
初期リリース
