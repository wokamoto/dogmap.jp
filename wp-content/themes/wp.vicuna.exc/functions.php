<?php
/**
 * function.php for wp.Vicuna
 * author: ma38su
 */


// wp.Vicuna Language File
load_textdomain('vicuna', dirname(__FILE__).'/languages/' . get_locale() . '.mo');

function vicuna_link() {
	$locale = get_locale();
	if ($locale == "ja") {
		echo 'http://wp.vicuna.jp/';
	} else if ($locale == "zh_TW" || $locale == "zh_CN") {
		echo 'http://cn.wp.vicugna.org/';
	} else {
		echo 'http://en.wp.vicugna.org/';
	}
}

/** -- ex add s -- **/

require_once(dirname(__FILE__).'/script/config.php');

require_once(dirname(__FILE__).'/script/layout.php');

if ( function_exists('register_sidebar') ) {
	require_once(dirname(__FILE__).'/script/widgets.php');
}

/**
 * get current language.
 */
function get_vicuna_language() {
	$options = get_option('vicuna_config');
	return $options['language'];
}

/**
 * display header for vicuna.
 */
function vicuna_head() {
	$options = get_option('vicuna_config');
	if ($options['feed_url'] && $options['feed_type']) {
?>
	<link rel="alternate" type="application/<?php echo $options['feed_type']; ?>" href="<?php bloginfo('atom_url'); ?>" title="Atom cite contents" />
<?php	} else { ?>
	<link rel="alternate" type="application/rss+xml" href="<?php bloginfo('rss2_url'); ?>" title="RSS 2.0" />
	<link rel="alternate" type="application/atom+xml" href="<?php bloginfo('atom_url'); ?>" title="Atom cite contents" />
<?php	}
}

add_action('wp_head', 'vicuna_head');

function is_widget($index, $widget_id) {
	global $wp_registered_sidebars;
	$index = sanitize_title($index);
	foreach ( $wp_registered_sidebars as $key => $value ) {
		if ( sanitize_title($value['name']) == $index ) {
			$index = $key;
			break;
		}
	}
	$sidebars_widgets = wp_get_sidebars_widgets();

	if ( !is_array($sidebars_widgets[$index]) || empty($sidebars_widgets[$index])) {
		return false;
	}

	foreach ($sidebars_widgets[$index] as $id) {
		if ($id == $widget_id) {
			return true;
		}
	}
	return false;
}
/** -- ex add e -- **/

/**
 * Display a tag clouds.
 */
function vicuna_tag_cloud( $args = '' ) {

	global $wp_rewrite;
	$defaults = array( 'levels' => 6, 'orderby' => 'name', 'order' => 'ASC', 'exclude' => '', 'include' => '' );

	$args = wp_parse_args( $args, $defaults );

	$tags = get_tags( array_merge($args, array('orderby' => 'count', 'order' => 'ASC') ) ); // Always query top tags

	if ( empty($tags) )
		return;

	extract($args);

	if ( !$tags )
		return;
	$counts = $tag_links = array();
	foreach ( (array) $tags as $tag ) {
		$counts[$tag->name] = $tag->count;
		$tag_links[$tag->name] = get_tag_link( $tag->term_id );
		if ( is_wp_error( $tag_links[$tag->name] ) )
			return $tag_links[$tag->name];
		$tag_ids[$tag->name] = $tag->term_id;
	}

	$min_count = min($counts);
	$step = (int) ((max($counts) - $min_count) / $levels) + 1;

	if ( $step <= 1 )
		$step = 1;

	// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
	if ( 'name' == $orderby )
		uksort($counts, 'strnatcasecmp');
	else
		asort($counts);

	if ( 'DESC' == $order )
		$counts = array_reverse( $counts, true );

	$a = array();

	$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

	foreach ( $counts as $tag => $count ) {
		$tag_id = $tag_ids[$tag];
		$tag_link = clean_url($tag_links[$tag]);
		$level = $levels - (int) (($count - $min_count) / $step);
		$tag = str_replace(' ', '&nbsp;', wp_specialchars( $tag ));
		$a[] = "<li class=\"level".$level."\"><a href=\"$tag_link\" title=\"" . attribute_escape( sprintf( __('%d Entries', 'vicuna'), $count ) ) . "\"$rel>$tag</a></li>";
	}

	$return = "<ul class=\"tagCloud\">\n\t";
	$return .= join("\n\t", $a);
	$return .= "\n</ul>\n";

	if ( is_wp_error( $return ) )
		return false;
	else
		echo apply_filters( 'vicuna_tag_cloud', $return, $tags, $args );
}


