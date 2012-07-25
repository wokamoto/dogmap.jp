<?php ks_header();
global $ks_settings;
?>
<h2 id="cats"><?php _e('Category List', 'ktai_style'); ?></h2>
<?php 
$categories = get_categories('orderby=count&order=desc&use_desc_for_title=0&hierarchical=0');
// $categories = get_categories('orderby=name&use_desc_for_title=0&hierarchical=0');
if (empty($categories)) {
	ipotch_box(); _e('No categories'); ?></div><?php
} else {
	foreach ($categories as $c) {
		ipotch_box(); ?><span class="num"><?php echo intval($c->count); ?></span> <a href="<?php echo get_category_link( $c->term_id ); ?>"><?php echo apply_filters( 'list_cats', attribute_escape( $c->name), $c ); ?></a></div><?php
	}
}
ks_footer(); ?>