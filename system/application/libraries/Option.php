<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Option
{
	var $CI 		= NULL;
	var $enabled	= FALSE;
	var $data		= array();
	var $config 	= array();
	
	function Option()
	{
		$this->CI =& get_instance();
		$this->CI->option = $this;
		
		$this->CI->config->load('application', TRUE);
		$this->config = $this->CI->config->item('option', 'application');
		
		$this->_is_enable();
		$this->_cache_all();
	}
	
	function _is_enable() 
	{
		$test = array ('table', 'attribute', 'value');
		$invalid = FALSE;
		
		if ($this->config['enable'] === TRUE)
		{
			foreach ($test as $value)
			{
				if (trim($this->config[$value]) === '') 
				{
					$invalid = TRUE;	
				}
			}
			
			$this->enabled = ($invalid === FALSE ? TRUE : FALSE);
		}
		else 
		{
			$this->enabled = FALSE;
		}
	}
	
	function _cache_all()
	{
		if ($this->enabled === TRUE) 
		{
			$this->CI->db->select($this->config['attribute']);
			$this->CI->db->select($this->config['value']);
			$this->CI->db->from($this->config['table']);
			$query = $this->CI->db->get();
			
			foreach ($query->result_array() as $row) 
			{
				$this->data[$row[$this->config['attribute']]] = $row[$this->config['value']];	
			}
		}
		
	}
	function get($name = '')
	{
		if ( ! isset($this->data[$name])) 
		{
			return FALSE;
		}
		else 
		{
			return $this->data[$name];
		}
	}
	function update($name = '', $value = '')
	{
		$data = array();
		
		if ($this->enabled === TRUE && trim($name) !== '') 
		{
			$data[$this->config['value']] = $value;
			
			
			if ( ! isset($this->data[$name])) 
			{
				$data[$this->config['attribute']] = $name;
				
				$this->CI->db->insert($this->config['table'], $data);
			}
			else 
			{
				$this->CI->db->where($this->config['attribute'], $name);
				$this->CI->db->update($this->config['table'], $data);
			}
			
			$this->data[$name] = $value;
		}
	}
	function delete($name = '')
	{
		if ($this->enabled === TRUE && trim($name) !== '') 
		{
			$this->CI->db->where($this->config['attribute'], $name);
			$this->CI->db->delete($this->config['table']);
			
			$this->data[$name] = FALSE;
		}
	}
}
