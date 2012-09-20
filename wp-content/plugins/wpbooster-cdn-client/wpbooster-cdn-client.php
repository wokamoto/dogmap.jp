<?php
/*
Plugin Name: The WP Booster CDN Client
Author: Digitalcube Co,.Ltd (Takayuki Miyauchi)
Description: Deliver static files from WPBooster CDN.
Version: 2.2.0
Author URI: http://wpbooster.net/
Domain Path: /languages
Text Domain: wpbooster-cdn-client
*/

/*
Copyright (c) 2012 Takayuki Miyauchi (DigitalCube Co,.Ltd).

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/


define("WPBOOSTER_CDN_CLIENT_URL", plugins_url("", __FILE__));
define("WPBOOSTER_CDN_CLIENT_DIR", dirname(__FILE__));

register_deactivation_hook(__FILE__, 'wp_booster_cdn_deactive');
function wp_booster_cdn_deactive(){
    delete_option("wpboosterapikey");
    delete_transient("wpbooster-is-active");
    delete_transient("wpbooster-site-data");
}

new WPBoosterCDN();

class WPBoosterCDN {

private $cdn = 'cdn.wpbooster.net';
private $api = 'http://api.wpbooster.net/check_host/%s';
private $key = 'wpboosterapikey';
private $is_active = 'wpbooster-is-active';
private $exp = 3600;

function __construct()
{
    if (is_admin()) {
        require_once(dirname(__FILE__).'/includes/admin.php');
    }

    register_activation_hook(__FILE__, array(&$this, "is_active_host"));
    add_action("plugins_loaded", array(&$this, "plugins_loaded"));
    add_action('admin_init', array(&$this, 'admin_init'));
    add_action("admin_bar_menu", array(&$this, "admin_bar_menu"), 9999);
    add_action("admin_head", array(&$this, "admin_head"));
    add_action("wp_head", array(&$this, "admin_head"));
}

public function admin_head()
{
    if (is_user_logged_in()) {
        $image = plugins_url("img/icon.png", __FILE__);
        echo <<<EOL
<style>
#wp-admin-bar-wp-booster-logo .ab-icon
{
    background-image: url($image) !important;
    background-repeat: no-repeat !important;
    background-position: center center !important;
}
</style>
EOL;
    }
}

public function admin_init()
{
    if (isset($_POST['wpbooster-api']) && $_POST['wpbooster-api']) {
        if (preg_match("/^[a-zA-Z0-9]{32}$/", $_POST['wpbooster-api'])) {
            update_option($this->key, $_POST['wpbooster-api']);
            wp_redirect(admin_url());
        }
    }
}

public function plugins_loaded()
{
    load_plugin_textdomain(
        "wpbooster-cdn-client",
        false,
        dirname(plugin_basename(__FILE__)).'/languages'
    );

    if ($this->is_active_host()) {
        if (!is_user_logged_in()) {
            $hooks = array(
                "stylesheet_directory_uri",
                "template_directory_uri",
                "plugins_url",
                "wp_get_attachment_url",
                "theme_mod_header_image",
                "theme_mod_background_image",
            );
            foreach ($hooks as $hook) {
                add_filter(
                    $hook,
                    array(&$this, "filter")
                );
            }
            add_filter('the_content', array(&$this, 'the_content'));
        }
    }
}

public function the_content($html)
{
    $up = wp_upload_dir();
    $upload_url = $up['baseurl'];
    $filtered_url = $this->filter($upload_url);
    return str_replace($upload_url, $filtered_url, $html);
}

public function filter($uri)
{
    $cdn = get_transient($this->is_active);
    return str_replace(
        $cdn->base_url,
        'http://'.$this->cdn.'/'.$cdn->id.'/',
        $uri
    );
}

public function is_active_host()
{
    if (!$api = get_option($this->key)) {
        delete_transient($this->is_active);
        add_action('admin_notices', array(&$this, 'admin_notice'));
        return false;
    }

    if (get_transient($this->is_active)) {
        return true;
    } else {
        $res = wp_remote_get(sprintf($this->api, $api));
        if ($res['response']['code'] === 200) {
            set_transient($this->is_active, json_decode($res['body']), $this->exp);
            return true;
        } else {
            delete_transient($this->is_active);
            add_action('admin_notices', array(&$this, 'admin_notice'));
            return false;
        }
    }
}

public function admin_notice()
{
    printf(
        '<div class="error"><form method="post">%s<input size=30 type="text" value="" name="wpbooster-api"><input type="submit" value="Save"> <span>%s</span></form></div>',
        __('Please input WP Booster API Key: ', 'wpbooster-cdn-client'),
        __('<a href="http://wpbooster.net/cpanel">Sign In</a>', 'wpbooster-cdn-client')
    );
}

public function admin_bar_menu($bar)
{
    $bar->add_menu( array(
        'id'    => 'wp-booster-logo',
        'title' => '<span class="ab-icon"></span>',
        'meta'  => array(
            'title' => __('The WP Booster CDN', 'wpbooster-cdn-client'),
        ),
    ) );

    if (get_transient($this->is_active)) {
        $message = __("WP Booster CDN is running...", "wpbooster-cdn-client");
    } else {
        $message = __("WP Booster CDN is stopped.", "wpbooster-cdn-client");
    }

    $bar->add_menu(array(
        "parent" => "wp-booster-logo",
        "id"    => "wp-booster-cdn-running",
        "title" => $message,
        'href'  => __('http://wpbooster.net/cpanel/', 'wpbooster-cdn-client'),
        "meta"  => false,
    ));
}

} // MegumiCDN

// EOF
