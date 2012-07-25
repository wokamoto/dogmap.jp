<?php
require_once(ABSPATH . 'wp-admin/includes/admin.php');

class Remote_Upgrader {
	private $error = '';

	function __construct() {
	}

	private function is_multisite() {
		return function_exists('is_multisite') && is_multisite();
	}

	// initialize wp_filesystem
	private function wp_filesystem() {
		global $wp_filesystem;
		if ( !isset($wp_filesystem) || !is_object($wp_filesystem) ) {
			if ( !function_exists('WP_Filesystem') )
				require_once(ABSPATH . 'wp-admin/includes/file.php');
			if ( !function_exists('WP_Filesystem') || WP_Filesystem() === false ) {
				$this->error = __('Could not access filesystem.');
				return false;
			}
		}
		return true;
	}

	// check wp version
	private function wp_version_check($version, $operator = ">=") {
		global $wp_version;
		return version_compare($wp_version, $version, $operator);
	}

	// get parameters
	private function get_parameters( $request ) {
		$parameters = 
			method_exists($request,'get_parameters')
			? $request->get_parameters()
			: (isset($_GET) ? $_GET : (isset($_POST) ? $_POST : false));

		return $parameters;
	}

	private function get_transient($key) {
		return
			function_exists('get_site_transient')
			? get_site_transient($key)
			: get_transient($key);
	}

	private function set_transient($key, $value) {
		if ( function_exists('set_site_transient') )
			return set_site_transient($key, $value);
		else
			return set_transient($key, $value);
	}

	private function delete_transient($key) {
		if ( function_exists('delete_site_transient') )
			return delete_site_transient('update_core');
		else
			return delete_transient('update_core');
	}

	private function maintenance_mode($enable = false) {
		if ( $this->wp_filesystem() ) {
			if (!class_exists('Remote_Upgrader_Skin'))
				require_once(dirname(__FILE__) . '/class-remote-upgrader-skin.php');
			$maintenance = new WP_Upgrader( new Remote_Upgrader_Skin( null ) );
			$maintenance->maintenance_mode( $enable );
			unset( $maintenance );
		} else {
			$file = ABSPATH . '.maintenance';
			if ( $enable ) {
				// Create maintenance file to signal that we are upgrading
				$maintenance_string = '<?php $upgrading = ' . time() . '; ?>';
				if (file_exists($file))
					@unlink($file);
				file_put_contents($file, $maintenance_string);
			} else if ( !$enable && file_exists($file) ) {
				@unlink($file);
			}
		}

		return $enable;
	}

	private function ob_end_all_clean() {
		$output = array();
		$ob_handlers = (array) ob_list_handlers();
		if  (count($ob_handlers) > 0) {
			foreach ($ob_handlers as $ob_handler) {
				$output[] = ob_get_clean();
			}
		}
		return count($output) > 0 ? implode(',', $output) : '';
	}

	private function get_versions() {
		global $wp_version, $wpdb;
		return array(
			'wp'      => $wp_version ,
			'php'     => PHP_VERSION ,
			'mysql'   => $wpdb->db_version() ,
			'plugins' => $this->get_transient('update_plugins') ,
			'themes'  => $this->get_transient('update_themes') ,
			);
	}

	// json decode
	private function json_decode( $string, $assoc = false ) {
		if ( function_exists('json_decode') ) {
			return json_decode( $string, $assoc );
		} else {
			// For PHP < 5.2.0
			if ( !class_exists('Services_JSON') ) {
				require_once( 'class-json.php' );
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
				require_once( 'class-json.php' );
			}
			$json = new Services_JSON();
			return $json->encode($content);
		}
	}

	// safe url
	private function safe_url($url) {
		if ( !preg_match('/^(https?|ftp):\/\//i', $url) )
			return false;
		if ( ($parsed = parse_url($url)) === false )
			return false;

		$url =
			( isset($parsed['scheme']) ? $parsed['scheme'] : 'http' ) . '://' .
			( isset($parsed['host'])   ? $parsed['host'] : '' ) .
			( isset($parsed['path'])   ? $parsed['path'] : '' ) .
			( isset($parsed['query'])  ? '?' . $parsed['query'] : '' );
		return $url;
	}

	// remote get
	private function remote_get($url) {
		$contents = false;
		if (($url = $this->safe_url($url)) !== false) {
			if (function_exists('wp_remote_get')) {
				$response = wp_remote_get($url);
				if( !is_wp_error( $response ) && $response["response"]["code"] === 200 )
				    $contents = $response["body"];
			} else {
				$contents = file_get_contents($url);
			}
		}
		return $contents;
	}

