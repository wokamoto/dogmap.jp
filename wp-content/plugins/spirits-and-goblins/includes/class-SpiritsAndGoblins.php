<?php
if ( !class_exists('otp') )
	require(dirname(__FILE__).'/php-otp-1.1.1/class.otp.php');
if ( !class_exists('InputValidator') )
	require(dirname(__FILE__).'/class-InputValidator.php');

class SpiritsAndGoblins {
	const TEXT_DOMAIN = 'spirits-and-goblins';
	const DEBUG_MODE = false;

	const DEFAULT_SEQ = 100;
	const OTP_ALGORITHM = 'sha1';
	const OTP_LENGTH = 6;
	const OTP_EXPIRES = 300;
	const SEND_OPTION = 'mail';
	const META_KEY_SEED = 'sag_seed';
	const META_KEY_SEQ = 'sag_seq';

	const TWILIO_VERSION = '2010-04-01';

	static $instance;

	private $options = array();

	function __construct($options = null) {
		self::$instance = $this;

		$this->options = isset($options) ? $options : SpiritsAndGoblins_Admin::get_option();

		add_action('login_form', array($this, 'login_form'));
		add_action('login_form_otp', array($this, 'login_form_otp'));
		add_action('login_form_login', array($this, 'login_form_login'));
		if ($this->options['send_option'] === 'sms') {
			add_action('register_form', array($this, 'register_form'));
			add_action('user_register', array($this, 'user_register'));
		}
	}

	public function activate(){
	}

	public function deactivate(){
		global $wpdb;

		$wpdb->query($wpdb->prepare(
			"delete from {$wpdb->usermeta} where meta_key like %s or meta_key like %s",
			'%'.self::META_KEY_SEED,
			'%'.self::META_KEY_SEQ
			));
	}

	public function login_form_login(){
		global $action;

		$user_login = isset($_POST['log']) ? $_POST['log'] : '';
		$user_pwd   = isset($_POST['pwd']) ? $_POST['pwd'] : '';
		$user = wp_authenticate($user_login, $user_pwd);
		if (is_wp_error($user))
			return;

		if ( $action !== 'otp')
			wp_die(__('Unknown error!', self::TEXT_DOMAIN));
	}

	public function login_form(){
		echo '<input type="hidden" name="action" value="otp" />'."\n";
	}

	public function user_register($user_id){
		if ($this->options['send_option'] !== 'sms')
			return;

		$iv = new InputValidator('POST');
		$iv->set_rules(SpiritsAndGoblins_Admin::USER_META_COUNTRY, array('trim','esc_html'));
		$iv->set_rules(SpiritsAndGoblins_Admin::USER_META_PHONE,   array('trim','esc_html', 'tel'));

		if ($iv->input(SpiritsAndGoblins_Admin::USER_META_COUNTRY))
			update_user_meta($user_id, SpiritsAndGoblins_Admin::USER_META_COUNTRY, $iv->input(SpiritsAndGoblins_Admin::USER_META_COUNTRY));
		if ($iv->input(SpiritsAndGoblins_Admin::USER_META_PHONE))
			update_user_meta($user_id, SpiritsAndGoblins_Admin::USER_META_PHONE, $iv->input(SpiritsAndGoblins_Admin::USER_META_PHONE));

		unset($iv);
	}

