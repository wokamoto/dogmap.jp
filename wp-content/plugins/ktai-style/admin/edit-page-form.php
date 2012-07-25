<?php
/* ==================================================
 *   Ktai Admin Post Form
 *   based on wp-admin/edit-page-form.php of WP 2.7
   ================================================== */

if (! defined('ABSPATH')) {
	exit;
}
global $Ktai_Style;
$View = new KtaiAdmin_Page_Form($Ktai_Style);
$View->output();

/* ==================================================
 *   KtaiAdmin_Page_Form class
   ================================================== */

class KtaiAdmin_Page_Form {
	private $base;
	private $admin;
	private $orig_referer;
	private $message;
	private $messages;
	private $notices;

public function __construct($base) {
	$this->base  = $base;
	$this->admin = $base->admin;
	$this->orig_referer = wp_get_original_referer();
	if ( isset($_GET['message']) ) {
		$this->message = intval( $_GET['message'] );
		if ($this->message == 1 && ! $this->orig_referer) {
			$this->message = 4;
		}
	}
	$this->messages[1] = sprintf(__('Page updated. Continue editing below or <a href="%s">go back</a>.', 'ktai_style'), esc_attr($this->orig_referer));
	$this->messages[2] = __('Custom field updated.', 'ktai_style');
	$this->messages[3] = __('Custom field deleted.', 'ktai_style');
	$this->messages[4] = __('Page updated.', 'ktai_style');
	$this->messages[5] = __('Page published.', 'ktai_style');
	$this->messages[6] = __('Page submitted.', 'ktai_style');
	
	if ( isset($_GET['revision']) ) {
		$this->messages[5] = sprintf( __('Page restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) );
	}

	$this->notices[1] = __( 'There is an autosave of this page that is more recent than the version below.  <a href="%s">View the autosave</a>.' );

	global $post_ID, $temp_ID;
	$post_ID = isset($post_ID) ? intval($post_ID) : 0;
	$temp_ID = isset($temp_ID) ? intval($temp_ID) : 0;
}

// ==================================================
public function output() {
	global $action, $post_ID, $temp_ID, $post, $post_referredby;

	$notice = false;
	if ( 0 == $post_ID ) {
		$temp_ID = -1 * time(); // don't change this formula without looking at wp_write_post()
		$form_action = 'post';
		$nonce_action = 'add-page';
		$slug_optional = ' ' . __('(Optional)', 'ktai_style');
		$form_extra = '<input type="hidden" name="temp_ID" value="' . intval($temp_ID) . '" />';
	} else {
		$form_action = 'editpost';
		$nonce_action = 'update-page_' . $post_ID;
		$slug_optional = '';
		$form_extra = '<input type="hidden" name="post_ID" value="' . intval($post_ID) . '" />';
	}
	$can_publish = current_user_can('publish_pages');
	include dirname(__FILE__) . '/admin-header.php';
	if ( $notice ) : ?>
<p><font color="red"><?php echo $notice ?></font></p>
<?php endif;
	if (isset($this->message)) : ?>
<p><font color="teal"><?php echo $this->messages[$this->message]; ?></font></p>
<?php endif; ?>
<form method="post" action="page.php"><div>
<?php
	$this->admin->sid_field();
	ks_fix_encoding_form();
	wp_nonce_field($nonce_action, "_wpnonce", false); ?>
<input type="hidden" name="action" value='<?php echo $form_action ?>' />
<input type="hidden" name="originalaction" value="<?php echo $form_action ?>" />
<input type="hidden" name="post_author" value="<?php echo $post->post_author; ?>" />
<input type="hidden" name="referredby" value="<?php echo esc_attr($post_referredby); ?>" />
<?php if ('draft' != $post->post_status) { ?>
<input type="hidden" name="_wp_original_http_referer" value="<?php echo esc_attr($this->orig_referer); ?>" />
<?php }
	echo $form_extra;
	_e('Title'); ?><br />
<input type="text" name="post_title" size="32" maxlength="999" tabindex="1" value="<?php echo $post->post_title; ?>" /><br />
<?php if ($can_publish) {
		_e('Slug');
		echo esc_html($slug_optional); ?><br />
<input type="text" name="post_name" size="24" maxlength="999" istyle="3" mode="alphabet" tabindex="2" value="<?php echo $post->post_name; ?>" /><br />
<?php }
	_e('Content', 'ktai_style'); ?><br />
<?php $this->admin->the_editor($post->post_content); ?>
<div><label><input type="checkbox" name="comment_status" tabindex="6" value="open" <?php checked($post->comment_status, 'open'); ?> /><?php _e('Allow Comments'); ?></label><br />
<label><input type="checkbox" name="ping_status" tabindex="7" value="open" <?php checked($post->ping_status, 'open'); ?> /> <?php _e('Allow Pings'); ?></label></div>
<?php 
	$referer = $this->orig_referer ? $this->orig_referer : $post_referredby;
	if ($referer && $referer != 'redo') {
		if (preg_match('!/page(-new)?\.php(\?|$)!', $referer)) {
			$referer = 'edit-pages.php';
		}
		if ($this->message) {
			printf(__('<a href="%s">Back to lists</a>', 'ktai_style'), esc_attr($referer));
		} else {
			printf(__('<a href="%s">Cancel Edit</a>', 'ktai_style'), esc_attr($referer));
		}
	}
?> <input type="submit" name="save" tabindex="8" value="<?php _e('Save'); ?>" />
<?php
	if ( !in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID ) {
		if ( $can_publish ) {
			?><input type="submit" name="publish" tabindex="9" value="<?php _e('Publish'); ?>" /><?php 
		} else {
			?><input type="submit" name="publish" tabindex="p" value="<?php _e('Submit for Review') ?>" /><?php 
		}
	}
	if ('edit' == $action && current_user_can('delete_page', $post->ID)) {
		if (function_exists('wp_trash_post') && EMPTY_TRASH_DAYS) {
			$delete_url = sprintf('page.php?action=trash&post=%d', $post->ID);
			$delete_url = wp_nonce_url($delete_url, "trash-page_{$post->ID}");
			$delete_text = __('Move to Trash');
		} else {
			$delete_url = sprintf('page.php?action=delete&post=%d', $post->ID);
			$delete_text = __('Delete');
		}
		?><br /><img localsrc="61" /><a href="<?php echo esc_attr($delete_url); ?>"><font color="fuchsia"><?php echo $delete_text; ?></font></a><?php
	} ?>
<hr />
<?php
	if ( 'publish' == $post->post_status || 'private' == $post->post_status || $can_publish ) {
		_e('Status'); ?>: 
<select name="post_status">
<?php if ( 'publish' == $post->post_status ) : ?>
<option<?php selected( $post->post_status, 'publish' ); ?> value='publish'><?php _e('Published') ?></option>
<?php elseif ( 'private' == $post->post_status ) : ?>
<option<?php selected( $post->post_status, 'private' ); ?> value='publish'><?php _e('Privately Published', 'ktai_style') ?></option>
<?php elseif ( 'future' == $post->post_status ) : ?>
<option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php _e('Scheduled') ?></option>
<?php endif; ?>
<option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e('Pending Review') ?></option>
<option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e('Draft') ?></option>
</select>
<br />
<?php
	}
	_e('Visibility:', 'ktai_style'); ?> <?php
	if ( 'private' == $post->post_status ) {
		$post->post_password = '';
		$visibility = 'private';
		$visibility_trans = __('Private');
	} elseif ( !empty( $post->post_password ) ) {
		$visibility = 'password';
		$visibility_trans = __('Password protected', 'ktai_style');
	} else {
		$visibility = 'public';
		$visibility_trans = __('Public', 'ktai_style');
	}
	echo esc_html( $visibility_trans ); ?><br /><?php 
	if ($can_publish) {
		?><label><input type="checkbox" name="visibility" tabindex="10" value="private" <?php checked( $visibility, 'private' ); ?> /><?php _e('Turn to private', 'ktai_style'); ?></label><br /><?php 
	} ?>
<label for="parent_id"><?php _e('Parent Page:', 'ktai_style'); ?></label><br />
<?php wp_dropdown_pages(array('exclude_tree' => $post->ID, 'selected' => $post->post_parent, 'name' => 'parent_id', 'show_option_none' => __('Main Page (no parent)'), 'sort_column'=> 'menu_order, post_title')); ?><br />
<label for="menu_order"><?php _e('Page Order:', 'ktai_style'); ?></label>
<input name="menu_order" type="text" size="4" value="<?php echo $post->menu_order ?>" />
<?php
	if ($post_ID) {
		echo '<br />';
		if ( $last_id = get_post_meta($post_ID, '_edit_last', true) ) {
			$last_user = get_userdata($last_id);
			printf(__('Last edited by %1$s on %2$s at %3$s'), esc_html( $last_user->display_name ), mysql2date(get_option('date_format'), $post->post_modified), mysql2date(get_option('time_format'), $post->post_modified));
		} else {
			printf(__('Last edited on %1$s at %2$s'), mysql2date(get_option('date_format'), $post->post_modified), mysql2date(get_option('time_format'), $post->post_modified));
		}
	}
?>
</div></form>
<?php 
	include dirname(__FILE__) . '/admin-footer.php'; 
}

// ===== End of class ====================
}
?>