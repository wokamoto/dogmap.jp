<?php

class wpsh_boot_loader {

	function wpsh_boot_loader() {
		$this->__construct();
	}
	
	function __construct() {
		$this->pure_init = !function_exists('add_action');
		$this->memory_limit = (int) $this->convert_ini_bytes(ini_get('memory_limit'));
		//if no limit is available set it to 1GB to be sure are divisions ;-)
		if($this->memory_limit == 0) $this->memory_limit = 1024*1024*1024; 
		$this->mem_usage_possible = function_exists('memory_get_usage');
		$this->mem_usage_denied	= preg_match('/memory_get_usage/', ini_get('disable_functions'));
		$this->exec_denied = preg_match('/exec/', ini_get('disable_functions'));
		$this->is_windows = ( substr(PHP_OS,0,3) == 'WIN');
		$this->check_points = array();
		if ($this->pure_init) {
			$this->pass_checkpoint('boot:wp-config', $this->memory_get_usage());
		}else{
			$this->pass_checkpoint('boot:wp-config.failed', 0);
		}
	}
	
	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	function convert_ini_bytes($value) {
		if ( is_numeric( $value ) ) {
			return $value;
		} else {
			$value_length = strlen( $value );
			$qty = substr( $value, 0, $value_length - 1 );
			$unit = strtolower( substr( $value, $value_length - 1 ) );
			switch ( $unit ) {
				case 'k':
					$qty *= 1024;
					break;
				case 'm':
					$qty *= 1048576;
					break;
				case 'g':
					$qty *= 1073741824;
					break;
			}
			return $qty;
		}		
	}
	
	function convert_ini_bool($value) {
		$value = strtolower($value);
		if (empty($value) || $value == 'off') 
			return __('Off', 'wp-system-health');
		if ($value == '1' || $value == 'on')
			return __('On', 'wp-system-health');
		return __('-n.a.-', 'wp-system-health');
	}
	
	function convert_const_bool($value) {
		if (defined($value) && constant($value))
			return __('On', 'wp-system-health');
		return __('Off', 'wp-system-health');
	}

	function pass_checkpoint($token, $mem = false) {
		$this->check_points[] = array(
			$token => array(
				'mem' => ($mem === false ? $this->memory_get_usage() : (int)$mem)
			)
		);
	}
		
	function memory_get_usage() {

		if($this->mem_usage_possible)
			return memory_get_usage();
			
		if ($this->mem_usage_denied || $this->exec_denied) {
			//your provider doesn't allow memory checks and also denies exec to emulate it
			return ''; 
		}
		//If its Windows
		//Tested on Win XP Pro SP2. Should work on Win 2003 Server too
		//Doesn't work for 2000
		//If you need it to work for 2000 look at http://us2.php.net/manual/en/function.memory-get-usage.php#54642
		if ($this->is_windows) {
			$output = array();
			@exec( 'tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output ); 
			return preg_replace( '/[\D]/', '', $output[5] ) * 1024;
		}else {
			//We now assume the OS is UNIX
			//Tested on Mac OS X 10.4.6 and Linux Red Hat Enterprise 4
			//This should work on most UNIX systems
			$pid = getmypid();
			@exec("ps -eo%mem,rss,pid | grep $pid", $output);
			$output = explode("  ", $output[0]);
			//rss is given in 1024 byte units
			return $output[1] * 1024;
		}
	}
	
	function get_loadavg() {
		$result = array(__('-n.a.-', 'wp-system-health'),__('-n.a.-', 'wp-system-health'),__('-n.a.-', 'wp-system-health'));
		if (function_exists('sys_getloadavg')) {
			$load = @sys_getloadavg();
			if (is_array($load)) {
				if(count($load) == 3)
					return $load;
				else {
					for($i=0;$i<count($load);$i++)
						$result[$i] = $load[$i];
				}
			}
		}
		if ($this->is_windows) {
			ob_start();
			$status = null;
			@passthru('typeperf -sc 1 "\processor(_total)\% processor time"',$status);
			$content = ob_get_contents();
			ob_end_clean();
			if ($status === 0) {
				if (preg_match("/\,\"([0-9]+\.[0-9]+)\"/",$content,$load)) {					
					$result[0] = number_format_i18n($load[1],2).' %';
					$result[1] = __('-n.a.-', 'wp-system-health');
					$result[2] = __('-n.a.-', 'wp-system-health');
					return $result;
				}
			}			
		}
		else{
			if (function_exists('file_get_contents') && @file_exists('/proc/loadavg')) {
				$load = explode(chr(32), @file_get_contents('/proc/loadavg'));
				if (is_array($load) && (count($load) >= 3)) {
					$result = array_slice($load, 0, 3);
					return $result;
				}
			}
			if (function_exists('shell_exec')) {
				$str = substr(strrchr(@shell_exec('uptime'),":"),1);
				return array_map("trim",explode(",",$str));
			}
		}
		return $result;
	}
	
	function get_quotas() {
		$result = false;
		if ($this->is_windows) {
			$filedata = array();
			$quota = array();
			$file = basename(__FILE__);
			$drive = substr(__FILE__, 0, 2);
			@exec('dir '.__FILE__.' /q', $filedata);
			$uname = '-n.a-';
			foreach($filedata as $line) {
				if (preg_match('#\s*'.preg_quote($file).'#', $line)) {
					$line = preg_replace('#\s*'.preg_quote($file).'#','',$line);
					$uname = array_pop(explode(' ',$line));
				}
			}
			@exec('fsutil quota query '.$drive, $quota);
			for($i=0;$i<count($quota);$i++){
				if(preg_match('#'.preg_quote($uname).'#', $quota[$i])) {
					$result = array('b_grace'=>0,'files'=>0,'f_quota'=>0,'f_limit'=>0,'f_grace'=>0);
					preg_match('#(\d+)$#',$quota[$i+2], $h);
					$result['blocks'] = $h[1];
					preg_match('#(\d+)$#',$quota[$i+3], $h);
					$result['b_quota'] = $h[1];
					preg_match('#(\d+)$#',$quota[$i+4], $h);
					$result['b_limit'] = $h[1];
				}
			}
			
		}else{
			@exec('quota -u '.fileowner(__FILE__), $quota);
			if (is_array($quota) && isset($quota[2])) {
				if(preg_match_all('#([^a-z]\d+\*?|\s{7,})#', trim($quota[2]), $r)) {
					$k = array('blocks','b_quota','b_limit','b_grace','files','f_quota','f_limit','f_grace');
					$result = array_combine($k, array_pad(array_map("intval",array_map("trim",$r[0])),count($k),0));
				}
			}
		}
		
		if($result !== false) {
			//div by zero checks
			$result['b_perc'] = ((int)$result['b_limit'] == 0 ? 0.0 : $result['blocks'] * 100.0 / $result['b_limit']);
			$result['f_perc'] = ((int)$result['f_limit'] == 0 ? 0.0 : $result['files'] * 100.0 / $result['f_limit']);;
		}
		
		return $result;
	}
}

$wpsh_boot_loader = new wpsh_boot_loader();

?>