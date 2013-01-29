<?php

//prevent direct call of file
if (!function_exists('add_action')) { header("Location: /", true, 302); exit(); }

function wpsh_get_phpinfo() {
	if (!function_exists('phpinfo') || !is_callable('phpinfo')) return false;
	ob_start();
	phpinfo(INFO_MODULES);
	$s = ob_get_contents();
	ob_end_clean();

	$s = strip_tags($s,'<h2><th><td>');
	$s = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$s);
	$s = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$s);
	$vTmp = preg_split('/(<h2>[^<]+<\/h2>)/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
	$vModules = array();
	for ($i=1;$i<count($vTmp);$i++) {
		if (preg_match('/<h2>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) {
			$vName = trim($vMat[1]);
			$vTmp2 = explode("\n",$vTmp[$i+1]);
			foreach ($vTmp2 AS $vOne) {
				$vPat = '<info>([^<]+)<\/info>';
				$vPat3 = "/$vPat\s*$vPat\s*$vPat/";
				$vPat2 = "/$vPat\s*$vPat/";
				if (preg_match($vPat3,$vOne,$vMat)) { // 3cols
					$vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
				} elseif (preg_match($vPat2,$vOne,$vMat)) { // 2cols
					$vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
				}
			}
		}
	}
	return $vModules;
} 

function wpsh_phpinfo_section($key, $data) {
	?>
	<tr><td width="200px;"><b><?php echo $key; ?></b></td>
		<td colspan="2"><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-phpi-<?php echo sanitize_title($key) ?>" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
	</tr>
	<?php
	foreach($data as $label => $values) : ?>
	<?php if (!preg_match("/version/i", $label)) : ?>
	<tr class="wpsh-sect-phpi-<?php echo sanitize_title($key) ?>" style="display:none">
	<?php else : ?>
	<tr>
	<?php endif; ?>
		<td><small><?php echo $label; ?></small></td>
		<?php if(is_array($values)) : ?>
			<td><b><small><?php echo $values[0]; ?></small></b></td>
			<td><b><small><?php echo $values[1]; ?></small></b></td>
		<?php else: ?>
			<td colspan="2"><b><small><?php echo $values; ?></small></b></td>
		<?php endif; ?>
	</tr>	
	<?php endforeach;
}

$phpinfo = wpsh_get_phpinfo();

global $wp_version, $pagenow, $wpdb, $l10n;
$this->boot_loader->pass_checkpoint('callback:dashboard'); 
$mysql_server_version = $wpdb->get_var("SELECT VERSION() AS version");
$cur_locale = get_locale();
?>
<div id="wpsh-tabs" class="inside">
	<ul>
		<li><a href="#wpsh-overview"><?php _e('System', 'wp-system-health'); ?></a></li>
		<li><a href="#wpsh-php"><?php _e('PHP', 'wp-system-health'); ?></a></li>
		<li><a href="#wpsh-wordpress"><?php _e('WordPress', 'wp-system-health'); ?></a></li>
		<?php if ($this->l10n_tracing) : ?>
		<li><a href="#wpsh-l10n"><?php _e('Translation', 'wp-system-health'); ?></a></li>
		<?php endif; ?>
		<li><a href="#wpsh-database"><?php _e('Database', 'wp-system-health'); ?></a></li>
		<li><a href="#wpsh-memorycheck"><?php _e('Test Suite', 'wp-system-health'); ?></a></li>
	</ul>
	<div id="wpsh-overview">
		<table width="100%" class="fixed widefat" cellspacing="8px"  style="border-style: none;">
			
			<tr><td width="160px;"><b><?php _e('Server Setup:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-0" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr><td><?php _e('OS Type', 'wp-system-health'); ?></td><td><b><?php echo php_uname(); ?></b></td></tr>
			<tr><td><?php _e('Server Software', 'wp-system-health'); ?></td><td><b><?php if(isset($_SERVER['SERVER_SOFTWARE'])) echo $_SERVER['SERVER_SOFTWARE']; ?></b></td></tr>
			<tr class="wpsh-sect-0" style="display:none"><td><?php _e('Server Signature', 'wp-system-health'); ?></td><td><b><?php if(isset($_SERVER['SERVER_SIGNATURE'])) echo $_SERVER['SERVER_SIGNATURE']; ?></b></td></tr>
			<tr class="wpsh-sect-0" style="display:none"><td><?php _e('Server Name', 'wp-system-health'); ?></td><td><b><?php if(isset($_SERVER['SERVER_NAME']))echo $_SERVER['SERVER_NAME']; ?></b></td></tr>
			<tr class="wpsh-sect-0" style="display:none"><td><?php _e('Server Address', 'wp-system-health'); ?></td><td><b><?php if(isset($_SERVER['SERVER_ADDR']))echo $_SERVER['SERVER_ADDR']; ?></b></td></tr>
			<tr class="wpsh-sect-0" style="display:none"><td><?php _e('Server Port', 'wp-system-health'); ?></td><td><b><?php if(isset($_SERVER['SERVER_PORT'])) echo $_SERVER['SERVER_PORT']; ?></b></td></tr>
			<tr><td><?php _e('PHP Version', 'wp-system-health'); ?></td><td><b><?php echo PHP_VERSION; ?></b></td></tr>
			<tr class="wpsh-sect-0" style="display:none"><td><?php _e('Zend Version', 'wp-system-health'); ?></td><td><b><?php echo zend_version(); ?></b></td></tr>
			<tr class="wpsh-sect-0" style="display:none"><td><?php _e('Platform', 'wp-system-health'); ?></td><td><b><?php echo (PHP_INT_SIZE * 8).'Bit'; ?></b></td></tr>
			<tr class="wpsh-sect-0" style="display:none"><td><?php _e('Loaded Extensions', 'wp-system-health'); ?></td><td><b><i><?php echo implode(', ', get_loaded_extensions()); ?></i></b></td></tr>
			<tr><td><?php _e('MySQL Server', 'wp-system-health'); ?></td><td><b><?php echo $mysql_server_version; ?></b></td></tr>
			<tr><td><?php _e('Memory Limit', 'wp-system-health'); ?></td><td><b><?php echo size_format($this->boot_loader->memory_limit); ?></b></td></tr>
			<?php $quota = $this->boot_loader->get_quotas();  if(is_array($quota)) : ?>
				<tr><td width="110px;"><b><?php _e('Server Quota\'s:', 'wp-system-health'); ?></b></td>
					<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-15" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
				</tr>
				<tr><td><?php _e('Storage Space', 'wp-system-health'); ?></td><td><?php echo sprintf(__('<b>%s</b> of <b>%s</b> (%s %%) used.','wp-system-health'),size_format($quota['blocks']*$this->options->quota_block_size,2),size_format($quota['b_limit']*$this->options->quota_block_size,2), number_format_i18n($quota['b_perc'],2)); ?></td></tr>					
				<tr class="wpsh-sect-15" style="display:none"><td><?php _e('- Soft Limit', 'wp-system-health'); ?></td><td><b><?php echo size_format($quota['b_quota']*$this->options->quota_block_size,2); ?></b></td></tr>					
				<tr><td><?php _e('Number of Files', 'wp-system-health'); ?></td><td><?php echo sprintf(__('<b>%s</b> of <b>%s</b> files (%s %%) stored.','wp-system-health'),$quota['files'],$quota['f_limit'], number_format_i18n($quota['f_perc'],2)); ?></td></tr>					
				<tr class="wpsh-sect-15" style="display:none"><td><?php _e('- Soft Limit', 'wp-system-health'); ?></td><td><b><?php echo $quota['f_quota'].' '.__('files','wp-system-health'); ?></b></td></tr>					
			<?php else :?>
				<tr><td width="110px;"><b><?php _e('Server Quota\'s:', 'wp-system-health'); ?></b></td><td><?php _e('access not permitted or quota not configured.','wp-system-health'); ?></td></tr>
			<?php endif; ?>

								
			<tr><td width="110px;"><b><?php _e('Server Locale:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-16" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr class="wpsh-sect-16" style="display:none"><td><?php _e('collation', 'wp-system-health'); ?><br/><small><i>(LC_COLLATE)</i></small></td><td><b><?php echo setlocale(LC_COLLATE, 0); ?></b></td></tr>
			<tr class="wpsh-sect-16" style="display:none"><td><?php _e('uppercasing', 'wp-system-health'); ?><br/><small><i>(LC_CTYPE)</i></small></td><td><b><?php echo setlocale(LC_CTYPE, 0); ?></b></td></tr>
			<tr class="wpsh-sect-16" style="display:none"><td><?php _e('monetary', 'wp-system-health'); ?><br/><small><i>(LC_MONETARY)</i></small></td><td><b><?php echo setlocale(LC_MONETARY, 0); ?></b></td></tr>
			<tr class="wpsh-sect-16" style="display:none"><td><?php _e('numerical', 'wp-system-health'); ?><br/><small><i>(LC_NUMERIC)</i></small></td><td><b><?php echo setlocale(LC_NUMERIC, 0); ?></b></td></tr>
			<tr><td><?php _e('date/time', 'wp-system-health'); ?><br/><small><i>(LC_TIME)</i></small></td><td><b><?php echo setlocale(LC_TIME, 0); ?></b></td></tr>
			<tr class="wpsh-sect-16" style="display:none"><td><?php _e('messages', 'wp-system-health'); ?><br/><small><i>(LC_MESSAGES)</i></small></td><td><b><?php echo @setlocale(LC_MESSAGES, 0); ?></b></td></tr>
			
			<tr><td width="110px;"><b><?php _e('Load Average:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-17" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<?php $lavg = $this->boot_loader->get_loadavg(array(__('-n.a.-', 'wp-system-health'),__('-n.a.-', 'wp-system-health'),__('-n.a.-', 'wp-system-health'))); ?>
			<tr><td><?php _e('last 1 minute', 'wp-system-health'); ?></td><td><b><?php echo (is_numeric($lavg[0]) ? number_format_i18n($lavg[0], 2) : $lavg[0]); ?></b></td></tr>
			<tr class="wpsh-sect-17" style="display:none"><td><?php _e('last 5 minutes', 'wp-system-health'); ?></td><td><b><?php echo (is_numeric($lavg[1]) ? number_format_i18n($lavg[1], 2) : $lavg[1]); ?></b></td></tr>
			<tr class="wpsh-sect-17" style="display:none"><td><?php _e('last 15 minutes', 'wp-system-health'); ?></td><td><b><?php echo (is_numeric($lavg[2]) ? number_format_i18n($lavg[2], 2) : $lavg[2]); ?></b></td></tr>
			
			
			<tr><td><b><?php _e('Checkpoints:', 'wp-system-health'); ?></b></td>
				<td>
					<a class="button-secondary wpsh-toggle-section" id="wpsh-sect-memory" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a>
				</td>
			</tr>
			<?php if ($this->boot_loader->mem_usage_denied || $this->boot_loader->exec_denied) : ?>
				<tr><td colspan="2"><span style="color:#f00"><b><?php _e('Attention:', 'wp-system-health'); ?></b></span><br/>
				<?php if ($this->boot_loader->mem_usage_denied) : ?>
					<span style="color:#f00"><?php _e('Your provider denies the function <b><i>memory_get_usage</i></b> for security reasons.', 'wp-system-health'); ?></span><br/>
				<?php endif; ?>
				<?php if ($this->boot_loader->exec_denied) : ?>
					<span style="color:#f00"><?php _e('Your provider denies the function <b><i>exec</i></b> for security reasons.', 'wp-system-health'); ?></span><br/>
				<?php endif; ?>
					<small style="color:#000"><?php _e('(You will <b>not</b> get any memory related information because of above named restriction.)', 'wp-system-health'); ?></small>
				</td></tr>
			<?php endif; ?>
			<?php foreach($this->boot_loader->check_points as $checkpoint) $this->_echo_checkpoint_row($checkpoint); ?>
		</table>			
	</div>
	<div id="wpsh-php" class="ui-tabs-hide">
		<table width="100%" class="fixed widefat" cellspacing="8px"  style="border-style: none;">
			<?php if ($phpinfo === false) : ?>
			<tr><td width="160px;"><?php _e('PHP Version', 'wp-system-health'); ?></td><td><b><?php echo PHP_VERSION; ?></b></td></tr>
			<tr><td><b><?php _e('Runtime Configuration:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-1" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr class="wpsh-sect-1" style="display:none"><td><?php _e('Include Path', 'wp-system-health'); ?><br/><small><i>(include_path)</i></small></td><td><b><?php echo ini_get('include_path'); ?></b><br/><small><?php _e('Specifies a list of directories where the require(), include(), fopen(), file(), readfile() and file_get_contents()  functions look for files.', 'wp-system-health'); ?></br></td></tr>										
			<tr class="wpsh-sect-1" style="display:none"><td><?php _e('Maximum Input Time', 'wp-system-health'); ?><br/><small><i>(max_input_time)</i></small></td><td><b><?php echo ini_get('max_input_time'); ?> <?php _e('seconds', 'wp-system-health'); ?></b><br/><small><?php _e('This sets the maximum time in seconds a script is allowed to parse input data, like POST, GET and file uploads.', 'wp-system-health'); ?></br></td></tr>					
			<tr><td><?php _e('Maximum Execution Time', 'wp-system-health'); ?><br/><small><i>(max_execution_time)</i></small></td><td><b><?php $et = ini_get('max_execution_time'); if ($et > 1000) $et /= 1000; echo $et; ?> <?php _e('seconds', 'wp-system-health'); ?></b><br/><small><?php _e('This sets the maximum time in seconds a script is allowed to run before it is terminated by the parser.', 'wp-system-health'); ?></br></td></tr>

			<tr><td width="150px;"><b><?php _e('File Upload Settings:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-2" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr class="wpsh-sect-2" style="display:none"><td><?php _e('HTTP File Upload', 'wp-system-health'); ?><br/><small><i>(file_uploads)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('file_uploads')); ?></b><br/><small><?php _e('Whether or not to allow HTTP file uploads.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-2" style="display:none"><td><?php _e('Temporary Directory', 'wp-system-health'); ?><br/><small><i>(upload_tmp_dir)</i></small></td><td><b><?php echo ini_get('upload_tmp_dir'); ?></b><br/><small><?php _e('The temporary directory used for storing files when doing file upload.', 'wp-system-health'); ?></br></td></tr>
			<tr><td><?php _e('Maximum File Size', 'wp-system-health'); ?><br/><small><i>(upload_max_filesize)</i></small></td><td><b>
				<?php echo size_format($this->boot_loader->convert_ini_bytes(ini_get('upload_max_filesize'))); ?></b><br/><small><?php _e('The maximum size of an uploaded file.', 'wp-system-health'); ?></small>
			</td></tr>
			
			<tr><td width="150px;"><b><?php _e('Data handling:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-3" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr class="wpsh-sect-3" style="display:none"><td><?php _e('Maximum Post Size', 'wp-system-health'); ?><br/><small><i>(post_max_size)</i></small></td><td><b><?php echo size_format($this->boot_loader->convert_ini_bytes(ini_get('post_max_size'))); ?></b><br/><small><?php _e('Sets max size of post data allowed. This setting also affects file upload. To upload large files, this value must be larger than upload_max_filesize.   If memory limit is enabled by your configure script, memory_limit also affects file uploading. Generally speaking, memory_limit should be larger than post_max_size.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-3" style="display:none"><td><?php _e('Multibyte Function Overload', 'wp-system-health'); ?><br/><small><i>(mbstring.func_overload)</i></small></td><td><b><?php echo ini_get('mbstring.func_overload'); ?></b><br/><small><?php _e('Overloads a set of single byte functions by the mbstring counterparts.', 'wp-system-health'); ?></br></td></tr>

			<tr><td width="150px;"><b><?php _e('Language Options:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-4" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr><td><?php _e('Short Open Tags', 'wp-system-health'); ?><br/><small><i>(short_open_tag)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('short_open_tag')); ?></b><br/><small><?php _e('Tells whether the short form (&lt;? ?&gt;) of PHP\'s open tag should be allowed.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-4" style="display:none"><td><?php _e('ASP Tags', 'wp-system-health'); ?><br/><small><i>(asp_tags)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('asp_tags')); ?></b><br/><small><?php _e('Enables the use of ASP-like &lt;% %&gt; tags in addition to the usual &lt;?php ?&gt; tags.', 'wp-system-health'); ?></br></td></tr>					
			<tr class="wpsh-sect-4" style="display:none"><td><?php _e('Zend Engine Compatibitlity', 'wp-system-health'); ?><br/><small><i>(zend.ze1_compatibility_mode)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('zend.ze1_compatibility_mode')); ?></b><br/><small><?php _e('Enable compatibility mode with Zend Engine 1 (PHP&nbsp;4).', 'wp-system-health'); ?></br></td></tr>					
								
			<tr><td width="150px;"><b><?php _e('Security and Safe Mode:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-5" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr><td><?php _e('Remote open Files', 'wp-system-health'); ?><br/><small><i>(allow_url_fopen)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('allow_url_fopen')); ?></b><br/><small><?php _e('This option enables the URL-aware fopen wrappers that enable accessing URL object like files. Should be disabled for security reasons, to prevent remote file inclusion attacks.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-5" style="display:none"><td><?php _e('Remote include Files', 'wp-system-health'); ?><br/><small><i>(allow_url_include)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('allow_url_include')); ?></b><br/><small><?php _e('This option allows the use of URL-aware fopen wrappers with the following functions: include(), include_once(), require(), require_once(). Should be disabled for security reasons, to prevent remote file inclusion attacks.', 'wp-system-health'); ?></br></td></tr>					
			<tr><td><?php _e('PHP Safe Mode', 'wp-system-health'); ?><br/><small><i>(safe_mode)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('safe_mode')); ?></b><br/><small><?php _e('Whether to enable PHP\'s safe mode.', 'wp-system-health'); ?></br></td></tr>
			<tr><td><?php _e('Open Basedir', 'wp-system-health'); ?><br/><small><i>(open_basedir)</i></small></td><td><b><?php echo ini_get('open_basedir'); ?></b><br/><small><?php _e('Limit the files that can be opened by PHP to the specified directory-tree, including the file itself. This directive is NOT affected by whether Safe Mode is turned On or Off.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-5" style="display:none"><td><?php _e('Disabled Function', 'wp-system-health'); ?><br/><small><i>(disable_functions)</i></small></td><td><b><?php echo ini_get('disable_functions'); ?></b><br/><small><?php _e('This directive allows you to disable certain functions for security reasons.', 'wp-system-health'); ?></br></td></tr>					
			<tr class="wpsh-sect-5" style="display:none"><td><?php _e('Disabled Classes', 'wp-system-health'); ?><br/><small><i>(disable_classes)</i></small></td><td><b><?php echo ini_get('disable_classes'); ?></b><br/><small><?php _e('This directive allows you to disable certain classes for security reasons.', 'wp-system-health'); ?></br></td></tr>
			<?php else : ?>
			<tr><td width="160px;"><?php _e('PHP Version', 'wp-system-health'); ?></td><td colspan="2"><b><?php echo PHP_VERSION; ?></b></td></tr>
			<?php foreach($phpinfo as $key => $data) { wpsh_phpinfo_section($key,$data); } ?>
			<?php endif; ?>					
		</table>						
	</div>
	<div id="wpsh-wordpress" class="ui-tabs-hide">
		<table width="100%" class="fixed widefat" cellspacing="8px"  style="border-style: none;">		

			<tr><td width="210px;"><b><?php _e('Core Information:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-13" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr><td><?php _e('Version', 'wp-system-health'); ?></td><td><b><?php echo $wp_version; ?></b></td></tr>
			<tr><td><?php _e('Installation Type', 'wp-system-health'); ?></td><td><b><?php echo ($this->is_multisite() ? __('Multi Site Installation', 'wp-system-health') : __('Standard Installation', 'wp-system-health')); ?></b></td></tr>
			<?php if ($this->is_multisite()) : $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid)); ?>
				
				<tr class="wpsh-sect-13" style="display:none">
					<td><?php _e('Blogs Overview', 'wp-system-health'); ?><br/><small><i>(<?php echo sprintf(_n('total %s blog', 'total %s blogs', count($rows),'wp-system-health'),count($rows))?>)</i></small></td>
					<td>
					<?php 
						$num=1;
						foreach($rows as $row) : ?>
							<div style="margin-bottom:10px;">
							<?php echo '<em><small>[ID: '.$row->blog_id.']</em></small> - <b>'.$row->domain.$row->path; ?></b><br/>
							<small>
								<?php _e('public:','wp-system-health'); echo ' <b>'.($row->public ? __('Yes', 'wp-system-health') :  __('No', 'wp-system-health')).'</b> | '; ?>
								<?php _e('deleted:','wp-system-health'); echo ' <b>'.($row->deleted ? __('Yes', 'wp-system-health') :  __('No', 'wp-system-health')).'</b> | '; ?>
								<?php _e('spam:','wp-system-health'); echo ' <b>'.($row->spam ? __('Yes', 'wp-system-health') :  __('No', 'wp-system-health')).'</b> | '; ?>
								<?php _e('archived:','wp-system-health'); echo ' <b>'.($row->archived ? __('Yes', 'wp-system-health') :  __('No', 'wp-system-health')).'</b>'; ?>
							</small>
							</div>
						<?php endforeach; ?>
					</td>
				</tr>
			<?php else : ?>
			<tr class="wpsh-sect-13" style="display:none"><td><?php _e('Active Plugins', 'wp-system-health'); ?></td><td><b><?php echo count(get_option('active_plugins')); ?></b></td></tr>
			<?php endif; ?>
		
			<tr><td width="160px;"><b><?php _e('Automatic Updates:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-12" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr><td><?php _e('Language', 'wp-system-health'); ?><br/><small><i>(WPLANG)</i></small></td><td><b><?php echo ( defined('WPLANG') && (strlen(WPLANG) > 0) ? WPLANG : '' ); ?></b></td></tr>
			<tr><td><?php _e('Language', 'wp-system-health'); ?><br/><small><i>(get_locale)</i></small></td><td><b><?php echo $cur_locale; ?></b></td></tr>
			<tr>
				<td><?php _e('Update Method', 'wp-system-health'); ?><br/><small><i>(evaluated)</i></small></td>
				<td>
					<b><?php $fsm = get_filesystem_method(array()); echo $fsm; ?></b><br/><small><?php _e('Describes the layer been used to performs automatic updates at your WordPress installation.', 'wp-system-health'); ?>&nbsp;
					<?php 
					if ($fsm != 'direct') : ?>
						<span style="color:#f00"><?php _e('Your provider denies direct file access for security reasons. That\'s why only FTP/SSH access is permitted.', 'wp-system-health'); ?></span><br/>
					<?php endif; ?>
				</td>
			</tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('File System Method (forced)', 'wp-system-health'); ?><br/><small><i>(FS_METHOD)</i></small></td><td><b><?php echo (defined('FS_METHOD') ? FS_METHOD : ''); ?></b><br/><small><?php _e('Forces the filesystem method. It should only be "direct", "ssh", "ftpext", or "ftpsockets". Generally, You should only change this if you are experiencing update problems.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP Base Path', 'wp-system-health'); ?><br/><small><i>(FTP_BASE)</i></small></td><td><b><?php echo (defined('FTP_BASE') ? FTP_BASE : ''); ?></b><br/><small><?php _e('The full path to the "base"(ABSPATH) folder of the WordPress installation', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP Content Dir', 'wp-system-health'); ?><br/><small><i>(FTP_CONTENT_DIR)</i></small></td><td><b><?php echo (defined('FTP_CONTENT_DIR') ? FTP_CONTENT_DIR : ''); ?></b><br/><small><?php _e('The full path to the wp-content folder of the WordPress installation.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP Plugin Dir', 'wp-system-health'); ?><br/><small><i>(FTP_PLUGIN_DIR)</i></small></td><td><b><?php echo (defined('FTP_PLUGIN_DIR') ? FTP_PLUGIN_DIR : ''); ?></b><br/><small><?php _e('The full path to the plugins folder of the WordPress installation.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP Public Key', 'wp-system-health'); ?><br/><small><i>(FTP_PUBKEY)</i></small></td><td><b><?php echo (defined('FTP_PUBKEY') ? FTP_PUBKEY : ''); ?></b><br/><small><?php _e('The full path to your SSH public key.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP Private Key', 'wp-system-health'); ?><br/><small><i>(FTP_PRIKEY)</i></small></td><td><b><?php echo (defined('FTP_PRIKEY') ? FTP_PRIKEY : ''); ?></b><br/><small><?php _e('The full path to your SSH private key.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP User', 'wp-system-health'); ?><br/><small><i>(FTP_USER)</i></small></td><td><b><?php echo (defined('FTP_USER') ? '****** <i><small>('.__('defined, but not shown for security reasons','wp-system-health').')</small></i>' : ''); ?></b><br/><small><?php _e('FTP_USER is either user FTP or SSH username. Most likely these are the same, but use the appropriate one for the type of update you wish to do. ', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP Password', 'wp-system-health'); ?><br/><small><i>(FTP_PASS)</i></small></td><td><b><?php echo (defined('FTP_PASS') ? '****** <i><small>('.__('defined, but not shown for security reasons','wp-system-health').')</small></i>' : ''); ?></b><br/><small><?php _e('FTP_PASS is the password for the username entered for FTP_USER. If you are using SSH public key authentication this can be omitted.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP Host', 'wp-system-health'); ?><br/><small><i>(FTP_HOST)</i></small></td><td><b><?php echo (defined('FTP_HOST') ? FTP_HOST : ''); ?></b><br/><small><?php _e('The hostname:port combination for your SSH/FTP server. The default FTP port is 21 and the default SSH port is 22, These do not need to be mentioned.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-12" style="display:none"><td><?php _e('FTP SSL', 'wp-system-health'); ?><br/><small><i>(FTP_SSL)</i></small></td><td><b><?php echo (defined('FTP_SSL') ? FTP_SSL : ''); ?></b><br/><small><?php _e('TRUE for SSL-connection if supported by the underlying transport, Not available on all servers. This is for "Secure FTP" not for SSH SFTP.', 'wp-system-health'); ?></br></td></tr>
		
			<tr><td width="160px;"><b><?php _e('System Constants:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-7" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr class="wpsh-sect-7" style="display:none"><td colspan="2"><?php _e('This section list most of WordPress constants can be configured at your <em>wp-config.php</em> file. Some of this constants have to be handled with care, please read additionally:', 'wp-system-health'); ?> <a target="_blank" href="http://codex.wordpress.org/Editing_wp-config.php">WordPress Codex Page</a></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Subdomain Install', 'wp-system-health'); ?><br/><small><i>(SUBDOMAIN_INSTALL)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("SUBDOMAIN_INSTALL"); ?></b><br/><small><?php _e('Determines a subdomain installation if VHOST not set.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Subdomain Install', 'wp-system-health'); ?><br/><small><i>(VHOST)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("VHOST"); ?></b><br/><small><?php _e('Determines a subdomain installation if SUBDOMAIN_INSTALL not set.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('WordPress address', 'wp-system-health'); ?><br/><small><i>(WP_SITEURL)</i></small></td><td><b><?php echo (defined('WP_SITEURL') ? WP_SITEURL : ''); ?></b><br/><small><?php _e('Allows the WordPress address (URL) to be defined. The valued defined is the address where your WordPress core files reside.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Blog address', 'wp-system-health'); ?><br/><small><i>(WP_HOME)</i></small></td><td><b><?php echo (defined('WP_HOME') ? WP_HOME : ''); ?></b><br/><small><?php _e('Similar to WP_SITEURL, WP_HOME overrides the wp_options table value for home but does not change it permanently. home is the address you want people to type in their browser to reach your WordPress blog.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Content Dir', 'wp-system-health'); ?><br/><small><i>(WP_CONTENT_DIR)</i></small></td><td><b><?php echo (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ''); ?></b><br/><small><?php _e('Defines the WordPress content directory.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Language Dir', 'wp-system-health'); ?><br/><small><i>(WP_LANG_DIR)</i></small></td><td><b><?php echo (defined('WP_LANG_DIR') ? WP_LANG_DIR : ''); ?></b><br/><small><?php _e('Defines what directory the WPLANG .mo file resides. If WP_LANG_DIR is not defined WordPress looks first to wp-content/languages and then wp-includes/languages for the .mo defined by WPLANG file.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Plugin Dir', 'wp-system-health'); ?><br/><small><i>(WP_PLUGIN_DIR)</i></small></td><td><b><?php echo (defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : ''); ?></b><br/><small><?php _e('Defines the Wordpress Plugin directory.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('WPMU Plugin Dir', 'wp-system-health'); ?><br/><small><i>(WPMU_PLUGIN_DIR)</i></small></td><td><b><?php echo (defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : ''); ?></b><br/><small><?php _e('Defines the must-use Plugin directory.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Plugin Dir <small>(compatibility)</small>', 'wp-system-health'); ?><br/><small><i>(PLUGINDIR)</i></small></td><td><b><?php echo (defined('PLUGINDIR') ? PLUGINDIR : ''); ?></b><br/><small><?php _e('Defines the relative WordPress Plugin directory.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('WPMU Plugin Dir <small>(compatibility)</small>', 'wp-system-health'); ?><br/><small><i>(MUPLUGINDIR)</i></small></td><td><b><?php echo (defined('MUPLUGINDIR') ? MUPLUGINDIR : ''); ?></b><br/><small><?php _e('Defines the relative must-use Plugin directory.', 'wp-system-health'); ?></br></td></tr>				
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('WP Temp Dir', 'wp-system-health'); ?><br/><small><i>(WP_TEMP_DIR)</i></small></td><td><b><?php echo (defined('WP_TEMP_DIR') ? WP_TEMP_DIR : ''); ?></b><br/><small><?php _e('If it is set, the temp dir will be used as replacement by any action WordPress needs a temp dir.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Uploads', 'wp-system-health'); ?><br/><small><i>(UPLOADS)</i></small></td><td><b><?php echo (defined('UPLOADS') ? UPLOADS : ''); ?></b><br/><small><?php _e('Only defined, if upload folder has been moved.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Content URL', 'wp-system-health'); ?><br/><small><i>(WP_CONTENT_URL)</i></small></td><td><b><?php echo (defined('WP_CONTENT_URL') ? WP_CONTENT_URL : ''); ?></b><br/><small><?php _e('Defines the URL part for direct access files from WordPress content directory.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Plugin URL', 'wp-system-health'); ?><br/><small><i>(WP_PLUGIN_URL)</i></small></td><td><b><?php echo (defined('WP_PLUGIN_URL') ? WP_PLUGIN_URL : ''); ?></b><br/><small><?php _e('Defines the URL part for direct access files from WordPress plugins directory.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('WPMU Plugin URL', 'wp-system-health'); ?><br/><small><i>(WPMU_PLUGIN_URL)</i></small></td><td><b><?php echo (defined('WPMU_PLUGIN_URL') ? WPMU_PLUGIN_URL : ''); ?></b><br/><small><?php _e('Defines the URL part for direct access files from WordPress must-use plugins directory.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Cookie Domain', 'wp-system-health'); ?><br/><small><i>(COOKIE_DOMAIN)</i></small></td><td><b><?php echo (defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : ''); ?></b><br/><small><?php _e('The domain set in the cookies for WordPress can be specified for those with unusual domain setups.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Default Theme', 'wp-system-health'); ?><br/><small><i>(WP_DEFAULT_THEME)</i></small></td><td><b><?php echo (defined('WP_DEFAULT_THEME') ? WP_DEFAULT_THEME : ''); ?></b><br/><small><?php _e('This is the standard theme WordPress uses if your current theme fails or new user will register.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Cache', 'wp-system-health'); ?><br/><small><i>(WP_CACHE)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("WP_CACHE"); ?></b><br/><small><?php _e('If true, includes the wp-content/advanced-cache.php script, when executing wp-settings.php. ', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('WP Memory Limit', 'wp-system-health'); ?><br/><small><i>(WP_MEMORY_LIMIT)</i></small></td><td><b><?php echo (defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : ''); ?></b><br/><small><?php _e('The defined initially memory limit WordPress suggests for your installation.', 'wp-system-health'); ?></br></td></tr>
			
			<tr><td><?php _e('Post Revision Handling', 'wp-system-health'); ?><br/><small><i>(WP_POST_REVISIONS)</i></small></td>
				<td><b><?php 
					$state = __('-n.a.-', 'wp-system-health');
					if ((WP_POST_REVISIONS === false) || (WP_POST_REVISIONS === 0)) {
						$state = __('Off', 'wp-system-health');
					}elseif ((WP_POST_REVISIONS === true) || (WP_POST_REVISIONS === -1)) {
						$state = __('no limit', 'wp-system-health');
					}
					else {
						$state = WP_POST_REVISIONS;
					}
					echo $state; ?></b><br/><small><?php _e('Whether to skip, enable or limit post revisions.', 'wp-system-health'); ?></br>
				</td>
			</tr>
			<tr>
				<td><?php _e('Automatic Save Interval', 'wp-system-health'); ?><br/><small><i>(AUTOSAVE_INTERVAL)</i></small></td>
				<td><b><?php echo (AUTOSAVE_INTERVAL == false ? __('Off', 'wp-system-health') : AUTOSAVE_INTERVAL.' '.__('seconds', 'wp-system-health')); ?></b><br/><small><?php _e('Post/Page Editor time interval for sending an automatic draft save.', 'wp-system-health'); ?></br></td>
			</tr>
			<tr>
				<td><?php _e('Empty Trash Interval', 'wp-system-health'); ?><br/><small><i>(EMPTY_TRASH_DAYS)</i></small></td>
				<td><b><?php echo (!defined('EMPTY_TRASH_DAYS') || EMPTY_TRASH_DAYS == 0  ? __('Off', 'wp-system-health') : EMPTY_TRASH_DAYS.' '.__('days', 'wp-system-health')); ?></b><br/><small><?php _e('Constant controls the number of days before WordPress permanently deletes posts, pages, attachments, and comments, from the trash bin.', 'wp-system-health'); ?></br></td>
			</tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Media Trash', 'wp-system-health'); ?><br/><small><i>(MEDIA_TRASH)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("MEDIA_TRASH"); ?></b><br/><small><?php _e('Permits media files to be trashed.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Journal Database Requests', 'wp-system-health'); ?><br/><small><i>(SAVEQUERIES)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("SAVEQUERIES"); ?></b><br/><small><?php _e('Whether to enable journal of database queries performed.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Concatinate Javascripts', 'wp-system-health'); ?><br/><small><i>(CONCATENATE_SCRIPTS)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("CONCATENATE_SCRIPTS"); ?></b><br/><small><?php _e('Whether to enable concatination of all enqueued Javascripts into one single file for serving.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Compress Javascripts', 'wp-system-health'); ?><br/><small><i>(COMPRESS_SCRIPTS)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("COMPRESS_SCRIPTS"); ?></b><br/><small><?php _e('Whether to enable compression of Javascripts being served.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Compress Stylesheets', 'wp-system-health'); ?><br/><small><i>(COMPRESS_CSS)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("COMPRESS_CSS"); ?></b><br/><small><?php _e('Whether to enable compression of Stylesheet files being served.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Enforce GZip Compression', 'wp-system-health'); ?><br/><small><i>(ENFORCE_GZIP)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("ENFORCE_GZIP"); ?></b><br/><small><?php _e('Whether to force GZip instead of Inflate if compression is enabled.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Debug WordPress', 'wp-system-health'); ?><br/><small><i>(WP_DEBUG)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("WP_DEBUG"); ?></b><br/><small><?php _e('Controls the display of some errors and warnings.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Debug WordPress visual', 'wp-system-health'); ?><br/><small><i>(WP_DEBUG_DISPLAY)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("WP_DEBUG_DISPLAY"); ?></b><br/><small><?php _e('Globally configured setting for display_errors and not force errors to be displayed.', 'wp-system-health'); ?></br></td></tr>					
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Debug WordPress logfile', 'wp-system-health'); ?><br/><small><i>(WP_DEBUG_LOG)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("WP_DEBUG_LOG"); ?></b><br/><small><?php _e('Enable error logging to wp-content/debug.log', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Debug javascripts', 'wp-system-health'); ?><br/><small><i>(SCRIPT_DEBUG)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("SCRIPT_DEBUG"); ?></b><br/><small><?php _e('This will allow you to edit the scriptname.dev.js files in the wp-includes/js and wp-admin/js directories. ', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Automatic Database Optimizing', 'wp-system-health'); ?><br/><small><i>(WP_ALLOW_REPAIR)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("WP_ALLOW_REPAIR"); ?></b><br/><small><?php _e('That this define enables the functionality, The user does not need to be logged in to access this functionality when this define is set. This is because its main intent is to repair a corrupted database, Users can often not login when the database is corrupt.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Alternative Cron', 'wp-system-health'); ?><br/><small><i>(ALTERNATE_WP_CRON)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("ALTERNATE_WP_CRON"); ?></b><br/><small><?php _e('This alternate method uses a redirection approach, which makes the users browser get a redirect when the cron needs to run, so that they come back to the site immediately while cron continues to run in the connection they just dropped. This method is a bit iffy sometimes, which is why it\'s not the default.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Disable WP Cron', 'wp-system-health'); ?><br/><small><i>(DISABLE_WP_CRON)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("DISABLE_WP_CRON"); ?></b><br/><small><?php _e('Disable any WordPress based cron jobs entirely.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('WP Cron Lock Timeout', 'wp-system-health'); ?><br/><small><i>(WP_CRON_LOCK_TIMEOUT)</i></small></td><td><b><?php echo (defined('WP_CRON_LOCK_TIMEOUT') ? WP_CRON_LOCK_TIMEOUT.' '.__('seconds', 'wp-system-health') : ''); ?></b><br/><small><?php _e('Disable any WordPress based cron jobs entirely.', 'wp-system-health'); ?></br></td></tr>
			
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Unfiltered Uploads', 'wp-system-health'); ?><br/><small><i>(ALLOW_UNFILTERED_UPLOADS)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("ALLOW_UNFILTERED_UPLOADS"); ?></b><br/><small><?php _e('Disallow unfiltered uploads by default, even for admins.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Unfiltered HTML', 'wp-system-health'); ?><br/><small><i>(DISALLOW_UNFILTERED_HTML)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("DISALLOW_UNFILTERED_HTML"); ?></b><br/><small><?php _e('This constant you can use to disallow unfiltered HTML for everyone, including administrators and super administrators.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Disable File Editing', 'wp-system-health'); ?><br/><small><i>(DISALLOW_FILE_EDIT)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("DISALLOW_FILE_EDIT"); ?></b><br/><small><?php _e('The Wordpress Dashboard by default allows administrators to edit PHP files, such as plugin and theme files. Use this constant to disable editing from Dashboard. ', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Disable Any Update', 'wp-system-health'); ?><br/><small><i>(DISALLOW_FILE_MODS)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("DISALLOW_FILE_MODS"); ?></b><br/><small><?php _e('This will block users being able to use the plugin and theme installation/update functionality from the WordPress admin area. Setting this constant also disables the Plugin and Theme editor.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Block External HTTP', 'wp-system-health'); ?><br/><small><i>(WP_HTTP_BLOCK_EXTERNAL)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("WP_HTTP_BLOCK_EXTERNAL"); ?></b><br/><small><?php _e('You block external URL requests by defining WP_HTTP_BLOCK_EXTERNAL as true in your wp-config.php file and this will only allow localhost and your blog to make requests.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Accessible Hosts', 'wp-system-health'); ?><br/><small><i>(WP_ACCESSIBLE_HOSTS)</i></small></td><td><b><?php echo (defined('WP_ACCESSIBLE_HOSTS') ? WP_ACCESSIBLE_HOSTS : ''); ?></b><br/><small><?php _e('The constant WP_ACCESSIBLE_HOSTS will allow additional hosts to go through for requests. The format of the WP_ACCESSIBLE_HOSTS constant is a comma separated list of hostnames to allow, wildcard domains are supported, eg *.wordpress.org will allow for all subdomains of wordpress.org to be contacted.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Force SSL Admin', 'wp-system-health'); ?><br/><small><i>(FORCE_SSL_ADMIN)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("FORCE_SSL_ADMIN"); ?></b><br/><small><?php _e('Determine whether the administration panel should be viewed over SSL.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Force SSL Login', 'wp-system-health'); ?><br/><small><i>(FORCE_SSL_LOGIN)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("FORCE_SSL_LOGIN"); ?></b><br/><small><?php _e('Determine whether the login page should be viewed over SSL.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('No Blog redirect', 'wp-system-health'); ?><br/><small><i>(NOBLOGREDIRECT)</i></small></td><td><b><?php echo (defined('NOBLOGREDIRECT') ? NOBLOGREDIRECT : ''); ?></b><br/><small><?php _e('Applies only for WordPress Multisite installation. Correct 404 redirects when NOBLOGREDIRECT is defined.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Custom User Table', 'wp-system-health'); ?><br/><small><i>(CUSTOM_USER_TABLE)</i></small></td><td><b><?php echo (defined('CUSTOM_USER_TABLE') ? CUSTOM_USER_TABLE : ''); ?></b><br/><small><?php _e('Is used to designated that the user table normally utilized by WordPress is not used, instead this value/table is used to store your user information.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Custom User Meta Table', 'wp-system-health'); ?><br/><small><i>(CUSTOM_USER_META_TABLE)</i></small></td><td><b><?php echo (defined('CUSTOM_USER_META_TABLE') ? CUSTOM_USER_META_TABLE : ''); ?></b><br/><small><?php _e('Is used to designated that the user meta table normally utilized by WordPress is not used, instead this value/table is used to store your user meta information.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Do not upgrade global tables', 'wp-system-health'); ?><br/><small><i>(DO_NOT_UPGRADE_GLOBAL_TABLES)</i></small></td><td><b><?php echo $this->boot_loader->convert_const_bool("DO_NOT_UPGRADE_GLOBAL_TABLES"); ?></b><br/><small><?php _e('Sites that have large global tables (particularly users and usermeta), as well as sites that share user tables with bbPress and other WordPress installs, can prevent the upgrade from changing those tables during upgrade by defining DO_NOT_UPGRADE_GLOBAL_TABLES. Since an ALTER, or an unbounded DELETE or UPDATE, can take a long time to complete, large sites usually want to avoid these being run as part of the upgrade so they can handle it themselves. Further, if installations are sharing user tables between multiple bbPress and WordPress installs it maybe necessary to want one site to be the upgrade master.', 'wp-system-health'); ?></br></td></tr>

			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Proxy Host', 'wp-system-health'); ?><br/><small><i>(WP_PROXY_HOST)</i></small></td><td><b><?php echo (defined('WP_PROXY_HOST') ? WP_PROXY_HOST : ''); ?></b><br/><small><?php _e('Enable proxy support and host for connecting.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Proxy Port', 'wp-system-health'); ?><br/><small><i>(WP_PROXY_PORT)</i></small></td><td><b><?php echo (defined('WP_PROXY_PORT') ? WP_PROXY_PORT : ''); ?></b><br/><small><?php _e('Proxy port for connection. No default, must be defined.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Proxy Username', 'wp-system-health'); ?><br/><small><i>(WP_PROXY_USERNAME)</i></small></td><td><b><?php echo (defined('WP_PROXY_USERNAME') ? '****** <i><small>('.__('defined, but not shown for security reasons','wp-system-health').')</small></i>' : ''); ?></b><br/><small><?php _e('Proxy username, if it requires authentication.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Proxy Password', 'wp-system-health'); ?><br/><small><i>(WP_PROXY_PASSWORD)</i></small></td><td><b><?php echo (defined('WP_PROXY_PASSWORD') ? '****** <i><small>('.__('defined, but not shown for security reasons','wp-system-health').')</small></i>' : ''); ?></b><br/><small><?php _e('Proxy password, if it requires authentication.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Proxy Bypass Hosts', 'wp-system-health'); ?><br/><small><i>(WP_PROXY_BYPASS_HOSTS)</i></small></td><td><b><?php echo (defined('WP_ACCESSIBLE_HOSTS') ? WP_ACCESSIBLE_HOSTS : ''); ?></b><br/><small><?php _e('Will prevent the hosts in this list from going through the proxy.', 'wp-system-health'); ?></br></td></tr>
						
			<tr><td width="160px;"><b><?php _e('HTTP Transport:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-6" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<?php foreach ( $this->transports_available as $transport => $data ) : ?>
				<tr class="wpsh-sect-6" style="display:none"><td><?php echo $data['name']; ?><br/><small><i>(<?php echo $transport; ?>)</i></small></td><td><b><?php echo ($data['use'] ? __('Yes', 'wp-system-health') :  __('No', 'wp-system-health')); ?></b><br/><small><?php echo $data['desc']; ?></br></td></tr>
			<?php endforeach; ?>
		</table>						
	</div>
	<?php if ($this->l10n_tracing) : ?>
	<div id="wpsh-l10n">
		<?php $num_gettext_files = 0; $num_gettext_strings = 0; $size_gettext_files = 0; ?>
		<table width="100%" class="fixed widefat" cellspacing="8px"  style="border-style: none;">
			<tr><td width="110px;"><b><?php _e('Textdomains:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-14" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr>
				<td><?php _e('WordPress', 'wp-system-health'); ?><br/><small><i>(textdomain: "default")</i></small></td>
				<td><?php 
					foreach($this->l10n_loaded as $file => $domain) {
						if ($domain == 'default') : ?>
							<?php _e('File:', 'wp-system-health'); ?> <strong><?php echo  str_replace(ABSPATH,'',$file); ?></strong><br/>
							<?php if(file_exists($file)) : ?>
								<?php _e('Size:', 'wp-system-health'); ?> <strong><?php $s = filesize($file); echo size_format($this->boot_loader->convert_ini_bytes($s)); ?></strong><br/>
								<?php _e('Strings:', 'wp-system-health'); ?> <strong><?php $e = $this->_get_mofile_entries($file); echo $e; ?></strong><br/><br/> 
								<?php $num_gettext_strings += $e; $size_gettext_files += $s; $num_gettext_files++;?>
							<?php else : ?>
								<?php if ($cur_locale != 'en_US') : ?>
									<strong style="color:red;"><?php _e('Translation File missing!', 'wp-system-health'); ?></strong><br/><br/>
								<?php else : ?>
									<?php _e('Translation File missing but not required.', 'wp-system-health'); ?><br/><br/>
								<?php endif; ?>
							<?php endif; ?>
					<?php endif; } ?>
				</td>
			</tr>
			<?php 
			foreach($this->l10n_loaded as $file => $domain) {
				if ($domain != 'default') : ?>
				<tr class="wpsh-sect-14" style="display:none"><td><?php echo $domain; ?><br/></td><td>
					<?php if(file_exists($file)) : ?>
						<?php _e('File:', 'wp-system-health'); ?> <strong><?php echo basename($file); ?></strong><br/>
						<?php _e('Size:', 'wp-system-health'); ?> <strong><?php $s = filesize($file); echo size_format($this->boot_loader->convert_ini_bytes($s)); ?></strong><br/>
						<?php _e('Strings:', 'wp-system-health'); ?> <strong><?php $e = $this->_get_mofile_entries($file); echo $e; ?></strong><br/> 
						<?php $num_gettext_strings += $e; $size_gettext_files += $s; $num_gettext_files++; ?>
					<?php else : ?>
						<?php if ($cur_locale != 'en_US') : ?>
							<?php _e('File:', 'wp-system-health'); ?> <strong><?php echo str_replace(ABSPATH,'',$file); ?></strong><br/>
							<strong style="color:red;"><?php _e('Translation File missing!', 'wp-system-health'); ?></strong><br/>
						<?php else : ?>
							<?php _e('File:', 'wp-system-health'); ?> <strong><?php echo str_replace(ABSPATH,'',$file); ?></strong><br/>
							<?php _e('Translation File missing but not required.', 'wp-system-health'); ?><br/>
						<?php endif; ?>

					<?php endif; ?>
				</td></tr>
			<?php endif; } ?>
			<tr>
				<td><?php _e('total translations', 'wp-system-health'); ?><br/><small><i>(<?php _e('summary','wp-system-health'); ?>)</i></small></td>
				<td>
					<?php _e('Loaded Files:', 'wp-system-health'); ?> <strong><?php echo $num_gettext_files; ?></strong><br/> 
					<?php _e('Total Size:', 'wp-system-health'); ?> <strong><?php echo ($size_gettext_files > 0 ? size_format($this->boot_loader->convert_ini_bytes($size_gettext_files)) : '0 B'); ?></strong><br/>
					<?php _e('Total Strings:', 'wp-system-health'); ?> <strong><?php echo $num_gettext_strings; ?></strong><br/> 
				</td>
			</tr>
		</table>						
	</div>
	<?php endif; ?>
	<div id="wpsh-database" class="ui-tabs-hide">
		<table width="100%" class="fixed widefat" cellspacing="8px"  style="border-style: none;">
			<tr><td width="160px;"><b><?php _e('Settings:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-11" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr><td><?php _e('MySQL Server', 'wp-system-health'); ?></td><td><b><?php echo $mysql_server_version; ?></b></td></tr>
			<tr class="wpsh-sect-11" style="display:none"><td><?php _e('Name', 'wp-system-health'); ?><br/><small><i>(DB_NAME)</i></small></td><td><b><?php echo DB_NAME; ?></b><br/><small><?php _e('Your database name currently configured.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-11" style="display:none"><td><?php _e('Host', 'wp-system-health'); ?><br/><small><i>(DB_HOST)</i></small></td><td><b><?php echo DB_HOST; ?></b><br/><small><?php _e('Your Database host currently configured.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-11" style="display:none"><td><?php _e('Charset', 'wp-system-health'); ?><br/><small><i>(DB_CHARSET)</i></small></td><td><b><?php echo DB_CHARSET; ?></b><br/><small><?php _e('Your Database character set currently configured.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-11" style="display:none"><td><?php _e('Collation', 'wp-system-health'); ?><br/><small><i>(DB_COLLATE)</i></small></td><td><b><?php echo DB_COLLATE; ?></b><br/><small><?php _e('Your Database collation currently configured.', 'wp-system-health'); ?></br></td></tr>
		
		<?php if (!$this->is_multisite()) : ?>
		
			<tr><td width="160px;"><b><?php _e('Table Statistics:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-8" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<?php
			global $wpdb, $table_prefix;
			$tables = array(
				$table_prefix.'comments', 
				$table_prefix.'links', 
				$table_prefix.'options', 
				$table_prefix.'postmeta', 
				$table_prefix.'posts', 
				$table_prefix.'term_relationships', 
				$table_prefix.'term_taxonomy', 
				$table_prefix.'terms', 
				$table_prefix.'usermeta', 
				$table_prefix.'users'
			);
			$res = $wpdb->get_results('SHOW TABLE STATUS');
			$counter = array();
			$db_memory = array('wordpress' => 0, 'custom' => 0 );
			foreach($res as $row) : ?>
					<tr<?php if ($row->Name != $table_prefix.'posts') echo  ' class="wpsh-sect-8" style="display:none"'; ?>>
						<?php if (in_array($row->Name, $tables)) : $db_memory['wordpress'] += ($row->Data_length + $row->Index_length + $row->Data_free); ?>
						<td><?php echo $row->Name; $counter[$row->Name] = $row->Rows; ?><br/><small><i>(<?php echo $row->Engine; ?> | <?php _e('WordPress', 'wp-system-health'); ?>)</i></small></td>
						<?php else : $db_memory['custom'] += $row->Data_length + $row->Index_length + $row->Data_free; ?>
						<td><?php echo $row->Name; $counter[$row->Name] = $row->Rows; ?><br/><small><i>(<?php echo $row->Engine; ?> | <?php _e('Custom', 'wp-system-health'); ?>)</i></small></td>
						<?php endif; ?>
						<td>
							<b><?php echo $row->Rows.' '._n('row', 'rows',$row->Rows, 'wp-system-health'); ?></b><br/>
							<i><?php _e('Data:', 'wp-system-health'); ?> </i><?php echo ($row->Data_length == 0 ? '0 B' : size_format($row->Data_length,2)); ?><br/> 
							<i><?php _e('Index:', 'wp-system-health'); ?> </i><?php echo ($row->Index_length == 0 ? '0 B' : size_format($row->Index_length,2)); ?><br/> 
							<i><?php _e('Waste:', 'wp-system-health'); ?> </i><?php echo ($row->Data_free == 0 ? '0 B' : size_format($row->Data_free,2)); ?><br/>
						</td>
					</tr>	
			<?php endforeach; ?>
			
			<tr><td width="160px;"><b><?php _e('Utilization Summary:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-9" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<tr class="wpsh-sect-9" style="display:none"><td><?php _e('WordPress Tables', 'wp-system-health'); ?><br/><small><i>(memory usage)</i></small></td><td><b><?php echo size_format($db_memory['wordpress'], 3) ?></b><br/><small><?php _e('Amount of memory your WordPress tables currently utilize.', 'wp-system-health'); ?></br></td></tr>
			<tr class="wpsh-sect-9" style="display:none"><td><?php _e('Custom/Plugin Tables', 'wp-system-health'); ?><br/><small><i>(memory usage)</i></small></td><td><b><?php echo size_format($db_memory['custom'], 3) ?></b><br/><small><?php _e('Amount of memory your Custom tables currently utilize.', 'wp-system-health'); ?></br></td></tr>
			<tr><td><?php _e('All Tables', 'wp-system-health'); ?><br/><small><i>(memory usage)</i></small></td><td><b><?php echo size_format($db_memory['wordpress'] + $db_memory['custom'], 3) ?></b><br/><small><?php _e('Amount of memory your database currently utilize in total.', 'wp-system-health'); ?></br></td></tr>
			
			<tr><td width="160px;"><b><i><?php _e('Posts', 'wp-system-health'); ?></i> <?php _e('Table Analysis:', 'wp-system-health'); ?></b></td>
				<td><a class="button-secondary wpsh-toggle-section" id="wpsh-sect-10" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
			</tr>
			<?php 
			$res = $wpdb->get_results('SELECT post_type, COUNT(post_type) as entries FROM '.$table_prefix.'posts GROUP BY post_type');
			$total = $counter[$table_prefix.'posts'];
			foreach($res as $row) { ?>
				<tr<?php if ($row->post_type != 'post') echo  ' class="wpsh-sect-10" style="display:none"'; ?>>
					<td><?php echo $row->post_type; ?><br/><small><i>(Type)</i></small></td>
					<td>
					<?php 
						$perc = round(($total != 0 ? $row->entries * 100.0 / $total: 0), 2);
						if ($row->post_type == 'revision') {
							$this->_echo_progress_bar($perc, $row->entries.' '._n('row', 'rows', $row->entries, 'wp-system-health'), __('percentage of total rows', 'wp-system-health'), 50, 70);
						}else{
							$this->_echo_progress_bar($perc, $row->entries.' '._n('row', 'rows', $row->entries, 'wp-system-health'), __('percentage of total rows', 'wp-system-health'), 100, 100);
						}
					?>
					</td>
				</tr>
			<?php
			}
			?>
		<?php else : ?>
			<tr><td colspan="2"><br/><?php _e('Sorry for less information here, the database analysis for WordPress Multisite Installation is much more expensive and will be introduced with the next update.', 'wp-system-health'); ?></td></tr>
		<?php endif; ?>
		</table>						
	</div>
	<div id="wpsh-memorycheck" class="ui-tabs-hide">
		<table width="100%" class="fixed widefat" cellspacing="8px"  style="border-style: none;">
			<tr>
				<td width="260px;"><b><?php _e('Memory Limit', 'wp-system-health'); ?></b><br/>(Provider)</td>
				<td><?php echo size_format($this->boot_loader->convert_ini_bytes(ini_get('memory_limit'))); ?>&nbsp;(<?php echo size_format($this->boot_loader->memory_limit ); ?>)</td>
			</tr>
			<tr>
				<td><b><?php _e('Memory Usable', 'wp-system-health'); ?></b><br/>(tested)</td>
				<td id="wpsh-mem-max"><?php _e('-n.a.-', 'wp-system-health'); ?></td>
			</tr>
			<tr>
				<td><b><?php _e('Test Conclusion', 'wp-system-health'); ?></b></td>
				<td id="wpsh-mem-max-perc"><?php _e('-n.a.-', 'wp-system-health'); ?></td>
			</tr>
		</table>
		<p><?php _e('Single Tests Report','wp-system-health'); ?></p>
		<ul id="wsph-check-memory-limit-results"></ul>
		<p>
			<a id="wsph-check-memory-limits" class="button-secondary" href="#"><?php _e('check  memory allocation limit', 'wp-system-health'); ?></a>
		</p>
		<p style="display:none;">
			<img alt="" title="" src="<?php echo admin_url(); ?>images/loading.gif" />&nbsp;<?php _e('test is running, please wait...','wp-system-health'); ?>
		</p>
	</div>
</div>