	public function register_form(){
		if ($this->options['send_option'] !== 'sms')
			return;

		if (!class_exists('CountryNameToCountryCodeMap'))
			require(dirname(__FILE__).'/class-CountryNameToCountryCodeMap.php');
		$contry_code = CountryNameToCountryCodeMap::$countryNameToCountryCodeMap;

		$iv = new InputValidator('POST');
		$iv->set_rules(SpiritsAndGoblins_Admin::USER_META_COUNTRY, array('trim','esc_html'));
		$iv->set_rules(SpiritsAndGoblins_Admin::USER_META_PHONE,   array('trim','esc_html', 'tel'));
		$country = 
			$iv->input(SpiritsAndGoblins_Admin::USER_META_COUNTRY)
			? $iv->input(SpiritsAndGoblins_Admin::USER_META_COUNTRY)
			: SpiritsAndGoblins_Admin::default_country();
		$phone_number =
			$iv->input(SpiritsAndGoblins_Admin::USER_META_PHONE)
			? $iv->input(SpiritsAndGoblins_Admin::USER_META_PHONE)
			: '';
		unset($iv);
?>
	<p>
		<label for="<?php echo SpiritsAndGoblins_Admin::USER_META_COUNTRY; ?>"><?php _e('Country', self::TEXT_DOMAIN); ?></label>
		<select name="<?php echo SpiritsAndGoblins_Admin::USER_META_COUNTRY; ?>">
		<option value=""></option>
<?php foreach ($contry_code as $name => $code) { ?>

		<option value="<?php echo esc_attr($code); ?>"<?php echo $code == $country ? ' selected' : ''; ?>><?php echo $name; ?></option>
<?php } ?>
	</select></p>
	<p>
		<label for="user_email"><?php _e('Phone number', self::TEXT_DOMAIN); ?><br />
		<input type="text" name="<?php echo SpiritsAndGoblins_Admin::USER_META_PHONE; ?>" id="<?php echo SpiritsAndGoblins_Admin::USER_META_PHONE; ?>" class="input" value="<?php echo esc_attr($phone_number); ?>" size="25" /></label>
	</p>
<?php
	}

	public function login_form_otp() {
		$secure_cookie = '';
		$interim_login = isset($_REQUEST['interim-login']);
		$customize_login = isset($_REQUEST['customize-login']);
		if ( $customize_login )
			wp_enqueue_script( 'customize-base' );

		$otpass = isset($_POST['otp']) ? intval($_POST['otp']) : false;
		$rememberme = isset($_POST['rememberme']) ? intval($_POST['rememberme']) : '';

		// get user
		if ( !$otpass ) {
			$user_login = isset($_POST['log']) ? $_POST['log'] : '';
			$user_pwd   = isset($_POST['pwd']) ? $_POST['pwd'] : '';
			$user = wp_authenticate($user_login, $user_pwd);
		} else {
			$user_login = '';
			if ( isset($_POST['log']) ) {
				$user_login = sanitize_user($_POST['log']);
				$user = get_user_by('login', $user_login);
			}
		}
		if ( !isset($user) || is_wp_error($user) )
			return;

		if ( !empty($user_login) && !force_ssl_admin() && $user && get_user_option('use_ssl', $user->ID) ) {
			$secure_cookie = true;
			force_ssl_admin(true);
		}

		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$redirect_to = $_REQUEST['redirect_to'];
			// Redirect to https if user wants ssl
			if ( $secure_cookie && false !== strpos($redirect_to, 'wp-admin') )
				$redirect_to = preg_replace('|^http://|', 'https://', $redirect_to);
		} else {
			$redirect_to = admin_url();
		}
		$redirect_to = apply_filters('login_otp_redirect', $redirect_to);

		if ( !$secure_cookie && is_ssl() && force_ssl_login() && !force_ssl_admin() && ( 0 !== strpos($redirect_to, 'https') ) && ( 0 === strpos($redirect_to, 'http') ) )
			$secure_cookie = false;

