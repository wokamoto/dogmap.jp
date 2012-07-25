	<p class="return"><a href="#header"><?php _e('Return to page top'); ?></a></p>
</div><!--end content-->

<div id="footer">
	<ul class="support">
		<li>Powered by <a href="http://wordpress.org/">WordPress <?php bloginfo('version'); ?></a></li>
		<li class="template"><a href="http://vicuna.jp/">vicuna CMS</a> - <a href="<?php vicuna_link(); ?>" title="ver.1.5.9"><?php _e('WordPress Theme'); ?></a> <a href="http://ma38su.org/projects/" title="1.58">Ext.</a> <a href="http://fos.uzusionet.com/wordpress/vicuna" title="vicuna 1.5.9 with Ext. code">Custom</a></li>
	</ul>
	<address><?php printf(__('Copyright &copy; %s All Rights Reserved.', 'vicuna'), get_bloginfo('name')); ?></address>
</div>
<?php	wp_footer(); ?>
</body>
</html>