	public function wp_getSiteInfo( $request, $userid, $username ) {
		global $current_user;

		if ( ($options = $this->get_parameters( $request )) !== false ) {
			ob_start();
			get_currentuserinfo();
			$permalink_structure = get_option('permalink_structure');
			$permalink_type = 'Ugly';
			if (empty($permalink_structure) || !$permalink_structure) {
				$permalink_type = 'Ugly';
			} else if (preg_match('/^\/index\.php/i', $permalink_structure)) {
				$permalink_type = 'Almost Pretty';
			} else {
				$permalink_type = 'Pretty';
			}
			$versions = $this->get_versions();
			$output = ob_get_clean();
			$result = array(
				'wp'      => $versions['wp'] ,
				'php'     => $versions['php'] ,
				'mysql'   => $versions['mysql'] ,
				'plugins' => $versions['plugins'] ,
				'themes'  => $versions['themes'] ,
				'site'	  => array(
					'name'           => get_bloginfo('name') ,
					'description'    => get_bloginfo('description') ,
					'url'            => function_exists('home_url') ? home_url() : get_bloginfo('url') ,
					'home'           => get_bloginfo('home') ,
					'admin_email'    => get_bloginfo('admin_email') ,
					'permalink_type' => $permalink_type ,
					) ,
				'user'	  => array(
					'ID'             => $current_user->ID ,
					'name'           => $current_user->user_login ,
					'email'          => $current_user->user_email ,
					'first_name'     => $current_user->user_firstname ,
					'last_name'      => $current_user->user_lastname ,
					'display_name'   => $current_user->display_name,
					) ,
				'output'  => $output ,
				);
			return $result;
		} else {
			return $this->error;
		}
	}

	public function wp_getVersions( $request, $userid, $username ) {
		if ( ($options = $this->get_parameters( $request )) !== false )
			return $this->get_versions();
		else
			return $this->error;
	}

	private function current_user_can( $capability ) {
			switch ($capability) {
			case 'update_core' :
				$capability = $this->wp_version_check('3.0')
					? 'update_core'
					: 'update_plugins';
				break;
			case 'update_plugins' :
			case 'update_themes' :
			default:
				break;
			}
			return current_user_can($capability);
	}

	public function wp_currentUserCan( $request, $userid, $username ) {
		$result = array();
		if ( ($options = $this->get_parameters( $request )) !== false && isset($options['capability'])) {
			ob_start();
			$result['capability'] = $options['capability'];
			$result['result']     = $this->current_user_can($options['capability']);
			ob_end_clean();
		} else {
			$result['capability'] = '';
			$result['result']     = false;
			$result['error']      = $this->error;
		}
		return $result;
	}

	private function disabled_update_cancel($product = 'core') {
		add_action( 'load-update-core.php', 'wp_update_'.$product );
		add_filter( 'pre_site_transient_update_'.$product, '__return_false', 11 );
	}