		// verify One-Time Password
		$verify_otp = $this->verify_otp($user, $otpass);
		if ( !is_wp_error($verify_otp) ) {
			wp_set_auth_cookie($user->ID, $rememberme, $secure_cookie);
			do_action('wp_login', $user_login, $user);
			if ( $interim_login ) {
				$message = '<p class="message">' . __('You have logged in successfully.') . '</p>';
				login_header( '', $message );
?>

			<?php if ( ! $customize_login ) : ?>
			<script type="text/javascript">setTimeout( function(){window.close()}, 8000);</script>
			<p class="alignright">
			<input type="button" class="button-primary" value="<?php esc_attr_e('Close'); ?>" onclick="window.close()" /></p>
			<?php endif; ?>
			</div>
			<?php do_action( 'login_footer' ); ?>
			<?php if ( $customize_login ) : ?>
				<script type="text/javascript">setTimeout( function(){ new wp.customize.Messenger({ url: '<?php echo wp_customize_url(); ?>', channel: 'login' }).send('login') }, 1000 );</script>
			<?php endif; ?>
			</body></html>
<?php
					exit;
			}

			if ( ( empty( $redirect_to ) || $redirect_to == 'wp-admin/' || $redirect_to == admin_url() ) ) {
				// If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
				if ( is_multisite() && !get_active_blog_for_user($user->ID) && !is_super_admin( $user->ID ) )
					$redirect_to = user_admin_url();
				elseif ( is_multisite() && !$user->has_cap('read') )
					$redirect_to = get_dashboard_url( $user->ID );
				elseif ( !$user->has_cap('edit_posts') )
					$redirect_to = admin_url('profile.php');
			}
			wp_safe_redirect($redirect_to);
			exit();
		}

		// send One-Time Password
		$this->send_otp($user);

