<?php
/*
Plugin Name: WP Ranking
Author: Takayuki Miyauchi
Plugin URI: http://firegoby.jp/wp/wp_ranking
Description: WP Ranking Multisite Compatible.
Version: 0.5.1
Author URI: http://firegoby.jp/
Domain Path: /languages
Text Domain: wp_ranking
*/

require_once(dirname(__FILE__).'/includes/admin.class.php');
require_once(dirname(__FILE__).'/includes/shortcode.class.php');
require_once(dirname(__FILE__).'/includes/class-addrewriterules.php');

$wpranking = new WPRanking();

register_activation_hook(__FILE__, 'flush_rewrite_rules');

class WPRanking {

private $count_timer  = 4000; // msec
private $counter;
private $table   = 'daily_ranking';
private $nonce   = 'wp_ranking';
private $action  = 'wp_ranking_counter';
private $loader  = 'wp_ranking_loader';
private $cookie  = 'wp_ranking';
private $query_set = array();

function __construct()
{
    global $wpdb;
    $this->table = mysql_real_escape_string($wpdb->base_prefix.$this->table);
    register_activation_hook(__FILE__, array(&$this, 'activation'));
    add_action('wp_ajax_nopriv_'.$this->action, array(&$this, 'counter'));
    add_action('wp_ajax_nopriv_'.$this->loader, array(&$this, 'loader'));
    add_action('wp_head', array(&$this, 'wp_head'));
    add_action('wp_footer', array(&$this, 'wp_footer'));
    add_shortcode('wp_ranking' , array(&$this, 'shortcode'));

    new WP_AddRewriteRules(
        'wp-ranking$',
        'wp-ranking',
        array(&$this, "loader")
    );
    new WP_AddRewriteRules(
        'wp-ranking-counter$',
        'wp-ranking-counter',
        array(&$this, "counter")
    );
}

public function get_action()
{
	return $this->action;
}

public function wp_head()
{
    if (!is_user_logged_in()) {
        wp_enqueue_script("jquery");
    }
}

public function wp_footer()
{
    if (is_user_logged_in() || !is_singular()) {
        return;
    }
    global $blog_id;
    $src = sprintf(
        home_url('wp-ranking/?blog_id=%d&post_id=%s&post_type=%s'),
        $blog_id,
        get_the_ID(),
        get_post_type(get_the_ID())
    );
    printf(
        '<script type="text/javascript" src="%s"></script>',
        $src
    );
}

public function counter()
{
    nocache_headers();

    header('Content-Type: application/json; charset=utf-8');
    if (wp_verify_nonce($_GET['nonce'], $this->nonce) && !is_user_logged_in()) {
        if (isset($_GET['blog_id']) && intval($_GET['blog_id']) &&
                    isset($_GET['post_id']) && intval($_GET['post_id']) &&
                        isset($_GET['post_id']) && $_GET['post_type'] == 'post' ) {
            if (isset($_COOKIE[$this->cookie]) && $_COOKIE[$this->cookie]) {
                global $wpdb;
                $sql = "INSERT INTO `{$this->table}` VALUES(%s, %d, %d, %d)";
                $sql .= " ON DUPLICATE KEY UPDATE `counter`=`counter`+1";
                $sql = $wpdb->prepare(
                    $sql,
                    date_i18n('Y-m-d'),
                    $_GET['blog_id'],
                    $_GET['post_id'],
                    1
                );
                $wpdb->query($sql);
                echo json_encode(array('status' => true));
                exit;
            }
        }
    }
    header('HTTP', true, 403);
    echo json_encode(array('status' => false));
    exit;
}

public function loader()
{
    if (!is_user_logged_in()) {
        header('Content-type: text/javascript');
        $res = setcookie(
            $this->cookie,
            md5(uniqid(rand(),1)),
            time()+60*60*24,
            '/'
        );

        $src = home_url('wp-ranking-counter/'); // ?
        $src = add_query_arg('blog_id', intval($_GET['blog_id']), $src);
        $src = add_query_arg('post_id', intval($_GET['post_id']), $src);
        $src = add_query_arg('post_type', trim($_GET['post_type']), $src);
        $src = add_query_arg('nonce', wp_create_nonce($this->nonce), $src);
        echo sprintf(
            file_get_contents(dirname(__FILE__).'/js/loader.js'),
            $src,
            apply_filters(
                'wp_ranking_count_timer',
                $this->count_timer
            )
        );
    }
    exit;
}

public function activation()
{
    global $wpdb;
    if ($wpdb->get_var("show tables like '$this->table'") != $this->table) {
        $sql = "DROP TABLE IF EXISTS `{$wpdb->base_prefix}ranking_table`";
        $wpdb->query($sql);
        $sql = "CREATE TABLE `{$this->table}` (
            `date` date not null,
            `blog_id` bigint(20) unsigned not null,
            `post_id` bigint(20) unsigned not null,
            `counter` bigint(20) not null,
            primary key (`date`, `blog_id`, `post_id`)
            );";
        require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}


public function get_ranking_data($query_set, $rows = 5)
{
    global $wpdb;
    global $blog_id;

    $q = $this->get_query_set();
    if (isset($q[$query_set]) && $q[$query_set]) {
        $query = $q[$query_set];
    } else {
        return new WP_Error(__LINE__, 'Unknown query set');
    }

    $sql = "select post_id,sum(`counter`) as `count` from `{$this->table}`";
    $sql .= " where `blog_id`=%d and `date` between %s and %s";
    $sql .= " group by `post_id`";
    $sql .= " order by sum(`counter`) desc";
    $sql .= " limit 0,%d";
    $sql = $wpdb->prepare(
        $sql,
        $blog_id,
        $query['start'],
        $query['end'],
        $rows
    );
    return $wpdb->get_results($sql, ARRAY_A);
}

public function get_query_set()
{
    $this->query_set = array(
        'yesterday' => array(
            'title' => __('Yesterday', 'wp_ranking'),
            'start' => date_i18n('Y-m-d', strtotime('last day')),
            'end' => date_i18n('Y-m-d', strtotime('last day'))
        ),
        '7days' => array(
            'title' => __('Last 7 days', 'wp_ranking'),
            'start' => date_i18n('Y-m-d', time()-60*60*24*7),
            'end' => date_i18n('Y-m-d', time())
        ),
        '30days' => array(
            'title' => __('Last 30 days', 'wp_ranking'),
            'start' => date_i18n('Y-m-d', time()-60*60*24*30),
            'end' => date_i18n('Y-m-d', time())
        ),
    );

    return apply_filters('wp_ranking_query_set', $this->query_set);
}

} // end WPRanking()


// EOF
