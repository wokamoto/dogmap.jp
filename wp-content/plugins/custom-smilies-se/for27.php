<?php
// Add common scripts for Smilies in admin page.
function clcs_add_common_scripts() {
	global $hook_suffix;
?>
<!-- <?php echo $hook_suffix;?> -->
<style type="text/css">
#smiliesdiv {
	position: absolute;
	left: 100px;
	top: 100px;
	background: #fff;
	z-index: 100;
	padding: 5px;
	border: 1px solid #dfdfdf;
	display: none;
}
#smiliesdiv img {
	margin: 3px;
}
#smiliesdiv a {
	text-decoration: none;
}
</style>
<script type="text/javascript">
function allPrpos(obj) { 
    // 用来保存所有的属性名称和值
    var props = "";
    // 开始遍历
    for(var p in obj){ 
        // 方法
        if(typeof(obj[p])=="function"){ 
            obj[p]();
        }else{ 
            // p 为属性名称，obj[p]为对应属性的值
            props+= p + "=" + obj[p] + "\t";
        } 
    } 
    // 最后显示所有的属性
    alert(props);
}
function mousePosition(ev) {
	if (ev.pageX || ev.pageY) {
		return {x:ev.pageX, y:ev.pageY};
	}
	return {
		x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
		y:ev.clientY + document.body.scrollTop  - document.body.clientTop
	};
}
function mouseMove(ev){
	ev = ev || window.event;
	return mousePosition(ev);
}
function grin(tag, target) {
	var myField;
	var myQuickPressTextarea = target;
	tag = ' ' + tag + ' ';
    if (document.getElementById(myQuickPressTextarea) && document.getElementById(myQuickPressTextarea).type == 'textarea') {
		myField = document.getElementById(myQuickPressTextarea);
	} else {
		return false;
	}
	if (document.selection) {
		myField.focus();
		sel = document.selection.createRange();
		sel.text = tag;
		myField.focus();
	}
	else if (myField.selectionStart || myField.selectionStart == '0') {
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		var cursorPos = endPos;
		myField.value = myField.value.substring(0, startPos)
					  + tag
					  + myField.value.substring(endPos, myField.value.length);
		cursorPos += tag.length;
		myField.focus();
		myField.selectionStart = cursorPos;
		myField.selectionEnd = cursorPos;
	}
	else {
		myField.value += tag;
		myField.focus();
	}
}
function smilies_win_show(ev, target) {
	var mousePos = mouseMove(ev);
	smiliesdiv_left = mousePos.x - jQuery("#smiliesdiv").width() + 20;
	smiliesdiv_top = mousePos.y - 20;
	jQuery("#smiliesdiv").css("display", "block");
	jQuery("#smiliesdiv").css("left", smiliesdiv_left + "px");
	jQuery("#smiliesdiv").css("top", smiliesdiv_top + "px");
	if (target == "quickpress") {
		jQuery("#smiliesdiv").html("<div style=\"text-align: right; margin-bottom: 5px;\"><a href=\"javascript: smilies_win_hide();void(0);\"><?php _e('Close', 'custom_smilies') ?></a></div>" + smilies_list);
		return;
	}
	if (target == "reply") {
		jQuery("#smiliesdiv").html("<div style=\"text-align: right; margin-bottom: 5px;\"><a href=\"javascript: smilies_win_hide();void(0);\"><?php _e('Close', 'custom_smilies') ?></a></div>" + smilies_list4reply);
		return;
	}
	if (target == "edit_comment") {
		jQuery("#smiliesdiv").html("<div style=\"text-align: right; margin-bottom: 5px;\"><a href=\"javascript: smilies_win_hide();void(0);\"><?php _e('Close', 'custom_smilies') ?></a></div>" + smilies_list);
		return;
	}
	if (target == "post") {
		<?php //global $user_ID; $rich_editing_flag = get_usermeta($user_ID, 'rich_editing'); ?>
		if (jQuery("#quicktags").css("display") == "block") {
			jQuery("#smiliesdiv").html("<div style=\"text-align: right; margin-bottom: 5px;\"><a href=\"javascript: smilies_win_hide();void(0);\"><?php _e('Close', 'custom_smilies') ?></a></div>" + smilies_list4post);
		} else {
			jQuery("#smiliesdiv").html("<div style=\"text-align: right; margin-bottom: 5px;\"><a href=\"javascript: smilies_win_hide();void(0);\"><?php _e('Close', 'custom_smilies') ?></a></div>" + smilies_list4wysiwyg);
		}
		//alert(switchEditors.mode);
		//allPrpos(switchEditors);
		return;
	}
}
function smilies_win_hide() {
	jQuery("#smiliesdiv").css("display", "none");
}
jQuery(function () {
	jQuery("body").append("<div id=\"smiliesdiv\"></div>");
});
</script>
<?php
}
add_action('admin_head-index.php', 'clcs_add_common_scripts', 12);
add_action('admin_head-edit-comments.php', 'clcs_add_common_scripts', 12);
add_action('admin_head-comment.php', 'clcs_add_common_scripts', 12);
add_action('admin_head-post-new.php', 'clcs_add_common_scripts', 12);
add_action('admin_head-post.php', 'clcs_add_common_scripts', 12);
add_action('admin_head-page-new.php', 'clcs_add_common_scripts', 12);
add_action('admin_head-page.php', 'clcs_add_common_scripts', 12);

