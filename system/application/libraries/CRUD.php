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
		'read' => array (),
		'modify' => array (),
		'delete' => array (),
		'model' => '',
		'segment' => 3,
		'segment_id' => 4,
		'segment_xhr' => 5,
		'enable_read' => TRUE,
		'enable_modify' => TRUE,
		'enable_delete' => TRUE,
		'404' => ''
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
		
		$segment = $this->CI->uri->segment($this->data['segment'], '');
		$is_read = array ('read', 'index', '');
		$is_modify = array ('modify', 'update', 'write');
		$is_delete = array ('delete', 'remove');
		$send_to = '';
		
		if (isset($segment))
		{
			$send_to = (in_array($segment, $is_read) ? 'read' : $send_to);
			$send_to = (in_array($segment, $is_modify) ? 'modify' : $send_to);
			$send_to = (in_array($segment, $is_delete) ? 'delete' : $send_to);
			
			if ($send_to != '')
			{
				$this->generate($send_to);
			}
			else 
			{
				$this->CI->$segment();
			}
		}
	}
	
	function generate($type = 'read')
	{
		$allowed = array ('read', 'modify', 'delete');
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
	function read($data = array ())
	{
		$data = $this->_prepare_read($data);
		
		$datagrid = NULL;
		$output = array (
			'html' => array (
				'datagrid' => '',
				'pagination' => ''
			),
			'data' => array (),
			'total_rows' => 0
		);
		
		if ( ! $data['is_accessible'] && !! $this->data['enable_read'])
		{
			return $this->_callback_404();
		}
		
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
		
		if ($this->data['segment_xhr'] > 0 && $this->CI->uri->segment($this->data['segment_xhr'], '') == 'xhr' && !! method_exists($this->CI, $data['callback_xhr']))
		{
			$this->CI->{$data['callback_xhr']}($output);
		}
		elseif ( !! method_exists($this->CI, $data['callback']))
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
	
	function form($data = array())
	{
		return $this->modify($data);
	}
	
	function modify($data = array())
	{
		$data = $this->_prepare_modify($data);
		$output = array (
			'html' => array (
				'form' => '',
				'fields' => array (),
				'error' => '',
				'value' => array ()
			), 
			'response' => $this->default_response
		);
		
		$form_template = $data['form_template'];
		
		if (is_array($form_template) && count($form_template))
		{
			$this->CI->form->set_template($form_template);
		}
		
		
		if ( ! $data['is_accessible'] && !! $this->data['enable_modify'])
		{
			return $this->_callback_404();
		}
		
		if ( !! property_exists($this->CI, $data['model']))
		{
			$data['fields'] = $this->_organizer($data, 'fields');
			$data['data'] = $this->_organizer($data, 'data');
			
			if (is_array($data['fields']) && count($data['fields']) > 0)
			{
				$output['html']['form'] = $this->CI->form->generate($data['fields'], $data['prefix'], $data['data']);
				$output['html']['fields'] = $this->CI->form->output[$data['prefix']];
				$output['html']['value'] =  $this->CI->form->value[$data['prefix']];
				
				if ( !! $this->CI->form->run($data['prefix']))
				{
					$result = $this->CI->form->result($data['prefix']);
					
					$data['result'] = $result;
					
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
			
		if ( trim($data['view']) !== '')
		{
			$this->_callback_viewer($output['html'], $data['output'], $data['view']);
		}
		elseif ( !! method_exists($this->CI, $data['callback']))
		{
			$this->CI->{$data['callback']}($output);
		}
		elseif ($this->data['segment_xhr'] > 0 && $this->CI->uri->segment($this->data['segment_xhr'], '') == 'xhr' && !! method_exists($this->CI, $data['callback_xhr']))
		{
			$this->CI->{$data['callback_xhr']}($output);
		}
		else 
		{
			return $output;
		}
	}
	
	function delete($data = array())
	{
		$data = $this->_prepare_delete($data);
		$output = array (
			'response' => $this->default_response
		);
		
		if ( ! $data['is_accessible'] && !! $this->data['enabled']['delete'])
		{
			return $this->_callback_404();
		}
		
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
			
		if ( trim($data['view']) !== '')
		{
			$this->_callback_viewer($output['html'], $data['output'], $data['view']);
		}
		elseif ( !! method_exists($this->CI, $data['callback']))
		{
			$this->CI->{$data['callback']}($output);
		}
		elseif ($this->data['segment_xhr'] > 0 && $this->CI->uri->segment($this->data['segment_xhr'], '') == 'xhr' && !! method_exists($this->CI, $data['callback_xhr']))
		{
			$this->CI->{$data['callback_xhr']}($output);
		}
		else 
		{
			return $output;
		}
	}
	
	function _prepare_read($data)
	{
		$model = 'model';
		
		if ( trim($this->data['model']) !== '')
		{
			$model = $this->data['model'];
		}
		
		$default = array (
			'model' => $model,
			'method' => 'read',
			'callback' => '',
			'callback_xhr' => '',
			'header' => array (),
			'limit' => 30,
			'offset' => 0,
			'base_url' => current_url(),
			'suffix_url' => '',
			'output' => array (),
			'view' => '',
			'is_accessible' => TRUE
		);
		
		if ( ! isset($data['offset']) && $this->data['segment_id'] > 0)
		{
			$data['offset'] = $this->CI->uri->segment($this->data['segment_id'], 0);
		}
		
		return array_merge($default, $data);
		
	}
	
	function _prepare_modify($data)
	{
		$model = 'model';
		
		if ( trim($this->data['model']) !== '')
		{
			$model = $this->data['model'];
		}
		
		$default = array (
			'id' => 0,
			'model' => $model,
			'method' => 'modify',
			'callback' => '',
			'callback_xhr' => '',
			'prefix' => 'default',
			'form_template' => array(),
			'fields' => array (),
			'data' => array(),
			'output' => array (),
			'view' => '',
			'is_accessible' => TRUE
		);
		
		if ( ! isset($data['id']) && $this->data['segment_id'] > 0)
		{
			$data['id'] = $this->CI->uri->segment($this->data['segment_id'], 0);
		}
		
		return array_merge($default, $data);
	}
	
	function _prepare_delete($data)
	{
		$model = 'model';
		
		if ( trim($this->data['model']) !== '')
		{
			$model = $this->data['model'];
		}
		
		$default = array (
			'id' => 0,
			'model' => $model,
			'method' => 'delete',
			'callback' => '',
			'callback_xhr' => '',
			'output' => array (),
			'view' => '',
			'is_accessible' => TRUE
		);
		
		if ( ! isset($data['id']) && $this->data['segment_id'] > 0)
		{
			$data['id'] = $this->CI->uri->segment($this->data['segment_id'], 0);
		}
		
		return array_merge($default, $data);
	}
	
	function _organizer($data, $prefix)
	{
		$output = $data[$prefix];
		
		if ( !! is_string($output) && trim($output) !== '')
		{
			if ( !!  method_exists($this->CI->{$data['model']}, $output))
			{
				$output = $this->CI->{$data['model']}->{$output}($data['id']);
			}
			elseif ( !! property_exists($this->CI->{$data['model']}, $output))
			{
				$output = $this->CI->{$data['model']}->{$output};
			}
			else {
				$output = array ();
			}
		}
		
		return $output;
	}
	
	function _callback_404()
	{
		if (trim($this->data['404']) === '')
		{
			show_404();
		}
		else
		{
			if ( !! property_exists($this->CI, 'ui')) 
			{
				$this->CI->ui->set_title('Module not accessible');
				$this->CI->ui->view($this->data['404']);
				$this->CI->ui->render();
			}
			else 
			{
				$this->CI->load->view($this->data['404']);
			}
		}
	}
	
	function _callback_viewer($scaffold, $output, $view)
	{
		$output = array_merge($output, $scaffold);
		
		if ( !! property_exists($this->CI, 'ui')) 
		{
			if (isset($output['title']) && trim($output['title']) !== '')
			{
				$this->CI->ui->set_title($output['title']);
			}
			
			$this->CI->ui->view($view, $output);
			$this->CI->ui->render();
		}
		else 
		{
			$this->CI->load->view($view, $output);
		}
	}
}
