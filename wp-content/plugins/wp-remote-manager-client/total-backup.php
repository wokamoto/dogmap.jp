<?php
if (!class_exists('TotalBackup')) :

class TotalBackup {
	public  $plugin_name = 'Total Backup';
	public  $textdomain  = 'total-backup';

	private $plugin_basename, $plugin_dir, $plugin_file, $plugin_url;
	private $menu_base;
	private $option_name;
	private $admin_action;

	private $default_excluded = array(
	    'wp-content/cache/',
	    'wp-content/tmp/',
	    'wp-content/upgrade/',
	    'wp-content/uploads/',
		);

	const   ACCESS_LEVEL = 'manage_options';
	const   NONCE_NAME   = '_wpnonce_Total_Backup';
	const   TIME_LIMIT   = 900;			// 15min * 60sec

	function __construct() {
		global $wpdb;

		$this->set_plugin_dir(__FILE__);
		$this->option_name = $this->plugin_name . ' Option';
		$this->load_textdomain($this->plugin_dir, 'languages', $this->textdomain);

		// add rewrite rules
		if (!class_exists('WP_AddRewriteRules'))
		        require_once 'includes/class-addrewriterules.php';
		new WP_AddRewriteRules('json/([^/]+)/?', 'json=$matches[1]', array(&$this, 'json_request'));

		if (is_admin()) {
			// add admin menu
			$this->menu_base = basename($this->plugin_file, '.php');
			if (function_exists('is_multisite') && is_multisite()) {
				$this->admin_action = $this->wp_admin_url('network/admin.php?page=' . $this->menu_base);
				add_action('network_admin_menu', array(&$this, 'admin_menu'));
			} else {
				$this->admin_action = $this->wp_admin_url('admin.php?page=' . $this->menu_base);
				add_action('admin_menu', array(&$this, 'admin_menu'));
				add_filter('plugin_action_links', array(&$this, 'plugin_setting_links'), 10, 2 );
			}
			add_action('init', array(&$this, 'file_download'));
		}

		// activation & deactivation
		if (function_exists('register_activation_hook'))
			register_activation_hook(__FILE__, array(&$this, 'activation'));
		if (function_exists('register_deactivation_hook'))
			register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
	}

	//**************************************************************************************
	// Plugin activation
	//**************************************************************************************
	public function activation() {
		flush_rewrite_rules();
	}

	//**************************************************************************************
	// Plugin deactivation
	//**************************************************************************************
	public function deactivation() {
		flush_rewrite_rules();
	}

	//**************************************************************************************
	// Utility
	//**************************************************************************************

	private function chg_directory_separator( $content, $url = TRUE ) {
		if ( DIRECTORY_SEPARATOR !== '/' ) {
			if ( $url === FALSE ) {
				if (!is_array($content)) {
					$content = str_replace('/', DIRECTORY_SEPARATOR, $content);
				} else foreach( $content as $key => $val ) {
					$content[$key] = $this->chg_directory_separator($val, $url);
				}
			} else {
				if (!is_array($content)) {
					$content = str_replace(DIRECTORY_SEPARATOR, '/', $content);
				} else foreach( $content as $key => $val ) {
					$content[$key] = $this->chg_directory_separator($val, $url);
				}
			}
		}
		return $content;
	}

	private function trailingslashit( $content, $url = TRUE ) {
		return $this->chg_directory_separator(trailingslashit($content), $url);
	}

	private function untrailingslashit( $content, $url = TRUE ) {
		return $this->chg_directory_separator(untrailingslashit($content), $url);
	}

	// set plugin dir
	private function set_plugin_dir( $file = '' ) {
		$file_path = ( !empty($file) ? $file : __FILE__);
		$filename = explode("/", $file_path);
		if (count($filename) <= 1)
			$filename = explode("\\", $file_path);
		$this->plugin_basename = plugin_basename($file_path);
		$this->plugin_dir  = $filename[count($filename) - 2];
		$this->plugin_file = $filename[count($filename) - 1];
		$this->plugin_url  = $this->wp_plugin_url($this->plugin_dir);
		unset($filename);
	}

