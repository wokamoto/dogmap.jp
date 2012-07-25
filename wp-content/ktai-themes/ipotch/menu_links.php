<?php
function ipotch_remove_external_icon($link_html, $href, $label, $icon) {
	return str_replace($icon, '', $link_html);
}
add_filter('external_link/ktai_style.php', 'ipotch_remove_external_icon', 10, 4);

ks_header();
global $ks_settings;
?>
<h2 id="links"><?php _e('Links', 'ktai_style'); ?></h2>
<?php $output = ks_list_bookmarks(array(
	'class' => '',
	'title_before' => $ks_settings['h3_style'],
	'title_after' => '</div>',
	'category_before' => '',
	'category_after' => '',
	'before' => ipotch_box(KTAI_NOT_ECHO) . '<img localsrc="63" />',
	'after' => '</div>',
	'between' => '',
	'echo' => false,
));
echo preg_replace('#</?ul>#', '', $output);
?>
<?php ks_footer(); ?>