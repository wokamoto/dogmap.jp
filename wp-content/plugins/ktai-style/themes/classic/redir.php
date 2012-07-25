<?php
	$accesskey = 1;
?>
<html><head>
<meta http-equiv="Content-Type" content="<?php ks_mimetype(); ?>; charset=<?php ks_charset(); ?>" />
<meta name="robots" content="noindex,nofollow">
<title><?php echo ks_redir_has('full_url') ? 
	__('Confirm connecting to external sites', 'ktai_style') : 
	__('Error linking to external sites', 'ktai_style') ?></title>
</head><body>
<div><h1 id="logo"><img alt="WordPress" src="<?php echo ks_plugin_url(KTAI_NOT_ECHO) . KTAI_INCLUDES_DIR; ?>/wplogo.gif" /></h1></div>
<?php if (ks_redir_has('full_url')) :
	if (! ks_redir_has('mobile_url')) { ?>
		<p><?php _e('You are about to visit a website for PC:', 'ktai_style'); ?><br />
		<font color="olive"><?php echo htmlspecialchars(ks_redir_get('full_url'), ENT_QUOTES); ?></font>
	<?php } else {
		if (ks_redir_get('mobile_url') === ks_redir_get('full_url')) { ?>
			<p><?php _e('A mobile view is provided with the same URL at the visiting site:', 'ktai_style');
		} else { ?>
			<p><?php _e('A mobile site found for the visiting site:', 'ktai_style');
		} ?>
		<br /><a href="<?php echo esc_url(ks_redir_get('mobile_url')); ?>"><?php echo htmlspecialchars(ks_redir_get('mobile_url'), ENT_QUOTES); ?></a>
		<?php if (! ks_redir_same_host()) { ?>
			<br /><font color="red"><?php _e('The host is diffrent from the origial. Make sure the valid mobile site.', 'ktai_style'); ?></font>
		<?php }
		if (ks_redir_get('mobile_url') != ks_redir_get('full_url')) { ?>
			</p><p><?php _e('The original URL of the site:', 'ktai_style'); ?><br />
			<font color="olive"><?php echo htmlspecialchars(ks_redir_get('full_url'), ENT_QUOTES); ?></font>
		<?php }
	}

	$sjk_link = 'http://www.sjk.co.jp/c/w.exe?y=' . rawurlencode(preg_replace('|^http://|', '', ks_redir_get('full_url')));
	$google_link = 'http://www.google.com/gwt/n?_gwt_noimg=1&u=' . rawurlencode(preg_replace('|^http://|', '', ks_redir_get('full_url')));
	?></p>
	<p><?php _e('You can translate the PC site to view suitable for mobile using services below.', 'ktai_style'); ?>
	<br /><?php ks_ordered_link($accesskey++, 10, $sjk_link, __('Tsuukin(commuting)-browser', 'ktai_style')); ?>
	<br /><?php ks_ordered_link($accesskey++, 10, $google_link, __('Google Wireless Transcoder', 'ktai_style'));

	if (is_ktai() == 'KDDI' && is_ktai('type') == 'WAP2.0') {
		echo '<br />';
		ks_ordered_link(0, 10, 'device:pcsiteviewer?url=' . esc_url(ks_redir_get('full_url')), __('PC Site Viewer on your phone.', 'ktai_style'));
	} elseif (is_ktai() == 'DoCoMo' && is_ktai('type') == 'FOMA') {
		echo '<br />'. ks_pict_number(0) . sprintf(__('<a %s>Full Browser on your phone.</a>', 'ktai_style'), 'href="' . esc_url(ks_redir_get('full_url')) . '" ifb="' . esc_url(ks_redir_get('full_url')) . '" accesskey="0"');
	}
	?></p>
	<?php if (! ks_redir_get('mobile_url') || ks_redir_get('mobile_url') != ks_redir_get('full_url')) { ?>
		<p><?php _e('If you know that the site is suitable for mobile at the same URL, it is good to jump directly.', 'ktai_style'); 
		?><br />#.<a href="<?php echo esc_url(ks_redir_get('full_url')); ?>"><?php echo htmlspecialchars(ks_redir_get('full_url'), ENT_QUOTES); ?></a></p>
	<?php } ?>
	<p><?php _e('To copy the URL, use below text field:', 'ktai_style'); ?></p>
	<form action=""><div>*.<input type="text" name="url" size="80" maxlength="255" value="<?php echo esc_url(ks_redir_get('full_url')); ?>" accesskey="*" /></div></form>
<?php else: // ks_redir_has ?>
	<p><?php _e("A certain time has elapsed since you viewed the page, the link to exteral sites has became invalid.<br />\nGo back the previous page and reload it. After that, retry clicking the link.", 'ktai_style'); ?></p>
<?php endif; ?>
</body></html>