	// load textdomain
	private function load_textdomain( $plugin_dir, $sub_dir = 'languages', $textdomain_name = FALSE ) {
		$textdomain_name = $textdomain_name !== FALSE ? $textdomain_name : $plugin_dir;
		$plugins_dir = $this->trailingslashit( defined('PLUGINDIR') ? PLUGINDIR : 'wp-content/plugins', FALSE );
		$abs_plugin_dir = $this->wp_plugin_dir($plugin_dir);
		$sub_dir = (
			!empty($sub_dir)
			? preg_replace('/^\//', '', $sub_dir)
			: (file_exists($abs_plugin_dir.'languages') ? 'languages' : (file_exists($abs_plugin_dir.'language') ? 'language' : (file_exists($abs_plugin_dir.'lang') ? 'lang' : '')))
			);
		$textdomain_dir = $this->trailingslashit(trailingslashit($plugin_dir) . $sub_dir, FALSE);

		if ( $this->wp_version_check("2.6") && defined('WP_PLUGIN_DIR') )
			load_plugin_textdomain($textdomain_name, false, $textdomain_dir);
		else
			load_plugin_textdomain($textdomain_name, $plugins_dir . $textdomain_dir);

		return $textdomain_name;
	}

	// check wp version
	private function wp_version_check($version, $operator = ">=") {
		global $wp_version;
		return version_compare($wp_version, $version, $operator);
	}

	// WP_SITE_URL
	private function wp_site_url($path = '') {
		$siteurl = trailingslashit(function_exists('site_url') ? site_url() : get_bloginfo('wpurl'));
		return $siteurl . $path;
	}

	// admin url
	private function wp_admin_url($path = '') {
		$adminurl = '';
		if ( defined( 'WP_SITEURL' ) && '' != WP_SITEURL )
			$adminurl = WP_SITEURL . '/wp-admin/';
		elseif ( function_exists('site_url') && '' != site_url() )
			$adminurl = site_url('/wp-admin/');
		elseif ( function_exists( 'get_bloginfo' ) && '' != get_bloginfo( 'wpurl' ) )
			$adminurl = get_bloginfo( 'wpurl' ) . '/wp-admin/';
		elseif ( strpos( $_SERVER['PHP_SELF'], 'wp-admin' ) !== false )
			$adminurl = '';
		else
			$adminurl = 'wp-admin/';
		return trailingslashit($adminurl) . $path;
	}

	// WP_CONTENT_DIR
	private function wp_content_dir($path = '') {
		return $this->trailingslashit( trailingslashit( defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: trailingslashit(ABSPATH) . 'wp-content'
			) . preg_replace('/^\//', '', $path), FALSE );
	}

	// WP_CONTENT_URL
	private function wp_content_url($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_URL')
			? WP_CONTENT_URL
			: trailingslashit(get_option('siteurl')) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
	}

	// WP_PLUGIN_DIR
	private function wp_plugin_dir($path = '') {
		return $this->trailingslashit($this->wp_content_dir( 'plugins/' . preg_replace('/^\//', '', $path) ), FALSE);
	}

