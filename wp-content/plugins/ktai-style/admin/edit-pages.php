<?php
/* ==================================================
 *   Ktai Admin Edit Page
 *   based on wp-admin/edit-pages.php of WP 2.7
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$title = __('Edit Pages', 'ktai_style');
$parent_file = 'edit-pages.php';
$View = new KtaiAdmin_Edit_Pages($Ktai_Style);
$View->output();
exit;

/* ==================================================
 *   KtaiAdmin_Edit_Pages class
   ================================================== */

class KtaiAdmin_Edit_Pages {
	private $base;
	private $admin;
	private $search;
	private $is_trash;
	const HEADER_FOOTER_SIZE = 3000;
	const COLUMN_SIZE = 256;

// ==================================================
public function __construct($base) {
	$this->base  = $base;
	$this->admin = $base->admin;

	if (isset($_GET['delete_all'])) {
		check_admin_referer('bulk-pages');
		$this->sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), $this->admin->get_referer() );
		if ( strpos($this->sendback, 'page.php') !== false ) {
			$this->sendback = ks_admin_url(KTAI_NOT_ECHO) . 'page-new.php';
		}
		$deleted = $this->delete_all();
		$this->sendback = add_query_arg('deleted', $deleted, $this->sendback);
		$this->admin->redirect($this->sendback);
		exit;
	}

	$this->search = isset($_GET['s']) ? stripslashes($_GET['s']) : '';
	$this->is_trash = isset($_GET['post_status']) && $_GET['post_status'] == 'trash' && function_exists('wp_trash_post');
}

// ==================================================
private function edit_pages_query() {
	$post_stati  = array(	//	array( adj, noun )
		'publish' => array(__('Published'), __('Published pages'), _n_noop('Published (%s)', 'Published (%s)')),
		'future' => array(__('Scheduled'), __('Scheduled pages'), _n_noop('Scheduled (%s)', 'Scheduled (%s)')),
		'pending' => array(__('Pending Review'), __('Pending pages'), _n_noop('Pending Review (%s)', 'Pending Review (%s)')),
		'draft' => array(__('Draft'), _c('Drafts|manage posts header'), _n_noop('Draft (%s)', 'Drafts (%s)')),
		'private' => array(__('Private'), __('Private pages'), _n_noop('Private (%s)', 'Private (%s)'))
	);
	if (function_exists('wp_trash_post') && EMPTY_TRASH_DAYS) {
		$post_stati['trash'] = array(_x('Trash', 'page'), __('Trash pages'), _n_noop('Trash (%s)', 'Trash (%s)'));
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
		'post_type' => 'page', 
		'what_to_show' => 'posts',
		'post_status' => 'any',
		'posts_per_page' => -1, 
		'posts_per_archive_page' => -1, 
		'orderby' => 'menu_order title', 
		'order' => 'ASC',
		's' => $this->search,
	);

	if ( isset($_GET['post_status'])) {
		$post_status = stripslashes($_GET['post_status']);
		if ( in_array($post_status, array_keys($post_stati)) ) {
			$query['post_status'] = $post_status;
			$query['perm'] = 'readable';
		}
	}
	$query = apply_filters('manage_pages_query', $query);
	query_posts($query);

	return array($post_stati, $pagenum, $per_page);
}

// ==================================================
public function status_menu($post_stati) {
	global $parent_file, $submenu, $submenu_file;

	$num_posts = wp_count_posts('page', 'readable');
	$num_total = array_sum( (array) $num_posts);

	$link = $submenu_file = 'edit-pages.php';
	$submenu[$parent_file] = array(
		array(__('Add New Page'), 'edit_pages', 'page-new.php'),
		array(sprintf(__('All (%s)', 'ktai_style'), $num_total), 'edit_pages', $link),
	);
	$post_status_label = __('Pages');
	$avail_post_stati = get_available_post_statuses('page');
	foreach ( $post_stati as $status => $label ) {
		if ( !in_array( $status, $avail_post_stati ) ) {
			continue;
		}
		if ( empty( $num_posts->$status ) ) {
			continue;
		}
		$link = add_query_arg( 'post_status', $status, $link );
		$submenu[$parent_file][] = array(sprintf(__ngettext( $label[2][0], $label[2][1], $num_posts->$status, 'ktai_style'), number_format_i18n($num_posts->$status)), 'edit_pages', $link);
		if ( str_replace( 'all', '', $status ) == $_GET['post_status'] ) {
			$submenu_file = $link;
			$post_status_label = $label[1];
			$num_total = $num_posts->$status;
		}
	}
	return array($post_status_label, $num_total);
}

