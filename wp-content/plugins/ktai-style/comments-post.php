<?php
/* ==================================================
 *   comments-post.php
 *   based on wp-comments-post.php of WP 2.2.3-3.1
   ================================================== */
   
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Allow: POST');
	header('HTTP/1.1 405 Method Not Allowed');
	header('Content-type: text/plain');
    exit;
}

if ( !defined('ABSPATH') ) {
	global $wpload_error;
	$wpload_error = 'Could not post comments because custom WP_PLUGIN_DIR is set.';
	require dirname(__FILE__) . '/wp-load.php';
}
nocache_headers();

global $wpdb, $Ktai_Style, $comment_post_ID, $comment_author, $comment_author_email, $comment_author_url, $comment_content, $comment_parent;
if (! isset($Ktai_Style)) {
	exit;
}
$comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;
$post = get_post($comment_post_ID);

if ( empty($post->comment_status) ) {
	do_action('comment_id_not_found', $comment_post_ID);
	$Ktai_Style->ks_die(__('No target for your post.', 'ktai_style'));
	exit;
}

// get_post_status() will get the parent status for attachments.
$status = get_post_status($post);

if ( function_exists('get_post_status_object') ) {
	$status_obj = get_post_status_object($status);
	$is_draft = !$status_obj->public && !$status_obj->private;
} else {
	$is_draft = in_array($status->post_status, array('draft', 'future', 'pending'));
}

if ( !comments_open($comment_post_ID) ) {
	do_action('comment_closed', $comment_post_ID);
	$Ktai_Style->ks_die(__('Sorry, comments are closed for this item.'));
} elseif ( 'trash' == $status ) {
	do_action('comment_on_trash', $comment_post_ID);
	exit;
} elseif ( $is_draft ) {
	do_action('comment_on_draft', $comment_post_ID);
	$Ktai_Style->ks_die(__('No target for your post.', 'ktai_style'));
	exit;
} elseif ( post_password_required($comment_post_ID) ) {
	do_action('comment_on_password_protected', $comment_post_ID);
	exit;
} else {
	do_action('pre_comment_on_post', $comment_post_ID);
}

$charset = isset($_POST['charset_detect']) ? $Ktai_Style->detect_encoding($_POST['charset_detect']) : 'auto';
$comment_author  = isset($_POST['author']) ? $_POST['author'] : null;
$comment_content = $_POST['comment'];
if ( !$Ktai_Style->get('encoding_converted') ) {
	$comment_author  = $Ktai_Style->decode_from_ktai($comment_author, $charset);
	$comment_content = $Ktai_Style->decode_from_ktai($comment_content, $charset);
}
if ( !$Ktai_Style->check_encoding($_POST['email'], 'ASCII') || !$Ktai_Style->check_encoding($_POST['url'], 'ASCII') ) {
	$Ktai_Style->ks_die(sprintf(__('Invalid character found for %s encoding', 'ktai_style'), 'ASCII'));
	// exit;
}
$comment_author       = trim(strip_tags($comment_author));
$comment_author_email = trim($_POST['email']);
$comment_author_url   = trim($_POST['url']);
$comment_content      = trim($comment_content);
$comment_parent       = isset($_POST['comment_parent']) ? abs(intval($_POST['comment_parent'])) : 0;

// If the user is logged in
$user = wp_get_current_user();
if ( $user->ID ) {
	if ( empty( $user->display_name ) ) {
		$user->display_name = $user->user_login;
	}
	$comment_author       = esc_sql($user->display_name);
	$comment_author_email = esc_sql($user->user_email);
	$comment_author_url   = esc_sql($user->user_url);
	if ( current_user_can('unfiltered_html') 
	&& wp_create_nonce('unfiltered-html-comment_' . $comment_post_ID) != $_POST['_wp_unfiltered_html_comment'] ) {
		kses_remove_filters(); // start with a clean slate
		kses_init_filters(); // set up the filters
	}
} else {
	if ( get_option('comment_registration') || 'private' == $status->post_status ) {
		$Ktai_Style->ks_die(__('Sorry, you must be logged in to post a comment.'));
	}
}

try {
$comment_type = '';

if ( !$user->ID && ks_option('ks_require_term_id') ) {
	if ( $Ktai_Style->ktai->get('sub_ID_available') && !$Ktai_Style->ktai->get('term_ID') && !$Ktai_Style->ktai->get('sub_ID')) {
		$message = $Ktai_Style->ktai->get('require_id_msg');
		if (empty($message)) {
			$message = 'Error: please configure to send your terminal ID (serial number, EZ number etc).';
		}
		throw new Exception(__($message, 'ktai_style'));
	} else {
		if (function_exists('add_comment_meta')) {
			add_action('comment_post', array($Ktai_Style->ktai, 'store_term_id'), 20, 2);
		} else {
			add_filter('pre_comment_user_agent', array($Ktai_Style->ktai, 'add_term_id'));
		}
	}
}

if (get_option('require_name_email') && ! $user->ID) {
	if ( 6 > strlen($comment_author_email) || '' == $comment_author )
		throw new Exception(__('Error: please fill the required fields (name, email).'));
	elseif (! is_email($comment_author_email))
		throw new Exception(__('Error: please enter a valid email address.'));
}

if ('' == $comment_content) {
	throw new Exception(__('Error: please type a comment.'));
}

$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

global $allowedtags;
if ($allowedtags) {
	$allowedtags['img']['localsrc'] = array();
	$allowedtags['img']['alt'] = array();
}
$comment_id = wp_new_comment($commentdata);
$comment = get_comment($comment_id);

if ( $user->ID ) {
	if ( isset($Ktai_Style->admin) 
	&& !$Ktai_Style->ktai->get('cookie_available') 
	&& !preg_match('!' . preg_quote(dirname(__FILE__) . '/' . KtaiStyle::ADMIN_DIR, '!') . '!', @$_POST['redirect_to']) ) {
		$Ktai_Style->admin->logout();
	}
} else {
	$comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
	setcookie('comment_author_' . COOKIEHASH, $comment->comment_author, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
	setcookie('comment_author_email_' . COOKIEHASH, $comment->comment_author_email, time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
	setcookie('comment_author_url_' . COOKIEHASH, esc_url($comment->comment_author_url), time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN);
}

if (empty($_POST['redirect_to'])) {
	$location = add_query_arg('view', 'co_list', get_permalink($comment_post_ID));
} else {
	$location = stripslashes($_POST['redirect_to']);
}
$location = apply_filters('comment_post_redirect', $location, $comment);
wp_redirect($location);
exit;

} catch (Exception $e) {
	if (! isset($_POST['inline'])) {
		$Ktai_Style->ks_die($e->getMessage());
		exit();
	}
	global $ks_commentdata, $withcomments, $comment_post_ID, $comment_author, $comment_author_email, $comment_author_url, $comment_content, $comment_parent;
	$ks_commentdata['author']  = stripslashes($comment_author);
	$ks_commentdata['email']   = stripslashes($comment_author_email);
	$ks_commentdata['url']     = stripslashes($comment_author_url);
	$ks_commentdata['content'] = stripslashes($comment_content);
	$ks_commentdata['message'] = $e->getMessage();
	unset($_POST['author']);
	unset($_POST['email']);
	unset($_POST['url']);
	unset($_POST['comment']);
	if ($comment_parent) {
		$_GET['replytocom'] = $comment_parent;
	}
	$_POST['view'] = 'co_post'; // force ks_is_comment_post() to true
	query_posts("p=$comment_post_ID&view=co_post");
	$withcomments = true;
	$Ktai_Style->output();
	exit;
}
?>