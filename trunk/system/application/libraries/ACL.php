<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class ACL {
	var $CI = NULL;
	var $config = array ();
	var $modules = array ();
	var $access_type = array ('NONE', 'READ', 'WRITE', 'MODIFY', 'DELETE');
	
	function ACL()
	{
		$this->CI =& get_instance();
		
		$this->CI->config->load('application', TRUE);
		$this->config = $this->CI->config->item('acl', 'application');
		
		$this->_fetch_modules();
		
		$this->CI->ACL = $this;
	}
	
	function register_module($module_name = '')
	{
		$id = FALSE;
		
		if (is_string($module_name) && trim($module_name) !== '')
		{
			$this->CI->db->set(array (
				'module_name' => $module_name,
				'module_status' => 1
			));
			$this->CI->db->insert($this->config['table']);
			
			$id = $this->CI->db->insert_id();
		}
		
		return $id;
	}
	
	function register_user ($access = 'READ', $module_id = 0, $user_id = 0, $overwrite = TRUE)
	{
		$id = $this->_get_access_id($access);
		
		$acl_exist = $this->verify($id, $module_id, $user_id);
		
		if ($overwrite === TRUE || $acl_exist === FALSE)
		{
			$this->remove_by_user($module_id, $user_id);
			$this->CI->db->set(array(
				'type' => 1,
				'module_id' => $module_id,
				'access_type' => $id,
				'user_data' => $user_id
			));
			$this->CI->db->insert($this->config['map_table']);
		}
	}
	
	function register_role ($access = 'READ', $module_id = 0, $role_id = 0, $overwrite = TRUE)
	{
		$validity = TRUE;
		$id = $this->_get_access_id($access);
		
		$acl_exist = $this->verify($id, $module_id, NULL, $role_id);
		
		if ($overwrite === TRUE || $acl_exist === FALSE)
		{
			$this->remove_by_role($module_id, $role_id);
			$this->CI->db->set(array(
				'type' => 2,
				'module_id' => $module_id,
				'access_type' => $id,
				'user_data' => $user_id
			));
			$this->CI->db->insert($this->config['map_table']);
		}
	}
	
	function remove_module($module_id = 0)
	{
		$result = FALSE;
		
		if (is_int($module_id) && $module_id > 0)
		{
			$this->CI->db->set(array (
				'module_status' => 0
			));
			$this->CI->db->where('module_id', $module_id);
			$this->CI->db->update($this->config['table']);
			
			$result = TRUE;
			
			$this->_fetch_modules();
		}
		
		return $result;
	}
	
	function remove_user ($module_id = 0, $user_id = 0)
	{
		$this->CI->db->delete($this->config['map_table'], array(
			'type' => 1,
			'module_id' => $module_id,
			'user_data' => $user_id
		));
	}
	
	function remove_role ($module_id = 0, $role_id = 0)
	{
		$this->CI->db->delete($this->config['map_table'], array(
			'type' => 2,
			'module_id' => $module_id,
			'user_data' => $role_id
		));
	}
	
	function verify($access = 'READ', $module_id = 0, $user_id = 0, $role_id = 0) {
		$result = FALSE;
		
		$id = $this->_get_access_id($access);
		
		if (is_int($user_id) && $user_id > 0) 
		{
			$this->CI->db->from($this->config['map_table']);
			$this->CI->db->where(array(
				'type' => 1,
				'user_data' => $user_id,
				'module_id' => $module_id
			));
			$this->CI->db->where('access_type >=', (int)$id);
			
			$query_user = $this->CI->db->get();
			
			if ($query_user->num_rows() > 0)
			{
				$result = TRUE;
			}
		}
		
		if (is_int($role_id) && $role_id > 0) 
		{
			$this->CI->db->from($this->config['map_table']);
			$this->CI->db->where(array(
				'type' => 2,
				'user_data' => $role_id,
				'module_id' => $module_id
			));
			$this->CI->db->where('access_type >=', (int)$id);
			
			$query_role = $this->CI->db->get();
			
			if ($query_role->num_rows() > 0)
			{
				$result = TRUE;
			}
		}
		
		return $result;
	}
	
	function get_modules()
	{
		return $this->modules;
	}
	
	function _get_access_id($access = 'READ')
	{
		if ( ! isset($this->access_type[$access]))
		{
			$id = array_search($access, $this->access_type);
			
			if ($id === FALSE)
			{
				$id = 0;
			}
		}
		else 
		{
			$id = (int)$access;
		}
		
		return $id;
	}
	function _fetch_modules()
	{
		$data = array ();
		
		$this->CI->db->from($this->config['table']);
		$this->CI->db->where('module_status', 1);
		$query = $this->CI->db->get();
		
		foreach ($query->result() as $row)
		{
			$data[$row->module_id] = $row->module_name;
		}
		
		$this->modules = $data;
	}
}
