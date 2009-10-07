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
	var $data = array (
		'retrieve' => array (),
		'editable' => array ()
	);
	
	function Scaffold()
	{
		$this->CI =& get_instance();
		
		$this->CI->load->library(array (
			'Form',
			'Table',
			'Pagination'
		));
		
		$this->CI->scaffold = $this;
	}
	
	function initiate($data = array ())
	{
		$this->data = array_merge($this->data, data);
	}
	
	function run($type = 'retrieve')
	{
		$allowed = array ('retrieve', 'editable');
		$type = trim(strtolower($type));
		
		if ( !! in_array($type, $allowed))
		{
			$this->{$type}($this->data[$type]);
		}
	}
	function retrieve($data = array ())
	{
		$data = $this->_prepare_retrieve($data);
		
		$this->CI->table->clear();
		$this->CI->table->set_heading($data['header']);
		
		$datagrid = NULL;
		$output = array (
			'html' => array (
				'datagrid' => '',
				'pagination' => ''
			),
			'data' => array (),
			'total_rows' => 0
		);
		
		if ( !! property_exists($this->CI, $data['model']))
		{
			if ( !! method_exists($this->CI->{$data['model']}, $data['method']))
			{
				$datagrid = $this->CI->{$data['model']}->{$data['method']}($data['limit'], $data['offset']);
				
				$output['data'] = $datagrid['data'];
				$output['total_rows'] = $datagrid['total_rows'];
				
				$config = array (
					'base_url' => $data['base_url'],
					'total_rows' => $output['total_rows'],
					'per_page' => $data['limit'],
					'cur_page' => $data['offset'],
					'suffix_url' => $data['suffix_url']
				);
				
				$this->CI->pagination->initiate($config);
				
				$output['html']['datagrid'] = $this->CI->table->generate($output['data']);
				$output['html']['pagination'] = $this->CI->pagination->create_links();
			}
			else 
			{
				log_message('error', 'Scaffold: cannot locate method under Application model class');
			}
		}
		else 
		{
			log_message('error', 'Scaffold: cannot locate Application model class');
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
			$output['html']['form'] = $this->CI->form->generate($data['fields'], $data['prefix']);
			
			if ( !! $this->CI->form->run($data['prefix']))
			{
				$result = $this->CI->form->result($data['prefix']);
				
				if ( !! property_exists($this->CI, $data['model']))
				{
					if ( !! method_exists($this->CI->{$data['model']}, $data['method']))
					{
						$output['response'] = $this->CI->{$data['model']}->{$data['method']}($result);
					}
					else 
					{
						log_message('error', 'Scaffold: cannot locate method under Application model class');
					}
					
					if ($output['response']['success'] === TRUE && trim($output['response']['redirect']) !== '')
					{
						redirect($output['response']['redirect']);
					} 						
					elseif ($output['response']['success'] === FALSE) 
					{
						$output['html']['error'] = sprintf(
							'<%s class="%s">%s</%s>',
							$this->CI->form->template['error'],
							$this->CI->form->template['error_class'],
							$output['response']['error'],
							$this->CI->form->template['error']
						);
					}
				}
				else 
				{
					log_message('error', 'Scaffold: cannot locate Application model class');
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
			log_message('error', 'Scaffold: form fields is empty or not an array');
		}
	}
	
	function _prepare_retrieve($data)
	{
		$default = array (
			'model' => 'model',
			'method' => 'get',
			'header' => array (),
			'parser' => '',
			'limit' => 30,
			'offset' => 0,
			'callback' => '',
			'base_url' => current_url(),
			'suffix_url' => ''
		);
		
		return $result = array_merge($default, $data);
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
