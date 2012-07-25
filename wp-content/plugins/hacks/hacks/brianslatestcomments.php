<?php
/*
Plugin Name: Brian's Latest Comments
Plugin URI: http://meidell.dk/archives/category/wordpress/latest-comments/
Version: 1.5.9
Description: This shows an overview of the recently active articles and the last people to comment on them. Original idea and code fixes contributed by <a href="http://binarybonsai.com">Michael Heilemann</a>.<br />If you have <a href="http://binarybonsai.com/archives/2004/08/17/time-since-plugin/">Dunstan's Time Since</a> installed, this plugin uses it for the title="" attributes on the comments and posts. (For WordPress 1.5)
Author: Brian Meidell
Author URI: http://meidell.dk/

Version 1.5: 	Now works without LOCK TABLE and CREATE TEMPORARY TABLE priviledges.
Version 1.5.1:  Can't remember what I did here
Version 1.5.2: 	Fixed count select statement to not include spammy comments
Version 1.5.3: 	Properly excludes track- and pingbacks
Version 1.5.4:  Excludes posts that are not published, even if they have comments
Version 1.5.5:	Fade old comments, fixed bug that wreaked havoc with Time Since
Version 1.5.6:	Bugfix from Jonas Rabbe (http://www.jonas.rabbe.com/) pertaining to timesince
Version 1.5.7:	Bugfix so old colors can be darker than new colors (stupid oversight), thanks to http://spiri.dk for spotting it.
		Bugfix where single digit hex would cause invalid colors, thanks to http://www.wereldkeuken.be/ for the fix.
Version 1.5.8:	Updated to work with WordPress 2.1 alpha by M. Heilemann.
Version 1.5.9:	Added caching and bugfix by wokamoto (http://dogmap.jp)

*/ 

if (is_admin()) return false;

function blc_latest_comments($num_posts = 5, $num_comments = 6, $hide_pingbacks_and_trackbacks = true, $prefix = "<li class='alternate'>", $postfix = "</li>", $fade_old = true, $range_in_days = 10, $new_col = "#444444", $old_col = "#cccccc") {
	$options = (array) get_option('blc_latest_comments');
	$out_text = (isset($options['out_text'])
		? $options['out_text']
		: blc_get_latest_comments(false, $num_posts, $num_comments, $hide_pingbacks_and_trackbacks, $prefix, $postfix, $fade_old, $range_in_days, $new_col, $old_col)
		);
	echo $out_text;
}