	// get all update information
	public function wp_getUpdates( $request, $userid, $username ) {
		global $wp_version, $wpdb;

		if ( ($options = $this->get_parameters( $request )) === false )
			return $this->error;

		ob_start();

		$url = isset($options['update_info']) ? urldecode($options['update_info']) : '';
		$upgrade = $this->json_decode($this->remote_get($url));
		$this->set_transient('remote_upgrade', $upgrade);
		if ( isset($upgrade->current) ) {
			ob_start();

			// get upgrade plugins
			$count = 0;
			wp_update_plugins();
			$current_plugins = $this->get_transient('update_plugins');
			$upgrade_plugins = isset($upgrade->current->plugins) ? $upgrade->current->plugins : '';
			foreach ( (array)$upgrade_plugins as $plugin_file => $plugin_data) {
				if ( isset( $current_plugins->checked[$plugin_file]) ) {
					if ($plugin_file === 'oauth-provider-ex/oauth-provider-ex.php' && $this->wp_version_check('3.1', '<') ) {
						continue;
					}
					if ( version_compare($current_plugins->checked[$plugin_file], $plugin_data->new_version, '<') ) {
						$current_plugins->response[$plugin_file] = (object)$plugin_data;
						$count++;
					}
				}
			}
			if ($count > 0) {
				$this->set_transient('update_plugins', $current_plugins);
			}

			// get upgrade themes
			$count = 0;
			wp_update_themes();
			$current_themes = $this->get_transient('update_themes');
			$upgrade_themes = isset($upgrade->current->themes) ? $upgrade->current->themes : '';
			foreach ( (array)$upgrade_themes as $theme_file => $theme_data) {
				if ( isset( $current_themes->checked[$theme_file]) ) {
					if ( version_compare($current_themes->checked[$theme_file], $theme_data->new_version, '<') ) {
						$current_themes->response[$theme_file] = (object)$theme_data;
						$count++;
					}
				}
			}
			if ($count > 0) {
				$this->set_transient('update_themes', $current_themes);
			}

			ob_end_clean();
		}

		wp_version_check();

		$core_updates = $this->wp_getCoreUpdates($request, $userid, $username);
		if ( !isset($core_updates[0]->response) || 'latest' == $core_updates[0]->response || 'development' == $core_updates[0]->response || version_compare( $core_updates[0]->current, $wp_version, '=') )
			$core_update_version = false;
		else
			$core_update_version = $core_updates[0]->current;
		$core_updates = array(
			'current' => $this->wp_getVersions($request, $userid, $username) ,
			'update' => array(
				'version' => $core_update_version ,
				),
			);

		$plugins = $this->wp_getPluginUpdates($request, $userid, $username);
		$plugin_updates = array();
		foreach ( (array) $plugins as $plugin_file => $plugin_data) {
			$plugin_updates[$plugin_file] = array(
				'name'	=> $plugin_data->Name,
				'current' => array(
					'version' => $plugin_data->Version,
					),
				'update' => $plugin_data->update,
				);
		}

		$themes = $this->wp_getThemeUpdates($request, $userid, $username);
		$theme_updates = array();
		foreach ( (array) $themes as $theme_file => $theme_data) {
			$theme_updates[$theme_file] = array(
				'name'	=> $theme_data->Name,
				'current' => array(
					'version' => $theme_data->Version,
					),
				'update' => $theme_data->update,
				);
		}

		ob_end_clean();

		return array(
			'core'		=> $core_updates ,
			'plugin'	=> $plugin_updates ,
			'theme'		=> $theme_updates ,
			);
	}

	// get core update information
	public function wp_getCoreUpdates( $request, $userid, $username ) {
		if ( ($options = $this->get_parameters( $request )) !== false ) {
			ob_start();
			wp_version_check();
			$this->disabled_update_cancel('core');
			$result = get_core_updates();
			ob_end_clean();
			return $result;
		} else {
			return $this->error;
		}
	}

	// get plugins update information
	public function wp_getPluginUpdates( $request, $userid, $username ) {
		if ( ($options = $this->get_parameters( $request )) !== false ) {
			ob_start();
			wp_update_plugins();
			$this->disabled_update_cancel('plugins');
			$result = get_plugin_updates();
			ob_end_clean();
			return $result;
		} else {
			return $this->error;
		}
	}

	// get themes update information
	public function wp_getThemeUpdates( $request, $userid, $username ) {
		if ( ($options = $this->get_parameters( $request )) !== false ) {
			ob_start();
			wp_update_themes();
			$this->disabled_update_cancel('themes');
			$result = get_theme_updates();
			ob_end_clean();
			return $result;
		} else {
			return $this->error;
		}
	}

	// core update
	public function wp_CoreUpdate( $request, $userid, $username ) {
		if ( ($options = $this->get_parameters( $request )) === false )
			return $this->error;

		if ( $this->wp_filesystem() === false )
			return $this->error;

		if ( ! $this->current_user_can( 'update_core' ) )
			return new WP_Error( 403, __( 'You do not have sufficient permissions to update this site.' ) );

		if (!class_exists('Remote_Upgrader_Skin'))
			require_once(dirname(__FILE__) . '/class-remote-upgrader-skin.php');

		$this->disabled_update_cancel('core');
		$version = isset($options['version']) ? $options['version'] : NULL;
		$locale  = isset($options['locale'])  ? $options['locale']  : NULL;
		if (!isset($locale))
			$locale  = (defined('WPLANG') ? WPLANG : 'en_US');
		$update  = find_core_update( $version, $locale );

		$maintenance = false;
		$results = array();
		if ($update) {
			// core update
			$error = false;
			try {
				ob_start();

				// upgrader
				$upgrader = new Core_Upgrader( new Remote_Upgrader_Skin( null ) );
				$result   = $upgrader->upgrade( $update );
				$error    = NULL;

				// Force refresh of update information
				$this->delete_transient('update_core');

				if ( isset($result->errors) ) {
					$error = '';
					foreach ( $result->errors as $val ) {
						$error .= implode(',', (array)$val);
					}
					if ( !empty($error) ) {
						$result = false;
					} else {
						$error = NULL;
					}
				}

				$output   = ob_get_clean();

				include(ABSPATH . 'wp-includes/version.php');
				$results = array(
					'name'       => "core $version $locale ( $wp_db_version )" ,
					'version'    => $version ,
					'locale'     => $locale ,
					'db_version' => $wp_db_version ,
					'result'     => $result ,
					'output'     => $output ,
					'error'      => $error ,
					);
			} catch(Exception $e) {
				global $wp_db_version;
				$results = array(
					'name'       => "core $version $locale ( $wp_db_version )" ,
					'version'    => $version ,
					'locale'     => $locale ,
					'db_version' => $wp_db_version ,
					'result'     => false ,
					'output'     => NULL ,
					'error'      => $error !== false ? $error : $e->getMessage() ,
					);
			}

		} else {
			global $wp_db_version;
			$results = array(
				'name'       => "core $version $locale ( $wp_db_version )" ,
				'version'    => $version ,
				'locale'     => $locale ,
				'db_version' => $wp_db_version ,
				'result'     => false ,
				'output'     => NULL ,
				'error'      => 'No update data.' ,
				);
		}

		return $results;
	}