/**
 * Display calendar for Vicuna.
 */
function vicuna_calendar($initial = true) {
	$weekday = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	global $wpdb, $m, $monthnum, $year, $timedifference, $wp_locale, $posts;

	$key = md5( $m . $monthnum . $year );

	// get cache
	if ( $cache = wp_cache_get( 'vicuna_calendar', 'calendar' ) ) {
		if ( isset( $cache[ $key ] ) ) {
			echo $cache[ $key ];
			return;
		}
	}

	ob_start();
	// Quick check. If we have no posts at all, abort!
	if ( !$posts ) {
		$gotsome = $wpdb->get_var("SELECT ID from $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1");
		if ( !$gotsome )
			return;
	}

	if ( isset($_GET['w']) )
		$w = ''.intval($_GET['w']);

	// week_begins = 0 stands for Sunday
	$week_begins = intval(get_option('start_of_week'));
	$add_hours = intval(get_option('gmt_offset'));
	$add_minutes = intval(60 * (get_option('gmt_offset') - $add_hours));

	// Let's figure out when we are
	if ( !empty($monthnum) && !empty($year) ) {
		$thismonth = ''.zeroise(intval($monthnum), 2);
		$thisyear = ''.intval($year);
	} elseif ( !empty($w) ) {
		// We need to get the month from MySQL
		$thisyear = ''.intval(substr($m, 0, 4));
		$d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
		$thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('${thisyear}0101', INTERVAL $d DAY) ), '%m')");
	} elseif ( !empty($m) ) {
		$calendar = substr($m, 0, 6);
		$thisyear = ''.intval(substr($m, 0, 4));
		if ( strlen($m) < 6 )
				$thismonth = '01';
		else
				$thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
	} else {
		$thisyear = gmdate('Y', current_time('timestamp'));
		$thismonth = gmdate('m', current_time('timestamp'));
	}

	$unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear);

	// Get the next and previous month and year with at least one post
	$previous = $wpdb->get_row("SELECT DISTINCT MONTH(post_date) AS month, YEAR(post_date) AS year
		FROM $wpdb->posts
		WHERE post_date < '$thisyear-$thismonth-01'
		AND post_type = 'post' AND post_status = 'publish'
			ORDER BY post_date DESC
			LIMIT 1");
	$next = $wpdb->get_row("SELECT	DISTINCT MONTH(post_date) AS month, YEAR(post_date) AS year
		FROM $wpdb->posts
		WHERE post_date >	'$thisyear-$thismonth-01'
		AND MONTH( post_date ) != MONTH( '$thisyear-$thismonth-01' )
		AND post_type = 'post' AND post_status = 'publish'
			ORDER	BY post_date ASC
			LIMIT 1");
?>
			<table class="calendar" cellpadding="0" cellspacing="0" summary="Monthly calendar">
				<caption><?php
	if ( $previous ) {
		echo '<a href="' . get_month_link($previous->year, $previous->month) . '" title="Older" rel="nofollow">&laquo;</a>';
	} else {
		echo '&laquo;';
	}
	printf(__(' %1$s %2$s ', 'vicuna'), $wp_locale->get_month($thismonth), date('Y', $unixmonth));
	if ( $next ) {
		echo '<a href="' . get_month_link($next->year, $next->month) . '" title="Newer">&raquo;</a>';
	} else {
		echo '&raquo;';
	}

        $myweek = array();

        for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
			// $myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
			$myweek[] = $weekday[ ($wdcount + $week_begins) % 7];
        }
?></caption>
				<tr>
<?php
	foreach ( $myweek as $wd ) {
		// $day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
		$day_name = mb_substr( $wd, 0, 1, 'UTF-8');
		$day_ex = __($wd);

		if ($day_name == "S") {
/* -- カレンダー日本語表記化 s --
			echo "\t\t\t\t\t<th class=\"". strtolower(substr( $wd, 0, 3)) ."\" title=\"$wd\">$day_name</th>\n";
-- */
			echo "\t\t\t\t\t<th class=\"". strtolower(substr( $wd, 0, 3)) ."\" title=\"$wd\">".mb_substr( $day_ex, 0, 1, 'UTF-8')."</th>\n";
		} else {
/* -- カレンダー日本語表記化 s --
			echo "\t\t\t\t\t<th title=\"$wd\">$day_name</th>\n";
-- */
			echo "\t\t\t\t\t<th title=\"$wd\">".mb_substr( $day_ex, 0, 1, 'UTF-8')."</th>\n";
		}
	}
?>
				</tr>
				<tr>
<?php
	// Get days with posts
	$dayswithposts = $wpdb->get_results("SELECT DISTINCT DAYOFMONTH(post_date)
		FROM $wpdb->posts WHERE MONTH(post_date) = '$thismonth'
		AND YEAR(post_date) = '$thisyear'
		AND post_type = 'post' AND post_status = 'publish'
		AND post_date < '" . current_time('mysql') . '\'', ARRAY_N);
	if ( $dayswithposts ) {
		foreach ( $dayswithposts as $daywith ) {
			$daywithpost[] = $daywith[0];
		}
	} else {
		$daywithpost = array();
	}

	if ( strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') || strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'camino') || strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'safari') )
		$ak_title_separator = "\n";
	else
		$ak_title_separator = ', ';

	$ak_titles_for_day = array();
	$ak_post_titles = $wpdb->get_results("SELECT post_title, DAYOFMONTH(post_date) as dom "
		."FROM $wpdb->posts "
		."WHERE YEAR(post_date) = '$thisyear' "
		."AND MONTH(post_date) = '$thismonth' "
		."AND post_date < '".current_time('mysql')."' "
		."AND post_type = 'post' AND post_status = 'publish'"
	);
	if ( $ak_post_titles ) {
		foreach ( $ak_post_titles as $ak_post_title ) {
				if ( empty($ak_titles_for_day['day_'.$ak_post_title->dom]) )
					$ak_titles_for_day['day_'.$ak_post_title->dom] = '';
				if ( empty($ak_titles_for_day["$ak_post_title->dom"]) ) // first one
					$ak_titles_for_day["$ak_post_title->dom"] = str_replace('"', '&quot;', wptexturize($ak_post_title->post_title));
				else
					$ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . str_replace('"', '&quot;', wptexturize($ak_post_title->post_title));
		}
	}

	// See how much we should pad in the beginning
	$pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
	if ( 0 != $pad ) {
		for ($i = 0; $i < $pad; $i++)
			echo "\t\t\t\t\t<td>&nbsp;</td>\n";
	}

	$daysinmonth = intval(date('t', $unixmonth));
	for ( $day = 1; $day <= $daysinmonth; ++$day ) {
		if ( isset($newrow) && $newrow )
			echo "\n\t\t\t\t</tr>\n\t\t\t\t<tr>\n";
		$newrow = false;

		if ( $day == gmdate('j', (time() + (get_option('gmt_offset') * 3600))) && $thismonth == gmdate('m', time()+(get_option('gmt_offset') * 3600)) && $thisyear == gmdate('Y', time()+(get_option('gmt_offset') * 3600)) )
			echo "\t\t\t\t\t<td class=\"today\">";
		else
			echo "\t\t\t\t\t<td>";

		if ( in_array($day, $daywithpost) ) // any posts today?
				echo '<a href="' . get_day_link($thisyear, $thismonth, $day) . "\" title=\"$ak_titles_for_day[$day]\">$day</a>";
		else
			echo $day;
		echo "</td>\n";

		if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) )
			$newrow = true;
	}

	$pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
	if ( $pad != 0 && $pad != 7 ) {
		for ($i = 0; $i < $pad; $i++)
			echo "\t\t\t\t\t<td>&nbsp;</td>\n";
	}

	echo "\t\t\t\t</tr>\n\t\t\t</table>\n";

	$output = ob_get_contents();
	ob_end_clean();
	echo $output;
	$cache[ $key ] = $output;
	wp_cache_set( 'vicuna_calendar', $cache, 'calendar' );
}

