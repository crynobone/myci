<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CRUD Generator for CodeIgniter
 *
 * PHP version 5
 *
 * @category  CodeIgniter
 * @package   CRUD CI
 * @author    Mior Muhammad Zaki (hello@crynobone.com)
 * @version   0.1.1
 * Copyright (c) 2009 Mior Muhammad Zaki  (http://crynobone.com)
 * Licensed under the MIT.
*/

class CRUD {
	// CI Singleton
	private $CI = NULL;
	
	private $_id = 0;
	private $_type = 'get';
	private $_format = 'http';
	
	// set default modify/delete response
	private $_default_response = array (
		'success' => FALSE,
		'redirect' => '',
		'error' => '',
		'data' => array ()
	);
	
	// Allow output to be access
	public $data = array ();
	
	// set global configuration variables for CRUD
	public $config = array (
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
		'404' => '',
		'enable_ui' => TRUE,
		'auto_render' => TRUE
	);
	
	/**
	 * Constructor
	 * 
	 * @access		public
	 * @return 		void
	 */
	public function __construct()
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
	public function initialize($config = array ())
	{
		// extends configurator
		$this->vars($config);
		
		// configure based on uri segment, enable autoloading via _remap method
		$segment = $this->_get_access();
		
		// set all possible uri segment value
		$is_get = array ('get', 'index', '');
		$is_set = array ('set', 'modify', 'write');
		$is_get_one = array ('get_one', 'detail');
		$is_remove = array ('delete', 'remove');
		$send_to = '';
		
		// test segment
		if (isset($segment)) {
			$send_to = (in_array($segment, $is_get) ? 'get' : $send_to);
			$send_to = (in_array($segment, $is_set) ? 'set' : $send_to);
			$send_to = (in_array($segment, $is_remove) ? 'remove' : $send_to);
			$send_to = (in_array($segment, $is_get_one) ? 'get_one' : $send_to);
			
			if ($send_to != '' ) {
				// method found, so send to related method
				$this->generate($send_to);
			}
			else {
				if ( !! method_exists($this->CI, $segment)) {
					// method not found but Controller contain this method
					$this->CI->{$segment}();
				}
				else {
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
	
	public function vars($config = array ())
	{
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * Generate the CRUD
	 * 
	 * @param object $type [optional]
	 * @param object $option [optional]
	 * @return 
	 */
	public function generate($type = 'get')
	{
		$allowed = array ('get', 'get_one', 'set', 'remove');
		$type_data = $type = trim(strtolower($type));
		
		if ($type == 'get_one') {
			$type_data = 'set';
		}
		
		// first we need to check whether it's pointing to valid method
		if ( !! in_array($type, $allowed)) {
			$this->{$type}($this->config[$type_data]);
		}
		else {
			log_message('error', 'CRUD: Unable to determine request ' . $type);
			
			// 404 using CRUD callback
			$this->_callback_404();
		}
	}
	
	/**
	 * 
	 * @param object $data [optional]
	 * @access public
	 * @return 
	 */
	public function get($config = array ())
	{
		// prepare configuration variable
		$config = $this->_prepare_get($config);
		
		$datagrid = array ();
		
		$this->_set_type('set');
		
		// output template for this method
		$data = array (
			'output' => array (
				'datagrid' => '',
				'records' => FALSE,
				'pagination' => ''
			),
			'records' => FALSE,
			'total_rows' => 0
		);
		
		// try to set table template when included
		$template = $config['table_template'];
		if (is_array($template) && count($template)) {
			$this->CI->table->set_template($template);
		}
		
		// show 404 if access to method is revoke
		if ( ! $config['is_accessible'] && !! $this->config['enable_get']) {
			return $this->_callback_404();
		}
		
		$model = $config['model'];
		$method = $config['method'];
		
		if ( !! property_exists($this->CI, $model)) {
			if ( !! method_exists($this->CI->{$model}, $method)) {
				// get data from method
				$grid = $this->CI->{$model}->{$method}(
					$config['limit'], 
					$config['offset'],
					array (
						'header' => array(),
						'total_rows' => 0,
						'data' => array (),
						'cols' => array (),
						'records' => FALSE
					)
				);
				
				$grid = $this->_args_to_array(
					$grid, 
					array('data', 'total_rows', 'header', 'cols', 'records')
				);
				
				$datagrid = $grid['data'];
				$data['total_rows'] = $grid['total_rows'];
				
				if ($config['enable_table'] === TRUE) {
					$header = $config['header'];
					$cols = $config['cols'];
					
					if ( isset($grid['header']) && is_array($grid['header'])) {
						$header = $grid['header'];
					}
					
					if ( isset($grid['cols']) && is_array($grid['cols'])) {
						$cols = $grid['cols'];
					}
					
					// clear table & set table
					$this->CI->table->clear();
					$this->CI->table->set_heading($header);
					$this->CI->table->set_cols($cols);
					
					// set table data
					$data['output']['datagrid'] = $this->CI->table->generate($datagrid);
				}
				
				// define pagination configuration
				$pagination_config = array (
					'base_url' => $config['base_url'],
					'total_rows' => $data['total_rows'],
					'per_page' => $config['limit'],
					'cur_page' => $config['offset'],
					'suffix_url' => $config['suffix_url']
				);
				
				// group pagination configuration & template
				$pagination_config = array_merge($pagination_config, $config['pagination_template']);
				
				// generate pagination links
				$this->CI->pagination->initialize($pagination_config);
				$data['output']['pagination'] = $this->CI->pagination->create_links();
				
				// paste certain information for additional use
				$data['records'] = $data['output']['records'] = $grid['records'];
			}
			else {
				// method is not available
				log_message('error', 'CRUD: cannot locate method under Application model class');
				return $this->_callback_404();
			}
		}
		else {
			// model is not available
			log_message('error', 'CRUD: cannot locate Application model class');
			return $this->_callback_404();
		}
		
		// extends output to global var
		$this->data = $data;
		
		$callback_xhr = $config['callback_xhr'];
		$callback = $config['callback'];
		$view = $config['view'];
		
		if ($this->is_format_xhr() && !! method_exists($this->CI, $callback_xhr)) {
			// output as an XHR callback
			$this->CI->{$callback_xhr}(
				$data['output'], 
				$data['records'], 
				$data['total_rows']
			);
		}
		elseif ( !! method_exists($this->CI, $callback)) {
			// output to a method in Controller
			$this->CI->{$callback}(
				$data['output'], 
				$data['records'], 
				$data['total_rows']
			);
		}
		elseif (trim($view) !== '') {
			// output using CRUD viewer
			$this->_callback_viewer($data['output'], $config);
		}
		else {
			// return the data
			return $data;
		}
		
	}
	
	public function get_one($config = array())
	{
		return $this->set($config, FALSE);
	}
	
	public function form($config = array ())
	{
		return $this->set($config, TRUE);
	}
	
	public function set($config = array(), $is_form = TRUE)
	{
		$config = $this->_prepare_set($config);
		
		$this->_set_id($config['id']);
		$this->_set_type( !$is_form ? 'get_one' : 'set');
		
		$data = array (
			'output' => array (
				'form' => '',
				'form_open' => '',
				'form_close' => '',
				'datagrid' => '',
				'fields' => array (),
				'error' => '',
				'value' => array ()
			), 
			'result' => FALSE,
			'response' => $this->_default_response
		);
		
		if ( !! $is_form) {
			$data['output']['form_open'] = form_open($config['action']);
			
			if ($config['multipart'] === TRUE) {
				$data['output']['form_open']= form_open_multipart($config['action']);
			}
			
			$data['output']['form_close'] = form_close();
		}
		
		// try to set form template when included
		$template = $config['form_template'];
		
		if (is_array($template) && count($template)) {
			$this->CI->form->set_template($template);
		}
		
		// stop processing if method access to off
		if ( ! $config['is_accessible'] || ! $this->config['enable_set']) {
			return $this->_callback_404();
		}
		
		$model = $config['model'];
		$method = $config['method'];
		
		if ( !! property_exists($this->CI, $model)) {
			$config['fields'] = $this->_organizer($config, 'fields');
			$config['data'] = $this->_organizer($config, 'data');
			
			if (is_array($config['fields']) && count($config['fields']) > 0) {
				$data['output']['form'] = $this->CI->form->generate(
					$config['fields'], 
					$config['prefix'], 
					$config['data'], 
					$is_form
				);
				
				$data['output']['fields'] = $this->CI->form->output[$config['prefix']];
				$data['output']['value'] =  $this->CI->form->value[$config['prefix']];
				
				if ( !! $is_form) {
					$data['output']['datagrid'] = $data['output']['form_open'];
					$data['output']['datagrid'] .= $data['output']['form'];
					$data['output']['datagrid'] .= $data['output']['form_close'];
				}
				else {
					$data['output']['datagrid'] = $data['output']['form'];
				}
				
				if ( !! $this->CI->form->run($config['prefix'])) {
					$data['result'] = $result = $this->CI->form->result($config['prefix']);
					
					if ( !! method_exists($this->CI->{$model}, $method))
					{
						$data['response'] = $this->CI->{$model}->{$method}($result, $this->_default_response);
					}
					else 
					{
						log_message('error', 'CRUD: cannot locate method under Application model class');
						return $this->_callback_404();
					}
					
					if ($data['response']['success'] === TRUE && trim($data['response']['redirect']) !== '')
					{
						redirect($data['response']['redirect']);
					} 						
					elseif ($data['response']['success'] === FALSE) 
					{
						$data['output']['error'] = sprintf(
							'<%s class="%s">%s</%s>',
							$this->CI->form->template['error'],
							$this->CI->form->template['error_class'],
							$data['response']['error'],
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
		
		// extends output to global var
		$this->data = $data;
		
		$callback = $config['callback'];
		$callback_xhr = $config['callback_xhr'];
		$view = $config['view'];
		
		if ($this->is_format_xhr() && !! method_exists($this->CI, $callback_xhr))
		{
			// output as an XHR callback
			$this->CI->{$callback_xhr}($data['output'], $data['response']);
		}
		elseif ( !! method_exists($this->CI, $callback))
		{
			// output to a method in Controller
			$this->CI->{$callback}($data['output'], $data['response']);
		}
		elseif ( trim($view) !== '')
		{
			// output using CRUD viewer
			$this->_callback_viewer($data['output'], $config);
		}
		else 
		{
			// return the data
			return $data;
		}
		
	}
	
	/**
	 * CRUD function for Delete
	 * 
	 * @access public
	 * @param object $data [optional]
	 * @return 
	 */
	public function remove($config = array())
	{
		// prepare configuration
		$config = $this->_prepare_remove($config);
		
		$model = $config['model'];
		$method = $config['method'];
		
		$this->_set_id($config['id']);
		$this->_set_type('remove');
		
		// default response value
		$data = array (
			'response' => $this->_default_response
		);
		
		// disable access: useful when user doesn't have ACL access
		if ( ! $config['is_accessible'] && !! $this->config['enabled_remove'])
		{
			return $this->_callback_404();
		}
		
		if ( !! property_exists($this->CI, $model))
		{
			$response = $data['response'];
			
			if ( !! method_exists($this->CI->{$model}, $method))
			{
				// get return value from delete method
				// response should be based from $this->default_response
				$response = $data['response'] = $this->CI->{$model}->{$method}($config['id'], $response);
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
		
		// extends output to global var
		$this->data = $data;
		
		$callback = $config['callback'];
		$callback_xhr = $config['callback_xhr'];
		$view = $config['view'];
		
		if ($this->is_format_xhr() && !! method_exists($this->CI, $callback_xhr))
		{
			// output as an XHR callback
			$this->CI->{$callback_xhr}($data['response']);
		}
		elseif ( !! method_exists($this->CI, $callback))
		{
			// output to a method in Controller
			$this->CI->{$callback}($data['response']);
		}
		elseif ( trim($view) !== '')
		{
			// output using CRUD viewer
			$this->_callback_viewer($data['response'], $config);
		}
		else 
		{
			// return the data
			return $data;
		}
 		
	}
	
	/**
	 * Prepare configuration from $this->get (Retrieve)
	 * 
	 * @access private
	 * @param object $data
	 * @return 
	 */
	private function _prepare_get($config)
	{
		// set default model to 'model', unless specified otherwise
		$model = 'model';
		
		if ( trim($this->config['model']) !== '') {
			$model = $this->config['model'];
		}
		
		// default configuration array
		$default = array (
			'model' => $model,
			'method' => 'get',
			'callback' => '',
			'callback_xhr' => '',
			'limit' => 30,
			'offset' => 0,
			'base_url' => current_url(),
			'suffix_url' => '',
			'enable_ui' => $this->config['enable_ui'],
			'output' => array (),
			'view' => '',
			'header' => array (),
			'cols' => array (),
			'no_record' => '',
			'enable_table' => TRUE,
			'table_template' => array (),
			'pagination_template' => array(),
			'is_accessible' => TRUE
		);
		
		// using 'segment_id' to detect offset for pagination
		if ( ! isset($config['offset']) && $this->config['segment_id'] > 0) {
			$config['offset'] = $this->CI->uri->segment($this->config['segment_id'], 0);
		}
		
		return array_merge($default, $config);
	}
	
	/**
	 * Prepare configuration for modify (create, update & retrieve single)
	 * 
	 * @access private
	 * @param object $data
	 * @return 
	 */
	private function _prepare_set($config)
	{
		// set default model to 'model', unless specified otherwise
		$model = 'model';
		
		if ( trim($this->config['model']) !== '') {
			$model = $this->config['model'];
		}
		
		// default configuration array
		$default = array (
			'id' => 0,
			'model' => $model,
			'method' => 'update',
			'callback' => '',
			'callback_xhr' => '',
			'prefix' => 'default',
			'form_template' => array(),
			'action' => current_url(),
			'multipart' => FALSE,
			'fields' => 'fields',
			'data' => 'get_one',
			'enable_ui' => $this->config['enable_ui'],
			'output' => array (),
			'view' => '',
			'view_read' => '',
			'view_create' => '',
			'view_update' => '',
			'is_accessible' => TRUE
		);
		
		// using 'segment_id' to detect data identity (only support integer)
		if ( ! isset($config['id']) && $this->config['segment_id'] > 0) {
			$config['id'] = $this->CI->uri->segment($this->config['segment_id'], 0);
		}
		
		return array_merge($default, $config);
	}
	
	/**
	 * Prepare configuration for remove (delete)
	 * 
	 * @access private
	 * @param object $data
	 * @return 
	 */
	private function _prepare_remove($config)
	{
		$model = 'model';
		
		if (trim($this->config['model']) !== '') {
			$model = $this->config['model'];
		}
		
		$default = array (
			'id' => 0,
			'model' => $model,
			'method' => 'remove',
			'callback' => '',
			'callback_xhr' => '',
			'enable_ui' => $this->config['enable_ui'],
			'output' => array (),
			'view' => '',
			'is_accessible' => TRUE
		);
		
		if ( ! isset($config['id']) && $this->config['segment_id'] > 0) {
			$config['id'] = $this->CI->uri->segment($this->config['segment_id'], 0);
		}
		
		return array_merge($default, $config);
	}
	
	/**
	 * Determine source of data from Model; Array, Method or Property
	 * 
	 * @access private
	 * @param object $data
	 * @param object $prefix
	 * @return 
	 */
	private function _organizer($config, $prefix)
	{
		$data = $config[$prefix];
		$model = $config['model'];
		
		// $output should be an array, otherwise assume it referring 
		// to either a method or property under model
		if ( !! is_string($data) && trim($data) !== '') {
			if ( !!  method_exists($this->CI->{$model}, $data)) {
				// get the return value from method under model
				$data = $this->CI->{$model}->{$data}($config['id']);
			}
			elseif ( !! property_exists($this->CI->{$model}, $data)) {
				// get the value from property under model
				$data = $this->CI->{$model}->{$data};
			}
		}
		
		if ( !is_array($data)) {
			// to be save return an empty array
			$data = array ();
		}
		
		return $data;
	}
	
	/**
	 * Prepare Error output to browser
	 * 
	 * @access private
	 * @return 
	 */
	private function _callback_404()
	{
		if ( ! isset($this->config['404']) || trim($this->config['404']) === '') {
			show_404();
		}
		else {
			if ( !! property_exists($this->CI, 'ui') && !! $this->config['enable_ui']) {
				// Using Template for CI: $this->ui
				$this->CI->ui->set_title('Module not accessible');
				$this->CI->ui->view($this->config['404']);
				
				if ( !! $this->config['auto_render']) {
					$this->CI->ui->render();
				}
			}
			else {
				// Using CI default template
				$this->CI->load->view($this->config['404']);
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
	private function _callback_viewer($scaffold = array (), $config = array ())
	{
		$data = array_merge($config['output'], $scaffold);
		list($title, $view, $pre_callback, $post_callback, $enable_ui) = $this->_prepare_viewer($data, $config);
		$data['title'] = $title;
		
		// if Template for CI is loaded: $this->ui
		if ( !! property_exists($this->CI, 'ui') && !! $enable_ui) {
			if (trim($title) !== '') {
				$this->CI->ui->set_title($title);
			}
			
			if ( !! method_exists($this->CI, $pre_callback)) {
				$this->CI->{$pre_callback}($data, $config, $this->_type, $this->_id);
			}
			
			if ($view != FALSE && trim($view) != '') {
				$this->CI->ui->view($view, $data);
			}
			
			if ( !! method_exists($this->CI, $post_callback)) {
				$this->CI->{$post_callback}($data, $config, $this->_type, $this->_id);
			}
				
			if ( !! $this->config['auto_render']) {
				$this->CI->ui->render();
			}
		}
		else {
			// Using CI default template
			
			if ( !! method_exists($this->CI, $pre_callback)) {
				$this->CI->{$pre_callback}($data, $config, $this->_type, $this->_id);
			}
			
			if ($view != FALSE && trim($view) != '') {
				$this->CI->load->view($view, $data);
			}
			
			if ( !! method_exists($this->CI, $post_callback)) {
				$this->CI->{$post_callback}($data, $config, $this->_type, $this->_id);
			}
		}
	}
	
	private function _prepare_viewer($data, $config)
	{
		$title = '';
		$view = $config['view'];
		$pre_callback = '';
		$post_callback = '';
		$enable_ui = $config['enable_ui'];
		
		// Automatically set <title> if available
		if (isset($data['title'])) {
			$title = $data['title'];
		}
		
		if (isset($data['pre_callback'])) {
			$pre_callback = $data['pre_callback'];
		}
		if (isset($data['post_callback'])) {
			$post_callback = $data['post_callback'];
		}
		
		if ($this->_type === 'get_one' && $this->_id > 0) {
			if (isset($data['title_read'])) {
				$title = $data['title_read'];
			}
			
			if (isset($config['view_read']) && ! empty($config['view_read'])) {
				$view = $config['view_read'];
			}
			
			if (isset($data['pre_callback_read'])) {
				$pre_callback = $data['pre_callback_read'];
			}
			
			if (isset($data['post_callback_read'])) {
				$post_callback = $data['post_callback_read'];
			}
			
			if (isset($data['enable_ui_read']) && is_bool($data['enable_ui_read'])) {
				$enable_ui = $data['enable_ui_read'];
			}
		}
		
		if ($this->_type === 'set' && $this->_id >= 0) {
			if (isset($data['title_update'])) {
				$title = $data['title_update'];
			}
			
			if (isset($config['view_update']) && ! empty($config['view_update'])) {
				$view = $config['view_update'];
			}
			
			if (isset($data['pre_callback_update'])) {
				$pre_callback = $data['pre_callback_update'];
			}
			
			if (isset($data['pre_callback_update'])) {
				$pre_callback = $data['pre_callback_update'];
			}
			
			if (isset($data['post_callback_update'])) {
				$post_callback = $data['post_callback_update'];
			}
			
			if (isset($data['enable_ui_update']) && is_bool($data['enable_ui_update'])) {
				$enable_ui = $data['enable_ui_update'];
			}
		}
		
		if ($this->_type === 'set' && $this->_id === 0) {
			if (isset($data['title_create'])) {
				$title = $data['title_create'];
			}
			
			if (isset($config['view_create']) && ! empty($config['view_create'])) {
				$view = $config['view_create'];
			}
			
			if (isset($data['pre_callback_create'])) {
				$pre_callback = $data['pre_callback_create'];
			}
			
			if (isset($data['post_callback_create'])) {
				$post_callback = $data['post_callback_create'];
			}
			
			if (isset($data['enable_ui_create']) && is_bool($data['enable_ui_create'])) {
				$enable_ui = $data['enable_ui_create'];
			}
		}
		
		return array ($title, $view, $pre_callback, $post_callback, $enable_ui);
	}
	
	private function _args_to_array($args = array(), $option = array (), $offset = 1)
	{
		$output = array ();
		
		if (count($args) == $offset) {
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
	
	public function enable_ui()
	{
		$this->config['enable_ui'] = TRUE;
	}
	
	public function disable_ui()
	{
		$this->config['enable_ui'] = FALSE;
	}
	
	public function enable_render()
	{
		$this->config['auto_render'] = TRUE;
	}
	
	public function disable_render()
	{
		$this->config['auto_render'] = FALSE;
	}
	
	public function set_model($model = 'model')
	{
		$this->config['model'] = $model;
	}
	
	public function set_404($path = '')
	{
		$this->config['404'] = $path;
	}
	
	public function set_format()
	{
		return ($this->get_format() == 'xhr' ? 'xhr' : 'http');
	}
	
	public function is_format_http()
	{
		return $this->_format === 'html';
	}
	
	public function is_format_xhr()
	{
		return ! $this->is_format_http();
	}
	
	private function _set_type($type = 'get')
	{
		$this->_type = $type;
	}
	
	private function _set_id($id = 0)
	{
		$this->_id = $id;
	}
	
	private function _get_access()
	{
		return $this->CI->uri->segment($this->config['segment'], '');
	}
	
	private function _get_format()
	{
		return $this->CI->uri->segment($this->config['segment_xhr'], '');
	}
	
	public function set_segment($id = 2)
	{
		$this->config['segment'] = intval($id);
	}
	
	public function set_segment_id($id = 3)
	{
		$this->config['segment_id'] = intval($id);
	}
	
	public function set_segment_xhr($id = 4)
	{
		$this->config['segment_xhr'] = intval($id);
	}
}
