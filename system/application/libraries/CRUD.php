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
		// extends configurator
		$this->data = array_merge($this->data, $data);
		
		// get actually uri segment, useful when you are configuring in _remap method
		$segment = $this->CI->uri->segment($this->data['segment'], '');
		
		// set all possible uri segment value
		$is_get = array ('get', 'index', '');
		$is_set = array ('set', 'modify', 'write');
		$is_get_one = array ('display', 'get_one');
		$is_remove = array ('delete', 'remove');
		$send_to = '';
		
		// test segment
		if (isset($segment))
		{
			$send_to = (in_array($segment, $is_get) ? 'get' : $send_to);
			$send_to = (in_array($segment, $is_set) ? 'set' : $send_to);
			$send_to = (in_array($segment, $is_remove) ? 'remove' : $send_to);
			$send_to = (in_array($segment, $is_get_one) ? 'get_one' : $send_to);
			
			if ($send_to != '' )
			{
				// method found, so send to related method
				$this->generate($send_to);
			}
			else 
			{
				if ( !! method_exists($this->CI, $segment))
				{
					// method not found but Controller contain this method
					$this->CI->$segment();
				}
				else 
				{
					// to prevent blank screen if everything else failed, load the 404
					show_404();
				}
			}
		}
		else {
			// this might be impossible to happen, but let just throw a 404 (just-in-case)
			show_404();
		}
	}
	
	/**
	 * Generate the CRUD
	 * 
	 * @param object $type [optional]
	 * @param object $option [optional]
	 * @return 
	 */
	function generate($type = 'get')
	{
		$allowed = array ('get', 'get_one', 'set', 'remove');
		$type_data = $type = trim(strtolower($type));
		
		if ($type == 'get_one')
		{
			$type_data = 'set';
		}
		
		// first we need to check whether it's pointing to valid method
		if ( !! in_array($type, $allowed))
		{
			$this->{$type}($this->data[$type_data]);
		}
		else 
		{
			log_message('error', 'CRUD: Unable to determine request ' . $type);
			// 404 using CRUD callback
			$this->_callback_404();
		}
	}
	
	/**
	 * 
	 * @param object $data [optional]
	 * @return 
	 */
	function get($data = array ())
	{
		// prepare configuration variable
		$data = $this->_prepare_get($data);
		$datagrid = NULL;
		
		// output template for this method
		$output = array (
			'html' => array (
				'datagrid' => '',
				'pagination' => ''
			),
			'data' => array (),
			'total_rows' => 0
		);
		
		// show 404 if access to method is revoke
		if ( ! $data['is_accessible'] && !! $this->data['enable_get'])
		{
			return $this->_callback_404();
		}
		
		$model = $data['model'];
		$method = $data['method'];
		
		if ( !! property_exists($this->CI, $model))
		{
			if ( !! method_exists($this->CI->{$model}, $method))
			{
				// get data from method
				$datagrid = $this->CI->{$model}->{$method}(
					$data['limit'], 
					$data['offset']
				);
				
				$datagrid = $this->_args_to_array(
					$datagrid, 
					array('data', 'total_rows', 'header')
				);
				
				$output['data'] = $datagrid['data'];
				$output['total_rows'] = $datagrid['total_rows'];
				
				// clear table & set table header
				$this->CI->table->clear();
				if ( isset($datagrid['header']) && is_array($datagrid['header']))
				{
					$data['header'] = $datagrid['header'];
					$this->CI->table->set_heading($data['header']);	
				}
				
				// define pagination configuration
				$config = array (
					'base_url' => $data['base_url'],
					'total_rows' => $output['total_rows'],
					'per_page' => $data['limit'],
					'cur_page' => $data['offset'],
					'suffix_url' => $data['suffix_url']
				);
				
				// group pagination configuration & template
				$config = array_merge($config, $data['pagination_template']);
				
				$this->CI->pagination->initialize($config);
				
				// generate table & pagination links
				$output['html']['datagrid'] = $this->CI->table->generate($output['data']);
				$output['html']['pagination'] = $this->CI->pagination->create_links();
			}
			else 
			{
				// method is not available
				log_message('error', 'CRUD: cannot locate method under Application model class');
				return $this->_callback_404();
			}
		}
		else 
		{
			// model is not available
			log_message('error', 'CRUD: cannot locate Application model class');
			return $this->_callback_404();
		}
		
		// extends output to global var
		$this->output = $output;
		
		
		if ($this->data['segment_xhr'] > 0 && $this->CI->uri->segment($this->data['segment_xhr'], '') == 'xhr' && !! method_exists($this->CI, $data['callback_xhr']))
		{
			// output as an XHR callback
			$this->CI->{$data['callback_xhr']}($output['html'], $output['data'], $output['total_rows']);
		}
		elseif ( !! method_exists($this->CI, $data['callback']))
		{
			// output to a method in Controller
			$this->CI->{$data['callback']}($output['html'], $output['data'], $output['total_rows']);
		}
		elseif ( trim($data['view']) !== '')
		{
			// output using CRUD viewer
			$this->_callback_viewer($output['html'], $data['output'], $data['view']);
		}
		else 
		{
			// return the data
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
		// try to set form template when included
		$form_template = $data['form_template'];
		if (is_array($form_template) && count($form_template))
		{
			$this->CI->form->set_template($form_template);
		}
		
		// stop processing if method access to off
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
	
	/**
	 * CRUD function for Delete
	 * 
	 * @access public
	 * @param object $data [optional]
	 * @return 
	 */
	function remove($data = array())
	{
		// prepare configuration
		$data = $this->_prepare_remove($data);
		$model = $data['model'];
		$method = $data['method'];
		
		// default response value
		$output = array (
			'response' => $this->default_response
		);
		
		// disable access: useful when user doesn't have ACL access
		if ( ! $data['is_accessible'] && !! $this->data['enabled_remove'])
		{
			return $this->_callback_404();
		}
		
		if ( !! property_exists($this->CI, $model))
		{
			$response = $output['response'];
			
			if ( !! method_exists($this->CI->{$model}, $method))
			{
				// get return value from delete method
				// response should be based from $this->default_response
				$response = $output['response'] = $this->CI->{$model}->{$method}($data['id'], $response);
			}
			else 
			{
				log_message('error', 'CRUD: cannot locate method under Application model class');
				return $this->_callback_404();
			}
			
			// if action is successful and redirect automatically
			if ($response['success'] === TRUE && trim($response['redirect']) !== '')
			{
				redirect($response['redirect']);
			}
		}
		else 
		{
			log_message('error', 'CRUD: cannot locate Application model class');
			return $this->_callback_404();
		}
		
		$callback = $data['callback'];
		$view = $data['view'];
		$xhr = $data['callback_xhr'];
 		
		if ( trim($view) !== '')
		{
			// view file is set, try to initiate
			$this->_callback_viewer(array (), $data['output'], $view);
		}
		elseif ($this->data['segment_xhr'] > 0 && $this->CI->uri->segment($this->data['segment_xhr'], '') == 'xhr' && !! method_exists($this->CI, $xhr))
		{
			// differentiate XHR/Ajax request
			$this->CI->{$xhr}($output['response']);
		}
		elseif ( !! method_exists($this->CI, $callback))
		{
			// 
			$this->CI->{$callback}($output['response']);
		}
		
		else 
		{
			// return output to allow full customization
			return $output;
		}
	}
	
	/**
	 * Prepare configuration from $this->get (Retrieve)
	 * 
	 * @access private
	 * @param object $data
	 * @return 
	 */
	function _prepare_get($data)
	{
		// set default model to 'model', unless specified otherwise
		$model = 'model';
		if ( trim($this->data['model']) !== '')
		{
			$model = $this->data['model'];
		}
		
		// default configuration array
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
			'pagination_template' => array(),
			'is_accessible' => TRUE
		);
		
		// using 'segment_id' to detect offset for pagination
		if ( ! isset($data['offset']) && $this->data['segment_id'] > 0)
		{
			$data['offset'] = $this->CI->uri->segment($this->data['segment_id'], 0);
		}
		
		return array_merge($default, $data);
	}
	
	/**
	 * Prepare configuration for modify (create, update & retrieve single)
	 * 
	 * @access private
	 * @param object $data
	 * @return 
	 */
	function _prepare_set($data)
	{
		// set default model to 'model', unless specified otherwise
		$model = 'model';
		if ( trim($this->data['model']) !== '')
		{
			$model = $this->data['model'];
		}
		
		// default configuration array
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
		
		// using 'segment_id' to detect data identity (only support integer)
		if ( ! isset($data['id']) && $this->data['segment_id'] > 0)
		{
			$data['id'] = $this->CI->uri->segment($this->data['segment_id'], 0);
		}
		
		return array_merge($default, $data);
	}
	
	/**
	 * Prepare configuration for remove (delete)
	 * 
	 * @access private
	 * @param object $data
	 * @return 
	 */
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
	
	/**
	 * Determine source of data from Model; Array, Method or Property
	 * 
	 * @access private
	 * @param object $data
	 * @param object $prefix
	 * @return 
	 */
	function _organizer($data, $prefix)
	{
		$output = $data[$prefix];
		$model = $data['model'];
		
		// $output should be an array, otherwise assume it referring 
		// to either a method or property under model
		if ( !! is_string($output) && trim($output) !== '')
		{
			if ( !!  method_exists($this->CI->{$model}, $output))
			{
				// get the return value from method under model
				$output = $this->CI->{$model}->{$output}($data['id']);
			}
			elseif ( !! property_exists($this->CI->{$model}, $output))
			{
				// get the value from property under model
				$output = $this->CI->{$model}->{$output};
			}
		}
		
		if ( !is_array($output))
		{
			// to be save return an empty array
			$output = array ();
		}
		
		return $output;
	}
	
	/**
	 * Prepare Error output to browser
	 * 
	 * @access private
	 * @return 
	 */
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
				// Using Template for CI: $this->ui
				$this->CI->ui->set_title('Module not accessible');
				$this->CI->ui->view($this->data['404']);
				$this->CI->ui->render();
			}
			else 
			{
				// Using CI default template
				$this->CI->load->view($this->data['404']);
			}
		}
	}
	
	/**
	 * Prepare CRUD output to browser
	 * 
	 * @access private
	 * @param object $scaffold [optional]
	 * @param object $output [optional]
	 * @param object $view [optional]
	 * @return 
	 */
	function _callback_viewer($scaffold = array (), $output = array (), $view = '')
	{
		$output = array_merge($output, $scaffold);
		
		// if Template for CI is loaded: $this->ui
		if ( !! property_exists($this->CI, 'ui')) 
		{
			// Automatically set <title> if available
			$title = $output['title'];
			if (isset($title) && trim($title) !== '')
			{
				$this->CI->ui->set_title($title);
			}
			
			$this->CI->ui->view($view, $output);
			$this->CI->ui->render();
		}
		else 
		{
			// Using CI default template
			$this->CI->load->view($view, $output);
		}
	}
	
	function _args_to_array($args = array(), $option = array (), $offset = 1)
	{
		$output = array ();
		
		if (count($args) == $offset)
		{
			foreach ($option as $key => $val)
			{
				if (isset($args[$key]))
				{
					$output[$val] = $args[$key];
				}
			}
		}
		else 
		{
			$output = $args;
		}
		
		return $output;
	}
}
