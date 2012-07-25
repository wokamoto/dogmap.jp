<?php ks_header();
global $ks_settings;
?>
<h2 id="pages"><?php _e('Pages', 'ktai_style'); ?></h2>
<?php 
$pages = get_pages('hierarchical=0&' . $ks_settings['list_pages']);
if ($pages) {
	foreach ($pages as $p) {
		ipotch_box(); ?><img localsrc="103" alt="" /><a href="<?php echo get_page_link($p->ID); ?>"><?php echo apply_filters('the_title', $p->post_title); ?></a></div><?php 
	}
}
ks_footer(); ?>
