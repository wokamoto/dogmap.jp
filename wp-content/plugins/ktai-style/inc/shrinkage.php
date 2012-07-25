<?php
/* ==================================================
 *   Ktai Content Shrinkage
   ================================================== */

if ( !defined('ABSPATH')) {
	exit;
}

//define ('KTAI_SHRINKAGE_DEBUG', true);

define('KTAI_DO_PC_HEAD', true);
define('KTAI_NONE_PC_HEAD', false);
define('KTAI_NO_PC_HEAD', false); // Ktai Style 2 beta compati

class KtaiShrinkage {
	public  $base;
	private $url;
	private $wpurl;
	private $is_multisite;
	private $url_host;
	private $self_dir;
	private $self_url;
	private $self_url_path;
	private $self_regex;
	private $self_urls_regex;
	private $plugin_url_regex;
	private $mobile_site_regex;
	private $pconly_site_regex;
	private $keep_image_regex;
	private $leave_regex;
	private $mobile_regex;
	private $none_mobile_regex;
	private $num_image;
	private $image_inline_default;
	private $image_inline;
	private $image_services;
	private $image_services_regex;
	private $skip_shrink_content = false;
	const COOKIE_IMAGE_INLINE = 'image_inline';
	const THUMBNAIL_FILENAME = '.ktai';
	const THUMBNAIL_MAX_SIZE = 96; // use ktai_thumbnail_max_size filter to change this value
	const GD_MEMORY_LIMIT = '256M';
	const SIZE_EXCEED_COLOR = '#808080';
	const MOBILE_SITE_CLASS = 'ktai';
	const PCONLY_SITE_CLASS = 'pconly';
	const DIRECT_LINK_IMAGE_SIZE = 76800; // use ktai_direct_link_image_size filter to change this value
	const MAX_PAGE_NUM = 1000;
	const START_PAGING_REGEX = '@<!--start paging(\[(.*?)\])?-->\n*@';
	const END_PAGING_REGEX = '@\n*<!--end paging(\[(.*?)\])?-->@';
	const PAGE_NUMBER_PARAM = 'kp';
	static public $mobile_same_url = array(
		// Use same URL for PC and mobile
		'http://[-\w]+\.blog\d+\.fc2\.com/', 
		'http://(jugem|yaplog)\.jp/[-\w]+/', 
		'http://[-\w]+\.seesaa\.net/', 
		'http://blog\.goo\.ne\.jp/[-\w]+/', 
		'http://blogs\.dion\.ne\.jp/[-\w]+/',
		'http://blog\.auone\.jp/[-\w]+/',
		'http://[-\w]+\.blog\.so-net\.ne\.jp/',
		'http://[-\w]+\.paslog\.jp/',
		'http://[-\w]+\.vox\.com/',
		'http://hb\.afl\.rakuten\.co\.jp/hgc/',
		'http://[-\w]+\.ap\.teacup\.com/',
		'http://pub\.ne\.jp/[-.\w]+/',
		'http://[-\w]+\.blog.shinobi.jp/',
		// Redirect mobile URL from PC
		'http://d\.hatena\.ne\.jp/[-\w]+/',
		'http://blog\.livedoor\.jp/[-\w]+/',
		'http://[-\w]+.(cocolog|air|moe|tea|txt|way)-nifty\.com/',
		'http://[-\w]+\.at\.webry\.info/',
		'http://ameblo\.jp/[-\w]+/',
		'http://[-\w]+\.spaces\.live\.com/',
		'http://plaza\.rakuten\.co\.jp/[-\w]+(/|$)',
		'http://[-\w]+\.blog.drecom.jp/',
	);
	static public $none_mobile_url = array(
		'http://(www|support|app)\.cocolog-nifty\.com/',
	);

// ==================================================
public function __construct() {
	global $Ktai_Style, $wpmu_version;
	$this->base = $Ktai_Style;
	$this->url   = trailingslashit(get_bloginfo('url'));
	$this->wpurl = trailingslashit(get_bloginfo('wpurl'));
	if (function_exists('is_multisite')) {
		$this->is_multisite = is_multisite();
	} elseif (isset($wpmu_version)) {
		$this->is_multisite = true;
	} else {
		$this->is_multisite = false;
	}
	$url_parts = parse_url($this->url);
	$wpurl_parts = parse_url($this->wpurl);
	$this->url_host = $url_parts['scheme'] . '://' . $url_parts['host'] . ($url_parts['port'] ? ':' . $url_parts['port'] : '');
	if ( !$this->is_multisite ) { // single site
		$uploads = wp_upload_dir();
		$uploads_url = trailingslashit($uploads['baseurl']);
		$content_url = trailingslashit(content_url());
		if ( $uploads['baseurl'] && false === strpos($uploads_url, $this->wpurl) && false === strpos($uploads_url, $content_url) ) {
			$this->self_dir[] = trailingslashit(str_replace('\\', '/', $uploads['basedir']));
			$this->self_url[] = $uploads_url;
			$this->self_url_path[] = $url_path = $this->base->strip_host($uploads_url);
			$this->self_regex[] = $this->make_url_regex($uploads_url, $url_path);
		}
		if ( strlen($content_url) <= 1 && false === strpos($content_url, $this->wpurl) ) {
			$this->self_dir[] = trailingslashit(str_replace('\\', '/', WP_CONTENT_DIR));
			$this->self_url[] = $content_url;
			$this->self_url_path[] = $url_path = $this->base->strip_host($content_url);
			$this->self_regex[] = $this->make_url_regex($content_url, $url_path);
		}
		if ($this->wpurl) {
			$this->self_dir[] = str_replace('\\', '/', ABSPATH);
			$this->self_url[] = $this->wpurl;
			$this->self_url_path[] = $url_path = $this->base->strip_host($this->wpurl);
			$this->self_regex[] = $this->make_url_regex($this->wpurl, $url_path);
		}
	} else { // multi site
		if ( strlen($this->wpurl) <= 1 ) {
			$this->wpurl = $this->url;
		}
		$this->wpurl .= 'files/';
		$this->self_dir[] = trailingslashit(str_replace('\\', '/', ABSPATH . UPLOADS));
		$this->self_url[] = $this->wpurl;
		$this->self_url_path[] = $url_path = $this->base->strip_host($this->wpurl);
		$this->self_regex[] = $this->make_url_regex($this->wpurl, $url_path);
	}
	if ( $this->url && strcmp($this->wpurl, $this->url) !== 0 ) {
		$this->self_dir[] = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] . $url_parts['path']);
		$this->self_url[] = $this->url;
		$this->self_url_path[] = $url_path = $this->base->strip_host($this->url);
		$this->self_regex[] = $this->make_url_regex($this->url, $url_path);
	}
	$this->self_urls_regex = '!^' . implode('|^', array_map('preg_quote', array_merge($this->self_url, $this->self_url_path))) . '!';
	
	$parts_plugin_url = parse_url($this->base->get('plugin_url'));
	$this->plugin_url_regex = '!^(' . preg_quote($this->base->get('plugin_url'), '!') . '|'
		. preg_quote($this->base->strip_host($this->base->get('plugin_url')), '!') . ')!';

	$this->mobile_site_regex = '/(^| )' . preg_quote(self::MOBILE_SITE_CLASS, '/') . '( |$)/';
	$this->pconly_site_regex = '/(^| )' . preg_quote(self::PCONLY_SITE_CLASS, '/') . '( |$)/';
	$this->keep_image_regex = '/(^| )(wp-smiley|latex|' . preg_quote(self::MOBILE_SITE_CLASS, '/') . ')( |$)/';
	$relative_url = array('^(\./|[-a-zA-Z0-9_.+=%~]+(/|$))');
	$leave_schemes = apply_filters('ktai_leave_scheme', array('tel:', 'tel-av:', 'vtel:', 'mailto:', 'device:', 'location:'));
	$leave_schemes = apply_filters('leave_scheme/ktai_style.php', $leave_schemes);
	$leave_sites = preg_split('/\\s+/', ks_option('ks_treat_as_internal'), -1, PREG_SPLIT_NO_EMPTY);
	$this->leave_regex = '!^(#|' . implode('|', array_merge($relative_url, $leave_schemes, array_map('preg_quote', $leave_sites))) . ')!';
	$this->mobile_regex = '!^(' . implode('|', 
		apply_filters('mobile_same_url/ktai_style.php', 
		apply_filters('ktai_mobile_same_url', self::$mobile_same_url)
		)) . ')!';
	$this->none_mobile_regex = '!^(' . implode('|', 
		apply_filters('none_mobile_url/ktai_style.php', 
		apply_filters('ktai_none_mobile_url', self::$none_mobile_url)
		)) . ')!';
	$this->num_image = 0;
	$this->image_services = apply_filters('ktai_image_services', array(
		'^https?://[-\w.]+\.flickr.com/' => array($this, 'get_thumbnail_flickr'),
	));
	$this->image_services = apply_filters('image_services/ktai_style.php', $this->image_services);
	$this->image_services_regex = '!^(' . implode('|', array_keys($this->image_services)) . ')!';
	$this->set_image_inline();

	add_filter('attribute_escape', array($this, 'attribute_escape_filter'), 90, 2);
	add_filter('esc_html', array($this, 'attribute_escape_filter'), 90, 2);
	add_filter('clean_url', array($this, 'clean_url_filter'), 90, 3);
	add_filter('the_title', array($this, 'shrink_title'), 90);
	add_filter('the_content', array($this, 'shrink_content'), 90);
	remove_filter('get_the_excerpt', 'wp_trim_excerpt');
	add_filter('get_the_excerpt',  array($this, 'post_excerpt'), 9);
	add_filter('get_comment_excerpt', array($this, 'comment_excerpt'), 90);
	add_filter('get_comment_text', array($this, 'shrink_content'), 90);
	if ( !defined('KTAI_ADMIN_MODE') || !KTAI_ADMIN_MODE ) {
		add_filter('post_link', array($this->base, 'strip_host'), 90);
		add_filter('page_link', array($this->base, 'strip_host'), 90);
		add_filter('attachment_link', array($this->base, 'strip_host'), 90);
		add_filter('year_link', array($this->base, 'strip_host'), 90);
		add_filter('month_link', array($this->base, 'strip_host'), 90);
		add_filter('day_link', array($this->base, 'strip_host'), 90);
		add_filter('category_link', array($this->base, 'strip_host'), 90);
		add_filter('list_cats', array($this->base, 'strip_host'), 90);
		add_filter('tag_link', array($this->base, 'strip_host'), 90);
	}
	add_filter('the_author_posts_link', array($this, 'shrink_link'));
	add_filter('the_category',  array($this, 'shrink_link'), 10, 3);
	add_filter('edit_post_link', array($this, 'shrink_link'), 10, 2);
	add_filter('edit_comment_link', array($this, 'shrink_link'), 10, 2);
	add_filter('edit_tag_link', array($this, 'shrink_link'), 10, 2);
	add_filter('edit_bookmark_link', array($this, 'shrink_link'), 10, 2);
	add_filter('redirect_canonical', array($this, 'complete_url'), 10, 2);
	add_filter('wp_generate_tag_cloud', array($this, 'shrink_tag_cloud'), 90, 3);
	add_filter('wp_dropdown_pages',  array($this, 'shrink_dropdown'), 90, 3);
	add_filter('comment_reply_link', array($this, 'fix_comment_reply_link'), 10, 4);
	add_filter('get_comments_pagenum_link', array($this, 'fix_comments_pagenum_link'));
	if ( !isset($_COOKIE[self::COOKIE_IMAGE_INLINE]) && $this->image_inline_default != $this->image_inline ) {
		add_filter('ktai_split_page', array($this, 'add_url_inline_image'), 7);
	}
	add_filter('ktai_split_page', array($this, 'split_page'), 9, 2);
	add_filter('ktai_split_page', array($this, 'trim_images'), 20);
	add_filter('img_caption_shortcode', array($this, 'img_caption'), 20, 3);
	return;
}

