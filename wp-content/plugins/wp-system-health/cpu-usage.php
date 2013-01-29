<?php

class WPSH_Cpu_Usage
{
	function __construct() {
		$this->is_windows = ( substr(PHP_OS,0,3) == 'WIN');
	}
	
	function live_values() {
		$result = array(0,0,0);
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
					$result[0] = $load[1];
					$result[1] = 0;
					$result[2] = 0;
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
}

//prevent direct call of file
if (!function_exists('add_action')) { 
	$usage = new WPSH_Cpu_Usage();
	$load = $usage->live_values();
	header('Content-Type: application/json');
	echo json_encode(
		array(
			time()*1000,
			round($load[0],2),
			round($load[0],2)
		)
	);
	exit();
} else {
	$this->cpu_usage = new WPSH_Cpu_Usage();
}

