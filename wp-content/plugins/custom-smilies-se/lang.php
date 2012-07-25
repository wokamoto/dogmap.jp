<?php
global $clcs_options;
$clcs_language = $mce_locale;

// escape text only if it needs translating
// copy from wp-langs.php and rename it.
function clcs_mce_escape($text) {
	global $clcs_language;

	if ( 'en' == $language ) return $text;
	else return js_escape($text);
}

$strings = 'tinyMCE.addI18n("' . $clcs_language . '.clcs", {
	clcs : "' . clcs_mce_escape(__('Custom Smilies', 'custom_smilies')) . '",
	delta_width : "' . $clcs_options['popup_win_width'] . '",
	delta_height : "' . $clcs_options['popup_win_height'] . '"
});
tinyMCE.addI18n("' . $clcs_language . '.clcs_dlg", {
	title : "' . clcs_mce_escape(__('Insert Smilies', 'custom_smilies')) . '"
});';
?>