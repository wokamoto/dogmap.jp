<?php
/* ==================================================
 *   KtaiStyle_Admin_Templates class
   ================================================== */

if (! defined('ABSPATH')) {
	exit;
}

class KtaiStyle_AdminTemplates extends KtaiStyle_Admin {

function __construct() {
	parent::__construct();
	add_filter('get_comment_excerpt', array($this, 'shrink_comment_excerpt'), 99);
}

/* ==================================================
 * @param	none
 * @return	none
 */
public function sid_field() {
	if ($this->sid && !$this->base->get('cookie_available')) {
		echo '<input type="hidden" name="' . parent::SESSION_NAME .'" value="' . htmlspecialchars($this->sid, ENT_QUOTES) . '" />';
	}
}

/* ==================================================
 * @param	string  $buffer
 * @return	string  $buffer
 */
public function add_link_sid($buffer) {
	if ( !$this->sid) {
		return $buffer;
	}
	for ($offset = 0, $replace = 'X' ; 
	     preg_match('!<a ([^>]*?)href=([\'"])(' . KtaiStyle::QUOTED_STRING_REGEX . ')\\2([^>]*?)>!s', $buffer, $l, PREG_OFFSET_CAPTURE, $offset) ; 
	     $offset += strlen($replace))
	{
		$orig    = $l[0][0];
		$offset  = $l[0][1];
		$url     = $l[3][0];
		$url     = _ks_quoted_remove_query_arg(self::SESSION_NAME, $url);
		$attr1   = $l[1][0];
		$attr2   = $l[4][0];
		$replace = $orig;
		if ($this->is_internal($url)) {
			$replace = sprintf('<a %shref="%s"%s>', $attr1, $this->add_sid($url, KTAI_DO_ECHO), $attr2); 
			$buffer = substr_replace($buffer, $replace, $offset, strlen($orig)); // convert links
		}
	}
	return $buffer;
}

/* ==================================================
 * @param	string  $excerpt
 * @return	string  $excerpt
 */
public function shrink_comment_excerpt($excerpt) {
	global $comment;
	$comment_text = $comment->comment_content;
	$comment_text = preg_replace('#<blockquote>.*?<blockquote>.*?</blockquote>.*?</blockquote>#s', __('--omitting quote--', 'ktai_style'), $comment_text); 
	$comment_text = preg_replace('#<blockquote>.*?</blockquote>#s', __('--omitting quote--', 'ktai_style'), $comment_text); 
	$blah = explode(' ', strip_tags($comment_text));
	if (count($blah) > 20) {
		$k = 20;
		$use_dotdotdot = 1;
	} else {
		$k = count($blah);
		$use_dotdotdot = 0;
	}
	$excerpt = '';
	for ($i=0; $i<$k; $i++) {
		$excerpt .= $blah[$i] . ' ';
	}
	$excerpt .= ($use_dotdotdot) ? '...' : '';
	return $excerpt;
}

/* ==================================================
 * @param	string  $name
 * @param	int     $selected
 * @return	string  $html
 */
public function dropdown_categories($name = 'default_category', $selected = 0) {
	global $wpdb;
	$html = '';
	$categories = get_categories('get=all');
	if (count($categories) < 1) {
		return $html;
	}
	$html .= '<select name="' . htmlspecialchars($name, ENT_QUOTES) . '">';
	foreach ($categories as $c) {
		$cat_id = isset($c->term_id) ? $c->term_id : $c->cat_ID;
		$html .= '<option value="' . intval($cat_id) . '"' . ($selected == $cat_id ? ' selected="selected"' : '') . '>' . esc_html($c->cat_name) . '</option>';
	}
	$html .= '</select>';
	return $html;
}


/* ==================================================
 * @param	string  $urk
 * @param	int     $id
 * @return	string  $context
 */
public function draft_or_post_title($post_id = 0) {
	$title = get_the_title($post_id);
	if ( empty($title) )
		$title = __('(no title)');
	return $title;
}

/* ==================================================
 * @param	object  $post
 * @return	string  $states
 * based on _post_states() at wp-admin/includes/template.php of WP 2.7
 */
public function get_post_states($post) {
	$post_states = array();
	if ( isset($_GET['post_status']) )
		$post_status = $_GET['post_status'];
	else
		$post_status = '';

	if ( !empty($post->post_password) )
		$post_states[] = __('Password protected', 'ktai_style');
	if ( 'private' == $post->post_status && 'private' != $post_status )
		$post_states[] = __('Private');
	if ( 'draft' == $post->post_status && 'draft' != $post_status )
		$post_states[] = __('Draft');
	if ( 'pending' == $post->post_status && 'pending' != $post_status )
		$post_states[] = __('Pending');

	if ( ! empty($post_states) ) {
		return ' - <small>' . implode(',', $post_states) . '</small>';
	}
	return '';
}

/* ==================================================
 * @param	string   $post_type
 * @param	string   $post_title
 * @return	string   $post_name
 * @since	2.0.0
 */
private function create_post_name($post_type = 'post', $post_title = '') {
	switch ($post_type) {
	case 'post':
		$post_name = mysql2date('His', current_time('mysql'));
		break;
	default:
		$post_name = sanitize_title_with_dashes($post_title);
		break;
	}
	return $post_name;
}

/* ==================================================
 * @param	none
 * @return	int     $post_ID
 * based on write_post() at wp-admin/includes/post.php of WP 2.3
 */
public function write_post($post_type = 'post') {
	global $current_user;
	if ('page' == $post_type) {
		if (! current_user_can('edit_pages')) {
			$this->base->ks_die(__('You are not allowed to create pages on this blog.'));
			exit;
		}
	} elseif (! current_user_can('edit_posts')) {
		$this->base->ks_die(__('You are not allowed to create posts or drafts on this blog.'));
		exit;
	}
	$charset = ks_detect_encoding();
	if ( !$this->base->get('encoding_converted') ) {
		foreach ( array('post_title','post_name','content','tags_input') as $f) {
			$_POST[$f] = $this->base->decode_from_ktai($_POST[$f], $charset);
		}
	}
	$post_data = &$_POST;
	$post_data['post_date']     = current_time('mysql');
	$post_data['post_date_gmt'] = get_gmt_from_date($post_data['post_date']);
	if (! isset($post_data['post_type'])) {
		$post_data['post_type'] = $post_type;
	}
	$post_data['post_title']    = trim(strip_tags($post_data['post_title']));
	$post_data['post_name']     = trim(strip_tags($post_data['post_name']));
	$post_data['post_content']  = trim(           $post_data['content']);
	$post_data['tags_input']    = trim(strip_tags($post_data['tags_input'] ));
	$post_data['post_excerpt']  = '';
	$post_data['post_author']   = intval($post_data['user_ID']);

	if (isset($post_data['post_cats'])) {
		$post_data['post_category'] = array_map('intval', explode(',', $post_data['post_cats']));
	}

	if (! isset( $post_data['comment_status'])) {
		$post_data['comment_status'] = 'closed';
	}
	if (! isset( $post_data['ping_status'])) {
		$post_data['ping_status'] = 'closed';
	}

	// What to do based on which button they pressed
	if (isset($post_data['publish']) && '' != $post_data['publish'] && $post_data['post_status'] != 'private') {
		$post_data['post_status'] = 'publish';
		if (empty($post_data['post_name'])) {
			$post_data['post_name'] = $this->create_post_name($post_type, $post_data['post_title']);
		}
	}
/*
	if (isset($post_data['advanced']) && '' != $post_data['advanced']) {
		$post_data['post_status'] = 'draft';
	}
 */
 	if ('publish' == $post_data['post_status'] && ! current_user_can('publish_posts')) {
		$post_data['post_status'] = 'pending';
	}

	if ( isset($post_data['visibility']) && 'private' == $post_data['visibility']) {
		$post_data['post_status'] = 'private';
		$post_data['post_password'] = '';
		unset( $post_data['sticky'] );
	}

	$post_ID = wp_insert_post($post_data);
	if (is_wp_error($post_ID)) {
		$this->base->ks_die($post_ID->get_error_message());
	} else {
		return $post_ID;
	}
}

/* ==================================================
 * @param	none
 * @return	int     $post_ID
 * based on edit_post() at wp-admin/includes/post.php of WP 2.3
 */
public function edit_post($post_ID, $post_type = 'post') {
	global $current_user;
	if (! $post_ID) {
		$this->base->ks_die(__("You attempted to edit a post that doesn't exist. Perhaps it was deleted?"));
		exit;
	} elseif ('page' == $post_type) {
		if (! current_user_can('edit_page', $post_ID)) {
			$this->base->ks_die(__('You are not allowed to edit this page.'));
			exit;
		}
	} elseif (! current_user_can('edit_post', $post_ID)) {
		$this->base->ks_die(__('You are not allowed to edit this post.'));
		exit;
	}
	$charset = ks_detect_encoding();
	if ( !$this->base->get('encoding_converted') ) {
		foreach ( array('post_title','post_name','content','tags_input') as $f) {
			$_POST[$f] = $this->base->decode_from_ktai($_POST[$f], $charset);
		}
	}
	$post = wp_get_single_post($post_ID, ARRAY_A);
	$post_data = &$_POST;
	$post_data['ID'] = intval($post_ID);
	if (! isset($post_data['post_type'])) {
		$post_data['post_type'] = $post_type;
	}
	$post_data['post_title']   = trim(strip_tags($post_data['post_title']));
	$post_data['post_name']    = trim(strip_tags($post_data['post_name']));
	$post_data['post_content'] = trim(           $post_data['content']);
	$post_data['tags_input']   = trim(strip_tags($post_data['tags_input'] ));
	$post_data['post_parent']  = isset($post_data['parent_id']) ? intval($post_data['parent_id']) : '';
	if ($post['post_author'] != $current_user->ID) {
		if ('page' == $post_type) {
			if (! current_user_can('edit_others_pages')) {
				$this->base->ks_die(__('You are not allowed to edit pages as this user.'));
			}
		} elseif (! current_user_can('edit_others_posts')) {
			$this->base->ks_die(__('You are not allowed to edit posts as this user.'));
		}
	}
	
	if (isset($post_data['post_cats'])) {
		$post_data['post_category'] = array_map('intval', explode(',', $post_data['post_cats']));
	}

	if (! isset( $post_data['comment_status'])) {
		$post_data['comment_status'] = 'closed';
	}
	if (! isset( $post_data['ping_status'])) {
		$post_data['ping_status'] = 'closed';
	}

	// What to do based on which button they pressed
	if (isset($post_data['publish']) && '' != $post_data['publish']) {
		$post_data['post_status'] = 'publish';
		if (empty($post_data['post_name'])) {
			$post_data['post_name'] = $post->post_name ? $post->post_name : $this->create_post_name($post_type, $post_data['post_title']);
		}
	}
	$previous_status = get_post_field('post_status', isset($post_data['ID']) ? $post_data['ID'] : $post_data['temp_ID']);
	if ( 'page' == $post_type ) {
		$publish_cap = 'publish_pages';
		$edit_cap = 'edit_published_pages';
	} else {
		$publish_cap = 'publish_posts';
		$edit_cap = 'edit_published_posts';
	}
	if ( isset($post_data['post_status']) && ('publish' == $post_data['post_status'] && !current_user_can( $publish_cap )) ) {
		if ( $previous_status != 'publish' || !current_user_can( $edit_cap ) )
			$post_data['post_status'] = 'pending';
	}
	if ( ! isset($post_data['post_status']) ) {
		$post_data['post_status'] = $previous_status;
	}

	if ( isset($post_data['visibility']) && 'private' == $post_data['visibility']) {
		$post_data['post_status'] = 'private';
		$post_data['post_password'] = '';
		unset( $post_data['sticky'] );
	}

	wp_update_post( $post_data );
	return intval($post_ID);
}

/* ==================================================
 * @param	string   $content
 * @param	int      $tab_index
 * @param	int      $rows
 * @return	none
 */
public function the_editor($content, $rows = 8, $tab_index = 4) {
	if ($rows < 1 || $rows > 100) {
		$rows = 8;
	}
	if ($tab_index < 1 || $tab_index > 100) {
		$tab_index = 4;
	}
	if (user_can_richedit()) { // Make sure the browsers which can use rich edit
		add_filter('the_editor_content', 'wp_htmledit_pre');
	}
	$the_editor = '<textarea cols="40" rows="' . intval($rows) . '" name="content" tabindex="' . intval($tab_index) . '">%s</textarea><br />';
	// $the_editor = apply_filters('the_editor', $the_editor);
	$the_editor_content = apply_filters('the_editor_content', $content);
	printf($the_editor, $the_editor_content);
	if (strlen($this->base->encode_for_ktai($the_editor_content)) >= $this->base->get('textarea_size')) {
		?><input type="hidden" name="content_overflow" value="1" />
		<font color="red"><img localsrc="1" alt="!" /><?php 
		_e('The content is too big is chopped to fit in the form. If you save changes, part of the content may be lost. Please back to previous page.', 'ktai_style');
		?></font><br /><?php
	}
}

/* ==================================================
 * @param	int      $current
 * @param	int      $total
 * @return	none
 */
public function nav_link($current, $total, $query = 'paged') {
	$link = remove_query_arg(array($query, 'filter', 'action', 'wpnonce'));
	$nav = '';
	if ($current > 1) {
		$nav .= '<a href="' . add_query_arg($query, $current - 1, $link) . '" accesskey="*">' . 
		__('<img localsrc="7" alt="&laquo;" />Previous Page', 'ktai_style') . 
		'</a>';
	}
	if ($current < $total) {
		if ($nav) {
			$nav .= ' | ';
		}
		$nav .= '<a href="' . add_query_arg($query, $current + 1, $link) . '" accesskey="#">' . 
		__('Next Page<img localsrc="8" alt="&raquo;" />', 'ktai_style') . 
		'</a>';
	}
	if ($nav) {
		echo '<p align="center">' . $nav . '</p>';
	}
}

/* ==================================================
 * @param	int      $current
 * @param	int      $total
 * @return	none
 */
public function nav_dropdown($current, $total, $query = 'paged') {
	if ($total == 2) {
		$this->nav_link($current, $total, $query);
	} else {
		ks_posts_nav_dropdown(array(
			'before' => '<div align="center">',
			'after' => '<br /></div>',
			'min_pages' => 3,
			'show_all_limit' => 9,
			'paged' => $current,
			'max_pages' => $total,
			'id' => $query,
			'baseurl' => remove_query_arg(array($query, 'filter', 'action', 'wpnonce')),
		));
	}
}

// ===== End of class ====================
}

/* ==================================================
 *   KtaiCategoryChecklist class
 *   based on class Walker_Category_Checklist at wp-admin/includes/template.php of WP 2.5.1
   ================================================== */

class KtaiCategory_Checklist extends Walker {
	public $tree_type = 'category';
//	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id');
	public $db_fields = array ('parent' => 'category_parent', 'id' => 'cat_ID');

	function start_lvl(&$output, $depth, $args) {
		return '';
	}

	function end_lvl(&$output, $depth, $args) {
		return '';
	}

	function start_el(&$output, $category, $depth, $args) {
		extract($args);

		$output .= '<dt>' . '<label><input value="' . $category->cat_ID . '" type="checkbox" name="cat[]" ' 
		. (in_array( $category->cat_ID, $selected_cats ) ? ' checked="checked"' : "" ) . '/> ' 
		. esc_html( apply_filters('the_category', $category->cat_name )) . '</label>';
		return $output;
	}

	function end_el(&$output, $category, $depth, $args) {
		$output .= '</dt>';
		return $output;
	}
}
?>