/* ==================================================
 * @param	string  $full_url
 * @param	string  $url_path
 * @return	string  $regex
 * @since	2.0.0
 */
private function make_url_regex($full_url, $url_path) {
	$regex = '!^(' . preg_quote($full_url, '!') // assume trailing shash
	. '?|' . preg_quote($url_path, '!')
	. ( strlen($url_path) <= 1 ? ')!' : '?)!');
	return $regex;
}

// ==================================================
public function get($key) {
	return isset($this->$key) ? $this->$key : NULL;
}

// ==================================================
public function added_image() {
	if ($this->skip_shrink_content) {
		return;
	}
	return ++$this->num_image;
}

/* ==================================================
 * @param	none
 * @return	none
 * @since	1.10
 */
private function set_image_inline() {
	$this->image_inline_default = $this->base->ktai->get('flat_rate');
	$this->image_inline_default = apply_filters('ktai_image_inline_setting', $this->image_inline_default);
	$this->image_inline_default = apply_filters('image_inline_setting/ktai_style.php', $this->image_inline_default);
	$image_format = get_query_var('img');
	if ( $image_format && in_array($image_format, array('inline', 'link')) ) {
		if (isset($_COOKIE[self::COOKIE_IMAGE_INLINE]) 
		&& ($_COOKIE[self::COOKIE_IMAGE_INLINE] == 'inline') == $this->image_inline_default) {
			setcookie(self::COOKIE_IMAGE_INLINE, false, time() - 31536000, COOKIEPATH, COOKIE_DOMAIN); // erase cookie
			if ( COOKIEPATH != SITECOOKIEPATH ) {
				setcookie(self::COOKIE_IMAGE_INLINE, false, time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN); // erase cookie
			}
		} else {
			setcookie(self::COOKIE_IMAGE_INLINE, $image_format, 0, COOKIEPATH, COOKIE_DOMAIN);
			if ( COOKIEPATH != SITECOOKIEPATH ) {
				setcookie(self::COOKIE_IMAGE_INLINE, $image_format, 0, SITECOOKIEPATH, COOKIE_DOMAIN);
			}
		}
	} elseif (isset($_COOKIE[self::COOKIE_IMAGE_INLINE])) {
		$image_format = stripslashes($_COOKIE[self::COOKIE_IMAGE_INLINE]);
	}
	switch($image_format) {
	case 'inline':
		$this->image_inline = $this->base->ktai->get('flat_rate');
		break;
	case 'link':
		$this->image_inline = false;
		break;
	default:
		$this->image_inline = $this->image_inline_default;
		break;
	}
}

/* ==================================================
 * @param	string  $safe_text
 * @param	string  $text
 * @return	string  $safe_text
 */
public function attribute_escape_filter($safe_text, $text) {
	return str_replace('&#038;', '&amp;', $safe_text);
}

/* ==================================================
 * @param	string  $url
 * @param	string  $original_url
 * @param	string  $context
 * @return	string  $url
 */
public function clean_url_filter($url, $original_url, $context) {
	if ('display' == $context) {
		$url = str_replace('&#038;', '&amp;', $url);
	}
	return $url;
}

/* ==================================================
 * @param	string  $redirect_url
 * @param	string  $requested_url
 * @return	string  $redirect_url
 */