	// WP_PLUGIN_URL
	private function wp_plugin_url($path = '') {
		return trailingslashit($this->wp_content_url( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// Sanitize string or array of strings for database.
	private function escape(&$array) {
		global $wpdb;

		if (!is_array($array)) {
			return($wpdb->escape($array));
		} else {
			foreach ( (array) $array as $k => $v ) {
				if ( is_array($v) ) {
					$this->escape($array[$k]);
				} else if ( is_object($v) ) {
					//skip
				} else {
					$array[$k] = $wpdb->escape($v);
				}
			}
		}
	}

	// get current user ID & Name
	private function get_current_user() {
		static $username = NULL;
		static $userid   = NULL;

		if ( $username && $userid )
			return array($userid, $username);

		if ( is_user_logged_in() ) {
			global $current_user;
			get_currentuserinfo();
			$username = $current_user->display_name;
			$userid   = $current_user->ID;
		}
		return array($userid, $username);
	}

	// json decode
	private function json_decode( $string, $assoc = FALSE ) {
		if ( function_exists('json_decode') ) {
			return json_decode( $string, $assoc );
		} else {
			// For PHP < 5.2.0
			if ( !class_exists('Services_JSON') ) {
				require_once( 'includes/class-json.php' );
			}
			$json = new Services_JSON();
			return $json->decode( $string, $assoc );
		}
	}

	// json encode
	private function json_encode( $content ) {
		if ( function_exists('json_encode') ) {
			return json_encode($content);
		} else {
			// For PHP < 5.2.0
			if ( !class_exists('Services_JSON') ) {
				require_once( 'includes/class-json.php' );
			}
			$json = new Services_JSON();
			return $json->encode($content);
		}
	}

	// get date and gmt
	private function get_date_and_gmt($aa = NULL, $mm = NULL, $jj = NULL, $hh = NULL, $mn = NULL, $ss = NULL) {
		$tz = date_default_timezone_get();
		if ($tz !== 'UTC')
			date_default_timezone_set('UTC');
		$time = time() + (int)get_option('gmt_offset') * 3600;
		if ($tz !== 'UTC')
			date_default_timezone_set( $tz );

		$aa = (int)(!isset($aa) ? date('Y', $time) : $aa);
		$mm = (int)(!isset($mm) ? date('n', $time) : $mm);
		$jj = (int)(!isset($jj) ? date('j', $time) : $jj);
		$hh = (int)(!isset($hh) ? date('G', $time) : $hh);
		$mn = (int)(!isset($mn) ? date('i', $time) : $mn);
		$ss = (int)(!isset($ss) ? date('s', $time) : $ss);

		$aa = ($aa <= 0 ) ? date('Y', $time) : $aa;
		$mm = ($mm <= 0 ) ? date('n', $time) : $mm;
		$jj = ($jj > 31 ) ? 31 : $jj;
		$jj = ($jj <= 0 ) ? date('j', $time) : $jj;
		$hh = ($hh > 23 ) ? $hh -24 : $hh;
		$mn = ($mn > 59 ) ? $mn -60 : $mn;
		$ss = ($ss > 59 ) ? $ss -60 : $ss;
		$date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
		$date_gmt = get_gmt_from_date( $date );

		return array('date' => $date, 'date_gmt' => $date_gmt);
	}

	// sys get temp dir
	private function sys_get_temp_dir() {
		$temp_dir = NULL;
		if (function_exists('sys_get_temp_dir')) {
			$temp_dir = sys_get_temp_dir();
		} elseif (isset($_ENV['TMP']) && !empty($_ENV['TMP'])) {
			$temp_dir = realpath($_ENV['TMP']);
		} elseif (isset($_ENV['TMPDIR']) && !empty($_ENV['TMPDIR'])) {
			$temp_dir = realpath($_ENV['TMPDIR']);
		} elseif (isset($_ENV['TEMP']) && !empty($_ENV['TEMP']))  {
			$temp_dir = realpath($_ENV['TEMP']);
		} else {
			$temp_file = tempnam(__FILE__,'');
			if (file_exists($temp_file)) {
				unlink($temp_file);
				$temp_dir = realpath(dirname($temp_file));
			}
		}
		return $this->chg_directory_separator($temp_dir, FALSE);
	}

	// get nonces
	private function get_nonces($nonce_field = 'backup') {
		$nonces = array();
		if ($this->wp_version_check('2.5') && function_exists('wp_nonce_field') ) {
			$nonce = wp_nonce_field($nonce_field, self::NONCE_NAME, true, false);
			$pattern = '/<input [^>]*name=["]([^"]*)["][^>]*value=["]([^"]*)["][^>]*>/i';
			if (preg_match_all($pattern,$nonce,$matches,PREG_SET_ORDER)) {
			    foreach($matches as $match) {
					$nonces[$match[1]] = $match[2];
				}
			}
		}
		return $nonces;
	}

	// get permalink type
	private function get_permalink_type() {
		$permalink_structure = get_option('permalink_structure');
		$permalink_type = 'Ugly';
		if (empty($permalink_structure) || !$permalink_structure) {
			$permalink_type = 'Ugly';
		} else if (preg_match('/^\/index\.php/i', $permalink_structure)) {
			$permalink_type = 'Almost Pretty';
		} else {
			$permalink_type = 'Pretty';
		}
		return $permalink_type;
	}

	// get request var
	private function get_request_var($key, $defualt = NULL) {
		return isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $defualt);
	}

	// get archive path
	private function get_archive_path($option = '') {
		if (empty($option) || !is_array($option))
			$option = (array)get_option($this->option_name);
		$archive_path = 
			(isset($option["archive_path"]) && is_dir($option["archive_path"]))
			? $option["archive_path"]
			: $this->sys_get_temp_dir() ;
		if ( is_dir($archive_path) && is_writable($archive_path) )
			return $archive_path;
		else
			return FALSE;
	}

	// get archive prefix
	private function get_archive_prefix($option = '') {
		return basename(ABSPATH) . '.';
	}

	// get excluded dir
	private function get_excluded_dir($option = '', $special = FALSE) {
		if (empty($option) || !is_array($option))
			$option = (array)get_option($this->option_name);
		if (!class_exists('WP_Backuper'))
			require_once 'includes/class-wp-backuper.php';

		$excluded =	(
			$special === FALSE
			? array(
				'./' ,
				'../' ,
				WP_Backuper::MAINTENANCE_MODE ,
				)
			: (array) $special
			);
		$excluded = $this->chg_directory_separator(
			(isset($option["excluded"]) && is_array($option["excluded"]))
			? array_merge($excluded, $option["excluded"])
			: array_merge($excluded, $this->default_excluded) ,
			FALSE);
		return $excluded;
	}

	// remote backuper
	private function remote_backuper($option = NULL) {
		static $remote_backuper;
		if (isset($remote_backuper))
			return $remote_backuper;

		if (!class_exists('WP_Backuper'))
			require_once 'includes/class-wp-backuper.php';

		if (!$option)
			$option = (array)get_option($this->option_name);
		$remote_backuper = new WP_Backuper(
			$this->get_archive_path($option) ,
			$this->get_archive_prefix($option) ,
			$this->trailingslashit(ABSPATH, FALSE) ,
			$this->get_excluded_dir($option)
			);
		return $remote_backuper;
	}

	// get filemtime
	private function get_filemtime($file_name) {
		$filemtime = filemtime($file_name)  + (int)get_option('gmt_offset') * 3600;
		$date_gmt  = $this->get_date_and_gmt(
			(int)date('Y', $filemtime),
			(int)date('n', $filemtime),
			(int)date('j', $filemtime),
			(int)date('G', $filemtime),
			(int)date('i', $filemtime),
			(int)date('s', $filemtime)
			);
		$filemtime =
			isset($date_gmt['date'])
			? $date_gmt['date']
			: date("Y-m-d H:i:s.", $filemtime)
			;
		return $filemtime;
	}

	// get backup files
	private function get_backup_files() {
		$remote_backuper = $this->remote_backuper();
		return $remote_backuper->get_backup_files();
	}

	// backup files info
	private function backup_files_info($backup_files = NULL) {
		$nonces = '';
		foreach ($this->get_nonces('backup') as $key => $val) {
			$nonces .= '&' . $key . '=' . rawurlencode($val);
		}
		$remote_backuper = $this->remote_backuper();
		return $remote_backuper->backup_files_info($nonces, $this->menu_base);
	}

	//**************************************************************************************
	// json request
	//**************************************************************************************
	public function json_request() {
		if (!is_user_logged_in()) {
			header("HTTP/1.0 401 Unauthorized");
			wp_die(__('not logged in!', $this->textdomain));
		}

		if ( !ini_get('safe_mode') )
			set_time_limit(self::TIME_LIMIT);

		$method_name = get_query_var('json');
		if ($this->wp_version_check('2.5') && function_exists('check_admin_referer'))
			check_admin_referer($method_name, self::NONCE_NAME);

		list($userid, $username) = $this->get_current_user();
		$userid = (int)$userid;
		$charset = get_bloginfo('charset');
		$content_type = 'application/json';	// $content_type = 'text/plain';
		$result = FALSE;

		switch ($method_name) {
			case 'backup':
				$result = $this->json_backup($userid);
				break;
			default:
				$result = array(
					'result' => FALSE,
					'method' => $method_name,
					'message' => __('Method not found!', $this->textdomain),
					);
				break;
		}

		header("Content-Type: {$content_type}; charset={$charset}" );
		echo $this->json_encode(
			$result
			? array_merge(array('result' => TRUE, 'method' => $method_name), (array)$result)
			: array('result' => FALSE, 'method' => $method_name)
			);
		exit;
	}

	//**************************************************************************************
	// Site backup
	//**************************************************************************************
	private function json_backup($userid_org) {
		$userid = (int)($this->get_request_var('userid', -1));
		if ($userid !== $userid_org)
			return array('userid' => $userid, 'result' => FALSE, 'message' => 'UnKnown UserID!');

		$remote_backuper = $this->remote_backuper();
		$result = $remote_backuper->wp_backup();
		$backup_file = isset($result['backup']) ? $result['backup'] : FALSE;
		if ($backup_file && file_exists($backup_file)) {
			$filesize = (int)sprintf('%u', filesize($backup_file)) / 1024 / 1024;
			return array(
				'backup_file' => $backup_file,
				'backup_date' => $this->get_filemtime($backup_file),
				'backup_size' => number_format($filesize, 2) . ' MB',
				);
		} else {
			return $result;
		}
	}

	//**************************************************************************************
	// Add setting link
	//**************************************************************************************
	public function plugin_setting_links($links, $file) {
		global $wp_version;

		$this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . $this->admin_action . '-options">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}

		return $links;
	}

	//**************************************************************************************
	// Add Admin Menu
	//**************************************************************************************
	public function add_admin_scripts() {
		wp_enqueue_script('jquery');
	}

	public function add_admin_head() {
?>
<style type="text/css" media="all">/* <![CDATA[ */
#backuplist td {
	line-height: 24px;
}
/* ]]> */</style>
<script type="text/javascript">//<![CDATA[
//]]></script>
<?php
	}

	public function add_admin_head_main() {
		list($userid, $username) = $this->get_current_user();
		$userid = (int)$userid;

		$site_url = trailingslashit(function_exists('home_url') ? home_url() : get_option('home'));
		$json_backup_url  = $site_url;
		$json_backup_args = "userid:{$userid},\n";
		$json_method_type = 'POST';
		switch ($this->get_permalink_type()) {
		case 'Pretty':
			$json_backup_url .= 'json/backup/';
			$json_method_type = 'POST';
			break;
		case 'Almost Pretty':
			$json_backup_url .= 'index.php/json/backup/';
			$json_method_type = 'POST';
			break;
		case 'Ugly':
		default:
			$json_backup_args .= "json:'backup',\n";
			$json_method_type = 'GET';
			break;
		}

		$img = '<img src="%1$s" class="%2$s" style="display:inline-block;position:relative;left:.25em;top:.25em;width:16p;height:16px;" />';
		$loading_img = sprintf($img, $this->wp_admin_url('images/wpspin_light.gif'), 'updating');
		$success_img = sprintf($img, $this->plugin_url . 'images/success.png', 'success');
		$failure_img = sprintf($img, $this->plugin_url . 'images/failure.png', 'failure');
		$nonces_1 = $nonces_2 = '';
		foreach ($this->get_nonces('backup') as $key => $val) {
			$nonces_1 .= "'{$key}':'{$val}',\n";
			$nonces_2 .= '&' . $key . '=' . rawurlencode($val);
		}
?>
<script type="text/javascript">//<![CDATA[
jQuery(function($){
	function buttons_disabled(disabled) {
		$('input[name="backup_site"]').attr('disabled', disabled);
	}

	function basename(path, suffix) {
		// Returns the filename component of the path
		//
		// version: 910.820
		// discuss at: http://phpjs.org/functions/basename	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +   improved by: Ash Searle (http://hexmen.com/blog/)
		// +   improved by: Lincoln Ramsay
		// +   improved by: djmix
		// *	 example 1: basename('/www/site/home.htm', '.htm');	// *	 returns 1: 'home'
		// *	 example 2: basename('ecra.php?p=1');
		// *	 returns 2: 'ecra.php?p=1'
		var b = path.replace(/^.*[\/\\]/g, '');
		if (typeof(suffix) == 'string' && b.substr(b.length-suffix.length) == suffix) {
			b = b.substr(0, b.length-suffix.length);
		}
		return b;
	}

	$('input[name="backup_site"]').unbind('click').click(function(){
		var args = {
<?php echo $json_backup_args; ?>
<?php echo $nonces_1; ?>
			};
		var wrap = $(this).parent();
		$('img.success', wrap).remove();
		$('img.failure', wrap).remove();
		$('div#message').remove();
		wrap.append('<?php echo $loading_img; ?>');
		buttons_disabled(true);

		$.ajax({
			async: true,
			cache: false,
			data: args,
			dataType: 'json',
			success: function(json, status, xhr){
				$('img.updating', wrap).remove();
				if ( xhr.status == 200 && json.result ) {
					var backup_file = '<a href="?page=<?php echo $this->menu_base; ?>&download=' + encodeURIComponent(json.backup_file) + '<?php echo $nonces_2; ?>' + '" title="' + basename(json.backup_file) + '">' + basename(json.backup_file) + '</a>';
					var tr = $('<tr><td>' + backup_file + '</td>' +
						'<td>' + json.backup_date  + '</td>' +
						'<td>' + json.backup_size  + '</td>' +
						'<td></td></tr>');
					wrap.append('<?php echo $success_img; ?>');
					$('#backuplist').prepend(tr);
				} else {
					wrap.append('<?php echo $failure_img; ?>');
				}
				buttons_disabled(false);
			},
			error: function(req, status, err){
				$('img.updating', wrap).remove();
				wrap.append('<?php echo $failure_img; ?>');
				buttons_disabled(false);
			},
			type: '<?php echo $json_method_type; ?>',
			url: '<?php echo $json_backup_url; ?>'
		});

		return false;
	});
});
//]]></script>
<?php
	}

	public function add_admin_head_option() {
	}

	public function icon_style() {
?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->plugin_url; ?>css/config.css" />
<?php
	}

	public function admin_menu() {
		$hook = add_menu_page(
			__('Total Backup', $this->textdomain) ,
			__('Total Backup', $this->textdomain) ,
			self::ACCESS_LEVEL,
			$this->menu_base ,
			array($this, 'site_backup') ,
			$this->plugin_url . 'images/backup16.png'
			);
		add_action('admin_print_scripts-'.$hook, array($this,'add_admin_scripts'));
		add_action('admin_head-'.$hook, array($this,'add_admin_head'));
		add_action('admin_head-'.$hook, array($this,'add_admin_head_main'));
		add_action('admin_print_styles-' . $hook, array($this, 'icon_style'));

		$hook = add_submenu_page(
			$this->menu_base ,
			__('Option &gt; Total Backup', $this->textdomain) ,
			__('Option', $this->textdomain) ,
			self::ACCESS_LEVEL,
			$this->menu_base . '-options' ,
			array($this, 'option_page')
			);
		add_action('admin_print_scripts-'.$hook, array($this,'add_admin_scripts'));
		add_action('admin_head-'.$hook, array($this,'add_admin_head'));
		add_action('admin_head-'.$hook, array($this,'add_admin_head_option'));
		add_action('admin_print_styles-' . $hook, array($this, 'icon_style'));
	}

	//**************************************************************************************
	// sites backup
	//**************************************************************************************
	public function site_backup() {
		$out   = '';
		$note  = '';
		$error = 0;
		$nonce_field = 'backup';

		if (isset($_POST['remove_backup'])) {
			if ($this->wp_version_check('2.5') && function_exists('check_admin_referer'))
				check_admin_referer($nonce_field, self::NONCE_NAME);
			if (isset($_POST['remove'])) {
				$count = 0;
				foreach((array)$_POST['remove'] as $file) {
					if (($file = realpath($file)) !== FALSE) {
						if (@unlink($file))
							$count ++;
					}
				}
				if ($count > 0) {
					$note .= "<strong>".__('Delete Backup Files!', $this->textdomain)."</strong>";
				}
			}
		}

		$nonces =
			( $this->wp_version_check('2.5') && function_exists('wp_nonce_field') )
			? wp_nonce_field($nonce_field, self::NONCE_NAME, true, false)
			: '';

		$out .= '<div class="wrap">'."\n";

		$out .= '<div id="icon-options-total-backup" class="icon32"><br /></div>';
		$out .= '<h2>';
		$out .= __('Total Backup', $this->textdomain);
		$out .= '</h2>'."\n";

		$out .= '<h3>';
		$out .= __('Site Backup', $this->textdomain);
		$out .= '</h3>'."\n";

		$out .= '<form method="post" id="backup_site" action="'.$this->admin_action.'">'."\n";
		$out .= $nonces;
		$out .= '<input type="hidden" name="backup_site" class="button-primary sites_backup" value="'.__('Site Backup', $this->textdomain).'" class="button" style="margin-left:1em;" />';
		$out .= '<p style="margin-top:1em">';
		$out .= '<input type="submit" name="backup_site" class="button-primary sites_backup" value="'.__('Site Backup', $this->textdomain).'" class="button" style="margin-left:1em;" />';
		$out .= '</p>';
		$out .= '</form>'."\n";


		$out .= '<h3>';
		$out .= __('Backup Files.', $this->textdomain);
		$out .= '</h3>'."\n";

		$out .= '<form method="post" action="'.$this->admin_action.'">'."\n";
		$out .= $nonces;

		$out .= '<table id="backuplist" class="wp-list-table widefat fixed" style="margin-top:0;">'."\n";

		$out .= '<thead><tr>';
		$out .= '<th>' . __('Backup file name', $this->textdomain) . '</th>';
		$out .= '<th>' . __('Datetime', $this->textdomain) . '</th>';
		$out .= '<th>' . __('Size', $this->textdomain) . '</th>';
		$out .= '<th style="width:50px">' . __('Delete', $this->textdomain) . '</th>';
		$out .= '</tr></thead>' . "\n";

		$out .= '<tfoot><tr>';
		$out .= '<th colspan="3">';
		$out .= '</th>';
		$out .= '<th style="width:50px"><input type="submit" name="remove_backup" class="button-primary" value="'.__('Delete', $this->textdomain).'" class="button" /></th>';
		$out .= '</tr></tfoot>' . "\n";

		$out .= '<tbody>';

		$backup_files = $this->backup_files_info($this->get_backup_files());
		$alternate = ' class="alternate"';
		if (count($backup_files) > 0) {
			$i = 0;
			foreach ($backup_files as $backup_file) {
				$out .= "<tr{$alternate}>";
				$out .= sprintf('<td>%s</td>', $backup_file['url']);
				$out .= sprintf('<td>%s</td>', $backup_file['filemtime']);
				$out .= sprintf('<td>%s MB</td>', number_format($backup_file['filesize'], 2));
				$out .= "<td><input type=\"checkbox\" name=\"remove[{$i}]\" value=\"{$backup_file['filename']}\" /></td>";
				$out .= '</tr>' . "\n";
				$i++;
				$alternate = empty($alternate) ? ' class="alternate"' : '';
			}
		}

		$out .= '</tbody>' . "\n";
		$out .= '</table>' . "\n";
		$out .= '</form>'."\n";

		$out .= '</div>'."\n";

		// Output
		echo ( !empty($note) ? '<div id="message" class="updated fade"><p>'.$note.'</p></div>'  : '' );
		echo "\n";

		echo ( $error <= 0 ? $out : '' );
		echo "\n";
	}

	//**************************************************************************************
	// Option Page
	//**************************************************************************************
	public function option_page() {
		$out   = '';
		$note  = '';
		$error = 0;
		$nonce_field = 'option_update';

		$option = (array)get_option($this->option_name);
		$archive_path = $this->get_archive_path($option);
		$excluded_dir = $this->get_excluded_dir($option, array());

		// option update
		if (isset($_POST['options_update'])) {
			if ( $this->wp_version_check('2.5') && function_exists('check_admin_referer') )
				check_admin_referer($nonce_field, self::NONCE_NAME);

			if ( isset($_POST['archive_path']) ) {
				if ( ($archive_path = realpath($_POST['archive_path'])) !== FALSE ) {
					if ( is_dir($archive_path) && is_writable($archive_path) ) {
						$options['archive_path'] = $archive_path;
					} else {
						$note .= "<strong>".__('Failure!: Archive path is not writable.', $this->textdomain)."</strong>";
						$error++;
					}
				} else {
					$note .= "<strong>".__('Failure!: Archive path is not found.', $this->textdomain)."</strong>";
					$error++;
				}
			}

			if ( isset($_POST['excluded']) ) {
				$excluded = $excluded_dir = array();
				$abspath  = $this->chg_directory_separator(ABSPATH, FALSE);
				foreach ( explode("\n", $_POST['excluded']) as $dir ) {
					$dir = trim($dir);
					if ( !empty($dir) ) {
						if ( ($realpath = realpath($dir)) !== FALSE) {
							$realpath = $this->chg_directory_separator($realpath, FALSE);
							$dir = str_replace($abspath, '', $realpath);
							if ( is_dir($realpath) )
								$dir = $this->trailingslashit($dir, FALSE);
							$excluded[] = $dir;
							$excluded_dir[] = str_replace($abspath, '', $dir);
						} else {
							$note .= !empty($note) ? "<br />\n" : '';
							$note .= "<strong>". sprintf(__('Failure!: Excluded dir("%s") is not found.', $this->textdomain), $dir)."</strong>";
							$error++;
						}
					}
				}
				$options['excluded'] = $excluded;
			}

			update_option($this->option_name, $options);

			// Done!
			if ( $error <= 0 )
				$note .= "<strong>".__('Done!', $this->textdomain)."</strong>";
		}

		$out .= '<div class="wrap">'."\n";

		$out .= '<div id="icon-options-general" class="icon32"><br /></div>';
		$out .= '<h2>';
		$out .= __('Option &gt; Total Backup', $this->textdomain);
		$out .= '</h2>'."\n";

		$out .= '<h3>';
		$out .= __('Options update.', $this->textdomain);
		$out .= '</h3>'."\n";

		$out .= '<form method="post" id="option_update" action="'.$this->admin_action.'-options">'."\n";
		if ($this->wp_version_check('2.5') && function_exists('wp_nonce_field') )
			$out .= wp_nonce_field($nonce_field, self::NONCE_NAME, true, false);

		$out .= "<table class=\"optiontable form-table\" style=\"margin-top:0;\"><tbody>\n";

		$out .= '<tr>';
		$out .= '<th>'.__('Archive path', $this->textdomain).'</th>';
		$out .= '<td><input type="text" name="archive_path" id="archive_path" size="100" value="'.htmlspecialchars($archive_path).'" /></td>';
		$out .= '</tr>'."\n";

		$out .= '<tr>';
		$out .= '<th>'.__('Excluded dir', $this->textdomain).'</th>';
		$out .= '<td><textarea name="excluded" id="excluded" rows="5" cols="100">';
		$abspath  = $this->chg_directory_separator(ABSPATH, FALSE);
		foreach ($excluded_dir as $dir) {
			$out .= htmlspecialchars($this->chg_directory_separator($abspath.$dir,FALSE)) . "\n";
		}
		$out .= '</textarea></td>';
		$out .= '</tr>'."\n";

		$out .= '</tbody></table>' . "\n";

		$out .= '<p style="margin-top:1em;">';
		$out .= '<input type="submit" name="options_update" class="button-primary" value="'.__('Update Options', $this->textdomain).'" class="button" />';
		$out .= '</p>';

		$out .= '</form>'."\n";

		$out .= '</div>'."\n";

		// Output
		echo ( !empty($note) ? '<div id="message" class="updated fade"><p>'.$note.'</p></div>'  : '' );
		echo "\n";

		echo $out;
		echo "\n";
	}

	//**************************************************************************************
	// file download
	//**************************************************************************************
	public function file_download() {
		if ( !is_admin() || !is_user_logged_in() )
			return;

		if ( isset($_GET['page']) && isset($_GET['download']) ) {
			if ( $_GET['page'] !== $this->menu_base )
				return;

			if ($this->wp_version_check('2.5') && function_exists('check_admin_referer'))
				check_admin_referer('backup', self::NONCE_NAME);

			if (($file = realpath($_GET['download'])) !== FALSE) {
				header("Content-Type: application/x-compress;");
				header("Content-Disposition: attachement; filename=".basename($file));
				readfile($file);
			} else {
				header("HTTP/1.1 404 Not Found");
				wp_die(__('File not Found', $this->textdomain));
			}
			exit;
		}
	}
}

global $total_backup;
$total_backup = new TotalBackup();

endif;
