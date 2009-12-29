<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * WordPress Option for CodeIgniter
 *
 * PHP version 5
 *
 * @category  CodeIgniter
 * @package   Option CI
 * @author    Mior Muhammad Zaki (hello@crynobone.com)
 * @version   0.1
 * Copyright (c) 2009 Mior Muhammad Zaki  (http://crynobone.com)
 * Licensed under the MIT.
*/

class Option
{
	private $DB 		= NULL;
	private $_enabled	= FALSE;
	private $_data		= array();
	private $_config 	= array();
	
	/**
	 * Constructor
	 * 
	 * @access public
	 * @return 
	 */
	public function Option()
	{
		$CI =& get_instance();
		$CI->option = $this;
		
		$CI->config->load('application', TRUE);
		$this->_config = $CI->config->item('option', 'application');
		
		$this->DB =& $CI->db;
		
		$this->_is_enabled();
		$this->_cache_all();
	}
	
	/**
	 * @access private
	 * @return void
	 */
	private function _is_enabled() 
	{
		$test = array ('table', 'attribute', 'value');
		$invalid = FALSE;
		$config = $this->_config;
		
		if ($config['enable'] === TRUE) {
			foreach ($test as $value) {
				if (trim($config[$value]) === '') {
					$invalid = TRUE;	
				}
			}
			
			$this->_enabled = ($invalid === FALSE ? TRUE : FALSE);
		}
		else {
			$this->_enabled = FALSE;
		}
	}
	
	/**
	 * load all values from database
	 * @access private
	 * @return void
	 */
	private function _cache_all()
	{
		$config = $this->_config;
		
		if ($this->_enabled === TRUE) {
			$this->DB->select($config['attribute']);
			$this->DB->select($config['value']);
			$this->DB->from($config['table']);
			$query = $this->DB->get();
			
			foreach ($query->result_array() as $row) {
				$this->_data[$row[$config['attribute']]] = $row[$config['value']];	
			}
		}
		
	}
	
	/**
	 * 
	 * @param string $name [optional]
	 * @return string
	 */
	public function get($name = '')
	{
		if ( ! isset($this->_data[$name])) {
			return FALSE;
		}
		else {
			return $this->_data[$name];
		}
	}
	
	/**
	 * 
	 * @param string $name [optional]
	 * @param string $value [optional]
	 * @return void
	 */
	public function update($name = '', $value = '')
	{
		$data = array();
		$config = $this->_config;
		
		if ($this->_enabled === TRUE && trim($name) !== '') {
			$data[$config['value']] = $value;
			
			if ( ! isset($this->_data[$name])) {
				$data[$config['attribute']] = $name;
				
				$this->DB->insert($config['table'], $data);
			}
			else {
				$this->DB->where($config['attribute'], $name);
				$this->DB->update($config['table'], $data);
			}
			
			$this->_data[$name] = $value;
		}
	}
	
	/**
	 * 
	 * @param string $name [optional]
	 * @return 
	 */
	public function delete($name = '')
	{
		$config = $this->_config;
		
		if ($this->_enabled === TRUE && trim($name) !== '') {
			$this->DB->where($config['attribute'], $name);
			$this->DB->delete($config['table']);
			
			$this->_data[$name] = FALSE;
		}
	}
}
