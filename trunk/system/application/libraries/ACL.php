<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class ACL {
	private $DB = NULL;
	private $_config = array ();
	private $_modules = array ();
	private $_access_type = array ('NONE', 'READ', 'WRITE', 'MODIFY', 'DELETE');
	
	public function ACL()
	{
		$CI =& get_instance();
		
		$CI->config->load('application', TRUE);
		$this->_config = $CI->config->item('acl', 'application');
		
		$this->_fetch_modules();
		
		$this->DB =& $CI->db;
		$CI->ACL = $this;
	}
	
	public function register_module($name = '')
	{
		$id = FALSE;
		
		if (is_string($name) && trim($name) !== '') {
			$this->DB->set(array (
				'module_name' => $name,
				'module_status' => 1
			));
			$this->DB->insert($this->_config['table']);
			
			$id = $this->DB->insert_id();
		}
		
		return $id;
	}
	
	public function register_user($access = 'READ', $module_id = 0, $user_id = 0, $overwrite = TRUE)
	{
		$id = $this->_get_access_id($access);
		
		$acl_exist = $this->verify($id, $module_id, $user_id);
		
		if ($overwrite === TRUE || $acl_exist === FALSE) {
			$this->remove_by_user($module_id, $user_id);
			$this->DB->set(array(
				'type' => 1,
				'module_id' => $module_id,
				'access_type' => $id,
				'user_data' => $user_id
			));
			$this->DB->insert($this->_config['map_table']);
		}
	}
	
	public function register_role($access = 'READ', $module_id = 0, $role_id = 0, $overwrite = TRUE)
	{
		$validity = TRUE;
		$id = $this->_get_access_id($access);
		
		$acl_exist = $this->verify($id, $module_id, NULL, $role_id);
		
		if ($overwrite === TRUE || $acl_exist === FALSE) {
			$this->remove_by_role($module_id, $role_id);
			$this->DB->set(array(
				'type' => 2,
				'module_id' => $module_id,
				'access_type' => $id,
				'user_data' => $user_id
			));
			$this->DB->insert($this->_config['map_table']);
		}
	}
	
	public function remove_module($module_id = 0)
	{
		$result = FALSE;
		
		if (is_int($module_id) && $module_id > 0) {
			$this->DB->set(array (
				'module_status' => 0
			));
			$this->DB->where('module_id', $module_id);
			$this->DB->update($this->_config['table']);
			
			$result = TRUE;
			
			$this->_fetch_modules();
		}
		
		return $result;
	}
	
	public function remove_user($module_id = 0, $user_id = 0)
	{
		$this->DB->delete($this->_config['map_table'], array(
			'type' => 1,
			'module_id' => $module_id,
			'user_data' => $user_id
		));
	}
	
	public function remove_role($module_id = 0, $role_id = 0)
	{
		$this->DB->delete($this->_config['map_table'], array(
			'type' => 2,
			'module_id' => $module_id,
			'user_data' => $role_id
		));
	}
	
	public function verify($access = 'READ', $module_id = 0, $user_id = 0, $role_id = 0) 
	{
		$result = FALSE;
		
		$id = $this->_get_access_id($access);
		
		if (is_int($user_id) && $user_id > 0) {
			$this->DB->from($this->_config['map_table']);
			$this->DB->where(array(
				'type' => 1,
				'user_data' => $user_id,
				'module_id' => $module_id
			));
			$this->DB->where('access_type >=', (int)$id);
			
			$records_user = $this->DB->get();
			
			if ($records_user->num_rows() > 0) {
				$result = TRUE;
			}
		}
		
		if (is_int($role_id) && $role_id > 0) {
			$this->DB->from($this->_config['map_table']);
			$this->DB->where(array(
				'type' => 2,
				'user_data' => $role_id,
				'module_id' => $module_id
			));
			$this->DB->where('access_type >=', (int)$id);
			
			$records_role = $this->DB->get();
			
			if ($records_role->num_rows() > 0) {
				$result = TRUE;
			}
		}
		
		return $result;
	}
	
	public function get_modules()
	{
		return $this->_modules;
	}
	
	private function _get_access_id($access = 'READ')
	{
		if ( ! isset($this->_access_type[$access])) {
			$id = array_search($access, $this->_access_type);
			
			if ($id === FALSE) {
				$id = 0;
			}
		}
		else {
			$id = (int)$access;
		}
		
		return $id;
	}
	
	private function _fetch_modules()
	{
		$data = array ();
		
		$this->DB->from($this->_config['table']);
		$this->DB->where('module_status', 1);
		
		$records = $this->DB->get();
		
		foreach ($records->result() as $row) {
			$data[$row->module_id] = $row->module_name;
		}
		
		$this->_modules = $data;
	}
}
