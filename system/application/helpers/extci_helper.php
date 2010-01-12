<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('prettylist')) {
	function prettylist($data = array(), $between = '', $last = '') 
	{
		$count = count($data);
		$output = '';
		
		if ($count > 1) {
			for ($loop = 0; $loop < ($count - 1); $loop++) {
				$output .= ($loop == 0 ? '' : $between) . $data[$loop];
			}
			
			$output .= $last . $data[($count-1)];
		}
		elseif ($count == 1) {
			$output = $data[0];
		}
		
		return $output;
	}
}

if (!function_exists('querystring')) {
	function querystring($data = array(), $start = '?')
	{
		$CI =& get_instance();
		
		$query = "";
		$count = 0;
		
		foreach ($data as $value) {
			$val = ($CI->input->get($value) != FALSE ? $CI->input->get($value) : '');
			
			if (trim($val) != '') {
				$query .= ($count == 0 ? $start : '&');
				$query .= $value . '=' . $val;
				$count++;
			}
		}
		
		return $query;
	}
}

if ( ! function_exists('is_mobile_browser')) {
	function is_mobile_browser()
	{
		$mobile = FALSE;
		
		if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			$mobile = TRUE;
		}
		
		if((strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') > 0) || ((isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])))) {
			$mobile = TRUE;
		}
		
		$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
		$mobile_agents = array(
			'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
			'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
			'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
			'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
			'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
			'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
			'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
			'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
			'wapr','webc','winw','winw','xda','xda-'
		);
		
		if (in_array($mobile_ua, $mobile_agents)) {
			$mobile = TRUE;
		}
		
		if (isset($_SERVER['ALL_HTTP']) && strpos(strtolower($_SERVER['ALL_HTTP']), 'OperaMini') > 0) {
			$mobile = TRUE;
		}
				
		if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') > 0) {
			$mobile = TRUE;
		}
				
		return $mobile;
	}
}

if ( ! function_exists('get_valid_domain_name')) {
	function get_valid_domain_name($domain = '')
	{
		$domain = prep_url($domain);
		$domain	= preg_replace('#\http:\/\/(\S+)#i', '\\1', $domain);
		$domain = preg_replace('#\HTTP:\/\/(\S+)#i', '\\1', $domain);
		$domain = preg_replace('#\https:\/\/(\S+)#i', '\\1', $domain);
		$domain = preg_replace('#\HTTPS:\/\/(\S+)#i', '\\1', $domain);
		$domain	= preg_replace('#\www\.(\S+)#i', '\\1', $domain);
		$domain = preg_replace('#\WWW\.(\S+)#i', '\\1', $domain);
		$domains = split('/', $domain);
		$dir = '/';
		$uri = $domains[0];
			
		for ($loop = 0; $loop < count($domains); $loop++) {
			if ($loop == 1 && isset($domains[1]) && $domains[1] != '') {
				$uri .= '/' . $domains[1];
			}
			
			if ($loop >= 1 && $domains[$loop] != '') {
				$dir .= $domains[$loop] .'/';
			}
		}
		
		return array (
			'uri' => $uri, 
			'domain' => $domains[0], 
			'directory' => $dir,
			'full' => $domain
			
		);
	}
}

if ( ! function_exists('to_proper_date')) {
	function to_proper_date($date)
	{
		return implode('-', array_reverse(explode('-', $date)));
	}
}

if ( ! function_exists('to_proper_datetime')) {
	function to_proper_datetime($datetime)
	{
		$date = explode(' ', $datetime);
		return implode('-', array_reverse(explode('-', $date[0]))) . ' ' . $date[1];
	}
}

if ( ! function_exists('args_to_array')) {
	function args_to_array($args = array(), $option = array (), $offset = 1)
	{
		$output = array ();
		
		if (count($args) != $offset) {
			foreach ($option as $key => $val) {
				if (isset($args[$key])) {
					$output[$val] = $args[$key];
				}
			}
		}
		else {
			$output = $args;
		}
		
		return $output;
	}
}
