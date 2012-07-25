<?php
/*
http://sevengoslings.net/~fangel/oauth-sp-guide.html
*/
if (!class_exists('OP_OAuthException'))
	require_once 'class-oauth.php';

class WP_OAuthServer extends OP_OAuthServer {
	function __construct($data_store) {
		parent::__construct($data_store);
	}

	public function get_expired() {
		return (time() - $this->timestamp_threshold);
	}
}

class WP_OAuthConsumer extends OP_OAuthConsumer {
	public $id;
	public $name;

	function __construct($id, $key, $secret, $name=NULL, $callback_url=NULL) {
		parent::__construct($key, $secret, $callback_url);
		$this->id = $id;
		$this->name = $name;
	}

	function __toString() {
		return parent::__toString();
	}
}

class WP_OAuthToken extends OP_OAuthToken {
	public $userid;
	public $authorized;

	function __construct($key, $secret, $userid = NULL, $authorized = NULL) {
		parent::__construct($key, $secret);
		$this->userid = $userid;
		$this->authorized = $authorized;
	}

	function __toString() {
		return parent::__toString();
	}
}

class WP_OAuthDataStore extends OP_OAuthDataStore {
	private $db;
	private $api_tables;

	function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->api_tables = array(
			'consumers'      => $wpdb->prefix . 'oauth_api_consumers',
			'request_tokens' => $wpdb->prefix . 'oauth_api_request_tokens',
			'access_tokens'  => $wpdb->prefix . 'oauth_api_access_tokens',
			'nonces'         => $wpdb->prefix . 'oauth_api_nonces',
			);
	}

	public function get_api_tables() {
		return $this->api_tables;
	}

	// create tables
	public function create_tables() {
		$charset_collate = '';
		if ( $this->db->has_cap( 'collation' ) ) {
			if ( ! empty( $this->db->charset ) )
				$charset_collate = 'DEFAULT CHARACTER SET ' . $this->db->charset;
			if ( ! empty( $this->db->collate ) )
				$charset_collate .= ' COLLATE ' . $this->db->collate;
		}

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS {$this->api_tables['consumers']} (
			id bigint(20) UNSIGNED NOT NULL auto_increment,
			name tinytext NOT NULL,
			oauthkey tinytext NOT NULL,
			secret tinytext NOT NULL,
			description tinytext NOT NULL DEFAULT '',
			PRIMARY KEY (id)
			) $charset_collate;" );

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS {$this->api_tables['request_tokens']} (
			id bigint(20) UNSIGNED NOT NULL auto_increment,
			consumerid bigint(20) NOT NULL,
			oauthkey tinytext NOT NULL,
			secret tinytext  NOT NULL,
			authorized tinyint(1) NOT NULL,
			userid bigint(20) UNSIGNED NULL,
			PRIMARY KEY (id)
			) $charset_collate;" );

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS {$this->api_tables['access_tokens']} (
			id bigint(20) UNSIGNED NOT NULL auto_increment,
			consumerid bigint(20) UNSIGNED NOT NULL,
			oauthkey tinytext NOT NULL,
			secret tinytext NOT NULL,
			userid bigint(20) UNSIGNED NOT NULL,
			PRIMARY KEY (id)
			) $charset_collate;" );

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS {$this->api_tables['nonces']} (
			id bigint(20) UNSIGNED NOT NULL auto_increment,
			consumerid bigint(20) UNSIGNED NOT NULL,
			token tinytext NOT NULL,
			nonce tinytext NOT NULL,
			timestamp int(10) UNSIGNED NOT NULL,
			PRIMARY KEY (id)
			) $charset_collate;" );

	}

	// drop tables
	public function drop_tables() {
		foreach ($this->api_tables as $key => $table) {
			$this->db->query( "DROP TABLE IF EXISTS {$table};" );
		}
	}

	// get consumers info
	public function get_consumers( $consumerid = FALSE) {
		if ($consumerid === FALSE) {
			$consumers = $this->db->get_results("SELECT id, name, description, oauthkey, secret FROM {$this->api_tables['consumers']}");
		} else {
			$consumers = $this->db->get_results($this->db->prepare(
				"SELECT id, name, description, oauthkey, secret
				FROM {$this->api_tables['consumers']}
				WHERE id = %d" ,
				(int) $consumerid
				));
		}
		return $consumers;
	}

	// get access tokens info
	public function get_access_tokens( $userid ) {
		$access_tokens = $this->db->get_results($this->db->prepare(
			"SELECT a.id, a.consumerid, a.oauthkey, a.secret, a.userid, c.name, c.description
			FROM {$this->api_tables['access_tokens']} a, {$this->api_tables['consumers']} c
			WHERE c.id = a.consumerid
			AND a.userid = %d" ,
			$userid
			));
		return $access_tokens;
	}

	// get access tokens info
	public function get_all_access_tokens() {
		$access_tokens = $this->db->get_results(
			"SELECT a.id, a.consumerid, a.oauthkey, a.secret, a.userid, c.name, c.description
			FROM {$this->api_tables['access_tokens']} a, {$this->api_tables['consumers']} c
			WHERE c.id = a.consumerid"
			);
		return $access_tokens;
	}

	// lookup consumer from consumer_key
	public function lookup_consumer($consumerkey) {
		$consumers = $this->db->get_results($this->db->prepare(
			"SELECT id, oauthkey, secret, name
			FROM {$this->api_tables['consumers']}
			WHERE oauthkey = %s;" ,
			$consumerkey
			));
		$result = null;
		foreach ( (array) $consumers as $consumer ) {
			$result = new WP_OAuthConsumer( $consumer->id, $consumer->oauthkey, $consumer->secret, $consumer->name, NULL);
			break;
		}
		return $result;
	}

	// lookup consumer from consumer name
	private function lookup_consumer_from_name($name) {
		$consumers = $this->db->get_results($this->db->prepare(
			"SELECT id, oauthkey, secret, name
			FROM {$this->api_tables['consumers']}
			WHERE name = %s;" ,
			$name
			));
		$result = null;
		foreach ( (array) $consumers as $consumer ) {
			$result = new WP_OAuthConsumer( $consumer->id, $consumer->oauthkey, $consumer->secret, $consumer->name, NULL);
			break;
		}
		return $result;
	}

	// lookup consumer from request token
	public function lookup_consumer_from_request_token( $key ) {
		$result = NULL;
		$tokens = $this->db->get_results($this->db->prepare(
			"SELECT consumerid, oauthkey, secret
			FROM {$this->api_tables['request_tokens']}
			WHERE oauthkey = %s;" ,
			$key
			));
		$consumerid = NULL;
		foreach ( (array) $tokens as $token ) {
			$consumerid = $token->consumerid;
		}

		if ($consumerid) {
			$consumers = $this->db->get_results($this->db->prepare(
				"SELECT id, oauthkey, secret, name
				FROM {$this->api_tables['consumers']}
				WHERE id = %d;" ,
				(int) $consumerid
				));
			foreach ( (array) $consumers as $consumer ) {
				$result = new WP_OAuthConsumer( $consumer->id, $consumer->oauthkey, $consumer->secret, $consumer->name, NULL);
				break;
			}
		}

		if ($result)
			return $result;
		else
			throw new OP_OAuthException( 'Request Token not found!' );
	}

	// lookup request or access token
	public function lookup_token($consumer, $token_type, $tokenkey) {
		if (!isset($consumer->id))
			$consumer = $this->lookup_consumer($consumer->key);
		$consumerid = isset($consumer->id) ? $consumer->id : NULL;

		$sql = null;
		$result = null;

		if ($token_type === 'request' ) {
			$sql =
				"SELECT consumerid, oauthkey, secret, userid, authorized
				FROM {$this->api_tables['request_tokens']}
				WHERE oauthkey = %s;";
		} else if ( $token_type === 'access' ) {
			$sql =
				"SELECT consumerid, oauthkey, secret, userid
				FROM {$this->api_tables['access_tokens']}
				WHERE oauthkey = %s;";
		} else {
			return $result;
		}

		$tokens = $this->db->get_results($this->db->prepare($sql, $tokenkey));
		foreach ( (array) $tokens as $token ) {
			if ( $consumerid === $token->consumerid ) {
				$result = new WP_OAuthToken( $token->oauthkey, $token->secret, $token->userid, isset($token->authorized) ? $token->authorized : NULL );
				break;
			}
		}
		return $result;
	}

	// lookup nonce
	public function lookup_nonce($consumer, $token, $nonce, $timestamp) {
		if (!isset($consumer->id))
			$consumer = $this->lookup_consumer($consumer->key);
		$consumerid = isset($consumer->id) ? $consumer->id : NULL;
		$tokenkey = isset($token->key) ? $token->key : null;

		if ( !$consumerid )
			$consumerid = $this->lookup_consumer_id($consumerkey);

		$existing = $this->db->get_results($this->db->prepare(
			"SELECT consumerid, token, nonce, timestamp
			FROM {$this->api_tables['nonces']}
			WHERE consumerid = %d
			AND token = %s
			AND nonce = %s
			AND timestamp = %d;" ,
			(int) $consumerid ,
			$tokenkey ,
			$nonce ,
			(int) $timestamp
			));

		$result = null;
		if ( count($existing) <= 0 ) {
			$this->db->query($this->db->prepare(
				"INSERT INTO {$this->api_tables['nonces']} ( consumerid, token, nonce, timestamp )
				VALUES ( %d, %s, %s, %d );" ,
				(int) $consumerid ,
				$tokenkey ,
				$nonce ,
				(int) $timestamp
				));
			return FALSE;
		} else {
			return TRUE;
		}
	}

	// delete consumer
	public function delete_consumer($consumerid = 0) {
		if ( (int)$consumerid > 0 )
			$result = $this->db->get_results($this->db->prepare(
				"DELETE FROM {$this->api_tables['consumers']}
				WHERE id = %d;" ,
				(int) $consumerid
				));
		else
			$result = $this->db->get_results("DELETE FROM {$this->api_tables['consumers']}");

		return $result ? TRUE : FALSE;
	}

	// delete nonce
	public function delete_nonce($timestamp = 0) {
		if ( (int)$timestamp > 0 )
			$result = $this->db->get_results($this->db->prepare(
				"DELETE FROM {$this->api_tables['nonces']}
				WHERE timestamp < %d;" ,
				(int) $timestamp
				));
		else
			$result = $this->db->get_results("DELETE FROM {$this->api_tables['nonces']}");

		return $result ? TRUE : FALSE;
	}

	// allow request token
	public function allow_request_token($userid, $consumerid, $token_key) {
		return $this->db->query($this->db->prepare(
			"UPDATE {$this->api_tables['request_tokens']}
			SET authorized = 1
			, userid = %d
			WHERE consumerid = %d
			AND oauthkey = %s" ,
			(int) $userid ,
			(int) $consumerid ,
			$token_key
			) );
	}

	// deny request token
	public function deny_request_token($userid, $consumerid, $token_key) {
		return $this->db->query($this->db->prepare(
			"DELETE FROM {$this->api_tables['request_tokens']}
			WHERE consumerid = %d
			AND oauthkey = %s" ,
			(int) $consumerid ,
			$token_key
			) );
	}

	// delete request token
	public function delete_request_token($authorized = 0, $userid = 0) {
		$sql = "DELETE FROM {$this->api_tables['request_tokens']}";
		if ( (int)$authorized >= 0 ) {
			if ( (int)$userid > 0 ) {
				$sql .= $this->db->prepare(
					' WHERE authorized = %d AND userid = %d;' ,
					(int)$authorized ,
					(int)$userid
					);
			} else {
				$sql .= $this->db->prepare(
					' WHERE authorized = %d;' ,
					(int)$authorized
					);
			}
		}

		$result = $this->db->get_results($sql);
		return $result ? TRUE : FALSE;
	}

	// delete access token
	public function delete_access_token($userid, $id = 0) {
		if ( (int)$id > 0 )
			$result = $this->db->get_results($this->db->prepare(
				"DELETE FROM {$this->api_tables['access_tokens']}
				WHERE id = %d
				AND userid = %d;",
				(int) $id ,
				(int) $userid
				));
		else
			$result = $this->db->get_results($this->db->prepare(
				"DELETE FROM {$this->api_tables['access_tokens']}
				WHERE userid = %d;",
				(int) $userid
				));

		return $result ? TRUE : FALSE;
	}

	// make random key
	private function str_makerand($length, $useupper = true, $usenumbers = true, $usespecial = false) {
		$charset = "abcdefghijklmnopqrstuvwxyz";
		if ($useupper)
			$charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if ($usenumbers)
			$charset .= "0123456789";
		if ($usespecial)
			$charset .= "~@#$%^*()_+-={}|][";
		// Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./";

		$key = '';
		for ( $i=0; $i<$length; $i++ ) {
			$key .= $charset[mt_rand(0, strlen($charset)-1)];
		}
		return $key;
	}

	// make token key & secret
	private function generate_key( $key_type, $key_length = 8, $secret_length = 16 ) {
		$key    = $this->str_makerand( $key_length ); 
		$secret = $this->str_makerand( $secret_length );

		return array($key, $secret);
	}

	// add new consumer key
	public function new_consumer_key($name, $description, $callback = NULL) {
		list($key, $secret) = $this->generate_key( 'consumer_key' );

		if ($this->lookup_consumer_from_name($name) === NULL) {
			$new_consumer = $this->db->query($this->db->prepare(
				"INSERT INTO {$this->api_tables['consumers']} ( name, oauthkey, secret, description )
				VALUES ( %s, %s, %s, %s );" ,
				$name ,
				$key ,
				$secret ,
				$description
				));
			if ($new_consumer)
				$new_consumer = new OP_OAuthConsumer( $key, $secret, $callback );
			return $new_consumer;
		} else {
			throw new OP_OAuthException( 'Duplicate Name!' );
		}
	}

	// add new request token
	public function new_request_token($consumer, $callback = null) {
		list($key, $secret) = $this->generate_key( 'request_token' );

		if (!isset($consumer->id))
			$consumer = $this->lookup_consumer($consumer->key);
		$consumerid = isset($consumer->id) ? $consumer->id : NULL;

		$request_token = $this->db->query($this->db->prepare(
			"INSERT INTO {$this->api_tables['request_tokens']} ( consumerid, oauthkey, secret, authorized )
			VALUES ( %d, %s, %s, %d );" ,
			(int)$consumerid ,
			$key ,
			$secret ,
			0
			));
		if ($request_token)
			$request_token = new WP_OAuthToken( $key, $secret, NULL, NULL );
		return $request_token;
	}

	// add new access token
	public function new_access_token($request_token, $consumer, $verifier = null) {
		if( isset($request_token->authorized) && $request_token->authorized ) {

			list($key, $secret) = $this->generate_key( 'access_token' );
			if (!isset($consumer->id))
				$consumer = $this->lookup_consumer($consumer->key);
			$consumerid = isset($consumer->id) ? $consumer->id : NULL;
			$userid = isset($request_token->userid) ? $request_token->userid : null;

			$this->db->query($this->db->prepare(
				"DELETE FROM {$this->api_tables['access_tokens']}
				WHERE consumerid = %d
				AND userid = %d;" ,
				(int)$consumerid ,
				(int)$userid
				));

			$access_token = $this->db->query($this->db->prepare(
				"INSERT INTO {$this->api_tables['access_tokens']} ( consumerid, oauthkey, secret, userid )
				VALUES ( %d, %s, %s, %d );" ,
				(int)$consumerid ,
				$key ,
				$secret ,
				(int)$userid
				));

			$this->db->query($this->db->prepare(
				"DELETE FROM {$this->api_tables['request_tokens']}
				WHERE consumerid = %d
				AND userid = %d;" ,
				(int)$consumerid ,
				(int)$userid
				));

			if ($access_token)
				$access_token = new WP_OAuthToken( $key, $secret, $userid, NULL );
			return $access_token;

		} else {
			throw new OP_OAuthException( 'Unauthorized Access Token!' );
		}
	}
}
