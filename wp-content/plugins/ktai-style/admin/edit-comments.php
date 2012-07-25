<?php
/* ==================================================
 *   Ktai Admin Edit Comments
 *   based on wp-admin/edit-comments.php of WP 2.7
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$View = new KtaiAdmin_Edit_Comments($Ktai_Style);
exit();

/* ==================================================
 *   KtaiAdmin_Edit_Comments class
   ================================================== */

class KtaiAdmin_Edit_Comments {
	private $base;
	private $admin;
	private $post_id;
	private $post_status;
	private $referer;
	const HEADER_FOOTER_SIZE = 3000;
	const COMMENT_EXCERPT_SIZE = 200;
	const COMMENT_AUTHOR_URL_LENGTH = 50;
	const COMMENT_NAV_SIZE = 400;
	const COMMENTS_PER_PAGE = 20;

// ==================================================
public function __construct($base) {
	$this->base  = $base;
	$this->admin = $base->admin;
	$this->post_id = isset($_REQUEST['p']) ? intval($_REQUEST['p']) : 0;
	$this->referer = remove_query_arg( array('approved', 'unapproved', 'spammed', 'unspammed', 'trashed', 'untrashed', 'deleted', 'ids'), $this->base->strip_host($this->admin->get_referer()) );

	if ( isset( $_REQUEST['delete_all'] ) && !empty( $_REQUEST['pagegen_timestamp'] ) ) {
		check_admin_referer('bulk-comments');
		$comment_status = isset($_POST['comment_status']) ? $_POST['comment_status'] : $_GET['comment_status'];
		$delete_time = isset($_POST['pagegen_timestamp']) ? $_POST['pagegen_timestamp'] : $_GET['pagegen_timestamp'];
		$comment_ids = $wpdb->get_col($wpdb->prepare("SELECT comment_ID FROM `$wpdb->comments` WHERE comment_approved = %s AND %s> comment_date_gmt", $comment_status, $delete_time));
		$this->bulk_comments('delete', $comment_id);
		exit;
	} elseif ( isset($_REQUEST['delete_comments']) && -1 != $_REQUEST['action'] ) {
		check_admin_referer('bulk-comments');
		$this->bulk_comments($_REQUEST['action'], $_REQUEST['delete_comments']);
		exit;
	} elseif ( $_REQUEST['doaction'] == 'undo' && isset($_REQUEST['ids']) ) {
		$comment_ids = array_map( 'absint', explode(',', $_REQUEST['ids']) );
		$this->bulk_comments($_REQUEST['action'], $comment_ids);
		exit;
	} elseif ( isset($_GET['_wp_http_referer']) && ! empty($_GET['_wp_http_referer']) ) {
		 $this->admin->redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI']) ) );
		 exit;
	}
	$this->output();
	include dirname(__FILE__) . '/admin-footer.php';
}

// ==================================================
private function bulk_comments($doaction, $comment_ids) {
	global $wpdb;

	$approved = $unapproved = $spammed = $unspammed = $trashed = $untrashed = $deleted = 0;
	foreach ( (array) $comment_ids as $comment_id) : // Check the permissions on each
		$_post_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID = %d", $comment_id) );

		if ( !current_user_can('edit_post', $_post_id) ) {
			continue;
		}
		switch( $doaction ) {
			case 'approve' :
				wp_set_comment_status($comment_id, 'approve');
				$approved++;
				break;
			case 'unapprove' :
				wp_set_comment_status($comment_id, 'hold');
				$unapproved++;
				break;
			case 'spam' :
			case 'markspam' :
				if (function_exists('wp_spam_coment')) {
					wp_spam_comment($comment_id);
				} else {
					wp_set_comment_status($comment_id, 'spam');
				}
				$spammed++;
				break;
			case 'unspam' :
				if (function_exists('wp_unspam_comment')) {
					wp_unspam_comment($comment_id);
					$unspammed++;
				}
				break;
			case 'trash' :
				if (function_exists('wp_trash_comment')) {
					wp_trash_comment($comment_id);
					$trashed++;
				}
				break;
			case 'untrash' :
				if (function_exists('wp_untrash_comment')) {
					wp_untrash_comment($comment_id);
					$untrashed++;
				}
				break;
			case 'delete' :
				if (function_exists('wp_delete_comment')) {
					wp_delete_comment($comment_id);
				} else {
					wp_set_comment_status($comment_id, 'delete');
				}
				$deleted++;
				break;
		}
	endforeach;

	$redirect_to = $this->referer;
	if ( false === strpos($redirect_to, 'edit-comments.php') ) {
		$redirect_to = 'edit-comments.php';
	}
	if ( $approved )
		$redirect_to = add_query_arg( 'approved', $approved, $redirect_to );
	if ( $unapproved )
		$redirect_to = add_query_arg( 'unapproved', $unapproved, $redirect_to );
	if ( $spammed )
		$redirect_to = add_query_arg( 'spammed', $spammed, $redirect_to );
	if ( $unspammed )
		$redirect_to = add_query_arg( 'unspammed', $unspammed, $redirect_to );
	if ( $trashed )
		$redirect_to = add_query_arg( 'trashed', $trashed, $redirect_to );
	if ( $untrashed )
		$redirect_to = add_query_arg( 'untrashed', $untrashed, $redirect_to );
	if ( $deleted )
		$redirect_to = add_query_arg( 'deleted', $deleted, $redirect_to );
	if ( $trashed || $spammed )
		$redirect_to = add_query_arg( 'ids', join(',', $comment_ids), $redirect_to );

	if ( $this->post_id > 0 ) {
		$redirect_to = add_query_arg( 'p', $this->post_id, $redirect_to );
	}
	if ( isset($_REQUEST['apage']) ) {
		$redirect_to = add_query_arg( 'apage', abs(intval($_REQUEST['apage'])), $redirect_to );
	}
	if ( !empty($_REQUEST['mode']) ) {
		$redirect_to = add_query_arg('mode', $_REQUEST['mode'], $redirect_to);
	}
	if ( !empty($_REQUEST['comment_status']) ) {
		$redirect_to = add_query_arg('comment_status', 
	$_REQUEST['comment_status'], $redirect_to);
	}	
	if ( !empty($_REQUEST['s']) ) {
		$redirect_to = add_query_arg('s', $_REQUEST['s'], $redirect_to);
	}
	$this->admin->redirect($redirect_to);
}

// ==================================================
private function comments_stati($num_comments, $comment_status, $comment_type) {
	$stati = array(
		'all' => _n_noop('All', 'All'), // singular not used
		'moderated' => _n_noop('Pending (%s)', 'Pending (%s)'),
		'approved' => _n_noop('Approved', 'Approved'), // singular not used
		'spam' => _n_noop('Spam (%s)', 'Spam (%s)'),
	);
	if (function_exists('wp_trash_comment') && EMPTY_TRASH_DAYS > 0) { // must have _n_noop()
		$stati['trash'] = _n_noop('Trash (%s)', 'Trash (%s)');
	}

	global $parent_file, $submenu, $submenu_file;
	$link = $submenu_file = 'edit-comments.php';
	if ( !empty($comment_type) && 'all' != $comment_type )
		$link = add_query_arg( 'comment_type', $comment_type, $link );
	foreach ( $stati as $status => $label ) {
		if ( !isset( $num_comments->$status ) ) {
			$num_comments->$status = 10;
		}
		if ( 'all' != $status ) {
			$link = add_query_arg( 'comment_status', $status, $link );
		}
		if ( $this->post_id )
			$link = add_query_arg( 'p', abs(intval( $this->post_id )), $link );
		$submenu[$parent_file][] = array(sprintf(
			_n( $label[0], $label[1], $num_comments->$status, 'ktai_style' ), 
			number_format_i18n( $num_comments->$status )), 
			'edit_posts', $link);
		if ( str_replace( 'all', '', $status ) == $comment_status )
			$submenu_file = $link;
	}
	return;
}

// ==================================================
private function get_post_title($id) {
	if ($id > 0) {
		$post = get_post($id, OBJECT, 'display');
		$post_title = esc_html( $post->post_title, 'double' );
		$post_title = ('' == $post_title) ? "# $comment->comment_post_ID" : $post_title;
	} else {
		$post_title = NULL;
	}
	return $post_title;
}

// ==================================================
private function comment_list_item($_comment) {
	global $comment;
	$comment = $_comment;

	$comment_status = wp_get_comment_status($comment->comment_ID);
	if ( 'unapproved' == $comment_status ) {
		$gray_start = '<font color="gray">';
		$gray_end   = '</font>';
	} else {
		$gray_start = '';
		$gray_end   = '';
	}
	$checkbox = '';
	if (current_user_can('edit_post', $comment->comment_post_ID)) {
		$checkbox = '<input type="checkbox" name="delete_comments[]" value="' . intval($comment->comment_ID) . '" />';
	}
	echo '<dt>';
	printf(_c('%1$s%2$d:%3$s|comment-list-title', 'ktai_style'), 
		$checkbox, 
		$comment->comment_ID, 
		$gray_start . mb_strcut(get_comment_excerpt(), 0, self::COMMENT_EXCERPT_SIZE) . $gray_end
	);
	echo '</dt><dd>';?>
<img localsrc="46" alt=" @ " /><font color="<?php echo ks_option('ks_date_color'); ?>"><?php echo ks_comment_datetime(); ?></font>
<img localsrc="<?php comment_type(68, 112, 112); ?>" alt="[<?php comment_type(__('Comment', 'ktai_style'), __('Trackback'), __('Pingback')); ?>] " /><?php comment_author(); ?><br /><?php 
	if ( strlen($author_url_display) > self::COMMENT_AUTHOR_URL_LENGTH )
		$author_url_display = substr($author_url_display, 0, self::COMMENT_AUTHOR_URL_LENGTH -1) . '...';
	if ($comment->comment_author_url && 'http://' != $comment->comment_author_url) {
		$author_url_display = $comment->comment_author_url;
		$author_url_display = str_replace('http://www.', '', $author_url_display);
		$author_url_display = str_replace('http://', '', $author_url_display);
		if ( strlen($author_url_display) > self::COMMENT_AUTHOR_URL_LENGTH )
			$author_url_display = substr($author_url_display, 0, self::COMMENT_AUTHOR_URL_LENGTH -1) . '...';
		?><img localsrc="112" alt="" /><font color="olive"><?php echo esc_html($author_url_display); ?></font><br /><?php 
	}
	if ($comment->comment_author_email && current_user_can('edit_post', $comment->comment_post_ID)) { 
		?><img localsrc="108" alt="" /><font color="olive"><?php comment_author_email(); ?></font><br /><?php
	}
	if ( !$this->post_id && $title = $this->get_post_title($comment->comment_post_ID)) {
		?><font color="gray" size="-1"><img localsrc="97" /><?php echo $title; ?></font><br /><?php
	}
	$app_nonce = esc_html( '&_wpnonce=' . wp_create_nonce( "approve-comment_$comment->comment_ID" ) );
	$del_nonce = esc_html( '&_wpnonce=' . wp_create_nonce( "delete-comment_$comment->comment_ID" ) );
	$approve_url = esc_url('comment.php?action=approvecomment&p=' . $comment->comment_post_ID . '&c=' . $comment->comment_ID. $app_nonce);
	$unapprove_url = esc_url('comment.php?action=unapprovecomment&p=' . $comment->comment_post_ID . '&c=' . $comment->comment_ID. $app_nonce);
	$spam_url = esc_url('comment.php?action=spamcomment&p=' . $comment->comment_post_ID . '&c=' . $comment->comment_ID . $del_nonce);
	$unspam_url = esc_url('comment.php?action=unspamcomment&p=' . $comment->comment_post_ID . '&c=' . $comment->comment_ID . $del_nonce);
	$trash_url = esc_url('comment.php?action=trashcomment&p=' . $comment->comment_post_ID . '&c=' . $comment->comment_ID . $del_nonce);
	$untrash_url = esc_url('comment.php?action=untrashcomment&c=' . $comment->comment_ID . $del_nonce);
	$delete_url = esc_url('comment.php?action=deletecomment&p=' . $comment->comment_post_ID . '&c=' . $comment->comment_ID); // do not add nonce
	$edit_url = esc_url('comment.php?action=editcomment&c=' . $comment->comment_ID);
	$reply_url = esc_url('comment-reply.php?replytocom=' . $comment->comment_ID);
	$action_template_color = '<a href="%s"><font color="%s">%s</font></a>';
	$action_template = '<a href="%s">%s</a>';
	if ( current_user_can('edit_post', $comment->comment_post_ID) ) {
		$actions = array();
		if (current_user_can('moderate_comments')) {
			if ('unapproved' == $comment_status || 'moderated' == $comment_status) {
				$actions['approve'] = sprintf($action_template_color, $approve_url, 'green', __('Approve'));
				$actions['spam'] = sprintf($action_template_color, $spam_url, 'red', __('Spam'));
			} elseif ('approved' == $_GET['comment_status'] && 'approved' == $comment_status) {
				$actions['unapprove'] = sprintf($action_template_color, $unapprove_url, '#ff9900', __('Unapprove'));
			} elseif ('spam' == $comment_status ) {
				if (function_exists('wp_trash_comment') && EMPTY_TRASH_DAYS) {
					$actions['unspam'] = sprintf($action_template_color, $unspam_url, 'orange', __('Restore'));
					$actions['trash'] = sprintf($action_template_color, $trash_url, 'red', __('Trash'));
				} else {
					$actions['approve'] = sprintf($action_template_color, $approve_url, 'green', __('Approve'));
					$actions['delete'] = sprintf($action_template_color, $delete_url, 'red', __('Delete'));
				}
			}
		}
		if ( 'trash' == $comment_status ) {
			$actions['restore'] = sprintf($action_template_color, $untrash_url, 'green', __('Restore'));
			$actions['delete'] = sprintf($action_template_color, $delete_url, 'red', __('Delete'));
		} elseif ('spam' != $comment_status ) {
			$actions['edit'] = '<img localsrc="104" alt="" />' . sprintf($action_template, $edit_url, __('Edit'));
			if ( ('approved' == $comment_status) && (NULL == $this->post_status || 'published' == $this->post_status) ) {
				$actions['reply'] = '<img localsrc="149" alt="" />' . sprintf($action_template, $reply_url, __('Reply', 'ktai_style'));	
			}
		}
		if ($actions) {
			$actions = apply_filters( 'comment_row_actions', array_filter($actions), $comment );
			echo '[ ' . implode(' | ', $actions) . ' ]';
		}
	}
}

// ==================================================
private function output() {
	global $title, $parent_file, $wpdb;
	if ($this->post_id > 0) {
		$title = sprintf(__('Comments for %s', 'ktai_style'), wp_html_excerpt($this->get_post_title($this->post_id), 50));
		$this->post_status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM $wpdb->posts WHERE ID = %d", $this->post_id));
	} else {
		$title = __('Edit Comment');
		$this->post_status = NULL;
	}
	$parent_file = 'edit-comments.php';
	include dirname(__FILE__) . '/admin-header.php';
	
	$mode = ( ! isset($_GET['mode']) || empty($_GET['mode']) ) ? 'detail' : esc_attr($_GET['mode']);
	$comment_status = !empty($_GET['comment_status']) ? esc_attr($_GET['comment_status']) : '';
	$comment_type = !empty($_GET['comment_type']) ? esc_attr($_GET['comment_type']) : '';
	$search_dirty = ( isset($_GET['s']) ) ? $_GET['s'] : '';
	$search = stripslashes($search_dirty);
	
	if ($search_dirty) {
		printf('<h2>' . __('Search results for &#8220;%s&#8221;', 'ktai_style') . '</h2>', esc_attr($search));
	}
	
	if ( isset($_GET['approved']) || isset($_GET['deleted']) || isset($_GET['trashed']) || isset($_GET['untrashed']) || isset($_GET['spammed']) || isset($_GET['unspammed']) ) {
		$approved  = isset($_GET['approved'])  ? (int) $_GET['approved']  : 0;
		$deleted   = isset($_GET['deleted'])   ? (int) $_GET['deleted']   : 0;
		$trashed   = isset($_GET['trashed'])   ? (int) $_GET['trashed']   : 0;
		$untrashed = isset($_GET['untrashed']) ? (int) $_GET['untrashed'] : 0;
		$spammed   = isset($_GET['spammed'])   ? (int) $_GET['spammed']   : 0;
		$unspammed = isset($_GET['unspammed']) ? (int) $_GET['unspammed'] : 0;
	
		if ( $approved > 0 || $deleted > 0 || $trashed > 0 || $untrashed > 0 || $spammed > 0 || $unspammed > 0 ) {
			echo '<p><font color="olive">';
	
			if ( $approved > 0 ) {
				printf( _n( '%s comment approved.', '%s comments approved.', $approved, 'ktai_style'), $approved );
				echo '<br />';
			}
			if ( $spammed > 0 ) {
				printf( _n( '%s comment marked as spam.', '%s comments marked as spam.', $spammed , 'ktai_style'), $spammed );
				if (isset($_GET['ids']) && function_exists('wp_unspam_comment')) {
					$undo_url = 'edit-comments.php?doaction=undo&action=unspam&ids=' . $_GET['ids'];
					$undo_url = wp_nonce_url($undo_url, 'bulk-comments'); // does html escape
					printf(' <a href="%s">%s</a><br />', $undo_url, __('Undo'));
				}
			}
			if ( $unspammed > 0 ) {
				printf( _n( '%s comment restored from the spam', '%s comments restored from the spam', $unspammed , 'ktai_style'), $unspammed );
				echo '<br />';
			}
			if ( $trashed > 0 ) {
				printf( _n( '%s comment moved to the trash.', '%s comments moved to the trash.', $trashed , 'ktai_style'), $trashed );
				if (isset($_GET['ids']) && function_exists('wp_untrash_comment')) {
					$undo_url = 'edit-comments.php?doaction=undo&action=untrash&ids=' . $_GET['ids'];
					$undo_url = wp_nonce_url($undo_url, 'bulk-comments'); // does html escape
					printf('<a href="%s">%s</a><br />', $undo_url, __('Undo'));
				}
			}
			if ( $untrashed > 0 ) {
				printf( _n( '%s comment restored from the trash.', '%s comments restored from the trash.', $untrashed, 'ktai_style'), $untrashed );
				echo '<br />';
			}
			if ( $deleted > 0 ) {
				printf( _n( '%s comment deleted.', '%s comments deleted.', $deleted, 'ktai_style'), $deleted );
				echo '<br />';
			}
			echo '</font></p>';
		}
	}
	
	if ( !$this->base->admin_available_wp_upper() ) {
		return;
	}
	if ($this->post_id > 0) {
		$num_comments = wp_count_comments($this->post_id);
	} else {
		$num_comments = wp_count_comments();
	}
	$this->comments_stati($num_comments, $comment_status, $comment_type);
	
	$comments_per_page = intval(($this->base->get('page_size') - self::HEADER_FOOTER_SIZE) / (self::COMMENT_EXCERPT_SIZE + self::COMMENT_NAV_SIZE));
	$comments_per_page = apply_filters('comments_per_page', $comments_per_page, $comment_status);
	if ($comments_per_page < 1) {
		$comments_per_page = 1;
	} elseif ($comments_per_page > self::COMMENTS_PER_PAGE) {
		$comments_per_page = self::COMMENTS_PER_PAGE;
	}
	if (isset($_GET['filter'])) {
		$page = 1;
	} else {
		$page = isset($_GET['apage']) ? abs((int) $_GET['apage']) : 1;
	}
	$start = $offset = ( $page - 1 ) * $comments_per_page;
	
	list($_comments, $total) = _wp_get_comment_list( $comment_status, $search_dirty, $start, $comments_per_page + 8, $this->post_id, $comment_type ); // Grab a few extra
	$_comment_post_ids = array();
	foreach ( $_comments as $_c ) {
		$_comment_post_ids[] = $_c->comment_post_ID;
	}
	$_comment_pending_count_temp = (array) get_pending_comments_num($_comment_post_ids);
	foreach ( (array) $_comment_post_ids as $_cpid ) {
		$_comment_pending_count[$_cpid] = isset( $_comment_pending_count_temp[$_cpid] ) ? $_comment_pending_count_temp[$_cpid] : 0;
	}
	if ( empty($_comment_pending_count) ) {
		$_comment_pending_count = array();
	}	
	$comments = array_slice($_comments, 0, $comments_per_page);
	$extra_comments = array_slice($_comments, $comments_per_page);
	
	$max_pages = ceil($total / $comments_per_page);
	$this->admin->nav_dropdown($page, $max_pages, 'apage');
	?><form action="" method="get"><div>
	<?php $this->admin->sid_field();
	_e('Search'); ?><input type="text" name="ks" value="<?php echo esc_attr($search); ?>" size="20" />
	<input type="hidden" name="mode" value="<?php echo $mode; ?>" />
	<?php if ( $this->post_id ) {
		echo '<input type="hidden" name="p" value="' . intval($this->post_id) . '" />';
	}
	if ( $comment_status ) {
		echo '<input type="hidden" name="comment_status" value="' . $comment_status . '" />';
	} ?>
	<input type="hidden" name="pagegen_timestamp" value="<?php echo current_time('mysql', 1); ?>" />
	<select name="comment_type">
	<option value="all"><?php _e('All comment types', 'ktai_style'); ?></option>
	<?php
		$comment_types = apply_filters( 'admin_comment_types_dropdown', array(
			'comment' => __('Comments'),
			'pings' => __('Pings', 'ktai_style'),
		) );
		foreach ( $comment_types as $type => $label ) {
			echo '<option value="' . esc_attr($type) . '"';
			selected( $comment_type, $type );
			echo '>' . esc_attr($label) . '</option>';
		}
	?>
	</select>
	<input type="submit" name="filter" value="<?php _e('Filter'); ?>" />
	<?php 
	// do_action('manage_comments_nav', $comment_status);
	?>
	</div>
	<?php 
	if ($comments) {
		echo '<dl>';
		foreach ($comments as $c) {
			$this->comment_list_item($c);
			echo '</dd>';
		}
		echo '</dl>';
		?><select name="action">
<option value="-1" selected="selected"><?php _e('Bulk Actions', 'ktai_style') ?></option><?php
		if ( empty($comment_status) || 'approved' == $comment_status ) {
			?><option value="unapprove"><?php _e('Unapprove'); ?></option><?php
		}
		if ( empty($comment_status) || 'unapproved' == $comment_status || 'moderated' == $comment_status || 'spam' == $comment_status ) {
			?><option value="approve"><?php _e('Approve'); ?></option><?php
		}
		if ( 'spam' != $comment_status ) {
			?><option value="markspam"><?php _e('Mark as Spam', 'ktai_style'); ?></option><?php
		}
		if ( 'trash' == $comment_status || 'spam' == $comment_status || !function_exists('wp_trash_comment') || !EMPTY_TRASH_DAYS ) {
			?><option value="delete"><?php _e('Delete'); ?></option><?php
		} else { 
			?><option value="trash"><?php _e('Move to Trash'); ?></option><?php
		}
		?></select>
<input type="submit" name="doaction" value="<?php _e('Apply'); ?>" /><?php 
		wp_nonce_field('bulk-comments');
		if ( ('spam' == $comment_status || 'trash' == $comment_status) && current_user_can ('moderate_comments') ) {
			if ($this->base->check_wp_version('2.9')) {
				wp_nonce_field('bulk-destroy', '_destroy_nonce');
			} else {
				wp_nonce_field('bulk-spam-delete', '_spam_nonce');
			}
			if ('spam' == $comment_status) {
				?><input type="submit" name="delete_all" value="<?php _e('Empty Spam', 'ktai_style'); ?>" /><?php
			} else {
				?><input type="submit" name="delete_all" value="<?php _e('Empty Trash'); ?>" /><?php
			}
		}
	} elseif ( 'moderated' == $_GET['comment_status'] ) {
		?><p><?php _e('No comments awaiting moderation&hellip; yet.'); ?></p><?php 
	} else {
		?><p><?php _e('No results found.'); ?></p><?php
	}
	?></form><?php 
	$this->admin->nav_link($page, $max_pages, 'apage');
	if ($this->post_id > 0 && preg_match('!/(edit|post)\.php($|\?)!', $referer = $this->admin->get_referer())) {
		echo '<div>' . sprintf(__('<a href="%s">Back to lists</a>', 'ktai_style'), $referer) . '</div>';
	}
}

// ===== End of class ====================
}
?>