// ==================================================
public function output() {
	global $title, $parent_file;
	list($post_stati, $pagenum, $per_page) = $this->edit_pages_query();

	include dirname(__FILE__) . '/admin-header.php';
	list($post_status_label, $total_posts) = $this->status_menu($post_stati);
	if ($this->search) {
		echo '<h2>' . sprintf( __('Search results for &#8220;%s&#8221;', 'ktai_style'), wp_specialchars($this->search) ) . '</h2>';
	}
	if ( isset($_GET['posted']) && $_GET['posted'] ) {
		$_GET['posted'] = (int) $_GET['posted'];
		?><p><font color="teal"><?php _e('Your page has been saved.'); ?></font></p><?php
		$_SERVER['REQUEST_URI'] = remove_query_arg(array('posted'), $_SERVER['REQUEST_URI']);
	}
	$max_pages = ceil($GLOBALS['wp_query']->post_count / $per_page);
	$this->admin->nav_dropdown($pagenum, $max_pages);
?>
<form action="" method="get"><div>
<?php $this->admin->sid_field(); ?>
<?php _e('Search'); ?><input type="text" name="ks" value="<?php echo wp_specialchars($this->search); ?>" size="17" /><br />
<?php if (isset($_GET['post_status'])) { ?>
<input type="hidden" name="post_status" value="<?php echo esc_attr($_GET['post_status']) ?>" />
<?php } ?>
<input type="submit" name="filter" value="<?php _e('Filter'); ?>" />
<?php if ($this->is_trash && current_user_can('edit_others_posts')) {
	wp_nonce_field('bulk-pages'); ?>
<br /><input type="submit" name="delete_all" id="delete_all" value="<?php esc_attr_e('Empty Trash'); ?>" />
<?php } ?>
</div></form>
<?php 
	if (have_posts()) {
		$this->page_rows($pagenum, $per_page);
		$this->admin->nav_link($pagenum, $max_pages);
	} else { ?>
<p><?php _e('No pages found.', 'ktai_style') ?></p>
<?php
	} // have_posts()
	include dirname(__FILE__) . '/admin-footer.php';
}

// ==================================================
private function page_rows($pagenum = 1, $per_page = 15) {
	global $wpdb, $wp_query;
	$level = 0;
	$pages =& $wp_query->posts;
	if ( ! $pages ) {
		return false;
	}
	/*
	 * arrange pages into two parts: top level pages and children_pages
	 * children_pages is two dimensional array, eg.
	 * children_pages[10][] contains all sub-pages whose parent is 10.
	 * It only takes O(N) to arrange this and it takes O(1) for subsequent lookup operations
	 * If searching, ignore hierarchy and treat everything as top level
	 */
	if ( empty($this->search) ) {

		$top_level_pages = array();
		$children_pages = array();

		foreach ( $pages as $page ) {

			// catch and repair bad pages
			if ( $page->post_parent == $page->ID ) {
				$page->post_parent = 0;
				$wpdb->query( $wpdb->prepare("UPDATE $wpdb->posts SET post_parent = '0' WHERE ID = %d", $page->ID) );
				clean_page_cache( $page->ID );
			}

			if ( 0 == $page->post_parent )
				$top_level_pages[] = $page;
			else
				$children_pages[ $page->post_parent ][] = $page;
		}

		$pages = &$top_level_pages;
	}

	$count = 0;
	$start = ($pagenum - 1) * $per_page;
	$end = $start + $per_page;
?><dl><?php 
	foreach ( $pages as $page ) {
		if ( $count >= $end )
			break;

		if ( $count >= $start )
			echo $this->display_page_row( $page, $level );

		$count++;

		if ( isset($children_pages) )
			$this->_page_rows( $children_pages, $count, $page->ID, $level + 1, $pagenum, $per_page );
	}

	// if it is the last pagenum and there are orphaned pages, display them with paging as well
	if ( isset($children_pages) && $count < $end ){
		foreach( $children_pages as $orphans ){
			foreach ( $orphans as $op ) {
				if ( $count >= $end )
					break;
				if ( $count >= $start )
					echo $this->display_page_row( $op, 0 );
				$count++;
			}
		}
	}
?></dl><?php 
}

