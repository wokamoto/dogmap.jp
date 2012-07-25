<?php
/* ==================================================
 *   Ktai Admin Edit Post
 *   based on wp-admin/edit.php of WP 2.3, 2.7
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$title = __('Edit Posts', 'ktai_style');
$parent_file = 'edit.php';
$View = new KtaiAdmin_Edit_Posts($Ktai_Style);
$View->output();
exit();

/* ==================================================
 *   KtaiAdmin_Edit_Posts class
   ================================================== */

class KtaiAdmin_Edit_Posts {
	private $base;
	private $admin;
	private $year;
	private $monthnum;
	private $cat;
	private $tag;
	private $search;
	private $is_trash;
	private $sendback;
	const HEADER_FOOTER_SIZE = 3000;
	const COLUMN_SIZE = 768;

// ==================================================
public function __construct($base) {
	$this->base  = $base;
	$this->admin = $base->admin;

	if ( isset($_GET['doaction']) || isset($_GET['delete_all']) ) {
		$this->bulk_action();
		$this->admin->redirect($this->sendback);
		exit;
	}

	$this->year = isset($_GET['year']) ? intval($_GET['year']) : '';
	if ($this->year < 1900 || $this->year > 2999) {
		$this->year = '';
	}
	$this->monthnum = isset($_GET['monthnum']) ? intval($_GET['monthnum']) : '';
	if ($this->monthnum < 1 || $this->monthnum > 12) {
		$this->monthnum = '';
	}
	$this->cat = isset($_GET['cat']) ? intval($_GET['cat']) : '';
	$this->tag = isset($_GET['tag']) ? $_GET['tag'] : '';
	$this->search = isset($_GET['s']) ? stripslashes($_GET['s']) : '';
	$this->is_trash = isset($_GET['post_status']) && $_GET['post_status'] == 'trash' && function_exists('wp_trash_post');
}

// ==================================================
private function edit_posts_query() {
	$post_stati  = array(	//	array( adj, noun )
		'publish' => array(__('Published'), __('Published posts'), _n_noop('Published (%s)', 'Published (%s)')),
		'future' => array(__('Scheduled'), __('Scheduled posts'), _n_noop('Scheduled (%s)', 'Scheduled (%s)')),
		'pending' => array(__('Pending Review'), __('Pending posts'), _n_noop('Pending Review (%s)', 'Pending Review (%s)')),
		'draft' => array(__('Draft'), _x('Drafts', 'manage posts header'), _n_noop('Draft (%s)', 'Drafts (%s)')),
		'private' => array(__('Private'), __('Private posts'), _n_noop('Private (%s)', 'Private (%s)')),
	);
	if (function_exists('wp_trash_post') && EMPTY_TRASH_DAYS) {
		$post_stati['trash'] = array(_x('Trash', 'post'), __('Trash posts'), _n_noop('Trash (%s)', 'Trash (%s)'));
	}
	$post_stati = apply_filters('post_stati', $post_stati);

	$per_page = intval(($this->base->get('page_size') - self::HEADER_FOOTER_SIZE) / self::COLUMN_SIZE);
	if ($per_page < 1) {
		$per_page = 1;
	} elseif ($per_page > 15) {
		$per_page = 15;
	}
	if (isset($_GET['filter'])) {
		$pagenum = 1;
	} else {
		$pagenum = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
	}

	$query = array(
		'post_type' => 'post',
		'what_to_show' => 'posts',
		'post_status' => 'any',
		'posts_per_page' => $per_page,
		'paged' => $pagenum, 
		'year' => $this->year,
		'monthnum' => $this->monthnum,
		'cat' => $this->cat,
		'tag' => $this->tag,
		'author' => intval($_GET['author']),
		's' => $this->search,
	);
	if ( isset($_GET['post_status'])) {
		$post_status = stripslashes($_GET['post_status']);
		if ( in_array($post_status, array_keys($post_stati)) ) {
			$query['post_status'] = $post_status;
			$query['perm'] = 'readable';
		}
	}
	if ( 'pending' === $_GET['post_status'] ) {
		$query['order']   = 'ASC';
		$query['orderby'] = 'modified';
	} elseif ( 'draft' === $_GET['post_status'] ) {
		$query['order']   = 'DESC';
		$query['orderby'] = 'modified';
	} else {
		$query['order']   = 'DESC';
		$query['orderby'] = 'date';
	}
	$query = apply_filters('manage_pages_query', $query);
	query_posts($query);

	return array($post_stati, $pagenum, $per_page);
}

// ==================================================
public function status_menu($post_stati) {
	global $parent_file, $submenu, $submenu_file;

	$num_posts = wp_count_posts('post', 'readable');
	$link = $submenu_file = 'edit.php';
	$submenu[$parent_file] = array(
		array(__('Add New Post'), 'edit_posts', 'post-new.php'),
		array(__('Any', 'ktai_style'), 'edit_posts', $link),
	);
	$post_status_label = __('Posts');

	$avail_post_stati = get_available_post_statuses('post');
	foreach ( $post_stati as $status => $label ) {
		if ( !in_array( $status, $avail_post_stati ) ) {
			continue;
		}
		if ( empty( $num_posts->$status ) ) {
			continue;
		}
		$link = add_query_arg( 'post_status', $status, $link );
		$submenu[$parent_file][] = array(sprintf(__ngettext( $label[2][0], $label[2][1], $num_posts->$status, 'ktai_style'), number_format_i18n($num_posts->$status)), 'edit_posts', $link);
		if ( str_replace( 'any', '', $status ) == $_GET['post_status'] ) {
			$submenu_file = $link;
			$post_status_label = $label[1];
		}
	}
	return $post_status_label;
}

// ==================================================
public function page_title($post_status_label) {
	global $user_ID, $post_listing_pageable, $wp_locale;
	if ($post_listing_pageable && ! is_archive() && ! is_search()) {
		$h2_noun = is_paged() ? sprintf(__( 'Previous %s' ), $post_status_label) : sprintf(__('Latest %s'), $post_status_label);
	} else {
		$h2_noun = $post_status_label;
	}
	// Use $_GET instead of is_ since they can override each other
	$h2_author = '';
	$_GET['author'] = intval($_GET['author']);
	if ( $_GET['author'] != 0 ) {
		if ( $_GET['author'] == '-' . $user_ID ) { // author exclusion
			$h2_author = ' ' . __('by other authors');
		} else {
			$author_user = get_userdata( $_GET['author'] );
			$h2_author = ' ' . sprintf(__('by %s', 'ktai_style'), wp_specialchars( $author_user->display_name ));
		}
	}
	$h2_search = $this->search ? ' ' . sprintf(__('matching &#8220;%s&#8221;', 'ktai_style'), wp_specialchars($this->search)) : '';
	$h2_cat = $this->cat ? ' ' . sprintf(__('in &#8220;%s&#8221;', 'ktai_style'), single_cat_title('', false)) : '';
	$h2_tag = $this->tag ? ' ' . sprintf(__('with tag &#8220;%s&#8221;', 'ktai_style'), single_tag_title('', false)) : '';
	$h2_m = $this->monthum ? $wp_locale->get_month($this->monthum) : '';
	$h2_y = $this->year ? $this->year : '';
	$h2_month = "$h2_m$h2_y" ? sprintf(__('during %1$s, %2$d', 'ktai_style'), $h2_m, $h2_y) : '';
//	return sprintf(_c('%1$s%2$s%3$s%4$s%5$s|You can reorder these: 1: Posts, 2: by {s}, 3: matching {s}, 4: in {s}, 5: during {s}'), $h2_noun, $h2_author, $h2_search, $h2_cat, $h2_month);
	if ($h2_author || $h2_search || $h2_cat || $h2_tag || $h2_month) {
		echo '<h2>' . sprintf(_c('%1$s%2$s%3$s%4$s%5$s%6$s|You can reorder these: 1: Posts, 2: by {s}, 3: matching {s}, 4: in {s}, 5: with {s}, 6: during {s}', 'ktai_style'), $h2_noun, $h2_author, $h2_search, $h2_cat, $h2_tag, $h2_month) . '</h2>';
	}
}

// ==================================================
public function author_menu() {
	global $user_ID;
	$editable_ids = get_editable_user_ids($user_ID);
	$html = '';
	if ( $editable_ids && count($editable_ids) >= 1 ) {
		if (function_exists('wp_dropdown_users')) {
			$html = __('Author');
			$html .= $this->base->filter_tags(wp_dropdown_users(array(
				'include' => $editable_ids, 
				'show_option_all' => __('Any', 'ktai_style'), 
				'name' => 'author', 
				'selected' => isset($_GET['author']) ? $_GET['author'] : 0, 
				'echo' => 0)));
		} else {
			$html = __('Post Author');
			$html .= '<select name="author"><option value="0">' . __('Any', 'ktai_style') . '</option>';
			foreach ($editable_ids as $e) {
				$a = get_userdata($e);
				if (isset($_GET['author']) && $_GET['author'] == $a->ID) { 
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
				$html .= '<option value="' . intval($e) . '"' . $selected . '>' . $a->display_name . '</option>';
			}
			$html .= '</select>';
		}
	}
	return $html;
}

// ==================================================
public function month_menu() {
	$html = sprintf(__('<label>Monthnum: <input type="text" name="monthnum" size="2" istyle="4" mode="numeric" value="%1$s" /></label>, <label>Year: <input type="text" name="year" size="4" istyle="4" mode="numeric" value="%2$s" /></label>', 'ktai_style'), $this->monthnum, $this->year);
	return $html;
}

/// ==================================================
public function output() {
	global $wp_query, $title, $parent_file;
	list($post_stati, $pagenum, $per_page) = $this->edit_posts_query();	

	include dirname(__FILE__) . '/admin-header.php';
	$post_status_label = $this->status_menu($post_stati);
	$this->page_title($post_status_label);
	if ( isset($_GET['posted']) && $_GET['posted'] ) {
		$_GET['posted'] = (int) $_GET['posted'];
		?><p><font color="teal"><?php _e('Your post has been saved.'); ?></font></p><?php
		$_SERVER['REQUEST_URI'] = remove_query_arg(array('posted'), $_SERVER['REQUEST_URI']);
	}
	if ( isset($_GET['deleted']) && intval($_GET['deleted']) ) {
		?><p><font color="teal"><?php 
		printf( _n( 'Post permanently deleted.', '%s posts permanently deleted.', intval($_GET['deleted']) , 'ktai_style'), number_format_i18n( intval($_GET['deleted']) ) );
		?></font></p><?php 
		unset($_GET['deleted']);
	}
	if ( isset($_GET['trashed']) && (int) $_GET['trashed'] ) { // WP 2.9 or later
		?><p><font color="teal"><?php 
		printf( _n( 'Post moved to the trash.', '%s posts moved to the trash.', intval($_GET['trashed']) ), number_format_i18n( $_GET['trashed'] ) );
		$ids = isset($_GET['ids']) ? $_GET['ids'] : 0;
		if ($ids && function_exists('wp_untrash_post') ) {
			printf(' <a href="%s">' . __('Undo') . '</a><br />', wp_nonce_url( "edit.php?doaction=undo&action=untrash&ids=$ids", 'bulk-posts') );
		}
		?></font></p><?php 
		unset($_GET['trashed']);
	}

	if ( isset($_GET['untrashed']) && (int) $_GET['untrashed'] ) { // WP 2.9 or later
		?><p><font color="teal"><?php
		printf( _n( 'Post restored from the trash.', '%s posts restored from the trash.', intval($_GET['untrashed']) ), number_format_i18n( intval($_GET['untrashed']) ) );
		?></font></p><?php 
		unset($_GET['undeleted']);
	}
	$this->admin->nav_dropdown($pagenum, $GLOBALS['wp_query']->max_num_pages);
?>
<form action="" method="get"><div><?php 
$this->admin->sid_field();
_e('Search'); ?><input type="text" name="ks" value="<?php echo wp_specialchars($this->search); ?>" size="17" /><br />
<?php
	$author_menu = $this->author_menu();
	if ($author_menu) {
		echo $author_menu . '<br />';
	}
	$month_menu = $this->month_menu();
	if ($month_menu) {
		echo $month_menu . '<br />';
	}
	if ( isset($_GET['post_status'] ) ) { ?>
<input type="hidden" name="post_status" value="<?php echo esc_attr($_GET['post_status']) ?>" />
<?php } ?>
<input type="submit" name="filter" value="<?php _e('Filter'); ?>" />
<?php if ($this->is_trash && current_user_can('edit_others_posts')) {
	wp_nonce_field('bulk-posts'); ?>
<br /><input type="submit" name="delete_all" id="delete_all" value="<?php esc_attr_e('Empty Trash'); ?>" />
<?php } ?>
</div></form>
<?php if (have_posts()) {
		$this->post_rows();
		$this->admin->nav_link($pagenum, $GLOBALS['wp_query']->max_num_pages);
	} else { ?>
<p><?php _e('No posts found.', 'ktai_style'); ?></p>
<?php
	} // have_posts()
	include dirname(__FILE__) . '/admin-footer.php';
}

// ==================================================
public function post_rows() {
	global $wp_query, $post;
	$columns = array('dt', 'title', '/dt', 'dd', 'author', 'date', 'comments', '/dd', 'dd-small', 'categories', '/dd-small');
	$columns = array_merge($columns, array('dd-small', 'tags', '/dd-small'));
	if ('trash' === $_GET['post_status'] && function_exists('wp_untrash_post')) {
		$columns = array_merge($columns, array('dd', 'actions', '/dd'));
	}
	$post_ids = array();
	foreach ( $wp_query->posts as $a_post ) {
		$post_ids[] = $a_post->ID;
	}
	$comment_pending_count = get_pending_comments_num($post_ids);
	if ( empty($comment_pending_count) ) {
		$comment_pending_count = array();
	}
?><dl><?php 
	while (have_posts()) : the_post();
		foreach ($columns as $column_name) {
			switch ($column_name) {
			case 'dt':
				echo '<dt>';
				break;
			case '/dt':
				echo '</dt>';
				break;
			case 'dd':
				echo '<dd>';
				break;
			case '/dd':
				echo '</dd>';
				break;
			case 'dd-small':
				echo '<dd><small>';
				break;
			case '/dd-small':
				echo '</small></dd>';
				break;
			case 'title':
				$title = $this->admin->draft_or_post_title();
				if ( current_user_can('edit_post', $post->ID) && $post->post_status != 'trash' ) {
					$title = sprintf(__('<img localsrc="104" alt="" /><a href="%1$s">%2$s</a>', 'ktai_style'), get_edit_post_link($post->ID, 'url'), $title);
				}
				printf(_c('%1$5d:%2$s%3$s|post_row_title', 'ktai_style'), $post->ID, $title, $this->admin->get_post_states($post));
				break;
			case 'date':
				if ('draft' === $_GET['post_status'] || 'pending' === $_GET['post_status']) {
					if ('0000-00-00 00:00:00' == $post->post_modified) {
						$time = __('Never');
					} else {
						$time = ks_get_mod_time();
					}
				} else {
					if ('0000-00-00 00:00:00' == $post->post_date) {
						$time = __('Unpublished');
					} else {
						$time = ks_get_time();
					}
				}
				printf(__('<img localsrc="46" alt=" at " />%s', 'ktai_style'), '<font color="' . ks_option('ks_date_color') . '">' . $time . '</font>');
				break;
			case 'author':
				printf(__('<img localsrc="68" alt="by " />%s', 'ktai_style'), get_the_author());
				break;
			case 'categories':
				$categories = get_the_category();
				if (! empty($categories)) {
					$cat_links = array();
					foreach ($categories as $c) {
						$cat_links[] = '<a href="' . basename(__FILE__) . '?cat=' . $c->cat_ID . '"><font color="gray">' . wp_specialchars(sanitize_term_field('name', $c->name, $c->term_id, 'category', 'display')) . '</font></a>';
					}
					echo sprintf(__('<img localsrc="354" alt="%1$s" />%2$s', 'ktai_style'), __('Category:'), implode(', ', $cat_links));
				}
				break;
			case 'tags':
				$tags = get_the_tags($post->ID);
				if (! empty($tags)) {
					$tag_links = array();
					foreach ($tags as $t) {
						$tag_links[] = '<a href="' . basename(__FILE__) . '?tag=' . $t->slug . '"><font color="gray">' . wp_specialchars(sanitize_term_field('name', $t->name, $t->term_id, 'post_tag', 'display')) . '</font></a>';
					}
					echo sprintf(__('<img localsrc="77" alt="%1$s" />%2$s', 'ktai_style'), __('Tags:'), implode(', ', $tag_links));
				}
				break;
			case 'comments':
				$link = '<a href="edit-comments.php?p=' . $post->ID . '">';
				echo ' <img localsrc="86" alt="[' . __('Comments') . '] " />';
				if (function_exists('_x')) {
					comments_number(
						($comment_pending_count[$post->ID] ? ($link . _x('0', 'comment count') . '</a>') : _x('0', 'comment count')), 
						$link . _x('1', 'comment count') . '</a>', 
						$link . _x('%', 'comment count') . '</a>'
					);
				} else {
					comments_number(
						($comment_pending_count[$post->ID] ? ($link . __('0') . '</a>') : __('0')), 
						$link . __('1') . '</a>', 
						$link . __('%') . '</a>'
					);
				}
				if ( $comment_pending_count[$post->ID] ) {
					echo '<img localsrc="2" alt="[' . sprintf(__('%s pending'), number_format( $comment_pending_count[$post->ID]) ) . ']" />';
				}
				break;
			case 'actions':
				$delete_url = 'post.php?action=delete&post=' . $post->ID;
				$untrash_url = 'post.php?action=untrash&post=' . $post->ID;
				$untrash_url = wp_nonce_url($untrash_url, 'untrash-post_' . $post->ID);
				$actions['restore'] = sprintf('<a href="%s"><font color="%s">%s</font></a>', $untrash_url, 'green', __('Restore'));
				$actions['delete'] = sprintf('<a href="%s"><font color="%s">%s</font></a>', $delete_url, 'red', __('Delete'));
				echo '[ ' . implode(' | ', $actions) . ' ]';
				break;
			}
		}
	endwhile;
?></dl><?php
}

/* ==================================================
 * @since 1.83
 */
public function bulk_action() {
	global $wpdb;
	check_admin_referer('bulk-posts');
	$this->sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), $this->admin->get_referer() );
	if ( strpos($this->sendback, 'post.php') !== false ) {
		$this->sendback = 'post-new.php';
	}
	if ( isset($_GET['delete_all']) ) {
	//	$post_status = preg_replace('/[^a-z0-9_-]+/i', '', $_GET['post_status']);
		$post_status = 'trash';
		$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='post' AND post_status = %s", $post_status ) );
		$doaction = 'delete';
	} elseif ( $_GET['action'] != -1 ) {
		if ( isset($_GET['post']) ) {
			$post_ids = array(intval($_GET['post']));
		} else {
			$post_ids = array_map('intval', explode(',', $_GET['ids']) );
		}
		$doaction = $_GET['action'];
	}
	switch ($doaction) {
	case 'trash':
		$trashed = 0;
		if ( !function_exists('wp_trash_post') ) {
			break;
		}
		foreach( (array) $post_ids as $post_id ) {
			if ( !current_user_can('delete_post', $post_id) ) {
				$this->base->ks_die( __('You are not allowed to move this post to the trash.') );
			}
			if ( !wp_trash_post($post_id) ) {
				$this->base->ks_die( __('Error in moving to trash...') );
			}
			$trashed++;
		}
		$this->sendback = add_query_arg(array( 'trashed' => $trashed, 'ids' => implode(',', $post_ids)), $this->sendback);
		break;		
	case 'untrash':
		$trashed = 0;
		if ( !function_exists('wp_untrash_post') ) {
			break;
		}
		foreach( (array) $post_ids as $post_id ) {
			if ( !current_user_can('delete_post', $post_id) ) {
				$this->base->ks_die( __('You are not allowed to restore this post from the trash.') );
			}
			if ( !wp_untrash_post($post_id) ) {
				$this->base->ks_die( __('Error in restoring from trash...') );
			}
			$untrashed++;
		}
		$this->sendback = add_query_arg('untrashed', $untrashed, $this->sendback);
		break;		
	case 'delete':
		$delete = 0;
		foreach( (array) $post_ids as $post_id ) {
			$post_del = get_post($post_id);
	
			if ( !current_user_can('delete_post', $post_id) ) {
				$this->base->ks_die( __('You are not allowed to delete this post.') );
			}
			if ( $post_del->post_type == 'attachment' ) {
				if ( ! wp_delete_attachment($post_id) )
					$this->base->ks_die( __('Error in deleting...') );
			} else {
				if ( !wp_delete_post($post_id) )
					$this->base->ks_die( __('Error in deleting...') );
			}
			$deleted++;
		}
		$this->sendback = add_query_arg('deleted', $deleted, $this->sendback);
	}
	$this->sendback = add_query_arg('deleted', $deleted, $this->sendback);
if ( isset($_GET['action']) )
	$this->sendback = remove_query_arg( array('action', 'cat', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view', 'post_type'), $this->sendback );
}

// ===== End of class ====================
}
?>