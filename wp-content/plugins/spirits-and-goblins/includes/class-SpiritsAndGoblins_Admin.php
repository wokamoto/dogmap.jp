<?php
if ( !class_exists('InputValidator') )
	require(dirname(__FILE__).'/class-InputValidator.php');

class SpiritsAndGoblins_Admin {
	const OPTION_KEY  = 'spirits-and-goblins';
	const OPTION_PAGE = 'spirits-and-goblins';

	const USER_META_COUNTRY = 'country';
	const USER_META_PHONE = 'phone_number';

	const DEFAULT_COUNTRY = 'US';

	static $instance;

	private $options = array();
	private $plugin_basename;
	private $admin_hook, $admin_action;

	private function __construct() {}

	public static function get_instance() {
		if( !isset( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c();    
		}
		return self::$instance;
	}

	public function init() {
		$this->options = self::get_option();
		$this->plugin_basename = plugin_basename(dirname(dirname(__FILE__)).'/plugin.php');

		add_action('admin_menu', array($this, 'admin_menu'));
		add_filter('plugin_action_links', array($this, 'plugin_setting_links'), 10, 2 );

		if ($this->options['send_option'] === 'sms') {
			add_action('show_user_profile', array($this, 'edit_user_profile'));
			add_action('edit_user_profile', array($this, 'edit_user_profile'));
			add_action('personal_options_update', array($this, 'edit_user_profile_update'));
			add_action('edit_user_profile_update', array($this, 'edit_user_profile_update'));
		}
	}

	static public function option_keys(){
		return array(
			'otp_length'   => __('One-Time Password length', SpiritsAndGoblins::TEXT_DOMAIN),
			'otp_expires'  => __('One-Time Password expires (sec)', SpiritsAndGoblins::TEXT_DOMAIN),
			'send_option'  => __('Send option', SpiritsAndGoblins::TEXT_DOMAIN),
			'twilio_sid'   => __('Twilio sid', SpiritsAndGoblins::TEXT_DOMAIN),
			'twilio_token' => __('Twilio token', SpiritsAndGoblins::TEXT_DOMAIN),
			'twilio_phone' => __('Twilio phone number', SpiritsAndGoblins::TEXT_DOMAIN),
			);
	}

	static public function get_option(){
		$options = get_option(self::OPTION_KEY);
		foreach (array_keys(self::option_keys()) as $key) {
			if (!isset($options[$key]) || is_wp_error($options[$key])) {
				switch($key){
				case 'otp_length';
					$options[$key] = SpiritsAndGoblins::OTP_LENGTH;
					break;
				case 'otp_expires';
					$options[$key] = SpiritsAndGoblins::OTP_EXPIRES;
					break;
				case 'send_option';
					$options[$key] = SpiritsAndGoblins::SEND_OPTION;
					break;
				default:
					$options[$key] = '';
				}
			}
		}
		return $options;
	}

	static public function default_country(){
		$country = __('default country', SpiritsAndGoblins::TEXT_DOMAIN);
		return
			$country !== 'default country'
			? strtoupper($country)
			: self::DEFAULT_COUNTRY;
	}

	//**************************************************************************************
	// Add Admin Menu
	//**************************************************************************************
	public function admin_menu() {
		global $wp_version;

		$title = __('Spirits and Goblins', SpiritsAndGoblins::TEXT_DOMAIN);
		$this->admin_hook = add_options_page($title, $title, 'manage_options', self::OPTION_PAGE, array($this, 'options_page'));
		$this->admin_action = admin_url('/options-general.php') . '?page=' . self::OPTION_PAGE;
	}

	public function options_page(){
		$nonce_action  = 'update_options';
		$nonce_name    = '_wpnonce_update_options';

		$option_keys   = $this->option_keys();
		$this->options = $this->get_option();
		$title = __('Spirits and Goblins', SpiritsAndGoblins::TEXT_DOMAIN);

		$iv = new InputValidator('POST');
		$iv->set_rules($nonce_name, 'required');

		// Update options
		if (!is_wp_error($iv->input($nonce_name)) && check_admin_referer($nonce_action, $nonce_name)) {
			// Get posted options
			$fields = array_keys($option_keys);
			foreach ($fields as $field) {
				switch ($field) {
				case 'otp_length':
				case 'otp_expires':
					$iv->set_rules($field, array('trim','esc_html','numeric','required'));
					break;
				case 'send_option':
					$iv->set_rules($field, array('trim','esc_html','required'));
					break;
				default:
					$iv->set_rules($field, array('trim','esc_html'));
					break;
				}
			}
			$options = $iv->input($fields);
			$err_message = '';
			foreach ($option_keys as $key => $field) {
				if (is_wp_error($options[$key])) {
					$error_data = $options[$key];
					$err = '';
					foreach ($error_data->errors as $errors) {
						foreach ($errors as $error) {
							$err .= (!empty($err) ? '<br />' : '') . __('Error! : ', SpiritsAndGoblins::TEXT_DOMAIN);
							$err .= sprintf(
								__(str_replace($key, '%s', $error), SpiritsAndGoblins::TEXT_DOMAIN),
								$field
								);
						}
					}
					$err_message .= (!empty($err_message) ? '<br />' : '') . $err;
				}
				if (!isset($options[$key]) || is_wp_error($options[$key]))
					$options[$key] = '';
			}
			if (SpiritsAndGoblins::DEBUG_MODE && function_exists('dbgx_trace_var')) {
				dbgx_trace_var($options);
			}

			// Update options
			if ($this->options !== $options) {
				update_option(self::OPTION_KEY, $options);
				printf(
					'<div id="message" class="updated fade"><p><strong>%s</strong></p></div>'."\n",
					empty($err_message) ? __('Done!', SpiritsAndGoblins::TEXT_DOMAIN) : $err_message
					);
				$this->options = $options;
			}
			unset($options);
		}

?>
		<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php echo esc_html( $title ); ?></h2>
		<form method="post" action="<?php echo $this->admin_action;?>">
		<?php echo wp_nonce_field($nonce_action, $nonce_name, true, false) . "\n"; ?>
		<table class="wp-list-table fixed"><tbody>
		<?php foreach ($option_keys as $field => $label) { $this->input_field($field, $label); } ?>
		</tbody></table>
		<?php submit_button(); ?>
		</form>
		</div>
<?php

		unset($iv);
	}

	private function input_field($field, $label, $args = array()){
		extract($args);

		$label = sprintf('<th><label for="%1$s">%2$s</label></th>'."\n", $field, $label);

		switch ($field) {
		case 'send_option':
			$input_field  = sprintf('<td><select name="%1$s">', $field);
			$input_field .= '<option value=""></option>';
			$send_options = array(
				'mail' => __('EMail', SpiritsAndGoblins::TEXT_DOMAIN),
				'sms'  => __('Short Message', SpiritsAndGoblins::TEXT_DOMAIN),
				);
			foreach ($send_options as $key => $val) {
				$input_field .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr($key),
					$key == $this->options[$field] ? ' selected' : '',
					$val);
			}
			$input_field .= '</select>';
			$input_field .= '<p>';
			$input_field .= __('When you use Short Message of Send option, you have to sign up <a href="http://www.twilio.com/" title="Twilio">Twilio</a> and get Twilio sid, Twilio token, and Twilio phone number.', SpiritsAndGoblins::TEXT_DOMAIN).'<br />';
			$input_field .= sprintf(
				__('Also you need to put in your country and your cell phone number at the <a href="%s">user profile</a>.', SpiritsAndGoblins::TEXT_DOMAIN),
				admin_url('profile.php'));
			$input_field .= '</p>';
			$input_field .= '</td>';
			break;
		default:
			$input_field = sprintf('<td><input type="text" name="%s" value="%2$s" id="%1$s" size=100 /></td>'."\n", $field, esc_attr($this->options[$field]));
		}

		echo "<tr>\n{$label}{$input_field}</tr>\n";
	}

