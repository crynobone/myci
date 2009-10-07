<?php


class Scaffold {
	var $CI = NULL;
	var $default_response = array (
		'success' => FALSE,
		'redirect' => '',
		'error' => '',
		'data' => array ()
	);
	var $form = NULL;
	var $output = array (
		'html' => ''
	);
	
	function Scaffold()
	{
		$this->CI =& get_instance();
		
		$this->CI->load->library('Form');
		$this->form = $this->CI->form;
		
		$this->CI->scaffold = $this;
	}
	
	function retrieve($data = array ())
	{
		$data = $this->_prepare_retrieve($data);
		
		
	}
	function editable($data = array())
	{
		$data = $this->_prepare_editable($data);
		$output = array (
			'html' => array (
				'form' => '',
				'error' => ''
			), 
			'response' => $this->default_response
		);
		
		if (is_array($data['fields']) && count($data['fields']) > 0)
		{
			$output['html']['form'] = $this->form->generate($data['fields'], $data['prefix']);
			
			if ( !! $this->form->run($data['prefix']))
			{
				$result = $this->simple_form->result($data['prefix']);
				if ( !! property_exists($this->CI, $data['model']))
				{
					if ( !! method_exists($this->CI->{$data['model']}, $data['method']))
					{
						$output['response'] = $this->CI->{$data['model']}->{$data['method']}($result);
					}
					
					if ($output['response']['success'] === TRUE && trim($output['response']['redirect']) !== '')
					{
						redirect($output['response']['redirect']);
					} 						
					elseif ($output['response']['success'] === FALSE) 
					{
						$output['html']['error'] = $output['response']['error'];
					}
				}
			}
			
			if ( !! method_exists($this->CI, $data['callback']))
			{
				$this->CI->{$data['callback']}($output);
			}
			else 
			{
				return $output;
			}
		}
		else 
		{
			
		}
	}
	
	function _prepare_editable($data)
	{
		$default = array (
			'model' => 'model',
			'method' => 'get',
			'fields' => array (),
			'prefix' => 'default',
			'callback' => ''
		);
		
		return $result = array_merge($default, $data);
	}
	
}