// Add smilies for QuickPress
function clcs_media_buttons() {
	global $hook_suffix;
	$target4action = ($hook_suffix == 'index.php') ? 'quickpress' : 'post';
?>
<script type="text/javascript">
jQuery(function () {
	jQuery("#smilies_button").click(function(ev){
		smilies_win_show(ev, "<?php echo $target4action; ?>");
	});
	// <?php global $hook_suffix; echo $hook_suffix; ?>

});
</script>
<a href="javascript:void(0);" id="smilies_button"><img width="12" height="12" alt="Add Smilie" src="<?php echo CLCSURL; ?>images/smile.gif"/></a>
<?php
}
add_action('media_buttons', 'clcs_media_buttons', 12);

function clcs_add_quickpress_scripts() {
?>
<script type="text/javascript" src="<?php echo CLCSURL . 'genlist_quickpress.js.php'; ?>"></script>
<?php
}
add_action('admin_head-index.php', 'clcs_add_quickpress_scripts', 12);

function clcs_add_post_scripts() {
?>
<script type="text/javascript" src="<?php echo CLCSURL . 'genlist_post.js.php'; ?>"></script>
<script type="text/javascript" src="<?php echo CLCSURL . 'genlist_post4wysiwyg.js.php'; ?>"></script>
<?php
}
add_action('admin_head-post-new.php', 'clcs_add_post_scripts', 12);
add_action('admin_head-post.php', 'clcs_add_post_scripts', 12);
add_action('admin_head-page-new.php', 'clcs_add_post_scripts', 12);
add_action('admin_head-page.php', 'clcs_add_post_scripts', 12);

// add smilies for reply in background.
function clcs_add_reply_scripts() {
?>
<script type="text/javascript" src="<?php echo CLCSURL . 'genlist_reply.js.php'; ?>"></script>
<script type="text/javascript">
jQuery(function (){
	jQuery('#ed_reply_toolbar').append('<input id="ed_reply_clcs_smilies" class="ed_button" type="button" value="smilies" />');
	jQuery('#ed_reply_clcs_smilies').click(function(ev){
		smilies_win_show(ev, "reply");
	});
	//if (edButtons) alert(edButtons[edButtons.length - 1].name);
/*
edButtons[edButtons.length] =
new edButton('ed_smilies'
,'smilies'
,'\n\n<blockquote>'
,'</blockquote>\n\n'
,'q'
);
*/
});
</script>
<?php
}
add_action('admin_head-index.php', 'clcs_add_reply_scripts', 12);
add_action('admin_head-edit-comments.php', 'clcs_add_reply_scripts', 12);

// add smilies for Edit Comment in background.
function clcs_add_edit_comment_scripts() {
?>
<script type="text/javascript" src="<?php echo CLCSURL . 'genlist_quickpress.js.php'; ?>"></script>
<script type="text/javascript">
jQuery(function (){
	jQuery('#ed_toolbar').append('<input id="ed_edit_comment_clcs_smilies" class="ed_button" type="button" value="smilies" />');
	jQuery('#ed_edit_comment_clcs_smilies').click(function(ev){
		smilies_win_show(ev, "edit_comment");
	});
});
</script>
<?php
}
add_action('admin_head-comment.php', 'clcs_add_edit_comment_scripts', 12);

// add admin pages
add_action('admin_menu', 'clcs_add_pages');

function clcs_add_pages() {
	add_options_page(__('Smilies Options', 'custom_smilies'), __('Smilies', 'custom_smilies'), 8, CLCSABSFILE, 'clcs_options_admin_page');
}

/*
// add custom box
//add_action('admin_menu', 'clcs_add_custom_box');

function clcs_add_custom_box() {
	add_meta_box( 'clcsbox', __('Smilies', 'custom_smilies'), 'clcs_inner_custom_box', 'post', 'normal');
	add_meta_box( 'clcsbox', __('Smilies', 'custom_smilies'), 'clcs_inner_custom_box', 'page', 'normal');
}

function add_clcs_tinymce_plugin($plugins_array) {
	$plugins_array['clcs'] = CLCSURL . 'tinymce/plugins/custom-smilies-se/editor_plugin.js';
	return $plugins_array;
}
function add_clcs_tinymce_language($languages_array) {
	$languages_array['clcs'] = CLCSABSPATH . '/lang.php';
	return $languages_array;
}
function register_clcs_button($buttons) {
	$buttons[] = 'separator';
	$buttons[] = 'clcs';
	return $buttons;
}
function clcs_addbuttons() {
	if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
		return;
	if ( get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'add_clcs_tinymce_plugin');
		add_filter('mce_external_languages', 'add_clcs_tinymce_language');
		add_filter('mce_buttons', 'register_clcs_button');
	}
}
add_action('init', 'clcs_addbuttons');
*/
?>