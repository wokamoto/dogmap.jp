<?php
/* Header and Footer of Widget */
register_sidebar(array(
	'name' => 'navi',
	'before_widget' => "\t\t<dt id=\"".'%1$s'."\" class=\"widget ".'%1$s'."\">",
	'after_widget' => "\t\t</dd>\n",
	'before_title' => '',
	'after_title' => "</dt>\n\t\t<dd>\n",
));
register_sidebar(array(
	'name' => 'others',
	'before_widget' => "\t\t<dt id=\"".'%1$s'."\" class=\"widget ".'%1$s'."\">",
	'after_widget' => "\t\t</dd>\n",
	'before_title' => '',
	'after_title' => "</dt>\n\t\t<dd>\n",
));
register_sidebar(array(
	'name' => 'header',
	'before_widget' => "\t\t<dt id=\"".'%1$s'."\" class=\"widget ".'%1$s'."\">",
	'after_widget' => "\t\t</dd>\n",
	'before_title' => '',
	'after_title' => "</dt>\n\t\t<dd>\n",
));
register_sidebar(array(
	'name' => 'footer',
	'before_widget' => "\t\t<dt id=\"".'%1$s'."\" class=\"widget ".'%1$s'."\">",
	'after_widget' => "\t\t</dd>\n",
	'before_title' => '',
	'after_title' => "</dt>\n\t\t<dd>\n",
));

/**
 * Display a widget of Pages. (in vicuna sidebar)
 */
function vicuna_widget_pages($args) {
	if ($pages = &get_pages('')) {
		extract($args);
		extract($args, EXTR_SKIP);
		$options = get_option('widget_pages');
		$title = empty($options['title']) ? __('Pages') : $options['title'];
		$sortby = empty($options['sortby']) ? '' : $options['sortby'];
		$exclude = empty($options['exclude']) ? '' : $options['exclude'];
		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo '<ul class="pages">';
		wp_list_pages('sort_column='.$sortby.'&title_li=0&exclude='.$exclude);
		echo '</ul>';
		echo $after_widget;
	}
}

