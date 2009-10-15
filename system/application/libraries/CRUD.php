<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CRUD Generator for CodeIgniter
 *
 * PHP version 5
 *
 * @category  CodeIgniter
 * @package   CRUD CI
 * @author    Mior Muhammad Zaki (hello@crynobone.com)
 * @version   0.1
 * Copyright (c) 2009 Mior Muhammad Zaki  (http://crynobone.com)
 * Licensed under the MIT.
*/

class CRUD {
	// CI Singleton
	var $CI = NULL;
	
	// set default modify/delete response
	var $default_response = array (
		'success' => FALSE,
		'redirect' => '',
		'error' => '',
		'data' => array ()
	);
	
	// Allow output to be access
	var $output = array ();
	
	// set global configuration variables for CRUD
	var $data = array (
		'get' => array (),
		'set' => array (),
		'remove' => array (),
		'model' => '',
		'segment' => 3,
		'segment_id' => 4,
		'segment_xhr' => 5,
		'enable_get' => TRUE,
		'enable_set' => TRUE,
		'enable_remove' => TRUE,
		'404' => ''
	);
	
	/**
	 * Constructor
	 * 
	 * @access		public
	 * @return 		void
	 */
	function CRUD()
	{
		// load CI object
		$this->CI =& get_instance();
		
		// load required libraries
		$this->CI->load->library(array (
			'Form',
			'Table',
			'Pagination'
		));
		
		// add this class to CI object
		$this->CI->CRUD = $this;
		
		log_message('debug', "CRUD Class Initialized");
	}
	
	/**
	 * 
	 * @param object $data [optional]
	 * @return 
	 */
	function initialize($data = array ())
	{
		$this->data = array_merge($this->data, $data);
		
		$segment = $this->CI->uri->segment($this->data['segment'], '');
		$is_get = array ('get', 'index', '');
		$is_set = array ('set', 'modify', 'write');
		$is_get_one = array ('display', 'get_one');
		$is_remove = array ('delete', 'remove');
		$send_to = '';
		
		if (isset($segment))
		{
			$send_to = (in_array($segment, $is_get) ? 'get' : $send_to);
			$send_to = (in_array($segment, $is_set) ? 'set' : $send_to);
			$send_to = (in_array($segment, $is_remove) ? 'remove' : $send_to);
			$send_to = (in_array($segment, $is_get_one) ? 'get_one' : $send_to);
			
			if ($send_to != '' )
			{
				$this->generate($send_to);
			}
			else 
			{
				if ( !! method_exists($this->CI, $segment))
				{
					$this->CI->$segment();
				}
				else 
				{
					show_404();
				}
			}
		}
	}
	
	function generate($type = 'get', $option = NULL)
	{
		$allowed = array ('get', 'get_one', 'set', 'remove');
		$type = trim(strtolower($type));
		
		if ( !! in_array($type, $allowed))
		{
			$this->{$type}($this->data[$type], $option);
		}
		else 
		{
			log_message('error', 'CRUD: Unable to determine request ' . $type);
			$this->_callback_404();
		}
	}
	function get($data = array ())
	{
		$data = $this->_prepare_get($data);
		
		$datagrid = NULL;
		$output = array (
			'html' => array (
				'datagrid' => '',
				'pagination' => ''
			),
			'data' => array (),
			'total_rows' => 0
		);
		
		if ( ! $data['is_accessible'] && !! $this->data['enable_get'])
		{
			return $this->_callback_404();
		}
		
		if ( !! property_exists($this->CI, $data['model']))
		{
			if ( !! method_exists($this->CI->{$data['model']}, $data['method']))
			{
				$datagrid = $this->CI->{$data['model']}->{$data['method']}($data['limit'], $data['offset']);
				$datagrid = args_to_array($datagrid, array('data', 'total_rows', 'header'));
		
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
				
				$config = array_merge($config, $data['pagination_template']);
				
				$this->CI->table->clear();
				
				if (isset($data['header'])) 
				{
					$this->CI->table->set_heading($data['header']);	
				}
				
				$this->CI->pagination->initialize($config);
				
				$output['html']['datagrid'] = $this->CI->table->generate($output['data']);
				$output['html']['pagination'] = $this->CI->pagination->create_links();
			}
			else 
			{
				log_message('error', 'CRUD: cannot locate method under Application model class');
				return $this->_callback_404();
			}
		}
		else 
		{
			log_message('error', 'CRUD: cannot locate Application model class');
			return $this->_callback_404();
		}
		
		$this->output = $output;
		
		if ($this->data['segment_xhr'] > 0 && $this->CI->uri->segment($this->data['segment_xhr'], '') == 'xhr' && !! method_exists($this->CI, $data['callback_xhr']))
		{
			$this->CI->{$data['callback_xhr']}($output['html'], $output['data'], $output['total_rows']);
		}
		elseif ( !! method_exists($this->CI, $data['callback']))
		{
			$this->CI->{$data['callback']}($output['html'], $output['data'], $output['total_rows']);
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
	
	function get_one($data = array())
	{
		return $this->set($data, FALSE);
	}
	
	function form($data = array ())
	{
		return $this->set($data);
	}
	
	function set($data = array(), $is_form = TRUE)
	{
		$data = $this->_prepare_set($data);
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
		
		
		if ( ! $data['is_accessible'] || ! $this->data['enable_set'])
		{
			return $this->_callback_404();
		}
		
		if ( !! property_exists($this->CI, $data['model']))
		{
			$data['fields'] = $this->_organizer($data, 'fields');
			$data['data'] = $this->_organizer($data, 'data');
			
			if (is_array($data['fields']) && count($data['fields']) > 0)
			{
				$output['html']['form'] = $this->CI->form->generate($data['fields'], $data['prefix'], $data['data'], $is_form);
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
						return $this->_callback_404();
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
			return $this->_callback_404();
		}
			
		if ( trim($data['view']) !== '')
		{
			$this->_callback_viewer($output['html'], $data['output'], $data['view']);
		}
		elseif ( !! method_exists($this->CI, $data['callback']))
		{
			$this->CI->{$data['callback']}($output['html'], $output['response']);
		}
		elseif ($this->data['segment_xhr'] > 0 && $this->CI->uri->segment($this->data['segment_xhr'], '') == 'xhr' && !! method_exists($this->CI, $data['callback_xhr']))
		{
			$this->CI->{$data['callback_xhr']}($output['html'], $output['response']);
		}
		else 
		{
			return $output;
		}
	}
	
	function remove($data = array())
	{
		$data = $this->_prepare_remove($data);
		$output = array (
			'response' => $this->default_response
		);
		
		if ( ! $data['is_accessible'] && !! $this->data['enabled_remove'])
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
				return $this->_callback_404();
			}
					
			if ($output['response']['success'] === TRUE && trim($output['response']['redirect']) !== '')
			{
				redirect($output['response']['redirect']);
			}
		}
		else 
		{
			log_message('error', 'CRUD: cannot locate Application model class');
			return $this->_callback_404();
		}
			
		if ( trim($data['view']) !== '')
		{
			$this->_callback_viewer(array (), $data['output'], $data['view']);
		}
		elseif ( !! method_exists($this->CI, $data['callback']))
		{
			$this->CI->{$data['callback']}($output['response']);
		}
		elseif ($this->data['segment_xhr'] > 0 && $this->CI->uri->segment($this->data['segment_xhr'], '') == 'xhr' && !! method_exists($this->CI, $data['callback_xhr']))
		{
			$this->CI->{$data['callback_xhr']}($output['response']);
		}
		else 
		{
			return $output;
		}
	}
	
	function _prepare_get($data)
	{
		$model = 'model';
		
		if ( trim($this->data['model']) !== '')
		{
			$model = $this->data['model'];
		}
		
		$default = array (
			'model' => $model,
			'method' => 'get',
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
	
	function _prepare_set($data)
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
			'pagination_template' => array(),
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
	
	function _prepare_remove($data)
	{
		$model = 'model';
		
		if ( trim($this->data['model']) !== '')
		{
			$model = $this->data['model'];
		}
		
		$default = array (
			'id' => 0,
			'model' => $model,
			'method' => 'remove',
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
		if ( ! isset($this->data['404']) || trim($this->data['404']) === '')
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
	
	/**
	 * 
	 * @param object $scaffold [optional]
	 * @param object $output [optional]
	 * @param object $view [optional]
	 * @return 
	 */
	function _callback_viewer($scaffold = array (), $output = array (), $view = '')
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
