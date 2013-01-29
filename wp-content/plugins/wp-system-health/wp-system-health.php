<?php

/*
Plugin Name: WP System Health
Version: 1.4.0
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
		global $wp_version;
		if (version_compare($wp_version, "2.8", '<')) {
			wp_deregister_script('jquery');
			wp_deregister_script('jquery-ui-core');
			wp_deregister_script('jquery-ui-tabs');
			wp_deregister_script('jquery-ui-sortable');
			wp_deregister_script('jquery-ui-draggable');
			wp_deregister_script('jquery-ui-resizable');
			wp_deregister_script('jquery-ui-dialog');
			wp_register_script('jquery', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wordpress-2.7/jquery.js', false, '1.3.2'); 
			wp_register_script('jquery-ui-core', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wordpress-2.7/ui.core.js', array('jquery'), '1.7.1'); 
			wp_register_script('jquery-ui-tabs', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wordpress-2.7/ui.tabs.js', array('jquery-ui-core'), '1.7.1'); 
			wp_register_script('jquery-ui-sortable', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wordpress-2.7/ui.sortable.js', array('jquery-ui-core'), '1.7.1'); 
			wp_register_script('jquery-ui-draggable', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wordpress-2.7/ui.draggable.js', array('jquery-ui-core'), '1.7.1'); 
			wp_register_script('jquery-ui-resizable', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wordpress-2.7/ui.resizable.js', array('jquery-ui-core'), '1.7.1'); 
			wp_register_script('jquery-ui-dialog', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wordpress-2.7/ui.dialog.js', array('jquery-ui-resizable', 'jquery-ui-draggable'), '1.7.1'); 
		}
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
			wp_enqueue_script('wp-system-health', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wp-system-health.js', array('jquery','jquery-ui-tabs'));
			wp_localize_script('wp-system-health', 'wpsh_values', 
				array( 
					'ajax' => admin_url('admin-ajax.php'),
					'max_mem_provider' => $this->boot_loader->convert_ini_bytes(ini_get('memory_limit')) / 1024 / 1024,
					'max_mem_loader' => $this->boot_loader->memory_limit  / 1024 / 1024,
					'label_requested' => __('requested:','wp-system-health'),
					'label_fullsize' => __('100% available, no problems at your provider.','wp-system-health'),
					'label_failed' => __('Your Provider does not permit the full limit has been configured! Contact your Provider to solve this issue.','wp-system-health'),
					'label_halfsize' => __('Your Provider permits WordPress to increase the limit but you can not use it fully! Contact your Provider to solve this issue.','wp-system-health')
				) 
			);
			wp_enqueue_style('jquery-ui-tabs-wpsh-css', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/ui.all.css');
		}
		if (is_super_admin()) {
			include dirname(__FILE__).'/cpu-usage.php';
			wp_add_dashboard_widget( 'csp_system_loadavg_chart', __('Server Performance','wp-system-health'), array (&$this, 'on_system_loadavg_chart') );
			wp_enqueue_script('wpsp-highcharts', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/highcharts-2.3.5/highcharts.js', array('jquery'));
			wp_enqueue_script('wpsp-highcharts-more', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/highcharts-2.3.5/highcharts-more.js', array('jquery'));
			wp_enqueue_script('wpsp-highcharts-render', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/highcharts-render.js', array('wpsp-highcharts'));
			wp_localize_script('wpsp-highcharts-render', 'wpsh_highcharts', 
				array( 
					'cpu_usage' => WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/cpu-usage.php',//plugins_url('cpu-usage.php', __FILE__),
					'label_cpu_history' => __('CPU Usage History','wp-system-health'),
					'label_cpu_percent' => __('% processor load','wp-system-health')
				)
			);
		}
	}
	
	function on_load_page() {
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('wp-system-health', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/js/wp-system-health.js', array('jquery','jquery-ui-tabs'));
		wp_enqueue_style('jquery-ui-tabs-wpsh-css', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__file__)).'/ui.all.css');
	}
	
	function _echo_progress_bar($perc, $value, $desc, $low, $high){
		$color = ($perc <= $low ? '#21759B' : ($perc > $high ? 'red' : '#E66F00'));
	?>
		<div class="progressbar">
			<div class="widget" style="height:12px; border:1px solid #DDDDDD; background-color:#F9F9F9;width:100%; margin: 3px 0;">
				<div style="width: <?php echo min($perc, 100.0) ?>%;height:100%;background-color:<?php echo $color; ?>!important;border-width:0px;text-shadow:0 1px 0 #000000;color:#FFFFFF;text-align:right;font-weight:bold;font-size:8px;margin-bottom:4px;line-height:11px;"><div style="padding:0 6px; white-space:nowrap;word-wrap:normal!important;overflow: hidden;"><?php echo number_format_i18n($perc, 2); ?>&nbsp;%</div></div>
			</div>
		</div>
		<div style="margin-bottom:8px;"><small><strong><?php echo  $value; ?></strong> | 
		<?php echo $desc; ?></small>
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
			<td><?php echo $title; ?><br/><small><i>(<?php echo $name; ?>)</i></small></td>
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
	
	function on_system_loadavg_chart() {
		?><div id="wpsh-highcharts" style="min-width: 200px; height: 200px; margin: 0 auto"></div><?php
	}
	
	function on_dashboard_widget() {
		include dirname(__FILE__).'/overview.php';
	}
	
	function on_show_page() {
	?>
	<?php global $wp_version; if (version_compare($wp_version, "3.5", '<')) : ?>
		<div id="update-nag"><strong><?php _e('Attention:','wp-system-health');?></strong> <?php _e('You are using an outdated WordPress version! Next upcomming updates of "WP System Health" plugin will require WordPress 3.5, please be prepared.', 'wp-system-health'); ?></div>
	<?php endif; ?>
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