public function complete_url($redirect_url, $requested_url) {
	if (preg_match('!^://!', $redirect_url)) {
		$request = @parse_url($requested_url);
		$redirect_url = $request['scheme'] . $redirect_url;
	} elseif (preg_match('!^/!', $redirect_url) && preg_match('!^(https?://[^/]*)!', $requested_url, $request)) {
		$redirect_url = $request[1] . $redirect_url;
	}
	return $redirect_url;
}

/* ==================================================
 * @param	string  $title
 * @return	string  $title
 */
public function shrink_title($title) {
	$phrase[0] =       str_replace('%s',     '',            __('Protected: %s'));
	$regex[0] = '/^' . str_replace('%s', '(.*)', preg_quote(__('Protected: %s'), '/')) . '$/';
	$icon[0] =         str_replace('%s',   '$1',            __('Protected: %s'));
	$icon[0] =         str_replace($phrase[0], '<img localsrc="501" alt="' . $phrase[0] . '" />', $icon[0]);
	$phrase[1] =       str_replace('%s',     '',            __('Private: %s'));
	$regex[1] = '/^' . str_replace('%s', '(.*)', preg_quote(__('Private: %s'), '/')) . '$/';
	$icon[1] =         str_replace('%s',   '$1',            __('Private: %s'));
	$icon[1] =         str_replace($phrase[1], '<img localsrc="279" alt="' . $phrase[1] . '" />', $icon[1]);
	return preg_replace($regex, $icon, $title, 1);
}

/* ==================================================
 * @param	string  $link
 * @return	string  $link
 * @since	2.0.0
 */
public function shrink_link($link) {
	return preg_replace('/ (rel|title)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', '', $link, 1);
}

/* ==================================================
 * @param	string  $content
 * @return	string  $content
 */
