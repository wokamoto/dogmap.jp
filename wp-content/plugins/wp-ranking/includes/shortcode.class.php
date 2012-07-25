<?php

new WPRankingShortcode();

class WPRankingShortcode {

private $cache_expire = 3600; // seconds

function __construct()
{
    add_shortcode('wp_ranking' , array(&$this, 'shortcode'));
}


public function shortcode($p)
{
    global $wpranking;
    $query_set = $wpranking->get_query_set();
    if (!isset($p['period']) || !isset($query_set[$p['period']])) {
        $p['period'] = apply_filters('wp_ranking_default_period', '7days');
    }
    if (!isset($p['rows']) || !intval($p['rows'])) {
        $p['rows'] = apply_filters('wp_ranking_default_rows', 5);
    }
    return $this->get_ranking($p['period'], $p['rows']);
}

public function get_ranking($query_set, $rows = 5)
{
    global $wpranking;
    $key = sprintf('wp_ranking_%s_%d', $query_set, $rows);
    if (!is_user_logged_in() && ($html = get_transient($key))) {
        return $html;
    } else {
        $posts = $wpranking->get_ranking_data($query_set, $rows);
        $list = array();
        $html = '<li class="post-%1$d"><span class="title"><a href="%2$s" title="%3$s">%3$s</a></span>%4$s</span></li>';
        foreach ($posts as $p) {
/*
			if ( has_post_thumbnail($p['post_id']) ){
				 $thumbnail = get_the_post_thumbnail($p['post_id'], 'ranking-thumbnail');
			} else {
				 $thumbnail = '<img src="' . get_template_directory_uri() . '/images/others/noimage.gif" width="45" height="45" alt="" />';
			}
            $thumbnail = sprintf('<span class="thumb"><a href="%s">%s</a>', get_permalink($p['post_id']), $thumbnai);
*/
            $thumbnail = '';
            $list[] = sprintf(
                $html,
                $p['post_id'],
                get_permalink($p['post_id']),
                get_the_title($p['post_id']),
	        $thumbnail
            );
        }
        $html = sprintf(
            '<ol class="wp_ranking %s">%s</ol>',
            'wp_ranking_'.esc_attr($query_set),
            join('', $list)
        );
        delete_transient($key);
        set_transient(
            $key,
            $html,
            intval(apply_filters('wp_ranking_cache_expire', $this->cache_expire))
        );
        return $html;
    }
}

} // end WPRankingShortCode()


// EOF
