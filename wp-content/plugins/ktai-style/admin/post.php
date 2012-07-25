<?php
/* ==================================================
 *   Ktai Admin Process Posts
 *   based on wp-admin/post.php of WP 2.3, 2.7
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$parent_file = 'edit.php';
$View = new KtaiAdmin_Posts($Ktai_Style);

/* ==================================================
 *   KtaiAdmin_Posts class
   ================================================== */

class KtaiAdmin_Posts {
	private $base;
	private $admin;
	private $walker;
	private $sendback;

public function __construct($base) {
	$this->base  = $base;
	$this->admin = $base->admin;

	global $action, $posts;
	wp_reset_vars(array('action', 'posts'));

	if (isset( $_POST['deletepost'])) {
		$action = 'delete';
	} elseif (isset($_POST['selcats'])) {
		$action = 'selcats';
	}
	$this->sendback = $this->admin->get_referer();
	if ( strpos($this->sendback, 'post.php') !== false || strpos($this->sendback, 'post-new.php') !== false ) {
		$this->sendback = 'edit.php';
	} else {
		$this->sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), $this->sendback );
	}
	switch ($action) {
	case 'post':
		$this->post();
		exit();
	case 'edit':
		$this->edit();
		break;
	case 'editpost':
		$this->editpost();
		exit();
	case 'delete':
		$this->delete();
		exit();
	case 'trash':
		$this->trash();
		exit();
	case 'untrash':
		$this->untrash();
		exit();
	case 'selcats':
		$this->select_cats();
		break;
	case 'changecats':
		$this->change_cats();
		break;
	default:
		$this->admin->redirect('edit.php');
		exit();
	}
}

// ==================================================
private function post() {
	global $parent_file, $submenu_file;
	$parent_file = 'edit.php';
	$submenu_file = 'post-new.php';
	check_admin_referer('add-post');
	$post_ID = $this->admin->write_post('post');
	$this->redirect_post($post_ID);
}

// ==================================================
private function edit() {
	if ( empty( $_GET['post'] ) ) {
		$this->admin->redirect('edit.php');
		exit();
	}
	global $title, $post_ID, $p, $post, $post_referredby;
	$title = __('Edit Post');
	$post_ID = $p = (int) $_GET['post'];
	$post = $this->get_post($post_ID);
	if ( current_user_can('edit_post', $post_ID) ) {
		if ( $last = wp_check_post_lock( $post->ID ) ) {
			$last_user = get_userdata( $last );
			$last_user_name = $last_user ? $last_user->display_name : __('Somebody');
			$message = sprintf( __( 'Warning: %s is currently editing this post' ), esc_html( $last_user_name ) );
			$message = '<p><font color="red">' . $message . '</font></p>';
			add_action('admin_notices', create_function( '', "echo '$message';" ) );
		} else {
			wp_set_post_lock( $post->ID );
		}
	} else {
		$this->base->ks_die(__('You are not allowed to edit this post.'));
	}
	$post_referredby = $this->sendback;
	include dirname(__FILE__) . '/edit-form.php';
}

// ==================================================
private function editpost() {
	$post_ID = (int) $_POST['post_ID'];
	check_admin_referer('update-post_' . $post_ID);
	$post_ID = $this->admin->edit_post($post_ID, 'post');
	$this->redirect_post($post_ID);
}

// ==================================================
private function delete() {
	$post_id = (isset($_GET['post'])) ? intval($_GET['post']) : intval($_POST['post_ID']);
	check_admin_referer('delete-post_' . $post_id);
	$post = get_post($post_id);
	if (! current_user_can('delete_post', $post_id)) {
		$this->base->ks_die(__('You are not allowed to delete this post.'));
	}
	if ( $post->post_type == 'attachment' ) {
		if (! wp_delete_attachment($post_id)) {
			$this->base->ks_die(__('Error in deleting...'));
		}
	} else {
		if (! wp_delete_post($post_id)) {
			$this->base->ks_die(__('Error in deleting...'));
		}
	}
	$this->sendback = add_query_arg('deleted', 1, $this->sendback);
	$this->admin->redirect($this->sendback);
}

