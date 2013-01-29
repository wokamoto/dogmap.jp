<?php

new WPBooster_Admin();

class WPBooster_Admin {

private $key = null;
private $get_point_by_api = 'http://api.wpbooster.net/get_point_by_api/%s';
private $get_requests_by_api = 'http://api.wpbooster.net/get_requests_by_api/%s';
private $stop_wpbooster_api = 'http://api.wpbooster.net/stop/%s';
private $transient_expire = 3600;
private $transient_key = 'wpbooster-site-data';
private $is_active = 'wpbooster-is-active';
private $feed_items = 5;
private $nonce = 'wpbooster-suspend-nonce';

function __construct()
{
    $this->key = get_option("wpboosterapikey");
    if ($this->key) {
        add_action("admin_menu", array(&$this, "admin_menu"));
    }
}

public function is_reserved()
{
    $cdn = get_transient($this->is_active);
    if (intval($cdn->reserved) === 1) {
        return true;
    } else {
        return false;
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

    if (isset($_POST['suspend-wpbooster']) && wp_verify_nonce($_POST['suspend-wpbooster'], $this->nonce)) {
        $this->suspend_wpbooster();
    }
}

public function enqueue_script()
{
    wp_enqueue_script(
        'enhance',
        WPBOOSTER_CDN_CLIENT_URL.'/js/enhance.min.js',
        array('jquery'),
        filemtime(WPBOOSTER_CDN_CLIENT_DIR.'/js/enhance.min.js'),
        true
    );
    wp_enqueue_script(
        'wpbooster-cdn-client',
        WPBOOSTER_CDN_CLIENT_URL.'/js/script.js',
        array('jquery'),
        filemtime(WPBOOSTER_CDN_CLIENT_DIR.'/js/script.js'),
        true
    );
}

public function enqueue_style()
{
    wp_enqueue_style(
        'jquery-visalize-basic',
        WPBOOSTER_CDN_CLIENT_URL.'/css/basic.css',
        array(),
        filemtime(WPBOOSTER_CDN_CLIENT_DIR.'/css/basic.css')
    );
    wp_enqueue_style(
        'wpbooster-cdn-client',
        WPBOOSTER_CDN_CLIENT_URL.'/css/style.css',
        array(),
        filemtime(WPBOOSTER_CDN_CLIENT_DIR.'/css/style.css')
    );
}

public function admin_panel()
{
    $data = $this->get_data();

    echo '<div class="wrap" id="wpbooster-cdn-client">';
    echo '<h2>'.__("WP Booster CDN Client", "wpbooster-cdn-client").'</h2>';

    echo '<p class="api-key">';
    echo "API KEY: ".$this->key;
    echo '</p>';

    if (!$this->is_reserved()) {
        echo '<p class="balance">';
        echo '<span class="number">';
        echo number_format($data['point']);
        echo '</span>';
        echo ' points.';
        echo '</p>';
    }

    if (!$this->is_reserved()) {
        $this->add_box(__('History', "wpbooster-cdn-client"), $this->get_history(), "");
    }

    $this->add_box(
        __('Information', 'wpbooster-cdn-client'),
        $this->get_feed(__('http://www.wpbooster.net/feed', 'wpbooster-cdn-client')),
        "half align-left"
    );

    $this->add_box(
        '',
        $this->get_point_box(),
        "half align-right"
    );

    echo '</div><!-- end #wpbooster-cdn-client -->';

    $footer =<<<EOL
        <div class="wpbooster-footer">
            <div class="cell">
            <a href="%3\$s" target="_blank"><img src="%1\$s/img/wp_booster.gif" width="200" height="84" alt="WP Booster" title="WP Booster"/></a>
            </div>
            <div class="cell">
            <a href="%4\$s" target="_blank"><img src="%1\$s/img/megumicloud_amimoto.gif" width="200" height="84" alt="megumi cloud" title="megumi cloud"/></a>
            </div>
            <div class="cell">
            <a href="%5\$s" target="_blank"><img src="%1\$s/img/wp_remote_manager.gif" width="200" height="84" alt="WP remote" title="WP remote"/></a>
            </div>
            <div class="cell">
            <a href="%2\$s" target="_blank"><img src="%1\$s/img/wordpress_consultant_1.gif" width="200" height="84" alt="WordPress CONSULTANT" title="WordPress CONSULTANT"/></a>
            </div>
            <div class="cell">
            <a href="https://aws.amazon.com/solution-providers/si/digitalcube-co-ltd" target="_blank"><img src="%1\$s/img/aws.png" width="193" height="84" alt="WP remote" title="WP remote"/></a>
            </div>

<div class="widget-container">
<div class="fb-like-box" data-href="http://www.facebook.com/WPBooster" data-width="1000" data-show-faces="true" data-stream="true" data-header="true"></div>
</div>
        </div>

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
EOL;

    printf(
        $footer,
        WPBOOSTER_CDN_CLIENT_URL,
        __('http://en.digitalcube.jp/about/wordpress_consultant/', 'wpbooster-cdn-client'),
        __('http://www.wpbooster.net/', 'wpbooster-cdn-client'),
        __('http://megumi-cloud.com/', 'wpbooster-cdn-client'),
        __('http://wp.remotemanager.me/', 'wpbooster-cdn-client')
    );


    echo '<script type="text/javascript">';
    $categories = array();
    $transfers = array();
    $datas = array_reverse($data['data']);
    foreach ($datas as $stat) {
        $date = preg_replace("/^[0-9]+\-/", '', $stat->date);
        $date = preg_replace("/\-/", '/', $date);
        $categories[] = $date;
        $transfers[] = intval($stat->bytes/1024/1024);
        $requests[] = intval($stat->request);
        $used[] = intval(ceil($stat->used));
    }
    echo 'var categories = '.json_encode($categories).";\n";
    echo 'var transfers = '.json_encode($transfers).";\n";
    echo 'var requests = '.json_encode($requests).";\n";
    echo 'var used = '.json_encode($used).";\n";
    echo '</script>';
}

private function get_point_box()
{
    $html = '';
    $html .= '<p>';
    $html .= __('Megumi payment” is a service to pay for WordPress-related services provided by <a href="http://www.digitalcube.jp/">DigitalCube Co. Ltd</a>.', 'wpbooster-cdn-client');
    $html .= '</p>';
    $html .= '<p style="margin: 20px 0 20px 0;">';
    $html .= __('<a class="btn blue" href="https://payment.digitalcube.jp/auth/login?language=en">Get the point!</a>', 'wpbooster-cdn-client');
    $html .= '</p>';

    if (get_transient($this->is_active)) {
        $html .= '<hr />';

        $html .= __(
            "WP Booster is running...<br />",
            'wpbooster-cdn-client'
        );
        $html .= __(
            "If you want to stop WP Booster temporarily, please click.",
            'wpbooster-cdn-client'
        );
        $html .= '<p style="margin: 20px 0 20px 0;">';
        $html .= sprintf(
            '<form action="%s" method="post">',
            $_SERVER['REQUEST_URI']
        );
        $html .= sprintf(
            '<input type="hidden" name="suspend-wpbooster" value="%s" />',
            wp_create_nonce($this->nonce)
        );
        if (get_option('wpbooster-suspended', 0)) {
            $html .= __(
                '<button class="btn green">Restart WP Booster !</button>',
                'wpbooster-cdn-client'
            );
        } else {
            $html .= __(
                '<button class="btn red">Suspend WP Booster !</button>',
                'wpbooster-cdn-client'
            );
        }
        $html .= "</form>";
        $html .= '</p>';
    }

    return $html;
}

private function get_feed($url)
{
    $feed = fetch_feed($url);
    if (!is_wp_error($feed)) {
        $maxitems = $feed->get_item_quantity($this->feed_items);
        $items = $feed->get_items(0, $maxitems);
    }

    $html = '<dl id="wpbooster-info">';
    foreach ($items as $item) {
        $html .= sprintf("<dt>%s</dt>", esc_html($item->get_date('Y/m/d H:i:s')));
        $html .= sprintf(
            '<dd><a href="%s">%s</a></dd>',
            esc_attr($item->get_permalink()),
            esc_html($item->get_title())
        );
    }
    $html .= '</dl>';
    return $html;
}

private function get_history()
{
    $html = '<div id="booster-chart"></div>';
    $html .= '<table id="booster-table">';
    $html .= '<tr id="cats"><th>'.__('Date', 'wpbooster-cdn-client').'</th></tr>';
    $html .= '<tr id="points"><th>'.__('Used Points', 'wpbooster-cdn-client').'</th></tr>';
    $html .= '<tr id="tra"><th>'.__('Transfers (MB)', 'wpbooster-cdn-client').'</th></tr>';
    $html .= '<tr id="req"><th>'.__('Requests', 'wpbooster-cdn-client').'</th></tr>';
    $html .= '</table>';
    return $html;
}

private function add_box($title, $content, $style = null)
{
    echo sprintf('<div class="postbox %s">', $style);
    if ($title) {
        echo '<h3 class="hndle" style="padding:10px;"><span>'.esc_html($title).'</span></h3>';
    }
    echo '<div class="inside">';
    echo $content;
    echo '</div><!-- end .inside -->';
    echo '</div><!-- end .postbox -->';
}

private function suspend_wpbooster()
{
    if (get_option('wpbooster-suspended', 0)) {
        delete_option('wpbooster-suspended'); // activate
    } else {
        update_option('wpbooster-suspended', 1); // deactivate
    }
    wp_redirect(admin_url('admin.php?page=wpbooster-cdn-client'));
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
