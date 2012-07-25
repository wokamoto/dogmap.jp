<?php
/*
Plugin Name: Facebook Linter
Author: Takayuki Miyauchi
Description: update cache on facebook of your post
*/

new FBLinter();

class FBLinter {

private $api = "http://developers.facebook.com/tools/lint/?url=%s";
private $message_flag = "fblinter_warn";

function __construct()
{
    add_action("save_post", array(&$this, "fb_linter"));
    if (is_admin() && get_transient($this->message_flag)) {
        add_action("admin_notices", array(&$this, "admin_notice"));
    }
}

public function fb_linter($id)
{
    $post = get_post($id);
    if ($post->post_status !== 'publish') {
        return;
    }
    $url = sprintf($this->api, rawurlencode(get_permalink($id)));
    $res = wp_remote_get($url.'&format=json');
    if (!is_wp_error($res) && $res["response"]["code"] === 200) {
        delete_transient($this->message_flag);
    } else {
        set_transient($this->message_flag, 1, 10);
    }
}

public function admin_notice()
{
    global $post;
    echo "<div class=\"error\"><p>";
    echo "Facebook Linter Error: ";
    $msg = "Please <a href=\"%s\" target=\"_blank\">click</a> to reload.";
    $url = sprintf($this->api, rawurlencode(get_permalink($post->ID)));
    printf($msg, $url);
    echo "</p></div>";
}

}

?>
