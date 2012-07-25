<?php
ks_header(); 
ipotch_box();
if (ks_redir_has('full_url')) : 
	?><h2 style="margin:0;"><?php _e('Confirm connecting to external sites', 'ktai_style'); ?></h2></div>
	<?php ipotch_box();
	if (! ks_redir_get('mobile_url')) { ?>
		<p><?php _e('You are about to visit a website for PC:', 'ktai_style'); ?><br />
		<a href="<?php echo attribute_escape(ks_redir_get('full_url')); ?>"><?php echo htmlspecialchars(ks_redir_get('full_url'), ENT_QUOTES); ?></a>
	<?php } else {
		if (ks_redir_get('mobile_url') === ks_redir_get('full_url')) { ?>
			<p><?php _e('A mobile view is provided with the same URL at the visiting site:', 'ktai_style');
		} else { ?>
			<p><?php _e('A mobile site found for the visiting site:', 'ktai_style');
		} ?>
		<br /><a href="<?php echo attribute_escape(ks_redir_get('mobile_url')); ?>"><?php echo htmlspecialchars(ks_redir_get('mobile_url'), ENT_QUOTES); ?></a>
		<?php if (! ks_redir_same_host()) { ?>
			<br /><font color="red"><?php _e('The host is diffrent from the origial. Make sure the valid mobile site.', 'ktai_style'); ?></font>
		<?php }
		if (ks_redir_get('mobile_url') != ks_redir_get('full_url')) { ?>
			</p><p><?php _e('The original URL of the site:', 'ktai_style'); ?><br />
			<a href="<?php echo attribute_escape(ks_redir_get('url')); ?>"><?php echo htmlspecialchars(ks_redir_get('full_url'), ENT_QUOTES); ?></a>
		<?php } 
	}

	if (is_ktai() == 'KDDI' && is_ktai('type') == 'WAP2.0') {
		echo '<br />'. sprintf(__('(<a %s>View the site by PC Site Viewer.</a>)', 'ktai_style'), ' href="device:pcsiteviewer?url=' . attribute_escape(ks_redir_get('full_url')) . '"');
	} elseif (is_ktai() == 'DoCoMo' && is_ktai('type') == 'FOMA') {
		echo '<br />'. sprintf(__('(<a %s>View the site by Full Browser.</a>)', 'ktai_style'), 'href="' . attribute_escape(ks_redir_get('full_url')) . '" ifb="' . attribute_escape(ks_redir_get('full_url')) . '"');
	}
	?></p>
	<p><?php _e("If you are sure, follow above link. If not, go to the previous page with browser's back button.", 'ktai_style') ?></p>
	<p><?php _e('To copy the URL, use below text field:', 'ktai_style'); ?></p></div>
	<?php ipotch_nbox(); ?><form action=""><div><input type="text" name="url" size="80" maxlength="255" value="<?php echo attribute_escape(ks_redir_get('full_url')); ?>" /></div></form></div>
<?php else: // ks_redir_has ?>
	<h2 style="margin:0;"><?php _e('Error linking to external sites', 'ktai_style'); ?></h2></div>
	<?php ipotch_box(); ?>
	<p><?php _e("A certain time has elapsed since you viewed the page, the link to exteral sites has became invalid.<br />\nGo back the previous page and reload it. After that, retry clicking the link.", 'ktai_style'); ?></p></div>
<?php endif; ?>
</body></html>