// ==================================================
private function _page_rows( &$children_pages, &$count, $parent, $level, $pagenum, $per_page ) {

	if ( ! isset( $children_pages[$parent] ) )
		return;

	$start = ($pagenum - 1) * $per_page;
	$end = $start + $per_page;

	foreach ( $children_pages[$parent] as $page ) {

		if ( $count >= $end )
			break;

		// If the page starts in a subtree, print the parents.
		if ( $count == $start && $page->post_parent > 0 ) {
			$my_parents = array();
			$my_parent = $page->post_parent;
			while ( $my_parent) {
				$my_parent = get_post($my_parent);
				$my_parents[] = $my_parent;
				if ( !$my_parent->post_parent )
					break;
				$my_parent = $my_parent->post_parent;
			}
			$num_parents = count($my_parents);
			while( $my_parent = array_pop($my_parents) ) {
				echo $this->display_page_row( $my_parent, $level - $num_parents );
				$num_parents--;
			}
		}

		if ( $count >= $start )
			echo $this->display_page_row( $page, $level );

		$count++;

		$this->_page_rows( $children_pages, $count, $page->ID, $level + 1, $pagenum, $per_page );
	}

	unset( $children_pages[$parent] ); //required in order to keep track of orphans
}

// ==================================================
function display_page_row( $page, $level = 0 ) {
	global $post;
	$columns = array('dt', 'title', '/dt', 'dd', 'author', 'date', 'comments', '/dd');
	if ('trash' === $_GET['post_status'] && function_exists('wp_untrash_post')) {
		$columns = array_merge($columns, array('dd', 'actions', '/dd'));
	}

	$post = $page;
	setup_postdata($page);
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
			$pad = str_repeat('&#8212; ', $level);
			if ( current_user_can('edit_post', $page->ID) && $post->post_status != 'trash' ) {
				$title = sprintf(__('<img localsrc="104" alt="" /><a href="%1$s">%2$s</a>', 'ktai_style'), get_edit_post_link($page->ID, 'url'), $title);
			}
			printf(_c('%1$5d:%2$s%3$s%4$s|page_row_title', 'ktai_style'), $post->ID, $pad, $title, $this->admin->get_post_states($page));
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
		case 'comments':
			$link = '<a href="edit-comments.php?p=' . $post->ID . '">';
			echo ' <img localsrc="86" alt="[' . __('Comments') . '] " />';
			if (function_exists('_x')) {
				comments_number(
					($comment_pending_count[$post->ID] ? ($link . _x('0', 'comment count') . '</a>') : _x('0','comment count')), 
					$link . _x('1','comment count') . '</a>', 
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
			$delete_url = 'page.php?action=delete&post=' . $post->ID;
			$untrash_url = 'page.php?action=untrash&post=' . $post->ID;
			$untrash_url = wp_nonce_url($untrash_url, 'untrash-page_' . $post->ID);
			$actions['restore'] = sprintf('<a href="%s"><font color="%s">%s</font></a>', $untrash_url, 'green', __('Restore'));
			$actions['delete'] = sprintf('<a href="%s"><font color="%s">%s</font></a>', $delete_url, 'red', __('Delete'));
			echo '[ ' . implode(' | ', $actions) . ' ]';
			break;
		}
	}
}

/* ==================================================
 * @since 2.0.0
 */
public function delete_all() {
	global $wpdb;
	$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash'", $post_status ) );
	$deleted = 0;
	foreach( (array) $post_ids as $post_id ) {
		$post_del = get_post($post_id);

		if ( !current_user_can('delete_page', $post_id) ) {
			$this->base->ks_die( __('You are not allowed to delete this page.') );
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
	return $deleted;
}

// ===== End of class ====================
}
?>