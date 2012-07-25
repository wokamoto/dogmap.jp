<?php
class WP_OAuthProvider {
	public  $plugin_name = 'OAuth Provider';
	public  $textdomain  = 'oauth-provider';

	private $plugin_basename, $plugin_dir, $plugin_file, $plugin_url;
	private $menu_base;
	private $option_name;
	private $admin_action;
	private $methods = array();

	private $server;
	private $datastore;

	public  $response_data_type   = 'json';
	public  $consumer_pin_enabled = FALSE;
	private $defalt_apl = array(
		'consumer_name' => 'OAuth Remote Access' ,
		'description'   => 'OAuth remote access application.' ,
		);

	function __construct($file = __FILE__, $response_data_type = 'json', $consumer_pin_enabled = FALSE, $def_consumer_name = FALSE, $def_consumer_description = false) {
		global $wpdb;

		$this->set_plugin_dir($file);
		$this->option_name = $this->plugin_name . ' Option';
		$this->load_textdomain($this->plugin_dir, 'languages', $this->textdomain);
		$this->methods     = array(
			'request_token' => NULL ,
			'authorize'     => NULL ,
			'access_token'  => NULL ,
			);
		$this->response_data_type   = $response_data_type;
		$this->consumer_pin_enabled = $consumer_pin_enabled;
		$this->menu_base = basename($this->plugin_file, '.php');
		$this->admin_action = $this->wp_admin_url('admin.php?page=' . $this->menu_base, $this->is_multisite());

		// add rewrite rules
		if (!class_exists('WP_AddRewriteRules'))
		        require_once 'class-addrewriterules.php';
		new WP_AddRewriteRules('oauth/([^/]+)/?', 'oauth=$matches[1]', array(&$this, 'oauth_request'));

		// create OAuth datastore & server
		if (!class_exists('OP_OAuthException'))
		        require_once 'class-oauth.php';
		if (!class_exists('WP_OAuthDataStore'))
		        require_once 'class-wp-oauth-datastore.php';
		$this->datastore = new WP_OAuthDataStore();
		$this->server    = new WP_OAuthServer( $this->datastore );
		$hmac_method     = new OP_OAuthSignatureMethod_HMAC_SHA1();
		$this->server->add_signature_method( $hmac_method );

		// set default consumer name
		$this->defalt_apl['consumer_name'] = $def_consumer_name ? $def_consumer_name : __('OAuth Remote Access', $this->textdomain);
		$this->defalt_apl['description']   = $def_consumer_description ? $def_consumer_description : __('OAuth remote access application.', $this->textdomain);

		if (is_admin()) {
			// add admin menu
			if ($this->is_multisite()) {
				add_action('network_admin_menu', array(&$this, 'admin_menu'));
			} else {
				add_action('admin_menu', array(&$this, 'admin_menu'));
				add_filter('plugin_action_links', array(&$this, 'plugin_setting_links'), 10, 2 );
			}

			if (!$this->check_tables())
				$this->activation();

			// activation & deactivation
			if (function_exists('register_activation_hook'))
				register_activation_hook(__FILE__, array(&$this, 'activation'));
			if (function_exists('register_deactivation_hook'))
				register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
		}
		add_action('init', array(&$this, 'init'), 9);

//		if (function_exists('wp_next_scheduled') && function_exists('wp_schedule_event'))
//			if ( !wp_next_scheduled(array(&$this, 'daily_task')) )
//				wp_schedule_event(time(), 'daily', array(&$this, 'daily_task'));
	}

	//**************************************************************************************
	// initialize
	//**************************************************************************************
	public function init() {
		if ( $this->is_plugin_active( 'twitconnect/twitconnect.php' ) && isset($_GET['oauth']) ) {
			remove_action( 'init', 'twc_init' );
		}
	}

	//**************************************************************************************
	// Plugin activation
	//**************************************************************************************
	public function activation() {
		if (!$this->check_tables())
			$this->datastore->create_tables();

		if (count($this->consumers()) <= 0) {
			$this->datastore->new_consumer_key(
				$this->defalt_apl['consumer_name'],
				$this->defalt_apl['description'] );
		}
	}

