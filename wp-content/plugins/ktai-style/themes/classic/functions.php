<?php
if (! defined('KTAI_ADMIN_MODE')) {
	add_filter('ktai_raw_content', 'ks_convert_kana');
}
?>