function blc_get_latest_comments($options_get = true, $num_posts = 5, $num_comments = 6, $hide_pingbacks_and_trackbacks = true, $prefix = "<li class='alternate'>", $postfix = "</li>", $fade_old = true, $range_in_days = 10, $new_col = "#444444", $old_col = "#cccccc") {
	global $wpdb;

	if ($options_get) {
		$options = (array) get_option('blc_latest_comments');
		$num_posts = (isset($options['num_posts']) ? $options['num_posts'] : $num_posts);
		$num_comments = (isset($options['num_comments']) ? $options['num_comments'] : $num_comments);
		$hide_pingbacks_and_trackbacks = (isset($options['hide_pingbacks_and_trackbacks']) ? $options['hide_pingbacks_and_trackbacks'] : $hide_pingbacks_and_trackbacks);
		$prefix = (isset($options['prefix']) ? $options['prefix'] : $prefix);
		$postfix = (isset($options['postfix']) ? $options['postfix'] : $postfix);
		$fade_old = (isset($options['fade_old']) ? $options['fade_old'] : $fade_old);
		$range_in_days = (isset($options['range_in_days']) ? $options['range_in_days'] : $range_in_days);
		$new_col = (isset($options['new_col']) ? $options['new_col'] : $new_col);
		$old_col = (isset($options['old_col']) ? $options['old_col'] : $old_col);
	}

	$usetimesince = function_exists('time_since'); // Work nicely with Dunstan's Time Since plugin (adapted by Michael Heilemann)

	// This is compensating for the lack of subqueries in mysql 3.x
	// The approach used in previous versions needed the user to
	// have database lock and create tmp table priviledges. 
	// This uses more queries and manual DISTINCT code, but it works with just select privs.
	$ping = (
		!$hide_pingbacks_and_trackbacks
		? ''
		: " AND comment_type<>'pingback' AND comment_type<>'trackback'"
		);
	$posts = $wpdb->get_results(
		 "SELECT DISTINCT"
		."  comment_post_ID"
		." ,post_title"
		." ,comment_count"
		." ,MAX(comment_date) AS comment_date"
		." FROM ($wpdb->comments LEFT JOIN $wpdb->posts ON (comment_post_ID = ID))"
		." WHERE comment_approved = '1'"
		." AND $wpdb->posts.post_status='publish'"
		.$ping
		." GROUP BY comment_post_ID, post_title, comment_count"
		." ORDER BY 4 DESC"
		." LIMIT $num_posts;"
		);
	$seen = array();
	$num = 0;

	if($fade_old) {
		$max_time = $range_in_days * 24 * 60 * 60 ; 

		$r_new = hexdec(substr($new_col, 1, 2));
		$r_old = hexdec(substr($old_col, 1, 2));
		$r_range = ($r_old-$r_new);

		$g_new = hexdec(substr($new_col, 3, 2));
		$g_old = hexdec(substr($old_col, 3, 2));
		$g_range = ($g_old-$g_new);

		$b_new = hexdec(substr($new_col, 5, 2));
		$b_old = hexdec(substr($old_col, 5, 2));
		$b_range = ($b_old-$b_new);
	}

	$out_text = '';
	foreach($posts as $post) {
		// The following 5 lines is a manual DISTINCT and LIMIT,
		// since mysql 3.x doesn't allow you to control which way a DISTINCT
		// select merges multiple entries.
		if(array_key_exists($post->comment_post_ID, $seen))
			continue;
		$seen[$post->comment_post_ID] = true;	
		if($num++ > $num_posts)
			break;

		$commenters = $wpdb->get_results(
				 "SELECT"
				."  comment_ID"
				." ,comment_post_ID"
				." ,comment_author"
				." ,comment_date"
				." ,UNIX_TIMESTAMP(comment_date) AS unixdate"
				." FROM $wpdb->comments"
				." WHERE comment_approved = '1'"
				." AND comment_post_ID = '$post->comment_post_ID'"
				.$ping
				." ORDER BY comment_date DESC"
				." LIMIT $num_comments;"
				);
		$count = $post->comment_count;
		$i = 0;
		$link = get_permalink($post->comment_post_ID);
		$title = (
			$usetimesince
			? ' title="Last comment was '.time_since($comment->unixdate).' ago"'
			: ''
			);

		$out_text .= $prefix;

		// echo post title & permalink
		$out_text .= "<a href=\"{$link}\"$title class=\"activityentry\">"
			.stripslashes($post->post_title)
			."</a>"
			."&nbsp;&nbsp;"
			."<a href=\"{$link}#comments\" title=\"Go to the comments of this entry\">"
//			."({$count})"
			."</a>"
			."<br />\n";

		// echo commenters
		$out_text .= "<small>";
		foreach($commenters as $commenter) {
			if($usetimesince)
				$title = " title=\"Posted ".time_since($commenter->unixdate)." ago\"";
			if($fade_old) {
				$diff = time() - $commenter->unixdate;
				$r = round($diff/$max_time*($r_range))+$r_new; 
				$r = max(min($r_new, $r_old), min(max($r_new, $r_old), $r));
				$g = round($diff/$max_time*($g_range))+$g_new; 
				$g = max(min($g_new, $g_old), min(max($g_new, $g_old), $g));
				$b = round($diff/$max_time*($b_range))+$b_new; 
				$b = max(min($b_new, $b_old), min(max($b_new, $b_old), $b));
				$r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
				$g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
				$b_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
				$colstr = " style=\"color: #".$r_hex.$g_hex.$b_hex.";\"";
			}
			if($i++ > 0)
				$out_text .= ", ";
			$out_text .= "<a{$colstr} href=\"{$link}#comment-{$commenter->comment_ID}\"{$title}>".stripslashes($commenter->comment_author)."</a>";
		}
		if($count > $num_comments) 
			$out_text .= " <a href=\"{$link}#comments\" title=\"Go to the comments of this entry\">[...]</a>";
		$out_text .= "</small>";

		$out_text .= $postfix."\n";
	}
	$options = compact(
		 'num_posts'
		,'num_comments'
		,'hide_pingbacks_and_trackbacks'
		,'prefix'
		,'postfix'
		,'fade_old'
		,'range_in_days'
		,'new_col'
		,'old_col'
		,'out_text'
		);
	update_option('blc_latest_comments', $options);
	unset($options);
	return $out_text;
}
add_action('wp_set_comment_status', create_function('$comment_id','blc_get_latest_comments(); return true;'));
add_action('wp_update_comment_count', create_function('$post_id','blc_get_latest_comments(); return true;'));