// ==================================================
private function trash() {
	$post_id = (isset($_GET['post'])) ? intval($_GET['post']) : intval($_POST['post_ID']);
	check_admin_referer('trash-post_' . $post_id);
	$post = get_post($post_id);
	if (! current_user_can('delete_post', $post_id)) {
		$this->base->ks_die(__('You are not allowed to move this post to the trash.'));
	}
	if (! wp_trash_post($post_id)) {
		$this->base->ks_die(__('Error in moving to trash...'));
	}
	$this->sendback = add_query_arg( array('trashed' => 1, 'ids' => $post_id), $this->sendback);
	$this->admin->redirect($this->sendback);
}

// ==================================================
private function untrash() {
	$post_id = (isset($_GET['post'])) ? intval($_GET['post']) : intval($_POST['post_ID']);
	check_admin_referer('untrash-post_' . $post_id);
	$post = get_post($post_id);
	if (! current_user_can('delete_post', $post_id)) {
		$this->base->ks_die(__('You are not allowed to move this post out of the trash.'));
	}
	if (! wp_untrash_post($post_id)) {
		$this->base->ks_die(__('Error in restoring from trash...'));
	}
	$this->sendback = add_query_arg( 'untrashed', 1, $this->sendback);
	$this->admin->redirect($this->sendback);
}

// ==================================================
private function select_cats() {
	global $title, $post_ID, $parent_file;
	$parent_file = 'edit.php';
	if ($_POST['originalaction'] == 'editpost') {
		$post_ID = (int) $_POST['post_ID'];
	 	if ($post_ID < 1) {
			$this->base->ks_die(__("You attempted to edit a post that doesn't exist. Perhaps it was deleted?"));
		}
		check_admin_referer('update-post_' . $post_ID);
	} else {
		$post_ID = 0;
		check_admin_referer('add-post');
	}
	foreach (array('post_ID', 'post_cats', 'originalaction', 'referredby', '_wp_original_http_referer') as $k) {
		if (isset($_POST[$k])) {
			$this->admin->set_data($k, $_POST[$k]);
		}
	}
	$charset = ks_detect_encoding();
	$this->admin->set_data('post_title',   ks_mb_get_form('post_title', $charset));
	$this->admin->set_data('post_name',    ks_mb_get_form('post_name',  $charset));
	$this->admin->set_data('post_content', ks_mb_get_form('content',    $charset));
	$this->admin->set_data('tags_input',   ks_mb_get_form('tags_input', $charset));
	$title = __('Select Category', 'ktai_style');
	include dirname(__FILE__) . '/admin-header.php';?>
<form action="post.php" method="post">
<input type="hidden" name="action" value="changecats" />
<?php 
	$this->admin->sid_field();
	wp_nonce_field('change-cats_' . $post_ID, "_wpnonce", false);
	$this->category_checklist(array_map('intval', explode(',', $_POST['post_cats']))); ?>
<input type="submit" name="cancel" value="<?php _e('Cancel'); ?>" />
<input type="submit" value="<?php _e('Set Category', 'ktai_style'); ?>" />
</form>
<?php
	include dirname(__FILE__) . '/admin-footer.php'; 
}

// ==================================================
private function change_cats() {
	global $title, $action, $post_ID, $parent_file, $post, $post_referredby, $post_orig_referer, $checked_cats;
	if ($this->admin->get_data('originalaction') == 'editpost') {
		$title = __('Edit');
		$action = 'edit';
		$post_ID = intval($this->admin->get_data('post_ID'));
		$post = $this->get_post($post_ID);
	} else {
//		$parent_file = 'post-new.php';
		$title = __('Add New Post');
		$post_ID = 0;
		$post = get_default_post_to_edit();
	}
	check_admin_referer('change-cats_' . $post_ID);
	foreach( array('post_title', 'post_name', 'post_content', 'tags_input') as $f) {
		$post->$f = $this->admin->get_data($f);
		$post->$f = sanitize_post_field($f, $post->$f, $post_ID, 'edit');
	}
	$post_referredby = $this->admin->get_data('referredby');
	$post_orig_referer = $this->admin->get_data('_wp_original_http_referer');
	if (! isset($_POST['cancel'])) {
		$checked_cats = array();
		if (is_array($_POST['cat']) && count($_POST['cat']) >= 1) {
			foreach ($_POST['cat'] as $c) {
				$checked_cats[] = intval($c);
			}
		} else {
			$checked_cats[] = get_option('default_category');
		}
	} else {
		$checked_cats = array_map('intval', explode(',', $this->admin->get_data('post_cats')));
	}
	include dirname(__FILE__) . '/edit-form.php';
}

