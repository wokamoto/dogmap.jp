<?php

new WPBooster_Admin();

class WPBooster_Admin {

private $key = null;
private $get_point_by_api = 'http://api.wpbooster.net/get_point_by_api/%s';
private $get_requests_by_api = 'http://api.wpbooster.net/get_requests_by_api/%s';
private $transient_expire = 3600;
private $transient_key = 'wpbooster-site-data';

function __construct()
{
    $this->key = get_option("wpboosterapikey");
    if ($this->key) {
        add_action("admin_menu", array(&$this, "admin_menu"));
    }
}

public function admin_menu()
{
    $hook = add_menu_page(
        "WP Booster",
        "WP Booster",
        "update_core",
        "wpbooster-cdn-client",
        array(&$this, "admin_panel"),
        WPBOOSTER_CDN_CLIENT_URL.'/img/icon.png',
        234040
    );
    add_action('admin_print_styles-'.$hook, array(&$this, 'enqueue_style'));
    add_action('admin_print_scripts-'.$hook, array(&$this, 'enqueue_script'));
}

public function enqueue_script()
{
    wp_enqueue_script(
        'highchart',
        WPBOOSTER_CDN_CLIENT_URL.'/Highcharts-2.3.2/js/highcharts.js',
        array('jquery'),
        filemtime(WPBOOSTER_CDN_CLIENT_DIR.'/Highcharts-2.3.2/js/highcharts.js'),
        true
    );
    wp_enqueue_script(
        'wpbooster-cdn-client',
        WPBOOSTER_CDN_CLIENT_URL.'/script.js',
        array('highchart'),
        filemtime(WPBOOSTER_CDN_CLIENT_DIR.'/script.js'),
        true
    );
}

public function enqueue_style()
{
    wp_enqueue_style(
        'wpbooster-cdn-client',
        WPBOOSTER_CDN_CLIENT_URL.'/style.css',
        array(),
        filemtime(WPBOOSTER_CDN_CLIENT_DIR.'/style.css')
    );
}

public function admin_panel()
{
    $data = $this->get_data();

    echo '<div class="wrap" id="wpbooster-cdn-client">';
    echo '<h2>'.__("WP Booster CDN Client", "wpbooster-cdn-client").'</h2>';

    echo '<p class="balance">';
    echo '<span class="number">';
    echo number_format($data['point']);
    echo '</span>';
    echo ' points';
    echo '</p>';

    $this->add_box('History', $this->get_history(), "");
    $this->add_box('Status', "API KEY: ".$this->key, "half align-left");
    $this->add_box(
        'Get The Point',
        __('<a href="https://payment.digitalcube.jp/auth/login?language=en">Megumi Payment</a>', 'wpbooster-cdn-client'),
        "half align-right"
    );
    //$this->add_box('Information', "aaaaaaaaa", "half align-left");
    //$this->add_box('Speed Test', "aaaaaaaaa", "half align-right");

    echo '</div><!-- end #wpbooster-cdn-client -->';

    echo '<script type="text/javascript">';
    $categories = array();
    $transfers = array();
    $datas = array_reverse($data['data']);
    foreach ($datas as $stat) {
        $date = preg_replace("/^[0-9]+\-[0-9]+\-/", '', $stat->date);
        $categories[] = $date;
        $transfers[] = intval($stat->bytes);
    }
    echo 'var categories = '.json_encode($categories).";\n";
    echo 'var transfers = '.json_encode($transfers).";\n";
    echo '</script>';
}

private function get_history()
{
    $html = '<div id="booster-chart">';
    $html .= '</div>';
    return $html;
}

private function add_box($title, $content, $style = null)
{
    echo sprintf('<div class="postbox %s">', $style);
    echo '<h3 class="hndle" style="padding:10px;"><span>'.esc_html($title).'</span></h3>';
    echo '<div class="inside">';
    echo $content;
    echo '</div><!-- end .inside -->';
    echo '</div><!-- end .postbox -->';
}

private function get_data()
{
    if ($data = get_transient($this->transient_key)) {
        return $data;
    }

    $get_point = sprintf($this->get_point_by_api, $this->key);
    $get_request = sprintf($this->get_requests_by_api, $this->key);

    $point = $this->remote_get($get_point);
    $request = $this->remote_get($get_request);
    if ($point && $request) {
        $data = array('point' => $point->point, 'data' => $request);
        set_transient(
            $this->transient_key,
            $data,
            3600
        );
        return $data;
    }

    return false;
}

private function remote_get($url)
{
    $res = wp_remote_get($url);
    if ($res['response']['code'] === 200) {
        return json_decode($res['body']);
    }
    return false;
}

} // end of class


// EOF