/**
 * Delete cache of calendar
 */
function delete_vicuna_calendar_cache() {
	wp_cache_delete( 'vicuna_calendar', 'calendar' );
}
add_action( 'save_post', 'delete_vicuna_calendar_cache' );
add_action( 'delete_post', 'delete_vicuna_calendar_cache' );
add_action( 'update_option_start_of_week', 'delete_vicuna_calendar_cache' );
add_action( 'update_option_gmt_offset', 'delete_vicuna_calendar_cache' );


/**
 * Display a description for the blog.
 */
function vicuna_description () {
	$description = get_bloginfo('description');
	if ( !empty($description) ) {
		echo '<p class="description">' . $description . '</p>';
	}
}

/**
 * Display a pager(Newer | Older).
 */
function vicuna_paging_link($args = '') {
	global $paged, $wpdb, $wp_query;

	if (is_array($args))
		$r = &$args;
	else
		parse_str($args, $r);

	$defaults = array('next_label' => __('Newer Entries', 'vicuna'), 'prev_label' => __('Older Entries', 'vicuna'), 'indent' => '');
	$r = array_merge($defaults, $r);
	extract($r);

	if ($indent != '') {
		$indent = (int) $indent;

		for ($i = 0; $i < $indent; $i ++)
			$indentText .= "\t";
	}

	// get max_page
	if (!$max_page)
		$max_page = $wp_query->max_num_pages;

	// get paged
	if (!$paged)
		$paged = 1;

	// set next page number
	$nextpage = intval($paged) + 1;

	if(!is_single()) {
		if ($max_page > 1)
			echo "$indentText<ul class=\"flip pager\" id=\"flip2\">\n";
		if ($paged > 1) {
			echo "$indentText\t<li class=\"newer\"><a href=\"";
			previous_posts();
			echo '">'. preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $next_label) ."</a></li>\n";
		}
		if (empty($paged) || $nextpage <= $max_page) {
			echo "$indentText\t<li class=\"older\"><a href=\"";
			next_posts($max_page);
			echo '">'. preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $prev_label) ."</a></li>\n";
		}


		if ($max_page > 1)
			echo $indentText . '</ul>' . "\n";
	}
}

