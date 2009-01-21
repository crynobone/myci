<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Apphook
{
	function pre()
	{
		$ci =& get_instance();
		$ci->uri->auto_segment();
	}
	function post()
	{
		$ci =& get_instance();
		$ci->ui->publish();
	}
}

?>