	//**************************************************************************************
	// Plugin deactivation
	//**************************************************************************************
	public function deactivation() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
		$this->datastore->delete_nonce();
		$this->datastore->delete_request_token(-1);
	}

	//**************************************************************************************
	// Daily task
	//**************************************************************************************
	public function daily_task() {
		$this->datastore->delete_nonce( $this->server->get_expired() );
		$this->datastore->delete_request_token( -1 );
	}

	//**************************************************************************************
	// Utility
	//**************************************************************************************

	// is plugin active
	private function is_plugin_active( $plugin, $network = TRUE ) {
		$is_active = FALSE;
		$plugins = (array) (
			$network && $this->is_multisite()
			? array_keys((array)get_site_option('active_sitewide_plugins', array()))
			: get_option('active_plugins', array())
			);
		foreach ( $plugins as $val ) {
			if (preg_match('/'.preg_quote($plugin, '/').'/i', $val)) {
				$is_active = TRUE;
				break;
			}
		}
		return $is_active;
	}

	// is multisite
	private function is_multisite() {
		return function_exists('is_multisite') && is_multisite();
	}

	// check tables
	private function check_tables() {
		$all_tables = (array)$this->get_all_tables();
		$api_tables = (array)$this->datastore->get_api_tables();
		$count = 0;
		foreach ($api_tables as $api_table) {
			if (array_search($api_table, $all_tables))
				$count++;
		}
		return
			$count < count($api_tables)
			? FALSE
			: TRUE;
	}

	// get all tables
	private function get_all_tables() {
		global $wpdb;
		$result = $wpdb->get_col('SHOW TABLES');
		$pattern = '/^'. preg_quote($wpdb->prefix, '/') . '/i';
		$tables = array();
		foreach ( $result as $table ) {
			if ( preg_match( $pattern, $table ) ) {
				$tables[] = $table;
			}
		}
		return $tables;
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
		$plugins_dir = trailingslashit( defined('PLUGINDIR') ? PLUGINDIR : 'wp-content/plugins' );
		$abs_plugin_dir = $this->wp_plugin_dir($plugin_dir);
		$sub_dir = (
			!empty($sub_dir)
			? preg_replace('/^\//', '', $sub_dir)
			: (file_exists($abs_plugin_dir.'languages') ? 'languages' : (file_exists($abs_plugin_dir.'language') ? 'language' : (file_exists($abs_plugin_dir.'lang') ? 'lang' : '')))
			);
		$textdomain_dir = trailingslashit(trailingslashit($plugin_dir) . $sub_dir);

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
		$siteurl = trailingslashit(get_option('siteurl'));
		return $siteurl . $path;
	}

	// admin url
	private function wp_admin_url($path = '', $network = FALSE) {
		$adminurl = $network ? 'wp-admin/network/' : 'wp-admin/';
		if ( defined( 'WP_SITEURL' ) && '' != WP_SITEURL )
			$adminurl = WP_SITEURL . '/' . $adminurl;
		elseif ( function_exists( 'get_bloginfo' ) && '' != get_bloginfo( 'wpurl' ) )
			$adminurl = get_bloginfo( 'wpurl' ) . '/' . $adminurl;
		elseif ( strpos( $_SERVER['PHP_SELF'], 'wp-admin' ) !== false )
			$adminurl = '';
		return trailingslashit($adminurl) . $path;
	}

	// WP_CONTENT_DIR
	private function wp_content_dir($path = '') {
		return trailingslashit( trailingslashit( defined('WP_CONTENT_DIR')
			? WP_CONTENT_DIR
			: trailingslashit(ABSPATH) . 'wp-content'
			) . preg_replace('/^\//', '', $path) );
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
		return trailingslashit($this->wp_content_dir( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// WP_PLUGIN_URL
	private function wp_plugin_url($path = '') {
		return trailingslashit($this->wp_content_url( 'plugins/' . preg_replace('/^\//', '', $path) ));
	}

	// get http url
	private function get_httpurl() {
		$scheme =
			(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
			? 'http'
			: 'https';
		$http_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		return $http_url;
	}

	// get request
	private function get_request() {
		$http_method = $_SERVER['REQUEST_METHOD'];
		$http_url = $this->get_httpurl();
		$request = OP_OAuthRequest::from_request($http_method, $http_url, NULL);
		if ( method_exists($request, 'unset_parameter') ) {
			$request->unset_parameter('q');
		} else {
			$parameters = $request->get_parameters();
			unset($parameters['q']);
			$request = new OP_OAuthRequest($http_method, $http_url, $parameters);
		}
		return $request;
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
	private function json_decode( $string ) {
		if ( function_exists('json_decode') ) {
			return json_decode( $string );
		} else {
			// For PHP < 5.2.0
			if ( !class_exists('Services_JSON') ) {
				require_once( 'class-json.php' );
			}
			$json = new Services_JSON();
			return $json->decode( $string );
		}
	}

	// json encode
	private function json_encode( $content ) {
		if ( function_exists('json_encode') ) {
			return json_encode($content);
		} else {
			// For PHP < 5.2.0
			if ( !class_exists('Services_JSON') ) {
				require_once( 'class-json.php' );
			}
			$json = new Services_JSON();
			return $json->encode($content);
		}
	}

	// xml encode
	private function xml_encode($content) {
		$xml = FALSE;

		if (!class_exists('PEAR'))
			include_once( 'pear/PEAR.php' );
		if (!class_exists('XML_Util'))
			include_once( 'pear/XML/Util.php' );
		if (!class_exists('XML_Serializer'))
			include_once( 'pear/XML/Serializer.php' );

		$serializer = &new XML_Serializer(array(
			'addDecl'          => TRUE ,
			'encoding'         => get_bloginfo('charset') ,
			'indent'           => "\t" ,
			'linebreak'        => "\n" ,
			'indentAttributes' => TRUE ,
			'mode'             => 'simplexml' ,
			'rootName'         => 'reponse' ,
			));
		if (($result = $serializer->serialize((array)$content)) === TRUE) {
			$xml = $serializer->getSerializedData();
			unset($serializer);
		} else {
			unset($serializer);
			throw new OP_OAuthException('XML Encode Failure!');
		}

		return $xml;
	}

	// text encode
	private function text_encode($content) {
		$text = '';

		if (is_array($content) || is_object($content)) {
			$text = var_export($content, TRUE);
		} else {
			$text = (string)$content;
		}

		return $text;
	}

	// safe url
	private function safe_url($url, $encode = FALSE) {
		if ( !$url || ($parsed = parse_url($url)) === FALSE )
			return FALSE;

		$url = $parsed['scheme'] . '://' . $parsed['host'] .
			( isset($parsed['path'])  ? $parsed['path'] : '/' ) .
			( isset($parsed['query']) ? '?' . $parsed['query'] : '' );
		unset($parsed);
		return $encode ? OP_OAuthUtil::urlencode_rfc3986($url) : $url;
	}

	// crate consumer PIN
	private function create_consumer_pin($id, $base_string, $key) {
		$pin = (string)hexdec(strtolower(preg_replace('/[^0-9a-f]/i', '', $this->hash_sha1($base_string, $key))));
		return substr(preg_replace('/[^0-9a-f]/i', '', $id . $pin), 0, 8);
	}
	private function hash_sha1($base_string, $key, $encode = TRUE) {
		$hash = hash_hmac('sha1', $base_string, $key, true);
		return ($encode ? base64_encode($hash) : $hash);
	}

	// ob end all clean
	private function ob_end_all_clean() {
		$output = '';
		$ob_handlers = (array) ob_list_handlers();
		if  (count($ob_handlers) > 0) {
			foreach ($ob_handlers as $ob_handler) {
				$output .= ob_get_clean();
			}
		}
		return $output;
	}

	// set transient
	private function set_transient($key, $value, $expiration = 0) {
		return
			function_exists('set_site_transient')
			? set_site_transient($key, $value, $expiration)
			: set_transient($key, $value, $expiration);
	}

	// get transient
	private function get_transient($key) {
		return
			function_exists('get_site_transient')
			? get_site_transient($key)
			: get_transient($key);
	}

	// delete_transient
	private function delete_transient($key) {
		return
			function_exists('delete_site_transient')
			? delete_site_transient($key)
			: delete_transient($key);
	}

	public function consumers($consumer_id = false) {
		$consumers = $this->datastore->get_consumers($consumer_id);
		if ( $this->consumer_pin_enabled ) {
			foreach ( $consumers as &$consumer ) {
				$consumer->pin = $this->create_consumer_pin($consumer->id, $consumer->oauthkey, $consumer->secret);
			}
		}
		return $consumers;
	}

	public function access_tokens($userid = false) {
		if ( !$userid )
			list($userid, $username) = $this->get_current_user();
		return $this->datastore->get_access_tokens($userid);
	}

	public function all_access_tokens() {
		return $this->datastore->get_all_access_tokens();
	}

	//**************************************************************************************
	// add method
	//**************************************************************************************
	public function add_method($name, $method) {
		if ( !isset($this->methods[$name]) && is_callable($method) ) {
			$this->methods[$name] = $method;
		}
	}

	//**************************************************************************************
	// remove method
	//**************************************************************************************
	public function remove_method($name) {
		if ( isset($this->methods[$name]) ) {
			unset($this->methods[$name]);
		}
	}

	//**************************************************************************************
	// Add setting link
	//**************************************************************************************
	public function plugin_setting_links($links, $file) {
		global $wp_version;

		$this_plugin = plugin_basename(__FILE__);
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
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
<script type="text/javascript">//<![CDATA[
//]]></script>
<?php
	}

	public function icon_style() {
?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->plugin_url; ?>css/config.css" />
<?php
	}

	public function admin_menu() {
		$hook = add_menu_page(
			__('OAuth Provider', $this->textdomain) ,
			__('OAuth Provider', $this->textdomain) ,
			'level_2',
			$this->menu_base ,
			array($this, 'options_page') //,
//			$this->plugin_url . 'img/icon16.png'
			);
//		add_action('admin_print_scripts-'.$hook, array($this,'add_admin_scripts'));
//		add_action('admin_head-'.$hook, array($this,'add_admin_head'));
//		add_action('admin_print_styles-' . $hook, array($this, 'icon_style'));

		$hook = add_submenu_page(
			$this->menu_base ,
			__('OAuth Provider', $this->textdomain) ,
			__('Consumers', $this->textdomain) ,
			'manage_options',
			$this->menu_base . '_consumers' ,
			array($this, 'options_consumers')
			);
//		add_action('admin_print_scripts-'.$hook, array($this,'add_admin_scripts'));
//		add_action('admin_head-'.$hook, array($this,'add_admin_head'));
//		add_action('admin_print_styles-' . $hook, array($this, 'icon_style'));

		$hook = add_submenu_page(
			$this->menu_base ,
			__('OAuth Provider', $this->textdomain) ,
			__('Access Tokens', $this->textdomain) ,
			'level_0',
			$this->menu_base . '_access_tokens' ,
			array($this, 'options_access_tokens')
			);
//		add_action('admin_print_scripts-'.$hook, array($this,'add_admin_scripts'));
//		add_action('admin_head-'.$hook, array($this,'add_admin_head'));
//		add_action('admin_print_styles-' . $hook, array($this, 'icon_style'));
	}

	private function get_option_table($type, $update = TRUE) {
		$out = '<table class="wp-list-table widefat fixed" style="margin-top:0;">'."\n";

		switch ($type) {
			case 'consumer':
				$title  = '<tr>';
				$title .= '<th>' . __('Consumer Name', $this->textdomain) . '</th>';
				$title .= '<th>' . __('Consumer Description', $this->textdomain) . '</th>';
				if ( !$this->consumer_pin_enabled ) {
					$title .= '<th>' . __('Consumer Key', $this->textdomain) . '</th>';
					$title .= '<th>' . __('Consumer Secret', $this->textdomain) . '</th>';
				} else {
					$title .= '<th>' . __('Consumer PIN', $this->textdomain) . '</th>';
				}
				if ($update)
					$title .= '<th class="check-column"></th>';
				$title .= '</tr>';
				$out .= "<thead>{$title}</thead>\n";
				$out .= "<tfoot>{$title}</tfoot>\n";

				$consumers = $this->consumers();
				$i = 0;
				$alternate = ' class="alternate"';
				$out .= '<tbody>';
				foreach ( (array) $consumers as $consumer) {
					$out .= "<tr{$alternate}>";
					$out .= "<td>{$consumer->name}</td>";
					$out .= "<td>{$consumer->description}</td>";
					if ( !$this->consumer_pin_enabled ) {
						$out .= "<td><input id=\"key-{$consumer->id}\" onfocus=\"this.select()\" value=\"{$consumer->oauthkey}\" /></td>";
						$out .= "<td><input id=\"secret-{$consumer->id}\" onfocus=\"this.select()\" value=\"{$consumer->secret}\" /></td>";
					} else {
						//$pin = $this->create_consumer_pin($consumer->id, $consumer->oauthkey, $consumer->secret);
						$out .= "<td><input id=\"pin-{$consumer->id}\" onfocus=\"this.select()\" value=\"{$consumer->pin}\" /></td>";
					}
					if ($update)
						$out .= "<td class=\"check-column\"><input type=\"checkbox\" name=\"consumers[{$i}]\" value=\"{$consumer->id}\" /></td>";
					$out .= '</tr>' . "\n";
					$i++;
					$alternate = empty($alternate) ? ' class="alternate"' : '';
				}
				$out .= '</tbody>' . "\n";

				if ($update) {
					$out .= '<div style="height:30px;margin:0.5em 0;">';
					$out .= '<div style="float:left;">';
					$out .= __('Name', $this->textdomain) . ': <input id="new_consumer" name="new_consumer" value="" /> ';
					$out .= __('Description', $this->textdomain) . ': <input id="description" name="description" size="60" value="" /> ';
					$out .= '<input type="submit" name="add_consumer" class="button-primary" value="'.__('Add Consumer', $this->textdomain).'" class="button" />';
					$out .= '</div>';
					$out .= '<div style="float:right;">';
					$out .= '<input type="submit" name="del_consumer" class="button-primary" value="'.__('Remove Consumer', $this->textdomain).'" style="margin-left:1em;" class="button" />';
					$out .= '</div>';
					$out .= '</div>' . "\n";
				}


				break;

			case 'access_token':
				$access_tokens = $this->access_tokens();
				if ( count($access_tokens) > 0 ) {
					$title  = '<tr>';
					$title .= '<th>' . __('Consumer Name', $this->textdomain) . '</th>';
					$title .= '<th>' . __('Consumer Description', $this->textdomain) . '</th>';
					$title .= '<th>' . __('Access Key', $this->textdomain) . '</th>';
					$title .= '<th>' . __('Access Secret', $this->textdomain) . '</th>';
					if ($update)
						$title .= '<th class="check-column"></th>';
					$title .= '</tr>';
					$out .= "<thead>{$title}</thead>\n";
					$out .= "<tfoot>{$title}</tfoot>\n";

					$i = 0;
					$alternate = ' class="alternate"';
					$out .= '<tbody>';
					foreach ( (array) $access_tokens as $access_token) {
						$out .= "<tr{$alternate}>";
						$out .= "<td>{$access_token->name}</td>";
						$out .= "<td>{$access_token->description}</td>";
						$out .= "<td><input id=\"key-{$access_token->id}\" onfocus=\"this.select()\" value=\"{$access_token->oauthkey}\" /></td>";
						$out .= "<td><input id=\"secret-{$access_token->id}\" onfocus=\"this.select()\" value=\"{$access_token->secret}\" /></td>";
						if ($update)
							$out .= "<td class=\"check-column\"><input type=\"checkbox\" name=\"access_tokens[{$i}]\" value=\"{$access_token->id}\" /></td>";
						$out .= '</tr>' . "\n";
						$i++;
						$alternate = empty($alternate) ? ' class="alternate"' : '';
					}
					$out .= '</tbody>' . "\n";

					if ($update) {
						$out .= '<p style="text-align:right">';
						$out .= '<input type="submit" name="revoke_access_token" class="button-primary" value="'.__('Revoke Access', $this->textdomain).'" class="button" />';
						$out .= '</p>' . "\n";
					}

				}
				break;

			default:
				break;
		}

		$out .= '</table>' . "\n";

		return $out;
	}

	public function options_page() {
		$nonce_action = 'update_options';
		$nonce_name   = '_wpnonce_update_options';

		$out   = '';
		$note  = '';
		$error = 0;

		list($userid, $username) = $this->get_current_user();

		// Update options
		if (isset($_POST['options_update'])) {
			if ($this->wp_version_check('2.5') && function_exists('check_admin_referer'))
				check_admin_referer($nonce_action, $nonce_name);

			// get options

			// update or delete options
			if (count($values) > 0) {
				update_option($this->option_name, $values);
			} else {
				delete_option($this->option_name);
			}
			$note .= "<strong>".__('Done!', $this->textdomain)."</strong>";
			unset($options);
			unset($values);
		}

		// Add Options
		$out .= '<div class="wrap">'."\n";
		$out .= '<div id="icon-options-general" class="icon32"><br /></div>';
		$out .= '<h2>';
		$out .= __('OAuth Provider Options', $this->textdomain);
		$out .= '</h2>'."\n";

		$out .= '<form method="post" id="update_options" action="'.$this->admin_action.'">'."\n";
		if ($this->wp_version_check('2.5') && function_exists('wp_nonce_field') ) {
			$out .= wp_nonce_field($nonce_action, $nonce_name, true, false);
		}

//		// Add Update Button
//		$out .= '<p style="margin-top:1em">';
//		$out .= '<input type="submit" name="options_update" class="button-primary" value="'.__('Save Changes').'" class="button" />';
//		$out .= '</p>';

		$out .= '</form>'."\n";

		$out .= '<h3>';
		$out .= __('Consumers', $this->textdomain);
		$out .= '</h3>'."\n";
		$out .= $this->get_option_table('consumer', FALSE);

//		$out .= '<p><a herf="'.$this->admin_action.'_consumers" id="manage_consumers">' . __('Manage Consumers', $this->textdomain) . '</a></p>';
//		$out .= '<p><a herf="'.$this->admin_action.'_access_tokens" id="manage_access_tokens">' . __('Manage Access Tokens', $this->textdomain) . '</a></p>';

		$out .= '</div>'."\n";

		// Output
		echo ( !empty($note) ? '<div id="message" class="updated fade"><p>'.$note.'</p></div>'  : '' );
		echo "\n";

		echo ( $error <= 0 ? $out : '' );
		echo "\n";
	}

	// Manage Consumers
	public function options_consumers() {
		$nonce_action = 'update_options';
		$nonce_name   = '_wpnonce_update_options';

		$out   = '';
		$note  = '';
		$error = 0;

		list($userid, $username) = $this->get_current_user();

		// add consumer
		if (isset($_POST['add_consumer'])) {
			if ($this->wp_version_check('2.5') && function_exists('check_admin_referer'))
				check_admin_referer($nonce_action, $nonce_name);

			$consumer_name = $this->escape($_POST['new_consumer']);
			$description   = $this->escape($_POST['description']);
			try {
				if (!empty($consumer_name) && $this->datastore->new_consumer_key($consumer_name, $description)) {
					$note .= "<strong>".__('Done!', $this->textdomain)."</strong>";
				} else {
					$note .= "<strong>".__('Failure!!', $this->textdomain)."</strong>";
					$error++;
				}
			} catch ( OP_OAuthException $e ) {
				$note .= "<strong>".__('Failure!!', $this->textdomain)."</strong>: " . __($e->getMessage(), $this->textdomain);
				$error++;
			}

		// delete consumer
		} else if (isset($_POST['del_consumer']) && isset($_POST['consumers'])) {
			$consumers = $_POST['consumers'];
			foreach ((array) $consumers as $consumerid) {
				$consumerid = $this->escape($consumerid);
				if ( $this->datastore->delete_consumer($consumerid) !== FALSE ) {
					$error++;
					break;
				}
			}
			if ( $error <= 0 )
				$note .= "<strong>".__('Done!', $this->textdomain)."</strong>";
			else
				$note .= "<strong>".__('Failure!!', $this->textdomain)."</strong>";
		}

		$out .= '<div class="wrap">'."\n";
		$out .= '<div id="icon-options-general" class="icon32"><br /></div>';
		$out .= '<h2>';
		$out .= __('OAuth Provider &raquo; Consumers', $this->textdomain);
		$out .= '</h2>'."\n";

		// Consumer Keys
		$out .= '<form method="post" id="update_options" action="'.$this->admin_action.'_consumers">'."\n";
		if ($this->wp_version_check('2.5') && function_exists('wp_nonce_field') )
			$out .= wp_nonce_field($nonce_action, $nonce_name, true, false);
		$out .= $this->get_option_table('consumer');
		$out .= '</form></div>'."\n";

		// Output
		echo ( !empty($note) ? '<div id="message" class="updated fade"><p>'.$note.'</p></div>'  : '' );
		echo "\n";

		echo ( $error <= 0 ? $out : '' );
		echo "\n";
	}

	// Manage Access Tokens
	public function options_access_tokens() {
		$nonce_action = 'update_options';
		$nonce_name   = '_wpnonce_update_options';

		$out   = '';
		$note  = '';
		$error = 0;

		list($userid, $username) = $this->get_current_user();

		// revoke access tokens
		if (isset($_POST['revoke_access_token']) && isset($_POST['access_tokens'])) {
			$access_tokens = $_POST['access_tokens'];
			foreach ((array) $access_tokens as $access_token_id) {
				$access_token_id = $this->escape($access_token_id);
				if ( $this->delete_access_token($userid, $access_token_id) !== FALSE ) {
					$error++;
					break;
				}
			}
			if ( $error <= 0 )
				$note .= "<strong>".__('Done!', $this->textdomain)."</strong>";
			else
				$note .= "<strong>".__('Failure!!', $this->textdomain)."</strong>";
		}

		$out .= '<div class="wrap">'."\n";
		$out .= '<div id="icon-options-general" class="icon32"><br /></div>';
		$out .= '<h2>';
		$out .= __('OAuth Provider &raquo; Access Tokens', $this->textdomain);
		$out .= '</h2>'."\n";

		// Access tokens
		$out .= '<form method="post" id="update_options" action="'.$this->admin_action.'_access_tokens">'."\n";
		if ($this->wp_version_check('2.5') && function_exists('wp_nonce_field') )
			$out .= wp_nonce_field($nonce_action, $nonce_name, true, false);
		$out .= $this->get_option_table('access_token');
		$out .= '</form></div>'."\n";

		// Output
		echo ( !empty($note) ? '<div id="message" class="updated fade"><p>'.$note.'</p></div>'  : '' );
		echo "\n";

		echo ( $error <= 0 ? $out : '' );
		echo "\n";
	}

	//**************************************************************************************
	// oauth request
	//**************************************************************************************
	public function oauth_request() {
		ob_start();
		$method_name = get_query_var('oauth');
		switch ($method_name) {
			case 'consumer_key':
				$this->consumer_key();
				break;
			case 'request_token':
				$this->request_token();
				break;
			case 'authorize':
				$this->authorize();
				break;
			case 'access_token':
				$this->access_token();
				break;
			case 'send_request':
				$this->send_request();
				break;
			default:
				$this->send_request($method_name);
				break;
		}
		$this->ob_end_all_clean();
		exit;
	}

	//**************************************************************************************
	// consumer key
	//**************************************************************************************
	private function consumer_key() {
		$result = '';

		ob_start();
		try {
			if ( !$this->consumer_pin_enabled )
				throw new OP_OAuthException( 'Unauthorized Access!' );

			$request = $this->get_request();
			$consumer_id = $request->get_parameter('consumer_id');
			$pin = $request->get_parameter('pin');
			if (!$consumer_id)
				$consumer_id = (int)substr($pin, 0, 1);

			$consumers = $this->consumers($consumer_id);
			if (count($consumers) > 0) {
				$consumer = $consumers[0];
				if ($pin === $consumer->pin ) // $this->create_consumer_pin($consumer_id, $consumer->oauthkey, $consumer->secret))
					$result = "oauth_consumer=" . OP_OAuthUtil::urlencode_rfc3986($consumer->oauthkey) .
						"&oauth_consumer_secret=" . OP_OAuthUtil::urlencode_rfc3986($consumer->secret);
				else
					throw new OP_OAuthException( 'Unauthorized PIN' );
			}

		} catch ( OP_OAuthException $e ) {
			header('WWW-Authenticate: OAuth realm="' . $this->wp_site_url() . '"');
			header("HTTP/1.0 401 Unauthorized");
			$result = __($e->getMessage(), $this->textdomain);
		}
		$this->ob_end_all_clean();

		header('Content-Type: text/plain; charset=' . get_bloginfo('charset') );
		nocache_headers();
		echo $result;
		exit;
	}

	//**************************************************************************************
	// request token
	//**************************************************************************************
	private function request_token() {
		$result = '';

		ob_start();
		try {
			$request = $this->get_request();
			$token   = $this->server->fetch_request_token( $request );
			$this->datastore->delete_nonce( $this->server->get_expired() );
			$result = $token;
		} catch ( OP_OAuthException $e ) {
			header('WWW-Authenticate: OAuth realm="' . $this->wp_site_url() . '"');
			header("HTTP/1.0 401 Unauthorized");
			$result = __($e->getMessage(), $this->textdomain);
		}
		$this->ob_end_all_clean();

		header('Content-Type: text/plain; charset=' . get_bloginfo('charset') );
		nocache_headers();
		echo $result;
		exit;
	}

	//**************************************************************************************
	// authorize
	//**************************************************************************************
	private function authorize() {
		ob_start();
		$title = __('Authorize', $this->textdomain);
		$message = '';
		$form = '';

		// get request
		$request   = $this->get_request();

		// get token key
		$token_key = $request->get_parameter('oauth_token');
		if (is_array($token_key))
			$token_key = $token_key[0];

		// get callback url
		$callbackurl = $request->get_parameter('callbackurl');
		if (is_array($callbackurl))
			$callbackurl = $callbackurl[0];
		$callbackurl = esc_attr($this->safe_url($callbackurl ? $callbackurl : $_SERVER['HTTP_REFERER']));

		// get request and consumer info
		try {
			$consumer  = $this->datastore->lookup_consumer_from_request_token($token_key);
		} catch ( OP_OAuthException $e ) {
			header("HTTP/1.0 401 Unauthorized");
			nocache_headers();
			wp_die( __($e->getMessage(), $this->textdomain) );
			exit;
		}

		// not loged in
		if ( !is_user_logged_in() ) {
			$message  = '<h1>';
			$message .= __('Not Login', $this->textdomain);
			$message .= "</h1>\n";

			$message .= '<p>';
			$message .= sprintf(
				__('The application &quot;%s&quot; would like the ability to access and update your data on &quot;%s&quot;.', $this->textdomain) ,
				$consumer->name ,
				get_bloginfo( 'name' )
				) . "<br />\n";
			$message .= __('Sign in if you want to connect to an account.', $this->textdomain);
			$message .= "</p>\n";

			$this->login_form($title, $message, $callbackurl);
			exit;
		}

		list($userid, $username) = $this->get_current_user();

		// Allow
		if ( isset($_POST['allow']) ) {
			$this->datastore->allow_request_token($userid, $consumer->id, $token_key);
			$this->datastore->delete_nonce( $this->server->get_expired() );
			$message  = '<p><strong>' . __('Allowed.', $this->textdomain) . "</strong></p>\n";

			$callbackurl .= ( strpos( $callbackurl, '?') ? '&' : '?' ) .
				'oauth_token=' . OP_OAuthUtil::urlencode_rfc3986($token_key);

			header( 'Location: ' . $callbackurl );
			nocache_headers();
			exit;

		// Deny
		} elseif ( isset($_POST['deny']) ) {
			$this->datastore->deny_request_token($userid, $consumer->id, $token_key);
			$this->datastore->delete_nonce( $this->server->get_expired() );
			$message  = '<p><strong>' . __('Denied.', $this->textdomain) . "</strong></p>\n";

		// Show submit form
		} else {
			$message  = '<h1>';
			$message .= __('An application would like to connect to your account.', $this->textdomain);
			$message .= "</h1>\n";

			$message .= '<p>';
			$message .= sprintf(
				__('The application &quot;%s&quot; would like the ability to access and update your data on &quot;%s&quot;.', $this->textdomain) ,
				$consumer->name ,
				get_bloginfo( 'name' )
				) . "<br />\n";
			$message .= sprintf(
				__('Sign out if you want to connect to an account other than &quot;%s&quot;.', $this->textdomain) ,
				$username
				) . "<br />\n";
			$message .= sprintf(
				__('Allow <strong>&quot;%s&quot;</strong> access?', $this->textdomain) ,
				$consumer->name
				) . "<br />\n";
			$message .= "</p>\n";

			$form  = '<form method="post" id="authorize">';
			$form .= '<input name="oauth_token" type="hidden" value="'.$token_key.'" />';
			$form .= '<input name="callbackurl" type="hidden" value="'.$callbackurl.'" />';
			$form .= '<input type="submit" name="allow" class="button-primary" value="' . __('Allow', $this->textdomain) . '" class="button" /> ';
			$form .= '<input type="submit" name="deny" class="button-primary" value="' . __('Deny', $this->textdomain) . '" class="button" />';
			$form .= '</form>'."\n";

		}
		$this->ob_end_all_clean();

		$this->authorize_form($title, $message, $form);
		exit;
	}

	// authorize form
	private function authorize_form($title, $message, $form) {
		ob_start();
		$charset = get_bloginfo( 'charset' );
		$title .= ' | ' . get_bloginfo( 'name' );
		$admin_dir = $this->wp_admin_url();

		$out = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
	<title>{$title}</title>
	<link rel="stylesheet" href="{$admin_dir}css/install.css" type="text/css" />
	<link rel="stylesheet" href="{$admin_dir}css/colors-fresh.css" type="text/css" />
	<style type="text/css">/* <![CDATA[ */
	html {
		background-color: #F9F9F9;
	}
	.button-primary {
		border: 1px solid;
		border-radius: 11px 11px 11px 11px;
		cursor: pointer;
		font-family: sans-serif;
		font-size: 13px;
		margin-top: -3px;
		padding: 3px 10px;
		text-decoration: none;
	}
	/* ]]> */</style>
</head>
<body>
	$message
	$form
</body>
</html>
EOT;

		$this->ob_end_all_clean();
		header('Content-type: text/html; charset=' . $charset);
		nocache_headers();
		echo $out;
		exit;
	}

	// login form
	private function login_form($title, $message, $callbackurl = '') {
		ob_start();
		$charset = get_bloginfo( 'charset' );
		$title .= ' | ' . get_bloginfo( 'name' );
		$admin_dir = $this->wp_admin_url();

		$action_url  = site_url('wp-login.php', 'login_post');
		$redirect_to = $this->get_httpurl();
		if (!empty($callbackurl))
			$redirect_to .= ( strpos( $redirect_to, '?') ? '&' : '?' ) . 'callbackurl=' . OP_OAuthUtil::urlencode_rfc3986($callbackurl);
		$label_user  = __('Username');
		$label_pwd   = __('Password');
		$value_user  = esc_attr($user_login);
		$msg_rememberme = esc_attr(__('Remember Me'));
		$msg_login      = esc_attr(__('Log In'));

		ob_start();
		do_action('login_form');
		$action_login_form = ob_get_clean();

		$out = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="ja">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
	<title>{$title}</title>
	<link rel="stylesheet" href="{$admin_dir}css/install.css" type="text/css" />
	<link rel="stylesheet" href="{$admin_dir}css/login.css" type="text/css" />
	<link rel="stylesheet" href="{$admin_dir}css/colors-fresh.css" type="text/css" />
	<style type="text/css">/* <![CDATA[ */
	html {
		background-color: #F9F9F9;
	}
	.button-primary {
		border: 1px solid;
		border-radius: 11px 11px 11px 11px;
		cursor: pointer;
		font-family: sans-serif;
		font-size: 13px;
		margin-top: -3px;
		padding: 3px 10px;
		text-decoration: none;
	}
	/* ]]> */</style>
</head>
<body class="login">
	$message
	<div id="login">
		<form name="loginform" id="loginform" action="$action_url" method="post">
			<p>
				<label>$label_user<br />
				<input type="text" name="log" id="user_login" class="input" value="$value_user" size="20" tabindex="10" /></label>
			</p>
			<p>
				<label>$label_pwd<br />
				<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" tabindex="20" /></label>
			</p>
			$action_login_form
			<p class="forgetmenot"">
				<label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" style="width:1em;" /> $msg_rememberme</label>
			</p>
			<br class="clear" />
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="$msg_login" tabindex="100" />
				<input type="hidden" name="redirect_to" value="$redirect_to" />
				<input type="hidden" name="testcookie" value="1" />
			</p>
		</form>
	</div>
</body>
</html>
EOT;

		$this->ob_end_all_clean();
		header('Content-type: text/html; charset=' . $charset);
		nocache_headers();
		echo $out;
		exit;
	}

	//**************************************************************************************
	// access token
	//**************************************************************************************
	private function access_token() {
		ob_start();
		$result = '';

		try {
			$request = $this->get_request();
			$token   = $this->server->fetch_access_token( $request );
			$this->datastore->delete_nonce( $this->server->get_expired() );
			$result = $token;
		} catch ( OP_OAuthException $e ) {
			header('WWW-Authenticate: OAuth realm="' . $this->wp_site_url() . '"');
			header("HTTP/1.0 401 Unauthorized");
			$result = __($e->getMessage(), $this->textdomain);
		}

		$this->ob_end_all_clean();
		header('Content-Type: text/plain; charset=' . get_bloginfo('charset') );
		nocache_headers();
		echo $result;
		exit;
	}

	public function delete_access_token($userid, $access_token_id){
		return $this->datastore->delete_access_token($userid, $access_token_id);
	}

	//**************************************************************************************
	// oauth request
	//**************************************************************************************
	private function send_request($method_name = NULL, $data_encode = TRUE) {
		ob_start();
		$charset = get_bloginfo('charset');
		$result  = FALSE;

		try {
			// get request
			$request = $this->get_request();
			$method_name = $method_name ? $method_name : $request->get_parameter('method');
			if ( preg_match('/^([^\.]*)\.(json|xml|text)$/i', $method_name, $match) ) {
				$method_name = $match[1];
				$this->response_data_type = strtolower($match[2]);
			}

			// verify request
			list($consumer, $token) = $this->server->verify_request( $request );

			// verify method name
			if ( !isset($this->methods[$method_name]) || !is_callable($this->methods[$method_name]) )
				throw new OP_OAuthException('Method Not Found!');

			// set current user
			wp_set_current_user( $token->userid );
			list($userid, $username) = $this->get_current_user();

			// get result from background
			$result = FALSE;
			$transient_key = FALSE;
			$taskid = $request->get_parameter('taskid');
			if ( $taskid ) {
				$transient_key = "oauth_request_{$method_name}_{$taskid}";
				$task = (array)$this->get_transient($transient_key);
				if ( isset($task['result']) ) {
					$result = $task['result'];
					$this->delete_transient($transient_key);
				}
			}

			// Exec request method
			if ( $result === FALSE ) {
				$result = call_user_func($this->methods[$method_name], $request, $userid, $username);
				if (is_wp_error($result))
					throw new OP_OAuthException($result->get_error_message());
				elseif ($result === FALSE)
					throw new OP_OAuthException('Unknown Error');
				if ( $transient_key !== FALSE ) {
					$task = array(
						'taskid' => $taskid ,
						'method' => $method_name ,
						'result' => $result ,
						);
					$this->set_transient($transient_key, $task, 1 * 60 * 60);
				}
			}

			if ($data_encode) {
				$result = array_merge(
					array('result' => TRUE),
					$result
					);
				switch ($this->response_data_type) {
				case 'xml':
					$content_type = 'text/xml';
					$result = $this->xml_encode($result);
					break;
				case 'text':
					$content_type = 'text/plain';
					$result = $this->text_encode($result);
					break;
				case 'json':
				default:
					$content_type = 'application/json';
					$result = $this->json_encode($result);
					break;
				}
			} else {
				$content_type = 'text/plain';
				$this->response_data_type = 'text';
				$result = $this->text_encode($result);
			}

			if ($result === FALSE || is_wp_error($result))
				throw new OP_OAuthException(is_wp_error($result) ? $result->get_error_message() : 'Encode Failure!');

		} catch ( OP_OAuthException $e ) {
			// The request wasn't valid!
			$err_message = __($e->getMessage(), $this->textdomain);
			if ($data_encode) {
				switch ($this->response_data_type) {
				case 'xml':
					$content_type = 'text/xml';
					$result = $this->xml_encode(array('result' => FALSE, 'error' => $err_message));
					break;
				case 'text':
					$content_type = 'text/plain';
					$result = $err_message;
					break;
				case 'json':
				default:
					$content_type = 'application/json';
					$result = $this->json_encode(array('result' => FALSE, 'error' => $err_message));
					break;
				}
			} else {
				$content_type = 'text/plain';
				$this->response_data_type = 'text';
				$result = $err_message;
			}

			if ($this->response_data_type !== 'json') {
				header('WWW-Authenticate: OAuth realm="' . $this->wp_site_url() . '"');
				header("HTTP/1.0 401 Unauthorized");
			}
		}

		$this->ob_end_all_clean();
		header("Content-Type: {$content_type}; charset={$charset}" );
		nocache_headers();
		echo $result;
		exit;
	}
}

//**************************************************************************************
// Add new oauth method
//**************************************************************************************
if (!function_exists('add_oauth_method')) {
	function add_oauth_method( $name, $method ) {
		global $oauth_provider;
		if (!isset($oauth_provider))
			$oauth_provider = new WP_OAuthProvider();
		$oauth_provider->add_method($name, $method);
	}
}

//**************************************************************************************
// Remove oauth method
//**************************************************************************************
if (!function_exists('remove_oauth_method')) {
	function remove_oauth_method( $name ) {
		global $oauth_provider;
		if (!isset($oauth_provider))
			$oauth_provider = new WP_OAuthProvider();
		$oauth_provider->remove_method($name);
	}
}
