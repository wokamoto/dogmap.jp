<?php
/* ==================================================
 *   Ktai Admin Dashboard
 *   based on wp-admin/includes/dashboard.php of WP 2.7
   ================================================== */

define('KTAI_DRAFT_LENGTH', 140);

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$View = new KtaiAdmin_Dashboard($Ktai_Style, 1);
global $title, $parent_file;
$title = __('Dashboard');
$parent_file = './';
include dirname(__FILE__) . '/admin-header.php';
$View->create();
$View->right_now();
$View->recent_comments();
$View->recent_drafts();

//do_action('activity_box_end');
include dirname(__FILE__) . '/admin-footer.php';
exit();

/* ==================================================
 *   KtaiAdmin_Dashboard class
   ================================================== */

class KtaiAdmin_Dashboard {
	private $base;
	private $admin;
	private $accesskey;

// ==================================================
public function __construct($base, $accesskey) {
	$this->base  = $base;
	$this->admin = $base->admin;
	$this->accesskey = absint($accesskey);
}

// ==================================================
public function create() {
	$links = array();
	if (current_user_can('edit_posts')) {
		$links[] = '<a href="post-new.php">' . __('Add New Post') . '</a>';
	}
	if (current_user_can('edit_pages')) {
		$links[] = '<a href="page-new.php">' . __('Add New Page') . '</a>';
	}
	if ($links) {
		echo '<p>[' . implode(' | ', $links) . ']</p>' . "\n";
	}
	return;
}

// ==================================================
public function right_now() { ?>
<h2><?php _e('Right Now'); ?></h2>
<?php
	$num_posts = wp_count_posts('post');
	$num_pages = wp_count_posts('page');
	$num_cats  = wp_count_terms('category');
	$num_tags  = wp_count_terms('post_tag');
	$num_comm  = wp_count_comments();
	echo '<dl>';

	// Posts
	echo '<dt>';
	$format = __('Posts: %link', 'ktai_style');
	$num = number_format_i18n($num_posts->publish);
	if ( current_user_can('edit_posts') && $num_posts->publish > 0) {
		ks_ordered_link($this->accesskey, 10, 'edit.php', $num, $format);
	} else {
		ks_ordered_link(-1, 10, NULL, $num, $format);
	}
	$this->accesskey++;
	echo '</dt>';

	// Pages
	echo '<dt>';
	$format = __('Pages: %link', 'ktai_style');
	$num = number_format_i18n($num_pages->publish);
	if ( current_user_can('edit_pages') && $num_pages->publish > 0) {
		ks_ordered_link($this->accesskey, 10, 'edit-pages.php', $num, $format);
	} else {
		ks_ordered_link(-1, 10, NULL, $num, $format);
	}
	$this->accesskey++;
	echo '</dt>';
	
	// Categories
	echo '<dt>';
	$format = __('Categories: %link', 'ktai_style');
	$num = number_format_i18n($num_cats);
/*	if ( current_user_can('manage_categories') && $num_cats > 0) {
		ks_ordered_link($this->accesskey++, 10, 'categories.php', $num, $format);
	} else {
*/
		ks_ordered_link(-1, 10, NULL, $num, $format);
//	}
	$this->accesskey++;
	echo '</dt>';

	// Tags
	echo '<dt>';
	$format = __('Tags: %link', 'ktai_style');
	$num = number_format_i18n($num_tags);
/*	if ( current_user_can('manage_categories') && $num_tags > 0) {
		ks_ordered_link($this->accesskey++, 10, 'edit-tags.php', $num, $format);
	} else {
*/
		ks_ordered_link(-1, 10, NULL, $num, $format);
//	}
	$this->accesskey++;
	echo '</dt>' . "\n";

	// Total Comments
	echo '<dt>';
	$format = __('Comments: %link', 'ktai_style');
	$total_comments = isset($num_comm->total_comments) ? $num_comm->total_comments : intval($num_comm->approved + $num_comm->moderated + $num_comm->spam);
	$num = number_format_i18n($total_comments);
	if ( current_user_can('moderate_comments') && $total_comments > 0 && $this->base->admin_available_wp_upper() ) {
		ks_ordered_link($this->accesskey, 10, 'edit-comments.php', $num, $format);
	} else {
		ks_ordered_link(-1, 10, NULL, $num, $format);
	}
	$this->accesskey++;
	echo '</dt>';

	// Approved Comments
	$string = sprintf(__('<font color="%s">Approved</font>', 'ktai_style'), 'green');
	$num = number_format_i18n($num_comm->approved);
/*	if ( current_user_can('moderate_comments') && $num_comm->approved > 0) {
		$num = '<a href="edit-comments.php?comment_status=approved">' . $num . '</a>';
	}
*/
	echo '<dt><ul><li>' . sprintf(_c('%1$s: %2$s|comments count', 'ktai_style'), $string, $num) . '</li>' . "\n";

	// Pending Comments
	$string = sprintf(__('<font color="%s">Pending</font>', 'ktai_style'), '#ff9900');
	$num = number_format_i18n($num_comm->moderated);
	if ( current_user_can('moderate_comments') && $num_comm->moderated > 0 && $this->base->admin_available_wp_upper() ) {
		$num = '<a href="edit-comments.php?comment_status=moderated">' . $num . '</a>';
	}
	echo '<li>' . sprintf(_c('%1$s: %2$s|comments count', 'ktai_style'), $string, $num) . '</li>' . "\n";

	// Spam Comments
	$string = sprintf(__('<font color="%s">Spam</font>', 'ktai_style'), 'red');
	$num = number_format_i18n($num_comm->spam);
	if ( current_user_can('moderate_comments') && $num_comm->spam > 0 && $this->base->admin_available_wp_upper() ) {
		$num = '<a href="edit-comments.php?comment_status=spam">' . $num . '</a>';
	}
	echo '<li>' . sprintf(_c('%1$s: %2$s|comments count', 'ktai_style'), $string, $num) . '</li></ul></dt></dl>' . "\n";
	return;
}

// ==================================================
public function recent_drafts($drafts = false) { ?>
<h2><?php _e('Recent Drafts', 'ktai_style'); ?></h2>
<?php
	if (! $drafts) {
		$drafts_query = new WP_Query( array(
			'post_type' => 'post',
			'what_to_show' => 'posts',
			'post_status' => 'draft',
			'author' => $GLOBALS['current_user']->ID,
			'posts_per_page' => 5,
			'orderby' => 'modified',
			'order' => 'DESC'
		) );
		$drafts =& $drafts_query->posts;
	}

	if ( $drafts && is_array($drafts) ) {
		global $Ktai_Style;
		echo '<dl>';
		foreach ( $drafts as $draft ) {
			$url = get_edit_post_link($draft->ID, 'url');
			$title = $this->admin->draft_or_post_title( $draft->ID );
			echo '<dt>';
			ks_ordered_link($this->accesskey++, 10, $url, $title);
			_ks_timestamp(get_the_time('U', $draft));
			echo '</dt>';
			if ($the_content = ks_cut_html(strip_tags($draft->post_content), KTAI_DRAFT_LENGTH) ) {
				echo '<dd>' . $the_content . '</dd>';
			}
		}
	?>
</dl>
<div align="right"><img localsrc="63" alt="&#8594;" /><a href="edit.php?post_status=draft"><?php _e('View all', 'ktai_style'); ?></a></div>
<?php
	} else { ?>
<p><?php _e('There are no drafts at the moment', 'ktai_style');?></p>
<?php }
	return;
}

// ==================================================
public function recent_comments() { ?>
<h2><?php echo _c('Recent Comments|dashboard', 'ktai_style'); ?></h2>
<?php
	global $wpdb;

	if ( current_user_can('edit_posts') ) {
		$allowed_states = array('0', '1');
	} else {
		$allowed_states = array('1');
	}

	// Select all comment types and filter out spam later for better query performance.
	$comments = array();
	$start = 0;

	while ( count( $comments ) < 5 && $possible = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$wpdb->comments` ORDER BY comment_date_gmt DESC LIMIT %d, 50", $start)) ) {
		foreach ( $possible as $comment ) {
			if ( count( $comments ) >= 5 ) {
				break;
			} elseif (in_array($comment->comment_approved, $allowed_states)) {
				$comments[] = $comment;
			}
		}
		$start = $start + 50;
	}
	if ($comments) { ?>
<dl>
		<?php foreach ($comments as $comment) {
			$this->recent_comments_row($comment, $this->accesskey);
		} ?>
</dl>
		<?php if ( current_user_can('edit_posts') ) { ?>
<div align="right"><img localsrc="63" alt="&#8594;" /><a href="edit-comments.php"><?php _e('View all', 'ktai_style'); ?></a></div>
<?php	}
	} else { ?>
<p><?php _e('No comments yet.', 'ktai_style'); ?></p>
<?php } // $comments;
	return;
}

// ==================================================
public function recent_comments_row( &$comment ) {
	$show_date = true;
	$GLOBALS['comment'] =& $comment;

	$comment_post_title = get_the_title( $comment->comment_post_ID );
	$edit_url = sprintf('comment.php?action=editcomment&c=%d', $comment->comment_ID);
	$reply_url = sprintf('comment-reply.php?replytocom=%d', $comment->comment_ID);
	if ( !$comment->comment_type || 'comment' == $comment->comment_type ) : 
		$desc = sprintf(__('From %s on %%link', 'ktai_style'), get_comment_author());
	else :
		switch ( $comment->comment_type ) :
		case 'pingback' :
			$type = __( 'Pingback' );
			break;
		case 'trackback' :
			$type = __( 'Trackback' );
			break;
		default :
			$type = ucwords( $comment->comment_type );
		endswitch;
		$desc = sprintf(__('%s on %%link', 'ktai_style'), wp_specialchars($type));
	endif; ?>
<dt><?php ks_ordered_link($this->accesskey++, 9, NULL, $comment_post_title, $desc); ?></dt>
<?php 
	if (current_user_can('edit_post', $comment->comment_post_ID)) { ?>
<dd>[ <img localsrc="149" alt="" /><a href="<?php echo esc_attr($reply_url);?>"><?php _e('Reply', 'ktai_style'); ?></a>
 | <img localsrc="104" alt="" /><a href="<?php echo esc_attr($edit_url); ?>"><?php  _e('Edit'); ?></a> ]</dd>
<?php }
}

// ===== End of class ====================
}
?>