	// core DB upgrade
	public function wp_DBUpgrade( $request, $userid, $username ) {
		if ( ($options = $this->get_parameters( $request )) === false )
			return $this->error;

		if ( $this->wp_filesystem() === false )
			return $this->error;

		if ( ! $this->current_user_can( 'update_core' ) )
			return new WP_Error( 403, __( 'You do not have sufficient permissions to update this site.' ) );

		if (!class_exists('Remote_Upgrader_Skin'))
			require_once(dirname(__FILE__) . '/class-remote-upgrader-skin.php');

		$this->disabled_update_cancel('core');
		$version = isset($options['version']) ? $options['version'] : NULL;
		$locale  = isset($options['locale'])  ? $options['locale']  : NULL;
		if (!isset($locale))
			$locale  = (defined('WPLANG') ? WPLANG : 'en_US');

		$results = array();

		include(ABSPATH . 'wp-includes/version.php');
		$error = false;
		try {
			ob_start();

			// db upgrade
			if ( !function_exists('wp_upgrade') )
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			if ( $this->is_multisite() ) {
				global $wpdb;
				$current_blogid = $wpdb->blogid;
				$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' AND spam = '0' AND deleted = '0' AND archived = '0' ORDER BY registered DESC" );
				if ( !empty( $blogs ) ) {
					foreach ( (array) $blogs as $blog ) {
						$wpdb->set_blog_id( $blog->blog_id );
						wp_upgrade();
						do_action('after_mu_upgrade',  $blog->blog_id);
						do_action('wpmu_upgrade_site', $blog->blog_id);
					}
				}
				$wpdb->set_blog_id( $current_blogid );
				update_site_option( 'wpmu_upgrade_site', $wp_db_version );

			} else {
				wp_upgrade();
				update_option( 'db_version', $wp_db_version );
			}

			$output = ob_get_clean();

			$results[] = array(
				'name'       => "core $version $locale ( $wp_db_version )" ,
				'version'    => $version ,
				'locale'     => $locale ,
				'db_version' => $wp_db_version ,
				'result'     => true ,
				'output'     => !empty($output) ? $output : NULL ,
				'error'      => $error ,
				);
		} catch(Exception $e) {
			global $wp_db_version;
			$results[] = array(
				'name'       => "core $version $locale ( $wp_db_version )" ,
				'version'    => $version ,
				'locale'     => $locale ,
				'db_version' => $wp_db_version ,
				'result'     => false ,
				'output'     => NULL ,
				'error'      => $error !== false ? $error : $e->getMessage() ,
				);
		}

		return $results;
	}

