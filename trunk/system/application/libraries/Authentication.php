<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Authentication
{
    var $CI 		= NULL;
	var $test 		= array ('id', 'name', 'pass', 'role', 'status');
	var $optional 	= array ('fullname', 'email');
    var $member 	= array (
	    'id' => 0,
	    'name' => 'guest',
	    'fullname' => '',
	    'email' => '',
	    'role' => 0,
	    'status' => 0
    );
	var $config 	= array();
	
    function Authentication()
    {
        $this->CI =& get_instance();
        
		$this->CI->config->load('application', TRUE);
		$this->config = $this->CI->config->item('auth', 'application');
		
		$this->_generate();
	}
	function _generate()
	{
		$has_session = FALSE;
		
        if ($this->CI->input->cookie($this->config['cookie']) && $this->config['enable'] === TRUE)
		{
            $cookies = html_entity_decode($this->CI->input->cookie($this->config['cookie'], TRUE));
            $cookie = explode( "|", $cookies );

            if ($cookie[2] > 0)
			{
				$query = $this->_generate_query($cookie);
				
				if ($query->num_rows() > 0) 
				{
					$row = $query->row_array();
					
					$secret = $row[$this->config['column']['name']];
					$secret .= $row[$this->config['column']['pass']];
					
					if ($cookie[1] == md5($secret))
					{
						foreach ($this->test as $value)
						{
							if (isset($row[$this->config['column'][$value]]) && trim($row[$this->config['column'][$value]]) !== '' ) 
							{
								$this->member[$value] = $row[$this->config['column'][$value]];
							}
						}
						
						foreach ($this->optional as $value)
						{
							if (isset($row[$this->config['column'][$value]]) && trim($row[$this->config['column'][$value]]) !== '') 
							{
								$this->member[$value] = $row[$this->config['column'][$value]];
							}
						}
						
						$has_session = TRUE;
					}
				}
				else 
				{
					log_message('debug', 'No user authentication found');
				}
            }
        }

        if ($has_session === FALSE)
		{
			$this->_create();
        }

        $this->CI->auth = $this->member;
        $this->CI->authentication = $this;
    }
	function _generate_query($cookie = array())
	{
		$invalid = FALSE;
		
		foreach ($this->test as $value)
		{
			if (trim($this->config['column'][ $value ]) === '') 
			{
				$invalid = TRUE;
			}
			else 
			{
				$this->CI->db->select($this->config['column'][$value]);
			}
		}
		
		foreach ($this->optional as $value)
		{
			if (trim($this->config['column'][ $value ]) !== '') 
			{
				$this->CI->db->select($this->config['column'][$value]);
			}
		}
		
		if ($invalid === FALSE) 
		{
			if (trim($this->config['table_meta']) !== '' && trim($this->config['column']['key']) !== '')
			{
				$this->CI->db->join(
					$this->config['table_meta'],
					$this->config['column']['key'] . '=' . $this->config['column']['id'], 
					'left'
				);
			}
			
			$this->CI->db->where($this->config['column']['id'], $cookie[0]);
			$this->CI->db->where($this->config['column']['role'], $cookie[2]);
			$this->CI->db->limit(1);
			$this->CI->db->from($this->config['table']);
			
			return $this->CI->db->get();
		}
		else {
			return NULL;
		}
	}
    function _create()
    {
    	$value = "0|" . md5('guest') . "|0";
        
		$cookie = array (
	        'name' => $this->config['cookie'],
	        'value' => $value,
	        'expire' => $this->_cookie_timeout()
        );

        set_cookie($cookie);
    }
    function register($id = 0, $name = 'guest', $password = '', $role = 0, $remember_me = FALSE)
    {
    	$value = $id . "|" . md5($name . $password) . "|" . $role;
        
		$cookie = array (
	        'name' => $this->config['cookie'],
	        'value' => $value,
	        'expire' => $this->_cookie_timeout($remember_me)
        );

        set_cookie($cookie);
    }
    function remove()
    {
        delete_cookie($this->config['cookie']);
    }
	function _cookie_timeout($remember_me = FALSE)
	{
		if ($this->config['expire'] > 0 && ! $remember_me)
		{
			return (int)$this->config['expire'] + time();
		}
		else 
		{
			return 0;
		}
	}
}