/**
 * Indent the body text.
 */
function indent_entry_body($content) {
	// インデント数 (div.textBody p から見て)
	$indent = 4;
	for ($i = 0; $i < $indent; $i ++)
		$indentText .= "\t";

	$pre_flag = false;
	$arr_content = split("\n", $content);

	foreach ($arr_content as $line) {
		if(!$pre_flag) {
			if (strpos($line, "<pre") !== false)
				$pre_flag = true;

			$mes .= $indentText . $line . "\n";
		}
		else {
			if (strpos($line, "</pre>") !== false)
				$pre_flag = false;

			$mes .= $line . "\n";
		}
	}

	return rtrim($mes) . "\n";
}

add_action('the_content', indent_entry_body, 99);


/**
 * Replace the body text with some vicuna style.
 */
function replace_entry_body($content) {
	// インデント数 (div.textBody から見て)
	$indent = 3;
	for ($i = 0; $i < $indent; $i ++)
		$indentText .= "\t";

	// get the title of entry.
	$entry_title = get_the_title();

	// [for ver.2.2]
	// $content = preg_replace('/\s*<p><span id="more-([0-9]+?)"><\/span>(.*?)<\/p>/', "\n\t\t\t</div>\n$indentText<div class=\"textBody\" id=\"extended\">\n$indentText\t<p>\\2</p>", $content);

	// [for ver.2.2]
	// $content = preg_replace('/\s*<span id="more-([0-9]+?)"><\/span>(.*?)<\/p>/', "\t</p>\n\t\t\t</div>\n$indentText<div class=\"textBody\" id=\"extended\">\n$indentText\t<p>\\2</p>", $content);

	// [for ver.2.2]
	// $content = preg_replace('/\s*<p><span id="more-([0-9]+?)"><\/span>(.*?)<br\s*\/>/', "\n\t\t\t</div>\n$indentText<div class=\"textBody\" id=\"extended\">\n<p>$indentText\t<p>\\2</p>", $content);

	if (is_page() || is_single()) {
		// Replace '<p><a id="more-**"></p>' by '<div class="textBody" id="extended">'.
		// $content = preg_replace('/\t<p(\s.+?=".+?">|>)<a id="more-([0-9]+?)"><\/a>(.*?)<\/p>/', "</div>\n$indentText<div class=\"textBody\" id=\"extended\">\n$indentText\t<p\\1\\3</p>", $content);

		// <a class="more-link">が、<p>から始まらない場合の<a id="more-**">を<div class="textBody" id="extended">に置き換える
		// $content = preg_replace('/<a id="more-([0-9]+?)"><\/a>(.*?)<\/p>/', "</p>\n\t\t\t</div>\n$indentText<div class=\"textBody\" id=\"extended\">\n$indentText\t<p>\\2</p>", $content);

		$content = preg_replace('/\s*<p><span id="more-([0-9]+?)"><\/span>(.*?)<\/p>/', "\n\t\t\t</div>\n$indentText<div class=\"textBody\" id=\"extended\">\n$indentText\t<p>\\2</p>", $content);

		$content = preg_replace('/\s*<span id="more-([0-9]+?)"><\/span>(.*?)<\/p>/', "\t</p>\n\t\t\t</div>\n$indentText<div class=\"textBody\" id=\"extended\">\n$indentText\t<p>\\2</p>", $content);

		$content = preg_replace('/\s*<p><span id="more-([0-9]+?)"><\/span>(.*?)<br\s*\/>/', "\n\t\t\t</div>\n$indentText<div class=\"textBody\" id=\"extended\">\n<p>$indentText\t<p>\\2</p>", $content);

	} else {
		// Replace '<p><a class="more-link">' by '<p class="continue"><a>'.
		// (href="hoge#more-**"を、href="hoge#extended"に置換)
		$content = preg_replace('/<p(\s.+?=".+?">|>)\s?<a href="(.+?)#more-([0-9]+?)" class="more-link">(.+?)<\/a><\/p>/', '<p class="continue"><a href="\2#extended" title="' . $entry_title . ' 全文を読む" rel="nofollow">\4</a></p>', $content);

		// <p>から始まらない場合の<a class="more-link">を<p class="continue"><a>に置き換える
		$content = preg_replace('/\s*<a href="(.+?)#more-([0-9]+?)" class="more-link">(.+?)<\/a><\/p>/', "</p>\n\t\t\t\t".'<p class="continue"><a href="\1#extended" title="' . $entry_title . ' 全文を読む" rel="nofollow">\3</a></p>', $content);

	}
	// Delete the tags '<p></p>', '<p class="hoge"></p>', '<p>&nbsp;</p>' and '<p class="hoge">&nbsp;</p>'
	$content = preg_replace('/\t*<p(\s.+?=".+?">|>)\s*?<\/p>\n/', '', $content);

	// Delete the tag "<br /></p>".
	$content = preg_replace("/<br \/>\s*<\/p>/", "</p>", $content);

	return $content;
}


