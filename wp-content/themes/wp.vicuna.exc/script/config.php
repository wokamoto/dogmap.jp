<?php

/**
 * add menu of vicuna options
 */
function add_vicuna_config_menu() {
        if ( ! current_user_can('switch_themes') )
		return;
	add_theme_page('Vicuna Config', __('Vicuna Config', 'vicuna'), 0, basename(__FILE__), 'vicuna_config_menu');
}
add_action( 'admin_menu', 'add_vicuna_config_menu' );

function vicuna_global_navigation() {
	$options = get_option('vicuna_config');
	$p_exclude = '';
	$c_exclude = '';
	if ( $options['g_navi_pages-exclude'] ) {
		$p_exclude = attribute_escape( $options['g_navi_pages-exclude'] );
	}
	if ( $options['g_navi_categories-exclude'] ) {
		$c_exclude = attribute_escape( $options['g_navi_categories-exclude'] );
	}
	if ( $options['g_navi'] ) {
?>
	<ul id="globalNavi">
<?php
	if ( $options['g_navi_home'] ) { ?>
		<li<?php if ( is_front_page() ) { echo ' class=home_page_item'; } ?> ><a href="<?php bloginfo('home'); ?>" title="Home">Home</a></li>
<?php
	}
	if ( $options['g_navi'] == 1 ) {	// pages
		wp_list_pages('sort_column=menu_order&title_li=0&depth=1&exclude='.$p_exclude);
	} else if ($options['g_navi'] == 2 ) {	// categories
		wp_list_categories('title_li=0&hierarchical=0&exclude='.$c_exclude);
	} else if ($options['g_navi'] == 3 ) {	// pages + categories
		wp_list_pages('sort_column=menu_order&title_li=0&depth=1&exclude='.$p_exclude);
		wp_list_categories('title_li=0&hierarchical=0&exclude='.$c_exclude);
	} else if ($options['g_navi'] == 4 ) {	// categories + pages
		wp_list_categories('title_li=0&hierarchical=0&exclude='.$c_exclude);
		wp_list_pages('sort_column=menu_order&title_li=0&depth=1&exclude='.$p_exclude);
	}
?>
	</ul>
<?php
	}
}

/**
 * Display the title of this page.
 */
function vicuna_title($title) {
	$options = get_option('vicuna_config');
	if ($options['title']) {
		echo $title . ' - ' . get_bloginfo('name');
	} else {
		echo get_bloginfo('name') . ' - ' . $title;
	}
}

/**
 * Display a page of config.
 */