		// One-Time Password form
		login_header(__('Log In'), '', $otpass ? $verify_otp : null);
?>

<form name="loginform" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post">
	<p>
		<label for="user_otp"><?php _e('One-Time Password', self::TEXT_DOMAIN) ?><br />
		<input type="text" name="otp" id="user_otp" class="input" value="<?php echo esc_attr($otpass); ?>" size="20" /></label>
	</p>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In'); ?>" />
<?php	if ( $interim_login ) { ?>
		<input type="hidden" name="interim-login" value="1" />
<?php	} else { ?>
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
<?php 	} ?>
<?php   if ( $customize_login ) : ?>
		<input type="hidden" name="customize-login" value="1" />
<?php   endif; ?>
		<input type="hidden" name="action" value="otp" />
		<input type="hidden" name="log" id="user_login" value="<?php echo esc_attr($user_login); ?>" />
		<input type="hidden" name="rememberme" id="rememberme" value="<?php echo esc_attr($rememberme); ?>" />
	</p>
</form>

<script type="text/javascript">
setTimeout( function(){ try{d = document.getElementById('user_otp');d.focus();d.select();} catch(e){}}, 200);
if(typeof wpOnload=='function')wpOnload();
</script>

<?php
		login_footer();
		exit();
	}

	private function verify_otp($user, $otpass) {
		if ( !$this->verify_user($user) )
			return new WP_Error('not_logged_in', __('not logged in', self::TEXT_DOMAIN));

		$seq = $this->get_user_meta_transient($user->ID, self::META_KEY_SEQ);
		if ( !$seq ) {
			$seq = self::DEFAULT_SEQ;
			$this->set_user_meta_transient($user->ID, self::META_KEY_SEQ, $seq, $this->options['otp_expires']);
		}
		$seq = intval($seq);

		$otpass = intval($otpass);
		$otpass_org = intval($this->get_otp($user, $seq));
		if ($otpass) {
			$seq--;
			if (!$seq) {
				$otp = new otp();
				$this->set_user_meta_transient($user->ID, self::META_KEY_SEED, $otp->generateSeed(), $this->options['otp_expires']);
				$this->set_user_meta_transient($user->ID, self::META_KEY_SEQ, self::DEFAULT_SEQ, $this->options['otp_expires']);
			} else {
				$this->set_user_meta_transient($user->ID, self::META_KEY_SEQ, $seq, $this->options['otp_expires']);
			}
		}

		return
			$otpass === $otpass_org
			? true
			: new WP_Error('otp_error', sprintf(__('One-Time Password incorrect for %s', self::TEXT_DOMAIN), $user->get('display_name')));
	}

	private function get_otp($user, $seq = false) {
		if ( !$this->verify_user($user) )
			return false;

		$otp = new otp();

		$seed = $this->get_user_meta_transient($user->ID, self::META_KEY_SEED);
		if ( !$seed ) {
			$seed = $otp->generateSeed();
			$this->set_user_meta_transient($user->ID, self::META_KEY_SEED, $seed, $this->options['otp_expires']);
		}

		if ( !$seq ) {
			$seq = $this->get_user_meta_transient($user->ID, self::META_KEY_SEQ);
			if ( !$seq ) {
				$seq = self::DEFAULT_SEQ;
				$this->set_user_meta_transient($user->ID, self::META_KEY_SEQ, $seq, $this->options['otp_expires']);
			}
		}
		$seq = intval($seq);

		if ( $pass = $otp->generateOtp($this->pass_phrase(), $seed, $seq, self::OTP_ALGORITHM) ) {
			$pass_dec = sprintf('%0'.$this->options['otp_length'].'d', abs(hexdec($pass['hex_otp']) % pow(10, $this->options['otp_length'])));
			//update_user_meta($user->ID, 'sag_otp', array('dec_otp' => $pass_dec, 'seequence_count' => $seq));
			return $pass_dec;
		} else {
			return false;
		}
	}

	private function pass_phrase() {
		return
			( defined('AUTH_SALT')  && AUTH_SALT !== 'put your unique phrase here' )
			? AUTH_SALT
			: ( defined('COOKIEHASH') ? COOKIEHASH : md5(get_site_option('siteurl')) );
	}

	private function verify_user($user){
		return ($user && !is_wp_error($user) && $user->exists());
	}

	private function send_otp($user) {
		if ( !$this->verify_user($user) )
			return false;

		if ( is_multisite() )
			$blogname = $GLOBALS['current_site']->site_name;
		else
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$title = sprintf(__('[%s] Your One-Time Password', self::TEXT_DOMAIN), $blogname);

		$message = $this->get_otp($user);

		$send_option = $this->options['send_option'];
		if (empty($this->options['twilio_sid']) || empty($this->options['twilio_token']) || empty($this->options['twilio_phone']))
			$send_option = 'mail';
		if ($send_option === 'sms' || $send_option === 'tel') {
			$country = get_user_meta( $user->ID, SpiritsAndGoblins_Admin::USER_META_COUNTRY, true );
			$phone_number = get_user_meta( $user->ID, SpiritsAndGoblins_Admin::USER_META_PHONE, true );
			if ( !$country || !$phone_number )
				$send_option = 'mail';
		}

		switch($send_option){
		case 'sms':
			$result = $this->sms($country, $phone_number, "{$title}: {$message}", $this->options);
			if (self::DEBUG_MODE)
				var_dump($result);
			if (is_wp_error($result))
				wp_mail($user->user_email, $title, $message);
			break;
		case 'mail':
		default:
			wp_mail($user->user_email, $title, $message);
			break;
		}
	}

	private function twilio($sid, $token){
		if ( !class_exists('Services_Twilio') )
			require dirname(__FILE__).'/twilio-php/Services/Twilio.php';
		return new Services_Twilio($sid, $token, self::TWILIO_VERSION);
	}

	private function phone_number($country, $phone_number){
		if ( !class_exists('com\google\i18n\phonenumbers\PhoneNumberUtil') )
			require dirname(__FILE__).'/libphonenumber-for-PHP/PhoneNumberUtil.php';
		$phone_util = com\google\i18n\phonenumbers\PhoneNumberUtil::getInstance();
		try {
			$phone_number_proto = $phone_util->parse($phone_number, strtoupper($country));
			$phone_number = $phone_util->format($phone_number_proto, com\google\i18n\phonenumbers\PhoneNumberFormat::E164);
			return $phone_number;
		} catch (Exception $e) {
			return new WP_Error('phone_number_error', $e->getMessage());
		}
		return $phone_number;
	}

	private function sms($country, $phone_number, $message, $args = array()){
		try {
			$phone_number = $this->phone_number($country, $phone_number);
			if (is_wp_error($phone_number))
				return $phone_number;
			$client = $this->twilio($args['twilio_sid'], $args['twilio_token']);
			$message = $client->account->sms_messages->create(
				$args['twilio_phone'],
				$phone_number,
				$message
				);
			return "Success: {$message->sid} - {$message->body}\n";
		} catch (Exception $e) {
			return new WP_Error('twilio_error', $e->getMessage());
		}
	}

	/**
	 * Delete a user meta transient.
	 */
	private function delete_user_meta_transient( $user_id, $transient ) {
		global $_wp_using_ext_object_cache;

		$user_id = (int) $user_id;

		do_action( 'delete_user_meta_transient_' . $transient, $user_id, $transient );

		if ( $_wp_using_ext_object_cache ) {
			$result = wp_cache_delete( "{$transient}-{$user_id}", "user_meta_transient-{$user_id}" );
		} else {
			$meta_timeout = '_transient_timeout_' . $transient;
			$meta = '_transient_' . $transient;
			$result = delete_user_meta( $user_id, $meta );
			if ( $result )
				delete_user_meta( $user_id, $meta_timeout );
		}

		if ( $result )
			do_action( 'deleted_user_meta_transient', $transient, $user_id, $transient );
		return $result;
	}

	/**
	 * Get the value of a user meta transient.
	 */
	private function get_user_meta_transient( $user_id, $transient ) {
		global $_wp_using_ext_object_cache;

		$user_id = (int) $user_id;

		if (has_filter('pre_user_meta_transient_' . $transient)) {
			$pre = apply_filters( 'pre_user_meta_transient_' . $transient, $user_id, $transient );
			if ( false !== $pre )
				return $pre;
		}

		if ( $_wp_using_ext_object_cache ) {
			$value = wp_cache_get( "{$transient}-{$user_id}", "user_meta_transient-{$user_id}" );
		} else {
			$meta_timeout = '_transient_timeout_' . $transient;
			$meta = '_transient_' . $transient;
			$value = get_user_meta( $user_id, $meta, true );
			if ( $value && ! defined( 'WP_INSTALLING' ) ) {
				if ( get_user_meta( $user_id, $meta_timeout, true ) < time() ) {
					$this->delete_user_meta_transient( $user_id, $transient );
					return false;
				}
			}
		}

		return 
			has_filter('user_meta_transient_' . $transient)
			? apply_filters('user_meta_transient_' . $transient, $value, $user_id)
			: $value;
	}

	/**
	 * Set/update the value of a user meta transient.
	 */
	function set_user_meta_transient( $user_id, $transient, $value, $expiration = 0 ) {
		global $_wp_using_ext_object_cache;

		$user_id = (int) $user_id;

		if (has_filter('pre_set_user_meta_transient_' . $transient)) {
			$value = apply_filters( 'pre_set_user_meta_transient_' . $transient, $value, $user_id, $transient );
		}

		if ( $_wp_using_ext_object_cache ) {
			$result = wp_cache_set( "{$transient}-{$user_id}", $value, "user_meta_transient-{$user_id}", $expiration );
		} else {
			$meta_timeout = '_transient_timeout_' . $transient;
			$meta = '_transient_' . $transient;
			if ( $expiration ) {
				update_user_meta( $user_id, $meta_timeout, time() + $expiration );
			}
			$result = update_user_meta( $user_id, $meta, $value );
		}
		if ( $result ) {
			do_action( 'set_user_meta_transient_' . $transient, $user_id, $transient );
			do_action( 'setted_user_meta_transient', $transient, $user_id, $transient );
		}
		return $result;
	}
}
