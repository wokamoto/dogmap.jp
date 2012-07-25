<?php
/**
 * add menu of vicuna options
 */
function add_vicuna_layout_menu() {
        if ( ! current_user_can('switch_themes') )
		return;
	add_theme_page(__('Layout', 'vicuna'), __('Layout', 'vicuna'), 0, basename(__FILE__), 'vicuna_layout_menu');
}
add_action( 'admin_menu', 'add_vicuna_layout_menu' );

/**
 * Get a parameter for layout in the page.
 */
function vicuna_layout($page) {
	$options = get_option('vicuna_layout');
	$key = $page . '_layout';
	if (!isset($options[$key])) {
		$options['index_layout'] = 'double';
		$options['category_layout'] = 'double';
		$options['archive_layout'] = 'double';
		$options['tag_layout'] = 'double';
		$options['page_layout'] = 'single';
		$options['single_layout'] = 'single';
		$options['search_layout'] = 'double';
		$options['404_layout'] = 'single';
		$options['ie6_layout'] = 'double';
		update_option('vicuna_layout', $options);
	}
/** -- ex cng s -- **/
//	echo $options[$key];
	$layout = $options[$key];
	//Substitute Layout(Measures to ie6)
	if(0 == strcmp($layout, 'multi')){
		$agent = $_SERVER['HTTP_USER_AGENT'];
		if(ereg("MSIE 6",$agent)){
			$layout = $options['ie6_layout'];
		}
	}
	$config = get_option('vicuna_config');
	$eye_catch = $config['eye_catch'];
	echo $layout.' '.$eye_catch;
/** -- ex cng e -- **/
}

/**
 * Update parameters of layout
 */
function update_vicuna_layout() {
        if ( ! current_user_can('switch_themes') )
		return;

	$options = get_option('vicuna_layout');
/** -- ex cng s -- **/
//	$keys = array('index', 'category', 'archive', 'tag', 'page', 'single', 'search', '404');
	$keys = array('index', 'category', 'archive', 'tag', 'page', 'single', 'search', '404', 'ie6');
/** -- ex cng e -- **/
	foreach ($keys as $key) {
		$key .= '_layout';
		if (isset($_POST['vicuna_'. $key])) {
/* -- ex del s --
			if (mb_ereg('multi(1|2)', $_POST['vicuna_'. $key], $ary)) {
				$options[$key] = 'multi';
				$options['multi_'. $key] = $ary[1];
			} else {
-- ex del e -- */
				$options[$key] = $_POST['vicuna_'. $key];
				$options['multi_'. $key] = "";
/* -- ex del s --
			}
-- ex del e -- */
		}
	}
	update_option('vicuna_layout', $options);
}
add_action( 'init', 'update_vicuna_layout');

/**
 * Display menu of layout setting.
 */