function vicuna_config_menu() {
        if ( ! current_user_can('switch_themes') ) {
		return;
	}
	$options = get_option('vicuna_config');
	$widget_options = get_option('vicuna_widget');
?>
<div class="wrap">
	<h2><?php _e('Vicuna Config', 'vicuna'); ?></h2>
	<p><?php _e('You can customize the vicuna theme.', 'vicuna'); ?></p>
	<form method="post" action="<?php echo attribute_escape($_SERVER['REQUEST_URI']); ?>">
		<p class="submit">
			<input type="submit" value="<?php _e('Save Changes &raquo;', 'vicuna'); ?>" />
		</p>
<?php
		$ex_image_style = 'position: relative; float: right; ';
		$ex_image_style = $ex_image_style.'right: 20px; width: 300px;';
		$ex_image_style = $ex_image_style.'padding-left: 10px; ';
		$ex_image_style = $ex_image_style.'border: 4px solid #aaa; background-color: #ddd;';
		$ex_image_path = get_bloginfo('template_directory').'/script/image/';
?>
		<div style="<?php echo $ex_image_style; ?>">
			<strong>Eye Catch Type</strong><br /><br />
			<input type="radio" name="sel_ex" onClick="change_ex(0);" checked="checked" />none<br />
			<input type="radio" name="sel_ex" onClick="change_ex(1);" />Header are<br />
			<input type="radio" name="sel_ex" onClick="change_ex(2);" />Header area (Banner type)<br />
			<input type="radio" name="sel_ex" onClick="change_ex(3);" />Contents area<br />
			<input type="radio" name="sel_ex" onClick="change_ex(4);" />Main area<br />
			<br />
			<img name="img_layout" src="" width="120" height="100" /><br /><br />
		</div>
		<script type="text/javascript">
		<!--
		var ex_image = new Array();
		ex_image[0] = "<?php echo $ex_image_path; ?>eye-none.png";
		ex_image[1] = "<?php echo $ex_image_path; ?>eye-h.png";
		ex_image[2] = "<?php echo $ex_image_path; ?>eye-hb.png";
		ex_image[3] = "<?php echo $ex_image_path; ?>eye-c.png";
		ex_image[4] = "<?php echo $ex_image_path; ?>eye-m.png";
		function change_ex(no){
		  document.forms[0].img_layout.src = ex_image[no];
		}
		change_ex(0);
		// -->
		</script>
		<dl>
			<dt><?php _e('Skin'); ?></dt>
			<dd><select name="vicuna_skin">
<?php
			$skin = $options['skin'];
			foreach (get_skin_dirs() as $file) : ?>
				<option<?php if ($file == $skin) : echo ' selected'; endif; ?>><?php echo $file; ?></option>
<?php
			endforeach;
?>
			</select></dd>
			<dt><?php _e('Language'); ?></dt>
			<dd><select name="vicuna_language">
				<option value="">default</option>
<?php
				$language = $options['language'];
				foreach (get_languages() as $lang) : ?>
					<option<?php if ($lang == $language) : echo ' selected'; endif; ?>><?php echo $lang; ?></option>
<?php
				endforeach;
?>
			</select></dd>
			<dt><?php _e('Eye Catch Type'); ?></dt>
			<?php $eye_catch = $options['eye_catch']; ?>
			<dd><select name="vicuna_eye_catch">
				<option value="0">none</option>
				<option value="eye-h"<?php if ($eye_catch == 'eye-h') : echo ' selected'; endif; ?>>Header area</option>
				<option value="eye-hb"<?php if ($eye_catch == 'eye-hb') : echo ' selected'; endif; ?>>Header area (Banner type)</option>
				<option value="eye-c"<?php if ($eye_catch == 'eye-c') : echo ' selected'; endif; ?>>Contents area</option>
				<option value="eye-m"<?php if ($eye_catch == 'eye-m') : echo ' selected'; endif; ?>>Main area</option>
			</select></dd>
			<dt>Feed Type</dt>
			<dd><select name="vicuna_feed_type">
				<option value="0">default</option>
				<option value="rss+xml"<?php if ($options['feed_type'] == 'rss+xml') : ?> selected<?php endif; ?>>rss+xml</option>
				<option value="atom+xml"<?php if ($options['feed_type'] == 'atom+xml') : ?> selected<?php endif; ?>>atom+xml</option>
			</select></dd>
			<dt>Feed URL</dt>
			<dd><input type="text" name="vicuna_feed_url" value="<?php echo $options['feed_url']; ?>" /></dd>
			<dt>Title Tag</dt>
			<dd><select name="vicuna_title">
				<option value="0"<?php if (!$options['title']) : ?> selected<?php endif; ?>>Blog Name  - Entry Title</option>
				<option value="1"<?php if ($options['title']) : ?> selected<?php endif; ?>>Entry Title - Blog Name</option>
			</select></dd>
		</dl>
		<h3>Navigation</h3>
		<dl>
			<dt>Global Navigation</dt>
			<dd><select name="vicuna_g_navi">
				<option value="0">none</option>
				<option value="1"<?php if ($options['g_navi'] == 1) : ?> selected<?php endif; ?>>pages</option>
				<option value="2"<?php if ($options['g_navi'] == 2) : ?> selected<?php endif; ?>>categories</option>
				<option value="3"<?php if ($options['g_navi'] == 3) : ?> selected<?php endif; ?>>pages + categories</option>
				<option value="4"<?php if ($options['g_navi'] == 4) : ?> selected<?php endif; ?>>categories + pages</option>
			</select>
			<select name="vicuna_g_navi_home">
				<option value="0">hide Home</option>
				<option value="1"<?php if ($options['g_navi_home']) : ?> selected<?php endif; ?>>display Home</option>
			</select>
			</dd>
			<dt><?php _e( 'Exclude:' ); ?></dt>
			<dd><label for="pages-exclude"><?php _e( 'Page' ); ?> <input type="text" name="vicuna_g_navi_pages-exclude" value="<?php echo $options['g_navi_pages-exclude']; ?>" />
				<?php _e( 'list(Reference):', 'vicuna' ); ?><?php vicuna_view_selectlist_pages(); ?><br />
				<label for="categories-exclude"><?php _e( 'Category' ); ?> <input type="text" name="vicuna_g_navi_categories-exclude" value="<?php echo $options['g_navi_categories-exclude']; ?>" />
				<?php _e( 'list(Reference):', 'vicuna' ); ?><?php vicuna_view_selectlist_categories(); ?>
			</dd>
		</dl>
		<h3>Custom</h3>
		<dl>
			<dt><?php _e('Access analysis code', 'vicuna'); ?></dt>
			<dd><textarea name="vicuna_analysis_code" rows="3" cols="80"><?php echo stripslashes($options['analysis_code']); ?></textarea></dd>
			<dt><?php _e('Tag displays', 'vicuna'); ?></dt>
			<dd>
				<input type="radio" name="vicuna_tag_displays" value="0"<?php if ($options['tag_displays'] != 1) : ?> checked="checked"<?php endif; ?> /><?php echo __("No"); ?>
				<input type="radio" name="vicuna_tag_displays" value="1"<?php if ($options['tag_displays'] == 1) : ?> checked="checked"<?php endif; ?> /><?php echo __("Yes"); ?>
			</dd>
			<dt><?php _e('Author displays', 'vicuna'); ?></dt>
			<dd>
				<input type="radio" name="vicuna_author_displays" value="0"<?php if ($options['author_displays'] != 1) : ?> checked="checked"<?php endif; ?> /><?php echo __("No"); ?>
				<input type="radio" name="vicuna_author_displays" value="1"<?php if ($options['author_displays'] == 1) : ?> checked="checked"<?php endif; ?> /><?php echo __("Yes"); ?>
			</dd>
			<dt><?php _e('Write in description at header', 'vicuna'); ?></dt>
			<dd>
				<input type="radio" name="vicuna_description_displays" value="1"<?php if ($options['description_displays'] == 1) : ?> checked="checked"<?php endif; ?> /><?php echo __("No"); ?>
				<input type="radio" name="vicuna_description_displays" value="0"<?php if ($options['description_displays'] != 1) : ?> checked="checked"<?php endif; ?> /><?php echo __("Yes"); ?>
			</dd>
			<dt><?php _e("don't use Vicuna Widgets(It is checked by the thing which I do not use)", 'vicuna'); ?></dt>
			<dd>
				<small><?php _e('(It is overwritten normal widgets when I use Vicuna widgets)', 'vicuna'); ?></small><br />
				<input type="hidden"   name="vicuna_widget" value="vicuna_widget" />
				<input type="checkbox" name="vicuna_widget_archives" value="1"<?php if ($widget_options['archives'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Archives'); ?>
				<input type="checkbox" name="vicuna_widget_categories" value="1"<?php if ($widget_options['categories'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Categories'); ?>
				<input type="checkbox" name="vicuna_widget_calendar" value="1"<?php if ($widget_options['calendar'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Calendar'); ?>
				<input type="checkbox" name="vicuna_widget_tag_cloud" value="1"<?php if ($widget_options['tag_cloud'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Tag Cloud'); ?>
				<input type="checkbox" name="vicuna_widget_pages" value="1"<?php if ($widget_options['pages'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Pages'); ?>
				<input type="checkbox" name="vicuna_widget_meta" value="1"<?php if ($widget_options['meta'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Meta'); ?>
				<input type="checkbox" name="vicuna_widget_recent_comments" value="1"<?php if ($widget_options['recent_comments'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Recent Comments'); ?>
				<input type="checkbox" name="vicuna_widget_recent_posts" value="1"<?php if ($widget_options['recent_posts'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Recent Posts'); ?>
				<input type="checkbox" name="vicuna_widget_search" value="1"<?php if ($widget_options['search'] == 1) : ?> checked="checked"<?php endif; ?> /><?php _e('Search'); ?>
			</dd>
		</dl>
		<p class="submit">
			<input type="submit" value="<?php _e('Save Changes &raquo;', 'vicuna'); ?>" />
		</p>
  	</form>
</div>
<?php
}

/**
 * Get directries of skin.
 */
function get_skin_dirs() {
	$theme_dir = get_theme_local_path();
	if ($dir = opendir($theme_dir)) {
		while (($file = readdir($dir)) !== false) {
			if ($file != "." && $file != ".." && is_dir($theme_dir . '/'. $file) && mb_substr($file, 0, 6) == 'style-') {
				$files[] = $file;
			}
		}
		closedir($dir);
	}
	return $files;
}

/**
 * Get supported languages.
 */
function get_languages() {
	$language_dir = get_theme_local_path() . '/languages';
	if ($dir = opendir($language_dir)) {
		while (($file = readdir($dir)) != false) {
			$l = strlen($file);
			if (mb_substr($file, $l - 3, $l) == ".mo") {
				$languages[] = mb_substr($file, 0, $l - 3);
			}
		}
		closedir($dir);
	}
	return $languages;
}

/**
 * Get a local path of the theme.
 */
function get_theme_local_path() {
	$cwd = getcwd();
	$theme_dir = get_bloginfo('template_directory');
	return mb_substr( $cwd, 0, strlen($cwd) - 8) . mb_substr( $theme_dir, mb_strrpos($theme_dir, 'wp-content'));
}

/**
 * Update the config of Vicuna
 */
function update_vicuna_config() {
        if ( ! current_user_can('switch_themes') ){
		return;
	}
	$options = get_option('vicuna_config');
	$widget_options = get_option('vicuna_widget');
	if (isset($_POST['vicuna_skin'])) {
		$options['skin'] = $_POST['vicuna_skin'];
	}
	if (isset($_POST['vicuna_language'])) {
		$options['language'] = $_POST['vicuna_language'];
	}
	if (isset($_POST['vicuna_eye_catch'])) {
		$options['eye_catch'] = $_POST['vicuna_eye_catch'];
	}
	if (isset($_POST['vicuna_feed_type'])) {
		$options['feed_type'] = $_POST['vicuna_feed_type'];
	}
	if (isset($_POST['vicuna_feed_url'])) {
		$options['feed_url'] = $_POST['vicuna_feed_url'];
	}
	if (isset($_POST['vicuna_g_navi'])) {
		$options['g_navi'] = $_POST['vicuna_g_navi'];
	}
	if (isset($_POST['vicuna_g_navi_home'])) {
		$options['g_navi_home'] = $_POST['vicuna_g_navi_home'];
	}
	if (isset($_POST['vicuna_g_navi_pages-exclude'])) {
		$options['g_navi_pages-exclude'] = $_POST['vicuna_g_navi_pages-exclude'];
	}
	if (isset($_POST['vicuna_g_navi_categories-exclude'])) {
		$options['g_navi_categories-exclude'] = $_POST['vicuna_g_navi_categories-exclude'];
	}
	if (isset($_POST['vicuna_analysis_code'])) {
		$options['analysis_code'] = $_POST['vicuna_analysis_code'];
	}
	if (isset($_POST['vicuna_tag_displays'])) {
		$options['tag_displays'] = $_POST['vicuna_tag_displays'];
	}
	if (isset($_POST['vicuna_author_displays'])) {
		$options['author_displays'] = $_POST['vicuna_author_displays'];
	}
	if (isset($_POST['vicuna_description_displays'])) {
		$options['description_displays'] = $_POST['vicuna_description_displays'];
	}
	if (isset($_POST['vicuna_title'])) {
		$options['title'] = $_POST['vicuna_title'];
	}
	if (isset($_POST['vicuna_fontsize_switcher'])) {
		$options['fontsize_switcher'] = $_POST['vicuna_fontsize_switcher'];
	}
	update_option('vicuna_config', $options);
	if (isset($_POST['vicuna_widget'])) {
		if (isset($_POST['vicuna_widget_archives'])) {
			$widget_options['archives'] = $_POST['vicuna_widget_archives'];
		} else {
			$widget_options['archives'] = 0;
		}
		if (isset($_POST['vicuna_widget_categories'])) {
			$widget_options['categories'] = $_POST['vicuna_widget_categories'];
		} else {
			$widget_options['categories'] = 0;
		}
		if (isset($_POST['vicuna_widget_calendar'])) {
			$widget_options['calendar'] = $_POST['vicuna_widget_calendar'];
		} else {
			$widget_options['calendar'] = 0;
		}
		if (isset($_POST['vicuna_widget_tag_cloud'])) {
			$widget_options['tag_cloud'] = $_POST['vicuna_widget_tag_cloud'];
		} else {
			$widget_options['tag_cloud'] = 0;
		}
		if (isset($_POST['vicuna_widget_pages'])) {
			$widget_options['pages'] = $_POST['vicuna_widget_pages'];
		} else {
			$widget_options['pages'] = 0;
		}
		if (isset($_POST['vicuna_widget_meta'])) {
			$widget_options['meta'] = $_POST['vicuna_widget_meta'];
		} else {
			$widget_options['meta'] = 0;
		}
		if (isset($_POST['vicuna_widget_recent_comments'])) {
			$widget_options['recent_comments'] = $_POST['vicuna_widget_recent_comments'];
		} else {
			$widget_options['recent_comments'] = 0;
		}
		if (isset($_POST['vicuna_widget_recent_posts'])) {
			$widget_options['recent_posts'] = $_POST['vicuna_widget_recent_posts'];
		} else {
			$widget_options['recent_posts'] = 0;
		}
		if (isset($_POST['vicuna_widget_search'])) {
			$widget_options['search'] = $_POST['vicuna_widget_search'];
		} else {
			$widget_options['search'] = 0;
		}
		update_option('vicuna_widget', $widget_options);
	}
}
add_action( 'init', 'update_vicuna_config');

function vicuna_analysis_code() {
	$options = get_option('vicuna_config');
	echo stripslashes($options['analysis_code'])."\n";
}
function vicuna_tag_displays() {
	$options = get_option('vicuna_config');
	if ($options['tag_displays'] == 1) {
		if (function_exists('the_tags')){
			echo the_tags('<li class="tags">', ' | ', '</li>');
		}
	}
}
function vicuna_author_displays() {
	$options = get_option('vicuna_config');
	if ($options['author_displays'] == 1) {
		echo '<li class="author">'.__("Author").':'.get_the_author().'</li>';
	}
}
function vicuna_description_displays() {
	$options = get_option('vicuna_config');
	if ($options['description_displays'] == 0) {
		if ( $description = get_bloginfo('description') ) {
			echo '<meta name="description" content="'.$description.'" />'."\n";
		}
	}
}

?>