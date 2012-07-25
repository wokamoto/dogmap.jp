<?php
ks_header(); 
global $ks_settings;
if (ks_redir_has('full_url')) : 
	?><div<?php echo $ks_settings['h2_style']; ?>><h2><?php _e('Confirm connecting to external sites', 'ktai_style'); ?></h2></div>
	<?php if (! ks_redir_has('mobile_url')) { ?>
		<p><?php _e('You are about to visit a website for PC:', 'ktai_style'); ?><br />
		<a href="<?php echo esc_url(ks_redir_get('url')); ?>"><?php echo esc_url(ks_redir_get('full_url')); ?></a>
	<?php } else {
		if (ks_redir_get('mobile_url') === ks_redir_get('full_url')) { ?>
			<p><?php _e('A mobile view is provided with the same URL at the visiting site:', 'ktai_style');
		} else { ?>
			<p><?php _e('A mobile site found for the visiting site:', 'ktai_style');
		} ?>
		<br /><a href="<?php echo esc_url(ks_redir_get('mobile_url')); ?>"><?php echo esc_url(ks_redir_get('mobile_url')); ?></a>
		<?php if (! ks_redir_same_host()) { ?>
				<br /><font color="red"><?php _e('The host is diffrent from the origial. Make sure the valid mobile site.', 'ktai_style'); ?></font>
		<?php }
		if (ks_redir_get('mobile_url') !== ks_redir_get('full_url')) { ?>
			</p><p><?php _e('The original URL of the site:', 'ktai_style'); ?><br />
			<a href="<?php echo esc_url(ks_redir_get('url')); ?>"><?php echo esc_url(ks_redir_get('full_url')); ?></a>
		<?php }
	}

	if (is_ktai() == 'KDDI' && is_ktai('type') == 'WAP2.0') {
		echo '<br />'. sprintf(__('(<a %s>View the site by PC Site Viewer.</a>)', 'ktai_style'), ' href="device:pcsiteviewer?url=' . esc_url(ks_redir_get('full_url')) . '"');
	} elseif (is_ktai() == 'DoCoMo' && is_ktai('type') == 'FOMA') {
		echo '<br />'. sprintf(__('(<a %s>View the site by Full Browser.</a>)', 'ktai_style'), 'href="' . esc_url(ks_redir_get('full_url')) . '" ifb="' . esc_url(ks_redir_get('full_url')) . '"');
	}
	?></p>
	<p><?php _e("If you are sure, follow above link. If not, go to the previous page with browser's back button.", 'ktai_style') ?></p>
	<p><?php _e('To copy the URL, use below text field:', 'ktai_style'); ?></p>
	<form action=""><div><input type="text" name="url" size="80" maxlength="255" value="<?php echo esc_url(ks_redir_get('full_url')); ?>" /></div></form>
<?php else: // ks_redir_has ?>
	<div<?php echo $ks_settings['h2_style']; ?>><h2><?php _e('Error linking to external sites', 'ktai_style'); ?></h2></div>
	<p><?php _e("A certain time has elapsed since you viewed the page, the link to exteral sites has became invalid.<br />\nGo back the previous page and reload it. After that, retry clicking the link.", 'ktai_style'); ?></p>
<?php endif; ?>
</body></html>