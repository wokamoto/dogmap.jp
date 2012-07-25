<div id="utilities">
	<dl class="navi">
<?php	if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('navi') ) : ?>
<?php		if ($pages = &get_pages('')) : ?>

		<dt><?php _e('Pages'); ?></dt>
		<dd>
			<ul class="pages">
<?php			wp_list_pages('sort_column=menu_order&title_li=0'); ?>
			</ul>
		</dd>
<?php		endif; ?>

		<dt><?php _e('Recent Posts'); ?></dt>
		<dd>
			<ul class="recentPosts">
<?php		wp_get_archives('type=postbypost&limit=5'); ?>
			</ul>
		</dd>
		<dt><?php _e('Categories'); ?></dt>
		<dd>
			<ul class="category">
<?php		wp_list_cats('sort_column=name&optioncount=0&hierarchical=1'); ?>
			</ul>
		</dd>

		<dt><?php _e('Archives'); ?></dt>
		<dd>
			<ul class="archive">
<?php		vicuna_archives_link(); ?>
			</ul>
		</dd>
<?php		if (function_exists('get_tags')) : ?>
		<dt><?php _e('Tag Cloud'); ?></dt>
		<dd>
<?php			vicuna_tag_cloud(); ?>
		</dd>
<?php		endif; ?>
<?php	endif; ?>
	</dl><!--end navi-->

	<dl class="others">
<?php	if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('others') ) : ?>
		<dt><?php _e('Search'); ?></dt>
		<dd>
			<form method="get" action="<?php bloginfo('home'); ?>/">
				<fieldset>
					<legend><label for="searchKeyword"><?php printf(__('Search %s', 'vicuna'), get_bloginfo('name')); ?></label></legend>
					<div>
						<input type="text" class="inputField" id="searchKeyword" name="s" size="10" onfocus="if (this.value == '<?php _e('Keyword(s)', 'vicuna'); ?>') this.value = '';" onblur="if (this.value == '') this.value = '<?php _e('Keyword(s)', 'vicuna'); ?>';" value="<?php if ( is_search() ) echo wp_specialchars($s, 1); else _e('Keyword(s)', 'vicuna'); ?>" />
						<input type="submit" class="submit" id="submit" value="<?php _e('Search'); ?>" />
					</div>
				</fieldset>
			</form>
		</dd>
		<dt><?php _e('Feeds', 'vicuna'); ?></dt>
		<dd>
			<ul class="feeds">
				<li class="rss"><a href="<?php bloginfo('rss2_url'); ?>"><?php _e('All Entries', 'vicuna'); ?>(RSS2.0)</a></li>
				<li class="atom"><a href="<?php bloginfo('atom_url'); ?>"><?php _e('All Entries', 'vicuna'); ?>(Atom)</a></li>
				<li class="rss"><a href="<?php bloginfo('comments_rss2_url'); ?>"><?php _e('All Comments', 'vicuna'); ?>(RSS2.0)</a></li>
			</ul>
		</dd>
		<dt><?php _e('Meta'); ?></dt>
		<dd>
			<ul class="meta">
				<li><a href="http://validator.w3.org/check/referer" title="<?php _e('This page validates as XHTML 1.0 Strict', 'vicuna'); ?>" rel="nofollow"><?php printf(__('Valid <abbr title="eXtensible HyperText Markup Language">XHTML</abbr>', 'vicuna')); ?></a></li>
<?php		wp_register(); ?>
				<li><?php wp_loginout(); ?></li>
<?php		wp_meta(); ?>
			</ul>
		</dd>
<?php	endif; ?>
	</dl><!--end others-->
</div><!--end utilities-->