/* ==================================================
 * based on wp_category_checklist() at wp-admin/includes/template.php of WP 2.5.1
 */
public function category_checklist($selected_cats = false, $descendants_and_self = 0) {
	$descendants_and_self = (int) $descendants_and_self;
	$args = array('orderby' => 'name','order' => 'ASC', 'show_count' => 0, 'hierarchical' => false);
	$walker = new KtaiCategory_Checklist();

	if (is_array( $selected_cats)) {
		$args['selected_cats'] = $selected_cats;
	}
	$args['popular_cats'] = array();
	if ( $descendants_and_self ) {
		$categories = get_categories( "child_of=$descendants_and_self&hierarchical=0&hide_empty=0" );
		$self = get_category( $descendants_and_self );
		array_unshift( $categories, $self );
	} else {
		$categories = get_categories('get=all');
	}

	$args = array($categories, -1, $args);
	$output = '<dl>' . call_user_func_array(array(&$walker, 'walk'), $args) . '</dl>';

	echo $output;
}

// ==================================================
private function get_post($post_ID) {
	$post = get_post_to_edit($post_ID);
	if (empty($post->ID)) {
		$this->base->ks_die(__("You attempted to edit a post that doesn't exist. Perhaps it was deleted?"));
	}
	if ('post' != $post->post_type) {
		$this->admin->redirect(get_edit_post_link($post->ID, 'url'));
		exit;
	}
	return $post;
}

// ==================================================
private function redirect_post($post_ID = '') {
	$admin_dir = $this->base->get('plugin_dir') . '/' . basename(dirname(__FILE__));

	$referredby = '';
	if ( !empty($_POST['referredby']) ) {
		$referredby = preg_replace('|https?://[^/]+|i', '', $_POST['referredby']);
		$referredby = remove_query_arg('_wp_original_http_referer', $referredby);
	}
	$referer = preg_replace('|https?://[^/]+|i', '', $this->admin->get_referer());

	if ( ( isset($_POST['save']) || isset($_POST['publish']) ) 
	&& ( empty($referredby) || $referredby == $referer || 'redo' != $referredby ) ) {
		$orig_referer = wp_get_original_referer();
		if ( strpos( $orig_referer, $admin_dir . '/post.php') === false 
		&&   strpos( $orig_referer, $admin_dir . '/post-new.php') === false ) {
			$location = add_query_arg( array(
				'_wp_original_http_referer' => urlencode($orig_referer),
				'message' => 1
			), get_edit_post_link( $post_ID, 'url' ) );
		} else {
			if ( isset( $_POST['publish'] ) ) {
				if ( 'pending' == get_post_status( $post_ID ) ) {
					$location = add_query_arg( 'message', 8, get_edit_post_link( $post_ID, 'url' ) );
				} else {
					$location = add_query_arg( 'message', 6, get_edit_post_link( $post_ID, 'url' ) );
				}
			} else {
				$location = add_query_arg( 'message', 7, get_edit_post_link( $post_ID, 'url' ) );
			}
		}
/*	} elseif (isset($_POST['addmeta']) && $_POST['addmeta']) {
		$location = add_query_arg( 'message', 2, $this->admin->get_referer() );
		$location = explode('#', $location);
		$location = $location[0] . '#postcustom';
	} elseif (isset($_POST['deletemeta']) && $_POST['deletemeta']) {
		$location = add_query_arg( 'message', 3, $this->admin->get_referer() );
		$location = explode('#', $location);
		$location = $location[0] . '#postcustom';
*/	} elseif (!empty($referredby) && $referredby != $referer) {
		$location = $_POST['referredby'];
		$location = remove_query_arg('_wp_original_http_referer', $location);
		if ( false !== strpos($location, 'edit.php')) {
			$location = add_query_arg('posted', $post_ID, $location);
		} elseif ( false !== strpos($location, basename(dirname(__FILE__))) ) {
			$location = "post-new.php?posted=$post_ID";
		}
	} elseif ( isset($_POST['publish']) ) {
		$location = "post-new.php?posted=$post_ID";
	} else {
		$location = add_query_arg( 'message', 4, get_edit_post_link( $post_ID, 'url' ) );
	}

	$this->admin->redirect($location);
}

// ===== End of class ====================
}
?>