function vicuna_widget_pages_control() {
	$options = $newoptions = get_option('widget_pages');
	if ( $_POST['pages-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['pages-title']));
		$sortby = stripslashes( $_POST['pages-sortby'] );
		if ( in_array( $sortby, array( 'post_title', 'menu_order', 'ID' ) ) ) {
			$newoptions['sortby'] = $sortby;
		} else {
			$newoptions['sortby'] = 'menu_order';
		}
		$newoptions['exclude'] = strip_tags( stripslashes( $_POST['pages-exclude'] ) );
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_pages', $options);
	}
	$title = attribute_escape($options['title']);
	$exclude = attribute_escape( $options['exclude'] );
?>
	<p><label for="pages-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="pages-title" name="pages-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="pages-sortby"><?php _e( 'Sort by:' ); ?>
	<select name="pages-sortby" id="pages-sortby">
	<option value="post_title"<?php selected( $options['sortby'], 'post_title' ); ?>><?php _e('Page title'); ?></option>
	<option value="menu_order"<?php selected( $options['sortby'], 'menu_order' ); ?>><?php _e('Page order'); ?></option>
	<option value="ID"<?php selected( $options['sortby'], 'ID' ); ?>><?php _e( 'Page ID' ); ?></option>
	</select></label></p>
	<p><label for="pages-exclude"><?php _e( 'Exclude:' ); ?> <input type="text" value="<?php echo $exclude; ?>" name="pages-exclude" id="pages-exclude" style="width: 220px;" /></label><br />
	<small><?php _e( 'Page IDs, separated by commas.' ); ?></small></p>
	<input type="hidden" id="pages-submit" name="pages-submit" value="1" />
	<p><?php _e('list(Reference):', 'vicuna'); ?><?php vicuna_view_selectlist_pages(); ?></p>
<?php
}

/**
 * Display a widget of Recent Posts. (in vicuna sidebar)
 */
function vicuna_widget_recent_posts($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_recent_posts');
	$title = empty($options['title']) ? __('Recent Posts') : $options['title'];
	if ( !$number = (int) $options['number'] )
		$number = 5;
	else if ( $number < 1 )
		$number = 1;
	else if ( $number > 15 )
		$number = 15;
	echo $before_widget;
	echo $before_title . $title . $after_title;
	echo '<ul class="recentEntries">';
	wp_get_archives('type=postbypost&limit='.$number);
	echo '</ul>';
	echo $after_widget;
}

function vicuna_widget_recent_posts_control() {
	$options = $newoptions = get_option('widget_recent_posts');
	if ( $_POST['recent_posts-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['recent_posts-title']));
		$newoptions['number'] = stripslashes( $_POST['recent_posts-number'] );
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_recent_posts', $options);
	}
	$title = attribute_escape($options['title']);
	if ( !$number = (int) $options['number'] )
		$number = 5;
?>
	<p><label for="recent_posts-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="recent_posts-title" name="recent_posts-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="recent_posts-number"><?php _e('Number of posts to show:'); ?> <input style="width: 30px;" id="recent_posts-number" name="recent_posts-number" type="text" value="<?php echo $number; ?>" /></label><br />
	<small><?php _e('(at most 15)'); ?></small></p>
	<input type="hidden" id="recent_posts-submit" name="recent_posts-submit" value="1" />
<?php
}

/**
 * Display a widget of Categories. (in vicuna sidebar)
 */
function vicuna_widget_categories($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_categories');
	$title = empty($options['title']) ? __('Categories') : $options['title'];
	$sortby = empty($options['sortby']) ? '' : $options['sortby'];
	$exclude = empty($options['exclude']) ? '' : $options['exclude'];
	echo $before_widget;
	echo $before_title . $title . $after_title;
	echo '<ul class="category">';
	wp_list_cats('sort_column='.$sortby.'&optioncount=0&hierarchical=1&exclude='.$exclude);
	echo '</ul>';
	echo $after_widget;
}

function vicuna_widget_categories_control() {
	$options = $newoptions = get_option('widget_categories');
	if ( $_POST['categories-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['categories-title']));
		$sortby = stripslashes( $_POST['categories-sortby'] );
		if ( in_array( $sortby, array( 'name', 'ID' ) ) ) {
			$newoptions['sortby'] = $sortby;
		} else {
			$newoptions['sortby'] = 'name';
		}
		$newoptions['exclude'] = strip_tags( stripslashes( $_POST['categories-exclude'] ) );
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_categories', $options);
	}
	$title = attribute_escape($options['title']);
	$exclude = attribute_escape( $options['exclude'] );
?>
	<p><label for="categories-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="categories-title" name="categories-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="categories-sortby"><?php _e( 'Sort by:' ); ?>
	<select name="categories-sortby" id="categories-sortby">
	<option value="post_title"<?php selected( $options['sortby'], 'post_title' ); ?>><?php _e('Category title'); ?></option>
	<option value="menu_order"<?php selected( $options['sortby'], 'name' ); ?>><?php _e('Category order'); ?></option>
	<option value="ID"<?php selected( $options['sortby'], 'ID' ); ?>><?php _e( 'Category ID' ); ?></option>
	</select></label></p>
	<p><label for="categories-exclude"><?php _e( 'Exclude:' ); ?> <input type="text" value="<?php echo $exclude; ?>" name="categories-exclude" id="categories-exclude" style="width: 220px;" /></label><br />
	<small><?php _e( 'Category IDs, separated by commas.' ); ?></small></p>
	<input type="hidden" id="categories-submit" name="categories-submit" value="1" />
	<p><?php _e('list(Reference):', 'vicuna'); ?><?php vicuna_view_selectlist_categories(); ?></p>
<?php
}

/**
 * Display a widget of Archives. (in vicuna sidebar)
 */
function vicuna_widget_archives($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_archives');
	$title = empty($options['title']) ? __('Archives') : $options['title'];
	echo $before_widget;
	echo $before_title . $title . $after_title;
	echo '<ul class="archives">';
	vicuna_archives_link();
	echo '</ul>';
	echo $after_widget;
}

function vicuna_widget_archives_control() {
	$options = $newoptions = get_option('widget_archives');
	if ( $_POST['archives-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['archives-title']));
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_archives', $options);
	}
	$title = attribute_escape($options['title']);
?>
	<p><label for="archives-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="archives-title" name="archives-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<input type="hidden" id="archives-submit" name="archives-submit" value="1" />
<?php
}

/**
 * Display a widget of Tag Cloud. (in vicuna sidebar)
 */
function vicuna_widget_tag_cloud($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_tag_cloud');
	$title = empty($options['title']) ? __('Tag Cloud') : $options['title'];
	echo $before_widget;
	echo $before_title . $title . $after_title;
	vicuna_tag_cloud();
	echo $after_widget;
}

function vicuna_widget_tag_cloud_control() {
	$options = $newoptions = get_option('widget_tag_cloud');
	if ( $_POST['tag_cloud-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['tag_cloud-title']));
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_tag_cloud', $options);
	}
	$title = attribute_escape($options['title']);
?>
	<p><label for="tag_cloud-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="tag_cloud-title" name="tag_cloud-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<input type="hidden" id="tag_cloud-submit" name="tag_cloud-submit" value="1" />
<?php
}

/**
 * Display a widget of Search. (in vicuna sidebar)
 */
function vicuna_widget_search($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_search');
	$title = empty($options['title']) ? __('Search') : $options['title'];
	echo $before_widget;
	echo $before_title . $title . $after_title;
?>
	<form method="get" action="<?php bloginfo('home'); ?>/">
		<fieldset>
			<legend><label for="searchKeyword"><?php printf(__('Search %s', 'vicuna'), get_bloginfo('name')); ?></label></legend>
			<div>
				<input type="text" class="inputField" id="searchKeyword" name="s" size="10" onfocus="if (this.value == '<?php _e('Keyword(s)', 'vicuna'); ?>') this.value = '';" onblur="if (this.value == '') this.value = '<?php _e('Keyword(s)', 'vicuna'); ?>';" value="<?php if ( is_search() ) echo wp_specialchars($s, 1); else _e('Keyword(s)', 'vicuna'); ?>" />
				<input type="submit" class="submit" id="submit" value="<?php _e('Search'); ?>" />
			</div>
		</fieldset>
	</form>
<?php
	echo $after_widget;
}

function vicuna_widget_search_control() {
	$options = $newoptions = get_option('widget_search');
	if ( $_POST['search-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['search-title']));
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_search', $options);
	}
	$title = attribute_escape($options['title']);
?>
	<p><label for="search-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="search-title" name="search-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<input type="hidden" id="search-submit" name="search-submit" value="1" />
<?php
}

/**
 * Display a widget of Feeds. (in vicuna sidebar)
 */
function vicuna_widget_feeds($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_feeds');
	$title = empty($options['title']) ? __('Feeds', 'vicuna') : $options['title'];
	if ( !$icon = (int) $options['icon'] )
		$icon = 0;
	else if ( $icon != 1 )
		$icon = 0;
	$style = '';
	if($icon == 1){
		$style = 'style="padding-left: 4px; list-style-image: url('.get_bloginfo('template_directory').'/script/image/feed.png);"';
	}
	echo $before_widget;
	echo $before_title . $title . $after_title;
?>
	<ul class="feeds">
		<li class="rss" <?php echo $style; ?>><a href="<?php bloginfo('rss2_url'); ?>"><?php _e('All Entries', 'vicuna'); ?>(RSS2.0)</a></li>
		<li class="atom"<?php echo $style; ?>><a href="<?php bloginfo('atom_url'); ?>"><?php _e('All Entries', 'vicuna'); ?>(Atom)</a></li>
		<li class="rss"<?php echo $style; ?>><a href="<?php bloginfo('comments_rss2_url'); ?>"><?php _e('All Comments', 'vicuna'); ?>(RSS2.0)</a></li>
	</ul>
<?php
	echo $after_widget;
}

function vicuna_widget_feeds_control() {
	$options = $newoptions = get_option('widget_feeds');
	if ( $_POST['feeds-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['feeds-title']));
		$newoptions['icon'] = stripslashes($_POST['feeds-icon']);
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_feeds', $options);
	}
	$title = attribute_escape($options['title']);
	if ( !$icon = (int) $options['icon'] )
		$icon = 0;
	else if ( $icon != 1 )
		$number = 0;
?>
	<p><label for="feeds-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="feeds-title" name="feeds-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="feeds-icon"><?php _e('Display Feed Icons:', 'vicuna'); ?><br />
		<input type="radio" name="feeds-icon" value="0"<?php if($icon != 1){ ?> checked="checked"<?php } ?>/><?php _e('No'); ?>
		<input type="radio" name="feeds-icon" value="1"<?php if($icon == 1){ ?> checked="checked"<?php } ?>/><?php _e('Yes'); ?>
	</label></p>
	<input type="hidden" id="feeds-submit" name="feeds-submit" value="1" />
<?php
}

/**
 * Display a widget of Meta. (in vicuna sidebar)
 */
function vicuna_widget_meta($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_meta');
	$title = empty($options['title']) ? __('Meta') : $options['title'];
	echo $before_widget;
	echo $before_title . $title . $after_title;
?>
	<ul class="meta">
		<li><a href="http://validator.w3.org/check/referer" title="<?php _e('This page validates as XHTML 1.0 Strict', 'vicuna'); ?>" rel="nofollow"><?php printf(__('Valid <abbr title="eXtensible HyperText Markup Language">XHTML</abbr>', 'vicuna')); ?></a></li>
		<?php wp_register(); ?>
		<li><?php wp_loginout(); ?></li>
		<?php wp_meta(); ?>
	</ul>
<?php
	echo $after_widget;
}

function vicuna_widget_meta_control() {
	$options = $newoptions = get_option('widget_meta');
	if ( $_POST['meta-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['meta-title']));
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_meta', $options);
	}
	$title = attribute_escape($options['title']);
?>
	<p><label for="meta-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="meta-title" name="meta-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<input type="hidden" id="meta-submit" name="meta-submit" value="1" />
<?php
}

/**
 * Display a widget of Calendar.
 */
function vicuna_widget_calendar($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_calendar');
	$title = empty($options['title']) ? __('Calendar') : $options['title'];
	echo $before_widget . $before_title . $title . $after_title;
	vicuna_calendar();
	echo $after_widget;
}

/**
 * Display a widget of Layout Manager.
 */
function vicuna_widget_layout_manager($args) {
        if ( ! current_user_can('switch_themes') )
		return;
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('vicuna_layout');
	$title = empty($options['title']) ? __('Layout Manager', 'vicuna') : $options['title'];
	echo $before_widget . $before_title . $title . $after_title;
	$layout_name = '';
	$opt_name = '';
	if (is_home()) {
		$layout_name = 'Index Layout';
		$opt_name = 'index_layout';
	} else if (is_category() ) {
		$layout_name = 'Category Layout';
		$opt_name = 'category_layout';
	} else if (is_archive()) {
		$layout_name = 'Archive Layout';
		$opt_name = 'archive_layout';
	} else if (is_search()) {
		$layout_name = 'Search Layout';
		$opt_name = 'search_layout';
	} else if (is_page()) {
		$layout_name = 'Page Layout';
		$opt_name = 'page_layout';
	} else if (is_single()) {
		$layout_name = 'Single Layout';
		$opt_name = 'single_layout';
	} else if (is_404()) {
		$layout_name = '404 Layout';
		$opt_name = '404_layout';
	} else if (is_tag()) {
		$layout_name = 'Tag Layout';
		$opt_name = 'tag_layout';
	}
?>
	<form method="post">
		<fieldset>
			<legend><label for="layout"><?php _e($layout_name, 'vicuna'); ?></label></legend>
			<div>
				<?php vicuna_layout_selector('vicuna_'.$opt_name, $options[$opt_name] , $options['multi_'.$opt_name]); ?>
				<input type="submit" class="submit" id="submit" value="<?php _e('Save'); ?>" />
			</div>
		</fieldset>
	</form>
<?php
	echo $after_widget;
}

/**
 * Display a widget of Recent Comments.
 */
function vicuna_widget_recent_comments($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_recent_comments');
	$title = empty($options['title']) ? __('Recent Comments') : $options['title'];
	if ( !$number = (int) $options['number'] )
		$number = 5;
	else if ( $number < 1 )
		$number = 1;
	else if ( $number > 15 )
		$number = 15;
	echo $before_widget . $before_title . $title . $after_title;
	get_recent_comments_list($number);
	echo $after_widget;
}

function vicuna_widget_recent_comments_control() {
	$options = $newoptions = get_option('widget_recent_comments');
	if ( $_POST['recent_comments-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['recent_comments-title']));
		$newoptions['number'] = stripslashes( $_POST['recent_comments-number'] );
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_recent_comments', $options);
		vicuna_delete_recent_comments_cache();
	}
	$title = attribute_escape($options['title']);
	if ( !$number = (int) $options['number'] )
		$number = 5;
?>
	<p><label for="recent_comments-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="recent_comments-title" name="recent_comments-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="recent_comments-number"><?php _e('Number of posts to show:'); ?> <input style="width: 30px;" id="recent_comments-number" name="recent_comments-number" type="text" value="<?php echo $number; ?>" /></label><br />
	<small><?php _e('(at most 15)'); ?></small></p>
	<input type="hidden" id="recent_comments-submit" name="recent_comments-submit" value="1" />
<?php
}

/**
 * Display a widget of Recent Pings.
 */
function vicuna_widget_recent_pings($args) {
	extract($args);
	extract($args, EXTR_SKIP);
	$options = get_option('widget_recent_pings');
	$title = empty($options['title']) ? __('Recent Pings', 'vicuna') : $options['title'];
	if ( !$number = (int) $options['number'] )
		$number = 5;
	else if ( $number < 1 )
		$number = 1;
	else if ( $number > 15 )
		$number = 15;
	echo $before_widget . $before_title . $title . $after_title;
	get_recent_pings_list($number);
	echo $after_widget;
}

function vicuna_widget_recent_pings_control() {
	$options = $newoptions = get_option('widget_recent_pings');
	if ( $_POST['recent_pings-submit'] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['recent_pings-title']));
		$newoptions['number'] = (int) $_POST["recent-pings-number"];
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_recent_pings', $options);
		vicuna_delete_recent_comments_cache();
	}
	$title = attribute_escape($options['title']);
	if ( !$number = (int) $options['number'] )
		$number = 5;
?>
	<p><label for="recent_pings-title"><?php _e('Title:'); ?> <input style="width: 220px;" id="recent_pings-title" name="recent_pings-title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="recent_pings-number"><?php _e('Number of posts to show:'); ?> <input style="width: 30px;" id="recent_pings-number" name="recent_pings-number" type="text" value="<?php echo $number; ?>" /></label><br />
	<small><?php _e('(at most 15)'); ?></small></p>
	<input type="hidden" id="recent_pings-submit" name="recent_pings-submit" value="1" />
<?php
}

/**
 * Register Sidebar. (in vicuna sidebar)
 */
/* Override Widget for WP 2.8.0 */
$options = get_option('vicuna_widget');
if((int)$options['pages'] != 1){
	wp_register_sidebar_widget('pages', __('Pages').'(vicuna)' , 'vicuna_widget_pages');
	wp_register_widget_control('pages', __('Pages'), 'vicuna_widget_pages_control');
}
if((int)$options['recent_posts'] != 1){
	wp_register_sidebar_widget('recent-posts', __('Recent Posts').'(vicuna)' , 'vicuna_widget_recent_posts');
	wp_register_widget_control('recent-posts', __('Recent Posts'), 'vicuna_widget_recent_posts_control');
}
if((int)$options['categories'] != 1){
	wp_register_sidebar_widget('categories', __('Categories').'(vicuna)' , 'vicuna_widget_categories');
	wp_register_widget_control('categories', __('Categories'), 'vicuna_widget_categories_control');
}
if((int)$options['archives'] != 1){
	wp_register_sidebar_widget('archives', __('Archives').'(vicuna)', 'vicuna_widget_archives');
	wp_register_widget_control('archives', __('Archives'), 'vicuna_widget_archives_control');
}
if((int)$options['tag_cloud'] != 1){
	wp_register_sidebar_widget('tag_cloud', __('Tag Cloud').'(vicuna)', 'vicuna_widget_tag_cloud');
	wp_register_widget_control('tag_cloud', __('Tag Cloud'), 'vicuna_widget_tag_cloud_control');
}
if((int)$options['search'] != 1){
	wp_register_sidebar_widget('search', __('Search').'(vicuna)', 'vicuna_widget_search');
	wp_register_widget_control('search', __('Search'), 'vicuna_widget_search_control');
}
if((int)$options['meta'] != 1){
	wp_register_sidebar_widget('meta', __('Meta').'(vicuna)', 'vicuna_widget_meta');
	wp_register_widget_control('meta', __('Meta'), 'vicuna_widget_meta_control');
}
if((int)$options['calendar'] != 1){
	wp_register_sidebar_widget('calendar', __('Calendar').'(vicuna)', 'vicuna_widget_calendar');
}
if((int)$options['recent_comments'] != 1){
	wp_register_sidebar_widget('recent-comments', __('Recent Comments').'(vicuna)', 'vicuna_widget_recent_comments');
	wp_register_widget_control('recent-comments', __('Recent Comments'), 'vicuna_widget_recent_comments_control');
}
/* non Override Widget for WP 2.8.0 */
wp_register_sidebar_widget('recent-pings', __('Recent Pings', 'vicuna').'(vicuna)', 'vicuna_widget_recent_pings');
wp_register_widget_control('recent-pings', __('Recent Pings'), 'vicuna_widget_recent_pings_control');
wp_register_sidebar_widget('feeds', __('Feeds', 'vicuna').'(vicuna)', 'vicuna_widget_feeds');
wp_register_widget_control('feeds', __('Feeds', 'vicuna'), 'vicuna_widget_feeds_control');
wp_register_sidebar_widget('layout-manager', __('Layout Manager', 'vicuna').'(vicuna)', 'vicuna_widget_layout_manager');


/* functions */

function get_recent_comments_list($number = 5) {
	global $wpdb, $comments, $comment;
	if ( !$comments = wp_cache_get( 'recent_comments', 'widget' ) ) {
		$comments = $wpdb->get_results("SELECT comment_author, comment_author_url, comment_ID, comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_approved = '1' AND comment_type = '' ORDER BY comment_date_gmt DESC LIMIT $number");
		wp_cache_add( 'recent_comments', $comments, 'widget' );
	}
?>
	<ul id="recent_comments">
<?php	if ( $comments ) {
		$post_ID = -1;
		foreach ($comments as $comment) {
			if ($comment->comment_post_ID != $post_ID) {
				if ($post_ID >= 0) {
?>
					</ul></li>
<?php				}
				$post_ID = $comment->comment_post_ID;
?>
				<li class="comment_on"><a href="<?php echo get_permalink($post_ID); ?>#comments"><?php echo get_the_title($post_ID); ?></a><ul>
<?php
			} ?>
			<li class="comment_author"><a href="<?php echo get_permalink($post_ID); ?>#comment<?php echo $comment->comment_ID; ?>"><?php comment_time('Y-m-d'); ?> <?php comment_author(); ?></a></li>
<?php
		}
	}
?>
		</ul></li>
	</ul>
<?php
}

function get_recent_pings_list($number = 5) {
	global $wpdb, $comments, $comment;
	if ( !$comments = wp_cache_get( 'recent_pings', 'widget' ) ) {
		$comments = $wpdb->get_results("SELECT comment_author, comment_author_url, comment_ID, comment_post_ID, comment_date FROM $wpdb->comments WHERE comment_approved = '1' AND (comment_type = 'trackback' OR comment_type = 'pingback') ORDER BY comment_date_gmt DESC LIMIT $number");
		wp_cache_add( 'recent_pings', $comments, 'widget' );
	}
?>
	<ul id="recentpings">
<?php	if ( $comments ) {
		$post_ID = -1;
		foreach ($comments as $comment) {
			if ($comment->comment_post_ID != $post_ID) {
				if ($post_ID >= 0) {
?>
					</ul></li>
<?php				}
				$post_ID = $comment->comment_post_ID;
?>
				<li class="recentpings"><a href="<?php echo get_permalink($post_ID); ?>#trackback"><?php echo get_the_title($post_ID); ?></a><ul>
<?php
			}
?>
			<li class="recentpings"><a href="<?php echo get_permalink($post_ID); ?>#ping<?php echo $comment->comment_ID ?>"><? comment_time('Y-m-d'); ?> <?php comment_author(); ?></a></li>
<?php
		}
	}
?>
		</ul></li>
	</ul>
<?php
}

function vicuna_delete_recent_comments_cache() {
	wp_cache_delete( 'recent_comments', 'widget' );
	wp_cache_delete( 'recent_pings', 'widget' );
}

add_action( 'comment_post', 'vicuna_delete_recent_comments_cache' );
add_action( 'wp_set_comment_status', 'vicuna_delete_recent_comments_cache' );

?>