function vicuna_layout_menu() {
        if ( ! current_user_can('switch_themes') )
		return;

	$options = get_option('vicuna_layout');
	$layout_index = $options['index_layout']; ?>
<div class="wrap">
	<h2><?php _e('Layout', 'vicuna'); ?></h2>
	<p><?php _e('You can select layout of your each pages.', 'vicuna'); ?></p>
	<form method="post" action="<?php echo attribute_escape($_SERVER['REQUEST_URI']); ?>">
		<p class="submit">
			<input type="submit" value="<?php _e('Save Changes &raquo;', 'vicuna'); ?>" />
		</p>
<?php /** -- ex add s -- **/ ?>
<?php
$ex_image_style = 'position: relative; float: right; ';
$ex_image_style = $ex_image_style.'right: 20px; width: 300px;';
$ex_image_style = $ex_image_style.'padding-left: 10px; ';
$ex_image_style = $ex_image_style.'border: 4px solid #aaa; background-color: #ddd;';
$ex_image_path = get_bloginfo('template_directory').'/script/image/';
?>
<div style="<?php echo $ex_image_style; ?>">
<strong>Column Layout</strong><br /><br />
<input type="radio" name="sel_ex" onClick="change_ex(0);" checked="checked" />Single<br />
<input type="radio" name="sel_ex" onClick="change_ex(1);" />Double - Menu on right<br />
<input type="radio" name="sel_ex" onClick="change_ex(2);" />Double - Menu on left<br />
<input type="radio" name="sel_ex" onClick="change_ex(3);" />Multi - Wing menu<br />
<input type="radio" name="sel_ex" onClick="change_ex(4);" />Multi - Double menu<br />
<input type="radio" name="sel_ex" onClick="change_ex(5);" />Multi - Double menu on left<br />
<br />
<img name="img_layout" src="" width="120" height="100" /><br /><br />
</div>
<script type="text/javascript">
<!--
var ex_image = new Array();
ex_image[0] = "<?php echo $ex_image_path; ?>layout_single.png";
ex_image[1] = "<?php echo $ex_image_path; ?>layout_double.png";
ex_image[2] = "<?php echo $ex_image_path; ?>layout_double-l.png";
ex_image[3] = "<?php echo $ex_image_path; ?>layout_multi.png";
ex_image[4] = "<?php echo $ex_image_path; ?>layout_multi2.png";
ex_image[5] = "<?php echo $ex_image_path; ?>layout_multi2-l.png";
function change_ex(no){
  document.forms[0].img_layout.src = ex_image[no];
}
change_ex(0);
// -->
</script>
<?php /** -- ex add e -- **/ ?>
		<dl>
			<dt><?php _e('Index Layout', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_index_layout', $options['index_layout'] . $options['multi_index_layout']); ?></dd>
			<dt><?php _e('Category Layout', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_category_layout', $options['category_layout'] . $options['multi_category_layout']); ?></dd>
			<dt><?php _e('Archive Layout', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_archive_layout', $options['archive_layout'] . $options['multi_archive_layout']); ?></dd>
			<dt><?php _e('Tag Layout', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_tag_layout', $options['tag_layout'] . $options['multi_tag_layout']); ?></dd>
			<dt><?php _e('Single Layout', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_single_layout', $options['single_layout'] . $options['multi_single_layout']); ?></dd>
			<dt><?php _e('Page Layout', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_page_layout', $options['page_layout'] . $options['multi_page_layout']); ?></dd>
			<dt><?php _e('Search Layout', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_search_layout', $options['search_layout'] . $options['multi_search_layout']); ?></dd>
			<dt><?php _e('404 Layout', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_404_layout', $options['404_layout'] . $options['multi_404_layout']); ?></dd>
		</dl>
<?php /** -- ex add s -- **/ ?>
		<br />
		<h3><?php _e('Measures to \'Internet Explorer 6.0\'', 'vicuna'); ?></h3>
		<dl>
			<dt><?php _e('Substitute Layout for \'Multi - Wing menu\'', 'vicuna'); ?></dt>
			<dd><?php vicuna_layout_selector('vicuna_ie6_layout', $options['ie6_layout'] . $options['multi_ie6_layout']); ?></dd>
		</dl>
<?php /** -- ex add e -- **/ ?>
		<p class="submit">
			<input type="submit" value="<?php _e('Save Changes &raquo;', 'vicuna'); ?>" />
		</p>
	</form>
</div>
<?php
}

function vicuna_layout_selector($name, $layout) {
?>
			<select name="<?php echo $name; ?>">
					<option value="single"<?php if ($layout == "single") : echo ' selected'; endif; ?>>Single</option>
					<option value="double"<?php if ($layout == "double") : echo ' selected'; endif; ?>>Double - Menu on right</option>
					<option value="double-l"<?php if ($layout == "double-l") : echo ' selected'; endif; ?>>Double - Menu on left</option>
					<option value="multi"<?php if ($layout == "multi") : echo ' selected'; endif; ?>>Multi - Wing menu</option>
					<option value="multi2"<?php if ($layout == "multi2") : echo ' selected'; endif; ?>>Multi - Double menu</option>
					<option value="multi2-l"<?php if ($layout == "multi2-l") : echo ' selected'; endif; ?>>Multi - Double menu on left</option>
			</select>
<?php
}
?>