<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Authentication
{
    private $DB 		= NULL;
	private $CI_input	= NULL;
	private $_test 		= array ('id', 'name', 'pass', 'role', 'status');
	private $_optional 	= array ('fullname', 'email');
    public $member 		= array (
	    'id' => 0,
	    'name' => 'guest',
	    'fullname' => '',
	    'email' => '',
	    'role' => 0,
	    'status' => 0
    );
	private $_config 	= array();
	
    public function Authentication()
    {
        $CI =& get_instance();
        
		$CI->config->load('application', TRUE);
		$this->_config = $CI->config->item('auth', 'application');
		
		$this->DB =& $CI->db;
		$this->CI_input =& $CI->input;
		
		$this->_generate();
		
		$CI->auth = $this->member;
        $CI->authentication = $this;
	}
	
	private function _generate()
	{
		$has_session = FALSE;
		$config = $this->_config;
		
        if ($this->CI_input->cookie($config['cookie']) && $config['enable'] === TRUE) {
			
            $cookies = html_entity_decode($this->CI_input->cookie($config['cookie'], TRUE));
            $cookie = explode( "|", $cookies );

            if ($cookie[2] > 0) {
				$query = $this->_generate_query($cookie);
				
				if ($query->num_rows() > 0) {
					$row = $query->row_array();
					
					$secret = $row[$config['column']['name']];
					$secret .= $row[$config['column']['pass']];
					
					if ($cookie[1] == md5($secret)) {
						foreach ($this->_test as $value) {
							$key = $config['column'][$value];
							
							if (isset($row[$key]) && trim($row[$key]) !== '') {
								$this->member[$value] = $row[$key];
							}
						}
						
						foreach ($this->_optional as $value) {
							$key = $config['column'][$value];
							
							if (isset($row[$key]) && trim($row[$key]) !== '') {
								$this->member[$value] = $row[$key];
							}
						}
						
						$has_session = TRUE;
					}
				}
				else {
					log_message('debug', 'No user authentication found');
				}
            }
        }

        if ($has_session === FALSE) {
			$this->register();
        }
    }
	
	private function _generate_query($cookie = array())
	{
		$invalid = FALSE;
		$config = $this->_config;
		
		foreach ($this->_test as $value) {
			if (trim($config['column'][ $value ]) === '') {
				$invalid = TRUE;
			}
			else {
				$this->DB->select($config['column'][$value]);
			}
		}
		
		foreach ($this->_optional as $value) {
			if (trim($config['column'][ $value ]) !== '') {
				$this->DB->select($config['column'][$value]);
			}
		}
		
		if ($invalid === FALSE) {
			if (trim($config['table_meta']) !== '' && trim($config['column']['key']) !== '') {
				$this->DB->join(
					$config['table_meta'],
					$config['column']['key'] . '=' . $config['column']['id'], 
					'left'
				);
			}
			
			$this->DB->where($config['column']['id'], $cookie[0]);
			$this->DB->where($config['column']['role'], $cookie[2]);
			$this->DB->limit(1);
			$this->DB->from($config['table']);
			
			return $this->DB->get();
		}
		else {
			return NULL;
		}
	}
	
	public function register($id = 0, $name = 'guest', $password = '', $role = 0, $remember_me = FALSE)
    {
    	$value = $id . "|" . md5($name . $password) . "|" . $role;
        
		$cookie = array (
	        'name' => $this->_config['cookie'],
	        'value' => $value,
	        'expire' => $this->_cookie_timeout($remember_me)
        );

        set_cookie($cookie);
    }
    
	public function remove()
    {
        delete_cookie($this->_config['cookie']);
    }
	
	private function _cookie_timeout($remember_me = FALSE)
	{
		if ($this->_config['expire'] > 0 && ! $remember_me) {
			return (int)$this->_config['expire'] + time();
		}
		else {
			return 0;
		}
	}
}