	//**************************************************************************************
	// edit user profile
	//**************************************************************************************
	public function edit_user_profile($user) {
		if ($this->options['send_option'] !== 'sms')
			return;

		if (!class_exists('CountryNameToCountryCodeMap'))
			require(dirname(__FILE__).'/class-CountryNameToCountryCodeMap.php');
		$contry_code = CountryNameToCountryCodeMap::$countryNameToCountryCodeMap;

		$country = get_user_meta($user->ID, self::USER_META_COUNTRY, true);
		$phone_number = get_user_meta($user->ID, self::USER_META_PHONE, true);
		if (!$country)
			$country = self::default_country();
?>
<table class="form-table" id="phone">
<tbody>
<tr>
	<th><label for="<?php echo self::USER_META_COUNTRY; ?>"><?php _e('Country', SpiritsAndGoblins::TEXT_DOMAIN); ?></label></th>
	<td><select name="<?php echo self::USER_META_COUNTRY; ?>">
		<option value=""></option>
<?php foreach ($contry_code as $name => $code) { ?>

		<option value="<?php echo esc_attr($code); ?>"<?php echo $code == $country ? ' selected' : ''; ?>><?php echo $name; ?></option>
<?php } ?>
	</select></td>
	</td>
</tr>
<tr>
	<th><label for="<?php echo self::USER_META_COUNTRY; ?>"><?php _e('Phone number', SpiritsAndGoblins::TEXT_DOMAIN); ?></label></th>
	<td>
		<input type="text" name="<?php echo self::USER_META_PHONE; ?>" id="<?php echo self::USER_META_PHONE; ?>" value="<?php echo esc_attr($phone_number); ?>" class="regular-text code" />
	</td>
</tr>
</tbody>
</table>
<script type="text/javascript">
jQuery(function($){$('#email').parent().parent().after($('table#phone tr'));$('table#phone').remove();});
</script>
<?php
	}

	public function edit_user_profile_update($user_id){
		if ($this->options['send_option'] !== 'sms')
			return;

		$iv = new InputValidator('POST');
		$iv->set_rules(self::USER_META_COUNTRY, array('trim','esc_html'));
		$iv->set_rules(self::USER_META_PHONE,   array('trim','esc_html', 'tel'));

		if ($iv->input(self::USER_META_COUNTRY))
			update_user_meta($user_id, self::USER_META_COUNTRY, $iv->input(self::USER_META_COUNTRY));
		else
			delete_user_meta($user_id, self::USER_META_COUNTRY);

		if ($iv->input(self::USER_META_PHONE))
			update_user_meta($user_id, self::USER_META_PHONE, $iv->input(self::USER_META_PHONE));
		else
			delete_user_meta($user_id, self::USER_META_PHONE);

		unset($iv);
	}

	//**************************************************************************************
	// Add setting link
	//**************************************************************************************
	public function plugin_setting_links($links, $file) {
		if ($file === $this->plugin_basename) {
			$settings_link = '<a href="' . $this->admin_action . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links
		}

		return $links;
	}
}