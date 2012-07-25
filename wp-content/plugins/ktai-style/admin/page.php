<?php
/* ==================================================
 *   Ktai Admin Process Posts
 *   based on wp-admin/post.php of WP 2.7
   ================================================== */

global $Ktai_Style;
require dirname(__FILE__) . '/admin.php';
$parent_file = 'edit-pages.php';
$submenu_file = 'edit-pages.php';
$View = new KtaiAdmin_Pages($Ktai_Style);

/* ==================================================
 *   KtaiAdmin_Pages class
   ================================================== */

class KtaiAdmin_Pages {
	private $base;
	private $admin;
	private $sendback;

public function __construct($base) {
	$this->base  = $base;
	$this->admin = $base->admin;

	global $action;
	wp_reset_vars(array('action'));

	if (isset($_POST['deletepost'])) {
		$action = "delete";
	}

	$this->sendback = $this->admin->get_referer();
	if ( strpos($this->sendback, 'page.php') !== false || strpos($this->sendback, 'page-new.php') !== false ) {
		$this->sendback = 'edit-pages.php';
	} else {
		$this->sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'ids'), $this->sendback );
	}

	switch($action) {
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
	default:
		$this->redirect('edit-pages.php');
		exit();
	} // end switch
}

// ==================================================
private function post() {
	global $parent_file, $submenu_file;
	$parent_file = 'edit-pages.php';
	$submenu_file = 'page-new.php';
	check_admin_referer('add-page');
	$page_ID = $this->admin->write_post('page');
	$this->redirect_page($page_ID);
}

// ==================================================
private function edit() {
	global $title, $post_ID, $p, $post, $post_referredby;
	$title = __('Edit Page');
	$page_ID = $post_ID = $p = (int) $_GET['post'];
	$post = $this->get_page($post_ID);
	if ( current_user_can('edit_page', $page_ID) ) {
		if ( $last = wp_check_post_lock( $post->ID ) ) {
			$last_user = get_userdata( $last );
			$last_user_name = $last_user ? $last_user->display_name : __('Somebody');
			$message = sprintf( __( 'Warning: %s is currently editing this page' ), esc_html( $last_user_name ) );
			$message = '<p><font color="red">' . $message . '</font></p>';
			add_action('admin_notices', create_function( '', "echo '$message';" ) );
		} else {
			wp_set_post_lock( $post->ID );
		}
	} else {
		$this->base->ks_die(__('You are not allowed to edit this page.'));
		// exit;
	}
	$post_referredby = $this->sendback;
	include dirname(__FILE__) . '/edit-page-form.php';
}

// ==================================================
private function editpost() {
	$page_ID = (int) $_POST['post_ID'];
	check_admin_referer('update-page_' . $page_ID);
	$page_ID = $this->admin->edit_post($page_ID, 'page');
	$this->redirect_page($page_ID);
}

// ==================================================
private function delete() {
	$page_id = (isset($_GET['post'])) ? intval($_GET['post']) : intval($_POST['post_ID']);
	check_admin_referer('delete-page_' .  $page_id);
	$page = get_post($page_id);
	if (! current_user_can('delete_page', $page_id)) {
		$this->base->ks_die(__('You are not allowed to delete this page.'));
		// exit;
	}
	if ( $page->post_type == 'attachment' ) {
		if ( ! wp_delete_attachment($page_id) ) {
			$this->base->ks_die(__('Error in deleting...'));
			// exit;
		}
	} else {
		if (! wp_delete_post($page_id)) {
			$this->base->ks_die(__('Error in deleting...'));
			// exit;
		}
	}
	$this->sendback = add_query_arg('deleted', 1, $this->sendback);
	$this->admin->redirect($this->sendback);
}

// ==================================================
private function trash() {
	$post_id = (isset($_GET['post'])) ? intval($_GET['post']) : intval($_POST['post_ID']);
	check_admin_referer('trash-page_' . $post_id);
	$post = get_post($post_id);
	if (! current_user_can('delete_page', $post_id)) {
		$this->base->ks_die(__('You are not allowed to move this page to the trash.'));
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
	check_admin_referer('untrash-page_' . $post_id);
	$post = get_post($post_id);
	if (! current_user_can('delete_page', $post_id)) {
		$this->base->ks_die(__('You are not allowed to move this page out of the trash.'));
	}
	if (! wp_untrash_post($post_id)) {
		$this->base->ks_die(__('Error in restoring from trash...'));
	}
	$this->sendback = add_query_arg( 'untrashed', 1, $this->sendback);
	$this->admin->redirect($this->sendback);
}

// ==================================================
private function get_page($post_ID) {
	$post = get_post($post_ID);
	if (empty($post->ID)) {
		$this->base->ks_die(__("You attempted to edit a page that doesn't exist. Perhaps it was deleted?"));
	}
	if ('page' != $post->post_type) {
		$this->admin->redirect(get_edit_post_link($post->ID, 'url'));
		exit;
	}
	return $post;
}

// ==================================================
public function redirect_page($page_ID) {
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
		if ( strpos( $orig_referer, $admin_dir . '/page.php') === false 
		&&   strpos( $orig_referer, $admin_dir . '/page-new.php') === false ) {
			$location = add_query_arg( array(
				'_wp_original_http_referer' => urlencode($orig_referer),
				'message' => 1
			), get_edit_post_link($page_ID, 'url') );
		} else {
			if ( isset($_POST['publish']) ) {
				if ('pending' == get_post_status($page_ID)) {
					$location = add_query_arg( 'message', 6, get_edit_post_link($page_ID, 'url') );
				} else {
					$location = add_query_arg( 'message', 5, get_edit_post_link($page_ID, 'url') );
				}
			} else {
				$location = add_query_arg( 'message', 4, get_edit_post_link( $page_ID, 'url' ) );
			}
		}
/*	} elseif ( isset($_POST['addmeta']) ) {
		$location = add_query_arg( 'message', 2, $this->admin->get_referer() );
		$location = explode('#', $location);
		$location = $location[0] . '#postcustom';
	} elseif ( isset($_POST['deletemeta']) ) {
		$location = add_query_arg( 'message', 3, $this->admin->get_referer() );
		$location = explode('#', $location);
		$location = $location[0] . '#postcustom';
*/	} elseif (!empty($referredby) && $referredby != $referer) {
		$location = $_POST['referredby'];
		$location = remove_query_arg('_wp_original_http_referer', $location);
		if ( false !== strpos($location, 'edit-pages.php') ) {
			$location = add_query_arg('posted', $page_ID, $location);
		} elseif ( false !== strpos($location, 'wp-admin') ) {
			$location = "page-new.php?posted=$page_ID";
		}
	} elseif ( isset($_POST['publish']) ) {
		$location = "page-new.php?posted=$page_ID";
	} else {
		$location = add_query_arg( 'message', 4, get_edit_post_link( $page_ID, 'url' ) );
	}

	$this->admin->redirect($location);
}

// ===== End of class ====================
}
?>