add_action('the_content', replace_entry_body, 100);

/**
 * Indent the comment body.
 */
function indent_comment_body($content) {
	// インデント数 (dl.log dd p から見て)
	$indent = 7;
	for ($i = 0; $i < $indent; $i ++)
		$indentText .= "\t";

	$pre_flag = false;
	$arr_content = split("\n", $content);

	foreach ($arr_content as $line) {
		if(!$pre_flag) {
			if (strpos($line, "<pre") !== false)
				$pre_flag = true;

			$mes .= $indentText . $line . "\n";
		}
		else {
			if (strpos($line, "</pre>") !== false)
				$pre_flag = false;

			$mes .= $line . "\n";
		}
	}

	return rtrim($mes) . "\n";
}

add_filter('comment_text', indent_comment_body, 100);

/**
 * Return a URI of the javascript for VICUNA.
 */
function get_vicuna_javascript_uri() {
	$javascript_uri = get_stylesheet_directory_uri() . "/script.js";
	return $javascript_uri;
}

/**
 * Return a title of Archive page.
 */
function get_vicuna_archive_title() {
	if ( is_day() ) /* If this is a daily archive */
		// [2007-04-03 変更部分]
		// return get_the_time('Y年 m月 d日');
		return get_the_time('Y-m-d');
	elseif ( is_month() ) /* If this is a monthly archive */
		// [2007-04-03 変更部分]
		// return get_the_time('Y年 m月');
		return get_the_time('Y-m');
	elseif ( is_year() ) /* If this is a yearly archive */
		// [2007-04-03 変更部分]
		// return get_the_time('Y年');
		return get_the_time('Y');
	elseif ( is_author() )
		return 'Author';
}

