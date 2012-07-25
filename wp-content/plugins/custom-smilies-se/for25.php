<?php
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
*/

function add_clcs_tinymce_plugin($plugin_array) {
	$plugin_array['clcs'] = CLCSURL . 'tinymce/plugins/custom-smilies-se/editor_plugin.js';
	return $plugin_array;
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
		add_filter('mce_buttons', 'register_clcs_button');
	}
}
add_action('init', 'clcs_addbuttons');
?>