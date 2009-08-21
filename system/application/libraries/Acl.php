<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Acl {
	var $ci = NULL;
	var $config = array ();
	var $modules = array ();
	var $access_type = array ('NONE', 'READ', 'WRITE', 'MODIFY', 'DELETE');
	
	function Acl()
	{
		$ci =& get_instance();
		
		$this->ci->config->load('application', TRUE);
		$this->config = $this->ci->config->item('acl', 'application');
		
		$this->_fetch_modules();
		
		$ci->acl = $this;
	}
	
	function register_module($module_name = '')
	{
		$id = FALSE;
		
		if (is_string($module_name) && trim($module_name) !== '')
		{
			$this->db->set(array (
				'module_name' => $module_name,
				'module_status' => 1
			));
			$this->db->insert($this->config['table']);
			
			$id = $this->db->insert_id();
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
			$this->db->set(array(
				'type' => 1,
				'module_id' => $module_id,
				'access_id' => $id,
				'user_id' => $user_id
			));
			$this->db->insert($this->config['map_table']);
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
			$this->db->set(array(
				'type' => 2,
				'module_id' => $module_id,
				'access_id' => $id,
				'user_id' => $user_id
			));
			$this->db->insert($this->config['map_table']);
		}
	}
	
	function remove_module($module_id = 0)
	{
		$result = FALSE;
		
		if (is_int($module_id) && $module_id > 0)
		{
			$this->db->set(array (
				'module_status' => 0
			));
			$this->db->where('module_id', $module_id);
			$this->db->update($this->config['table']);
			
			$result = TRUE;
			
			$this->_fetch_modules();
		}
		
		return $result;
	}
	
	function remove_user ($module_id = 0, $user_id = 0)
	{
		$this->db->delete($this->config['map_table'], array(
			'type' => 1,
			'user_id' => $user_id
		));
	}
	
	function remove_role ($module_id = 0, $role_id = 0)
	{
		$this->db->delete($this->config['map_table'], array(
			'type' => 2,
			'user_id' => $role_id
		));
	}
	
	function verify($access = 'READ', $module_id = 0, $user_id = 0, $role_id = 0) {
		$result = FALSE;
		
		$id = $this->_get_access_id($access);
		
		if (is_int($user_id) && $user_id > 0) 
		{
			$this->db->from($this->config['map_table']);
			$this->db->where(array(
				'type' => 1,
				'user_id' => $user_id
			));
			$this->db->where('access_id >=', (int)$id);
			
			$query_user = $this->db->get();
			
			if ($query_user->num_rows() > 0)
			{
				$result = TRUE;
			}
		}
		
		if (is_int($role_id) && $role_id > 0) 
		{
			$this->db->from($this->config['map_table']);
			$this->db->where(array(
				'type' => 2,
				'user_id' => $role_id
			));
			$this->db->where('access_id >=', (int)$id);
			
			$query_role = $this->db->get();
			
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
		$this->db->from($this->config['table']);
		$this->db->where('module_status', 1);
		$query = $this->db->get();
		
		foreach ($query->result() as $row)
		{
			$data[$row->module_id] = $row->module_name;
		}
		
		$this->modules = $data;
	}
}
