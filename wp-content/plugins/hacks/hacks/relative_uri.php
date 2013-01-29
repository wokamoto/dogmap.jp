<?php
class relative_URI {
    function relative_URI() {
        add_action('get_header', array(&$this, 'get_header'), 1);
        add_action('wp_footer', array(&$this, 'wp_footer'), 99999);
    }
    function replace_relative_URI($content) {
        $home_url = trailingslashit(get_home_url('/'));
        $parsed   = parse_url($home_url);
        $replace  = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['port']))
            $replace .= ':'.$parsed['port'];

        $pattern  = array(
            '# (href|src|action)="'.preg_quote($replace).'([^"]*)"#ism',
            "# (href|src|action)='".preg_quote($replace)."([^']*)'#ism",
        );
        $content  = preg_replace($pattern, ' $1="$2"', $content);

        $pattern  = '#<(meta [^>]*property=[\'"]og:[^\'"]*[\'"] [^>]*content=|link [^>]*rel=[\'"]canonical[\'"] [^>]*href=|link [^>]*rel=[\'"]shortlink[\'"] [^>]*href=|data-href=|data-url=)[\'"](/[^\'"]*)[\'"]([^>]*)>#uism';
        $content = preg_replace($pattern, '<$1"'.$replace.'$2"$3>', $content);

        if ( is_ssl() ) {
            $content = str_replace(
                str_replace( 'https://', 'http://', $home_url),
                str_replace( 'http://', 'https://', $home_url),
                $content);
            $content = str_replace(
                array('http://connect.facebook.net/', 'http://apis.google.com/', 'http://platform.twitter.com/', 'http://wokamoto.files.wordpress.com'),
                array('https://connect.facebook.net/', 'https://apis.google.com/', 'https://platform.twitter.com/', 'https://wokamoto.files.wordpress.com'),
                $content);
        }

        return $content;
    }
    function get_header(){
        ob_start(array(&$this, 'replace_relative_URI'));
    }
    function wp_footer(){
        ob_end_flush();
    }
}
new relative_URI();