	// plugins upgrade
	public function wp_PluginsUpdate( $request, $userid, $username ) {
		if ( ($options = $this->get_parameters( $request )) === false )
			return $this->error;

		if ( $this->wp_filesystem() === false )
			return $this->error;

		if ( ! $this->current_user_can( 'update_plugins' ) )
			return new WP_Error( 403, __( 'You do not have sufficient permissions to update this site.' ) );

		$this->disabled_update_cancel('plugins');
		$plugins = 
			isset($options['plugins'])
			? explode(',', $options['plugins'])
			: array();

		$results = array();
		if (!class_exists('Remote_Upgrader_Skin'))
			require_once(dirname(__FILE__) . '/class-remote-upgrader-skin.php');

		// get active plugins
		$active_plugins = array();
		foreach ( (array) $plugins as $plugin ) {
			$plugin_active = is_plugin_active( $plugin );
			$plugin_network_active =
				$this->is_multisite() && function_exists('is_plugin_active_for_network')
				? is_plugin_active_for_network( $plugin )
				: false;
			if ($plugin_active || $plugin_network_active) {
				$active_plugins[$plugin] = array(
					'active'  => $plugin_active ,
					'network' => $plugin_network_active ,
					);
			}
		}

		// deactivate plugins & maintenance mode ON
		$maintenance = false;
		if (count($active_plugins) > 0) {
			deactivate_plugins(array_keys($active_plugins), true);
		}

		// get plugins update information
		wp_update_plugins();
		$current_option = $this->get_transient('update_plugins');

		// plugins upgrade
		foreach ( (array) $plugins as $plugin ) {
			try {
				ob_start();

				// plugin upgrade
				$this->set_transient('update_plugins', $current_option);
				$upgrader = new Plugin_Upgrader( new Remote_Upgrader_Skin( null ) );
				$result   = $upgrader->upgrade($plugin);
				$output   = isset($upgrader->output) ? (array)$upgrader->output : array();

				// plugin activate
				if ( isset($active_plugins[$plugin]) ) {
					activate_plugin( $plugin, '', $active_plugins[$plugin]['network'], true );
				}

				// get errors
				$error = (isset($upgrader->error) ? $upgrader->error : NULL);
				if ( isset($upgrader->skin) && isset($upgrader->skin->result) && isset($upgrader->skin->result->errors) ) {
					$errors = array();
					foreach ( $upgrader->skin->result->errors as $val ) {
						$errors[] = implode(',', (array)$val);
					}
					if ( count($errors) > 0 ) {
						$error = implode(',', $errors);
					}
					unset($errors);
				}
				if ( !empty($error) ) {
					$result = false;
				} else {
					$result = true;
					$error = NULL;
				}

				$output[] = ob_get_clean();

				$results[] = array(
					'name'   => $plugin ,
					'result' => $result ,
					'active' => is_plugin_active( $plugin ) ,
					'active_for_network' => function_exists('is_plugin_active_for_network') ? is_plugin_active_for_network( $plugin ) : false ,
					'output' => count($output) > 0 ? $output : NULL ,
					'error'  => $error ,
					);
			} catch(Exception $e) {
				$results[] = array(
					'name'   => $plugin ,
					'result' => false ,
					'active' => is_plugin_active( $plugin ) ,
					'active_for_network' => function_exists('is_plugin_active_for_network') ? is_plugin_active_for_network( $plugin ) : false ,
					'output' => NULL ,
					'error'  => $e->getMessage() ,
					);
			}
		}

		return $results;
	}

	// themes upgrade
	public function wp_ThemesUpdate( $request, $userid, $username ) {
		if ( ($options = $this->get_parameters( $request )) === false )
			return $this->error;

		if ( $this->wp_filesystem() === false )
			return $this->error;

		if ( ! $this->current_user_can( 'update_themes' ) )
			return new WP_Error( 403, __( 'You do not have sufficient permissions to update this site.' ) );

		$this->disabled_update_cancel('themes');
		$themes = 
			isset($options['themes'])
			? explode(',', $options['themes'])
			: array();

		// get themes update information
		wp_update_themes();
		$current_option = $this->get_transient('update_themes');

		$results = array();
		if (!class_exists('Remote_Upgrader_Skin'))
			require_once(dirname(__FILE__) . '/class-remote-upgrader-skin.php');
		foreach ( (array) $themes as $theme ) {
			try {
				ob_start();

				// theme upgrade
				$this->set_transient('update_themes', $current_option);
				$upgrader = new Theme_Upgrader( new Remote_Upgrader_Skin( null ) );
				$result = $upgrader->upgrade($theme);
				$output = isset($upgrader->output) ? (array)$upgrader->output : array();

				// get errors
				$error = (isset($upgrader->error) ? $upgrader->error : NULL);
				if ( isset($upgrader->skin) && isset($upgrader->skin->result) && isset($upgrader->skin->result->errors) ) {
					$errors = array();
					foreach ( $upgrader->skin->result->errors as $val ) {
						$errors[] = implode(',', (array)$val);
					}
					if ( count($errors) > 0 ) {
						$error = implode(',', $errors);
					}
					unset($errors);
				}
				if ( !empty($error) ) {
					$result = false;
				} else {
					$result = true;
					$error = NULL;
				}

				$output[] = ob_get_clean();

				$results[] = array(
					'name'   => $theme ,
					'result' => $result ,
					'output' => count($output) > 0 ? $output : NULL ,
					'error'  => $error ,
					);
			} catch(Exception $e) {
				$results[] = array(
					'name'   => $theme ,
					'result' => false ,
					'output' => NULL ,
					'error'  => $e->getMessage() ,
					);
			}
		}

		return $results;
	}
}