public function convert_links($content) {
	for ($offset = 0, $replace = 'X' ; 
	     preg_match('!<a ([^>]*?)>(.*?)</a>!s', $content, $a, PREG_OFFSET_CAPTURE, $offset) ; 
	     $offset += strlen($replace))
	{
		$orig      = $a[0][0];
		$offset    = $a[0][1];
		$attr_str  = $a[1][0];
		$anchor    = $a[2][0];
		$link_html = $anchor; // default is stripping links
		$replace   = $orig;
		$link_attr = array();
		if (preg_match_all('/\b(class|style)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $attr_str, $attr_list, PREG_SET_ORDER)) {
			foreach($attr_list as $a) {
				$link_attr[$a[1]] = stripslashes($a[3]);
			}
		}
		if ( !preg_match('/href=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\1/s', $attr_str, $h) ) {
			continue;
		}
		$href = $h[0];
		$q    = $h[1];
		$url  = $h[2];
		if (preg_match('!^\s*<img ([^>]*?)\bsrc=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2([^>]*?) ?/?>\s*$!s', $anchor, $image)) { // <?php /* syntax hilighting fix */
			$src = $image[3];
			$img_attr = $image[1] . $image[4];
			if (preg_match($this->plugin_url_regex, $src)) { // skip plug-in's icon
				continue; // leave links
			}
			list($link_html, $url) = $this->image_inside_link($orig, $href, $url, $link_attr, $anchor, $src, $img_attr);
		} else {
			$link_html = $this->rewrite_link($url, $anchor, $link_attr);
		}
		$replace = apply_filters('ktai_convert_links', $link_html, $orig, $url, $anchor);
		$replace = apply_filters('convert_links/ktai_style.php', $replace, $orig, $url, $anchor);
		if (! is_null($replace)) {
			$content = substr_replace($content, $replace, $offset, strlen($orig)); // convert links
		} else {
			$offset += strlen($orig);
		}
	}
	return $content;
}

/* ==================================================
 * @param	string  $orig
 * @param	string  $href
 * @param	string  $url
 * @param	array   $link_attr
 * @param	string  $anchor
 * @param	string  $src
 * @param	string  $img_attr
 * @return	string  $link_html
 * @return	string  $url
 */
private function image_inside_link($orig, $href, $url, $link_attr, $anchor, $src, $img_attr) {
	$attr = array();
	if (preg_match_all('/\b(alt|class|style|width|height)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $img_attr, $attr_list, PREG_SET_ORDER)) {
		foreach($attr_list as $a) {
			$attr[$a[1]] = stripslashes($a[3]);
		}
	}
	if ($this->is_useless_image($attr)) {
		$link_html = '';
		return array($link_html, $url);
	}

	if ($this->image_inline 
	&& preg_match($this->image_services_regex, $url) 
	&& preg_match($this->image_services_regex, $src)) {
		foreach ($this->image_services as $regex => $callback) {
			if (preg_match('!' . $regex . '!', $src)) {
				$url = call_user_func($callback, $src, $url);
				break;
			}
		}
		$link_html = str_replace($href, 'href="' . $url . '"', $orig); // leave image services URL (Flickr etc)
		return array($link_html, $url);
	}
	$full_url = NULL;
	$path = NULL;
	foreach ( $this->self_regex as $index => $regex) {
		if ( preg_match($regex, $url) && preg_match($this->self_urls_regex, $src) ) {
			// both internal link
			$path = preg_replace($regex, $this->self_dir[$index], $url, 1);
			$full_url = $this->complete_relative_url($url);
			break;
		}
	}
	if ($path) {
		if (is_file($path) && ($imagesize = @filesize($path)) > 0 ) {
			// a thumbnail linked to original image
			$size_limit = apply_filters('ktai_direct_link_image_size', self::DIRECT_LINK_IMAGE_SIZE);
			$size_limit = apply_filters('direct_link_image_size/ktai_style.php', $size_limit);
			if ($this->image_inline && $imagesize <= min($this->base->get('cache_size'), $size_limit)) {
				$link_html = $orig;
			} else {
				$thumbnail = str_replace('<img ', '<img has_orig="true" ', $anchor); // inform existance of original to image_to_link()
				$link_html = $thumbnail . sprintf('<img src="%s" alt="%s" filesize="%d" />', $url, sprintf(__('Original(%dKB)', 'ktai_style'), intval($imagesize / 1024)), $imagesize); // pass filesize to image_to_link()
			}
		} else { // internal link to other than images
			$link_html = $orig;
		}
	} else { // external link
		$link_html = $this->rewrite_link($url, __('Link Target', 'ktai_style'), $link_attr);
		if (is_null($link_html)) {
			$link_html = sprintf('(<a href="%s">%s</a>)', $url, __('Link Target', 'ktai_style'));
		}
		$link_html = "{$anchor}($link_html)";
	}
	return array($link_html, $url);
}

/* ==================================================
 * @param   string  $url
 */
private function complete_relative_url($url) {
	if ( preg_match('!^/!', $url) ) {
		$url = $this->url_host . $url;
	} elseif ( preg_match('!^\./!', $url) ) {
		$current = preg_replace('!\?.*$!', '', $_SERVER['REQUEST_URI'], 1);
		$url = $this->url_host . trailingslashit($current) . preg_replace('!^\./!', '', $url, 1);
	} // don't touch relative URL with "../"
	return $url;
}

/* ==================================================
 * @param   string  $url
 * @param	string  $anchor
 * @param	array   $attr
 * @return	string  $link_html
 */
public function rewrite_link($url, $anchor, $attr) {
	if ( preg_match($this->self_urls_regex, $url) ) {
		$link_html = $this->link_element($this->base->strip_host($url), $anchor, $attr);
	} elseif ( preg_match($this->leave_regex, $url) 
	  || preg_match($this->mobile_site_regex, $attr['class'])
	  || $this->has_mobile_sites($url)
	  || $this->base->ktai->is_search_engine() ) {
		$link_html = NULL; // keep untouched
	} else {
		$colored_anchor = '<font color="' . ks_option('ks_external_link_color') . '">' . $anchor . '</font>';
		if ( !$this->base->get('use_redir') ) {
			$link_html = $this->link_element($url, $colored_anchor, $attr);
		} else {
			$icon = '<img localsrc="70" alt="' . __('[external]', 'ktai_style') . '" />';
			$nonce = $this->base->create_anon_nonce('redir_' . md5($url) . md5($_SERVER['HTTP_USER_AGENT']));
			if ( preg_match($this->pconly_site_regex, $class) || $this->none_mobile_sites($url) ) {
				$pconly_html = '&amp;' . self::PCONLY_SITE_CLASS . '=true';
			} else {
				$pconly_html = '';
			}
			$class = !empty($attr['class']) ? ('class="' . $attr['class'] . '"') : '';
			$style = !empty($attr['style']) ? ('style="' . $attr['style'] . '"') : '';
			$link_html = $icon . sprintf('<a href="%s%s/redir.php?_wpnonce=%s%s&amp;url=%s"%s%s>%s</a>', ks_plugin_url(KTAI_NOT_ECHO), KtaiStyle::INCLUDES_DIR, esc_attr($nonce), $pconly_html, rawurlencode($url), $class, $style, $colored_anchor);
		}
		$link_html = apply_filters('ktai_external_link', $link_html, $url, $anchor, $icon);
		$link_html = apply_filters('external_link/ktai_style.php', $link_html, $url, $anchor, $icon);
	}
	return $link_html;
}

// ==================================================
private function has_mobile_sites($url) {
	if (preg_match($this->none_mobile_regex, $url)) {
		return false;
	}
	return preg_match($this->mobile_regex, $url);
}

// ==================================================
private function none_mobile_sites($url) {
	return preg_match($this->none_mobile_regex, $url);
}

/* ==================================================
 * @param	string  $content
 * @return	string  $content
 */
public function convert_images($content) {
	for ($offset = 0, $replace = 'X'; 
	     preg_match('!<img ([^>]*?)\bsrc=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2([^>]*?) ?/?>!s', $content, $img, PREG_OFFSET_CAPTURE, $offset); // <?php /* syntax hilighting fix */
	     $offset += strlen($replace))
	{
		$orig    = $img[0][0];
		$offset  = $img[0][1];
		$src     = $img[3][0];
		$attr    = $img[1][0] . $img[4][0];
		$replace = $orig;
		if (preg_match('/local$/', $img[1][0])) { // pictograms
			continue;
		}
		list($replace, $has_image) = $this->image_to_link($orig, $src, $attr);
		$replace = apply_filters('ktai_image_to_link', $replace, $orig, $src);
		$replace = apply_filters('image_to_link/ktai_style.php', $replace, $orig, $src);
		if ( !is_null($replace) ) {
			$content = substr_replace($content, $replace, $offset, strlen($orig));
			if ($has_image) {
				$this->added_image();
			}
		} else {
			$offset += strlen($orig);
		}
	}
	return $content;
}

/* ==================================================
 * @param	string  $html
 * @param	string  $src
 * @param	string  $attr_str
 * @return	string  $replace
 * @return	boolean $has_image
 */
private function image_to_link($html, $src, $attr_str) {
	$replace = $html;
	$has_image = false;
	$attr = array();
	if (preg_match_all('/\b(alt|title|class|style|width|height|align)=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2/s', $attr_str, $attr_list, PREG_SET_ORDER)) {
		foreach($attr_list as $a) {
			$attr[$a[1]] = stripslashes($a[3]);
		}
	}
	if ($this->is_useless_image($attr)) {
		$replace = '';
		return array($replace, $has_image);
	}
	$alt = isset($attr['alt']) ? $attr['alt'] : NULL;
	if (empty($alt)) {
		if (isset($attr['title'])) {
			$alt = $attr['title'];
		} else {
			$src_parts = parse_url($src);
			$alt = basename($src_parts['path']);
		}
	}
	if (isset($attr['class']) && preg_match($this->keep_image_regex, $attr['class'])) {
		$replace = $this->image_element($this->base->strip_host($src), $alt, $attr);
	} elseif (preg_match($this->plugin_url_regex, $src)) { // plugin's icon or what
		if ($this->base->get('show_plugin_icon')) {
			$has_image = true;
			$replace = $this->image_inline ? preg_replace('!(src=[\'"])https?://[^/]*!', '$1', $html, 1) : "[$alt]"; // strip host
		} else {
			$replace = "[$alt]";
		}
	} elseif (preg_match('/filesize="(\d*)"/', $attr_str, $filesize)) { // original image for thumbnail passed by convert_links()
		$cache_size = $this->base->get('cache_size');
		if ($filesize[1] && $cache_size > 0 && $filesize[1] > $cache_size) {
			$replace = '<font color="' . self::SIZE_EXCEED_COLOR . '">' . $alt . '</font>';
		} else {
			$replace = $this->link_element($src, $alt, $attr);
		}
		$replace .= ']';
	} else {
		$path = $thum_path = $thum_url = NULL;
		foreach ( $this->self_regex as $index => $regex) {
			$dir = $this->self_dir[$index];
			if ( !preg_match($regex, $src) ) {
				continue;
			}
			$path = preg_replace($regex, $dir, $src, 1);
			$thumb_path = $this->get_thumbnail($path);
			if (! is_ks_error($thumb_path)) {
				$thumb_url = preg_replace('!^' . preg_quote($dir, '!') . '!', $this->self_url_path[$index], $thumb_path, 1);
			}
			break;
		}
		if (! $path) {
			foreach ($this->image_services as $pat => $callback) {
				if (preg_match('!' . $pat . '!', $src)) {
					$thumb_url = $src = call_user_func($callback, $src);
					$has_image = true;
					break;
				}
			}
		}
		if ( defined('KTAI_SHRINKAGE_DEBUG') && KTAI_SHRINKAGE_DEBUG && is_ks_error($thumb_path)) {
			$replace = '[[' . $thumb_path->getMessage() . ']]';
		} elseif ($this->image_inline && $thumb_url) {
			$replace = $this->image_element($thumb_url, $alt, $attr) 
			. (preg_match('/has_orig="true"/', $attr_str) ? '[' : '');
			$has_image = true;
		} else {
			$replace = '[<img localsrc="94" alt="' . __('IMAGE:', 'ktai_style') . '" />';
			if (! $path) { // link to a image of external sites
				$replace .= $this->link_element($src, $alt, $attr);
			} elseif (! is_dir($path) && $size = @filesize($path) && $size <= $this->base->get('cache_size')) { // link to the image
				$replace .= $this->link_element($src, $alt, $attr);
				$has_image = ! is_ks_error($thumb_path);
			} elseif ($thumb_path && $thumb_url) { // link to a thumbnail
				$replace .= $this->link_element($thumb_url, $alt, $attr);
				$has_image = ! is_ks_error($thumb_path);
			} else { // no link to images
				$replace .= '<font color="' . self::SIZE_EXCEED_COLOR . '">' . $alt . '</font>';
			}
			$replace .= preg_match('/has_orig="true"/', $attr_str) ? ' | ' : ']';
		}
	}
	if ( $this->image_inline && isset($attr['class']) && preg_match('/\b(align(left|right))\b/', $attr['class'], $align) ) {
		$replace = ks_image_alignment($replace, $align[1]);
	}
	return array($replace, $has_image);
}

/* ==================================================
 * @param	string $href
 * @param	string $anchor
 * @param	array  $attr
 * @return	string $html
 */
private function link_element($href, $anchor, $attr) {
	$html = sprintf('<a href="%s"%s%s>%s</a>', 
		$href, 
		(isset($attr['class']) ? ' class="' . $attr['class'] . '"' : ''),
		(isset($attr['style']) ? ' style="' . $attr['style'] . '"' : ''),
		$anchor);
	return $html;
}

/* ==================================================
 * @param	string $src
 * @param	string $alt
 * @param	array  $attr
 * @return	string $html
 */
private function image_element($src, $alt, $attr) {
	$html = sprintf('<img src="%s"%s%s%s%s />', 
		$src, 
		(isset($alt)           ? ' alt="' . $alt . '"' : ''),
		(isset($attr['class']) ? ' class="' . $attr['class'] . '"' : ''),
		(isset($attr['style']) ? ' style="' . $attr['style'] . '"' : ''),
		(isset($attr['align']) ? ' align="' . $attr['align'] . '"' : ''));
	return $html;
}

/* ==================================================
 * @param	array  $attr
 * @return	boolean $is_useless
 */
private function is_useless_image($attr) {
	$is_useless = false;
	if (isset($attr['alt'])) {
		$alt = stripslashes($attr['alt']);
		if (empty($alt) && ( !isset($attr['class']) || !preg_match('/(^| )(wp-image-\d+|attachment-\dx\d)( |$)/', $attr['class'])) ) {
			$is_useless = true; // hide images if the alt string is empty. 
		}
	}
	if ( (isset($attr['width']) && $attr['width'] <= 1) || (isset($attr['height']) && $attr['height'] <= 1) ) {
		$is_useless = true; // hide 1 pixel width/height images
	} elseif ( isset($attr['style']) && preg_match('/\b(visibility:\s*hidden|disypay:\s*none)/', $attr['style']) ) {
		$is_useless = true; // hide hidden styled images
	}
	return $is_useless;
}

/* ==================================================
 * @param	string  $path
 * @return	string  $thumb
 */
public function get_thumbnail($path) {
	if (! preg_match('!^(cropped-)?(.*?)(\.thumbnail|-\d+x\d+)?(\.[^.]+)?$!', basename($path), $file)) {
		return false;
	}
	$base = $file[2];
	$ext = isset($file[4]) ? $file[4] : '';
	$orig = dirname($path) . '/' . $base . $ext;
	if (isset($file[3]) && file_exists($orig)) {		
		$target = $orig; // Use the original image to make a smaller thumbnail.
		$result = $this->create_alter_image($path, false);
		if ( defined('KTAI_SHRINKAGE_DEBUG') && KTAI_SHRINKAGE_DEBUG && is_ks_error($result)) {
			return $result;
		}
	} else {
		$target = $path;
	}
	$thumb = dirname($path) . '/' . $base . self::THUMBNAIL_FILENAME . $ext;
	if (! file_exists($thumb)) {
		$thumb = $this->create_thumbnail($target, $thumb);
	}
	return $thumb;
}

/* ==================================================
 * @param	string  $img_path
 * @param	string  $thumb_path
 * @return	mix     $result
 */
private function create_thumbnail($img_path, $thumb_path) {
	$size = $this->create_alter_image($img_path, true);
	$max_size = apply_filters('ktai_thumbnail_max_size', self::THUMBNAIL_MAX_SIZE);
	$max_size = apply_filters('thumbnail_max_size/ktai_style.php', $max_size);
	if (is_ks_error($size)) {
		return $size;
	}
	try {
		$width  = $size[0];
		$height = $size[1];
		$type   = $size[2];
		$image  = $size['image'];
		if ($width <= $max_size && $height <= $max_size) { // No need to make thumbnail
			return $img_path;
		}
		if ($width > $height) {
			$image_ratio = $width / $max_size;
			$new_width  = $max_size;
			$new_height = $height / $image_ratio;
		} else {
			$image_ratio = $height / $max_size;
			$new_height = $max_size;
			$new_width = $width / $image_ratio;
		}
		$thumbnail = @imagecreatetruecolor($new_width, $new_height);
		if (! $thumbnail) {
			throw new KS_Error('Cannot initialize for thumbnail');
		}
		if (function_exists('imageantialias')) {
			imageantialias($thumbnail, true);
		}
		if (! imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height)) {
			throw new KS_Error('Resample failed');
		}
	
		// move the thumbnail to it's final destination
		$other_path = NULL;
		$result_other = NULL;
		switch ($type) {
		case IMAGETYPE_GIF:
			$result = @imagegif($thumbnail, $thumb_path);
			if ($result) {
				$other_path = preg_replace('|\.gif$|i', '.png', $thumb_path, 1);
				$result_other = @imagepng($thumbnail, $other_path);
			}
			break;
		case IMAGETYPE_PNG:
			$result = @imagepng($thumbnail, $thumb_path);
			if ($result) {
				$other_path = preg_replace('|\.png$|i', '.gif', $thumb_path, 1);
				$result_other = @imagegif($thumbnail, $other_path);
			}
			break;
		case IMAGETYPE_JPEG:
		default:
			$result = @imagejpeg($thumbnail, $thumb_path);
		break;
		}
		imagedestroy($thumbnail);
		if (! $result || ! file_exists($thumb_path)) {
			throw new KS_Error('Thumbnail file not written');
		}
		chmod($thumb_path, 0646);
		if ($other_path && $result_other) {
			chmod($other_path, 0646);
		}
		return $thumb_path;
	
	} catch (KS_Error $e) {
		return $e;
	}
}

/* ==================================================
 * @param	string   $img_path
 * @param	boolean  $return_image
 * @return	resource $image
 */
private function create_alter_image($img_path, $return_image = true) {
	try {
		if (! function_exists('imagecreatetruecolor')) {
			throw new KS_Error('GD not available');
		}
		if (empty($img_path)) {
			throw new KS_Error('No file name');
		}
		if (! file_exists($img_path)) {
			throw new KS_Error('No such a file:' . $img_path);
		}		
		$size = getimagesize($img_path);
		if (! $size) {
			throw new KS_Error('Cannot access to image:' . $img_path);
		}
		$width  = $size[0];
		$height = $size[1];
		$type   = $size[2];
		if ($width <= 0 || $height <= 0) {
			throw new KS_Error('Zero size image');
		}
		$other_path = NULL;
		$result = NULL;
		@ini_set('memory_limit', self::GD_MEMORY_LIMIT); // Use memory to load the image
		switch ($type) {
		case IMAGETYPE_GIF:
			$image = imagecreatefromgif($img_path);
			$other_path = preg_replace('|\.gif$|i', '.png', $img_path, 1);
			if ($image && ! file_exists($other_path)) {
				$result = @imagepng($image, $other_path);
			}
			break;
		case IMAGETYPE_PNG:
			$image = imagecreatefrompng($img_path);
			$other_path = preg_replace('|\.png$|i', '.gif', $img_path, 1);
			if ($image && ! file_exists($other_path)) {
				$result = @imagegif($image, $other_path);
			}
			break;
		case IMAGETYPE_JPEG:
			if ($return_image) {
				$image = imagecreatefromjpeg($img_path);
			}
			break;
		default:
			throw new KS_Error(sprintf('Can\'t handle image type "%1$s" of file: %2$s', $size['mime'], $img_path));
			break;
		}
		if ($return_image && ! $image) {
			throw new KS_Error('Invalid image file: ' . $img_path);
		}
		if ($other_path && $result) {
			chmod($other_path, 0646);
		}
		if ($result === false) {
			throw new KS_Error('Cannot write PNG/GIF image: ' . $other_path);
		}
		if ($return_image) {
			$size['image'] = $image;
			return $size;
		} else {
			return $result;
		}

	} catch (KS_Error $e) {
		return $e;
	}
}

/* ==================================================
 * @param	string  $img_src
 * @param	string  $img_href
 * @return	string  $thumb_url
 */
public function get_thumbnail_flickr($img_src, $img_href = '') {
	if ($img_href) {
		$thumb_url = preg_replace('!(/\d+_[0-9a-z]+)(_[stmb])?(\.jpg)$!', '$1_m$3', $img_src, 1);
	} else {
		$thumb_url = preg_replace('!(/\d+_[0-9a-z]+)(_[stmb])?(\.jpg)$!', '$1_t$3', $img_src, 1);
	}
	return $thumb_url;
}

/* ==================================================
 * @param	string  $content
 * @return	string  $content
 */
public function strip_styles_scripts_del($content) {
	$content = preg_replace('#<(script|style)[^>]*>.*?</\\1>#s', '', $content);
	$content = preg_replace('#<!--[^[].*?-->\\s*#s', '', $content);
	$content = preg_replace('#<del[^>]*>.*?</del>\\s*#s', '', $content);
	return $content;
}

/* ==================================================
 * @param	string  $content
 * @return	string  $content
 */
public function shrink_content($content) {
	if (! $this->skip_shrink_content) {
		$content = $this->strip_styles_scripts_del($content);
		$content = $this->base->filter_tags($content);
		$content = $this->convert_links($content);
		$content = $this->convert_images($content);
	}
	return $content;
}

/* ==================================================
 * @param	string  $text
 * @param	string  $text
 */
public function trim_excerpt($text) {
	$text = $this->base->filter_tags($text);
	$text = $this->strip_styles_scripts_del($text);
	$text = str_replace(']]>', ']]&gt;', $text);
	$text = strip_tags($text, '<img>');
	$text = preg_replace('!<img ([^>]+?)(\blocalsrc="\w+")?([^>]+?)>!e', '"$2" ? "<img $2 />" : ""', $text);
	$len = apply_filters('excerpt_length', KTAI_EXCERPT_LENGTH);
	if (strlen($text) > $len) {
		$text = ks_cut_html($text, $len, 0) . apply_filters('excerpt_more', '...');
	}
	return $text;
}

/* ==================================================
 * @param	string  $text
 * @param	string  $text
 * Based on wp_trim_excerpt at formatting.php of WP 2.5
 */
public function post_excerpt($text) { // Fakes an excerpt if needed
	if ( '' == $text ) {
		$text = get_the_content('');
		$text = strip_shortcodes($text);
		$this->skip_shrink_content = true;
		$text = apply_filters('the_content', $text);
		$this->skip_shrink_content = false;
		$text = $this->trim_excerpt($text);
	}
	return $text;
}

/* ==================================================
 * @param	string  $excerpt
 * @return	string  $excerpt
 */
public function comment_excerpt($excerpt) {
	global $comment;
	return $this->trim_excerpt($comment->comment_content);
}

/* ==================================================
 * @param	none
 * @return	int    $page_num
 */
public function get_page_num() {
	$page_num = 0;
	if (isset($_GET['kp']) && is_numeric($_GET['kp'])) {
		$page_num = intval($_GET['kp']);
	} elseif (isset($_POST['kp']) && is_numeric($_POST['kp'])) {
		$page_num = intval($_POST['kp']);
	}
	return $page_num;
}

/* ==================================================
 * @param	string  $buffer
 * @param	int     $page_num
 * @return	string  $paged_content
 */
public function split_page($buffer, $page_num) {
	if ($page_num > self::MAX_PAGE_NUM) {
		$page_num = self::MAX_PAGE_NUM;
	} elseif ($page_num < 1) {
		$page_num = 1;
	}
	list($header, $buffer, $footer, $sep) = $this->separate_buffer($buffer);

	if (preg_match('/<input type="hidden" name="post_password" value="(.*?)"/s', $buffer, $match)) {
		$post_password = $match[1];
	} else {
		$post_password = '';
	}
	list($navi, $del_accesskey) = $this->get_split_page_navi(self::MAX_PAGE_NUM +1 ,true, $post_password);
	$page_size = $this->base->get('page_size') - strlen($header . $navi . '<hr /><hr />' . $navi . $footer) - 32; // 32-byte is space for adding tags by force_balance_tags()
	if ($page_size < 256) { // too small
		if (preg_match('@<body[^>]*>@s', $header, $s, PREG_OFFSET_CAPTURE)) {
			$move2body = substr_replace($header, '', 0, $s[0][1] + strlen($s[0][0]));
			$header = substr_replace($header, '', $s[0][1] + strlen($s[0][0]));
		} else {
			$move2body = $header;
			$header = '';
		}
		$buffer = $move2body . $buffer . $footer;
		$footer = '';
		$page_size = $this->base->get('page_size') - strlen($header . $navi . '<hr /><hr />' . $navi . $footer) - 32;
	}

	$start_tags = '';
	$terminator = '<!--KTAI_TERMINATOR_' . md5(uniqid()) . '-->';
	$marker = 0;
	$buffer_length = strlen($buffer);
	for ($count = 0 ; $count < $page_num ; $count++) {
		$fragment = ks_cut_html($buffer, $page_size, $marker, $this->base->get('charset'));
		$fragment = preg_replace('/\x1b\$[GEFOPQ]?$/', '', $fragment);
		if (preg_match('/(\x1b\$[GEFOPQ])[!-z]+$/', $fragment, $pict_sequence)) {
			$complemention = "\x0f"; // complete softbank pictgram shift-in
		} else {
			$complemention = '';
		}
		$quoted = str_replace(array("<\x0f", ">\x0f"), array("&lt;\x0f", "&gt;\x0f"), $start_tags . $fragment . $terminator); // protect softbank pictograms
		$balanced = force_balance_tags($quoted);
		preg_match("/$terminator(.*)\$/", $balanced, $added_html);
		$complemented = preg_replace('/\x1b\$[GEFOPQ]?\x1b/', '', $start_tags . $fragment . $complemention . @$added_html[1]);
		if (preg_match_all('!</([^<>]*)>!', @$added_html[1], $added_tags)) {
			$start_tags = '<' . implode('><', array_reverse($added_tags[1])) . '>'; // store complemented tags to next fragment
			if (strpos($start_tags, '<ol>') !== false) {
				$start_tags = $this->detect_nesting_list($balanced, $start_tags);
			}
			$start_tags .= (isset($pict_sequence[1]) ? $pict_sequence[1] : '');
		} else {
			$start_tags = (isset($pict_sequence[1]) ? $pict_sequence[1] : '');
		}
		$marker += strlen($fragment);
		if ($marker >= $buffer_length) {
			$count++;
			break;
		}
	}

	if (strlen($fragment) < $buffer_length && isset($added_html[1])) {
		list($navi, $del_accesskey) = $this->get_split_page_navi($count, ($marker +1 < $buffer_length), $post_password);
		if ($del_accesskey) { // delete redundant access keys
			$complemented = preg_replace('/(<(a|label)[^>]*?) accesskey="[' . $del_accesskey . ']"([^>]*?)>/', '$1$3>', $complemented);
		}
		return $header . 
			$sep['start']['before'] . $navi . $sep['start']['after'] . 
			$complemented . 
			$sep['end']['before']   . $navi . $sep['end']['after'] . 
			$footer;
	} else {
		return $header . $buffer . $footer;
	}
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
private function separate_buffer($buffer) {
	$sep = array(
		'start' => array('before' => '', 'after' => '<hr />'),
		'end'   => array('before' => '<hr />', 'after' => ''),
	);
	if (preg_match(self::START_PAGING_REGEX, $buffer, $s, PREG_OFFSET_CAPTURE)) {
		$header = substr_replace($buffer, '', $s[0][1]);
		$buffer = substr_replace($buffer, '', 0, $s[0][1] + strlen($s[0][0]));
		if (isset($s[1])) {
			list($before, $after) = explode(',', $s[2][0]);
			$sep['start']['before'] = strpos($s[2][0], ',') !== false ? $before : '';
			$sep['start']['after']  = strpos($s[2][0], ',') !== false ? $after : $before;
		}
	} elseif (preg_match('@<body[^>]*>@s', $buffer, $s, PREG_OFFSET_CAPTURE)) {
		$header = substr_replace($buffer, '', $s[0][1] + strlen($s[0][0]));
		$buffer = substr_replace($buffer, '', 0, $s[0][1] + strlen($s[0][0]));
	} else {
		$header = '';
	}
	if ($num_match = preg_match_all(self::END_PAGING_REGEX, $buffer, $s, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
		$s = $s[$num_match -1];
		$footer = substr_replace($buffer, '', 0, $s[0][1] + strlen($s[0][0]));
		$buffer = substr_replace($buffer, '', $s[0][1]);
		if (isset($s[1])) {
			list($before, $after) = explode(',', $s[2][0]);
			$sep['end']['before'] = $before;
			$sep['end']['after']  = $after;
		}
	} else {
		if (preg_match('!<hr [^/>]*/>\s*(<div>\s*)?<a (id|name)="tail"!s', $buffer, $s, PREG_OFFSET_CAPTURE)) {
			$footer = substr_replace($buffer, '', 0, $s[0][1]);
			$buffer = substr_replace($buffer, '', $s[0][1]);
		} else {
			list($buffer, $footer) = preg_split('@</body@', $buffer, 2);
			$footer = '</body' . $footer;
		}
	}

	return array($header, $buffer, $footer, $sep);
}

/* ==================================================
 * @param	int     $num
 * @param	boolean $rest
 * @param	string  $post_password
 * @return	string  $navi
 * @return	int     $page_num
 */
private function get_split_page_navi($num, $rest, $post_password) {
	$link = htmlspecialchars(remove_query_arg(self::PAGE_NUMBER_PARAM, $_SERVER['REQUEST_URI']), ENT_QUOTES);
	$link .= (strpos($link, '?') === false ? '?' : '&amp;') . self::PAGE_NUMBER_PARAM . '=';
	$del_accesskey = '';
	if ($num == 2) {
		$prev = _ks_internal_link(preg_replace('/(\?|&(amp;)?)' . self::PAGE_NUMBER_PARAM . '=/', '', $link), '*', __('*.Prev', 'ktai_style'), $post_password) . ' | ';
		$del_accesskey .= '*';
	} elseif ($num >= 3) {
		$prev = _ks_internal_link($link . intval($num -1), '*', __('*.Prev', 'ktai_style'), $post_password) . ' | ';
		$del_accesskey .= '*';
	} else {
		$prev = '';
	}
	if ($rest) {
		$next = ' | ' . _ks_internal_link($link . intval($num +1), '#', __('#.Next', 'ktai_style'), $post_password);
		$del_accesskey .= '#';
	} else {
		$next = '';
	}
	$navi = sprintf(__('<div align="center">Splitting the page for mobile: %1$s page #%2$d %3$s</div>', 'ktai_style'), $prev, $num, $next);
	$navi = apply_filters('ktai_split_page_navi', $navi, $prev, $num, $next);
	$navi = apply_filters('split_page_navi/ktai_style.php', $navi, $prev, $num, $next);
	if (function_exists('mb_convert_encoding')) {
		$navi = mb_convert_encoding($navi, $this->base->get('charset'), get_bloginfo('charset'));
	}
	return array($navi, $del_accesskey);
}

/* ==================================================
 * @param	string  $balanced
 * @param	string  $start_tags
 * @return	string  $start_tags
 */
private function detect_nesting_list($balanced, $start_tags) {
	$open[0] = '<ol>';
	preg_match_all('!</?[ou]l([^>]*)>!s', $balanced, $lists, PREG_OFFSET_CAPTURE);
	do {
		$close = array_pop($lists[0]);
	} while (strpos(maybe_serialize($close), '</ol>') !== false);
	$max_ol_level = preg_match_all('/<ol>/', $start_tags, $ol);
	for ($ol_level = 0 ; $ol_level < $max_ol_level ; $ol_level++) {
		$inside[$ol_level][0] = array('start' => strlen($close[0]), 'end' => $close[1]);
	}
	$level = 0;
	$ol_level = 0;
	$below_level[$ol_level] = $level +1;
	$entered[$ol_level] = 1; // to make sure
	foreach (array_reverse($lists[0]) as $l) {
		if (strpos($l[0], '</') !== false) {
			$level++;
			if ($level == $below_level[$ol_level]) {
				$inside[$ol_level][0]['start'] = $l[1] + strlen($l[0]);
			}
			if (strpos($l[0], '</ol>') !== false && $ol_level < $max_ol_level -1 && ! isset($entered[$ol_level +1])) {
				$ol_level++;
				$below_level[$ol_level] = $level +1;
				$inside[$ol_level][0]['end'] = $l[1];
				$entered[$ol_level] = 1;
			}
		} elseif ($level <= 0) {
			$open[0] = $l[0];
			$inside[$ol_level][0]['start'] = $l[1] + strlen($l[0]);
			break;
		} else {
			if (strpos($l[0], '<ol') !== false && $level < $below_level[$ol_level] && @$entered[$ol_level] == 1) {
				$open[$ol_level] = $l[0];
				$inside[$ol_level][0]['start'] = $l[1] + strlen($l[0]);
				$entered[$ol_level] = 2;
				$ol_level--;
			}
			if ($level == $below_level[$ol_level]) {
				array_unshift($inside[$ol_level], array('end' => $l[1]));
			}
			$level--;
		}
	}
	for ($ol_level = 0 ; $ol_level < $max_ol_level ; $ol_level++) {
		if (preg_match('/start=[\'"](\d+)[\'"]/', $open[$ol_level], $start)) {
			$start_num = intval(@$start[1]);
		} else {
			$start_num = 1;
		}
		$inside_html = '';
		foreach ($inside[$ol_level] as $offset) {
			$inside_html .= substr($balanced, $offset['start'], $offset['end'] - $offset['start']);
		}
		$start_num += preg_match_all('/<li>/', $inside_html, $items);
		$ol_pos = strpos($start_tags, '<ol>'); // should be matched
		if (strpos($start_tags, '<li>', $ol_pos) == $ol_pos + 4) {
			$start_num -= 1; // use same number for splited item
		}
		if ($start_num > 1) {
			$start_html = ' start="' . $start_num . '"';
		} else {
			$start_html = ' start="1"';
		}
		preg_match('/\stype=[\'"][^\'"]+[\'"]/', $open[$ol_level], $type);
		$start_tags = preg_replace('/<ol>/', '<ol' . $start_html . @$type[0] . '>', $start_tags, 1);
	}
	return str_replace(' start="1"', '', $start_tags);
}

/* ==================================================
 * @param	string  $content
 * @return	string  $content
 */
public function trim_images($content) {
	if ($this->base->get('cache_size') > 0) {
		$total_size = strlen($content);
		for ($offset = 0, $replace = 'X'; 
		     preg_match('!<img ([^>]*?)\bsrc=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2([^>]*?) ?/?>!s', $content, $i, PREG_OFFSET_CAPTURE, $offset); //" <?php /*syntax hilighting fix */
		     $offset += strlen($replace)) {
			$offset  = $i[0][1];
			$replace = $i[0][0];
			$src     = $i[3][0];
			$attr    = $i[1][0] . $i[4][0];
			if (preg_match($this->self_urls_regex, $src)) {
				$imagesize = @filesize(preg_replace($this->self_regex, $this->self_dir, $src, 1));
				if ($imagesize) {
					$total_size += $imagesize;
				}
				if ($total_size > $this->base->get('cache_size')) {
					if (preg_match('/alt=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\1/s', $attr, $a)) { //"syntax highlighting fix
						$replace = $a[2];
					} else {
						$replace = basename($src);
					}
					$content = substr_replace($content, $replace, $offset, strlen($i[0][0]));
				}
			}
		}
	}
	return $content;
}

/* ==================================================
 * @param	string  $output
 * @return	string  $output
 */
public function shrink_dropdown($output) {
	$output = preg_replace(
		array('/ class=([\'"])[-_ \w]+\1/', '/[\r\n\t]/'),
		array('', ''),
		$output);
	$output = str_replace('&nbsp;', '-', $output);
	return $output;
}

/* ==================================================
 * @param	string  $content
 * @param	array   $tags
 * @param	array   $args
 * @return	string  $content
 */
public function shrink_tag_cloud($content, $tags, $args) {
	for ($offset = 0, $replace = 'X' ; 
	     preg_match('!<a href=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\1([^>]*?)>(.*?)</a>!s', $content, $l, PREG_OFFSET_CAPTURE, $offset); 
	     $offset += strlen($replace))
	{
		$orig    = $l[0][0];
		$offset  = $l[0][1];
		$q       = $l[1][0];
		$url     = $l[2][0];
		$attr    = $l[3][0];
		$anchor  = $l[4][0];
		$replace = $orig;
		preg_match('/ style=([\'"])' . KtaiStyle::QUOTED_STRING_REGEX . '\\1/s', $attr, $style);
		$replace = '<a href=' . $q . $url . $q . $style[0] . '>' . $anchor . '</a>';
		$content = substr_replace($content, $replace, $offset, strlen($orig));
	}
	return $content;
}


/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function add_url_inline_image($buffer) {
	$value = $this->image_inline ? 'inline' : 'link';
	for ($offset = 0, $replace = 'X' ; 
	     preg_match('!<a ([^>]*?)href=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2([^>]*?)>!s', $buffer, $l, PREG_OFFSET_CAPTURE, $offset) ; 
	     $offset += strlen($replace))
	{
		$orig    = $l[0][0];
		$offset  = $l[0][1];
		$url     = $l[3][0];
		$url     = _ks_quoted_remove_query_arg('img', $url);
		$attr1   = $l[1][0];
		$attr2   = $l[4][0];
		$replace = $orig;
		if ( !preg_match($this->self_urls_regex, $url) || preg_match('/id="inline"/', $attr1 . $attr2)) {
			continue;
		}
		$url .= (strpos($url, '?') === false ? '?' : '&amp;' ) . "img=$value"; // can not use add_query_arg()
		$replace = sprintf('<a %shref="%s"%s>', $attr1, $url, $attr2); 
		$buffer = substr_replace($buffer, $replace, $offset, strlen($orig)); // convert links		
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $link
 * @param	array   $args
 * @param	object  $comment
 * @param	object  $post
 * @return	none
 */
public function fix_comment_reply_link($link, $args, $comment, $post) {
	$reply_url = add_query_arg('replytocom', $comment->comment_ID, ks_comments_post_url($post->ID) );
	if (strpos($link, 'wp-login.php?redirect_to=')) {
		$url = ks_get_login_url(KTAI_NOT_ECHO, $reply_url);
		if ($url) {
			$link = $args['before'] . '<a href="' . esc_url($url) . '">' . $args['login_text'] . '</a>' . $args['after'];
		} else {
			$link = '';
		}
	} elseif ($post->post_password && ! ks_post_password_required($post)) {
		if (preg_match('!<img localsrc="\w+"[^>]*?>!s', $args['reply_text'], $icon)) { // <?php /* syntax hilighting fix */
			$icon = $icon[0];
			$reply_text = strip_tags($args['reply_text']);
		} else {
			$icon = '';
			$reply_text = strip_tags($args['reply_text']);
		}
		$link = _ks_internal_link($reply_url, '', $reply_text, $post->post_password, $args['before'] . $icon, $args['after']);
	} else {
		$link = $args['before'] . '<a href="' . esc_url($reply_url) . '">' . $args['reply_text'] . '</a>' . $args['after'];
	}
	return $link;
}

/* ==================================================
 * @param	string  $result
 * @return	string  $result
 */
public function fix_comments_pagenum_link($result) {
	$result = preg_replace('/#comments$/', '', $result);
	if (! preg_match('/(comment-page-\d+|cpage=\d+)/', $result)) {
		global $post;
		$result = ks_get_comments_list_link($post->ID);
	}
	return $result;
}

/* ==================================================
 * @param	string  $null
 * @param	mix     $attr
 * @param	string  $content
 * @return	string  $content
 * based on img_caption_shortcode() at wp-includes/media.php of WP 2.8.4
 */
public function img_caption($null, $attr, $content = null) {
	extract(shortcode_atts(array(
		'id'	=> '',
		'align'	=> 'alignnone',
		'width'	=> '',
		'caption' => '',
		'margin' => 2,
	), $attr));
	if ( 1 > (int) $width || empty($caption) ) {
		return $content;
	}
	$content = do_shortcode($content);
	if ($this->image_inline) {
		$content = ks_image_alignment($content, $align, $margin);
	}
	return $content;
}

// ===== End of class ====================
}

/* ==================================================
 * @param	none
 * @return	int     $num_images
 */
function ks_added_image() {
	global $Ktai_Style;
	return $Ktai_Style->shrinkage->added_image();
}

/* ==================================================
 * @param	none
 * @return	boolean $is_image_inline
 */
function ks_is_image_inline() {
	global $Ktai_Style;
	return $Ktai_Style->shrinkage->get('image_inline');
}

/* ==================================================
 * @param	none
 * @return	boolean $num_image
 */
function ks_has_image_inline() {
	global $Ktai_Style;
	return $Ktai_Style->shrinkage->get('num_image');
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_wp_head($pc_head = true) {
	do_action('ktai_wp_head');
	if ($pc_head) {
		global $Ktai_Style;
		ob_start();
		do_action('wp_head');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $Ktai_Style->shrinkage->strip_styles_scripts_del($buffer);
	}
	return;
}

/* ==================================================
 * @param	none
 * @return	none
 */
function ks_wp_footer($pc_footer = true) {
	do_action('ktai_wp_footer');
	if ($pc_footer) {
		global $Ktai_Style;
		ob_start();
		do_action('wp_footer');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $Ktai_Style->shrinkage->strip_styles_scripts_del($buffer);
	}
	return;
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $url (with tailing slash)
 */
function ks_blogurl($echo = true) {
	global $Ktai_Style;
	$url = $Ktai_Style->strip_host($Ktai_Style->shrinkage->get('url'));
	if ($echo) {
		echo $url;
	}
	return $url;
}

/* ==================================================
 * @param	boolean $echo
 * @return	string  $url (with tailing slash)
 */
function ks_siteurl($echo = true) {
	global $Ktai_Style;
	$url = $Ktai_Style->strip_host($Ktai_Style->shrinkage->get('wpurl'));
	if ($echo) {
		echo $url;
	}
	return $url;
}

// ==================================================
global $Ktai_Style;
$Ktai_Style->shrinkage = new KtaiShrinkage;
?>
