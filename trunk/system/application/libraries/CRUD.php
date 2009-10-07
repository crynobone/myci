<?php

class CRUD {
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
		'retriever' => array (),
		'updater' => array (),
		'remover' => array (),
		'model' => '',
		'segment' => ''
	);
	
	function CRUD()
	{
		$this->CI =& get_instance();
		
		$this->CI->load->library(array (
			'Form',
			'Table',
			'Pagination'
		));
		
		$this->CI->CRUD = $this;
		
		log_message('debug', "CRUD Class Initialized");
	}
	
	function initialize($data = array ())
	{
		$this->data = array_merge($this->data, $data);
		
		$segment = trim($this->data['segment']);
		$is_retriever = array ('index', '', 'retriever', 'retrieve');
		$is_updater = array ('updater', 'update', 'editable');
		$is_remover = array ('remover', 'remove');
		$send_to = '';
		
		if (isset($segment))
		{
			$send_to = (in_array($segment, $is_retriever) ? 'retriever' : $send_to);
			$send_to = (in_array($segment, $is_updater) ? 'updater' : $send_to);
			$send_to = (in_array($segment, $is_remover) ? 'remover' : $send_to);
			
			$this->generate($send_to);
		}
	}
	
	function generate($type = 'retriever')
	{
		$allowed = array ('retriever', 'updater', 'remover');
		$type = trim(strtolower($type));
		
		if ( !! in_array($type, $allowed))
		{
			$this->{$type}($this->data[$type]);
		}
		else 
		{
			log_message('error', 'CRUD: Unable to determine request ' . $type);
		}
	}
	function retriever($data = array ())
	{
		$data = $this->_prepare_retriever($data);
		
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
				
				if ( isset($datagrid['header']) && is_array($datagrid['header']))
				{
					$data['header'] = $datagrid['header'];
				}
				
				$config = array (
					'base_url' => $data['base_url'],
					'total_rows' => $output['total_rows'],
					'per_page' => $data['limit'],
					'cur_page' => $data['offset'],
					'suffix_url' => $data['suffix_url']
				);
				
				$this->CI->table->clear();
				$this->CI->table->set_heading($data['header']);
				
				$this->CI->pagination->initialize($config);
				
				$output['html']['datagrid'] = $this->CI->table->generate($output['data']);
				$output['html']['pagination'] = $this->CI->pagination->create_links();
			}
			else 
			{
				log_message('error', 'CRUD: cannot locate method under Application model class');
			}
		}
		else 
		{
			log_message('error', 'CRUD: cannot locate Application model class');
		}
		
		if ( !! method_exists($this->CI, $data['callback']))
		{
			$this->CI->{$data['callback']}($output);
		}
		elseif ( trim($data['view']) !== '')
		{
			$this->_callback_viewer($output['html'], $data['output'], $data['view']);
		}
		else 
		{
			return $output;
		}
		
	}
	function updater($data = array())
	{
		$data = $this->_prepare_updater($data);
		$output = array (
			'html' => array (
				'form' => '',
				'error' => ''
			), 
			'response' => $this->default_response
		);
		
		if ( !! property_exists($this->CI, $data['model']))
		{
			
			if (trim($data['callback_fields']) !== '' && method_exists($this->CI->{$data['model']}, $data['callback_fields']))
			{
				$data['fields'] = $this->CI->{$data['model']}->{$data['callback_fields']}($data['id']);
			}
			
			if (trim($data['callback_data']) !== '' && method_exists($this->CI->{$data['model']}, $data['callback_data']))
			{
				$data['data'] = $this->CI->{$data['model']}->{$data['callback_data']}($data['id']);
			}
			
			if (is_array($data['fields']) && count($data['fields']) > 0)
			{
				$output['html']['form'] = $this->CI->form->generate($data['fields'], $data['prefix'], $data['data']);
				
				if ( !! $this->CI->form->run($data['prefix']))
				{
					$result = $this->CI->form->result($data['prefix']);
					
					
					if ( !! method_exists($this->CI->{$data['model']}, $data['method']))
					{
						$output['response'] = $this->CI->{$data['model']}->{$data['method']}($result, $this->default_response);
					}
					else 
					{
						log_message('error', 'CRUD: cannot locate method under Application model class');
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
				
			}
		}
		else 
		{
			log_message('error', 'CRUD: cannot locate Application model class');
		}
			
		if ( !! method_exists($this->CI, $data['callback']))
		{
			$this->CI->{$data['callback']}($output);
		}
		elseif ( trim($data['view']) !== '')
		{
			$this->_callback_viewer($output['html'], $data['output'], $data['view']);
		}
		else 
		{
			return $output;
		}
	}
	
	function remover($data = array())
	{
		$data = $this->_prepare_remover($data);
		$output = array (
			'response' => $this->default_response
		);
		
		if ( !! property_exists($this->CI, $data['model']))
		{
			
			if ( !! method_exists($this->CI->{$data['model']}, $data['method']))
			{
				$output['response'] = $this->CI->{$data['model']}->{$data['method']}($result, $this->default_response);
			}
			else 
			{
				log_message('error', 'CRUD: cannot locate method under Application model class');
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
			log_message('error', 'CRUD: cannot locate Application model class');
		}
			
		if ( !! method_exists($this->CI, $data['callback']))
		{
			$this->CI->{$data['callback']}($output);
		}
		elseif ( trim($data['view']) !== '')
		{
			$this->_callback_viewer($output['html'], $data['output'], $data['view']);
		}
		else 
		{
			return $output;
		}
	}
	
	function _prepare_retriever($data)
	{
		$default = array (
			'id' => 0,
			'model' => 'model',
			'method' => 'retriever',
			'view' => '',
			'output' => array (),
			'header' => array (),
			'limit' => 30,
			'offset' => 0,
			'callback' => '',
			'base_url' => current_url(),
			'suffix_url' => ''
		);
		
		$result = array_merge($default, $data);
		
		if ( trim($this->data['model']) !== '')
		{
			$result['model'] = $this->data['model'];
		}
		
		return $result;
	}
	
	function _prepare_updater($data)
	{
		$default = array (
			'id' => 0,
			'model' => 'model',
			'method' => 'updater',
			'view' => '',
			'output' => array (),
			'fields' => array (),
			'callback_fields' => '',
			'data' => array(),
			'callback_data' => '',
			'prefix' => 'default',
			'callback' => ''
		);
		
		$result = array_merge($default, $data);
		
		if ( trim($this->data['model']) !== '')
		{
			$result['model'] = $this->data['model'];
		}
		
		return $result;
	}
	
	function _prepare_remover($data)
	{
		$default = array (
			'id' => 0,
			'model' => 'model',
			'method' => 'remover',
			'callback' => '',
			'output' => array (),
			'view' => ''
		);
		
		$result = array_merge($default, $data);
		
		if ( trim($this->data['model']) !== '')
		{
			$result['model'] = $this->data['model'];
		}
		
		return $result;
	}
	
	function _callback_viewer($scaffold, $output, $view)
	{
		$output = array_merge($output, $scaffold);
		
		if (isset($output['title']) && trim($output['title']) !== '')
		{
			$this->CI->ui->set_title($output['title']);
		}
		
		$this->CI->ui->view($view, $output);
		$this->CI->ui->render();
	}
}