/**
 * Return a page navigation.
 */
function get_vicuna_page_navigation($args = '') {
	if ( is_array($args) )
		$r = &$args;
	else
		parse_str($args, $r);

	$defaults = array('depth' => 0, 'show_date' => '', 'date_format' => get_option('date_format'),
		'child_of' => 0, 'exclude' => '', 'echo' => 1, 'authors' => '', 'separator' => ' | ');
	$r = array_merge($defaults, $r);

	$output = '';
	$current_page = 0;

	// sanitize, mostly to keep spaces out
	$r['exclude'] = preg_replace('[^0-9,]', '', $r['exclude']);

	// Allow plugins to filter an array of excluded pages
	$r['exclude'] = implode(',', apply_filters('wp_list_pages_excludes', explode(',', $r['exclude'])));
	$separator = $r['separator'];
	// Query pages.
	$pages = get_pages($r);
	if ( !empty($pages) ) {
		global $wp_query;
		if ( is_page() ) {
			$current_page = $wp_query->get_queried_object_id();
			$flag = false;
			$output = '';
			$family = get_vicuna_upper_page($pages, array(), $current_page);
			array_shift($family);
			foreach ($family as $page) {
				if ( $flag ) {
					$output = $separator . $output;
				} else {
					$flag = true;
				}
				$output = '<a href="' .get_permalink($page->ID). "\">$page->post_title</a>". $output;
			}
			return $output;
		}
	}
}

/**
 * Return a page upper the page.
 */
function get_vicuna_upper_page($pages, $family, $page_id) {
	foreach ($pages as $page) {
		if ($page_id == $page->ID) {
			if (array_push($family, $page)) {
				$family = get_vicuna_upper_page($pages, $family, $page->post_parent);
				break;
			}
		}
	}
	return $family;
}

/**
 * Return a link for archives.
 */
function vicuna_archives_link($limit = '') {
	global $wp_locale, $wpdb;

	if ( '' != $limit ) {
		$limit = (int) $limit;
		$limit = ' LIMIT '.$limit;
	}

	$arcresults = $wpdb->get_results("SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC" . $limit);
	if ( $arcresults ) {
		foreach ( $arcresults as $arcresult ) {
			$url    = get_month_link($arcresult->year,      $arcresult->month);
			$text	= sprintf("%04d-%02d", $arcresult->year, $arcresult->month);
			echo "\t<li><a href=\"$url\" title=\"$arcresult->posts\" rel=\"nofollow\">$text</a></li>\n";
		}
	}
}

