<?php

/*
Plugin Name: WP System Health
Version: 1.3.3
Plugin URI: http://www.code-styling.de/english/development/wordpress-plugin-wp-system-health-en
Description: Comprehensive Overview for your WordPress Parameter and Server Performance.
Author: Heiko Rabe
Author URI: http://www.code-styling.de/english/

License:
 ==============================================================================
 Copyright 2009 Heiko Rabe  (email : info@code-styling.de)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 
   Requirements:
 ==============================================================================
 This plugin requires WordPress >= 2.7 and PHP Interpreter >= 4.4.3

*/

if (!function_exists ('add_action')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if (function_exists("admin_url")) {
	define('WPSH_ADMIN_URL', rtrim(strtolower(admin_url()), '/'));
}else{
	define('WPSH_ADMIN_URL', rtrim(strtolower(get_option('siteurl')).'/wp-admin/', '/'));
}


if (!function_exists('_n')) {
	function _n($single, $plural, $number, $domain = 'default') {
		return __gettext($single, $plural, $number, $domain);
	}
}

if (!function_exists('is_super_admin')) {
	function is_super_admin( $user_id = false ) {
		if ( ! $user_id ) {
			$current_user = wp_get_current_user();
			$user_id = ! empty($current_user) ? $current_user->id : 0;
		}

		if ( ! $user_id )
			return false;

		$user = new WP_User($user_id);
		if ( $user->has_cap('delete_users') )
			return true;
			
		return false;
	}
}

class wpsh_plugin {
	function wpsh_plugin() {
		$this->__construct();
	}
	
	function __construct() {
		$this->defaults = new stdClass;
		$this->defaults->disable_dashboard_widget = false;
		$this->defaults->disable_admin_footer = false;
		$this->defaults->disable_rightnow = false;
		$this->defaults->enable_frontend_monitoring = false;
		$this->defaults->quota_block_size = 1024;
		//try to get the options now, repair the PHP 4 issue of cloning not supported
		if (version_compare(phpversion(), "5.0.0", '<')) { $this->options = $this->defaults; } else { $this->options = clone($this->defaults); }
		$tmp = get_option('wp-system-health', $this->defaults);
		foreach(array_keys(get_object_vars($this->defaults)) as $key) {
			if (isset($tmp->$key)) 
				$this->options->$key = $tmp->$key;
		}		
		
		$this->theme_done = false;
		$this->gettext_default_done = false;
		global $wpsh_boot_loader, $wp_version;
		if (!is_object($wpsh_boot_loader) || (get_class($wpsh_boot_loader) != 'wpsh_boot_loader')) {
			require_once('boot-loader.php');
			$this->boot_loader = new wpsh_boot_loader();
		}
		else{
			$this->boot_loader = &$wpsh_boot_loader;
		}
		
		if ($this->boot_loader->is_windows) 
			$this->options->quota_block_size = 1; //Windows have bytes, so override it!
		
		$this->transports_available = array(
			'curl' => array( 'use' => false ), 
			'streams' => array( 'use' => false ), 
			'fopen' => array( 'use' => false ), 
			'fsockopen' => array( 'use' => false ), 
			'exthttp' => array( 'use' => false )
		);
		$this->l10n_tracing = version_compare($wp_version, "2.9", '>=');
		$this->l10n_loaded = array();
		$this->boot_loader->pass_checkpoint('boot:plugin');
		add_action('load_textdomain', array(&$this, 'on_load_textdomain'), 99999, 2);
		add_action('plugins_loaded', array(&$this, 'on_plugins_loaded'), 99999);
		add_action('setup_theme', array(&$this, 'on_setup_theme'), 99999);
		add_filter('gettext', array(&$this, 'on_gettext'), 99999, 2);
		add_action('init', array(&$this, 'on_init'), 99999);
		add_action('admin_init', array(&$this, 'on_admin_init'), 99999);
		add_action('admin_menu', array(&$this, 'on_admin_menu'), 99999);
		add_action('rightnow_end', array(&$this, 'on_rightnow_end'), 0);
		add_action('wp_footer', array(&$this, 'on_frontend_footer'), 99999);
		add_action('admin_post_wp_system_health_save_settings', array(&$this, 'on_save_settings'));
		add_action('wp_ajax_wp_system_healts_check_memory', array(&$this, 'on_ajax_wp_system_healts_check_memory'));
		add_filter('use_fsockopen_transport', array(&$this, 'on_use_fsockopen_transport'));
		add_filter('use_fopen_transport', array(&$this, 'on_use_fopen_transport'));
		add_filter('use_streams_transport', array(&$this, 'on_use_streams_transport'));
		add_filter('use_http_extension_transport', array(&$this, 'on_use_http_extension_transport'));
		add_filter('use_curl_transport', array(&$this, 'on_use_curl_transport'));
		add_filter('update_footer', array (&$this, 'on_footer_text'), 99999);
		$this->plugin_basename = plugin_basename(__FILE__);
		$active_plugins = get_option('active_plugins');
		if ($active_plugins[0] != $this->plugin_basename) {
			//force at least at next page load this plugin to be the first one loaded to show up the other plugins memory consume
			$reorder[] = $this->plugin_basename;
			foreach($active_plugins as $plugin) {
				if ($plugin != $this->plugin_basename)
					$reorder[] = $plugin;
			}
			update_option('active_plugins', $reorder);
		}
	}
	
	function is_multisite() {
		if (!function_exists('is_multisite'))
			return false;
		return is_multisite();
	}
	
	function on_use_fsockopen_transport($use) {
		$this->transports_available['fsockopen']['use'] = $use;
		return $use; //do not change
	}
	function on_use_fopen_transport($use) {
		$this->transports_available['fopen']['use'] = $use;
		return $use; //do not change
	}
	function on_use_streams_transport($use) {
		$this->transports_available['streams']['use'] = $use;
		return $use; //do not change
	}
	function on_use_http_extension_transport($use) {
		$this->transports_available['exthttp']['use'] = $use;
		return $use; //do not change
	}
	function on_use_curl_transport($use) {
		$this->transports_available['curl']['use'] = $use;
		return $use; //do not change
	}

	function on_load_textdomain($domain, $file) {
		$this->l10n_loaded[$file] = $domain;
	}
	
	
	function on_plugins_loaded() {
		$this->boot_loader->pass_checkpoint('hook:plugins_loaded');
	}
	
	function on_setup_theme() {
		$this->boot_loader->pass_checkpoint('hook:setup_theme');
		$this->theme_done = true;
	}
	
	function on_gettext($trans, $org) {
		if ($this->gettext_default_done) return $trans;
		if ($org == 'M_Monday_initial') {
			$this->boot_loader->pass_checkpoint('hook:gettext', ($org != $trans ? false : 0));
			$this->gettext_default_done = true;
		}
		return $trans;
	}
	
	
	function on_init() {
		$this->boot_loader->pass_checkpoint('hook:init');
		$http = _wp_http_get_object(); //force collect of transports
	}
	
	function on_admin_init() {
		load_plugin_textdomain('wp-system-health', false, dirname(plugin_basename(__FILE__)).'/languages');
		$this->transports_available['curl']['name'] = __('PHP libcurl', 'wp-system-health');
		$this->transports_available['curl']['desc']= __('Allows you to connect and communicate to many different types of servers with many different types of protocols.', 'wp-system-health'); 
		$this->transports_available['streams']['name'] = __('PHP Streams API', 'wp-system-health');
		$this->transports_available['streams']['desc'] = __('Streams were introduced with PHP 4.3.0 as a way of generalizing file, network, data compression, and other operations which share a common set of functions and uses.', 'wp-system-health'); 
		$this->transports_available['fopen']['name'] = __('function fopen', 'wp-system-health');
		$this->transports_available['fopen']['desc'] = __('Opens file or URL.', 'wp-system-health');
		$this->transports_available['fsockopen']['name'] = __('function fsockopen', 'wp-system-health');
		$this->transports_available['fsockopen']['desc'] = __('Open Internet or Unix domain socket connection', 'wp-system-health');
		$this->transports_available['exthttp']['name'] = __('Built-in HTTP', 'wp-system-health');
		$this->transports_available['exthttp']['desc'] = __('It eases handling of HTTP URLs, dates, redirects, headers and messages and more.', 'wp-system-health');
		$this->checkpoint_trans = array(
			'boot:wp-config' => array(
				'title' => __('PHP Runtime Boot', 'wp-system-health'), 
				'desc' => __('Will be passed if basic configuration values has been defined and WordPress tries it\'s boot sequence.', 'wp-system-health')
			),
			'boot:wp-config.failed' => array(
				'title' => __('PHP Runtime Boot', 'wp-system-health'), 
				'desc' => __('If you want to know the pure amount of memory the PHP runtime has allocated, read the <a href="http://www.code-styling.de/english/development/wordpress-plugin-wp-system-health-en">documentation</a> about modification of <i>wp-config.php</i> to get a boot strip.', 'wp-system-health')
			),
			'boot:plugin' => array(
				'title' => __('System Health Boot', 'wp-system-health'), 
				'desc' => __('Will be passed if this monitoring plugin has been loaded successful.', 'wp-system-health')
			),
			'hook:plugins_loaded' => array(
				'title' => __('Plugins Active', 'wp-system-health'), 
				'desc' => __('Will be passed if all active plugins have been loaded successful.', 'wp-system-health')
			),
			'hook:setup_theme' => array(
				'title' => __('Theme / Widgets', 'wp-system-health'), 
				'desc' => __('Will be passed if the Theme and Widget Factory has been initialized.', 'wp-system-health')
			),
			'hook:gettext' => array(
				'title' => __('Localization File', 'wp-system-health'), 
				'desc' => __('Will be passed if the WordPress language file has been loaded successful.', 'wp-system-health')
			),
			'hook:init' => array(
				'title' => __('Init WordPress', 'wp-system-health'), 
				'desc' => __('Will be passed if WordPress initialization has been finished.', 'wp-system-health')
			),
			'hook:admin_init' => array(
				'title' => __('Init Admin Center', 'wp-system-health'), 
				'desc' => __('Will be passed if Admin Center initialization has been finished.', 'wp-system-health')
			),
			'callback:dashboard' => array(
				'title' => __('Rendering Dashboard', 'wp-system-health'), 
				'desc' => __('Will be passed during generation of this Dashboard Overview.', 'wp-system-health')
			)
		);
		$this->boot_loader->pass_checkpoint('hook:admin_init');
		if (current_user_can('manage_options')) {
			add_action('wp_dashboard_setup', array (&$this, 'on_dashboard_setup'));
		}
	}
	
	function on_admin_menu() {
		$this->pagehook = add_dashboard_page(__("WP System Health", "wp-system-health" ), __("WP System Health", "wp-system-health" ), 'manage_options', 'wp-system-health', array(&$this, 'on_show_page'));
		add_action('load-'.$this->pagehook, array(&$this, 'on_load_page'));		
	}
	
	function on_dashboard_setup() {
		if (!$this->options->disable_dashboard_widget) {
			wp_add_dashboard_widget( 'csp_boot_loader_dashboard', 'WordPress System Health', array (&$this, 'on_dashboard_widget') );
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_style('jquery-ui-tabs-wpsh-css', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/ui.all.css');
		}
	}
	
	function on_load_page() {
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_style('jquery-ui-tabs-wpsh-css', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/ui.all.css');
	}
	
	function _echo_progress_bar($perc, $value, $desc, $low, $high){
		$color = ($perc <= $low ? '#21759B' : ($perc > $high ? 'red' : '#E66F00'));
	?>
		<div class="progressbar">
			<div class="widget" style="height:12px; border:1px solid #DDDDDD; background-color:#F9F9F9;width:100%; margin: 3px 0;">
				<div class="widget" style="width: <?php echo min($perc, 100.0) ?>%;height:100%;background-color:<?php echo $color; ?>!important;border-width:0px;text-shadow:0 1px 0 #000000;color:#FFFFFF;text-align:right;font-weight:bold;font-size:8px;margin-bottom:4px;"><div style="padding:0 6px; white-space:nowrap;word-wrap:normal!important;overflow: hidden;"><?php echo number_format_i18n($perc, 2); ?>&nbsp;%</div></div>
			</div>
		</div>
		<div style="margin-bottom:8px;"><small><strong><?php echo  $value; ?></strong> | 
		<?php echo $desc; ?><small>
		</div>
	<?php
	}
	
	function _echo_checkpoint_row($checkpoint){
		$name = array_shift(array_keys($checkpoint));
		$mem = $checkpoint[$name]['mem'];
		$title = (isset($this->checkpoint_trans[$name]) ? $this->checkpoint_trans[$name]['title'] : __('unknown checkpoint','wp-system-health'));
		$desc = (isset($this->checkpoint_trans[$name]) ? $this->checkpoint_trans[$name]['desc'] : __('There are no specific descriptions available for this checkpoint.','wp-system-health'));
		$perc = round($mem  * 100.0 / $this->boot_loader->memory_limit, 2);
	?>
		<tr class="wpsh-sect-memory" style="display:none;">
			<td><?php echo $title; ?><br/><small><i>(<?php echo $name; ?>)</i><small></td>
			<td width="100%">
				<?php $this->_echo_progress_bar($perc, (empty($mem) ? '-n.a.-' : size_format($mem, 2)), $desc, 80, 95);	?>
			</td>
		</tr>
	<?php
	}
	
	function _get_mofile_entries($mofile) {
		if (is_readable($mofile)) {						
			$file = fopen( $mofile, 'rb' );
			if ( !$file )
				return 0;
				
			$header = fread( $file, 28 );
			if ( strlen( $header ) != 28 ) {
				fclose($file);
				return 0;
			}

			// detect endianess
			$endian = unpack( 'Nendian', substr( $header, 0, 4 ) );
			if ( $endian['endian'] == intval( hexdec( '950412de' ) ) )
				$endian = 'N';
			else if ( $endian['endian'] == intval( hexdec( 'de120495' ) ) )
				$endian = 'V';
			else {
				fclose($file);
				return 0;
			}
			// parse header
			$header = unpack( "{$endian}Hrevision/{$endian}Hcount/{$endian}HposOriginals/{$endian}HposTranslations/{$endian}HsizeHash/{$endian}HposHash", substr( $header, 4 ) );
			fclose($file);
			if ( is_array( $header ) )
				return $header['Hcount'];
		}
		return 0;
	}
	
	function on_dashboard_widget() {
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
						<td><a class="wpsh-toggle-section" id="wpsh-sect-0" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
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
							<td><a class="wpsh-toggle-section" id="wpsh-sect-15" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
						</tr>
						<tr><td><?php _e('Storage Space', 'wp-system-health'); ?></td><td><?php echo sprintf(__('<b>%s</b> of <b>%s</b> (%s %%) used.','wp-system-health'),size_format($quota['blocks']*$this->options->quota_block_size,2),size_format($quota['b_limit']*$this->options->quota_block_size,2), number_format_i18n($quota['b_perc'],2)); ?></td></tr>					
						<tr class="wpsh-sect-15" style="display:none"><td><?php _e('- Soft Limit', 'wp-system-health'); ?></td><td><b><?php echo size_format($quota['b_quota']*$this->options->quota_block_size,2); ?></b></td></tr>					
						<tr><td><?php _e('Number of Files', 'wp-system-health'); ?></td><td><?php echo sprintf(__('<b>%s</b> of <b>%s</b> files (%s %%) stored.','wp-system-health'),$quota['files'],$quota['f_limit'], number_format_i18n($quota['f_perc'],2)); ?></td></tr>					
						<tr class="wpsh-sect-15" style="display:none"><td><?php _e('- Soft Limit', 'wp-system-health'); ?></td><td><b><?php echo $quota['f_quota'].' '.__('files','wp-system-health'); ?></b></td></tr>					
					<?php else :?>
						<tr><td width="110px;"><b><?php _e('Server Quota\'s:', 'wp-system-health'); ?></b></td><td><?php _e('access not permitted or quota not configured.','wp-system-health'); ?></td></tr>
					<?php endif; ?>

										
					<tr><td width="110px;"><b><?php _e('Server Locale:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-16" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr class="wpsh-sect-16" style="display:none"><td><?php _e('collation', 'wp-system-health'); ?><br/><small><i>(LC_COLLATE)</i></small></td><td><b><?php echo setlocale(LC_COLLATE, 0); ?></b></td></tr>
					<tr class="wpsh-sect-16" style="display:none"><td><?php _e('uppercasing', 'wp-system-health'); ?><br/><small><i>(LC_CTYPE)</i></small></td><td><b><?php echo setlocale(LC_CTYPE, 0); ?></b></td></tr>
					<tr class="wpsh-sect-16" style="display:none"><td><?php _e('monetary', 'wp-system-health'); ?><br/><small><i>(LC_MONETARY)</i></small></td><td><b><?php echo setlocale(LC_MONETARY, 0); ?></b></td></tr>
					<tr class="wpsh-sect-16" style="display:none"><td><?php _e('numerical', 'wp-system-health'); ?><br/><small><i>(LC_NUMERIC)</i></small></td><td><b><?php echo setlocale(LC_NUMERIC, 0); ?></b></td></tr>
					<tr><td><?php _e('date/time', 'wp-system-health'); ?><br/><small><i>(LC_TIME)</i></small></td><td><b><?php echo setlocale(LC_TIME, 0); ?></b></td></tr>
					<tr class="wpsh-sect-16" style="display:none"><td><?php _e('messages', 'wp-system-health'); ?><br/><small><i>(LC_MESSAGES)</i></small></td><td><b><?php echo @setlocale(LC_MESSAGES, 0); ?></b></td></tr>
					
					<tr><td width="110px;"><b><?php _e('Load Average:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-17" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<?php $lavg = $this->boot_loader->get_loadavg(); ?>
					<tr><td><?php _e('last 1 minute', 'wp-system-health'); ?></td><td><b><?php echo (is_numeric($lavg[0]) ? number_format_i18n($lavg[0], 2) : $lavg[0]); ?></b></td></tr>
					<tr class="wpsh-sect-17" style="display:none"><td><?php _e('last 5 minutes', 'wp-system-health'); ?></td><td><b><?php echo (is_numeric($lavg[1]) ? number_format_i18n($lavg[1], 2) : $lavg[1]); ?></b></td></tr>
					<tr class="wpsh-sect-17" style="display:none"><td><?php _e('last 15 minutes', 'wp-system-health'); ?></td><td><b><?php echo (is_numeric($lavg[2]) ? number_format_i18n($lavg[2], 2) : $lavg[2]); ?></b></td></tr>
					
					
					<tr><td><b><?php _e('Checkpoints:', 'wp-system-health'); ?></b></td>
						<td>
							<a class="wpsh-toggle-section" id="wpsh-sect-memory" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a>
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
					<tr><td width="160px;"><?php _e('PHP Version', 'wp-system-health'); ?></td><td><b><?php echo PHP_VERSION; ?></b></td></tr>
					<tr><td><b><?php _e('Runtime Configuration:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-1" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr class="wpsh-sect-1" style="display:none"><td><?php _e('Include Path', 'wp-system-health'); ?><br/><small><i>(include_path)</i></small></td><td><b><?php echo ini_get('include_path'); ?></b><br/><small><?php _e('Specifies a list of directories where the require(), include(), fopen(), file(), readfile() and file_get_contents()  functions look for files.', 'wp-system-health'); ?></br></td></tr>										
					<tr class="wpsh-sect-1" style="display:none"><td><?php _e('Maximum Input Time', 'wp-system-health'); ?><br/><small><i>(max_input_time)</i></small></td><td><b><?php echo ini_get('max_input_time'); ?> <?php _e('seconds', 'wp-system-health'); ?></b><br/><small><?php _e('This sets the maximum time in seconds a script is allowed to parse input data, like POST, GET and file uploads.', 'wp-system-health'); ?></br></td></tr>					
					<tr><td><?php _e('Maximum Execution Time', 'wp-system-health'); ?><br/><small><i>(max_execution_time)</i></small></td><td><b><?php $et = ini_get('max_execution_time'); if ($et > 1000) $et /= 1000; echo $et; ?> <?php _e('seconds', 'wp-system-health'); ?></b><br/><small><?php _e('This sets the maximum time in seconds a script is allowed to run before it is terminated by the parser.', 'wp-system-health'); ?></br></td></tr>

					<tr><td width="150px;"><b><?php _e('File Upload Settings:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-2" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr class="wpsh-sect-2" style="display:none"><td><?php _e('HTTP File Upload', 'wp-system-health'); ?><br/><small><i>(file_uploads)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('file_uploads')); ?></b><br/><small><?php _e('Whether or not to allow HTTP file uploads.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-2" style="display:none"><td><?php _e('Temporary Directory', 'wp-system-health'); ?><br/><small><i>(upload_tmp_dir)</i></small></td><td><b><?php echo ini_get('upload_tmp_dir'); ?></b><br/><small><?php _e('The temporary directory used for storing files when doing file upload.', 'wp-system-health'); ?></br></td></tr>
					<tr><td><?php _e('Maximum File Size', 'wp-system-health'); ?><br/><small><i>(upload_max_filesize)</i></small></td><td><b>
						<?php echo size_format($this->boot_loader->convert_ini_bytes(ini_get('upload_max_filesize'))); ?></b><br/><small><?php _e('The maximum size of an uploaded file.', 'wp-system-health'); ?></small>
					</td></tr>
					
					<tr><td width="150px;"><b><?php _e('Data handling:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-3" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr class="wpsh-sect-3" style="display:none"><td><?php _e('Maximum Post Size', 'wp-system-health'); ?><br/><small><i>(post_max_size)</i></small></td><td><b><?php echo size_format($this->boot_loader->convert_ini_bytes(ini_get('post_max_size'))); ?></b><br/><small><?php _e('Sets max size of post data allowed. This setting also affects file upload. To upload large files, this value must be larger than upload_max_filesize.   If memory limit is enabled by your configure script, memory_limit also affects file uploading. Generally speaking, memory_limit should be larger than post_max_size.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-3" style="display:none"><td><?php _e('Multibyte Function Overload', 'wp-system-health'); ?><br/><small><i>(mbstring.func_overload)</i></small></td><td><b><?php echo ini_get('mbstring.func_overload'); ?></b><br/><small><?php _e('Overloads a set of single byte functions by the mbstring counterparts.', 'wp-system-health'); ?></br></td></tr>

					<tr><td width="150px;"><b><?php _e('Language Options:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-4" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr><td><?php _e('Short Open Tags', 'wp-system-health'); ?><br/><small><i>(short_open_tag)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('short_open_tag')); ?></b><br/><small><?php _e('Tells whether the short form (&lt;? ?&gt;) of PHP\'s open tag should be allowed.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-4" style="display:none"><td><?php _e('ASP Tags', 'wp-system-health'); ?><br/><small><i>(asp_tags)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('asp_tags')); ?></b><br/><small><?php _e('Enables the use of ASP-like &lt;% %&gt; tags in addition to the usual &lt;?php ?&gt; tags.', 'wp-system-health'); ?></br></td></tr>					
					<tr class="wpsh-sect-4" style="display:none"><td><?php _e('Zend Engine Compatibitlity', 'wp-system-health'); ?><br/><small><i>(zend.ze1_compatibility_mode)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('zend.ze1_compatibility_mode')); ?></b><br/><small><?php _e('Enable compatibility mode with Zend Engine 1 (PHP&nbsp;4).', 'wp-system-health'); ?></br></td></tr>					
										
					<tr><td width="150px;"><b><?php _e('Security and Safe Mode:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-5" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr><td><?php _e('Remote open Files', 'wp-system-health'); ?><br/><small><i>(allow_url_fopen)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('allow_url_fopen')); ?></b><br/><small><?php _e('This option enables the URL-aware fopen wrappers that enable accessing URL object like files. Should be disabled for security reasons, to prevent remote file inclusion attacks.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-5" style="display:none"><td><?php _e('Remote include Files', 'wp-system-health'); ?><br/><small><i>(allow_url_include)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('allow_url_include')); ?></b><br/><small><?php _e('This option allows the use of URL-aware fopen wrappers with the following functions: include(), include_once(), require(), require_once(). Should be disabled for security reasons, to prevent remote file inclusion attacks.', 'wp-system-health'); ?></br></td></tr>					
					<tr><td><?php _e('PHP Safe Mode', 'wp-system-health'); ?><br/><small><i>(safe_mode)</i></small></td><td><b><?php echo $this->boot_loader->convert_ini_bool(ini_get('safe_mode')); ?></b><br/><small><?php _e('Whether to enable PHP\'s safe mode.', 'wp-system-health'); ?></br></td></tr>
					<tr><td><?php _e('Open Basedir', 'wp-system-health'); ?><br/><small><i>(open_basedir)</i></small></td><td><b><?php echo ini_get('open_basedir'); ?></b><br/><small><?php _e('Limit the files that can be opened by PHP to the specified directory-tree, including the file itself. This directive is NOT affected by whether Safe Mode is turned On or Off.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-5" style="display:none"><td><?php _e('Disabled Function', 'wp-system-health'); ?><br/><small><i>(disable_functions)</i></small></td><td><b><?php echo ini_get('disable_functions'); ?></b><br/><small><?php _e('This directive allows you to disable certain functions for security reasons.', 'wp-system-health'); ?></br></td></tr>					
					<tr class="wpsh-sect-5" style="display:none"><td><?php _e('Disabled Classes', 'wp-system-health'); ?><br/><small><i>(disable_classes)</i></small></td><td><b><?php echo ini_get('disable_classes'); ?></b><br/><small><?php _e('This directive allows you to disable certain classes for security reasons.', 'wp-system-health'); ?></br></td></tr>
					
				</table>						
			</div>
			<div id="wpsh-wordpress" class="ui-tabs-hide">
				<table width="100%" class="fixed widefat" cellspacing="8px"  style="border-style: none;">		

					<tr><td width="160px;"><b><?php _e('Core Information:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-13" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
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
						<td><a class="wpsh-toggle-section" id="wpsh-sect-12" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
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
						<td><a class="wpsh-toggle-section" id="wpsh-sect-7" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr class="wpsh-sect-7" style="display:none"><td colspan="2"><?php _e('This section list most of WordPress constants can be configured at your <em>wp-config.php</em> file. Some of this constants have to be handled with care, please read additionally:', 'wp-system-health'); ?> <a target="_blank" href="http://codex.wordpress.org/Editing_wp-config.php">WordPress Codex Page</a></td></tr>
					<tr class="wpsh-sect-7" style="display:none"><td><?php _e('WordPress address', 'wp-system-health'); ?><br/><small><i>(WP_SITEURL)</i></small></td><td><b><?php echo (defined('WP_SITEURL') ? WP_SITEURL : ''); ?></b><br/><small><?php _e('Allows the WordPress address (URL) to be defined. The valued defined is the address where your WordPress core files reside.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Blog address', 'wp-system-health'); ?><br/><small><i>(WP_HOME)</i></small></td><td><b><?php echo (defined('WP_HOME') ? WP_HOME : ''); ?></b><br/><small><?php _e('Similar to WP_SITEURL, WP_HOME overrides the wp_options table value for home but does not change it permanently. home is the address you want people to type in their browser to reach your WordPress blog.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Language Dir', 'wp-system-health'); ?><br/><small><i>(WP_LANG_DIR)</i></small></td><td><b><?php echo (defined('WP_LANG_DIR') ? WP_LANG_DIR : ''); ?></b><br/><small><?php _e('Defines what directory the WPLANG .mo file resides. If WP_LANG_DIR is not defined WordPress looks first to wp-content/languages and then wp-includes/languages for the .mo defined by WPLANG file.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Content Dir', 'wp-system-health'); ?><br/><small><i>(WP_CONTENT_DIR)</i></small></td><td><b><?php echo (defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ''); ?></b><br/><small><?php _e('', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Plugin Dir', 'wp-system-health'); ?><br/><small><i>(WP_PLUGIN_DIR)</i></small></td><td><b><?php echo (defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : ''); ?></b><br/><small><?php _e('', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Plugin Dir (compatibility)', 'wp-system-health'); ?><br/><small><i>(PLUGINDIR)</i></small></td><td><b><?php echo (defined('PLUGINDIR') ? PLUGINDIR : ''); ?></b><br/><small><?php _e('', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Content URL', 'wp-system-health'); ?><br/><small><i>(WP_CONTENT_URL)</i></small></td><td><b><?php echo (defined('WP_CONTENT_URL') ? WP_CONTENT_URL : ''); ?></b><br/><small><?php _e('', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-7" style="display:none"><td><?php _e('Plugin URL', 'wp-system-health'); ?><br/><small><i>(WP_PLUGIN_URL)</i></small></td><td><b><?php echo (defined('WP_PLUGIN_URL') ? WP_PLUGIN_URL : ''); ?></b><br/><small><?php _e('', 'wp-system-health'); ?></br></td></tr>
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
					
					<tr><td width="160px;"><b><?php _e('HTTP Transport:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-6" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
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
						<td><a class="wpsh-toggle-section" id="wpsh-sect-14" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
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
						<td><a class="wpsh-toggle-section" id="wpsh-sect-11" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr><td><?php _e('MySQL Server', 'wp-system-health'); ?></td><td><b><?php echo $mysql_server_version; ?></b></td></tr>
					<tr class="wpsh-sect-11" style="display:none"><td><?php _e('Name', 'wp-system-health'); ?><br/><small><i>(DB_NAME)</i></small></td><td><b><?php echo DB_NAME; ?></b><br/><small><?php _e('Your database name currently configured.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-11" style="display:none"><td><?php _e('Host', 'wp-system-health'); ?><br/><small><i>(DB_HOST)</i></small></td><td><b><?php echo DB_HOST; ?></b><br/><small><?php _e('Your Database host currently configured.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-11" style="display:none"><td><?php _e('Charset', 'wp-system-health'); ?><br/><small><i>(DB_CHARSET)</i></small></td><td><b><?php echo DB_CHARSET; ?></b><br/><small><?php _e('Your Database character set currently configured.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-11" style="display:none"><td><?php _e('Collation', 'wp-system-health'); ?><br/><small><i>(DB_COLLATE)</i></small></td><td><b><?php echo DB_COLLATE; ?></b><br/><small><?php _e('Your Database collation currently configured.', 'wp-system-health'); ?></br></td></tr>
				
				<?php if (!$this->is_multisite()) : ?>
				
					<tr><td width="160px;"><b><?php _e('Table Statistics:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-8" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
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
						<td><a class="wpsh-toggle-section" id="wpsh-sect-9" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
					</tr>
					<tr class="wpsh-sect-9" style="display:none"><td><?php _e('WordPress Tables', 'wp-system-health'); ?><br/><small><i>(memory usage)</i></small></td><td><b><?php echo size_format($db_memory['wordpress'], 3) ?></b><br/><small><?php _e('Amount of memory your WordPress tables currently utilize.', 'wp-system-health'); ?></br></td></tr>
					<tr class="wpsh-sect-9" style="display:none"><td><?php _e('Custom/Plugin Tables', 'wp-system-health'); ?><br/><small><i>(memory usage)</i></small></td><td><b><?php echo size_format($db_memory['custom'], 3) ?></b><br/><small><?php _e('Amount of memory your Custom tables currently utilize.', 'wp-system-health'); ?></br></td></tr>
					<tr><td><?php _e('All Tables', 'wp-system-health'); ?><br/><small><i>(memory usage)</i></small></td><td><b><?php echo size_format($db_memory['wordpress'] + $db_memory['custom'], 3) ?></b><br/><small><?php _e('Amount of memory your database currently utilize in total.', 'wp-system-health'); ?></br></td></tr>
					
					<tr><td width="160px;"><b><i><?php _e('Posts', 'wp-system-health'); ?></i> <?php _e('Table Analysis:', 'wp-system-health'); ?></b></td>
						<td><a class="wpsh-toggle-section" id="wpsh-sect-10" href="#wpsh-checkpoint"><span><?php _e('show detailed &raquo;', 'wp-system-health'); ?></span><span style="display:none"><?php _e('&laquo; collapse', 'wp-system-health'); ?></span></a></td>
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
		<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			//jQuery UI 1.5.2 doesn't expect tab Id's at DIV, so we have to apply a hotfix instead
			var needs_jquery_hotfix = (($.ui.version === undefined) || !$.ui.version.match(/^(1\.[7-9]|[2-9]\.)/));
			$("#wpsh-tabs"+(needs_jquery_hotfix ? ">ul" : "")).tabs({
				selected: 0
			}); 
			//because of jQuery 1.3.2 IE8 bug: http://dev.jquery.com/ticket/4661  we can't use toggle at them moment
			//IE 8 doesn't evaluate is(':visible') and also is(':hidden'), so the toggle() function doesn't work !!! 
			$('.wpsh-toggle-section').click(function() {
				//additionally the jQuery 1.2.6 has an other bug related to table displayment also worked around !!!
				if (!needs_jquery_hotfix) {
					$(this).find('span').each(function(i, elem) {
						$(elem).toggle($(elem).css('display') == 'none');
					});
					$('.'+$(this).attr('id')).each(function(i, elem) {
						$(elem).toggle($(elem).css('display') == 'none');
					});
				}else {
					/* this works in all browser except IE 8 at jQuery 1.3.2 and seems to affect Safari 4 too. 
					$(this).find('span').toggle();
					$('.'+$(this).attr('id')).toggle();
					*/
					$(this).find('span').each(function(i, elem) {
						$(elem).css('display') == 'none' ? $(elem).css('display', 'table-row') : $(elem).hide();
					});
					$('.'+$(this).attr('id')).each(function(i, elem) {
						$(elem).css('display') == 'none' ? $(elem).css('display', 'table-row') : $(elem).hide();
					});
				}
				return false;
			});
			$('.wpsh-sect-memory:last').show().removeClass('wpsh-sect-memory');

			var max_mem_provider = Math.round(<?php echo $this->boot_loader->convert_ini_bytes(ini_get('memory_limit')); ?> / 1024 / 1024);
			var max_mem_loader = Math.round(<?php echo $this->boot_loader->memory_limit; ?> / 1024 / 1024);
			
			//memory testing
			function call_test_mem(mb) {
				$.post("<?php echo WPSH_ADMIN_URL; ?>/admin-ajax.php",  {action: 'wp_system_healts_check_memory', size : mb }, function(data) {
					var details = data.replace(/<br\s\/>/g, '').split('|');
					if (details.length >= 3){
						$('#wsph-check-memory-limit-results').prepend('<li class="mem-test-success"><?php _e('requested:','wp-system-health'); ?>&nbsp;'+mb+'&nbsp;MB</li>');
						if (mb < max_mem_provider) { 
							call_test_mem(mb+1);
						}else{
							$('#wpsh-mem-max').html(max_mem_provider+' MB');
							$('#wpsh-mem-max-perc').html('<?php _e('100% available, no problems at your provider.','wp-system-health');?>');
							$('#wsph-check-memory-limits').show().parent().next().hide();
						}
					}
					else{
						$('#wsph-check-memory-limit-results').prepend('<li class="mem-test-failed"><?php _e('requested:','wp-system-health'); ?>&nbsp;'+mb+'&nbsp;MB<div><small>'+details+'</small></div></li>');
						$('#wpsh-mem-max').html((mb-1)+' MB');
						if (mb < max_mem_loader && max_mem_loader == max_mem_provider) {
							$('#wpsh-mem-max-perc').html( '<b>'+Math.round((mb-1) * 100.0 / max_mem_provider)+'%</b>&nbsp;|&nbsp;<?php _e('Your Provider does not permit the full limit has been configured! Contact your Provider to solve this issue.','wp-system-health');?>');
						}else{
							$('#wpsh-mem-max-perc').html( '<b>'+Math.round((mb-1) * 100.0 / max_mem_provider)+'%</b>&nbsp;|&nbsp;<?php _e('Your Provider permits WordPress to increase the limit but you can not use it fully! Contact your Provider to solve this issue.','wp-system-health');?>');
						}
						$('#wsph-check-memory-limits').show().parent().next().hide();
					}
				});		
			}
			
			$('#wsph-check-memory-limits').click(function(event) {
				event.preventDefault();
				$(this).hide().parent().next().show();
				$('#wsph-check-memory-limit-results').html('');
				$('#wpsh-mem-max').html('<?php _e('-n.a.-', 'wp-system-health'); ?>');
				$('#wpsh-mem-max-perc').html('<?php _e('-n.a.-', 'wp-system-health'); ?>');
				call_test_mem(1);
			});
			
		});
		//]]>
		</script>
		<?php
	}
	
	function on_show_page() {
	?>
		<div id="wp-system-health-settings" class="wrap">
			<?php screen_icon('wp-system-health'); ?>
			<h2><?php _e('WordPress System Health', 'wp-system-health'); ?></h2>
			<p><?php _e('WordPress System Health provides a comprehensive overview about your current WordPress installation in terms of configuration and settings.','wp-system-health'); ?></p>
			<form action="admin-post.php" method="post">
				<?php wp_nonce_field('wp_system_health_save_settings'); ?>
				<input type="hidden" name="action" value="wp_system_health_save_settings" />
				<?php $this->on_dashboard_widget(); ?>
				<p>
					<input type="checkbox" id="disable_dashboard_widget" name="disable_dashboard_widget" value="1"<?php if ($this->options->disable_dashboard_widget) echo ' checked="checked"'; ?> />&nbsp;<label for="disable_dashboard_widget"><?php _e('disable dashboard widget', 'wp-system-health'); ?></label><br/>
					<input type="checkbox" id="disable_rightnow" name="disable_rightnow" value="1"<?php if ($this->options->disable_rightnow) echo ' checked="checked"'; ?> />&nbsp;<label for="disable_rightnow"><?php _e('disable server storage quota\'s at dashboard', 'wp-system-health'); ?></label><br/>
					<input type="checkbox" id="disable_admin_footer" name="disable_admin_footer" value="1"<?php if ($this->options->disable_admin_footer) echo ' checked="checked"'; ?> />&nbsp;<label for="disable_admin_footer"><?php _e('disable admin footer memory message', 'wp-system-health'); ?></label><br/>
					<input type="checkbox" id="enable_frontend_monitoring" name="enable_frontend_monitoring" value="1"<?php if ($this->options->enable_frontend_monitoring) echo ' checked="checked"'; ?> />&nbsp;<label for="enable_frontend_monitoring"><?php _e('enable public performance monitoring comments', 'wp-system-health'); ?></label><br/>
					<label for="quota_block_size"><?php _e('block size for disk quota\'s', 'wp-system-health'); ?></label> 
					<select size="1" id="quota_block_size" name="quota_block_size">
					<option value="1"<?php if($this->options->quota_block_size == 1) echo ' selected="selected"'; ?>>Windows</option>
					<option value="512"<?php if($this->options->quota_block_size == 512) echo ' selected="selected"'; ?>>512 Bytes</option>
					<option value="1024"<?php if($this->options->quota_block_size == 1024) echo ' selected="selected"'; ?>>1024 Bytes</option>
					<option value="2048"<?php if($this->options->quota_block_size == 2048) echo ' selected="selected"'; ?>>2048 Bytes</option>
					<option value="4096"<?php if($this->options->quota_block_size == 4096) echo ' selected="selected"'; ?>>4096 Bytes</option>
					</select><br/>
				</p>
				<p>
					<input type="submit" value="<?php _e('Save Changes', 'wp-system-health'); ?>" class="button-primary" name="Submit"/>	
				</p>				
			</form>
		</div>
	<?php
	}
	
	function on_rightnow_end() {
		if (!$this->options->disable_rightnow && is_super_admin()) {
			$quota = $this->boot_loader->get_quotas();  if(is_array($quota)) : ?>
			<p style="border-top:1px solid #ECECEC;margin-top:12px;position:static;" class="sub"><?php _e( 'Server Storage Quota\'s', 'wp-system-health' ); ?><small><?php echo ($this->boot_loader->is_windows ? ' (Windows)' : ' (Unix)'); ?></small></p>
			<p>
				<?php echo sprintf(__('<b>%s</b> of <b>%s</b> (%s %%) used.','wp-system-health'),size_format($quota['blocks']*$this->options->quota_block_size,2),size_format($quota['b_limit'] * $this->options->quota_block_size,2), ($quota['b_limit'] == 0 ? '0B' : number_format_i18n($quota['blocks'] * 100.0 / $quota['b_limit'],2))); ?>
			</p>
			<p>
				<?php echo sprintf(__('<b>%s</b> of <b>%s</b> files (%s %%) stored.','wp-system-health'),$quota['files'],$quota['f_limit'], number_format_i18n($quota['f_perc'], 2)); ?>
			</p>
			<br class="clear" />
			<?php endif;
		}
	}
	
	function on_footer_text($content) {
		if (!$this->options->disable_admin_footer) {
			$this->boot_loader->pass_checkpoint('admin:footer');
			$checkpoint = end($this->boot_loader->check_points);
			$name = array_shift(array_keys($checkpoint));
			$mem = $checkpoint[$name]['mem'];

			$content .= ' | '.sprintf(__('Memory: %s of %s ~ %s %%','wp-system-health'), size_format($mem, 2), size_format($this->boot_loader->memory_limit,2), round($mem  * 100.0 / $this->boot_loader->memory_limit, 2));
		}
		return $content;
	}
	
	function on_frontend_footer() {
		if (!$this->options->enable_frontend_monitoring) return;
		echo "<!-- WP System Health - Site Performance Overview -->\n";
		$this->boot_loader->pass_checkpoint('theme:footer');
		if (function_exists('memory_get_peak_usage')) {
			$this->boot_loader->pass_checkpoint('php:peak_usage', memory_get_peak_usage(true));
		}
		foreach($this->boot_loader->check_points as $checkpoint) {
			$name = array_shift(array_keys($checkpoint));
			$perc = round($checkpoint[$name]['mem']  * 100.0 / $this->boot_loader->memory_limit, 1);
			$mem = size_format($checkpoint[$name]['mem'],3);
			$name = str_pad($name,32-strlen($mem));
			$title = (isset($this->checkpoint_trans[$name]) ? $this->checkpoint_trans[$name]['title'] : __('unknown checkpoint','wp-system-health'));
			echo "<!--    $name$mem  ($perc%) -->\n"; 
		}
		$queries = get_num_queries();
		$timer = timer_stop();		
		$name_db = str_pad('database:queries', 29-strlen($queries));
		$name_timer = str_pad('content:timing', 29-strlen($timer));
		echo "<!--    $name_db$queries queries. -->\n";
		echo "<!--    $name_timer$timer seconds. -->\n";
	}
	
	function on_save_settings() {
		if (!is_user_logged_in() || !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );			
		//cross check the given referer
		check_admin_referer('wp_system_health_save_settings');
		//handle here the DB saving of configuration options,
		$this->options = $this->defaults;
		foreach(array_keys(get_object_vars($this->defaults)) as $key) {
			if (isset($_POST[$key])) 
				$this->options->$key = $_POST[$key];
		}		
		update_option('wp-system-health', $this->options);
		wp_redirect($_POST['_wp_http_referer']);						
	}
	
	function on_ajax_wp_system_healts_check_memory() {
		if (!is_user_logged_in() || !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );			
			
		define('MEGABYTE', 1024.0 * 1024.0);

		$mem_requested = (int)$_POST['size'] * MEGABYTE - 102400*6;
		$res = round(memory_get_usage()/MEGABYTE,2).'|';
		$alloc = (int)max($mem_requested - memory_get_usage(), 0);
		$bytes = str_pad('', $alloc);
		echo $res . (int)$_POST['size'] .'|'. round(memory_get_usage()/MEGABYTE,2);
		exit();			
	}
	
}



$wpsh_plugin = new wpsh_plugin();

?>