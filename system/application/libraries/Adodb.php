<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Adodb 
{
	public function Adodb() 
	{
		if (!class_exists('ADONewConnection')) {
			 require_once(APPPATH . 'libraries/adodb5/adodb.inc.php');
		}

		$CI =& get_instance();
		$db_var = FALSE;
		$debug = FALSE;
		
		// try to load config/adodb.php
		// extra parameter comes from patch at http://www.codeigniter.com/wiki/ConfigLoadPatch/
		// without this patch, if config/adodb.php doesn't exist, CI will display a fatal error.
		
		if ( ! isset($dsn) || $dsn == NULL) {
			// fallback to using the CI database file
			include(APPPATH . 'config/database.php');
			$group = 'default';
			$dsn = $db[$group]['dbdriver'].'://'.$db[$group]['username']
				   .':'.$db[$group]['password'].'@'.$db[$group]['hostname']
				   .'/'.$db[$group]['database'];
		}
		
		// $ci is by reference, refers back to global instance
		$CI->adodb =& ADONewConnection($dsn);
		
		if ($debug) {
			$CI->adodb->debug = TRUE;
		}
		
		array_push($CI->ci_is_loaded, 'adodb');
		
		return $this;
	}
}

/* End of Adodb.php
 */