/**
 * Return the upper category.
 */
function get_vicuna_upper_category($cat_id, $category_split = ' | ') {
	$parent = &get_category($cat_id);
	$name = $parent->cat_name;
	$flag = true;
	$parent_category = '';
	while ( $parent->category_parent ) {
		$tmp = '';
		$parent = &get_category($parent->category_parent);
		$tmp .= '<a href="'. get_category_link($parent->cat_ID).'">'.$parent->cat_name.'</a>';
		if ($flag) {
			$flag = false;
		} else {
			$tmp .= $category_split;
		}
		$parent_category = $tmp.$output;
	}
	return $parent_category;
}

/**
 * Return the total amount of pings.
 */
function get_vicuna_pings_count() {
	global $post, $wpdb, $id;
/* -- whisper cng s --
	$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_post_ID = '$post->ID' AND comment_approved = '1' AND comment_type != '' ORDER BY comment_date");
-- */
	$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_post_ID = '$post->ID' AND comment_approved != 'spam' AND (comment_type = 'trackback' OR comment_type = 'pingback') ORDER BY comment_date");
/* -- whisper cng e -- */
	// $comments = apply_filters( 'comments_array', $comments, $post->ID );
	return count($comments);
}

/* -- whisper add s -- */
function get_vicuna_comment_count() {
	global $post, $wpdb, $id, $comments;
//	$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_post_ID = '$post->ID' AND (comment_approved = '1' or comment_type = 'whisper')");
	$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_post_ID = '$post->ID' AND comment_approved != 'spam' order by comment_date");
	return count($comments);
}
/* -- whisper add e -- */

/**
 * Return all the posts.
 */
function get_all_posts() {
	global $wpdb;
	$posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY ID DESC");
	// $comments = apply_filters( 'comments_array', $comments, $post->ID );
	return $posts;
}

/**
 * Return all the comments.
 */
function get_all_comments() {
	global $wpdb;
	$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_approved = '1' AND comment_type = '' ORDER BY comment_date");
	// $comments = apply_filters( 'comments_array', $comments, $post->ID );
	return $comments;
}

/**
 * Display a link to edit the comments for every posts.
 * (unsupported in page)
 */
function vicuna_edit_comments_link($link = 'Edit This Comments.', $before = '', $after = '') {
	global $post;
	if ( is_attachment() )
		return;

	if( $post->post_type == 'page' ) {
		return;
	} else {
	        if ( ! current_user_can('edit_post', $post->ID) )
	                return;
	        $file = 'post';
	}
	$location = get_option('siteurl') . "/wp-admin/edit.php?p=$post->ID&amp;c=1";
	echo $before . "<a href=\"$location\">$link</a>" . $after;
}

add_action('entries_footer', vicuna_paging_link);

function vicuna_view_selectlist_pages(){
	$pages = get_pages('sort_column=menu_order&depth=-1');
	if (!empty($pages)) {
		echo '<select name="pages-list" id="pages-list">'."\n";
		foreach ( $pages as $post) {
			setup_postdata( $post);
			$id = (int) $post->ID;
			$post->post_title = wp_specialchars( $post->post_title );
			echo '<option>'.$id.' : '.$post->post_title.'</option>'."\n";
		}
		echo "</select>\n";
	}
}

function vicuna_view_selectlist_categories(){
	$categories = get_categories('orderby=ID');
	if (!empty($categories)) {
		echo '<select name="categories-list" id="categories-list">'."\n";
		foreach ( $categories as $cat) {
			$id = (int) $cat->cat_ID;
			echo '<option>'.$id.' : '.get_cat_name($id).'</option>'."\n";
		}
		echo "</select>\n";
	}
}

?>
