<?php

if (!class_exists('WP_AdminAjax')):
class WP_AdminAjax {

private $nonce;

function __construct($action, $callback, $nopriv = false, $nonce = false)
{
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $action)) {
        $this->action = $action;
    } else {
        wp_die("Invalid strings for \$action.");
    }

    $this->callback = $callback;

    if ($nonce) {
        $this->nonce = $nonce;
    } else {
        $this->nonce = $action;
    }

    add_action('wp_ajax_'.$action, array(&$this, 'wp_ajax'));
    if ($nopriv) {
        add_action('wp_ajax_nopriv_'.$action, array(&$this, 'wp_ajax'));
    }
}

public function get_url($query = array())
{
    $query['action'] = $this->action;
    $query['nonce']  = $this->get_nonce();

    $url = admin_url("admin-ajax.php");
    foreach ($query as $key => $value) {
        $url = add_query_arg($key, $value, $url);
    }

    return $url;
}

public function get_nonce()
{
    return wp_create_nonce($this->nonce);
}

public function wp_ajax()
{
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    if (!wp_verify_nonce($_GET["nonce"], $this->nonce)) {
        $res = call_user_func($this->callback);
        if (is_array($res)) {
            echo json_encode($res);
        } else {
            echo $res;
        }
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo '{}';
    }
    exit;
}

} // end class
endif;

// EOL