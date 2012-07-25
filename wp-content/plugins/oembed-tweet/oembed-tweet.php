<?php
/*
Plugin Name: oEmbed Tweet
Plugin URI: http://firegoby.jp/wp/oembedtweet
Description: Embed tweet from twitter easily.
Author: Takayuki Miyauchi
Version: 1.2.0
Author URI: http://firegoby.theta.ne.jp/
*/

/*
Copyright (c) 2010 Takayuki Miyauchi (THETA NETWORKS Co,.Ltd).

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

require_once(dirname(__FILE__).'/includes/tinyTemplate.php');

new oEmebedTweet();

class oEmebedTweet {

private $api = "http://api.twitter.com/1/statuses/show/%s.json";
private $meta_id = '_oEmbedTweet-';

function __construct()
{
    wp_embed_register_handler(
        'oEmbedTweet',
        '#https?://twitter.com/.*/status/([\d]+)$#i',
        array(&$this, 'handler')
    );
    add_action(
        'wp_head',
        array(&$this, 'loadCSS')
    );
    add_shortcode('oEmbedTweet', array(&$this, 'shortCode'));
		add_action('save_post', array(&$this, 'delete_oembed_caches'));
}

public function handler($matches, $attr, $url, $rawattr)
{
    return "[oEmbedTweet {$matches[1]}]";
}

public function shortCode($p)
{
    global $post;
    global $wpdb;
    if (!preg_match("/^[0-9]+$/", $p[0])) {
        return;
    }
    $id = $p[0];
    $opkey = $this->meta_id.$id;
    if ($json = get_post_meta($post->ID, $opkey, true)) {
        // since 0.2
    } elseif ($json = get_option($opkey)) {
        // for ver 0.1
        delete_option($opkey);
        add_post_meta($post->ID, $opkey, $wpdb->escape($json));
    } elseif ($json = file_get_contents(sprintf($this->api, $id))) {
        add_post_meta($post->ID, $opkey, $wpdb->escape($json));
    } else {
        return "Sorry! Please try later!";
    }

    $embed = $this->getTweet($id, $json);
    return apply_filters('oEmbedTweet', $embed);
}

private function getTweet($id, $json)
{
    $tweet = json_decode($json);
    $url = sprintf('http://twitter.com/#!/%s/status/%s',
        $tweet->user->screen_name,
        $tweet->id_str
    );
    $link = "<a href=\"%s\">%s</a>";

    $created_at = sprintf($link, $url, $this->parseDate($tweet->created_at));
    $profile_image = sprintf("<img src=\"%s\" alt=\"%s\" />",
        $tweet->user->profile_image_url,
        $tweet->user->screen_name
    );
    $profile_image = sprintf($link,
        'http://twitter.com/#!/'.$tweet->user->screen_name,
        $profile_image
    );
    $name = sprintf($link,
        'http://twitter.com/#!/'.$tweet->user->screen_name,
        $tweet->user->name
    );

    $tpl = new TinyTemplate();
    $tpl->set('text', $this->parseText($tweet->text), false);
    $tpl->set('screen_name', $tweet->user->screen_name, false);
    $tpl->set('profile_url', $tweet->user->url, false);
    $tpl->set('profile_description', $tweet->user->description, false);
    $tpl->set('created_at', $created_at, false);
    $tpl->set('source', $tweet->source, false);
    $tpl->set('profile_image', $profile_image, false);
    $tpl->set('name', $name, false);
    $tpl->set(
        'profile_background_image_url',
        $tweet->user->profile_background_image_url, false
    );

    $template = get_stylesheet_directory().'/oembed_tweet.tpl';
    if (is_file($template)) {
        return $tpl->fetch($template);
    } else {
        return $tpl->parse($this->get_template());
    }
}

private function parseText($text)
{
    // links
    $text = preg_replace(
        '@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@',
        '<a href="$1">$1</a>',
        $text);
    // users
    $text = preg_replace(
        '/@(\w+)/',
        '<a href="http://twitter.com/$1">@$1</a>',
        $text);
    // hashtags
    $text = preg_replace(
        '/\s+#(\w+)/',
        '<a href="http://search.twitter.com/search?q=%23$1">#$1</a>',
        $text);
    return $text;
}

private function parseDate($date)
{
    $format = "%a %b %d %H:%M:%S +0000 %Y";
    $d = strptime($date, $format);
    $time = mktime($d['tm_hour'], $d['tm_min'],
        $d['tm_sec'], $d['tm_mon']+1, $d['tm_mday'], $d['tm_year']+1900);
    $time = $time + 3600 * get_option('gmt_offset');
    return date('Y/m/d H:i:s', $time);
}

public function loadCSS()
{
    $style = get_stylesheet_directory().'/oembed_tweet.css';
    if (is_file($style)) {
        $style = get_stylesheet_directory_uri().'/oembed_tweet.css';
    } else {
        $style = plugins_url("", __FILE__).'/style.css';
    }
    echo "<!--oEmbed Tweet-->";
    echo '<link rel="stylesheet" type="text/css" media="all" href="'.$style.'" />';
}

public function delete_oembed_caches($post_ID) {
    $post_metas = get_post_custom_keys($post_ID);
    if (empty($post_metas))
        return;

    foreach ($post_metas as $post_meta_key) {
        if ($this->meta_id == substr($post_meta_key, 0, strlen($this->meta_id)))
            delete_post_meta($post_ID, $post_meta_key);
    }
}

private function get_template()
{
    $html =<<<EOL
<div class="otweet" style="background-image:url({\$profile_background_image_url})">
<div class="otweet_content">
    <div class="otweet_header">
        <div class="text">{\$text}</div>
        <div class="created_at">{\$created_at} via {\$source}</div>
    </div><!--end .otweet_header-->
    <div class="otweet_footer">
        <div class="profile_image">{\$profile_image}</div>
        <div class="name">{\$name}</div>
        <div class="screen_name">{\$screen_name}</div>
    </div><!--end .otweet_footer-->
</div><!--end .otweet_content-->
</div><!--end .otweet-->
EOL;

    return $html;
}


} // end class

// EOF
