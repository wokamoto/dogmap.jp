<?php
/*
Plugin Name: disable functions.
Plugin URI:
Description:
Version: 0.1
Author: DigitalCube
Author URI:
License: GPLv2 or later
*/

//アップグレード通知の無効化
//Thanks to http://www.warna.info/archives/781/
remove_action( 'wp_version_check', 'wp_version_check' );
remove_action( 'admin_init', '_maybe_update_core' );
add_filter( 'pre_site_transient_update_core', '__return_zero' );

//管理画面からのテーマファイル、プラグインファイルの編集停止
if ( ! defined( 'DISALLOW_FILE_EDIT' ) )
  define( 'DISALLOW_FILE_EDIT', true );

//管理画面からの一切のアップグレード(自動アップグレードを含む)を禁止
if ( ! defined( 'DISALLOW_FILE_MODS' ) )
  define( 'DISALLOW_FILE_MODS', true );

//リビジョンを10個に制限
if ( !defined('WP_POST_REVISIONS') )
  define( 'WP_POST_REVISIONS', 10 );

//必須プラグインの有効化
// http://dogmap.jp/2012/08/25/must-use-plugins/
new just_do_it();
class just_do_it {
  private $must_plugins = array(
    'WP Multibyte Patch' => 'wp-multibyte-patch/wp-multibyte-patch.php',
  );
 
  function __construct() {
    add_action('shutdown', array($this, 'plugins_loaded'));
    if (defined('IS_AMIMOTO') && IS_AMIMOTO)
      $must_plugins['Nginx Cache Controller'] = 'nginx-champuru/nginx-champuru.php';
  }
 
  public function plugins_loaded() {
    $activePlugins = get_settings('active_plugins');
    foreach ($this->must_plugins as $key => $plugin) {
      if ( !array_search($plugin, $activePlugins) ) {
        activate_plugin( $plugin, '', $this->is_multisite() );
      }
    }
  }

  private function is_multisite() {
    return function_exists('is_multisite') && is_multisite();
  }
}
